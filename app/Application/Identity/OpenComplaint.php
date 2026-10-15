<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\Complaints;
use App\Domain\Identity\Complaint;

/**
 * `complaint.open` (BRS v2.0 CMP-R03): an Investor's or a Business's complaint, in-app, with its reference and the
 * 4-hour acknowledgement and 48-hour resolution clocks from now.
 */
final readonly class OpenComplaint
{
    public function __construct(private Complaints $complaints) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, string $requestId, mixed $body): array
    {
        return $this->complaints->open($userId, $requestId, Complaint::body($body));
    }

    /**
     * `complaint.open` for a Business the person may view: the complaint belongs to its entity Party.
     *
     * @return array<string, mixed>
     */
    public function forBusiness(int $userId, int $contextRevision, string $businessId, string $requestId, mixed $body): array
    {
        return $this->complaints->openForBusiness($userId, $contextRevision, $businessId, $requestId, Complaint::body($body));
    }
}
