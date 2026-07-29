import type { CSSProperties, RefObject } from 'react';
import { StatCurrency, StatTile } from '@/components/pulse/stat-tile';
import {
    formatAverage,
    formatFull,
    formatNumber,
    MAX_PLEDGE,
    MIN_PLEDGE,
} from '@/lib/pulse';
import type { BusinessNote } from '@/lib/pulse';

type InvestorPanelProps = {
    panelRef: RefObject<HTMLDivElement | null>;
    pledged: number;
    investors: number;
    averageYield: number | null;
    averageTerm: number | null;
    pledge: number;
    payout: number;
    notes: BusinessNote[];
    onPledgeChange: (pledge: number) => void;
    onSaveSpot: () => void;
};

/**
 * The investor side of the waitlist: set an amount, see what it earns back and
 * reserve an allocation.
 */
export function InvestorPanel({
    panelRef,
    pledged,
    investors,
    averageYield,
    averageTerm,
    pledge,
    payout,
    notes,
    onPledgeChange,
    onSaveSpot,
}: InvestorPanelProps) {
    return (
        <div
            ref={panelRef}
            className="relative flex flex-col overflow-hidden rounded-2xl border border-[var(--rz-card-border)] bg-[var(--rz-card-bg)] px-2 py-3 md:p-[17px]"
        >
            <div className="absolute top-0 right-0 left-0 h-px bg-[linear-gradient(90deg,transparent,rgba(10,92,255,0.7),transparent)]" />
            <div className="flex items-center justify-between">
                <span className="text-[11px] font-bold tracking-[0.16em] text-[var(--rz-blue-fg)]">
                    FOR INVESTORS
                </span>
            </div>
            <div className="mt-[15px] text-[22px] leading-[1.22] font-bold tracking-[-0.02em] text-[var(--rz-fg-title)]">
                Earn up to 15% backing
                <br />
                audited local businesses.
            </div>
            <p className="mt-3 text-[13.5px] leading-[1.55] text-[var(--rz-muted)]">
                Set your amount and reserve your allocation for launch.
            </p>
            <div className="mt-5">
                <div className="mt-[13px] grid grid-cols-[1fr_90px_90px] gap-2 md:grid-cols-[1.25fr_1fr_1fr]">
                    <StatTile label="PLEDGED" color="var(--rz-stat-value)">
                        <StatCurrency />
                        {formatNumber(pledged)}
                    </StatTile>
                    <StatTile label="INVESTORS" color="var(--rz-stat-value)">
                        {formatNumber(investors)}
                    </StatTile>
                    <StatTile
                        label="AVG YIELD"
                        color="var(--rz-blue-yield)"
                        suffix={averageTerm === null ? '—' : `${averageTerm}MO`}
                    >
                        {averageYield === null
                            ? '—'
                            : `${formatAverage(averageYield, 1)}%`}
                    </StatTile>
                </div>
            </div>
            <div className="mt-[22px] flex flex-col items-start justify-between gap-1.5 md:flex-row md:items-baseline md:gap-2.5">
                <span className="rz-num text-[32px] leading-none font-bold tracking-[-0.03em] text-[var(--rz-fg-strong)]">
                    {formatFull(pledge)}
                </span>
                <span className="rz-num text-[13px] font-semibold text-[var(--rz-blue-fg)]">
                    → {formatFull(payout)} back
                </span>
            </div>
            <input
                type="range"
                className="rz-slider mt-6 mb-2"
                min={MIN_PLEDGE}
                max={MAX_PLEDGE}
                step={MIN_PLEDGE}
                value={pledge}
                aria-label="Pledge amount"
                onChange={(event) =>
                    onPledgeChange(
                        parseInt(event.target.value, 10) || MIN_PLEDGE,
                    )
                }
            />
            <div className="mt-5">
                <span className="text-[10px] font-bold tracking-[0.14em] text-[var(--rz-blue-fg)]">
                    BUSINESSES YOU CAN BACK
                </span>
            </div>
            <div className="rz-scrollwin mt-[9px] mb-[14px] h-auto max-h-[148px] min-h-[120px] flex-auto overflow-y-auto rounded-[11px] border border-[rgba(10,92,255,0.16)] bg-[rgba(10,92,255,0.03)] md:h-0 md:max-h-none">
                {notes.length === 0 && (
                    <div className="px-[11px] py-4 text-center text-[10.5px] font-semibold text-[var(--rz-note-meta)]">
                        No businesses have pre-qualified yet. Yours could be the
                        first.
                    </div>
                )}
                {notes.map((note) => (
                    <div
                        key={note.id}
                        className="flex items-center gap-2 border-b border-[rgba(255,255,255,0.05)] px-[11px] py-2"
                    >
                        <div
                            className="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-[var(--rz-avatar)] text-[9px] font-bold text-white dark:bg-[var(--rz-avatar-dark)]"
                            style={
                                {
                                    '--rz-avatar': note.avatar.light,
                                    '--rz-avatar-dark': note.avatar.dark,
                                } as CSSProperties
                            }
                        >
                            {note.initial}
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="truncate text-[10.5px] font-semibold text-[var(--rz-note-name)]">
                                {note.name}
                            </div>
                            <div className="rz-num mt-px text-[8.5px] whitespace-nowrap text-[var(--rz-note-meta)]">
                                <span
                                    className="font-semibold text-[var(--rz-rating)] dark:text-[var(--rz-rating-dark)]"
                                    style={
                                        {
                                            '--rz-rating':
                                                note.ratingColor.light,
                                            '--rz-rating-dark':
                                                note.ratingColor.dark,
                                        } as CSSProperties
                                    }
                                >
                                    {note.rating_band} {note.rating_score}
                                </span>
                            </div>
                        </div>
                        <div className="mr-[7px] shrink-0 text-right">
                            <div className="rz-num text-[10.5px] font-semibold text-[var(--rz-note-term)]">
                                {note.term}
                            </div>
                        </div>
                        <div className="mr-[7px] shrink-0 text-right">
                            <div className="rz-num text-[10.5px] font-semibold text-[var(--rz-blue-fg)]">
                                {note.yield}
                            </div>
                        </div>
                        <div className="shrink-0 text-right">
                            <div className="rz-num text-[11.5px] font-bold text-[var(--rz-green-fg)]">
                                {note.projectedReturn}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <button
                type="button"
                onClick={onSaveSpot}
                className="rz-cta mt-auto h-12 w-full shrink-0 cursor-pointer rounded-[10px] border-none bg-[#0a5cff] text-[14px] font-semibold text-white transition-[filter] duration-150 md:mt-0"
            >
                Save your spot →
            </button>
        </div>
    );
}
