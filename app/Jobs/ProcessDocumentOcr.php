<?php

namespace App\Jobs;

use App\Models\Document;
use App\Support\OcrTextNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProcessDocumentOcr implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    public int $timeout = 240;

    public function __construct(public int $documentId) {}

    /**
     * The hosted OCR API throttles after a burst of jobs, so space the retries
     * out: a rejection is usually transient and clears within a few minutes.
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        $document = Document::find($this->documentId);

        if (! $document) {
            return;
        }

        $document->update(['ocr_status' => 'processing', 'ocr_error' => null]);

        $filename = $document->original_filename ?: basename($document->image_path);

        try {
            $contents = Storage::disk('public')->get($document->image_path);
        } catch (FileNotFoundException) {
            $this->markFailed($document, 'The stored image could not be read.');

            return;
        }

        try {
            $payload = config('services.paddle_ocr.driver') === 'cloud'
                ? $this->runCloudOcr($contents, $filename)
                : $this->runLocalOcr($contents, $filename);
        } catch (ConnectionException $e) {
            // The OCR service being down is transient, so do NOT burn the
            // document: stay "processing" and let the queue retry with
            // backoff. It is only marked failed once the attempts run out.
            $document->update([
                'ocr_error' => Str::limit(sprintf(
                    'OCR service unreachable — retrying (attempt %d of %d)…',
                    $this->attempts(),
                    $this->tries,
                ), 1000),
            ]);

            Log::warning('Document OCR service unreachable', [
                'document_id' => $document->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $document->update([
            'ocr_text' => OcrTextNormalizer::toReadableText((string) ($payload['text'] ?? '')),
            'ocr_confidence' => isset($payload['confidence']) ? (float) $payload['confidence'] : null,
            'ocr_raw' => [
                'lines' => $payload['lines'] ?? [],
                'layout' => $payload['layout'] ?? [],
                'visuals' => $payload['visuals'] ?? [],
                'line_count' => $payload['line_count'] ?? null,
                'engine' => $payload['engine'] ?? null,
                'language' => $payload['language'] ?? null,
                'processing_time_ms' => $payload['processing_time_ms'] ?? null,
            ],
            'ocr_status' => 'completed',
            'ocr_error' => null,
        ]);
    }

    private function runLocalOcr(string $contents, string $filename): array
    {
        $response = Http::timeout(config('services.paddle_ocr.timeout'))
            ->connectTimeout(10)
            ->attach('file', $contents, $filename)
            ->post(rtrim(config('services.paddle_ocr.url'), '/').'/ocr');

        if ($response->failed()) {
            throw new \RuntimeException('OCR service responded with '.$response->status());
        }

        return $response->json();
    }

    /**
     * Async OCR through the hosted PaddleOCR API (paddleocr.aistudio-app.com).
     * Submits a job, polls until it finishes, then folds the resulting markdown
     * pages into the same shape the rest of the app expects.
     */
    private function runCloudOcr(string $contents, string $filename): array
    {
        $cloud = config('services.paddle_ocr.cloud');
        $baseUrl = rtrim((string) $cloud['url'], '/');
        $headers = ['Authorization' => 'bearer '.$cloud['token']];

        $optionalPayload = [
            'useDocOrientationClassify' => true,
            'useDocUnwarping' => true,
            'useChartRecognition' => false,
        ];

        $started = microtime(true);

        $jobResponse = Http::timeout(config('services.paddle_ocr.timeout'))
            ->connectTimeout(10)
            ->withHeaders($headers)
            ->asMultipart()
            ->attach('file', $contents, $filename)
            ->post($baseUrl, [
                'model' => $cloud['model'],
                'optionalPayload' => json_encode($optionalPayload),
            ]);

        if ($jobResponse->failed()) {
            throw new \RuntimeException(sprintf(
                'OCR cloud API rejected the job with %d: %s',
                $jobResponse->status(),
                Str::limit($jobResponse->body(), 500),
            ));
        }

        $jobId = data_get($jobResponse->json(), 'data.jobId');

        if (! is_string($jobId) || $jobId === '') {
            throw new \RuntimeException('OCR cloud API returned no job id: '.Str::limit($jobResponse->body(), 500));
        }

        $deadline = time() + (int) ($cloud['max_wait'] ?? 120);
        $jsonlUrl = null;

        while (true) {
            if (time() >= $deadline) {
                throw new \RuntimeException('OCR cloud job '.$jobId.' exceeded the '.$cloud['max_wait'].'s wait.');
            }

            $stateResponse = Http::timeout(config('services.paddle_ocr.timeout'))
                ->connectTimeout(10)
                ->withHeaders($headers)
                ->get($baseUrl.'/'.$jobId);

            if ($stateResponse->failed()) {
                throw new \RuntimeException(sprintf(
                    'OCR cloud poll failed with %d: %s',
                    $stateResponse->status(),
                    Str::limit($stateResponse->body(), 500),
                ));
            }

            $state = data_get($stateResponse->json(), 'data.state', 'pending');

            if ($state === 'done') {
                $jsonlUrl = data_get($stateResponse->json(), 'data.resultUrl.jsonUrl');

                break;
            }

            if ($state === 'failed') {
                throw new \RuntimeException(
                    'OCR cloud job failed: '.data_get($stateResponse->json(), 'data.errorMsg', 'unknown error')
                );
            }

            sleep(max(1, (int) ($cloud['poll_interval'] ?? 5)));
        }

        if (! is_string($jsonlUrl) || $jsonlUrl === '') {
            throw new \RuntimeException('OCR cloud job completed without a result URL.');
        }

        return $this->collapseCloudMarkdown($jsonlUrl, $started);
    }

    private function collapseCloudMarkdown(string $jsonlUrl, float $started): array
    {
        $response = Http::timeout(config('services.paddle_ocr.timeout'))
            ->connectTimeout(10)
            ->get($jsonlUrl);

        if ($response->failed()) {
            throw new \RuntimeException(sprintf(
                'OCR cloud result download failed with %d: %s',
                $response->status(),
                Str::limit($response->body(), 500),
            ));
        }

        $pages = [];
        $exploded = Str::of($response->body())->explode("\n")->filter(fn ($line) => trim($line) !== '');

        foreach ($exploded as $line) {
            $decoded = json_decode($line, true);
            $results = data_get($decoded, 'result.layoutParsingResults', []);

            foreach ($results as $result) {
                $markdown = (string) data_get($result, 'markdown.text', '');

                if (trim($markdown) !== '') {
                    $pages[] = $markdown;
                }
            }
        }

        $text = implode("\n\n", $pages);

        // The hosted API reports no per-line confidence, so mark it unknown and
        // let requiresReview() flag the scan for a quick human check.
        return [
            'text' => $text,
            'confidence' => null,
            'lines' => [],
            'layout' => [],
            'visuals' => [],
            'line_count' => count($pages),
            'engine' => (string) (config('services.paddle_ocr.cloud.model') ?? 'PaddleOCR-VL-1.6'),
            'language' => null,
            'processing_time_ms' => round((microtime(true) - $started) * 1000, 2),
        ];
    }

    public function failed(?Throwable $e): void
    {
        $document = Document::find($this->documentId);

        if ($document) {
            $this->markFailed($document, $e?->getMessage() ?? 'Unknown OCR failure');
        }
    }

    private function markFailed(Document $document, string $message): void
    {
        $document->update([
            'ocr_status' => 'failed',
            'ocr_error' => Str::limit($message, 1000),
        ]);

        Log::warning('Document OCR failed', [
            'document_id' => $document->id,
            'error' => $message,
        ]);
    }
}
