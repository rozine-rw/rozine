<?php

declare(strict_types=1);

namespace App\Application\Primary;

/**
 * The Portfolio facts for the authenticated Investor (C3 v2 §2d), read under current Investor
 * authority through `GetInvestorViewer`. The only portfolio fact that exists today is the cash
 * still idle in the wallet. A Holding is issued only on a reconciled disbursement success and
 * nothing issues one yet (`primary_holdings` is unactivated), there is no Investor-wide
 * commitment read, and admission fails closed, so no commitment can await issue: the page shows
 * its empty state rather than any figure that was not read.
 *
 * @phpstan-type PortfolioFacts array{identity_context_revision: int, verified: bool, idle: array{currency: string, amount: string}|null}
 */
final class GetInvestorPortfolio
{
    public const array TABS = ['active', 'matured'];

    public function __construct(private GetInvestorViewer $viewer) {}

    /**
     * Idle cash is the available wallet bucket as the ledger reports it, or null when there is
     * none; a person still being verified has no wallet yet.
     *
     * @return PortfolioFacts
     */
    public function handle(int $userId, ?int $contextRevision): array
    {
        $viewer = $this->viewer->handle($userId, $contextRevision);
        /** @var array{currency: string, amount: string}|null $available */
        $available = $viewer['wallet']['wallet']['breakdown']['available'] ?? null;

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'verified' => $viewer['wallet'] !== null,
            'idle' => $available === null || $available['amount'] === '0' ? null : $available];
    }
}
