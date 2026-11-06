<?php

declare(strict_types=1);

namespace AhmedArafat\AllInOne\Helpers;

use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
class RateLimiterHelper
{
    /**
     * Apply rate limiting for the given key.
     *
     * If the maximum number of attempts has been exceeded,
     * a 429 (Too Many Requests) exception will be thrown.
     *
     * Example:
     *
     * ```php
     * RateLimiterHelper::apply(
     *     key: 'login:' . $request->ip(),
     *     maxAttempts: 5,
     *     decaySeconds: 60,
     *     message: 'Too many login attempts. Please try again later.'
     * );
     * ```
     *
     * @param string $key Unique rate limit key.
     * @param int $maxAttempts Maximum allowed attempts.
     * @param int $decaySeconds Number of seconds before attempts reset.
     * @param string $message Exception message when the limit is exceeded.
     * @param bool $disabled Skip rate limiting when true.
     *
     * @throws TooManyRequestsHttpException
     */
    public static function apply(
        string $key,
        int $maxAttempts,
        int $decaySeconds,
        string $message = 'Too many requests.',
        bool $disabled = false,
    ): void {
        if ($disabled) {
            return;
        }

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new TooManyRequestsHttpException(
                retryAfter: RateLimiter::availableIn($key),
                message: $message,
                code: 429,
            );
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
