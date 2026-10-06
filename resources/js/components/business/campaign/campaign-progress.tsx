import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Tile } from '@/components/business/note/progress-summary';
import { PollStopped } from '@/components/rozine/c3-notice';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { timeLeft, useServerNow } from '@/lib/investor/server-clock';
import {
    formatCount,
    formatDate,
    formatDateTime,
    formatRwf,
    formatRwfShort,
} from '@/lib/rozine/format';
import { isReadableProgress, SETTLING } from '@/lib/rozine/raising';
import { cn } from '@/lib/utils';
import type {
    CampaignProgressV2 as Progress,
    CampaignServicing,
} from '@/types/business';
import type {
    BusinessCampaignLifecycle,
    CampaignRestriction,
    RaisingLifecycle,
    ServicingState,
    Units,
} from '@/types/settlement';

type Phase<P extends Progress['phase']> = Extract<Progress, { phase: P }>;

/** Repayment states in tone: amber when a payment is due, red once it is late. */
const SERVICING_TONE: Record<ServicingState, string> = {
    current: 'bg-rz-accent-soft text-rz-accent-app-text',
    due_today:
        'bg-[#fff3e6] text-[#a55418] dark:bg-[rgba(194,102,31,.14)] dark:text-[#f0a060]',
    overdue: 'bg-[rgba(229,72,77,.1)] text-rz-danger-text',
    repaid: 'bg-rz-accent-soft text-rz-accent-app-text',
    defaulted: 'bg-[rgba(229,72,77,.1)] text-rz-danger-text',
};

/** The raise's status chip: amber once new investors can't commit, green otherwise. */
const RAISING_TONE: Record<RaisingLifecycle, string> = {
    live: 'bg-rz-accent-soft text-rz-accent-app-text',
    fully_reserved: 'bg-rz-accent-soft text-rz-accent-app-text',
    sold_out_pending_settlement: 'bg-rz-accent-soft text-rz-accent-app-text',
    inventory_unavailable: SERVICING_TONE.due_today,
    closing_pending_settlement: SERVICING_TONE.due_today,
};

/** What a raise that isn't simply live says about itself. Never "funded" before settlement. */
const RAISING_NOTICE: Record<RaisingLifecycle, MessageCode | null> = {
    live: null,
    fully_reserved: 'business.campaign.fully_reserved',
    sold_out_pending_settlement: 'business.campaign.sold_out',
    inventory_unavailable: 'business.campaign.inventory_unavailable',
    closing_pending_settlement: 'business.campaign.closing',
};

/** A whole-unit count as the server sent it, grouped for reading: "1,200". */
const formatUnits = (units: Units): string =>
    units.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

function Notice({
    tone,
    children,
}: {
    tone: 'green' | 'amber' | 'blue';
    children: ReactNode;
}) {
    return (
        <p
            role="status"
            className={cn(
                'mt-3 rounded-2xl border p-[15px] text-[12.5px] leading-normal text-rz-ink',
                tone === 'green' &&
                    'border-[#cfe9d8] bg-rz-accent-soft dark:border-transparent',
                tone === 'amber' &&
                    'border-[#fbe4cc] bg-[#fff8f1] dark:border-transparent dark:bg-[rgba(194,102,31,.12)]',
                tone === 'blue' &&
                    'border-[#dbe7ff] bg-rz-surface dark:border-rz-border',
            )}
        >
            {children}
        </p>
    );
}

/**
 * An active restriction, drawn apart from the lifecycle: a restricted raise still says whether it
 * is live, fully reserved or funded (v2 §0.4).
 */
function RestrictionBanner({
    restriction,
}: {
    restriction: CampaignRestriction;
}) {
    const { t, locale } = useTranslation();

    if (restriction === null) {
        return null;
    }

    return (
        <p
            role="alert"
            className="mt-3 flex items-start gap-3 rounded-2xl border border-[rgba(229,72,77,.25)] bg-[rgba(229,72,77,.06)] p-[15px] text-[12.5px] leading-normal text-rz-ink"
        >
            <Icon name="blocked" tone="red" />
            <span className="flex-1">
                {t(`business.campaign.restriction.${restriction.code}`, {
                    date: formatDate(restriction.since, locale),
                })}
            </span>
        </p>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-3 py-[5px]">
            <dt className="text-[13px] text-rz-secondary">{label}</dt>
            <dd className="text-right text-[13px] font-semibold text-rz-ink">
                {value}
            </dd>
        </div>
    );
}

