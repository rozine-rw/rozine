import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Tile } from '@/components/business/note/progress-summary';
import { PollStopped } from '@/components/rozine/c3-notice';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { timeLeft, useServerNow } from '@/lib/investor/server-clock';
import {
    formatCount,
    formatDate,
    formatDateTime,
    formatRwf,
    formatRwfShort,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { CampaignProgress as Progress } from '@/types/business';
import type { CampaignRestriction, Units } from '@/types/settlement';

type Phase<P extends Progress['phase']> = Extract<Progress, { phase: P }>;

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
                tone === 'blue' && 'border-[#dbe7ff] bg-rz-surface',
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

    return (
        <>
            <p className="mt-4 flex items-center gap-2">
                <span className="rounded-full bg-rz-accent-soft px-2.5 py-1 text-[11px] font-bold text-rz-accent-app-text">
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
                    label={t('business.note.tile.funded')}
                    value={t('business.note.pct', { pct: progress.funded_pct })}
                    green
                />
                <Tile
                    label={t('business.note.tile.investors')}
                    value={formatCount(progress.investors)}
                />
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
            </div>
            <div className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4">
                <div className="flex items-center justify-between">
                    <span className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('business.note.tracker.funding')}
                    </span>
                    <span className="text-[11px] font-semibold text-rz-accent-app-text">
                        {t('business.note.tracker.funded_pct', {
                            pct: progress.funded_pct,
                        })}
                    </span>
                </div>
                <div
                    role="progressbar"
                    aria-label={t('business.note.tracker.funding')}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={pct}
                    aria-valuetext={t('business.note.tracker.funded_pct', {
                        pct: progress.funded_pct,
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
                        label={t('business.campaign.committed')}
                        value={formatRwf(progress.committed)}
                    />
                    <Row
                        label={t('business.campaign.reserved')}
                        value={formatRwf(progress.reserved)}
                    />
                    <Row
                        label={t('business.note.tracker.left_to_raise')}
                        value={formatRwf(progress.remaining)}
                    />
                </dl>
                <p className="mt-2 text-[11px] leading-normal text-rz-secondary">
                    {t('business.campaign.reserved_note')}
                </p>
                <p className="mt-2 text-[11px] leading-normal text-rz-secondary">
                    {t('business.campaign.units', {
                        committed: formatUnits(progress.units.committed),
                        reserved: formatUnits(progress.units.reserved),
                        available: formatUnits(progress.units.available),
                        total: formatUnits(progress.units.total),
                    })}
                </p>
                <p className="mt-1 text-[11px] leading-normal text-rz-secondary">
                    {t('business.campaign.closes', {
                        date: formatDateTime(progress.clock.expires_at, locale),
                    })}
                </p>
            </div>
            {progress.lifecycle === 'fully_reserved' && (
                <Notice tone="blue">
                    {t('business.campaign.fully_reserved')}
                </Notice>
            )}
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
                {t(`business.campaign.closed.${progress.phase}`, {
                    date: formatDate(progress.closed_at, locale),
                    amount: formatRwf(progress.committed_refunded),
                })}
            </Notice>
        </>
    );
}

/**
 * A campaign's aggregate funding progress (C3 v2 §2f). Every figure is the server's and the client
 * never subtracts; no Investor is named, typed or given an amount (H16). Closing is coarse (H15):
 * a payment in flight reads "not yet confirmed" — never paid or failed — until the server moves it
 * on, and it carries no provider reference.
 */
export function CampaignProgress({
    progress,
    serverTime,
    poll,
}: {
    progress: Progress;
    serverTime: string;
    poll: { exhausted: boolean; refresh: () => void };
}) {
    switch (progress.phase) {
        case 'raising':
            return <Raising progress={progress} serverTime={serverTime} />;
        case 'funded':
            return <Funded progress={progress} poll={poll} />;
        case 'disbursed':
            return <Disbursed progress={progress} />;
        default:
            return <Closed progress={progress} />;
    }
}
