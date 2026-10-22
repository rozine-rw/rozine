import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
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
 * The history's date range (design `txFrom`/`txTo`, "All time"): inclusive calendar days the
 * server narrows the list and the statement to. Applying reloads the page with the new range.
 */
function RangePicker({
    range,
}: {
    range: NonNullable<BusinessWalletProps['history']['range']>;
}) {
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState(false);
    const [from, setFrom] = useState(range.from ?? '');
    const [to, setTo] = useState(range.to ?? '');
    const label =
        range.from === null && range.to === null
            ? t('business.wallet.range.all')
            : `${range.from === null ? t('business.wallet.range.start') : formatDate(range.from, locale)} – ${range.to === null ? t('business.wallet.range.now') : formatDate(range.to, locale)}`;
    const apply = (query: Record<string, string>) => {
        const search = new URLSearchParams(query).toString();

        setOpen(false);
        router.visit(
            {
                url:
                    search === ''
                        ? range.base.url
                        : `${range.base.url}${range.base.url.includes('?') ? '&' : '?'}${search}`,
                method: 'get',
            },
            { preserveScroll: true },
        );
    };
    const submit = (event: FormEvent) => {
        event.preventDefault();
        apply({
            ...(from === '' ? {} : { from }),
            ...(to === '' ? {} : { to }),
        });
    };

    return (
        <div className="relative">
            <button
                type="button"
                aria-label={`${t('business.wallet.range.label')}: ${label}`}
                aria-expanded={open}
                onClick={() => setOpen(!open)}
                className="flex h-[30px] items-center gap-1.5 rounded-[10px] border border-rz-border bg-rz-surface px-2.5 text-xs font-semibold text-rz-slate"
            >
                <svg
                    aria-hidden
                    width="13"
                    height="13"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                    <rect
                        x="4"
                        y="5"
                        width="16"
                        height="15"
                        rx="2"
                        stroke="currentColor"
                        strokeWidth="1.9"
                    />
                    <path
                        d="M4 10h16M9 3v4M15 3v4"
                        stroke="currentColor"
                        strokeWidth="1.9"
                        strokeLinecap="round"
                    />
                </svg>
                {label}
            </button>
            {open && (
                <form
                    onSubmit={submit}
                    className="absolute top-[37px] right-0 z-40 flex w-[220px] flex-col gap-2.5 rounded-xl border border-rz-border bg-rz-surface p-3 shadow-[0_20px_44px_-16px_rgba(20,45,95,.34)]"
                >
                    <label className="flex flex-col gap-1 text-[11px] font-semibold text-rz-secondary">
                        {t('business.wallet.range.from')}
                        <input
                            type="date"
                            value={from}
                            max={to === '' ? undefined : to}
                            onChange={(event) => setFrom(event.target.value)}
                            className="rounded-[10px] border border-rz-border bg-rz-surface px-2.5 py-2 text-[12.5px] text-rz-ink"
                        />
                    </label>
                    <label className="flex flex-col gap-1 text-[11px] font-semibold text-rz-secondary">
                        {t('business.wallet.range.to')}
                        <input
                            type="date"
                            value={to}
                            min={from === '' ? undefined : from}
                            onChange={(event) => setTo(event.target.value)}
                            className="rounded-[10px] border border-rz-border bg-rz-surface px-2.5 py-2 text-[12.5px] text-rz-ink"
                        />
                    </label>
                    <div className="flex gap-2">
                        <button
                            type="button"
                            onClick={() => apply({})}
                            className="flex-1 rounded-[10px] border border-rz-border py-2 text-xs font-semibold text-rz-slate"
                        >
                            {t('business.wallet.range.clear')}
                        </button>
                        <button
                            type="submit"
                            className="flex-1 rounded-[10px] bg-rz-accent-fill py-2 text-xs font-semibold text-white"
                        >
                            {t('business.wallet.range.apply')}
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}

/** The statement download (design "Download"): a PDF or a spreadsheet of the same range, from the server. */
function StatementDownload({
    exports,
}: {
    exports: NonNullable<BusinessWalletProps['exports']>;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const options = [
        {
            key: 'pdf',
            link: exports.pdf,
            label: t('business.wallet.export.pdf'),
            tint: 'bg-[rgba(229,72,77,.10)] text-[#b3383c]',
        },
        {
            key: 'csv',
            link: exports.csv,
            label: t('business.wallet.export.csv'),
            tint: 'bg-rz-accent-soft text-rz-accent-app-text',
        },
    ];

    return (
        <div className="relative">
            <button
                type="button"
                title={t('business.wallet.export.label')}
                aria-label={t('business.wallet.export.label')}
                aria-expanded={open}
                onClick={() => setOpen(!open)}
                className="flex size-[30px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-rz-slate"
            >
                <svg
                    aria-hidden
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
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
            {open && (
                <div className="absolute top-[37px] right-0 z-40 w-[168px] rounded-xl border border-rz-border bg-rz-surface p-[5px] shadow-[0_20px_44px_-16px_rgba(20,45,95,.34)]">
                    {options.map((option) => (
                        <a
                            key={option.key}
                            href={option.link.url}
                            download
                            onClick={() => setOpen(false)}
                            className="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-[9px] text-left"
                        >
                            <span
                                aria-hidden
                                className={cn(
                                    'flex size-[26px] items-center justify-center rounded-[10px] text-[10px] font-bold uppercase',
                                    option.tint,
                                )}
                            >
                                {option.key}
                            </span>
                            <span className="text-[12.5px] font-semibold text-rz-ink">
                                {option.label}
                            </span>
                        </a>
                    ))}
                </div>
            )}
        </div>
    );
}

/**
 * The wallet history (design L1347–1457) in C4 terms: external cash in apart from internal
 * repayments (H5), newest first, each opening its immutable receipt. Amounts are a magnitude with
 * a direction, never a sign the client computed; older entries page on. The date range and the
 * statement download sit beside the heading, as the design draws them.
 */
export function WalletHistory({
    history,
    exports = null,
}: {
    history: BusinessWalletProps['history'];
    exports?: BusinessWalletProps['exports'];
}) {
    const { t, locale } = useTranslation();
    const label = useEntryLabel();

    return (
        <section aria-label={t('business.wallet.history')} className="mt-5">
            <div className="flex items-center justify-between gap-2">
                <h2 className="text-sm font-semibold text-rz-ink">
                    {t('business.wallet.history')}
                </h2>
                <div className="flex items-center gap-1.5">
                    {history.range !== null && (
                        <RangePicker range={history.range} />
                    )}
                    {exports !== null && (
                        <StatementDownload exports={exports} />
                    )}
                </div>
            </div>
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
