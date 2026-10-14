<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Business\Contracts\CampaignClosureEvidence;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\FundedCommitment;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\ReturnedCash;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Primary\EloquentFundedCampaigns;
use App\Infrastructure\Primary\RetainedFundedClosing;
use App\Models\BusinessCampaignClosure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\PrimaryFailedClosingFixture;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(fn () => $this->freezeSecond());

it('atomically refunds all original funded principal and releases exposure once for authentic failure', function (string $cause): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close($cause);
    PrimaryHoldingFixture::flushDeferredChecks();
    $closure = BusinessCampaignClosure::query()->sole();
    expect($closure->id)->toBe($instruction->closingId)->and($closure->phase)->toBe('failed_closing')
        ->and($closure->payload['closing']['cause'])->toBe($cause)
        ->and($closure->payload['committed_refunded'])->toBe(['currency' => 'RWF', 'amount' => $campaign->principal])
        ->and(DB::table('primary_holdings')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($campaign->businessId))->toBe([])
        ->and(DB::table('primary_campaign_closure_returns')->count())->toBe(count($campaign->commitments));
    foreach ($campaign->commitments as $commitment) {
        $root = DB::table('primary_commitments')->where('id', $commitment->id)->value('primary_reservation_id');
        $refund = DB::table('ledger_entries')->where('source_id', $root)->where('kind', 'primary_refund')->sole();
        $lines = DB::table('ledger_lines')->where('entry_id', $refund->id)->orderBy('direction')->get();
        expect($lines)->toHaveCount(2)->and($lines[0]->amount)->toBe($commitment->principal)->and($lines[1]->amount)->toBe($commitment->principal);
    }
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    PrimaryFailedClosingFixture::replay($campaign, $instruction);
    PrimaryHoldingFixture::flushDeferredChecks();
    expect(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(BusinessCampaignClosure::query()->count())->toBe(1)
        ->and(DB::table('primary_failed_closings')->count())->toBe(1);
})->with(['approve_recheck', 'worker_recheck', 'reconciled_failure']);

it('rolls back every financial effect when any refund binding fails', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close(settle: false);
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    DB::unprepared("CREATE FUNCTION fail_terminal_return() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'synthetic terminal return failure'; END; $$; CREATE TRIGGER fail_terminal_return BEFORE INSERT ON primary_campaign_closure_returns FOR EACH ROW EXECUTE FUNCTION fail_terminal_return()");
    try {
        expect(fn () => PrimaryFailedClosingFixture::replay($campaign, $instruction))->toThrow(QueryException::class, 'synthetic terminal return failure');
    } finally {
        DB::unprepared('DROP TRIGGER fail_terminal_return ON primary_campaign_closure_returns; DROP FUNCTION fail_terminal_return()');
    }
    expect(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)->and(DB::table('primary_failed_closings')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($campaign->businessId))->toHaveCount(1);
    PrimaryFailedClosingFixture::replay($campaign, $instruction);
    PrimaryHoldingFixture::flushDeferredChecks();
});

it('refuses altered instructions or stale foreign campaign roots before any financial writes', function (string $change): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close(settle: false);
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    $fields = get_object_vars($campaign);
    $call = get_object_vars($instruction);
    if (array_key_exists($change, $fields) && $change !== 'commitments') {
        $fields[$change] = $change === 'termMonths' ? $campaign->termMonths + 1 : PrimaryHoldingFixture::id();
        $campaign = new FundedCampaign(...$fields);
    } elseif ($change === 'commitments') {
        $purchase = get_object_vars($campaign->commitments[0]);
        $purchase['originOperationId'] = PrimaryHoldingFixture::id();
        $fields['commitments'][0] = new FundedCommitment(...$purchase);
        $campaign = new FundedCampaign(...$fields);
    } else {
        $call[$change] = match ($change) {
            'cause' => 'worker_recheck', 'causes' => ['exposure'], default => PrimaryHoldingFixture::id(),
        };
        $instruction = new FailedClosing(...$call);
    }
    expect(fn () => PrimaryFailedClosingFixture::replay($campaign, $instruction))->toThrow(match ($change) {
        'businessId', 'campaignId' => CommandRejection::class,
        'closingId' => DisbursementViolation::class, default => RuntimeException::class,
    })
        ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(DB::table('business_campaign_closures')->count())->toBe(0)
        ->and(DB::table('primary_failed_closings')->count())->toBe(0);
})->with(['businessId', 'campaignId', 'exposureReservationId', 'termMonths', 'fundedAt', 'commitments', 'closingId', 'disbursementId', 'cause', 'causes']);

