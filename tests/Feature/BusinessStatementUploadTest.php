<?php

declare(strict_types=1);

use App\Models\StatementOriginal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessApplicationFixture as DraftFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\StatementFixture;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25T03:00:00Z'));
    $this->withoutVite();
});

/**
 * The statements the apply page shows on step 1.
 *
 * @return array<string, mixed>
 */
function statementUploadPage(User $user, string $path): array
{
    $statements = [];
    test()->actingAs($user)->get($path)->assertOk()->assertInertia(function (Assert $page) use (&$statements): Assert {
        $statements = $page->toArray()['props']['statements'];

        return $page->component('business/apply');
    });

    return $statements;
}

/**
 * The upload exactly as the page posts it.
 *
 * @return array<string, mixed>
 */
function statementUploadForm(UploadedFile $file, int $revision, ?string $requestId = null): array
{
    return ['file' => $file, 'identity_context_revision' => 1, 'expected_revision' => $revision, 'request_id' => $requestId ?? (string) Str::uuid()];
}

it('lets a new business upload a statement on step 1 and lists it as received but not verified', function (): void {
    $fixture = DraftFixture::make();
    $user = $fixture['authority']['users'][0];
    $business = $fixture['business']->id;
    $path = "/business/{$business}/applications/{$fixture['application']->id}";

    expect(statementUploadPage($user, $path))->toBe(['revision' => 0, 'documents' => [],
        'upload' => ['url' => "/business/{$business}/statements", 'method' => 'post']]);

    $this->actingAs($user)->from($path)->post("/business/{$business}/statements",
        statementUploadForm(UploadedFile::fake()->createWithContent('bank-august.pdf', StatementFixture::pdf()), 0))
        ->assertRedirect($path)->assertSessionHasNoErrors();

    $original = StatementOriginal::query()->sole();
    $statements = statementUploadPage($user, $path);
    expect($statements['revision'])->toBe(1)
        ->and($statements['documents'])->toHaveCount(1)
        ->and($statements['documents'][0])->toMatchArray(['id' => $original->id, 'media_type' => 'application/pdf', 'reading' => 'reading',
            'received_at' => '2026-09-25T03:00:00+00:00', 'link' => ['url' => "/business/{$business}/statements/{$original->id}", 'method' => 'get']]);

    $download = $this->get($statements['documents'][0]['link']['url'])->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($download->getContent())->toBe(StatementFixture::pdf())
        ->and($download->headers->get('Content-Disposition'))->toStartWith('attachment;');
});

it('shows a refused upload as a form error on the page without keeping anything', function (): void {
    $fixture = DraftFixture::make();
    $user = $fixture['authority']['users'][0];
    $business = $fixture['business']->id;
    $path = "/business/{$business}/applications/{$fixture['application']->id}";

    $this->actingAs($user)->from($path)->post("/business/{$business}/statements",
        statementUploadForm(UploadedFile::fake()->createWithContent('bank.pdf', 'not a PDF'), 0))
        ->assertRedirect($path)->assertSessionHasErrors(['file' => 'Upload an original PDF or a UTF-8 CSV export.']);
    StatementFixture::ingest($fixture);
    $this->from($path)->post("/business/{$business}/statements",
        statementUploadForm(UploadedFile::fake()->createWithContent('bank.csv', StatementFixture::csv('200')), 0))
        ->assertRedirect($path)->assertSessionHasErrors(['file' => 'Another statement arrived in the meantime. Check the list and upload again if it is missing.']);
    $request = (string) Str::uuid();
    StatementFixture::ingest($fixture, 1, $request, '300');
    $this->from($path)->post("/business/{$business}/statements",
        statementUploadForm(UploadedFile::fake()->createWithContent('bank.csv', StatementFixture::csv('400')), 2, $request), ['Accept' => 'application/json'])
        ->assertConflict()->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
    $this->from($path)->post("/business/{$business}/statements", ['identity_context_revision' => 1, 'expected_revision' => 2, 'request_id' => (string) Str::uuid()])
        ->assertSessionHasErrors('file');

    expect(StatementOriginal::query()->count())->toBe(2);
});

it('serves statements over the API with its own receipts and scopes', function (): void {
    $fixture = DraftFixture::make();
    $user = $fixture['authority']['users'][0];
    $business = $fixture['business']->id;

    Sanctum::actingAs($user, ['business:read']);
    $this->getJson("/api/v1/business/{$business}/statements")->assertOk()
        ->assertExactJson(['data' => ['revision' => 0, 'documents' => [], 'upload' => ['url' => "/api/v1/business/{$business}/statements", 'method' => 'post']]]);
    $this->post("/api/v1/business/{$business}/statements", statementUploadForm(UploadedFile::fake()->createWithContent('momo.csv', StatementFixture::csv()), 0),
        ['Accept' => 'application/json'])->assertForbidden();

    Sanctum::actingAs($user, ['business:read', 'business:command']);
    $receipt = $this->post("/api/v1/business/{$business}/statements", statementUploadForm(UploadedFile::fake()->createWithContent('momo.csv', StatementFixture::csv()), 0),
        ['Accept' => 'application/json'])->assertOk()->assertJsonPath('code', 'INGESTED_NOT_AUDIT_APPROVED')->json();
    $document = $receipt['data']['document_id'];
    $listed = $this->getJson("/api/v1/business/{$business}/statements")->assertOk()->json('data');
    expect($listed['revision'])->toBe(1)->and($listed['documents'][0]['link']['url'])->toBe("/api/v1/business/{$business}/statements/{$document}");
    expect($this->get($listed['documents'][0]['link']['url'])->assertOk()->getContent())->toBe(StatementFixture::csv());
    $this->post("/api/v1/business/{$business}/statements", statementUploadForm(UploadedFile::fake()->createWithContent('momo.txt', 'x'), 1),
        ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonPath('code', 'STATEMENT_TYPE_UNSUPPORTED');

    Sanctum::actingAs($user, ['investor:read']);
    $this->getJson("/api/v1/business/{$business}/statements")->assertForbidden();
    $this->getJson("/api/v1/business/{$business}/statements/{$document}")->assertForbidden();
});

it('keeps another business and a view-only member away from the originals', function (): void {
    $fixture = DraftFixture::make('organization', 2);
    $other = DraftFixture::make();
    $business = $fixture['business']->id;
    $document = StatementFixture::ingest($fixture)['data']['document_id'];
    $authority = $fixture['authority'];
    $authority['terms']['required_signatories'] = [$authority['people'][0]->id];
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $authority['people'][1]->id
        ? [...$person, 'permissions' => ['business.view']] : $person, $authority['terms']['people']);
    BusinessAuthorityFixture::configure($authority, 1);

    $viewer = $authority['users'][1];
    expect(statementUploadPage($viewer, "/business/{$business}/applications/{$fixture['application']->id}")['documents'])->toHaveCount(1);
    $this->actingAs($viewer)->get("/business/{$business}/statements/{$document}")->assertForbidden();
    $this->actingAs($viewer)->post("/business/{$business}/statements",
        statementUploadForm(UploadedFile::fake()->createWithContent('bank.csv', StatementFixture::csv('5')), 1), ['Accept' => 'application/json'])
        ->assertForbidden();
    $this->actingAs($other['authority']['users'][0])->get("/business/{$business}/statements/{$document}")->assertNotFound();
});
