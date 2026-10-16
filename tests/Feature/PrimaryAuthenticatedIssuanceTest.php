<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Disbursement\ClosingEvidence;
use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\FundedCommitment;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\HoldingSource;
use App\Application\Primary\Contracts\IssuedHoldingEvidence;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Primary\PrimaryIssuancePayload;
use App\Infrastructure\Primary\RetainedPrimaryIssuance;
use App\Models\PrimaryCampaignIssuance;
use App\Models\PrimaryHolding;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;

beforeEach(fn () => $this->freezeSecond());

it('issues every genuine purchase with original rights terms and anchored due dates once', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(units: ['1080', '600', '480']);
    PrimaryHoldingFixture::flushDeferredChecks();
    $holdings = PrimaryHolding::query()->orderBy('commitment_id')->get();
    expect($holdings)->toHaveCount(3)->and($holdings->sum('principal'))->toEqual($campaign->principal)
        ->and($instruction->effectiveDate)->toBe('2026-01-31')
        ->and($instruction->dueDates)->toBe(['2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31', '2026-06-30', '2026-07-31']);
    foreach ($holdings as $holding) {
        $facts = app(HoldingSource::class)->facts($holding->commitment_id);
        app(HoldingSource::class)->verify($holding->id);
        expect($holding->rights)->toEqual($facts['rights'])->and($holding->terms)->toEqual($facts['terms'])
            ->and(array_column($holding->schedule, 'due_date'))->toBe($instruction->dueDates)
            ->and(array_column($holding->schedule, 'principal'))->toEqual(array_column($facts['rights']['instalments'], 'principal'))
            ->and($holding->payload['receipt']['code'])->toBe('HOLDING_ISSUED');
    }
    expect(app(BusinessExposureStore::class)->current($campaign->businessId))->toBe([['id' => $campaign->exposureReservationId, 'principal' => $campaign->principal]])
        ->and(DB::table('primary_campaign_issuances')->count())->toBe(1)
        ->and(DB::table('ledger_entries')->where('kind', 'primary_issue')->count())->toBe(3)
        ->and(DB::table('ledger_entries')->where('kind', 'primary_refund')->count())->toBe(0);
    $before = [$holdings->toJson(), DB::table('ledger_entries')->orderBy('id')->get()->toJson(), DB::table('primary_campaign_issuances')->get()->toJson(), DB::table('change_feed')->orderBy('id')->get()->toJson()];
    $this->travel(1)->days();
    PrimaryIssuanceFixture::replay($campaign, $instruction);
    expect([PrimaryHolding::query()->orderBy('commitment_id')->get()->toJson(), DB::table('ledger_entries')->orderBy('id')->get()->toJson(), DB::table('primary_campaign_issuances')->get()->toJson(), DB::table('change_feed')->orderBy('id')->get()->toJson()])->toBe($before);
});

it('reads a certified complete bundle with SELECTs only and never reprices original purchases', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(units: ['540', '540', '1080'], buyers: [0 => 'same', 1 => 'same']);
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = strtolower($query->sql);
    });
    $bundle = DB::transaction(fn (): ?array => app(IssuedHoldingEvidence::class)->find($campaign->campaignId));
    expect($bundle['exposure'])->toBe(['reservation_id' => $campaign->exposureReservationId, 'principal' => $campaign->principal, 'state' => 'outstanding'])
        ->and($bundle['issued_holdings'])->toHaveCount(3)->and($bundle['closing']['dueDates'])->toBe($instruction->dueDates);
    foreach ($queries as $query) {
        expect($query)->toStartWith('select')->not->toContain('for update', 'for share');
    }
    $this->travel(2)->years();
    expect(DB::transaction(fn (): ?array => app(IssuedHoldingEvidence::class)->find($campaign->campaignId)))->toBe($bundle);
});

it('distinguishes absence from schema-only uncertified Holdings and requires caller transaction', function (): void {
    $source = app(IssuedHoldingEvidence::class);
    expect(fn () => $source->find(PrimaryHoldingFixture::id()))->toThrow(DisbursementViolation::class, 'PRIMARY_ISSUANCE_TRANSACTION_REQUIRED')
        ->and(DB::transaction(fn (): ?array => $source->find(PrimaryHoldingFixture::id())))->toBeNull();
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $closing);
        PrimaryHoldingFixture::issue($commitment->id, $closing);
    }
    PrimaryHoldingFixture::flushDeferredChecks();
    expect(fn () => DB::transaction(fn (): ?array => $source->find($campaign->id)))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_SOURCE_UNAVAILABLE');
});

