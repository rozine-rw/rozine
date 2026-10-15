<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\Complaints;
use App\Domain\Identity\Complaint;

/**
 * `complaint.open` (BRS v2.0 CMP-R03): an Investor's complaint, in-app, with its reference and the
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
}
