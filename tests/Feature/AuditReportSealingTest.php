<?php

declare(strict_types=1);

use App\Application\Auditor\AmendAuditReport;
use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Auditor\FindAuditReportOperation;
use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\RecordIsolatedAuditSourceFacts;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditReportSeal;
use App\Models\AuditReportVersion;
use App\Models\AuditSigningKey;
use App\Models\AuditSigningKeyRevocation;
use App\Models\AuditStepUpProof;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditLedgerFixture;
use Tests\Support\AuditSealingFixture as Fixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

it('retains a real ES256 signature and consumes the bound proof once while preserving retry identity', function (): void {
    $fixture = Fixture::ready();
    $operations = CommandOperation::query()->count();
    $proof = Fixture::proof($fixture);
    expect(CommandOperation::query()->count())->toBe($operations)->and($proof['proof'])->toHaveLength(64);
    $record = AuditStepUpProof::query()->firstOrFail();
    expect($record->proof_sha256)->toBe(hash('sha256', $proof['proof']))->and($record->toArray())->not->toHaveKeys(['proof_sha256', 'credential_binding']);
    $request = (string) Str::uuid();
    $receipt = Fixture::seal($fixture, $proof['proof'], $request);
    expect($receipt['code'])->toBe('AUDIT_SEALED')->and($receipt['revision'])->toBe(6)
        ->and($record->refresh()->consumed_at)->not->toBeNull()
        ->and(Fixture::seal($fixture, $proof['proof'], $request))->toBe($receipt)
        ->and(app(FindAuditReportOperation::class)->handle($fixture['user']->id, 1, 'audit.seal', $request))->toBe($receipt);
    $seal = AuditReportSeal::query()->firstOrFail();
    expect(app(AuditReportCryptography::class)->verify($seal->audit_signing_key_id, $seal->jws, $seal->payload))->toBeTrue()
        ->and($seal->digest)->toBe(hash('sha256', app(CanonicalJson::class)->encode($seal->payload['report'])))
        ->and($seal->toArray())->not->toHaveKeys(['payload', 'jws'])
        ->and($seal->getRawOriginal('jws'))->not->toBe($seal->jws)
        ->and($fixture['report']->fresh()->status)->toBe('sealed');
    DB::statement('SET CONSTRAINTS audit_seal_version_required IMMEDIATE');
    $this->assertDatabaseCount('audit_report_seals', 1);
    expect(fn () => Fixture::seal($fixture, str_repeat('a', 64), $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('refuses changed report echoes without consuming a proof', function (string $field, mixed $value): void {
    $fixture = Fixture::ready();
    $proof = Fixture::proof($fixture);
    $result = Fixture::seal($fixture, $proof['proof'], fields: [...$fixture['fields'], $field => $value]);
    expect($result['code'])->toBe('DIGEST_STALE')->and(AuditStepUpProof::query()->firstOrFail()->consumed_at)->toBeNull();
    $this->assertDatabaseCount('audit_report_seals', 0);
})->with([
    ['digest', str_repeat('0', 64)], ['procedure_version', 'different'], ['findings_version', 'different'],
    ['evidence_version', 'different'], ['evidence_ids', []], ['note', 'Changed after confirmation'], ['note', "Changed\u{2028}text"],
]);

it('refuses unknown expired and credential-changed proofs without changing the report', function (string $case): void {
    $fixture = Fixture::ready();
    $proof = Fixture::proof($fixture)['proof'];
    if ($case === 'unknown') {
        $proof = str_repeat('x', 64);
    } elseif ($case === 'expired') {
        $this->travel(5)->minutes();
    } else {
        $fixture['user']->forceFill(['password' => 'changed synthetic password'])->save();
    }
    $result = Fixture::seal($fixture, $proof);
    expect($result['code'])->toBe($case === 'expired' ? 'STEP_UP_EXPIRED' : 'STEP_UP_INVALID')
        ->and($fixture['report']->fresh()->status)->toBe('draft');
    $this->assertDatabaseCount('audit_report_seals', 0);
})->with(['unknown', 'expired', 'credentials']);

it('rolls back the seal and proof consumption if the immutable report version cannot be appended', function (): void {
    $fixture = Fixture::ready();
    $proof = Fixture::proof($fixture);
    Event::listen('eloquent.creating: '.AuditReportVersion::class, function (): never {
        throw new RuntimeException('synthetic history failure');
    });
    try {
        expect(fn () => Fixture::seal($fixture, $proof['proof']))->toThrow(RuntimeException::class, 'synthetic history failure');
    } finally {
        Event::forget('eloquent.creating: '.AuditReportVersion::class);
    }
    expect(AuditStepUpProof::query()->firstOrFail()->consumed_at)->toBeNull()->and($fixture['report']->fresh()->status)->toBe('draft');
    $this->assertDatabaseCount('audit_report_seals', 0);
    expect(Fixture::seal($fixture, $proof['proof'])['code'])->toBe('AUDIT_SEALED');
});

it('serves separate private step-up and journalled seal commands on both transports', function (bool $api): void {
    $fixture = Fixture::ready();
    $this->actingAs($fixture['user']);
    if ($api) {
        Sanctum::actingAs($fixture['user'], ['auditor:command', 'auditor:read']);
    }
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    $show = route($prefix.'reports.show', ['report' => $fixture['report']->id]);
    $read = function () use ($show, $api): array {
        $response = $this->get($show)->assertOk();

        return $api ? $response->json('data') : $response->viewData('page')['props'];
    };
    expect($read()['allowed_actions'])->toContain('audit.seal');
    $body = ['audit_id' => $fixture['report']->id, 'expected_revision' => 5, 'identity_context_revision' => 1, 'request_id' => (string) Str::uuid()];
    $proof = $this->postJson(route($prefix.'reports.step-up', ['report' => $fixture['report']->id]),
        [...$body, 'digest' => $fixture['fields']['digest'], 'code' => $fixture['code']])->assertOk()->assertJsonStructure(['proof', 'expires_at'])->json('proof');
    $response = $this->postJson(route($prefix.'reports.seal', ['report' => $fixture['report']->id]),
        [...$body, ...$fixture['fields'], 'step_up' => ['proof' => $proof]])->assertOk()->assertJsonPath('code', 'AUDIT_SEALED');
    $this->getJson(route($prefix.'reports.operations.show', ['request_id' => $body['request_id'], 'command' => 'audit.seal']))
        ->assertOk()->assertJsonPath('data.sealed.signature_ref', $response->json('data.sealed.signature_ref'));
    expect($read()['stage']['step'])->toBe('sealed')->and($read()['stage']['seal_status'])->toBe('valid');
    app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id, 6, (string) Str::uuid());
    expect($read()['stage']['amended_by'])->not->toBeNull();
    if ($api) {
        Sanctum::actingAs($fixture['user'], ['auditor:read']);
        expect($read()['allowed_actions'])->toBe([])->and($read()['actions']['seal'])->toBeNull();
        $this->postJson(route($prefix.'reports.step-up', ['report' => $fixture['report']->id]),
            [...$body, 'digest' => $fixture['fields']['digest'], 'code' => $fixture['code']])->assertForbidden();
    }

})->with([false, true]);

it('returns precise wrong-code errors and a shared five-attempt account throttle', function (): void {
    $fixture = Fixture::ready();
    $this->actingAs($fixture['user']);
    $body = ['audit_id' => $fixture['report']->id, 'expected_revision' => 5, 'identity_context_revision' => 1,
        'request_id' => (string) Str::uuid(), 'digest' => $fixture['fields']['digest'], 'code' => $fixture['code'] === '000000' ? '111111' : '000000'];
    for ($i = 0; $i < 5; $i++) {
        $this->postJson(route('auditor.reports.step-up', ['report' => $fixture['report']->id]), $body)->assertUnprocessable()->assertJsonValidationErrors('code');
    }
    Sanctum::actingAs($fixture['user'], ['auditor:command']);
    $this->postJson(route('api.v1.auditor.reports.step-up', ['report' => $fixture['report']->id]), $body)->assertTooManyRequests()->assertHeader('Retry-After');
    $this->assertDatabaseCount('audit_step_up_proofs', 0);
});

it('retains key rotation history and immediately invalidates revoked seals', function (): void {
    $fixture = Fixture::ready();
    $proof = Fixture::proof($fixture);
    Fixture::seal($fixture, $proof['proof']);
    $seal = AuditReportSeal::query()->firstOrFail();
    $crypto = app(AuditReportCryptography::class);
    $this->travel(90)->days();
    expect($crypto->available())->toBeFalse()->and($crypto->verify($seal->audit_signing_key_id, $seal->jws, $seal->payload))->toBeTrue();
    AuditSigningKey::factory()->create();
    expect($crypto->available())->toBeTrue();
    AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $seal->audit_signing_key_id]);
    expect($crypto->verify($seal->audit_signing_key_id, $seal->jws, $seal->payload))->toBeFalse();
    foreach (['audit_signing_keys', 'audit_signing_key_revocations', 'audit_report_seals'] as $table) {
        expect(fn () => DB::transaction(fn () => DB::table($table)->delete()))->toThrow(QueryException::class);
    }
    $migration = require database_path('migrations/2026_09_25_134827_create_audit_report_signing_tables.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Existing audit signing history requires a forward migration.');
});

it('records malformed step inputs without losing existing receipt identities', function (mixed $amount): void {
    ['user' => $user, 'report' => $report] = AuditLedgerFixture::ready();
    $request = (string) Str::uuid();
    $input = ['observed_stock' => $amount, 'reconciled' => true];
    $result = app(SaveAuditReportStep::class)->handle($user->id, 1, $report->id, 4, 'ledger', $input, $request);
    expect($result['code'])->toBe('AUDIT_STEP_INPUT_INVALID')->and($result['field_errors'])->toHaveKey('observed_stock')
        ->and(app(FindAuditReportOperation::class)->handle($user->id, 1, 'audit.save_step', $request))->toBe($result);
    expect(fn () => app(SaveAuditReportStep::class)->handle($user->id, 1, $report->id, 4, 'ledger', [...$input, 'observed_stock' => 1.25], $request))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
})->with([3.14, 9007199254740992, "38\u{2028}000"]);

it('refuses step-up for incomplete changed or stale drafts and for an unavailable signing key', function (string $case): void {
    $fixture = Fixture::ready();
    if ($case === 'key') {
        AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $fixture['key']->id]);
    } elseif ($case === 'step') {
        app(SaveAuditReportStep::class)->handle($fixture['user']->id, 1, $fixture['report']->id, $fixture['report']->revision, 'review', [], (string) Str::uuid());
        $fixture['report']->refresh();
    } elseif ($case === 'revision') {
        $fixture['report']->revision--;
    } else {
        $fixture['fields']['digest'] = str_repeat('0', 64);
    }
    expect(fn () => Fixture::proof($fixture))->toThrow(CommandRejection::class, match ($case) {
        'key' => 'SEAL_KEY_UNAVAILABLE', 'step' => 'AUDIT_PROCEDURE_INCOMPLETE', 'revision' => 'VERSION_CONFLICT', default => 'DIGEST_STALE',
    });
    $this->assertDatabaseCount('audit_step_up_proofs', 0);
})->with(['key', 'step', 'revision', 'digest']);

