<?php

declare(strict_types=1);

use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\Party;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->freezeSecond();
});

function primarySchemaFlush(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

it('retains encrypted reservation and disclosure history with a single exact commitment', function (): void {
    $commitment = PrimaryCommitment::factory()->create();
    primarySchemaFlush();
    $reservation = PrimaryReservationRecord::query()->sole();
    $versions = PrimaryReservationVersion::query()->orderBy('revision')->get();
    expect($reservation->principal)->toBe('5000')->and($reservation->units)->toBe(1)
        ->and($reservation->expires_at->diffInSeconds($reservation->created_at, absolute: true))->toBe(300.0)
        ->and($versions->pluck('state')->all())->toBe(['held', 'confirmed'])
        ->and($commitment->primary_reservation_version_id)->toBe($versions[1]->id)
        ->and($commitment->operation_id)->toBe($versions[1]->operation_id)
        ->and($commitment->confirmed_at->equalTo($versions[1]->created_at))->toBeTrue()
        ->and($versions[1]->previous_sha256)->toBe($versions[0]->sha256);
    foreach ([$reservation, ...$versions] as $record) {
        expect($record->payload)->toBe(['source' => 'unsupported-fixture'])
            ->and($record->toArray())->not->toHaveKey('payload')
            ->and(DB::table($record->getTable())->where('id', $record->id)->value('payload'))->not->toContain('unsupported-fixture');
    }
});

it('clips a hold at the retained campaign expiry and preserves microseconds', function (): void {
    $campaign = BusinessCampaign::factory()->create(['live_at' => now()->subDays(30)->addSeconds(2), 'expires_at' => now()->addSeconds(2)]);
    $reservation = PrimaryReservationRecord::factory()->withInitialVersion()->create(['business_campaign_id' => $campaign->id,
        'created_at' => now()->addMicroseconds(123456)]);
    primarySchemaFlush();
    expect($reservation->refresh()->expires_at->equalTo($campaign->expires_at))->toBeTrue()
        ->and($reservation->created_at->format('u'))->toBe('123456');
});

it('rejects an invalid reservation window or principal even through raw SQL', function (string $case): void {
    $campaign = BusinessCampaign::factory()->create();
    $values = match ($case) {
        'early' => ['created_at' => $campaign->live_at->subSecond()],
        'late' => ['created_at' => $campaign->expires_at],
        'extended' => ['expires_at' => now()->addSeconds(301)],
        'shortened' => ['expires_at' => now()->addSeconds(299)],
        'zero' => ['units' => 0, 'principal' => '0'],
        'fractional' => ['principal' => '5001'],
        'too_large' => ['units' => 601, 'principal' => '3005000'],
        'publication' => ['publication_sha256' => str_repeat('0', 64)],
        default => throw new InvalidArgumentException('Unknown test case.'),
    };
    expect(fn () => DB::transaction(function () use ($campaign, $values): void {
        $attributes = PrimaryReservationRecord::factory()->make(['business_campaign_id' => $campaign->id, ...$values])->getAttributes();
        $attributes['id'] = strtolower((string) Str::ulid());
        DB::table('primary_reservations')->insert($attributes);
    }))->toThrow(QueryException::class);
})->with(['early', 'late', 'extended', 'shortened', 'zero', 'fractional', 'too_large', 'publication']);

it('requires initial reservation evidence before the outer commit', function (): void {
    expect(fn () => DB::transaction(function (): void {
        PrimaryReservationRecord::factory()->create();
        primarySchemaFlush();
    }))->toThrow(QueryException::class, 'initial retained revision');
    expect(PrimaryReservationRecord::query()->count())->toBe(0);
});

it('requires a matching operation while allowing journal insertion after the reservation', function (): void {
    $campaign = BusinessCampaign::factory()->create();
    $user = User::factory()->create(['party_id' => Party::factory()]);
    $operation = CommandOperation::factory()->make(['id' => strtolower((string) Str::ulid()), 'actor_key' => 'party:'.$user->party_id,
        'actor_user_id' => $user->id, 'command' => 'primary.reserve', 'target_type' => 'campaign', 'target_id' => $campaign->id]);
    PrimaryReservationRecord::factory()->withInitialVersion()->create(['business_campaign_id' => $campaign->id, 'party_id' => $user->party_id, 'origin_operation_id' => $operation->id]);
    $operation->save();
    primarySchemaFlush();
    expect(PrimaryReservationRecord::query()->sole()->origin_operation_id)->toBe($operation->id);
});

it('refuses a dangling operation at commit', function (): void {
    expect(fn () => DB::transaction(function (): void {
        PrimaryReservationRecord::factory()->withInitialVersion()->create(['origin_operation_id' => strtolower((string) Str::ulid())]);
        primarySchemaFlush();
    }))->toThrow(QueryException::class);
});

it('refuses duplicate origin operations', function (): void {
    $reservation = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    expect(fn () => DB::transaction(fn () => PrimaryReservationRecord::factory()->create(['origin_operation_id' => $reservation->origin_operation_id])))
        ->toThrow(QueryException::class);
});

it('rejects gaps stale hashes and backwards or terminal revision transitions', function (string $case): void {
    $reservation = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $first = PrimaryReservationVersion::query()->sole();
    $attributes = match ($case) {
        'gap' => ['revision' => 3], 'duplicate' => ['revision' => 1], 'negative' => ['revision' => -1],
        'hash' => ['previous_sha256' => str_repeat('0', 64)],
        'backwards' => ['created_at' => $reservation->created_at->subMicrosecond()],
        'unknown' => ['state' => 'issued'],
        'missing_operation' => ['operation_id' => null],
        'early_expiry' => ['state' => 'expired'],
        'deadline_confirm' => ['state' => 'confirmed', 'created_at' => $reservation->expires_at],
        'deadline_requote' => ['created_at' => $reservation->expires_at],
        default => throw new InvalidArgumentException('Unknown case.'),
    };
    expect(fn () => DB::transaction(fn () => PrimaryReservationVersion::factory()->withCashMovement()->create([
        'primary_reservation_id' => $reservation->id, 'previous_sha256' => $first->sha256, ...$attributes])))
        ->toThrow(QueryException::class);
})->with(['gap', 'duplicate', 'negative', 'hash', 'backwards', 'unknown', 'missing_operation', 'early_expiry', 'deadline_confirm', 'deadline_requote']);

it('cannot change a terminal reservation again', function (string $state): void {
    $reservation = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $version = PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $reservation->id, 'state' => $state,
        'created_at' => $state === 'expired' ? $reservation->expires_at : $reservation->created_at]);
    if ($state === 'confirmed') {
        PrimaryCommitment::factory()->create(['primary_reservation_version_id' => $version->id]);
    }
    primarySchemaFlush();
    expect(fn () => DB::transaction(fn () => PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $reservation->id,
        'revision' => 3, 'state' => 'held', 'created_at' => $version->created_at])))->toThrow(QueryException::class);
})->with(['released', 'expired', 'confirmed']);

