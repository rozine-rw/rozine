<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\RecordIsolatedAuditSourceFacts;
use App\Application\Evidence\GetAuditStatements;
use App\Application\Evidence\IngestStatement;
use App\Models\AuditReport;
use App\Models\RoleMembership;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSourceFactsFixture;
use Tests\Support\BusinessQuoteFixture as Fixture;

beforeEach(function (): void {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-09-25 10:00:00', 'UTC'));
});

/** @return array<string, mixed> */
function procedureHttpFixture(string $kind = 'flash'): array
{
    $fixture = Fixture::ready(auditKind: $kind);
    Fixture::submit($fixture, Fixture::acceptance($fixture));

    return $fixture;
}

/**
 * @param  array<string, mixed>  $fixture
 * @return array<string, mixed>
 */
function procedureStartPayload(array $fixture): array
{
    return ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1,
        'assignment_id' => $fixture['assignment']->id,
        'expected_revision' => $fixture['assignment']->refresh()->revision,
        'application_id' => $fixture['application']->id, 'application_revision' => $fixture['application']->refresh()->revision];
}

it('starts, reads, saves and recovers through matching web and API routes', function (bool $api): void {
    $fixture = procedureHttpFixture();
    $actor = $fixture['audit']['partners'][0]['user'];
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    if ($api) {
        Sanctum::actingAs($actor, ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($actor);
    }
    $this->get(route($prefix.'jobs.show', ['assignment' => $fixture['assignment']->id]))->assertOk();
    $this->assertDatabaseCount('audit_reports', 0);
    $start = $this->postJson(route($prefix.'reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))
        ->assertOk()->assertJsonPath('code', 'AUDIT_REPORT_STARTED')->json();
    $id = $start['data']['audit_id'];
    expect($start['data']['next'])->toBe(['url' => route($prefix.'reports.show', ['report' => $id], false), 'method' => 'get']);
    $page = $this->get($start['data']['next']['url'])->assertOk()->assertHeaderContains('Cache-Control', 'no-store');
    if ($api) {
        $page->assertJsonPath('data.stage.step', 'review')->assertJsonPath('data.can_continue', true)->assertJsonMissingPath('data.sources');
    } else {
        $page->assertInertia(fn (Assert $inertia): Assert => $inertia->component('auditor/audit')->where('stage.step', 'review')->where('can_continue', true)->missing('sources'));
    }
    $this->assertDatabaseCount('audit_report_versions', 1);
    $request = (string) Str::uuid();
    $body = ['request_id' => $request, 'identity_context_revision' => 1, 'expected_revision' => 1, 'audit_id' => $id, 'step' => 'review'];
    $saved = $this->postJson(route($prefix.'reports.save', ['report' => $id]), $body)
        ->assertOk()->assertJsonPath('code', 'AUDIT_STEP_SAVED')->assertJsonPath('revision', 2)->json();
    $this->postJson(route($prefix.'reports.save', ['report' => $id]), $body)->assertOk()->assertJsonPath('operation_id', $saved['operation_id']);
    $this->getJson(route($prefix.'reports.operations.show', ['request_id' => $request, 'command' => 'audit.save_step']))
        ->assertOk()->assertJsonPath('data', $saved['data']);
    $read = $this->get($saved['data']['next']['url'])->assertOk();
    if ($api) {
        $read->assertJsonPath('data.stage.step', 'check_in')->assertJsonPath('data.can_continue', false)->assertJsonPath('data.stage.package.handoff', null);
    } else {
        $read->assertInertia(fn (Assert $inertia): Assert => $inertia->where('stage.step', 'check_in')->where('can_continue', false)->where('stage.package.handoff', null));
    }
    $this->assertDatabaseCount('audit_report_versions', 2);
    $back = route($prefix.'reports.show', ['report' => $id, 'step' => 'review'], false);
    $props = $api ? $read->json('data') : $read->viewData('page')['props'];
    expect($props['links']['back']['url'])->toBe($back);
    $previous = $this->get($back)->assertOk();
    $previousProps = $api ? $previous->json('data') : $previous->viewData('page')['props'];
    expect($previousProps['stage']['step'])->toBe('review')->and($previousProps['can_continue'])->toBeTrue()
        ->and(AuditReport::query()->whereKey($id)->firstOrFail()->step)->toBe('check_in');
    $this->getJson(route($prefix.'reports.show', ['report' => $id, 'step' => 'seal']))->assertUnprocessable()
        ->assertJsonPath('code', 'AUDIT_STEP_NOT_AVAILABLE');
    $this->getJson(route($prefix.'reports.show', ['report' => $id, 'step' => ['review']]))->assertUnprocessable()->assertJsonValidationErrors('step');
    $this->assertDatabaseCount('audit_report_versions', 2);
})->with([false, true]);

it('returns a recorded 422 with field errors for a skipped procedure step', function (bool $api): void {
    $fixture = procedureHttpFixture();
    $user = $fixture['audit']['partners'][0]['user'];
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    if ($api) {
        Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($user);
    }
    $id = $this->postJson(route($prefix.'reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))->assertOk()->json('data.audit_id');
    $request = (string) Str::uuid();
    $body = ['request_id' => $request, 'identity_context_revision' => 1, 'expected_revision' => 1, 'audit_id' => $id, 'step' => 'seal', 'note' => 'Skip'];
    $refusal = $this->postJson(route($prefix.'reports.save', ['report' => $id]), $body)->assertUnprocessable()
        ->assertJsonPath('code', 'AUDIT_STEP_NOT_AVAILABLE')->assertJsonValidationErrors('step')->json();
    $this->getJson(route($prefix.'reports.operations.show', ['request_id' => $request, 'command' => 'audit.save_step']))
        ->assertUnprocessable()->assertJsonPath('operation_id', $refusal['operation_id']);
    expect(AuditReport::query()->whereKey($id)->firstOrFail()->revision)->toBe(1);
})->with([false, true]);

it('enforces API read and command abilities and matches the payload report to the route', function (): void {
    $fixture = procedureHttpFixture();
    $user = $fixture['audit']['partners'][0]['user'];
    Sanctum::actingAs($user, ['auditor:read']);
    $this->postJson(route('api.v1.auditor.reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))->assertForbidden();
    Sanctum::actingAs($user, ['auditor:command']);
    $this->postJson(route('api.v1.auditor.reports.start', ['assignment' => $fixture['assignment']->id]), [
        ...procedureStartPayload($fixture), 'assignment_id' => (string) Str::ulid(),
    ])->assertUnprocessable()->assertJsonValidationErrors('assignment_id');
    $id = $this->postJson(route('api.v1.auditor.reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))->assertOk()->json('data.audit_id');
    $this->getJson(route('api.v1.auditor.reports.show', ['report' => $id]))->assertForbidden();
    $this->postJson(route('api.v1.auditor.reports.save', ['report' => $id]), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => 1, 'audit_id' => (string) Str::ulid(), 'step' => 'review',
    ])->assertUnprocessable()->assertJsonValidationErrors('audit_id');
    Sanctum::actingAs($user, ['auditor:read']);
    $this->getJson(route('api.v1.auditor.reports.show', ['report' => $id]))->assertOk()->assertJsonPath('data.allowed_actions', []);
    $this->assertDatabaseCount('audit_report_versions', 1);
});

it('projects the exact monthly facts and authorizes every original download on both transports', function (bool $api): void {
    $fixture = procedureHttpFixture('routine');
    $user = $fixture['audit']['partners'][0]['user'];
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    if ($api) {
        Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($user);
    }
    $id = $this->postJson(route($prefix.'reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))->assertOk()->json('data.audit_id');
    $path = route($prefix.'reports.show', ['report' => $id]);
    $page = $this->get($path)->assertOk();
    if ($api) {
        $page->assertJsonPath('data.audit.month', '2026-08')->assertJsonPath('data.can_continue', true)
            ->assertJsonPath('data.stage.statements.inflow.amount', '4000000')->assertJsonPath('data.stage.statements.net.amount', '3000000')
            ->assertJsonPath('data.stage.statements.cover', ['value' => '4.00', 'band' => null]);
        $documents = $page->json('data.stage.statements.documents');
    } else {
        $page->assertInertia(fn (Assert $inertia): Assert => $inertia->where('audit.month', '2026-08')->where('can_continue', true)
            ->where('stage.statements.inflow.amount', '4000000')->where('stage.statements.net.amount', '3000000')
            ->where('stage.statements.cover', ['value' => '4.00', 'band' => null]));
        $documents = $page->viewData('page')['props']['stage']['statements']['documents'];
    }
    expect($documents)->toHaveCount(1);
    $download = $this->get($documents[0]['link']['url'])->assertOk()->assertHeaderContains('Cache-Control', 'no-store')
        ->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Content-Security-Policy', "default-src 'none'; sandbox");
    expect($download->headers->get('Content-Disposition'))->not->toContain('synthetic-quote-history')
        ->and($download->getContent())->toContain('2026-08-01,sales,4000000');
    $foreign = procedureHttpFixture();
    $foreignDocument = app(GetAuditStatements::class)->handle($foreign['audit']['partners'][0]['user']->id, 1, $foreign['assignment']->id)['evidence']['documents'][0]['id'];
    $this->getJson(route($prefix.'reports.statements.show', ['report' => $id, 'document' => $foreignDocument]))
        ->assertNotFound()->assertJsonPath('code', 'STATEMENT_NOT_FOUND');
    RoleMembership::query()->where('party_id', $fixture['audit']['partners'][0]['party']->id)->update(['status' => 'revoked']);
    $this->get($documents[0]['link']['url'])->assertForbidden();
})->with([false, true]);

/** @return array<string, mixed> */
function completeProcedureFacts(): array
{
    $facts = AuditSourceFactsFixture::facts();
    $facts['photos']['required'][1]['captured_at'] = $facts['check_in']['at'];
    $facts['photos']['required'][1]['position'] = $facts['check_in']['position'];

    return $facts;
}

it('reopens a changed source through the previous-step link and explicitly invalidates later observations', function (): void {
    $fixture = procedureHttpFixture();
    $facts = completeProcedureFacts();
    AuditSourceFactsFixture::record($fixture['audit']['staff'], $fixture['assignment']->refresh(), facts: $facts);
    $user = $fixture['audit']['partners'][0]['user'];
    Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    $id = $this->postJson(route('api.v1.auditor.reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))->assertOk()->json('data.audit_id');
    $revision = 1;
    foreach (['review' => [], 'check_in' => [], 'photos' => ['titles' => ['extra-1' => 'Rear stock room']],
        'ledger' => ['observed_stock' => '37000000', 'reconciled' => true], 'seal' => ['note' => 'Prior factual explanation.']] as $step => $fields) {
        $this->postJson(route('api.v1.auditor.reports.save', ['report' => $id]), [...$fields, 'audit_id' => $id, 'step' => $step,
            'identity_context_revision' => 1, 'expected_revision' => $revision++, 'request_id' => (string) Str::uuid()])->assertOk();
    }
    $facts['declared_stock_rwf'] = '39000000';
    $source = AuditSourceFactsFixture::record($fixture['audit']['staff'], $fixture['assignment'], revision: 1, facts: $facts);
    expect($source['status'])->toBe('completed');
    $path = route('api.v1.auditor.reports.show', ['report' => $id]);
    $this->getJson($path)->assertOk()->assertJsonPath('data.can_continue', false);
    $this->getJson($path.'?step=check_in')->assertOk()->assertJsonPath('data.can_continue', true)
        ->assertJsonPath('data.stage.check_in.evidence.sha256', $source['data']['audit_source']['sha256']);
    expect(AuditReport::query()->whereKey($id)->firstOrFail()->step)->toBe('seal');
    $this->assertDatabaseCount('audit_report_versions', $revision);
    $this->postJson(route('api.v1.auditor.reports.save', ['report' => $id]), ['audit_id' => $id, 'step' => 'check_in',
        'identity_context_revision' => 1, 'expected_revision' => $revision, 'request_id' => (string) Str::uuid()])->assertOk();
    $report = AuditReport::query()->whereKey($id)->firstOrFail();
    expect($report->step)->toBe('photos')->and($report->draft['note'])->toBe('')
        ->and($report->draft['completed_steps'])->toBe(['review', 'check_in'])
        ->and($report->draft['fields'])->not->toHaveKeys(['photos', 'ledger']);
});

it('completes the isolated factual procedure and previews only the persisted report on both transports', function (string $kind, bool $api): void {
    $fixture = procedureHttpFixture($kind);
    $user = $fixture['audit']['partners'][0]['user'];
    $assignmentRevision = $fixture['assignment']->refresh()->revision;
    expect(AuditSourceFactsFixture::record($fixture['audit']['staff'], $fixture['assignment'], facts: completeProcedureFacts())['code'])->toBe('AUDIT_SOURCE_FACTS_RECORDED');
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    if ($api) {
        Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($user);
    }
    $id = $this->postJson(route($prefix.'reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))->assertOk()->json('data.audit_id');
    $path = route($prefix.'reports.show', ['report' => $id]);
    $page = function (array $query = []) use ($path, $api): array {
        $response = $this->get($path.($query === [] ? '' : '?'.http_build_query($query)))->assertOk();

        return $api ? $response->json('data') : $response->viewData('page')['props'];
    };
    $revision = 1;
    $save = function (string $step, array $fields) use ($id, $prefix, &$revision): void {
        $this->postJson(route($prefix.'reports.save', ['report' => $id]), [...$fields, 'audit_id' => $id, 'step' => $step,
            'identity_context_revision' => 1, 'expected_revision' => $revision, 'request_id' => (string) Str::uuid()])
            ->assertOk()->assertJsonPath('code', 'AUDIT_STEP_SAVED')->assertJsonPath('revision', ++$revision);
    };
    $steps = $kind === 'flash'
        ? ['review' => [], 'check_in' => [], 'photos' => ['titles' => ['extra-1' => 'Rear stock room']],
            'ledger' => ['observed_stock' => '37000000', 'reconciled' => true]]
        : ['statements' => [], 'count' => ['cash' => '108000001', 'stock_units' => '189', 'operational_status' => 'restricted',
            'financial_proofs' => ['bank', 'momo'], 'inventory_proofs' => ['photo']], 'photos' => ['titles' => ['extra-1' => 'Rear stock room']]];
    foreach ($steps as $step => $fields) {
        $current = $page();
        expect($current['stage']['step'])->toBe($step)->and($current['can_continue'])->toBeTrue();
        if ($step === 'check_in') {
            expect($current['stage']['check_in']['evidence']['source'])->toBe('isolated_synthetic')
                ->and($current['stage']['check_in']['evidence']['device_attestation'])->toBe('unavailable');
        } elseif ($step === 'photos') {
            expect($current['stage']['required'])->toBe(2)->and($current['stage']['slots'])->toHaveCount(3)
                ->and($current['stage']['slots'][2]['thumbnail_url'])->toBeNull()->and($current['stage']['package']['handoff'])->toBeNull();
        } elseif ($step === 'ledger') {
            expect($current['stage']['reported_stock']['amount'])->toBe('38000000');
        } elseif ($step === 'count') {
            expect($current['stage']['cash']['statement']['amount'])->toBe('108000000')
                ->and($current['stage']['sector']['definition'])->toBe('Sealed crates')
                ->and($current['stage']['account_ref'])->toBe('Synthetic bank ending 4417');
        }
        $save($step, $fields);
    }
    $seal = $page();
    expect($seal['stage']['step'])->toBe('seal')->and($seal['can_continue'])->toBeFalse()->and($seal['stage']['note']['required'])->toBeTrue()
        ->and($seal['stage']['findings'])->not->toBeEmpty()->and($seal['actions']['seal'])->toBeNull();
    /* Figures read with thousands separators: no run of four digits is shown ungrouped. */
    foreach ($seal['stage']['findings'] as $finding) {
        expect($finding['body'])->not->toMatch('/\d{4}/');
    }
    $digest = $seal['stage']['digest'];
    expect($page(['observed_stock' => '0', 'cash' => '0', 'stock_units' => '0'])['stage']['digest'])->toBe($digest);
    $save('seal', ['note' => 'Factual differences observed and recorded.']);
    $ready = $page();
    expect($ready['can_continue'])->toBeTrue()->and($ready['stage']['digest'])->not->toBe($digest)
        ->and($ready['stage']['note']['value'])->toBe('Factual differences observed and recorded.')
        ->and($fixture['assignment']->refresh()->revision)->toBe($assignmentRevision);
    $projection = app(GetAuditProcedure::class)->handle($user->id, 1, $id);
    expect($projection['seal']['payload']['source_provenance']['kind'])->toBe('isolated_synthetic');
    $this->assertDatabaseCount('audit_report_versions', $revision);
    $withdrawn = app(RecordIsolatedAuditSourceFacts::class)->handle($fixture['audit']['staff']->id, $fixture['assignment']->id,
        $assignmentRevision, 1, null, 'synthetic:withdrawn-fixture', 'Withdraw the synthetic evidence package.', (string) Str::uuid());
    expect($withdrawn['status'])->toBe('completed')->and($page()['can_continue'])->toBeFalse();
    $this->postJson(route($prefix.'reports.save', ['report' => $id]), ['audit_id' => $id, 'step' => 'seal', 'note' => 'Do not advance.',
        'identity_context_revision' => 1, 'expected_revision' => $revision, 'request_id' => (string) Str::uuid()])->assertConflict();
    $this->assertDatabaseCount('audit_report_versions', $revision);
})->with(['flash', 'routine'])->with([false, true]);

it('keeps incomplete or review-required source packages blocked with truthful nullable metadata', function (string $case): void {
    $fixture = procedureHttpFixture();
    $facts = completeProcedureFacts();
    if ($case === 'unknown review') {
        $facts['check_in']['review_required'] = null;
    } elseif ($case === 'review required') {
        $facts['check_in']['review_required'] = true;
    } elseif ($case === 'missing position') {
        $facts['check_in']['position'] = null;
    } elseif ($case === 'missing photo') {
        $facts['photos']['required'][1]['captured_at'] = null;
    } elseif ($case === 'unknown extras') {
        $facts['photos']['extra'] = null;
    } else {
        foreach (range(2, 4) as $number) {
            $facts['photos']['extra'][] = [...$facts['photos']['extra'][0], 'id' => 'extra-'.$number];
        }
    }
    AuditSourceFactsFixture::record($fixture['audit']['staff'], $fixture['assignment']->refresh(), facts: $facts);
    $user = $fixture['audit']['partners'][0]['user'];
    Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    $id = $this->postJson(route('api.v1.auditor.reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))->assertOk()->json('data.audit_id');
    $body = ['audit_id' => $id, 'identity_context_revision' => 1, 'expected_revision' => 1, 'step' => 'review', 'request_id' => (string) Str::uuid()];
    $this->postJson(route('api.v1.auditor.reports.save', ['report' => $id]), $body)->assertOk();
    $photoCase = in_array($case, ['missing photo', 'unknown extras', 'too many photos'], true);
    if ($photoCase) {
        $this->postJson(route('api.v1.auditor.reports.save', ['report' => $id]), [...$body,
            'expected_revision' => 2, 'step' => 'check_in', 'request_id' => (string) Str::uuid()])->assertOk();
    }
    $this->getJson(route('api.v1.auditor.reports.show', ['report' => $id]))->assertOk()->assertJsonPath('data.can_continue', false);
    $this->postJson(route('api.v1.auditor.reports.save', ['report' => $id]), [...$body,
        'expected_revision' => $photoCase ? 3 : 2, 'step' => $photoCase ? 'photos' : 'check_in',
        ...($photoCase ? ['titles' => array_fill_keys(array_column($facts['photos']['extra'] ?? [], 'id'), 'Extra stock photo')] : []),
        'request_id' => (string) Str::uuid()])->assertConflict()->assertJsonPath('code', 'AUDIT_CAPTURE_REQUIRED');
})->with(['unknown review', 'review required', 'missing position', 'missing photo', 'unknown extras', 'too many photos']);

it('withdraws stale monthly facts and journals the refused step after evidence changes', function (): void {
    $fixture = procedureHttpFixture('routine');
    $user = $fixture['audit']['partners'][0]['user'];
    Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    $id = $this->postJson(route('api.v1.auditor.reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))->assertOk()->json('data.audit_id');
    $revision = app(GetAuditStatements::class)->handle($user->id, 1, $fixture['assignment']->id)['evidence']['revision'];
    $ingested = app(IngestStatement::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $revision,
        'additional.csv', "date,reference,amount\n2026-08-01,additional,1\n", (string) Str::uuid());
    expect($ingested['status'])->toBe('completed');
    $this->getJson(route('api.v1.auditor.reports.show', ['report' => $id]))->assertOk()
        ->assertJsonPath('data.stage.statements.status', 'unavailable')->assertJsonPath('data.can_continue', false);
    $this->postJson(route('api.v1.auditor.reports.save', ['report' => $id]), ['audit_id' => $id, 'step' => 'statements',
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => 1])
        ->assertConflict()->assertJsonPath('code', 'AUDIT_VERIFIED_STATEMENTS_REQUIRED');
    $this->assertDatabaseCount('audit_report_versions', 1);
});

it('offers an explicit start command then resumes the same report without mutating a file read', function (bool $api): void {
    $fixture = procedureHttpFixture();
    $user = $fixture['audit']['partners'][0]['user'];
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    if ($api) {
        Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($user);
    }
    $file = route($prefix.'jobs.show', ['assignment' => $fixture['assignment']->id]);
    $page = $this->get($file)->assertOk();
    if ($api) {
        $page->assertJsonPath('data.application.id', $fixture['application']->id)->assertJsonPath('data.links.procedure', null)
            ->assertJsonPath('data.actions.start.method', 'post')->assertJsonFragment(['audit.start']);
    } else {
        $page->assertInertia(fn (Assert $inertia): Assert => $inertia->where('application.id', $fixture['application']->id)
            ->where('links.procedure', null)->where('actions.start.method', 'post')->where('allowed_actions', fn ($actions): bool => in_array('audit.start', $actions->all(), true)));
    }
    $this->assertDatabaseCount('audit_reports', 0);
    $id = $this->postJson(route($prefix.'reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))->assertOk()->json('data.audit_id');
    $next = route($prefix.'reports.show', ['report' => $id], false);
    $page = $this->get($file)->assertOk();
    if ($api) {
        $page->assertJsonPath('data.actions.start', null)->assertJsonPath('data.links.procedure.url', $next);
    } else {
        $page->assertInertia(fn (Assert $inertia): Assert => $inertia->where('actions.start', null)->where('links.procedure.url', $next));
    }
    $this->assertDatabaseCount('audit_reports', 1);
    $this->assertDatabaseCount('audit_report_versions', 1);
})->with([false, true]);

it('withdraws count readiness and explains missing source proof lists', function (string $key): void {
    $fixture = procedureHttpFixture('routine');
    $facts = completeProcedureFacts();
    $facts['proof_ids'][$key] = null;
    AuditSourceFactsFixture::record($fixture['audit']['staff'], $fixture['assignment']->refresh(), facts: $facts);
    $user = $fixture['audit']['partners'][0]['user'];
    Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    $id = $this->postJson(route('api.v1.auditor.reports.start', ['assignment' => $fixture['assignment']->id]), procedureStartPayload($fixture))->assertOk()->json('data.audit_id');
    $this->postJson(route('api.v1.auditor.reports.save', ['report' => $id]), ['audit_id' => $id, 'step' => 'statements',
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => 1])->assertOk();
    $this->getJson(route('api.v1.auditor.reports.show', ['report' => $id]))->assertOk()->assertJsonPath('data.can_continue', false)
        ->assertJsonPath('data.hint', 'The required financial or inventory proofs are unavailable.');
})->with(['financial', 'inventory']);