it('keeps pending unknown and success observations from becoming failed-closing refunds', function (string $state): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close($state, settle: false);
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    expect(fn () => PrimaryFailedClosingFixture::replay($campaign, $instruction))->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_UNAVAILABLE')
        ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(DB::table('primary_failed_closings')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($campaign->businessId))->toHaveCount(1);
})->with(['pending', 'unknown', 'succeeded']);

it('preserves immutable histories and refuses occupied rollback', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close(settle: false);
    $tables = ['primary_reservations', 'primary_reservation_versions', 'primary_commitments', 'primary_campaign_fundings', 'primary_funding_commitments'];
    $before = array_map(fn (string $table): string => DB::table($table)->orderBy($table === 'primary_funding_commitments' ? 'commitment_id' : 'id')->get()->toJson(), $tables);
    PrimaryFailedClosingFixture::replay($campaign, $instruction);
    PrimaryHoldingFixture::flushDeferredChecks();
    expect(array_map(fn (string $table): string => DB::table($table)->orderBy($table === 'primary_funding_commitments' ? 'commitment_id' : 'id')->get()->toJson(), $tables))->toBe($before);
    foreach (['update', 'delete'] as $action) {
        expect(fn () => DB::transaction(fn () => $action === 'delete' ? DB::table('primary_failed_closings')->delete()
            : DB::table('primary_failed_closings')->update(['closing_sha256' => str_repeat('0', 64)])))->toThrow(QueryException::class);
    }
    $migration = require database_path('migrations/2026_10_12_054843_retain_primary_terminal_failed_closings.php');
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Retained terminal funded failures require a forward migration');
});

