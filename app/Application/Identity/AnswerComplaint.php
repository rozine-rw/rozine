<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\Complaints;
use App\Domain\Identity\Complaint;

/**
 * `complaint.answer` and `complaint.resolve` (`complaints.answer`): a staff message on a complaint
 * that is not resolved. The first one acknowledges it; the resolution closes it. The customer gets
 * an in-app notice either way.
 */
final readonly class AnswerComplaint
{
    public function __construct(private Complaints $complaints) {}

    /** @return array<string, mixed> */
    public function handle(int $staffId, string $requestId, string $complaintId, mixed $body, bool $resolves): array
    {
        return $this->complaints->answer($staffId, $requestId, $complaintId, Complaint::body($body), $resolves);
    }
}
