<?php

declare(strict_types=1);

use App\Application\Evidence\Contracts\StatementTextExtractor;
use App\Application\Evidence\FindStatementOperation;
use App\Application\Evidence\GetStatementEvidence;
use App\Application\Evidence\IngestStatement;
use App\Application\Evidence\ReadStatementOriginal;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use App\Models\StatementEvidence;
use App\Models\StatementExtraction;
use App\Models\StatementOriginal;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\StatementFixture;

it('starts with missing evidence without inventing observations or mutating state on read', function (): void {
    $fixture = BusinessApplicationFixture::make();
    expect(app(GetStatementEvidence::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id))
        ->toBe(['revision' => 0, 'documents' => []]);
    $this->assertDatabaseCount('statement_evidence', 0);
});

it('atomically retains encrypted originals and extraction lineage with replayable receipts', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    $result = StatementFixture::ingest($fixture, requestId: $request);
    $original = StatementOriginal::query()->firstOrFail();
    $extraction = StatementExtraction::query()->firstOrFail();
    expect($result['code'])->toBe('INGESTED_NOT_AUDIT_APPROVED')
        ->and(StatementFixture::ingest($fixture, requestId: $request))->toBe($result)
        ->and(app(FindStatementOperation::class)->handle($fixture['authority']['users'][0]->id, 1, $request))->toBe($result)
        ->and($original->content)->toBe(StatementFixture::csv())
        ->and($original->getRawOriginal('content'))->not->toContain('SYNTHETIC-ONLY')
        ->and($extraction->getRawOriginal('text'))->not->toContain('SYNTHETIC-ONLY')
        ->and($extraction->text)->toBe(StatementFixture::csv())
        ->and($original->toArray())->not->toHaveKey('content')
        ->and($extraction->toArray())->not->toHaveKey('text')
        ->and($result['data']['evidence']['documents'][0]['extraction']['status'])->toBe('text_extracted')
        ->and(json_encode($result))->not->toContain('SYNTHETIC-ONLY');
    $download = app(ReadStatementOriginal::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, $original->id);
    expect($download['content'])->toBe(StatementFixture::csv())->and($download['sha256'])->toBe(hash('sha256', $download['content']));
    $this->assertDatabaseCount('statement_originals', 1);
    $this->assertDatabaseCount('statement_extractions', 1);
});

it('retains non-UTF8 PDF bytes exactly through encrypted storage and authorized reads', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $content = StatementFixture::pdf();
    expect(mb_check_encoding($content, 'UTF-8'))->toBeFalse();
    $result = app(IngestStatement::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, 0,
        'original.pdf', $content, (string) Str::uuid());
    $original = app(ReadStatementOriginal::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, $result['data']['document_id']);
    expect($original['content'])->toBe($content)
        ->and($result['data']['evidence']['documents'][0]['extraction']['status'])->toBe('text_extracted');
});

it('deduplicates original bytes and preserves superseded source bytes when new evidence arrives', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    $first = StatementFixture::ingest($fixture, requestId: $request);
    expect(StatementFixture::ingest($fixture, 1)['revision'])->toBe(1);
    $second = StatementFixture::ingest($fixture, 1, amount: '200');
    expect($second['revision'])->toBe(2)->and($second['data']['evidence']['documents'])->toHaveCount(2)
        ->and(StatementFixture::ingest($fixture, requestId: $request))->toBe($first)
        ->and(fn () => StatementFixture::ingest($fixture, requestId: $request, amount: '300'))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(StatementFixture::ingest($fixture, 1, amount: '300')['code'])->toBe('VERSION_CONFLICT');
    expect(app(GetStatementEvidence::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id)['revision'])->toBe(2);
    $this->assertDatabaseCount('statement_originals', 2);
});

it('journals invalid input without retaining a partial original or evidence aggregate', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $result = app(IngestStatement::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, 0,
        'source.pdf', 'not a PDF', (string) Str::uuid());
    expect($result['code'])->toBe('STATEMENT_TYPE_UNSUPPORTED')->and($result['http_status'])->toBe(422)
        ->and($result['field_errors'])->toHaveKey('file');
    $this->assertDatabaseCount('statement_evidence', 0);
    $this->assertDatabaseCount('statement_originals', 0);
});

it('retains an unreadable original for review without approving or fabricating its contents', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $content = '%PDF-1.7 broken original';
    $result = app(IngestStatement::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, 0,
        'original.pdf', $content, (string) Str::uuid());
    expect($result['code'])->toBe('INGESTED_NOT_AUDIT_APPROVED')
        ->and($result['data']['evidence']['documents'][0]['extraction']['reason_codes'])->toBe(['PDF_TEXT_UNAVAILABLE'])
        ->and(StatementOriginal::query()->firstOrFail()->content)->toBe($content);
});

it('rolls back source storage and outcomes on unexpected extraction failures so retry can recover', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    $real = app(StatementTextExtractor::class);
    $extractor = $this->createMock(StatementTextExtractor::class);
    $extractor->method('extract')->willThrowException(new RuntimeException('synthetic worker failure'));
    $this->app->instance(StatementTextExtractor::class, $extractor);
    expect(fn () => StatementFixture::ingest($fixture, requestId: $request))->toThrow(RuntimeException::class, 'synthetic worker failure');
    $this->assertDatabaseCount('statement_evidence', 0);
    expect(CommandOperation::query()->where('command', 'statement.ingest')->count())->toBe(0);
    $this->app->instance(StatementTextExtractor::class, $real);
    expect(StatementFixture::ingest($fixture, requestId: $request)['code'])->toBe('INGESTED_NOT_AUDIT_APPROVED');
});

