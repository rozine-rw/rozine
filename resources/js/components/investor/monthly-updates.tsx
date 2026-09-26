import { useState } from 'react';
import { LocalSheet, SheetClose } from '@/components/investor/local-sheet';
import { POSITIVE_TEXT } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatSigned,
    formatSignedCompact,
    intlTag,
    isNegative,
} from '@/lib/investor/format';
import {
    formatDate,
    formatMonthYearLong,
    formatRwf,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    MonthlyUpdateStatus,
    MonthlyUpdateSummary,
    PublishedPhoto,
} from '@/types/investor';

const STATUS_PILL: Record<MonthlyUpdateStatus, string> = {
    healthy:
        'bg-[rgba(29,158,117,.10)] text-[#17795a] dark:bg-[rgba(63,205,160,.14)] dark:text-[#3fcda0]',
    watch: 'bg-[rgba(194,102,31,.10)] text-[#a55418] dark:bg-[rgba(240,160,96,.14)] dark:text-[#f0a060]',
};

/** The design shows three reports, then the rest on "Show more". */
const FIRST_PAGE = 3;

const netTone = (update: MonthlyUpdateSummary) =>
    isNegative(update.net) ? 'text-rz-danger-text' : POSITIVE_TEXT;

function StatusPill({ status }: { status: MonthlyUpdateStatus }) {
    const { t } = useTranslation();

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-[10px] px-[7px] py-0.5 text-[10.5px] font-bold',
                STATUS_PILL[status],
            )}
        >
            <span className="size-[5px] rounded-full bg-current" />
            {t(`investor.updates.status.${status}`)}
        </span>
    );
}

function MonthTile({ month }: { month: string }) {
    const { locale } = useTranslation();
    const date = new Date(month);
    const short = new Intl.DateTimeFormat(intlTag(locale), {
        month: 'short',
        timeZone: 'Africa/Kigali',
    }).format(date);
    const year = new Intl.DateTimeFormat('en-GB', {
        year: '2-digit',
        timeZone: 'Africa/Kigali',
    }).format(date);

    return (
        <span className="flex size-11 shrink-0 flex-col items-center justify-center rounded-xl border border-rz-border bg-[#f3f6fc] dark:bg-rz-surface-muted">
            <span className="text-[12.5px] leading-none font-bold text-rz-ink uppercase">
                {short.replace('.', '')}
            </span>
            <span className="mt-0.5 text-[10.5px] font-semibold text-rz-secondary">
                ’{year}
            </span>
        </span>
    );
}

type UpdateListProps = {
    updates: MonthlyUpdateSummary[];
    variant: 'phone' | 'desk';
    onOpen: (update: MonthlyUpdateSummary) => void;
};

/**
 * Verified monthly reports (phone L943–996, desk L446–459): month, health, the business's summary
 * and the audited net. The design's month-range filter is left out — the list is already the
 * server's, newest first, and "Show more" pages through it.
 */
