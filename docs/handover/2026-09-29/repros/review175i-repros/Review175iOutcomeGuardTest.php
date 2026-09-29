<?php

declare(strict_types=1);

/*
 * Review repros for PR #175 delta 124eec88..ad598a09 (require_completed_primary_outcome).
 * Copy into tests/Feature/ and run against a PostgreSQL test database.
 *
 * Tests named "DEFECT:" assert the behaviour we expect; they FAIL on ad598a09.
 * Tests named "PROBE:" try to bypass the guard; they PASS when the guard holds.
 */

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->freezeSecond();
});

/** @param array<string, mixed> $result */
function r175iReservationOperation(PrimaryReservationRecord $root, string $command, array $result): CommandOperation
{
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();

    return CommandOperation::factory()->create([...$origin->only(['actor_key', 'actor_user_id']),
        'command' => $command, 'target_type' => 'primary_reservation', 'target_id' => $root->id, 'result' => $result]);
}

function r175iOutcomeMigration(): object
{
    return require database_path('migrations/2026_09_28_163057_require_completed_primary_command_outcomes.php');
}

it('DEFECT: refuses an actor-driven expiry observation whose command outcome is completed', function (string $command): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $operation = r175iReservationOperation($root, $command, ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED']);

    // A replay of this operation says "completed" while the retained evidence says the hold expired
    // (and, for confirm, no commitment exists). The guard skips every expired row, whatever its outcome.
    expect(fn () => DB::transaction(function () use ($root, $operation): void {
        PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $root->id, 'state' => 'expired',
            'created_at' => $root->expires_at, 'operation_id' => $operation->id]);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class);
})->with(['primary.confirm', 'primary.release']);

it('PROBE: journal-last insertion of a completed receipt after the evidence still commits', function (): void {
    $party = Party::factory()->create();
    $user = User::factory()->create(['party_id' => $party->id]);
    $campaign = BusinessCampaign::factory()->create();
    $operationId = strtolower((string) Str::ulid());
    DB::transaction(function () use ($party, $user, $campaign, $operationId): void {
        PrimaryReservationRecord::factory()->withInitialVersion()->create(['business_campaign_id' => $campaign->id,
            'party_id' => $party->id, 'origin_operation_id' => $operationId]);
        CommandOperation::factory()->create(['id' => $operationId, 'actor_key' => 'party:'.$party->id, 'actor_user_id' => $user->id,
            'command' => 'primary.reserve', 'target_type' => 'campaign', 'target_id' => $campaign->id,
            'result' => ['status' => 'completed', 'code' => 'RESERVATION_HELD']]);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
    });
    expect(PrimaryReservationRecord::query()->count())->toBe(1);
});

