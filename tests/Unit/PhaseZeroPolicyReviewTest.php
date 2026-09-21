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

test('September stakeholder sheet captures every response without converting questions into choices', function (): void {
    $sheet = file_get_contents(__DIR__.'/../../docs/phase-0/stakeholder-decision-sheet-2026-09-17.md');

    if ($sheet === false) {
        throw new UnexpectedValueException('Missing stakeholder decision sheet.');
    }

    preg_match_all('/^\| (TENOR|HISTORY|UW-R\d{2}|SEC-R\d{2}) \| ([A-Z_]+) \|/m', $sheet, $matches);

    expect($matches[1])->toHaveCount(19)
        ->and(array_combine($matches[1], $matches[2]))->toBe([
            'TENOR' => 'CHOICE_CAPTURED',
            'HISTORY' => 'QUESTION_OPEN',
            'UW-R01' => 'QUESTION_OPEN',
            'UW-R02' => 'QUESTION_OPEN',
            'UW-R03' => 'QUESTION_OPEN',
            'UW-R04' => 'PARTIAL_ANSWER',
            'UW-R05' => 'CHOICE_CAPTURED',
            'UW-R06' => 'CHOICE_CAPTURED',
            'UW-R07' => 'CHANGE_REQUEST',
            'SEC-R01' => 'CHOICE_CAPTURED',
            'SEC-R02' => 'CHOICE_CAPTURED',
            'SEC-R03' => 'CHOICE_CAPTURED',
            'SEC-R04' => 'CHOICE_CAPTURED',
            'SEC-R05' => 'CHOICE_CAPTURED',
            'SEC-R06' => 'QUESTION_OPEN',
            'SEC-R07' => 'CHOICE_CAPTURED',
            'SEC-R08' => 'CHOICE_CAPTURED',
            'SEC-R09' => 'CHOICE_CAPTURED',
            'SEC-R10' => 'CHANGE_REQUEST',
        ]);

    preg_match_all('/^### (Q\d{2}) —/m', $sheet, $questions);
    preg_match_all('/\bQ\d{2}\b/', $sheet, $references);

    expect($questions[1])->toBe(array_map(fn (int $number): string => sprintf('Q%02d', $number), range(1, 12)))
        ->and(array_diff($references[0], $questions[1]))->toBe([])
        ->and(substr_count($sheet, '**Answer:** PENDING.'))->toBe(12)
        ->and($sheet)->toContain(
            'PARTIAL_CHOICES_CAPTURED — CLARIFICATIONS_OPEN — NOT_ACTIVATED',
            'DRAFT_NOT_SENT',
            'Production activation: NONE. Effective policy version/date: NOT_SET.',
            'The pasted messages identify Robert as the speaker',
            "Kimani's individual concurrence",
            'Strict DSCR is not the Coverage-precision decision',
            'review-only flags, not automatic penalties',
            'Phase 0 and the Phase 1 behavioral contract freeze remain open.',
        );
});

test('September stakeholder sheet has resolvable local document references', function (): void {
    $directory = __DIR__.'/../../docs/phase-0/';
    $sheet = file_get_contents($directory.'stakeholder-decision-sheet-2026-09-17.md');

    if ($sheet === false) {
        throw new UnexpectedValueException('Missing stakeholder decision sheet.');
    }

    preg_match_all('/\]\((?!https?:\/\/)([^)]+)\)/', $sheet, $links);

    expect($links[1])->not->toBeEmpty();

    foreach ($links[1] as $link) {
        expect(is_file($directory.$link))->toBeTrue('Missing decision-sheet reference: '.$link);
    }
});

test('historical proposal and phase plan :dataset link to the subsequent response sheet', function (string $filename, string $target): void {
    $contents = file_get_contents(__DIR__.'/../../docs/'.$filename);

    expect($contents)->toBeString()->toContain(']('.$target.')')
        ->and(is_file(dirname(__DIR__.'/../../docs/'.$filename).'/'.$target))->toBeTrue();
})->with([
    'underwriting' => ['phase-0/underwriting-decision-review.md', 'stakeholder-decision-sheet-2026-09-17.md'],
    'secondary' => ['phase-0/secondary-contract-review.md', 'stakeholder-decision-sheet-2026-09-17.md'],
    'phase plan' => ['Rozine_Phased_Implementation_Plan.md', 'phase-0/stakeholder-decision-sheet-2026-09-17.md'],
]);

