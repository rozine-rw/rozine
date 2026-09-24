import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { BalanceCard } from '@/components/business/wallet/balance-card';
import { FlowPanel } from '@/components/business/wallet/flow-panel';
import { TransactionHistory } from '@/components/business/wallet/transaction-history';
import { TransactionSheet } from '@/components/business/wallet/transaction-sheet';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type {
    BusinessWalletProps,
    WalletFlow,
    WalletTransaction,
} from '@/types/business';

/** The Deposit and Withdraw buttons' tray arrows (design L1336–1345). */
function Arrow({ flow, className }: { flow: WalletFlow; className: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden
            className="size-[15px]"
        >
            <path
                d={
                    flow === 'deposit'
                        ? 'M12 4v10M8 11l4 4 4-4M5 20h14'
                        : 'M12 20V10M8 13l4-4 4 4M5 4h14'
                }
                stroke="currentColor"
                className={className}
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

/**
 * Wallet (MVP-BUSINESS-SCR-08, design L1315–1457), reached from Home's wallet pill. It is not a
 * tab, so it renders full width in the main pane, as the design's desktop does.
 */
export default function BusinessWallet({
    wallet,
    methods,
    quick_amounts,
    quote,
    today,
    transactions,
    links,
    actions,
}: BusinessWalletProps) {
    const { t } = useTranslation();
    const [flow, setFlow] = useState<WalletFlow | null>(null);
    const [open, setOpen] = useState<WalletTransaction | null>(null);

    return (
        <BusinessShell
            title={t('business.wallet.title')}
            tab="home"
            links={links}
            showTabBar={false}
        >
            <div
                data-rzcol
                className="relative px-4 pt-[calc(env(safe-area-inset-top)+2px)] pb-10 lg:h-full lg:overflow-y-auto lg:pt-[52px]"
            >
                <div className="flex items-center gap-[11px]">
                    <Link
                        href={links.back}
                        aria-label={t('business.wallet.back')}
                        className="flex size-[34px] items-center justify-center rounded-xl border border-rz-border bg-rz-surface text-base text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <h1 className="text-[17px] font-semibold text-rz-ink">
                        {t('business.wallet.title')}
                    </h1>
                </div>
                <BalanceCard wallet={wallet} />
                <div className="mt-3.5 grid grid-cols-2 gap-[11px]">
                    {(['deposit', 'withdraw'] as const).map((key) => (
                        <button
                            key={`${key}-${flow === key}`}
                            type="button"
                            aria-pressed={flow === key}
                            onClick={() => setFlow(flow === key ? null : key)}
                            className={cn(
                                'relative flex items-center justify-center gap-[7px] rounded-2xl border border-rz-border bg-rz-surface py-[13px] text-[13px] font-semibold text-rz-ink shadow-[0_5px_14px_-8px_rgba(20,45,95,.3)]',
                                flow === key && 'border-transparent text-white',
                            )}
                        >
                            {flow === key && (
                                <span
                                    aria-hidden
                                    className={cn(
                                        'absolute inset-0 animate-[rz-pillin_.32s_cubic-bezier(.34,1.2,.5,1)_both] rounded-2xl',
                                        key === 'deposit'
                                            ? 'bg-rz-accent-fill'
                                            : 'bg-[#7d420f]',
                                    )}
                                />
                            )}
                            <span className="relative flex items-center gap-[7px]">
                                <Arrow
                                    flow={key}
                                    className={
                                        flow === key
                                            ? 'text-white'
                                            : key === 'deposit'
                                              ? 'text-rz-accent-app-text'
                                              : 'text-[#b05c1a] dark:text-[#f0a060]'
                                    }
                                />
                                {t(`business.wallet.${key}.button`)}
                            </span>
                        </button>
                    ))}
                </div>
                {flow !== null && (
                    <FlowPanel
                        key={flow}
                        flow={flow}
                        methods={methods[flow]}
                        quickAmounts={quick_amounts}
                        quote={quote}
                        action={actions[flow]}
                        onClose={() => setFlow(null)}
                    />
                )}
                <TransactionHistory
                    transactions={transactions}
                    today={today}
                    links={links}
                    onOpen={setOpen}
                />
                {open !== null && (
                    <TransactionSheet
                        transaction={open}
                        onClose={() => setOpen(null)}
                    />
                )}
            </div>
        </BusinessShell>
    );
}
