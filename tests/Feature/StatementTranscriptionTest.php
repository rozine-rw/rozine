<?php

declare(strict_types=1);

use App\Application\Evidence\FindStatementOperation;
use App\Application\Evidence\GetStatementTranscription;
use App\Application\Evidence\GetStatementTranscriptionDraft;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\StatementEvidence;
use App\Models\StatementExtraction;
use App\Models\StatementOriginal;
use App\Models\StatementTranscription;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\StatementFixture;

it('requires original evidence before recording and leaves empty reads side effect free', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $actor = $fixture['authority']['users'][0];
    $input = StatementFixture::transcription('not-an-original');
    $request = (string) Str::uuid();
    $first = StatementFixture::transcribe($fixture, $input, 0, $request);
    expect($first['code'])->toBe('STATEMENT_ORIGINAL_REQUIRED')
        ->and(StatementFixture::transcribe($fixture, $input, 0, $request))->toBe($first)
        ->and(app(GetStatementTranscription::class)->handle($actor->id, 1, $fixture['business']->id))->toBeNull();
    $this->assertDatabaseCount('statement_evidence', 0);
    $this->assertDatabaseCount('statement_transcriptions', 0);
    expect(fn () => app(GetStatementTranscription::class)->handle($actor->id, 1, $fixture['business']->id, (string) Str::ulid()))
        ->toThrow(CommandRejection::class, 'STATEMENT_TRANSCRIPTION_NOT_FOUND')
        ->and(fn () => app(FindStatementOperation::class)->handle($actor->id, 1, $request, 'approve'))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('stores encrypted reconciled facts with canonical hashes and source lineage without asserting verification', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $source = StatementFixture::ingest($fixture);
    StatementFixture::ingest($fixture, 1, amount: '200');
    $documentId = $source['data']['document_id'];
    $input = StatementFixture::transcription($documentId);
    $receipt = StatementFixture::transcribe($fixture, $input, 2);
    $id = $receipt['data']['transcription']['id'];
    $record = app(GetStatementTranscription::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, $id);
    expect($receipt['code'])->toBe('STATEMENT_RECONCILED_UNVERIFIED')->and($receipt['revision'])->toBe(3)
        ->and($record['verified'])->toBeFalse()->and($record['current'])->toBeTrue()->and($record['amends_id'])->toBeNull()
        ->and($record['payload']['source_revision'])->toBe(2)
        ->and($record['payload']['observations'][0]['verified'])->toBeFalse()
        ->and($record['payload']['observations'][0]['operating_inflow'])->toBe('1000')
        ->and($record['payload']['source_hashes'])->toBe([$documentId => hash('sha256', StatementFixture::csv())])
        ->and($record['sha256'])->toBe(hash('sha256', app(CanonicalJson::class)->encode($record['payload'])));
    $stored = StatementTranscription::query()->whereKey($id)->firstOrFail();
    expect($stored->actor_user_id)->toBe($fixture['authority']['users'][0]->id)
        ->and($stored->actor_party_id)->toBe($fixture['authority']['people'][0]->id)
        ->and($stored->toArray())->not->toHaveKey('payload')
        ->and(DB::table('statement_transcriptions')->where('id', $id)->value('payload'))->not->toContain('operating_inflow')
        ->and(json_encode($receipt, JSON_THROW_ON_ERROR))->not->toContain('operating_inflow');
});

it('appends linked corrections and retains old exact retry results without restoring old evidence', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $source = StatementFixture::ingest($fixture);
    $input = StatementFixture::transcription($source['data']['document_id']);
    $request = (string) Str::uuid();
    $review = StatementFixture::reviewed($fixture['authority']['users'][0]->id, $fixture['business']->id);
    $first = StatementFixture::transcribe($fixture, $input, 1, $request, review: $review);
    $firstId = $first['data']['transcription']['id'];
    $changed = $input;
    $changed['statements'] = array_map(fn (array $statement): array => [...$statement, 'transactions' => array_map(
        fn (array $transaction): array => $transaction['reference'] === 'sales' ? [...$transaction, 'classification' => 'financing'] : $transaction,
        $statement['transactions'])], $input['statements']);
    $second = StatementFixture::transcribe($fixture, $changed, 2);
    $actor = $fixture['authority']['users'][0];
    $get = app(GetStatementTranscription::class);
    expect($second['data']['transcription']['amends_id'])->toBe($firstId)
        ->and($get->handle($actor->id, 1, $fixture['business']->id)['payload']['observations'][0]['operating_inflow'])->toBe('0')
        ->and($get->handle($actor->id, 1, $fixture['business']->id, $firstId)['current'])->toBeFalse()
        ->and($get->handle($actor->id, 1, $fixture['business']->id, $firstId)['payload']['observations'][0]['operating_inflow'])->toBe('1000')
        ->and(StatementFixture::transcribe($fixture, $input, 1, $request, review: $review))->toBe($first)
        ->and(app(FindStatementOperation::class)->handle($actor->id, 1, $request, 'reconcile'))->toBe($first)
        ->and(fn () => StatementFixture::transcribe($fixture, $changed, 1, $request, review: $review))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    $this->assertDatabaseCount('statement_transcriptions', 2);
    expect(StatementFixture::transcribe($fixture, $changed, 2)['code'])->toBe('VERSION_CONFLICT');
    StatementFixture::ingest($fixture, 3, amount: '300');
    expect($get->handle($actor->id, 1, $fixture['business']->id)['current'])->toBeFalse();
});

