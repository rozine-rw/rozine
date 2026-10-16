<?php

declare(strict_types=1);

use App\Application\Identity\SelectActiveRole;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\RoleMembership;
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

it('derives the reservation and journal Party from current Investor authority', function (): void {
    $request = (string) Str::uuid();
    $result = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', $request, PrimaryReservationFixture::terms(...));
    expect($result['code'])->toBe('RESERVATION_HELD')
        ->and(PrimaryReservationRecord::query()->sole()->party_id)->toBe($this->investor['party']->id)
        ->and(CommandOperation::query()->whereKey($result['operation_id'])->sole()->actor_key)->toBe('party:'.$this->investor['party']->id)
        ->and($this->checkout->findReservation($this->investor['user']->id, 1, $this->campaign->id, $request))->toBe($result);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('replays and retrieves the retained receipt after campaign expiry without re-running admission', function (): void {
    $request = (string) Str::uuid();
    $result = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', $request, PrimaryReservationFixture::terms(...));
    $this->travelTo($this->campaign->expires_at);
    $unexpected = function (): never {
        throw new RuntimeException('Replay must not admit another purchase.');
    };
    expect($this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', $request, $unexpected))->toBe($result)
        ->and($this->checkout->findReservation($this->investor['user']->id, 1, $this->campaign->id, $request))->toBe($result)
        ->and(fn () => $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '2', $request, $unexpected))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(PrimaryReservationRecord::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(1);
    expect($this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), $unexpected)['code'])->toBe('CAMPAIGN_CLOSED');
});

it('requires fresh identity authority before a new command replay or receipt lookup', function (string $change, string $reason): void {
    $request = (string) Str::uuid();
    $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', $request, PrimaryReservationFixture::terms(...));
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
    $context = in_array($change, ['context', 'role'], true) ? 2 : 1;
    if ($change === 'context') {
        $context = 1;
    }
    foreach ([$request, (string) Str::uuid()] as $key) {
        expect(fn () => $this->checkout->reserve($user->id, $context, $this->campaign->id, '1', $key, PrimaryReservationFixture::terms(...)))
            ->toThrow(IdentityViolation::class, $reason);
    }
    expect(fn () => $this->checkout->findReservation($user->id, $context, $this->campaign->id, $request))->toThrow(IdentityViolation::class, $reason)
        ->and(PrimaryReservationRecord::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'primary.reserve')->count())->toBe(1);
})->with([
    ['context', 'ACTIVE_ROLE_REVISION_CONFLICT'], ['email', 'EMAIL_VERIFICATION_REQUIRED'],
    ['party', 'IDENTITY_VERIFICATION_REQUIRED'], ['membership', 'ROLE_MEMBERSHIP_REQUIRED'], ['role', 'ACTIVE_ROLE_REQUIRED'],
]);

it('isolates operation lookup by canonical Party and exact campaign target', function (): void {
    $request = (string) Str::uuid();
    $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', $request, PrimaryReservationFixture::terms(...));
    $other = InvestorWalletFixture::investor();
    $otherCampaign = BusinessCampaign::factory()->create();
    expect(fn () => $this->checkout->findReservation($other['user']->id, 1, $this->campaign->id, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $this->checkout->findReservation($this->investor['user']->id, 1, $otherCampaign->id, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $this->checkout->reserve($this->investor['user']->id, 1, strtolower((string) Str::ulid()), '1', $request, PrimaryReservationFixture::terms(...)))
        ->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FOUND');
});

it('retains a refusal receipt while rolling back admission evidence and cash atomically', function (): void {
    $request = (string) Str::uuid();
    $result = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', $request,
        function (): never {
            throw new CommandRejection('POLICY_INPUT_REQUIRED');
        });
    expect($result)->toMatchArray(['status' => 'rejected', 'code' => 'POLICY_INPUT_REQUIRED'])
        ->and($this->checkout->findReservation($this->investor['user']->id, 1, $this->campaign->id, $request))->toBe($result)
        ->and(PrimaryReservationRecord::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0);
});
