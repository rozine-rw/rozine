<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessCampaignClosure;
use App\Models\LedgerEntry;
use App\Models\PrimaryCampaignFunding;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->purchase = function (bool $confirm = true): PrimaryReservationRecord {
        $investor = PrimaryReservationFixture::investor();
        $checkout = app(PrimaryCheckout::class);
        $result = $checkout->reserve($investor['user']->id, 1, $this->campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        if ($confirm) {
            $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
            expect($checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, 1,
                $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
        }

        return $root;
    };
    $this->admit = fn (string $id): array => ['campaign_id' => $id, 'publication_sha256' => $this->campaign->sha256,
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => 'Isolated persistence test, not live eligibility.']])];
    $this->fund = fn (): array => app(PrimaryFunding::class)->lock($this->campaign->id, $this->admit);
});

it('retains one full funding lock without paying issuing or double counting exposure', function (string $instant): void {
    ($this->purchase)();
    ($this->purchase)();
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $exposure = app(BusinessExposureStore::class)->current($this->campaign->business_id);
    $this->travelTo(match ($instant) {
        'live' => now(), 'deadline' => $this->campaign->expires_at, 'late' => $this->campaign->expires_at->addDay(),
        default => throw new InvalidArgumentException('Unknown funding instant.'),
    });
    $funding = ($this->fund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect($funding)->toMatchArray(['scope' => 'primary-funding-v1', 'principal' => '10800000',
        'exposure_reservation_id' => $this->campaign->exposure_reservation_id, 'publication_sha256' => $this->campaign->sha256])
        ->and($funding['commitments'])->toHaveCount(2)
        ->and(array_sum(array_column($funding['commitments'], 'units')))->toBe(2160)
        ->and(($this->fund)())->toBe($funding)
        ->and(app(CampaignFundingEvidence::class)->find($this->campaign->id))->toBe($funding)
        ->and(PrimaryCampaignFunding::query()->count())->toBe(1)
        ->and(DB::table('primary_funding_commitments')->count())->toBe(2)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($exposure);
    $page = app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
    expect($page['lifecycle'])->toBe('funded_pending_disbursement')->and($page['progress']['phase'])->toBe('funded')
        ->and($page['progress'])->toBe(['phase' => 'funded', 'lifecycle' => 'funded_pending_disbursement', 'restriction' => null,
            'committed' => ['currency' => 'RWF', 'amount' => '10800000'], 'investors' => 2,
            'funded_at' => $funding['recorded_at'], 'closing' => ['stage' => 'awaiting_disbursement']])
        ->and($page['can_cancel'])->toBeFalse();
    $this->travelTo(now()->addHour());
    $again = app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
    expect($again['progress'])->toBe($page['progress'])
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash);
})->with(['live', 'deadline', 'late']);

it('requires explicit admission and preserves its original evidence on a checked retry', function (): void {
    ($this->purchase)();
    ($this->purchase)();
    expect(fn () => app(PrimaryFunding::class)->lock($this->campaign->id, fn (): array => []))->toThrow(CommandRejection::class, 'POLICY_INPUT_REQUIRED')
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0);
    $funding = ($this->fund)();
    $checks = 0;
    expect(app(PrimaryFunding::class)->lock($this->campaign->id, function () use (&$checks, $funding): array {
        $checks++;

        return [...$funding['admission'], 'different_current_evidence' => 'does not replace retained terms'];
    }))->toBe($funding)->and($checks)->toBe(1);
    expect(fn () => app(PrimaryFunding::class)->lock($this->campaign->id, function (): array {
        throw new CommandRejection('POLICY_INPUT_REQUIRED');
    }))->toThrow(CommandRejection::class, 'POLICY_INPUT_REQUIRED')->and(PrimaryCampaignFunding::query()->count())->toBe(1);
});

