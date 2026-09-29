import { Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    ApplicationStateChip,
    DECISION_TONE,
} from '@/components/admin/applications/application-status';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import { avatarColor, initialOf } from '@/components/admin/format';
import { ReasonStage } from '@/components/admin/reason-stage';
import type { StageTone } from '@/components/admin/reason-stage';
import { TrailList } from '@/components/admin/trail-list';
import {
    CAPTION,
    EXPLAIN,
    RATING_TEXT,
    capacityFill,
    capacityText,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    ApplicationReview,
    EvidenceFactorKey,
    ReviewAction,
    StaffViewer,
} from '@/types/admin';

const FACTOR_FILL: Record<EvidenceFactorKey, string> = {
    repayment_history: 'bg-[#1d9e75]',
    revenue_consistency: 'bg-[#1e3aff] dark:bg-[#5b74ff]',
    statement_record: 'bg-[#7c3aed]',
    capacity_headroom: 'bg-[#c2661f]',
    sector_risk: 'bg-[#0891b2]',
};

const STAGE_TONE: Record<ReviewAction, StageTone> = {
    take: 'blue',
    approve: 'green',
    reject: 'red',
    info: 'purple',
    escalate: 'orange',
};

const TILE =
    'rounded-[13px] border border-[#eaeef6] bg-rz-surface dark:border-rz-border';

const APPROVE =
    'h-[46px] flex-1 rounded-xl bg-[#1d9e75] text-[14px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-45';

const TONAL_BUTTON =
    'h-10 flex-1 rounded-[11px] border border-rz-hairline bg-[#eef3fb] text-[12.5px] font-bold dark:bg-rz-surface-muted';

/**
 * The underwriting review (design T2920–3013). The engine's recommendation and evidence are
 * shown read-only — nobody types a score or a rating here (MVP-ADMIN-AC-04). Every decision,
 * including approval, carries a written reason (AC-01), and approval is blocked while the listing
 * audit is not sealed (MVP-ADMIN-SCR-02-ST-02).
 */
