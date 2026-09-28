<?php

declare(strict_types=1);

use App\Models\CommandOperation;
use App\Models\Disbursement;
use App\Models\DisbursementIntent;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DisbursementFixture;

/*
 * The staff disbursement console over both transports (C3 v2 §2e, #96 5871738394): the published
 * routes, token abilities, the step-up exchange's DTO and throttle, and the Resource binding.
 */

beforeEach(fn () => $this->withoutVite());

/**
 * @param  array<string, mixed>  $parameters
 * @return array{0: int}
 */
function console(string $command, array $parameters = []): array
{
    return [Artisan::call($command, $parameters)];
}

function console_output(): string
{
    return Artisan::output();
}

/** @return array{disbursement: Disbursement, maker: User, checker: User} */
function authorizedDisbursement(): array
{
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);

    return ['disbursement' => $disbursement, 'maker' => $maker, 'checker' => $checker];
}

it('renders the queue and the opened disbursement with only this viewer\'s actions', function (): void {
    ['disbursement' => $disbursement, 'maker' => $maker, 'checker' => $checker] = authorizedDisbursement();
    $this->actingAs($maker)->get(route('staff.disbursements.index'))->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertInertia(fn (Assert $page) => $page->component('admin/disbursements')
            ->where('contract_version', 'staff-disbursement-v1')->where('staff_access_version', 'staff-access-v1')
            ->where('disbursement', null)->where('refusal', null)->where('awaiting_second_approver', 1)->where('badges.disbursements', 1)
            ->where('nav.disbursements.url', route('staff.disbursements.index', [], false))->where('nav.applications', null)
            ->where('disbursements.0.id', $disbursement->id)->where('disbursements.0.state', 'awaiting_second_approver')
            ->where('disbursements.0.link.url', route('staff.disbursements.show', ['disbursement' => $disbursement->id], false))
            ->where('pagination.next', null));

    $this->actingAs($maker)->get(route('staff.disbursements.show', ['disbursement' => $disbursement->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('disbursement.viewer_is_maker', true)->where('disbursement.step_up.route', null)
            ->where('disbursement.allowed_actions', ['disbursement.hold'])
            ->where('disbursement.actions.hold.url', route('staff.disbursements.hold', ['disbursement' => $disbursement->id], false))
            ->missing('disbursement.step_up_allowed')
            ->where('disbursement.links.operation.url', str_replace('00000000-0000-0000-0000-000000000000', '{request_id}',
                route('staff.disbursements.operations.show', ['request_id' => '00000000-0000-0000-0000-000000000000'], false))));

    $this->actingAs($checker)->get(route('staff.disbursements.show', ['disbursement' => $disbursement->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('disbursement.step_up.purpose', 'disbursement.approve')
            ->where('disbursement.step_up.route.url', route('staff.disbursements.step-up', ['disbursement' => $disbursement->id], false))
            ->where('disbursement.approval_binding.revision', 1)->where('disbursement.approval_binding.amount.amount', $disbursement->amount)
            ->where('disbursement.allowed_actions', ['disbursement.approve', 'disbursement.reject', 'disbursement.hold'])
            ->where('nav.applications.url', route('staff.applications.index', [], false)));
});

it('shows a scoped refusal for an unknown disbursement and the access page for a non-staff account', function (): void {
    $viewer = DisbursementFixture::staff(['compliance']);
    $this->actingAs($viewer)->get(route('staff.disbursements.show', ['disbursement' => strtolower((string) Str::ulid())]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('disbursement', null)->where('refusal', ['code' => 'DISBURSEMENT_NOT_FOUND', 'status' => 404]));
    $this->actingAs(User::factory()->create())->get(route('staff.disbursements.index'))->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('identity/access-denied')->where('code', 'STAFF_ACCESS_REQUIRED'));
    $this->actingAs(DisbursementFixture::staff(['analyst']))->getJson(route('staff.disbursements.index'))->assertForbidden()
        ->assertJsonPath('code', 'STAFF_PERMISSION_REQUIRED');
});

it('runs a command over both transports and returns the receipt beside fresh facts', function (bool $api): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $prefix = $api ? 'api.v1.' : '';
    if ($api) {
        Sanctum::actingAs($maker, ['staff:disbursements:read', 'staff:disbursements:manage']);
    } else {
        $this->actingAs($maker);
    }
    $key = (string) Str::uuid();
    $body = ['request_id' => $key, 'expected_revision' => 0, 'reason' => 'Funded in full.'];
    $response = $this->postJson(route($prefix.'staff.disbursements.authorize', ['disbursement' => $disbursement->id]), $body)->assertOk()
        ->assertJsonPath('code', 'DISBURSEMENT_AUTHORIZED')->assertJsonPath('data.receipt.request_id', $key)
        ->assertJsonPath('data.current.state', 'awaiting_second_approver')->assertJsonPath('data.next', null)
        ->assertJsonPath('allowed_actions', ['disbursement.hold']);
    expect($response->json('data.receipt.link.url'))->toBe(route($prefix.'staff.disbursements.operations.show', ['request_id' => $key, 'command' => 'disbursement.authorize'], false));
    $this->getJson(route($prefix.'staff.disbursements.operations.show', ['request_id' => $key, 'command' => 'disbursement.authorize']))->assertOk()
        ->assertJsonPath('operation_id', $response->json('operation_id'))->assertJsonPath('data.current.revision', 1);
    $this->postJson(route($prefix.'staff.disbursements.hold', ['disbursement' => $disbursement->id]), [...$body, 'request_id' => (string) Str::uuid(), 'expected_revision' => 1, 'reason' => ''])
        ->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_FAILED')->assertJsonValidationErrors('reason');
    $this->postJson(route($prefix.'staff.disbursements.hold', ['disbursement' => $disbursement->id]), [...$body, 'request_id' => (string) Str::uuid(), 'expected_revision' => 0])
        ->assertConflict()->assertJsonPath('code', 'VERSION_CONFLICT')->assertJsonPath('data.receipt', null)->assertJsonPath('data.current', null);
})->with(['web' => false, 'api' => true]);

it('limits an API token to its abilities and never lets a body choose another disbursement', function (): void {
    ['disbursement' => $disbursement, 'maker' => $maker] = authorizedDisbursement();
    Sanctum::actingAs($maker, ['staff:disbursements:read']);
    $this->getJson(route('api.v1.staff.disbursements.show', ['disbursement' => $disbursement->id]))->assertOk()
        ->assertJsonPath('data.disbursement.allowed_actions', [])->assertJsonPath('data.disbursement.step_up.route', null);
    $this->postJson(route('api.v1.staff.disbursements.hold', ['disbursement' => $disbursement->id]),
        ['request_id' => (string) Str::uuid(), 'expected_revision' => 1, 'reason' => 'Hold.'])->assertForbidden();
    Sanctum::actingAs($maker, []);
    $this->getJson(route('api.v1.staff.disbursements.index'))->assertForbidden();
    $this->getJson(route('api.v1.staff.disbursements.operations.show', ['request_id' => (string) Str::uuid(), 'command' => 'disbursement.hold']))->assertForbidden();

    $this->actingAs($maker);
    $this->postJson(route('staff.disbursements.hold', ['disbursement' => $disbursement->id]), ['request_id' => (string) Str::uuid(), 'expected_revision' => 1,
        'reason' => 'Hold.', 'disbursement_id' => strtolower((string) Str::ulid())])->assertUnprocessable()->assertJsonValidationErrors('disbursement_id');
    $this->postJson(route('staff.disbursements.hold', ['disbursement' => $disbursement->id]), ['request_id' => (string) Str::uuid(), 'expected_revision' => 1,
        'reason' => 'Hold.', 'step_up_proof' => 'x'])->assertUnprocessable()->assertJsonValidationErrors('step_up_proof');
    $this->getJson(route('staff.disbursements.operations.show', ['request_id' => (string) Str::uuid(), 'command' => 'application.release']))
        ->assertUnprocessable()->assertJsonValidationErrors('command');
    $this->getJson(route('staff.disbursements.operations.show', ['request_id' => (string) Str::uuid(), 'command' => 'disbursement.hold']))
        ->assertNotFound()->assertJsonPath('code', 'OPERATION_NOT_FOUND');
});

it('exchanges a code for a private, uncached, unjournaled proof and approves with it', function (bool $api): void {
    ['disbursement' => $disbursement, 'checker' => $checker] = authorizedDisbursement();
    $prefix = $api ? 'api.v1.' : '';
    if ($api) {
        Sanctum::actingAs($checker, ['staff:disbursements:read', 'staff:disbursements:manage']);
    } else {
        $this->actingAs($checker);
    }
    $binding = DisbursementFixture::detail($checker, $disbursement)['approval_binding'];
    $operations = CommandOperation::query()->count();
    $proof = $this->postJson(route($prefix.'staff.disbursements.step-up', ['disbursement' => $disbursement->id]),
        ['expected_revision' => 1, 'intent_digest' => $binding['intent_digest'], 'code' => DisbursementFixture::code($checker)])
        ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonStructure(['proof', 'expires_at'])->json('proof');
    expect(CommandOperation::query()->count())->toBe($operations);
    $this->postJson(route($prefix.'staff.disbursements.approve', ['disbursement' => $disbursement->id]),
        ['request_id' => (string) Str::uuid(), 'expected_revision' => 1, 'reason' => 'Second approval.', 'step_up_proof' => $proof])
        ->assertOk()->assertJsonPath('code', 'DISBURSEMENT_INTENT_RECORDED')->assertJsonPath('data.current.state', 'dispatched')
        ->assertJsonPath('data.current.intent.receipt.code', 'DISBURSEMENT_INTENT_RECORDED')
        ->assertJsonMissingPath('data.current.step_up_allowed');
    $record = CommandOperation::query()->where('command', 'disbursement.approve')->sole();
    expect(json_encode($record->result))->not->toContain($proof);
})->with(['web' => false, 'api' => true]);

it('refuses the exchange with the published codes and throttles it', function (): void {
    ['disbursement' => $disbursement, 'maker' => $maker, 'checker' => $checker] = authorizedDisbursement();
    $digest = DisbursementFixture::detail($checker, $disbursement)['approval_binding']['intent_digest'];
    $url = route('staff.disbursements.step-up', ['disbursement' => $disbursement->id]);
    $this->actingAs($maker)->postJson($url, ['expected_revision' => 1, 'intent_digest' => $digest, 'code' => '000000'])->assertForbidden();
    $this->actingAs($checker)->postJson($url, ['expected_revision' => 1, 'intent_digest' => $digest, 'code' => '000000', 'request_id' => (string) Str::uuid()])
        ->assertUnprocessable()->assertJsonValidationErrors('request_id');
    $this->postJson($url, ['expected_revision' => 2, 'intent_digest' => $digest, 'code' => '000000'])->assertConflict()->assertJsonPath('code', 'VERSION_CONFLICT');
    $this->postJson($url, ['expected_revision' => 1, 'intent_digest' => str_repeat('0', 64), 'code' => '000000'])->assertConflict()->assertJsonPath('code', 'DIGEST_STALE');
    $this->postJson($url, ['expected_revision' => 1, 'intent_digest' => $digest, 'code' => DisbursementFixture::code($checker) === '000000' ? '111111' : '000000'])
        ->assertUnprocessable()->assertJsonPath('code', 'STEP_UP_CODE_INVALID')->assertJsonValidationErrors('code');
    $this->postJson($url, ['expected_revision' => 1, 'intent_digest' => $digest, 'code' => '123'])->assertUnprocessable();
    $this->postJson($url, ['expected_revision' => 1, 'intent_digest' => $digest, 'code' => '123'])->assertTooManyRequests()->assertHeader('Retry-After');
});

it('prepares synthetic scenarios and runs the worker and reconciler from the console', function (): void {
    expect(console('local:disbursement', ['--seed' => true]))->toMatchArray([0 => 0])->and(console_output())->toContain('Queue: /admin/disbursements');
    $disbursement = Disbursement::query()->sole();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    expect(console('local:disbursement', ['--script-send' => 'nack']))->toMatchArray([0 => 0])->and(console_output())->toContain('Sends now answer nack');
    expect(console('local:disbursement', ['--script-send' => 'ack', '--idempotent' => true]))->toMatchArray([0 => 0])->and(console_output())->toContain('(idempotent)');
    expect(console('local:disbursement', ['--script-send' => 'maybe']))->toMatchArray([0 => 2]);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    DisbursementFixture::command($checker, $disbursement, 'approve', 1, DisbursementFixture::stepUp($checker, $disbursement)['proof']);
    $intent = DisbursementIntent::query()->sole();
    expect(console('disbursements:dispatch'))->toMatchArray([0 => 0])->and(console_output())->toContain('Claimed 0 payout dispatches');
    expect(console('disbursements:dispatch', ['--limit' => 0]))->toMatchArray([0 => 2]);
    expect(console('local:disbursement', ['--script-query' => $intent->id]))->toMatchArray([0 => 0])->and(console_output())->toContain('answer nothing');
    expect(console('disbursements:reconcile'))->toMatchArray([0 => 0])->and(console_output())->toContain('Decisions: open 1');
    expect(console('disbursements:reconcile', ['--limit' => 'x']))->toMatchArray([0 => 2]);
    expect(console('local:disbursement', ['--event' => $intent->id, '--state' => 'x']))->toMatchArray([0 => 2]);
    expect(console('local:disbursement', ['--event' => $intent->id, '--state' => 'succeeded', '--effective-at' => '2027-01-31T08:00:00+00:00']))
        ->toMatchArray([0 => 0])->and(console_output())->toContain('Callback: applied; reconciliation matched_success');
    expect(console('local:disbursement', ['--fail-recheck' => $disbursement->business_campaign_id]))->toMatchArray([0 => 0])->and(console_output())->toContain('fail on: mandate');
    expect(console('local:disbursement', ['--event' => strtolower((string) Str::ulid()), '--state' => 'failed']))->toMatchArray([0 => 1]);
    config(['isolation.live_money_enabled' => true]);
    expect(console('disbursements:reconcile'))->toMatchArray([0 => 0])->and(console_output())->toContain('unavailable here');
    expect(console('disbursements:dispatch'))->toMatchArray([0 => 1]);
    expect(console('local:disbursement', ['--seed' => true]))->toMatchArray([0 => 1]);
});
