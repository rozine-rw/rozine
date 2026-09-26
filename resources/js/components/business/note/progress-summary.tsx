import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatCount,
    formatDate,
    formatRwf,
    formatRwfShort,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { NoteProgress } from '@/types/business';

/** One of the dashboard's figure tiles (design L638–646). */
export function Tile({
    label,
    value,
    green = false,
}: {
    label: string;
    value: string;
    green?: boolean;
}) {
    return (
        <div className="rounded-2xl border border-rz-border bg-rz-surface p-3.5">
            <p className="text-[11px] font-semibold text-rz-secondary uppercase">
                {label}
            </p>
            <p
                className={cn(
                    'mt-[3px] text-lg font-semibold',
                    green ? 'text-rz-accent-app-text' : 'text-rz-ink',
                )}
            >
                {value}
            </p>
        </div>
    );
}

type Tracker = {
    title: string;
    pct: number;
    pctLabel: string;
    left: { label: string; value: string };
    right: { label: string; value: string; note: string };
};

function TrackerCard({ title, pct, pctLabel, left, right }: Tracker) {
    return (
        <div className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4">
            <div className="flex items-center justify-between">
                <span className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                    {title}
                </span>
                <span className="text-[11px] font-semibold text-rz-accent-app-text">
                    {pctLabel}
                </span>
            </div>
            <div
                role="progressbar"
                aria-label={title}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={pct}
                className="mt-[11px] h-[9px] overflow-hidden rounded-[5px] bg-rz-page"
            >
                <div
                    className="h-full rounded-[5px] bg-rz-accent-fill"
                    style={{ width: `${pct}%` }}
                />
            </div>
            <div className="mt-[11px] flex gap-2.5">
                <div className="flex-1">
                    <p className="text-[10px] font-semibold text-rz-secondary uppercase">
                        {left.label}
                    </p>
                    <p className="mt-0.5 text-[13px] font-bold whitespace-nowrap text-rz-ink">
                        {left.value}
                    </p>
                </div>
                <div className="flex-1">
                    <p className="text-[10px] font-semibold text-rz-secondary uppercase">
                        {right.label}
                    </p>
                    <p className="mt-0.5 text-[13px] font-bold whitespace-nowrap text-rz-ink">
                        {right.value}
                    </p>
                    <p className="mt-px text-[10px] text-rz-secondary">
                        {right.note}
                    </p>
                </div>
            </div>
        </div>
    );
}

function Notice({
    tone,
    children,
}: {
    tone: 'green' | 'amber';
    children: ReactNode;
}) {
    return (
        <p
            role="status"
            className={cn(
                'mt-3 rounded-2xl border p-[15px] text-[12.5px] leading-normal text-rz-ink',
                tone === 'green'
                    ? 'border-[#cfe9d8] bg-rz-accent-soft dark:border-transparent'
                    : 'border-[#fbe4cc] bg-[#fff8f1] dark:border-transparent dark:bg-[rgba(194,102,31,.12)]',
            )}
        >
            {children}
        </p>
    );
}

/**
 * The top of the note dashboard (design L638–660): four tiles, the tracker and what comes next.
 * The design only draws a repaying note; before disbursement the same tiles and tracker carry the
 * raise's own facts instead.
 */
