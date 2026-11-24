import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { RiskStatementsStep } from '@/components/investor/deals/risk-statements';
import { useModalDialog } from '@/hooks/use-modal-dialog';
import { useTranslation } from '@/hooks/use-translation';
import { portfolio } from '@/routes/investor';
import type { InvestorRiskAcknowledgementDue } from '@/types/investor';

/**
 * Pages that put money toward an investment stay closed until the statements are acknowledged:
 * the cart and a deal's checkout. Everything else only reads or returns the Investor's own money,
 * so a holder can see their balance, payouts and receipts and withdraw without them (#660).
 * Adding money is gated where it is used, on Deposit, since wide screens show the deposit form on
 * every wallet visit (#663).
 */
const MONEY_PAGE = /^\/investor\/(?:cart|deals\/[^/?#]+\/checkout)(?:[/?#]|$)/;

/**
 * The wallet never opens the statements by itself: wide screens put the deposit form on it, and
 * Deposit asks for them when it is used (#663).
 */
const ASKED_ON_USE = /^\/investor\/wallet(?:[/?#]|$)/;

/** Asks the open gate to show the statements for an action that adds money. */
const REQUEST_EVENT = 'rozine:risk-statements';

/**
 * Before an action that adds money: true when nothing is due, otherwise the statements open
 * (whatever "Not now" said earlier) and the action waits for them.
 */
export function useRiskStatementsCleared(): () => boolean {
    const due = usePage().props.risk_acknowledgement as
        | InvestorRiskAcknowledgementDue
        | null
        | undefined;

    return () => {
        if (!due) {
            return true;
        }

        window.dispatchEvent(new Event(REQUEST_EVENT));

        return false;
    };
}

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
 * holdings, deals, market and wallet (#644, #660). The cart and checkout stay behind the
 * statements, where "Not now" goes to Portfolio instead; Deposit opens them again before it sends,
 * where "Not now" just closes them over the wallet (#663). They come back the next time the app opens.
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
    const [requested, setRequested] = useState(false);
    const moneyPage = MONEY_PAGE.test(url);
    const open = moneyPage || requested || (!later && !ASKED_ON_USE.test(url));
    const notNow = () => {
        putOff(due.version);
        setLater(true);

        if (requested) {
            setRequested(false);
        } else if (moneyPage) {
            router.visit(portfolio().url);
        }
    };

    useEffect(() => {
        const request = () => setRequested(true);

        window.addEventListener(REQUEST_EVENT, request);

        return () => window.removeEventListener(REQUEST_EVENT, request);
    }, []);
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
                    note={
                        requested
                            ? t('investor.risk_gate.deposit')
                            : moneyPage
                              ? t('investor.risk_gate.money_page')
                              : null
                    }
                    secondary={
                        <button
                            type="button"
                            onClick={notNow}
                            className="mt-2 h-[44px] w-full rounded-xl border border-rz-border text-[13.5px] font-semibold text-rz-ink"
                        >
                            {t(
                                requested
                                    ? 'investor.risk_gate.later_wallet'
                                    : moneyPage
                                      ? 'investor.risk_gate.later_portfolio'
                                      : 'investor.risk_gate.later',
                            )}
                        </button>
                    }
                />
            </div>
        </div>
    );
}
