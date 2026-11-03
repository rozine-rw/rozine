<?php

declare(strict_types=1);

use App\Application\Evidence\GetStaffStatementAuditReadiness;
use App\Application\Evidence\RequestStatementReview;
use App\Domain\Evidence\StatementAuditReview;
use App\Models\AuditAssignment;
use App\Models\StatementTranscription;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\StatementFixture;

/*
 * The Audit Partner's statement review on the web (#396): the accepted job's file lists each
 * statement to open and the Business's saved figures, and the factual verification is sent and
 * looked up from it.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

it('lets the partner open each statement and verify the review from the job file', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $readiness = app(GetStaffStatementAuditReadiness::class)->handle($fixture['staff']->id, $fixture['business']);
    $receipt = app(RequestStatementReview::class)->handle($fixture['staff']->id, $fixture['business'], [
        'expected_business_revision' => $readiness['business_revision'], 'expected_mandate_version' => $readiness['mandate_version'],
        'expected_evidence_revision' => $readiness['evidence_revision'], 'transcription_id' => $readiness['transcription']['id'] ?? '',
        'transcription_sha256' => $readiness['transcription']['sha256'] ?? '', 'readiness_sha256' => $readiness['readiness_sha256']],
        'First factual review of the uploaded statements.', (string) Str::uuid());
    $assignment = AuditAssignment::query()->whereKey($receipt['data']['assignment_id'])->firstOrFail();
    $user = $fixture['partners'][0]['user'];
    Fixture::respond($user, $assignment);
    $assignment->refresh();
    $transcription = StatementTranscription::query()->whereKey($sources['transcription_id'])->firstOrFail();

    $props = [];
    $this->actingAs($user)->get('/auditor/jobs/'.$assignment->id)->assertOk()->assertInertia(function (Assert $page) use (&$props): Assert {
        $props = $page->toArray()['props'];

        // A statement review reads as one: its own brief, and no monthly window for it in Jobs (#511).
        return $page->component('auditor/file')->where('job.state', 'assigned')->where('file.brief', 'statement_review')->where('jobs.monthly', null);
    });
    $review = $props['review'];
    expect($props['allowed_actions'])->toContain('statement.verify')
        ->and($review)->toMatchArray(['assignment_revision' => $assignment->revision, 'evidence_revision' => 2, 'verification_revision' => 0,
            'procedure_version' => StatementAuditReview::PROCEDURE, 'checks' => StatementAuditReview::CHECKS,
            'verify' => ['url' => '/auditor/jobs/'.$assignment->id.'/statement-verification', 'method' => 'post'],
            'operation' => ['url' => '/auditor/statement-operations/{request_id}', 'method' => 'get']])
        ->and($review['documents'])->toHaveCount(1)
        ->and($review['documents'][0])->toMatchArray(['id' => $sources['document_id'], 'received_on' => now('Africa/Kigali')->format('Y-m-d'),
            'open' => ['url' => '/auditor/jobs/'.$assignment->id.'/statements/'.$sources['document_id'], 'method' => 'get']])
        ->and($review['documents'][0]['filename'])->not->toContain('private-account-reference')
        ->and($review['transcription'])->toMatchArray(['id' => $transcription->id, 'sha256' => $transcription->sha256, 'months' => $transcription->payload['months']])
        ->and($review['transcription']['observations'])->not->toBeEmpty()
        ->and(array_keys($review['transcription']['observations'][0]))->toBe(['month', 'operating_inflow', 'operating_outflow', 'owner_draw', 'debt_service'])
        ->and($review['transcription']['balances'])->toBe(array_map(fn (array $statement): array => ['account' => $statement['rail_id'], 'month' => $statement['month'],
            'opening_balance' => $statement['opening_balance'], 'closing_balance' => $statement['closing_balance']], $transcription->payload['statements']))
        ->and($review['transcription']['balances'])->not->toBeEmpty();

    $original = $this->get($review['documents'][0]['open']['url'])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    expect($original->getContent())->toBe(StatementFixture::csv())
        ->and((string) $original->headers->get('Content-Disposition'))->toStartWith('attachment');

    $body = ['identity_context_revision' => $props['identity_context_revision'], 'expected_revision' => $review['assignment_revision'],
        'expected_evidence_revision' => $review['evidence_revision'], 'expected_verification_revision' => $review['verification_revision'],
        'transcription_id' => $transcription->id, 'transcription_sha256' => $transcription->sha256,
        'review' => StatementFixture::review($transcription->payload['source_hashes'])];
    $this->postJson($review['verify']['url'], ['review' => []])->assertUnprocessable();
    $this->postJson($review['verify']['url'], [...$body, 'expected_evidence_revision' => 1, 'request_id' => (string) Str::uuid()])
        ->assertConflict()->assertJsonPath('code', 'VERSION_CONFLICT');

    $request = (string) Str::uuid();
    $verified = $this->postJson($review['verify']['url'], [...$body, 'request_id' => $request])->assertOk()
        ->assertJsonPath('status', 'completed')->assertJsonPath('code', 'STATEMENT_SOURCE_VERIFIED')
        ->assertJsonPath('data.report_approval', 'not_cosigned')->assertJsonPath('data.next', ['url' => '/auditor/jobs', 'method' => 'get'])->json();
    $this->getJson(str_replace('{request_id}', $request, $review['operation']['url']).'?command=statement.verify')->assertOk()
        ->assertJsonPath('operation_id', $verified['operation_id'])->assertJsonPath('code', 'STATEMENT_SOURCE_VERIFIED');

    // The finished review's old link says it is done rather than reading as not found (#511).
    $this->get('/auditor/jobs/'.$assignment->id)->assertStatus(409)
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied')
            ->where('code', 'STATEMENT_REVIEW_COMPLETED')->where('status', 409));
    $this->getJson('/auditor/jobs/'.$assignment->id)->assertStatus(409)->assertJsonPath('code', 'STATEMENT_REVIEW_COMPLETED');
});