it('allows a system expiry exactly at the deadline without inventing a user operation', function (): void {
    $reservation = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $reservation->id, 'state' => 'expired',
        'created_at' => $reservation->expires_at, 'operation_id' => null]);
    primarySchemaFlush();
    expect(PrimaryReservationVersion::query()->where('state', 'expired')->sole()->operation_id)->toBeNull();
});

it('requires a commitment for confirmation in the same transaction', function (): void {
    expect(fn () => DB::transaction(function (): void {
        PrimaryReservationVersion::factory()->confirmed()->withCashMovement()->create();
        DB::statement('SET CONSTRAINTS primary_confirmation_evidence IMMEDIATE');
    }))->toThrow(QueryException::class, 'requires a commitment');
    expect(PrimaryReservationVersion::query()->count())->toBe(0);
});

it('refuses commitment reparenting operation or confirmation time substitution', function (string $case): void {
    $confirmation = PrimaryReservationVersion::factory()->confirmed()->withCashMovement()->create();
    $other = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $values = match ($case) {
        'parent' => ['primary_reservation_id' => $other->id],
        'operation' => ['operation_id' => CommandOperation::factory()->create()->id],
        'time' => ['confirmed_at' => $confirmation->created_at->addMicrosecond()],
        'recorded' => ['created_at' => $confirmation->created_at->subMicrosecond()],
        'unconfirmed' => ['primary_reservation_version_id' => PrimaryReservationVersion::query()->where('primary_reservation_id', $other->id)->sole()->id],
        default => throw new InvalidArgumentException('Unknown case.'),
    };
    expect(fn () => DB::transaction(fn () => PrimaryCommitment::factory()->create(['primary_reservation_version_id' => $confirmation->id, ...$values])))
        ->toThrow(QueryException::class);
})->with(['parent', 'operation', 'time', 'recorded', 'unconfirmed']);