test('September 20 reconciliation preserves rule coverage and unresolved authority boundaries', function (): void {
    $sheet = file_get_contents(__DIR__.'/../../docs/phase-0/stakeholder-decision-sheet-2026-09-20.md');

    if ($sheet === false) {
        throw new UnexpectedValueException('Missing September 20 reconciliation.');
    }

    preg_match_all('/^\| ((?:UW|SEC)-R\d{2}) \|/m', $sheet, $rules);
    preg_match_all('/^### (F\d{2}) /m', $sheet, $questions);
    preg_match_all('/\bF\d{2}\b/', $sheet, $references);

    expect($rules[1])->toBe([
        ...array_map(fn (int $number): string => sprintf('UW-R%02d', $number), range(1, 7)),
        ...array_map(fn (int $number): string => sprintf('SEC-R%02d', $number), range(1, 10)),
    ])
        ->and($questions[1])->toBe(array_map(fn (int $number): string => sprintf('F%02d', $number), range(1, 7)))
        ->and(array_diff($references[0], $questions[1]))->toBe([])
        ->and(substr_count($sheet, '**Response:** Pending confirmation.'))->toBe(7)
        ->and($sheet)->toContain(
            'response-2026-09-20.1',
            'DECISIONS_RECORDED — RECONCILIATION_OPEN — NOT_ACTIVATED',
            'Maximum Allowed Secondary Listing Price = Remaining Unpaid Principal x 0.70',
            'the conflicting maximum formula is not silently corrected or implemented',
            'Automatic bid matching is a new requested capability',
            'or 1 whole unit',
            'ordinary uncalibrated activity flags remain review-only',
            'No permission to trade distressed notes is inferred',
            'The effective policy version/date, required approvals and implementation evidence remain open',
            'Hussain confirmed that "Kamau" refers to Kimani',
            'db1d40588ced04c128ed6cf0d8ec8fee85ae66e3eaa1a3c2414215982dc22893',
            'c7a9070ed631df6cef757751bae05f175d6010990be1de5eaa3714912f863974',
        );
});

test('September 20 reconciliation links resolve and preserve the prior decision sheet', function (): void {
    $directory = __DIR__.'/../../docs/phase-0/';
    $sheet = file_get_contents($directory.'stakeholder-decision-sheet-2026-09-20.md');
    $plan = file_get_contents(__DIR__.'/../../docs/Rozine_Phased_Implementation_Plan.md');

    if ($sheet === false || $plan === false) {
        throw new UnexpectedValueException('Missing reconciliation or implementation plan.');
    }

    preg_match_all('/\]\((?!https?:\/\/)([^)]+)\)/', $sheet, $links);

    expect($links[1])->not->toBeEmpty()
        ->and($plan)->toContain('](phase-0/stakeholder-decision-sheet-2026-09-20.md)', 'neither gate is closed')
        ->and(hash_file('sha256', $directory.'stakeholder-decision-sheet-2026-09-17.md'))
        ->toBe('cdcf0e27380a4e98b84ff79b1ef02054271f7c944b9a27a8bb78b7790457d137');

    foreach ($links[1] as $link) {
        expect(is_file($directory.$link))->toBeTrue('Missing reconciliation reference: '.$link);
    }
});

test('later September 20 answers close specific questions without approving new policy changes', function (): void {
    $sheet = file_get_contents(__DIR__.'/../../docs/phase-0/stakeholder-decision-closure-2026-09-20.md');

    if ($sheet === false) {
        throw new UnexpectedValueException('Missing decision closure addendum.');
    }

    preg_match_all('/^\| (F\d{2}) \| ([A-Z0-9_]+) \|/m', $sheet, $answers);
    preg_match_all('/^### (C\d{2}) /m', $sheet, $changes);
    preg_match_all('/\bC\d{2}\b/', $sheet, $references);

    expect(array_combine($answers[1], $answers[2]))->toBe([
        'F01' => 'DSCR_CHOICE_RESOLVED',
        'F02' => 'PRICE_BAND_RESOLVED',
        'F03' => 'MATCHING_RECORDED_FEE_OPEN',
        'F04' => 'SECONDARY_MINIMUM_RESOLVED',
        'F05' => 'TRADING_BAN_RESOLVED_RECOVERY_OPEN',
        'F06' => 'HOLD_TIMERS_RESOLVED',
        'F07' => 'CONCURRENCE_REPORTED_TRANSITION_OPEN',
    ])
        ->and($changes[1])->toBe(['C01', 'C02', 'C03'])
        ->and(array_diff($references[0], $changes[1]))->toBe([])
        ->and(substr_count($sheet, '**Decision:** PENDING_OWNER_RESPONSE.'))->toBe(3)
        ->and($sheet)->toContain(
            'response-2026-09-20.2',
            'ANSWERS_RECORDED — THREE_CHANGE_GROUPS_OPEN — NOT_ACTIVATED',
            'DRAFT_NOT_SENT',
            'ON_HOLD_BY_USER',
            'test the original requested amount',
            'below 1.25 is rejected without downsizing',
            'final offer reaches at least 1.50',
            'minimum price is 70% of remaining unpaid principal',
            'whole unit AND gross consideration of at least RWF 1,000',
            'execution at the seller\'s ask',
            'hold expires at 24 hours',
            'five-business-day clock starts at the original flag timestamp',
            'Compliance Officer OR Legal Counsel',
            'Kimani\'s concurrence',
            'No Phase 1 development until',
            'rate/amount remains NOT_SET',
            'not zero-fee defaults',
            'not a guaranteed payout deadline',
            'never rewrite already-issued investor holdings',
            'not a determination of what Rwanda law permits',
            'Three follow-up groups do not mean only three Phase 0 tasks remain',
            '58391d6c193e6ab5e9554db12c100154dba6472914e174c6102a9534068f9f8a',
        );
});

