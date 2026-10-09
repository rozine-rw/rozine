import { Link } from '@inertiajs/react';
import {
    Eyebrow,
    EmptyState,
    INSET,
    StatusPill,
} from '@/components/auditor/ui';
import type { PillTone } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatDate,
    formatDayMonth,
    formatMonthYearLong,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AuditorPortfolioProps,
    FiledReport,
    FiledReportStatus,
    OwedVerification,
} from '@/types/auditor';
import type { RouteLink } from '@/types/routing';

/** A Kigali calendar day, as `YYYY-MM-DD`. */
type Day = string;

/** One entry on the calendar: a verification still owed, or a report already filed. */
export type CalendarItem = {
    key: string;
    day: Day;
    /** Whole days from today to `day`; negative once it has passed. */
    days: number;
    business: string;
    district: string;
    link: RouteLink;
} & (
    | { owed: OwedVerification; report?: never }
    | { report: FiledReport; owed?: never }
);

/** Within this many days a due verification counts as "soon" (design L2953). */
const SOON_DAYS = 5;

/** The alert looks this far ahead for the next filing, whatever month is open (design L2979). */
const AHEAD_DAYS = 60;

const KIGALI_DAY = new Intl.DateTimeFormat('en-CA', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    timeZone: 'Africa/Kigali',
});

/** The Kigali day a timestamp falls on; a bare date is already one. */
const kigaliDay = (iso: string): Day =>
    /^\d{4}-\d{2}-\d{2}$/u.test(iso) ? iso : KIGALI_DAY.format(new Date(iso));

const epochDay = (day: Day): number => Date.parse(`${day}T00:00:00Z`) / 864e5;

/** The first of the month `offset` months from `today`'s, as `YYYY-MM-01`. */
export const monthStart = (today: Day, offset: number): Day => {
    const [year, month] = today.split('-').map(Number);
    const first = new Date(Date.UTC(year, month - 1 + offset, 1));

    return first.toISOString().slice(0, 10);
};

/**
 * Everything the calendar shows, from the partner's own reads: each verification still owed by
 * its due date, and each filed report by the date it was due (or filed, where no due date is
 * recorded). Sorted by day.
 */
export function calendarItems(
    page: Pick<AuditorPortfolioProps, 'owed' | 'reports' | 'server_time'>,
): { today: Day; items: CalendarItem[] } {
    const today = kigaliDay(page.server_time);
    const since = (day: Day) => epochDay(day) - epochDay(today);
    const owed = page.owed.map((entry): CalendarItem => {
        const day = kigaliDay(entry.due_at);

        return {
            key: `owed-${entry.id}`,
            day,
            days: since(day),
            business: entry.business,
            district: entry.district,
            link: entry.link,
            owed: entry,
        };
    });
    const filed = page.reports.map((report): CalendarItem => {
        const day = kigaliDay(report.due_on ?? report.filed_on);

        return {
            key: `report-${report.id}`,
            day,
            days: since(day),
            business: report.business,
            district: report.district,
            link: report.link,
            report,
        };
    });

    return {
        today,
        items: [...owed, ...filed].sort((a, b) => a.day.localeCompare(b.day)),
    };
}

const REPORT_TONE: Record<FiledReportStatus, PillTone> = {
    awaiting_cosign: 'blue',
    published: 'green',
    late: 'red',
    rejected: 'red',
};

/** The dot under a day (design L2963): passed or filed grey, due soon ink, later blue. */
const dotClass = (item: CalendarItem): string =>
    item.report !== undefined || item.days < 0
        ? 'bg-[#5b6578] dark:bg-rz-secondary'
        : item.days <= SOON_DAYS
          ? 'bg-[#0c1830] dark:bg-rz-ink'
          : 'bg-[#1e3aff] dark:bg-rz-investor-text';

/** The date tile's tint (design L2959): grey once passed or filed, amber soon, blue later. */
const tileClass = (item: CalendarItem): string =>
    item.report !== undefined || item.days < 0
        ? 'bg-[#f3f6fc] text-[#5b6578] dark:bg-rz-surface-sunken dark:text-rz-secondary'
        : item.days <= SOON_DAYS
          ? 'bg-[rgba(194,102,31,.10)] text-[#7d420f] dark:text-[#e3b56a]'
          : 'bg-[rgba(30,58,255,.09)] text-[#1e3aff] dark:text-rz-investor-text';

