<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Move patient scans off the web-served disk.
 *
 * New uploads already go to the private disk. This command finishes the job
 * for scans uploaded before that change, including orphaned files that are no
 * longer referenced by a document row.
 *
 * Usage:
 *   php artisan documents:move-to-private --dry-run
 *   php artisan documents:move-to-private
 */
class MoveDocumentsToPrivate extends Command
{
    protected $signature = 'documents:move-to-private
                            {--dry-run : List files without moving anything}
                            {--force : Overwrite a file that already exists on the private disk}';

    protected $description = 'Move patient scans from the public disk to private storage.';

    public function handle(): int
    {
        $public = Storage::disk(Document::LEGACY_STORAGE_DISK);
        $private = Storage::disk(Document::STORAGE_DISK);

        $paths = Document::query()
            ->whereNotNull('image_path')
            ->where('image_path', '<>', '')
            ->pluck('image_path')
            ->merge($public->allFiles('documents'))
            ->filter()
            ->unique()
            ->values();

        if ($paths->isEmpty()) {
            $this->info('No scans found on the public disk.');

            return self::SUCCESS;
        }

        $moved = 0;
        $skipped = 0;
        $failed = 0;
        $dryRun = (bool) $this->option('dry-run');

        foreach ($paths as $path) {
            if (! $public->exists($path)) {
                if ($private->exists($path)) {
                    $skipped++;

                    continue;
                }

                $this->warn(sprintf('%s: missing on both disks.', $path));
                $skipped++;

                continue;
            }

            if ($private->exists($path) && ! $this->option('force')) {
                $this->warn(sprintf(
                    '%s: already private; the public copy was left in place (use --force to replace it).',
                    $path,
                ));
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('Would move %s', $path));
                $moved++;

                continue;
            }

            try {
                $stream = $public->readStream($path);

                if (! is_resource($stream)) {
                    throw new RuntimeException('Could not open the public file for reading.');
                }

                try {
                    $private->writeStream($path, $stream);
                } finally {
                    fclose($stream);
                }

                if (! $private->exists($path)) {
                    throw new RuntimeException('The private copy was not written.');
                }

                $public->delete($path);
                $moved++;
            } catch (Throwable $e) {
                $failed++;
                $this->error(sprintf('%s: %s', $path, $e->getMessage()));
            }
        }

        if ($dryRun) {
            $this->info(sprintf('Would move %d scan(s), skip %d.', $moved, $skipped));

            return $failed > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->info(sprintf('Moved %d scan(s), skipped %d, failed %d.', $moved, $skipped, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
