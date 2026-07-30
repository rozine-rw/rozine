import type { CSSProperties, RefObject } from 'react';
import { useState } from 'react';
import {
    PanelCriteria,
    PanelHeading,
    PanelLabel,
    PulsePanel,
} from '@/components/pulse/panel';
import { StatCurrency, StatTile } from '@/components/pulse/stat-tile';
import {
    digitsOnly,
    formatAbbrev,
    formatAverage,
    formatFull,
    formatNumber,
    INVESTOR_CRITERIA,
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
    exampleOpen: boolean;
    onExampleToggle: () => void;
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
    exampleOpen,
    onExampleToggle,
    onPledgeChange,
    onSaveSpot,
}: InvestorPanelProps) {
    return (
        <PulsePanel tone="investor" panelRef={panelRef}>
            <PanelLabel color="var(--rz-blue-fg)">FOR INVESTORS</PanelLabel>
            <PanelHeading>Set amount to invest.</PanelHeading>
            <PanelCriteria tone="investor" items={INVESTOR_CRITERIA} />
            <button
                type="button"
                onClick={onExampleToggle}
                aria-expanded={exampleOpen}
                className="mt-[11px] cursor-pointer self-start border-none bg-transparent p-0 text-left text-[12px] font-semibold text-[var(--rz-blue-fg)]"
            >
                {exampleOpen
                    ? 'Hide the example'
                    : 'See how a repayment works →'}
            </button>
            {exampleOpen && (
                <div className="mt-[9px] rounded-[11px] border border-[var(--rz-intent-blue-border)] bg-[var(--rz-intent-blue-bg)] px-[13px] py-[11px] text-[12px] leading-[1.6] text-[var(--rz-criteria)]">
                    A business borrows{' '}
                    <strong className="text-[var(--rz-fg)]">
                        RWF 10,000,000
                    </strong>{' '}
                    for 6 months at a{' '}
                    <strong className="text-[var(--rz-fg)]">
                        14% total return
                    </strong>
                    . It repays{' '}
                    <strong className="text-[var(--rz-fg)]">
                        RWF 11,400,000
                    </strong>{' '}
                    in 6 monthly tranches of{' '}
                    <strong className="text-[var(--rz-fg)]">
                        RWF 1,900,000
                    </strong>
                    .
                    <div className="mt-[7px] text-[var(--rz-muted)]">
                        Put RWF 100,000 into that raise and you get RWF 114,000
                        back — paid to you monthly, not at the end.
                    </div>
                </div>
            )}
            <div className="mt-5">
                <div className="mt-[13px] grid grid-cols-[1fr_90px_90px] gap-2 md:grid-cols-[1.25fr_1fr_1fr]">
                    <StatTile label="PLEDGED" color="var(--rz-stat-value)">
                        <StatCurrency />
                        {formatAbbrev(pledged)}
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
                <PledgeAmount pledge={pledge} onChange={onPledgeChange} />
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
                aria-label="Adjust pledge amount"
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
        </PulsePanel>
    );
}

/**
 * The amount an investor is pledging, set by typing as well as by the slider.
 *
 * It has to read as the figure it already was, so the field carries no chrome
 * of its own: an invisible copy of the text sizes the box, and the input sits
 * over it wearing the same type.
 */
function PledgeAmount({
    pledge,
    onChange,
}: {
    pledge: number;
    onChange: (pledge: number) => void;
}) {
    const [draft, setDraft] = useState<string | null>(null);
    const shown = draft ?? formatNumber(pledge);

    return (
        <span className="rz-num inline-flex items-baseline text-[32px] leading-none font-bold tracking-[-0.03em] text-[var(--rz-fg-strong)]">
            <span>RWF&nbsp;</span>
            <span className="relative inline-block">
                <span className="invisible whitespace-pre" aria-hidden="true">
                    {shown}
                </span>
                <input
                    value={shown}
                    inputMode="numeric"
                    aria-label="Pledge amount"
                    onFocus={(event) => event.currentTarget.select()}
                    onChange={(event) => {
                        const typed = Math.min(
                            digitsOnly(event.target.value, 9),
                            MAX_PLEDGE,
                        );

                        setDraft(typed === 0 ? '' : formatNumber(typed));

                        if (typed > 0) {
                            onChange(typed);
                        }
                    }}
                    onBlur={() => {
                        setDraft(null);
                        onChange(commit(pledge));
                    }}
                    className="absolute inset-0 w-full border-none bg-transparent p-0 font-[inherit] tracking-[inherit] text-[inherit] outline-none"
                />
            </span>
        </span>
    );
}

/**
 * Settle a typed amount onto a figure the slider can also hold.
 */
function commit(pledge: number): number {
    const held = Math.min(Math.max(pledge, MIN_PLEDGE), MAX_PLEDGE);

    return Math.round(held / MIN_PLEDGE) * MIN_PLEDGE;
}
