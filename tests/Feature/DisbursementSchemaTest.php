<?php

declare(strict_types=1);

use App\Models\Disbursement;
use App\Models\Party;
use App\Models\PrimaryHolding;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * The database enforces the disbursement rules on its own: raw SQL cannot skip a state, approve
 * its own authorization, rewrite a record, send twice, reconcile over a conflict or close twice.
 */

function schemaId(): string
{
    return strtolower((string) Str::ulid());
}

/** @param array<string, mixed> $overrides */
function schemaEvent(string $disbursementId, int $revision, string $kind, ?int $actor, array $overrides = []): string
{
    $id = schemaId();
    $staff = in_array($kind, ['authorized', 'rejected', 'held', 'hold_released', 'intent_recorded'], true);
    DB::table('disbursement_events')->insert([...['id' => $id, 'disbursement_id' => $disbursementId, 'revision' => $revision, 'kind' => $kind,
        'actor_user_id' => $actor, 'operation_id' => $staff ? schemaId() : null, 'request_id' => $staff ? (string) Str::uuid() : null,
        'binding_sha256' => $kind === 'authorized' ? str_repeat('b', 64) : null, 'destination_sha256' => $kind === 'authorized' ? str_repeat('d', 64) : null,
        'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()], ...$overrides]);

    return $id;
}

/** @param array<string, mixed> $overrides */
function schemaIntent(Disbursement $disbursement, int $maker, int $checker, array $overrides = []): string
{
    $id = schemaId();
    DB::table('disbursement_intents')->insert([...['id' => $id, 'disbursement_id' => $disbursement->id, 'operation_id' => schemaId(),
        'request_id' => (string) Str::uuid(), 'revision' => 1, 'amount' => $disbursement->amount, 'currency' => 'RWF', 'destination_id' => 'dest-1',
        'destination_revision' => 1, 'destination_sha256' => str_repeat('d', 64), 'commitments_digest' => $disbursement->commitments_digest,
        'binding_sha256' => str_repeat('b', 64), 'intent_digest' => str_repeat('i', 64), 'provider' => 'synthetic', 'provider_reference' => 'x',
        'provider_reference_sha256' => hash('sha256', $id), 'environment' => 'testing', 'idempotent_sends' => false, 'maker_user_id' => $maker,
        'checker_user_id' => $checker, 'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()], ...$overrides]);

    return $id;
}

function schemaPhase(string $intentId, string $phase): void
{
    DB::table('disbursement_dispatches')->insert(['id' => schemaId(), 'intent_id' => $intentId, 'phase' => $phase, 'created_at' => now()]);
}

function schemaCall(string $intentId, string $kind, string $source, ?string $operationId = null): void
{
    DB::table('disbursement_provider_calls')->insert(['id' => schemaId(), 'intent_id' => $intentId, 'kind' => $kind, 'source' => $source,
        'operation_id' => $operationId, 'created_at' => now()]);
}

/** @param array<string, mixed> $overrides */
function schemaObservation(string $intentId, string $state, string $disposition, array $overrides = []): string
{
    $id = schemaId();
    DB::table('disbursement_provider_events')->insert([...['id' => $id, 'intent_id' => $intentId, 'provider' => 'synthetic',
        'provider_event_id' => 'evt-'.$id, 'content_sha256' => hash('sha256', $id), 'source' => 'callback', 'state' => $state,
        'amount' => '3000000', 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now(),
        'effective_at' => $state === 'succeeded' ? '2027-01-31T08:00:00Z' : null, 'disposition' => $disposition,
        'mismatches' => $disposition === 'unverifiable' ? '["amount"]' : '[]', 'evidence' => 'x', 'created_at' => now()], ...$overrides]);

    return $id;
}

function schemaReconcile(string $intentId, ?string $eventId, string $decision, string $causes = '[]'): string
{
    $id = schemaId();
    DB::table('disbursement_reconciliations')->insert(['id' => $id, 'intent_id' => $intentId, 'provider_event_id' => $eventId,
        'decision' => $decision, 'causes' => $causes, 'comparison' => '{}', 'created_at' => now()]);

    return $id;
}

/** @param array<string, mixed> $overrides */
function schemaClosing(string $disbursementId, string $kind, string $cause, array $overrides = []): string
{
    $id = schemaId();
    $issued = $kind === 'issued';
    DB::table('disbursement_closings')->insert([...['id' => $id, 'disbursement_id' => $disbursementId, 'intent_id' => null, 'reconciliation_id' => null,
        'kind' => $kind, 'cause' => $cause, 'causes' => in_array($cause, ['approve_recheck', 'worker_recheck'], true) ? '["mandate"]' : '[]',
        'operation_id' => $cause === 'approve_recheck' ? schemaId() : null, 'effective_at' => $issued ? '2027-01-31T08:00:00Z' : null,
        'effective_date' => $issued ? '2027-01-31' : null, 'due_dates' => $issued ? '["2027-02-28"]' : null,
        'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()], ...$overrides]);

    return $id;
}

/**
 * A disbursement that has reached `dispatched` through the database's own rules.
 *
 * @return array{disbursement: Disbursement, intent: string, maker: int, checker: int}
 */
function schemaDispatched(bool $idempotent = false): array
{
    $disbursement = Disbursement::factory()->create();
    [$maker, $checker] = [User::factory()->create()->id, User::factory()->create()->id];
    schemaEvent($disbursement->id, 1, 'authorized', $maker);
    $intent = schemaIntent($disbursement, $maker, $checker, ['idempotent_sends' => $idempotent]);
    $operation = (string) DB::table('disbursement_intents')->where('id', $intent)->value('operation_id');
    schemaEvent($disbursement->id, 2, 'intent_recorded', $checker, ['operation_id' => $operation]);
    schemaPhase($intent, 'queued');
    schemaPhase($intent, 'claimed');
    schemaCall($intent, 'send', 'dispatch');
    schemaPhase($intent, 'sent');
    schemaEvent($disbursement->id, 3, 'dispatched', null);

    return ['disbursement' => $disbursement, 'intent' => $intent, 'maker' => $maker, 'checker' => $checker];
}

/** @param list<string> $codes check (23514) or uniqueness (23505) refusals */
function refusedBySchema(Closure $write, array $codes = ['23514', '23505']): void
{
    try {
        DB::transaction(fn () => $write());
    } catch (QueryException $exception) {
        expect($codes)->toContain($exception->getCode());

        return;
    }
    throw new RuntimeException('The database accepted a write it must refuse.');
}

it('refuses to change or delete any disbursement record', function (): void {
    $chain = schemaDispatched();
    $event = schemaObservation($chain['intent'], 'succeeded', 'applied');
    $reconciliation = schemaReconcile($chain['intent'], $event, 'matched_success');
    schemaClosing($chain['disbursement']->id, 'issued', 'reconciled_success', ['intent_id' => $chain['intent'], 'reconciliation_id' => $reconciliation]);
    DB::table('disbursement_step_up_markers')->insert(['id' => schemaId(), 'actor_user_id' => $chain['checker'], 'marker' => str_repeat('m', 64), 'created_at' => now()]);

    foreach (['disbursements', 'disbursement_events', 'disbursement_intents', 'disbursement_dispatches', 'disbursement_provider_calls',
        'disbursement_provider_events', 'disbursement_reconciliations', 'disbursement_closings', 'disbursement_step_up_markers'] as $table) {
        refusedBySchema(fn () => DB::table($table)->update(['created_at' => now()->addMinute()]));
        refusedBySchema(fn () => DB::table($table)->delete());
    }
});

it('folds the event log itself: next revision only, legal transitions only', function (): void {
    $disbursement = Disbursement::factory()->create();
    $maker = User::factory()->create()->id;
    refusedBySchema(fn () => schemaEvent($disbursement->id, 2, 'authorized', $maker));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 1, 'rejected', $maker));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 1, 'dispatched', null));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 1, 'failed_closing', null));
    schemaEvent($disbursement->id, 1, 'held', $maker);
    refusedBySchema(fn () => schemaEvent($disbursement->id, 2, 'authorized', $maker));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 2, 'held', $maker));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 2, 'hold_released', $maker));
    schemaEvent($disbursement->id, 2, 'hold_released', User::factory()->create()->id);
    schemaEvent($disbursement->id, 3, 'authorized', $maker);
    refusedBySchema(fn () => schemaEvent($disbursement->id, 4, 'rejected', $maker));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 4, 'dispatched', null));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 4, 'succeeded', null));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 4, 'intent_recorded', User::factory()->create()->id));
    schemaEvent($disbursement->id, 4, 'rejected', User::factory()->create()->id);

    expect(DB::selectOne('SELECT * FROM disbursement_fold(?)', [$disbursement->id]))
        ->toMatchArray(['f_state' => 'ready', 'f_revision' => 4, 'f_maker' => null]);
});