export function ReviewDrawer({
    review,
    viewer,
    initialStage,
}: {
    review: ApplicationReview;
    viewer: StaffViewer;
    initialStage: ReviewAction | null;
}) {
    const { t, locale } = useTranslation();
    const opening =
        initialStage === null ? undefined : review.actions[initialStage];
    const [stage, setStage] = useState<{
        key: ReviewAction;
        action: RouteAction;
    } | null>(
        initialStage !== null && opening !== undefined
            ? { key: initialStage, action: opening }
            : null,
    );
    const decision = DECISION_TONE[review.decision.code];
    const auditGap =
        review.audit.state === 'sealed' ? null : review.audit.state;
    const blocked = auditGap !== null;
    const { approve, reject, take, info, escalate } = review.actions;
    const decides = Object.keys(review.actions).length > 0;

    return (
        <Drawer
            label={t('admin.review.label', { business: review.business })}
            close={review.links.close}
        >
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto">
                <div className="bg-[#0d1a31] px-[26px] py-[22px] text-white">
                    <div className="flex items-center justify-between">
                        <span className="text-[11px] font-bold tracking-[.14em] text-[#8b96ff] uppercase">
                            {t('admin.review.eyebrow')}
                        </span>
                        <DrawerClose
                            close={review.links.close}
                            variant="dark"
                        />
                    </div>
                    <div className="mt-4 flex items-center gap-3.5">
                        <div
                            className="flex size-[52px] shrink-0 items-center justify-center rounded-[14px] text-[20px] font-bold text-white"
                            style={{ background: avatarColor(review.id) }}
                        >
                            {initialOf(review.business)}
                        </div>
                        <div className="min-w-0 flex-1">
                            <h2 className="truncate text-[19px] font-bold">
                                {review.business}
                            </h2>
                            <p className="truncate text-[13px] text-[#9fb2d4]">
                                {review.note_title} · {review.sector}
                            </p>
                        </div>
                        <ApplicationStateChip
                            state={review.state}
                            className="text-[11px] font-bold"
                        />
                    </div>
                </div>

                <div className="px-5 py-[22px] lg:px-[26px]">
                    <div
                        className={cn(
                            'rounded-[14px] border border-[rgba(0,0,0,.04)] px-[17px] py-[15px]',
                            decision.box,
                        )}
                    >
                        <div
                            className={cn(
                                'text-[14px] font-bold',
                                decision.text,
                            )}
                        >
                            {t(
                                `admin.review.recommend.${review.decision.code}`,
                            )}
                        </div>
                        <p
                            className={cn(
                                'mt-[3px] text-[12.5px] leading-[1.5]',
                                EXPLAIN,
                            )}
                        >
                            {review.decision.reason}
                        </p>
                    </div>

                    {auditGap !== null && (
                        <div
                            id="review-audit-block"
                            role="status"
                            className="mt-3.5 flex gap-2.5 rounded-[14px] border border-[#fdeaea] bg-[rgba(255,77,79,.06)] px-[17px] py-3.5 dark:border-[rgba(255,107,111,.25)]"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden
                                className="mt-0.5 shrink-0"
                            >
                                <rect
                                    x="4.5"
                                    y="10.5"
                                    width="15"
                                    height="10"
                                    rx="2.5"
                                    stroke="#e5484d"
                                    strokeWidth="1.7"
                                />
                                <path
                                    d="M8 10.5V8a4 4 0 0 1 8 0v2.5"
                                    stroke="#e5484d"
                                    strokeWidth="1.7"
                                />
                            </svg>
                            <div>
                                <div className="text-[13px] font-bold text-[#e5484d] dark:text-[#ff6b6f]">
                                    {t(`admin.review.audit.${auditGap}_title`)}
                                </div>
                                <p
                                    className={cn(
                                        'mt-0.5 text-[12.5px] leading-[1.5]',
                                        EXPLAIN,
                                    )}
                                >
                                    {t(`admin.review.audit.${auditGap}_body`)}
                                </p>
                            </div>
                        </div>
                    )}

                    <dl className="mt-[18px] grid grid-cols-2 gap-3">
                        <div className={cn(TILE, 'px-4 py-3.5')}>
                            <dt
                                className={cn(
                                    'text-[11px] font-semibold',
                                    CAPTION,
                                )}
                            >
                                {t('admin.review.requested')}
                            </dt>
                            <dd className="mt-1 text-[18px] font-bold text-rz-ink">
                                {formatRwfShort(review.requested)}
                            </dd>
                        </div>
                        <div className={cn(TILE, 'px-4 py-3.5')}>
                            <dt
                                className={cn(
                                    'text-[11px] font-semibold',
                                    CAPTION,
                                )}
                            >
                                {t('admin.review.rating')}
                            </dt>
                            <dd className="mt-1 text-[18px] font-bold text-rz-ink">
                                {review.rating === null ? (
                                    t('admin.rating.pending')
                                ) : (
                                    <>
                                        <span
                                            className={
                                                RATING_TEXT[review.rating.band]
                                            }
                                        >
                                            {t(
                                                `admin.rating.band.${review.rating.band}`,
                                            )}
                                        </span>
                                        <span className="text-[13px] font-semibold text-rz-group">
                                            {' '}
                                            {t('admin.review.rating_score', {
                                                score: review.rating.score,
                                            })}
                                        </span>
                                    </>
                                )}
                            </dd>
                        </div>
                        <div className={cn(TILE, 'px-4 py-3.5')}>
                            <dt
                                className={cn(
                                    'text-[11px] font-semibold',
                                    CAPTION,
                                )}
                            >
                                {t('admin.review.term')}
                            </dt>
                            <dd className="mt-1 text-[15px] font-bold text-rz-ink">
                                {t('admin.review.term_months', {
                                    count: review.term_months,
                                })}
                            </dd>
                        </div>
                        <div className={cn(TILE, 'px-4 py-3.5')}>
                            <dt
                                className={cn(
                                    'text-[11px] font-semibold',
                                    CAPTION,
                                )}
                            >
                                {t('admin.review.yield')}
                            </dt>
                            <dd className="mt-1 text-[15px] font-bold text-[#1d9e75] dark:text-[#3fcda0]">
                                {t('admin.review.yield_value', {
                                    rate: review.rate_pct,
                                })}
                            </dd>
                        </div>
                    </dl>

                    <div className={cn(TILE, 'mt-4 px-[17px] py-[15px]')}>
                        <div className="flex items-center justify-between">
                            <span className="text-[12.5px] font-semibold text-rz-slate">
                                {t('admin.review.capacity_title')}
                            </span>
                            <span
                                className={cn(
                                    'text-[12.5px] font-bold',
                                    capacityText(review.capacity.used_pct),
                                )}
                            >
                                {review.capacity.used_pct}%
                            </span>
                        </div>
                        <div
                            role="progressbar"
                            aria-label={t('admin.review.capacity_title')}
                            aria-valuenow={review.capacity.used_pct}
                            aria-valuemin={0}
                            aria-valuemax={100}
                            className="mt-[9px] h-2 overflow-hidden rounded-[5px] bg-[#e8edf5] dark:bg-rz-surface-muted"
                        >
                            <div
                                className={cn(
                                    'h-full rounded-[5px]',
                                    capacityFill(review.capacity.used_pct),
                                )}
                                style={{
                                    width: `${Math.max(4, Math.min(100, review.capacity.used_pct))}%`,
                                }}
                            />
                        </div>
                        <div
                            className={cn(
                                'mt-[9px] flex flex-wrap justify-between gap-1 text-[11.5px]',
                                CAPTION,
                            )}
                        >
                            <span>
                                {t('admin.review.capacity_approved', {
                                    amount: formatRwfShort(
                                        review.capacity.approved,
                                    ),
                                })}
                            </span>
                            <span>
                                {t('admin.review.capacity_existing', {
                                    count: review.capacity.active_notes,
                                    amount: formatRwfShort(
                                        review.capacity.outstanding,
                                    ),
                                })}
                            </span>
                        </div>
                    </div>

                    <section
                        aria-label={t('admin.review.factors_title')}
                        className={cn(TILE, 'mt-4 px-[17px] py-[15px]')}
                    >
                        <div className="mb-3 flex items-baseline justify-between gap-2">
                            <h3 className="text-[12.5px] font-bold text-rz-slate">
                                {t('admin.review.factors_title')}
                            </h3>
                            <span className={cn('text-[11px]', CAPTION)}>
                                {t('admin.review.factors_caption')}
                            </span>
                        </div>
                        {review.factors.map((factor) => (
                            <div
                                key={factor.key}
                                className="mb-[11px] last:mb-0"
                            >
                                <div className="mb-1 flex justify-between">
                                    <span
                                        className={cn('text-[12px]', EXPLAIN)}
                                    >
                                        {t(`admin.review.factor.${factor.key}`)}
                                    </span>
                                    <span className="text-[12px] font-bold text-rz-ink">
                                        {factor.value}
                                    </span>
                                </div>
                                <div className="h-1.5 overflow-hidden rounded-[3px] bg-[#eef2f8] dark:bg-rz-surface-muted">
                                    <div
                                        className={cn(
                                            'h-full rounded-[3px]',
                                            FACTOR_FILL[factor.key],
                                        )}
                                        style={{ width: `${factor.value}%` }}
                                    />
                                </div>
                            </div>
                        ))}
                    </section>

                    <div className={cn(TILE, 'mt-4 px-[17px] py-[15px]')}>
                        <div
                            className={cn('text-[11px] font-semibold', CAPTION)}
                        >
                            {t('admin.review.use_of_funds')}
                        </div>
                        <p className="mt-[5px] text-[13px] leading-[1.55] text-[#2c3a52] dark:text-rz-slate">
                            {review.use_of_funds}
                        </p>
                        <div
                            className={cn(
                                'mt-3 flex flex-wrap justify-between gap-1 text-[11.5px]',
                                CAPTION,
                            )}
                        >
                            <span>
                                {t('admin.review.submitted', {
                                    date: formatDate(
                                        review.submitted_at,
                                        locale,
                                    ),
                                })}
                            </span>
                            <span>
                                {t('admin.review.reviewer', {
                                    name:
                                        review.reviewer ??
                                        t('admin.review.unassigned'),
                                })}
                            </span>
                        </div>
                    </div>

                    <Link
                        href={review.links.business}
                        className="mt-3.5 block w-full rounded-[11px] border border-rz-hairline bg-rz-surface p-[11px] text-center text-[13px] font-bold text-rz-accent-app-text"
                    >
                        {t('admin.review.business_profile')}
                    </Link>

                    <TrailList
                        title={t('admin.review.trail')}
                        entries={review.trail}
                        empty={t('admin.review.trail_empty')}
                    />

                    {decides && stage === null && (
                        <div className="mt-[18px] border-t border-rz-hairline pt-[18px]">
                            <div className="flex gap-2.5">
                                {approve ? (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setStage({
                                                key: 'approve',
                                                action: approve,
                                            })
                                        }
                                        className={APPROVE}
                                    >
                                        {t('admin.review.approve')}
                                    </button>
                                ) : (
                                    <button
                                        type="button"
                                        disabled
                                        aria-describedby={
                                            blocked
                                                ? 'review-audit-block'
                                                : undefined
                                        }
                                        className={APPROVE}
                                    >
                                        {t('admin.review.approve')}
                                    </button>
                                )}
                                {reject && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setStage({
                                                key: 'reject',
                                                action: reject,
                                            })
                                        }
                                        className="h-[46px] flex-1 rounded-xl border border-[#fdd9da] bg-rz-surface text-[14px] font-bold text-[#e5484d] dark:border-[rgba(255,107,111,.3)] dark:text-[#ff6b6f]"
                                    >
                                        {t('admin.review.reject')}
                                    </button>
                                )}
                            </div>
                            <div className="mt-2.5 flex gap-2.5">
                                {take && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setStage({
                                                key: 'take',
                                                action: take,
                                            })
                                        }
                                        className={cn(
                                            TONAL_BUTTON,
                                            'text-rz-slate',
                                        )}
                                    >
                                        {t('admin.review.take')}
                                    </button>
                                )}
                                {info && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setStage({
                                                key: 'info',
                                                action: info,
                                            })
                                        }
                                        className={cn(
                                            TONAL_BUTTON,
                                            'text-rz-slate',
                                        )}
                                    >
                                        {t('admin.review.info')}
                                    </button>
                                )}
                                {escalate && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setStage({
                                                key: 'escalate',
                                                action: escalate,
                                            })
                                        }
                                        className={cn(
                                            TONAL_BUTTON,
                                            'text-[#c2410c] dark:text-[#f59a6b]',
                                        )}
                                    >
                                        {t('admin.review.escalate')}
                                    </button>
                                )}
                            </div>
                        </div>
                    )}

                    {stage !== null && (
                        <ReasonStage
                            key={stage.key}
                            title={t(`admin.review.stage.${stage.key}.title`)}
                            body={t(`admin.review.stage.${stage.key}.body`, {
                                amount: formatRwfShort(review.requested),
                                rate: review.rate_pct,
                                term: review.term_months,
                            })}
                            cta={t(`admin.review.stage.${stage.key}.cta`)}
                            placeholder={t(
                                `admin.review.stage.${stage.key}.placeholder`,
                            )}
                            tone={STAGE_TONE[stage.key]}
                            action={stage.action}
                            viewer={viewer}
                            onCancel={() => setStage(null)}
                        />
                    )}
                </div>
            </div>
        </Drawer>
    );
}
