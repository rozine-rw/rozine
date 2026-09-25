<?php

declare(strict_types=1);

use App\Domain\Business\ApplicationDraft;
use App\Domain\Operations\CommandRejection;
use Tests\Support\BusinessApplicationFixture;

it('preserves exact whole franc requests and incomplete draft facts', function (): void {
    $draft = new ApplicationDraft;
    expect($draft->normalize($draft->empty()))->toBe($draft->empty());
    $fields = BusinessApplicationFixture::fields('9007199254740993');
    $fields['title'] = '  Résumé  ';
    expect($draft->normalize($fields)['target'])->toBe('9007199254740993')
        ->and($draft->normalize($fields)['title'])->toBe('Résumé');
});

it('rejects ambiguous numeric input unsupported terms and prohibited draft fields', function (string $case): void {
    $fields = BusinessApplicationFixture::fields();
    switch ($case) {
        case 'leading zero': $fields['target'] = '08000000';
            break;
        case 'fraction': $fields['target'] = '8000000.5';
            break;
        case 'exponent': $fields['target'] = '8e6';
            break;
        case 'negative': $fields['target'] = '-1';
            break;
        case 'too large': $fields['target'] = '1000000000000000000';
            break;
        case 'unsupported term': $fields['term_months'] = 12;
            break;
        case 'unknown use': $fields['use_of_funds'] = ['unsupported'];
            break;
        case 'duplicate use': $fields['use_of_funds'] = ['equipment', 'equipment'];
            break;
        case 'many uses': $fields['use_of_funds'] = array_fill(0, 7, 'equipment');
            break;
        case 'long title': $fields['title'] = str_repeat('x', 181);
            break;
        case 'long story': $fields['story'] = str_repeat('x', 10001);
            break;
        case 'tax identifier': $fields['tax_identifier'] = 'prohibited';
            break;
    }
    expect(fn () => (new ApplicationDraft)->normalize($fields))->toThrow(CommandRejection::class, 'APPLICATION_INPUT_INVALID');
})->with(['leading zero', 'fraction', 'exponent', 'negative', 'too large', 'unsupported term', 'unknown use', 'duplicate use', 'many uses', 'long title', 'long story', 'tax identifier']);

it('requires the exact draft revision and stops editing a submitted application', function (): void {
    $draft = new ApplicationDraft;
    $draft->assertEditable('draft', 2, 2);
    expect(fn () => $draft->assertEditable('draft', 2, 1))->toThrow(CommandRejection::class, 'VERSION_CONFLICT')
        ->and(fn () => $draft->assertEditable('submitted', 2, 2))->toThrow(CommandRejection::class, 'APPLICATION_NOT_EDITABLE');
});
