<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Business\Contracts\StaffApplicationQueue;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\ActivityTrailStore;
use App\Application\Operations\Contracts\ReconciliationStore;
use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;

/**
 * System Health (`health.view`): the design's monitored signals, each read from the records that
 * already exist and graded against the design's tolerance. Reads only.
 *
 * - `ledger`: the provider clearing accounts against the provider's outcomes. `critical` when an
 *   account's ledger balance differs from its statement, `watch` while a break is open.
 * - `capital`: the cash raised so far by every raise still filling or funded and awaiting its
 *   disbursement. Always `healthy`.
 * - `queue`: applications waiting for review; `watch` above five (design).
 * - `events`: governed actions recorded in the last hour; `watch` when there were none (design).
 *
 * The design's default rate, arrears ratio, outstanding repayments, treasury position and frozen
 * accounts are `untracked`: the platform records no repayment servicing, treasury balance or
 * account freeze yet, so nothing is estimated for them.
 *
 * @phpstan-type Signal array{
 *     key: 'ledger'|'capital'|'queue'|'events'|'default_rate'|'arrears'|'outstanding'|'treasury'|'frozen',
 *     status: 'critical'|'watch'|'healthy'|'untracked',
 *     facts: array<string, int|string>
 * }
 */
final readonly class CheckSystemHealth
{
    /** The design asks for an underwriter once more than five applications wait. */
    public const int QUEUE_TOLERANCE = 5;

    public function __construct(private ReconciliationStore $reconciliation, private InvestorDealCatalogue $deals,
        private StaffApplicationQueue $applications, private ActivityTrailStore $trail, private AuthorizeStaffPermission $staff) {}

    /** @return list<Signal> */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'health.view');
        $now = CarbonImmutable::now();

        $accounts = $this->reconciliation->accounts($now);
        $unmatched = count(array_filter($accounts, fn (array $account): bool => ! BigInteger::of($account['ledger'])->isEqualTo($account['statement'])));
        $breaks = count($this->reconciliation->breaks($now));

        $raised = BigInteger::zero();
        $raises = 0;
        foreach ($this->deals->deals() as $deal) {
            $raised = $raised->plus((string) $deal['raised']['amount']);
            $raises++;
        }

        $queue = $this->applications->page('pending', '', null, 1, null);
        /** @var array{pending?: int|string} $counts */
        $counts = is_array($queue['counts'] ?? null) ? $queue['counts'] : [];
        $pending = (int) ($counts['pending'] ?? 0);

        $lastHour = $this->trail->trail('', $now->subHour()->utc()->toIso8601String(), null, 1)['total'];
        $total = $this->trail->trail('', null, null, 1)['total'];

        return [
            ['key' => 'ledger', 'status' => $unmatched > 0 ? 'critical' : ($breaks > 0 ? 'watch' : 'healthy'),
                'facts' => ['accounts' => count($accounts), 'unmatched' => $unmatched, 'breaks' => $breaks]],
            ['key' => 'capital', 'status' => 'healthy', 'facts' => ['amount' => (string) $raised, 'raises' => $raises]],
            ['key' => 'queue', 'status' => $pending > self::QUEUE_TOLERANCE ? 'watch' : 'healthy', 'facts' => ['pending' => $pending]],
            ['key' => 'events', 'status' => $lastHour > 0 ? 'healthy' : 'watch', 'facts' => ['last_hour' => $lastHour, 'total' => $total]],
            ['key' => 'default_rate', 'status' => 'untracked', 'facts' => []], ['key' => 'arrears', 'status' => 'untracked', 'facts' => []],
            ['key' => 'outstanding', 'status' => 'untracked', 'facts' => []], ['key' => 'treasury', 'status' => 'untracked', 'facts' => []],
            ['key' => 'frozen', 'status' => 'untracked', 'facts' => []],
        ];
    }
}
