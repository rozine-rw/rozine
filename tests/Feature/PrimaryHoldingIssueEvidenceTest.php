<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessCampaign;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;

/*
 * The Holdings-side completeness guards: a Holding needs its exact primary issue posting by the
 * time its transaction commits, an issued closing issues every funded commitment, and issued
 * ownership is never refunded. Real checkout, real funding lock, real wallet postings.
 */

/** What a commit would do now: every deferred check runs, and later writes stay deferred. */
function issueEvidenceCommit(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

function issueEvidenceRefused(Closure $write, string $message): void
{
    $refusal = null;
    try {
        DB::transaction(fn () => $write());
    } catch (QueryException $exception) {
        $refusal = $exception;
    }
    expect($refusal?->getCode())->toBe('23514')->and($refusal?->getMessage())->toContain('ERROR:  '.$message);
}

beforeEach(fn () => $this->freezeSecond());

it('accepts a Holding and its issue posting in either order and refuses the commit without the posting', function (bool $holdingsFirst): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed(['1080', '600', '480']);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $holdings = fn () => array_map(fn (PrimaryCommitment $commitment): string => PrimaryHoldingFixture::insert($commitment->id, $closing), $commitments);
    $postings = fn () => array_map(fn (PrimaryCommitment $commitment) => PrimaryHoldingFixture::issue($commitment->id, $closing), $commitments);
    if ($holdingsFirst) {
        $holdings();
        issueEvidenceRefused(fn () => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'), 'A Holding requires its reservation\'s exact primary issue posting for its own closing');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
        $postings();
    } else {
        $postings();
        issueEvidenceCommit();
        $holdings();
    }
    issueEvidenceCommit();
    expect(DB::table('primary_holdings')->count())->toBe(3)
        ->and(DB::table('ledger_entries')->where('kind', 'primary_issue')->where('cause_id', $closing)->count())->toBe(3)
        ->and(DB::table('primary_holdings')->sum('principal'))->toEqual($campaign->principal);
})->with(['Holdings first, then the Party-locked postings' => true, 'postings first, then Holdings' => false]);

it('refuses a Holding whose issue posting is missing or names another closing', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $second]] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $message = 'A Holding requires its reservation\'s exact primary issue posting for its own closing';
    issueEvidenceRefused(function () use ($first, $second, $closing): void {
        PrimaryHoldingFixture::insert($first->id, $closing);
        PrimaryHoldingFixture::insert($second->id, $closing);
        PrimaryHoldingFixture::issue($first->id, $closing);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }, $message);
    // Another campaign's genuinely issued closing: a valid cause for an issue posting, but not this Holding's.
    $foreign = PrimaryHoldingFixture::issuedClosing(BusinessCampaign::factory()->create());
    issueEvidenceRefused(function () use ($first, $second, $closing, $foreign): void {
        PrimaryHoldingFixture::insert($first->id, $closing);
        PrimaryHoldingFixture::insert($second->id, $closing);
        PrimaryHoldingFixture::issue($first->id, $closing);
        PrimaryHoldingFixture::issue($second->id, $foreign);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }, $message);
    expect(DB::table('primary_holdings')->count())->toBe(0)->and(DB::table('ledger_entries')->where('kind', 'primary_issue')->count())->toBe(0);
});

it('does not let one Investor\'s posting for one reservation stand in for another of the same size', function (): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed(['540', '540', '1080'], buyers: [0 => 'same', 1 => 'same']);
    [$first, $second, $third] = $commitments;
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    expect(PrimaryReservationRecord::query()->whereKey($first->primary_reservation_id)->sole()->party_id)
        ->toBe(PrimaryReservationRecord::query()->whereKey($second->primary_reservation_id)->sole()->party_id);
    foreach ($commitments as $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $closing);
    }
    PrimaryHoldingFixture::issue($first->id, $closing);
    PrimaryHoldingFixture::issue($third->id, $closing);
    issueEvidenceRefused(fn () => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'), 'A Holding requires its reservation\'s exact primary issue posting for its own closing');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    PrimaryHoldingFixture::issue($second->id, $closing);
    issueEvidenceCommit();
});