export function UpdateList({ updates, variant, onOpen }: UpdateListProps) {
    const { t, locale } = useTranslation();
    const [all, setAll] = useState(false);
    const shown = all ? updates : updates.slice(0, FIRST_PAGE);

    if (updates.length === 0) {
        return (
            <p className="mt-[11px] rounded-2xl border border-dashed border-[#dbe3f0] px-4 py-5 text-center text-xs text-rz-secondary dark:border-rz-border">
                {t('investor.updates.none')}
            </p>
        );
    }

    return (
        <div
            className={cn(
                'flex flex-col',
                variant === 'phone' ? 'mt-[11px] gap-[9px]' : 'mt-2.5 gap-2',
            )}
        >
            {shown.map((update) =>
                variant === 'phone' ? (
                    <button
                        key={update.id}
                        type="button"
                        onClick={() => onOpen(update)}
                        className="flex w-full items-center gap-3 rounded-2xl border border-rz-border bg-rz-surface px-3 py-[11px] text-left shadow-[0_4px_13px_-10px_rgba(20,45,95,.28)]"
                    >
                        <MonthTile month={update.month} />
                        <span className="min-w-0 flex-1">
                            <StatusPill status={update.status} />
                            <span className="mt-[5px] block truncate text-xs text-rz-secondary">
                                {update.summary}
                            </span>
                        </span>
                        <span className="shrink-0 text-right">
                            <span
                                className={cn(
                                    'block text-[13px] font-bold whitespace-nowrap',
                                    netTone(update),
                                )}
                            >
                                {formatSignedCompact(update.net)}
                            </span>
                            <span className="mt-px block text-[10.5px] font-semibold text-rz-secondary">
                                {t('investor.updates.net')}
                            </span>
                        </span>
                        <Chevron />
                    </button>
                ) : (
                    <button
                        key={update.id}
                        type="button"
                        onClick={() => onOpen(update)}
                        className="flex w-full items-center justify-between gap-2.5 rounded-xl border border-rz-border bg-rz-surface px-[13px] py-[11px] text-left"
                    >
                        <span className="min-w-0">
                            <span className="block text-[12.5px] font-bold whitespace-nowrap text-rz-ink">
                                {formatMonthYearLong(update.month, locale)}
                            </span>
                            <span className="mt-0.5 block text-[10.5px] whitespace-nowrap text-rz-secondary">
                                {t('investor.updates.parsed_audited')}
                            </span>
                        </span>
                        <span className="flex shrink-0 items-center gap-[7px]">
                            <span className="text-right">
                                <span
                                    className={cn(
                                        'block text-[12.5px] font-bold whitespace-nowrap',
                                        netTone(update),
                                    )}
                                >
                                    {formatSignedCompact(update.net)}
                                </span>
                                <span
                                    className={cn(
                                        'mt-0.5 block text-[10px] font-bold',
                                        update.status === 'healthy'
                                            ? POSITIVE_TEXT
                                            : 'text-[#a55418] dark:text-[#f0a060]',
                                    )}
                                >
                                    {t(
                                        `investor.updates.status.${update.status}`,
                                    )}
                                </span>
                            </span>
                            <Chevron />
                        </span>
                    </button>
                ),
            )}
            {updates.length > FIRST_PAGE &&
                (variant === 'phone' ? (
                    <button
                        type="button"
                        onClick={() => setAll(!all)}
                        className="w-full rounded-xl border border-dashed border-[#dbe3f0] bg-[#fafbfd] py-[11px] text-[12.5px] font-semibold text-rz-accent-app-text dark:border-rz-border dark:bg-rz-surface-sunken"
                    >
                        {all
                            ? t('investor.updates.show_less')
                            : t('investor.updates.show_more')}
                    </button>
                ) : (
                    <button
                        type="button"
                        onClick={() => setAll(!all)}
                        className="mt-px h-[34px] w-full rounded-[10px] border border-rz-border text-xs font-semibold text-rz-accent-app-text"
                    >
                        {all
                            ? t('investor.updates.show_less')
                            : t('investor.updates.show_more')}
                    </button>
                ))}
        </div>
    );
}

function Chevron() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden
            className="size-3.5 shrink-0 text-rz-secondary"
        >
            <path
                d="M9 6l6 6-6 6"
                stroke="currentColor"
                strokeWidth="2.2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

function Micro({ children }: { children: string }) {
    return (
        <p className="mt-4 text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
            {children}
        </p>
    );
}

type UpdateSheetProps = {
    update: MonthlyUpdateSummary;
    onClose: () => void;
};

/**
 * One verified monthly report (phone L4543–4602, desk L468–527): who audited it and when, the
 * statement-verified inflow and outflow, the net, the business's note, the auditor's note and any
 * captioned images the business published, each of which opens full size. No location text or
 * Audit Partner originals are shown (C3 v2, H9).
 */