it('refuses changed issue instructions before cash or Holdings and accepts the original retry', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(settle: false);
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    foreach (['closingId' => PrimaryHoldingFixture::id(), 'disbursementId' => PrimaryHoldingFixture::id(),
        'intentOperationId' => PrimaryHoldingFixture::id(), 'effectiveAt' => '2026-01-31T08:00:00+00:00',
        'effectiveDate' => '2026-02-01', 'dueDates' => ['2026-03-01'], 'issuedAt' => now()->addSecond()->toIso8601String()] as $field => $value) {
        $other = new IssueInstruction(...array_replace(get_object_vars($instruction), [$field => $value]));
        expect(fn () => PrimaryIssuanceFixture::replay($campaign, $other))->toThrow($field === 'closingId' ? DisbursementViolation::class : RuntimeException::class)
            ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before)
            ->and(DB::table('primary_holdings')->count())->toBe(0)->and(DB::table('primary_campaign_issuances')->count())->toBe(0);
    }
    $fields = get_object_vars($campaign);
    $purchase = get_object_vars($campaign->commitments[0]);
    $purchase['originOperationId'] = PrimaryHoldingFixture::id();
    $fields['commitments'][0] = new FundedCommitment(...$purchase);
    expect(fn () => PrimaryIssuanceFixture::replay(new FundedCampaign(...$fields), $instruction))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
    PrimaryIssuanceFixture::replay($campaign, $instruction);
    PrimaryHoldingFixture::flushDeferredChecks();
});

it('rolls back every effect even when a caller catches failure after an earlier Holding and journal', function (string $table): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(settle: false);
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    DB::unprepared("CREATE OR REPLACE FUNCTION synthetic_issue_failure() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN IF TG_TABLE_NAME = 'primary_campaign_issuances' OR EXISTS (SELECT 1 FROM primary_holdings) THEN RAISE EXCEPTION 'synthetic late issue failure'; END IF; RETURN NEW; END; $$;");
    DB::unprepared(match ($table) {
        'primary_holdings' => 'CREATE TRIGGER synthetic_issue_failure BEFORE INSERT ON primary_holdings FOR EACH ROW EXECUTE FUNCTION synthetic_issue_failure()',
        'primary_campaign_issuances' => 'CREATE TRIGGER synthetic_issue_failure BEFORE INSERT ON primary_campaign_issuances FOR EACH ROW EXECUTE FUNCTION synthetic_issue_failure()',
        'change_feed' => 'CREATE TRIGGER synthetic_issue_failure BEFORE INSERT ON change_feed FOR EACH ROW EXECUTE FUNCTION synthetic_issue_failure()',
        default => throw new LogicException('Unknown synthetic failure target.'),
    });
    try {
        expect(fn () => PrimaryIssuanceFixture::replay($campaign, $instruction))->toThrow(QueryException::class, 'synthetic late issue failure');
    } finally {
        DB::unprepared(match ($table) {
            'primary_holdings' => 'DROP TRIGGER synthetic_issue_failure ON primary_holdings; DROP FUNCTION synthetic_issue_failure()',
            'primary_campaign_issuances' => 'DROP TRIGGER synthetic_issue_failure ON primary_campaign_issuances; DROP FUNCTION synthetic_issue_failure()',
            default => 'DROP TRIGGER synthetic_issue_failure ON change_feed; DROP FUNCTION synthetic_issue_failure()',
        });
    }
    expect(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(DB::table('primary_holdings')->count())->toBe(0)->and(DB::table('primary_campaign_issuances')->count())->toBe(0);
    PrimaryIssuanceFixture::replay($campaign, $instruction);
    PrimaryHoldingFixture::flushDeferredChecks();
})->with(['primary_holdings', 'primary_campaign_issuances', 'change_feed']);

it('refuses digest-consistent Holding forgeries on read and replay', function (string $field): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    DB::statement('ALTER TABLE primary_holdings DISABLE TRIGGER primary_holdings_protected');
    try {
        $holding = PrimaryHolding::query()->orderBy('commitment_id')->firstOrFail();
        $payload = $holding->payload;
        $native = [];
        match ($field) {
            'digest' => $native['sha256'] = str_repeat('0', 64),
            'schedule' => $payload['schedule'][0]['due_date'] = '2026-03-01',
            'terms' => $payload['terms']['policy_version'] = 'forged-new-policy',
            'rights' => $payload['rights']['instalments'][0]['return']['amount'] = '1',
            'receipt' => $payload['receipt']['code'] = 'FAKE',
            'cash' => $payload['cash']['issue_entry_id'] = PrimaryHoldingFixture::id(),
            'cash_shape' => $payload['cash'] = [],
            'closing' => $payload['closing_sha256'] = str_repeat('0', 64),
            'funding' => $payload['funding_id'] = PrimaryHoldingFixture::id(),
            'native' => $native['schedule'] = [['index' => 1]],
            'created' => $native['created_at'] = now()->addSecond(),
            default => throw new LogicException('Unknown Holding forgery.'),
        };
        if (in_array($field, ['schedule', 'terms', 'rights'], true)) {
            $native[$field] = $payload[$field];
        }
        $holding->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload)), ...$native])->save();
    } finally {
        DB::statement('ALTER TABLE primary_holdings ENABLE TRIGGER primary_holdings_protected');
    }
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    expect(fn () => DB::transaction(fn (): ?array => app(IssuedHoldingEvidence::class)->find($campaign->campaignId)))->toThrow(RuntimeException::class)
        ->and(fn () => PrimaryIssuanceFixture::replay($campaign, $instruction))->toThrow(RuntimeException::class)
        ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before);
})->with(['digest', 'schedule', 'terms', 'rights', 'receipt', 'cash', 'cash_shape', 'closing', 'funding', 'native', 'created']);

