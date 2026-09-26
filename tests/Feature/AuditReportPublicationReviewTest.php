<?php

declare(strict_types=1);

use App\Application\Auditor\AmendAuditReport;
use App\Application\Auditor\FindAuditCosignOperation;
use App\Application\Auditor\GetBusinessAuditReport;
use App\Application\Auditor\SaveAuditReportStep;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditReport;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use App\Models\AuditReportSignature;
use App\Models\AuditStepUpProof;
use App\Models\RoleMembership;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditSealingFixture as Fixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

it('withdraws an amended parent without changing its retained signature and records a recoverable refusal', function (): void {
    $fixture = Fixture::ready(2);
    Fixture::seal($fixture);
    $signedRequest = (string) Str::uuid();
    $signed = Fixture::cosign($fixture, overrides: ['requestId' => $signedRequest]);
    $signature = AuditReportSignature::query()->firstOrFail();
    $before = $signature->getAttributes();
    $amended = app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id,
        $fixture['report']->revision + 1, (string) Str::uuid());
    expect($amended['code'])->toBe('AUDIT_AMENDMENT_CREATED');
    $reader = $fixture['audit']['authority']['users'][1];
    $page = app(GetBusinessAuditReport::class)->handle($reader->id, 1, $fixture['audit']['business'], $fixture['report']->id);
    expect($page['can_cosign'])->toBeFalse()->and($page['cosign']['state'])->toBe('unavailable');
    $request = (string) Str::uuid();
    $body = ['identity_context_revision' => 1, 'expected_revision' => 2, 'report_revision' => $fixture['report']->revision + 1,
        'mandate_version' => 1, 'digest' => $fixture['fields']['digest'], 'accepted' => true, 'note' => '', 'request_id' => $request];
    $route = route('business.audit-reports.cosign', ['business' => $fixture['audit']['business'], 'report' => $fixture['report']->id]);
    $receipt = $this->actingAs($reader)->postJson($route, $body)->assertConflict()->assertJsonPath('code', 'AUDIT_REPORT_AMENDED')->json();
    $this->postJson($route, $body)->assertConflict()->assertJsonPath('operation_id', $receipt['operation_id']);
    expect(app(FindAuditCosignOperation::class)->handle($reader->id, 1, $request)['operation_id'])->toBe($receipt['operation_id'])
        ->and($signature->refresh()->getAttributes())->toBe($before)
        ->and(app(FindAuditCosignOperation::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $signedRequest)['operation_id'])->toBe($signed['operation_id']);
    $publication = AuditReportPublication::query()->firstOrFail();
    expect($publication->status)->toBe('pending')->and($publication->revision)->toBe(2);
    expect(fn () => DB::transaction(fn () => AuditReportSignature::factory()->forPublication($publication, $reader)->create()))
        ->toThrow(QueryException::class, 'An amended audit report cannot receive signatures or publish');
    expect(fn () => DB::transaction(fn () => $publication->forceFill(['status' => 'published', 'published_at' => now(), 'revision' => 3])->save()))
        ->toThrow(QueryException::class, 'An amended audit report cannot receive signatures or publish');
});

it('publishes only the amendment while retaining an already published parent as history', function (bool $publishParent): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    if ($publishParent) {
        Fixture::cosign($fixture);
    }
    $parent = AuditReportPublication::query()->firstOrFail();
    $before = $parent->getAttributes();
    $receipt = app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id,
        $fixture['report']->revision + 1, (string) Str::uuid());
    $child = AuditReport::query()->whereKey($receipt['data']['audit_id'])->firstOrFail();
    foreach (['review' => [], 'check_in' => [], 'photos' => ['titles' => ['extra-1' => 'Stock room']],
        'ledger' => ['observed_stock' => '38000000', 'reconciled' => true]] as $step => $fields) {
        expect(app(SaveAuditReportStep::class)->handle($fixture['user']->id, 1, $child->id, $child->refresh()->revision, $step, $fields, (string) Str::uuid())['code'])
            ->toBe('AUDIT_STEP_SAVED');
    }
    $childFixture = Fixture::prepare([...$fixture, 'report' => $child]);
    $childFixture['user']->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXQ')])->save();
    $childFixture['code'] = (new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXQ');
    expect(Fixture::seal($childFixture)['code'])->toBe('AUDIT_SEALED')->and(Fixture::cosign($childFixture)['code'])->toBe('REPORT_PUBLISHED')
        ->and($parent->refresh()->getAttributes())->toBe($before)
        ->and(AuditReportPublication::query()->where('status', 'published')->count())->toBe($publishParent ? 2 : 1);
    foreach ([false, true] as $api) {
        $route = $api ? 'api.v1.audit.seals.verify' : 'audit.seals.verify';
        foreach ([[$fixture['report']->id, null, $child->id], [$child->id, $fixture['report']->id, null]] as [$id, $amends, $amendedBy]) {
            $response = $this->get(route($route, ['report' => $id]))->assertOk();
            if ($api) {
                $response->assertJsonPath('data.amends_id', $amends)->assertJsonPath('data.amended_by', $amendedBy);
            } else {
                $response->assertInertia(fn (Assert $page): Assert => $page->component('audit/verify-seal')->where('seal.amends_id', $amends)->where('seal.amended_by', $amendedBy));
            }
        }
    }
})->with([false, true]);

