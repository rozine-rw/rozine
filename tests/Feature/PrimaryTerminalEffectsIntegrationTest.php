<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\IssueInstruction;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryFailedClosingFixture;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;

beforeEach(fn () => $this->freezeSecond());

/** @return array<string, string> */
function terminalEffectSnapshot(): array
{
    $snapshot = [];
    foreach (['ledger_entries', 'ledger_lines', 'primary_holdings', 'primary_campaign_issuances',
        'primary_failed_closings', 'business_campaign_closures', 'business_exposure_reservations',
        'primary_campaign_closure_returns', 'change_feed'] as $table) {
        $snapshot[$table] = DB::table($table)->get()
            ->sortBy(fn (object $row): string => json_encode($row, JSON_THROW_ON_ERROR))->values()->toJson();
    }

    return $snapshot;
}

it('keeps a genuine complete issue unchanged when the shared adapter receives failed-closing authority for that success', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    $before = terminalEffectSnapshot();
    $failed = new FailedClosing($instruction->closingId, $instruction->disbursementId, 'reconciled_failure', []);

    expect(fn () => PrimaryFailedClosingFixture::replay($campaign, $failed))->toThrow(RuntimeException::class, 'PRIMARY_CLOSING_MISMATCH')
        ->and(terminalEffectSnapshot())->toBe($before)
        ->and(DB::table('primary_holdings')->count())->toBe(2)
        ->and(DB::table('ledger_entries')->where('kind', 'primary_refund')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($campaign->businessId))->toHaveCount(1);
});

it('keeps a genuine complete refund unchanged when the shared adapter receives issue authority for that failure', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close('reconciled_failure');
    PrimaryHoldingFixture::flushDeferredChecks();
    $before = terminalEffectSnapshot();
    $issued = new IssueInstruction($instruction->closingId, $instruction->disbursementId, PrimaryHoldingFixture::id(),
        now()->toIso8601String(), now()->toDateString(), [], now()->toIso8601String());

    expect(fn () => PrimaryIssuanceFixture::replay($campaign, $issued))->toThrow(RuntimeException::class, 'PRIMARY_CLOSING_MISMATCH')
        ->and(terminalEffectSnapshot())->toBe($before)
        ->and(DB::table('primary_holdings')->count())->toBe(0)
        ->and(DB::table('ledger_entries')->where('kind', 'primary_issue')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($campaign->businessId))->toBe([]);
});
