<?php

declare(strict_types=1);

use App\Application\Auditor\FindAuditCosignOperation;
use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\GetBusinessAuditReport;
use App\Application\Auditor\VerifyAuditReportSeal;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Infrastructure\Auditor\EloquentAuditReportPublicationStore;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use App\Models\AuditReportSignature;
use App\Models\AuditSigningKeyRevocation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSealingFixture as Fixture;
use Tests\Support\AuditSourceFactsFixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

it('requires every independently attributed Party signature before publication and retains original retries', function (): void {
    $fixture = Fixture::ready(2);
    expect(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
    $request = (string) Str::uuid();
    $first = Fixture::cosign($fixture, overrides: ['requestId' => $request, 'note' => "Reviewed\r\nagainst originals."]);
    expect($first['code'])->toBe('REPORT_COSIGNATURE_RECORDED')->and($first['revision'])->toBe(2);
    $page = app(GetBusinessAuditReport::class)->handle($fixture['audit']['authority']['users'][1]->id, 1, $fixture['audit']['business'], $fixture['report']->id);
    expect($page['cosign']['state'])->toBe('partly_signed')->and($page['cosign']['signed_count'])->toBe(1)
        ->and($page['cosign']['required_signatures'])->toBe(2)->and($page['can_cosign'])->toBeTrue()->and($page['report']['published_at'])->toBeNull();
    $last = Fixture::cosign($fixture, 1, 2);
    expect($last['code'])->toBe('REPORT_PUBLISHED')->and($last['revision'])->toBe(3)
        ->and(Fixture::cosign($fixture, overrides: ['requestId' => $request, 'note' => "Reviewed\r\nagainst originals."]))->toBe($first)
        ->and(app(FindAuditCosignOperation::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $request))->toBe($first);
    $signature = AuditReportSignature::query()->orderBy('id')->firstOrFail();
    expect($signature->payload['note'])->toBe("Reviewed\nagainst originals.")
        ->and($signature->getRawOriginal('payload'))->not->toBe(json_encode($signature->payload));
    $sealed = app(GetAuditProcedure::class)->handle($fixture['user']->id, 1, $fixture['report']->id)['sealed'];
    expect($sealed['cosign']['state'])->toBe('signed')->and($sealed['cosign']['due_on'])->toBeNull()->and($sealed['published_at'])->not->toBeNull();
    DB::statement('SET CONSTRAINTS audit_publication_signatures_required IMMEDIATE');
    $this->assertDatabaseCount('audit_report_signatures', 2);
});

it('records co-sign refusals under their UUID and never advances publication', function (array $overrides, string $code): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $request = (string) Str::uuid();
    $receipt = Fixture::cosign($fixture, overrides: [...$overrides, 'requestId' => $request]);
    expect($receipt['code'])->toBe($code)->and($receipt['status'])->toBe('rejected')
        ->and(app(FindAuditCosignOperation::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $request))->toBe($receipt)
        ->and(AuditReportPublication::query()->firstOrFail()->revision)->toBe(1);
    $this->assertDatabaseCount('audit_report_signatures', 0);
})->with([
    [['expectedRevision' => 0], 'VERSION_CONFLICT'], [['reportRevision' => 1], 'DIGEST_STALE'],
    [['digest' => str_repeat('0', 64)], 'DIGEST_STALE'], [['mandateVersion' => 2], 'MANDATE_STALE'],
    [['accepted' => false], 'REPORT_ACCEPTANCE_REQUIRED'], [['note' => str_repeat('é', 101)], 'REPORT_NOTE_INVALID'],
    [['note' => "Review\u{2028}hidden"], 'REPORT_NOTE_INVALID'],
]);

it('withdraws current signing readiness after key revocation while retaining the sealed report', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $fixture['key']->id]);
    $page = app(GetBusinessAuditReport::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['report']->id);
    expect($page['report']['seal']['status'])->toBe('unavailable')->and($page['cosign']['state'])->toBe('unavailable')
        ->and($page['can_cosign'])->toBeFalse()->and(Fixture::cosign($fixture)['code'])->toBe('AUDIT_PUBLICATION_UNAVAILABLE');
    $sealed = app(GetAuditProcedure::class)->handle($fixture['user']->id, 1, $fixture['report']->id)['sealed'];
    expect($sealed['seal_status'])->toBe('unavailable')->and($sealed['digest'])->toBe($fixture['fields']['digest']);
});

