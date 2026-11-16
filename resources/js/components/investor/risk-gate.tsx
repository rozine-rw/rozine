import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { RiskStatementsStep } from '@/components/investor/deals/risk-statements';
import { useModalDialog } from '@/hooks/use-modal-dialog';
import { useTranslation } from '@/hooks/use-translation';
import { portfolio, wallet as walletRoute } from '@/routes/investor';
import type { InvestorRiskAcknowledgementDue } from '@/types/investor';

/**
 * Pages that put money toward an investment stay closed until the statements are acknowledged:
 * adding money to the wallet ("the wallet unlocks once it is ticked"), the cart and a deal's
 * checkout. Everything else only reads or returns the Investor's own money, so a holder can see
 * their balance, payouts and receipts and withdraw without them (#660).
 */
const MONEY_PAGE =
    /^\/investor\/(?:(?:cart|deals\/[^/?#]+\/checkout)(?:[/?#]|$)|wallet\?(?:[^#]*&)?kind=deposit(?:[&#]|$))/;

/** Where "Not now" goes from a closed page: a wallet deposit back to the wallet, the rest to Portfolio. */
const WALLET_DEPOSIT = /^\/investor\/wallet\?/;

const laterKey = (version: string) => `rozine.risk-gate.later.${version}`;

function wasPutOff(version: string): boolean {
    try {
        return window.sessionStorage.getItem(laterKey(version)) === '1';
    } catch {
        return false;
    }
}

function putOff(version: string): void {
    try {
        window.sessionStorage.setItem(laterKey(version), '1');
    } catch {
        // Without storage the statements simply come back on the next page.
    }
}

/**
 * The once-only risk acknowledgement (BRS v3.0 KYC-R05). While the shared
 * `risk_acknowledgement` prop names statements the verified Investor has not yet acknowledged in
 * their current wording, the Investor app opens on them, so a first checkout has nothing extra
 * to do. Accepting reloads the page, which then carries no prop.
 *
 * "Not now" puts them off for this visit, so an Investor can still read their portfolio,
 * holdings, deals, market and wallet (#644, #660). Adding money, the cart and checkout stay
 * behind the statements; there "Not now" goes back to the wallet or to Portfolio instead. They come back the next time the app opens.
 */
export function RiskGate() {
    const page = usePage();
    const due = page.props.risk_acknowledgement as
        | InvestorRiskAcknowledgementDue
        | null
        | undefined;

    return due ? (
        <Gate key={due.version} due={due} url={page.url ?? ''} />
    ) : null;
}

function Gate({
    due,
    url,
}: {
    due: InvestorRiskAcknowledgementDue;
    url: string;
}) {
    const { t } = useTranslation();
    const [later, setLater] = useState(() => wasPutOff(due.version));
    const moneyPage = MONEY_PAGE.test(url);
    const open = moneyPage || !later;
    const notNow = () => {
        putOff(due.version);
        setLater(true);

        if (moneyPage) {
            router.visit(
                WALLET_DEPOSIT.test(url) ? walletRoute().url : portfolio().url,
            );
        }
    };
    const dialog = useModalDialog<HTMLDivElement>(notNow, open, true);

    if (!open) {
        return null;
    }

    return (
        <div
            ref={dialog}
            tabIndex={-1}
            role="dialog"
            aria-modal="true"
            aria-labelledby="risk-statements-title"
            className="fixed inset-0 z-[95] flex items-end justify-center bg-black/40 p-4 sm:items-center"
        >
            <div className="max-h-full w-full max-w-[480px] overflow-y-auto">
                <RiskStatementsStep
                    risks={{ ...due, acknowledged: false }}
                    action={due.action}
                    identityRevision={due.identity_context_revision}
                    note={moneyPage ? t('investor.risk_gate.money_page') : null}
                    secondary={
                        <button
                            type="button"
                            onClick={notNow}
                            className="mt-2 h-[44px] w-full rounded-xl border border-rz-border text-[13.5px] font-semibold text-rz-ink"
                        >
                            {moneyPage
                                ? t(
                                      WALLET_DEPOSIT.test(url)
                                          ? 'investor.risk_gate.later_wallet'
                                          : 'investor.risk_gate.later_portfolio',
                                  )
                                : t('investor.risk_gate.later')}
                        </button>
                    }
                />
            </div>
        </div>
    );
}