it('refuses digest-consistent forged retained closing provenance when projecting exposure release', function (string $field): void {
    ['campaign' => $campaign] = PrimaryFailedClosingFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    DB::statement('ALTER TABLE business_campaign_closures DISABLE TRIGGER business_campaign_closures_protected');
    try {
        $closure = BusinessCampaignClosure::query()->sole();
        $payload = $closure->payload;
        match ($field) {
            'digest' => $payload['closing']['closingSha256'] = str_repeat('0', 64),
            'funding' => $payload['funding_id'] = PrimaryHoldingFixture::id(),
            'shape' => $payload['closing'] = null,
            default => throw new LogicException('Unknown provenance forgery.'),
        };
        $closure->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE business_campaign_closures ENABLE TRIGGER business_campaign_closures_protected');
    }
    expect(fn () => app(BusinessExposureStore::class)->current($campaign->businessId))->toThrow(RuntimeException::class, 'CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
})->with(['digest', 'funding', 'shape']);

it('refuses a relational failed-closing binding with no complete terminal refund closure at commit', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close(settle: false);
    $evidence = DB::transaction(fn () => app(RetainedFundedClosing::class)->failed($campaign, $instruction));
    expect(fn () => DB::transaction(function () use ($campaign, $instruction, $evidence): void {
        DB::table('primary_failed_closings')->insert(['disbursement_closing_id' => $instruction->closingId,
            'business_campaign_closure_id' => $instruction->closingId,
            'funding_id' => DB::table('primary_campaign_fundings')->where('business_campaign_id', $campaign->campaignId)->value('id'),
            'closing_sha256' => $evidence->closingSha256, 'disbursement_sha256' => $evidence->disbursementSha256]);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class)
        ->and(DB::table('primary_failed_closings')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($campaign->businessId))->toHaveCount(1);
});

it('refuses corrupted complete-return membership even when native immutability was bypassed', function (): void {
    ['campaign' => $campaign] = PrimaryFailedClosingFixture::close();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE primary_campaign_closure_returns DISABLE TRIGGER primary_closure_returns_protected');
    try {
        DB::table('primary_campaign_closure_returns')->where('primary_reservation_id', DB::table('primary_campaign_closure_returns')->value('primary_reservation_id'))->update(['principal' => '5000']);
    } finally {
        DB::statement('ALTER TABLE primary_campaign_closure_returns ENABLE TRIGGER primary_closure_returns_protected');
    }
    expect(fn () => app(BusinessExposureStore::class)->current($campaign->businessId))->toThrow(RuntimeException::class, 'CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
});

it('refuses issue without closing authority after failure and refuses attempts to admit reused confirmed ordinals', function (): void {
    ['campaign' => $campaign] = PrimaryFailedClosingFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    $id = PrimaryHoldingFixture::id();
    expect(fn () => DB::transaction(fn () => app(EloquentFundedCampaigns::class)->issue($campaign,
        new IssueInstruction($id, $id, $id, now()->toIso8601String(), now()->toDateString(), [], now()->toIso8601String()))))
        ->toThrow(DisbursementViolation::class, 'DISBURSEMENT_CLOSING_UNAVAILABLE')
        ->and(DB::table('primary_holdings')->count())->toBe(0);
    expect(fn () => DB::transaction(function (): void {
        $root = (array) DB::table('primary_reservations')->first();
        $root['id'] = PrimaryHoldingFixture::id();
        DB::table('primary_reservations')->insert($root);
    }))->toThrow(QueryException::class);
});

it('refunds separate commitments to their original Party and counts distinct investors', function (): void {
    ['campaign' => $campaign] = PrimaryFailedClosingFixture::close(units: ['540', '540', '1080'], buyers: [0 => 'same', 1 => 'same']);
    PrimaryHoldingFixture::flushDeferredChecks();
    expect($campaign->partyIds())->toHaveCount(2)->and($campaign->commitments)->toHaveCount(3)
        ->and(BusinessCampaignClosure::query()->sole()->payload['investors'])->toBe(2)
        ->and(DB::table('ledger_entries')->where('kind', 'primary_refund')->count())->toBe(3)
        ->and(app(BusinessExposureStore::class)->current($campaign->businessId))->toBe([]);
});

it('reverses an empty forward migration and reinstalls it without weakening the earlier gates', function (): void {
    $migration = require database_path('migrations/2026_10_12_054843_retain_primary_terminal_failed_closings.php');
    $migration->down();
    expect(Schema::hasTable('primary_failed_closings'))->toBeFalse();
    $migration->up();
    expect(Schema::hasTable('primary_failed_closings'))->toBeTrue();
    ['campaign' => $campaign] = PrimaryFailedClosingFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    expect(app(BusinessExposureStore::class)->current($campaign->businessId))->toBe([]);
});

it('authenticates the entire previous terminal closure on replay', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE business_campaign_closures DISABLE TRIGGER business_campaign_closures_protected');
    try {
        $closure = BusinessCampaignClosure::query()->sole();
        $payload = $closure->payload;
        $payload['committed_refunded']['amount'] = '0';
        $closure->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE business_campaign_closures ENABLE TRIGGER business_campaign_closures_protected');
    }
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    expect(fn () => PrimaryFailedClosingFixture::replay($campaign, $instruction))->toThrow(RuntimeException::class, 'CAMPAIGN_CLOSURE_INTEGRITY_FAILED')
        ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before);
});

it('refuses corrupted terminal phase instead of acknowledging a replay', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE business_campaign_closures DISABLE TRIGGER business_campaign_closures_protected');
    try {
        BusinessCampaignClosure::query()->update(['phase' => 'expired']);
    } finally {
        DB::statement('ALTER TABLE business_campaign_closures ENABLE TRIGGER business_campaign_closures_protected');
    }
    expect(fn () => PrimaryFailedClosingFixture::replay($campaign, $instruction))->toThrow(RuntimeException::class, 'PRIMARY_CLOSING_MISMATCH');
});

it('refuses inconsistent returned-cash evidence and rolls back the journal write', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close(settle: false);
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    $cash = $this->createMock(PrimaryReturnedCash::class);
    $cash->expects($this->once())->method('requireReturned')->willReturn(new ReturnedCash('', null, '', 'primary_release', '', '', '', '0', now()->toDateTimeImmutable()));
    app()->instance(PrimaryReturnedCash::class, $cash);
    expect(fn () => PrimaryFailedClosingFixture::replay($campaign, $instruction))->toThrow(RuntimeException::class, 'PRIMARY_CLOSING_MISMATCH')
        ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(DB::table('primary_failed_closings')->count())->toBe(0);
});

it('refuses replay when the retained closure port supplies inconsistent ancestry', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close();
    $closure = $this->createMock(CampaignClosureEvidence::class);
    $closure->expects($this->once())->method('find')->with($campaign->campaignId)->willReturn(['closing' => []]);
    app()->instance(CampaignClosureEvidence::class, $closure);
    expect(fn () => PrimaryFailedClosingFixture::replay($campaign, $instruction))->toThrow(RuntimeException::class, 'CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
});
