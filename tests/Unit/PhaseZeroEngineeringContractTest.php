<?php

declare(strict_types=1);
use Illuminate\Support\Arr;

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
        if (($pack['status'] ?? null) !== 'MVP_SPECIFICATION_DEFAULTS_BASELINED_NOT_IMPLEMENTED'
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
                'erastus_engineering' => [
                    'status' => 'REVIEW_RECORDED_WITH_AMENDMENTS',
                    'reviewer' => 'Engineersticity',
                    'reviewed_at' => '2026-09-21T17:31:17Z',
                    'source_url' => 'https://github.com/rozine-rw/rozine/issues/91#issuecomment-5764744196',
                    'reviewed_version' => 'engineering-2026-09-20.3',
                    'reviewed_fixture_sha256' => 'afa73ed3277e86b356437fe456c8278e1c4acc1451264fb7e6ea3d7610db4b48',
                    'dispositions' => ['AR-01' => 'AGREE', 'AR-02' => 'AGREE_WITH_AMENDMENTS', 'AR-03' => 'AGREE', 'AR-04' => 'AGREE', 'AR-05' => 'CHANGE_REQUIRED', 'AR-06' => 'AGREE'],
                    'full_contract_freeze' => false,
                ],
                'independent_expected_results' => [
                    'status' => 'REVIEWER_REPORTED_MATCH',
                    'reviewer' => 'Engineersticity',
                    'source_url' => 'https://github.com/rozine-rw/rozine/issues/91#issuecomment-5764744196',
                    'reviewed_version' => 'engineering-2026-09-20.3',
                    'reviewed_fixture_sha256' => 'afa73ed3277e86b356437fe456c8278e1c4acc1451264fb7e6ea3d7610db4b48',
                    'numeric_case_count' => 37,
                    'vector_disposition_count' => 48,
                    'historical_arithmetic_ids' => ['GV-020', 'GV-021', 'GV-026', 'GV-040'],
                    'numeric_cases_sha256' => '4768c906fa4036ab6cb3ebd72ee3d4329664e4d99eca0489b3ae7912769d495c',
                    'vector_dispositions_sha256' => 'bd12eea5b9c26594b1cff175696dd874d0d25e940b71c8f8dc0be69b77e9c7da',
                    'current_amendment_examples_reviewed' => false,
                    'full_contract_freeze' => false,
                ],
                'aminu_amendments' => [
                    'status' => 'ACCEPTED_CHANGES_RECORDED',
                    'recorded_on' => '2026-09-22',
                    'evidence_type' => 'DIRECT_USER_MESSAGES',
                    'scope_ids' => ['AR-02', 'AR-05'],
                    'amendment_ids' => ['A2-1', 'A2-2', 'A2-3', 'A5-1', 'A5-2', 'A5-3'],
                    'user_responses' => ['Okay, agreed with what Erastus proposed for AR-02', "You're correct with your intuition for the locking mechanism", 'I agree with you'],
                    'clarifications' => ['ALL_REQUIRED_NOTE_LOCKS_BEFORE_WALLETS_FOR_CROSS_NOTE_COMMANDS', 'NO_AUTO_INTER_LISTING_COMBINATION_SELLER_SAME_NOTE_BUNDLING_RETAINED'],
                    'revised_hash_reviewed' => false,
                    'full_contract_freeze' => false,
                ],
            ]
            || ($pack['missing_parameters'] ?? null) !== array_fill_keys([
                'reserve_funding_arrangement',
            ], null)) {
            throw new UnexpectedValueException('Unapproved review authority or missing-input default.');
        }

        $defaultsHash = hash('sha256', json_encode($pack['accepted_defaults'] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        if (($pack['version'] ?? null) !== 'engineering-2026-09-23.4'
            || ($pack['prior_snapshot'] ?? null) !== [
                'version' => 'engineering-2026-09-22.1',
                'fixture_sha256' => '53e20605a45b30065e29de211836f2f5cf1b0800ab924beefcde69a3c88e352c',
            ]
            || $defaultsHash !== '46a3ac2261fd436bb911e69452de4ccb78c92b6f42848b31e3b5836386673c15') {
            throw new UnexpectedValueException('Accepted defaults, provenance or remaining review gates changed.');
        }

        $financialHash = hash('sha256', json_encode($pack['financial_defaults'] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        if (($pack['accepted_defaults_snapshot_version'] ?? null) !== 'engineering-2026-09-23.1'
            || ($pack['financial_defaults_previous_snapshot'] ?? null) !== [
                'version' => 'engineering-2026-09-23.1',
                'fixture_sha256' => '8e1c4bd6043f4610c9556ef093ec4e542e0eeda4f3365fb9bce916d8ed666639',
            ]
            || $financialHash !== '063165e32fa9d3471ca0e4f76342dc9a8dd1d4251d3e691779cdd0c145fe0f33') {
            throw new UnexpectedValueException('Financial design acceptance changed or claims unevidenced authority.');
        }

        if (($pack['completion_previous_snapshot'] ?? null) !== [
            'version' => 'engineering-2026-09-23.2',
            'fixture_sha256' => '227cb86bb7ff8e11f73650a50eea8dc2a5fbba03b4aefdeda7a857c0395661ee',
        ] || ($pack['current_review_status'] ?? null) !== [
            'recorded_on' => '2026-09-23',
            'source' => 'DIRECT_USER_REPORT_OF_OWNER_GO_AHEAD',
            'verbatim' => 'what does legal/owner needs to review again, they already gave the go ahead because these are minor decisions',
            'accepted_default_policy_approval' => 'CONFIRMED_AS_REPORTED_BY_AMINU',
            'repeat_minor_policy_approval_required' => false,
            'scope' => ['accepted_defaults', 'financial_defaults'],
            'supersedes_historical_pending_owner_review_flags' => true,
            'direct_robert_kimani_signature_claimed' => false,
            'external_clearance_verified' => false,
            'reserve_funding_verified' => false,
            'provider_evidence_verified' => false,
            'erastus_current_candidate_reviewed' => false,
            'full_contract_freeze' => false,
        ]) {
            throw new UnexpectedValueException('User-reported authority was lost or broadened to unevidenced approval.');
        }

        $completionHash = hash('sha256', json_encode($pack['completion_candidate'] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        if ($completionHash !== '15bba3b48872062124f235ee04177920711a62361575e83d5103e515ebce60d6') {
            throw new UnexpectedValueException('Completion candidate changed or silently supplies missing configuration.');
        }

        $mvpHash = hash('sha256', json_encode($pack['mvp_baseline'] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        if (($pack['effective_authority_record'] ?? null) !== 'mvp_baseline'
            || ($pack['mvp_previous_snapshot'] ?? null) !== [
                'version' => 'engineering-2026-09-23.3',
                'fixture_sha256' => '5ab03735062470596aa5ef7eba747fb890a29eab4f13a5bcb707789837359c53',
            ]
            || $mvpHash !== 'e5a4ba40ad2288ea2feef66324670f3523828fd5c0a5280ca7fae424bd340fe0') {
            throw new UnexpectedValueException('MVP defaults, current authority or review waiver changed.');
        }

        $cases = self::records($pack, 'numeric_cases');
        $ids = array_column($cases, 'id');

        if ($ids !== array_map(fn (int $number): string => sprintf('EC-%03d', $number), range(1, 37))) {
            throw new UnexpectedValueException('Numeric example coverage changed.');
        }

        $vectors = self::records($pack, 'vector_dispositions');

        foreach (['numeric_cases', 'vector_dispositions'] as $collection) {
            $hash = hash('sha256', json_encode($pack[$collection], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            if ($hash !== $pack['approvals']['independent_expected_results'][$collection.'_sha256']) {
                throw new UnexpectedValueException('Original independent review does not cover changed examples.');
            }
        }

        if (($pack['engineering_amendments'] ?? null) !== [
            'idempotency_scope' => ['AUTHENTICATED_PARTY', 'COMMAND_NAME', 'KEY'],
            'body_hash_fields' => 'CANONICAL_JSON_PERMITTED_USER_FIELDS_ONLY',
            'retention_extra_seconds' => 86400,
            'seven_day_command_minimum_retention_seconds' => 691200,
            'resource_not_visible_http_status' => 404,
            'visible_action_forbidden_http_status' => 403,
            'optimistic_financial_ui' => false,
            'countdown_clock' => 'SERVER_TIME_OFFSET',
            'lock_order' => ['MARKET_GATE', 'ALL_REQUIRED_NOTE_GATES_ASCENDING_ID', 'DISCOVER_CANDIDATES', 'PARTIES_WALLETS_ASCENDING_ID', 'ORDERS_HOLDINGS_ASCENDING_ID', 'REVALIDATE'],
            'new_note_lock_while_holding_wallet' => false,
            'retry_idempotency_key' => 'ORIGINAL_KEY',
            'fixed_ask_checkout_buffer' => false,
            'sub_minimum_candidate' => 'SKIP_SINGLE_LISTING_THEN_WAIT_IF_NONE_QUALIFIES',
            'automatic_inter_listing_combination' => false,
            'seller_same_note_available_unit_bundling' => true,
            'terminal_bid_states' => ['FILLED', 'CANCELLED', 'EXPIRED', 'HALTED', 'INELIGIBLE'],
            'implementation_verified' => false,
        ]) {
            throw new UnexpectedValueException('Accepted engineering amendment changed or claims implementation.');
        }

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
     * @param  array<string, mixed>  $example
     * @return array{numerator: int, denominator: int}
     */
    public static function monthlyCfads(array $example): array
    {
        $months = self::integer($example, 'months');
        $count = 0;
        $nocf = 0;

        foreach (self::records($example, 'nocf_runs') as $run) {
            $runMonths = self::integer($run, 'months');

            if ($runMonths <= 0) {
                throw new UnexpectedValueException('Invalid synthetic month count.');
            }

            $count += $runMonths;
            $nocf += $runMonths * self::integer($run, 'nocf_rwf');
        }

        if (! in_array($months, [12, 36], true) || $count !== $months) {
            throw new UnexpectedValueException('Incomplete observation window.');
        }

        $draw = max(self::integer($example, 'draw_total_rwf'), $months * self::integer($example, 'draw_commitment_rwf'));
        $debt = max(self::integer($example, 'debt_total_rwf'), $months * self::integer($example, 'peak_monthly_debt_rwf'));
        $numerator = $nocf - $draw - $debt;
        $fraction = self::reducedFraction(abs($numerator), $months);

        return ['numerator' => ($numerator < 0 ? -1 : 1) * $fraction['numerator'], 'denominator' => $fraction['denominator']];
    }

    /** @return non-empty-list<int> */
    public static function componentAllocation(int $component, int $units): array
    {
        if ($component < 0 || $units <= 0) {
            throw new UnexpectedValueException('Invalid immutable unit component.');
        }

        return array_map(
            fn (int $ordinal): int => intdiv($component, $units) + ($ordinal <= $component % $units ? 1 : 0),
            range(1, $units),
        );
    }

    public static function mvpQuantizedPrincipal(int $capacity): int
    {
        if ($capacity < 0) {
            throw new UnexpectedValueException('Capacity must not be negative.');
        }

        $grid = self::integer(self::load()['mvp_baseline']['configuration']['CFG-02']['issuance_unit_grid'], 'primary_face_rwf');
        $principal = intdiv($capacity, $grid) * $grid;

        return $principal >= 3000000 ? $principal : 0;
    }

    /** @return array{allocation: non-empty-list<int>, next_cursor: int} */
    public static function cyclicComponentAllocation(int $component, int $units, int $cursor): array
    {
        if ($component < 0 || $units <= 0 || $cursor < 0 || $cursor >= $units) {
            throw new UnexpectedValueException('Invalid cyclic component allocation.');
        }

        $allocation = array_fill(0, $units, intdiv($component, $units));
        $remainder = $component % $units;

        for ($offset = 0; $offset < $remainder; $offset++) {
            $allocation[($cursor + $offset) % $units]++;
        }

        return ['allocation' => array_values($allocation), 'next_cursor' => ($cursor + $remainder) % $units];
    }

    /**
     * @param  non-empty-list<int>  $unpaid
     * @return array{allocation: non-empty-list<int>, unapplied: int}
     */
    public static function distributeReceipt(int $receipt, array $unpaid): array
    {
        if ($receipt < 0 || min($unpaid) < 0) {
            throw new UnexpectedValueException('Receipt and unpaid rights must be nonnegative.');
        }

        $total = array_sum($unpaid);
        $applied = min($receipt, $total);
        $allocation = array_fill(0, count($unpaid), 0);

        if ($total === 0) {
            return ['allocation' => $allocation, 'unapplied' => $receipt];
        }

        $remainders = [];

        foreach ($unpaid as $ordinal => $amount) {
            $allocation[$ordinal] = intdiv($applied * $amount, $total);
            $remainders[$ordinal] = ($applied * $amount) % $total;
        }

        $ordinals = array_keys($unpaid);
        usort($ordinals, fn (int $left, int $right): int => ($remainders[$right] <=> $remainders[$left]) ?: ($left <=> $right));

        for ($offset = 0, $residual = $applied - array_sum($allocation); $offset < $residual; $offset++) {
            $allocation[$ordinals[$offset]]++;
        }

        return ['allocation' => array_values($allocation), 'unapplied' => $receipt - $applied];
    }

    public static function dueDate(string $anchor, int $monthOffset): string
    {
        if ($monthOffset < 1) {
            throw new UnexpectedValueException('First instalment must be at least one month after disbursement.');
        }

        $date = new DateTimeImmutable($anchor, new DateTimeZone('Africa/Kigali'));
        $target = $date->modify('first day of this month')->modify('+'.$monthOffset.' months');

        return $target->setDate((int) $target->format('Y'), (int) $target->format('m'), min((int) $date->format('j'), (int) $target->format('t')))->format('Y-m-d');
    }

    public static function withinInvestorLimits(bool $verified, int $gross, int $notePrincipal, int $originalIssue, int $businessPrincipal, int $aggregatePrincipal, bool $connected = false): bool
    {
        $limits = self::load()['mvp_baseline']['configuration']['CFG-01']['investor_limit_matrix'];

        return $verified && ! $connected
            && $gross > 0 && $gross <= $limits['verified_transaction_gross_limit_rwf']
            && $notePrincipal >= 0 && $notePrincipal <= $limits['per_note_principal_limit_rwf']
            && $originalIssue > 0 && $notePrincipal * $limits['per_note_issue_fraction_denominator'] <= $originalIssue * $limits['per_note_issue_fraction_numerator']
            && $businessPrincipal >= $notePrincipal && $businessPrincipal <= $limits['per_business_principal_limit_rwf']
            && $aggregatePrincipal >= $businessPrincipal && $aggregatePrincipal <= $limits['aggregate_principal_limit_rwf'];
    }

    public static function failedClosingDisposition(string $paymentState): string
    {
        return match ($paymentState) {
            'NOT_DISPATCHED', 'VERIFIED_FAILED' => 'REFUND_ONCE_NO_FEE_RELEASE_EXPOSURE',
            'DISPATCHED', 'UNKNOWN' => 'PRESERVE_RECONCILE_NO_SECOND_PAYMENT',
            'VERIFIED_SUCCEEDED', 'ISSUED' => 'KEEP_PURCHASED_TERMS',
            default => throw new UnexpectedValueException('Unknown payment state.'),
        };
    }

    /** @return array{state: string, effect: string} */
    public static function providerOutcome(string $state, string $incoming, bool $verified): array
    {
        if (! $verified) {
            return ['state' => $state, 'effect' => 'NO_FINANCIAL_EFFECT'];
        }

        if (in_array($state, ['SUCCEEDED', 'FAILED_FINAL'], true)) {
            return ['state' => $state, 'effect' => $state === $incoming || $incoming === 'PENDING' ? 'NO_FINANCIAL_EFFECT' : 'RECONCILIATION_CASE'];
        }

        return match ($incoming) {
            'SUCCEEDED' => ['state' => 'SUCCEEDED', 'effect' => 'POST_ONCE'],
            'FAILED_FINAL' => ['state' => 'FAILED_FINAL', 'effect' => 'RELEASE_ONCE'],
            'UNKNOWN' => ['state' => 'UNKNOWN', 'effect' => 'PRESERVE_RESERVATION'],
            'PENDING' => ['state' => 'PENDING', 'effect' => 'PRESERVE_RESERVATION'],
            default => throw new UnexpectedValueException('Unknown provider outcome.'),
        };
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

test('as-is input confirmation preserves the prior snapshot identity and unresolved execution gates', function (): void {
    $directory = __DIR__.'/../../docs/phase-0/';
    $document = file_get_contents($directory.'engineering-contract-draft-2026-09-20.md');
    $plan = file_get_contents($directory.'../Rozine_Phased_Implementation_Plan.md');
    $pack = PhaseZeroEngineeringReview::load();
    PhaseZeroEngineeringReview::validate($pack);

    expect($document)->toBeString()->toContain(
        "### 9.3 Aminu's as-is input confirmation - 2026-09-23",
        'lets close and confirm all inputs as it is',
        'CONFIRMED_AS_WRITTEN',
        'resolution of all executable inputs and joint contract freeze are not complete',
        'These gaps are not waived or moved to a later phase',
        'Phase 1 remains ON_HOLD_BY_USER',
    )
        ->and($plan)->toBeString()->toContain('As-is input confirmation 2026-09-23', 'CONFIRMED_AS_WRITTEN')
        ->and($pack['prior_snapshot']['fixture_sha256'])
        ->toBe('53e20605a45b30065e29de211836f2f5cf1b0800ab924beefcde69a3c88e352c')
        ->and($pack['production_activation'])->toBeFalse()
        ->and($pack['phase_1_development_authorized'])->toBeFalse();

    foreach ($pack['missing_parameters'] as $parameter => $value) {
        expect($value)->toBeNull()
            ->and($document)->toContain('`'.$parameter.'`');
    }
});

test('historical user reviews and amended sections retain distinct hashes and approval scopes', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    PhaseZeroEngineeringReview::validate($pack);
    $records = PhaseZeroEngineeringReview::records($pack, 'engineering_section_reviews');
    $amended = array_column(PhaseZeroEngineeringReview::records($pack, 'amended_section_reviews'), null, 'id');
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
        ->and(array_keys($amended))->toBe(['AR-02', 'AR-05'])
        ->and($pack['approvals']['erastus_engineering']['reviewed_version'])->not->toBe($pack['version'])
        ->and($pack['approvals']['independent_expected_results']['current_amendment_examples_reviewed'])->toBeFalse()
        ->and($document)->toContain(
            'ERASTUS_REVIEW_RECORDED - AMINU_AMENDMENTS_ACCEPTED',
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
            ->and(hash('sha256', substr($document, $start, $end - $start)))->toBe($amended[$record['id']]['content_sha256'] ?? $record['content_sha256'])
            ->and($document)->toContain('| '.$record['id'].' | '.$record['annotation_index'].' |');

        if (isset($amended[$record['id']])) {
            expect($amended[$record['id']]['previous_content_sha256'])->toBe($record['content_sha256'])
                ->and($amended[$record['id']]['content_sha256'])->not->toBe($record['content_sha256'])
                ->and($amended[$record['id']]['scope'])->toBe('ACCEPTED_AMENDMENT_TRANSCRIPTION_NOT_NEW_HASH_SIGNOFF')
                ->and($amended[$record['id']]['erastus_revised_hash_reviewed'])->toBeFalse();
        }
    }
});

test('supplied owner answers populate the three inputs without inventing parameters or operational evidence', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    $reconciliation = $pack['answer_reconciliation'];
    $records = PhaseZeroEngineeringReview::records($reconciliation, 'inputs');
    $inputs = array_column($records, null, 'id');
    $document = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-draft-2026-09-20.md');

    expect($reconciliation['reviewed_on'])->toBe('2026-09-21')
        ->and($pack['answer_reconciliation_snapshot_version'])->toBe('engineering-2026-09-22.1')
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
            if (in_array($key, [...$pack['accepted_defaults']['resolved_parameter_keys'], ...$pack['financial_defaults']['resolved_parameter_keys']], true)) {
                expect($pack['missing_parameters'])->not->toHaveKey($key);

                continue;
            }

            expect($pack['missing_parameters'])->toHaveKey($key)
                ->and($pack['missing_parameters'][$key])->toBeNull();
        }
    }

    PhaseZeroEngineeringReview::validate($pack);
});

test('accepted defaults record Aminu authority without inventing other approvals or operational arrangements', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    PhaseZeroEngineeringReview::validate($pack);
    $defaults = $pack['accepted_defaults'];
    $directory = __DIR__.'/../../docs/phase-0/';
    $document = file_get_contents($directory.'engineering-contract-draft-2026-09-20.md');
    $plan = file_get_contents($directory.'../Rozine_Phased_Implementation_Plan.md');

    expect($defaults['verbatim'])->toBe('go with your proposals')
        ->and($defaults['origin'])->toBe('ASSISTANT_PROPOSALS_ACCEPTED_BY_AMINU_NOT_ROBERT_KIMANI_ANSWERS')
        ->and($defaults['resolved_parameter_keys'])->toBe([
            'four_month_premium', 'five_month_premium', 'standard_nocf_method', 'hold_business_calendar',
        ])
        ->and(array_unique(array_values($defaults['required_reviews'])))->toBe(['PENDING'])
        ->and($defaults['full_contract_freeze'])->toBeFalse()
        ->and($defaults['implementation_verified'])->toBeFalse()
        ->and($defaults['penalty'])->toBe([
            'basis' => 'OVERDUE_PRINCIPAL_ONLY',
            'interest' => 'SIMPLE_NON_COMPOUNDING',
            'rate_and_accrual_schedule' => 'UNRESOLVED_NO_ZERO_DEFAULT',
        ])
        ->and($defaults['hold_calendar']['holiday_dataset_verified'])->toBeFalse()
        ->and($pack['default_examples_scope'])->toBe('SYNTHETIC_SPECIFICATION_ONLY_NOT_CREDIT_CALIBRATION_OR_OFFICIAL_HOLIDAY_EVIDENCE')
        ->and($document)->toBeString()->toContain(
            '### 8.4 Explicit defaults accepted by Aminu - 2026-09-23',
            '### 9.4 Aminu accepts the explicit proposals - 2026-09-23',
            'not backdated Robert/Kimani answers or production configuration',
            'No reserve default',
            'go with your proposals',
        )
        ->and($plan)->toBeString()->toContain('Explicit defaults accepted 2026-09-23', $pack['version']);
});

test('accepted tenor premiums retain exact fractions through final return rounding :dataset', function (array $example): void {
    $pack = PhaseZeroEngineeringReview::load();
    $pricing = $pack['accepted_defaults']['pricing'];
    $rating = PhaseZeroEngineeringReview::integer($example, 'rating_hundredths');
    $tenor = PhaseZeroEngineeringReview::integer($example, 'tenor_months');
    $premium = $pricing['premiums_by_tenor'][$tenor];
    $premiumNumerator = PhaseZeroEngineeringReview::integer($premium, 'numerator');
    $premiumDenominator = PhaseZeroEngineeringReview::integer($premium, 'denominator');

    $denominator = 500 * $premiumDenominator;
    $numerator = (5000 + 8 * (500 - $rating)) * $premiumDenominator + $premiumNumerator * 500;
    $bounded = max($pricing['minimum_percent'] * $denominator, min($pricing['maximum_percent'] * $denominator, $numerator));

    expect(PhaseZeroEngineeringReview::halfUp($bounded * 10, $denominator))
        ->toBe($example['expected_return_tenths_percent']);
})->with(function (): array {
    return array_map(fn (array $example): array => [$example], PhaseZeroEngineeringReview::records(PhaseZeroEngineeringReview::load(), 'default_pricing_examples'));
});

test('accepted cash flow examples retain seasonal and negative observations without treating missing months as zero', function (): void {
    $rules = PhaseZeroEngineeringReview::load()['accepted_defaults']['nocf'];

    foreach (['first_time_months' => 36, 'eligible_repeat_months' => 12] as $window => $months) {
        expect($rules[$window])->toBe($months);
        $observations = [...array_fill(0, $months - 2, 1000000), 6000000, -2000000];

        expect(count($observations))->toBe($months)
            ->and(array_sum($observations))->toBe(($months + 2) * 1000000)
            ->and(PhaseZeroEngineeringReview::reducedFraction(array_sum($observations), $months))
            ->toBe($months === 36
                ? ['numerator' => 9500000, 'denominator' => 9]
                : ['numerator' => 3500000, 'denominator' => 3]);

        $observations[$months - 1] = null;
        expect(count(array_filter($observations, fn (?int $observation): bool => $observation !== null)))
            ->toBe($months - 1)
            ->and($rules['missing_observation'])->toBe('UNAVAILABLE_NOT_ZERO');
    }

    expect($rules['automatic_trimming'])->toBeFalse()
        ->and($rules['retain_negative_months'])->toBeTrue()
        ->and($rules['retain_seasonal_peaks'])->toBeTrue()
        ->and($rules['deductions'])->toBe('CONFIRMED_OWNER_DRAW_AND_EXISTING_DEBT_SERVICE_ONCE');
});

test('accepted projections use corresponding audited months without growth or replacing existing credit limits', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    $rules = $pack['accepted_defaults']['projection'];
    $recorded = $pack['answer_reconciliation']['inputs'][1]['recorded'];

    expect($rules['method'])->toBe('CORRESPONDING_CALENDAR_MONTH_LATEST_AUDITED_YEAR')
        ->and($rules['assumed_growth_numerator'])->toBe(0)
        ->and($rules['assumed_growth_denominator'])->toBe(1)
        ->and($rules['missing_source_month'])->toBe('UNAVAILABLE_NOT_ZERO')
        ->and($rules['repeat_projection_cap_and_monthly_dscr'])->toBe('UNCHANGED_RECORDED_RULES')
        ->and($rules['final_standard_dscr'])->toBe('UNCHANGED_RECORDED_RULE')
        ->and([$recorded['projection_cap_median_revenue_numerator'], $recorded['projection_cap_median_revenue_denominator']])->toBe([12, 10])
        ->and([$recorded['repeat_monthly_dscr_numerator'], $recorded['repeat_monthly_dscr_denominator']])->toBe([135, 100])
        ->and([$pack['known_parameters']['final_dscr_numerator'], $pack['known_parameters']['final_dscr_denominator']])->toBe([3, 2]);
});

test('accepted hold deadlines count five subsequent eligible dates in Kigali :dataset', function (array $example): void {
    $calendar = PhaseZeroEngineeringReview::load()['accepted_defaults']['hold_calendar'];
    $flag = new DateTimeImmutable($example['flag']);
    $deadline = $flag->setTimezone(new DateTimeZone($calendar['timezone']));
    $counted = 0;

    while ($counted < $calendar['business_dates_to_add']) {
        $deadline = $deadline->modify('+1 day');

        if (in_array((int) $deadline->format('N'), $calendar['eligible_iso_weekdays'], true)
            && ! in_array($deadline->format('Y-m-d'), $example['synthetic_holidays'], true)) {
            $counted++;
        }
    }

    expect($deadline->format(DateTimeInterface::ATOM))->toBe($example['expected'])
        ->and($deadline->format('H:i:s'))->toBe($flag->format('H:i:s'))
        ->and($calendar['origin'])->toBe('ORIGINAL_FLAG')
        ->and($calendar['include_origin_date'])->toBeFalse()
        ->and($calendar['confirmation_restarts_clock'])->toBeFalse()
        ->and($calendar['unconfirmed_hold_elapsed_seconds'])->toBe(24 * 60 * 60);
})->with(function (): array {
    return array_map(fn (array $example): array => [$example], PhaseZeroEngineeringReview::records(PhaseZeroEngineeringReview::load(), 'default_hold_examples'));
});

test('financial package records the annotated design approval without fabricating funding or signoffs', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    PhaseZeroEngineeringReview::validate($pack);
    $financial = $pack['financial_defaults'];
    $document = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-draft-2026-09-20.md');
    $plan = file_get_contents(__DIR__.'/../../docs/Rozine_Phased_Implementation_Plan.md');

    expect($financial['verbatim'])->toBe('go ahead')
        ->and($financial['annotation_index'])->toBe(1)
        ->and($financial['source_message_id'])->toBe('msg_08a35f686d830a89016ab37ebe9cf887d0a04df44f98c2a91f')
        ->and($financial['supersedes'])->toBe(['accepted_defaults.penalty.rate_and_accrual_schedule'])
        ->and($financial['resolved_parameter_keys'])->toBe(['penalty_rate'])
        ->and($pack['missing_parameters'])->toBe(['reserve_funding_arrangement' => null])
        ->and(array_unique(array_values($financial['required_reviews'])))->toBe(['PENDING'])
        ->and(array_unique(array_values($financial['evidence_verified'])))->toBe([false])
        ->and($financial['full_contract_freeze'])->toBeFalse()
        ->and($financial['implementation_verified'])->toBeFalse()
        ->and($financial['live_lending_or_payout_authorized'])->toBeFalse()
        ->and($financial['penalty']['annual_rate_bps'])->toBe(200)
        ->and($financial['penalty']['simple_non_compounding'])->toBeTrue()
        ->and($financial['penalty']['automatic_collection_cost_surcharge'])->toBeFalse()
        ->and($financial['penalty']['stop_events'])->toBe(['REPAYMENT_OF_THAT_PRINCIPAL', 'WRITE_OFF', 'APPLICABLE_LEGAL_RESTRICTION'])
        ->and($pack['financial_examples_scope'])->toBe('SYNTHETIC_SPECIFICATION_ONLY_NOT_RUNTIME_OR_FUNDED_PROTECTION_PROOF')
        ->and($document)->toBeString()->toContain(
            '### 8.5 Financial-default package accepted by Aminu - 2026-09-23',
            '### 9.5 Aminu accepts the financial-default package - 2026-09-23',
            'not a regulatory rate or validated market benchmark',
            'not evidence that capital exists',
            'No actual account, funding transfer, borrowing commitment, recovery collection or payout is created here',
        )
        ->and($plan)->toBeString()->toContain('Financial-default package accepted 2026-09-23', $pack['version']);
});

test('financial penalty examples preserve grace actual year and cumulative rounding :dataset', function (array $example): void {
    $rules = PhaseZeroEngineeringReview::load()['financial_defaults']['penalty'];
    $accrued = ['numerator' => 0, 'denominator' => 1];
    $posted = 0;
    $postings = [];

    foreach (PhaseZeroEngineeringReview::records($example, 'segments') as $segment) {
        $year = PhaseZeroEngineeringReview::integer($segment, 'year');
        $daysInYear = (new DateTimeImmutable($year.'-01-01'))->format('L') === '1' ? 366 : 365;
        $graceDaysRemaining = max(0, $rules['first_chargeable_dpd'] - PhaseZeroEngineeringReview::integer($segment, 'first_dpd'));
        $chargeableDays = $segment['stopped'] ? 0 : max(0, PhaseZeroEngineeringReview::integer($segment, 'days') - $graceDaysRemaining);
        $dailyNumerator = PhaseZeroEngineeringReview::integer($segment, 'principal_rwf') * $rules['annual_rate_bps'];
        $dailyDenominator = 10000 * $daysInYear;

        for ($day = 0; $day < $chargeableDays; $day++) {
            $accrued = PhaseZeroEngineeringReview::reducedFraction(
                $accrued['numerator'] * $dailyDenominator + $dailyNumerator * $accrued['denominator'],
                $accrued['denominator'] * $dailyDenominator,
            );
            $newCumulative = PhaseZeroEngineeringReview::halfUp($accrued['numerator'], $accrued['denominator']);
            $postings[] = $newCumulative - $posted;
            $posted = $newCumulative;
        }
    }

    expect($posted)->toBe($example['expected_accrued_rwf'])
        ->and(array_sum($postings))->toBe($posted)
        ->and($rules['backdated_accrual'])->toBeFalse()
        ->and($rules['day_21_stops_accrual'])->toBeFalse();

    if ($example['id'] === 'FP-06') {
        expect($postings)->toBe([164, 165]);
    }
})->with(function (): array {
    $examples = PhaseZeroEngineeringReview::records(PhaseZeroEngineeringReview::load(), 'financial_penalty_examples');

    return array_combine(array_column($examples, 'id'), array_map(fn (array $example): array => [$example], $examples));
});

test('financial daily recovery targets use inclusive remaining days and do not rewrite investor schedules', function (): void {
    $rules = PhaseZeroEngineeringReview::load()['financial_defaults']['daily_recovery_plan'];
    $remainingArrears = 1400000;

    expect($rules['first_dpd'])->toBe(8)
        ->and($rules['last_dpd_inclusive'])->toBe(21)
        ->and(PhaseZeroEngineeringReview::reducedFraction($remainingArrears, 21 - 8 + 1))
        ->toBe(['numerator' => 100000, 'denominator' => 1]);

    $remainingArrears -= 200000;

    expect(PhaseZeroEngineeringReview::reducedFraction($remainingArrears, 21 - 9 + 1))
        ->toBe(['numerator' => 1200000, 'denominator' => 13])
        ->and($rules['recalculate_after_receipts'])->toBeTrue()
        ->and($rules['recovery_team_affordability_review_required'])->toBeTrue()
        ->and($rules['rewrite_issued_investor_schedule'])->toBeFalse();
});

test('financial payment allocation clears oldest overdue principal before return and penalties', function (): void {
    $financial = PhaseZeroEngineeringReview::load()['financial_defaults'];
    $buckets = ['older_overdue_principal' => 100000, 'newer_overdue_principal' => 200000, 'due_return' => 20000, 'penalties' => 5000];
    $receipt = 310000;
    $allocated = [];

    foreach ($buckets as $bucket => $balance) {
        $allocated[$bucket] = min($receipt, $balance);
        $receipt -= $allocated[$bucket];
    }

    expect($financial['payment_allocation'])->toBe(['OLDEST_OVERDUE_PRINCIPAL', 'DUE_CONTRACTUAL_INTEREST_OR_RETURN', 'PENALTIES'])
        ->and($allocated)->toBe(['older_overdue_principal' => 100000, 'newer_overdue_principal' => 200000, 'due_return' => 10000, 'penalties' => 0])
        ->and(array_sum($allocated))->toBe(310000)
        ->and($receipt)->toBe(0);
});

test('financial reserve backing includes new protected commitments and rejects missing evidence :dataset', function (array $example): void {
    $rules = PhaseZeroEngineeringReview::load()['financial_defaults']['reserve'];
    $obligation = $example['protected_outstanding_rwf'] + $example['new_protected_commitment_rwf'];
    $available = $example['verified_available_backing_rwf'];
    $backingPasses = is_int($available)
        && $available * $rules['coverage_denominator'] >= $obligation * $rules['coverage_numerator'];

    expect($backingPasses)->toBe($example['expected_admission_backing_passes'])
        ->and([$rules['coverage_numerator'], $rules['coverage_denominator']])->toBe([1, 1])
        ->and($rules['investor_wallet_funding_allowed'])->toBeFalse()
        ->and($rules['expected_future_fee_funding_allowed'])->toBeFalse()
        ->and($rules['insufficient_backing'])->toBe('BLOCK_ADDITIONAL_PROTECTED_COMMITMENTS')
        ->and($rules['actual_shortfall'])->toBe('INCIDENT_NOT_PAID_STATE');
})->with(function (): array {
    $examples = PhaseZeroEngineeringReview::records(PhaseZeroEngineeringReview::load(), 'financial_reserve_examples');

    return array_combine(array_column($examples, 'id'), array_map(fn (array $example): array => [$example], $examples));
});

test('financial reserve payout protects only unresolved principal with distinct approval and evidence controls', function (): void {
    $rules = PhaseZeroEngineeringReview::load()['financial_defaults']['reserve'];
    $protectedPrincipal = 3000000;
    $principalRecovered = 1200000;

    expect($protectedPrincipal - $principalRecovered)->toBe(1800000)
        ->and($rules['payout_basis'])->toBe('PROTECTED_PRINCIPAL_LESS_PRINCIPAL_ALREADY_RECOVERED')
        ->and($rules['future_return_or_penalty_income_guaranteed'])->toBeFalse()
        ->and([$rules['prepare_from_dpd'], $rules['prepare_through_dpd'], $rules['complete_by_dpd']])->toBe([31, 44, 45])
        ->and($rules['distinct_authorized_approvers'])->toBe(2)
        ->and($rules['reconciliation'])->toBe('DAILY')
        ->and($rules['payout_evidence_required'])->toBeTrue()
        ->and($rules['subsequent_recoveries'])->toBe('REQUIRES_DOCUMENTED_LEGAL_TREATMENT');
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

test('accepted bid reserve examples conserve cash through partial fills and terminal release :dataset', function (array $fixture): void {
    $pack = PhaseZeroEngineeringReview::load();
    $quantity = PhaseZeroEngineeringReview::integer($fixture, 'quantity');
    $limit = PhaseZeroEngineeringReview::integer($fixture, 'limit_rwf');
    $perUnitReserve = $limit + intdiv($limit * 35 + 9999, 10000);
    $reserved = $quantity * $perUnitReserve;
    $initial = $reserved;
    $debited = 0;
    $released = 0;

    expect($fixture['evidence_level'])->toBe('SYNTHETIC_ARITHMETIC_NOT_EXECUTED_TRADE')
        ->and($reserved)->toBe($fixture['initial_reserve_rwf'])
        ->and($fixture['fills'])->toBeArray();

    foreach ($fixture['fills'] as $fill) {
        $units = PhaseZeroEngineeringReview::integer($fill, 'units');
        $ask = PhaseZeroEngineeringReview::integer($fill, 'ask_rwf');
        $gross = $units * $ask;
        $fee = PhaseZeroEngineeringReview::halfUp($gross * 35, 10000);
        $debit = $gross + $fee;
        $remainingUnits = $quantity - $units;
        $remainingReserve = $remainingUnits * $perUnitReserve;
        $release = $reserved - $debit - $remainingReserve;

        expect($units)->toBeGreaterThan(0)->toBeLessThanOrEqual($quantity)
            ->and($ask)->toBeLessThanOrEqual($limit)
            ->and($gross)->toBeGreaterThanOrEqual(1000)
            ->and($fee)->toBe($fill['buyer_fee_rwf'])
            ->and($debit)->toBe($fill['buyer_debit_rwf'])
            ->and($release)->toBeGreaterThanOrEqual(0)->toBe($fill['released_rwf'])
            ->and($remainingReserve)->toBe($fill['remaining_reserve_rwf']);

        $quantity = $remainingUnits;
        $reserved = $remainingReserve;
        $debited += $debit;
        $released += $release;

        expect($debited + $released + $reserved)->toBe($initial);
    }

    expect($fixture['terminal_state'])->toBeIn($pack['engineering_amendments']['terminal_bid_states'])
        ->and($reserved)->toBe($fixture['terminal_release_rwf'])
        ->and($debited + $released + $fixture['terminal_release_rwf'])->toBe($initial);

    if ($fixture['terminal_state'] === 'FILLED') {
        expect($quantity)->toBe(0)->and($reserved)->toBe(0);
    }
})->with(function (): array {
    $fixtures = PhaseZeroEngineeringReview::records(PhaseZeroEngineeringReview::load(), 'bid_reserve_examples');

    return array_combine(array_column($fixtures, 'id'), array_map(fn (array $fixture): array => [$fixture], $fixtures));
});

test('terminal release accounting requires zero repeat release without claiming database race proof :dataset', function (string $state): void {
    $remainingUnits = $state === 'FILLED' ? 0 : 2;
    $reserved = $remainingUnits * 1004;
    $initialReserve = $reserved;
    $releases = [];

    foreach ([1, 2] as $attempt) {
        $releases[$attempt] = $reserved;
        $reserved -= $releases[$attempt];
    }

    expect($releases)->toBe([1 => $initialReserve, 2 => 0])
        ->and(array_sum($releases))->toBe($initialReserve)
        ->and($reserved)->toBe(0);
})->with(['FILLED', 'CANCELLED', 'EXPIRED', 'HALTED', 'INELIGIBLE']);

test('minimum fill examples skip separate undersized listings without forced extra units :dataset', function (array $fixture): void {
    $bidUnits = PhaseZeroEngineeringReview::integer($fixture, 'bid_units');
    $limit = PhaseZeroEngineeringReview::integer($fixture, 'limit_rwf');
    $skipped = [];
    $result = ['skipped' => [], 'selected_listing' => null, 'units' => 0, 'gross_rwf' => 0];

    expect($fixture['evidence_level'])->toBe('SYNTHETIC_SELECTION_NOT_EXECUTED_MATCH')
        ->and($fixture['assumption'])->toBeString()->not->toBeEmpty();

    foreach (PhaseZeroEngineeringReview::records($fixture, 'listings') as $listing) {
        $ask = PhaseZeroEngineeringReview::integer($listing, 'ask_rwf');
        $units = min($bidUnits, PhaseZeroEngineeringReview::integer($listing, 'available_units'));
        $gross = $units * $ask;

        if ($ask > $limit) {
            continue;
        }

        if ($units < 1 || $gross < 1000) {
            $skipped[] = $listing['id'];

            continue;
        }

        $result = ['skipped' => $skipped, 'selected_listing' => $listing['id'], 'units' => $units, 'gross_rwf' => $gross];
        break;
    }

    $result['skipped'] = $skipped;

    expect($result)->toBe($fixture['expected']);
})->with(function (): array {
    $fixtures = PhaseZeroEngineeringReview::records(PhaseZeroEngineeringReview::load(), 'minimum_fill_examples');

    return array_combine(array_column($fixtures, 'id'), array_map(fn (array $fixture): array => [$fixture], $fixtures));
});

test('amendment records and document preserve server authority lock ordering and original review scope', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    PhaseZeroEngineeringReview::validate($pack);
    $document = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-draft-2026-09-20.md');
    $rules = $pack['engineering_amendments'];

    expect($rules['seven_day_command_minimum_retention_seconds'])
        ->toBe($pack['known_parameters']['bid_seconds'] + $rules['retention_extra_seconds'])
        ->toBe(8 * 24 * 60 * 60)
        ->and($pack['known_parameters']['listing_seconds'])->toBe($pack['known_parameters']['bid_seconds'])
        ->and(array_column(PhaseZeroEngineeringReview::records($pack, 'bid_reserve_examples'), 'id'))->toBe(['BA-01', 'BA-02', 'BA-03', 'BA-04', 'BA-05', 'BA-06'])
        ->and(array_column(PhaseZeroEngineeringReview::records($pack, 'minimum_fill_examples'), 'id'))->toBe(['MF-01', 'MF-02', 'MF-03', 'MF-04', 'MF-05'])
        ->and($document)->toBeString()->toContain(
            'unique per authenticated Party and command name',
            'canonical JSON of permitted user fields only',
            'at least eight days',
            'scoped 404 when the actor cannot see the resource at all',
            '`ACTION_FORBIDDEN` (403) when they can see it but cannot perform the action',
            'no optimistic UI for reserve, confirm, bid, cancel or settlement',
            'offset from `server_time`, never the browser clock alone',
            'ask the user to confirm again; do not retry permission failures',
            'Ordinary single-Note commands take one Note gate',
            'all required Note gates in ascending ID order before any wallet lock',
            'Under the Note gates, discover candidates',
            'Never acquire additional Note locks while holding wallet locks',
            'same idempotency key',
            'No automatic combination of separate listings',
            'their own available, unencumbered units of the same Note into one listing',
            'No cross-Note/cross-seller bundle',
            'new standalone sub-minimum listing',
            'with no bid buffer',
            'release the whole remaining reserve exactly once',
            'reserved cash is zero even if unmatched units remain in order history',
            $pack['approvals']['erastus_engineering']['source_url'],
            'eleven new amendment examples and the revised document hash are not covered by that original check',
        );
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
        case 'erase Erastus review':
            $pack['approvals']['erastus_engineering'] = null;
            break;
        case 'carry Erastus to revised hash':
            $pack['approvals']['erastus_engineering']['reviewed_version'] = $pack['version'];
            break;
        case 'extend independent examples':
            $pack['approvals']['independent_expected_results']['current_amendment_examples_reviewed'] = true;
            break;
        case 'amendment full freeze':
            $pack['approvals']['aminu_amendments']['full_contract_freeze'] = true;
            break;
        case 'change reviewed arithmetic':
            $pack['numeric_cases'][0]['inputs']['eligible_draws_rwf']++;
            break;
        case 'wrong key scope':
            $pack['engineering_amendments']['idempotency_scope'] = ['KEY'];
            break;
        case 'short retention':
            $pack['engineering_amendments']['seven_day_command_minimum_retention_seconds'] = 604800;
            break;
        case 'optimistic finance':
            $pack['engineering_amendments']['optimistic_financial_ui'] = true;
            break;
        case 'late Note lock':
            $pack['engineering_amendments']['new_note_lock_while_holding_wallet'] = true;
            break;
        case 'automatic combination':
            $pack['engineering_amendments']['automatic_inter_listing_combination'] = true;
            break;
        case 'forbid seller bundle':
            $pack['engineering_amendments']['seller_same_note_available_unit_bundling'] = false;
            break;
        case 'amendment runtime claim':
            $pack['engineering_amendments']['implementation_verified'] = true;
            break;
        case 'default':
            $pack['missing_parameters']['penalty_rate'] = 0;
            break;
        case 'invented tenor premium':
            $pack['accepted_defaults']['pricing']['premiums_by_tenor'][4]['numerator'] = 25;
            break;
        case 'invented seasonality formula':
            $pack['accepted_defaults']['nocf']['method'] = 'TRIM_SEASONAL_PEAKS';
            break;
        case 'erase accepted defaults':
            unset($pack['accepted_defaults']);
            break;
        case 'invent credit risk approval':
            $pack['accepted_defaults']['required_reviews']['kimani_credit_risk_and_worked_cases'] = 'APPROVED';
            break;
        case 'invent official holiday evidence':
            $pack['accepted_defaults']['hold_calendar']['holiday_dataset_verified'] = true;
            break;
        case 'restart hold clock':
            $pack['accepted_defaults']['hold_calendar']['confirmation_restarts_clock'] = true;
            break;
        case 'assume projection growth':
            $pack['accepted_defaults']['projection']['assumed_growth_numerator'] = 1;
            break;
        case 'compound penalty':
            $pack['accepted_defaults']['penalty']['interest'] = 'COMPOUND';
            break;
        case 'defaults full freeze':
            $pack['accepted_defaults']['full_contract_freeze'] = true;
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
})->with(['activation', 'phase one', 'signature', 'erase recorded approval', 'full contract freeze', 'expand review scope', 'independent approval', 'default', 'invented tenor premium', 'invented seasonality formula', 'invented reserve arrangement', 'baseline', 'runtime race', 'duplicate example', 'erase Erastus review', 'carry Erastus to revised hash', 'extend independent examples', 'amendment full freeze', 'change reviewed arithmetic', 'wrong key scope', 'short retention', 'optimistic finance', 'late Note lock', 'automatic combination', 'forbid seller bundle', 'amendment runtime claim', 'erase accepted defaults', 'invent credit risk approval', 'invent official holiday evidence', 'restart hold clock', 'assume projection growth', 'compound penalty', 'defaults full freeze']);

test('financial acceptance rejects changed economics or fabricated evidence :dataset', function (string $path, mixed $value): void {
    $pack = PhaseZeroEngineeringReview::load();
    Arr::set($pack, 'financial_defaults.'.$path, $value);

    expect(fn () => PhaseZeroEngineeringReview::validate($pack))->toThrow(UnexpectedValueException::class);
})->with([
    'alter annual rate' => ['penalty.annual_rate_bps', 2400],
    'compound charges' => ['penalty.simple_non_compounding', false],
    'backdate charges' => ['penalty.backdated_accrual', true],
    'daily rounding' => ['penalty.rounding', 'HALF_UP_EACH_DAY'],
    'return first' => ['payment_allocation', ['DUE_CONTRACTUAL_INTEREST_OR_RETURN', 'OLDEST_OVERDUE_PRINCIPAL', 'PENALTIES']],
    'rewrite issued schedule' => ['daily_recovery_plan.rewrite_issued_investor_schedule', true],
    'ten percent reserve' => ['reserve.coverage_denominator', 10],
    'investor money as backing' => ['reserve.investor_wallet_funding_allowed', true],
    'future profit guaranteed' => ['reserve.future_return_or_penalty_income_guaranteed', true],
    'invent cash funding' => ['evidence_verified.reserve_funding', true],
    'invent financial approval' => ['required_reviews.robert_kimani_financial_terms_and_real_arrangement', 'APPROVED'],
    'invent legal clearance' => ['evidence_verified.legal_review', true],
    'activate payout' => ['live_lending_or_payout_authorized', true],
    'freeze entire contract' => ['full_contract_freeze', true],
]);

test('current owner go ahead supersedes repeated approval gates without signing for other reviewers', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    PhaseZeroEngineeringReview::validate($pack);
    $document = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-draft-2026-09-20.md');
    $plan = file_get_contents(__DIR__.'/../../docs/Rozine_Phased_Implementation_Plan.md');
    $candidate = $pack['completion_candidate'];

    expect($pack['current_review_status']['repeat_minor_policy_approval_required'])->toBeFalse()
        ->and($pack['current_review_status']['accepted_default_policy_approval'])->toBe('CONFIRMED_AS_REPORTED_BY_AMINU')
        ->and($candidate['issue_90_modified'])->toBeFalse()
        ->and($candidate['implementation_verified'])->toBeFalse()
        ->and($candidate['independent_current_examples_reviewed'])->toBeFalse()
        ->and($candidate['phase_1_development_authorized'])->toBeFalse()
        ->and($pack['completion_examples']['scope'])->toBe('SYNTHETIC_SPECIFICATION_ONLY_NOT_RUNTIME_RACE_OR_PROVIDER_PROOF')
        ->and($document)->toBeString()->toContain('### 9.6 Owner go-ahead reported by Aminu', '## 10. Consolidated completion candidate', 'Do not send the same defaults back for approval.')
        ->and($plan)->toBeString()->toContain('Engineering completion candidate 2026-09-23', $pack['version']);

    $mechanics = PhaseZeroEngineeringReview::records($candidate, 'mechanics');
    expect(array_column($mechanics, 'id'))->toBe(array_map(fn (int $id): string => sprintf('MC-%02d', $id), range(1, 8)));

    foreach ($mechanics as $mechanic) {
        expect($document)->toContain($mechanic['id']);
    }
});

test('historical completion gaps retain provenance before explicit MVP defaults were delegated', function (): void {
    $candidate = PhaseZeroEngineeringReview::load()['completion_candidate'];
    $gaps = PhaseZeroEngineeringReview::records($candidate, 'unresolved_configuration');
    $document = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-draft-2026-09-20.md');
    $inputs = array_map(fn (int $id): string => sprintf('IN-%02d', $id), range(1, 12));
    $actions = array_map(fn (int $id): string => sprintf('AC-%02d', $id), range(1, 13));

    expect(array_column($gaps, 'id'))->toBe(['CFG-01', 'CFG-02', 'CFG-03', 'CFG-04', 'CFG-05'])
        ->and($candidate['missing_configuration_result'])->toBe('POLICY_INPUT_REQUIRED_NO_ZERO_UNLIMITED_OR_SUCCESS_DEFAULT');

    foreach ($gaps as $gap) {
        expect($gap['value'])->toBeNull()
            ->and($gap['fields'])->toBeArray()->not->toBeEmpty()
            ->and($gap['blocked_actions'])->toBeArray()->not->toBeEmpty()
            ->and(array_diff($gap['inputs'], $inputs))->toBe([])
            ->and(array_diff($gap['blocked_actions'], $actions))->toBe([])
            ->and($document)->toContain('| '.$gap['id'].' |');
    }
});

test('completion monthly means and deductions preserve exact seasonal cash flows :dataset', function (array $example): void {
    expect(PhaseZeroEngineeringReview::monthlyCfads($example))->toBe($example['expected_cfads']);
})->with(function (): array {
    return array_map(fn (array $record): array => [$record], PhaseZeroEngineeringReview::records(PhaseZeroEngineeringReview::load()['completion_examples'], 'cash_flow'));
});

test('complete monthly example feeds the unchanged downstream sizing contract', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    $upstream = PhaseZeroEngineeringReview::monthlyCfads($pack['completion_examples']['cash_flow'][0]);
    $downstream = array_column(PhaseZeroEngineeringReview::records($pack, 'numeric_cases'), null, 'id')['EC-010'];

    expect($upstream)->toBe(['numerator' => 3000000, 'denominator' => 1])
        ->and($downstream['inputs']['cfads_rwf'])->toBe($upstream['numerator'])
        ->and(PhaseZeroEngineeringReview::calculate($downstream['kind'], $downstream['inputs']))->toBe($downstream['expected']);
});

test('cash flow observation gaps are not zero filled and negative CFADS is not clamped', function (): void {
    $example = PhaseZeroEngineeringReview::load()['completion_examples']['cash_flow'][2];
    $example['nocf_runs'] = [['months' => 12, 'nocf_rwf' => -1000000]];

    expect(PhaseZeroEngineeringReview::monthlyCfads($example))->toBe(['numerator' => -1500000, 'denominator' => 1]);
    $example['nocf_runs'][0]['months'] = 11;
    expect(fn () => PhaseZeroEngineeringReview::monthlyCfads($example))->toThrow(UnexpectedValueException::class);
    $example['nocf_runs'] = [['months' => 12, 'nocf_rwf' => null]];
    expect(fn () => PhaseZeroEngineeringReview::monthlyCfads($example))->toThrow(UnexpectedValueException::class);
});

test('fixed unit component examples conserve integer rights :dataset', function (array $example): void {
    $allocation = PhaseZeroEngineeringReview::componentAllocation($example['component_rwf'], $example['units']);
    expect($allocation)->toBe($example['expected_rwf'])
        ->and(array_sum($allocation))->toBe($example['component_rwf'])
        ->and(max($allocation) - min($allocation))->toBeLessThanOrEqual(1);
})->with(function (): array {
    return array_map(fn (array $record): array => [$record], PhaseZeroEngineeringReview::records(PhaseZeroEngineeringReview::load()['completion_examples'], 'unit_components'));
});

test('unit partitioning transfers original ordinal rights without re-rounding holders', function (): void {
    $components = [10001, 7502, 2501];
    $allocations = array_map(fn (int $amount): array => PhaseZeroEngineeringReview::componentAllocation($amount, 3), $components);
    $buyerUnits = [0, 2];
    $sellerUnits = [1];

    foreach ($allocations as $index => $allocation) {
        $buyer = array_sum(array_map(fn (int $ordinal): int => $allocation[$ordinal], $buyerUnits));
        $seller = array_sum(array_map(fn (int $ordinal): int => $allocation[$ordinal], $sellerUnits));
        expect($buyer + $seller)->toBe($components[$index]);
    }

    expect(PhaseZeroEngineeringReview::load()['completion_candidate']['unit_allocation']['universal_5000_denomination_assumed'])->toBeFalse()
        ->and(fn () => PhaseZeroEngineeringReview::componentAllocation(10, 0))->toThrow(UnexpectedValueException::class)
        ->and(fn () => PhaseZeroEngineeringReview::componentAllocation(-1, 2))->toThrow(UnexpectedValueException::class);
});

test('DPD and default deadline use disclosed Kigali calendar dates :dataset', function (array $example): void {
    $zone = new DateTimeZone('Africa/Kigali');
    $due = new DateTimeImmutable($example['due_date'], $zone);
    $now = (new DateTimeImmutable($example['at']))->setTimezone($zone)->setTime(0, 0);
    $dpd = max(0, (int) $due->diff($now)->format('%r%a'));

    expect($dpd)->toBe($example['expected_dpd'])
        ->and($dpd > 30)->toBe($example['expected_default_deadline_elapsed']);
})->with(function (): array {
    return array_map(fn (array $record): array => [$record], PhaseZeroEngineeringReview::records(PhaseZeroEngineeringReview::load()['completion_examples'], 'servicing_dates'));
});

test('closing balance penalty and recovery targets have explicit intraday and whole franc boundaries', function (): void {
    $openingPrincipal = 3000000;
    $sameDayReceipt = 1000000;
    $closingPrincipal = $openingPrincipal - $sameDayReceipt;
    $chargeableDayPenalty = PhaseZeroEngineeringReview::halfUp($closingPrincipal * 200, 10000 * 365);

    expect($chargeableDayPenalty)->toBe(110)
        ->and(PhaseZeroEngineeringReview::halfUp(0 * 200, 10000 * 365))->toBe(0)
        ->and(PhaseZeroEngineeringReview::halfUp(1, 22 - 8))->toBe(0)
        ->and(PhaseZeroEngineeringReview::halfUp(1, 22 - 21))->toBe(1)
        ->and(PhaseZeroEngineeringReview::halfUp(1001, 22 - 8))->toBe(72);
});

test('primary expiry admits only before deadline and returns investor principal without fees :dataset', function (array $commitments, int $target, int $secondsAfterExpiry, bool $expectedAdmission, int $expectedRefund): void {
    $rule = PhaseZeroEngineeringReview::load()['completion_candidate']['primary'];
    $live = new DateTimeImmutable('2026-09-23T10:00:00Z');
    $expiry = $live->modify('+'.$rule['unfunded_expiry_seconds'].' seconds');
    $now = $expiry->modify($secondsAfterExpiry.' seconds');
    $committed = array_sum($commitments);
    $canAdmit = $now < $expiry && $committed < $target;
    $refund = $now >= $expiry && $committed < $target ? $committed : 0;

    expect($expiry->format(DATE_ATOM))->toBe('2026-10-23T10:00:00+00:00')
        ->and($canAdmit)->toBe($expectedAdmission)
        ->and($refund)->toBe($expectedRefund)
        ->and($rule['late_confirmation_extends_expiry'])->toBeFalse()
        ->and($rule['expiry_refund'])->toBe('ALL_COMMITTED_INVESTOR_PRINCIPAL_NO_FEE');
})->with([
    'before expiry' => [[2000000, 1000000, 500000], 5000000, -1, true, 0],
    'exact expiry' => [[2000000, 1000000, 500000], 5000000, 0, false, 3500000],
    'after expiry' => [[2000000, 1000000, 500000], 5000000, 1, false, 3500000],
    'already fully funded' => [[2000000, 3000000], 5000000, 0, false, 0],
]);

test('commitment conversion preserves exposure once through partial funding and disbursement', function (): void {
    $commitment = 10000000;
    $otherOutstanding = 2000000;
    $funded = 4000000;
    $before = $otherOutstanding + $commitment;
    $after = ($otherOutstanding + $commitment) + 0;

    expect($before)->toBe(12000000)
        ->and($after)->toBe($before)
        ->and($before)->not->toBe($otherOutstanding + $commitment + $funded)
        ->and(PhaseZeroEngineeringReview::load()['completion_candidate']['primary']['failed_predisbursement_recheck'])->toBe('BLOCK_DISBURSEMENT_PRESERVE_FUNDS_AND_ISSUED_TERMS');
});

test('hold confirmation has no inclusive expiry loophole and releasing one case keeps other restrictions', function (): void {
    $flag = new DateTimeImmutable('2026-09-23T12:00:00Z');
    $deadline = $flag->modify('+24 hours');
    $cases = ['automated-review' => ['SECONDARY', 'WITHDRAW'], 'disputed-note' => ['SECONDARY']];
    unset($cases['automated-review']);

    expect($deadline->modify('-1 second') < $deadline)->toBeTrue()
        ->and($deadline < $deadline)->toBeFalse()
        ->and(array_values(array_unique(array_merge(...array_values($cases)))))->toBe(['SECONDARY'])
        ->and(PhaseZeroEngineeringReview::load()['completion_candidate']['holds']['confirmation_restarts_clock'])->toBeFalse();
});

test('provider reference outcomes never infer success or repeat a settled effect :dataset', function (string $state, string $incoming, bool $verified, string $expectedState, string $effect): void {
    expect(PhaseZeroEngineeringReview::providerOutcome($state, $incoming, $verified))->toBe(['state' => $expectedState, 'effect' => $effect]);
})->with([
    'timeout' => ['PENDING', 'UNKNOWN', true, 'UNKNOWN', 'PRESERVE_RESERVATION'],
    'unverified success' => ['PENDING', 'SUCCEEDED', false, 'PENDING', 'NO_FINANCIAL_EFFECT'],
    'confirmed success' => ['UNKNOWN', 'SUCCEEDED', true, 'SUCCEEDED', 'POST_ONCE'],
    'duplicate success' => ['SUCCEEDED', 'SUCCEEDED', true, 'SUCCEEDED', 'NO_FINANCIAL_EFFECT'],
    'late pending' => ['SUCCEEDED', 'PENDING', true, 'SUCCEEDED', 'NO_FINANCIAL_EFFECT'],
    'late failure' => ['SUCCEEDED', 'FAILED_FINAL', true, 'SUCCEEDED', 'RECONCILIATION_CASE'],
    'final failure' => ['UNKNOWN', 'FAILED_FINAL', true, 'FAILED_FINAL', 'RELEASE_ONCE'],
    'duplicate failure' => ['FAILED_FINAL', 'FAILED_FINAL', true, 'FAILED_FINAL', 'NO_FINANCIAL_EFFECT'],
    'conflicting final' => ['FAILED_FINAL', 'SUCCEEDED', true, 'FAILED_FINAL', 'RECONCILIATION_CASE'],
]);

test('Auditor ingestion identity distinguishes replay from replacement without implying audit approval', function (): void {
    $policy = PhaseZeroEngineeringReview::load()['completion_candidate']['auditor_ingestion'];
    $existing = ['evidence-1' => hash('sha256', 'synthetic camera content')];
    $same = hash('sha256', 'synthetic camera content');
    $changed = hash('sha256', 'different content');
    $outcome = fn (string $digest): string => hash_equals($existing['evidence-1'], $digest)
        ? $policy['duplicate_same_digest'] : $policy['duplicate_changed_digest'];

    expect($outcome($same))->toBe('RETURN_ORIGINAL_RECEIPT')
        ->and($outcome($changed))->toBe('EVIDENCE_CONFLICT')
        ->and($policy['out_of_order'])->toBe('STAGED_NOT_REPORT_READY')
        ->and($policy['revoked_or_expired_at_commit'])->toBe('EVIDENCE_REJECTED')
        ->and($policy['receipt_means'])->toBe('INGESTED_NOT_AUDIT_APPROVED');
});

test('historical authority and completion candidate reject retroactive closure or changed safeguards :dataset', function (string $path, mixed $value): void {
    $pack = PhaseZeroEngineeringReview::load();
    Arr::set($pack, $path, $value);

    expect(fn () => PhaseZeroEngineeringReview::validate($pack))->toThrow(UnexpectedValueException::class);
})->with([
    'reopen minor approval' => ['current_review_status.repeat_minor_policy_approval_required', true],
    'forge direct signature' => ['current_review_status.direct_robert_kimani_signature_claimed', true],
    'claim capital exists' => ['current_review_status.reserve_funding_verified', true],
    'claim external clearance' => ['current_review_status.external_clearance_verified', true],
    'claim revised review' => ['current_review_status.erastus_current_candidate_reviewed', true],
    'freeze candidate' => ['current_review_status.full_contract_freeze', true],
    'silently set zero fee' => ['completion_candidate.unresolved_configuration.0.value', 0],
    'reuse secondary timer' => ['completion_candidate.primary.unfunded_expiry_seconds', 604800],
    'extend primary expiry' => ['completion_candidate.primary.late_confirmation_extends_expiry', true],
    'invent changed offer refund' => ['completion_candidate.primary.invent_changed_term_reconsent_or_refund', true],
    'reinterpret anchor' => ['completion_candidate.unit_allocation.universal_5000_denomination_assumed', true],
    'reallocate ownership' => ['completion_candidate.unit_allocation.reallocate_after_ownership_transfer', true],
    'restart review clock' => ['completion_candidate.holds.confirmation_restarts_clock', true],
    'retry uncertain payment' => ['completion_candidate.provider_outcomes.provider_retry_without_idempotency_guarantee', true],
    'claim implementation' => ['completion_candidate.implementation_verified', true],
]);

test('MVP delegation closes every configuration group without inventing independent review or implementation', function (): void {
    $pack = PhaseZeroEngineeringReview::load();
    PhaseZeroEngineeringReview::validate($pack);
    $mvp = $pack['mvp_baseline'];
    $document = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-draft-2026-09-20.md');
    $plan = file_get_contents(__DIR__.'/../../docs/Rozine_Phased_Implementation_Plan.md');

    expect($pack['effective_authority_record'])->toBe('mvp_baseline')
        ->and($mvp['decision_owner'])->toBe('AMINU')
        ->and($mvp['default_origin'])->toBe('ASSISTANT_SELECTED_UNDER_EXPLICIT_USER_DELEGATION')
        ->and($mvp['configuration_groups_resolved'])->toBe(['CFG-01', 'CFG-02', 'CFG-03', 'CFG-04', 'CFG-05'])
        ->and(array_keys($mvp['configuration']))->toBe($mvp['configuration_groups_resolved'])
        ->and($mvp['remaining_configuration_groups'])->toBe([])
        ->and($mvp['specification_baseline_established'])->toBeTrue()
        ->and($mvp['erastus_specification_review'])->toBe('WAIVED_BY_USER_NOT_PERFORMED')
        ->and($document)->toBeString()->toContain(
            '## 11. Selected MVP defaults and specification-review waiver',
            'No further Erastus review is required for this MVP specification baseline.',
            hash_file('sha256', __DIR__.'/../../docs/phase-0/engineering-contract-fixtures-2026-09-20.json'),
        )
        ->and($plan)->toBeString()->toContain('MVP design baseline 2026-09-23', $pack['version'], 'the original contract-freeze scheduling below does not reopen that review');

    foreach (['independent_current_review_performed', 'direct_robert_kimani_approval_claimed', 'actual_external_evidence_verified', 'implementation_verified', 'phase_0_exit_verified', 'phase_1_development_authorized', 'production_activation'] as $flag) {
        expect($mvp[$flag])->toBeFalse();
    }

    foreach (PhaseZeroEngineeringReview::records($pack['completion_candidate'], 'unresolved_configuration') as $historicalGap) {
        foreach ($historicalGap['fields'] as $field) {
            expect($mvp['configuration'][$historicalGap['id']])->toHaveKey($field)
                ->and($mvp['configuration'][$historicalGap['id']][$field])->not->toBeNull();
        }
    }
});

test('MVP policy changes cannot forge review evidence or silently weaken selected defaults :dataset', function (string $path, mixed $value): void {
    $pack = PhaseZeroEngineeringReview::load();
    Arr::set($pack, $path, $value);

    expect(fn () => PhaseZeroEngineeringReview::validate($pack))->toThrow(UnexpectedValueException::class);
})->with([
    'restore obsolete authority' => ['effective_authority_record', 'current_review_status'],
    'undo default selection' => ['mvp_baseline.specification_baseline_established', false],
    'attribute approval to Erastus' => ['mvp_baseline.erastus_specification_review', 'APPROVED'],
    'invent independent review' => ['mvp_baseline.independent_current_review_performed', true],
    'invent direct owner signatures' => ['mvp_baseline.direct_robert_kimani_approval_claimed', true],
    'invent external evidence' => ['mvp_baseline.actual_external_evidence_verified', true],
    'invent implementation' => ['mvp_baseline.implementation_verified', true],
    'claim Phase 0 exit' => ['mvp_baseline.phase_0_exit_verified', true],
    'start Phase 1' => ['mvp_baseline.phase_1_development_authorized', true],
    'activate production' => ['mvp_baseline.production_activation', true],
    'invent a primary fee' => ['mvp_baseline.configuration.CFG-01.primary_listing_fee_rwf', 1000],
    'unlimited investment' => ['mvp_baseline.configuration.CFG-01.investor_limit_matrix.aggregate_principal_limit_rwf', null],
    'override financial gate' => ['mvp_baseline.configuration.CFG-01.disclosure_and_override_policy.financial_hard_gate_override', true],
    'round principal up' => ['mvp_baseline.configuration.CFG-02.issue_target_remainder_policy.round_up', true],
    'transfer rerounds rights' => ['mvp_baseline.configuration.CFG-02.issue_target_remainder_policy.reallocate_on_transfer', true],
    'secondary timer on primary' => ['mvp_baseline.configuration.CFG-03.primary_checkout_and_voluntary_cancellation_policy.expiry', 'CHECKOUT_WINS'],
    'refund unknown payment' => ['mvp_baseline.configuration.CFG-03.changed_funded_offer_cash_disposition.provider_already_dispatched_or_unknown', 'REFUND'],
    'automatic formal hold renewal' => ['mvp_baseline.configuration.CFG-04.formal_finding_authority_policy.renewal', 'AUTOMATIC'],
    'claim native proof' => ['mvp_baseline.configuration.CFG-05.offline_ttl_and_device_support_policy.physical_capability_verified', true],
    'unlimited offline capture' => ['mvp_baseline.configuration.CFG-05.offline_ttl_and_device_support_policy.capture_authorization_seconds', 604800],
]);

test('MVP quantization floors capacity before acceptance without lifting subminimum loans :dataset', function (int $capacity, int $expected): void {
    expect(PhaseZeroEngineeringReview::mvpQuantizedPrincipal($capacity))->toBe($expected);
})->with([[10704728, 10700000], [3000000, 3000000], [2999999, 0], [3004999, 3000000], [100000000, 100000000], [0, 0]]);

test('MVP quantized EC-010 recomputes the whole schedule while preserving the original request rejection', function (): void {
    $example = array_column(PhaseZeroEngineeringReview::records(PhaseZeroEngineeringReview::load(), 'numeric_cases'), null, 'id')['EC-010'];
    $sized = PhaseZeroEngineeringReview::size($example['inputs']);
    $principal = PhaseZeroEngineeringReview::mvpQuantizedPrincipal($sized['principal_rwf']);
    $return = PhaseZeroEngineeringReview::halfUp($principal * 121, 1000);
    $total = $principal + $return;
    $regular = PhaseZeroEngineeringReview::halfUp($total, 6);
    $schedule = [...array_fill(0, 5, $regular), $total - 5 * $regular];

    expect($principal)->toBe(10700000)
        ->and(intdiv($principal, 5000))->toBe(2140)
        ->and($return)->toBe(1294700)
        ->and($schedule)->toBe([1999117, 1999117, 1999117, 1999117, 1999117, 1999115])
        ->and(array_sum($schedule))->toBe(11994700)
        ->and($example['inputs']['cfads_rwf'] * 2)->toBeGreaterThanOrEqual(max($schedule) * 3);

    $unaffordable = [...$example['inputs'], 'cfads_rwf' => 1000000];
    expect(PhaseZeroEngineeringReview::size($unaffordable))->toBe(['status' => 'REJECT_ORIGINAL', 'principal_rwf' => null])
        ->and(fn () => PhaseZeroEngineeringReview::mvpQuantizedPrincipal(-1))->toThrow(UnexpectedValueException::class);
});

test('carried principal remainder gives every equal face unit exactly its principal without changing transferred rights', function (): void {
    $cursor = 0;
    $totals = [0, 0, 0];
    $expected = [[1667, 1667, 1666], [1667, 1666, 1667], [1666, 1667, 1667]];

    foreach ([5000, 5000, 5000] as $index => $principal) {
        $result = PhaseZeroEngineeringReview::cyclicComponentAllocation($principal, 3, $cursor);
        $cursor = $result['next_cursor'];
        expect($result['allocation'])->toBe($expected[$index])
            ->and(array_sum($result['allocation']))->toBe($principal);

        foreach ($result['allocation'] as $ordinal => $amount) {
            $totals[$ordinal] += $amount;
        }
    }

    expect($totals)->toBe([5000, 5000, 5000])->and($cursor)->toBe(0)
        ->and(PhaseZeroEngineeringReview::cyclicComponentAllocation(0, 3, 2))->toBe(['allocation' => [0, 0, 0], 'next_cursor' => 2])
        ->and(fn () => PhaseZeroEngineeringReview::cyclicComponentAllocation(1, 3, 3))->toThrow(UnexpectedValueException::class);
});

test('partial receipts conserve cash and cannot exceed frozen unpaid entitlements :dataset', function (int $receipt, array $unpaid, array $expected, int $unapplied): void {
    $result = PhaseZeroEngineeringReview::distributeReceipt($receipt, PhaseZeroEngineeringReview::integers(['unpaid' => $unpaid], 'unpaid'));
    expect($result)->toBe(['allocation' => $expected, 'unapplied' => $unapplied])
        ->and(array_sum($result['allocation']) + $result['unapplied'])->toBe($receipt);

    foreach ($result['allocation'] as $ordinal => $amount) {
        expect($amount)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual($unpaid[$ordinal]);
    }
})->with([
    [5, [3, 3, 4], [2, 1, 2], 0],
    [5, [1, 2, 2], [1, 2, 2], 0],
    [1, [1, 1, 1], [1, 0, 0], 0],
    [3, [0, 2, 2], [0, 2, 1], 0],
    [5, [1, 1, 1], [1, 1, 1], 2],
    [5, [0, 0, 0], [0, 0, 0], 5],
    [0, [3, 3, 4], [0, 0, 0], 0],
]);

test('quantized real issue allocates all principal and return without creating or losing unit rights', function (): void {
    $units = 2140;
    $principal = 10700000;
    $total = 11994700;
    $regularPrincipal = PhaseZeroEngineeringReview::halfUp($principal, 6);
    $regularTotal = PhaseZeroEngineeringReview::halfUp($total, 6);
    $unitPrincipal = array_fill(0, $units, 0);
    $allocatedReturn = 0;
    $principalCursor = 0;
    $returnCursor = 0;

    foreach (range(1, 6) as $month) {
        $principalComponent = $month === 6 ? $principal - 5 * $regularPrincipal : $regularPrincipal;
        $payment = $month === 6 ? $total - 5 * $regularTotal : $regularTotal;
        $principalAllocation = PhaseZeroEngineeringReview::cyclicComponentAllocation($principalComponent, $units, $principalCursor);
        $returnAllocation = PhaseZeroEngineeringReview::cyclicComponentAllocation($payment - $principalComponent, $units, $returnCursor);
        $principalCursor = $principalAllocation['next_cursor'];
        $returnCursor = $returnAllocation['next_cursor'];
        $allocatedReturn += array_sum($returnAllocation['allocation']);

        expect(array_sum($principalAllocation['allocation']) + array_sum($returnAllocation['allocation']))->toBe($payment);

        foreach ($principalAllocation['allocation'] as $ordinal => $amount) {
            $unitPrincipal[$ordinal] += $amount;
        }
    }

    expect(array_unique($unitPrincipal))->toBe([5000])
        ->and(array_sum($unitPrincipal))->toBe($principal)
        ->and($allocatedReturn)->toBe(1294700);
});

test('repeat projection keeps negative months and applies exact median cap before the monthly 1.35 test :dataset', function (int $nocf, int $medianMiddleSum, int $draw, int $debt, int $payment, int $expectedNumerator, bool $eligible): void {
    $rule = PhaseZeroEngineeringReview::load()['mvp_baseline']['repeat_projection_mapping'];
    $cfadsNumerator = min($nocf * 5, $medianMiddleSum * 3) - ($draw + $debt) * 5;

    expect($cfadsNumerator)->toBe($expectedNumerator)
        ->and($payment > 0 && $cfadsNumerator * 20 >= $payment * 5 * 27)->toBe($eligible)
        ->and($rule['nocf_cap'])->toBe('MIN_CORRESPONDING_AUDITED_MONTH_NOCF_AND_6_OVER_5_TIMES_12_MONTH_MEDIAN_REVENUE')
        ->and($rule['monthly_test'])->toBe('EXACT_CFADS_OVER_ACTUAL_NEW_SCHEDULE_PAYMENT_AT_LEAST_27_OVER_20');
})->with([
    [1500000, 2000000, 100000, 200000, 666666, 4500000, true],
    [1500000, 2000000, 100000, 200000, 666667, 4500000, false],
    [1000000, 2000000, 100000, 200000, 518518, 3500000, true],
    [1000000, 2000000, 100000, 200000, 518519, 3500000, false],
    [1500000, 2000001, 100000, 200000, 666667, 4500003, true],
    [-100000, 2000000, 10000, 20000, 500000, -650000, false],
]);

test('due date clamp uses the original disbursement day without February drift :dataset', function (string $anchor, int $months, string $expected): void {
    expect(PhaseZeroEngineeringReview::dueDate($anchor, $months))->toBe($expected);
})->with([
    ['2026-01-31', 1, '2026-02-28'], ['2026-01-31', 2, '2026-03-31'],
    ['2028-01-31', 1, '2028-02-29'], ['2026-08-31', 6, '2027-02-28'],
    ['2026-12-15', 1, '2027-01-15'],
]);

test('MVP investor limits include reserved principal and deny unverified connected or over-cap investment :dataset', function (array $position, bool $expected): void {
    expect(PhaseZeroEngineeringReview::withinInvestorLimits(...$position))->toBe($expected);
})->with([
    'all at applicable cap' => [[true, 600000, 600000, 3000000, 2000000, 5000000], true],
    'one franc over twenty percent' => [[true, 600001, 600001, 3000000, 2000000, 5000000], false],
    'large note monetary cap' => [[true, 1000000, 1000000, 100000000, 2000000, 5000000], true],
    'transaction limit' => [[true, 1000001, 600000, 3000000, 2000000, 5000000], false],
    'outstanding plus reservation note limit' => [[true, 5000, 1000001, 100000000, 2000000, 5000000], false],
    'business limit' => [[true, 5000, 600000, 3000000, 2000001, 5000000], false],
    'aggregate limit' => [[true, 5000, 600000, 3000000, 2000000, 5000001], false],
    'unverified' => [[false, 5000, 5000, 3000000, 5000, 5000], false],
    'connected business' => [[true, 5000, 5000, 3000000, 5000, 5000, true], false],
]);

test('primary checkout never outlives campaign expiry and fails at its exact deadline :dataset', function (int $createdAt, int $campaignEnd, int $at, bool $expected): void {
    $rule = PhaseZeroEngineeringReview::load()['mvp_baseline']['configuration']['CFG-03']['primary_checkout_and_voluntary_cancellation_policy'];
    $expiresAt = min($createdAt + $rule['reservation_seconds'], $campaignEnd);

    expect($at >= $createdAt && $at < $expiresAt)->toBe($expected)
        ->and($rule['expiry'])->toBe('MIN_CHECKOUT_DEADLINE_AND_30_DAY_CAMPAIGN_DEADLINE');
})->with([[0, 1000, 299, true], [0, 1000, 300, false], [950, 1000, 999, true], [950, 1000, 1000, false]]);

test('failed closing distinguishes refundable money from dispatched and issued obligations :dataset', function (string $state, string $expected): void {
    expect(PhaseZeroEngineeringReview::failedClosingDisposition($state))->toBe($expected);
})->with([
    ['NOT_DISPATCHED', 'REFUND_ONCE_NO_FEE_RELEASE_EXPOSURE'],
    ['VERIFIED_FAILED', 'REFUND_ONCE_NO_FEE_RELEASE_EXPOSURE'],
    ['DISPATCHED', 'PRESERVE_RECONCILE_NO_SECOND_PAYMENT'],
    ['UNKNOWN', 'PRESERVE_RECONCILE_NO_SECOND_PAYMENT'],
    ['VERIFIED_SUCCEEDED', 'KEEP_PURCHASED_TERMS'], ['ISSUED', 'KEEP_PURCHASED_TERMS'],
]);

test('MVP capture and upload cutoffs are distinct without claiming physical evidence :dataset', function (int $captureAge, int $uploadAge, bool $authorityValid, bool $expected): void {
    $rule = PhaseZeroEngineeringReview::load()['mvp_baseline']['configuration']['CFG-05']['offline_ttl_and_device_support_policy'];
    $allowed = $authorityValid && $captureAge >= 0 && $captureAge < $rule['capture_authorization_seconds']
        && $uploadAge >= $captureAge && $uploadAge < $rule['upload_and_unacknowledged_retention_seconds'];

    expect($allowed)->toBe($expected)
        ->and($rule['capture_deadline_and_upload_deadline_distinct'])->toBeTrue()
        ->and($rule['physical_capability_verified'])->toBeFalse();
})->with([[86399, 604799, true, true], [86400, 86400, true, false], [86399, 604800, true, false], [10, 20, false, false], [20, 10, true, false]]);

test('unknown review arithmetic is rejected', function (): void {
    expect(fn () => PhaseZeroEngineeringReview::calculate('production_engine', []))->toThrow(UnexpectedValueException::class);
});