it('refuses empty partial held or returned cash as full funding', function (string $state): void {
    if ($state === 'returned') {
        $root = ($this->purchase)();
        ($this->purchase)();
        $wallets = app(WalletPostings::class);
        $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
    } elseif ($state !== 'empty') {
        ($this->purchase)($state !== 'held');
    }
    expect(fn () => ($this->fund)())->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FULLY_COMMITTED')
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0);
})->with(['empty', 'partial', 'held', 'returned']);

it('refuses new purchase input cancellation expiry and unilateral refunds after funding', function (): void {
    $root = ($this->purchase)();
    ($this->purchase)();
    ($this->fund)();
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    expect(fn () => app(PrimaryCampaignSource::class)->lock($this->campaign->id))->toThrow(CommandRejection::class, 'CAMPAIGN_FUNDED');
    $store = app(BusinessCampaignStore::class);
    expect($store->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id, 1, null, (string) Str::uuid())['code'])->toBe('CAMPAIGN_FUNDED');
    $wallets = app(WalletPostings::class);
    expect(fn () => DB::transaction(fn () => $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
        new PostingSource('primary_reservation', $root->id, $root->origin_operation_id))))->toThrow(QueryException::class, 'authoritative failed closing');
    $this->travelTo($this->campaign->expires_at);
    expect($store->expireDue(1))->toBe(0)->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash);
});

it('refuses raw held revisions and unfunded closure after funding', function (): void {
    $root = ($this->purchase)();
    ($this->purchase)();
    ($this->fund)();
    expect(fn () => DB::transaction(fn () => DB::table('primary_reservation_versions')->insert([
        'id' => strtolower((string) Str::ulid()), 'primary_reservation_id' => $root->id, 'revision' => 3,
        'state' => 'held', 'payload' => 'ignored', 'sha256' => str_repeat('0', 64), 'created_at' => now(),
    ])))->toThrow(QueryException::class, 'funded campaign cannot admit');
    expect(fn () => DB::transaction(fn () => DB::table('business_campaign_closures')->insert([
        'id' => strtolower((string) Str::ulid()), 'business_campaign_id' => $this->campaign->id, 'business_id' => $this->campaign->business_id,
        'exposure_reservation_id' => $this->campaign->exposure_reservation_id, 'principal' => $this->campaign->principal,
        'phase' => 'cancelled', 'actor_user_id' => $this->campaign->actor_user_id, 'closed_at' => now(),
        'payload' => 'ignored', 'sha256' => str_repeat('0', 64), 'created_at' => now(),
    ])))->toThrow(QueryException::class, 'authoritative failed closing or issue');
});

it('rolls the funding record and every membership back when persistence fails', function (): void {
    ($this->purchase)();
    ($this->purchase)();
    DB::listen(function (QueryExecuted $event): void {
        if (str_starts_with($event->sql, 'insert into "primary_funding_commitments"')) {
            throw new RuntimeException('Synthetic membership failure.');
        }
    });
    expect(fn () => ($this->fund)())->toThrow(RuntimeException::class, 'Synthetic membership failure.')
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0)->and(DB::table('primary_funding_commitments')->count())->toBe(0);
});

it('protects the funding record and its membership from mutation', function (string $target): void {
    ($this->purchase)();
    ($this->purchase)();
    ($this->fund)();
    expect(fn () => DB::transaction(fn () => $target === 'funding'
        ? PrimaryCampaignFunding::query()->update(['sha256' => str_repeat('0', 64)])
        : DB::table('primary_funding_commitments')->delete()))->toThrow(QueryException::class, 'immutable');
})->with(['funding', 'membership']);

