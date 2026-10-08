<?php

declare(strict_types=1);

namespace App\Application\Identity;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * The six-digit code that confirms a new account's email address. Only a keyed digest of the
 * latest code is kept, for ten minutes; a new code replaces the old one. Every check counts as
 * an attempt before the code is compared, so five tries in fifteen minutes is the most anyone
 * gets, however many codes they request or how fast they send them.
 */
final class EmailVerificationCode
{
    public const int EXPIRES_IN_MINUTES = 10;

    private const int MAX_ATTEMPTS = 5;

    private const int ATTEMPT_WINDOW_SECONDS = 900;

    public function __construct(
        private readonly Cache $cache,
        private readonly RateLimiter $limiter,
        private readonly Config $config,
    ) {}

    /** Issue a fresh code for the account, replacing any earlier one, and return it for delivery. */
    public function issue(int $userId): string
    {
        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        $this->cache->put($this->codeKey($userId), $this->digest($userId, $code), now()->addMinutes(self::EXPIRES_IN_MINUTES));

        return $code;
    }

    /**
     * Accept the account's current code once, or return why it was refused.
     *
     * @return 'EMAIL_CODE_TOO_MANY_ATTEMPTS'|'EMAIL_CODE_EXPIRED'|'EMAIL_CODE_INVALID'|null
     */
    public function confirm(int $userId, string $code): ?string
    {
        if ($this->limiter->hit($this->attemptKey($userId), self::ATTEMPT_WINDOW_SECONDS) > self::MAX_ATTEMPTS) {
            return 'EMAIL_CODE_TOO_MANY_ATTEMPTS';
        }

        $expected = $this->cache->get($this->codeKey($userId));

        if (! is_string($expected)) {
            return 'EMAIL_CODE_EXPIRED';
        }

        if (! hash_equals($expected, $this->digest($userId, $code))) {
            return 'EMAIL_CODE_INVALID';
        }

        $this->cache->forget($this->codeKey($userId));
        $this->limiter->clear($this->attemptKey($userId));

        return null;
    }

    private function digest(int $userId, string $code): string
    {
        return hash_hmac('sha256', "email-verification|{$userId}|{$code}", $this->config->string('app.key'));
    }

    private function codeKey(int $userId): string
    {
        return "identity:email-verification-code:{$userId}";
    }

    private function attemptKey(int $userId): string
    {
        return "identity:email-verification-attempts:{$userId}";
    }
}
