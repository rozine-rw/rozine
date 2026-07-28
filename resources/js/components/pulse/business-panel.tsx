import type { CSSProperties, RefObject } from 'react';
import { FileIcon, UploadIcon, VerifiedIcon } from '@/components/pulse/icons';
import { StatCurrency, StatTile } from '@/components/pulse/stat-tile';
import {
    DOCUMENT_TYPES,
    formatAverage,
    formatCompact,
    TERMS,
} from '@/lib/pulse';
import type { Rating } from '@/lib/pulse';

type BusinessPanelProps = {
    panelRef: RefObject<HTMLDivElement | null>;
    businesses: number;
    averageLoan: number | null;
    averageRating: number | null;
    idle: boolean;
    parsing: boolean;
    result: boolean;
    progress: number;
    progressLabel: string;
    statementName: string;
    annualInflow: number;
    qualifiedAmount: number;
    monthlyRepayment: number;
    flatRate: string;
    rating: Rating;
    termIndex: number;
    onUpload: () => void;
    onTermChange: (index: number) => void;
    onSaveSpot: () => void;
};

/**
 * The business side of the waitlist: upload a statement, see the capacity it
 * buys and claim a pass.
 */
export function BusinessPanel({
    panelRef,
    businesses,
    averageLoan,
    averageRating,
    idle,
    parsing,
    result,
    progress,
    progressLabel,
    statementName,
    annualInflow,
    qualifiedAmount,
    monthlyRepayment,
    flatRate,
    rating,
    termIndex,
    onUpload,
    onTermChange,
    onSaveSpot,
}: BusinessPanelProps) {
    return (
        <div
            ref={panelRef}
            className="relative flex flex-col overflow-hidden rounded-2xl border border-[var(--rz-card-border)] bg-[var(--rz-card-bg)] px-2 py-3 md:p-[17px]"
        >
            <div className="absolute top-0 right-0 left-0 h-px bg-[linear-gradient(90deg,transparent,rgba(16,161,80,0.7),transparent)]" />
            <div className="flex items-center justify-between">
                <span className="text-[11px] font-bold tracking-[0.16em] text-[var(--rz-green-fg)]">
                    FOR BUSINESSES
                </span>
            </div>
            <div className="mt-[15px] text-[22px] leading-[1.22] font-bold tracking-[-0.02em] text-[var(--rz-fg-title)]">
                Raise growth capital
                <br />
                from cash flow, not collateral.
            </div>
            <p className="mt-3 text-[13.5px] leading-[1.55] text-[var(--rz-muted)]">
                See exactly how much you pre-qualify for. Drop proof of cash
                flow.
            </p>
            <div className="mt-5">
                <div className="mt-[13px] grid grid-cols-[1fr_90px_90px] gap-2 md:grid-cols-[1.25fr_1fr_1fr]">
                    <StatTile
                        label="AVG PRE-QUALIFIED"
                        color="var(--rz-stat-value)"
                    >
                        {averageLoan !== null && <StatCurrency />}
                        {formatAverage(averageLoan)}
                    </StatTile>
                    <StatTile label="SIZED" color="var(--rz-stat-value)">
                        {businesses}
                    </StatTile>
                    <StatTile
                        label="AVG RATING"
                        color="var(--rz-green-rating)"
                        muted
                    >
                        {formatAverage(averageRating, 1)}
                    </StatTile>
                </div>
            </div>

            {idle && (
                <>
                    <div className="mt-[22px] text-center text-[13px] font-semibold text-[var(--rz-muted-2)]">
                        Upload a statement to see how much you qualify for.
                    </div>
                    <button
                        type="button"
                        onClick={onUpload}
                        className="rz-ghost rz-upload mt-[13px] flex min-h-[150px] w-full flex-1 cursor-pointer flex-col items-center justify-center gap-2.5 rounded-xl border border-dashed border-[rgba(74,222,128,0.42)] bg-[rgba(16,161,80,0.05)] text-[#bfe9cf] transition-[background] duration-150"
                    >
                        <span className="text-[var(--rz-green-fg)]">
                            <UploadIcon />
                        </span>
                        <span className="text-[15px] font-semibold text-[var(--rz-fg)]">
                            Upload a statement
                        </span>
                    </button>
                    <div className="mt-3 flex flex-nowrap gap-1">
                        {DOCUMENT_TYPES.map((type) => (
                            <span
                                key={type}
                                className="rounded-lg border border-[var(--rz-line)] bg-[var(--rz-tile-bg)] px-[5px] py-1.5 text-center text-[8px] font-semibold whitespace-nowrap text-[var(--rz-muted-2)]"
                            >
                                {type}
                            </span>
                        ))}
                    </div>
                </>
            )}

            {parsing && (
                <div className="mt-[22px] pt-3.5 pb-1.5 text-center">
                    <div className="mx-auto h-[42px] w-[42px] animate-[rzp-spin_0.8s_linear_infinite] rounded-full border-[3px] border-[rgba(16,161,80,0.18)] border-t-[#12a150]" />
                    <div className="rz-num mt-4 text-[30px] font-bold tracking-[-0.03em] text-[var(--rz-fg-title)]">
                        {progress}%
                    </div>
                    <div className="mt-1.5 text-[11px] tracking-[0.06em] text-[var(--rz-dim)]">
                        {progressLabel}
                    </div>
                    <div className="mx-auto mt-3.5 h-0.5 max-w-[240px] overflow-hidden bg-[var(--rz-track)]">
                        <div
                            className="h-full bg-[#12a150] transition-[width] duration-300"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>
            )}

            {result && (
                <div className="mt-5">
                    <div className="flex items-center gap-[7px] text-[10px] font-semibold whitespace-nowrap text-[var(--rz-green-strong)]">
                        <span className="inline-flex shrink-0 items-center gap-[5px] rounded-[7px] border border-[var(--rz-chip-border)] bg-[var(--rz-chip-bg)] px-2 py-1">
                            <span className="text-[var(--rz-muted-2)]">
                                <FileIcon />
                            </span>
                            <span className="inline-block max-w-[58px] truncate align-bottom text-[10.5px] font-semibold text-[var(--rz-strong)]">
                                {statementName}
                            </span>
                        </span>
                        <VerifiedIcon />
                        Verified ·{' '}
                        <span className="rz-num">
                            {formatCompact(annualInflow)}
                        </span>{' '}
                        avg annual inflow
                    </div>
                    <div className="mt-2.5">
                        <RatingPill rating={rating} />
                    </div>
                    <div className="mt-4">
                        <div className="text-[11px] font-bold tracking-[0.16em] text-[var(--rz-dim)]">
                            PRE-QUALIFIED
                        </div>
                        <div className="mt-2 flex items-end justify-between gap-3">
                            <span className="rz-num text-[42px] leading-[0.9] font-bold tracking-[-0.035em] text-[var(--rz-fg-strong)]">
                                {formatCompact(qualifiedAmount)}
                            </span>
                        </div>
                    </div>
                    <div className="mt-[18px] flex gap-0.5 rounded-[10px] border border-[var(--rz-seg-border)] bg-[var(--rz-seg-bg)] p-[3px]">
                        {TERMS.map((term, index) => (
                            <button
                                key={term}
                                type="button"
                                onClick={() => onTermChange(index)}
                                className={`rz-num flex-1 cursor-pointer rounded-[7px] border-none py-[9px] text-[12.5px] font-bold transition-all duration-150 ${
                                    termIndex === index
                                        ? 'bg-[#12a150] text-white'
                                        : 'bg-transparent text-[var(--rz-muted)]'
                                }`}
                            >
                                {term}mo
                            </button>
                        ))}
                    </div>
                    <div className="mt-3 flex gap-0.5 overflow-hidden rounded-xl border border-[var(--rz-line)]">
                        <div className="flex-1 border-r border-[var(--rz-line-soft)] py-3 text-center">
                            <div className="rz-num text-[16px] font-bold text-[var(--rz-green-fg)]">
                                {flatRate}
                            </div>
                            <div className="mt-1 text-[9.5px] tracking-[0.06em] text-[var(--rz-dim)]">
                                FLAT RETURN
                            </div>
                        </div>
                        <div className="flex-1 border-r border-[var(--rz-line-soft)] py-3 text-center">
                            <div className="rz-num text-[16px] font-bold text-[var(--rz-fg)]">
                                {formatCompact(monthlyRepayment)}
                            </div>
                            <div className="mt-1 text-[9.5px] tracking-[0.06em] text-[var(--rz-dim)]">
                                MONTHLY
                            </div>
                        </div>
                        <div className="flex-1 py-3 text-center">
                            <div className="rz-num text-[16px] font-bold text-[var(--rz-green-fg)]">
                                ✓ 1.25×
                            </div>
                            <div className="mt-1 text-[9.5px] tracking-[0.06em] text-[var(--rz-dim)]">
                                DSCR
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onSaveSpot}
                        className="rz-cta mt-5 h-12 w-full shrink-0 cursor-pointer rounded-[10px] border-none bg-[#12a150] text-[14px] font-semibold text-white transition-[filter] duration-150"
                    >
                        Save your spot →
                    </button>
                </div>
            )}
        </div>
    );
}

