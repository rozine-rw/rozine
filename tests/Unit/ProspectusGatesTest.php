<?php

declare(strict_types=1);

use App\Domain\Business\ProspectusGates;

/**
 * A prospectus for RWF 12,000,000 over 6 months: the 3% platform fee is 360,000, so use-of-funds lines
 * must total 11,640,000. The verified monthly revenue is 7,000,000 and the instalment 2,280,000.
 *
 * @return array{0: array{purpose: string, use_of_funds: array<int, array{label: string, amount: string, quote_amount: string|null, quote_document_id: string|null}>, forecast: array<int, array{month: string, revenue: string, costs: string, contract_document_id: string|null}>, assumptions: string, downside: string, profiles: array<int, array{name: string, role: string, text: string}>, largest_customer_share_percent: int|null, risks: array<int, array{text: string, concentration: bool}>}, 1: array{principal: string, term_months: int, instalment: string, verified_monthly_revenue: string, latest_sealed_report_at: string|null, registered_names: list<string>, required_people: list<array{name: string, role: string}>, evidence: array<string, string>, signed: bool}}
 */
function prospectusCase(): array
{
    $profile = fn (string $name, string $role): array => ['name' => $name, 'role' => $role, 'text' => trim(str_repeat('Runs the shop floor and supplier accounts. ', 8))];
    $month = fn (string $month): array => ['month' => $month, 'revenue' => '7000000', 'costs' => '3000000', 'contract_document_id' => null];
    $inputs = [
        'purpose' => 'Buy a second cold room so we can hold three weeks of stock for hotel customers in Kigali during the busy season.',
        'use_of_funds' => [['label' => 'Cold room', 'amount' => '9640000', 'quote_amount' => '9700000', 'quote_document_id' => '01jc0quote0000000000000000'], ['label' => 'Installation', 'amount' => '2000000', 'quote_amount' => null, 'quote_document_id' => null]],
        'forecast' => array_map($month, ['2026-12', '2027-01', '2027-02', '2027-03', '2027-04', '2027-05']),
        'assumptions' => 'Hotel orders stay at the level verified over the last three months.',
        'downside' => 'If orders fall, we pause the second delivery van and repay from the existing cold room.',
        'profiles' => [$profile('Aline Uwase', 'representative'), $profile('Jean Mugabo', 'director')],
        'largest_customer_share_percent' => 18,
        'risks' => [['text' => 'Power cuts can spoil stock.', 'concentration' => false], ['text' => 'Hotel demand is seasonal.', 'concentration' => false],
            ['text' => 'Supplier prices can rise.', 'concentration' => false]],
    ];
    $facts = ['principal' => '12000000', 'term_months' => 6, 'instalment' => '2280000', 'verified_monthly_revenue' => '7000000',
        'latest_sealed_report_at' => '2026-11-01T10:00:00Z', 'registered_names' => ['Aline  Uwase', 'jean mugabo'],
        'required_people' => [['name' => 'Aline Uwase', 'role' => 'representative'], ['name' => 'JEAN MUGABO', 'role' => 'director']],
        'evidence' => ['01jc0quote0000000000000000' => 'quote', '01jc0contract0000000000000' => 'contract'], 'signed' => false];

    return [$inputs, $facts];
}

/**
 * @param  array{purpose: string, use_of_funds: array<int, array{label: string, amount: string, quote_amount: string|null, quote_document_id: string|null}>, forecast: array<int, array{month: string, revenue: string, costs: string, contract_document_id: string|null}>, assumptions: string, downside: string, profiles: array<int, array{name: string, role: string, text: string}>, largest_customer_share_percent: int|null, risks: array<int, array{text: string, concentration: bool}>}  $inputs
 * @param  array{principal: string, term_months: int, instalment: string, verified_monthly_revenue: string, latest_sealed_report_at: string|null, registered_names: list<string>, required_people: list<array{name: string, role: string}>, evidence: array<string, string>, signed: bool}  $facts
 * @return array<string, array{id: string, section: string, passed: bool, fix: string|null}>
 */
function prospectusGates(array $inputs, array $facts, string $now = '2026-11-10T08:00:00Z'): array
{
    $result = (new ProspectusGates)->evaluate($inputs, $facts, new DateTimeImmutable($now));

    return array_column($result['gates'], null, 'id');
}