/** How long the raise has left, from the server's clock (engineering contract §4). */
function Countdown({
    expiresAt,
    serverTime,
}: {
    expiresAt: string;
    serverTime: string;
}) {
    const { t } = useTranslation();
    const left = timeLeft(expiresAt, useServerNow(serverTime, true));

    if (left.kind === 'closed') {
        return t('business.campaign.closing_now');
    }

    if (left.kind === 'clock') {
        return left.label;
    }

    return t(left.days === 1 ? 'business.note.day' : 'business.note.days', {
        count: left.days,
    });
}

function Raising({
    progress,
    serverTime,
}: {
    progress: Phase<'raising'>;
    serverTime: string;
}) {
    const { t, locale } = useTranslation();
    const pct = Math.min(100, Math.max(0, Number(progress.funded_pct)));
    const settling = SETTLING.includes(progress.lifecycle);
    const notice = RAISING_NOTICE[progress.lifecycle];

    return (
        <>
            <p className="mt-4 flex items-center gap-2">
                <span
                    className={cn(
                        'rounded-full px-2.5 py-1 text-[11px] font-bold',
                        RAISING_TONE[progress.lifecycle],
                    )}
                >
                    {t(`business.campaign.lifecycle.${progress.lifecycle}`)}
                </span>
            </p>
            <RestrictionBanner restriction={progress.restriction} />
            <div className="mt-4 grid grid-cols-2 gap-[11px]">
                <Tile
                    label={t('business.campaign.tile.committed')}
                    value={formatRwfShort(progress.committed)}
                />
                <Tile
                    label={t('business.campaign.tile.committed')}
                    value={t('business.note.pct', { pct: progress.funded_pct })}
                    green
                />
                <Tile
                    label={t('business.note.tile.investors')}
                    value={formatCount(progress.investors)}
                />
                {settling ? (
                    <Tile
                        label={t('business.campaign.tile.deadline')}
                        value={formatDate(progress.clock.expires_at, locale)}
                    />
                ) : (
                    <div
                        role="timer"
                        aria-label={t('business.note.tile.closes_in')}
                        className="rounded-2xl border border-rz-border bg-rz-surface p-3.5"
                    >
                        <p className="text-[11px] font-semibold text-rz-secondary uppercase">
                            {t('business.note.tile.closes_in')}
                        </p>
                        <p className="mt-[3px] text-lg font-semibold text-rz-ink tabular-nums">
                            <Countdown
                                expiresAt={progress.clock.expires_at}
                                serverTime={serverTime}
                            />
                        </p>
                    </div>
                )}
            </div>
            <div className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4">
                <div className="flex items-center justify-between">
                    <span className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('business.campaign.tracker.title')}
                    </span>
                    <span className="text-[11px] font-semibold text-rz-accent-app-text">
                        {t('business.campaign.tracker.committed_pct', {
                            pct: progress.funded_pct,
                        })}
                    </span>
                </div>
                <div
                    role="progressbar"
                    aria-label={t('business.campaign.tracker.title')}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={pct}
                    aria-valuetext={t(
                        'business.campaign.tracker.committed_pct',
                        {
                            pct: progress.funded_pct,
                        },
                    )}
                    className="mt-[11px] h-[9px] overflow-hidden rounded-[5px] bg-rz-page"
                >
                    <div
                        className="h-full rounded-[5px] bg-rz-accent-fill"
                        style={{ width: `${pct}%` }}
                    />
                </div>
                <dl className="mt-3">
                    <Row
                        label={t('business.campaign.committed')}
                        value={formatRwf(progress.committed)}
                    />
                    <Row
                        label={t('business.campaign.reserved')}
                        value={formatRwf(progress.reserved)}
                    />
                    <Row
                        label={t('business.campaign.not_yet_committed')}
                        value={formatRwf(progress.remaining)}
                    />
                </dl>
                {progress.units.reserved !== '0' && (
                    <p className="mt-2 text-[11px] leading-normal text-rz-secondary">
                        {t('business.campaign.reserved_note')}
                    </p>
                )}
                <p className="mt-2 text-[11px] leading-normal text-rz-secondary">
                    {t('business.campaign.units', {
                        committed: formatUnits(progress.units.committed),
                        reserved: formatUnits(progress.units.reserved),
                        available: formatUnits(progress.units.available),
                        unavailable: formatUnits(progress.units.unavailable),
                        total: formatUnits(progress.units.total),
                    })}
                </p>
                {progress.units.unavailable !== '0' && (
                    <p className="mt-1 text-[11px] leading-normal text-rz-secondary">
                        {t('business.campaign.unavailable_note')}
                    </p>
                )}
                <p className="mt-1 text-[11px] leading-normal text-rz-secondary">
                    {t(
                        progress.lifecycle === 'closing_pending_settlement'
                            ? 'business.campaign.deadline_passed'
                            : 'business.campaign.closes',
                        {
                            date: formatDateTime(
                                progress.clock.expires_at,
                                locale,
                            ),
                        },
                    )}
                </p>
            </div>
            {notice !== null && <Notice tone="blue">{t(notice)}</Notice>}
        </>
    );
}

