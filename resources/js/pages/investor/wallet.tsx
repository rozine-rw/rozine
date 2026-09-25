import { Link, router } from '@inertiajs/react';
import { InvestorShell } from '@/components/investor/investor-shell';
import {
    BalanceCard,
    DepositPanel,
} from '@/components/investor/wallet/funding';
import {
    DepositIntents,
    HoldsList,
    ReceiptSheet,
    WalletHistory,
} from '@/components/investor/wallet/history';
import { PollStopped } from '@/components/rozine/c3-notice';
import { useBoundedPoll } from '@/hooks/use-bounded-poll';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import type { C3InvestorWalletProps } from '@/types/investor';

/**
 * Wallet (MVP-INVESTOR-SCR-08, design L2835–3095; C3 v2 §2a): the ledger total and its breakdown,
 * live checkout holds, deposit through a linked account under a versioned deposit policy, recorded
 * deposit intents, and the history with external cash apart from internal transfers. Withdrawal,
 * earnings and exports are hidden in C3. Deposit intents still `pending` or `unknown` are polled,
 * boundedly, for fresh facts. The design's Rozine Plus upsell is outside the MVP.
 */
export default function InvestorWallet(props: C3InvestorWalletProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const kind = props.funding.kind ?? (wide ? 'deposit' : null);
    const inFlight = props.deposits.some(
        (deposit) => deposit.state === 'pending' || deposit.state === 'unknown',
    );
    const poll = useBoundedPoll(inFlight, ['wallet', 'deposits', 'history']);
    const closeReceipt = () =>
        router.visit(props.links.close, { preserveScroll: true });

    const left = (
        <>
            <BalanceCard wallet={props.wallet} />
            {kind === null ? (
                <Link
                    href={props.links.deposit}
                    preserveScroll
                    className="mt-3.5 flex items-center justify-center gap-[7px] rounded-2xl bg-rz-accent-fill py-[13px] text-[13px] font-semibold text-white shadow-[0_5px_14px_-8px_rgba(20,45,95,.3)]"
                >
                    {t('investor.wallet.deposit')}
                </Link>
            ) : (
                <DepositPanel
                    key={kind}
                    funding={props.funding}
                    actions={props.actions}
                    allowed_actions={props.allowed_actions}
                    identity_context_revision={props.identity_context_revision}
                    preview_outcome={props.preview_outcome}
                    operation={props.links.operation}
                    close={wide ? null : props.links.close}
                    wide={wide}
                />
            )}
            {props.funding.methods.length === 0 && (
                <p className="mt-2.5 text-center text-xs text-rz-secondary">
                    {t('investor.wallet.no_methods')}{' '}
                    <Link
                        href={props.links.link_account}
                        className="font-semibold text-rz-accent-app-text"
                    >
                        {t('investor.wallet.link_account')}
                    </Link>
                </p>
            )}
            <HoldsList holds={props.holds} serverTime={props.server_time} />
        </>
    );

    const right = (
        <>
            <DepositIntents deposits={props.deposits} />
            <PollStopped {...poll} className="mt-2" />
            <WalletHistory history={props.history} />
            {props.receipt !== null && (
                <ReceiptSheet receipt={props.receipt} close={closeReceipt} />
            )}
        </>
    );

    return (
        <InvestorShell
            title={t('investor.wallet.title')}
            tab={null}
            links={props.links}
            showTabBar={false}
        >
            {wide ? (
                <div className="flex h-full min-h-0 flex-col px-[30px] pt-3 pb-3.5">
                    <div className="flex min-h-0 flex-1 flex-col px-5 pt-3.5 pb-[18px]">
                        <h1 className="text-[17px] font-semibold text-rz-ink">
                            {t('investor.wallet.title')}
                        </h1>
                        <div className="mt-1 flex min-h-0 flex-1 items-stretch gap-[18px]">
                            <div className="rz-scroll flex min-h-0 min-w-0 flex-[0_0_calc(50%-9px)] flex-col overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface p-4">
                                {left}
                            </div>
                            <div className="rz-scroll relative flex min-h-0 min-w-0 flex-[0_0_calc(50%-9px)] flex-col overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface p-4">
                                {right}
                            </div>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="px-4 pt-[calc(env(safe-area-inset-top)+12px)] pb-10">
                    <div className="flex items-center gap-[11px]">
                        <Link
                            href={props.links.deals}
                            aria-label={t('investor.common.back')}
                            className="flex size-[34px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-base text-rz-ink"
                        >
                            <span aria-hidden>←</span>
                        </Link>
                        <h1 className="text-[17px] font-semibold text-rz-ink">
                            {t('investor.wallet.title')}
                        </h1>
                    </div>
                    {left}
                    {right}
                </div>
            )}
        </InvestorShell>
    );
}
