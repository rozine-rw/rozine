<?php

declare(strict_types=1);

use App\Models\AuditDisputeProof;
use App\Models\AuditReportPublication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSealingFixture as Fixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

it('serves and recovers file-only disputes, CPA review and staff decisions across both transports', function (bool $api): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $prefix = $api ? 'api.v1.' : '';
    $business = ['business' => $fixture['audit']['business'], 'report' => $fixture['report']->id];
    $auditor = ['report' => $fixture['report']->id];
    $staff = ['assignment' => $fixture['assignment']->id, ...$auditor];
    $authenticate = function ($user, array $abilities) use ($api): void {
        if ($api) {
            Sanctum::actingAs($user, $abilities);
        } else {
            $this->actingAs($user);
        }
    };
    $authenticate($fixture['audit']['authority']['users'][0], ['business:read', 'business:command']);
    $response = $this->get(route($prefix.'business.audit-reports.show', $business))->assertOk();
    $page = $api ? $response->json('data') : $response->viewData('page')['props'];
    expect($page['allowed_actions'])->toBe(['report.cosign', 'report.dispute'])->and($page['cosign']['required_signatures'])->toBe(1);
    $input = ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => 1,
        'report_revision' => $fixture['report']->revision + 1, 'mandate_version' => 1, 'digest' => $fixture['fields']['digest']];
    $content = "%PDF-1.4\nSynthetic monthly proof\n%%EOF";
    $payload = [...$input, 'proof_files' => [UploadedFile::fake()->createWithContent('confidential.pdf', $content)]];
    $result = $this->post(route($prefix.'business.audit-reports.dispute', $business), $payload, ['Accept' => 'application/json'])
        ->assertOk()->assertJsonPath('code', 'REPORT_DISPUTED')->assertJsonPath('data.next.url', route($prefix.'business.audit-reports.show', $business, false))->json();
    $this->getJson(route($prefix.'business.audit-reports.operations.show', ['request_id' => $input['request_id'], 'command' => 'report.dispute', 'identity_context_revision' => 1]))
        ->assertOk()->assertJsonPath('operation_id', $result['operation_id']);
    $response = $this->get(route($prefix.'business.audit-reports.show', $business))->assertOk();
    $page = $api ? $response->json('data') : $response->viewData('page')['props'];
    expect($page['allowed_actions'])->toBeEmpty()->and($page['actions']['dispute'])->toBeNull()
        ->and($page['cosign']['dispute']['status'])->toBe('under_review')->and($page['cosign']['dispute']['supporting_text'])->toBe('');
    $this->get($page['cosign']['dispute']['proof_files'][0]['download']['url'])->assertOk()->assertContent($content)
        ->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
    $proof = AuditDisputeProof::query()->firstOrFail();
    $authenticate($fixture['user'], ['auditor:read', 'auditor:command']);
    $response = $this->get(route($prefix.'auditor.reports.show', $auditor))->assertOk();
    $page = $api ? $response->json('data') : $response->viewData('page')['props'];
    expect($page['allowed_actions'])->toContain('audit.dispute.uphold');
    $this->get(route($prefix.'auditor.reports.disputes.proofs.show', [...$auditor, 'proof' => $proof->id]))->assertOk()->assertContent($content);
    $uphold = [...$input, 'request_id' => (string) Str::uuid(), 'expected_revision' => 2, 'reason' => 'This original belongs to a later reporting period.'];
    $this->postJson(route($prefix.'auditor.reports.disputes.uphold', $auditor), $uphold)->assertOk()->assertJsonPath('code', 'REPORT_DISPUTE_ESCALATED');
    $this->getJson(route($prefix.'auditor.reports.operations.show', ['request_id' => $uphold['request_id'], 'command' => 'audit.dispute.uphold', 'identity_context_revision' => 1]))
        ->assertOk()->assertJsonPath('data.next.url', route($prefix.'auditor.reports.show', $auditor, false));
    $authenticate($fixture['audit']['staff'], ['staff:audit:read', 'staff:audit:manage']);
    $this->get(route($prefix.'staff.audit.disputes.proofs.show', [...$staff, 'proof' => $proof->id]))->assertOk()->assertContent($content);
    $this->getJson(route($prefix.'staff.audit.disputes.show', $staff))->assertOk()->assertJsonPath('data.allowed_actions', ['audit.dispute.resolve']);
    $decision = [...$input, 'request_id' => (string) Str::uuid(), 'expected_revision' => 3, 'reason' => 'Reconcile the disputed period against the retained receipt.', 'decision' => 'require_amendment'];
    $this->postJson(route($prefix.'staff.audit.disputes.resolve', $staff), $decision)->assertOk()->assertJsonPath('code', 'REPORT_DISPUTE_AMENDMENT_REQUIRED');
    $this->getJson(route($prefix.'staff.audit.disputes.operations.show', ['request_id' => $decision['request_id'], 'command' => 'audit.dispute.resolve']))
        ->assertOk()->assertJsonPath('code', 'REPORT_DISPUTE_AMENDMENT_REQUIRED');
    $this->getJson(route($prefix.'staff.audit.show', ['assignment' => $fixture['assignment']->id]))->assertOk()
        ->assertJsonPath('data.case.audit_reviews.0.report_id', $fixture['report']->id);
    expect(AuditReportPublication::query()->firstOrFail()->status)->toBe('escalated');
})->with([false, true]);

it('rejects malformed or disallowed proof envelopes and denies a read-only API command', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $input = ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => 1,
        'report_revision' => $fixture['report']->revision + 1, 'mandate_version' => 1, 'digest' => $fixture['fields']['digest']];
    $path = route('api.v1.business.audit-reports.dispute', ['business' => $fixture['audit']['business'], 'report' => $fixture['report']->id]);
    Sanctum::actingAs($fixture['audit']['authority']['users'][0], ['business:read']);
    $this->postJson($path, $input)->assertForbidden();
    Sanctum::actingAs($fixture['audit']['authority']['users'][0], ['business:read', 'business:command']);
    $this->post($path, [...$input, 'proof_files' => [UploadedFile::fake()->createWithContent('fake.pdf', 'not a PDF')]], ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonPath('code', 'REPORT_DISPUTE_PROOF_INVALID');
    $input['request_id'] = (string) Str::uuid();
    $this->postJson($path, $input)->assertUnprocessable()->assertJsonPath('code', 'REPORT_DISPUTE_PROOF_REQUIRED');
    $this->assertDatabaseCount('audit_dispute_proofs', 0);
});

it('runs a bounded due-review sweep and rejects an invalid CLI limit', function (): void {
    Cache::forget('monthly-audit-review-sweep-cursor');
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $this->travel(25)->hours();
    expect(Artisan::call('audits:advance-reviews', ['--limit' => '0']))->toBe(2);
    expect(Artisan::call('audits:advance-reviews', ['--limit' => '1']))->toBe(0)->and(Artisan::output())->toContain('Inspected 1 due reviews; published 1.');
    expect(Artisan::call('audits:advance-reviews', ['--limit' => '1']))->toBe(0)->and(Artisan::output())->toContain('Inspected 0 due reviews; published 0.');
    expect(AuditReportPublication::query()->firstOrFail()->published_reason)->toBe('auto_approved');
});
