<?php

namespace AhmedArafat\AllInOne\Services\Smtp;


use Illuminate\Support\Facades\Cache;

class SmtpPoolManager
{
    public static function next(bool $isTesting = false): ?array
    {
        if ($isTesting) {
            return [
                'name' => 'mailpit',
                'username' => 'mailpit',
                'host' => '127.0.0.1',
                'port' => 1025,
                'encryption' => null,
            ];
        }
        $accounts = config('all-in-one.smtpData.accounts');
        $limit = config('all-in-one.smtpData.dailyLimit');
        foreach ($accounts as $account) {
            $sent = Cache::get(self::sentKey($account['name']), 0);
            if ($sent < $limit) {
                return $account;
            }
        }
        return null;
    }

    public static function markSent(string $accountName)
    {
        $key = self::sentKey($accountName);
        Cache::add($key, 0, now()->endOfDay());
        return Cache::increment($key);
    }

    private static function sentKey(string $name): string
    {
        return "smtp5_sent_{$name}_" . now()->format('Y_m_d');
    }
}
