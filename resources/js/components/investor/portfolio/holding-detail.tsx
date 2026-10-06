import { Link } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { PhotoFill } from '@/components/investor/deals/deal-bits';
import { SourceBadge } from '@/components/investor/deals/deal-sections';
import { UpdateList, UpdateSheet } from '@/components/investor/monthly-updates';
import { HealthPill } from '@/components/investor/portfolio/holding-card';
import { IssueRecord } from '@/components/investor/portfolio/issue-record';
import {
    ACCENT_FILL,
    accentBanner,
    AMBER_TEXT,
    POSITIVE_TEXT,
    RATING_STYLE,
} from '@/components/investor/tokens';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatAmount, formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { C3HoldingDetail, MonthlyUpdateSummary } from '@/types/investor';

const MICRO =
    'text-[10.5px] font-bold tracking-[.04em] text-rz-slate uppercase';
const HEADING =
    'text-[13px] font-semibold tracking-[.04em] text-rz-secondary uppercase';

function MoneyTile({
    label,
    amount,
    sub,
    positive = false,
}: {
    label: string;
    amount: string;
    sub?: string;
    positive?: boolean;
}) {
    const { t } = useTranslation();

    return (
        <div className="min-w-0 rounded-2xl border border-rz-border bg-rz-surface px-3 py-[11px]">
            <p className={MICRO}>{label}</p>
            <p
                className={cn(
                    'mt-1 truncate text-[15px] font-bold tracking-[-.2px] whitespace-nowrap',
                    positive ? POSITIVE_TEXT : 'text-rz-ink',
                )}
            >
                <span
                    className={cn(
                        'mr-0.5 text-[10.5px] font-bold',
                        positive ? POSITIVE_TEXT : 'text-rz-slate',
                    )}
                >
                    {positive
                        ? `+${t('investor.money.rwf')}`
                        : t('investor.money.rwf')}
                </span>
                {amount}
            </p>
            {sub !== undefined && (
                <p className="mt-0.5 text-[10.5px] text-rz-secondary">{sub}</p>
            )}
        </div>
    );
}

function Metric({
    label,
    value,
    sub,
    valueClass = 'text-rz-ink',
    subClass = 'text-rz-secondary',
}: {
    label: string;
    value: ReactNode;
    sub: string;
    valueClass?: string;
    subClass?: string;
}) {
    return (
        <div className="min-w-0">
            <p className="text-[10px] font-bold tracking-[.03em] text-rz-slate uppercase">
                {label}
            </p>
            <div
                className={cn(
                    'mt-[3px] truncate text-[14.5px] font-bold tracking-[-.2px] whitespace-nowrap',
                    valueClass,
                )}
            >
                {value}
            </div>
            <p className={cn('mt-px text-[10px]', subClass)}>{sub}</p>
        </div>
    );
}