/** The state pill: a filed report's own status, or how far off an owed verification is. */
function ItemPill({ item }: { item: CalendarItem }) {
    const { t } = useTranslation();

    if (item.report !== undefined) {
        return (
            <StatusPill tone={REPORT_TONE[item.report.status]}>
                {t(`auditor.reports.status.${item.report.status}`)}
            </StatusPill>
        );
    }

    if (item.days < 0) {
        return (
            <StatusPill tone="red">
                {item.days === -1
                    ? t('auditor.calendar.overdue_one')
                    : t('auditor.calendar.overdue_other', {
                          count: -item.days,
                      })}
            </StatusPill>
        );
    }

    if (item.days === 0) {
        return (
            <StatusPill tone="red">
                {t('auditor.calendar.due_today')}
            </StatusPill>
        );
    }

    return (
        <StatusPill tone={item.days <= SOON_DAYS ? 'amber' : 'blue'}>
            {item.days === 1
                ? t('auditor.calendar.in_days_one')
                : t('auditor.calendar.in_days_other', { count: item.days })}
        </StatusPill>
    );
}

/** What the item is: a Flash Audit or a monthly verification, and for a report when it was filed. */
function useItemTitle(): (item: CalendarItem) => string {
    const { t, locale } = useTranslation();

    return (item) => {
        if (item.owed !== undefined) {
            return item.owed.kind === 'flash'
                ? t('auditor.reports.flash')
                : t('auditor.calendar.monthly');
        }

        const { report } = item;
        const title =
            report.month === null
                ? t('auditor.reports.flash')
                : t('auditor.reports.monthly', {
                      month: formatMonthYearLong(report.month, locale),
                  });

        return report.status === 'late' && report.late_days !== null
            ? t('auditor.reports.late_by', { title, days: report.late_days })
            : t('auditor.calendar.filed_on', {
                  title,
                  date: formatDayMonth(report.filed_on, locale),
              });
    };
}

/**
 * Where a day's entries open (design L2993): just under the picked day's row, or above it when
 * that row is the month's last.
 */
const popoverPosition = (
    lead: number,
    daysInMonth: number,
    picked: Day,
): { top: string } | { bottom: string } => {
    const rows = Math.ceil((lead + daysInMonth) / 7);
    const row = Math.floor((lead + Number(picked.slice(8)) - 1) / 7);

    return row + 1 < rows
        ? { top: `calc((100% + 4px) * ${(row + 1) / rows})` }
        : { bottom: `calc((100% + 4px) * ${(rows - row) / rows})` };
};

const weekdays = (locale: string): string[] => {
    const format = new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : locale, {
        weekday: 'short',
        timeZone: 'UTC',
    });

    /* 5 Jan 2026 was a Monday: the week starts there, as the design's does. */
    return Array.from({ length: 7 }, (_, index) =>
        format
            .format(new Date(Date.UTC(2026, 0, 5 + index)))
            .replace('.', '')
            .toUpperCase(),
    );
};

/**
 * The audit calendar (design L433–503): the open month's grid with a dot on each day something
 * is due, the day's entries on a tap, and a note on what falls due in the next five days.
 */
