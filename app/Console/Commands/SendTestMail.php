<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTestMail extends Command
{
    /**
     * @var string
     */
    protected $signature = 'send-mail
                            {to : Recipient email address}
                            {--subject=Mailtrap test email}';

    /**
     * @var string
     */
    protected $description = 'Send a test email through the configured mailer (Mailtrap)';

    public function handle(): int
    {
        $to = (string) $this->argument('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid recipient email address.');

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        /** @var array<string, mixed> $config */
        $config = (array) config('mail.mailers.'.$mailer, []);
        $password = $config['password'] ?? null;

        $this->line(sprintf(
            'Mailer: %s (%s:%s, user: %s, password: %s)',
            $mailer,
            $config['host'] ?? '-',
            $config['port'] ?? '-',
            $config['username'] ?? '-',
            blank($password) ? 'EMPTY' : strlen((string) $password).' chars',
        ));

        // Mailtrap answers an empty SASL password with a misleading
        // "501 5.5.4 Syntax: AUTH mechanism" (a wrong password gives 535
        // instead), so surface the real cause before the SMTP exchange.
        if (($config['transport'] ?? null) === 'smtp'
            && filled($config['username'] ?? null)
            && blank($password)) {
            $this->error('The SMTP password is empty.');
            $this->line('Set MAILTRAP_PASSWORD in .env, then run: php artisan config:clear');

            return self::FAILURE;
        }

        try {
            Mail::raw(
                'This is a test email from '.config('app.name')
                    .'. If you received it, Mailtrap is configured correctly.',
                function ($message) use ($to): void {
                    $message->to($to)->subject((string) $this->option('subject'));
                }
            );
        } catch (Throwable $e) {
            $this->error('Mail failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Test email sent to '.$to.' via the "'.$mailer.'" mailer.');

        return self::SUCCESS;
    }
}
