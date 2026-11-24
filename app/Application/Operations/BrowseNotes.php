<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Business\Contracts\ServicingRecord;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationsDashboardStore;
use App\Application\Secondary\Contracts\NoteUnitHolders;

/**
 * The Notes register (`primary.view`): every published raise as a note, by where it sits now (the
 * same rule the dashboard counts), with the economics the Investors saw: business, rate, term, fill
 * and what was raised. Capital raised is the dashboard's figure, the principal of every issued
 * Holding. A repaying note whose servicing record shows it repaid in full is matured. Read-only.
 *
 * @phpstan-type NoteRow array{
 *     campaign_id: string, state: 'live'|'funded'|'repaying'|'matured'|'failed', name: string, industry: string, rate_pct: string, term_months: int,
 *     funded_pct: string, raised: array{currency: string, amount: string}, target: array{currency: string, amount: string}, investors: int, live_at: string
 * }
 */
final readonly class BrowseNotes
{
    public function __construct(private OperationsDashboardStore $dashboard, private InvestorDealCatalogue $deals, private AuthorizeStaffPermission $staff,
        private NoteUnitHolders $lots, private ServicingRecord $servicing) {}

    /** @return array{notes: list<NoteRow>, capital_raised: string} */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'primary.view');
        $notes = [];
        $repaid = [];
        foreach ($this->dashboard->noteStates() as $note) {
            $repaid[$note['business_id']] ??= array_column($this->servicing->forBusiness($note['business_id'])['repaid'], 'campaign_id');
            $card = $this->deals->card($note['campaign_id']);
            if ($card === null) {
                continue;
            }
            $state = $note['state'] === 'repaying' && in_array($note['campaign_id'], $repaid[$note['business_id']], true) ? 'matured' : $note['state'];
            $notes[] = ['campaign_id' => $note['campaign_id'], 'state' => $state, 'name' => (string) $card['name'], 'industry' => (string) $card['industry'],
                'rate_pct' => (string) $card['rate_pct'], 'term_months' => (int) $card['term_months'], 'funded_pct' => (string) $card['funded_pct'],
                'raised' => $card['raised'], 'target' => $card['target'], 'investors' => $this->investors($note['campaign_id'], (int) $card['investors']), 'live_at' => $note['live_at']];
        }

        return ['notes' => $notes, 'capital_raised' => $this->dashboard->figures()['capital_raised']];
    }

    /**
     * How many Investors hold the note now, buyers on the market included, so the row agrees with
     * its drawer (#663). Before issuance nothing is held yet, and the raise's own count of Investors stands.
     */
    private function investors(string $noteId, int $committed): int
    {
        $parties = array_unique(array_column($this->lots->lots($noteId), 'party_id'));

        return $parties === [] ? $committed : count($parties);
    }

    /**
     * Who holds a note's units now, largest first: each holder's issued units and the units it bought
     * on the market, by its name.
     *
     * @return list<array{name: string, units: string, bought: bool}>
     */
    public function holders(int $actorId, string $noteId): array
    {
        $this->staff->check($actorId, 'primary.view');
        $lots = $this->lots->lots($noteId);
        $names = $this->dashboard->partyNames(array_values(array_unique(array_column($lots, 'party_id'))));
        // One row per holder and way in: a holder's lots bought on the market add up to one row.
        $holders = [];
        foreach ($lots as $lot) {
            $bought = $lot['lot_type'] === 'secondary_transfer';
            $key = $lot['party_id'].($bought ? ':bought' : ':issued');
            $holders[$key] = ['name' => $names[$lot['party_id']] ?? '—', 'units' => (string) ((int) ($holders[$key]['units'] ?? 0) + (int) $lot['units']),
                'bought' => $bought, 'party' => $lot['party_id']];
        }
        usort($holders, fn (array $left, array $right): int => (int) $right['units'] <=> (int) $left['units']);

        return array_map(fn (array $row): array => ['name' => $row['name'], 'units' => $row['units'], 'bought' => $row['bought']], $holders);
    }
}
