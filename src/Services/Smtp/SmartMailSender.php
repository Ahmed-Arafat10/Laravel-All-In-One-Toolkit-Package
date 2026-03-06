<?php

namespace AhmedArafat\AllInOne\Services\Smtp;

use Exception;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SmartMailSender
{
    /**
     * @throws Exception
     */
    public static function send(Mailable $mailable, string $to): void
    {
        $lockKey = "mail_lock_" . md5($to . serialize($mailable));
        $lock = Cache::lock($lockKey, 10);
        if (!$lock->get())  return;
        try {
            while ($account = SmtpPoolManager::next(
                config('all-in-one.smtpData.isTesting')
            )) {
                SmtpConfigApplier::apply($account);
                try {
                   Mail::to($to)->send($mailable);
                   $keyCount = SmtpPoolManager::markSent($account['name']);
                    Log::channel('SmartMailSender')->info("SmartMailSender Execution Success", [
                        'mailable' => $mailable::class,
                        'toEmail' => $to,
                        'keyCount' => $keyCount,
                        'smtpUsername' => $account['username']
                    ]);
                    return;
                } catch (Throwable $e) {
                    report($e);
                    continue;
                }
            }
            Log::channel('SmartMailSender')->error("SmartMailSender Execution Failed", [
                'mailable' => $mailable::class,
                'toEmail' => $to
            ]);
            throw new Exception("All SMTP accounts exhausted");
        } finally {
            optional($lock)->release();
        }
    }
}