it('scopes original downloads manifests and outcomes to current authority', function (): void {
    $one = BusinessApplicationFixture::make();
    $two = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    $result = StatementFixture::ingest($one, requestId: $request);
    $id = $result['data']['document_id'];
    expect(fn () => app(GetStatementEvidence::class)->handle($two['authority']['users'][0]->id, 1, $one['business']->id))
        ->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND')
        ->and(fn () => app(ReadStatementOriginal::class)->handle($two['authority']['users'][0]->id, 1, $two['business']->id, $id))
        ->toThrow(CommandRejection::class, 'STATEMENT_NOT_FOUND');
    $authority = $one['authority'];
    $authority['terms']['status'] = 'revoked';
    BusinessAuthorityFixture::configure($authority, 1);
    expect(fn () => StatementFixture::ingest($one, requestId: $request))->toThrow(CommandRejection::class, 'MANDATE_REQUIRED')
        ->and(fn () => app(ReadStatementOriginal::class)->handle($authority['users'][0]->id, 1, $one['business']->id, $id))
        ->toThrow(CommandRejection::class, 'MANDATE_REQUIRED')
        ->and(fn () => app(FindStatementOperation::class)->handle($authority['users'][0]->id, 1, $request))
        ->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
});

it('does not let a view-only mandate upload evidence', function (): void {
    $fixture = BusinessApplicationFixture::make('organization', 2);
    $authority = $fixture['authority'];
    $authority['terms']['required_signatories'] = [$authority['people'][0]->id];
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $authority['people'][1]->id
        ? [...$person, 'permissions' => ['business.view']] : $person, $authority['terms']['people']);
    BusinessAuthorityFixture::configure($authority, 1);
    expect(fn () => StatementFixture::ingest($fixture, actor: 1))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN');
});

it('fails closed on stored original integrity or incomplete extraction lineage', function (string $fault): void {
    $fixture = BusinessApplicationFixture::make();
    $evidence = StatementEvidence::factory()->create(['business_id' => $fixture['business']->id]);
    $original = StatementOriginal::factory()->create(['statement_evidence_id' => $evidence->id,
        ...($fault === 'hash' ? ['sha256' => str_repeat('0', 64)] : []),
        ...($fault === 'size' ? ['size_bytes' => 1] : [])]);
    if ($fault === 'extraction') {
        expect(fn () => app(GetStatementEvidence::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id))
            ->toThrow(RuntimeException::class, 'Statement extraction is missing.');
    } else {
        expect(fn () => app(ReadStatementOriginal::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, $original->id))
            ->toThrow(RuntimeException::class, 'Statement original integrity check failed.');
    }
})->with(['hash', 'size', 'extraction']);

it('protects original source and extraction records from updates or deletes', function (string $model, string $operation): void {
    $record = $model === 'original' ? StatementOriginal::factory()->create() : StatementExtraction::factory()->create();
    expect(fn () => DB::transaction(fn (): mixed => $operation === 'update'
        ? $record->forceFill(['created_at' => now()->addDay()])->save() : $record->delete()))
        ->toThrow(QueryException::class, 'Statement originals and extractions are immutable');
})->with(['original', 'extraction'])->with(['update', 'delete']);

it('refuses unlinked identities and journal records outside the statement business scope', function (): void {
    expect(fn () => app(FindStatementOperation::class)->handle(User::factory()->create()->id, 0, (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'IDENTITY_NOT_LINKED');
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    CommandOperation::factory()->create(['actor_key' => 'party:'.$fixture['authority']['people'][0]->id, 'command' => 'statement.ingest',
        'request_id' => $request, 'target_type' => 'other', 'target_id' => $fixture['business']->id]);
    expect(fn () => app(FindStatementOperation::class)->handle($fixture['authority']['users'][0]->id, 1, $request))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('does not reveal a previous Party receipt when identity changes before locked lookup', function (): void {
    $fixture = BusinessApplicationFixture::make('organization', 2);
    $request = (string) Str::uuid();
    StatementFixture::ingest($fixture, requestId: $request);
    $user = $fixture['authority']['users'][0];
    $nextParty = $fixture['authority']['people'][1];
    $nextMembership = RoleMembership::query()->where('party_id', $nextParty->id)->firstOrFail();
    $repository = app(IdentityRepository::class);
    $calls = 0;
    $mock = $this->createMock(IdentityRepository::class);
    $mock->method('forUser')->willReturnCallback(function (int $id) use ($repository, $user, $nextParty, $nextMembership, &$calls): array {
        $snapshot = $repository->forUser($id);
        if ($calls++ === 0) {
            $user->forceFill(['party_id' => $nextParty->id, 'active_membership_id' => $nextMembership->id])->save();
        }

        return $snapshot;
    });
    $this->app->instance(IdentityRepository::class, $mock);
    expect(fn () => app(FindStatementOperation::class)->handle($user->id, 1, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});
