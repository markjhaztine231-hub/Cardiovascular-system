<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDocumentOcr;
use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Artisan command: ocr:reprocess
 *
 * Requeues scans whose OCR never finished: documents left at "pending" because
 * no queue worker was running, "processing" documents whose job was lost, and
 * "failed" documents after the upstream service recovered.
 *
 * Usage:
 *   php artisan ocr:reprocess --dry-run             # list what would be touched
 *   php artisan ocr:reprocess                       # push them back onto the queue
 *   php artisan ocr:reprocess --sync                # run them now, no worker needed
 *   php artisan ocr:reprocess --status=failed --sync
 */
class ReprocessOcr extends Command
{
    protected $signature = 'ocr:reprocess
                            {--status=pending,processing,failed : Comma-separated OCR statuses to pick up.}
                            {--limit=50 : Maximum number of documents to touch.}
                            {--older-than=0 : Only documents created at least this many minutes ago.}
                            {--sync : Run OCR in this process instead of queueing it.}
                            {--dry-run : List matching documents without changing anything.}';

    protected $description = 'Requeue scans stuck in OCR (no worker, stalled job, or failed API call).';

    public function handle(): int
    {
        $statuses = collect(explode(',', (string) $this->option('status')))
            ->map(fn (string $status) => trim($status))
            ->filter()
            ->values()
            ->all();

        $olderThan = max(0, (int) $this->option('older-than'));

        $documents = Document::query()
            ->whereIn('ocr_status', $statuses)
            ->when($olderThan > 0, fn ($query) => $query->where('created_at', '<=', now()->subMinutes($olderThan)))
            ->oldest()
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        if ($documents->isEmpty()) {
            $this->info('No documents match those filters.');

            return self::SUCCESS;
        }

        $touched = 0;
        $skipped = 0;

        foreach ($documents as $document) {
            if (! Storage::disk('public')->exists($document->image_path)) {
                $this->warn(sprintf(
                    'Skipping #%d (%s): the stored image is missing.',
                    $document->id,
                    $document->original_filename ?: $document->image_path,
                ));
                $skipped++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line(sprintf(
                    '#%d %s [%s]',
                    $document->id,
                    $document->original_filename ?: $document->image_path,
                    $document->ocr_status,
                ));
                $touched++;

                continue;
            }

            $document->update(['ocr_status' => 'pending', 'ocr_error' => null]);
            $touched++;

            if (! $this->option('sync')) {
                ProcessDocumentOcr::dispatch($document->id);

                continue;
            }

            try {
                ProcessDocumentOcr::dispatchSync($document->id);
            } catch (Throwable $e) {
                // dispatchSync does not run the job's failed() callback, so do
                // not leave the document stranded at "processing".
                $document->refresh();

                if ($document->ocr_status !== 'completed') {
                    $document->update([
                        'ocr_status' => 'failed',
                        'ocr_error' => Str::limit($e->getMessage(), 1000),
                    ]);
                }

                $this->error(sprintf('Document #%d failed: %s', $document->id, $e->getMessage()));
            }

            $this->line(sprintf(
                '#%d %s -> %s',
                $document->id,
                $document->original_filename ?: $document->image_path,
                $document->fresh()->ocr_status,
            ));
        }

        if ($this->option('dry-run')) {
            $this->info(sprintf('Would reprocess %d document(s).', $touched));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Reprocessed %d document(s)%s.',
            $touched,
            $skipped > 0 ? ', skipped '.$skipped : '',
        ));

        if (! $this->option('sync')) {
            $this->warn('Jobs are queued. A worker must be running: php artisan queue:work --tries=4');
            $this->warn('If you just deployed code or .env changes, restart the worker: php artisan queue:restart');
        }

        return self::SUCCESS;
    }
}