it('fails closed when retained funding evidence is corrupted', function (string $damage): void {
    ($this->purchase)();
    ($this->purchase)();
    ($this->fund)();
    $funding = PrimaryCampaignFunding::query()->sole();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE primary_campaign_fundings DISABLE TRIGGER primary_funding_immutable');
    try {
        $payload = $funding->payload;
        $payload[$damage] = 'corrupted';
        $funding->forceFill(['payload' => $payload, 'sha256' => $damage === 'principal'
            ? hash('sha256', app(CanonicalJson::class)->encode($payload)) : $funding->sha256])->save();
    } finally {
        DB::statement('ALTER TABLE primary_campaign_fundings ENABLE TRIGGER primary_funding_immutable');
    }
    expect(fn () => app(CampaignFundingEvidence::class)->find($this->campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
})->with(['scope', 'principal']);

it('refuses incomplete or misbound raw funding even when the application port is bypassed', function (string $damage): void {
    $root = ($this->purchase)();
    ($this->purchase)();
    DB::beginTransaction();
    ($this->fund)();
    $record = (array) DB::table('primary_campaign_fundings')->sole();
    $members = DB::table('primary_funding_commitments')->orderBy('reservation_id')->get()->map(fn (object $row): array => (array) $row)->all();
    DB::rollBack();
    if ($damage === 'returned') {
        $wallets = app(WalletPostings::class);
        $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
    }
    expect(fn () => DB::transaction(function () use ($record, $members, $damage): void {
        DB::table('primary_campaign_fundings')->insert($record);
        if ($damage === 'missing') {
            $members = [];
        } elseif ($damage === 'partial') {
            $members = [$members[0]];
        } elseif ($damage === 'wrong_cash') {
            $members[0]['commit_entry_id'] = $members[0]['hold_entry_id'];
        } elseif ($damage === 'wrong_wallet') {
            $members[0]['wallet_id'] = $members[1]['wallet_id'];
        }
        foreach ($members as $member) {
            DB::table('primary_funding_commitments')->insert($member);
        }
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, in_array($damage, ['wrong_cash', 'wrong_wallet'], true)
        ? 'original commitment and cash' : 'complete confirmed principal')
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0);
})->with(['missing', 'partial', 'returned', 'wrong_cash', 'wrong_wallet']);

it('does not fund a closed or not-yet-live publication', function (string $state): void {
    $store = app(BusinessCampaignStore::class);
    if ($state === 'early') {
        $this->travelTo($this->campaign->live_at->subMicrosecond());
    } elseif ($state === 'cancelled') {
        expect($store->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id, 1, null, (string) Str::uuid())['code'])->toBe('CAMPAIGN_CANCELLED');
    } else {
        $this->travelTo($this->campaign->expires_at);
        expect($store->expireDue(1))->toBe(1);
    }
    expect(fn () => ($this->fund)())->toThrow(CommandRejection::class, 'CAMPAIGN_CLOSED')
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0);
})->with(['early', 'cancelled', 'expired']);

it('records no funding for mismatched failed or unavailable admission', function (string $damage, string $expected): void {
    ($this->purchase)();
    ($this->purchase)();
    $admission = ($this->admit)($this->campaign->id);
    if ($damage === 'binding') {
        $admission['publication_sha256'] = str_repeat('0', 64);
    } elseif ($damage === 'campaign') {
        $admission['campaign_id'] = strtolower((string) Str::ulid());
    } elseif ($damage === 'failed') {
        $admission['eligibility']['status'] = 'failed';
    } else {
        unset($admission['destination']);
    }
    expect(fn () => app(PrimaryFunding::class)->lock($this->campaign->id, fn (): array => $admission))->toThrow(CommandRejection::class, $expected)
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0);
})->with([
    ['binding', 'FUNDING_ADMISSION_MISMATCH'], ['campaign', 'FUNDING_ADMISSION_MISMATCH'],
    ['failed', 'FUNDING_PRECHECK_FAILED'], ['missing', 'POLICY_INPUT_REQUIRED'],
]);

