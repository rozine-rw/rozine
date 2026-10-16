<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Identity\SelectActiveRole;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Models\BusinessCampaign;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\RoleMembership;
use Illuminate\Database\Events\QueryExecuted;
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
    $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->initial = PrimaryReservationVersion::query()->sole()->payload;
    $this->read = fn (): array => $this->checkout->reservationFacts($this->investor['user']->id, 1, $this->campaign->id, $this->root->id);
    $this->confirm = fn (?Closure $admit = null): array => $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id,
        $this->root->id, 1, $this->initial['terms']['disclosure_version'], $this->initial['disclosure_sha256'],
        (string) Str::uuid(), $admit ?? PrimaryReservationFixture::terms(...));
});

it('returns authenticated retained facts with no journal wallet cash feed or retirement writes', function (): void {
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = strtolower($query->sql);
    });
    $facts = ($this->read)();
    expect($facts)->toMatchArray(['reservation_id' => $this->root->id, 'campaign_id' => $this->campaign->id,
        'party_id' => $this->investor['party']->id, 'state' => 'held', 'revision' => 1, 'units' => '3',
        'principal' => ['currency' => 'RWF', 'amount' => '15000'], 'confirmation' => null,
        'ordinal_ranges' => [['first' => '1', 'last' => '3']], 'terms' => $this->initial['terms'],
        'disclosure_sha256' => $this->initial['disclosure_sha256']])
        ->and(array_keys($facts))->toBe(['reservation_id', 'campaign_id', 'party_id', 'publication_sha256', 'root_sha256',
            'revision', 'revision_sha256', 'state', 'starts_at', 'expires_at', 'units', 'ordinal_ranges', 'principal', 'rights',
            'terms', 'disclosure_sha256', 'confirmation'])
        ->and($facts['root_sha256'])->toBe($this->root->sha256)
        ->and($facts['revision_sha256'])->toBe(PrimaryReservationVersion::query()->sole()->sha256)
        ->and($facts['rights'])->toBe($this->root->payload['rights']);
    foreach ($queries as $query) {
        expect($query)->toStartWith('select ')->not->toMatch('/wallet|ledger|command_operations|change_feed|primary_held_claim/');
    }
    $this->travelTo($this->campaign->expires_at->addDay());
    expect(($this->read)())->toBe($facts);
});

it('returns the latest fully replayed disclosure without repricing or extending the original window', function (): void {
    ($this->confirm)(function (UnitRights $rights, array $campaign): PrimaryTerms {
        $terms = PrimaryReservationFixture::terms($rights, $campaign);

        return PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $terms->policyVersion,
            'synthetic-read-requote-2', $terms->earningsFee, $terms->payoutFee, $rights);
    });
    $facts = ($this->read)();
    expect($facts['revision'])->toBe(2)->and($facts['state'])->toBe('held')->and($facts['confirmation'])->toBeNull()
        ->and($facts['terms']['disclosure_version'])->toBe('synthetic-read-requote-2')
        ->and($facts['expires_at'])->toBe($this->root->expires_at->format('Y-m-d\TH:i:s.u\Z'));
});

it('binds confirmation to its exact retained version and keeps it historical after cash refund and expiry', function (): void {
    $confirmed = ($this->confirm)();
    $commitmentId = $confirmed['data']['commitment_id'];
    $facts = ($this->read)();
    expect($facts['state'])->toBe('confirmed')->and($facts['revision'])->toBe(2)
        ->and($facts['confirmation'])->toMatchArray(['commitment_id' => $commitmentId, 'operation_id' => $confirmed['operation_id']])
        ->and($this->checkout->commitmentFacts($this->investor['user']->id, 1, $this->campaign->id, $commitmentId))->toBe($facts);
    expect($this->checkout->refund($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 2, (string) Str::uuid())['code'])
        ->toBe('COMMITMENT_REFUNDED');
    $this->travelTo($this->campaign->expires_at->addDay());
    expect(($this->read)())->toBe($facts)
        ->and($this->checkout->commitmentFacts($this->investor['user']->id, 1, $this->campaign->id, $commitmentId))->toBe($facts);
});

it('preserves terminal history when the original ordinals have been recycled', function (string $terminal): void {
    if ($terminal === 'released') {
        $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid());
    } else {
        $this->travelTo($this->root->expires_at);
        expect(app(PrimaryReservations::class)->expireDue(1))->toBe(1);
    }
    $before = ($this->read)();
    $other = PrimaryReservationFixture::investor();
    $replacement = $this->checkout->reserve($other['user']->id, 1, $this->campaign->id, '2', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect(($this->read)())->toBe($before)->and($before['state'])->toBe($terminal)
        ->and($before['ordinal_ranges'])->toBe([['first' => '1', 'last' => '3']])
        ->and($this->checkout->reservationFacts($other['user']->id, 1, $this->campaign->id, $replacement['data']['reservation_id'])['ordinal_ranges'])
        ->toBe([['first' => '1', 'last' => '2']]);
})->with(['released', 'expired']);

