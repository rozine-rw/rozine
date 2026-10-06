<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\GetAuditOperationsCase;
use App\Application\Auditor\GetBusinessAuditReport;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditDisputeProof;
use App\Models\AuditPublicationEvent;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSignature;
use App\Models\AuditSigningKeyRevocation;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSealingFixture as Fixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

/** @param array<string, mixed> $fixture
 * @return array<string, mixed>
 */
function correctedReviewInput(array $fixture, int $revision = 1): array
{
    return ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => $revision,
        'report_revision' => $fixture['report']->revision + 1, 'mandate_version' => 1, 'digest' => $fixture['fields']['digest'],
        'supporting_text' => 'The retained receipt changes these findings.', 'reason' => 'Review the retained receipt before publication.'];
}

it('accepts a timely Business objection during a CPA outage and never auto approves after restoration', function (bool $api): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $membership = RoleMembership::query()->where('party_id', $fixture['user']->party_id)->where('role', 'auditor')->firstOrFail();
    $membership->forceFill(['status' => 'suspended'])->save();
    $this->travel(2)->hours();
    $owner = $fixture['audit']['authority']['users'][0];
    $page = app(GetBusinessAuditReport::class)->handle($owner->id, 1, $fixture['audit']['business'], $fixture['report']->id);
    expect($page['can_dispute'])->toBeTrue()->and($page['can_cosign'])->toBeFalse();
    $prefix = $api ? 'api.v1.' : '';
    if ($api) {
        Sanctum::actingAs($owner, ['business:read', 'business:command']);
    } else {
        $this->actingAs($owner);
    }
    $parameters = ['business' => $fixture['audit']['business'], 'report' => $fixture['report']->id];
    $response = $this->get(route($prefix.'business.audit-reports.show', $parameters))->assertOk();
    $props = $api ? $response->json('data') : $response->viewData('page')['props'];
    expect($props['allowed_actions'])->toBe(['report.dispute'])->and($props['actions']['dispute'])->not->toBeNull();
    $input = correctedReviewInput($fixture);
    $receipt = $this->postJson(route($prefix.'business.audit-reports.dispute', $parameters), $input)->assertOk()->assertJsonPath('code', 'REPORT_DISPUTED')->json();
    $this->travel(23)->hours();
    $membership->forceFill(['status' => 'active'])->save();
    $store = app(AuditReportPublicationStore::class);
    expect($store->advanceDue()['published'])->toBe(0)
        ->and(AuditReportPublication::query()->firstOrFail()->status)->toBe('disputed')
        ->and(AuditReportPublication::query()->firstOrFail()->published_reason)->toBeNull()
        ->and(AuditReportSignature::query()->count())->toBe(0)
        ->and($store->findOperation($owner->id, 1, $input['request_id'], 'report.dispute')['operation_id'])->toBe($receipt['operation_id']);
})->with([false, true]);

it('returns the closed-window refusal before unavailable signing-key authority', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $fixture['key']->id]);
    $this->travel(24)->hours();
    expect(app(AuditReportPublicationStore::class)->dispute($fixture['audit']['authority']['users'][0]->id,
        $fixture['audit']['business'], $fixture['report']->id, correctedReviewInput($fixture), [])['code'])->toBe('REPORT_WINDOW_CLOSED');
});

it('freezes staff amendment requirements against repeated or reversed decisions and direct event inserts', function (bool $api): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $store->dispute($fixture['audit']['authority']['users'][0]->id, $fixture['audit']['business'], $fixture['report']->id, correctedReviewInput($fixture), []);
    $staff = $fixture['audit']['staff'];
    $store->staffDecision($staff->id, $fixture['assignment']->id, $fixture['report']->id, 'audit.dispute.escalate', correctedReviewInput($fixture, 2));
    $input = [...correctedReviewInput($fixture, 3), 'decision' => 'require_amendment'];
    $receipt = $store->staffDecision($staff->id, $fixture['assignment']->id, $fixture['report']->id, 'audit.dispute.resolve', $input);
    expect($store->staffDecision($staff->id, $fixture['assignment']->id, $fixture['report']->id, 'audit.dispute.resolve', $input))->toBe($receipt);
    foreach (['require_amendment', 'uphold'] as $decision) {
        $retry = [...correctedReviewInput($fixture, 4), 'decision' => $decision];
        expect($store->staffDecision($staff->id, $fixture['assignment']->id, $fixture['report']->id, 'audit.dispute.resolve', $retry)['code'])->toBe('REPORT_REVIEW_CLOSED');
    }
    if ($api) {
        Sanctum::actingAs($staff, ['staff:audit:read', 'staff:audit:manage']);
    } else {
        $this->actingAs($staff);
    }
    $this->getJson(route(($api ? 'api.v1.' : '').'staff.audit.disputes.show', ['assignment' => $fixture['assignment']->id, 'report' => $fixture['report']->id]))
        ->assertOk()->assertJsonPath('data.allowed_actions', [])->assertJsonPath('data.actions.resolve', null);
    $publication = AuditReportPublication::query()->firstOrFail();
    expect(fn () => DB::transaction(fn () => AuditPublicationEvent::factory()->create([
        'audit_report_publication_id' => $publication->id, 'publication_revision' => 5, 'command' => 'audit.dispute.resolve',
        'actor_kind' => 'staff', 'actor_user_id' => $staff->id, 'previous_sha256' => AuditPublicationEvent::query()->orderByDesc('publication_revision')->value('sha256')])))->toThrow(QueryException::class, 'A required amendment remains frozen');
    expect($publication->refresh()->revision)->toBe(4)->and($publication->published_at)->toBeNull();
})->with([false, true]);

