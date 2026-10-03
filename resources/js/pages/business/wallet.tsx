import { Link, router } from '@inertiajs/react';
import { BusinessShell } from '@/components/business/business-shell';
import { BalanceCard } from '@/components/business/wallet/balance-card';
import {
    EntryReceiptSheet,
    WalletHistory,
} from '@/components/business/wallet/history';
import { DepositPanel } from '@/components/investor/wallet/funding';
import {
    DepositIntents,
    ReceiptSheet,
} from '@/components/investor/wallet/history';
import { PollStopped } from '@/components/rozine/c3-notice';
import { useBoundedPoll } from '@/hooks/use-bounded-poll';
import { useTranslation } from '@/hooks/use-translation';
import type { BusinessWalletProps } from '@/types/business';
import type { DepositIntent } from '@/types/investor';

const isIntent = (
    receipt: NonNullable<BusinessWalletProps['receipt']>,
): receipt is DepositIntent => 'intent_receipt' in receipt;

/**
 * Wallet (MVP-BUSINESS-SCR-08, design L1315–1457; C4 v1 §4a, `business.wallet.show`), reached
 * from Home's wallet pill or from Repayments when funds are short. Repayments are paid from the
 * available balance, which the Business funds through the C3 deposit adapter: a deposit records
 * an intent only, and nothing is credited until the provider's success is verified. Intents still
 * `pending` or `unknown` are polled, boundedly. Withdrawal and exports are Phase 2.
 */
export default function BusinessWallet(props: BusinessWalletProps) {
    const { t } = useTranslation();
    const inFlight = props.deposits.some(
        (deposit) => deposit.state === 'pending' || deposit.state === 'unknown',
    );
    const poll = useBoundedPoll(inFlight, ['wallet', 'deposits', 'history']);
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
                {props.funding.kind === null ? (
                    <Link
                        href={props.links.deposit}
                        preserveScroll
                        className="mt-3.5 flex items-center justify-center rounded-2xl bg-rz-accent-fill py-[13px] text-[13px] font-semibold text-white shadow-[0_5px_14px_-8px_rgba(20,45,95,.3)]"
                    >
                        {t('business.wallet.deposit.button')}
                    </Link>
                ) : (
                    <DepositPanel<'business.wallet.deposit'>
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
                <DepositIntents deposits={props.deposits} />
                <PollStopped {...poll} className="mt-2" />
                <WalletHistory history={props.history} />
                {receipt !== null &&
                    (isIntent(receipt) ? (
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