it('refuses confirmation after a source is withdrawn and records missing-note seal errors on both transports', function (bool $api): void {
    $fixture = Fixture::ready(findings: true);
    $user = $fixture['user'];
    $report = $fixture['report'];
    app(SaveAuditReportStep::class)->handle($user->id, 1, $report->id, $report->revision, 'seal', ['note' => ''], (string) Str::uuid());
    $report->refresh();
    $preview = app(GetAuditProcedure::class)->handle($user->id, 1, $report->id)['seal'];
    $this->actingAs($user);
    if ($api) {
        Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    }
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    $request = (string) Str::uuid();
    $body = [...$fixture['fields'], 'digest' => $preview['digest'], 'note' => '', 'audit_id' => $report->id,
        'expected_revision' => $report->revision, 'identity_context_revision' => 1, 'request_id' => $request, 'step_up' => ['proof' => str_repeat('p', 64)]];
    $receipt = $this->postJson(route($prefix.'reports.seal', ['report' => $report->id]), $body)
        ->assertUnprocessable()->assertJsonPath('code', 'AUDIT_NOTE_REQUIRED')->assertJsonValidationErrors('note')->json();
    $this->travel(30)->seconds();
    $recovered = $this->getJson(route($prefix.'reports.operations.show', ['request_id' => $request, 'command' => 'audit.seal']))
        ->assertUnprocessable()->assertJsonPath('operation_id', $receipt['operation_id'])->assertJsonValidationErrors('note')->json();
    expect($recovered['recorded_at'])->toBe($receipt['recorded_at'])->and($recovered['server_time'])->not->toBe($receipt['server_time']);
    app(RecordIsolatedAuditSourceFacts::class)->handle($fixture['audit']['staff']->id, $fixture['assignment']->id,
        $fixture['assignment']->refresh()->revision, 1, null, 'synthetic:withdrawn-before-seal', 'Withdraw the capture evidence.', (string) Str::uuid());
    $fixture['fields']['digest'] = $preview['digest'];
    expect(fn () => Fixture::proof($fixture))->toThrow(CommandRejection::class, 'AUDIT_CAPTURE_REQUIRED');
})->with([false, true]);