function Funded({
    progress,
    poll,
}: {
    progress: Phase<'funded'>;
    poll: { exhausted: boolean; refresh: () => void };
}) {
    const { t, locale } = useTranslation();
    const { closing } = progress;

    return (
        <>
            <RestrictionBanner restriction={progress.restriction} />
            <div className="mt-4 grid grid-cols-2 gap-[11px]">
                <Tile
                    label={t('business.campaign.tile.committed')}
                    value={formatRwfShort(progress.committed)}
                    green
                />
                <Tile
                    label={t('business.note.tile.investors')}
                    value={formatCount(progress.investors)}
                />
            </div>
            <Notice tone="green">
                {t('business.campaign.funded', {
                    date: formatDate(progress.funded_at, locale),
                })}
            </Notice>
            <div className="mt-3 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                <p className="flex items-center gap-2 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                    <Icon name="hourglass" />
                    {t('business.campaign.closing_title')}
                </p>
                <p className="mt-1.5 text-[13px] font-semibold text-rz-ink">
                    {closing.stage === 'awaiting_disbursement'
                        ? t('business.campaign.closing.awaiting.title')
                        : t('business.campaign.closing.in_flight.title')}
                </p>
                <p className="mt-1 text-xs leading-[1.55] text-rz-secondary">
                    {closing.stage === 'awaiting_disbursement'
                        ? t('business.campaign.closing.awaiting.body')
                        : t(
                              `business.campaign.closing.in_flight.${closing.provider}`,
                          )}
                </p>
                <PollStopped
                    exhausted={poll.exhausted}
                    refresh={poll.refresh}
                    className="mt-3"
                />
            </div>
        </>
    );
}

function Disbursed({ progress }: { progress: Phase<'disbursed'> }) {
    const { t, locale } = useTranslation();
    const { receipt } = progress;

    return (
        <>
            <Notice tone="green">
                {t('business.campaign.disbursed', {
                    amount: formatRwf(progress.amount),
                    destination: progress.destination,
                })}
            </Notice>
            <dl className="mt-3 rounded-2xl border border-rz-border bg-rz-surface px-[15px] py-2.5">
                <Row
                    label={t('business.campaign.disbursed_amount')}
                    value={formatRwf(progress.amount)}
                />
                <Row
                    label={t('business.campaign.destination')}
                    value={progress.destination}
                />
                <Row
                    label={t('business.campaign.effective_at')}
                    value={formatDateTime(
                        progress.disbursement_effective_at,
                        locale,
                    )}
                />
                <Row
                    label={t('business.campaign.effective_date')}
                    value={formatDate(progress.effective_date, locale)}
                />
            </dl>
            <section
                aria-label={t('business.campaign.receipt.title')}
                className="mt-3 rounded-2xl border border-rz-border bg-rz-surface px-[15px] py-2.5"
            >
                <p className="py-[5px] text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                    {t('business.campaign.receipt.title')}
                </p>
                <dl>
                    <Row
                        label={t('business.campaign.receipt.amount')}
                        value={formatRwf(receipt.amount)}
                    />
                    <Row
                        label={t('business.campaign.receipt.reference')}
                        value={receipt.reference}
                    />
                    <Row
                        label={t('business.campaign.receipt.recorded')}
                        value={formatDateTime(receipt.recorded_at, locale)}
                    />
                </dl>
                <Link
                    href={receipt.link}
                    className="mt-1 mb-1 inline-block text-[12.5px] font-bold text-rz-accent-app-text"
                >
                    {t('business.campaign.receipt.view')}
                </Link>
            </section>
        </>
    );
}

