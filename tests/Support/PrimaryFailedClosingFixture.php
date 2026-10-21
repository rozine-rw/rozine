<?php

declare(strict_types=1);

namespace Tests\Support;

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
use App\Models\DisbursementIntent;
use Illuminate\Support\Facades\DB;

/** Real retained purchases and store-generated closing ancestry; only external/current inputs are synthetic. */
final class PrimaryFailedClosingFixture
{
    /**
     * @param  list<string>  $units
     * @param  array<int, string>  $buyers
     * @return array{campaign: FundedCampaign, instruction: FailedClosing}
     */
    public static function close(string $cause = 'auto_recheck', bool $settle = true, array $units = ['1080', '1080'], array $buyers = []): array
    {
        ['campaign' => $publication] = PrimaryHoldingFixture::committed(units: $units, requoted: [0], buyers: $buyers);
        $adapter = new class(app(EloquentFundedCampaigns::class), app(SyntheticDisbursementSources::class), $settle) implements FundedCampaigns
        {
            public ?FundedCampaign $campaign = null;

            public ?FailedClosing $instruction = null;

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
                $this->retained->issue($campaign, $instruction);
            }

            public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
            {
                $this->campaign = $campaign;
                $this->instruction = $closing;
                if ($this->settle) {
                    $this->retained->failClose($campaign, $closing);
                }
            }
        };
        app()->instance(FundedCampaigns::class, $adapter);
        DisbursementFixture::sources()->setDestination($publication->business_id, 'verified');
        if ($cause === 'auto_recheck') {
            DisbursementFixture::sources()->scriptRecheck($publication->id, 'failed', ['restriction']);
            app(DisbursementStore::class)->openFunded(1);
        } elseif ($cause === 'worker_recheck') {
            DB::transaction(function () use ($publication): void {
                app(DisbursementStore::class)->openFunded(1);
                DisbursementFixture::sources()->scriptRecheck($publication->id, 'failed', ['exposure']);
            });
        } else {
            app(DisbursementStore::class)->openFunded(1);
        }
        $disbursement = Disbursement::query()->where('business_campaign_id', $publication->id)->sole();
        if (! in_array($cause, ['auto_recheck', 'worker_recheck'], true)) {
            $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole();
            app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, in_array($cause, ['pending', 'unknown', 'succeeded'], true) ? $cause : 'failed'));
        }

        if ($adapter->campaign === null) {
            $adapter->campaign = DB::transaction(function () use ($publication): FundedCampaign {
                $retained = app(EloquentFundedCampaigns::class);
                $retained->lockBusiness($publication->business_id);

                return $retained->lockFunded($publication->id);
            });
        }

        return ['campaign' => $adapter->campaign,
            'instruction' => $adapter->instruction ?? new FailedClosing(PrimaryHoldingFixture::id(), $disbursement->id, 'reconciled_failure', [])];
    }

    public static function replay(FundedCampaign $campaign, FailedClosing $instruction): void
    {
        DB::transaction(function () use ($campaign, $instruction): void {
            $adapter = app(EloquentFundedCampaigns::class);
            $adapter->lockBusiness($campaign->businessId);
            $adapter->lockFunded($campaign->campaignId);
            $adapter->failClose($campaign, $instruction);
        });
    }
}
