<?php

declare(strict_types=1);

/**
 * Preparation integrity only: no device result, owner signature, provider
 * connection or native implementation is produced by these checks.
 */
final class PhaseZeroDeliveryReadiness
{
    /** @return array<string, mixed> */
    public static function load(): array
    {
        $contents = file_get_contents(__DIR__.'/../../docs/phase-0/delivery-readiness.json');

        if ($contents === false) {
            throw new UnexpectedValueException('Missing delivery readiness record.');
        }

        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new UnexpectedValueException('Readiness must be an object.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public static function object(array $record, string $key): array
    {
        $value = $record[$key] ?? null;

        if (! is_array($value) || array_is_list($value)) {
            throw new UnexpectedValueException('Expected an object: '.$key);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return list<array<string, mixed>>
     */
    public static function records(array $record, string $key): array
    {
        $value = $record[$key] ?? null;

        if (! is_array($value) || ! array_is_list($value) || $value === []) {
            throw new UnexpectedValueException('Expected records: '.$key);
        }

        foreach ($value as $row) {
            if (! is_array($row) || array_is_list($row)) {
                throw new UnexpectedValueException('Invalid record: '.$key);
            }
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return list<string>
     */
    public static function strings(array $record, string $key): array
    {
        $value = $record[$key] ?? null;

        if (! is_array($value) || ! array_is_list($value)) {
            throw new UnexpectedValueException('Expected list: '.$key);
        }

        foreach ($value as $item) {
            if (! is_string($item) || trim($item) === '') {
                throw new UnexpectedValueException('Invalid list item: '.$key);
            }
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{int, int}
     */
    public static function range(array $record, string $key): array
    {
        $value = $record[$key] ?? null;

        if (! is_array($value) || array_keys($value) !== [0, 1]
            || ! is_int($value[0]) || ! is_int($value[1])
            || $value[0] <= 0 || $value[1] < $value[0]) {
            throw new UnexpectedValueException('Invalid estimate range: '.$key);
        }

        return [$value[0], $value[1]];
    }

    /** @param array<string, mixed> $pack */
    public static function validate(array $pack): void
    {
        if (($pack['schema_version'] ?? null) !== 1
            || ($pack['status'] ?? null) !== 'PREPARATION_ONLY_NOT_APPROVED'
            || ($pack['production_activation'] ?? null) !== false
            || ($pack['approvals'] ?? null) !== array_fill_keys([
                'engineering', 'security', 'product', 'compliance', 'independent_test', 'external_authority',
            ], null)) {
            throw new UnexpectedValueException('Preparation cannot assert activation or approval.');
        }

        $auditor = self::object($pack, 'auditor');

        if (($auditor['platform_decision'] ?? null) !== 'D-04_OPTION_B_APPROVED'
            || ($auditor['estimate_status'] ?? null) !== 'PROPOSED_LOW_CONFIDENCE') {
            throw new UnexpectedValueException('Native planning authority changed.');
        }

        self::requireNulls($auditor, ['calendar_commitment', 'framework']);

        $aminu = [0, 0];
        $erastus = [0, 0];
        $seen = [];

        foreach (self::records($auditor, 'work_packages') as $row) {
            $id = $row['id'] ?? null;

            if (! is_string($id) || $id !== sprintf('CAP-%02d', count($seen) + 1)) {
                throw new UnexpectedValueException('Capture work IDs must remain ordered and unique.');
            }

            foreach (self::strings($row, 'depends_on') as $dependency) {
                if (! in_array($dependency, $seen, true)) {
                    throw new UnexpectedValueException('Capture dependency is missing, cyclic or out of order.');
                }
            }

            $seen[] = $id;
            $aminuDays = self::range($row, 'aminu_days');
            $erastusDays = self::range($row, 'erastus_days');

            foreach ([0, 1] as $bound) {
                $aminu[$bound] += $aminuDays[$bound];
                $erastus[$bound] += $erastusDays[$bound];
            }
        }

        $totals = self::object($auditor, 'incremental_developer_days');

        if (count($seen) !== 6 || self::range($totals, 'Aminu') !== $aminu
            || self::range($totals, 'Erastus') !== $erastus
            || self::range($totals, 'total') !== [$aminu[0] + $erastus[0], $aminu[1] + $erastus[1]]) {
            throw new UnexpectedValueException('Incremental native effort does not reconcile.');
        }

        $weeks = self::range($auditor, 'incremental_focused_weeks');

        if ($weeks !== [intdiv(max($aminu[0], $erastus[0]) + 4, 5), intdiv(max($aminu[1], $erastus[1]) + 4, 5)]) {
            throw new UnexpectedValueException('Calendar capacity cannot invent extra developers.');
        }

        $windows = self::object($auditor, 'candidate_rc_windows');

        foreach (['stretch' => 8, 'planning' => 9, 'remediation' => 10] as $name => $baseline) {
            if (self::range($windows, $name) !== [$baseline + $weeks[0], $baseline + $weeks[1]]) {
                throw new UnexpectedValueException('Candidate RC window drifted.');
            }
        }

        foreach (self::records($auditor, 'device_runs') as $run) {
            if (($run['status'] ?? null) !== 'NOT_RUN' || ($run['owner'] ?? null) !== 'Erastus'
                || ! in_array($run['matrix_row'] ?? null, [1, 3, 4], true)) {
                throw new UnexpectedValueException('A prepared device run is not measured evidence.');
            }

            self::requireNulls($run, ['device', 'os_version', 'started_at', 'finished_at', 'artifact']);
        }

        foreach (self::records($auditor, 'dwell_probes') as $run) {
            if (($run['status'] ?? null) !== 'NOT_STARTED' || ($run['minimum_elapsed_hours'] ?? null) !== 168
                || ($run['platform'] ?? null) !== 'iOS') {
                throw new UnexpectedValueException('Dwell evidence cannot be invented or shortened.');
            }

            self::requireNulls($run, ['origin', 'started_at', 'finished_at', 'artifact']);
        }

        $brs = file_get_contents(__DIR__.'/../../docs/Rozine-BRS.md');
        $plan = file_get_contents(__DIR__.'/../../docs/Rozine_Phased_Implementation_Plan.md');

        if ($brs === false || $plan === false) {
            throw new UnexpectedValueException('Missing authoritative source.');
        }

        $integrations = [];
        $trackIds = [];

        foreach (self::records($pack, 'provider_tracks') as $track) {
            $id = $track['id'] ?? null;

            if ($id !== sprintf('DEP-%02d', count($trackIds) + 1)
                || ($track['status'] ?? null) !== 'PREPARED_NOT_STARTED'
                || ($track['fake_status'] ?? null) !== 'SPECIFIED_NOT_IMPLEMENTED'
                || ($track['due_status'] ?? null) !== 'PROPOSED_NOT_ACCEPTED'
                || ! in_array($track['accountable'] ?? null, ['Aminu', 'Erastus', 'Kimani', 'Robert'], true)
                || ! in_array($track['engineering'] ?? null, ['Aminu', 'Erastus'], true)) {
                throw new UnexpectedValueException('Invalid or falsely activated provider track.');
            }

            $trackIds[] = $id;
            self::requireNulls($track, ['owner_acknowledged_at', 'selected_provider', 'contacted_at',
                'contract_evidence', 'sandbox_evidence', 'certification_evidence', 'live_approval']);
            $due = $track['next_review_due'] ?? null;
            $date = is_string($due) ? DateTimeImmutable::createFromFormat('!Y-m-d', $due) : false;

            if ($date === false || $date->format('Y-m-d') !== $due) {
                throw new UnexpectedValueException('A track needs a valid proposed review date.');
            }

            foreach (['requested_inputs', 'fake_cases', 'exit_evidence', 'requirement_ids', 'decision_ids'] as $key) {
                if (self::strings($track, $key) === []) {
                    throw new UnexpectedValueException('Incomplete provider preparation: '.$key);
                }
            }

            foreach (self::strings($track, 'requirement_ids') as $requirement) {
                if (! str_contains($brs, '`'.$requirement.'`')) {
                    throw new UnexpectedValueException('Unknown BRS requirement.');
                }

                if (str_starts_with($requirement, 'IR-')) {
                    $integrations[] = $requirement;
                }
            }

            foreach (self::strings($track, 'decision_ids') as $decision) {
                if (! str_contains($plan, '**'.$decision)) {
                    throw new UnexpectedValueException('Unknown decision gate.');
                }
            }
        }

        sort($integrations);

        if (count($trackIds) !== 13 || array_values(array_unique($integrations)) !== ['IR-1', 'IR-2', 'IR-3', 'IR-4', 'IR-5', 'IR-6', 'IR-7', 'IR-8']) {
            throw new UnexpectedValueException('External dependency coverage is incomplete.');
        }
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  list<string>  $keys
     */
    private static function requireNulls(array $record, array $keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $record) || $record[$key] !== null) {
                throw new UnexpectedValueException('Preparation cannot invent evidence: '.$key);
            }
        }
    }
}

test('delivery preparation preserves pending authority and reconciles estimates and integration coverage', function () {
    $pack = PhaseZeroDeliveryReadiness::load();
    PhaseZeroDeliveryReadiness::validate($pack);

    expect($pack['status'])->toBe('PREPARATION_ONLY_NOT_APPROVED')
        ->and(PhaseZeroDeliveryReadiness::records($pack, 'provider_tracks'))->toHaveCount(13);
});

test('the reduced device and dwell pack covers each actual required mode once', function () {
    $auditor = PhaseZeroDeliveryReadiness::object(PhaseZeroDeliveryReadiness::load(), 'auditor');

    expect(array_column(PhaseZeroDeliveryReadiness::records($auditor, 'device_runs'), 'matrix_row'))->toBe([1, 3, 4])
        ->and(array_column(PhaseZeroDeliveryReadiness::records($auditor, 'dwell_probes'), 'mode'))->toBe(['installed', 'browser_tab']);
});

test('readiness negative controls reject invented evidence omitted scope and estimate drift', function (string $violation) {
    $pack = PhaseZeroDeliveryReadiness::load();

    switch ($violation) {
        case 'approval':
            $pack['approvals']['engineering'] = 'invented approval';
            break;
        case 'capacity':
            $pack['auditor']['incremental_focused_weeks'] = [1, 2];
            break;
        case 'effort':
            $pack['auditor']['work_packages'][0]['aminu_days'] = [3, 4];
            break;
        case 'cycle':
            $pack['auditor']['work_packages'][0]['depends_on'] = ['CAP-06'];
            break;
        case 'device pass':
            $pack['auditor']['device_runs'][0]['status'] = 'PASS';
            break;
        case 'dwell shortened':
            $pack['auditor']['dwell_probes'][0]['minimum_elapsed_hours'] = 24;
            break;
        case 'dwell started':
            $pack['auditor']['dwell_probes'][0]['started_at'] = '2026-09-10T10:00:00Z';
            break;
        case 'contact sent':
            $pack['provider_tracks'][0]['contacted_at'] = '2026-09-10';
            break;
        case 'provider selected':
            $pack['provider_tracks'][0]['selected_provider'] = 'Unapproved';
            break;
        case 'integration omitted':
            $pack['provider_tracks'][0]['requirement_ids'] = ['CR-1', 'NFR-4'];
            break;
        case 'unknown decision':
            $pack['provider_tracks'][0]['decision_ids'] = ['D-999'];
            break;
        case 'duplicate track':
            $pack['provider_tracks'][1]['id'] = 'DEP-01';
            break;
    }

    expect(fn () => PhaseZeroDeliveryReadiness::validate($pack))->toThrow(UnexpectedValueException::class);
})->with(['approval', 'capacity', 'effort', 'cycle', 'device pass', 'dwell shortened', 'dwell started',
    'contact sent', 'provider selected', 'integration omitted', 'unknown decision', 'duplicate track']);

test('readable delivery documents preserve all IDs and well formed tables', function (string $filename, string $prefix, int $count) {
    $contents = file_get_contents(__DIR__.'/../../docs/phase-0/'.$filename);

    if ($contents === false) {
        throw new UnexpectedValueException('Missing delivery document.');
    }

    preg_match_all('/^\| ('.$prefix.'-\d{2}) \|/m', $contents, $matches);
    expect($matches[1])->toBe(array_map(fn (int $number): string => sprintf('%s-%02d', $prefix, $number), range(1, $count)));

    $columns = null;

    foreach (explode("\n", $contents) as $line) {
        if (! str_starts_with($line, '|')) {
            $columns = null;

            continue;
        }

        $width = count(explode('|', $line));
        $columns ??= $width;
        expect($width)->toBe($columns);
    }

    expect($contents)->toContain('## Status', '## Goal', '## Acceptance criteria');
})->with([
    ['auditor-capture-delivery-plan.md', 'CAP', 6],
    ['provider-dependency-register.md', 'DEP', 13],
]);