function Closed({
    progress,
}: {
    progress: Phase<'expired' | 'cancelled' | 'failed_closing'>;
}) {
    const { t, locale } = useTranslation();
    /* A raise that closed before anyone committed has nothing to refund, and says so. */
    const nobody =
        progress.investors === 0 && progress.committed_refunded.amount === '0';

    return (
        <>
            <div className="mt-4 grid grid-cols-2 gap-[11px]">
                <Tile
                    label={t('business.campaign.tile.refunded')}
                    value={formatRwfShort(progress.committed_refunded)}
                />
                <Tile
                    label={t('business.note.tile.investors')}
                    value={formatCount(progress.investors)}
                />
            </div>
            <Notice tone="amber">
                {t(
                    `business.campaign.closed.${progress.phase}${nobody ? '_none' : ''}`,
                    {
                        date: formatDate(progress.closed_at, locale),
                        amount: formatRwf(progress.committed_refunded),
                    },
                )}
            </Notice>
        </>
    );
}

/** A servicing restriction, drawn apart from the state: it never hides how late a payment is. */
function ServicingRestriction({
    restriction,
}: {
    restriction: CampaignServicing['restriction'];
}) {
    const { t, locale } = useTranslation();

    if (restriction === null) {
        return null;
    }

    return (
        <p
            role="alert"
            className="mt-3 flex items-start gap-3 rounded-2xl border border-[rgba(229,72,77,.25)] bg-[rgba(229,72,77,.06)] p-[15px] text-[12.5px] leading-normal text-rz-ink"
        >
            <Icon name="blocked" tone="red" />
            <span className="flex-1">
                {t(
                    `business.campaign.servicing.restriction.${restriction.code}`,
                    { date: formatDate(restriction.since, locale) },
                )}
            </span>
        </p>
    );
}

function Repaying({ progress }: { progress: Phase<'repaying'> }) {
    const { t, locale } = useTranslation();
    const { servicing } = progress;
    const pct = Math.min(
        100,
        Math.max(0, Number(servicing.progress.repaid_pct)),
    );

    return (
        <>
            <p className="mt-4 flex flex-wrap items-center gap-2">
                <span
                    className={cn(
                        'rounded-full px-2.5 py-1 text-[11px] font-bold',
                        SERVICING_TONE[servicing.state],
                    )}
                >
                    {t(`business.campaign.servicing.state.${servicing.state}`)}
                </span>
                {servicing.dpd !== null && servicing.dpd > 0 && (
                    <span className="text-[11.5px] font-semibold text-rz-danger-text">
                        {t('business.campaign.servicing.dpd', {
                            count: servicing.dpd,
                        })}
                    </span>
                )}
            </p>
            <ServicingRestriction restriction={servicing.restriction} />
            <div className="mt-4 grid grid-cols-2 gap-[11px]">
                <Tile
                    label={t('business.note.tracker.repaid')}
                    value={formatRwfShort(servicing.progress.repaid)}
                    green
                />
                <Tile
                    label={t('business.note.tracker.left_to_pay')}
                    value={formatRwfShort(servicing.progress.remaining)}
                />
                <Tile
                    label={t('business.note.tile.payments_made')}
                    value={t('business.campaign.servicing.payments', {
                        made: servicing.progress.payments_made,
                        total: servicing.progress.payments_total,
                    })}
                />
                <Tile
                    label={t('business.note.tile.investors')}
                    value={formatCount(progress.investors)}
                />
            </div>
            <div className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4">
                <div className="flex items-center justify-between">
                    <span className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('business.note.tracker.repayment')}
                    </span>
                    <span className="text-[11px] font-semibold text-rz-accent-app-text">
                        {t('business.note.tracker.repaid_pct', {
                            pct: servicing.progress.repaid_pct,
                        })}
                    </span>
                </div>
                <div
                    role="progressbar"
                    aria-label={t('business.note.tracker.repayment')}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={pct}
                    aria-valuetext={t('business.note.tracker.repaid_pct', {
                        pct: servicing.progress.repaid_pct,
                    })}
                    className="mt-[11px] h-[9px] overflow-hidden rounded-[5px] bg-rz-page"
                >
                    <div
                        className="h-full rounded-[5px] bg-rz-accent-fill"
                        style={{ width: `${pct}%` }}
                    />
                </div>
                <dl className="mt-3">
                    <Row
                        label={t('business.note.tracker.repaid')}
                        value={formatRwf(servicing.progress.repaid)}
                    />
                    <Row
                        label={t('business.note.tracker.left_to_pay')}
                        value={formatRwf(servicing.progress.remaining)}
                    />
                    <Row
                        label={t('business.campaign.servicing.total')}
                        value={formatRwf(servicing.progress.total)}
                    />
                </dl>
                <p className="mt-2 text-[11px] leading-normal text-rz-secondary">
                    {t('business.campaign.servicing.instalments_left', {
                        count: servicing.progress.remaining_instalments,
                    })}
                </p>
            </div>
            {servicing.next !== null && (
                <section
                    aria-label={t('business.campaign.servicing.next_title')}
                    className="mt-3 rounded-2xl border border-rz-border bg-rz-surface px-[15px] py-2.5"
                >
                    <p className="py-[5px] text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                        {t('business.campaign.servicing.next_title')}
                    </p>
                    <p className="text-[12.5px] text-rz-secondary">
                        {t('business.campaign.servicing.next_due', {
                            index: servicing.next.index,
                            date: formatDate(servicing.next.due_on, locale),
                        })}
                    </p>
                    <dl className="mt-1">
                        <Row
                            label={t('business.campaign.servicing.principal')}
                            value={formatRwf(servicing.next.amounts.principal)}
                        />
                        <Row
                            label={t('business.campaign.servicing.return')}
                            value={formatRwf(servicing.next.amounts.return)}
                        />
                        <Row
                            label={t('business.campaign.servicing.service_fee')}
                            value={formatRwf(
                                servicing.next.amounts.service_fee,
                            )}
                        />
                        <Row
                            label={t('business.campaign.servicing.next_total')}
                            value={formatRwf(servicing.next.amounts.total)}
                        />
                    </dl>
                </section>
            )}
            <Link
                href={progress.link}
                className="mt-3 flex h-[46px] w-full items-center justify-center rounded-xl bg-rz-accent-fill text-sm font-semibold text-white"
            >
                {t('business.campaign.servicing.open')}
            </Link>
        </>
    );
}