it('does not mark a published monthly report overdue on a later cold read', function (): void {
    $this->travelTo(now('UTC')->startOfMonth()->addDays(4)->setTime(10, 0));
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    expect(Fixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');
    $this->travel(3)->days();
    $page = app(GetBusinessAuditReport::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['report']->id);
    expect($page['cosign']['state'])->toBe('signed')->and($page['cosign']['overdue'])->toBeFalse()->and($page['report']['published_at'])->not->toBeNull();
});

it('renders identity refusals as pages for the new web reads and JSON for the API', function (string $pageRoute, bool $api): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $business = $pageRoute === 'business.audit-reports.show';
    $user = $business ? $fixture['audit']['authority']['users'][0] : $fixture['user'];
    RoleMembership::query()->where('party_id', $user->party_id)->update(['status' => 'revoked']);
    $this->actingAs($user);
    if ($api) {
        Sanctum::actingAs($user, [$business ? 'business:read' : 'auditor:read']);
    }
    $response = $this->get(route(($api ? 'api.v1.' : '').$pageRoute,
        ['business' => $fixture['audit']['business'], 'report' => $fixture['report']->id]))->assertForbidden();
    if ($api) {
        $response->assertHeader('Content-Type', 'application/json')->assertJsonStructure(['code', 'message']);
    } else {
        $response->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied'));
    }
})->with(['business.audit-reports.show', 'auditor.reports.show', 'auditor.engagement.show'])->with([false, true]);

it('uses one accepted instant for the publication window and all retained signature times', function (bool $closed): void {
    $this->travelTo(now('UTC')->startOfMonth()->addDays(6)->setTime(21, 59, 59, 999999));
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $accepted = now('UTC')->toImmutable();
    if ($closed) {
        $this->travelTo($accepted->addMicrosecond());
    }
    Event::listen('eloquent.creating: '.AuditReportSignature::class, function () use ($accepted): void {
        $this->travelTo($accepted->addMicrosecond());
    });
    try {
        $body = ['identity_context_revision' => 1, 'expected_revision' => 1, 'report_revision' => $fixture['report']->revision + 1,
            'mandate_version' => 1, 'digest' => $fixture['fields']['digest'], 'accepted' => true, 'note' => '', 'request_id' => (string) Str::uuid()];
        $response = $this->actingAs($fixture['audit']['authority']['users'][0])->postJson(route('business.audit-reports.cosign',
            ['business' => $fixture['audit']['business'], 'report' => $fixture['report']->id]), $body);
        if ($closed) {
            $response->assertConflict()->assertJsonPath('code', 'REPORT_WINDOW_CLOSED');
            $this->assertDatabaseCount('audit_report_signatures', 0);
        } else {
            $response->assertOk()->assertJsonPath('code', 'REPORT_PUBLISHED');
            $signature = AuditReportSignature::query()->firstOrFail();
            $publication = AuditReportPublication::query()->firstOrFail();
            expect($signature->payload['signed_at'])->toBe($accepted->format('Y-m-d\TH:i:s\Z'))
                ->and($signature->created_at->format('Y-m-d\TH:i:s\Z'))->toBe($signature->payload['signed_at'])
                ->and($publication->published_at->format('Y-m-d\TH:i:s\Z'))->toBe($signature->payload['signed_at']);
        }
    } finally {
        Event::forget('eloquent.creating: '.AuditReportSignature::class);
    }
})->with([false, true]);

it('retains the purpose and exact consumed proof authorizing each seal', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $proof = AuditStepUpProof::query()->firstOrFail();
    $seal = AuditReportSeal::query()->firstOrFail();
    expect($proof->purpose)->toBe('audit.seal')->and($proof->consumed_at)->not->toBeNull()->and($seal->step_up_proof_id)->toBe($proof->id);
    expect(fn () => DB::transaction(fn () => $proof->delete()))->toThrow(QueryException::class, 'Audit step-up purpose and history must be retained')
        ->and(fn () => DB::transaction(fn () => $proof->forceFill(['purpose' => 'another.command'])->save()))->toThrow(QueryException::class);
    $migration = require database_path('migrations/2026_09_25_154051_enforce_audit_seal_proof_and_publication_lineage.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Existing audit proof and publication history requires a forward migration.');
});

it('refuses an outstanding proof after the authenticator secret changes', function (): void {
    $fixture = Fixture::ready();
    $proof = Fixture::proof($fixture)['proof'];
    $fixture['user']->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXQ')])->save();
    expect(Fixture::seal($fixture, $proof)['code'])->toBe('STEP_UP_INVALID');
    expect(AuditStepUpProof::query()->firstOrFail()->consumed_at)->toBeNull();
});

