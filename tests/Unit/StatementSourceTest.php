<?php

declare(strict_types=1);

use App\Domain\Evidence\StatementSource;
use App\Domain\Operations\CommandRejection;
use Tests\Support\StatementFixture;

it('describes the exact original bytes without trusting an upload content type', function (): void {
    $source = new StatementSource;
    $csv = StatementFixture::csv();
    expect($source->describe('EXAMPLE.CSV', $csv))->toBe(['filename' => 'EXAMPLE.CSV', 'media_type' => 'text/csv',
        'size_bytes' => strlen($csv), 'sha256' => hash('sha256', $csv)])
        ->and($source->describe('original.pdf', StatementFixture::pdf())['media_type'])->toBe('application/pdf');
});

it('rejects invalid statement names bytes and formats', function (string $filename, string $content, string $reason): void {
    expect(fn () => (new StatementSource)->describe($filename, $content))->toThrow(CommandRejection::class, $reason);
})->with([
    'empty filename' => ['', 'a,b', 'STATEMENT_FILENAME_INVALID'],
    'path' => ['../source.csv', 'a,b', 'STATEMENT_FILENAME_INVALID'],
    'windows path' => ['folder\\source.csv', 'a,b', 'STATEMENT_FILENAME_INVALID'],
    'header injection' => ["source\n.csv", 'a,b', 'STATEMENT_FILENAME_INVALID'],
    'long name' => [str_repeat('a', 181), 'a,b', 'STATEMENT_FILENAME_INVALID'],
    'invalid filename encoding' => ["\xff.csv", 'a,b', 'STATEMENT_FILENAME_INVALID'],
    'empty original' => ['source.csv', '', 'STATEMENT_SIZE_INVALID'],
    'over size' => ['source.csv', str_repeat('x', StatementSource::MAX_BYTES + 1), 'STATEMENT_SIZE_INVALID'],
    'renamed text' => ['source.pdf', 'a,b', 'STATEMENT_TYPE_UNSUPPORTED'],
    'binary as csv' => ['source.csv', "a,\x00b", 'STATEMENT_TYPE_UNSUPPORTED'],
    'non utf8' => ['source.csv', "a,\xff", 'STATEMENT_TYPE_UNSUPPORTED'],
    'unsupported extension' => ['source.html', 'a,b', 'STATEMENT_TYPE_UNSUPPORTED'],
    'renamed pdf' => ['source.csv', '%PDF-1.7', 'STATEMENT_TYPE_UNSUPPORTED'],
]);
