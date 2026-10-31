<?php

declare(strict_types=1);

use App\Models\AuditAssignment;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture as Fixture;

/**
 * The request body a staff client builds from the readiness it showed.
 *
 * @param  array<string, mixed>  $readiness
 * @return array<string, mixed>
 */
function statementReviewBody(array $readiness, ?string $requestId = null): array
{
    return ['request_id' => $requestId ?? (string) Str::uuid(), 'reason' => 'First factual review of the uploaded statements.',
        'expected_business_revision' => $readiness['business_revision'], 'expected_mandate_version' => $readiness['mandate_version'],
        'expected_evidence_revision' => $readiness['evidence_revision'], 'transcription_id' => $readiness['transcription']['id'],
        'transcription_sha256' => $readiness['transcription']['sha256'], 'readiness_sha256' => $readiness['readiness_sha256']];
}

it('lets staff request a statement review from the readiness they saw and recover its receipt', function (): void {
    $fixture = Fixture::make(1);
    $business = $fixture['business'];
    Fixture::statements($fixture);
    Sanctum::actingAs($fixture['staff'], ['staff:audit:read', 'staff:audit:manage']);
    $readiness = $this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit")->assertOk()->json('data');
    $body = statementReviewBody($readiness);

    $receipt = $this->postJson("/api/v1/staff/businesses/{$business}/initial-statement-audit/review", $body)->assertOk()
        ->assertJsonPath('status', 'succeeded')->assertJsonPath('code', 'AUDIT_ASSIGNMENT_REQUESTED')
        ->assertJsonPath('data.next', ['url' => "/api/v1/staff/businesses/{$business}/initial-statement-audit", 'method' => 'get'])
        ->assertJsonPath('allowed_actions', [])->json();
    $assignment = AuditAssignment::query()->whereKey($receipt['data']['assignment_id'])->firstOrFail();
    expect($assignment->state['purpose'] ?? null)->toBe('statement_review');

    $this->postJson("/api/v1/staff/businesses/{$business}/initial-statement-audit/review", $body)->assertOk()->assertJsonPath('operation_id', $receipt['operation_id']);
    $this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit/operations/{$body['request_id']}")->assertOk()
        ->assertJsonPath('operation_id', $receipt['operation_id'])->assertJsonPath('data.assignment_id', $assignment->id);
    $this->getJson('/api/v1/staff/businesses/'.Str::ulid()."/initial-statement-audit/operations/{$body['request_id']}")->assertNotFound();
    $this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit")->assertOk()
        ->assertJsonPath('data.reasons', [['code' => 'AUDIT_STATEMENT_REVIEW_OPEN', 'field' => 'assignment']]);
    $this->postJson("/api/v1/staff/businesses/{$business}/initial-statement-audit/review", [...$body, 'reason' => 'Changed.'])->assertConflict();
    expect(AuditAssignment::query()->count())->toBe(1);
});

it('refuses a stale readiness binding and leaves the request available', function (): void {
    $fixture = Fixture::make(1);
    $business = $fixture['business'];
    Fixture::statements($fixture);
    Sanctum::actingAs($fixture['staff'], ['staff:audit:read', 'staff:audit:manage']);
    $readiness = $this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit")->json('data');

    $this->postJson("/api/v1/staff/businesses/{$business}/initial-statement-audit/review", [...statementReviewBody($readiness), 'readiness_sha256' => str_repeat('0', 64)])
        ->assertConflict()->assertJsonPath('code', 'STATEMENT_READINESS_STALE')->assertJsonPath('allowed_actions', ['audit.statement_review.request']);
    $this->assertDatabaseCount('audit_assignments', 0);
});

it('checks the request body before the domain sees it', function (string $field, mixed $value): void {
    $fixture = Fixture::make(1);
    $business = $fixture['business'];
    Fixture::statements($fixture);
    Sanctum::actingAs($fixture['staff'], ['staff:audit:read', 'staff:audit:manage']);
    $body = statementReviewBody($this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit")->json('data'));

    $this->postJson("/api/v1/staff/businesses/{$business}/initial-statement-audit/review", [...$body, $field => $value])->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('audit_assignments', 0);
})->with([
    ['request_id', 'not-a-uuid'], ['expected_business_revision', -1], ['expected_mandate_version', 'one'], ['expected_evidence_revision', null],
    ['transcription_id', 'not-a-ulid'], ['transcription_sha256', 'ABC'], ['readiness_sha256', str_repeat('g', 64)],
]);

it('keeps the request and its lookup to staff with the audit abilities and permission', function (): void {
    $fixture = Fixture::make(1);
    $business = $fixture['business'];
    Fixture::statements($fixture);
    Sanctum::actingAs($fixture['staff'], ['staff:audit:read', 'staff:audit:manage']);
    $body = statementReviewBody($this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit")->json('data'));

    Sanctum::actingAs($fixture['staff'], ['staff:audit:read']);
    $this->postJson("/api/v1/staff/businesses/{$business}/initial-statement-audit/review", $body)->assertForbidden();
    Sanctum::actingAs($fixture['staff'], ['staff:audit:manage']);
    $this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit/operations/{$body['request_id']}")->assertForbidden();
    Sanctum::actingAs($fixture['partners'][0]['staff'], ['staff:audit:read', 'staff:audit:manage']);
    $this->postJson("/api/v1/staff/businesses/{$business}/initial-statement-audit/review", $body)->assertForbidden()->assertJsonPath('code', 'STAFF_PERMISSION_REQUIRED');
    $this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit/operations/{$body['request_id']}")->assertForbidden();
    $this->assertDatabaseCount('audit_assignments', 0);
});