test('decision closure references resolve while earlier answers and governing rules remain unchanged', function (): void {
    $directory = __DIR__.'/../../docs/phase-0/';
    $sheet = file_get_contents($directory.'stakeholder-decision-closure-2026-09-20.md');
    $plan = file_get_contents(__DIR__.'/../../docs/Rozine_Phased_Implementation_Plan.md');

    if ($sheet === false || $plan === false) {
        throw new UnexpectedValueException('Missing decision closure addendum or implementation plan.');
    }

    preg_match_all('/\]\((?!https?:\/\/)([^)]+)\)/', $sheet, $links);

    expect($links[1])->not->toBeEmpty()
        ->and($plan)->toContain(
            '](phase-0/stakeholder-decision-closure-2026-09-20.md)',
            'No preparatory-slice exception is approved for this work',
            'the existing foundation status is not permission to start',
        )
        ->and(hash_file('sha256', $directory.'stakeholder-decision-sheet-2026-09-20.md'))
        ->toBe('82af54e9d1eb08a5d95cfe022a316595724f3da694c8a949cbf637102c0527d0')
        ->and(hash_file('sha256', $directory.'stakeholder-decision-sheet-2026-09-17.md'))
        ->toBe('cdcf0e27380a4e98b84ff79b1ef02054271f7c944b9a27a8bb78b7790457d137')
        ->and(hash_file('sha256', $directory.'../Rozine-BRS.md'))
        ->toBe('0badfe6c175f58ec5c883ab80a9a9c52b921cf802a0f133fbcc251af6fa96f74');

    foreach ($links[1] as $link) {
        expect(is_file($directory.$link))->toBeTrue('Missing decision closure reference: '.$link);
    }
});

test('current consolidation covers every rule and closes the three stakeholder follow-ups', function (): void {
    $sheet = file_get_contents(__DIR__.'/../../docs/phase-0/stakeholder-policy-consolidation-2026-09-20.md');

    if ($sheet === false) {
        throw new UnexpectedValueException('Missing current stakeholder consolidation.');
    }

    preg_match_all('/^\| ((?:UW|SEC)-R\d{2}) \|/m', $sheet, $rules);
    preg_match_all('/^\| (C\d{2}) \| ([A-Z0-9_]+) \|/m', $sheet, $answers);

    expect($rules[1])->toBe([
        ...array_map(fn (int $number): string => sprintf('UW-R%02d', $number), range(1, 7)),
        ...array_map(fn (int $number): string => sprintf('SEC-R%02d', $number), range(1, 10)),
    ])
        ->and(array_combine($answers[1], $answers[2]))->toBe([
            'C01' => 'ANSWERED',
            'C02' => 'RETAIN_ROBERT_AS_PROVIDED',
            'C03' => 'RETAIN_ROBERT_AS_PROVIDED',
        ])
        ->and($sheet)->toContain(
            'response-2026-09-20.3',
            'Buyer fee is 0.35%; seller fee is 0.35%; combined nominal rate is 0.7%',
            'execution price multiplied by units matched',
            'supersedes 0.5% taker / 0.2% maker and the legacy 3% seller fee; do not stack them',
            'isolated phrase "70% combined"',
            'not a 70% charge',
            'No additional fee clarification is required',
            'historical and must not be sent as current questions',
            'd9622386ebbaae2fa27beb65fe03acd158d5a04bee02b1d33133a73b95ad2dfd',
        );
});