it('does not expose signer or Business facts on public verification', function (bool $api): void {
    $fixture = Fixture::ready(findings: true);
    Fixture::seal($fixture);
    $seal = ['report_id' => $fixture['report']->id, 'digest' => $fixture['fields']['digest'],
        'seal_status' => 'valid', 'amends_id' => null, 'amended_by' => null];
    $response = $this->get(route($api ? 'api.v1.audit.seals.verify' : 'audit.seals.verify', ['report' => $fixture['report']->id]))
        ->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    if ($api) {
        $response->assertExactJson(['data' => $seal]);
    } else {
        $response->assertInertia(fn (Assert $page): Assert => $page->component('audit/verify-seal')->where('seal', $seal));
    }
    $this->assertGuest();
    expect(fn () => app(VerifyAuditReportSeal::class)->handle((string) Str::ulid()))->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND');
})->with([false, true]);

it('serves the co-sign contract and one-signature publication through both transports', function (bool $api): void {
    $fixture = Fixture::ready(findings: true);
    Fixture::seal($fixture);
    $user = $fixture['audit']['authority']['users'][0];
    $this->actingAs($user);
    if ($api) {
        Sanctum::actingAs($user, ['business:read', 'business:command']);
    }
    $prefix = $api ? 'api.v1.business.audit-reports.' : 'business.audit-reports.';
    $parameters = ['business' => $fixture['audit']['business'], 'report' => $fixture['report']->id];
    $response = $this->get(route($prefix.'show', $parameters))->assertOk();
    $page = $api ? $response->json('data') : $response->viewData('page')['props'];
    expect($page['contract_version'])->toBe('business-audit-report-v1')->and($page['allowed_actions'])->toBe(['report.cosign'])
        ->and($page['report']['findings'][0])->toHaveKeys(['code', 'title', 'body', 'evidence_ids'])
        ->and($page['report']['findings'][0]['body'])->toBe('Reported: 38,000,000. Observed: 37,000,000. Difference: -1,000,000.')
        ->and($page['report']['auditor_note'])->toBe('Observed difference retained for review.');
    $request = (string) Str::uuid();
    $body = ['identity_context_revision' => 1, 'expected_revision' => 1, 'report_revision' => $fixture['report']->revision + 1,
        'mandate_version' => 1, 'digest' => $fixture['fields']['digest'], 'accepted' => true, 'note' => '', 'request_id' => $request];
    $receipt = $this->postJson(route($prefix.'cosign', $parameters), $body)->assertOk()->assertJsonPath('code', 'REPORT_PUBLISHED')
        ->assertJsonPath('data.next.url', route($prefix.'show', $parameters, false))->json();
    $this->getJson(route($prefix.'operations.show', ['request_id' => $request, 'identity_context_revision' => 1, 'command' => 'report.cosign']))
        ->assertOk()->assertJsonPath('operation_id', $receipt['operation_id'])->assertJsonPath('code', 'REPORT_PUBLISHED');
    if ($api) {
        Sanctum::actingAs($user, ['business:read']);
        $this->getJson(route($prefix.'show', $parameters))->assertOk()->assertJsonPath('data.allowed_actions', [])->assertJsonPath('data.actions.cosign', null);
        $this->postJson(route($prefix.'cosign', $parameters), $body)->assertForbidden();
    }
})->with([false, true]);

it('rolls back a signature and its receipt when publication cannot advance', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    Event::listen('eloquent.updating: '.AuditReportPublication::class, function (): never {
        throw new RuntimeException('synthetic publication failure');
    });
    try {
        expect(fn () => Fixture::cosign($fixture))->toThrow(RuntimeException::class, 'synthetic publication failure');
    } finally {
        Event::forget('eloquent.updating: '.AuditReportPublication::class);
    }
    $this->assertDatabaseCount('audit_report_signatures', 0);
    expect(Fixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');
});

