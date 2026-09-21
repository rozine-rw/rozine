<?php

declare(strict_types=1);

/**
 * Independent, test-only checks of synthetic specification examples. This is
 * not a production calculator, workflow engine or financial acceptance test.
 */
final class PhaseZeroEngineeringReview
{
    /** @return array<string, mixed> */
    public static function load(string $filename = 'engineering-contract-fixtures-2026-09-20.json'): array
    {
        $contents = file_get_contents(__DIR__.'/../../docs/phase-0/'.$filename);

        if ($contents === false) {
            throw new UnexpectedValueException('Missing engineering review artifact.');
        }

        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new UnexpectedValueException('Review artifact must be an object.');
        }

        return $decoded;
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
                throw new UnexpectedValueException('Invalid review record.');
            }
        }

        return $records;
    }

    /** @param array<string, mixed> $pack */
    public static function validate(array $pack): void
    {
        if (($pack['status'] ?? null) !== 'SYNTHETIC_CONTRACT_REVIEW_NOT_BASELINED'
            || ($pack['production_activation'] ?? null) !== false
            || ($pack['phase_1_development_authorized'] ?? null) !== false
            || ($pack['approvals'] ?? null) !== [
                'aminu_engineering' => [
                    'status' => 'SELECTED_SECTIONS_APPROVED',
                    'recorded_on' => '2026-09-21',
                    'evidence_type' => 'DIRECT_USER_RESPONSE_ANNOTATIONS',
                    'source_message_id' => 'msg_08a35f686d830a89016ab0c52c412487d0b5966cd547f5dd16',
                    'mapped_snapshot_version' => 'engineering-2026-09-20.2',
                    'scope_ids' => ['AR-01', 'AR-02', 'AR-03', 'AR-04', 'AR-05', 'AR-06'],
                    'full_contract_freeze' => false,
                ],
                'erastus_engineering' => null,
                'independent_expected_results' => null,
            ]
            || ($pack['missing_parameters'] ?? null) !== array_fill_keys([
                'four_month_premium', 'five_month_premium', 'standard_nocf_method',
                'penalty_rate', 'reserve_funding_arrangement', 'hold_business_calendar',
            ], null)) {
            throw new UnexpectedValueException('Unapproved review authority or missing-input default.');
        }

        $cases = self::records($pack, 'numeric_cases');
        $ids = array_column($cases, 'id');

        if ($ids !== array_map(fn (int $number): string => sprintf('EC-%03d', $number), range(1, 37))) {
            throw new UnexpectedValueException('Numeric example coverage changed.');
        }

        $vectors = self::records($pack, 'vector_dispositions');

        if (array_column($vectors, 'id') !== array_map(fn (int $number): string => sprintf('GV-%03d', $number), range(1, 48))) {
            throw new UnexpectedValueException('Source-vector coverage changed.');
        }

        foreach ($vectors as $vector) {
            if (($vector['baselined'] ?? null) !== false || ($vector['implementation_verified'] ?? null) !== false) {
                throw new UnexpectedValueException('Synthetic vector claims implementation or approval.');
            }
        }

        foreach (self::records($pack, 'planned_scenarios') as $scenario) {
            if (($scenario['evidence_level'] ?? null) !== 'SCENARIO_ONLY_NOT_EXECUTED'
                || ($scenario['implementation_verified'] ?? null) !== false) {
                throw new UnexpectedValueException('Planned scenario claims execution.');
            }
        }
    }

    /** @param array<string, mixed> $values */
    public static function integer(array $values, string $key): int
    {
        if (! is_int($values[$key] ?? null)) {
            throw new UnexpectedValueException('Expected exact integer: '.$key);
        }

        return $values[$key];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return non-empty-list<int>
     */
    public static function integers(array $values, string $key): array
    {
        $items = $values[$key] ?? null;

        if (! is_array($items) || ! array_is_list($items) || $items === []) {
            throw new UnexpectedValueException('Expected integer series.');
        }

        foreach ($items as $item) {
            if (! is_int($item)) {
                throw new UnexpectedValueException('Non-integer series member.');
            }
        }

        return $items;
    }

    public static function halfUp(int $numerator, int $denominator): int
    {
        if ($numerator < 0 || $denominator <= 0) {
            throw new UnexpectedValueException('Invalid nonnegative monetary fraction.');
        }

        return intdiv($numerator, $denominator) + (2 * ($numerator % $denominator) >= $denominator ? 1 : 0);
    }

    /** @return array{numerator: int, denominator: int} */
    public static function reducedFraction(int $numerator, int $denominator): array
    {
        if ($numerator < 0 || $denominator <= 0) {
            throw new UnexpectedValueException('Invalid review fraction.');
        }

        $left = $numerator;
        $right = $denominator;

        while ($right !== 0) {
            [$left, $right] = [$right, $left % $right];
        }

        return ['numerator' => intdiv($numerator, $left), 'denominator' => intdiv($denominator, $left)];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    public static function calculate(string $kind, array $inputs): array
    {
        $integer = fn (string $key): int => self::integer($inputs, $key);

        switch ($kind) {
            case 'draw':
                $months = $integer('months');

                return self::reducedFraction(max($integer('eligible_draws_rwf'), $integer('recurring_rwf') * $months), $months);

            case 'debt':
                $months = $integer('months');
                $peak = max(self::integers($inputs, 'forward_months_rwf'));

                return self::reducedFraction(max($integer('historical_service_rwf'), $peak * $months), $months);

            case 'dscr':
                $numerator = $integer('numerator');
                $denominator = $integer('denominator');

                return ['status' => match (true) {
                    $denominator <= 0 => 'UNAVAILABLE',
                    4 * $numerator < 5 * $denominator => 'REJECT_ORIGINAL',
                    2 * $numerator < 3 * $denominator => 'SCALE_REQUIRED',
                    default => 'TARGET_MET',
                }];

            case 'sizing':
                return self::size($inputs);

            case 'exposure':
                $limit = min($integer('ttm_revenue_rwf') * 35, 100000000 * 100);
                $room = $limit - 100 * ($integer('platform_principal_rwf') + $integer('external_principal_rwf') + $integer('commitments_rwf'));

                return [
                    'limit_numerator' => $limit,
                    'room_numerator' => $room,
                    'denominator' => 100,
                    'max_whole_room_rwf' => intdiv($room, 100) - ($room < 0 && $room % 100 !== 0 ? 1 : 0),
                ];

            case 'fee':
                $gross = $integer('gross_rwf');
                $buyer = self::halfUp($gross * $integer('buyer_bps'), 10000);
                $seller = self::halfUp($gross * $integer('seller_bps'), 10000);

                return ['buyer_fee_rwf' => $buyer, 'seller_fee_rwf' => $seller, 'buyer_debit_rwf' => $gross + $buyer,
                    'seller_credit_rwf' => $gross - $seller, 'platform_fee_rwf' => $buyer + $seller];

            case 'price':
                $gross = $integer('units') * $integer('ask_rwf');

                return ['gross_rwf' => $gross, 'status' => match (true) {
                    $integer('units') <= 0 => 'INVALID_UNITS',
                    $gross < 1000 => 'TRADE_BELOW_MINIMUM',
                    $gross * 100 < $integer('allocated_principal_rwf') * 70,
                    $gross > $integer('allocated_future_gross_rwf') => 'PRICE_OUT_OF_BOUNDS',
                    default => 'VALID',
                }];

            case 'fragmentation':
                $fees = array_map(fn (int $gross): int => self::halfUp($gross * $integer('bps'), 10000), self::integers($inputs, 'fills_rwf'));

                return ['split_fee_each_side_rwf' => array_sum($fees),
                    'combined_fee_each_side_rwf' => self::halfUp($integer('combined_gross_rwf') * $integer('bps'), 10000)];

            default:
                throw new UnexpectedValueException('Unknown example arithmetic.');
        }
    }

    /**
     * Independent bounded binary search, not the fixture-authoring decrement method.
     *
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    public static function size(array $inputs): array
    {
        $cfads = self::integer($inputs, 'cfads_rwf');
        $request = self::integer($inputs, 'requested_rwf');
        $months = self::integer($inputs, 'tenor_months');
        $rate = self::integer($inputs, 'rate_tenths_percent');
        $room = self::integer($inputs, 'remaining_room_rwf');
        $totalFor = fn (int $principal): int => $principal + self::halfUp($principal * $rate, 1000);

        if ($cfads * $months * 4 < $totalFor($request) * 5) {
            return ['status' => 'REJECT_ORIGINAL', 'principal_rwf' => null];
        }

        if ($room < 3000000 || $cfads * 2 * $months * 1000 < 3000000 * 3 * (1000 + $rate)) {
            return ['status' => 'CAPACITY_BELOW_MINIMUM', 'principal_rwf' => null];
        }

        $candidate = min($request, $room, 100000000, self::halfUp($cfads * 2 * $months * 1000, 3 * (1000 + $rate)));
        $lower = 0;
        $upper = $candidate;

        while ($lower < $upper) {
            $middle = intdiv($lower + $upper + 1, 2);

            if ($cfads * $months * 2 >= $totalFor($middle) * 3) {
                $lower = $middle;
            } else {
                $upper = $middle - 1;
            }
        }

        if ($lower < 3000000) {
            return ['status' => 'CAPACITY_BELOW_MINIMUM', 'principal_rwf' => null];
        }

        $total = $totalFor($lower);
        $regular = self::halfUp($total, $months);

        return ['status' => 'ARITHMETIC_CANDIDATE_ONLY', 'principal_rwf' => $lower,
            'return_rwf' => $total - $lower, 'total_rwf' => $total,
            'instalments_rwf' => [...array_fill(0, $months - 1, $regular), $total - $regular * ($months - 1)],
            'rounding_guard_rwf' => $candidate - $lower];
    }
}

test('engineering examples match independent exact arithmetic :dataset', function (array $fixture): void {
    $inputs = $fixture['inputs'] ?? null;
    $kind = $fixture['kind'] ?? null;

    if (! is_array($inputs) || ! is_string($kind)) {
        throw new UnexpectedValueException('Invalid arithmetic example.');
    }

    $result = PhaseZeroEngineeringReview::calculate($kind, $inputs);

    expect($result)->toBe($fixture['expected']);

    if ($kind === 'fee') {
        expect($result['buyer_debit_rwf'])->toBe($result['seller_credit_rwf'] + $result['platform_fee_rwf'])
            ->and($inputs['buyer_bps'])->toBe(35)
            ->and($inputs['seller_bps'])->toBe(35);
    }

    if ($kind === 'sizing' && $result['status'] === 'ARITHMETIC_CANDIDATE_ONLY') {
        expect(array_sum($result['instalments_rwf']))->toBe($result['total_rwf'])
            ->and($inputs['cfads_rwf'] * $inputs['tenor_months'] * 2)->toBeGreaterThanOrEqual($result['total_rwf'] * 3)
            ->and($result['principal_rwf'])->toBeGreaterThanOrEqual(3000000)
            ->and($result['principal_rwf'])->toBeLessThanOrEqual($inputs['requested_rwf'])
            ->and($result['principal_rwf'])->toBeLessThanOrEqual($inputs['remaining_room_rwf']);
    }
})->with(function (): array {
    $pack = PhaseZeroEngineeringReview::load();
    PhaseZeroEngineeringReview::validate($pack);
    $dataset = [];

    foreach (PhaseZeroEngineeringReview::records($pack, 'numeric_cases') as $fixture) {
        $dataset[$fixture['id']] = [$fixture];
    }

    return $dataset;
});

test('engineering draft preserves every original vector status and source hash', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    PhaseZeroEngineeringReview::validate($pack);
    $source = PhaseZeroEngineeringReview::load('underwriting-vector-review.json');
    $sourceStatuses = array_column(PhaseZeroEngineeringReview::records($source, 'vectors'), 'source_status', 'id');
    $vectors = PhaseZeroEngineeringReview::records($pack, 'vector_dispositions');

    expect(array_column($vectors, 'source_status', 'id'))->toBe($sourceStatuses)
        ->and($pack['known_parameters'])->toMatchArray([
            'buyer_fee_bps' => 35, 'seller_fee_bps' => 35, 'secondary_minimum_gross_rwf' => 1000,
            'loan_floor_rwf' => 3000000, 'loan_ceiling_rwf' => 100000000, 'tenors_months' => [3, 4, 5, 6],
        ]);

    foreach ($pack['source_hashes'] as $filename => $hash) {
        expect(hash_file('sha256', __DIR__.'/../../docs/phase-0/'.$filename))->toBe($hash);
    }

    $dispositions = array_column($vectors, 'disposition', 'id');

    foreach (['GV-008', 'GV-010', 'GV-013', 'GV-031', 'GV-039', 'GV-046'] as $id) {
        expect($dispositions[$id])->toBe('REVISE_EXPECTATION_BEFORE_BASELINE');
    }

    foreach (['GV-020', 'GV-021', 'GV-040'] as $id) {
        expect($dispositions[$id])->toBe('RETAIN_ARITHMETIC_SCOPE_ONLY');
    }
});

test('engineering document covers amendments inputs actions and resolvable references without asserting freeze', function (): void {
    $directory = __DIR__.'/../../docs/phase-0/';
    $document = file_get_contents($directory.'engineering-contract-draft-2026-09-20.md');
    $plan = file_get_contents($directory.'../Rozine_Phased_Implementation_Plan.md');

    if ($document === false) {
        throw new UnexpectedValueException('Missing engineering contract draft.');
    }

    foreach (['AM' => 12, 'IN' => 12, 'AC' => 13] as $prefix => $count) {
        preg_match_all('/^\| ('.$prefix.'-\d{2})[ |]/m', $document, $ids);
        preg_match_all('/\b'.$prefix.'-\d{2}\b/', $document, $references);

        expect($ids[1])->toBe(array_map(fn (int $number): string => sprintf('%s-%02d', $prefix, $number), range(1, $count)))
            ->and(array_diff($references[0], $ids[1]))->toBe([]);
    }

    preg_match_all('/\]\((?!https?:\/\/)([^)]+)\)/', $document, $links);

    foreach ($links[1] as $link) {
        expect(is_file($directory.$link))->toBeTrue('Missing engineering reference: '.$link);
    }

    expect($document)->toContain(
        'DRAFT_FOR_JOINT_REVIEW - NOT_FROZEN - NOT_IMPLEMENTED',
        'ON_HOLD_BY_USER',
        'This is a proposed versioned amendment overlay',
        'No interpolation of 4/5-month premiums',
        'Original requested amount is now an input',
        'Do not substitute a day-45 reporting checkpoint',
        'Do not add the withdrawn fresh-acceptance/refund/re-consent proposal',
        'No outreach occurs in this task',
    )
        ->and($plan)->toBeString()->toContain(
            '](phase-0/engineering-contract-draft-2026-09-20.md)',
            '](phase-0/engineering-contract-fixtures-2026-09-20.json)',
            '37 arithmetic examples, 16 planned (not executed) state/race scenarios',
        )
        ->and($document)->toContain(hash_file('sha256', $directory.'engineering-contract-fixtures-2026-09-20.json'));
});

test('direct user review covers exactly the six selected unchanged sections without replacing independent approval', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    PhaseZeroEngineeringReview::validate($pack);
    $records = PhaseZeroEngineeringReview::records($pack, 'engineering_section_reviews');
    $document = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-draft-2026-09-20.md');

    if ($document === false) {
        throw new UnexpectedValueException('Missing reviewed document.');
    }

    expect(array_column($records, 'id'))->toBe($pack['approvals']['aminu_engineering']['scope_ids'])
        ->and(array_column($records, 'annotation_index'))->toBe([1, 2, 3, 4, 5, 6])
        ->and(array_column($records, 'user_response'))->toBe(['approved', 'correct', 'correct', 'correct', 'agreed', 'correct'])
        ->and(array_column($records, 'start_heading'))->toBe([
            '### Proposed exact operation order',
            '## 4. Shared wire and authorization proposal',
            '## 5. Proposed actions and logical records',
            '## 6. Secondary arithmetic, states and concurrency',
            "### Engineering choices proposed for Erastus's review",
            '## 7. Recovery, holds and policy transitions',
        ])
        ->and($pack['approvals']['erastus_engineering'])->toBeNull()
        ->and($pack['approvals']['independent_expected_results'])->toBeNull()
        ->and($document)->toContain(
            'AMINU_SELECTED_SECTIONS_APPROVED - ERASTUS_REVIEW_PENDING',
            "### 9.1 Aminu's recorded section review - 2026-09-21",
            'No missing input is filled by this approval.',
        );

    foreach ($records as $index => $record) {
        $start = strpos($document, $record['start_heading']);
        $end = strpos($document, $record['end_heading']);

        if ($start === false || $end === false || $end <= $start) {
            throw new UnexpectedValueException('Missing reviewed section boundaries.');
        }

        expect($record['end_heading'])->toBe($records[$index + 1]['start_heading'] ?? '## 8. Remaining input register - not a repeated questionnaire')
            ->and(hash('sha256', substr($document, $start, $end - $start)))->toBe($record['content_sha256'])
            ->and($document)->toContain('| '.$record['id'].' | '.$record['annotation_index'].' |');
    }
});

test('supplied owner answers populate the three inputs without inventing parameters or operational evidence', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    $reconciliation = $pack['answer_reconciliation'];
    $records = PhaseZeroEngineeringReview::records($reconciliation, 'inputs');
    $inputs = array_column($records, null, 'id');
    $document = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-draft-2026-09-20.md');

    expect($reconciliation['reviewed_on'])->toBe('2026-09-21')
        ->and(array_keys($inputs))->toBe(['IN-01', 'IN-02', 'IN-04'])
        ->and($inputs['IN-01']['state'])->toBe('ANSWER_INCORPORATED_WITH_PARAMETER_GAP')
        ->and($inputs['IN-01']['recorded'])->toBe([
            'tenors_months' => [3, 4, 5, 6],
            'retained_three_month_premium_bps' => 0,
            'retained_six_month_premium_bps' => 50,
            'premiums_origin' => 'EXISTING_PRICING_NOT_NEW_ROBERT_VALUES',
        ])
        ->and($inputs['IN-01']['recorded']['tenors_months'])->toBe($pack['known_parameters']['tenors_months'])
        ->and($inputs['IN-02']['state'])->toBe('ANSWER_INCORPORATED_CALCULATION_MAPPING_PENDING')
        ->and($inputs['IN-02']['recorded'])->toBe([
            'first_time_audited_history_months' => 36,
            'repeat_audited_consecutive_months' => 12,
            'repeat_only_exception' => true,
            'original_passed_baseline_required' => true,
            'minimum_fully_settled_notes' => 1,
            'maximum_late_payments' => 0,
            'all_unmonitored_gap_months_audited' => true,
            'revenue_cap_ttm_months' => 12,
            'repeat_monthly_dscr_numerator' => 135,
            'repeat_monthly_dscr_denominator' => 100,
            'projection_cap_median_revenue_numerator' => 12,
            'projection_cap_median_revenue_denominator' => 10,
            'collection_safeguards' => ['BANK_STANDING_ORDER', 'LOCKED_MOMO_COLLECTION_ACCOUNT'],
            'assistant_winsorization_approved' => false,
        ])
        ->and($inputs['IN-04']['state'])->toBe('ANSWER_INCORPORATED_EXECUTION_PARAMETERS_AND_EVIDENCE_PENDING')
        ->and($inputs['IN-04']['recorded'])->toBe([
            'secondary_ban_from_dpd' => 1,
            'grace_days' => [1, 7],
            'active_recovery_days' => [8, 21],
            'formal_default_stage_days' => [22, 30],
            'uncured_default_deadline_day' => 30,
            'final_capital_resolution_days' => [31, 45],
            'borrower_notice_frequency' => 'DAILY_SMS_AND_EMAIL',
            'investor_update_frequency' => 'WEEKLY_PORTAL',
            'penalty_interest_in_active_recovery' => true,
            'enforced_daily_repayment_plan' => true,
            'final_resolution_mechanisms' => ['LEGAL_LIQUIDATION', 'RESERVE_FUND_BUYOUT_OR_PAYOUT'],
            'day_45_is_progress_checkpoint_only' => false,
        ])
        ->and($inputs['IN-04']['operational_arrangements_verified'])->toBeFalse()
        ->and($document)->toBeString()->toContain(
            $pack['version'],
            '### 8.1 IN-01 - tenor answer incorporated',
            '### 8.2 IN-02 - history and repeat-track answers incorporated',
            '### 8.3 IN-04 - full recovery answer incorporated',
            '**DSCR >= 1.35 in every month of the proposed tenor**',
            '**Projected monthly cash flow capped at 1.2 times historical median revenue**',
            'Keep the full answer; do not recast day 45 as merely a reporting checkpoint.',
        );

    foreach ($records as $input) {
        expect($input['sources'])->toBeArray()->not->toBeEmpty();

        foreach ($input['missing_parameter_keys'] as $key) {
            expect($pack['missing_parameters'])->toHaveKey($key)
                ->and($pack['missing_parameters'][$key])->toBeNull();
        }
    }

    PhaseZeroEngineeringReview::validate($pack);
});

test('planned scenarios and partial vector examples reference known contracts without claiming runtime proof', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    $caseIds = array_column(PhaseZeroEngineeringReview::records($pack, 'numeric_cases'), 'id');
    $amendments = array_map(fn (int $number): string => sprintf('AM-%02d', $number), range(1, 12));
    $inputs = array_map(fn (int $number): string => sprintf('IN-%02d', $number), range(1, 12));
    $scenarios = PhaseZeroEngineeringReview::records($pack, 'planned_scenarios');

    expect(array_column($scenarios, 'id'))->toBe(array_map(fn (int $number): string => sprintf('SC-%02d', $number), range(1, 16)));

    foreach ($scenarios as $scenario) {
        expect(array_diff($scenario['amendment_ids'], $amendments))->toBe([])
            ->and(array_diff($scenario['required_input_ids'], $inputs))->toBe([])
            ->and($scenario['evidence_level'])->toBe('SCENARIO_ONLY_NOT_EXECUTED')
            ->and($scenario['implementation_verified'])->toBeFalse();
    }

    foreach (PhaseZeroEngineeringReview::records($pack, 'vector_dispositions') as $vector) {
        expect(array_diff($vector['partial_example_ids'], $caseIds))->toBe([])
            ->and(array_diff($vector['required_input_ids'], $inputs))->toBe([]);
    }

    foreach (PhaseZeroEngineeringReview::records($pack, 'numeric_cases') as $fixture) {
        expect(array_diff($fixture['amendment_ids'], $amendments))->toBe([]);
    }
});

test('proposed wire receipts use exact RWF strings and audience specific fee facts', function (): void {
    $example = PhaseZeroEngineeringReview::load()['wire_example'];

    expect($example['evidence_level'])->toBe('ILLUSTRATIVE_ONLY_NO_EXECUTED_TRADE');

    foreach (['buyer_receipt' => ['gross', 'buyer_fee', 'buyer_debit'], 'seller_receipt' => ['gross', 'seller_fee', 'seller_credit']] as $receipt => $fields) {
        foreach ($fields as $field) {
            expect($example[$receipt][$field]['currency'])->toBe('RWF')
                ->and($example[$receipt][$field]['amount'])->toBeString()->toMatch('/^(0|[1-9][0-9]*)$/');
        }
    }

    expect($example['buyer_receipt']['buyer_debit']['amount'])->toBe('10035')
        ->and($example['seller_receipt']['seller_credit']['amount'])->toBe('9965')
        ->and($example['buyer_receipt'])->not->toHaveKey('seller_credit')
        ->and($example['seller_receipt'])->not->toHaveKey('buyer_debit');
});

test('engineering review rejects fabricated approval implementation or silent defaults :dataset', function (string $mutation): void {
    $pack = PhaseZeroEngineeringReview::load();

    switch ($mutation) {
        case 'activation':
            $pack['production_activation'] = true;
            break;
        case 'phase one':
            $pack['phase_1_development_authorized'] = true;
            break;
        case 'signature':
            $pack['approvals']['erastus_engineering'] = 'Approved';
            break;
        case 'erase recorded approval':
            $pack['approvals']['aminu_engineering'] = null;
            break;
        case 'full contract freeze':
            $pack['approvals']['aminu_engineering']['full_contract_freeze'] = true;
            break;
        case 'expand review scope':
            $pack['approvals']['aminu_engineering']['scope_ids'][] = 'IN-01';
            break;
        case 'independent approval':
            $pack['approvals']['independent_expected_results'] = 'Approved';
            break;
        case 'default':
            $pack['missing_parameters']['penalty_rate'] = 0;
            break;
        case 'invented tenor premium':
            $pack['missing_parameters']['four_month_premium'] = 25;
            break;
        case 'invented seasonality formula':
            $pack['missing_parameters']['standard_nocf_method'] = 'ASSUMED_MEAN';
            break;
        case 'invented reserve arrangement':
            $pack['missing_parameters']['reserve_funding_arrangement'] = 'FUNDED';
            break;
        case 'baseline':
            $pack['vector_dispositions'][0]['baselined'] = true;
            break;
        case 'runtime race':
            $pack['planned_scenarios'][0]['implementation_verified'] = true;
            break;
        case 'duplicate example':
            $pack['numeric_cases'][1]['id'] = $pack['numeric_cases'][0]['id'];
            break;
    }

    expect(fn () => PhaseZeroEngineeringReview::validate($pack))->toThrow(UnexpectedValueException::class);
})->with(['activation', 'phase one', 'signature', 'erase recorded approval', 'full contract freeze', 'expand review scope', 'independent approval', 'default', 'invented tenor premium', 'invented seasonality formula', 'invented reserve arrangement', 'baseline', 'runtime race', 'duplicate example']);

test('unknown review arithmetic is rejected', function (): void {
    expect(fn () => PhaseZeroEngineeringReview::calculate('production_engine', []))->toThrow(UnexpectedValueException::class);
});
