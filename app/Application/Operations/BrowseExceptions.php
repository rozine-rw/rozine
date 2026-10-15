<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\AccountHolds;
use App\Application\Operations\Contracts\OverdueAudits;
use App\Application\Operations\Contracts\ReconciliationStore;
use Carbon\CarbonImmutable;

/**
 * Exceptions for Treasury, Compliance and superadmin (`exceptions.view`, MVP-ADMIN-SCR-06): every
 * open variance, halt and overdue audit, aged in Kigali days. A variance is an open reconciliation
 * break; a halt is an Investor account case in force; an overdue audit is a Flash Audit still
 * unfinished after its 24-hour clock (BRS AUD-R07). Arrears wait on the servicing schedule, so none is read
 * yet. Nothing here can be assigned, escalated or resolved.
 *
 * @phpstan-import-type BreakRecord from ReconciliationStore
 * @phpstan-import-type Hold from AccountHolds
 *
 * @phpstan-type Variance array{
 *     id: string, account_id: 'deposits'|'withdrawals', flow: 'investor'|'business', kind: 'conflict'|'reversed'|'mismatch'|'key_conflict',
 *     reference: string, amount: string, opened_at: string, party_id: string|null, business_id: string|null, age_days: int
 * }
 * @phpstan-type Halt array{
 *     id: string, party_id: string, name: string|null, cause: string, scope: list<string>, effective_at: string, expires_at: string|null, age_days: int
 * }
 * @phpstan-type Audit array{
 *     id: string, business_id: string, business: string, partner: string|null, status: 'offered'|'accepted'|'operations', due_at: string, age_days: int
 * }
 */
final readonly class BrowseExceptions
{
    private const string ZONE = 'Africa/Kigali';

    public function __construct(private ReconciliationStore $reconciliation, private AccountHolds $holds, private OverdueAudits $audits, private AuthorizeStaffPermission $staff) {}

    /** @return array{variances: list<Variance>, halts: list<Halt>, audits: list<Audit>} */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'exceptions.view');
        $now = CarbonImmutable::now(self::ZONE);
        $kigali = fn (string $at): CarbonImmutable => CarbonImmutable::parse($at)->setTimezone(self::ZONE);
        $age = fn (CarbonImmutable $at): int => (int) $at->startOfDay()->diffInDays($now->startOfDay());

        $variances = [];
        foreach ($this->reconciliation->breaks($now) as $break) {
            $opened = $kigali($break['opened_at']);
            $variances[] = [...$break, 'opened_at' => $opened->toIso8601String(), 'age_days' => $age($opened)];
        }
        $halts = [];
        foreach ($this->holds->inForce($now) as $hold) {
            $effective = $kigali($hold['effective_at']);
            $halts[] = [...$hold, 'effective_at' => $effective->toIso8601String(),
                'expires_at' => $hold['expires_at'] === null ? null : $kigali($hold['expires_at'])->toIso8601String(), 'age_days' => $age($effective)];
        }

        $audits = [];
        foreach ($this->audits->flashOverdue($now) as $audit) {
            $due = $kigali($audit['due_at']);
            $audits[] = [...$audit, 'due_at' => $due->toIso8601String(), 'age_days' => $age($due)];
        }

        return ['variances' => $variances, 'halts' => $halts, 'audits' => $audits];
    }
}