it('checks current authority before decryption for both read targets', function (string $change, string $reason): void {
    $confirmed = ($this->confirm)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER USER');
    DB::table('primary_reservations')->where('id', $this->root->id)->update(['payload' => 'invalid encrypted payload']);
    $user = $this->investor['user'];
    $party = $this->investor['party'];
    match ($change) {
        'context' => $user->forceFill(['context_revision' => 2])->save(),
        'email' => $user->forceFill(['email_verified_at' => null])->save(),
        'party' => $party->forceFill(['verified_at' => null])->save(),
        'membership' => RoleMembership::query()->where('party_id', $party->id)->update(['status' => 'suspended']),
        'role' => (function () use ($user, $party): void {
            RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
            app(SelectActiveRole::class)->handle($user->id, 'business', 1, (string) Str::uuid());
        })(),
        default => throw new InvalidArgumentException('Unknown authority change.'),
    };
    $context = $change === 'role' ? 2 : 1;
    expect(fn () => $this->checkout->reservationFacts($user->id, $context, $this->campaign->id, $this->root->id))->toThrow(IdentityViolation::class, $reason)
        ->and(fn () => $this->checkout->commitmentFacts($user->id, $context, $this->campaign->id, $confirmed['data']['commitment_id']))->toThrow(IdentityViolation::class, $reason);
})->with([
    ['context', 'ACTIVE_ROLE_REVISION_CONFLICT'], ['email', 'EMAIL_VERIFICATION_REQUIRED'],
    ['party', 'IDENTITY_VERIFICATION_REQUIRED'], ['membership', 'ROLE_MEMBERSHIP_REQUIRED'], ['role', 'ACTIVE_ROLE_REQUIRED'],
]);

it('scopes both native targets by current canonical Party and exact campaign before decrypting', function (): void {
    $confirmed = ($this->confirm)();
    $other = InvestorWalletFixture::investor();
    $otherCampaign = BusinessCampaign::factory()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER USER');
    DB::table('primary_reservations')->where('id', $this->root->id)->update(['payload' => 'invalid encrypted payload']);
    foreach ([[$other['user']->id, $this->campaign->id], [$this->investor['user']->id, $otherCampaign->id]] as [$user, $campaign]) {
        expect(fn () => $this->checkout->reservationFacts($user, 1, $campaign, $this->root->id))->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND')
            ->and(fn () => $this->checkout->commitmentFacts($user, 1, $campaign, $confirmed['data']['commitment_id']))->toThrow(CommandRejection::class, 'COMMITMENT_NOT_FOUND');
    }
    expect(fn () => $this->checkout->reservationFacts($this->investor['user']->id, 1, $this->campaign->id, strtolower((string) Str::ulid())))->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND')
        ->and(fn () => $this->checkout->commitmentFacts($this->investor['user']->id, 1, $this->campaign->id, strtolower((string) Str::ulid())))->toThrow(CommandRejection::class, 'COMMITMENT_NOT_FOUND');
});

it('refuses malformed complete retained history instead of trusting a latest version or schema-only row', function (string $damage): void {
    ($this->confirm)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER USER');
    DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER USER');
    if ($damage === 'root digest') {
        $this->root->forceFill(['sha256' => str_repeat('a', 64)])->save();
    } elseif ($damage === 'native principal') {
        $this->root->forceFill(['units' => 4, 'principal' => '20000', 'ordinal_ranges' => '{[1,5)}'])->save();
    } else {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $this->root->id)
            ->orderBy($damage === 'initial digest' ? 'revision' : 'id', $damage === 'initial digest' ? 'asc' : 'desc')->firstOrFail();
        match ($damage) {
            'ancestry' => $version->forceFill(['previous_sha256' => str_repeat('a', 64)])->save(),
            'initial digest', 'latest digest' => $version->forceFill(['sha256' => str_repeat('a', 64)])->save(),
            'native state' => $version->forceFill(['state' => 'released'])->save(),
            default => throw new InvalidArgumentException('Unknown retained damage.'),
        };
    }
    expect($this->read)->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
})->with(['root digest', 'native principal', 'ancestry', 'initial digest', 'latest digest', 'native state']);

it('refuses a malformed confirmation binding for reservation and commitment views', function (string $damage): void {
    $confirmed = ($this->confirm)();
    $commitment = PrimaryCommitment::query()->sole();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_commitments DISABLE TRIGGER USER');
    match ($damage) {
        'missing' => $commitment->delete(),
        'version' => $commitment->forceFill(['primary_reservation_version_id' => PrimaryReservationVersion::query()->orderBy('revision')->firstOrFail()->id])->save(),
        'operation' => $commitment->forceFill(['operation_id' => $this->root->origin_operation_id])->save(),
        'confirmed time' => $commitment->forceFill(['confirmed_at' => now()->addSecond()])->save(),
        'created time' => $commitment->forceFill(['created_at' => now()->addSecond()])->save(),
        default => throw new InvalidArgumentException('Unknown confirmation damage.'),
    };
    expect($this->read)->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
    expect(fn () => $this->checkout->commitmentFacts($this->investor['user']->id, 1, $this->campaign->id, $confirmed['data']['commitment_id']))
        ->toThrow($damage === 'missing' ? CommandRejection::class : RuntimeException::class, $damage === 'missing' ? 'COMMITMENT_NOT_FOUND' : 'RESERVATION_INTEGRITY_FAILED');
})->with(['missing', 'version', 'operation', 'confirmed time', 'created time']);

it('refuses an extraneous commitment attached to an unconfirmed root', function (): void {
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_commitments DISABLE TRIGGER USER');
    PrimaryCommitment::factory()->create(['primary_reservation_id' => $this->root->id,
        'primary_reservation_version_id' => PrimaryReservationVersion::query()->sole()->id,
        'operation_id' => $this->root->origin_operation_id, 'confirmed_at' => now(), 'created_at' => now()]);
    expect($this->read)->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
});

it('keeps exact historical facts accessible after an authenticated campaign closing', function (string $terminal): void {
    if ($terminal === 'released') {
        $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid());
    } else {
        $this->travelTo($this->root->expires_at);
        expect(app(PrimaryReservations::class)->expireDue(1))->toBe(1);
    }
    $facts = ($this->read)();
    $closed = app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, null, (string) Str::uuid());
    expect($closed['code'])->toBe('CAMPAIGN_CANCELLED')->and(($this->read)())->toBe($facts);
})->with(['released', 'expired']);