it('rejects direct signature mutation and publication without retained signatures', function (): void {
    $fixture = Fixture::ready(2);
    Fixture::seal($fixture);
    $publication = AuditReportPublication::query()->firstOrFail();
    expect(fn () => DB::transaction(function () use ($publication): void {
        $publication->forceFill(['revision' => 2, 'status' => 'published', 'published_at' => now()])->save();
        DB::statement('SET CONSTRAINTS audit_publication_signatures_required IMMEDIATE');
    }))->toThrow(QueryException::class, 'Publication requires every retained mandated signature');
    Fixture::cosign($fixture);
    $signature = AuditReportSignature::query()->firstOrFail();
    expect(fn () => DB::transaction(fn () => $signature->forceFill(['sha256' => str_repeat('0', 64)])->save()))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => $signature->delete()))->toThrow(QueryException::class);
});

it('uses the retained reporting cycle for monthly publication and keeps a late report unsigned', function (bool $late): void {
    $this->travelTo(now('UTC')->startOfMonth()->addDays($late ? 7 : 4)->setTime(10, 0));
    $fixture = Fixture::ready(kind: 'monthly');
    expect(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
    $page = app(GetBusinessAuditReport::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['report']->id);
    expect($page['cosign']['overdue'])->toBe($late)->and($page['cosign']['due_at'])->toBe(now('UTC')->day(7)->setTime(21, 59, 59)->format('Y-m-d\TH:i:s\Z'))
        ->and($page['can_cosign'])->toBe(! $late)
        ->and(Fixture::cosign($fixture)['code'])->toBe($late ? 'REPORT_WINDOW_CLOSED' : 'REPORT_PUBLISHED');
})->with([false, true]);

it('retains signatures and current-authority receipt recovery without accepting a second signature from the same Party', function (): void {
    $fixture = Fixture::ready(2);
    Fixture::seal($fixture);
    $request = (string) Str::uuid();
    $first = Fixture::cosign($fixture, overrides: ['requestId' => $request]);
    $again = Fixture::cosign($fixture, revision: 2);
    expect($again['data']['signature_id'])->toBe($first['data']['signature_id'])->and($again['revision'])->toBe(2);
    expect(fn () => Fixture::cosign($fixture, overrides: ['requestId' => $request, 'note' => 'Different acceptance']))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    $this->assertDatabaseCount('audit_report_signatures', 1);
});

it('withdraws publication readiness when current mandate or source facts change', function (string $change): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    if ($change === 'mandate') {
        $authority = $fixture['audit']['authority'];
        $authority['profile']['name'] = 'Verified renamed business';
        BusinessAuthorityFixture::configure($authority, 1);
    } else {
        AuditSourceFactsFixture::record($fixture['audit']['staff'], $fixture['assignment']->refresh(), revision: 1, facts: null);
    }
    $page = app(GetBusinessAuditReport::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['report']->id);
    expect($page['can_cosign'])->toBeFalse()->and($page['cosign']['state'])->toBe('unavailable')
        ->and(Fixture::cosign($fixture)['code'])->toBe($change === 'mandate' ? 'MANDATE_STALE' : 'AUDIT_PUBLICATION_UNAVAILABLE');
})->with(['mandate', 'source']);

