<?php

declare(strict_types=1);

use App\Application\Evidence\IngestStatement;
use App\Models\StatementTranscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BusinessApplicationFixture as DraftFixture;
use Tests\Support\StatementFixture;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25T03:00:00Z'));
    $this->withoutVite();
});

/**
 * The figures page's props as the representative opens it.
 *
 * @return array<string, mixed>
 */
function statementTranscriptionPage(User $user, string $business): array
{
    $props = [];
    actingAs($user)->get("/business/{$business}/statements/transcription")->assertOk()->assertInertia(function (Assert $page) use (&$props): Assert {
        $props = $page->toArray()['props'];

        return $page->component('business/statement-transcription');
    });

    return $props;
}

/**
 * The save exactly as the page posts it from the draft it was drawn with.
 *
 * @param  array<string, mixed>  $draft
 * @param  array<string, mixed>  $input
 * @return array<string, mixed>
 */
function statementTranscriptionForm(array $draft, array $input, ?string $requestId = null): array
{
    return [...$input, 'request_id' => $requestId ?? (string) Str::uuid(), 'identity_context_revision' => 1,
        'expected_business_revision' => $draft['business_revision'], 'expected_mandate_version' => $draft['mandate_version'],
        'expected_evidence_revision' => $draft['evidence']['evidence_revision'],
        'expected_transcription_id' => $draft['current_transcription']['id'] ?? null,
        'expected_transcription_sha256' => $draft['current_transcription']['sha256'] ?? null,
        'source_binding' => $draft['evidence'], 'reviewed_against_originals' => true];
}

it('lets a representative enter the figures from their statements on the web and saves them unverified', function (): void {
    $fixture = DraftFixture::make();
    $user = $fixture['authority']['users'][0];
    $business = $fixture['business']->id;
    $page = "/business/{$business}/statements/transcription";

    $empty = statementTranscriptionPage($user, $business);
    expect($empty['draft']['allowed_actions'])->toBe([])->and($empty['draft']['save'])->toBeNull()
        ->and($empty['identity_context_revision'])->toBe(1)
        ->and($empty['statements']['documents'])->toBe([])
        ->and($empty['links']['close'])->toBe(['url' => "/business/{$business}/applications/{$fixture['application']->id}", 'method' => 'get'])
        ->and($empty['home']['links']['home']['url'])->toBe("/business/{$business}");

    $document = app(IngestStatement::class)->handle($user->id, 1, $business, 0, 'momo.csv', StatementFixture::csv(), (string) Str::uuid())['data']['document_id'];
    $props = statementTranscriptionPage($user, $business);
    $draft = $props['draft'];
    expect($draft['save'])->toBe(['url' => $page, 'method' => 'post'])
        ->and($draft['allowed_actions'])->toBe(['statement.reconcile'])
        ->and($draft['current_transcription'])->toBeNull()
        ->and($props['statements']['documents'][0]['id'])->toBe($document)
        ->and($props['statements']['transcription'])->toBe(['url' => $page, 'method' => 'get']);

    actingAs($user)->from($page)->post($page, statementTranscriptionForm($draft, StatementFixture::transcription($document)))
        ->assertRedirect($page)->assertSessionHasNoErrors();
    $saved = StatementTranscription::query()->sole();
    expect($saved->actor_user_id)->toBe($user->id);

    actingAs($user)->get($page)->assertInertia(fn (Assert $inertia) => $inertia->component('business/statement-transcription')
        ->where('draft.current_transcription.id', $saved->id)->where('draft.current_transcription.verified', false)
        ->hasFlash('toast', ['type' => 'success', 'message' => 'Figures saved. An Audit Partner still has to verify them against your statements.']));
});

it('shows a refused save on the form in words, and saves nothing', function (string $fault, string $message): void {
    $fixture = DraftFixture::make();
    $user = $fixture['authority']['users'][0];
    $business = $fixture['business']->id;
    $page = "/business/{$business}/statements/transcription";
    $document = app(IngestStatement::class)->handle($user->id, 1, $business, 0, 'momo.csv', StatementFixture::csv(), (string) Str::uuid())['data']['document_id'];
    $draft = statementTranscriptionPage($user, $business)['draft'];
    $input = StatementFixture::transcription($document);

    $body = match ($fault) {
        'difference' => statementTranscriptionForm($draft, [...$input, 'statements' => [[...$input['statements'][0], 'closing_balance' => '1'], ...array_slice($input['statements'], 1)]]),
        'unreviewed' => [...statementTranscriptionForm($draft, $input), 'reviewed_against_originals' => false],
        default => [...statementTranscriptionForm($draft, $input), 'expected_evidence_revision' => 0],
    };

    actingAs($user)->from($page)->post($page, $body)->assertRedirect($page)->assertSessionHasErrors(['transcription' => $message]);
    $this->assertDatabaseCount('statement_transcriptions', 0);
})->with([
    'a month that does not add up' => ['difference', 'A month does not add up: opening balance plus transactions must equal the closing balance.'],
    'no review acknowledgement' => ['unreviewed', 'Confirm you checked these figures against your original statements.'],
    'statements changed meanwhile' => ['stale', 'Your statements changed while you were entering figures. Reload the page and check again.'],
]);

it('keeps the figures page to members of the business', function (): void {
    $fixture = DraftFixture::make();
    $business = $fixture['business']->id;
    $outsider = DraftFixture::make()['authority']['users'][0];

    $this->get("/business/{$business}/statements/transcription")->assertRedirect('/login');
    expect(actingAs($outsider)->get("/business/{$business}/statements/transcription")->status())->toBeIn([403, 404]);
});
