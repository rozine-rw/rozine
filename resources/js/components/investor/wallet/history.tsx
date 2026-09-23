import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { LocalSheet, SheetClose } from '@/components/investor/local-sheet';
import { POSITIVE_TEXT } from '@/components/investor/tokens';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatCompact,
    formatSigned,
    intlTag,
    isNegative,
} from '@/lib/investor/format';
import { formatDate, formatMonthYear, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    InvestorWalletProps,
    TransactionItem,
    TransactionKind,
    TransactionReceipt,
    TransactionStatus,
} from '@/types/investor';

/**
 * Earnings history (design L2936–2980): interest and principal that came back between two dates.
 * The range chips are the server's; editing a date re-reads the history for it.
 */
export function EarningsCard({
    earnings,
    wide,
}: {
    earnings: InvestorWalletProps['earnings'];
    wide: boolean;
}) {
    const { t, locale } = useTranslation();
    const reread = (from: string, to: string) =>
        router.reload({ only: ['earnings'], data: { from, to } });
    const date =
        'box-border h-8 rounded-[10px] border border-rz-border bg-rz-surface px-2 text-[11px] font-semibold text-rz-ink outline-none';
    const totals = [
        [
            'investor.wallet.earn.interest',
            formatCompact(earnings.totals.interest),
            POSITIVE_TEXT,
        ],
        [
            'investor.wallet.earn.received',
            formatCompact(earnings.totals.received),
            'text-rz-ink',
        ],
        [
            'investor.wallet.earn.payouts',
            String(earnings.totals.payouts),
            'text-rz-ink',
        ],
        [
            'investor.wallet.earn.avg',
            formatCompact(earnings.totals.avg_monthly),
            'text-rz-ink',
        ],
    ] as const;

    return (
        <section
            aria-label={t('investor.wallet.earn.title')}
            className={cn(
                'rounded-2xl border border-rz-border bg-rz-surface px-4 py-[15px]',
                !wide && 'mt-5',
            )}
        >
            <div className="flex flex-wrap items-end justify-between gap-2.5">
                <div>
                    <h2 className="text-sm font-semibold text-rz-ink">
                        {t('investor.wallet.earn.title')}
                    </h2>
                    <p className="mt-[3px] text-[11.5px] text-rz-secondary">
                        {t('investor.wallet.earn.subtitle')}
                    </p>
                </div>
                <div className="flex items-center gap-1.5">
                    <input
                        type="date"
                        aria-label={t('investor.wallet.earn.from')}
                        defaultValue={earnings.from.slice(0, 10)}
                        onChange={(event) =>
                            reread(event.target.value, earnings.to.slice(0, 10))
                        }
                        className={date}
                    />
                    <span className="text-[11px] text-rz-secondary">
                        {t('investor.wallet.earn.to')}
                    </span>
                    <input
                        type="date"
                        aria-label={t('investor.wallet.earn.to_date')}
                        defaultValue={earnings.to.slice(0, 10)}
                        onChange={(event) =>
                            reread(
                                earnings.from.slice(0, 10),
                                event.target.value,
                            )
                        }
                        className={date}
                    />
                </div>
            </div>
            <nav
                aria-label={t('investor.wallet.earn.ranges')}
                className="mt-[11px] flex flex-wrap gap-[7px]"
            >
                {earnings.ranges.map((range) => (
                    <Link
                        key={range.key}
                        href={range.link}
                        preserveScroll
                        aria-current={range.active ? 'true' : undefined}
                        className={cn(
                            'rounded-[10px] border px-[11px] py-1.5 text-[11.5px] font-semibold',
                            range.active
                                ? 'border-rz-accent-fill bg-rz-accent-fill text-white'
                                : 'border-rz-border bg-rz-surface text-rz-slate',
                        )}
                    >
                        {t(`investor.wallet.earn.range.${range.key}`)}
                    </Link>
                ))}
            </nav>
            <div className="mt-3 grid grid-cols-2 gap-[9px]">
                {totals.map(([label, value, tone]) => (
                    <div
                        key={label}
                        className="min-w-0 rounded-xl border border-[#eef2f9] bg-[#f7f9fd] px-[11px] py-2.5 dark:border-rz-divider dark:bg-rz-surface-sunken"
                    >
                        <p className="truncate text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                            {t(label)}
                        </p>
                        <p
                            className={cn(
                                'mt-[3px] truncate text-sm font-bold',
                                tone,
                            )}
                        >
                            {value}
                        </p>
                    </div>
                ))}
            </div>
            <table className="mt-3 w-full overflow-hidden rounded-xl border border-[#eef2f9] text-left whitespace-nowrap dark:border-rz-divider">
                <thead className="bg-[#f7f9fd] dark:bg-rz-surface-sunken">
                    <tr className="border-b border-[#eef2f9] text-[10.5px] font-bold text-rz-slate uppercase dark:border-rz-divider">
                        <th className="px-[11px] py-[9px]">
                            {t('investor.wallet.earn.month')}
                        </th>
                        <th className="py-[9px]">
                            {t('investor.wallet.earn.paid')}
                        </th>
                        <th className="py-[9px] text-right">
                            {t('investor.wallet.earn.interest')}
                        </th>
                        <th className="px-[11px] py-[9px] text-right">
                            {t('investor.wallet.earn.total')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {earnings.months.map((row) => (
                        <tr
                            key={row.month}
                            className="border-b border-[#f4f7fb] last:border-0 dark:border-rz-divider"
                        >
                            <td className="truncate px-[11px] py-2.5 text-[11.5px] font-semibold text-rz-ink">
                                {formatMonthYear(row.month, locale)}
                            </td>
                            <td className="py-2.5 text-[11.5px] text-rz-secondary">
                                {row.payouts}
                            </td>
                            <td
                                className={cn(
                                    'truncate py-2.5 text-right text-[11.5px] font-semibold',
                                    POSITIVE_TEXT,
                                )}
                            >
                                +{formatCompact(row.interest)}
                            </td>
                            <td className="truncate px-[11px] py-2.5 text-right text-[11.5px] font-bold text-rz-ink">
                                {formatCompact(row.total)}
                            </td>
                        </tr>
                    ))}
                    {earnings.months.length === 0 && (
                        <tr>
                            <td
                                colSpan={4}
                                className="px-[11px] py-5 text-center text-[11.5px] text-rz-secondary"
                            >
                                {t('investor.wallet.earn.empty')}
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </section>
    );
}

const KIND_ICON: Record<
    TransactionKind,
    { icon: IconName; tint: string; tone: 'green' | 'amber' | 'blue' | 'red' }
> = {
    deposit: {
        icon: 'money-out',
        tint: 'bg-[rgba(29,158,117,.10)]',
        tone: 'green',
    },
    withdrawal: {
        icon: 'money-out',
        tint: 'bg-[rgba(194,102,31,.10)]',
        tone: 'amber',
    },
    investment: { icon: 'note', tint: 'bg-rz-accent-soft', tone: 'blue' },
    payout: {
        icon: 'repeat',
        tint: 'bg-[rgba(29,158,117,.10)]',
        tone: 'green',
    },
    refund: { icon: 'undo', tint: 'bg-rz-accent-soft', tone: 'blue' },
    fee: { icon: 'receipt', tint: 'bg-[rgba(229,72,77,.10)]', tone: 'red' },
};

const STATUS_TEXT: Record<TransactionStatus, string> = {
    completed: POSITIVE_TEXT,
    pending: 'text-[#a55418] dark:text-[#f0a060]',
    failed: 'text-rz-danger-text',
};

function KindTile({
    kind,
    large = false,
}: {
    kind: TransactionKind;
    large?: boolean;
}) {
    const spec = KIND_ICON[kind];

    return (
        <span
            className={cn(
                'flex shrink-0 items-center justify-center rounded-[10px]',
                large ? 'size-10 text-[19px]' : 'size-8 text-[15px]',
                spec.tint,
            )}
        >
            <Icon name={spec.icon} tone={spec.tone} />
        </span>
    );
}

function amountTone(item: TransactionItem): string {
    if (item.status === 'failed') {
        return 'text-rz-secondary line-through';
    }

    return isNegative(item.amount) ? 'text-rz-slate' : POSITIVE_TEXT;
}

/**
 * Transaction history (design L2981–3095): the server's range chips and exports, then each money
 * movement with its status — pending and failed transfers show as such, never as settled (crosswalk
 * SCR-08-ST-02/03). The design's free date-range popover is folded into the chips.
 */
export function Transactions({
    transactions,
    wide,
}: {
    transactions: InvestorWalletProps['transactions'];
    wide: boolean;
}) {
    const { t, locale } = useTranslation();
    const [menu, setMenu] = useState(false);
    const [all, setAll] = useState(false);
    const first = wide ? transactions.items.length : 3;
    const shown = all ? transactions.items : transactions.items.slice(0, first);

    return (
        <section
            aria-label={t('investor.wallet.tx.title')}
            className={cn(!wide && 'mt-5')}
        >
            <div
                className={cn(
                    'flex items-center justify-between gap-2',
                    wide && 'mt-3.5',
                )}
            >
                <h2 className="text-sm font-semibold text-rz-ink">
                    {t(
                        wide
                            ? 'investor.wallet.tx.title_short'
                            : 'investor.wallet.tx.title',
                    )}
                </h2>
                <div className="relative">
                    <button
                        type="button"
                        aria-label={t('investor.wallet.tx.export')}
                        aria-expanded={menu}
                        onClick={() => setMenu(!menu)}
                        className="flex size-[30px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-rz-slate"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className="size-3.5"
                        >
                            <path
                                d="M12 3v12M8 11l4 4 4-4M5 20h14"
                                stroke="currentColor"
                                strokeWidth="1.9"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                    </button>
                    {menu && (
                        <div className="absolute top-[37px] right-0 z-40 w-[168px] animate-[rz-fade_.18s_ease] rounded-xl border border-rz-border bg-rz-surface p-[5px] shadow-[0_20px_44px_-16px_rgba(20,45,95,.34)]">
                            {(
                                [
                                    [
                                        'pdf',
                                        'investor.wallet.tx.pdf',
                                        'bg-[rgba(229,72,77,.10)]',
                                        'red',
                                    ],
                                    [
                                        'csv',
                                        'investor.wallet.tx.csv',
                                        'bg-[rgba(29,158,117,.10)]',
                                        'green',
                                    ],
                                ] as const
                            ).map(([key, label, tint, tone]) => (
                                <a
                                    key={key}
                                    href={transactions.exports[key].url}
                                    className="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-[9px] text-left"
                                >
                                    <span
                                        className={cn(
                                            'flex size-[26px] items-center justify-center rounded-[10px] text-sm',
                                            tint,
                                        )}
                                    >
                                        <Icon name="document" tone={tone} />
                                    </span>
                                    <span className="text-[12.5px] font-semibold text-rz-ink">
                                        {t(label)}
                                    </span>
                                </a>
                            ))}
                        </div>
                    )}
                </div>
            </div>
            <nav
                aria-label={t('investor.wallet.tx.ranges')}
                className="rz-hscroll mt-[11px] flex shrink-0 gap-[7px] overflow-x-auto"
            >
                {transactions.ranges.map((range) => (
                    <Link
                        key={range.key}
                        href={range.link}
                        preserveScroll
                        aria-current={range.active ? 'true' : undefined}
                        className={cn(
                            'shrink-0 rounded-[10px] border px-[13px] py-[7px] text-xs font-semibold whitespace-nowrap',
                            range.active
                                ? 'border-[#d6e4ff] bg-rz-accent-fill text-white dark:border-rz-investor'
                                : 'border-rz-border bg-rz-surface text-rz-slate',
                        )}
                    >
                        {t(`investor.wallet.tx.range.${range.key}`)}
                    </Link>
                ))}
            </nav>
            <ul className="mt-2.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {shown.map((item) => (
                    <li
                        key={item.id}
                        className="border-b border-[#eef2f9] last:border-0 dark:border-rz-divider"
                    >
                        <Link
                            href={item.link}
                            preserveScroll
                            className="flex w-full items-center gap-[11px] px-[13px] py-3 text-left"
                        >
                            <KindTile kind={item.kind} />
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-[12.5px] font-semibold text-rz-ink">
                                    {t(`investor.wallet.tx.kind.${item.kind}`, {
                                        counterparty: item.counterparty,
                                    })}
                                </span>
                                <span className="mt-px block text-[11px] text-rz-secondary">
                                    {formatDate(item.occurred_at, locale)}
                                    {item.status !== 'completed' && (
                                        <span
                                            className={cn(
                                                'ml-1.5 font-semibold',
                                                STATUS_TEXT[item.status],
                                            )}
                                        >
                                            ·{' '}
                                            {t(
                                                `investor.wallet.tx.status.${item.status}`,
                                            )}
                                        </span>
                                    )}
                                </span>
                            </span>
                            <span
                                className={cn(
                                    'text-[12.5px] font-semibold whitespace-nowrap',
                                    amountTone(item),
                                )}
                            >
                                {formatSigned(item.amount)}
                            </span>
                        </Link>
                    </li>
                ))}
                {transactions.items.length === 0 && (
                    <li className="px-4 py-[26px] text-center text-[12.5px] text-rz-secondary">
                        {t('investor.wallet.tx.empty')}
                    </li>
                )}
                {transactions.items.length > first && (
                    <li>
                        <button
                            type="button"
                            onClick={() => setAll(!all)}
                            className="w-full bg-[#fafbfd] py-3 text-[12.5px] font-semibold text-rz-accent-app-text dark:bg-rz-surface-sunken"
                        >
                            {all
                                ? t('investor.updates.show_less')
                                : t('investor.updates.show_more')}
                        </button>
                    </li>
                )}
            </ul>
        </section>
    );
}