it('journals reconciliation denials without advancing source revisions or accepting foreign evidence', function (string $fault): void {
    $fixture = BusinessApplicationFixture::make();
    $source = StatementFixture::ingest($fixture);
    $input = StatementFixture::transcription($source['data']['document_id']);
    if ($fault === 'foreign source') {
        $other = BusinessApplicationFixture::make();
        $foreign = StatementFixture::ingest($other);
        $input = StatementFixture::transcription($foreign['data']['document_id']);
    } else {
        $input['statements'] = array_map(fn (array $statement): array => [...$statement, 'closing_balance' => '0'], $input['statements']);
    }
    $request = (string) Str::uuid();
    $receipt = StatementFixture::transcribe($fixture, $input, 1, $request);
    expect($receipt['code'])->toBe($fault === 'foreign source' ? 'STATEMENT_ORIGINAL_REQUIRED' : 'STATEMENT_RECONCILIATION_DIFFERENCE')
        ->and($receipt['http_status'])->toBe(422)
        ->and(StatementFixture::transcribe($fixture, $input, 1, $request))->toBe($receipt)
        ->and(StatementEvidence::query()->where('business_id', $fixture['business']->id)->firstOrFail()->revision)->toBe(1);
    $this->assertDatabaseCount('statement_transcriptions', 0);
})->with(['foreign source', 'difference']);

it('gives the representative a draft that binds the originals they review against', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $actor = $fixture['authority']['users'][0];
    $draft = app(GetStatementTranscriptionDraft::class);
    $empty = $draft->handle($actor->id, 1, $fixture['business']->id);
    expect($empty['evidence'])->toBe(['evidence_revision' => 0, 'manifest_sha256' => hash('sha256', app(CanonicalJson::class)->encode(
        ['version' => 'statement-manifest-1', 'evidence_revision' => 0, 'documents' => []])), 'originals' => [], 'extractions' => []])
        ->and($empty['allowed_actions'])->toBe([])->and($empty['current_transcription'])->toBeNull();
    $first = StatementFixture::ingest($fixture)['data']['document_id'];
    $second = StatementFixture::ingest($fixture, 1, amount: '200')['data']['document_id'];
    $read = $draft->handle($actor->id, 1, $fixture['business']->id);
    $ids = [$first, $second];
    sort($ids);
    $originals = StatementOriginal::query()->whereKey($ids)->get()->keyBy('id');
    $extractions = StatementExtraction::query()->whereIn('statement_original_id', $ids)->get()->keyBy('statement_original_id');
    expect($read['contract_version'])->toBe('initial-statement-audit-1')
        ->and($read['business_id'])->toBe($fixture['business']->id)
        ->and($read['business_revision'])->toBe($fixture['business']->refresh()->revision)
        ->and($read['mandate_version'])->toBe($fixture['business']->mandate_version)
        ->and($read['evidence']['evidence_revision'])->toBe(2)
        ->and($read['evidence']['originals'])->toBe(array_map(fn (string $id): array => ['document_id' => $id, 'sha256' => $originals[$id]->sha256], $ids))
        ->and($read['evidence']['extractions'])->toBe(array_map(fn (string $id): array => ['document_id' => $id, 'extraction_id' => $extractions[$id]->id,
            'revision' => 1, 'parser_version' => 'pending-1'], $ids))
        ->and($read['evidence']['manifest_sha256'])->toBe(hash('sha256', app(CanonicalJson::class)->encode(['version' => 'statement-manifest-1', 'evidence_revision' => 2,
            'documents' => array_map(fn (string $id): array => ['id' => $id, 'sha256' => $originals[$id]->sha256, 'media_type' => $originals[$id]->media_type,
                'size_bytes' => $originals[$id]->size_bytes], $ids)])))
        ->and(json_encode($read, JSON_THROW_ON_ERROR))->not->toContain('.csv')
        ->and($read['assistance'])->toBe(['status' => 'unavailable', 'reason_codes' => ['STRUCTURED_CONVERSION_UNAVAILABLE'], 'candidate' => null])
        ->and($read['allowed_actions'])->toBe(['statement.reconcile']);
    $saved = StatementFixture::transcribe($fixture, StatementFixture::transcription($first), 2);
    expect($draft->handle($actor->id, 1, $fixture['business']->id)['current_transcription']['id'])->toBe($saved['data']['transcription']['id']);
});