export function AuditCalendar({
    today,
    items,
    month,
    onMonth,
    picked,
    onPick,
}: {
    today: Day;
    items: CalendarItem[];
    /** The open month, as `YYYY-MM-01`. */
    month: Day;
    onMonth: (step: -1 | 1) => void;
    picked: Day | null;
    onPick: (day: Day | null) => void;
}) {
    const { t, locale } = useTranslation();
    const titleFor = useItemTitle();
    const [year, monthNumber] = month.split('-').map(Number);
    const daysInMonth = new Date(Date.UTC(year, monthNumber, 0)).getUTCDate();
    const lead =
        (new Date(Date.UTC(year, monthNumber - 1, 1)).getUTCDay() + 6) % 7;
    const prefix = month.slice(0, 8);
    const byDay = (day: Day) => items.filter((item) => item.day === day);
    const pickedItems = picked === null ? [] : byDay(picked);
    const ahead = items.filter(
        (item) =>
            item.owed !== undefined &&
            item.days >= 0 &&
            item.days <= AHEAD_DAYS,
    );
    const soon = ahead.filter((item) => item.days <= SOON_DAYS);
    const next = soon[0] ?? ahead[0];

    return (
        <section aria-labelledby="audit-calendar">
            <Eyebrow as="h2" className="mt-[18px] text-rz-secondary">
                <span id="audit-calendar">{t('auditor.calendar.title')}</span>
            </Eyebrow>
            <p className="mt-[3px] text-[11.5px] leading-normal text-rz-slate">
                {t('auditor.calendar.lead')}
            </p>
            <div className="mt-[11px] rounded-2xl border border-rz-border bg-rz-surface p-4">
                <div className="flex items-center justify-between gap-2.5">
                    <button
                        type="button"
                        onClick={() => onMonth(-1)}
                        aria-label={t('auditor.calendar.previous')}
                        className="size-[30px] rounded-[10px] border border-rz-border bg-[#f3f6fc] text-[13px] font-bold text-[#5b6a86] dark:bg-rz-surface-sunken dark:text-rz-secondary"
                    >
                        ‹
                    </button>
                    <p
                        aria-live="polite"
                        className="text-[14px] font-bold text-rz-ink"
                    >
                        {formatMonthYearLong(month, locale)}
                    </p>
                    <button
                        type="button"
                        onClick={() => onMonth(1)}
                        aria-label={t('auditor.calendar.next')}
                        className="size-[30px] rounded-[10px] border border-rz-border bg-[#f3f6fc] text-[13px] font-bold text-[#5b6a86] dark:bg-rz-surface-sunken dark:text-rz-secondary"
                    >
                        ›
                    </button>
                </div>
                <div
                    aria-hidden
                    className="mt-3.5 grid grid-cols-7 gap-1 text-center text-[10.5px] font-bold tracking-[.04em] text-rz-slate"
                >
                    {weekdays(locale).map((name) => (
                        <span key={name}>{name}</span>
                    ))}
                </div>
                <div className="relative mt-1.5 grid grid-cols-7 gap-1">
                    {Array.from({ length: lead }, (_, index) => (
                        <span key={`lead-${index}`} aria-hidden />
                    ))}
                    {Array.from({ length: daysInMonth }, (_, index) => {
                        const day = `${prefix}${String(index + 1).padStart(2, '0')}`;
                        const due = byDay(day);
                        const isToday = day === today;
                        const isPicked = day === picked;

                        return (
                            <button
                                key={day}
                                type="button"
                                disabled={due.length === 0}
                                aria-pressed={
                                    due.length > 0 ? isPicked : undefined
                                }
                                aria-label={
                                    due.length === 0
                                        ? formatDate(day, locale)
                                        : `${formatDate(day, locale)}, ${
                                              due.length === 1
                                                  ? t(
                                                        'auditor.calendar.day_due_one',
                                                    )
                                                  : t(
                                                        'auditor.calendar.day_due_other',
                                                        {
                                                            count: due.length,
                                                        },
                                                    )
                                          }`
                                }
                                onClick={() => onPick(isPicked ? null : day)}
                                className={cn(
                                    'flex aspect-square flex-col items-center justify-center gap-[3px] rounded-[10px] border p-0 disabled:cursor-default',
                                    isPicked
                                        ? 'border-[#d6e4ff] bg-[#f4f7fc] dark:border-rz-border dark:bg-rz-surface-sunken'
                                        : isToday
                                          ? 'border-[#0c1830] bg-[rgba(194,102,31,.10)] dark:border-rz-ink'
                                          : due.length > 0
                                            ? 'border-rz-border bg-[#f8fafc] dark:bg-rz-surface-sunken'
                                            : 'border-rz-border bg-rz-surface',
                                )}
                            >
                                <span
                                    className={cn(
                                        'text-[11.5px]',
                                        isToday
                                            ? 'font-bold text-[#7d420f] dark:text-[#e3b56a]'
                                            : cn(
                                                  'text-rz-ink',
                                                  due.length > 0
                                                      ? 'font-bold'
                                                      : 'font-semibold',
                                              ),
                                    )}
                                >
                                    {index + 1}
                                </span>
                                <span
                                    aria-hidden
                                    className={cn(
                                        'size-[5px] rounded-full',
                                        due.length > 0
                                            ? dotClass(due[0])
                                            : 'invisible',
                                    )}
                                />
                            </button>
                        );
                    })}
                    {picked !== null && pickedItems.length > 0 && (
                        <div
                            role="dialog"
                            aria-label={formatDate(picked, locale)}
                            style={popoverPosition(lead, daysInMonth, picked)}
                            className="absolute inset-x-0 z-10 rounded-2xl border border-[#e0e7f2] bg-rz-surface p-3 shadow-[0_18px_38px_-18px_rgba(20,45,95,.45)] dark:border-rz-border"
                        >
                            <div className="flex items-start gap-2.5">
                                <div className="min-w-0 flex-1">
                                    <p className="text-[12.5px] font-bold text-rz-ink">
                                        {formatDate(picked, locale)}
                                    </p>
                                    <p className="mt-px text-[10.5px] text-rz-secondary">
                                        {pickedItems.length === 1
                                            ? t('auditor.calendar.day_due_one')
                                            : t(
                                                  'auditor.calendar.day_due_other',
                                                  {
                                                      count: pickedItems.length,
                                                  },
                                              )}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => onPick(null)}
                                    aria-label={t('auditor.calendar.close')}
                                    className={cn(
                                        'size-[22px] shrink-0 rounded-[10px] text-[12px] leading-none text-rz-secondary',
                                        INSET,
                                    )}
                                >
                                    ×
                                </button>
                            </div>
                            <ul className="mt-[9px] flex flex-col gap-[7px]">
                                {pickedItems.map((item) => (
                                    <li key={item.key}>
                                        <Link
                                            href={item.link}
                                            className={cn(
                                                'flex items-center gap-[9px] rounded-[10px] px-2.5 py-[9px]',
                                                INSET,
                                            )}
                                        >
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-[11.5px] font-bold text-rz-ink">
                                                    {item.business}
                                                </span>
                                                <span className="mt-px block truncate text-[10px] text-rz-secondary">
                                                    {titleFor(item)} ·{' '}
                                                    {item.district}
                                                </span>
                                            </span>
                                            <ItemPill item={item} />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
                <ul className="mt-[13px] flex flex-wrap gap-3.5 border-t border-[#f1f4f9] pt-3 dark:border-rz-divider">
                    {(
                        [
                            ['soon', 'bg-[#0c1830] dark:bg-rz-ink'],
                            [
                                'scheduled',
                                'bg-[#1e3aff] dark:bg-rz-investor-text',
                            ],
                            ['passed', 'bg-[#5b6578] dark:bg-rz-secondary'],
                        ] as const
                    ).map(([key, colour]) => (
                        <li key={key} className="flex items-center gap-1.5">
                            <span
                                aria-hidden
                                className={cn(
                                    'size-[7px] rounded-full',
                                    colour,
                                )}
                            />
                            <span className="text-[10.5px] text-rz-secondary">
                                {t(`auditor.calendar.legend.${key}`)}
                            </span>
                        </li>
                    ))}
                </ul>
            </div>
            <div
                role="status"
                className={cn(
                    'mt-3 rounded-2xl border bg-rz-surface p-3.5',
                    soon.length > 0
                        ? 'border-[#f0dcb8] dark:border-[rgba(227,181,106,.35)]'
                        : 'border-[#cfe9d8] dark:border-[rgba(29,158,117,.35)]',
                )}
            >
                <p
                    className={cn(
                        'text-[12.5px] font-bold',
                        soon.length > 0
                            ? 'text-[#7d420f] dark:text-[#e3b56a]'
                            : 'text-rz-positive',
                    )}
                >
                    {soon.length === 0
                        ? t('auditor.calendar.clear')
                        : soon.length === 1
                          ? t('auditor.calendar.soon_one')
                          : t('auditor.calendar.soon_other', {
                                count: soon.length,
                            })}
                </p>
                <p className="mt-1 text-[11.5px] leading-[1.55] text-rz-slate">
                    {next === undefined
                        ? t('auditor.calendar.clear_none')
                        : t(
                              soon.length === 0
                                  ? 'auditor.calendar.clear_next'
                                  : soon.length === 1
                                    ? 'auditor.calendar.soon_body_one'
                                    : 'auditor.calendar.soon_body_other',
                              {
                                  business: next.business,
                                  date: formatDayMonth(next.day, locale),
                              },
                          )}
                </p>
            </div>
        </section>
    );
}

const MINI_TILE = cn('min-w-0 flex-1 rounded-[10px] px-2.5 py-2', INSET);

/**
 * What falls due in the open month (design L556–585): each entry with its day, its state, its
 * district, the partner's share and the day it is due. No read serves the partner's share yet, so
 * it reads as a dash. A rejected report keeps its reason and its linked amendment.
 */
export function DueList({
    items,
    month,
}: {
    items: CalendarItem[];
    month: Day;
}) {
    const { t, locale } = useTranslation();
    const titleFor = useItemTitle();
    const due = items.filter((item) => item.day.startsWith(month.slice(0, 8)));
    const intlLocale = locale === 'en' ? 'en-GB' : locale;
    const monthName = new Intl.DateTimeFormat(intlLocale, {
        month: 'long',
        timeZone: 'UTC',
    }).format(new Date(`${month}T00:00:00Z`));
    const monthAbbr = new Intl.DateTimeFormat(intlLocale, {
        month: 'short',
        timeZone: 'UTC',
    });

    return (
        <section aria-labelledby="due-in-month">
            <div className="mt-5 flex items-baseline justify-between">
                <Eyebrow as="h2">
                    <span id="due-in-month">
                        {t('auditor.calendar.due_in', { month: monthName })}
                    </span>
                </Eyebrow>
                <span className="text-[11px] text-rz-secondary">
                    {due.length === 1
                        ? t('auditor.calendar.count_one')
                        : t('auditor.calendar.count_other', {
                              count: due.length,
                          })}
                </span>
            </div>
            {due.length === 0 ? (
                <EmptyState className="mt-2.5 py-[26px]">
                    {t('auditor.calendar.empty')}
                </EmptyState>
            ) : (
                <ul className="mt-2.5 flex flex-col gap-[9px]">
                    {due.map((item) => {
                        const date = new Date(`${item.day}T00:00:00Z`);
                        const rejection = item.report?.rejection ?? null;

                        return (
                            <li
                                key={item.key}
                                className={cn(
                                    'rounded-2xl border bg-rz-surface px-3.5 py-[13px]',
                                    rejection !== null
                                        ? 'border-[#f5cfcb] dark:border-[rgba(255,107,111,.3)]'
                                        : item.owed !== undefined &&
                                            item.days >= 0 &&
                                            item.days <= SOON_DAYS
                                          ? 'border-[#f0dcb8] dark:border-[rgba(227,181,106,.35)]'
                                          : 'border-rz-border',
                                )}
                            >
                                <Link
                                    href={item.link}
                                    className="flex items-center gap-[11px]"
                                >
                                    <span
                                        className={cn(
                                            'flex size-[38px] shrink-0 flex-col items-center justify-center rounded-[10px]',
                                            tileClass(item),
                                        )}
                                    >
                                        <span className="text-[13px] leading-none font-bold">
                                            {date.getUTCDate()}
                                        </span>
                                        <span className="text-[10px] font-bold opacity-70">
                                            {monthAbbr
                                                .format(date)
                                                .replace('.', '')
                                                .slice(0, 3)
                                                .toUpperCase()}
                                        </span>
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-[13.5px] font-bold text-rz-ink">
                                            {item.business}
                                        </span>
                                        <span className="mt-px block truncate text-[11px] text-rz-secondary">
                                            {titleFor(item)}
                                        </span>
                                    </span>
                                    <ItemPill item={item} />
                                </Link>
                                <div className="mt-2.5 flex gap-2">
                                    <div className={MINI_TILE}>
                                        <p className="text-[10.5px] font-bold tracking-[.04em] text-rz-slate uppercase">
                                            {t('auditor.reports.district')}
                                        </p>
                                        <p className="mt-0.5 truncate text-[11.5px] font-bold text-rz-ink">
                                            {item.district}
                                        </p>
                                    </div>
                                    <div className={MINI_TILE}>
                                        <p className="text-[10.5px] font-bold tracking-[.04em] text-rz-slate uppercase">
                                            {t('auditor.calendar.share')}
                                        </p>
                                        <p className="mt-0.5 truncate text-[11.5px] font-bold text-rz-ink">
                                            —
                                        </p>
                                    </div>
                                    <div className={MINI_TILE}>
                                        <p className="text-[10.5px] font-bold tracking-[.04em] text-rz-slate uppercase">
                                            {t('auditor.calendar.on_time_by')}
                                        </p>
                                        <p className="mt-0.5 truncate text-[11.5px] font-bold text-rz-ink">
                                            {formatDayMonth(item.day, locale)}
                                        </p>
                                    </div>
                                </div>
                                {rejection !== null && (
                                    <div className="mt-2.5 rounded-[10px] border border-[#f5cfcb] px-3 py-2.5 dark:border-[rgba(255,107,111,.3)]">
                                        <p className="text-[11px] leading-[1.5] text-[#8a5a55] dark:text-rz-danger-text">
                                            {rejection.reason}
                                        </p>
                                        <Link
                                            href={rejection.amend}
                                            className="mt-1.5 inline-block text-[11.5px] font-bold text-rz-ink"
                                        >
                                            {t('auditor.reports.amend')}
                                        </Link>
                                    </div>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
