<?php

namespace AhmedArafat\AllInOne\Services\Smtp;

use Illuminate\Support\Facades\Config;

/**
 * Apply an account's connection settings to Laravel's SMTP mailer configuration.
 */
class SmtpConfigApplier
{
    /**
     * Set SMTP host, port, encryption and credentials, clearing credentials for Mailpit.
     *
     * @param array{name: string, host: string, port: int|string, encryption: ?string, username?: ?string, password?: ?string} $account
     */
    public static function apply(array $account): void
    {
        // A mailer URL would override the individual account settings.
        Config::set('mail.mailers.smtp.url', null);

        if ($account['name'] === 'mailpit') {
            Config::set('mail.mailers.smtp.transport', 'smtp');
            Config::set('mail.mailers.smtp.host', $account['host']);
            Config::set('mail.mailers.smtp.port', $account['port']);
            Config::set('mail.mailers.smtp.encryption', $account['encryption']);
            Config::set('mail.mailers.smtp.username', null);
            Config::set('mail.mailers.smtp.password', null);
        } else {
            Config::set('mail.mailers.smtp.transport', 'smtp');
            Config::set('mail.mailers.smtp.host', $account['host']);
            Config::set('mail.mailers.smtp.port', $account['port']);
            Config::set('mail.mailers.smtp.encryption', $account['encryption']);
            Config::set('mail.mailers.smtp.username', $account['username']);
            Config::set('mail.mailers.smtp.password', $account['password']);
        }
    }
}