it('passes gates one to eleven, then unlocks signing, and publishes only once the representative signs', function (): void {
    [$inputs, $facts] = prospectusCase();
    $before = (new ProspectusGates)->evaluate($inputs, $facts, new DateTimeImmutable('2026-11-10T08:00:00Z'));
    expect(array_column($before['gates'], 'id'))->toBe(['P-G01', 'P-G02', 'P-G03', 'P-G04', 'P-G05', 'P-G06', 'P-G07', 'P-G08', 'P-G09', 'P-G10', 'P-G11', 'P-G12'])
        ->and(array_slice(array_column($before['gates'], 'passed'), 0, 11))->each->toBeTrue()
        ->and($before['gates'][11]['passed'])->toBeFalse()
        ->and($before['may_sign'])->toBeTrue()
        ->and($before['publishable'])->toBeFalse()
        ->and(ProspectusGates::SECTIONS)->toHaveCount(16);

    $after = (new ProspectusGates)->evaluate($inputs, [...$facts, 'signed' => true], new DateTimeImmutable('2026-11-10T08:00:00Z'));
    expect($after['gates'][11]['passed'])->toBeTrue()->and($after['publishable'])->toBeTrue()->and($after['may_sign'])->toBeFalse();
});

it('keeps signing locked while any earlier gate fails, so a signature alone never publishes', function (): void {
    [$inputs, $facts] = prospectusCase();
    $result = (new ProspectusGates)->evaluate([...$inputs, 'risks' => []], [...$facts, 'signed' => true], new DateTimeImmutable('2026-11-10T08:00:00Z'));
    $gates = array_column($result['gates'], null, 'id');
    expect($result['may_sign'])->toBeFalse()->and($result['publishable'])->toBeFalse()
        ->and($gates['P-G12'])->toMatchArray(['passed' => false, 'fix' => 'Signing opens once every other gate passes.'])
        ->and($gates['P-G09'])->toMatchArray(['section' => 'risks', 'passed' => false]);
});

it('requires every section, a forecast for each month of the term and the representative profile', function (): void {
    [$inputs, $facts] = prospectusCase();
    expect(prospectusGates([...$inputs, 'forecast' => array_slice($inputs['forecast'], 0, 5)], $facts)['P-G01'])->toMatchArray(['passed' => false, 'section' => 'forecast'])
        ->and(prospectusGates([...$inputs, 'profiles' => [$inputs['profiles'][1]]], $facts)['P-G01'])->toMatchArray(['passed' => false, 'section' => 'management'])
        ->and(prospectusGates([...$inputs, 'largest_customer_share_percent' => null], $facts)['P-G01'])->toMatchArray(['passed' => false, 'section' => 'risks'])
        ->and(prospectusGates([...$inputs, 'use_of_funds' => []], $facts)['P-G01'])->toMatchArray(['passed' => false, 'section' => 'purpose']);
});

it('needs a sealed report at most 35 days old at the moment of evaluation', function (): void {
    [$inputs, $facts] = prospectusCase();
    expect(prospectusGates($inputs, $facts, '2026-12-06T10:00:00Z')['P-G02']['passed'])->toBeTrue()
        ->and(prospectusGates($inputs, $facts, '2026-12-06T10:00:01Z')['P-G02']['passed'])->toBeFalse()
        ->and(prospectusGates($inputs, [...$facts, 'latest_sealed_report_at' => null], '2026-11-10T08:00:00Z')['P-G02']['passed'])->toBeFalse()
        ->and(prospectusGates($inputs, $facts, '2026-10-31T10:00:00Z')['P-G02']['passed'])->toBeFalse();
});

it('matches every profile name to KYC and RDB, ignoring only case and spacing', function (): void {
    [$inputs, $facts] = prospectusCase();
    $renamed = $inputs['profiles'];
    $renamed[1] = [...$renamed[1], 'name' => 'Jean-Paul Mugabo'];
    expect(prospectusGates([...$inputs, 'profiles' => $renamed], $facts)['P-G03'])
        ->toMatchArray(['passed' => false, 'fix' => 'Use each person\'s name exactly as it appears in KYC and the RDB register: Jean-Paul Mugabo.']);
});

it('balances use of funds plus the 3% platform fee against principal to the franc', function (): void {
    [$inputs, $facts] = prospectusCase();
    $lines = $inputs['use_of_funds'];
    $lines[1] = [...$lines[1], 'amount' => '2000001'];
    expect(prospectusGates([...$inputs, 'use_of_funds' => $lines], $facts)['P-G04'])
        ->toMatchArray(['passed' => false, 'fix' => 'Use-of-funds lines must add up to RWF 11,640,000, the principal less the 3% platform fee.']);
    $odd = prospectusGates($inputs, [...$facts, 'principal' => '12000010'])['P-G04'];
    expect($odd)->toMatchArray(['passed' => false, 'fix' => 'Choose a principal whose 3% platform fee is a whole franc.']);
});

