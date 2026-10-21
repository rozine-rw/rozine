<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\InvestorDirectoryStore;
use App\Application\Operations\Contracts\OperationsDashboardStore;

/**
 * How much of the sandbox's hard limits the platform uses now (SBX-R01, shown on App Control per
 * APP-D01), for any enabled staff member. Businesses count those whose mandate is active and in
 * force, Investors those verified, and principal outstanding is every issued Holding's principal:
 * the platform records no repayment yet, so nothing has reduced it. Reads only.
 *
 * @phpstan-type Usage array{key: 'businesses'|'investors'|'principal', used: string, cap: string, used_pct: int}
 */
final readonly class ReadSandboxUsage
{
    public function __construct(private OperationsDashboardStore $store, private InvestorDirectoryStore $investors,
        private AuthorizeStaffPermission $staff) {}

    /** @return list<Usage> */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'admin.open');
        $figures = $this->store->figures();
        $caps = PlatformPolicies::SANDBOX;
        $used = ['businesses' => $figures['active_businesses'], 'investors' => $this->investors->directory('all', 'name', '', 0)['counts']['verified'],
            'principal' => (int) $figures['capital_raised']];

        return array_map(fn (string $key): array => ['key' => $key, 'used' => (string) $used[$key], 'cap' => (string) $caps[$key],
            'used_pct' => intdiv($used[$key] * 100, $caps[$key])], ['businesses', 'investors', 'principal']);
    }
}