it('PROBE: journal-last insertion of a rejected receipt is refused whatever the constraint mode', function (string $mode): void {
    $party = Party::factory()->create();
    $user = User::factory()->create(['party_id' => $party->id]);
    $campaign = BusinessCampaign::factory()->create();
    $operationId = strtolower((string) Str::ulid());
    expect(fn () => DB::transaction(function () use ($party, $user, $campaign, $operationId, $mode): void {
        if ($mode === 'deferred') {
            DB::statement('SET CONSTRAINTS primary_reservation_outcome_bound, primary_version_outcome_bound DEFERRED');
        }
        PrimaryReservationRecord::factory()->withInitialVersion()->create(['business_campaign_id' => $campaign->id,
            'party_id' => $party->id, 'origin_operation_id' => $operationId]);
        CommandOperation::factory()->create(['id' => $operationId, 'actor_key' => 'party:'.$party->id, 'actor_user_id' => $user->id,
            'command' => 'primary.reserve', 'target_type' => 'campaign', 'target_id' => $campaign->id,
            'result' => ['status' => 'rejected', 'code' => 'SYNTHETIC_REFUSAL']]);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, 'completed command outcome');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(PrimaryReservationRecord::query()->count())->toBe(0);
})->with(['default', 'deferred']);

it('PROBE: forcing the outcome trigger immediate before the receipt exists refuses rather than skips', function (): void {
    $party = Party::factory()->create();
    $user = User::factory()->create(['party_id' => $party->id]);
    $campaign = BusinessCampaign::factory()->create();
    $operationId = strtolower((string) Str::ulid());
    expect(fn () => DB::transaction(function () use ($party, $campaign, $operationId): void {
        PrimaryReservationRecord::factory()->withInitialVersion()->create(['business_campaign_id' => $campaign->id,
            'party_id' => $party->id, 'origin_operation_id' => $operationId]);
        DB::statement('SET CONSTRAINTS primary_reservation_outcome_bound IMMEDIATE');
    }))->toThrow(QueryException::class, 'completed command outcome');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect($user->exists)->toBeTrue();
});

it('PROBE: a receipt cannot be flipped or removed after the guard has passed', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(fn () => DB::transaction(fn () => DB::update("UPDATE command_operations SET result = jsonb_set(result, '{status}', '\"rejected\"') WHERE id = ?", [$root->origin_operation_id])))
        ->toThrow(QueryException::class, 'immutable')
        ->and(fn () => DB::transaction(fn () => DB::delete('DELETE FROM command_operations WHERE id = ?', [$root->origin_operation_id])))
        ->toThrow(QueryException::class, 'immutable')
        ->and(fn () => DB::transaction(fn () => DB::update("UPDATE primary_reservation_versions SET state = 'expired' WHERE primary_reservation_id = ?", [$root->id])))
        ->toThrow(QueryException::class, 'immutable');
});

it('PROBE: the pre-install audit preserves actor-failed and system expiry observations', function (): void {
    $migration = r175iOutcomeMigration();
    $migration->down();
    $actorExpired = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $operation = r175iReservationOperation($actorExpired, 'primary.confirm', ['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED']);
    PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $actorExpired->id, 'state' => 'expired',
        'created_at' => $actorExpired->expires_at, 'operation_id' => $operation->id]);
    $systemExpired = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $systemExpired->id, 'state' => 'expired',
        'created_at' => $systemExpired->expires_at, 'operation_id' => null]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $migration->up();
    expect(DB::selectOne("SELECT count(*) AS total FROM pg_trigger WHERE tgname IN ('primary_reservation_outcome_bound', 'primary_version_outcome_bound')")->total)->toBe(2)
        ->and(PrimaryReservationVersion::query()->where('state', 'expired')->count())->toBe(2);
});

it('PROBE: the pre-install audit refuses an actor-expired row whose root receipt was rejected', function (): void {
    $migration = r175iOutcomeMigration();
    $migration->down();
    $root = PrimaryReservationRecord::factory()->make();
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    $rejected = CommandOperation::factory()->create([...$origin->only(['actor_key', 'actor_user_id', 'command', 'target_type', 'target_id']),
        'result' => ['status' => 'rejected']]);
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create([...$root->only(['business_campaign_id', 'party_id']), 'origin_operation_id' => $rejected->id]);
    PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $root->id, 'state' => 'expired', 'created_at' => $root->expires_at, 'operation_id' => null]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'completed command outcome');
});

it('DEFECT (S3-D follow-up): a cash release posting is not bound to any release outcome or released revision', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $rejected = r175iReservationOperation($root, 'primary.release', ['status' => 'rejected', 'code' => 'SYNTHETIC_REFUSAL']);

    // Held cash goes back to available while the latest retained revision is still "held" and the only
    // release receipt is rejected. The outcome guard cannot see this: postings carry the reserve origin id.
    expect(fn () => DB::transaction(function () use ($root, $rejected): void {
        $postings = app(WalletPostings::class);
        $postings->release($postings->lockForParty($root->party_id), WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
        expect($rejected->result['status'])->toBe('rejected');
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class);
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0);
});
