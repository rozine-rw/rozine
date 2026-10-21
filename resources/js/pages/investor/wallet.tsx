import { Link, router } from '@inertiajs/react';
import { InvestorShell } from '@/components/investor/investor-shell';
import {
    BalanceCard,
    DepositPanel,
    WithdrawPanel,
} from '@/components/investor/wallet/funding';
import {
    DepositIntents,
    HoldsList,
    ReceiptSheet,
    WalletHistory,
    WithdrawalList,
} from '@/components/investor/wallet/history';
import { PollStopped } from '@/components/rozine/c3-notice';
import { useBoundedPoll } from '@/hooks/use-bounded-poll';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import { cn } from '@/lib/utils';
import type { C3InvestorWalletProps } from '@/types/investor';

/**
 * Wallet (MVP-INVESTOR-SCR-08, design L2835–3095; C3 v2 §2a): the ledger total and its breakdown,
 * live checkout holds, deposit through a linked account under a versioned deposit policy, withdrawal
 * to the Investor's own verified account (BRS LED-R04), recorded deposits and withdrawals, and the
 * history with external cash apart from internal transfers, with its statement downloads. Earnings
 * are hidden in C3. Deposits and withdrawals still `pending` or `unknown` are polled, boundedly, for
 * fresh facts. The design's Rozine Plus upsell is outside the MVP.
 */
/** What a wallet change reloads: the wallet's facts and actions, never the deposit form. */
const BEACON_RELOADS = [
    'server_time',
    'allowed_actions',
    'wallet',
    'deposits',
    'withdrawals',
    'history',
    'receipt',
];

const unsettled = (state: string): boolean =>
    state === 'pending' || state === 'unknown';

export default function InvestorWallet(props: C3InvestorWalletProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const kind = props.funding.kind ?? (wide ? 'deposit' : null);
    const inFlight =
        props.deposits.some((deposit) => unsettled(deposit.state)) ||
        props.withdrawals.some((withdrawal) => unsettled(withdrawal.state));
    const poll = useBoundedPoll(inFlight, [
        'wallet',
        'deposits',
        'withdrawals',
        'history',
    ]);
    const closeReceipt = () =>
        router.visit(props.links.close, { preserveScroll: true });

    const left = (
        <>
            <BalanceCard
                wallet={props.wallet}
                depositOffered={props.allowed_actions.includes(
                    'wallet.deposit',
                )}
            />
            <nav
                aria-label={t('investor.wallet.c3.funding')}
                className="mt-3.5 flex gap-2"
            >
                {(['deposit', 'withdraw'] as const).map((option) => (
                    <Link
                        key={option}
                        href={props.links[option]}
                        preserveScroll
                        aria-current={kind === option ? 'true' : undefined}
                        className={cn(
                            'flex flex-1 items-center justify-center gap-[7px] rounded-2xl border py-[13px] text-[13px] font-semibold shadow-[0_5px_14px_-8px_rgba(20,45,95,.3)]',
                            kind === option
                                ? 'border-rz-accent-fill bg-rz-accent-fill text-white'
                                : 'border-rz-border bg-rz-surface text-rz-ink',
                        )}
                    >
                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className={cn(
                                kind === option
                                    ? 'stroke-white'
                                    : option === 'deposit'
                                      ? 'stroke-rz-accent-fill'
                                      : 'stroke-[#b05c1a] dark:stroke-[#f0a060]',
                            )}
                        >
                            <path
                                d={
                                    option === 'deposit'
                                        ? 'M12 4v10M8 11l4 4 4-4M5 20h14'
                                        : 'M12 20V10M8 13l4-4 4 4M5 4h14'
                                }
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                        {t(`investor.wallet.${option}`)}
                    </Link>
                ))}
            </nav>
            {kind === 'withdraw' && (
                <WithdrawPanel
                    funding={props.funding}
                    action={props.actions.withdraw}
                    available={props.wallet.breakdown.available}
                    allowed_actions={props.allowed_actions}
                    identity_context_revision={props.identity_context_revision}
                    preview_outcome={props.preview_outcome}
                    operation={props.links.withdraw_operation}
                    close={wide ? null : props.links.close}
                    wide={wide}
                />
            )}
            {kind === 'deposit' && (
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
                    {t('investor.wallet.no_methods')}
                    {props.links.link_account !== null && (
                        <>
                            {' '}
                            <Link
                                href={props.links.link_account}
                                className="font-semibold text-rz-accent-app-text"
                            >
                                {t('investor.wallet.link_account')}
                            </Link>
                        </>
                    )}
                </p>
            )}
            <HoldsList holds={props.holds} serverTime={props.server_time} />
        </>
    );

    const right = (
        <>
            <DepositIntents deposits={props.deposits} />
            <WithdrawalList withdrawals={props.withdrawals} />
            <PollStopped {...poll} className="mt-2" />
            <WalletHistory history={props.history} exports={props.exports} />
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
            beacon={{
                link: props.links.changes,
                reloads: { wallet: { only: BEACON_RELOADS } },
            }}
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
                        {props.links.deals !== null && (
                            <Link
                                href={props.links.deals}
                                aria-label={t('investor.common.back')}
                                className="flex size-[34px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-base text-rz-ink"
                            >
                                <span aria-hidden>←</span>
                            </Link>
                        )}
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
