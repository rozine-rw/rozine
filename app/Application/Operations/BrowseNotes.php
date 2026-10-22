<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationsDashboardStore;

/**
 * The Notes register (`primary.view`): every published raise as a note, by where it sits now (the
 * same rule the dashboard counts), with the economics the Investors saw: business, rate, term, fill
 * and what was raised. Capital raised is the dashboard's figure, the principal of every issued
 * Holding. No note matures yet (servicing records no final repayment), so `matured` stays empty
 * rather than being guessed. Read-only.
 *
 * @phpstan-type NoteRow array{
 *     campaign_id: string, state: 'live'|'funded'|'repaying'|'failed', name: string, industry: string, rate_pct: string, term_months: int,
 *     funded_pct: string, raised: array{currency: string, amount: string}, target: array{currency: string, amount: string}, investors: int, live_at: string
 * }
 */
final readonly class BrowseNotes
{
    public function __construct(private OperationsDashboardStore $dashboard, private InvestorDealCatalogue $deals, private AuthorizeStaffPermission $staff) {}

    /** @return array{notes: list<NoteRow>, capital_raised: string} */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'primary.view');
        $notes = [];
        foreach ($this->dashboard->noteStates() as $note) {
            $card = $this->deals->card($note['campaign_id']);
            if ($card === null) {
                continue;
            }
            $notes[] = ['campaign_id' => $note['campaign_id'], 'state' => $note['state'], 'name' => (string) $card['name'], 'industry' => (string) $card['industry'],
                'rate_pct' => (string) $card['rate_pct'], 'term_months' => (int) $card['term_months'], 'funded_pct' => (string) $card['funded_pct'],
                'raised' => $card['raised'], 'target' => $card['target'], 'investors' => (int) $card['investors'], 'live_at' => $note['live_at']];
        }

        return ['notes' => $notes, 'capital_raised' => $this->dashboard->figures()['capital_raised']];
    }
}
