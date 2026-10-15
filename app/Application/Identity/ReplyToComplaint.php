<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\Complaints;
use App\Domain\Identity\Complaint;

/** `complaint.reply`: the Investor, or a person acting for the Business, adds to one of its own complaints until it is resolved. */
final readonly class ReplyToComplaint
{
    public function __construct(private Complaints $complaints) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, string $requestId, string $complaintId, mixed $body): array
    {
        return $this->complaints->reply($userId, $requestId, $complaintId, Complaint::body($body));
    }

    /** @return array<string, mixed> */
    public function forBusiness(int $userId, int $contextRevision, string $businessId, string $requestId, string $complaintId, mixed $body): array
    {
        return $this->complaints->replyForBusiness($userId, $contextRevision, $businessId, $requestId, $complaintId, Complaint::body($body));
    }
}
