import type { ReactNode } from 'react';
import { useAgo } from '@/components/auditor/clock';
import { compactRwf } from '@/components/auditor/money';
import { DIVIDER, Eyebrow, Tick } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatMonthYearLong } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AuditorActivity } from '@/types/auditor';

const GREEN_TILE = 'bg-[rgba(29,158,117,.10)]';

const ReportGlyph = (
    <svg viewBox="0 0 24 24" fill="none" aria-hidden className="size-4">
        <rect
            x="5"
            y="3.5"
            width="14"
            height="17"
            rx="2.4"
            stroke="currentColor"
            strokeWidth="1.8"
        />
        <path
            d="M8.6 9h6.8M8.6 13h6.8M8.6 17h4"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
        />
    </svg>
);

const MissGlyph = (
    <svg viewBox="0 0 24 24" fill="none" aria-hidden className="size-4">
        <circle
            cx="12"
            cy="12"
            r="8.4"
            stroke="currentColor"
            strokeWidth="1.8"
        />
        <path
            d="M12 7.6v5.2M12 16.2v.2"
            stroke="currentColor"
            strokeWidth="2.2"
            strokeLinecap="round"
        />
    </svg>
);

const PlanGlyph = (
    <svg viewBox="0 0 24 24" fill="none" aria-hidden className="size-4">
        <path
            d="M4 12a8 8 0 0 1 13.7-5.6"
            stroke="currentColor"
            strokeWidth="1.9"
            strokeLinecap="round"
        />
        <path
            d="M20 12a8 8 0 0 1-13.7 5.6"
            stroke="currentColor"
            strokeWidth="1.9"
            strokeLinecap="round"
        />
        <path
            d="M18 3.4v3.4h-3.4M6 20.6v-3.4h3.4"
            stroke="currentColor"
            strokeWidth="1.9"
            strokeLinecap="round"
            strokeLinejoin="round"
        />
    </svg>
);

type Row = {
    tile: string;
    icon: ReactNode;
    title: string;
    sub: string;
    amount?: { text: string; tone: string };
};

/** Recent activity (design L187–197, rows L3060–3109), newest first as the server orders it. */
export function ActivityList({
    activity,
    serverTime,
}: {
    activity: AuditorActivity[];
    serverTime: string;
}) {
    const { t, locale } = useTranslation();
    const ago = useAgo(serverTime);

    const row = (item: AuditorActivity): Row => {
        const when = ago(item.at);

        switch (item.kind) {
            case 'report_filed':
                return {
                    tile: `${GREEN_TILE} text-rz-positive`,
                    icon: <Tick className="size-4" />,
                    title: t('auditor.activity.filed', {
                        business: item.business,
                    }),
                    sub:
                        item.variance_pct === null
                            ? when
                            : t('auditor.activity.filed_sub', {
                                  when,
                                  variance: item.variance_pct,
                              }),
                };
            case 'report_published':
                return {
                    tile: 'bg-[rgba(30,58,255,.1)] text-[#1e3aff] dark:text-rz-investor-text',
                    icon: ReportGlyph,
                    title: t('auditor.activity.published', {
                        month: formatMonthYearLong(item.month, locale),
                    }),
                    sub: `${when} · ${item.business}`,
                };
            case 'repayment_received':
                return {
                    tile: `${GREEN_TILE} text-rz-positive`,
                    icon: <Tick className="size-4" />,
                    title: t('auditor.activity.repayment'),
                    sub: `${when} · ${item.business}`,
                    amount: {
                        text: compactRwf(item.amount),
                        tone: 'text-rz-secondary',
                    },
                };
            case 'repayment_missed':
                return {
                    tile: 'bg-[rgba(192,57,43,.10)] text-rz-danger-text',
                    icon: MissGlyph,
                    title: t('auditor.activity.missed', {
                        business: item.business,
                    }),
                    sub: t('auditor.activity.missed_sub', { when }),
                };
            case 'payment_deferred':
                return {
                    tile: 'bg-rz-accent-soft text-rz-ink',
                    icon: PlanGlyph,
                    title: t('auditor.activity.deferred', {
                        business: item.business,
                    }),
                    sub: `${when} · ${item.cause.toLowerCase()}`,
                };
            case 'payout':
                return {
                    tile: `${GREEN_TILE} text-rz-positive`,
                    icon: (
                        <span
                            aria-hidden
                            className="text-[13px] font-extrabold"
                        >
                            %
                        </span>
                    ),
                    title: t('auditor.activity.payout'),
                    sub: when,
                    amount: {
                        text: `+${compactRwf(item.amount)}`,
                        tone: 'text-rz-positive',
                    },
                };
        }
    };

    return (
        <section>
            <Eyebrow as="h2" className="mt-5 lg:mt-0">
                {t('auditor.activity.title')}
            </Eyebrow>
            <div className="mt-2.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {activity.length === 0 && (
                    <p className="px-3.5 py-5 text-center text-[12.5px] text-rz-secondary">
                        {t('auditor.activity.empty')}
                    </p>
                )}
                <ul>
                    {activity.map((item, index) => {
                        const view = row(item);

                        return (
                            <li
                                key={`${item.kind}-${item.at}-${index}`}
                                className={cn(
                                    'flex items-center gap-3 border-b px-3.5 py-3',
                                    DIVIDER,
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex size-8 shrink-0 items-center justify-center rounded-[10px]',
                                        view.tile,
                                    )}
                                >
                                    {view.icon}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-[13px] font-semibold text-rz-ink">
                                        {view.title}
                                    </span>
                                    <span className="mt-px block text-[11px] text-rz-secondary">
                                        {view.sub}
                                    </span>
                                </span>
                                {view.amount && (
                                    <span
                                        className={cn(
                                            'shrink-0 text-[12.5px] font-bold',
                                            view.amount.tone,
                                        )}
                                    >
                                        {view.amount.text}
                                    </span>
                                )}
                            </li>
                        );
                    })}
                </ul>
            </div>
        </section>
    );
}
