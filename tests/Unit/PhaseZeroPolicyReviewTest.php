<?php

declare(strict_types=1);

/**
 * Test-only arithmetic for unsigned review examples. This is not the application
 * calculator, evidence ingestion, policy activation or a baseline approval gate.
 */
final class PhaseZeroPolicyReview
{
    /** @return array<string, mixed> */
    public static function load(string $filename): array
    {
        $contents = file_get_contents(__DIR__.'/../../docs/phase-0/'.$filename);

        if ($contents === false) {
            throw new UnexpectedValueException('Missing review artifact.');
        }

        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new UnexpectedValueException('Review artifact must be an object.');
        }

        return $decoded;
    }

    /** @param array<string, mixed> $pack */
    public static function validatePack(array $pack): void
    {
        if (($pack['schema_version'] ?? null) !== 1
            || ($pack['status'] ?? null) !== 'SYNTHETIC_REVIEW_ONLY_NOT_BASELINED'
            || ($pack['production_activation'] ?? null) !== false
            || ($pack['calculator_version'] ?? null) !== 'REVIEW_ARITHMETIC_ONLY_NO_PRODUCTION_ENGINE'
            || ($pack['evidence_origin'] ?? null) !== 'SYNTHETIC_NORMALIZED_INPUTS_NO_PROVIDER_ATTESTATION') {
            throw new UnexpectedValueException('Review authority changed.');
        }

        if (($pack['approvals'] ?? null) !== array_fill_keys([
            'product', 'business', 'finance_risk', 'compliance', 'internal_legal',
            'engineering', 'security', 'independent_test', 'external_authority',
        ], null)) {
            throw new UnexpectedValueException('Review pack cannot assert approvals.');
        }

        $seen = [];

        foreach (self::records($pack, 'fixtures') as $fixture) {
            $id = $fixture['id'] ?? null;

            if (! is_string($id) || ! str_starts_with($id, 'REV-') || in_array($id, $seen, true)) {
                throw new UnexpectedValueException('Invalid or duplicate review fixture ID.');
            }

            $seen[] = $id;

            if (($fixture['claim'] ?? null) !== 'EXAMPLE_ARITHMETIC_ONLY_NOT_COMPLETE_SOURCE_VECTOR'
                || ! in_array($fixture['policy_authority'] ?? null, [
                    'BRS_FIXED', 'PRODUCT_APPROVED_NOT_ACTIVATED', 'PROPOSED_SEC_R04_R06',
                    'PROPOSED_UW_R01', 'PROPOSED_UW_R02', 'PROPOSED_UW_R03', 'PROPOSED_UW_R04',
                ], true)) {
                throw new UnexpectedValueException('Fixture authority changed.');
            }

            $inputs = $fixture['inputs'] ?? null;

            if (! is_array($inputs) || array_is_list($inputs)
                || hash('sha256', json_encode($inputs, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)) !== ($fixture['input_sha256'] ?? null)) {
                throw new UnexpectedValueException('Review input hash mismatch.');
            }

            $sourceIds = $fixture['source_vector_ids'] ?? null;

            if (! is_array($sourceIds) || ! array_is_list($sourceIds)) {
                throw new UnexpectedValueException('Invalid source-vector references.');
            }

            foreach ($sourceIds as $sourceId) {
                if (! is_string($sourceId) || ! preg_match('/^GV-(00[1-9]|0[1-3][0-9]|04[0-8])$/', $sourceId)) {
                    throw new UnexpectedValueException('Unknown source-vector reference.');
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    public static function records(array $document, string $key): array
    {
        $records = $document[$key] ?? null;

        if (! is_array($records) || ! array_is_list($records) || $records === []) {
            throw new UnexpectedValueException('Expected nonempty review records.');
        }

        foreach ($records as $record) {
            if (! is_array($record) || array_is_list($record)) {
                throw new UnexpectedValueException('Expected a review record object.');
            }
        }

        return $records;
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    public static function calculate(string $kind, array $inputs): array
    {
        $integer = fn (string $key): int => self::integer($inputs, $key);
        $series = fn (string $key): array => self::series($inputs, $key);

        switch ($kind) {
            case 'pricing':
                $premium = [3 => 0, 6 => 5, 9 => 10, 12 => 15][$integer('tenor_months')];
                $hundredths = 1000 + (50 - $integer('rating_tenths')) * 16 + $premium * 10;

                return ['rate_tenths_percent' => self::halfUp(max(1000, min(1500, $hundredths)), 10)];

            case 'rating':
                $denominator = $integer('score_denominator');
                $clamped = max(0, min(100 * $denominator, $integer('score_numerator')));
                $rating = self::halfUp($clamped, 2 * $denominator);

                return ['rating_tenths' => $rating, 'band' => match (true) {
                    $rating >= 40 => 'Strong',
                    $rating >= 30 => 'Stable',
                    $rating >= 20 => 'Weak',
                    default => 'Distressed',
                }];

            case 'schedule':
                $principal = $integer('principal_rwf');
                $return = self::halfUp($principal * $integer('rate_tenths_percent'), 1000);
                $total = $principal + $return;
                $months = $integer('tenor_months');
                $regular = self::halfUp($total, $months);

                return [
                    'return_rwf' => $return,
                    'total_rwf' => $total,
                    'instalments_rwf' => [...array_fill(0, $months - 1, $regular), $total - $regular * ($months - 1)],
                ];

            case 'capacity':
                $cfads = $integer('cfads_rwf');
                $months = $integer('tenor_months');
                $rate = $integer('rate_tenths_percent');
                $candidate = self::halfUp($cfads * 4 * $months * 1000, 5 * (1000 + $rate));
                $totalFor = fn (int $principal): int => $principal + self::halfUp($principal * $rate, 1000);
                $capacity = $candidate;

                while ($cfads * $months * 4 < $totalFor($capacity) * 5) {
                    $capacity--;
                }

                return [
                    'nearest_candidate_rwf' => $candidate,
                    'candidate_total_rwf' => $totalFor($candidate),
                    'capacity_rwf' => $capacity,
                    'total_rwf' => $totalFor($capacity),
                    'correction_rwf' => $candidate - $capacity,
                    'reason' => $candidate === $capacity ? 'NO_CORRECTION' : 'ROUNDING_GUARD',
                ];

            case 'payoff':
                return ['remaining_rwf' => $integer('contractual_total_rwf') - $integer('paid_rwf')];

            case 'dscr_tier':
                $numerator = $integer('numerator');
                $denominator = $integer('denominator');

                return ['tier' => match (true) {
                    $numerator < $denominator => 'DECLINE',
                    $numerator * 4 < $denominator * 5 => 'MANUAL',
                    default => 'AUTO_SUBJECT_TO_OTHER_GATES',
                }];

            case 'coverage':
                $numerator = $integer('inflow_rwf');
                $denominator = $integer('outflow_rwf') + $integer('debt_service_rwf');

                if ($denominator <= 0) {
                    return ['ratio_six_dp' => null, 'ordinary_display' => null, 'health' => 'Watch', 'boundary_hint_required' => false];
                }

                $health = self::health($numerator, $denominator);

                return [
                    'ratio_six_dp' => self::decimal($numerator, $denominator, 6),
                    'ordinary_display' => self::decimal($numerator, $denominator, 2),
                    'health' => $health,
                    'boundary_hint_required' => $health !== self::health(self::halfUp($numerator * 100, $denominator), 100),
                ];

            case 'upper_winsorization':
                $original = $series('nocf_rwf');
                $sorted = $original;
                sort($sorted, SORT_NUMERIC);
                $count = count($sorted);
                $upper = max(1, intdiv($count, 5));
                $cap = $sorted[$count - $upper - 1];
                $adjusted = array_map(fn (int $value): int => min($value, $cap), $original);

                return ['cap_rwf' => $cap, 'upper_count' => $upper, 'adjusted_rwf' => $adjusted, 'sum_rwf' => array_sum($adjusted), 'count' => $count];

            case 'owner_draw':
            case 'debt_service':
                $isDraw = $kind === 'owner_draw';
                $history = $series($isDraw ? 'historical_draws_rwf' : 'historical_service_rwf');
                $forward = $isDraw ? $integer('committed_monthly_rwf') : max($series('forward_monthly_service_rwf'));
                $numerator = max(array_sum($history), $forward * count($history));

                if ($numerator % count($history) !== 0) {
                    throw new UnexpectedValueException('These review examples require integral final deductions, not a rounded intermediate mean.');
                }

                $deduction = intdiv($numerator, count($history));
                $other = $integer($isDraw ? 'debt_service_rwf' : 'owner_draw_rwf');

                return [$isDraw ? 'owner_draw_rwf' : 'debt_service_rwf' => $deduction, 'cfads_rwf' => $integer('trimmed_nocf_rwf') - $deduction - $other];

            case 'pulse_range':
                $pledge = $integer('pledge_rwf');
                $minimum = self::halfUp($pledge, 10);
                $maximum = self::halfUp($pledge * 15, 100);

                return ['return_min_rwf' => $minimum, 'return_max_rwf' => $maximum, 'gross_min_rwf' => $pledge + $minimum, 'gross_max_rwf' => $pledge + $maximum];

            case 'secondary_fee':
                $gross = $integer('gross_rwf');
                $fee = self::halfUp($gross * 3, 100);

                return ['seller_fee_rwf' => $fee, 'seller_net_rwf' => $gross - $fee, 'buyer_debit_rwf' => $gross];

            case 'secondary_price':
                $gross = $integer('units') * $integer('unit_ask_rwf');
                $capPass = $gross <= $integer('remaining_gross_rwf');
                $minimumPass = $gross >= $integer('minimum_ticket_rwf');

                return ['gross_rwf' => $gross, 'cap_pass' => $capPass, 'minimum_pass' => $minimumPass, 'price_eligible' => $integer('units') > 0 && $integer('unit_ask_rwf') > 0 && $capPass && $minimumPass];

            default:
                throw new UnexpectedValueException('Unknown review calculation kind.');
        }
    }

    /** @param array<string, mixed> $inputs */
    private static function integer(array $inputs, string $key): int
    {
        $value = $inputs[$key] ?? null;

        if (! is_int($value)) {
            throw new UnexpectedValueException('Review arithmetic requires integer inputs.');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return non-empty-list<int>
     */
    private static function series(array $inputs, string $key): array
    {
        $values = $inputs[$key] ?? null;

        if (! is_array($values) || ! array_is_list($values) || $values === []) {
            throw new UnexpectedValueException('Review series must be a nonempty list.');
        }

        foreach ($values as $value) {
            if (! is_int($value)) {
                throw new UnexpectedValueException('Review series requires integers.');
            }
        }

        return $values;
    }

    private static function halfUp(int $numerator, int $denominator): int
    {
        if ($numerator < 0 || $denominator <= 0) {
            throw new UnexpectedValueException('This review rounding helper requires nonnegative numerators and positive denominators.');
        }

        return intdiv($numerator * 2 + $denominator, $denominator * 2);
    }

    private static function decimal(int $numerator, int $denominator, int $places): string
    {
        $scale = 10 ** $places;
        $rounded = self::halfUp($numerator * $scale, $denominator);

        return intdiv($rounded, $scale).'.'.str_pad((string) ($rounded % $scale), $places, '0', STR_PAD_LEFT);
    }

    private static function health(int $numerator, int $denominator): string
    {
        return match (true) {
            $numerator < $denominator => 'Distressed',
            $numerator * 4 < $denominator * 5 => 'Watch',
            default => 'Healthy',
        };
    }
}

test('review artifacts preserve unsigned authority and exact synthetic input hashes', function (): void {
    $pack = PhaseZeroPolicyReview::load('policy-review-fixtures.json');
    PhaseZeroPolicyReview::validatePack($pack);

    expect($pack['review_version'])->toBe('review-2026-09-10.1')
        ->and($pack['as_of_utc'])->toBe('2026-09-10T08:00:00Z')
        ->and($pack['fixtures'])->toHaveCount(34);
});

test('review guards reject false authority or corrupted evidence for :dataset', function (string $mutation, string $message): void {
    $pack = PhaseZeroPolicyReview::load('policy-review-fixtures.json');

    switch ($mutation) {
        case 'activated':
            $pack['production_activation'] = true;
            break;
        case 'baselined':
            $pack['status'] = 'BASELINED';
            break;
        case 'signed':
            $pack['approvals']['product'] = 'Not a supplied signature';
            break;
        case 'input changed':
            $pack['fixtures'][0]['inputs']['rating_tenths'] = 39;
            break;
        case 'unknown source':
            $pack['fixtures'][0]['source_vector_ids'] = ['GV-999'];
            break;
        case 'duplicate ID':
            $pack['fixtures'][1]['id'] = $pack['fixtures'][0]['id'];
            break;
    }

    expect(function () use ($pack): void {
        PhaseZeroPolicyReview::validatePack($pack);
    })->toThrow(UnexpectedValueException::class, $message);
})->with([
    'activation claim' => ['activated', 'Review authority changed.'],
    'baseline claim' => ['baselined', 'Review authority changed.'],
    'invented approval' => ['signed', 'Review pack cannot assert approvals.'],
    'input tampering' => ['input changed', 'Review input hash mismatch.'],
    'unknown vector' => ['unknown source', 'Unknown source-vector reference.'],
    'duplicate fixture' => ['duplicate ID', 'Invalid or duplicate review fixture ID.'],
]);

test('all 48 source dispositions and bidirectional example links remain exact without claiming baseline coverage', function (): void {
    $register = PhaseZeroPolicyReview::load('underwriting-vector-review.json');
    $pack = PhaseZeroPolicyReview::load('policy-review-fixtures.json');
    $fixtures = PhaseZeroPolicyReview::records($pack, 'fixtures');
    $vectors = PhaseZeroPolicyReview::records($register, 'vectors');
    $source = file_get_contents(__DIR__.'/../../docs/Rozine_Phased_Implementation_Plan.md');

    if ($source === false) {
        throw new UnexpectedValueException('Missing source plan.');
    }

    preg_match_all('/^\| `GV-\d{3}` \|.*$/m', $source, $matches);

    expect($register['schema_version'])->toBe(1)
        ->and($register['review_version'])->toBe($pack['review_version'])
        ->and($register['status'])->toBe('SOURCE_DISPOSITION_REGISTER_NOT_BASELINED')
        ->and($register['production_activation'])->toBeFalse()
        ->and($register['source_path'])->toBe('docs/Rozine_Phased_Implementation_Plan.md')
        ->and($register['source_section'])->toBe('A.5 Minimum golden-vector matrix')
        ->and($register['fixture_pack'])->toBe('policy-review-fixtures.json')
        ->and($matches[0])->toHaveCount(48)
        ->and($register['source_rows_sha256'])->toBe(hash('sha256', implode("\n", $matches[0])))
        ->and($vectors)->toHaveCount(48);

    $statuses = array_count_values(array_column($vectors, 'source_status'));
    ksort($statuses);
    expect($statuses)->toBe(['BLOCKED' => 7, 'QUARANTINED' => 1, 'READY-TO-BASELINE' => 34, 'REJECTED' => 6]);

    foreach ($vectors as $index => $vector) {
        $cells = array_map(trim(...), explode('|', trim($matches[0][$index], '|')));
        $linkedIds = [];

        foreach ($fixtures as $fixture) {
            if (in_array($vector['id'], $fixture['source_vector_ids'], true)) {
                $linkedIds[] = $fixture['id'];
            }
        }

        expect($vector['id'])->toBe(sprintf('GV-%03d', $index + 1))->toBe(trim($cells[0], '`'))
            ->and($vector['source_status'])->toBe(trim($cells[1], '`'))
            ->and($vector['source_condition'])->toBe($cells[2])
            ->and($vector['source_expected'])->toBe($cells[3])
            ->and($vector['source_requirements'])->toBe($cells[4])
            ->and($vector['review_fixture_ids'])->toBe($linkedIds)
            ->and($vector['review_coverage'])->toBe($linkedIds === [] ? 'SCENARIO_CONSTRUCTION_REQUIRED' : 'PARTIAL_SYNTHETIC_EXAMPLES')
            ->and($vector['baselined'])->toBeFalse()
            ->and($vector['implementation_verified'])->toBeFalse()
            ->and($vector['required_next_step'])->toBeString()->not->toBeEmpty()
            ->and($vector['required_layers'])->toBe(['domain', 'persistence', 'application_action', 'resource', 'inertia', 'api_v1', 'mobile_contract', 'admin_replay', 'observability'])
            ->and($vector['approvals'])->toBe(array_fill_keys(['product', 'finance_risk', 'compliance', 'internal_legal', 'engineering_security', 'independent_test', 'external_authority'], null));
    }
});

test('synthetic review example :dataset reproduces its expected arithmetic only', function (string $kind, array $inputs, array $expected): void {
    expect(PhaseZeroPolicyReview::calculate($kind, $inputs))->toBe($expected);
})->with(function (): array {
    $pack = PhaseZeroPolicyReview::load('policy-review-fixtures.json');
    $dataset = [];

    foreach (PhaseZeroPolicyReview::records($pack, 'fixtures') as $fixture) {
        $dataset[$fixture['id']] = [$fixture['kind'], $fixture['inputs'], $fixture['expected']];
    }

    return $dataset;
});