/**
 * The band and score read off a statement.
 */
export function RatingPill({
    rating,
    compact = false,
}: {
    rating: Rating;
    compact?: boolean;
}) {
    return (
        <span
            className={`inline-flex items-center whitespace-nowrap ${
                compact
                    ? 'shrink-0 gap-1 rounded-md px-[7px] py-[3px]'
                    : 'gap-[7px] rounded-lg px-[11px] py-[5px]'
            } border border-[var(--rz-rating)] bg-[var(--rz-rating-bg)] dark:border-[var(--rz-rating-dark)] dark:bg-[var(--rz-rating-bg-dark)]`}
            style={
                {
                    '--rz-rating': rating.color.light,
                    '--rz-rating-dark': rating.color.dark,
                    '--rz-rating-bg': rating.background.light,
                    '--rz-rating-bg-dark': rating.background.dark,
                } as CSSProperties
            }
        >
            <span
                className={`${compact ? 'text-[9.5px]' : 'text-[12.5px]'} font-bold text-[var(--rz-rating)] dark:text-[var(--rz-rating-dark)]`}
            >
                {rating.band}
            </span>
            <span
                className={`rz-num ${compact ? 'text-[9px]' : 'text-[11.5px]'} font-semibold text-[#cdd8ea]`}
            >
                {rating.score}
            </span>
        </span>
    );
}