it('lists only proof metadata while retaining protected original downloads', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $owner = $fixture['audit']['authority']['users'][0];
    $store->dispute($owner->id, $fixture['audit']['business'], $fixture['report']->id, correctedReviewInput($fixture),
        [['filename' => 'sensitive-name.pdf', 'content' => "%PDF-1.4\nSynthetic original\n%%EOF"]]);
    $event = 'eloquent.retrieved: '.AuditDisputeProof::class;
    $reads = 0;
    Event::listen($event, function (AuditDisputeProof $proof) use (&$reads): void {
        $reads++;
        expect(array_keys($proof->getAttributes()))->toBe(['id', 'mime_type', 'size_bytes', 'sha256']);
    });
    try {
        $page = $store->get($owner->id, 1, $fixture['audit']['business'], $fixture['report']->id);
        $store->forAuditor($fixture['report']->id);
        $store->staffCase($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['report']->id);
        expect($reads)->toBeGreaterThanOrEqual(3)->and($page['cosign']['dispute']['proof_files'][0]['name'])->toStartWith('audit-proof-');
    } finally {
        Event::forget($event);
    }
});

it('verifies attributed event metadata and every predecessor before serving a review', function (string $fault): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $owner = $fixture['audit']['authority']['users'][0];
    $store->dispute($owner->id, $fixture['audit']['business'], $fixture['report']->id, correctedReviewInput($fixture), []);
    $model = in_array($fault, ['count', 'projection'], true) ? AuditReportPublication::class : AuditPublicationEvent::class;
    $event = 'eloquent.retrieved: '.$model;
    Event::listen($event, function ($record) use ($fault): void {
        if ($record instanceof AuditPublicationEvent && $record->publication_revision !== 1) {
            return;
        }
        $record->forceFill(match ($fault) {
            'command' => ['command' => 'report.auto_approve'], 'actor' => ['actor_kind' => 'staff'],
            'time' => ['created_at' => $record->created_at->addSecond()], 'predecessor' => ['previous_sha256' => str_repeat('0', 64)],
            'revision' => ['publication_revision' => 3], 'count' => ['revision' => 3],
            default => ['review' => [...$record->review, 'resolution_note' => 'changed']],
        });
    });
    try {
        expect(fn () => $store->get($owner->id, 1, $fixture['audit']['business'], $fixture['report']->id))->toThrow(CommandRejection::class, 'AUDIT_REVIEW_UNAVAILABLE');
    } finally {
        Event::forget($event);
    }
})->with(['command', 'actor', 'time', 'predecessor', 'revision', 'count', 'projection']);

it('rejects an event that substitutes its predecessor and exposes blocked expiry in the Operations case', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $publication = AuditReportPublication::query()->firstOrFail();
    expect(fn () => DB::transaction(fn () => AuditPublicationEvent::factory()->create([
        'audit_report_publication_id' => $publication->id, 'publication_revision' => 2, 'command' => 'report.dispute',
        'actor_kind' => 'party', 'actor_user_id' => $fixture['audit']['authority']['users'][0]->id,
        'actor_party_id' => $fixture['audit']['authority']['users'][0]->party_id, 'previous_sha256' => str_repeat('0', 64)])))
        ->toThrow(QueryException::class, 'Review events must extend their retained predecessor');
    AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $fixture['key']->id]);
    $this->travel(25)->hours();
    $case = app(GetAuditOperationsCase::class)->handle($fixture['audit']['staff']->id, $fixture['assignment']->id);
    expect($case['audit_reviews'][0])->toMatchArray(['report_id' => $fixture['report']->id, 'status' => 'pending',
        'due_at' => $publication->due_at->toIso8601String(), 'publication_available' => false, 'unavailable_reason' => 'AUDIT_PUBLICATION_UNAVAILABLE']);
});

