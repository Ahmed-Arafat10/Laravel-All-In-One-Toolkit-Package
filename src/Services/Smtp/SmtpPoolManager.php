<?php

namespace AhmedArafat\AllInOne\Services\Smtp;


use Illuminate\Support\Facades\Cache;

/**
 * Select an SMTP account and track its daily send count in the cache.
 */
class SmtpPoolManager
{
    /**
     * Return Mailpit in testing mode or the first account below its daily limit, or null if exhausted.
     */
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

    /**
     * Increment and return the account's cached send count for today.
     *
     * @return int|false
     */
    public static function markSent(string $accountName)
    {
        $key = self::sentKey($accountName);
        Cache::add($key, 0, now()->endOfDay());
        return Cache::increment($key);
    }

    /**
     * Build the daily cache key for an account name.
     */
    private static function sentKey(string $name): string
    {
        return "smtp5_sent_{$name}_" . now()->format('Y_m_d');
    }
}