/** The holding's banner (design L1308–1321): back, identity, rating and health. */
export function HoldingBanner({
    holding,
    back,
    wide,
}: {
    holding: C3HoldingDetail;
    back: RouteLink;
    wide: boolean;
}) {
    const { t } = useTranslation();

    return (
        <div
            className={cn(
                'relative flex-none overflow-hidden',
                wide ? 'h-[116px] rounded-2xl' : 'h-[150px]',
            )}
            style={{ background: accentBanner(holding.accent) }}
        >
            <span className="absolute inset-0 bg-[linear-gradient(180deg,rgba(10,15,28,.32)_0%,rgba(10,15,28,0)_45%,rgba(10,15,28,.9)_100%)]" />
            <Link
                href={back}
                aria-label={t('investor.common.back')}
                className={cn(
                    'absolute left-4 z-[5] flex size-[38px] items-center justify-center rounded-[10px] border border-[#dbe3f0] bg-[rgba(8,11,20,.6)] text-lg text-white dark:border-white/20',
                    wide
                        ? 'top-3.5'
                        : 'top-[calc(env(safe-area-inset-top)+12px)]',
                )}
            >
                <span aria-hidden>←</span>
            </Link>
            <div
                className={cn(
                    'absolute bottom-3.5 left-[18px] flex items-center gap-3',
                    wide ? 'right-[356px]' : 'right-[18px]',
                )}
            >
                <span
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl bg-white/95 text-[19px] font-bold"
                    style={{ color: ACCENT_FILL[holding.accent] }}
                >
                    {holding.name.charAt(0)}
                </span>
                <div className="min-w-0 flex-1">
                    <div className="flex min-w-0 items-center gap-2">
                        <h1 className="min-w-0 truncate text-[17px] font-semibold text-white [text-shadow:0_1px_5px_rgba(0,0,0,.5)]">
                            {holding.name}
                        </h1>
                        <span className="flex shrink-0 items-center gap-1.5 rounded-full bg-[rgba(8,16,34,.62)] py-[3px] pr-[5px] pl-2.5 backdrop-blur-[8px]">
                            <span className="text-[11.5px] font-bold whitespace-nowrap text-white">
                                {t(`investor.rating.${holding.rating.band}`)}
                            </span>
                            <span
                                className="rounded-full px-2 py-px text-[11.5px] font-extrabold text-white"
                                style={{
                                    background:
                                        RATING_STYLE[holding.rating.band].plate,
                                }}
                            >
                                {holding.rating.score}
                            </span>
                        </span>
                    </div>
                    <div className="mt-[3px] flex min-w-0 items-center gap-[9px]">
                        <span className="min-w-0 truncate text-xs text-white [text-shadow:0_1px_4px_rgba(0,0,0,.5)]">
                            {holding.industry} · {holding.district}
                        </span>
                        <HealthPill
                            health={holding.health}
                            className="bg-white/15 py-[3px] font-semibold text-white dark:bg-white/15 dark:text-white"
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}

/**
 * The holding's own figures (design L1324–1398): money tiles, the payment metrics, a rating change,
 * a declared recovery plan and an arrears or default timeline (crosswalk SCR-05-ST-01..03), then
 * the photos the business filed. The design's "Sell on the market" card is secondary trading,
 * outside the MVP.
 */
export function HoldingFigures({ holding }: { holding: C3HoldingDetail }) {
    const { t, locale } = useTranslation();
    const rating = RATING_STYLE[holding.rating.band];

    return (
        <>
            <div className="grid grid-cols-2 gap-2">
                <MoneyTile
                    label={t('investor.holding.invested')}
                    amount={formatAmount(holding.invested)}
                />
                <MoneyTile
                    label={t('investor.holding.expected_profit')}
                    amount={formatAmount(holding.expected_profit)}
                    sub={t('investor.holding.yield', {
                        rate: holding.rate_pct,
                    })}
                    positive
                />
                <MoneyTile
                    label={t('investor.holding.total_at_maturity')}
                    amount={formatAmount(holding.maturity_value)}
                    sub={t('investor.holding.principal_yield')}
                />
                <MoneyTile
                    label={t('investor.holding.received')}
                    amount={formatAmount(holding.received)}
                    sub={t('investor.holding.payments', {
                        made: holding.payments_made,
                        total: holding.payments_total,
                    })}
                />
            </div>

            <div className="mt-3.5 grid grid-cols-2 gap-x-3 gap-y-[15px]">
                <Metric
                    label={t('investor.holding.next_payment')}
                    value={
                        holding.next_payment === null
                            ? '—'
                            : formatRwf(holding.next_payment.amount)
                    }
                    sub={
                        holding.next_payment === null
                            ? t(
                                  `investor.holding.no_next.${holding.health === 'matured' ? 'matured' : 'paused'}`,
                              )
                            : formatDate(holding.next_payment.due_on, locale)
                    }
                />
                <Metric
                    label={t('investor.holding.on_time')}
                    value={`${holding.on_time.on_time}/${holding.on_time.made}`}
                    sub={
                        holding.on_time.late === 0
                            ? t('investor.holding.all_on_time')
                            : t(
                                  holding.on_time.late === 1
                                      ? 'investor.holding.late_one'
                                      : 'investor.holding.late_other',
                                  { count: holding.on_time.late },
                              )
                    }
                    subClass={cn(
                        'font-semibold',
                        holding.on_time.late === 0
                            ? POSITIVE_TEXT
                            : 'text-rz-danger-text',
                    )}
                />
                <Metric
                    label={t('investor.holding.rating')}
                    value={
                        <span className="flex items-baseline gap-[5px]">
                            <span className={rating.text}>
                                {t(`investor.rating.${holding.rating.band}`)}
                            </span>
                            <span className={cn('text-[11px]', rating.text)}>
                                {holding.rating.score}
                            </span>
                        </span>
                    }
                    sub={t('investor.holding.out_of_five')}
                />
                <Metric
                    label={t('investor.holding.repaid')}
                    value={`${holding.repaid_pct}%`}
                    valueClass={POSITIVE_TEXT}
                    sub={t(
                        holding.months_left === 1
                            ? 'investor.holding.month_left_one'
                            : 'investor.holding.month_left_other',
                        { count: holding.months_left },
                    )}
                />
            </div>

            {holding.rating_change !== null && (
                <div className="mt-3.5 rounded-2xl border border-[#fbe4cc] bg-[#fff8f1] px-[15px] py-3.5 dark:border-[rgba(240,160,96,.3)] dark:bg-[rgba(240,160,96,.08)]">
                    <div className="flex items-center gap-[7px]">
                        <span
                            className={cn(
                                'flex size-[18px] items-center justify-center rounded-full bg-[rgba(194,102,31,.10)] text-[11px] font-extrabold',
                                AMBER_TEXT,
                            )}
                        >
                            !
                        </span>
                        <span className={cn('text-xs font-bold', AMBER_TEXT)}>
                            {t('investor.holding.rating_changed')}
                        </span>
                    </div>
                    <p className="mt-1.5 text-[12.5px] leading-normal text-[#7a6a55] dark:text-rz-body">
                        {t('investor.holding.rating_changed_body', {
                            from: `${t(`investor.rating.${holding.rating_change.from.band}`)} ${holding.rating_change.from.score}`,
                            to: `${t(`investor.rating.${holding.rating_change.to.band}`)} ${holding.rating_change.to.score}`,
                        })}
                    </p>
                    <ul className="mt-2.5 flex flex-col gap-[5px]">
                        {holding.rating_change.reasons.map((reason) => (
                            <li
                                key={reason.label}
                                className="flex items-baseline justify-between gap-2.5"
                            >
                                <span className="truncate text-[11.5px] text-[#7a6a55] dark:text-rz-body">
                                    {reason.label}
                                </span>
                                <span
                                    className={cn(
                                        'shrink-0 text-[11.5px] font-bold',
                                        reason.delta.startsWith('-')
                                            ? 'text-rz-danger-text'
                                            : POSITIVE_TEXT,
                                    )}
                                >
                                    {reason.delta}
                                </span>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-2.5 text-[11px] leading-[1.45] text-rz-ink">
                        {t('investor.holding.rating_changed_note')}
                    </p>
                </div>
            )}

            {holding.recovery_plan !== null && (
                <div className="mt-[18px] rounded-2xl border border-[#cfe9d8] bg-[#f0f9f3] p-4 dark:border-[rgba(63,205,160,.25)] dark:bg-[rgba(63,205,160,.08)]">
                    <div className="flex items-center gap-2.5">
                        <p className="min-w-0 flex-1 text-sm font-bold text-rz-ink">
                            {t('investor.holding.plan_title')}
                        </p>
                        <span
                            className={cn(
                                'shrink-0 rounded-[10px] px-[9px] py-1 text-[10px] font-bold',
                                holding.recovery_plan.state === 'on_track'
                                    ? cn(
                                          'bg-[rgba(29,158,117,.10)]',
                                          POSITIVE_TEXT,
                                      )
                                    : 'bg-[rgba(229,72,77,.10)] text-rz-danger-text',
                            )}
                        >
                            {t(
                                `investor.holding.plan_state.${holding.recovery_plan.state}`,
                            )}
                        </span>
                    </div>
                    <p className="mt-[9px] text-xs leading-[1.55] text-[#3d7a68] dark:text-[#3fcda0]">
                        {t('investor.holding.plan_body', {
                            name: holding.name,
                        })}
                    </p>
                    <dl className="mt-[13px] rounded-xl border border-[#dbeee3] bg-rz-surface px-[13px] py-0.5 dark:border-rz-border">
                        {(
                            [
                                [
                                    'investor.holding.plan_reason',
                                    holding.recovery_plan.reason,
                                ],
                                [
                                    'investor.holding.plan_arrives',
                                    formatDate(
                                        holding.recovery_plan.money_arrives,
                                        locale,
                                    ),
                                ],
                                [
                                    'investor.holding.plan_deferred',
                                    formatRwf(holding.recovery_plan.deferred),
                                ],
                            ] as const
                        ).map(([label, value], index) => (
                            <div
                                key={label}
                                className={cn(
                                    'flex justify-between gap-3 py-2.5',
                                    index < 2 &&
                                        'border-b border-[#eef2f9] dark:border-rz-divider',
                                )}
                            >
                                <dt className="text-[12.5px] text-rz-secondary">
                                    {t(label)}
                                </dt>
                                <dd className="text-right text-[12.5px] font-semibold text-rz-ink">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>
            )}

            {holding.arrears !== null && (
                <div className="mt-[18px] rounded-2xl border border-[#f3dfbf] bg-[#fff8ee] p-4 dark:border-[rgba(240,160,96,.3)] dark:bg-[rgba(240,160,96,.08)]">
                    <div className="flex items-center gap-[9px]">
                        <span className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-[rgba(194,102,31,.10)] text-[17px]">
                            <Icon name="hourglass" tone="amber" />
                        </span>
                        <div className="flex-1">
                            <p className="text-sm font-bold text-rz-ink">
                                {t(
                                    holding.health === 'defaulted'
                                        ? 'investor.holding.defaulted'
                                        : 'investor.holding.delayed',
                                )}
                            </p>
                            <p
                                className={cn(
                                    'text-[11.5px] font-semibold',
                                    AMBER_TEXT,
                                )}
                            >
                                {t('investor.holding.days_overdue', {
                                    count: holding.arrears.days_overdue,
                                })}
                            </p>
                        </div>
                        <span
                            className={cn(
                                'inline-flex shrink-0 items-center rounded-[10px] border border-[#f0d9ac] bg-[rgba(194,102,31,.10)] px-[9px] py-1 text-[10px] font-bold uppercase dark:border-[rgba(240,160,96,.3)]',
                                AMBER_TEXT,
                            )}
                        >
                            {t('investor.holding.in_recovery')}
                        </span>
                    </div>
                    <p className="mt-[11px] text-xs leading-[1.55] text-[#8a6d3b] dark:text-[#f0a060]">
                        {t('investor.holding.arrears_body', {
                            name: holding.name,
                        })}
                    </p>
                    <ol className="mt-[15px] flex flex-col">
                        {holding.arrears.steps.map((step, index, steps) => {
                            const last = index === steps.length - 1;

                            return (
                                <li key={step.key} className="flex gap-[11px]">
                                    <span className="flex shrink-0 flex-col items-center">
                                        <span
                                            className={cn(
                                                'flex size-5 items-center justify-center rounded-full border-2 text-[10px] font-bold',
                                                step.state === 'done'
                                                    ? cn(
                                                          'border-[#cfe9d8] bg-[rgba(29,158,117,.10)]',
                                                          POSITIVE_TEXT,
                                                      )
                                                    : step.state === 'current'
                                                      ? 'border-[#c2661f] bg-[#c2661f] text-white'
                                                      : 'border-[#f0dcb8] bg-rz-surface dark:border-rz-border',
                                            )}
                                        >
                                            {step.state === 'done' ? '✓' : ''}
                                        </span>
                                        {!last && (
                                            <span
                                                className={cn(
                                                    'min-h-3.5 w-0.5 flex-1',
                                                    step.state === 'done'
                                                        ? 'bg-[#17795a]'
                                                        : 'bg-[#f0dcb8] dark:bg-rz-border',
                                                )}
                                            />
                                        )}
                                    </span>
                                    <span className="flex-1 pb-3">
                                        <span
                                            className={cn(
                                                'block text-[12.5px] font-semibold',
                                                step.state === 'pending'
                                                    ? 'text-rz-secondary'
                                                    : 'text-rz-ink',
                                            )}
                                        >
                                            {t(
                                                `investor.holding.step.${step.key}.title`,
                                            )}
                                        </span>
                                        <span className="mt-px block text-[11px] leading-[1.4] text-rz-slate">
                                            {t(
                                                `investor.holding.step.${step.key}.body`,
                                            )}
                                        </span>
                                    </span>
                                </li>
                            );
                        })}
                    </ol>
                    <p className="mt-1 flex items-center gap-2 border-t border-[#f0e2c8] pt-3 text-[11px] leading-normal text-[#8a6d3b] dark:border-[rgba(240,160,96,.25)] dark:text-[#f0a060]">
                        <Icon name="shield" tone="amber" />
                        {t('investor.holding.claim_note')}
                    </p>
                </div>
            )}

            {holding.photos.length > 0 && (
                <>
                    <div className="mt-5 flex items-center justify-between gap-2">
                        <h2 className={HEADING}>
                            {t('investor.holding.photos')}
                        </h2>
                        <SourceBadge kind="business">
                            {t('investor.deal.reported_by_business')}
                        </SourceBadge>
                    </div>
                    <div className="rz-hscroll -mx-5 mt-2.5 flex gap-2.5 overflow-x-auto px-5 lg:-mx-[18px] lg:px-[18px]">
                        {holding.photos.map((photo) => (
                            <figure
                                key={photo.caption}
                                className="relative h-[120px] w-[180px] shrink-0 overflow-hidden rounded-2xl"
                            >
                                <PhotoFill
                                    photo={photo}
                                    accent={holding.accent}
                                />
                                <figcaption className="absolute inset-x-0 bottom-0 bg-[linear-gradient(180deg,rgba(10,15,28,0),rgba(10,15,28,.85))] px-[11px] pt-[18px] pb-2 text-left text-[11px] font-semibold text-white">
                                    {photo.caption}
                                </figcaption>
                            </figure>
                        ))}
                    </div>
                </>
            )}
        </>
    );
}

/**
 * How the Holding was issued, its repayment progress and the audited monthly updates (design
 * L1583–1642; C3 v2 §2d). Servicing figures keep their empty states until C4.
 */
export function HoldingSchedule({ holding }: { holding: C3HoldingDetail }) {
    const { t, locale } = useTranslation();
    const [update, setUpdate] = useState<MonthlyUpdateSummary | null>(null);

    return (
        <>
            <IssueRecord issue={holding.issue} />
            <h2 className={cn(HEADING, 'mt-[18px]')}>
                {t('investor.holding.progress')}
            </h2>
            <div className="mt-2.5 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                <div
                    role="progressbar"
                    aria-label={t('investor.holding.progress')}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={holding.repaid_pct}
                    className="h-[7px] overflow-hidden rounded-[4px] bg-rz-border"
                >
                    <div
                        className="h-full bg-[linear-gradient(90deg,#1e3aff,#17795a)]"
                        style={{ width: `${holding.repaid_pct}%` }}
                    />
                </div>
                <div className="mt-[9px] flex items-center justify-between">
                    <span className="text-xs font-semibold text-rz-secondary">
                        {t('investor.holding.payments_made', {
                            made: holding.payments_made,
                            total: holding.payments_total,
                        })}
                    </span>
                    <span className="text-xs text-rz-secondary">
                        {t('investor.holding.investors', {
                            count: holding.investors,
                        })}
                    </span>
                </div>
                <div className="mt-3 flex items-center justify-between border-t border-[#eef2f9] pt-3 dark:border-rz-divider">
                    <div>
                        <p className="text-[10px] font-semibold text-rz-secondary uppercase">
                            {t('investor.holding.next_payment')}
                        </p>
                        <p className="mt-0.5 text-sm font-semibold text-rz-ink">
                            {holding.next_payment === null
                                ? '—'
                                : formatRwf(holding.next_payment.amount)}
                        </p>
                    </div>
                    {holding.next_payment !== null && (
                        <span className="text-[12.5px] font-semibold text-rz-ink">
                            {formatDate(holding.next_payment.due_on, locale)}
                        </span>
                    )}
                </div>
            </div>
            <div className="mt-[18px] flex items-center gap-2">
                <h2 className={HEADING}>{t('investor.holding.updates')}</h2>
                <SourceBadge kind="audited">
                    {t('investor.deal.audited')}
                </SourceBadge>
            </div>
            <UpdateList
                updates={holding.updates}
                variant="phone"
                onOpen={setUpdate}
            />
            {update !== null && (
                <UpdateSheet update={update} onClose={() => setUpdate(null)} />
            )}
        </>
    );
}
