<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\Complaints;
use Carbon\CarbonImmutable;

/**
 * Every complaint with its conversation, for staff holding `complaints.answer` (BRS v2.0 CMP-R03:
 * Compliance, then escalation): unresolved first, newest first. Each says which clock it is past, if
 * any: not acknowledged by its 4-hour mark, or not resolved by its 48-hour mark.
 *
 * @phpstan-type Listed array{
 *     id: string, reference: string, status: 'awaiting'|'answered'|'resolved', opened_at: string, acknowledge_by: string, resolve_by: string,
 *     messages: list<array{id: string, author: 'customer'|'staff', body: string, resolves: bool, at: string}>, party_id: string,
 *     role: 'investor'|'business', customer: string, late: 'acknowledge'|'resolve'|null
 * }
 */
final readonly class BrowseComplaints
{
    public function __construct(private Complaints $complaints, private AuthorizeStaffPermission $staff) {}

    /** @return list<Listed> */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'complaints.answer');
        $now = CarbonImmutable::now('UTC');

        return array_map(fn (array $thread): array => [...$thread, 'late' => match (true) {
            $thread['status'] === 'awaiting' && CarbonImmutable::parse($thread['acknowledge_by'])->lessThanOrEqualTo($now) => 'acknowledge',
            $thread['status'] !== 'resolved' && CarbonImmutable::parse($thread['resolve_by'])->lessThanOrEqualTo($now) => 'resolve',
            default => null,
        }], $this->complaints->all());
    }
}