it('requires attribution on staff events and none on system events', function (): void {
    $disbursement = Disbursement::factory()->create();
    refusedBySchema(fn () => schemaEvent($disbursement->id, 1, 'authorized', null));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 1, 'authorized', User::factory()->create()->id, ['binding_sha256' => null]));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 1, 'held', User::factory()->create()->id, ['operation_id' => null]));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 1, 'held', User::factory()->create()->id, ['destination_sha256' => str_repeat('d', 64)]));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 1, 'paid', User::factory()->create()->id));
});

it('binds an intent to the authorized amount, destination and commitments, with a checker other than the maker', function (): void {
    $disbursement = Disbursement::factory()->create();
    [$maker, $checker] = [User::factory()->create()->id, User::factory()->create()->id];
    refusedBySchema(fn () => schemaIntent($disbursement, $maker, $checker));
    schemaEvent($disbursement->id, 1, 'authorized', $maker);
    refusedBySchema(fn () => schemaIntent($disbursement, $maker, $maker));
    refusedBySchema(fn () => schemaIntent($disbursement, $checker, $maker));
    refusedBySchema(fn () => schemaIntent($disbursement, $maker, $checker, ['amount' => '3000001']));
    refusedBySchema(fn () => schemaIntent($disbursement, $maker, $checker, ['destination_sha256' => str_repeat('e', 64)]));
    refusedBySchema(fn () => schemaIntent($disbursement, $maker, $checker, ['binding_sha256' => str_repeat('c', 64)]));
    refusedBySchema(fn () => schemaIntent($disbursement, $maker, $checker, ['commitments_digest' => str_repeat('f', 64)]));
    refusedBySchema(fn () => schemaIntent($disbursement, $maker, $checker, ['revision' => 2]));
    refusedBySchema(fn () => schemaIntent($disbursement, $maker, $checker, ['environment' => 'production']));
    $intent = schemaIntent($disbursement, $maker, $checker);
    refusedBySchema(fn () => schemaEvent($disbursement->id, 2, 'intent_recorded', $maker));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 2, 'intent_recorded', $checker));
    refusedBySchema(fn () => schemaIntent($disbursement, $maker, $checker));
    schemaEvent($disbursement->id, 2, 'intent_recorded', $checker, ['operation_id' => DB::table('disbursement_intents')->where('id', $intent)->value('operation_id')]);
    refusedBySchema(fn () => schemaEvent($disbursement->id, 3, 'held', $maker));
});

