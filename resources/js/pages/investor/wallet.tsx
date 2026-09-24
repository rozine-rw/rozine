import { Link, router } from '@inertiajs/react';
import { InvestorShell } from '@/components/investor/investor-shell';
import {
    BalanceCard,
    FundingPanel,
    FundingSwitch,
} from '@/components/investor/wallet/funding';
import {
    EarningsCard,
    ReceiptSheet,
    Transactions,
} from '@/components/investor/wallet/history';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import type { InvestorWalletProps } from '@/types/investor';

/**
 * Wallet (MVP-INVESTOR-SCR-08, design L2835–3095) and its receipts (L4637–4703): the available
 * balance, deposit and withdrawal through linked accounts, what came back (earnings) and every
 * money movement with its status. A phone shows one column; a wide screen shows balance and the
 * open panel beside the history, with receipts sliding up inside that column. The design's Rozine
 * Plus upsell is outside the MVP.
 */
export default function InvestorWallet(props: InvestorWalletProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const kind = props.funding.kind ?? (wide ? 'deposit' : null);
    const closeReceipt = () =>
        router.visit(props.links.close, { preserveScroll: true });

    const left = (
        <>
            <BalanceCard wallet={props.wallet} />
            <FundingSwitch kind={kind} links={props.links} />
            {kind !== null && (
                <FundingPanel
                    key={kind}
                    kind={kind}
                    funding={props.funding}
                    actions={props.actions}
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
        </>
    );

    const receipt =
        props.receipt !== null ? (
            <ReceiptSheet receipt={props.receipt} close={closeReceipt} />
        ) : null;

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
                                <EarningsCard earnings={props.earnings} wide />
                                <Transactions
                                    transactions={props.transactions}
                                    wide
                                />
                                {receipt}
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
                    <EarningsCard earnings={props.earnings} wide={false} />
                    <Transactions
                        transactions={props.transactions}
                        wide={false}
                    />
                    {receipt}
                </div>
            )}
        </InvestorShell>
    );
}
