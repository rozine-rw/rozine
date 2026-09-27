<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use Closure;

interface AcceptedApplicationStore
{
    /**
     * Internal release input. The caller must hold current Business or staff authorization.
     * Keeps current identity, mandate, evidence, credit, consent and report locks through the effect.
     *
     * @template TResult
     *
     * @param  Closure(array<string, mixed>): TResult  $operation
     * @return TResult
     */
    public function withReleaseInput(string $businessId, string $applicationId, Closure $operation): mixed;
}