it('orders dispatch phases and allows one send unless the provider accepts idempotent recovery', function (): void {
    $chain = schemaDispatched();
    refusedBySchema(fn () => schemaCall($chain['intent'], 'send', 'dispatch'));
    refusedBySchema(fn () => schemaCall($chain['intent'], 'send', 'recovery'));
    refusedBySchema(fn () => schemaPhase($chain['intent'], 'unsent'));
    refusedBySchema(fn () => schemaPhase($chain['intent'], 'claimed'));
    schemaCall($chain['intent'], 'query', 'reconcile');
    schemaCall($chain['intent'], 'query', 'requery', schemaId());
    refusedBySchema(fn () => schemaCall($chain['intent'], 'query', 'requery'));
    refusedBySchema(fn () => schemaCall($chain['intent'], 'query', 'dispatch'));

    $disbursement = Disbursement::factory()->create();
    [$maker, $checker] = [User::factory()->create()->id, User::factory()->create()->id];
    schemaEvent($disbursement->id, 1, 'authorized', $maker);
    $intent = schemaIntent($disbursement, $maker, $checker, ['idempotent_sends' => true]);
    refusedBySchema(fn () => schemaPhase($intent, 'claimed'));
    schemaPhase($intent, 'queued');
    refusedBySchema(fn () => schemaCall($intent, 'send', 'dispatch'));
    refusedBySchema(fn () => schemaCall($intent, 'query', 'reconcile'));
    refusedBySchema(fn () => schemaPhase($intent, 'sent'));
    schemaPhase($intent, 'claimed');
    refusedBySchema(fn () => schemaPhase($intent, 'recheck_failed'));
    schemaCall($intent, 'send', 'dispatch');
    schemaCall($intent, 'send', 'recovery');
    schemaObservation($intent, 'pending', 'applied');
    refusedBySchema(fn () => schemaCall($intent, 'send', 'recovery'));
    schemaPhase($intent, 'unsent');
    refusedBySchema(fn () => schemaPhase($intent, 'sent'));
});

