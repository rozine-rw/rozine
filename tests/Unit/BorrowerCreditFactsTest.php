<?php

declare(strict_types=1);

use App\Domain\Operations\CommandRejection;
use App\Domain\Underwriting\BorrowerCreditFacts;
use Tests\Support\BusinessCreditFactsFixture;
use Tests\Support\UnderwritingEvidenceFixture;

it('preserves explicitly unknown history and factual adverse or repeat conduct', function (): void {
    $normalizer = new BorrowerCreditFacts;
    $facts = BusinessCreditFactsFixture::facts();
    expect($normalizer->normalize($facts))->toBe($facts);
    $facts['history'] = null;
    expect($normalizer->normalize($facts)['history'])->toBeNull();
    $facts['history'] = [...UnderwritingEvidenceFixture::application()['history'], 'has_rozine_history' => true,
        'repeat_eligibility' => UnderwritingEvidenceFixture::repeat(), 'instalment_conduct' => ['on_time' => 2, 'total' => 3],
        'report_conduct' => ['on_time' => 0, 'total' => 1], 'defaulted' => true, 'days_past_due' => 30];
    $facts['history']['repeat_eligibility']['late_payments'] = 1;
    $facts['restriction_active'] = true;
    expect($normalizer->normalize($facts))->toBe($facts);
});

it('normalizes obligation ordering without rounding or inventing a missing schedule', function (): void {
    $facts = BusinessCreditFactsFixture::facts();
    $facts['obligations'] = [
        ['id' => 'platform:second', 'principal' => '999999999999999999999999', 'service_by_month' => ['2027-01' => '2', '2026-12' => '1']],
        ['id' => 'bank:first', 'principal' => '3', 'service_by_month' => ['2026-12' => '0']],
    ];
    $result = (new BorrowerCreditFacts)->normalize($facts);
    expect(array_column($result['obligations'], 'id'))->toBe(['bank:first', 'platform:second'])
        ->and($result['obligations'][1]['principal'])->toBe('999999999999999999999999')
        ->and($result['obligations'][1]['service_by_month'])->toBe(['2026-12' => '1', '2027-01' => '2']);
});

it('rejects malformed source facts before they can support a quote', function (string $path, mixed $value): void {
    $facts = BusinessCreditFactsFixture::facts();
    $facts['history'] = [...UnderwritingEvidenceFixture::application()['history'], 'has_rozine_history' => true,
        'repeat_eligibility' => UnderwritingEvidenceFixture::repeat(), 'instalment_conduct' => ['on_time' => 1, 'total' => 2]];
    $facts['obligations'] = [['id' => 'note:one', 'principal' => '100', 'service_by_month' => ['2026-09' => '10']]];
    data_set($facts, $path, $value);
    expect(fn () => (new BorrowerCreditFacts)->normalize($facts))->toThrow(CommandRejection::class, 'CREDIT_FACTS_INVALID');
})->with([
    'unknown root field' => ['score', 99], 'not a list' => ['obligations', ['key' => []]],
    'not an array' => ['obligations', 'unknown'], 'invalid restriction' => ['restriction_active', 1],
    'invalid obligation' => ['obligations.0', null], 'unknown obligation field' => ['obligations.0.extra', true],
    'invalid id' => ['obligations.0.id', 1], 'empty id' => ['obligations.0.id', ' '],
    'long id' => ['obligations.0.id', str_repeat('x', 256)], 'control id' => ['obligations.0.id', "note\n"],
    'invalid encoding' => ['obligations.0.id', "\xff"], 'invalid schedule' => ['obligations.0.service_by_month', 1],
    'missing schedule' => ['obligations.0.service_by_month', []], 'integer month' => ['obligations.0.service_by_month', ['10']],
    'bad month' => ['obligations.0.service_by_month', ['2026-13' => '10']], 'bad month amount' => ['obligations.0.service_by_month', ['2026-09' => 10]],
    'negative principal' => ['obligations.0.principal', '-1'], 'decimal principal' => ['obligations.0.principal', '1.1'],
    'leading zero principal' => ['obligations.0.principal', '0100'], 'float principal' => ['obligations.0.principal', 100.0],
    'invalid history' => ['history', false], 'unknown history field' => ['history.extra', true],
    'invalid history boolean' => ['history.defaulted', 'false'], 'noninteger days' => ['history.days_past_due', '0'],
    'negative days' => ['history.days_past_due', -1], 'invalid repeat' => ['history.repeat_eligibility', 1],
    'unknown repeat field' => ['history.repeat_eligibility.extra', true], 'invalid repeat boolean' => ['history.repeat_eligibility.baseline_passed', 1],
    'noninteger notes' => ['history.repeat_eligibility.settled_notes', '1'], 'negative late payments' => ['history.repeat_eligibility.late_payments', -1],
    'invalid conduct' => ['history.instalment_conduct', false], 'unknown conduct field' => ['history.instalment_conduct.extra', true],
    'noninteger conduct' => ['history.instalment_conduct.on_time', 1.0], 'negative conduct' => ['history.instalment_conduct.on_time', -1],
    'impossible conduct' => ['history.instalment_conduct.total', 0], 'negative total' => ['history.instalment_conduct', ['on_time' => 0, 'total' => -1]],
]);

it('refuses duplicated obligations and contradictory first-time history', function (): void {
    $facts = BusinessCreditFactsFixture::facts();
    $obligation = ['id' => 'note:one', 'principal' => '100', 'service_by_month' => ['2026-09' => '10']];
    expect(fn () => (new BorrowerCreditFacts)->normalize([...$facts, 'obligations' => [$obligation, $obligation]]))
        ->toThrow(CommandRejection::class, 'CREDIT_FACTS_INVALID');
    $facts['history'] = [...UnderwritingEvidenceFixture::application()['history'], 'days_past_due' => 1];
    expect(fn () => (new BorrowerCreditFacts)->normalize($facts))->toThrow(CommandRejection::class, 'CREDIT_FACTS_INVALID');
    unset($facts['history']);
    expect(fn () => (new BorrowerCreditFacts)->normalize($facts))->toThrow(CommandRejection::class, 'CREDIT_FACTS_INVALID');
});
