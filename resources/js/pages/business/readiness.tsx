import { BusinessShell } from '@/components/business/business-shell';
import { useTranslation } from '@/hooks/use-translation';
import { formatMonthYearLong, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    BusinessReadinessProps,
    ReadinessStageNumber,
} from '@/types/business';

const COLUMN =
    'lg:min-h-0 lg:min-w-0 lg:flex-[0_0_calc(50%-8px)] lg:overflow-y-auto lg:rounded-2xl lg:border lg:border-rz-border lg:bg-rz-surface';

const STAGE_KEY = {
    0: 'business.readiness.stage.0',
    1: 'business.readiness.stage.1',
    2: 'business.readiness.stage.2',
    3: 'business.readiness.stage.3',
    4: 'business.readiness.stage.4',
} as const satisfies Record<ReadinessStageNumber, string>;

/**
 * Readiness (BRS v3.0 Section 20, APP-B01): the stage the business's verified monthly revenue has
 * settled in, with the RDY-R05 status line beside it, what is still pending, how far the next
 * stage is, the four stages and the months counted. Every line states a fact; none asks a
 * question. Readiness is shown only inside the Business app.
 */
export default function BusinessReadiness({
    readiness,
    links,
}: BusinessReadinessProps) {
    const { t, locale } = useTranslation();
    const stageName = (stage: ReadinessStageNumber) => t(STAGE_KEY[stage]);
    const { stage, vmr, pending, next } = readiness;

    return (
        <BusinessShell
            title={t('business.readiness.title')}
            tab="readiness"
            links={links}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+4px)] pb-[92px] lg:flex lg:h-full lg:gap-4 lg:px-5 lg:pt-4 lg:pb-5">
                <section
                    data-rzcol
                    aria-labelledby="readiness-title"
                    className={cn(COLUMN, 'rz-scroll lg:p-[18px]')}
                >
                    <h1
                        id="readiness-title"
                        className="text-2xl font-semibold text-rz-ink"
                    >
                        {t('business.readiness.title')}
                    </h1>
                    <p className="mt-1 text-[13.5px] text-rz-secondary">
                        {t('business.readiness.subtitle')}
                    </p>
                    <div className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4">
                        <div className="flex items-center justify-between gap-3">
                            <p className="text-[19px] font-semibold text-rz-ink">
                                {stage === null
                                    ? t('business.readiness.stage_pending')
                                    : stageName(stage)}
                            </p>
                            <span
                                className={cn(
                                    'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    readiness.status === 'ready'
                                        ? 'bg-rz-accent-fill text-white'
                                        : 'bg-rz-accent-soft text-rz-accent-app-text',
                                )}
                            >
                                {t(
                                    `business.readiness.status.${readiness.status}`,
                                )}
                            </span>
                        </div>
                        {vmr === null ? (
                            <p className="mt-2 text-[13px] text-rz-secondary">
                                {t('business.readiness.reports_needed', {
                                    count: readiness.reports_needed,
                                })}
                            </p>
                        ) : (
                            <dl className="mt-3 border-t border-[#eef2f9] pt-3 dark:border-rz-divider">
                                <dt className="text-[11px] font-semibold text-rz-secondary">
                                    {t('business.readiness.vmr')}
                                </dt>
                                <dd className="mt-0.5 text-[17px] font-semibold text-rz-ink">
                                    {formatRwfShort(vmr)}
                                </dd>
                                <dd className="mt-0.5 text-[12px] text-rz-secondary">
                                    {t('business.readiness.vmr_basis')}
                                </dd>
                            </dl>
                        )}
                    </div>
                    <ul className="mt-3 space-y-2 text-[13px] text-rz-ink">
                        {pending !== null && (
                            <li className="rounded-xl bg-[rgba(194,102,31,.10)] px-3.5 py-2.5">
                                {t(
                                    `business.readiness.pending.${pending.direction}`,
                                    {
                                        count: pending.months,
                                        stage: stageName(pending.stage),
                                        needed: readiness.window,
                                    },
                                )}
                            </li>
                        )}
                        {next !== null && (
                            <li className="rounded-xl bg-rz-page px-3.5 py-2.5">
                                {next.gap.amount === '0'
                                    ? t('business.readiness.next_reached', {
                                          stage: stageName(next.stage),
                                          threshold: formatRwfShort(
                                              next.threshold,
                                          ),
                                          needed: readiness.window,
                                      })
                                    : t('business.readiness.next', {
                                          stage: stageName(next.stage),
                                          threshold: formatRwfShort(
                                              next.threshold,
                                          ),
                                          gap: formatRwfShort(next.gap),
                                      })}
                            </li>
                        )}
                        {stage === 4 && (
                            <li className="rounded-xl bg-rz-page px-3.5 py-2.5">
                                {t('business.readiness.top')}
                            </li>
                        )}
                        <li className="px-1 text-[12.5px] text-rz-secondary">
                            {t('business.readiness.ready_rule')}
                        </li>
                    </ul>
                </section>
                <section
                    data-rzcol
                    aria-labelledby="readiness-stages-title"
                    className={cn(COLUMN, 'rz-scroll mt-6 lg:mt-0 lg:p-[18px]')}
                >
                    <h2
                        id="readiness-stages-title"
                        className="text-base font-semibold text-rz-ink"
                    >
                        {t('business.readiness.stages')}
                    </h2>
                    <ol className="mt-3 space-y-2">
                        {readiness.thresholds.map((row) => (
                            <li
                                key={row.stage}
                                aria-current={
                                    row.stage === stage ? 'step' : undefined
                                }
                                className={cn(
                                    'flex items-center justify-between gap-3 rounded-2xl border p-3.5',
                                    row.stage === stage
                                        ? 'border-rz-accent-fill bg-rz-accent-soft'
                                        : 'border-rz-border bg-rz-surface',
                                )}
                            >
                                <span>
                                    <span className="block text-[14px] font-semibold text-rz-ink">
                                        {stageName(row.stage)}
                                    </span>
                                    <span className="block text-[12px] text-rz-secondary">
                                        {t('business.readiness.stage_from', {
                                            amount: formatRwfShort(row.from),
                                        })}
                                    </span>
                                </span>
                                {row.stage === stage && (
                                    <span className="text-[11px] font-semibold text-rz-accent-app-text">
                                        {t('business.readiness.current')}
                                    </span>
                                )}
                            </li>
                        ))}
                    </ol>
                    <h2 className="mt-6 text-base font-semibold text-rz-ink">
                        {t('business.readiness.months')}
                    </h2>
                    {readiness.months.length === 0 ? (
                        <p className="mt-2 text-[13px] text-rz-secondary">
                            {t('business.readiness.months_empty')}
                        </p>
                    ) : (
                        <dl className="mt-2 divide-y divide-rz-divider rounded-2xl border border-rz-border bg-rz-surface">
                            {readiness.months.map((month) => (
                                <div
                                    key={month.period}
                                    className="flex items-center justify-between px-3.5 py-2.5"
                                >
                                    <dt className="text-[13px] text-rz-secondary">
                                        {formatMonthYearLong(
                                            `${month.period}-01`,
                                            locale,
                                        )}
                                    </dt>
                                    <dd className="text-[13px] font-semibold text-rz-ink">
                                        {formatRwfShort(month.revenue)}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    )}
                </section>
            </div>
        </BusinessShell>
    );
}
