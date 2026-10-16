<?php

declare(strict_types=1);

use App\Models\CommandOperation;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DisbursementFixture;

/*
 * The staff disbursement console over both transports (C3 v2 §2e, #96 5871738394; BRS v2.0 SVC-R01,
 * ADM-R02): the published routes, token abilities, the automatic payout's facts and the Resource
 * binding. The retired maker/checker routes are gone.
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

it('renders the queue and the opened disbursement with only this viewer\'s actions', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $treasury = DisbursementFixture::staff(['treasury']);
    $approver = DisbursementFixture::staff(['approver']);
    $this->actingAs($treasury)->get(route('staff.disbursements.index'))->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertInertia(fn (Assert $page) => $page->component('admin/disbursements')
            ->where('contract_version', 'staff-disbursement-v2')->where('staff_access_version', 'staff-access-v1')
            ->where('disbursement', null)->where('refusal', null)->where('needs_attention', 1)->where('badges.disbursements', 1)
            ->missing('awaiting_second_approver')
            ->where('nav.disbursements.url', route('staff.disbursements.index', [], false))->where('nav.applications', null)
            ->where('disbursements.0.id', $disbursement->id)->where('disbursements.0.state', 'ready')->where('disbursements.0.destination', 'Unverified')
            ->where('disbursements.0.link.url', route('staff.disbursements.show', ['disbursement' => $disbursement->id], false))
            ->where('pagination.next', null));

    $this->actingAs($treasury)->get(route('staff.disbursements.show', ['disbursement' => $disbursement->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('disbursement.allowed_actions', ['disbursement.hold'])
            ->where('disbursement.actions.hold.url', route('staff.disbursements.hold', ['disbursement' => $disbursement->id], false))
            ->where('disbursement.automation', ['rule_version' => 'brs-2.0-svc-r01', 'payout' => ['currency' => 'RWF', 'amount' => '2910000'],
                'waiting_for' => 'verified_payout_destination'])
            ->where('disbursement.amount.amount', $disbursement->amount)
            ->where('disbursement.platform_fee', ['fee' => ['currency' => 'RWF', 'amount' => '90000'],
                'audit_partner_share' => ['currency' => 'RWF', 'amount' => '36000'], 'rozine_share' => ['currency' => 'RWF', 'amount' => '54000'],
                'payout' => ['currency' => 'RWF', 'amount' => '2910000'], 'version' => 'brs-2.0-fee-r01'])
            ->where('disbursement.intent', null)->where('disbursement.hold', null)
            ->missing('disbursement.maker')->missing('disbursement.checker')->missing('disbursement.approval_binding')->missing('disbursement.step_up')
            ->missing('disbursement.step_up_allowed')->missing('disbursement.independence')->missing('disbursement.viewer_is_maker')
            ->missing('disbursement.viewer_placed_hold')
            ->where('disbursement.links.operation.url', str_replace('00000000-0000-0000-0000-000000000000', '{request_id}',
                route('staff.disbursements.operations.show', ['request_id' => '00000000-0000-0000-0000-000000000000'], false))));

    $this->actingAs($approver)->get(route('staff.disbursements.show', ['disbursement' => $disbursement->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('disbursement.allowed_actions', ['disbursement.hold'])
            ->where('nav.applications.url', route('staff.applications.index', [], false)));
});

it('shows an automatic payout as the system\'s, with no lookup on its receipt', function (): void {
    ['disbursement' => $disbursement, 'intent' => $intent] = DisbursementFixture::paid();
    $this->actingAs(DisbursementFixture::staff(['treasury']))->get(route('staff.disbursements.show', ['disbursement' => $disbursement->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('needs_attention', 0)->where('badges.disbursements', 0)
            ->where('disbursement.state', 'dispatched')->where('disbursement.automation.waiting_for', null)
            ->where('disbursement.precheck.state', 'passed')
            ->where('disbursement.intent.operation_id', $intent->operation_id)
            ->where('disbursement.intent.receipt.code', 'DISBURSEMENT_INTENT_RECORDED')->where('disbursement.intent.receipt.request_id', $intent->request_id)
            ->where('disbursement.intent.receipt.link', null)
            ->where('disbursement.trail.1.action.code', 'auto_intent_recorded')->where('disbursement.trail.1.actor', 'System')
            ->where('disbursement.allowed_actions', ['disbursement.requery'])
            ->where('disbursement.actions.requery.url', route('staff.disbursements.requery', ['disbursement' => $disbursement->id], false)));
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
    $placer = DisbursementFixture::staff(['treasury']);
    $prefix = $api ? 'api.v1.' : '';
    if ($api) {
        Sanctum::actingAs($placer, ['staff:disbursements:read', 'staff:disbursements:manage']);
    } else {
        $this->actingAs($placer);
    }
    $key = (string) Str::uuid();
    $body = ['request_id' => $key, 'expected_revision' => 0, 'reason' => 'Business asked us to wait.'];
    $response = $this->postJson(route($prefix.'staff.disbursements.hold', ['disbursement' => $disbursement->id]), $body)->assertOk()
        ->assertJsonPath('code', 'DISBURSEMENT_HELD')->assertJsonPath('data.receipt.request_id', $key)
        ->assertJsonPath('data.current.state', 'on_hold')->assertJsonPath('data.current.hold.reason', 'Business asked us to wait.')
        ->assertJsonPath('data.next', null)->assertJsonPath('allowed_actions', ['disbursement.release_hold']);
    expect($response->json('data.receipt.link.url'))->toBe(route($prefix.'staff.disbursements.operations.show', ['request_id' => $key, 'command' => 'disbursement.hold'], false));
    $this->getJson(route($prefix.'staff.disbursements.operations.show', ['request_id' => $key, 'command' => 'disbursement.hold']))->assertOk()
        ->assertJsonPath('operation_id', $response->json('operation_id'))->assertJsonPath('data.current.revision', 1);
    $this->postJson(route($prefix.'staff.disbursements.release-hold', ['disbursement' => $disbursement->id]), [...$body, 'request_id' => (string) Str::uuid(), 'expected_revision' => 1, 'reason' => ''])
        ->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_FAILED')->assertJsonValidationErrors('reason');
    $this->postJson(route($prefix.'staff.disbursements.release-hold', ['disbursement' => $disbursement->id]), [...$body, 'request_id' => (string) Str::uuid(), 'expected_revision' => 0])
        ->assertConflict()->assertJsonPath('code', 'VERSION_CONFLICT')->assertJsonPath('data.receipt', null)->assertJsonPath('data.current', null);
    $this->postJson(route($prefix.'staff.disbursements.release-hold', ['disbursement' => $disbursement->id]), [...$body, 'request_id' => (string) Str::uuid(), 'expected_revision' => 1])
        ->assertOk()->assertJsonPath('code', 'DISBURSEMENT_HOLD_RELEASED')->assertJsonPath('data.current.state', 'ready')
        ->assertJsonPath('data.current.revision', 2)->assertJsonPath('allowed_actions', ['disbursement.hold']);
})->with(['web' => false, 'api' => true]);

it('limits an API token to its abilities and never lets a body choose another disbursement', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $staff = DisbursementFixture::staff(['treasury']);
    Sanctum::actingAs($staff, ['staff:disbursements:read']);
    $this->getJson(route('api.v1.staff.disbursements.show', ['disbursement' => $disbursement->id]))->assertOk()
        ->assertJsonPath('data.disbursement.allowed_actions', [])->assertJsonPath('data.disbursement.actions', []);
    $this->postJson(route('api.v1.staff.disbursements.hold', ['disbursement' => $disbursement->id]),
        ['request_id' => (string) Str::uuid(), 'expected_revision' => 0, 'reason' => 'Hold.'])->assertForbidden();
    Sanctum::actingAs($staff, []);
    $this->getJson(route('api.v1.staff.disbursements.index'))->assertForbidden();
    $this->getJson(route('api.v1.staff.disbursements.operations.show', ['request_id' => (string) Str::uuid(), 'command' => 'disbursement.hold']))->assertForbidden();

    $this->actingAs($staff);
    $this->postJson(route('staff.disbursements.hold', ['disbursement' => $disbursement->id]), ['request_id' => (string) Str::uuid(), 'expected_revision' => 0,
        'reason' => 'Hold.', 'disbursement_id' => strtolower((string) Str::ulid())])->assertUnprocessable()->assertJsonValidationErrors('disbursement_id');
    foreach (['application.release', 'disbursement.authorize', 'disbursement.approve', 'disbursement.reject'] as $command) {
        $this->getJson(route('staff.disbursements.operations.show', ['request_id' => (string) Str::uuid(), 'command' => $command]))
            ->assertUnprocessable()->assertJsonValidationErrors('command');
    }
    $this->getJson(route('staff.disbursements.operations.show', ['request_id' => (string) Str::uuid(), 'command' => 'disbursement.hold']))
        ->assertNotFound()->assertJsonPath('code', 'OPERATION_NOT_FOUND');
});

it('no longer publishes the maker/checker routes', function (string $path, bool $api): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $staff = DisbursementFixture::staff(['treasury', 'approver']);
    if ($api) {
        Sanctum::actingAs($staff, ['staff:disbursements:read', 'staff:disbursements:manage']);
    } else {
        $this->actingAs($staff);
    }
    $this->postJson(($api ? '/api/v1/staff' : '/admin').'/disbursements/'.$disbursement->id.'/'.$path,
        ['request_id' => (string) Str::uuid(), 'expected_revision' => 0, 'reason' => 'Approve.', 'intent_digest' => str_repeat('0', 64), 'code' => '000000'])
        ->assertNotFound();
    expect(Route::has('staff.disbursements.'.$path))->toBeFalse()
        ->and(Route::has('api.v1.staff.disbursements.'.$path))->toBeFalse()
        ->and(CommandOperation::query()->count())->toBe(0)
        ->and(DisbursementFixture::detail($staff, $disbursement)['revision'])->toBe(0);
})->with(['authorize', 'approve', 'reject', 'step-up'])->with(['web' => false, 'api' => true]);

it('prepares synthetic scenarios, pays them automatically and runs the worker and reconciler from the console', function (): void {
    expect(console('local:disbursement', ['--seed' => true]))->toMatchArray([0 => 0])->and(console_output())->toContain('Queue: /admin/disbursements');
    $disbursement = Disbursement::query()->sole();
    $intent = DisbursementIntent::query()->sole();
    expect([$intent->disbursement_id, $intent->maker_user_id, $intent->rule_version])->toBe([$disbursement->id, null, 'brs-2.0-svc-r01']);
    expect(console('local:disbursement', ['--script-send' => 'nack']))->toMatchArray([0 => 0])->and(console_output())->toContain('Sends now answer nack');
    expect(console('local:disbursement', ['--script-send' => 'ack', '--idempotent' => true]))->toMatchArray([0 => 0])->and(console_output())->toContain('(idempotent)');
    expect(console('local:disbursement', ['--script-send' => 'maybe']))->toMatchArray([0 => 2]);
    expect(console('disbursements:dispatch'))->toMatchArray([0 => 0])->and(console_output())->toContain('Claimed 0 payout dispatches');
    expect(console('disbursements:dispatch', ['--limit' => 0]))->toMatchArray([0 => 2]);
    expect(console('local:disbursement', ['--script-query' => $intent->id]))->toMatchArray([0 => 0])->and(console_output())->toContain('answer nothing');
    expect(console('disbursements:reconcile'))->toMatchArray([0 => 0])->and(console_output())
        ->toContain('Opened 0 funded disbursements')->toContain('queued 0 automatic payouts, failed-closed 0')->toContain('Decisions: open 1');
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

it('opens and fails closed a funded campaign from the reconciler when its recheck fails', function (): void {
    $campaign = DisbursementFixture::sources()->fund();
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['mandate']);
    expect(console('disbursements:reconcile'))->toMatchArray([0 => 0])->and(console_output())
        ->toContain('Opened 1 funded disbursements (0 already open); queued 0 automatic payouts, failed-closed 1.')
        ->and(DisbursementIntent::query()->count())->toBe(0);
});

it('shows a failed closing\'s refund receipt without a lookup, since no staff command closed it', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['mandate']);
    DisbursementFixture::release($campaign);
    $closing = DisbursementClosing::query()->sole();
    $this->actingAs(DisbursementFixture::staff(['treasury']))->get(route('staff.disbursements.show', ['disbursement' => $disbursement->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('disbursement.state', 'failed_closing')->where('disbursement.intent', null)
            ->where('disbursement.precheck.causes', ['mandate'])
            ->where('disbursement.refund.total', ['currency' => 'RWF', 'amount' => $disbursement->amount])
            ->where('disbursement.refund.receipt.code', 'COMMITMENTS_REFUNDED')->where('disbursement.refund.receipt.receipt_id', $closing->id)
            ->where('disbursement.refund.receipt.link', null)->where('disbursement.allowed_actions', []));
});
