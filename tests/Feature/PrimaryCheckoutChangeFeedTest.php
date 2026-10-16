<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Operations\ReadChanges;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\ChangeCursor;
use App\Domain\Operations\ChangeScope;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Models\BusinessCampaign;
use App\Models\Party;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
});

/** @return array<string, string> */
function primaryBeaconSnapshot(): array
{
    $snapshot = [];
    foreach (['primary_reservations', 'primary_reservation_versions', 'primary_commitments', 'ledger_entries', 'ledger_lines', 'command_operations', 'investor_wallets', 'change_feed'] as $table) {
        $snapshot[$table] = hash('sha256', DB::table($table)->orderBy('id')->get()->toJson());
    }

    return $snapshot;
}

/**
 * Real checkout/postings with explicitly synthetic admission.
 *
 * @param  array{user: User, party: Party}  $investor
 * @return array<string, mixed>|null
 */
function preparePrimaryBeacon(PrimaryCheckout $checkout, string $action, BusinessCampaign $campaign, array $investor): ?array
{
    if ($action === 'reserve') {
        return null;
    }
    $held = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    if ($action === 'refund') {
        performPrimaryBeacon($checkout, 'confirm', $campaign, $investor, $held, (string) Str::uuid());
    }

    return $held;
}

/**
 * @param  array{user: User, party: Party}  $investor
 * @param  array<string, mixed>|null  $held
 * @return array<string, mixed>
 */
function performPrimaryBeacon(PrimaryCheckout $checkout, string $action, BusinessCampaign $campaign, array $investor, ?array $held, string $request): array
{
    $user = $investor['user']->id;
    if ($action === 'reserve') {
        return $checkout->reserve($user, 1, $campaign->id, '1', $request, PrimaryReservationFixture::terms(...));
    }
    $id = $held['data']['reservation_id'];
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $id)->orderByDesc('revision')->firstOrFail();
    $terms = $version->payload['terms'];
    if (in_array($action, ['confirm', 'requote', 'expire_confirm'], true)) {
        $admit = $action === 'requote' ? function (UnitRights $rights, array $input): PrimaryTerms {
            $original = PrimaryReservationFixture::terms($rights, $input);

            return PrimaryTerms::disclosed($original->ratePercent, $original->termMonths, $original->policyVersion,
                'synthetic-disclosure-2', $original->earningsFee, $original->payoutFee, $rights);
        } : PrimaryReservationFixture::terms(...);

        return $checkout->confirm($user, 1, $campaign->id, $id, $version->revision, $terms['disclosure_version'],
            $version->payload['disclosure_sha256'], $request, $admit);
    }

    return $action === 'refund'
        ? $checkout->refund($user, 1, $campaign->id, $id, $version->revision, $request)
        : $checkout->release($user, 1, $campaign->id, $id, $version->revision, $request);
}

dataset('standalone Primary beacon actions', ['reserve', 'requote', 'confirm', 'release', 'refund', 'expire_release', 'expire_confirm']);

it('records only newly written standalone effects and leaves every replay silent', function (string $action): void {
    $held = preparePrimaryBeacon($this->checkout, $action, $this->campaign, $this->investor);
    if (str_starts_with($action, 'expire_')) {
        $this->travelTo(PrimaryReservationRecord::query()->whereKey($held['data']['reservation_id'])->sole()->expires_at);
    }
    $before = (int) DB::table('change_feed')->max('id');
    $cursor = app(ReadChanges::class)->cursor($this->investor['user']->id);
    $request = (string) Str::uuid();
    $result = performPrimaryBeacon($this->checkout, $action, $this->campaign, $this->investor, $held, $request);
    $id = $result['data']['reservation_id'];
    $rows = DB::table('change_feed')->where('id', '>', $before)->orderBy('id')->get();
    expect($rows->pluck('topic')->all())->toBe($action === 'requote' ? ['purchase'] : ['purchase', 'campaign'])
        ->and($rows[0]->party_id)->toBe($this->investor['party']->id)
        ->and($rows[0]->business_id)->toBeNull()
        ->and($rows[0]->staff_queue)->toBeNull()
        ->and($rows[0]->subject)->toBe($id);
    if ($action !== 'requote') {
        expect($rows[1]->business_id)->toBe($this->campaign->business_id)
            ->and($rows[1]->party_id)->toBeNull()
            ->and($rows[1]->subject)->toBe($this->campaign->id);
    }
    expect(app(ReadChanges::class)->handle($this->investor['user']->id, ['purchase'], $cursor)['changes'])
        ->toBe([['topic' => 'purchase', 'subject' => $id, 'revision' => $rows[0]->revision]]);
    $snapshot = primaryBeaconSnapshot();
    $unexpected = function (): never {
        throw new RuntimeException('Admission must not run on an operation replay.');
    };
    $user = $this->investor['user']->id;
    $replayed = match ($action) {
        'reserve' => $this->checkout->reserve($user, 1, $this->campaign->id, '1', $request, $unexpected),
        'confirm', 'requote', 'expire_confirm' => $this->checkout->confirm($user, 1, $this->campaign->id, $id, 1,
            'synthetic-disclosure-1', PrimaryReservationVersion::query()->where('primary_reservation_id', $id)->where('revision', 1)->sole()->payload['disclosure_sha256'], $request, $unexpected),
        'refund' => $this->checkout->refund($user, 1, $this->campaign->id, $id, 2, $request),
        default => $this->checkout->release($user, 1, $this->campaign->id, $id, 1, $request),
    };
    expect($replayed)->toBe($result)->and(primaryBeaconSnapshot())->toBe($snapshot);
    if (in_array($action, ['release', 'refund'], true)) {
        performPrimaryBeacon($this->checkout, $action, $this->campaign, $this->investor, $held, (string) Str::uuid());
        expect(DB::table('change_feed')->where('id', '>', $before)->count())->toBe($rows->count());
    }
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with('standalone Primary beacon actions');

it('emits nothing for refused admission or stale caller authority', function (): void {
    $before = DB::table('change_feed')->orderBy('id')->get()->toJson();
    $refused = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(),
        function (): never {
            throw new CommandRejection('POLICY_INPUT_REQUIRED');
        });
    expect($refused['code'])->toBe('POLICY_INPUT_REQUIRED')
        ->and(fn () => $this->checkout->reserve($this->investor['user']->id, 0, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...)))
        ->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REVISION_CONFLICT')
        ->and(DB::table('change_feed')->orderBy('id')->get()->toJson())->toBe($before);
});