it('prevents duplicate commitments and mutation of retained evidence', function (string $table, string $action): void {
    $commitment = PrimaryCommitment::factory()->create();
    primarySchemaFlush();
    $record = DB::table($table)->first();
    expect(fn () => DB::transaction(function () use ($record, $table, $action): void {
        if ($action === 'delete') {
            DB::table($table)->where('id', $record->id)->delete();
        } else {
            DB::table($table)->where('id', $record->id)->update(['created_at' => now()->addSecond()]);
        }
    }))->toThrow(QueryException::class, 'immutable');
    expect(fn () => DB::transaction(fn () => PrimaryCommitment::factory()->create(['primary_reservation_version_id' => $commitment->primary_reservation_version_id])))
        ->toThrow(QueryException::class);
})->with(['primary_reservations', 'primary_reservation_versions', 'primary_commitments'])->with(['update', 'delete']);

it('rolls back all Primary records when the surrounding command fails', function (): void {
    expect(fn () => DB::transaction(function (): void {
        PrimaryCommitment::factory()->create();
        throw new RuntimeException('command failed');
    }))->toThrow(RuntimeException::class, 'command failed');
    foreach (['primary_reservations', 'primary_reservation_versions', 'primary_commitments'] as $table) {
        expect(DB::table($table)->count())->toBe(0);
    }
});

it('reverses an empty schema but refuses rollback after reservation evidence exists', function (): void {
    $migration = require database_path('migrations/2026_09_28_143756_create_primary_reservation_records.php');
    $capacity = require database_path('migrations/2026_09_28_151253_enforce_primary_campaign_capacity_and_closure.php');
    $commands = require database_path('migrations/2026_09_28_152823_bind_primary_evidence_to_command_actors.php');
    $ordinals = require database_path('migrations/2026_09_28_154941_enforce_primary_ordinal_exclusion.php');
    $walletBindings = require database_path('migrations/2026_09_28_161335_bind_primary_reservations_to_wallet_holds.php');
    $outcomes = require database_path('migrations/2026_09_28_163057_require_completed_primary_command_outcomes.php');
    $terminalCash = require database_path('migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php');
    $confirmationReceipts = require database_path('migrations/2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php');
    $confirmationOperations = require database_path('migrations/2026_09_28_212446_bind_primary_confirmation_operations_to_purchases.php');
    $expiryFailures = require database_path('migrations/2026_09_29_112938_create_primary_expiry_failures_table.php');
    $fundings = require database_path('migrations/2026_09_30_054318_create_primary_campaign_fundings.php');
    $holdingBinding = require database_path('migrations/2026_09_30_084737_bind_primary_holdings_to_retained_commitments.php');
    $holdingBinding->down();
    $fundings->down();
    $expiryFailures->down();
    $confirmationOperations->down();
    $confirmationReceipts->down();
    $terminalCash->down();
    $outcomes->down();
    $walletBindings->down();
    $ordinals->down();
    $commands->down();
    $capacity->down();
    $migration->down();
    expect(Schema::hasTable('primary_reservations'))->toBeFalse();
    $migration->up();
    $capacity->up();
    $commands->up();
    $ordinals->up();
    $walletBindings->up();
    $outcomes->up();
    $terminalCash->up();
    $confirmationReceipts->up();
    $confirmationOperations->up();
    $expiryFailures->up();
    $fundings->up();
    $holdingBinding->up();
    PrimaryReservationRecord::factory()->withInitialVersion()->create();
    primarySchemaFlush();
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'forward migration');
    expect(fn () => $capacity->down())->toThrow(QueryException::class, 'forward migration');
    expect(fn () => $commands->down())->toThrow(QueryException::class, 'forward migration');
    expect(fn () => $ordinals->down())->toThrow(QueryException::class, 'forward migration');
    expect(Schema::hasTable('primary_reservations'))->toBeTrue();
});

it('requires the first revision to match the original hold identity and instant', function (string $case): void {
    $reservation = PrimaryReservationRecord::factory()->create();
    $changes = match ($case) {
        'state' => ['state' => 'released'],
        'operation' => ['operation_id' => CommandOperation::factory()->create()->id],
        'previous' => ['previous_sha256' => str_repeat('a', 64)],
        'instant' => ['created_at' => $reservation->created_at->addMicrosecond()],
        default => throw new InvalidArgumentException('Unknown case.'),
    };
    expect(fn () => DB::transaction(fn () => PrimaryReservationVersion::factory()->withCashMovement()->create([
        'primary_reservation_id' => $reservation->id, 'revision' => 1, 'operation_id' => $reservation->origin_operation_id,
        'previous_sha256' => null, 'created_at' => $reservation->created_at, ...$changes])))->toThrow(QueryException::class);
})->with(['state', 'operation', 'previous', 'instant']);

