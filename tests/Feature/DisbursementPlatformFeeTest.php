<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Disbursement\Contracts\PlatformFeeLedger;
use App\Application\Disbursement\ReconcileDisbursements;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Disbursement\PlatformFee;
use App\Domain\Wallet\WalletViolation;
use App\Models\AuditPartnerCredit;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use App\Models\LedgerEntry;
use App\Models\PartyNotice;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\DisbursementFixture;
use Tests\Support\PrimaryIssuanceFixture;

/*
 * BRS v2.0 SVC-R01, FEE-R01 and FEE-R02 (ruled on #154, 16 Oct 2026): the Business receives its raise
 * less the 3% platform fee, the fee is posted once the note is issued, and 40% of it is credited to
 * the Audit Partner who verified the note.
 */

/** @return array<string, string> each platform account's movement on one entry, signed debit-positive */
function platformFeeLines(string $entryId): array
{
    return DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
        ->where('ledger_lines.entry_id', $entryId)->orderBy('ledger_accounts.kind')
        ->selectRaw("ledger_accounts.kind, (CASE WHEN direction = 'debit' THEN amount ELSE -amount END)::text AS movement")
        ->pluck('movement', 'kind')->all();
}

it('sends the Business its raise less the fee, posts the fee once on issue and tells the Business both amounts', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $disbursement = Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->sole();
    $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole();
    $closing = DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->sole();

    expect([$disbursement->amount, $disbursement->platform_fee, $disbursement->audit_partner_fee, $disbursement->fee_version])
        ->toBe(['10800000', '324000', '129600', 'brs-2.0-fee-r01'])
        ->and($intent->amount)->toBe('10476000')
        ->and($closing->kind)->toBe('issued');

    $entry = LedgerEntry::query()->where('kind', 'disbursement_platform_fee')->sole();
    expect([$entry->source_type, $entry->source_id, $entry->origin_operation_id])->toBe(['disbursement_closing', $closing->id, $intent->operation_id])
        ->and(DB::table('business_wallets')->where('id', $entry->wallet_id)->value('business_id'))->toBe($campaign->businessId)
        ->and(platformFeeLines($entry->id))->toBe(['audit_partner_fee_payable' => '-129600', 'disbursement_settlement' => '324000', 'platform_fee_revenue' => '-194400']);
    $credit = AuditPartnerCredit::query()->sole();
    $report = DB::table('audit_reports')->where('status', 'sealed')
        ->where('application_id', DB::table('business_campaigns')->where('id', $campaign->campaignId)->value('business_application_id'))->sole();
    expect([$credit->disbursement_id, $credit->ledger_entry_id, $credit->amount, $credit->kind])->toBe([$disbursement->id, $entry->id, '129600', 'platform_fee_share'])
        ->and([$credit->audit_report_id, $credit->auditor_party_id])->toBe([$report->id, $report->author_party_id]);

    $treasury = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($treasury->id, true, 'Ledger assignment.', (string) Str::uuid(), ['treasury']);
    $this->actingAs($treasury)->get(route('staff.ledger.index', ['entry' => $entry->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('entry.kind', 'platform_fee')->where('entry.from', 'Disbursement settlement')
            ->where('entry.to', 'Platform fee revenue')->where('entry.amount.amount', '324000'));

    $sent = PartyNotice::query()->where('kind', 'business.disbursement_sent')->sole();
    expect($sent->facts)->toMatchArray(['amount' => '10476000', 'principal' => '10800000', 'platform_fee' => '324000']);

    app(ReconcileDisbursements::class)->handle();
    expect(LedgerEntry::query()->where('kind', 'disbursement_platform_fee')->count())->toBe(1)
        ->and(AuditPartnerCredit::query()->count())->toBe(1);
});

it('replays a posted fee without a second entry and refuses one under another operation', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $disbursement = Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->sole();
    $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole();
    $closing = DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->sole();
    $ledger = app(PlatformFeeLedger::class);
    $fee = PlatformFee::of($disbursement->amount);
    $post = fn (string $operationId): bool => DB::transaction(fn (): bool => $ledger->post($disbursement->id, $disbursement->business_id, $closing->id,
        $operationId, $fee, null));
    $otherOperation = strtolower((string) Str::ulid());

    expect($post($intent->operation_id))->toBeTrue()
        ->and(LedgerEntry::query()->where('kind', 'disbursement_platform_fee')->count())->toBe(1);
    expect(fn (): bool => $post($otherOperation))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
});

