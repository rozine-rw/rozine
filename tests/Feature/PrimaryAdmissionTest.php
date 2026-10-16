<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessConnections;
use App\Domain\Primary\PrimaryDisclosure;
use App\Domain\Primary\RiskStatements;
use App\Models\InvestorRiskAcknowledgement;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * Live purchase admission (C3 S3-C): the KYC-R05 risk acknowledgement gates every new reserve and
 * confirm, and the INV-R04 earnings-fee band follows active capital after the checkout.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->actingAs($this->investor['user']);

    $this->acknowledge = fn (array $overrides = [], ?string $request = null) => $this->postJson(route('investor.risks.acknowledge'), [
        'request_id' => $request ?? (string) Str::uuid(), 'identity_context_revision' => 1, 'version' => RiskStatements::VERSION,
        'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => true, ...$overrides]);
    $this->reserve = fn (string $units) => $this->postJson(route('investor.primary.reserve', ['campaign' => $this->campaign->id]), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'campaign_id' => $this->campaign->id, 'units' => $units,
        'expected_campaign_revision' => 1, 'quote_revision' => 1]);
    $this->confirm = function (string $reservation) {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $reservation)->orderByDesc('revision')->firstOrFail();

        return $this->postJson(route('investor.primary.confirm', ['reservation' => $reservation]), [
            'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'reservation_id' => $reservation,
            'expected_reservation_revision' => $version->revision, 'disclosure_version' => $version->payload['terms']['disclosure_version'],
            'disclosure_sha256' => $version->payload['disclosure_sha256'], 'acknowledged' => true]);
    };
});

it('refuses a reserve RISK_ACKNOWLEDGEMENT_REQUIRED and holds nothing until the risks are acknowledged', function (): void {
    ($this->reserve)('2')->assertJsonPath('status', 'rejected')->assertJsonPath('code', 'RISK_ACKNOWLEDGEMENT_REQUIRED');

    expect(PrimaryReservationRecord::query()->count())->toBe(0);
});

it('shows the five statements and records one acknowledgement, replayed by its request', function (): void {
    $this->getJson(route('investor.risks.show', ['identity_context_revision' => 1]))->assertOk()
        ->assertJsonPath('data.version', RiskStatements::VERSION)->assertJsonPath('data.statements_sha256', RiskStatements::sha256())
        ->assertJsonCount(5, 'data.statements')->assertJsonPath('data.statements.0.key', 'total_loss')->assertJsonPath('data.acknowledged', false);
    $request = (string) Str::uuid();

    $first = ($this->acknowledge)([], $request)->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('code', 'RISKS_ACKNOWLEDGED')
        ->assertJsonPath('data.receipt.version', RiskStatements::VERSION)->json();
    ($this->acknowledge)([], $request)->assertOk()->assertJsonPath('operation_id', $first['operation_id']);
    ($this->acknowledge)()->assertOk()->assertJsonPath('data.receipt.acknowledgement_id', $first['data']['receipt']['acknowledgement_id']);

    $acknowledgement = InvestorRiskAcknowledgement::query()->sole();
    expect($acknowledgement->party_id)->toBe($this->investor['party']->id)
        ->and($acknowledgement->statements_sha256)->toBe(RiskStatements::sha256())->and($acknowledgement->sha256)->toMatch('/^[0-9a-f]{64}$/');
    $this->getJson(route('investor.risks.show'))->assertJsonPath('data.acknowledged', true);
});

it('refuses an acknowledgement of statements that are not the current ones', function (array $overrides): void {
    ($this->acknowledge)($overrides)->assertJsonPath('status', 'rejected')->assertJsonPath('code', 'RISK_STATEMENTS_STALE');

    expect(InvestorRiskAcknowledgement::query()->count())->toBe(0);
})->with([[['version' => 'brs-1.0-kyc-r05']], [['statements_sha256' => str_repeat('a', 64)]]]);

it('requires every statement to be ticked and the request to be well formed', function (array $overrides): void {
    ($this->acknowledge)($overrides)->assertUnprocessable();
})->with([[['acknowledged' => false]], [['statements_sha256' => 'short']], [['request_id' => 'not-a-uuid']]]);

it('keeps acknowledgements immutable in the database', function (): void {
    ($this->acknowledge)()->assertOk();

    expect(fn () => DB::table('investor_risk_acknowledgements')->update(['version' => 'changed']))->toThrow(QueryException::class);
    expect(fn () => DB::table('investor_risk_acknowledgements')->delete())->toThrow(QueryException::class);
});

it('reserves and confirms at the band of the first RWF 1M, on the return only', function (): void {
    ($this->acknowledge)()->assertOk();

    $held = ($this->reserve)('2')->assertOk()->assertJsonPath('code', 'RESERVATION_HELD')->json('data.receipt');
    $confirmed = ($this->confirm)($held['reservation_id'])->assertOk()->assertJsonPath('code', 'RESERVATION_CONFIRMED')->json('data.receipt');

    expect($confirmed['terms']['earnings_fee'])->toEqual(['tier' => 'standard', 'rate_bps' => 1500, 'basis' => 'return_only', 'policy_version' => 'brs-2.0-inv-r04'])
        ->and($confirmed['terms']['disclosure_version'])->toBe(PrimaryDisclosure::VERSION)
        ->and($confirmed['terms']['rate_pct'])->toBe('11.1')->and($confirmed['amount'])->toBe('10000')
        ->and(PrimaryCommitment::query()->count())->toBe(1);
});

