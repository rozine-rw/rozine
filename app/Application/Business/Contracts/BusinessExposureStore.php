<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/** @phpstan-import-type Commitment from \App\Domain\Underwriting\AcceptedExposure */
interface BusinessExposureStore
{
    /**
     * Internal source port: the authorized caller retains the Business aggregate lock.
     *
     * @return list<Commitment>
     */
    public function current(string $businessId): array;

    /** Reserve from the retained submission inside the authorized final-signature transaction. */
    public function reserve(string $businessId, string $submissionId): void;
}