it('refuses a save that was not reviewed against the current Business, mandate, originals and transcription', function (string $fault): void {
    $fixture = BusinessApplicationFixture::make();
    $source = StatementFixture::ingest($fixture);
    $input = StatementFixture::transcription($source['data']['document_id']);
    $actor = $fixture['authority']['users'][0];
    $review = StatementFixture::reviewed($actor->id, $fixture['business']->id);
    $revision = 1;
    if ($fault === 'transcription') {
        StatementFixture::transcribe($fixture, $input);
        $review = [...StatementFixture::reviewed($actor->id, $fixture['business']->id), 'expected_transcription_id' => null, 'expected_transcription_sha256' => null];
        $revision = 2;
    }
    $review = match ($fault) {
        'acknowledgement' => [...$review, 'reviewed_against_originals' => false],
        'business' => [...$review, 'expected_business_revision' => $review['expected_business_revision'] + 1],
        'mandate' => [...$review, 'expected_mandate_version' => $review['expected_mandate_version'] + 1],
        'original' => [...$review, 'source_binding' => [...$review['source_binding'], 'originals' => [['document_id' => $source['data']['document_id'], 'sha256' => str_repeat('0', 64)]]]],
        'manifest' => [...$review, 'source_binding' => [...$review['source_binding'], 'manifest_sha256' => str_repeat('0', 64)]],
        'evidence' => [...$review, 'source_binding' => [...$review['source_binding'], 'evidence_revision' => 0]],
        default => $review,
    };
    if ($fault === 'extraction') {
        (new StatementExtraction)->forceFill(['statement_original_id' => $source['data']['document_id'], 'revision' => 2,
            'parser_version' => 'csv-1', 'status' => 'failed', 'reason_codes' => ['UNREADABLE'], 'text' => null, 'record_count' => null])->save();
    }
    $request = (string) Str::uuid();
    $receipt = StatementFixture::transcribe($fixture, $input, $revision, $request, review: $review);
    expect($receipt['code'])->toBe(match ($fault) {
        'acknowledgement' => 'STATEMENT_REVIEW_REQUIRED',
        'business' => 'BUSINESS_STALE',
        'mandate' => 'MANDATE_STALE',
        'transcription' => 'STATEMENT_TRANSCRIPTION_STALE',
        default => 'STATEMENT_SOURCE_CHANGED',
    })->and($receipt['http_status'])->toBe($fault === 'acknowledgement' ? 422 : 409)
        ->and(StatementFixture::transcribe($fixture, $input, $revision, $request, review: $review))->toBe($receipt)
        ->and(StatementEvidence::query()->where('business_id', $fixture['business']->id)->firstOrFail()->revision)->toBe($revision);
    $this->assertDatabaseCount('statement_transcriptions', $revision - 1);
})->with(['acknowledgement', 'business', 'mandate', 'original', 'manifest', 'evidence', 'extraction', 'transcription']);

it('replays a reviewed save exactly after its Business and mandate move on', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $source = StatementFixture::ingest($fixture);
    $input = StatementFixture::transcription($source['data']['document_id']);
    $review = StatementFixture::reviewed($fixture['authority']['users'][0]->id, $fixture['business']->id);
    $request = (string) Str::uuid();
    $receipt = StatementFixture::transcribe($fixture, $input, 1, $request, review: $review);
    BusinessAuthorityFixture::configure($fixture['authority'], 1);
    expect($fixture['business']->refresh()->mandate_version)->not->toBe($review['expected_mandate_version'])
        ->and(StatementFixture::transcribe($fixture, $input, 1, $request, review: $review))->toBe($receipt)
        ->and(StatementFixture::transcribe($fixture, $input, 2, review: $review)['code'])->toBe('BUSINESS_STALE');
});

