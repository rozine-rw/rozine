import { router } from '@inertiajs/react';
import { useState } from 'react';
import { DirectionTile } from '@/components/business/wallet/transaction-row';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDayMonth, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessWalletProps, WalletTransaction } from '@/types/business';

const SHOWN = 3;

/** An ISO date `days` before `today`, for the quick ranges. */
const daysBefore = (today: string, days: number): string => {
    const date = new Date(`${today}T00:00:00Z`);

    date.setUTCDate(date.getUTCDate() - days);

    return date.toISOString().slice(0, 10);
};

type TransactionHistoryProps = Pick<
    BusinessWalletProps,
    'transactions' | 'today' | 'links'
> & {
    onOpen: (transaction: WalletTransaction) => void;
};

/**
 * "Transaction history" (design L1388–1455). The date range and the exports are the server's:
 * applying a range asks for that slice, and the PDF and Excel files are built from the same one.
 */
export function TransactionHistory({
    transactions,
    today,
    links,
    onOpen,
}: TransactionHistoryProps) {
    const { t, locale } = useTranslation();
    const [panel, setPanel] = useState<'range' | 'export' | null>(null);
    const [from, setFrom] = useState(transactions.from ?? '');
    const [to, setTo] = useState(transactions.to ?? '');
    const [expanded, setExpanded] = useState(false);
    const filtered = transactions.from !== null || transactions.to !== null;
    const items = expanded
        ? transactions.items
        : transactions.items.slice(0, SHOWN);

    const load = (range: { from?: string; to?: string }) => {
        setPanel(null);
        router.get(links.wallet.url, range, {
            only: ['transactions'],
            preserveState: true,
            preserveScroll: true,
        });
    };

    const rangeLabel = filtered
        ? t('business.wallet.range.span', {
              from:
                  transactions.from === null
                      ? t('business.wallet.range.start')
                      : formatDayMonth(transactions.from, locale),
              to:
                  transactions.to === null
                      ? t('business.wallet.range.now')
                      : formatDayMonth(transactions.to, locale),
          })
        : t('business.wallet.range.all');

    return (
        <section aria-labelledby="wallet-history" className="mt-5">
            <div className="flex items-center justify-between gap-2">
                <h2
                    id="wallet-history"
                    className="text-sm font-semibold text-rz-ink"
                >
                    {t('business.wallet.history')}
                </h2>
                <div className="flex items-center gap-[7px]">
                    <div className="relative">
                        <button
                            type="button"
                            aria-expanded={panel === 'range'}
                            aria-label={t('business.wallet.range.label', {
                                range: rangeLabel,
                            })}
                            onClick={() =>
                                setPanel(panel === 'range' ? null : 'range')
                            }
                            className={cn(
                                'flex h-[30px] items-center gap-[5px] rounded-[10px] border bg-rz-surface px-[9px] text-[11px] font-semibold text-rz-slate',
                                filtered
                                    ? 'border-[#1e3aff] dark:border-[#3d57ff]'
                                    : 'border-rz-border',
                            )}
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden
                                className="size-[13px]"
                            >
                                <rect
                                    x="3.5"
                                    y="5"
                                    width="17"
                                    height="16"
                                    rx="2.5"
                                    stroke="currentColor"
                                    className="text-rz-secondary"
                                    strokeWidth="1.8"
                                />
                                <path
                                    d="M3.5 9.5h17M8 3.5v3M16 3.5v3"
                                    stroke="currentColor"
                                    className="text-rz-secondary"
                                    strokeWidth="1.8"
                                />
                            </svg>
                            <span className="whitespace-nowrap">
                                {rangeLabel}
                            </span>
                            <svg
                                viewBox="0 0 10 6"
                                fill="none"
                                aria-hidden
                                className="h-1.5 w-2"
                            >
                                <path
                                    d="M1 1l4 4 4-4"
                                    stroke="currentColor"
                                    className="text-rz-secondary"
                                    strokeWidth="1.6"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                            </svg>
                        </button>
                        {panel === 'range' && (
                            <div className="absolute top-[37px] right-0 z-40 w-[232px] animate-[rz-fade_.18s_ease] rounded-2xl border border-rz-border bg-rz-surface p-[13px] shadow-[0_20px_44px_-16px_rgba(20,45,95,.34)]">
                                <p className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
                                    {t('business.wallet.range.quick')}
                                </p>
                                <div className="mt-2 flex gap-1.5">
                                    {[7, 30, 90].map((days) => (
                                        <button
                                            key={days}
                                            type="button"
                                            onClick={() =>
                                                load({
                                                    from: daysBefore(
                                                        today,
                                                        days,
                                                    ),
                                                    to: today,
                                                })
                                            }
                                            className="flex-1 rounded-[10px] border border-rz-border bg-[#f3f6fc] py-[7px] text-[11px] font-semibold text-rz-slate dark:bg-rz-page"
                                        >
                                            {t('business.wallet.range.days', {
                                                count: days,
                                            })}
                                        </button>
                                    ))}
                                </div>
                                <label
                                    htmlFor="wallet-from"
                                    className="mt-3 block text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase"
                                >
                                    {t('business.wallet.range.from')}
                                </label>
                                <input
                                    id="wallet-from"
                                    type="date"
                                    value={from}
                                    onChange={(event) =>
                                        setFrom(event.target.value)
                                    }
                                    className="mt-[5px] w-full rounded-[10px] border border-rz-border bg-[#f8fafc] px-2.5 py-[9px] text-xs text-rz-ink outline-none dark:bg-rz-page"
                                />
                                <label
                                    htmlFor="wallet-to"
                                    className="mt-2.5 block text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase"
                                >
                                    {t('business.wallet.range.to')}
                                </label>
                                <input
                                    id="wallet-to"
                                    type="date"
                                    value={to}
                                    onChange={(event) =>
                                        setTo(event.target.value)
                                    }
                                    className="mt-[5px] w-full rounded-[10px] border border-rz-border bg-[#f8fafc] px-2.5 py-[9px] text-xs text-rz-ink outline-none dark:bg-rz-page"
                                />
                                <div className="mt-[13px] flex gap-2">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setFrom('');
                                            setTo('');
                                            load({});
                                        }}
                                        className="flex-1 rounded-[10px] border border-rz-border bg-rz-surface py-[9px] text-xs font-semibold text-rz-secondary"
                                    >
                                        {t('business.wallet.range.clear')}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            load({
                                                ...(from !== '' && { from }),
                                                ...(to !== '' && { to }),
                                            })
                                        }
                                        className="flex-1 rounded-[10px] bg-rz-accent-fill py-[9px] text-xs font-semibold text-white"
                                    >
                                        {t('business.wallet.range.apply')}
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                    <div className="relative">
                        <button
                            type="button"
                            aria-expanded={panel === 'export'}
                            aria-label={t('business.wallet.export')}
                            title={t('business.wallet.export')}
                            onClick={() =>
                                setPanel(panel === 'export' ? null : 'export')
                            }
                            className="flex size-[30px] items-center justify-center rounded-xl border border-rz-border bg-rz-surface"
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
                                    className="text-rz-slate"
                                    strokeWidth="1.9"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                            </svg>
                        </button>
                        {panel === 'export' && (
                            <div className="absolute top-[37px] right-0 z-40 w-[168px] animate-[rz-fade_.18s_ease] rounded-xl border border-rz-border bg-rz-surface p-[5px] shadow-[0_20px_44px_-16px_rgba(20,45,95,.34)]">
                                {(['pdf', 'csv'] as const).map((format) => (
                                    <a
                                        key={format}
                                        href={
                                            (format === 'pdf'
                                                ? links.export_pdf
                                                : links.export_csv
                                            ).url
                                        }
                                        download
                                        onClick={() => setPanel(null)}
                                        className="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-[9px] text-left"
                                    >
                                        <span
                                            className={cn(
                                                'flex size-[26px] items-center justify-center rounded-[10px]',
                                                format === 'pdf'
                                                    ? 'bg-[rgba(229,72,77,.10)]'
                                                    : 'bg-rz-accent-soft',
                                            )}
                                        >
                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                aria-hidden
                                                className="size-3.5"
                                            >
                                                <path
                                                    d="M6 2.5h8l4 4V21a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 6 21V2.5Z M14 2.5v4h4"
                                                    stroke={
                                                        format === 'pdf'
                                                            ? '#b3383c'
                                                            : '#17795a'
                                                    }
                                                    strokeWidth="1.7"
                                                    strokeLinejoin="round"
                                                />
                                                <path
                                                    d={
                                                        format === 'pdf'
                                                            ? 'M8.5 15.5h7M8.5 18h4'
                                                            : 'M9 12l2.5 3.5M14 12l-2.5 3.5'
                                                    }
                                                    stroke={
                                                        format === 'pdf'
                                                            ? '#b3383c'
                                                            : '#17795a'
                                                    }
                                                    strokeWidth="1.6"
                                                    strokeLinecap="round"
                                                />
                                            </svg>
                                        </span>
                                        <span className="text-[12.5px] font-semibold text-rz-ink">
                                            {t(
                                                `business.wallet.download_${format}`,
                                            )}
                                        </span>
                                    </a>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
            <div className="mt-2.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                <ul>
                    {items.map((transaction) => {
                        const inbound = transaction.direction === 'in';

                        return (
                            <li key={transaction.id}>
                                <button
                                    type="button"
                                    onClick={() => onOpen(transaction)}
                                    className="flex w-full items-center gap-[11px] border-b border-[#eef2f9] px-[13px] py-3 text-left dark:border-rz-divider"
                                >
                                    <DirectionTile
                                        direction={transaction.direction}
                                    />
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-[12.5px] font-semibold text-rz-ink">
                                            {t(
                                                `business.wallet.kind.${transaction.kind}`,
                                            )}{' '}
                                            · {transaction.via}
                                        </span>
                                        <span className="mt-px block text-[11px] text-rz-secondary">
                                            {formatDate(
                                                transaction.occurred_at,
                                                locale,
                                            )}
                                            {transaction.status !==
                                                'completed' && (
                                                <span
                                                    className={
                                                        transaction.status ===
                                                        'failed'
                                                            ? 'text-rz-danger-text'
                                                            : 'text-rz-warning-text'
                                                    }
                                                >
                                                    {' · '}
                                                    {t(
                                                        `business.wallet.tx_status.${transaction.status}`,
                                                    )}
                                                </span>
                                            )}
                                        </span>
                                    </span>
                                    <span
                                        className={cn(
                                            'text-[12.5px] font-semibold whitespace-nowrap',
                                            inbound
                                                ? 'text-rz-accent-app-text'
                                                : 'text-rz-slate',
                                        )}
                                    >
                                        {inbound ? '+' : '-'}
                                        {formatRwf(transaction.amount)}
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ul>
                {transactions.items.length === 0 && (
                    <p className="px-4 py-[26px] text-center text-[12.5px] text-rz-secondary">
                        {t('business.wallet.empty')}
                    </p>
                )}
                {transactions.items.length > SHOWN && (
                    <button
                        type="button"
                        aria-expanded={expanded}
                        onClick={() => setExpanded((value) => !value)}
                        className="w-full bg-[#fafbfd] py-3 text-[12.5px] font-semibold text-rz-accent-app-text dark:bg-rz-page"
                    >
                        {t(
                            expanded
                                ? 'business.wallet.show_less'
                                : 'business.wallet.show_more',
                        )}
                    </button>
                )}
            </div>
        </section>
    );
}
