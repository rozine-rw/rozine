<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\ConsentCatalog;
use Closure;

/** @phpstan-import-type Release from ConsentCatalog */
final class WithCurrentConsent
{
    public function __construct(private ConsentCatalog $catalog) {}

    /**
     * @template TResult
     *
     * @param  Closure(Release|null): TResult  $operation
     * @return TResult
     */
    public function handle(Closure $operation): mixed
    {
        return $this->catalog->withCurrent($operation);
    }
}