it('refuses forged conversion certificates even with a recalculated envelope digest', function (string $field): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    DB::statement('ALTER TABLE primary_campaign_issuances DISABLE TRIGGER primary_campaign_issuances_immutable');
    try {
        $record = PrimaryCampaignIssuance::query()->sole();
        $payload = $record->payload;
        $native = [];
        match ($field) {
            'exposure' => $payload['exposure']['state'] = 'released',
            'closing' => $payload['closing']['closingSha256'] = str_repeat('0', 64),
            'membership' => $payload['holdings'] = [],
            'digest' => $native['sha256'] = str_repeat('0', 64),
            'members_digest' => $native['holdings_sha256'] = str_repeat('0', 64),
            'native' => $native['commitment_count'] = 1,
            default => throw new LogicException('Unknown conversion forgery.'),
        };
        $record->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload)), ...$native])->save();
    } finally {
        DB::statement('ALTER TABLE primary_campaign_issuances ENABLE TRIGGER primary_campaign_issuances_immutable');
    }
    expect(fn () => PrimaryIssuanceFixture::replay($campaign, $instruction))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED');
})->with(['exposure', 'closing', 'membership', 'digest', 'members_digest', 'native']);

it('never adopts a partial preexisting issue and leaves original purchase history untouched', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(settle: false);
    $tables = ['primary_reservations', 'primary_reservation_versions', 'primary_commitments', 'primary_campaign_fundings'];
    $before = array_map(fn (string $table): string => DB::table($table)->orderBy('id')->get()->toJson(), $tables);
    PrimaryHoldingFixture::issue($campaign->commitments[0]->id, $instruction->closingId);
    expect(fn () => PrimaryIssuanceFixture::replay($campaign, $instruction))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED')
        ->and(DB::table('primary_holdings')->count())->toBe(0)->and(DB::table('primary_campaign_issuances')->count())->toBe(0)
        ->and(array_map(fn (string $table): string => DB::table($table)->orderBy('id')->get()->toJson(), $tables))->toBe($before);
});

it('preserves immutable conversion history and refuses occupied rollback', function (): void {
    PrimaryIssuanceFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    foreach (['update', 'delete'] as $action) {
        expect(fn () => DB::transaction(fn () => $action === 'delete' ? DB::table('primary_campaign_issuances')->delete()
            : DB::table('primary_campaign_issuances')->update(['sha256' => str_repeat('0', 64)])))->toThrow(QueryException::class, 'Primary campaign issuance is immutable');
    }
    $migration = require database_path('migrations/2026_10_12_113137_retain_primary_successful_issuance.php');
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Retained primary issuances require a forward migration');
});

it('refuses partial retained membership rather than recreating a missing Holding', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    DB::statement('ALTER TABLE primary_holdings DISABLE TRIGGER primary_holdings_protected');
    try {
        PrimaryHolding::query()->orderBy('commitment_id')->firstOrFail()->delete();
    } finally {
        DB::statement('ALTER TABLE primary_holdings ENABLE TRIGGER primary_holdings_protected');
    }
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    expect(fn () => PrimaryIssuanceFixture::replay($campaign, $instruction))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED')
        ->and(DB::table('primary_holdings')->count())->toBe(1)->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before);
});

it('requires caller transaction even at the private producer and refuses a missing retained funding source', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close();
    $writer = app(RetainedPrimaryIssuance::class);
    expect(fn () => $writer->issue($campaign, $instruction))->toThrow(DisbursementViolation::class, 'FUNDING_TRANSACTION_REQUIRED');
    $foreign = new FundedCampaign(...array_replace(get_object_vars($campaign), ['campaignId' => PrimaryHoldingFixture::id()]));
    expect(fn () => DB::transaction(fn () => $writer->issue($foreign, $instruction)))->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE');
});