it('rolls back the transcription and journal together if persistence fails unexpectedly', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $source = StatementFixture::ingest($fixture);
    $input = StatementFixture::transcription($source['data']['document_id']);
    $event = 'eloquent.created: '.StatementTranscription::class;
    Event::listen($event, fn () => throw new RuntimeException('Synthetic persistence failure.'));
    try {
        expect(fn () => StatementFixture::transcribe($fixture, $input))->toThrow(RuntimeException::class, 'Synthetic persistence failure.');
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('statement_transcriptions', 0);
    expect(StatementEvidence::query()->firstOrFail()->revision)->toBe(1)
        ->and(CommandOperation::query()->where('command', 'statement.reconcile')->count())->toBe(0);
});

it('requires current Business authority for recording, history reads and operation lookup', function (): void {
    $fixture = BusinessApplicationFixture::make('organization', 2);
    $source = StatementFixture::ingest($fixture);
    $input = StatementFixture::transcription($source['data']['document_id']);
    $request = (string) Str::uuid();
    $review = StatementFixture::reviewed($fixture['authority']['users'][0]->id, $fixture['business']->id);
    $receipt = StatementFixture::transcribe($fixture, $input, 1, $request, review: $review);
    $id = $receipt['data']['transcription']['id'];
    $other = BusinessApplicationFixture::make();
    $get = app(GetStatementTranscription::class);
    expect(fn () => $get->handle($other['authority']['users'][0]->id, 1, $fixture['business']->id, $id))
        ->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND')
        ->and(fn () => $get->handle($other['authority']['users'][0]->id, 1, $other['business']->id, $id))
        ->toThrow(CommandRejection::class, 'STATEMENT_TRANSCRIPTION_NOT_FOUND');
    $authority = $fixture['authority'];
    $authority['terms']['required_signatories'] = [$authority['people'][0]->id];
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $authority['people'][1]->id
        ? [...$person, 'permissions' => ['business.view']] : $person, $authority['terms']['people']);
    BusinessAuthorityFixture::configure($authority, 1);
    expect(fn () => $get->handle($authority['users'][1]->id, 1, $fixture['business']->id, $id))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN')
        ->and(fn () => StatementFixture::transcribe($fixture, $input, 2, actor: 1, review: $review))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN')
        ->and(fn () => app(GetStatementTranscriptionDraft::class)->handle($authority['users'][1]->id, 1, $fixture['business']->id))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN');
    $authority['terms']['status'] = 'revoked';
    BusinessAuthorityFixture::configure($authority, 2);
    expect(fn () => StatementFixture::transcribe($fixture, $input, 1, $request, review: $review))->toThrow(CommandRejection::class, 'MANDATE_REQUIRED')
        ->and(fn () => $get->handle($authority['users'][0]->id, 1, $fixture['business']->id, $id))->toThrow(CommandRejection::class, 'MANDATE_REQUIRED')
        ->and(fn () => app(FindStatementOperation::class)->handle($authority['users'][0]->id, 1, $request, 'reconcile'))->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
});

it('fails closed on a corrupted transcription digest or mismatched Business provenance', function (string $fault): void {
    $fixture = BusinessApplicationFixture::make();
    $evidence = StatementEvidence::factory()->create(['business_id' => $fixture['business']->id]);
    $record = StatementTranscription::factory()->make(['statement_evidence_id' => $evidence->id]);
    if ($fault === 'hash') {
        $record->sha256 = str_repeat('0', 64);
    } else {
        $payload = [...$record->payload, 'business_id' => 'different-business'];
        $record->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
    }
    $record->save();
    expect(fn () => app(GetStatementTranscription::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, $record->id))
        ->toThrow(RuntimeException::class, 'Statement transcription integrity check failed.');
})->with(['hash', 'business']);

it('protects transcription history against database updates and deletes', function (string $operation): void {
    $record = StatementTranscription::factory()->create();
    expect(fn () => DB::transaction(fn (): mixed => $operation === 'update'
        ? $record->forceFill(['payload' => []])->save() : $record->delete()))
        ->toThrow(QueryException::class, 'Statement transcriptions are immutable');
})->with(['update', 'delete']);