it('refuses economic snapshot tampering even if its funding digest was recomputed', function (string $field): void {
    ($this->purchase)();
    ($this->purchase)();
    ($this->fund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $funding = PrimaryCampaignFunding::query()->sole();
    $payload = $funding->payload;
    $payload['commitments'][0][$field] = $field === 'principal' ? '1' : ['tampered' => 'must not become purchased terms'];
    DB::statement('ALTER TABLE primary_campaign_fundings DISABLE TRIGGER primary_funding_immutable');
    try {
        $funding->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE primary_campaign_fundings ENABLE TRIGGER primary_funding_immutable');
    }
    expect(fn () => app(CampaignFundingEvidence::class)->find($this->campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
})->with(['terms', 'rights', 'principal']);

it('rejects a conflicting retained commitment set instead of rewriting a checked funding retry', function (): void {
    ($this->purchase)();
    ($this->purchase)();
    $payload = ($this->fund)();
    $payload['commitments'][0]['commitment_id'] = strtolower((string) Str::ulid());
    app()->instance(CampaignFundingEvidence::class, new class($payload) implements CampaignFundingEvidence
    {
        /** @param array<string, mixed> $payload */
        public function __construct(private array $payload) {}

        public function find(string $campaignId): array
        {
            return $this->payload;
        }
    });
    expect(fn () => ($this->fund)())->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED')
        ->and(PrimaryCampaignFunding::query()->count())->toBe(1);
});

it('refuses absent source facts before acquiring purchases and retains prior funding on a refused retry', function (string $check): void {
    ($this->purchase)();
    ($this->purchase)();
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $admission = ($this->admit)($this->campaign->id);
    $admission[$check] = ['status' => 'passed', 'evidence' => [null]];
    $purchaseLocks = 0;
    DB::listen(function (QueryExecuted $event) use (&$purchaseLocks): void {
        if (str_contains($event->sql, 'from "primary_reservations"') && str_contains($event->sql, 'for update')) {
            $purchaseLocks++;
        }
    });
    expect(fn () => app(PrimaryFunding::class)->lock($this->campaign->id, fn (): array => $admission))
        ->toThrow(CommandRejection::class, 'POLICY_INPUT_REQUIRED')
        ->and($purchaseLocks)->toBe(0)
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0)
        ->and(DB::table('primary_funding_commitments')->count())->toBe(0)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash);
    $funding = ($this->fund)();
    $purchaseLocks = 0;
    expect(fn () => app(PrimaryFunding::class)->lock($this->campaign->id, fn (): array => $admission))
        ->toThrow(CommandRejection::class, 'POLICY_INPUT_REQUIRED')
        ->and($purchaseLocks)->toBe(0)
        ->and(app(CampaignFundingEvidence::class)->find($this->campaign->id))->toBe($funding)
        ->and(PrimaryCampaignFunding::query()->count())->toBe(1)
        ->and(DB::table('primary_funding_commitments')->count())->toBe(2)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash);
})->with(['eligibility', 'policy', 'connections', 'destination']);

it('refuses retained funding with absent admission facts even when its digest is consistent', function (string $check): void {
    ($this->purchase)();
    ($this->purchase)();
    ($this->fund)();
    $funding = PrimaryCampaignFunding::query()->sole();
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE primary_campaign_fundings DISABLE TRIGGER primary_funding_immutable');
    try {
        $payload = $funding->payload;
        $payload['admission'][$check]['evidence'] = ['source' => ['id' => null]];
        $funding->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE primary_campaign_fundings ENABLE TRIGGER primary_funding_immutable');
    }
    expect(fn () => app(CampaignFundingEvidence::class)->find($this->campaign->id))
        ->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED')
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash);
})->with(['eligibility', 'policy', 'connections', 'destination']);

it('evaluates server admission under Business before campaign or wallet locks', function (): void {
    ($this->purchase)();
    ($this->purchase)();
    $queries = [];
    DB::listen(function (QueryExecuted $event) use (&$queries): void {
        if (str_contains($event->sql, 'for update')) {
            $queries[] = $event->sql;
        }
    });
    app(PrimaryFunding::class)->lock($this->campaign->id, function () use (&$queries): array {
        expect($queries)->toHaveCount(1)->and($queries[0])->toContain('"business_profiles"');

        return ($this->admit)($this->campaign->id);
    });
});

