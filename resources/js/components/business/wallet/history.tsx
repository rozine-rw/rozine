import { Link } from '@inertiajs/react';
import { LocalSheet, SheetClose } from '@/components/investor/local-sheet';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDateTime, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    BusinessWalletEntry,
    BusinessWalletProps,
} from '@/types/business';
import type { Receipt } from '@/types/settlement';

/** The design's in/out arrow tile (`_ic`, L3355–3360): an arrow in for deposits, out for repayments. */
function DirectionTile({
    direction,
    large = false,
}: {
    direction: BusinessWalletEntry['direction'];
    large?: boolean;
}) {
    const inbound = direction === 'in';

    return (
        <span
            aria-hidden
            className={cn(
                'flex shrink-0 items-center justify-center rounded-[10px]',
                large ? 'size-10' : 'size-8',
                inbound ? 'bg-rz-accent-soft' : 'bg-[rgba(194,102,31,.10)]',
            )}
        >
            <svg viewBox="0 0 24 24" fill="none" className="size-4">
                <path
                    d={
                        inbound
                            ? 'M12 4v10M8 11l4 4 4-4M5 20h14'
                            : 'M12 20V10M8 13l4-4 4 4M5 4h14'
                    }
                    stroke="currentColor"
                    className={
                        inbound
                            ? 'text-rz-accent-app-text'
                            : 'text-rz-secondary'
                    }
                    strokeWidth="1.9"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        </span>
    );
}

function useEntryLabel(): (entry: BusinessWalletEntry) => string {
    const { t } = useTranslation();

    return (entry) =>
        entry.movement === 'external'
            ? t('business.servicing.wallet.entry.deposit', {
                  counterparty: entry.counterparty,
              })
            : t('business.servicing.wallet.entry.repayment', {
                  note: entry.note_title,
              });
}

/**
 * The wallet history (design L1347–1457) in C4 terms: external cash in apart from internal
 * repayments (H5), newest first, each opening its immutable receipt. Amounts are a magnitude with
 * a direction, never a sign the client computed; older entries page on.
 */
export function WalletHistory({
    history,
}: {
    history: BusinessWalletProps['history'];
}) {
    const { t, locale } = useTranslation();
    const label = useEntryLabel();

    return (
        <section aria-label={t('business.wallet.history')} className="mt-5">
            <h2 className="text-sm font-semibold text-rz-ink">
                {t('business.wallet.history')}
            </h2>
            <nav
                aria-label={t('business.servicing.wallet.movement')}
                className="mt-[11px] flex gap-[7px]"
            >
                {history.filters.map((filter) => (
                    <Link
                        key={filter.key}
                        href={filter.link}
                        preserveScroll
                        aria-current={filter.active ? 'true' : undefined}
                        className={cn(
                            'shrink-0 rounded-[10px] border px-[13px] py-[7px] text-xs font-semibold whitespace-nowrap',
                            filter.active
                                ? 'border-transparent bg-rz-accent-fill text-white'
                                : 'border-rz-border bg-rz-surface text-rz-slate',
                        )}
                    >
                        {t(`business.servicing.wallet.filter.${filter.key}`)}
                    </Link>
                ))}
            </nav>
            <ul className="mt-2.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {history.items.map((entry) => (
                    <li
                        key={entry.id}
                        className="border-b border-[#eef2f9] last:border-0 dark:border-rz-divider"
                    >
                        <Link
                            href={entry.link}
                            preserveScroll
                            className="flex items-center gap-[11px] px-[13px] py-3"
                        >
                            <DirectionTile direction={entry.direction} />
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-[12.5px] font-semibold text-rz-ink">
                                    {label(entry)}
                                </span>
                                <span className="mt-px block text-[11px] text-rz-secondary">
                                    {formatDate(entry.occurred_at, locale)}
                                    {entry.movement === 'internal' &&
                                        ` · ${t('business.servicing.wallet.instalments', { list: entry.instalment_indexes.join(', ') })}`}
                                </span>
                            </span>
                            <span className="text-[12.5px] font-semibold whitespace-nowrap text-rz-slate">
                                {formatRwf(entry.amount)}
                            </span>
                        </Link>
                    </li>
                ))}
                {history.items.length === 0 && (
                    <li className="px-4 py-[26px] text-center text-[12.5px] text-rz-secondary">
                        {t('business.servicing.wallet.empty')}
                    </li>
                )}
                {history.pagination.next !== null && (
                    <li>
                        <Link
                            href={history.pagination.next}
                            preserveScroll
                            className="block w-full bg-[#fafbfd] py-3 text-center text-[12.5px] font-semibold text-rz-accent-app-text dark:bg-rz-surface-sunken"
                        >
                            {t('business.servicing.wallet.older')}
                        </Link>
                    </li>
                )}
            </ul>
        </section>
    );
}

/**
 * An entry's receipt (design L2378–2413): the immutable record of what was recorded, with its
 * reference and policy version. A deposit shows the provider's fee as the server passed it on.
 */
export function EntryReceiptSheet({
    entry,
    close,
}: {
    entry: BusinessWalletEntry & { receipt: Receipt };
    close: () => void;
}) {
    const { t, locale } = useTranslation();
    const label = useEntryLabel();
    const rows: [string, string][] = [
        ...(entry.movement === 'external'
            ? [
                  [
                      t('business.servicing.wallet.receipt.psp_fee'),
                      formatRwf(entry.psp_fee),
                  ] as [string, string],
              ]
            : []),
        [
            t('business.wallet.detail.when'),
            formatDateTime(entry.receipt.recorded_at, locale),
        ],
        [t('business.wallet.detail.reference'), entry.receipt.reference],
        [
            t('business.servicing.wallet.receipt.policy'),
            entry.receipt.policy_version,
        ],
    ];

    return (
        <LocalSheet
            label={t('business.servicing.wallet.receipt.label')}
            onClose={close}
            maxHeight="lg:max-h-[88%]"
            className="lg:rounded-2xl"
        >
            <div className="flex shrink-0 items-center gap-[11px] border-b border-[#eef2f9] px-4 pt-3.5 pb-3 dark:border-rz-divider">
                <DirectionTile direction={entry.direction} large />
                <p className="min-w-0 flex-1 text-sm font-semibold text-rz-ink">
                    {label(entry)}
                </p>
                <SheetClose onClose={close} />
            </div>
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto">
                <p className="px-4 pt-3.5 text-center text-2xl font-bold text-rz-ink">
                    {formatRwf(entry.amount)}
                </p>
                <dl className="px-4 pt-1.5 pb-0.5">
                    {rows.map(([name, value]) => (
                        <div
                            key={name}
                            className="flex items-center justify-between gap-3 border-b border-[#f1f4f9] py-2 last:border-0 dark:border-rz-divider"
                        >
                            <dt className="text-xs text-rz-secondary">
                                {name}
                            </dt>
                            <dd className="text-right text-xs font-semibold break-all text-rz-ink">
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
                    {t('business.wallet.detail.done')}
                </button>
            </div>
        </LocalSheet>
    );
}