it('refuses foreign and unmandated accounts for signature effects and receipt recovery', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $other = BusinessAuthorityFixture::make(companyCode: 'ANOTHER-SYNTHETIC-BUSINESS');
    BusinessAuthorityFixture::configure($other);
    expect(fn () => Fixture::cosign($fixture, overrides: ['userId' => $other['users'][0]->id]))->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND');
    $request = (string) Str::uuid();
    Fixture::cosign($fixture, overrides: ['requestId' => $request]);
    expect(fn () => app(FindAuditCosignOperation::class)->handle($other['users'][0]->id, 1, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('refuses a signature attributed to a different account Party and protects published history', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $publication = AuditReportPublication::query()->firstOrFail();
    $user = $fixture['audit']['authority']['users'][0];
    expect(fn () => DB::transaction(fn () => AuditReportSignature::factory()->forPublication($publication, $user)
        ->create(['actor_user_id' => $fixture['user']->id])))->toThrow(QueryException::class, 'Audit actor account must belong');
    Fixture::cosign($fixture);
    expect(fn () => DB::transaction(fn () => $publication->refresh()->forceFill(['revision' => 3])->save()))->toThrow(QueryException::class, 'Published audit reports cannot be changed')
        ->and(fn () => DB::transaction(fn () => $publication->delete()))->toThrow(QueryException::class, 'Published audit reports cannot be changed');
});

it('fails closed on a corrupted sealed package or signature history', function (string $record): void {
    $fixture = Fixture::ready(2);
    Fixture::seal($fixture);
    if ($record === 'signature') {
        Fixture::cosign($fixture);
        DB::statement('ALTER TABLE audit_report_signatures DISABLE TRIGGER audit_report_signatures_immutable');
        AuditReportSignature::query()->firstOrFail()->forceFill(['sha256' => str_repeat('0', 64)])->save();
        DB::statement('ALTER TABLE audit_report_signatures ENABLE TRIGGER audit_report_signatures_immutable');
    } else {
        DB::statement('SET CONSTRAINTS audit_seal_version_required IMMEDIATE');
        DB::statement('ALTER TABLE audit_report_seals DISABLE TRIGGER audit_report_seals_immutable');
        AuditReportSeal::query()->firstOrFail()->forceFill(['digest' => str_repeat('0', 64)])->save();
        DB::statement('ALTER TABLE audit_report_seals ENABLE TRIGGER audit_report_seals_immutable');
        expect(app(VerifyAuditReportSeal::class)->handle($fixture['report']->id)['seal_status'])->toBe('unavailable');
    }
    expect(fn () => app(GetBusinessAuditReport::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['report']->id))
        ->toThrow(CommandRejection::class, $record === 'signature' ? 'AUDIT_SIGNATURE_UNAVAILABLE' : 'AUDIT_SEAL_UNAVAILABLE');
})->with(['seal', 'signature']);

it('scopes absent report and operation targets without revealing another business', function (): void {
    $fixture = Fixture::ready();
    $user = $fixture['audit']['authority']['users'][0];
    expect(fn () => app(GetBusinessAuditReport::class)->handle($user->id, 1, $fixture['audit']['business'], (string) Str::ulid()))
        ->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND');
    $request = (string) Str::uuid();
    app(OperationJournal::class)->execute('party:'.$user->party_id, $user->id, 'report.cosign', $request,
        'audit.publication', (string) Str::ulid(), [], function (): void {}, fn () => new OperationResult('SYNTHETIC_UNKNOWN_TARGET', [], 1));
    expect(fn () => app(FindAuditCosignOperation::class)->handle($user->id, 1, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('allows a current Business reader to view but not sign when the mandate does not require their signature', function (): void {
    $fixture = Fixture::ready(2, requiredSignatories: 1);
    Fixture::seal($fixture);
    $reader = $fixture['audit']['authority']['users'][1];
    $page = app(GetBusinessAuditReport::class)->handle($reader->id, 1, $fixture['audit']['business'], $fixture['report']->id);
    expect($page['cosign']['signers'])->toHaveCount(1)->and($page['can_cosign'])->toBeFalse();
    expect(fn () => Fixture::cosign($fixture, 1))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN');
    $this->assertDatabaseCount('audit_report_signatures', 0);
});

it('requires a current professional-standing check even when the historical verification remains valid', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $this->travel(31)->days();
    expect(Fixture::cosign($fixture)['code'])->toBe('AUDIT_PUBLICATION_UNAVAILABLE');
    $this->assertDatabaseCount('audit_report_signatures', 0);
});

it('refuses receipt recovery when the captured Party and locked Business identity no longer match', function (): void {
    $fixture = Fixture::ready(2);
    Fixture::seal($fixture);
    $user = $fixture['audit']['authority']['users'][0];
    $other = $fixture['audit']['authority']['users'][1];
    $request = (string) Str::uuid();
    Fixture::cosign($fixture, 1, overrides: ['requestId' => $request]);
    $snapshot = app(IdentityRepository::class)->forUser($other->id);
    $identities = $this->createMock(IdentityRepository::class);
    $identities->expects($this->once())->method('forUser')->with($user->id)->willReturn($snapshot);
    $store = app()->makeWith(EloquentAuditReportPublicationStore::class, ['identities' => $identities]);
    expect(fn () => $store->findOperation($user->id, 1, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});
