<?php

declare(strict_types=1);

use App\Domain\Primary\FundingAdmission;

it('requires a bound explicit positive server result for every funding prerequisite', function (string $damage, ?string $expected): void {
    $admission = ['campaign_id' => 'campaign', 'publication_sha256' => 'publication',
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['test' => 'explicit source evidence']])];
    if ($damage === 'wrong_binding') {
        $admission['publication_sha256'] = 'other';
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
    'missing destination' => ['missing', 'POLICY_INPUT_REQUIRED'], 'empty policy evidence' => ['empty', 'POLICY_INPUT_REQUIRED'],
    'failed eligibility' => ['failed', 'FUNDING_PRECHECK_FAILED'], 'unavailable connections' => ['unavailable', 'POLICY_INPUT_REQUIRED'],
]);