it('locks the next band once committed principal plus the checkout reaches exactly RWF 1M', function (): void {
    ($this->acknowledge)()->assertOk();
    $first = ($this->reserve)('199')->json('data.receipt.reservation_id');
    expect(($this->confirm)($first)->json('data.receipt.terms.earnings_fee.tier'))->toBe('standard');

    $second = ($this->reserve)('1')->assertOk()->json('data.receipt.reservation_id');
    $confirmed = ($this->confirm)($second)->assertOk()->assertJsonPath('code', 'RESERVATION_CONFIRMED')->json('data.receipt');

    expect($confirmed['terms']['earnings_fee']['tier'])->toBe('bronze')->and($confirmed['terms']['earnings_fee']['rate_bps'])->toBe(1200);
});

it('requotes a held line whose band moved before it was confirmed', function (): void {
    ($this->acknowledge)()->assertOk();
    $small = ($this->reserve)('1')->json('data.receipt.reservation_id');
    $large = ($this->reserve)('199')->json('data.receipt.reservation_id');
    ($this->confirm)($large)->assertJsonPath('code', 'RESERVATION_CONFIRMED');

    ($this->confirm)($small)->assertOk()->assertJsonPath('code', 'RESERVATION_REQUOTED')->assertJsonPath('data.receipt.terms.earnings_fee.tier', 'bronze');
    ($this->confirm)($small)->assertOk()->assertJsonPath('code', 'RESERVATION_CONFIRMED');
});

it('refuses a reserve against a campaign revision the Investor did not see', function (): void {
    ($this->acknowledge)()->assertOk();

    $this->postJson(route('investor.primary.reserve', ['campaign' => $this->campaign->id]), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => 1, 'campaign_id' => $this->campaign->id, 'units' => '2', 'expected_campaign_revision' => 2, 'quote_revision' => 1])
        ->assertJsonPath('code', 'VERSION_CONFLICT');
    expect(PrimaryReservationRecord::query()->count())->toBe(0);
});

it('refuses a confirm once the acknowledged statements are no longer current for the Party', function (): void {
    ($this->acknowledge)()->assertOk();
    $held = ($this->reserve)('2')->json('data.receipt.reservation_id');
    DB::statement('ALTER TABLE investor_risk_acknowledgements DISABLE TRIGGER investor_risk_acknowledgements_immutable');
    DB::table('investor_risk_acknowledgements')->delete();
    DB::statement('ALTER TABLE investor_risk_acknowledgements ENABLE TRIGGER investor_risk_acknowledgements_immutable');

    ($this->confirm)($held)->assertJsonPath('code', 'RISK_ACKNOWLEDGEMENT_REQUIRED');
    expect(PrimaryCommitment::query()->count())->toBe(0);
});

it('serves the statements and the acknowledgement over API v1 with investor token abilities', function (): void {
    Sanctum::actingAs($this->investor['user'], ['investor:read']);
    $this->getJson(route('api.v1.investor.risks.show'))->assertOk()->assertJsonPath('data.acknowledged', false);
    $this->postJson(route('api.v1.investor.risks.acknowledge'), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1,
        'version' => RiskStatements::VERSION, 'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => true])->assertForbidden();

    Sanctum::actingAs($this->investor['user'], ['investor:read', 'investor:command']);
    $this->postJson(route('api.v1.investor.risks.acknowledge'), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1,
        'version' => RiskStatements::VERSION, 'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => true])
        ->assertOk()->assertJsonPath('code', 'RISKS_ACKNOWLEDGED');
});

/** Reports the real declared set as incomplete, as a narrower future connection source might. */
function incompleteBusinessConnections(): void
{
    $real = app(BusinessConnections::class);
    app()->instance(BusinessConnections::class, new class($real) implements BusinessConnections
    {
        public function __construct(private BusinessConnections $real) {}

        public function lockDeclared(string $businessId): array
        {
            return [...$this->real->lockDeclared($businessId), 'complete' => false];
        }
    });
}

it('admits an unconnected Investor because the declared mandate is the complete connection check', function (): void {
    ($this->acknowledge)()->assertOk();

    ($this->reserve)('2')->assertOk()->assertJsonPath('code', 'RESERVATION_HELD');
});

it('refuses a reserve with nothing held while the connection assessment is incomplete', function (): void {
    ($this->acknowledge)()->assertOk();
    $entries = LedgerEntry::query()->count();
    incompleteBusinessConnections();

    ($this->reserve)('2')->assertStatus(409)->assertJsonPath('code', 'CONNECTION_ASSESSMENT_INCOMPLETE');
    expect(PrimaryReservationRecord::query()->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->count())->toBe($entries);
});

it('refuses a confirm with no commitment while the connection assessment is incomplete', function (): void {
    ($this->acknowledge)()->assertOk();
    $reservation = ($this->reserve)('2')->json('data.receipt.reservation_id');
    $entries = LedgerEntry::query()->count();
    incompleteBusinessConnections();

    ($this->confirm)($reservation)->assertStatus(409)->assertJsonPath('code', 'CONNECTION_ASSESSMENT_INCOMPLETE');
    expect(PrimaryCommitment::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->count())->toBe($entries);
});
