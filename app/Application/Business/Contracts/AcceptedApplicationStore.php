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
     * @param  Closure(string): void|null  $passedGate
     * @return TResult
     */
    public function withReleaseInput(string $businessId, string $applicationId, Closure $operation, ?Closure $passedGate = null): mixed;

    /**
     * Current independent prerequisite facts, available only within the caller's authorized scope.
     *
     * @return array{signatures_retained: bool, quote_current: bool, terms_current: bool}
     */
    public function publicationPrerequisites(string $businessId, string $applicationId): array;
}
