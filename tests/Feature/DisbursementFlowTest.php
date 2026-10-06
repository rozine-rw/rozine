<?php

declare(strict_types=1);

use App\Application\Disbursement\ReconcileDisbursements;
use App\Models\DisbursementClosing;
use App\Models\DisbursementProviderCall;
use Tests\Support\DisbursementFixture;

it('authorizes, approves, dispatches after commit and issues once on a reconciled success', function (): void {
    ['campaign' => $campaign, 'disbursement' => $disbursement, 'intent' => $intent, 'approval' => $approval, 'checker' => $checker] = DisbursementFixture::approved();
    expect($approval['code'])->toBe('DISBURSEMENT_INTENT_RECORDED')
        ->and(DisbursementProviderCall::query()->where('intent_id', $intent->id)->where('kind', 'send')->count())->toBe(1)
        ->and(DisbursementFixture::detail($checker, $disbursement)['state'])->toBe('dispatched');
    DisbursementFixture::provider()->scriptQuery($intent->id, 'succeeded');
    $result = app(ReconcileDisbursements::class)->handle();
    expect($result['decisions'])->toBe(['matched_success' => 1])
        ->and(DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->sole()->kind)->toBe('issued')
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId))->toHaveCount(1)
        ->and(DisbursementFixture::detail($checker, $disbursement)['state'])->toBe('succeeded');
});