it('refuses an issued closing that leaves a funded commitment without a Holding', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $second]] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    PrimaryHoldingFixture::insert($first->id, $closing);
    PrimaryHoldingFixture::issue($first->id, $closing);
    PrimaryHoldingFixture::issue($second->id, $closing);
    issueEvidenceRefused(fn () => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'), 'An issued closing must issue a Holding for every funded commitment');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    PrimaryHoldingFixture::insert($second->id, $closing);
    issueEvidenceCommit();
});

it('refuses a refund of principal that has a Holding, separately from the funded-refund gate', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $second]] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $root = PrimaryReservationRecord::query()->whereKey($first->primary_reservation_id)->sole();
    $other = PrimaryReservationRecord::query()->whereKey($second->primary_reservation_id)->sole();
    $refund = function (PrimaryReservationRecord $root): void {
        $wallets = app(WalletPostings::class);
        DB::transaction(fn () => $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id)));
    };
    PrimaryHoldingFixture::insert($first->id, $closing);
    // S3-C's own gate speaks first while it stands.
    issueEvidenceRefused(fn () => $refund($root), 'Funded principal requires authoritative failed closing');
    // With that gate lifted, standing in for S3-C's future authoritative failed-closing refund, this guard still refuses.
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER primary_funding_refund_gate');
    issueEvidenceRefused(fn () => $refund($root), 'Principal issued as a Holding cannot be refunded');
    expect(DB::table('ledger_entries')->where('kind', 'primary_refund')->count())->toBe(0);
    // A commitment without a Holding is not this guard's business.
    $refund($other);
    expect(DB::table('ledger_entries')->where('kind', 'primary_refund')->where('source_id', $other->id)->count())->toBe(1);
});

it('installs only over Holdings that carry their evidence and rolls back exactly', function (): void {
    $migration = require database_path('migrations/2026_09_30_114217_require_issue_evidence_for_primary_holdings.php');
    $shape = fn (): array => [
        DB::select("SELECT tgname, pg_get_triggerdef(oid) AS definition FROM pg_trigger WHERE tgrelid IN ('primary_holdings'::regclass, 'ledger_entries'::regclass) AND NOT tgisinternal ORDER BY tgname"),
        DB::select("SELECT proname, pg_get_functiondef(oid) AS definition FROM pg_proc WHERE proname IN ('check_primary_holding_issue', 'require_primary_holding_issue', 'refuse_refund_after_primary_holding') ORDER BY proname"),
        DB::select("SELECT indexname, indexdef FROM pg_indexes WHERE tablename = 'primary_holdings' ORDER BY indexname")];
    $installed = $shape();
    expect(array_column($installed[0], 'tgname'))->toContain('primary_holding_issue_bound', 'primary_holding_refund_refused', 'primary_funding_refund_gate')
        ->and($installed[1])->toHaveCount(3)->and(array_column($installed[2], 'indexname'))->toContain('primary_holding_reservation_lookup');
    $migration->down();
    expect(array_column($shape()[0], 'tgname'))->not->toContain('primary_holding_issue_bound', 'primary_holding_refund_refused')
        ->toContain('primary_funding_refund_gate')->and($shape()[1])->toBe([]);

    ['campaign' => $campaign, 'commitments' => [$first, $second]] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    PrimaryHoldingFixture::insert($first->id, $closing);
    PrimaryHoldingFixture::insert($second->id, $closing);
    PrimaryHoldingFixture::issue($first->id, $closing);
    issueEvidenceCommit();
    issueEvidenceRefused(fn () => $migration->up(), 'A Holding requires its reservation\'s exact primary issue posting for its own closing');
    PrimaryHoldingFixture::issue($second->id, $closing);
    issueEvidenceCommit();
    $migration->up();
    expect($shape())->toEqual($installed);
    issueEvidenceRefused(fn () => $migration->down(), 'Issued Holdings require a forward migration; rollback is refused');
    expect($shape())->toEqual($installed);
});