it('backs each line of 20% or more of principal with a quote within 5%', function (): void {
    [$inputs, $facts] = prospectusCase();
    $lines = $inputs['use_of_funds'];
    expect(prospectusGates($inputs, $facts)['P-G05']['passed'])->toBeTrue();
    $lines[0] = [...$lines[0], 'quote_amount' => '10122000'];
    expect(prospectusGates([...$inputs, 'use_of_funds' => $lines], $facts)['P-G05']['passed'])->toBeTrue();
    $lines[0] = [...$lines[0], 'quote_amount' => '10122001'];
    expect(prospectusGates([...$inputs, 'use_of_funds' => $lines], $facts)['P-G05'])->toMatchArray(['passed' => false, 'section' => 'purpose']);
    $lines[0] = [...$lines[0], 'quote_amount' => null];
    expect(prospectusGates([...$inputs, 'use_of_funds' => $lines], $facts)['P-G05']['fix'])->toContain('Cold room');
});

it('keeps forecast months within 25% of the verified average unless a signed contract is attached', function (): void {
    [$inputs, $facts] = prospectusCase();
    $forecast = $inputs['forecast'];
    $forecast[2] = [...$forecast[2], 'revenue' => '8750000'];
    expect(prospectusGates([...$inputs, 'forecast' => $forecast], $facts)['P-G06']['passed'])->toBeTrue();
    $forecast[2] = [...$forecast[2], 'revenue' => '8750001'];
    expect(prospectusGates([...$inputs, 'forecast' => $forecast], $facts)['P-G06']['fix'])->toContain('2027-02');
    $forecast[2] = [...$forecast[2], 'contract_document_id' => '01jc0contract0000000000000'];
    expect(prospectusGates([...$inputs, 'forecast' => $forecast], $facts)['P-G06']['passed'])->toBeTrue();
    $forecast[2] = [...$forecast[2], 'contract_document_id' => '01jc0quote0000000000000000'];
    expect(prospectusGates([...$inputs, 'forecast' => $forecast], $facts)['P-G06']['passed'])->toBeFalse();
    $forecast[2] = [...$forecast[2], 'contract_document_id' => '01jc0foreign00000000000000'];
    expect(prospectusGates([...$inputs, 'forecast' => $forecast], $facts)['P-G06']['passed'])->toBeFalse();
});

it('requires a downside line when any forecast month is below 1.25 coverage', function (): void {
    [$inputs, $facts] = prospectusCase();
    $forecast = $inputs['forecast'];
    $forecast[0] = [...$forecast[0], 'costs' => '4200001'];
    expect(prospectusGates([...$inputs, 'forecast' => $forecast, 'downside' => ''], $facts)['P-G07']['passed'])->toBeFalse()
        ->and(prospectusGates([...$inputs, 'forecast' => $forecast], $facts)['P-G07']['passed'])->toBeTrue();
    $forecast[0] = [...$forecast[0], 'costs' => '4150000'];
    expect(prospectusGates([...$inputs, 'forecast' => $forecast, 'downside' => ''], $facts)['P-G07']['passed'])->toBeTrue();
});

it('keeps a concentration risk when the largest customer is 25% or more, and counts it apart from the three business risks', function (): void {
    [$inputs, $facts] = prospectusCase();
    expect(prospectusGates([...$inputs, 'largest_customer_share_percent' => 25], $facts)['P-G08']['passed'])->toBeFalse();
    $risks = [...$inputs['risks'], ['text' => 'One hotel group buys a quarter of our output.', 'concentration' => true]];
    expect(prospectusGates([...$inputs, 'largest_customer_share_percent' => 25, 'risks' => $risks], $facts)['P-G08']['passed'])->toBeTrue()
        ->and(prospectusGates([...$inputs, 'risks' => [...array_slice($inputs['risks'], 0, 2), $risks[3]]], $facts)['P-G09']['passed'])->toBeFalse();
});