it('rejects a malformed issued closing projection at the source boundary', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close();
    $record = PrimaryCampaignIssuance::query()->sole();
    $evidence = new ClosingEvidence(...array_replace($record->payload['closing'], ['dueDates' => null]));
    app()->instance(DisbursementClosingEvidence::class, new class($evidence) implements DisbursementClosingEvidence
    {
        public function __construct(private ClosingEvidence $evidence) {}

        public function find(string $closingId): ClosingEvidence
        {
            return $this->evidence;
        }

        public function intentRequest(string $closingId): ?string
        {
            return null;
        }
    });
    expect(fn () => DB::transaction(fn (): ?array => app(IssuedHoldingEvidence::class)->find($campaign->campaignId)))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED');
});

it('enforces native conversion completeness and original bindings independently of the application', function (string $field): void {
    PrimaryIssuanceFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    $row = (array) DB::table('primary_campaign_issuances')->sole();
    DB::statement('ALTER TABLE primary_campaign_issuances DISABLE TRIGGER primary_campaign_issuances_immutable');
    try {
        DB::table('primary_campaign_issuances')->delete();
    } finally {
        DB::statement('ALTER TABLE primary_campaign_issuances ENABLE TRIGGER primary_campaign_issuances_immutable');
    }
    match ($field) {
        'principal' => $row['principal'] = (string) ((int) $row['principal'] + 5000),
        'count' => $row['commitment_count'] = 1,
        'created' => $row['created_at'] = now()->addSecond()->toIso8601String(),
        default => throw new LogicException('Unknown native conversion forgery.'),
    };
    expect(fn () => DB::transaction(function () use ($row): void {
        DB::table('primary_campaign_issuances')->insert($row);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, 'Primary issuance requires complete original funding Holdings cash and issued closing');
})->with(['principal', 'count', 'created']);

it('atomically records each purchase wallet and Business refresh once without extra financial effects on replay', function (): void {
    $approved = PrimaryIssuanceFixture::approved(units: ['540', '540', '1080'], buyers: [0 => 'same', 1 => 'same']);
    $before = (int) DB::table('change_feed')->max('id');
    $instruction = $approved['observe']('2026-01-30T22:30:00+00:00');
    $changes = DB::table('change_feed')->where('id', '>', $before)->get();
    // Each purchase is told once when the provider confirms the payout and once when its Holding issues.
    expect($changes)->toHaveCount(9)->and($changes->where('topic', 'purchase'))->toHaveCount(6)
        ->and($changes->where('topic', 'wallet'))->toHaveCount(2)->and($changes->where('topic', 'campaign'))->toHaveCount(1);
    PrimaryIssuanceFixture::replay($approved['campaign'], $instruction);
    expect(DB::table('change_feed')->where('id', '>', $before)->get()->toJson())->toBe($changes->toJson());
});

it('refuses truncated or reordered certified schedule inputs before producing a Holding envelope', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close();
    $holding = PrimaryHolding::query()->orderBy('commitment_id')->firstOrFail();
    $facts = app(HoldingSource::class)->facts($holding->commitment_id);
    $funding = app(CampaignFundingEvidence::class)->find($campaign->campaignId);
    $purchase = array_column($funding['commitments'], null, 'commitment_id')[$holding->commitment_id];
    $closing = DB::transaction(fn () => app(DisbursementClosingEvidence::class)->find($holding->disbursement_closing_id));
    foreach (['truncated', 'reordered'] as $damage) {
        $other = $facts;
        if ($damage === 'truncated') {
            array_pop($other['rights']['instalments']);
        } else {
            $other['rights']['instalments'] = array_reverse($other['rights']['instalments']);
        }
        expect(fn () => PrimaryIssuancePayload::holding($other, $purchase, $closing, $funding['funding_id'],
            $holding->id, $holding->receipt_id, $holding->payload['cash']['issue_entry_id']))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED');
    }
});

it('refuses a replay source port that returns authority for another closing without adding effects', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close();
    app()->instance(IssuedHoldingEvidence::class, new class implements IssuedHoldingEvidence
    {
        public function find(string $campaignId): array
        {
            return ['closing' => ['closingId' => PrimaryHoldingFixture::id()]];
        }
    });
    $before = [DB::table('ledger_entries')->orderBy('id')->get()->toJson(), DB::table('change_feed')->orderBy('id')->get()->toJson()];
    expect(fn () => PrimaryIssuanceFixture::replay($campaign, $instruction))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED')
        ->and([DB::table('ledger_entries')->orderBy('id')->get()->toJson(), DB::table('change_feed')->orderBy('id')->get()->toJson()])->toBe($before);
});
