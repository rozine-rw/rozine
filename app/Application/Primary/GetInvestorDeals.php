<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Domain\Operations\CommandRejection;
use Brick\Math\BigDecimal;

/**
 * The Deals deck for the authenticated Investor. Current Investor authority comes first, through
 * the wallet read; the deck then reads only the allowlisted publication projection. Nothing here
 * quotes, reserves or chooses terms: until admission exists there is no quote and no checkout.
 *
 * A person whose identity is not yet verified may browse the same deck while it is checked, with
 * no wallet and the stage of their verification instead: verification comes before any investment
 * (BRS FR-100, CR-1), not before reading the deals. Every command still requires the Investor role.
 *
 * @phpstan-type DealsPage array{identity_context_revision: int, restricted: bool, available: array{currency: string, amount: string}|null,
 *     verification: 'required'|'pending'|null, sort: string, industry: string|null, industries: list<array{industry: string|null, count: int}>,
 *     deals: list<array<string, mixed>>, focus: array<string, mixed>|null}
 */
final class GetInvestorDeals
{
    public const array SORTS = ['all', 'top_interest', 'top_rated'];

    public function __construct(private GetInvestorViewer $viewer, private InvestorDealCatalogue $catalogue) {}

    /**
     * @param  array{sort?: string|null, industry?: string|null, deal?: string|null}  $query
     * @return DealsPage
     */
    public function page(int $userId, ?int $contextRevision, array $query): array
    {
        $viewer = $this->viewer($userId, $contextRevision);
        $all = $this->catalogue->deals();
        $sort = in_array($query['sort'] ?? null, self::SORTS, true) ? $query['sort'] : 'all';
        $industry = $query['industry'] ?? null;
        $names = array_values(array_unique(array_map(fn (array $deal): string => (string) $deal['industry'], $all)));
        sort($names, SORT_STRING);
        $industry = in_array($industry, $names, true) ? $industry : null;
        $deals = self::sorted(array_values(array_filter($all, fn (array $deal): bool => $industry === null || $deal['industry'] === $industry)), $sort);
        $focusId = $query['deal'] ?? ($deals[0]['campaign_id'] ?? null);

        return [...$viewer, 'sort' => $sort, 'industry' => $industry,
            'industries' => [['industry' => null, 'count' => count($all)],
                ...array_map(fn (string $name): array => ['industry' => $name,
                    'count' => count(array_filter($all, fn (array $deal): bool => $deal['industry'] === $name))], $names)],
            'deals' => $deals, 'focus' => $focusId === null ? null : $this->catalogue->deal($focusId)];
    }

    /**
     * One deal, with the deck it sits in.
     *
     * @return DealsPage
     */
    public function show(int $userId, ?int $contextRevision, string $campaignId): array
    {
        $page = $this->page($userId, $contextRevision, ['deal' => $campaignId]);
        if ($page['focus'] === null) {
            throw new CommandRejection('DEAL_NOT_FOUND', 404);
        }

        return $page;
    }

    /**
     * A verified Investor's wallet facts or, for a person whose identity is still unverified, no
     * wallet and whether their submission is with Compliance (`GetInvestorViewer`).
     *
     * @return array{identity_context_revision: int, restricted: bool, available: array{currency: string, amount: string}|null, verification: 'required'|'pending'|null}
     */
    private function viewer(int $userId, ?int $contextRevision): array
    {
        $viewer = $this->viewer->handle($userId, $contextRevision);
        $wallet = $viewer['wallet'];

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'restricted' => $wallet !== null && $wallet['wallet']['status'] === 'restricted',
            'available' => $wallet === null ? null : $wallet['wallet']['breakdown']['available'], 'verification' => $viewer['verification']];
    }

    /**
     * Newest first unless asked otherwise: most filled, or highest rated. Ties keep that order.
     *
     * @param  list<array<string, mixed>>  $deals
     * @return list<array<string, mixed>>
     */
    private static function sorted(array $deals, string $sort): array
    {
        if ($sort === 'all') {
            return $deals;
        }
        $key = $sort === 'top_interest' ? 'funded_pct' : 'score';
        usort($deals, fn (array $a, array $b): int => BigDecimal::of(self::metric($b, $key))->compareTo(self::metric($a, $key)));

        return $deals;
    }

    /** @param array<string, mixed> $deal */
    private static function metric(array $deal, string $key): string
    {
        return $key === 'score' ? (string) $deal['rating']['score'] : (string) $deal['funded_pct'];
    }
}
