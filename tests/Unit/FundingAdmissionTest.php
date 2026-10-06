<?php

declare(strict_types=1);

use App\Domain\Primary\FundingAdmission;

it('requires a bound explicit positive server result for every funding prerequisite', function (string $damage, ?string $expected): void {
    $admission = ['campaign_id' => 'campaign', 'publication_sha256' => 'publication',
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['test' => 'explicit source evidence']])];
    if ($damage === 'wrong_binding') {
        $admission['publication_sha256'] = 'other';
    } elseif ($damage === 'wrong_campaign') {
        $admission['campaign_id'] = 'other';
    } elseif ($damage === 'missing') {
        unset($admission['destination']);
    } elseif ($damage === 'empty') {
        $admission['policy']['evidence'] = [];
    } elseif ($damage === 'failed') {
        $admission['eligibility']['status'] = 'failed';
    } elseif ($damage === 'unavailable') {
        $admission['connections']['status'] = 'unavailable';
    }
    expect(FundingAdmission::rejectionReason($admission, 'campaign', 'publication'))->toBe($expected);
})->with([
    'complete' => ['complete', null], 'wrong binding' => ['wrong_binding', 'FUNDING_ADMISSION_MISMATCH'],
    'wrong campaign' => ['wrong_campaign', 'FUNDING_ADMISSION_MISMATCH'],
    'missing destination' => ['missing', 'POLICY_INPUT_REQUIRED'], 'empty policy evidence' => ['empty', 'POLICY_INPUT_REQUIRED'],
    'failed eligibility' => ['failed', 'FUNDING_PRECHECK_FAILED'], 'unavailable connections' => ['unavailable', 'POLICY_INPUT_REQUIRED'],
]);

it('requires a source fact rather than containers of absent evidence', function (string $check, array $evidence, ?string $expected): void {
    $admission = ['campaign_id' => 'campaign', 'publication_sha256' => 'publication',
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['source' => 'retained-source-id']])];
    $admission[$check] = ['status' => 'passed', 'evidence' => $evidence];

    expect(FundingAdmission::rejectionReason($admission, 'campaign', 'publication'))->toBe($expected);
})->with(['eligibility', 'policy', 'connections', 'destination'])->with([
    'null list' => [[null], 'POLICY_INPUT_REQUIRED'],
    'null map' => [['source' => null], 'POLICY_INPUT_REQUIRED'],
    'empty nested containers' => [['source' => [[], [null]]], 'POLICY_INPUT_REQUIRED'],
    'blank source' => [['source' => '  '], 'POLICY_INPUT_REQUIRED'],
    'empty string' => [['source' => ''], 'POLICY_INPUT_REQUIRED'],
    'object placeholder' => [['source' => new stdClass], 'POLICY_INPUT_REQUIRED'],
    'retained nested identity' => [['optional' => null, 'source' => ['id' => 'retained-source-id']], null],
    'recorded zero' => [['count' => 0], null],
    'recorded false' => [['connected' => false], null],
]);