test('consolidation preserves supplied recovery and transitions without the withdrawn alternatives', function (): void {
    $sheet = file_get_contents(__DIR__.'/../../docs/phase-0/stakeholder-policy-consolidation-2026-09-20.md');

    expect($sheet)->toBeString()->toContain(
        'Days 1–7 — grace and automated retries',
        'Days 8–21 — active recovery',
        'penalty-interest accrual and an enforced structured daily repayment plan',
        'Days 22–30 — formal default',
        'if the balance is not cured by day 30, declare Default',
        'Days 31–45 maximum — final investor resolution',
        'reserve-fund buyout/payout mechanisms execute to deliver final capital resolution',
        "The assistant's alternative that made day 45 only a progress checkpoint is withdrawn",
        'grandfathered until their five-minute timer completes or expires',
        'grandfathered on original listing terms until the seven-day window expires',
        're-evaluated immediately against new DSCR scaling rules and exposure caps before disbursement',
        'protection of already-issued investor rating/return/schedule snapshots',
        "without importing the assistant's proposed fresh-acceptance, cancellation/refund or re-consent requirements",
        'five-business-day clock starts at the original automated flag timestamp',
        'Compliance Officer OR Legal Counsel',
        'Missing inputs are not zero/default values',
        '58391d6c193e6ab5e9554db12c100154dba6472914e174c6102a9534068f9f8a',
    );
});

test('published fee examples use equal thirty-five basis point fees and conserve every RWF', function (): void {
    $sheet = file_get_contents(__DIR__.'/../../docs/phase-0/stakeholder-policy-consolidation-2026-09-20.md');

    if ($sheet === false) {
        throw new UnexpectedValueException('Missing current stakeholder consolidation.');
    }

    preg_match_all('/^\| (\d+) \| (\d+) \| (\d+) \| (\d+) \| (\d+) \| (\d+) \|$/m', $sheet, $examples, PREG_SET_ORDER);

    expect(array_column($examples, 1))->toBe(['1000', '10000', '20000'])
        ->and($sheet)->toContain(
            'per-fill whole-RWF half-up rounding and no minimum fee',
            'derived synthetic examples, not executed trades',
            'gross threshold is applied before the seller fee',
        );

    foreach ($examples as $example) {
        [$gross, $buyerFee, $buyerDebit, $sellerFee, $sellerCredit, $platformFee] = array_map('intval', array_slice($example, 1));
        $roundedFee = intdiv($gross * 35 + 5000, 10000);

        expect($buyerFee)->toBe($roundedFee)
            ->and($sellerFee)->toBe($roundedFee)
            ->and($buyerDebit)->toBe($gross + $roundedFee)
            ->and($sellerCredit)->toBe($gross - $roundedFee)
            ->and($platformFee)->toBe($buyerFee + $sellerFee)
            ->and($buyerDebit)->toBe($sellerCredit + $platformFee);
    }
});

test('current consolidation preserves historical evidence and leaves contract and exit gates open', function (): void {
    $directory = __DIR__.'/../../docs/phase-0/';
    $sheet = file_get_contents($directory.'stakeholder-policy-consolidation-2026-09-20.md');
    $plan = file_get_contents(__DIR__.'/../../docs/Rozine_Phased_Implementation_Plan.md');

    if ($sheet === false || $plan === false) {
        throw new UnexpectedValueException('Missing current consolidation or implementation plan.');
    }

    preg_match_all('/^\| ((?:ER|EXIT)-\d{2}) \|/m', $sheet, $findings);
    preg_match_all('/\b(?:ER|EXIT)-\d{2}\b/', $sheet, $references);
    preg_match_all('/\]\((?!https?:\/\/)([^)]+)\)/', $sheet, $links);

    expect($findings[1])->toBe([
        ...array_map(fn (int $number): string => sprintf('ER-%02d', $number), range(1, 5)),
        ...array_map(fn (int $number): string => sprintf('EXIT-%02d', $number), range(1, 6)),
    ])
        ->and(array_diff($references[0], $findings[1]))->toBe([])
        ->and($sheet)->toContain(
            'BUILD_DIRECTION_RECORDED — CONTRACT_FREEZE_PENDING — NO_RUNTIME_ACTIVATION',
            'ON_HOLD_BY_USER',
            'DESK_REVIEW_COMPLETE — JOINT_CONTRACT_FREEZE_PENDING',
            'not Erastus\'s review',
            '**Exit result:** NOT_CLEARED',
            'Harness publication is not device evidence',
            'Do not repeat SSH hardening as undone',
            'No Phase 1 development started. No runtime policy activated',
        )
        ->and($plan)->toContain(
            '](phase-0/stakeholder-policy-consolidation-2026-09-20.md)',
            'Keep development on hold until the joint contract freeze and the remaining Phase 0 exit gate',
            'earlier three-question follow-up is superseded',
        )
        ->and(hash_file('sha256', $directory.'stakeholder-decision-closure-2026-09-20.md'))
        ->toBe('4d91810d87a90f76c0819b45dc0c62765b973b440644b98531a5efc3eb0d8271')
        ->and($links[1])->not->toBeEmpty();

    foreach ($links[1] as $link) {
        expect(is_file($directory.$link))->toBeTrue('Missing consolidation reference: '.$link);
    }
});
