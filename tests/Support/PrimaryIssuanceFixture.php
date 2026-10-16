<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\RecheckResult;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Infrastructure\Primary\EloquentFundedCampaigns;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use Illuminate\Support\Facades\DB;

/** Real retained purchases and store-generated closing ancestry; only external/current inputs are synthetic. */
final class PrimaryIssuanceFixture
{
    /**
     * @param  list<string>  $units
     * @param  array<int, string>  $buyers
     * @return array{campaign: FundedCampaign, intent: DisbursementIntent, observe: \Closure(string): IssueInstruction}
     */
    public static function approved(bool $settle = true, array $units = ['1080', '1080'], array $buyers = []): array
    {
        ['campaign' => $publication] = PrimaryHoldingFixture::committed(units: $units, requoted: [0], buyers: $buyers);
        $adapter = new class(app(EloquentFundedCampaigns::class), app(SyntheticDisbursementSources::class), $settle) implements FundedCampaigns
        {
            public function __construct(private FundedCampaigns $retained, private FundedCampaigns $current, private bool $settle) {}

            public function funded(?string $before, int $limit): array
            {
                return $this->retained->funded($before, $limit);
            }

            public function lockBusiness(string $businessId): void
            {
                $this->retained->lockBusiness($businessId);
            }

            public function lockFunded(string $campaignId): FundedCampaign
            {
                return $this->retained->lockFunded($campaignId);
            }

            public function recheck(FundedCampaign $campaign): RecheckResult
            {
                return $this->current->recheck($campaign);
            }

            public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
            {
                if ($this->settle) {
                    $this->retained->issue($campaign, $instruction);
                }
            }

            public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
            {
                $this->retained->failClose($campaign, $closing);
            }
        };
        app()->instance(FundedCampaigns::class, $adapter);
        DisbursementFixture::sources()->setDestination($publication->business_id, 'verified');
        app(DisbursementStore::class)->openFunded(1);
        $disbursement = Disbursement::query()->where('business_campaign_id', $publication->id)->sole();
        $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole();
        $campaign = DB::transaction(function () use ($publication): FundedCampaign {
            $retained = app(EloquentFundedCampaigns::class);
            $retained->lockBusiness($publication->business_id);

            return $retained->lockFunded($publication->id);
        });

        return ['campaign' => $campaign, 'intent' => $intent, 'observe' => function (string $effectiveAt) use ($intent): IssueInstruction {
            app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'succeeded', ['effective_at' => $effectiveAt]));

            $closing = DisbursementClosing::query()->where('intent_id', $intent->id)->where('kind', 'issued')->sole();
            $evidence = DB::transaction(fn () => app(DisbursementClosingEvidence::class)->find($closing->id));
            if ($evidence->intentOperationId === null || $evidence->effectiveAt === null || $evidence->effectiveDate === null || $evidence->dueDates === null) {
                throw new \LogicException('Issued closing did not carry complete authority.');
            }

            return new IssueInstruction($evidence->closingId, $evidence->disbursementId, $evidence->intentOperationId,
                $evidence->effectiveAt, $evidence->effectiveDate, $evidence->dueDates, $evidence->recordedAt);
        }];
    }

    /** @param list<string> $units
     * @param  array<int, string>  $buyers
     * @return array{campaign: FundedCampaign, instruction: IssueInstruction}
     */
    public static function close(string $effectiveAt = '2026-01-30T22:30:00+00:00', bool $settle = true, array $units = ['1080', '1080'], array $buyers = []): array
    {
        $approved = self::approved($settle, $units, $buyers);

        return ['campaign' => $approved['campaign'], 'instruction' => $approved['observe']($effectiveAt)];
    }

    public static function replay(FundedCampaign $campaign, IssueInstruction $instruction): void
    {
        DB::transaction(function () use ($campaign, $instruction): void {
            $adapter = app(EloquentFundedCampaigns::class);
            $adapter->lockBusiness($campaign->businessId);
            $adapter->lockFunded($campaign->campaignId);
            $adapter->issue($campaign, $instruction);
        });
    }
}
