<?php

declare(strict_types=1);

use App\Application\Business\Contracts\CampaignClosureEvidence;
use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Infrastructure\Business\RetainedAuditedRaises;
use App\Models\BusinessCampaign;

it('caps eligible audited raises after skipping newer funded and unrated campaigns', function (): void {
    $this->freezeSecond();
    $oldest = BusinessCampaign::factory()->create(['live_at' => now()->subDays(3)]);
    $live = BusinessCampaign::factory()->create(['live_at' => now()->subDays(2)]);
    $funded = BusinessCampaign::factory()->create(['live_at' => now()->subDay()]);
    $unrated = BusinessCampaign::factory()->create();
    $foreign = BusinessCampaign::factory()->create();
    $card = ['name' => 'Synthetic business', 'lifecycle' => 'live', 'raised' => ['amount' => '15000'],
        'target' => ['amount' => '3000000'], 'investors' => 2];
    $cards = [$oldest->id => [...$card, 'lifecycle' => 'funded'], $live->id => $card,
        $funded->id => [...$card, 'lifecycle' => 'funded']];
    $deals = new class($cards) implements InvestorDealCatalogue
    {
        /** @var list<string> */
        public array $requested = [];

        /** @param array<string, array<string, mixed>> $cards */
        public function __construct(private array $cards) {}

        public function card(string $campaignId): ?array
        {
            $this->requested[] = $campaignId;

            return $this->cards[$campaignId] ?? null;
        }

        public function deals(): array
        {
            throw new LogicException('Only individual cards belong to this projection.');
        }

        public function deal(string $campaignId): ?array
        {
            throw new LogicException('This projection must not request private deal detail.');
        }

        public function updates(string $campaignId): array
        {
            throw new LogicException('This projection must not request monthly updates.');
        }
    };
    $closures = new class($oldest->id) implements CampaignClosureEvidence
    {
        public function __construct(private string $failedCampaign) {}

        public function find(string $campaignId): ?array
        {
            return $campaignId === $this->failedCampaign
                ? ['committed_refunded' => ['amount' => '25000'], 'investors' => 3] : null;
        }

        public function released(string $businessId): array
        {
            throw new LogicException('This projection must not request exposure releases.');
        }
    };
    $publications = new class implements PublishedCampaignEvidence
    {
        public function find(string $campaignId): array
        {
            return ['title' => 'Synthetic raise'];
        }
    };
    $raises = new RetainedAuditedRaises($deals, $publications, $closures);
    $applications = [$oldest->business_application_id, $live->business_application_id, $funded->business_application_id, $unrated->business_application_id];

    expect($raises->unfunded($applications, 1))->toBe([
        ['campaign_id' => $live->id, 'business' => 'Synthetic business', 'title' => 'Synthetic raise',
            'status' => 'live', 'raised' => '15000', 'target' => '3000000', 'investors' => 2],
    ])->and($deals->requested)->toBe([$unrated->id, $funded->id, $live->id]);

    $deals->requested = [];
    expect($raises->unfunded($applications, 2))->toBe([
        ['campaign_id' => $live->id, 'business' => 'Synthetic business', 'title' => 'Synthetic raise',
            'status' => 'live', 'raised' => '15000', 'target' => '3000000', 'investors' => 2],
        ['campaign_id' => $oldest->id, 'business' => 'Synthetic business', 'title' => 'Synthetic raise',
            'status' => 'failed', 'raised' => '25000', 'target' => '3000000', 'investors' => 3],
    ])->and($deals->requested)->toBe([$unrated->id, $funded->id, $live->id, $oldest->id])
        ->and($deals->requested)->not->toContain($foreign->id);
});

it('returns no raises for a nonpositive result cap', function (int $limit): void {
    $campaign = BusinessCampaign::factory()->create();
    $raises = app(RetainedAuditedRaises::class);

    expect($raises->unfunded([$campaign->business_application_id], $limit))->toBe([]);
})->with([0, -1]);
