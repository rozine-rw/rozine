import { Link, router } from '@inertiajs/react';
import { BusinessShell } from '@/components/business/business-shell';
import { BalanceCard } from '@/components/business/wallet/balance-card';
import {
    EntryReceiptSheet,
    WalletHistory,
} from '@/components/business/wallet/history';
import {
    DepositPanel,
    WithdrawPanel,
} from '@/components/investor/wallet/funding';
import {
    DepositIntents,
    ReceiptSheet,
    WithdrawalList,
} from '@/components/investor/wallet/history';
import { PollStopped } from '@/components/rozine/c3-notice';
import { useBoundedPoll } from '@/hooks/use-bounded-poll';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { BusinessWalletProps } from '@/types/business';
import type { DepositIntent, WalletWithdrawal } from '@/types/investor';

type Opened = NonNullable<BusinessWalletProps['receipt']>;

type BusinessWalletCommand =
    | 'business.wallet.deposit'
    | 'business.wallet.withdraw';

const isRecord = (
    receipt: Opened,
): receipt is DepositIntent | WalletWithdrawal =>
    'intent_receipt' in receipt || 'request_receipt' in receipt;

/** The design's Deposit and Withdraw pair (L1333–1351): the open panel's button is filled. */
function MovementButton({
    href,
    active,
    label,
    tone,
    arrow,
}: {
    href: BusinessWalletProps['links']['deposit'];
    active: boolean;
    label: string;
    tone: 'deposit' | 'withdraw';
    arrow: string;
}) {
    return (
        <Link
            href={href}
            preserveScroll
            aria-current={active || undefined}
            className={cn(
                'flex items-center justify-center gap-[7px] rounded-2xl border py-[13px] text-[13px] font-semibold shadow-[0_5px_14px_-8px_rgba(20,45,95,.3)]',
                active
                    ? tone === 'deposit'
                        ? 'border-[#17795a] bg-[#17795a] text-white'
                        : 'border-[#7d420f] bg-[#7d420f] text-white'
                    : 'border-rz-border bg-rz-surface text-rz-ink',
            )}
        >
            <svg
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden
            >
                <path
                    d={arrow}
                    stroke={
                        active
                            ? '#fff'
                            : tone === 'deposit'
                              ? '#17795a'
                              : '#b05c1a'
                    }
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
            {label}
        </Link>
    );
}

/**
 * Wallet (MVP-BUSINESS-SCR-08, design L1315–1457; C4 v1 §4a, `business.wallet.show`), reached
 * from Home's wallet pill or from Repayments when funds are short. Repayments are paid from the
 * available balance, which the Business funds through the C3 deposit adapter: a deposit records
 * an intent only, and nothing is credited until the provider's success is verified. A withdrawal
 * (APP-B01, LED-R04) goes only to the Business's own verified account and leaves `available` at
 * once; it is paid out on the provider's verified success, or returned on a failure. Intents and
 * withdrawals still `pending` or `unknown` are polled, boundedly.
 */
export default function BusinessWallet(props: BusinessWalletProps) {
    const { t } = useTranslation();
    const inFlight = [...props.deposits, ...props.withdrawals].some(
        (record) => record.state === 'pending' || record.state === 'unknown',
    );
    const poll = useBoundedPoll(inFlight, [
        'wallet',
        'deposits',
        'withdrawals',
        'history',
    ]);
    const kind = props.funding.kind;
    const closeReceipt = () =>
        router.visit(props.links.close, { preserveScroll: true });
    const receipt = props.receipt;

    return (
        <BusinessShell
            title={t('business.wallet.title')}
            tab="home"
            links={props.shell_links}
            showTabBar={false}
        >
            <div
                data-rzcol
                className="relative px-4 pt-[calc(env(safe-area-inset-top)+2px)] pb-10 lg:h-full lg:overflow-y-auto lg:pt-[52px]"
            >
                <div className="flex items-center gap-[11px]">
                    <Link
                        href={props.shell_links.home}
                        aria-label={t('business.wallet.back')}
                        className="flex size-[34px] items-center justify-center rounded-xl border border-rz-border bg-rz-surface text-base text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <h1 className="text-[17px] font-semibold text-rz-ink">
                        {t('business.wallet.title')}
                    </h1>
                </div>
                <BalanceCard
                    wallet={props.wallet}
                    basis={props.bases.available}
                />
                {props.links.repayments !== null && (
                    <Link
                        href={props.links.repayments}
                        className="mt-2.5 block text-center text-xs font-semibold text-rz-accent-app-text"
                    >
                        {t('business.servicing.wallet.to_repayments')}
                    </Link>
                )}
                <div className="mt-3.5 grid grid-cols-2 gap-[11px]">
                    <MovementButton
                        href={
                            kind === 'deposit'
                                ? props.links.close
                                : props.links.deposit
                        }
                        active={kind === 'deposit'}
                        label={t('business.wallet.deposit.button')}
                        tone="deposit"
                        arrow="M12 4v10M8 11l4 4 4-4M5 20h14"
                    />
                    <MovementButton
                        href={
                            kind === 'withdraw'
                                ? props.links.close
                                : props.links.withdraw
                        }
                        active={kind === 'withdraw'}
                        label={t('business.wallet.withdraw.button')}
                        tone="withdraw"
                        arrow="M12 20V10M8 13l4-4 4 4M5 4h14"
                    />
                </div>
                {kind === 'deposit' && (
                    <DepositPanel<BusinessWalletCommand>
                        funding={props.funding}
                        actions={props.actions}
                        allowed_actions={props.allowed_actions}
                        identity_context_revision={
                            props.identity_context_revision
                        }
                        preview_outcome={props.preview_outcome}
                        operation={props.links.operation}
                        close={props.links.close}
                        wide={false}
                        command="business.wallet.deposit"
                        payload={{ business_id: props.business.id }}
                    />
                )}
                {kind === 'withdraw' && (
                    <WithdrawPanel<BusinessWalletCommand>
                        funding={props.funding}
                        action={props.actions.withdraw}
                        available={props.wallet.available}
                        allowed_actions={props.allowed_actions}
                        identity_context_revision={
                            props.identity_context_revision
                        }
                        preview_outcome={props.preview_outcome}
                        operation={props.links.withdrawal_operation}
                        close={props.links.close}
                        wide={false}
                        command="business.wallet.withdraw"
                        payload={{ business_id: props.business.id }}
                    />
                )}
                <DepositIntents deposits={props.deposits} />
                <WithdrawalList withdrawals={props.withdrawals} />
                <PollStopped {...poll} className="mt-2" />
                <WalletHistory
                    history={props.history}
                    exports={props.exports}
                />
                {receipt !== null &&
                    (isRecord(receipt) ? (
                        <ReceiptSheet receipt={receipt} close={closeReceipt} />
                    ) : (
                        <EntryReceiptSheet
                            entry={receipt}
                            close={closeReceipt}
                        />
                    ))}
            </div>
        </BusinessShell>
    );
}