it('accepts sniffed image proof with neutral attachment names', function (string $kind): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $owner = $fixture['audit']['authority']['users'][0];
    Sanctum::actingAs($owner, ['business:read', 'business:command']);
    $parameters = ['business' => $fixture['audit']['business'], 'report' => $fixture['report']->id];
    $file = UploadedFile::fake()->image('private-owner-name.'.$kind);
    $this->post(route('api.v1.business.audit-reports.dispute', $parameters), [...correctedReviewInput($fixture), 'proof_files' => [$file]], ['Accept' => 'application/json'])
        ->assertOk()->assertJsonPath('code', 'REPORT_DISPUTED');
    $proof = AuditDisputeProof::query()->firstOrFail();
    $this->get(route('api.v1.business.audit-reports.disputes.proofs.show', [...$parameters, 'proof' => $proof->id]))
        ->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="audit-proof-'.$proof->id.'.'.$kind.'"')
        ->assertHeader('X-Content-Type-Options', 'nosniff')->assertContent($file->getContent());
})->with(['png', 'jpg']);

it('rejects oversized and excessive proof envelopes without storing an original', function (string $fault): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    Sanctum::actingAs($fixture['audit']['authority']['users'][0], ['business:read', 'business:command']);
    $content = "%PDF-1.4\nSynthetic\n%%EOF";
    $files = match ($fault) {
        'count' => array_map(fn (int $n) => UploadedFile::fake()->createWithContent('proof-'.$n.'.pdf', $content), range(1, 6)),
        'size' => [UploadedFile::fake()->createWithContent('proof.pdf', str_pad($content, 10 * 1024 * 1024 + 1, ' '))],
        default => [UploadedFile::fake()->createWithContent(str_repeat('é', 256).'.pdf', $content)],
    };
    $this->post(route('api.v1.business.audit-reports.dispute', ['business' => $fixture['audit']['business'], 'report' => $fixture['report']->id]),
        [...correctedReviewInput($fixture), 'proof_files' => $files], ['Accept' => 'application/json'])->assertUnprocessable();
    $this->assertDatabaseCount('audit_dispute_proofs', 0);
    expect(AuditReportPublication::query()->firstOrFail()->revision)->toBe(1);
})->with(['count', 'size', 'name']);

it('denies command tokens without write authority and protects proof from another Business', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $store->dispute($fixture['audit']['authority']['users'][0]->id, $fixture['audit']['business'], $fixture['report']->id, correctedReviewInput($fixture),
        [['filename' => 'private.pdf', 'content' => "%PDF-1.4\nSynthetic\n%%EOF"]]);
    $proof = AuditDisputeProof::query()->firstOrFail();
    Sanctum::actingAs($fixture['user'], ['auditor:read']);
    $this->postJson(route('api.v1.auditor.reports.disputes.uphold', ['report' => $fixture['report']->id]), correctedReviewInput($fixture, 2))->assertForbidden();
    $staffParameters = ['assignment' => $fixture['assignment']->id, 'report' => $fixture['report']->id];
    Sanctum::actingAs($fixture['audit']['staff'], ['staff:audit:read']);
    foreach (['escalate', 'resolve'] as $command) {
        $this->postJson(route('api.v1.staff.audit.disputes.'.$command, $staffParameters), [...correctedReviewInput($fixture, 2), 'decision' => 'uphold'])->assertForbidden();
    }
    $other = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($other);
    Sanctum::actingAs($other['users'][0], ['business:read']);
    $this->getJson(route('api.v1.business.audit-reports.disputes.proofs.show', ['business' => $fixture['audit']['business'], 'report' => $fixture['report']->id, 'proof' => $proof->id]))->assertNotFound();
    $unprivileged = User::factory()->withTwoFactor()->create();
    Sanctum::actingAs($unprivileged, ['staff:audit:read', 'staff:audit:manage']);
    $this->getJson(route('api.v1.staff.audit.disputes.show', $staffParameters))->assertForbidden();
    $this->getJson(route('api.v1.staff.audit.disputes.proofs.show', [...$staffParameters, 'proof' => $proof->id]))->assertForbidden();
    expect(AuditReportPublication::query()->firstOrFail()->status)->toBe('disputed');
});
