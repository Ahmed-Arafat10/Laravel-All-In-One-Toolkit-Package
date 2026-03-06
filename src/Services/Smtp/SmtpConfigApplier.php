<?php

namespace AhmedArafat\AllInOne\Services\Smtp;

use Illuminate\Support\Facades\Config;

class SmtpConfigApplier
{
    public static function apply(array $account): void
    {
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $account['host']);
        Config::set('mail.mailers.smtp.port', $account['port']);
        Config::set('mail.mailers.smtp.encryption', $account['encryption']);
        Config::set('mail.mailers.smtp.username', $account['username']);
        Config::set('mail.mailers.smtp.password', $account['password']);
    }
}
