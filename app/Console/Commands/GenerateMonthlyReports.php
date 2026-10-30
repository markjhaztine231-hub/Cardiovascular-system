<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MonthlyReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Artisan command: reports:generate-monthly
 *
 * Generates monthly performance statistics for every doctor and persists
 * them to the monthly_reports table.  Defaults to last month so it can
 * be scheduled to run on the 1st of each month after midnight.
 *
 * Usage:
 *   php artisan reports:generate-monthly              # last month (default)
 *   php artisan reports:generate-monthly --month=2026-04  # specific month
 *   php artisan reports:generate-monthly --practice  # also build the practice-wide report
 */
class GenerateMonthlyReports extends Command
{
    protected $signature = 'reports:generate-monthly
                            {--month= : Target month in Y-m format. Defaults to the previous calendar month.}
                            {--practice : Also generate the practice-wide report for the month.}';

    protected $description = 'Auto-generates monthly performance statistics for every doctor.';

    public function handle(): int
    {
        // Resolve target month
        if ($this->option('month')) {
            try {
                $month = Carbon::createFromFormat('Y-m', $this->option('month'))
                    ->startOfMonth()
                    ->format('Y-m');
            } catch (\Exception) {
                $this->error('Invalid --month format. Use Y-m, e.g. 2026-04.');

                return Command::FAILURE;
            }
        } else {
            $month = Carbon::now()->subMonth()->format('Y-m');
        }

        $this->info("Generating monthly reports for: {$month}");

        // The practice-wide report is a separate row in monthly_reports and is
        // opt-in so a scheduled run does not silently start averaging every
        // doctor's activity into a report where only personal reports existed.
        if ($this->option('practice')) {
            try {
                MonthlyReportService::generatePracticeReport($month);
                $this->info('Practice-wide report saved.');
            } catch (\Throwable $e) {
                $this->error('Practice-wide report failed: '.$e->getMessage());
            }
        }

        // Only doctors who actually have a provider profile can own a report;
        // monthly_reports.provider_id is a foreign key to providers. Skipping
        // them here keeps a half-configured account from being reported as a
        // hard failure of the whole batch.
        $doctors = User::where('role', 'doctor')
            ->whereHas('provider')
            ->get(['id', 'name']);

        $skipped = User::where('role', 'doctor')
            ->whereDoesntHave('provider')
            ->count();

        if ($skipped > 0) {
            $this->warn("{$skipped} doctor account(s) have no provider profile and were skipped.");
        }

        if ($doctors->isEmpty()) {
            $this->warn('No doctors with a provider profile found in the database.');

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($doctors->count());
        $bar->start();

        $errors = 0;

        foreach ($doctors as $doctor) {
            try {
                MonthlyReportService::generate($doctor->id, $month);
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->error("Failed for Dr. {$doctor->name} (ID {$doctor->id}): {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $saved = $doctors->count() - $errors;
        $this->info("Done. {$saved}/{$doctors->count()} report(s) saved successfully.");

        if ($errors > 0) {
            $this->warn("{$errors} report(s) failed — check the log for details.");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