it('consumes a step-up proof exactly once, before it expires, and never otherwise changes it', function (): void {
    $disbursement = Disbursement::factory()->create();
    $actor = User::factory()->create()->id;
    $proof = fn (array $overrides = []): string => tap(schemaId(), fn (string $id) => DB::table('disbursement_step_up_proofs')->insert([...[
        'id' => $id, 'purpose' => 'disbursement.approve', 'actor_user_id' => $actor, 'disbursement_id' => $disbursement->id, 'revision' => 1,
        'amount' => '3000000', 'destination_sha256' => str_repeat('d', 64), 'intent_digest' => str_repeat('i', 64),
        'credential_binding' => str_repeat('c', 64), 'proof_sha256' => hash('sha256', $id), 'expires_at' => now()->addMinutes(5),
        'created_at' => now()], ...$overrides]));
    refusedBySchema(fn () => $proof(['consumed_at' => now(), 'consumed_operation_id' => schemaId()]));
    refusedBySchema(fn () => $proof(['purpose' => 'audit.seal']));
    $id = $proof();
    refusedBySchema(fn () => DB::table('disbursement_step_up_proofs')->where('id', $id)->update(['consumed_at' => now()]));
    refusedBySchema(fn () => DB::table('disbursement_step_up_proofs')->where('id', $id)->update(['consumed_at' => now()->addMinutes(6), 'consumed_operation_id' => schemaId()]));
    refusedBySchema(fn () => DB::table('disbursement_step_up_proofs')->where('id', $id)->update(['consumed_at' => now(), 'consumed_operation_id' => schemaId(), 'revision' => 2]));
    DB::table('disbursement_step_up_proofs')->where('id', $id)->update(['consumed_at' => now(), 'consumed_operation_id' => schemaId()]);
    refusedBySchema(fn () => DB::table('disbursement_step_up_proofs')->where('id', $id)->update(['consumed_at' => now()->addSecond(), 'consumed_operation_id' => schemaId()]));
    refusedBySchema(fn () => DB::table('disbursement_step_up_proofs')->where('id', $id)->delete());
});

it('keeps a changed body under a known event identity only as a key conflict, and one applied final outcome', function (): void {
    $chain = schemaDispatched();
    schemaObservation($chain['intent'], 'succeeded', 'applied', ['provider_event_id' => 'evt-1', 'content_sha256' => str_repeat('1', 64)]);
    refusedBySchema(fn () => schemaObservation($chain['intent'], 'succeeded', 'duplicate', ['provider_event_id' => 'evt-1', 'content_sha256' => str_repeat('1', 64)]));
    refusedBySchema(fn () => schemaObservation($chain['intent'], 'failed', 'applied', ['provider_event_id' => 'evt-1', 'content_sha256' => str_repeat('2', 64)]));
    schemaObservation($chain['intent'], 'failed', 'key_conflict', ['provider_event_id' => 'evt-1', 'content_sha256' => str_repeat('2', 64)]);
    refusedBySchema(fn () => schemaObservation($chain['intent'], 'failed', 'applied'));
    refusedBySchema(fn () => schemaObservation($chain['intent'], 'pending', 'unverifiable', ['mismatches' => '[]']));
    refusedBySchema(fn () => schemaObservation($chain['intent'], 'pending', 'applied', ['mismatches' => '["amount"]']));
    refusedBySchema(fn () => schemaObservation($chain['intent'], 'paid', 'applied'));
});

it('reconciles only an unblocked applied final observation, once', function (): void {
    $chain = schemaDispatched();
    $pending = schemaObservation($chain['intent'], 'pending', 'applied');
    refusedBySchema(fn () => schemaReconcile($chain['intent'], $pending, 'matched_success'));
    refusedBySchema(fn () => schemaReconcile($chain['intent'], null, 'matched_success'));
    refusedBySchema(fn () => schemaReconcile($chain['intent'], null, 'exception'));
    schemaReconcile($chain['intent'], null, 'open');
    $success = schemaObservation($chain['intent'], 'succeeded', 'applied');
    refusedBySchema(fn () => schemaReconcile($chain['intent'], $success, 'matched_failure'));
    $other = schemaDispatched();
    $foreign = schemaObservation($other['intent'], 'succeeded', 'applied');
    refusedBySchema(fn () => schemaReconcile($chain['intent'], $foreign, 'matched_success'));
    schemaReconcile($chain['intent'], $success, 'matched_success');
    refusedBySchema(fn () => schemaReconcile($chain['intent'], $success, 'matched_success'));

    $blocked = schemaDispatched();
    $final = schemaObservation($blocked['intent'], 'failed', 'applied');
    schemaObservation($blocked['intent'], 'failed', 'conflict');
    refusedBySchema(fn () => schemaReconcile($blocked['intent'], $final, 'matched_failure'));
    schemaReconcile($blocked['intent'], null, 'exception', '["conflict"]');
});

