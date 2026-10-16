<?php

declare(strict_types=1);

namespace App\Application\Environment;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Config\Repository;

/**
 * Staging's hourly mail allowance. Checking it and taking a place are one step, held under a lock
 * on the same shared cache the limiter counts in, so two workers sending at once cannot both take
 * the last place. A worker that cannot get the lock in time, or a cache without locks, sends nothing.
 */
final class StagingMailAllowance
{
    private const string KEY = 'staging-mail';

    private const int WAIT_SECONDS = 5;

    public function __construct(
        private readonly Repository $config,
        private readonly CacheFactory $cache,
        private readonly RateLimiter $limiter,
    ) {}

    public function reserve(): bool
    {
        $limit = $this->config->integer('isolation.staging_mail.hourly_limit');
        $store = $this->cache->store($this->config->get('cache.limiter'))->getStore();

        if (! $store instanceof LockProvider) {
            return false;
        }

        try {
            return $store->lock(self::KEY.':reservation', 10)->block(self::WAIT_SECONDS, function () use ($limit): bool {
                if ($this->limiter->tooManyAttempts(self::KEY, $limit)) {
                    return false;
                }

                $this->limiter->hit(self::KEY, 3600);

                return true;
            });
        } catch (LockTimeoutException) {
            return false;
        }
    }
}