/**
 * A transaction receipt (design L4637–4703): what moved, its status, time, identifiers and the
 * wallet balance either side; withdrawals add gross, fee and net, and payouts the principal, return
 * and investor fee they carried (crosswalk AC-09, every earning traces to a repayment).
 */
export function ReceiptSheet({
    receipt,
    close,
}: {
    receipt: TransactionReceipt;
    close: () => void;
}) {
    const { t, locale } = useTranslation();
    const rows: [string, string, string?][] = [
        [
            t('investor.wallet.receipt.type'),
            t(
                isNegative(receipt.amount)
                    ? 'investor.wallet.receipt.debit'
                    : 'investor.wallet.receipt.credit',
            ),
        ],
        [
            t('investor.wallet.receipt.when'),
            new Intl.DateTimeFormat(intlTag(locale), {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                timeZone: 'Africa/Kigali',
            }).format(new Date(receipt.occurred_at)),
        ],
        [t('investor.wallet.receipt.transaction_id'), receipt.transaction_id],
        [t('investor.wallet.receipt.reference'), receipt.reference],
        [
            t('investor.wallet.receipt.before'),
            formatRwf(receipt.balance_before),
        ],
        [
            t('investor.wallet.receipt.after'),
            formatRwf(receipt.balance_after),
            'font-bold',
        ],
    ];

    if (receipt.breakdown !== null) {
        rows.push(
            [
                t('investor.wallet.receipt.gross'),
                formatRwf(receipt.breakdown.gross),
            ],
            [
                t('investor.wallet.receipt.fee'),
                formatRwf(receipt.breakdown.fee),
            ],
            [
                t('investor.wallet.receipt.net'),
                formatRwf(receipt.breakdown.net),
                'font-bold',
            ],
        );
    }

    if (receipt.payout !== null) {
        rows.push(
            [
                t('investor.wallet.receipt.principal'),
                formatRwf(receipt.payout.principal),
            ],
            [
                t('investor.wallet.receipt.return'),
                formatRwf(receipt.payout.return),
            ],
            [
                t('investor.wallet.receipt.payout_fee'),
                formatRwf(receipt.payout.fee),
            ],
        );
    }

    return (
        <LocalSheet
            label={t('investor.wallet.receipt.label')}
            onClose={close}
            maxHeight="lg:max-h-[88%]"
            className="lg:rounded-2xl"
        >
            <div className="flex shrink-0 items-center gap-[11px] border-b border-[#eef2f9] px-4 pt-3.5 pb-3 dark:border-rz-divider">
                <KindTile kind={receipt.kind} large />
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-rz-ink">
                        {t(`investor.wallet.receipt.kind.${receipt.kind}`)}
                    </p>
                    <p className="mt-px text-[11px] text-rz-secondary">
                        {receipt.counterparty}
                    </p>
                </div>
                <SheetClose onClose={close} />
            </div>
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto">
                <div className="px-4 pt-3.5 pb-1 text-center">
                    <p className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
                        {t('investor.wallet.receipt.amount')}
                    </p>
                    <p
                        className={cn(
                            'mt-[3px] text-2xl font-bold',
                            amountTone(receipt),
                        )}
                    >
                        {formatSigned(receipt.amount)}
                    </p>
                </div>
                <dl className="px-4 pt-1.5 pb-0.5">
                    <div className="flex items-center justify-between border-b border-[#f1f4f9] py-2 dark:border-rz-divider">
                        <dt className="text-xs text-rz-secondary">
                            {t('investor.wallet.receipt.status')}
                        </dt>
                        <dd
                            className={cn(
                                'inline-flex items-center gap-1.5 text-xs font-semibold',
                                STATUS_TEXT[receipt.status],
                            )}
                        >
                            <span className="size-1.5 rounded-full bg-current" />
                            {t(`investor.wallet.tx.status.${receipt.status}`)}
                        </dd>
                    </div>
                    {rows.map(([label, value, weight], index) => (
                        <div
                            key={label}
                            className={cn(
                                'flex items-center justify-between gap-3 py-2',
                                index < rows.length - 1 &&
                                    'border-b border-[#f1f4f9] dark:border-rz-divider',
                            )}
                        >
                            <dt className="text-xs text-rz-secondary">
                                {label}
                            </dt>
                            <dd
                                className={cn(
                                    'text-right text-xs text-rz-ink',
                                    weight ?? 'font-semibold',
                                )}
                            >
                                {value}
                            </dd>
                        </div>
                    ))}
                </dl>
            </div>
            <div className="shrink-0 px-4 pt-2.5 pb-[calc(env(safe-area-inset-bottom)+14px)] lg:pb-3.5">
                <button
                    type="button"
                    onClick={close}
                    className="h-[46px] w-full rounded-xl bg-rz-accent-fill text-sm font-semibold text-white"
                >
                    {t('investor.wallet.receipt.done')}
                </button>
            </div>
        </LocalSheet>
    );
}