it('closes a disbursement once: no double issue and no refund after issue', function (): void {
    $chain = schemaDispatched();
    $id = $chain['disbursement']->id;
    $success = schemaObservation($chain['intent'], 'succeeded', 'applied');
    refusedBySchema(fn () => schemaClosing($id, 'issued', 'reconciled_success', ['intent_id' => $chain['intent']]));
    $reconciliation = schemaReconcile($chain['intent'], $success, 'matched_success');
    refusedBySchema(fn () => schemaClosing($id, 'issued', 'reconciled_success', ['intent_id' => $chain['intent'], 'reconciliation_id' => $reconciliation,
        'effective_at' => '2027-02-01T08:00:00Z']));
    refusedBySchema(fn () => schemaClosing($id, 'failed_closing', 'reconciled_failure', ['intent_id' => $chain['intent'], 'reconciliation_id' => $reconciliation]));
    refusedBySchema(fn () => schemaClosing($id, 'issued', 'reconciled_success', ['intent_id' => $chain['intent'], 'reconciliation_id' => $reconciliation, 'due_dates' => null]));
    refusedBySchema(fn () => schemaEvent($id, 4, 'succeeded', null));
    schemaClosing($id, 'issued', 'reconciled_success', ['intent_id' => $chain['intent'], 'reconciliation_id' => $reconciliation]);
    schemaEvent($id, 4, 'succeeded', null);
    refusedBySchema(fn () => schemaClosing($id, 'issued', 'reconciled_success', ['intent_id' => $chain['intent'], 'reconciliation_id' => $reconciliation]));
    refusedBySchema(fn () => schemaClosing($id, 'failed_closing', 'worker_recheck', ['intent_id' => $chain['intent']]));
    refusedBySchema(fn () => schemaEvent($id, 5, 'failed_closing', null));
});

it('allows a failed closing only from an approve-time or pre-send worker recheck, or a reconciled failure', function (): void {
    $disbursement = Disbursement::factory()->create();
    [$maker, $checker] = [User::factory()->create()->id, User::factory()->create()->id];
    refusedBySchema(fn () => schemaClosing($disbursement->id, 'failed_closing', 'approve_recheck'));
    schemaEvent($disbursement->id, 1, 'authorized', $maker);
    refusedBySchema(fn () => schemaClosing($disbursement->id, 'failed_closing', 'approve_recheck', ['causes' => '[]']));
    refusedBySchema(fn () => schemaClosing($disbursement->id, 'failed_closing', 'reconciled_success'));
    $closing = schemaClosing($disbursement->id, 'failed_closing', 'approve_recheck');
    $operation = (string) DB::table('disbursement_closings')->where('id', $closing)->value('operation_id');
    refusedBySchema(fn () => schemaEvent($disbursement->id, 2, 'failed_closing', $maker, ['operation_id' => $operation, 'request_id' => (string) Str::uuid()]));
    refusedBySchema(fn () => schemaEvent($disbursement->id, 2, 'failed_closing', null));
    schemaEvent($disbursement->id, 2, 'failed_closing', $checker, ['operation_id' => $operation, 'request_id' => (string) Str::uuid()]);

    $queued = Disbursement::factory()->create();
    schemaEvent($queued->id, 1, 'authorized', $maker);
    $intent = schemaIntent($queued, $maker, $checker);
    schemaEvent($queued->id, 2, 'intent_recorded', $checker, ['operation_id' => DB::table('disbursement_intents')->where('id', $intent)->value('operation_id')]);
    schemaPhase($intent, 'queued');
    refusedBySchema(fn () => schemaClosing($queued->id, 'failed_closing', 'worker_recheck', ['intent_id' => $intent]));
    refusedBySchema(fn () => schemaClosing($queued->id, 'failed_closing', 'approve_recheck'));
    schemaPhase($intent, 'recheck_failed');
    refusedBySchema(fn () => schemaPhase($intent, 'claimed'));
    schemaClosing($queued->id, 'failed_closing', 'worker_recheck', ['intent_id' => $intent]);
    schemaEvent($queued->id, 3, 'failed_closing', null);

    $chain = schemaDispatched();
    $failure = schemaObservation($chain['intent'], 'failed', 'applied');
    refusedBySchema(fn () => schemaClosing($chain['disbursement']->id, 'failed_closing', 'worker_recheck', ['intent_id' => $chain['intent']]));
    $reconciliation = schemaReconcile($chain['intent'], $failure, 'matched_failure');
    schemaClosing($chain['disbursement']->id, 'failed_closing', 'reconciled_failure', ['intent_id' => $chain['intent'], 'reconciliation_id' => $reconciliation]);
    schemaEvent($chain['disbursement']->id, 4, 'failed_closing', null);
});