export function ProgressSummary({
    progress,
    pay,
}: {
    progress: NoteProgress;
    pay: RouteLink | null;
}) {
    const { t, locale } = useTranslation();
    const investors = t('business.note.tile.investors');

    if (progress.phase === 'repaying') {
        const { next_payment: next } = progress;

        return (
            <>
                <div className="mt-4 grid grid-cols-2 gap-[11px]">
                    <Tile
                        label={t('business.note.tile.outstanding')}
                        value={formatRwfShort(progress.outstanding)}
                    />
                    <Tile
                        label={t('business.note.tile.payments_made')}
                        value={t('business.note.payments_made', {
                            made: progress.payments_made,
                            total: progress.payments_total,
                        })}
                        green
                    />
                    <Tile
                        label={investors}
                        value={formatCount(progress.investors)}
                    />
                    <Tile
                        label={t('business.note.tile.health')}
                        value={t(`business.note.health.${progress.health}`)}
                        green={progress.health === 'on_time'}
                    />
                </div>
                <TrackerCard
                    title={t('business.note.tracker.repayment')}
                    pct={progress.repaid_pct}
                    pctLabel={t('business.note.tracker.repaid_pct', {
                        pct: progress.repaid_pct,
                    })}
                    left={{
                        label: t('business.note.tracker.repaid'),
                        value: formatRwf(progress.repaid),
                    }}
                    right={{
                        label: t('business.note.tracker.left_to_pay'),
                        value: formatRwf(progress.remaining),
                        note:
                            progress.remaining_months === 0
                                ? t('business.note.tracker.fully_repaid')
                                : t(
                                      progress.remaining_months === 1
                                          ? 'business.note.tracker.over_month'
                                          : 'business.note.tracker.over_months',
                                      {
                                          count: progress.remaining_months,
                                      },
                                  ),
                    }}
                />
                {next !== null && (
                    <div className="mt-3 flex items-center gap-[13px] rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                        <span className="flex size-[42px] shrink-0 items-center justify-center rounded-xl bg-[rgba(194,102,31,.10)] text-lg">
                            <Icon name="calendar" tone="amber" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-semibold text-rz-secondary uppercase">
                                {t('business.note.upcoming')}
                            </p>
                            <p className="mt-0.5 text-base font-semibold text-rz-ink">
                                {formatRwf(next.amount)}
                            </p>
                            <p className="mt-px text-xs font-medium text-rz-ink">
                                {t(
                                    next.days_until === 1
                                        ? 'business.note.due_in_day'
                                        : 'business.note.due_in_days',
                                    {
                                        date: formatDate(next.due_on, locale),
                                        count: next.days_until,
                                    },
                                )}
                            </p>
                        </div>
                        {pay !== null && (
                            <Link
                                href={pay}
                                className="shrink-0 self-center rounded-[10px] bg-rz-accent-fill px-5 py-[11px] text-[13.5px] font-semibold text-white"
                            >
                                {t('business.note.pay')}
                            </Link>
                        )}
                    </div>
                )}
            </>
        );
    }

    if (progress.phase === 'raising') {
        return (
            <>
                <div className="mt-4 grid grid-cols-2 gap-[11px]">
                    <Tile
                        label={t('business.note.tile.raised')}
                        value={formatRwfShort(progress.raised)}
                    />
                    <Tile
                        label={t('business.note.tile.funded')}
                        value={t('business.note.pct', {
                            pct: progress.funded_pct,
                        })}
                        green
                    />
                    <Tile
                        label={investors}
                        value={formatCount(progress.investors)}
                    />
                    <Tile
                        label={t('business.note.tile.closes_in')}
                        value={t(
                            progress.days_left === 1
                                ? 'business.note.day'
                                : 'business.note.days',
                            { count: progress.days_left },
                        )}
                    />
                </div>
                <TrackerCard
                    title={t('business.note.tracker.funding')}
                    pct={progress.funded_pct}
                    pctLabel={t('business.note.tracker.funded_pct', {
                        pct: progress.funded_pct,
                    })}
                    left={{
                        label: t('business.note.tracker.raised'),
                        value: formatRwf(progress.raised),
                    }}
                    right={{
                        label: t('business.note.tracker.left_to_raise'),
                        value: formatRwf(progress.remaining),
                        note: t('business.note.tracker.closes', {
                            date: formatDate(progress.closes_on, locale),
                        }),
                    }}
                />
            </>
        );
    }

    return (
        <>
            <div className="mt-4 grid grid-cols-2 gap-[11px]">
                <Tile
                    label={t('business.note.tile.raised')}
                    value={formatRwfShort(progress.raised)}
                    green={progress.phase === 'funded'}
                />
                <Tile
                    label={investors}
                    value={formatCount(progress.investors)}
                />
            </div>
            {progress.phase === 'funded' ? (
                <Notice tone="green">
                    {t('business.note.funded_notice', {
                        date: formatDate(progress.funded_on, locale),
                    })}
                </Notice>
            ) : (
                <Notice tone="amber">
                    {t('business.note.expired_notice', {
                        date: formatDate(progress.closed_on, locale),
                        target: formatRwf(progress.target),
                    })}
                </Notice>
            )}
        </>
    );
}