function Repaid({ progress }: { progress: Phase<'repaid'> }) {
    const { t, locale } = useTranslation();

    return (
        <>
            <div className="mt-4 grid grid-cols-2 gap-[11px]">
                <Tile
                    label={t('business.note.tracker.repaid')}
                    value={formatRwfShort(progress.total_repaid)}
                    green
                />
                <Tile
                    label={t('business.note.tile.investors')}
                    value={formatCount(progress.investors)}
                />
            </div>
            <Notice tone="green">
                {t('business.campaign.servicing.repaid', {
                    amount: formatRwf(progress.total_repaid),
                    date: formatDate(progress.completed_on, locale),
                })}
            </Notice>
        </>
    );
}

/** Progress the server sent incompletely or inconsistently: no guessed figures, no countdown. */
function Unreadable() {
    const { t } = useTranslation();

    return (
        <Notice tone="amber">
            {t('business.campaign.progress_unavailable')}
        </Notice>
    );
}

/**
 * A campaign's aggregate funding progress (C3 v2 §2f). Every figure is the server's and the client
 * never subtracts; no Investor is named, typed or given an amount (H16). Closing is coarse (H15):
 * a payment in flight reads "not yet confirmed" — never paid or failed — until the server moves it
 * on, and it carries no provider reference. Once issued, v2 adds the repayment phases (C4 v1 §4a):
 * how much is repaid and left, the next instalment and any arrears, all as the server states them.
 * A raise whose figures are missing, unknown or disagree with the campaign's lifecycle fails closed.
 */
export function CampaignProgress({
    progress,
    lifecycle,
    serverTime,
    poll,
}: {
    progress: Progress;
    lifecycle: BusinessCampaignLifecycle;
    serverTime: string;
    poll: { exhausted: boolean; refresh: () => void };
}) {
    if (!isReadableProgress(progress, lifecycle)) {
        return <Unreadable />;
    }

    switch (progress.phase) {
        case 'raising':
            return <Raising progress={progress} serverTime={serverTime} />;
        case 'funded':
            return <Funded progress={progress} poll={poll} />;
        case 'disbursed':
            return <Disbursed progress={progress} />;
        case 'repaying':
            return <Repaying progress={progress} />;
        case 'repaid':
            return <Repaid progress={progress} />;
        default:
            return <Closed progress={progress} />;
    }
}
