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

        $this->info('Test email sent to '.$to.' via the "'.config('mail.default').'" mailer.');

        return self::SUCCESS;
    }
}
