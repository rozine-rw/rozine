<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditLocationStore;
use App\Domain\Operations\CommandRejection;

final class VerifyAuditLocation
{
    public function __construct(private AuditLocationStore $store) {}

    /**
     * Records a verified location. A refusal whose code is in `$explained` is answered as a rejected
     * result, so a form can say what to change; any other refusal is thrown.
     *
     * @param  list<string>  $explained
     * @return array<string, mixed>
     */
    public function handle(int $actorId, string $kind, string $subjectId, int $expectedRevision, string $latitude, string $longitude, int $uncertainty, string $verifiedAt, string $reference, string $reason, string $requestId, array $explained = []): array
    {
        try {
            return $this->store->verify($actorId, $kind, $subjectId, $expectedRevision, $latitude, $longitude, $uncertainty, $verifiedAt, $reference, $reason, $requestId);
        } catch (CommandRejection $rejection) {
            if (! in_array($rejection->reason, $explained, true)) {
                throw $rejection;
            }

            return ['status' => 'rejected', 'code' => $rejection->reason];
        }
    }
}
