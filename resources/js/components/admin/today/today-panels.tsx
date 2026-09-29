import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { avatarColor, initialOf } from '@/components/admin/format';
import { KIND_META } from '@/components/admin/kind-meta';
import {
    CardTitle,
    InfoTip,
    Panel,
    RATING_TEXT,
    useRelativeLabel,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { AdminTodayProps, FunnelStage } from '@/types/admin';

const CAPTION = 'mt-1.5 text-[11px] text-rz-muted';

const DATE_INPUT =
    'relative rounded-lg border border-rz-hairline bg-rz-surface px-[7px] py-[5px] text-[11px] text-rz-ink uppercase outline-none [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:h-auto [&::-webkit-calendar-picker-indicator]:w-auto [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-0';

const short = (money: AdminTodayProps['treasury']['invested']): string =>
    formatRwfShort(money).replace('RWF ', '');

/** "Capital raised": the server's buckets for the chosen window, refetched when it changes. */
export function CapitalRaisedPanel({
    chart,
}: {
    chart: AdminTodayProps['capital_raised'];
}) {
    const { t, locale } = useTranslation();
    const [range, setRange] = useState({
        fromDate: chart.from?.slice(0, 10) ?? '',
        fromTime: chart.from?.slice(11, 16) ?? '',
        toDate: chart.to?.slice(0, 10) ?? '',
        toTime: chart.to?.slice(11, 16) ?? '',
    });

    const stamp = (date: string, time: string): string | null =>
        date === '' ? null : `${date}T${time === '' ? '00:00' : time}`;

    const change = (field: keyof typeof range, value: string) => {
        const next = { ...range, [field]: value };

        setRange(next);
        router.reload({
            data: {
                from: stamp(next.fromDate, next.fromTime),
                to: stamp(next.toDate, next.toTime),
            },
            only: ['capital_raised'],
        });
    };

    const clear = () => {
        setRange({ fromDate: '', fromTime: '', toDate: '', toTime: '' });
        router.reload({
            data: { from: null, to: null },
            only: ['capital_raised'],
        });
    };

    const rangeLabel =
        chart.from === null && chart.to === null
            ? t('admin.today.capital.all_time')
            : t('admin.today.capital.range', {
                  from:
                      chart.from === null
                          ? '…'
                          : formatDate(chart.from, locale),
                  to: chart.to === null ? '…' : formatDate(chart.to, locale),
              });

    return (
        <Panel label={t('admin.today.capital.title')}>
            <div className="flex flex-wrap items-center justify-between gap-2.5">
                <CardTitle>{t('admin.today.capital.title')}</CardTitle>
                <div className="flex flex-wrap items-center gap-2 lg:flex-nowrap">
                    {(['from', 'to'] as const).map((end) => (
                        <div key={end} className="flex items-center gap-[5px]">
                            <span className="text-[10px] font-semibold text-[#7b8699] uppercase dark:text-rz-muted">
                                {t(`admin.today.capital.${end}`)}
                            </span>
                            <input
                                type="date"
                                aria-label={t(
                                    `admin.today.capital.${end}_date`,
                                )}
                                value={range[`${end}Date`]}
                                onChange={(event) =>
                                    change(`${end}Date`, event.target.value)
                                }
                                className={DATE_INPUT}
                            />
                            <input
                                type="time"
                                aria-label={t(
                                    `admin.today.capital.${end}_time`,
                                )}
                                value={range[`${end}Time`]}
                                onChange={(event) =>
                                    change(`${end}Time`, event.target.value)
                                }
                                className={DATE_INPUT}
                            />
                        </div>
                    ))}
                    {(chart.from !== null || chart.to !== null) && (
                        <button
                            type="button"
                            onClick={clear}
                            className="shrink-0 rounded-lg border border-[#d6e4ff] bg-[#eef3fb] px-2.5 py-[5px] text-[11px] font-semibold text-rz-accent-app-text dark:border-rz-border dark:bg-rz-surface-muted"
                        >
                            {t('admin.today.capital.clear')}
                        </button>
                    )}
                </div>
            </div>
            <p className={CAPTION}>
                {t('admin.today.capital.caption', {
                    range: rangeLabel,
                    grain: t(`admin.today.capital.grain.${chart.grain}`),
                })}
            </p>
            <div className="mt-5 flex h-[200px] items-end gap-2.5">
                {chart.bars.map((bar) => (
                    <div
                        key={bar.label}
                        className="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1.5"
                    >
                        <span className="text-[8.5px] font-bold whitespace-nowrap text-[#1428a8] dark:text-[#99a3ff]">
                            {short(bar.amount)}
                        </span>
                        <div
                            className="w-full rounded-t-md bg-[#1e3aff] dark:bg-[#3d57ff]"
                            style={{
                                height: `${Math.max(2, bar.height_pct)}%`,
                            }}
                        />
                        <span className="text-[10px] text-rz-muted">
                            {bar.label}
                        </span>
                    </div>
                ))}
            </div>
        </Panel>
    );
}

/** The health donut: the server's three shares, drawn as one conic ring. */
export function PortfolioHealthPanel({
    health,
}: {
    health: AdminTodayProps['portfolio_health'];
}) {
    const { t } = useTranslation();
    const a = health.healthy.pct * 3.6;
    const b = (health.healthy.pct + health.watch.pct) * 3.6;
    const rows = [
        [
            'healthy',
            health.healthy,
            'bg-[#1d9e75]',
            'text-[#1d9e75] dark:text-[#3fcda0]',
        ],
        [
            'watch',
            health.watch,
            'bg-[#c2661f]',
            'text-[#c2661f] dark:text-[#f0a060]',
        ],
        [
            'distressed',
            health.distressed,
            'bg-[#e5484d]',
            'text-[#e5484d] dark:text-[#ff6b6f]',
        ],
    ] as const;

    return (
        <Panel label={t('admin.today.health.title')}>
            <CardTitle>{t('admin.today.health.title')}</CardTitle>
            <p className={CAPTION}>
                {t('admin.today.health.caption', { count: health.total })}
            </p>
            <div className="mt-4 flex justify-center">
                <div
                    role="img"
                    aria-label={t('admin.today.health.donut', {
                        pct: health.healthy.pct,
                    })}
                    className="relative size-[150px] rounded-full"
                    style={{
                        background: `conic-gradient(#1d9e75 0deg ${a}deg, #c2661f ${a}deg ${b}deg, #e5484d ${b}deg 360deg)`,
                    }}
                >
                    <div className="absolute inset-[18px] flex flex-col items-center justify-center rounded-full bg-[#eef3fb] dark:bg-rz-surface-muted">
                        <span className="text-[28px] font-bold text-[#1d9e75] dark:text-[#3fcda0]">
                            {health.healthy.pct}%
                        </span>
                        <span className="text-[11px] text-[#7b8699] dark:text-rz-muted">
                            {t('admin.today.health.healthy_caption')}
                        </span>
                    </div>
                </div>
            </div>
            <dl className="mt-[18px] flex flex-col gap-[9px]">
                {rows.map(([key, share, dot, text]) => (
                    <div
                        key={key}
                        className="flex items-center justify-between"
                    >
                        <dt className="flex items-center gap-[7px] text-[12.5px] text-rz-body">
                            <span
                                className={cn('size-[9px] rounded-[3px]', dot)}
                            />
                            {t(`admin.today.health.${key}`)}
                        </dt>
                        <dd className={cn('text-[12.5px] font-semibold', text)}>
                            {share.count} · {share.pct}%
                        </dd>
                    </div>
                ))}
            </dl>
        </Panel>
    );
}

/** Live activity: the newest ledger movements, each opening its entry. */
export function ActivityPanel({
    items,
    ledger,
    serverTime,
}: {
    items: AdminTodayProps['activity'];
    ledger: RouteLink;
    serverTime: string;
}) {
    const { t } = useTranslation();
    const ago = useRelativeLabel(serverTime);

    return (
        <Panel label={t('admin.today.activity.title')}>
            <div className="flex items-center justify-between">
                <CardTitle>{t('admin.today.activity.title')}</CardTitle>
                <span className="text-[11px] font-semibold text-rz-muted">
                    {t('admin.today.activity.caption')}
                </span>
            </div>
            <div className="mt-3.5 flex flex-col">
                {items.length === 0 && (
                    <p className="py-6 text-center text-[12.5px] text-rz-faint">
                        {t('admin.today.activity.empty')}
                    </p>
                )}
                {items.map((item) => (
                    <Link
                        key={item.id}
                        href={item.link}
                        className="flex items-center gap-3 border-b border-rz-hairline py-[9px]"
                    >
                        <span
                            aria-hidden
                            className={cn(
                                'flex size-[30px] shrink-0 items-center justify-center rounded-[9px] border border-rz-hairline bg-rz-surface text-[14px] font-bold',
                                KIND_META[item.kind].color,
                            )}
                        >
                            {KIND_META[item.kind].glyph}
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block text-[13px] font-semibold text-rz-ink">
                                {t(`admin.ledger.kind.${item.kind}`)}
                            </span>
                            <span className="block truncate text-[11px] text-rz-muted">
                                {item.summary}
                            </span>
                        </span>
                        <span className="shrink-0 text-right">
                            <span
                                className={cn(
                                    'block text-[12.5px] font-bold',
                                    KIND_META[item.kind].color,
                                )}
                            >
                                {formatRwfShort(item.amount)}
                            </span>
                            <span className="block text-[10.5px] text-rz-group">
                                {ago(item.at)}
                            </span>
                        </span>
                    </Link>
                ))}
                <Link
                    href={ledger}
                    className="mt-3 flex w-full items-center justify-center gap-[7px] rounded-[10px] border border-rz-hairline bg-rz-page p-[11px] text-[12.5px] font-bold text-rz-accent-app-text"
                >
                    {t('admin.today.activity.see_all')}
                    <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                    >
                        <path
                            d="M9 6l6 6-6 6"
                            stroke="currentColor"
                            strokeWidth="2.2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </Link>
            </div>
        </Panel>
    );
}

const FUNNEL_FILL: Record<FunnelStage, string> = {
    submitted: 'bg-[#7c3aed]',
    live: 'bg-[#1e3aff]',
    funded: 'bg-[#1428a8] dark:bg-[#5b74ff]',
    repaying: 'bg-[#c2661f]',
    matured: 'bg-[#1d9e75]',
    failed: 'bg-[#e5484d]',
};

export function LifecyclePanel({
    funnel,
}: {
    funnel: AdminTodayProps['funnel'];
}) {
    const { t } = useTranslation();

    return (
        <Panel label={t('admin.today.lifecycle.title')}>
            <CardTitle>{t('admin.today.lifecycle.title')}</CardTitle>
            <p className={CAPTION}>{t('admin.today.lifecycle.caption')}</p>
            <div className="mt-4 flex flex-col gap-[13px]">
                {funnel.map((stage) => (
                    <div key={stage.stage}>
                        <div className="mb-[5px] flex items-center justify-between">
                            <span className="text-[12.5px] font-semibold text-rz-slate">
                                {t(`admin.today.lifecycle.${stage.stage}`)}
                            </span>
                            <span className="text-[12.5px] font-bold text-rz-ink">
                                {stage.count}
                            </span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-[5px] bg-[#e2e8f2] dark:bg-rz-surface-muted">
                            <div
                                className={cn(
                                    'h-full rounded-[5px]',
                                    FUNNEL_FILL[stage.stage],
                                )}
                                style={{
                                    width: `${Math.max(3, stage.width_pct)}%`,
                                }}
                            />
                        </div>
                    </div>
                ))}
            </div>
        </Panel>
    );
}

export function PendingApplicationsPanel({
    items,
}: {
    items: AdminTodayProps['pending_applications'];
}) {
    const { t } = useTranslation();

    return (
        <Panel label={t('admin.today.pending.title')}>
            <CardTitle>{t('admin.today.pending.title')}</CardTitle>
            <div className="mt-3.5 flex flex-col gap-2.5">
                {items.length === 0 && (
                    <p className="py-6 text-center text-[12.5px] text-rz-faint">
                        {t('admin.today.pending.empty')}
                    </p>
                )}
                {items.map((item) => (
                    <div
                        key={item.id}
                        className="flex items-center gap-3 rounded-[11px] bg-rz-surface-sunken px-[13px] py-[11px]"
                    >
                        <div
                            className="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] text-[13px] font-semibold text-white"
                            style={{ background: avatarColor(item.id) }}
                        >
                            {initialOf(item.business)}
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="truncate text-[13px] font-semibold text-rz-ink">
                                {item.business}
                            </div>
                            <div className="text-[11px] text-[#7b8699] dark:text-rz-muted">
                                {formatRwfShort(item.requested)} ·{' '}
                                {item.rating === null ? (
                                    <span className="font-semibold">
                                        {t('admin.rating.pending')}
                                    </span>
                                ) : (
                                    <span
                                        className={cn(
                                            'font-semibold',
                                            RATING_TEXT[item.rating.band],
                                        )}
                                    >
                                        {t(
                                            `admin.rating.band.${item.rating.band}`,
                                        )}{' '}
                                        {item.rating.score}
                                    </span>
                                )}
                            </div>
                        </div>
                        <Link
                            href={item.link}
                            aria-label={t('admin.today.pending.review_named', {
                                name: item.business,
                            })}
                            className="rounded-lg bg-rz-accent-fill px-3 py-1.5 text-[11.5px] font-semibold text-white"
                        >
                            {t('admin.today.pending.review')}
                        </Link>
                    </div>
                ))}
            </div>
        </Panel>
    );
}

export function SectorPanel({
    sectors,
}: {
    sectors: AdminTodayProps['sector_exposure'];
}) {
    const { t } = useTranslation();

    return (
        <Panel label={t('admin.today.sectors.title')}>
            <div className="flex items-center gap-[7px]">
                <CardTitle>{t('admin.today.sectors.title')}</CardTitle>
                <InfoTip label={t('admin.today.sectors.about')} align="right">
                    {t('admin.today.sectors.tip')}
                </InfoTip>
            </div>
            <div className="mt-3.5 grid grid-cols-2 gap-2 sm:grid-cols-3">
                {sectors.map((sector) => (
                    <div
                        key={sector.sector}
                        className={cn(
                            'rounded-[10px] px-[11px] py-[13px]',
                            sector.at_risk
                                ? 'bg-[#c85a2a]'
                                : 'bg-[#1e3aff] dark:bg-[#3d57ff]',
                        )}
                    >
                        <div className="text-[11.5px] font-semibold text-white/90">
                            {sector.sector}
                        </div>
                        <div className="mt-[5px] text-[15px] font-bold text-white">
                            {formatRwfShort(sector.outstanding)}
                        </div>
                        <div className="mt-0.5 text-[10.5px] text-white/75">
                            {t('admin.today.sectors.notes', {
                                count: sector.notes,
                            })}
                        </div>
                    </div>
                ))}
            </div>
        </Panel>
    );
}

export function TreasuryPanel({
    treasury,
}: {
    treasury: AdminTodayProps['treasury'];
}) {
    const { t } = useTranslation();
    const tiles = [
        ['invested', treasury.invested, 'text-rz-ink'],
        ['disbursed', treasury.disbursed, 'text-rz-ink'],
        [
            'platform_net',
            treasury.platform_net,
            'text-[#1d9e75] dark:text-[#3fcda0]',
        ],
        [
            'paid_to_investors',
            treasury.paid_to_investors,
            'text-[#7c3aed] dark:text-[#b199fb]',
        ],
    ] as const;

    return (
        <Panel label={t('admin.today.treasury.title')}>
            <CardTitle>{t('admin.today.treasury.title')}</CardTitle>
            <dl className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                {tiles.map(([key, value, tone]) => (
                    <div
                        key={key}
                        className="rounded-xl border border-rz-hairline bg-rz-surface px-[15px] py-3.5"
                    >
                        <dt className="text-[11.5px] font-semibold text-[#7b8699] dark:text-rz-muted">
                            {t(`admin.today.treasury.${key}`)}
                        </dt>
                        <dd
                            className={cn('mt-1.5 text-[19px] font-bold', tone)}
                        >
                            {formatRwfShort(value)}
                        </dd>
                    </div>
                ))}
            </dl>
        </Panel>
    );
}

export function CollectionsPanel({
    collections,
}: {
    collections: AdminTodayProps['collections'];
}) {
    const { t } = useTranslation();
    const states = [
        [
            'on_track',
            collections.on_track,
            'bg-[#1d9e75]',
            'text-[#1d9e75] dark:text-[#3fcda0]',
        ],
        [
            'late',
            collections.late,
            'bg-[#c2661f]',
            'text-[#c2661f] dark:text-[#f0a060]',
        ],
        [
            'default_risk',
            collections.default_risk,
            'bg-[#e5484d]',
            'text-[#e5484d] dark:text-[#ff6b6f]',
        ],
    ] as const;

    return (
        <Panel label={t('admin.today.collections.title')}>
            <CardTitle>{t('admin.today.collections.title')}</CardTitle>
            <p className={CAPTION}>
                {t('admin.today.collections.caption', {
                    count: collections.in_repayment,
                })}
            </p>
            <dl className="mt-[15px] flex flex-col gap-[11px]">
                <div className="flex items-center justify-between">
                    <dt className="text-[12px] font-semibold text-[#7b8699] dark:text-rz-muted">
                        {t('admin.today.collections.outstanding')}
                    </dt>
                    <dd className="text-[14px] font-bold text-rz-ink">
                        {formatRwfShort(collections.outstanding)}
                    </dd>
                </div>
                <div className="flex items-center justify-between">
                    <dt className="text-[12px] font-semibold text-[#7b8699] dark:text-rz-muted">
                        {t('admin.today.collections.next_due')}
                    </dt>
                    <dd className="text-[14px] font-bold text-rz-accent-app-text">
                        {formatRwfShort(collections.next_due)}
                    </dd>
                </div>
                <div className="my-0.5 h-px bg-rz-hairline" />
                {states.map(([key, count, dot, text]) => (
                    <div
                        key={key}
                        className="flex items-center justify-between"
                    >
                        <dt className="flex items-center gap-[7px] text-[12.5px] text-rz-body">
                            <span
                                className={cn('size-[9px] rounded-[3px]', dot)}
                            />
                            {t(`admin.today.collections.${key}`)}
                        </dt>
                        <dd className={cn('text-[12.5px] font-bold', text)}>
                            {count}
                        </dd>
                    </div>
                ))}
            </dl>
        </Panel>
    );
}
