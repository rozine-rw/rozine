<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Primary\Contracts\CampaignDisbursementProgress;
use App\Domain\Disbursement\DisbursementState;
use App\Models\Disbursement;
use App\Models\DisbursementDispatch;
use App\Models\DisbursementEvent;
use App\Models\DisbursementIntent;
use App\Models\DisbursementProviderEvent;

/**
 * Folds the campaign's disbursement events: from the recorded intent until its closing the payout
 * is in flight. The provider outcome is the last applied observation, or what the dispatch left:
 * a send that may not have reached the provider is `unknown`. A provider success still awaiting
 * its closing is `pending`, because nothing is issued until the closing is recorded.
 */
final class EloquentCampaignDisbursementProgress implements CampaignDisbursementProgress
{
    public function inFlight(string $campaignId): ?string
    {
        $disbursement = Disbursement::query()->where('business_campaign_id', $campaignId)->first();
        if ($disbursement === null) {
            return null;
        }
        $state = DisbursementState::fold(array_values(DisbursementEvent::query()->where('disbursement_id', $disbursement->id)->orderBy('revision')
            ->get(['kind', 'actor_user_id'])->map(fn (DisbursementEvent $event): array => ['kind' => $event->kind, 'actor_user_id' => $event->actor_user_id])->all()));
        $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->first();
        if (! in_array($state->state, ['queued', 'dispatched', 'succeeded'], true) || $intent === null) {
            return null;
        }
        $applied = DisbursementProviderEvent::query()->where('intent_id', $intent->id)->where('disposition', 'applied')->orderByDesc('id')->value('state');
        if (is_string($applied)) {
            return $applied === 'unknown' ? 'unknown' : 'pending';
        }

        return DisbursementDispatch::query()->where('intent_id', $intent->id)->where('phase', 'unsent')->exists() ? 'unknown' : 'pending';
    }
}
