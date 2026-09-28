<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
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
    $this->version = PrimaryReservationVersion::query()->sole();
    $disclosure = $this->version->payload;
    $this->confirm = fn (?string $request = null, int $revision = 1, ?string $version = null, ?string $hash = null, ?Closure $admit = null): array => $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $revision,
        $version ?? $disclosure['terms']['disclosure_version'], $hash ?? $disclosure['disclosure_sha256'],
        $request ?? (string) Str::uuid(), $admit ?? PrimaryReservationFixture::terms(...));
});

/** @param array<string, mixed> $campaign */
function revisedPrimaryDisclosure(UnitRights $rights, array $campaign): PrimaryTerms
{
    $terms = PrimaryReservationFixture::terms($rights, $campaign);

    return PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $terms->policyVersion, 'synthetic-disclosure-2', $terms->earningsFee, $terms->payoutFee, $rights);
}

it('atomically confirms the original exact rights terms and held principal', function (): void {
    $this->travel(1)->minutes();
    $result = ($this->confirm)();
    $commitment = PrimaryCommitment::query()->sole();
    $version = PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail();
    $entry = LedgerEntry::query()->where('kind', 'primary_commit')->sole();
    expect($result)->toMatchArray(['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED', 'revision' => 2])
        ->and($result['data'])->toMatchArray(['commitment_id' => $commitment->id, 'entry_id' => $entry->id, 'amount' => '15000', 'terms' => $this->version->payload['terms']])
        ->and($commitment->primary_reservation_version_id)->toBe($version->id)
        ->and($commitment->operation_id)->toBe($result['operation_id'])
        ->and($commitment->confirmed_at->eq($version->created_at))->toBeTrue()
        ->and($version->payload)->toMatchArray(['revision' => 2, 'state' => 'confirmed', 'previous_sha256' => $this->version->sha256,
            'terms' => $this->version->payload['terms'], 'disclosure_sha256' => $this->version->payload['disclosure_sha256']])
        ->and($entry->source_id)->toBe($this->root->id)->and($entry->origin_operation_id)->toBe($this->root->origin_operation_id)
        ->and($this->root->fresh()->payload)->toBe($this->root->payload)
        ->and(app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown'])
        ->toMatchArray(['held' => ['currency' => 'RWF', 'amount' => '0'], 'committed' => ['currency' => 'RWF', 'amount' => '15000'], 'available' => ['currency' => 'RWF', 'amount' => '9985000']]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('retains a new disclosure without moving cash or extending the original deadline then requires its acknowledgement', function (): void {
    $this->travel(1)->minutes();
    $requote = ($this->confirm)(admit: revisedPrimaryDisclosure(...));
    expect($requote)->toMatchArray(['status' => 'completed', 'code' => 'RESERVATION_REQUOTED', 'revision' => 2])
        ->and($requote['data']['commitment_id'])->toBeNull()->and($requote['data']['entry_id'])->toBeNull()
        ->and($requote['data']['expires_at'])->toBe($this->root->expires_at->format('Y-m-d\TH:i:s.u\Z'))
        ->and(PrimaryCommitment::query()->count())->toBe(0)->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(0);
    expect(($this->confirm)(revision: 2, admit: revisedPrimaryDisclosure(...))['code'])->toBe('DISCLOSURE_STALE');
    expect(($this->confirm)(revision: 2, version: $requote['data']['terms']['disclosure_version'], hash: $requote['data']['disclosure_sha256'], admit: revisedPrimaryDisclosure(...))['code'])
        ->toBe('RESERVATION_CONFIRMED')
        ->and(PrimaryReservationVersion::query()->orderBy('revision')->pluck('state')->all())->toBe(['held', 'held', 'confirmed'])
        ->and($this->version->fresh()->payload)->toBe($this->version->payload);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('replays and looks up confirmation after expiry without another admission or posting', function (): void {
    $key = (string) Str::uuid();
    $result = ($this->confirm)($key);
    $this->travelTo($this->campaign->expires_at);
    $unexpected = function (): never {
        throw new RuntimeException('Unexpected repeat admission.');
    };
    expect(($this->confirm)($key, admit: $unexpected))->toBe($result)
        ->and($this->checkout->findConfirmation($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $key))->toBe($result)
        ->and(fn () => ($this->confirm)($key, revision: 2))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(PrimaryCommitment::query()->count())->toBe(1)->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(1);
});

it('requires current authority on new confirmations replays and retained lookup', function (): void {
    $key = (string) Str::uuid();
    ($this->confirm)($key);
    $this->investor['party']->forceFill(['verified_at' => null])->save();
    foreach ([$key, (string) Str::uuid()] as $request) {
        expect(fn () => ($this->confirm)($request))->toThrow(IdentityViolation::class, 'IDENTITY_VERIFICATION_REQUIRED');
    }
    expect(fn () => $this->checkout->findConfirmation($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $key))
        ->toThrow(IdentityViolation::class, 'IDENTITY_VERIFICATION_REQUIRED');
});

it('scopes confirmation and lookup to the canonical Party exact reservation and owning campaign', function (): void {
    $key = (string) Str::uuid();
    ($this->confirm)($key);
    $other = PrimaryReservationFixture::investor();
    $otherCampaign = BusinessCampaign::factory()->create();
    foreach ([[$other['user']->id, $this->campaign->id, $this->root->id], [$this->investor['user']->id, $otherCampaign->id, $this->root->id], [$this->investor['user']->id, $this->campaign->id, strtolower((string) Str::ulid())]] as [$user, $campaign, $reservation]) {
        expect(fn () => $this->checkout->confirm($user, 1, $campaign, $reservation, 1, 'x', 'x', $key, PrimaryReservationFixture::terms(...)))
            ->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND')
            ->and(fn () => $this->checkout->findConfirmation($user, 1, $campaign, $reservation, $key))->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND');
    }
    $reserved = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect(fn () => $this->checkout->findConfirmation($this->investor['user']->id, 1, $this->campaign->id, $reserved['data']['reservation_id'], $key))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    expect(fn () => app(PrimaryReservations::class)->confirm($this->campaign->id, $this->root->id, $other['party']->id, strtolower((string) Str::ulid()), 1, 'x', 'x', PrimaryReservationFixture::terms(...)))
        ->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND');
});

it('refuses a stale revision or acknowledgement without changing the live hold', function (string $case, string $reason): void {
    $result = ($this->confirm)(revision: $case === 'revision' ? 0 : 1, version: $case === 'version' ? 'wrong' : null, hash: $case === 'hash' ? str_repeat('0', 64) : null);
    expect($result)->toMatchArray(['status' => 'rejected', 'code' => $reason])
        ->and(PrimaryReservationVersion::query()->count())->toBe(1)->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(0);
})->with([['revision', 'VERSION_CONFLICT'], ['version', 'DISCLOSURE_STALE'], ['hash', 'DISCLOSURE_STALE']]);

it('refuses another confirmation of a terminal reservation', function (): void {
    ($this->confirm)();
    expect(($this->confirm)(revision: 2)['code'])->toBe('RESERVATION_NOT_HELD')
        ->and(PrimaryReservationVersion::query()->count())->toBe(2)->and(PrimaryCommitment::query()->count())->toBe(1);
});

it('checks the half-open hold deadline both before and after current admission', function (bool $duringAdmission): void {
    if (! $duringAdmission) {
        $this->travelTo($this->root->expires_at);
    }
    $admit = function (UnitRights $rights, array $campaign) use ($duringAdmission): PrimaryTerms {
        if (! $duringAdmission) {
            throw new RuntimeException('Expired holds must not reach admission.');
        }
        $this->travelTo($this->root->expires_at);

        return PrimaryReservationFixture::terms($rights, $campaign);
    };
    expect(($this->confirm)(admit: $admit)['code'])->toBe('RESERVATION_EXPIRED')
        ->and(PrimaryReservationVersion::query()->count())->toBe(2)->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->value('state'))->toBe('expired')
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with([false, true]);

it('does not append a requote after admission reaches the deadline', function (): void {
    expect(($this->confirm)(admit: function (UnitRights $rights, array $campaign): PrimaryTerms {
        $this->travelTo($this->root->expires_at);

        return revisedPrimaryDisclosure($rights, $campaign);
    })['code'])->toBe('RESERVATION_EXPIRED')->and(PrimaryReservationVersion::query()->count())->toBe(2)
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->value('state'))->toBe('expired')
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('admits a matching acknowledgement immediately before the deadline', function (): void {
    $this->travelTo($this->root->expires_at->subMicrosecond());
    expect(($this->confirm)()['code'])->toBe('RESERVATION_CONFIRMED')
        ->and(PrimaryCommitment::query()->sole()->confirmed_at->format('u'))->toBe('999999');
});

it('records explicit admission refusal and rolls back any incomplete confirmation', function (): void {
    expect(($this->confirm)(admit: function (): never {
        throw new CommandRejection('RESTRICTION_ACTIVE');
    })['code'])->toBe('RESTRICTION_ACTIVE')
        ->and(PrimaryCommitment::query()->count())->toBe(0)->and(PrimaryReservationVersion::query()->count())->toBe(1);
});

it('rejects terms that change publication policy or do not conserve purchased rights', function (string $case): void {
    expect(($this->confirm)(admit: function (UnitRights $rights, array $campaign) use ($case): PrimaryTerms {
        $terms = PrimaryReservationFixture::terms($rights, $campaign);

        return PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $case === 'policy' ? 'other-policy' : $terms->policyVersion,
            $terms->disclosureVersion, $terms->earningsFee, $case === 'fee' ? '999999' : $terms->payoutFee, $rights);
    })['code'])->toBe('INVALID_PRIMARY_TERMS')->and(PrimaryCommitment::query()->count())->toBe(0);
})->with(['policy', 'fee']);

it('rolls back commitment and version if the original hold cannot be committed', function (): void {
    $wallets = app(WalletPostings::class);
    $wallets->release($wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal),
        new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id));
    expect(fn () => ($this->confirm)())->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID')
        ->and(PrimaryCommitment::query()->count())->toBe(0)->and(PrimaryReservationVersion::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(0);
});

it('rejects corrupted retained disclosure history even with a recomputed payload digest', function (string $damage): void {
    $payload = $this->version->payload;
    match ($damage) {
        'missing_terms' => $payload['terms'] = null,
        'invalid_terms' => $payload['terms']['rate_pct'] = '99.0',
        'terms_policy' => $payload['terms']['policy_version'] = 'unrelated-policy',
        'terms_currency' => $payload['terms']['payout_fee']['currency'] = 'USD',
        'rights' => $payload['terms']['payout_fee']['amount'] = '999999',
        'hash' => $payload['disclosure_sha256'] = str_repeat('0', 64),
        'root' => $payload['reservation_sha256'] = str_repeat('0', 64),
        'revision' => $payload['revision'] = 2,
        'extra' => $payload['unexpected'] = true,
        'digest' => $payload['state'] = 'confirmed',
        'digest_only' => $this->version->sha256 = str_repeat('0', 64),
        'initial_state' => $this->version->forceFill(['state' => 'released']),
        'initial_time' => $this->version->forceFill(['created_at' => $this->root->created_at->addSecond()]),
        default => throw new InvalidArgumentException('Unknown history damage.'),
    };
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER USER');
    try {
        $this->version->forceFill(['payload' => $payload, 'sha256' => in_array($damage, ['digest', 'digest_only'], true) ? $this->version->sha256 : hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
        DB::statement('ALTER TABLE primary_reservation_versions ENABLE TRIGGER USER');
    }
    expect(fn () => ($this->confirm)())->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED')
        ->and(PrimaryCommitment::query()->count())->toBe(0)->and(CommandOperation::query()->where('command', 'primary.confirm')->count())->toBe(0);
})->with(['missing_terms', 'invalid_terms', 'terms_policy', 'terms_currency', 'rights', 'hash', 'root', 'revision', 'extra', 'digest', 'digest_only', 'initial_state', 'initial_time']);

it('rejects a missing version chain or a rewritten hold deadline', function (bool $missing): void {
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $table = $missing ? 'primary_reservation_versions' : 'primary_reservations';
    DB::statement('ALTER TABLE '.$table.' DISABLE TRIGGER USER');
    try {
        if ($missing) {
            $this->version->delete();
        } else {
            $expires = $this->root->expires_at->addSecond();
            $payload = [...$this->root->payload, 'expires_at' => $expires->format('Y-m-d\TH:i:s.u\Z')];
            $this->root->forceFill(['expires_at' => $expires, 'payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
        }
    } finally {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
        DB::statement('ALTER TABLE '.$table.' ENABLE TRIGGER USER');
    }
    expect(fn () => ($this->confirm)())->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
})->with([true, false]);

it('rejects damaged chain links projections and terminal history', function (string $damage): void {
    ($this->confirm)(admit: revisedPrimaryDisclosure(...));
    $version = PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail();
    if ($damage === 'previous') {
        $version->previous_sha256 = str_repeat('0', 64);
    } elseif ($damage === 'backwards') {
        $version->created_at = $this->root->created_at->subSecond();
    } elseif ($damage === 'gap') {
        $version->revision = 3;
    } else {
        $version->state = 'unknown';
    }
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    if ($damage === 'unknown') {
        DB::statement('ALTER TABLE primary_reservation_versions DROP CONSTRAINT primary_reservation_state');
    }
    DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER USER');
    try {
        $version->save();
    } finally {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
        DB::statement('ALTER TABLE primary_reservation_versions ENABLE TRIGGER USER');
    }
    expect(fn () => ($this->confirm)(revision: 2))->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
})->with(['previous', 'backwards', 'gap', 'unknown']);

it('reads retained release and expiry evidence as terminal without recommitting their released cash', function (string $state): void {
    if ($state === 'expired') {
        $this->travelTo($this->root->expires_at);
    }
    $operation = CommandOperation::factory()->create(['actor_key' => 'party:'.$this->root->party_id,
        'actor_user_id' => $this->investor['user']->id, 'command' => 'primary.release',
        'target_type' => 'primary_reservation', 'target_id' => $this->root->id,
        'result' => $state === 'expired' ? ['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED'] : ['status' => 'completed']]);
    $payload = [...$this->version->payload, 'revision' => 2, 'state' => $state, 'operation_id' => $operation->id,
        'previous_sha256' => $this->version->sha256, 'recorded_at' => now('UTC')->format('Y-m-d\TH:i:s.u\Z')];
    (new PrimaryReservationVersion)->forceFill(['primary_reservation_id' => $this->root->id, 'revision' => 2,
        'state' => $state, 'operation_id' => $operation->id, 'previous_sha256' => $this->version->sha256,
        'payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload)), 'created_at' => now('UTC')])->save();
    $wallets = app(WalletPostings::class);
    $wallets->release($wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal),
        new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id));
    expect(($this->confirm)(revision: 2)['code'])->toBe('RESERVATION_NOT_HELD')
        ->and(PrimaryCommitment::query()->count())->toBe(0)->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['released', 'expired']);
