<?php

declare(strict_types=1);

/*
 * Review probe for PR #175 c8f30fcb, check_primary_expiry_outcome() in 175455. Copy into tests/Feature/.
 *
 * PrimaryExpiryOutcomeTest's "completed" case uses code RESERVATION_CONFIRMED, so removing the
 * `result->>'status' = 'rejected'` half of the predicate survives every shipped test. This probe uses
 * a completed receipt whose code is RESERVATION_EXPIRED. PASSES on c8f30fcb; kills that mutant.
 */

use App\Models\CommandOperation;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->freezeSecond();
});

it('PROBE: an actor expiry receipt must be rejected, not merely coded RESERVATION_EXPIRED', function (string $status): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    $receipt = CommandOperation::factory()->create([...$origin->only(['actor_key', 'actor_user_id']), 'command' => 'primary.confirm',
        'target_type' => 'primary_reservation', 'target_id' => $root->id, 'result' => ['status' => $status, 'code' => 'RESERVATION_EXPIRED']]);
    expect(fn () => DB::transaction(function () use ($root, $receipt): void {
        PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id,
            'state' => 'expired', 'created_at' => $root->expires_at, 'operation_id' => $receipt->id]);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, 'rejected RESERVATION_EXPIRED outcome');
})->with(['completed', 'pending', 'REJECTED']);