it('does not reveal draft or unknown reports through either public transport', function (bool $api): void {
    $fixture = Fixture::ready();
    foreach ([$fixture['report']->id, (string) Str::ulid()] as $id) {
        $response = $this->get(route($api ? 'api.v1.audit.seals.verify' : 'audit.seals.verify', ['report' => $id]))->assertNotFound();
        if ($api) {
            $response->assertJsonPath('code', 'AUDIT_REPORT_NOT_FOUND');
        } else {
            /* A logged-out visitor on the public check sees not-found copy, never account-access copy. */
            $response->assertInertia(fn (Assert $page): Assert => $page->component('errors/not-found')
                ->where('code', 'AUDIT_REPORT_NOT_FOUND')->where('auth.user', null));
        }
    }
})->with([false, true]);

it('refuses a seal referencing an absent or unconsumed proof and rolls back consumption', function (bool $missing): void {
    $fixture = Fixture::ready();
    $proof = Fixture::proof($fixture)['proof'];
    $unconsumed = AuditStepUpProof::factory()->forReport($fixture['report'], $fixture['user'])->create(['digest' => $fixture['fields']['digest']]);
    Event::listen('eloquent.creating: '.AuditReportSeal::class, function (AuditReportSeal $seal) use ($missing, $unconsumed): void {
        $seal->forceFill(['step_up_proof_id' => $missing ? (string) Str::ulid() : $unconsumed->id]);
    });
    try {
        expect(fn () => Fixture::seal($fixture, $proof))->toThrow(QueryException::class, 'Audit seal requires its consumed purpose-bound proof');
    } finally {
        Event::forget('eloquent.creating: '.AuditReportSeal::class);
    }
    expect(AuditStepUpProof::query()->whereNotNull('consumed_at')->count())->toBe(0)->and(AuditReportSeal::query()->count())->toBe(0);
})->with([false, true]);

it('backfills only an unambiguous consumed proof without rewriting signed history', function (string $legacyProof): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $seal = AuditReportSeal::query()->firstOrFail();
    $proof = AuditStepUpProof::query()->firstOrFail();
    $original = $seal->getAttributes();
    unset($original['step_up_proof_id']);
    DB::unprepared(<<<'SQL'
        DROP TRIGGER audit_publication_lineage ON audit_report_publications;
        DROP TRIGGER audit_signature_00_lineage ON audit_report_signatures;
        DROP TRIGGER audit_amendment_publication_lock ON audit_reports;
        DROP TRIGGER audit_seal_proof_required ON audit_report_seals;
        DROP TRIGGER audit_step_up_retained ON audit_step_up_proofs;
        ALTER TABLE audit_report_seals DROP COLUMN step_up_proof_id CASCADE;
        ALTER TABLE audit_step_up_proofs DROP COLUMN purpose CASCADE;
        SQL);
    if ($legacyProof === 'missing') {
        $proof->delete();
    } elseif ($legacyProof === 'ambiguous') {
        $duplicate = $proof->getAttributes();
        unset($duplicate['purpose']);
        DB::table('audit_step_up_proofs')->insert([...$duplicate, 'id' => (string) Str::ulid(), 'proof_sha256' => hash('sha256', Str::random(64))]);
    }
    $migration = require database_path('migrations/2026_09_25_154051_enforce_audit_seal_proof_and_publication_lineage.php');
    if ($legacyProof === 'valid') {
        $migration->up();
        expect($seal->refresh()->step_up_proof_id)->toBe($proof->id)->and($proof->refresh()->purpose)->toBe('audit.seal');
    } else {
        expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'Existing audit seals require one unambiguous consumed proof before protection.');
    }
    $retained = $seal->fresh()->getAttributes();
    unset($retained['step_up_proof_id']);
    expect($retained)->toBe($original);
})->with(['valid', 'missing', 'ambiguous']);

it('withdraws outstanding seal proofs and pending publication when the Auditor declares a conflict', function (bool $sealed): void {
    $fixture = Fixture::ready();
    $proof = Fixture::proof($fixture)['proof'];
    if ($sealed) {
        Fixture::seal($fixture, $proof);
    }
    expect(AuditAssignmentFixture::respond($fixture['user'], $fixture['assignment']->refresh(),
        'conflict', 'New financial interest.', 'financial_interest')['code'])->toBe('CONFLICT_RECORDED');
    if ($sealed) {
        $page = app(GetBusinessAuditReport::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['report']->id);
        expect($page['can_cosign'])->toBeFalse()->and($page['cosign']['state'])->toBe('unavailable')
            ->and(Fixture::cosign($fixture)['code'])->toBe('AUDIT_PUBLICATION_UNAVAILABLE');
    } else {
        expect(fn () => Fixture::seal($fixture, $proof))->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND')
            ->and(AuditStepUpProof::query()->firstOrFail()->consumed_at)->toBeNull();
    }
})->with([false, true]);

it('does not expose an Auditor draft as a Business publication', function (): void {
    $fixture = Fixture::ready();
    expect(fn () => app(GetBusinessAuditReport::class)->handle($fixture['audit']['authority']['users'][0]->id, 1,
        $fixture['audit']['business'], $fixture['report']->id))->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND');
});
