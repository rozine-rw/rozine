import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { ReasonStage } from '@/components/admin/reason-stage';
import {
    CARD,
    CARD_SHADOW,
    CardTitle,
    EmptyState,
    KpiTile,
    TONE_DOT,
    useStatFormatter,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteAction, RouteLink } from '@/types';
import type {
    AttentionKey,
    AttentionTile,
    ReconciliationBreak,
    StaffViewer,
    Tone,
    TodayKpi,
} from '@/types/admin';

const RED = 'text-[#e5484d] dark:text-[#ff6b6f]';
const GREEN = 'text-[#1d9e75] dark:text-[#3fcda0]';

/** The trend line under each figure, and when it is a warning. */
function useTrend(): (kpi: TodayKpi) => { text: string; tone?: string } {
    const { t } = useTranslation();

    return (kpi) => {
        switch (kpi.key) {
            case 'treasury_position':
                return kpi.negative
                    ? {
                          text: t('admin.today.kpi.treasury_negative'),
                          tone: RED,
                      }
                    : {
                          text: t('admin.today.kpi.treasury_positive'),
                          tone: GREEN,
                      };
            case 'default_rate':
                return {
                    text: t('admin.today.kpi.default_rate_trend', {
                        failed: kpi.failed,
                        total: kpi.total,
                    }),
                    tone: kpi.breach ? RED : GREEN,
                };
            default:
                return { text: t(`admin.today.kpi.${kpi.key}_trend`) };
        }
    };
}

/** Headline figures. The design's "Trust Index™" tile is dropped: Rozine publishes one score. */
export function TodayKpis({ kpis }: { kpis: TodayKpi[] }) {
    const { t } = useTranslation();
    const format = useStatFormatter();
    const trend = useTrend();

    return (
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
            {kpis.map((kpi, index) => {
                const line = trend(kpi);

                return (
                    <KpiTile
                        key={kpi.key}
                        index={index}
                        label={t(`admin.today.kpi.${kpi.key}`)}
                        value={format(kpi.value)}
                        sub={line.text}
                        subClassName={line.tone}
                        inlineLabel
                    />
                );
            })}
        </div>
    );
}

const ATTENTION_TONE: Record<AttentionKey, Tone> = {
    applications_pending: 'blue',
    kyc_awaiting: 'blue',
    notes_late: 'amber',
    notes_default_risk: 'red',
    frozen_accounts: 'grey',
};

const ATTENTION_HOVER: Record<Tone, string> = {
    blue: 'hover:border-[#1e3aff]',
    amber: 'hover:border-[#c2661f]',
    red: 'hover:border-[#e5484d]',
    grey: 'hover:border-[#69748a]',
    green: 'hover:border-[#1d9e75]',
    purple: 'hover:border-[#7c3aed]',
};

/** What needs a human: each queue's size, opening the queue when its screen exists. */
export function AttentionTiles({ tiles }: { tiles: AttentionTile[] }) {
    const { t } = useTranslation();

    return (
        <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            {tiles.map((tile) => {
                const tone = ATTENTION_TONE[tile.key];
                const body = (
                    <>
                        <span className="flex items-center gap-[7px]">
                            <span
                                className={cn(
                                    'size-[7px] shrink-0 rounded-[3px]',
                                    TONE_DOT[tone],
                                )}
                            />
                            <span className="text-[22px] leading-none font-extrabold tracking-[-.02em] text-rz-ink">
                                {tile.count}
                            </span>
                        </span>
                        <span className="text-[11.5px] leading-[1.3] font-semibold text-[#6b7688] dark:text-rz-muted">
                            {t(`admin.today.attention.${tile.key}`)}
                        </span>
                    </>
                );
                const box = cn(
                    'flex flex-col gap-1.5 rounded-2xl p-4 text-left shadow-[0_1px_2px_rgba(16,32,58,.04)] transition-[transform,border-color] duration-100 dark:shadow-none',
                    CARD,
                );

                return tile.link === null ? (
                    <div key={tile.key} className={box}>
                        {body}
                    </div>
                ) : (
                    <Link
                        key={tile.key}
                        href={tile.link}
                        className={cn(
                            box,
                            'hover:-translate-y-0.5',
                            ATTENTION_HOVER[tone],
                        )}
                    >
                        {body}
                    </Link>
                );
            })}
        </div>
    );
}

