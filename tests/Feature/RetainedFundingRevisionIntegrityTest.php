<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\HoldingSource;
use App\Infrastructure\Business\RetainedFundedCampaignFacts;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Mockery\Expectation;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(fn () => $this->freezeSecond());

it('refuses an unauthenticated earlier disclosure before projecting a funded commitment', function (int $revision): void {
    ['campaign' => $campaign, 'commitments' => [$commitment]] = PrimaryHoldingFixture::committed(requoted: [0]);
    $source = app(RetainedFundedCampaignFacts::class);
    $before = $source->find($campaign->id);
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $commitment->primary_reservation_id)
        ->where('revision', $revision)->sole();
    $ledger = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    DB::beginTransaction();
    try {
        DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER primary_reservation_versions_immutable');
        $version->forceFill(['sha256' => str_repeat('0', 64)])->save();
        expect(app(CampaignFundingEvidence::class)->find($campaign->id))->toBeArray()
            ->and(fn () => $source->find($campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED')
            ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($ledger);
    } finally {
        DB::rollBack();
    }
    expect($source->find($campaign->id))->toEqual($before);
})->with(['original hold' => 1, 'requote' => 2]);

it('refuses digest-consistent disclosure ancestry forgeries without altering funding or cash', function (): void {
    ['campaign' => $campaign, 'commitments' => [$commitment]] = PrimaryHoldingFixture::committed(requoted: [0]);
    [$held, $requote, $confirmed] = PrimaryReservationVersion::query()->where('primary_reservation_id', $commitment->primary_reservation_id)
        ->orderBy('revision')->get()->all();
    $source = app(RetainedFundedCampaignFacts::class);
    $before = $source->find($campaign->id);
    $funding = app(CampaignFundingEvidence::class)->find($campaign->id);
    $rewrite = function (PrimaryReservationVersion $version, array $payload): string {
        $digest = hash('sha256', app(CanonicalJson::class)->encode($payload));
        PrimaryReservationVersion::query()->whereKey($version->id)->sole()->forceFill(['payload' => $payload, 'sha256' => $digest])->save();

        return $digest;
    };
    foreach ([
        'original operation' => fn () => $rewrite($held, [...$held->payload, 'operation_id' => 'foreign']),
        'requote parent' => fn () => PrimaryReservationVersion::query()->whereKey($requote->id)->update(['previous_sha256' => str_repeat('0', 64)]),
        'unexpected terminal state' => fn () => PrimaryReservationVersion::query()->whereKey($requote->id)->update(['state' => 'expired']),
        're-signed recorded instant' => function () use ($rewrite, $requote, $confirmed): void {
            $digest = $rewrite($requote, [...$requote->payload, 'recorded_at' => '2099-01-01T00:00:00.000000Z']);
            $rewrite($confirmed, [...$confirmed->payload, 'previous_sha256' => $digest]);
            PrimaryReservationVersion::query()->whereKey($confirmed->id)->update(['previous_sha256' => $digest]);
        },
    ] as $corrupt) {
        DB::beginTransaction();
        try {
            DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER primary_reservation_versions_immutable');
            $corrupt();
            expect(app(CampaignFundingEvidence::class)->find($campaign->id))->toBeArray()
                ->and(fn () => $source->find($campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED');
        } finally {
            DB::rollBack();
        }
    }
    expect(app(CampaignFundingEvidence::class)->find($campaign->id))->toBe($funding)
        ->and($source->find($campaign->id))->toEqual($before);
});

it('binds every replayed purchase fact before using it in a funded campaign', function (): void {
    ['campaign' => $campaign, 'commitments' => [$commitment]] = PrimaryHoldingFixture::committed(requoted: [0]);
    $genuine = app(HoldingSource::class);
    $retained = $genuine->campaignFacts($campaign->id);
    $ledger = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    foreach (['business_campaign_id', 'commitment_id', 'primary_reservation_id', 'party_id', 'units', 'principal',
        'ordinals', 'rights', 'terms', 'confirmation_version_id', 'missing'] as $field) {
        $facts = $retained[$commitment->id];
        if ($field === 'missing') {
            unset($facts['confirmation_version_id']);
        } else {
            $facts[$field] = match ($field) {
                'units' => $facts[$field] + 1,
                'ordinals', 'rights', 'terms' => [],
                default => 'foreign',
            };
        }
        $source = $this->mock(HoldingSource::class);
        $source->shouldReceive('campaignFacts');
        $expectation = $source->mockery_findExpectation('campaignFacts', []);
        if (! $expectation instanceof Expectation) {
            throw new RuntimeException('The expected purchase test boundary was not registered.');
        }
        $expectation->andReturnUsing(fn (string $id): array => $id === $campaign->id ? [...$retained, $commitment->id => $facts] : $genuine->campaignFacts($id));
        $source->shouldNotReceive('facts', 'verify');
        $projection = app(RetainedFundedCampaignFacts::class);
        expect(fn () => $projection->find($campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
    }
    expect(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($ledger);
});

it('propagates unavailable purchase authority without treating it as a failed campaign', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $failure = new RuntimeException('PRIMARY_HOLDING_SOURCE_UNAVAILABLE');
    $source = new class($failure) implements HoldingSource
    {
        public function __construct(private RuntimeException $failure) {}

        public function facts(string $commitmentId): array
        {
            throw $this->failure;
        }

        public function campaignFacts(string $campaignId): array
        {
            throw $this->failure;
        }

        public function verify(string $holdingId): void
        {
            throw new RuntimeException('Projection must not require a persisted Holding.');
        }
    };
    $projection = new RetainedFundedCampaignFacts(app(CampaignFundingEvidence::class), app(PublishedCampaignEvidence::class), app(CanonicalJson::class), $source);
    $ledger = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    try {
        $projection->find($campaign->id);
        throw new LogicException('Unavailable source was accepted.');
    } catch (RuntimeException $exception) {
        expect($exception)->toBe($failure);
    }
    expect(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($ledger)
        ->and(DB::table('disbursement_closings')->count())->toBe(0);
});

it('requires the exact campaign purchase membership while allowing extra verified fact metadata', function (string $damage): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed(requoted: [1]);
    $facts = app(HoldingSource::class)->campaignFacts($campaign->id);
    if ($damage === 'missing') {
        unset($facts[$commitments[0]->id]);
    } elseif ($damage === 'extra') {
        $facts[PrimaryHoldingFixture::id()] = $facts[$commitments[0]->id];
    } else {
        $facts = array_map(fn (array $fact): array => [...$fact, 'source_metadata' => ['verified' => true]], $facts);
    }
    $source = $this->mock(HoldingSource::class);
    $source->shouldReceive('campaignFacts');
    $expectation = $source->mockery_findExpectation('campaignFacts', []);
    if (! $expectation instanceof Expectation) {
        throw new RuntimeException('The expected campaign test boundary was not registered.');
    }
    $expectation->once()->with($campaign->id)->andReturn($facts);
    $source->shouldNotReceive('facts', 'verify');
    $projection = app(RetainedFundedCampaignFacts::class);
    $ledger = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    $funding = app(CampaignFundingEvidence::class)->find($campaign->id);
    if ($damage === 'metadata') {
        expect($projection->find($campaign->id)->commitments)->toHaveCount(count($commitments));
    } else {
        expect(fn () => $projection->find($campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
    }
    expect(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($ledger)
        ->and(app(CampaignFundingEvidence::class)->find($campaign->id))->toBe($funding)
        ->and(DB::table('disbursement_closings')->count())->toBe(0);
})->with(['missing', 'extra', 'metadata']);
