<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wipes every clinical/business record while preserving user accounts.
 *
 * Intended for clearing demo or test data before go-live. Users, auth and
 * queue infrastructure survive so accounts keep working; everything a
 * patient, appointment or consultation generated is removed.
 */
class ResetClinicData extends Command
{
    protected $signature = 'clinic:reset-data
                            {--force : Skip the interactive confirmation}';

    protected $description = 'Delete all clinic data (patients, appointments, records) but keep user accounts.';

    /**
     * Tables that hold clinical/business data. Truncated in one transaction.
     */
    private array $wipe = [
        'appointments',
        'encounters',
        'soap_notes',
        'vital_signs',
        'diagnoses',
        'prescriptions',
        'medication_logs',
        'echocardiograms',
        'treadmill_tests',
        'doppler_studies',
        'documents',
        'manual_drafts',
        'monthly_reports',
        'audit_logs',
        'doctor_schedules',
        'patients',
        'providers',
    ];

    /**
     * Infrastructure/auth tables that must survive the wipe so accounts keep
     * working and the scheduler/queue stay operational.
     */
    private array $keep = [
        'users',
        'migrations',
        'password_reset_tokens',
        'sessions',
        'personal_access_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function handle(): int
    {
        $tables = array_values(array_filter(
            $this->wipe,
            fn (string $table): bool => Schema::hasTable($table),
        ));

        if ($tables === []) {
            $this->warn('Nothing to wipe — no matching tables found.');

            return self::SUCCESS;
        }

        $userCount = DB::table('users')->count();

        $this->warn('About to permanently delete ALL clinic data. User accounts are kept.');
        $this->line('Tables: '.implode(', ', $tables));
        $this->line("User accounts preserved: {$userCount}");

        if (! $this->option('force') && ! $this->confirm('This cannot be undone. Continue?', false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // One statement so PostgreSQL resolves inter-table foreign keys,
            // resetting identity sequences along the way.
            $quoted = array_map(fn (string $t): string => '"'.$t.'"', $tables);
            DB::statement('TRUNCATE TABLE '.implode(', ', $quoted).' RESTART IDENTITY CASCADE');
        } else {
            Schema::disableForeignKeyConstraints();

            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }

            Schema::enableForeignKeyConstraints();
        }

        $this->info('Clinic data cleared. User accounts and infrastructure tables were preserved.');

        return self::SUCCESS;
    }
}