it('takes the fee from a synthetic payout but posts nothing for a Business with no profile', function (): void {
    ['disbursement' => $disbursement, 'intent' => $intent] = DisbursementFixture::approved();
    DisbursementFixture::provider()->scriptQuery($intent->id, 'succeeded');
    app(ReconcileDisbursements::class)->handle();

    expect($intent->amount)->toBe('2910000')
        ->and(DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->sole()->kind)->toBe('issued')
        ->and(LedgerEntry::query()->where('kind', 'disbursement_platform_fee')->exists())->toBeFalse();
});

it('shows a disbursement opened before the fee rule with no fee, and refuses a fee the rule did not compute', function (): void {
    $legacy = Disbursement::factory()->create(['payload' => ['business_name' => 'Kigali Bakery', 'title' => 'Ovens']]);
    $viewer = DisbursementFixture::staff(['treasury']);
    $page = DisbursementFixture::detail($viewer, $legacy);

    expect($page['amount']['amount'])->toBe('3000000')->and($page['platform_fee'])->toBeNull();

    $codes = [];
    foreach ([['platform_fee' => '90000', 'audit_partner_fee' => '36000', 'fee_version' => null],
        ['platform_fee' => '90001', 'audit_partner_fee' => '36000', 'fee_version' => PlatformFee::VERSION],
        ['platform_fee' => '90000', 'audit_partner_fee' => '36001', 'fee_version' => PlatformFee::VERSION],
        ['platform_fee' => '90000', 'audit_partner_fee' => '36000', 'fee_version' => 'brs-1.0']] as $fee) {
        try {
            DB::transaction(fn () => Disbursement::factory()->create($fee));
        } catch (QueryException $exception) {
            $codes[] = $exception->getCode();
        }
    }
    expect($codes)->toBe(['23514', '23514', '23514', '23514'])
        ->and(Disbursement::factory()->create(['platform_fee' => '90000', 'audit_partner_fee' => '36000', 'fee_version' => PlatformFee::VERSION])->platform_fee)
        ->toBe('90000');
});

it('refuses closing evidence for a disbursement read back without the fee its authenticated record carries', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $disbursement = Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->sole();
    $closing = DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->sole();
    $evidence = app(DisbursementClosingEvidence::class);
    expect(DB::transaction(fn () => $evidence->find($closing->id))->kind)->toBe('issued');

    Disbursement::retrieved(function (Disbursement $read): void {
        $read->setRawAttributes([...$read->getAttributes(), 'platform_fee' => '0', 'audit_partner_fee' => '0', 'fee_version' => null], true);
    });
    try {
        expect(fn () => DB::transaction(fn () => $evidence->find($closing->id)))->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED');
    } finally {
        Event::forget('eloquent.retrieved: '.Disbursement::class);
    }
});

it('refuses an Audit Partner credit that does not repeat its fee, and a fee entry without its credit', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $disbursement = Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->sole();
    $entry = LedgerEntry::query()->where('kind', 'disbursement_platform_fee')->sole();
    $credit = fn (array $overrides): mixed => DB::transaction(fn () => (new AuditPartnerCredit)->forceFill([...['disbursement_id' => $disbursement->id,
        'ledger_entry_id' => $entry->id, 'kind' => 'platform_fee_share', 'amount' => '129600', 'fee_version' => PlatformFee::VERSION,
        'created_at' => now()], ...$overrides])->save());

    $codes = [];
    foreach ([['amount' => '129601'], ['audit_report_id' => null, 'auditor_party_id' => strtolower((string) Str::ulid())]] as $overrides) {
        try {
            $credit($overrides);
        } catch (QueryException $exception) {
            $codes[] = $exception->getCode();
        }
    }
    expect($codes)->toBe(['23514', '23514'])
        ->and(fn () => DB::transaction(fn () => AuditPartnerCredit::query()->sole()->forceFill(['amount' => '1'])->save()))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(function () use ($entry): void {
            DB::table('ledger_entries')->insert([...collect($entry->getAttributes())->except(['id', 'wallet_owner', 'created_xid'])->all(),
                'id' => strtolower((string) Str::ulid()), 'source_id' => strtolower((string) Str::ulid())]);
            // The check is deferred to commit; the test's own transaction never commits, so run it now.
            DB::statement('SET CONSTRAINTS ledger_entries_platform_fee_credited IMMEDIATE');
        }))->toThrow(QueryException::class, 'needs its Audit Partner credit');
});
