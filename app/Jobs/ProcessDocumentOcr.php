<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Throwable;

class ProcessDocumentOcr implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public int $documentId) {}

    public function backoff(): array
    {
        return [10, 45, 120];
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
            $response = Http::timeout(config('services.paddle_ocr.timeout'))
                ->connectTimeout(10)
                ->attach('file', $contents, $filename)
                ->post(rtrim(config('services.paddle_ocr.url'), '/').'/ocr');
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

        if ($response->failed()) {
            throw new \RuntimeException('OCR service responded with '.$response->status());
        }

        $payload = $response->json();

        $document->update([
            'ocr_text' => (string) ($payload['text'] ?? ''),
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