export function UpdateSheet({ update, onClose }: UpdateSheetProps) {
    const { t, locale } = useTranslation();
    const [photo, setPhoto] = useState<number | null>(null);
    const label = formatMonthYearLong(update.month, locale);

    return (
        <LocalSheet
            label={t('investor.updates.sheet_label', { month: label })}
            onClose={onClose}
        >
            <div className="flex shrink-0 items-center gap-[11px] border-b border-[#eef2f9] px-4 pt-3.5 pb-[13px] dark:border-rz-divider">
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <span className="text-base font-semibold text-rz-ink">
                            {label}
                        </span>
                        <StatusPill status={update.status} />
                    </div>
                    <p className="mt-0.5 text-[11px] text-rz-secondary">
                        {t('investor.updates.verified_report')}
                    </p>
                </div>
                <SheetClose onClose={onClose} />
            </div>
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-4 pt-[15px] pb-5">
                <div className="flex items-center gap-[11px] rounded-xl border border-[#cdeddb] bg-[#eef7f1] px-[13px] py-3 dark:border-[rgba(63,205,160,.25)] dark:bg-[rgba(63,205,160,.08)]">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-[#17795a]">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className="size-5"
                        >
                            <path
                                d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"
                                fill="rgba(255,255,255,.25)"
                                stroke="#fff"
                                strokeWidth="1.7"
                                strokeLinejoin="round"
                            />
                            <path
                                d="M9 12l2 2 4-4.5"
                                stroke="#fff"
                                strokeWidth="1.9"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                    </span>
                    <div className="min-w-0 flex-1">
                        <p
                            className={cn(
                                'text-[12.5px] font-bold',
                                POSITIVE_TEXT,
                            )}
                        >
                            {t('investor.updates.audited_by', {
                                name: update.auditor.name,
                            })}
                        </p>
                        <p className="mt-0.5 text-[11px] text-[#5b6a86] dark:text-rz-body">
                            {t('investor.updates.licence_verified', {
                                licence: update.auditor.licence,
                                date: formatDate(
                                    update.auditor.verified_on,
                                    locale,
                                ),
                            })}
                        </p>
                    </div>
                </div>

                <Micro>{t('investor.updates.financials')}</Micro>
                <div className="mt-[9px] flex gap-[9px]">
                    {(
                        [
                            ['inflow', update.inflow],
                            ['outflow', update.outflow],
                        ] as const
                    ).map(([key, money]) => (
                        <div
                            key={key}
                            className="min-w-0 flex-1 rounded-xl border border-[#eef2f9] bg-[#f8fafc] p-3 dark:border-rz-divider dark:bg-rz-surface-sunken"
                        >
                            <div className="flex items-center gap-[5px]">
                                <span className="text-[10px] font-bold text-rz-secondary uppercase">
                                    {t(`investor.updates.${key}`)}
                                </span>
                                <span
                                    className={cn(
                                        'rounded-[5px] bg-rz-surface px-[5px] py-[1.5px] text-[10px] font-bold uppercase',
                                        POSITIVE_TEXT,
                                    )}
                                >
                                    {t('investor.updates.verified')}
                                </span>
                            </div>
                            <p className="mt-[5px] text-base font-bold whitespace-nowrap text-rz-ink">
                                {formatRwf(money)}
                            </p>
                            <p className="mt-[3px] text-[10.5px] text-rz-secondary">
                                {t('investor.updates.from_statements')}
                            </p>
                        </div>
                    ))}
                </div>
                <div className="mt-[9px] flex items-center justify-between rounded-xl border border-rz-border bg-rz-surface px-3.5 py-3">
                    <span className="text-[12.5px] font-semibold text-rz-slate">
                        {t('investor.updates.net_month')}
                    </span>
                    <span
                        className={cn('text-base font-bold', netTone(update))}
                    >
                        {formatSigned(update.net)}
                    </span>
                </div>

                <Micro>{t('investor.updates.from_business')}</Micro>
                <p className="mt-2 text-[12.5px] leading-[1.6] text-rz-slate">
                    {update.business_note}
                </p>

                {update.auditor_note !== '' && (
                    <>
                        <Micro>{t('investor.updates.auditor_note')}</Micro>
                        <div className="mt-2 flex gap-2.5 rounded-xl border border-[#eef2f9] bg-[#f8fafc] px-[13px] py-3 dark:border-rz-divider dark:bg-rz-surface-sunken">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden
                                className="mt-0.5 size-[15px] shrink-0 text-rz-secondary"
                            >
                                <circle
                                    cx="10.8"
                                    cy="10.8"
                                    r="6.4"
                                    stroke="currentColor"
                                    strokeWidth="1.7"
                                />
                                <path
                                    d="m15.6 15.6 4.4 4.4"
                                    stroke="currentColor"
                                    strokeWidth="1.7"
                                    strokeLinecap="round"
                                />
                            </svg>
                            <p className="text-[12.5px] leading-[1.55] text-rz-slate">
                                {update.auditor_note}
                            </p>
                        </div>
                    </>
                )}

                {update.photos.length > 0 && (
                    <>
                        <Micro>{t('investor.updates.published_photos')}</Micro>
                        <div className="rz-hscroll mt-[9px] flex gap-[9px] overflow-x-auto">
                            {update.photos.map((item, index) => (
                                <button
                                    key={item.caption}
                                    type="button"
                                    onClick={() => setPhoto(index)}
                                    aria-label={t(
                                        'investor.updates.open_photo',
                                        {
                                            caption: item.caption,
                                        },
                                    )}
                                    className="relative h-[104px] w-[150px] shrink-0 overflow-hidden rounded-xl bg-[linear-gradient(135deg,#3a5a7a,#0c1830)]"
                                >
                                    {item.url !== null && (
                                        <img
                                            src={item.url}
                                            alt=""
                                            className="size-full object-cover"
                                        />
                                    )}
                                    <span className="absolute inset-x-0 bottom-0 bg-[linear-gradient(180deg,rgba(8,14,28,0),rgba(8,14,28,.82))] px-2.5 pt-4 pb-2 text-left text-[11px] font-semibold text-white">
                                        {item.caption}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </>
                )}
            </div>
            {photo !== null && (
                <ProofLightbox
                    photos={update.photos}
                    index={photo}
                    onMove={setPhoto}
                    onClose={() => setPhoto(null)}
                />
            )}
        </LocalSheet>
    );
}

type ProofLightboxProps = {
    photos: PublishedPhoto[];
    index: number;
    onMove: (index: number) => void;
    onClose: () => void;
};

/** A published image full size (design L4507–4541): its caption and counter, and nothing else. */
function ProofLightbox({ photos, index, onMove, onClose }: ProofLightboxProps) {
    const { t } = useTranslation();
    const photo = photos[index];
    const many = photos.length > 1;

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label={photo.caption}
            className="fixed inset-0 z-[90] flex animate-[rz-scrim_.2s_ease] flex-col items-center justify-center bg-[rgba(6,10,20,.94)] px-4"
        >
            <button
                type="button"
                aria-label={t('app.sheet.close')}
                onClick={onClose}
                className="absolute top-4 right-4 z-[3] flex size-[38px] items-center justify-center rounded-xl bg-white/15 text-[17px] text-white"
            >
                <span aria-hidden>✕</span>
            </button>
            <div className="flex w-full max-w-[520px] flex-col overflow-hidden rounded-2xl shadow-[0_30px_70px_-20px_rgba(0,0,0,.7)]">
                <div className="aspect-[4/3] bg-[linear-gradient(135deg,#3a5a7a,#0c1830)]">
                    {photo.url !== null && (
                        <img
                            src={photo.url}
                            alt=""
                            className="size-full object-cover"
                        />
                    )}
                </div>
                <div className="border-t border-white/10 bg-[#0f1524] px-[18px] pt-3.5 pb-[15px]">
                    <div className="flex items-center gap-[9px]">
                        <span className="min-w-0 flex-1 text-[14.5px] font-bold text-white">
                            {photo.caption}
                        </span>
                        <span className="shrink-0 text-[11px] font-semibold text-white">
                            {index + 1} / {photos.length}
                        </span>
                    </div>
                </div>
            </div>
            {many && (
                <>
                    <button
                        type="button"
                        aria-label={t('investor.updates.photo_previous')}
                        onClick={() =>
                            onMove((index - 1 + photos.length) % photos.length)
                        }
                        className="absolute top-1/2 left-6 z-[4] flex size-[52px] -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white"
                    >
                        <span aria-hidden>‹</span>
                    </button>
                    <button
                        type="button"
                        aria-label={t('investor.updates.photo_next')}
                        onClick={() => onMove((index + 1) % photos.length)}
                        className="absolute top-1/2 right-6 z-[4] flex size-[52px] -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white"
                    >
                        <span aria-hidden>›</span>
                    </button>
                </>
            )}
        </div>
    );
}