it('rejects guaranteed or risk-free claims in English, Kinyarwanda and French, but allows saying returns are not guaranteed', function (string $claim, bool $allowed): void {
    [$inputs, $facts] = prospectusCase();
    $gate = prospectusGates([...$inputs, 'assumptions' => 'Our plan: '.$claim.'.'], $facts)['P-G10'];
    expect($gate['passed'])->toBe($allowed);
    if (! $allowed) {
        expect($gate['section'])->toBe('forecast');
    }
})->with([
    ['A guaranteed return for every investor', false],
    ['This is a SAFE INVESTMENT', false],
    ['Completely risk-free', false],
    ['Nta ngaruka', false],
    ['Un placement sûr', false],
    ['Rendement garanti', false],
    ['Returns are not guaranteed and capital is at risk', true],
]);

it('enforces purpose and profile lengths', function (): void {
    [$inputs, $facts] = prospectusCase();
    $short = $inputs['profiles'];
    $short[0] = [...$short[0], 'text' => 'Runs the shop.'];
    expect(prospectusGates([...$inputs, 'purpose' => str_repeat('a', 79)], $facts)['P-G11'])->toMatchArray(['passed' => false, 'section' => 'purpose'])
        ->and(prospectusGates([...$inputs, 'purpose' => str_repeat('a', 601)], $facts)['P-G11']['passed'])->toBeFalse()
        ->and(prospectusGates([...$inputs, 'purpose' => str_repeat('a', 600)], $facts)['P-G11']['passed'])->toBeTrue()
        ->and(prospectusGates([...$inputs, 'profiles' => $short], $facts)['P-G11'])->toMatchArray(['passed' => false, 'section' => 'management']);
});

it('counts the term\'s months once each and one after another, not just the number of rows', function (): void {
    [$inputs, $facts] = prospectusCase();
    $repeated = array_fill(0, 6, $inputs['forecast'][0]);
    $gap = $inputs['forecast'];
    $gap[5] = [...$gap[5], 'month' => '2027-07'];
    $shuffled = array_reverse($inputs['forecast']);
    $blank = $inputs['forecast'];
    $blank[0] = [...$blank[0], 'month' => ''];
    expect(prospectusGates([...$inputs, 'forecast' => $repeated], $facts)['P-G01'])
        ->toMatchArray(['passed' => false, 'section' => 'forecast', 'fix' => 'Give a forecast for each of the term\'s consecutive months, once each, with your assumptions and downside.'])
        ->and(prospectusGates([...$inputs, 'forecast' => $gap], $facts)['P-G01']['passed'])->toBeFalse()
        ->and(prospectusGates([...$inputs, 'forecast' => $blank], $facts)['P-G01']['passed'])->toBeFalse()
        ->and(prospectusGates([...$inputs, 'forecast' => $shuffled], $facts)['P-G01']['passed'])->toBeTrue()
        ->and(prospectusGates([...$inputs, 'forecast' => []], [...$facts, 'term_months' => 0])['P-G01']['passed'])->toBeFalse();
});

it('requires a profile for every director, owner and representative on record', function (): void {
    [$inputs, $facts] = prospectusCase();
    $owner = ['name' => 'Grace Ingabire', 'role' => 'owner'];
    expect(prospectusGates($inputs, [...$facts, 'registered_names' => [...$facts['registered_names'], 'Grace Ingabire'], 'required_people' => [...$facts['required_people'], $owner]])['P-G03'])
        ->toMatchArray(['passed' => false, 'fix' => 'Add a profile for every director, 10% owner and the official representative on record: Grace Ingabire (owner).']);
    $wrongRole = $inputs['profiles'];
    $wrongRole[1] = [...$wrongRole[1], 'role' => 'owner'];
    expect(prospectusGates([...$inputs, 'profiles' => $wrongRole], $facts)['P-G03']['passed'])->toBeFalse();
});

it('accepts a quote only when it is a quote this Business uploaded to this prospectus', function (?string $documentId): void {
    [$inputs, $facts] = prospectusCase();
    $lines = $inputs['use_of_funds'];
    $lines[0] = [...$lines[0], 'quote_document_id' => $documentId];
    expect(prospectusGates([...$inputs, 'use_of_funds' => $lines], $facts)['P-G05'])
        ->toMatchArray(['passed' => false, 'fix' => 'Upload the quote or invoice, and give its amount within 5% of the line, for: Cold room.']);
})->with([
    'no document' => [null],
    'a document from another application' => ['01jc0foreign00000000000000'],
    'a contract, not a quote' => ['01jc0contract0000000000000'],
]);