/** Reconciliation breaks: aged, owned or unowned, and never hidden (MVP-ADMIN-SCR-01-ST-02). */
export function BreaksCard({
    breaks,
    viewer,
    ledger,
}: {
    breaks: ReconciliationBreak[];
    viewer: StaffViewer;
    ledger: RouteLink;
}) {
    const { t } = useTranslation();
    const [stage, setStage] = useState<{
        id: string;
        kind: 'assign' | 'escalate';
        action: RouteAction;
    } | null>(null);

    return (
        <section aria-label={t('admin.today.breaks.title')} className="mt-4">
            <div className="flex flex-wrap items-baseline justify-between gap-2">
                <CardTitle>{t('admin.today.breaks.title')}</CardTitle>
                <Link
                    href={ledger}
                    className="text-[12px] font-bold text-rz-accent-app-text"
                >
                    {t('admin.today.breaks.ledger')}
                </Link>
            </div>
            <p className="mt-1 text-[12px] text-rz-muted">
                {t('admin.today.breaks.caption')}
            </p>
            <div
                className={cn(
                    'mt-3 overflow-hidden rounded-[20px]',
                    CARD,
                    CARD_SHADOW,
                )}
            >
                {breaks.length === 0 ? (
                    <EmptyState
                        title={t('admin.today.breaks.empty_title')}
                        body={t('admin.today.breaks.empty_body')}
                    />
                ) : (
                    breaks.map((item) => {
                        const open = stage?.id === item.id ? stage : null;
                        const { assign, escalate } = item.actions;

                        return (
                            <div
                                key={item.id}
                                className="border-b border-[#eef2f8] px-5 py-3.5 last:border-b-0 dark:border-rz-divider"
                            >
                                <div className="flex flex-wrap items-center gap-x-3.5 gap-y-2">
                                    <span className="shrink-0 font-mono text-[12px] text-rz-body">
                                        {item.reference}
                                    </span>
                                    <span className="min-w-[160px] flex-1 text-[13px] font-semibold text-rz-ink">
                                        {item.description}
                                        <span className="mt-0.5 block text-[11px] font-medium text-rz-muted">
                                            {t('admin.today.breaks.age', {
                                                days: item.age_days,
                                            })}
                                            {' · '}
                                            {item.owner === null
                                                ? t(
                                                      'admin.today.breaks.unassigned',
                                                  )
                                                : t(
                                                      'admin.today.breaks.owner',
                                                      {
                                                          name: item.owner
                                                              .actor,
                                                      },
                                                  )}
                                        </span>
                                    </span>
                                    <span
                                        className={cn(
                                            'text-[13px] font-bold',
                                            RED,
                                        )}
                                    >
                                        {formatRwf(item.gap)}
                                    </span>
                                    <div className="flex shrink-0 gap-1.5">
                                        {assign && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setStage({
                                                        id: item.id,
                                                        kind: 'assign',
                                                        action: assign,
                                                    })
                                                }
                                                className="rounded-lg bg-rz-accent-fill px-3 py-1.5 text-[11.5px] font-semibold text-white"
                                            >
                                                {t('admin.today.breaks.assign')}
                                            </button>
                                        )}
                                        {escalate && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setStage({
                                                        id: item.id,
                                                        kind: 'escalate',
                                                        action: escalate,
                                                    })
                                                }
                                                className="rounded-lg border border-[#f6e7c8] bg-rz-surface px-[11px] py-1.5 text-[11.5px] font-semibold text-[#c2410c] dark:border-rz-border dark:text-[#f0a060]"
                                            >
                                                {t(
                                                    'admin.today.breaks.escalate',
                                                )}
                                            </button>
                                        )}
                                    </div>
                                </div>
                                {open !== null && (
                                    <ReasonStage
                                        key={open.kind}
                                        title={t(
                                            `admin.today.breaks.${open.kind}_title`,
                                            {
                                                reference: item.reference,
                                            },
                                        )}
                                        body={t(
                                            `admin.today.breaks.${open.kind}_body`,
                                        )}
                                        cta={t(
                                            `admin.today.breaks.${open.kind}_cta`,
                                        )}
                                        placeholder={t(
                                            `admin.today.breaks.${open.kind}_placeholder`,
                                        )}
                                        tone={
                                            open.kind === 'assign'
                                                ? 'blue'
                                                : 'orange'
                                        }
                                        action={open.action}
                                        viewer={viewer}
                                        onCancel={() => setStage(null)}
                                    />
                                )}
                            </div>
                        );
                    })
                )}
            </div>
        </section>
    );
}