it('delivers a real campaign closure after standalone progress advanced its feed revision', function (): void {
    $held = preparePrimaryBeacon($this->checkout, 'refund', $this->campaign, $this->investor);
    performPrimaryBeacon($this->checkout, 'refund', $this->campaign, $this->investor, $held, (string) Str::uuid());
    $cancelled = app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, 'Unfunded principal returned.', (string) Str::uuid());
    expect($cancelled['code'])->toBe('CAMPAIGN_CANCELLED');
    expect(DB::table('change_feed')->where('topic', 'campaign')->where('subject', $this->campaign->id)->orderBy('revision')->pluck('revision')->all())
        ->toBe([1, 2, 3, 4]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('rolls back financial evidence and receipts when the standalone feed fails after recording', function (string $action): void {
    $held = preparePrimaryBeacon($this->checkout, $action, $this->campaign, $this->investor);
    if (str_starts_with($action, 'expire_')) {
        $this->travelTo(PrimaryReservationRecord::query()->whereKey($held['data']['reservation_id'])->sole()->expires_at);
    }
    $snapshot = primaryBeaconSnapshot();
    $real = app(ChangeFeed::class);
    $calls = 0;
    $record = function (ChangeScope $scope, string $topic, string $subject, ?int $revision = null) use ($real, $action, &$calls): void {
        expect($revision)->toBeNull();
        $expectedKind = match ($action) {
            'reserve' => 'primary_hold', 'confirm' => 'primary_commit', 'refund' => 'primary_refund', default => 'primary_release',
        };
        if ($action === 'requote') {
            expect(PrimaryReservationVersion::query()->where('revision', 2)->count())->toBe(1);
        } else {
            expect(DB::table('ledger_entries')->where('kind', $expectedKind)->count())->toBe(1);
        }
        $real->record($scope, $topic, $subject, $revision);
        $calls++;
        if ($topic === 'campaign' || $action === 'requote') {
            throw new RuntimeException('standalone feed failure');
        }
    };
    $feed = new class($record) implements ChangeFeed
    {
        public function __construct(private Closure $record) {}

        public function record(ChangeScope $scope, string $topic, string $subject, ?int $revision = null): void
        {
            ($this->record)($scope, $topic, $subject, $revision);
        }

        public function horizon(): array
        {
            throw new LogicException('Only the recording boundary is used in this fault probe.');
        }

        public function read(array $audiences, ChangeCursor $after, int $limit): array
        {
            throw new LogicException('Only the recording boundary is used in this fault probe.');
        }

        public function prune(int $retentionHours): int
        {
            throw new LogicException('Only the recording boundary is used in this fault probe.');
        }
    };
    app()->instance(ChangeFeed::class, $feed);
    $checkout = app(PrimaryCheckout::class);
    expect(fn () => performPrimaryBeacon($checkout, $action, $this->campaign, $this->investor, $held, (string) Str::uuid()))
        ->toThrow(RuntimeException::class, 'standalone feed failure')
        ->and($calls)->toBe($action === 'requote' ? 1 : 2)
        ->and(primaryBeaconSnapshot())->toBe($snapshot);
})->with('standalone Primary beacon actions');

it('keeps new standalone changes atomic with the callers outer rollback', function (string $action): void {
    $held = preparePrimaryBeacon($this->checkout, $action, $this->campaign, $this->investor);
    if (str_starts_with($action, 'expire_')) {
        $this->travelTo(PrimaryReservationRecord::query()->whereKey($held['data']['reservation_id'])->sole()->expires_at);
    }
    $snapshot = primaryBeaconSnapshot();
    expect(fn () => DB::transaction(function () use ($action, $held): void {
        performPrimaryBeacon($this->checkout, $action, $this->campaign, $this->investor, $held, (string) Str::uuid());
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        throw new RuntimeException('caller outer rollback');
    }))->toThrow(RuntimeException::class, 'caller outer rollback')
        ->and(primaryBeaconSnapshot())->toBe($snapshot);
})->with('standalone Primary beacon actions');