it('refuses a disbursement snapshot outside whole issuance units', function (): void {
    refusedBySchema(fn () => Disbursement::factory()->create(['amount' => '3000001']));
    refusedBySchema(fn () => Disbursement::factory()->create(['currency' => 'USD']));
    refusedBySchema(fn () => Disbursement::factory()->create(['commitment_count' => 0]));
});

it('issues a proposed Holding only from its campaign\'s issued closing and within its principal', function (): void {
    $chain = schemaDispatched();
    $success = schemaObservation($chain['intent'], 'succeeded', 'applied');
    $reconciliation = schemaReconcile($chain['intent'], $success, 'matched_success');
    $closing = schemaClosing($chain['disbursement']->id, 'issued', 'reconciled_success', ['intent_id' => $chain['intent'], 'reconciliation_id' => $reconciliation]);
    $holding = fn (array $overrides = []) => DB::table('primary_holdings')->insert([...['id' => schemaId(),
        'business_campaign_id' => $chain['disbursement']->business_campaign_id, 'commitment_id' => schemaId(), 'party_id' => schemaId(),
        'disbursement_closing_id' => $closing, 'units' => 300, 'principal' => '1500000', 'ordinals' => '[{"first":1,"last":300}]',
        'rights' => '{}', 'terms' => '{}', 'schedule' => '[{"index":1}]', 'issued_at' => '2027-01-31T09:00:00Z', 'disbursement_effective_at' => '2027-01-31T08:00:00Z',
        'effective_date' => '2027-01-31', 'receipt_id' => schemaId(), 'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()], ...$overrides]);
    refusedBySchema(fn () => $holding(['principal' => '1500001']));
    refusedBySchema(fn () => $holding(['business_campaign_id' => schemaId()]));
    refusedBySchema(fn () => $holding(['effective_date' => '2027-02-01']));
    refusedBySchema(fn () => $holding(['units' => 700, 'principal' => '3500000']));
    $holding();
    $holding(['payload' => encrypt(json_encode(['source' => 'synthetic']), false)]);
    refusedBySchema(fn () => $holding(['units' => 1, 'principal' => '5000']));
    $read = PrimaryHolding::query()->where('payload', '<>', 'x')->sole();
    expect([$read->principal, $read->ordinals, $read->effective_date->format('Y-m-d'), $read->payload])
        ->toEqual(['1500000', [['first' => 1, 'last' => 300]], '2027-01-31', ['source' => 'synthetic']]);
    refusedBySchema(fn () => DB::table('primary_holdings')->update(['units' => 1]));
    refusedBySchema(fn () => DB::table('primary_holdings')->delete());

    $failed = Disbursement::factory()->create();
    schemaEvent($failed->id, 1, 'authorized', $chain['maker']);
    $refund = schemaClosing($failed->id, 'failed_closing', 'approve_recheck');
    refusedBySchema(fn () => $holding(['disbursement_closing_id' => $refund, 'business_campaign_id' => $failed->business_campaign_id]));
});

it('refuses to roll back the schema once a disbursement exists', function (): void {
    Disbursement::factory()->create();
    $holdings = require database_path('migrations/2026_09_29_100100_create_primary_holdings_table.php');
    $holdings->down();
    $holdings->up();
    $migration = require database_path('migrations/2026_09_29_100000_create_disbursement_tables.php');
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Recorded disbursements require a forward migration');
});

it('keeps staff accounts and marketplace Parties disjoint and staff accounts undeletable', function (): void {
    $staff = DisbursementFixture::staff(['treasury']);
    $party = Party::factory()->create();
    refusedBySchema(fn () => DB::table('users')->where('id', $staff->id)->update(['party_id' => $party->id]));
    refusedBySchema(fn () => DB::table('staff_accounts')->where('user_id', $staff->id)->delete());
    $member = User::factory()->create();
    DB::table('users')->where('id', $member->id)->update(['party_id' => $party->id]);
    expect(DB::table('users')->where('id', $member->id)->value('party_id'))->toBe($party->id);
});