it('preserves earlier disclosure versions when a live hold is requoted', function (): void {
    $reservation = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $original = PrimaryReservationVersion::query()->sole();
    $version = PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $reservation->id,
        'payload' => ['source' => 'replacement-fixture'], 'created_at' => $reservation->expires_at->subMicrosecond()]);
    primarySchemaFlush();
    expect($original->refresh()->payload)->toBe(['source' => 'unsupported-fixture'])
        ->and($version->payload)->toBe(['source' => 'replacement-fixture'])
        ->and($version->previous_sha256)->toBe($original->sha256)
        ->and($reservation->refresh()->expires_at->diffInSeconds($reservation->created_at, absolute: true))->toBe(300.0);
});

it('bounds total retained allocations even through direct database writes', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create(['units' => 600, 'principal' => '3000000']);
    expect(fn () => DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create([
        'business_campaign_id' => $root->business_campaign_id, 'units' => 1, 'principal' => '5000',
    ])))->toThrow(QueryException::class, 'published campaign capacity');
    $this->travel(5)->minutes();
    PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id, 'state' => 'expired', 'operation_id' => null, 'created_at' => now()]);
    expect(fn () => DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create([
        'business_campaign_id' => $root->business_campaign_id,
    ])))->toThrow(QueryException::class, 'published campaign capacity');
});

it('rejects held and confirmed revisions after campaign closure while allowing cash-unwinding states', function (string $state): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    BusinessCampaignClosure::factory()->create(['business_campaign_id' => $root->business_campaign_id, 'closed_at' => now()]);
    $write = fn () => PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id, 'state' => $state]);
    if (in_array($state, ['held', 'confirmed'], true)) {
        expect(fn () => DB::transaction($write))->toThrow(QueryException::class, 'closed campaign');
    } else {
        expect($write()->state)->toBe('released');
        primarySchemaFlush();
    }
})->with(['held', 'confirmed', 'released']);

it('refuses to install a capacity guard over already oversubscribed evidence', function (): void {
    DB::statement('DROP TRIGGER primary_campaign_capacity ON primary_reservations');
    DB::statement('DROP TRIGGER primary_ordinals_unique ON primary_reservations');
    DB::statement('DROP TRIGGER primary_ordinal_evidence ON primary_reservations');
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create(['units' => 600, 'principal' => '3000000']);
    PrimaryReservationRecord::factory()->withInitialVersion()->create(['business_campaign_id' => $root->business_campaign_id, 'units' => 600, 'principal' => '3000000']);
    $capacity = require database_path('migrations/2026_09_28_151253_enforce_primary_campaign_capacity_and_closure.php');
    expect(fn () => DB::transaction(fn () => $capacity->up()))->toThrow(QueryException::class, 'Existing Primary allocations exceed');
});

it('creates the reservation after its campaign when the factory clock crosses a second boundary', function (): void {
    $base = CarbonImmutable::now()->startOfSecond();
    $calls = 0;
    Carbon::setTestNow(function () use ($base, &$calls): Carbon {
        return Carbon::instance($calls++ === 0 ? $base->addMicroseconds(999999) : $base->addSecond());
    });
    try {
        $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
        expect($root->created_at->greaterThanOrEqualTo(BusinessCampaign::query()->findOrFail($root->business_campaign_id)->live_at))->toBeTrue();
        primarySchemaFlush();
    } finally {
        Carbon::setTestNow();
    }
});

it('creates the commitment after its confirmation when the factory clock crosses a second boundary', function (): void {
    $base = CarbonImmutable::now()->startOfSecond();
    $calls = 0;
    Carbon::setTestNow(function () use ($base, &$calls): Carbon {
        return Carbon::instance($calls++ === 0 ? $base->addMicroseconds(999999) : $base->addSecond());
    });
    try {
        $commitment = PrimaryCommitment::factory()->create();
        $confirmation = PrimaryReservationVersion::query()->findOrFail($commitment->primary_reservation_version_id);
        expect($calls)->toBeGreaterThan(1)
            ->and($commitment->confirmed_at->equalTo($confirmation->created_at))->toBeTrue()
            ->and($commitment->created_at->greaterThanOrEqualTo($confirmation->created_at))->toBeTrue();
        primarySchemaFlush();
    } finally {
        Carbon::setTestNow();
    }
});