it('will not seed financial funding authority from an unbound factory', function (): void {
    expect(fn () => DB::transaction(fn () => PrimaryCampaignFunding::factory()->create()))->toThrow(QueryException::class)
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0);
});

it('refuses rolling the migration back once funding evidence exists', function (): void {
    ($this->purchase)();
    ($this->purchase)();
    ($this->fund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $migration = require database_path('migrations/2026_09_30_054318_create_primary_campaign_fundings.php');
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'forward migration')
        ->and(PrimaryCampaignFunding::query()->count())->toBe(1);
});

it('can reverse and reapply the empty funding migration', function (): void {
    $heldGenerations = require database_path('migrations/2026_10_03_083345_create_primary_held_claim_generations.php');
    $heldGenerations->down();
    $entryIndex = require database_path('migrations/2026_09_30_171842_index_wallet_ledger_lines_by_entry.php');
    $entryIndex->down();
    $refundReceipts = require database_path('migrations/2026_09_30_120729_bind_primary_refund_receipts_to_returned_cash.php');
    $refundReceipts->down();
    $migration = require database_path('migrations/2026_09_30_054318_create_primary_campaign_fundings.php');
    $holdingBinding = require database_path('migrations/2026_09_30_084737_bind_primary_holdings_to_retained_commitments.php');
    $holdingIssue = require database_path('migrations/2026_09_30_114217_require_issue_evidence_for_primary_holdings.php');
    $issuedCompleteness = require database_path('migrations/2026_09_30_234802_require_complete_primary_holdings_for_issued_closings.php');
    $issuedCompleteness->down();
    $holdingIssue->down();
    $holdingBinding->down();
    $migration->down();
    expect(Schema::hasTable('primary_campaign_fundings'))->toBeFalse();
    $migration->up();
    $refundReceipts->up();
    $entryIndex->up();
    $holdingBinding->up();
    $holdingIssue->up();
    $issuedCompleteness->up();
    $heldGenerations->up();
    expect(DB::scalar("SELECT count(*) FROM pg_trigger WHERE tgname IN ('primary_issued_closing_complete', 'primary_funded_closing_complete')"))->toBe(2);
    expect(Schema::hasTable('primary_campaign_fundings'))->toBeTrue();
});

it('refuses malformed funding membership snapshots without losing the integrity refusal', function (string $damage): void {
    ($this->purchase)();
    ($this->purchase)();
    ($this->fund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $funding = PrimaryCampaignFunding::query()->sole();
    $payload = $funding->payload;
    if ($damage === 'list') {
        $payload['commitments'] = 'malformed';
    } else {
        unset($payload['commitments'][0]['cash']['commit_entry_id']);
    }
    DB::statement('ALTER TABLE primary_campaign_fundings DISABLE TRIGGER primary_funding_immutable');
    try {
        $funding->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE primary_campaign_fundings ENABLE TRIGGER primary_funding_immutable');
    }
    expect(fn () => app(CampaignFundingEvidence::class)->find($this->campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
})->with(['list', 'cash']);

it('reports distinct funded investors from retained membership across multiple purchases', function (): void {
    $one = PrimaryReservationFixture::investor();
    $two = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    foreach ([[$one, '540'], [$one, '540'], [$two, '1080']] as [$investor, $units]) {
        $result = $checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        expect($checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    }
    $funding = ($this->fund)();
    $page = app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
    expect($funding['commitments'])->toHaveCount(3)->and($page['progress']['investors'])->toBe(2)
        ->and($page['progress']['committed'])->toBe(['currency' => 'RWF', 'amount' => '10800000'])
        ->and($page['progress']['funded_at'])->toBe($funding['recorded_at'])
        ->and($page['progress']['closing'])->toBe(['stage' => 'awaiting_disbursement']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});
