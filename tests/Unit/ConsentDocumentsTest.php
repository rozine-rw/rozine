<?php

declare(strict_types=1);

use App\Domain\Identity\ConsentDocuments;
use App\Domain\Operations\CommandRejection;
use Tests\Support\ConsentFixture;

it('binds consent hashes to the exact original text and matches every explicit acceptance', function (): void {
    $policy = new ConsentDocuments;
    $documents = $policy->documents(ConsentFixture::documents());
    $disclosures = $policy->disclosures(ConsentFixture::disclosures());
    expect(array_column($documents, 'kind'))->toBe(['privacy', 'terms'])
        ->and($documents[1]['sha256'])->toBe(hash('sha256', 'Synthetic terms. Test use only.'))
        ->and($disclosures[0]['sha256'])->toBe(hash('sha256', 'Another isolated disclosure.'));
    $acceptedDocuments = array_map(fn (array $row): array => ['sha256' => $row['sha256'], 'version' => $row['version'], 'kind' => $row['kind']], array_reverse($documents));
    $acceptedDisclosures = array_map(fn (array $row): array => ['sha256' => $row['sha256'], 'key' => $row['key'], 'version' => $row['version']], array_reverse($disclosures));
    $policy->assertAccepted($documents, $disclosures, $acceptedDocuments, $acceptedDisclosures);
});

it('rejects incomplete or changed consent echoes rather than coercing versions or hashes', function (string $case): void {
    $policy = new ConsentDocuments;
    $documents = $policy->documents(ConsentFixture::documents('1'));
    $disclosures = $policy->disclosures(ConsentFixture::disclosures('1'));
    $acceptedDocuments = array_map(fn (array $row): array => array_intersect_key($row, array_flip(['kind', 'version', 'sha256'])), $documents);
    $acceptedDisclosures = array_map(fn (array $row): array => array_intersect_key($row, array_flip(['key', 'version', 'sha256'])), $disclosures);
    switch ($case) {
        case 'omitted document': array_pop($acceptedDocuments);
            break;
        case 'duplicate document': $acceptedDocuments[] = $acceptedDocuments[0];
            break;
        case 'changed hash': $acceptedDocuments = array_map(fn (array $row): array => [...$row, 'sha256' => str_repeat('0', 64)], $acceptedDocuments);
            break;
        case 'numeric version': $acceptedDocuments = array_map(fn (array $row): array => [...$row, 'version' => '01'], $acceptedDocuments);
            break;
        case 'omitted disclosure': array_pop($acceptedDisclosures);
            break;
        case 'changed disclosure': $acceptedDisclosures = array_map(fn (array $row): array => [...$row, 'version' => 'old'], $acceptedDisclosures);
            break;
    }
    expect(fn () => $policy->assertAccepted($documents, $disclosures, $acceptedDocuments, $acceptedDisclosures))
        ->toThrow(CommandRejection::class, 'DOCUMENT_VERSION_STALE');
})->with(['omitted document', 'duplicate document', 'changed hash', 'numeric version', 'omitted disclosure', 'changed disclosure']);

it('refuses incomplete or ambiguous legal document definitions', function (string $case): void {
    $documents = ConsentFixture::documents();
    switch ($case) {
        case 'missing terms': array_shift($documents);
            break;
        case 'duplicate kind': $documents[1]['kind'] = 'terms';
            break;
        case 'unknown field': $documents[0]['sha256'] = str_repeat('a', 64);
            break;
        case 'bad version': $documents[0]['version'] = 'not a version';
            break;
        case 'blank body': $documents[0]['body'] = ' ';
            break;
        case 'long body': $documents[0]['body'] = str_repeat('x', 200001);
            break;
        case 'invalid utf8': $documents[0]['body'] = "\xC3\x28";
            break;
        case 'missing summary': $documents[0]['summary'] = [];
            break;
        case 'too many clauses': $documents[0]['summary'] = array_fill(0, 31, ['heading' => 'x', 'body' => 'x']);
            break;
        case 'invalid clause': $documents[0]['summary'] = [['heading' => 'x', 'body' => 'x', 'other' => 'x']];
            break;
        case 'blank heading': $documents[0]['summary'] = [['heading' => '', 'body' => 'x']];
            break;
        case 'blank clause': $documents[0]['summary'] = [['heading' => 'x', 'body' => '']];
            break;
    }
    expect(fn () => (new ConsentDocuments)->documents($documents))->toThrow(CommandRejection::class, 'CONSENT_DOCUMENTS_INVALID');
})->with(['missing terms', 'duplicate kind', 'unknown field', 'bad version', 'blank body', 'long body', 'invalid utf8', 'missing summary', 'too many clauses', 'invalid clause', 'blank heading', 'blank clause']);

it('refuses missing or ambiguous mandatory disclosures', function (string $case): void {
    $disclosures = ConsentFixture::disclosures();
    switch ($case) {
        case 'empty': $disclosures = [];
            break;
        case 'many': $disclosures = array_fill(0, 31, $disclosures[0]);
            break;
        case 'duplicate': $disclosures[] = $disclosures[0];
            break;
        case 'unknown field': $disclosures[0]['unexpected'] = true;
            break;
        case 'bad key': $disclosures[0]['key'] = 'bad key';
            break;
        case 'blank text': $disclosures[0]['text'] = '';
            break;
    }
    expect(fn () => (new ConsentDocuments)->disclosures($disclosures))->toThrow(CommandRejection::class, 'CONSENT_DOCUMENTS_INVALID');
})->with(['empty', 'many', 'duplicate', 'unknown field', 'bad key', 'blank text']);
