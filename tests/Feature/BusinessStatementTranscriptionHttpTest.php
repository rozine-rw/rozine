<?php

declare(strict_types=1);

use App\Application\Evidence\IngestStatement;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\StatementFixture;

/**
 * The reviewed save exactly as a client builds it from the draft.
 *
 * @param  array<string, mixed>  $draft
 * @param  array<string, mixed>  $input
 * @return array<string, mixed>
 */
function reviewedTranscriptionBody(array $draft, array $input, ?string $requestId = null): array
{
    return [...$input, 'request_id' => $requestId ?? (string) Str::uuid(), 'identity_context_revision' => 1, 'business_id' => $draft['business_id'],
        'expected_business_revision' => $draft['business_revision'], 'expected_mandate_version' => $draft['mandate_version'],
        'expected_evidence_revision' => $draft['evidence']['evidence_revision'],
        'expected_transcription_id' => $draft['current_transcription']['id'] ?? null,
        'expected_transcription_sha256' => $draft['current_transcription']['sha256'] ?? null,
        'source_binding' => $draft['evidence'], 'reviewed_against_originals' => true];
}

it('lets a representative review, save and follow a transcription over the API', function (): void {
    $fixture = Fixture::make(0);
    $owner = $fixture['authority']['users'][0];
    $business = $fixture['business'];
    $document = app(IngestStatement::class)->handle($owner->id, 1, $business, 0, 'momo.csv', StatementFixture::csv(), (string) Str::uuid())['data']['document_id'];
    $input = StatementFixture::transcription($document);

    Sanctum::actingAs($owner, ['business:read']);
    $readOnly = $this->getJson("/api/v1/business/{$business}/statements/transcription-draft")->assertOk()->json('data');
    expect($readOnly['allowed_actions'])->toBe([])->and($readOnly['save'])->toBeNull()
        ->and($readOnly['assistance']['status'])->toBe('unavailable')
        ->and($readOnly['readiness'])->toBe(['url' => "/api/v1/business/{$business}/statements/audit-readiness", 'method' => 'get']);
    $this->postJson("/api/v1/business/{$business}/statements/transcription", reviewedTranscriptionBody($readOnly, $input))->assertForbidden();

    Sanctum::actingAs($owner, ['business:read', 'business:command']);
    $draft = $this->getJson("/api/v1/business/{$business}/statements/transcription-draft")->assertOk()->json('data');
    expect($draft['save'])->toBe(['url' => "/api/v1/business/{$business}/statements/transcription", 'method' => 'post'])
        ->and($draft['allowed_actions'])->toBe(['statement.reconcile']);
    $request = (string) Str::uuid();
    $body = reviewedTranscriptionBody($draft, $input, $request);
    $receipt = $this->postJson($draft['save']['url'], $body)->assertOk()
        ->assertJsonPath('code', 'STATEMENT_RECONCILED_UNVERIFIED')->assertJsonPath('data.transcription.verified', false)
        ->assertJsonPath('data.next', ['url' => "/api/v1/business/{$business}/statements/audit-readiness", 'method' => 'get'])->json();
    $id = $receipt['data']['transcription']['id'];
    expect($this->postJson($draft['save']['url'], $body)->assertOk()->json('operation_id'))->toBe($receipt['operation_id'])
        ->and($this->getJson("/api/v1/business/{$business}/statement-operations/{$request}?command=reconcile")->assertOk()->json('operation_id'))->toBe($receipt['operation_id']);
    $this->getJson('/api/v1/business/'.strtolower((string) Str::ulid())."/statement-operations/{$request}?command=reconcile")->assertNotFound();
    $this->getJson("/api/v1/business/{$business}/statements/transcription")->assertOk()->assertJsonPath('data.id', $id)->assertJsonPath('data.verified', false);
    $this->getJson("/api/v1/business/{$business}/statements/transcription?transcription={$id}")->assertOk()->assertJsonPath('data.current', true);
    $this->getJson("/api/v1/business/{$business}/statements/transcription?transcription=not-an-id")->assertNotFound();
    $this->getJson("/api/v1/business/{$business}/statements/audit-readiness")->assertOk()
        ->assertJsonPath('data.transcription.id', $id)->assertJsonPath('data.ready_to_request', null)->assertJsonPath('data.allowed_actions', []);

    $stale = $this->postJson($draft['save']['url'], reviewedTranscriptionBody($draft, $input))->assertStatus(409)->json();
    expect($stale['code'])->toBe('VERSION_CONFLICT');
    $next = $this->getJson("/api/v1/business/{$business}/statements/transcription-draft")->json('data');
    $this->postJson($draft['save']['url'], [...reviewedTranscriptionBody($next, $input), 'reviewed_against_originals' => false])
        ->assertUnprocessable()->assertJsonPath('code', 'STATEMENT_REVIEW_REQUIRED');

    Sanctum::actingAs($owner, ['investor:read']);
    $this->getJson("/api/v1/business/{$business}/statements/transcription-draft")->assertForbidden();
    $this->getJson("/api/v1/business/{$business}/statements/audit-readiness")->assertForbidden();
    $this->getJson("/api/v1/business/{$business}/statements/transcription")->assertForbidden();
});

it('validates the reviewed save structure before recording anything', function (string $fault): void {
    $fixture = Fixture::make(0);
    $owner = $fixture['authority']['users'][0];
    $business = $fixture['business'];
    $document = app(IngestStatement::class)->handle($owner->id, 1, $business, 0, 'momo.csv', StatementFixture::csv(), (string) Str::uuid())['data']['document_id'];
    Sanctum::actingAs($owner, ['business:read', 'business:command']);
    $draft = $this->getJson("/api/v1/business/{$business}/statements/transcription-draft")->json('data');
    $body = reviewedTranscriptionBody($draft, StatementFixture::transcription($document));
    $body = match ($fault) {
        'business' => [...$body, 'business_id' => strtolower((string) Str::ulid())],
        'binding' => [...$body, 'source_binding' => [...$body['source_binding'], 'manifest_sha256' => 'not-a-hash']],
        'extra' => [...$body, 'source_binding' => [...$body['source_binding'], 'verified' => true]],
        'transaction' => [...$body, 'statements' => [[...$body['statements'][0], 'transactions' => [['reference' => 'x']]]]],
        default => array_diff_key($body, ['reviewed_against_originals' => true]),
    };
    $this->postJson("/api/v1/business/{$business}/statements/transcription", $body)->assertUnprocessable();
    $this->assertDatabaseCount('statement_transcriptions', 0);
})->with(['business', 'binding', 'extra', 'transaction', 'acknowledgement']);

it('shows staff who dispatch audits the raw readiness only with their audit ability', function (): void {
    $fixture = Fixture::make(1);
    $business = $fixture['business'];
    Fixture::statements($fixture);
    Sanctum::actingAs($fixture['staff'], ['staff:audit:read']);
    $readiness = $this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit")->assertOk()->json('data');
    expect($readiness['transcription']['current'])->toBeTrue()->and($readiness['ready_to_request'])->toBeNull()
        ->and(json_encode($readiness, JSON_THROW_ON_ERROR))->not->toContain('opening_balance');
    Sanctum::actingAs($fixture['staff'], ['business:read']);
    $this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit")->assertForbidden();
    Sanctum::actingAs($fixture['partners'][0]['staff'], ['staff:audit:read']);
    $this->getJson("/api/v1/staff/businesses/{$business}/initial-statement-audit")->assertForbidden();
});
