import { DirectionTile } from '@/components/business/wallet/transaction-row';
import { ColumnSheet } from '@/components/rozine/column-sheet';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { WalletTransaction } from '@/types/business';

const STATUS_TONE = {
    completed: { text: 'text-rz-accent-app-text', dot: 'bg-rz-accent-fill' },
    pending: { text: 'text-rz-warning-text', dot: 'bg-[#c2661f]' },
    failed: { text: 'text-rz-danger-text', dot: 'bg-rz-danger' },
} as const;

type TransactionSheetProps = {
    transaction: WalletTransaction;
    onClose: () => void;
};

/** Transaction detail (design L2378–2413): the receipt to inspect before retrying anything. */
export function TransactionSheet({
    transaction,
    onClose,
}: TransactionSheetProps) {
    const { t, locale } = useTranslation();
    const inbound = transaction.direction === 'in';
    const sign = inbound ? '+' : '-';
    const tone = STATUS_TONE[transaction.status];
    const amountTone = inbound ? 'text-rz-accent-app-text' : 'text-rz-slate';
    const rows: { label: string; value: string; strong?: boolean }[] = [
        {
            label: t('business.wallet.detail.when'),
            value: formatDateTime(transaction.occurred_at, locale),
        },
        { label: t('business.wallet.detail.id'), value: transaction.id },
        {
            label: t('business.wallet.detail.reference'),
            value: transaction.reference,
        },
        {
            label: t('business.wallet.detail.before'),
            value: formatRwf(transaction.balance_before),
        },
        {
            label: t('business.wallet.detail.after'),
            value: formatRwf(transaction.balance_after),
            strong: true,
        },
    ];

    if (transaction.charges !== null) {
        rows.push(
            {
                label: t('business.wallet.detail.gross'),
                value: formatRwf(transaction.charges.gross),
            },
            {
                label: t('business.wallet.detail.fee'),
                value: formatRwf(transaction.charges.fee),
            },
            {
                label: t('business.wallet.detail.net'),
                value: formatRwf(transaction.charges.net),
                strong: true,
            },
        );
    }

    return (
        <ColumnSheet
            label={t(`business.wallet.kind.${transaction.kind}`)}
            close={onClose}
            fraction={0.72}
        >
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto">
                <div className="flex items-center gap-[11px] border-b border-[#eef2f9] px-4 pt-3.5 pb-3 dark:border-rz-divider">
                    <DirectionTile direction={transaction.direction} large />
                    <div className="min-w-0 flex-1">
                        <h2 className="text-sm font-semibold text-rz-ink">
                            {t(`business.wallet.kind.${transaction.kind}`)}
                        </h2>
                        <p className="mt-px text-[11px] text-rz-secondary">
                            {transaction.via}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label={t('app.sheet.close')}
                        className="flex size-7 shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-[#f3f6fc] text-sm text-rz-secondary dark:bg-rz-page"
                    >
                        <span aria-hidden>✕</span>
                    </button>
                </div>
                <div className="px-4 pt-3.5 pb-1 text-center">
                    <p className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
                        {t('business.wallet.amount')}
                    </p>
                    <p
                        className={cn(
                            'mt-[3px] text-2xl font-bold',
                            amountTone,
                        )}
                    >
                        {sign}
                        {formatRwf(transaction.amount)}
                    </p>
                </div>
                <dl className="px-4 pt-1.5 pb-0.5">
                    <div className="flex items-center justify-between border-b border-[#f1f4f9] py-2 dark:border-rz-divider">
                        <dt className="text-xs text-rz-secondary">
                            {t('business.wallet.detail.status')}
                        </dt>
                        <dd
                            className={cn(
                                'inline-flex items-center gap-1.5 text-xs font-semibold',
                                tone.text,
                            )}
                        >
                            <span
                                className={cn(
                                    'size-1.5 rounded-full',
                                    tone.dot,
                                )}
                            />
                            {t(
                                `business.wallet.tx_status.${transaction.status}`,
                            )}
                        </dd>
                    </div>
                    {transaction.failure_reason !== null && (
                        <div className="flex items-center justify-between gap-3 border-b border-[#f1f4f9] py-2 dark:border-rz-divider">
                            <dt className="text-xs text-rz-secondary">
                                {t('business.wallet.detail.reason')}
                            </dt>
                            <dd className="text-right text-xs font-semibold text-rz-danger-text">
                                {transaction.failure_reason}
                            </dd>
                        </div>
                    )}
                    <div className="flex items-center justify-between border-b border-[#f1f4f9] py-2 dark:border-rz-divider">
                        <dt className="text-xs text-rz-secondary">
                            {t('business.wallet.detail.type')}
                        </dt>
                        <dd className={cn('text-xs font-bold', amountTone)}>
                            {t(
                                inbound
                                    ? 'business.wallet.detail.credit'
                                    : 'business.wallet.detail.debit',
                            )}
                        </dd>
                    </div>
                    {rows.map((row) => (
                        <div
                            key={row.label}
                            className="flex items-center justify-between border-b border-[#f1f4f9] py-2 last:border-b-0 dark:border-rz-divider"
                        >
                            <dt className="text-xs text-rz-secondary">
                                {row.label}
                            </dt>
                            <dd
                                className={cn(
                                    'text-xs text-rz-ink',
                                    row.strong ? 'font-bold' : 'font-semibold',
                                )}
                            >
                                {row.value}
                            </dd>
                        </div>
                    ))}
                </dl>
                <div className="px-4 pt-2.5 pb-5">
                    <button
                        type="button"
                        onClick={onClose}
                        className="h-[46px] w-full rounded-xl bg-rz-accent-fill text-sm font-semibold text-white"
                    >
                        {t('business.wallet.detail.done')}
                    </button>
                </div>
            </div>
        </ColumnSheet>
    );
}
