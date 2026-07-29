import type { CSSProperties, ReactNode, RefObject } from 'react';
import { AuditIcon } from '@/components/pulse/icons';
import { StatCurrency, StatTile } from '@/components/pulse/stat-tile';
import {
    digitsOnly,
    formatAverage,
    formatCompact,
    formatFull,
    formatNumber,
    monthlySurplus,
    registrationYears,
    SECTORS,
    TERMS,
} from '@/lib/pulse';
import type { Rating, Sizing } from '@/lib/pulse';

export type BusinessFigures = {
    name: string;
    annualRevenue: number;
    annualCosts: number;
    sector: string;
    registeredYear: string;
};

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
    figures: BusinessFigures;
    sizing: Sizing;
    termIndex: number;
    canSize: boolean;
    onFiguresChange: (figures: Partial<BusinessFigures>) => void;
    onSize: () => void;
    onTermChange: (index: number) => void;
    onSaveSpot: () => void;
};

/**
 * The business side of the waitlist: answer five questions, see the loan they
 * buy and claim a pass.
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
    figures,
    sizing,
    termIndex,
    canSize,
    onFiguresChange,
    onSize,
    onTermChange,
    onSaveSpot,
}: BusinessPanelProps) {
    const surplus = monthlySurplus(figures.annualRevenue, figures.annualCosts);
    const showSurplus = figures.annualRevenue > 0 && figures.annualCosts > 0;
    const overspent =
        showSurplus && figures.annualCosts >= figures.annualRevenue;

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
                Raise a business loan
                <br />
                based on revenue. No collateral.
            </div>
            <p className="mt-3 text-[13.5px] leading-[1.55] text-[var(--rz-muted)]">
                Answer short questions to see how much your business
                pre-qualifies for.
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
                    <div className="mt-5 flex items-center gap-2 rounded-[11px] border-l-[3px] border-[#12a150] bg-[rgba(16,161,80,0.07)] px-[11px] py-2.5">
                        <AuditIcon />
                        <span className="text-[10.5px] leading-[1.3] font-semibold whitespace-nowrap text-[var(--rz-green-fg)]">
                            An Audit Partner confirms your numbers at launch.
                        </span>
                    </div>
                    <div className="mt-[13px] flex flex-col gap-[11px]">
                        <div>
                            <FieldLabel>Name of business</FieldLabel>
                            <input
                                value={figures.name}
                                onChange={(event) =>
                                    onFiguresChange({
                                        name: event.target.value,
                                    })
                                }
                                placeholder="e.g. GreenLeaf Agro"
                                className="rz-ipt h-[42px] w-full rounded-[10px] border border-[var(--rz-input-border)] bg-[var(--rz-input-bg)] px-[13px] text-[14px] text-[var(--rz-fg)] outline-none"
                            />
                        </div>
                        <AmountField
                            label="Total revenue over the last 12 months"
                            hint="Total sales: cash, MoMo, bank,…"
                            value={figures.annualRevenue}
                            onChange={(annualRevenue) =>
                                onFiguresChange({ annualRevenue })
                            }
                        />
                        <AmountField
                            label="Total costs over the last 12 months"
                            hint="All expenses: rent, salaries, transport,…"
                            value={figures.annualCosts}
                            onChange={(annualCosts) =>
                                onFiguresChange({ annualCosts })
                            }
                        >
                            {showSurplus && (
                                <SurplusStrip
                                    overspent={overspent}
                                    surplus={surplus}
                                />
                            )}
                        </AmountField>
                        <div className="flex gap-[9px]">
                            <div className="min-w-0 flex-1">
                                <FieldLabel>What you do</FieldLabel>
                                <PanelSelect
                                    label="What you do"
                                    value={figures.sector}
                                    placeholder="Choose one"
                                    options={SECTORS.map(
                                        (sector) => sector.value,
                                    )}
                                    onChange={(sector) =>
                                        onFiguresChange({ sector })
                                    }
                                />
                            </div>
                            <div className="min-w-0 flex-1">
                                <FieldLabel>Registered in</FieldLabel>
                                <PanelSelect
                                    label="Registered in"
                                    value={figures.registeredYear}
                                    placeholder="Year"
                                    options={registrationYears().map(String)}
                                    onChange={(registeredYear) =>
                                        onFiguresChange({ registeredYear })
                                    }
                                />
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        disabled={!canSize}
                        onClick={onSize}
                        style={{
                            background: canSize
                                ? '#0f7a3d'
                                : 'var(--rz-disabled-bg)',
                            color: canSize
                                ? '#ffffff'
                                : 'var(--rz-disabled-fg)',
                            cursor: canSize ? 'pointer' : 'not-allowed',
                        }}
                        className="rz-cta mt-4 h-12 w-full shrink-0 rounded-[10px] border-none text-[14px] font-semibold transition-[filter] duration-150"
                    >
                        Check loan amount →
                    </button>
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
                    <div className="flex items-center gap-[7px] text-[10px] font-semibold whitespace-nowrap text-[var(--rz-green-fg)]">
                        <span className="rz-num">
                            {formatCompact(figures.annualRevenue)}
                        </span>{' '}
                        a year ·{' '}
                        <span className="font-semibold text-[var(--rz-muted)]">
                            audit at launch
                        </span>
                    </div>
                    <div className="mt-2.5">
                        <RatingPill rating={sizing.rating} />
                    </div>
                    <div className="mt-4">
                        <div className="text-[11px] font-bold tracking-[0.16em] text-[var(--rz-dim)]">
                            PRE-QUALIFIED
                        </div>
                        <div className="mt-2 flex items-end justify-between gap-3">
                            <span className="rz-num text-[42px] leading-[0.9] font-bold tracking-[-0.035em] text-[var(--rz-fg-strong)]">
                                {formatCompact(sizing.qualifiedAmount)}
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
                        <ResultFigure
                            label="FLAT RETURN"
                            color="var(--rz-green-fg)"
                        >
                            {sizing.flatRate.toFixed(1)}%
                        </ResultFigure>
                        <ResultFigure label="MONTHLY" color="var(--rz-fg)">
                            {formatCompact(sizing.monthlyRepayment)}
                        </ResultFigure>
                        <ResultFigure
                            label="DSCR"
                            color="var(--rz-green-fg)"
                            last
                        >
                            {sizing.coverRatio >= 1 && '✓ '}
                            {sizing.coverRatio.toFixed(2)}×
                        </ResultFigure>
                    </div>
                    <button
                        type="button"
                        onClick={onSaveSpot}
                        className="rz-cta mt-5 h-12 w-full shrink-0 cursor-pointer rounded-[10px] border-none bg-[#0f7a3d] text-[14px] font-semibold text-white transition-[filter] duration-150"
                    >
                        Save your spot →
                    </button>
                </div>
            )}
        </div>
    );
}

/**
 * A figure in francs, typed without punctuation and read back grouped.
 */
function AmountField({
    label,
    hint,
    value,
    onChange,
    children,
}: {
    label: string;
    hint: string;
    value: number;
    onChange: (value: number) => void;
    children?: ReactNode;
}) {
    return (
        <div>
            <FieldLabel>{label}</FieldLabel>
            <div className="flex h-[42px] items-center overflow-hidden rounded-[10px] border border-[var(--rz-input-border)] bg-[var(--rz-input-bg)]">
                <span className="shrink-0 pr-[9px] pl-[13px] text-[10.5px] font-bold tracking-[0.06em] text-[var(--rz-hint)]">
                    RWF
                </span>
                <input
                    value={value === 0 ? '' : formatNumber(value)}
                    onChange={(event) =>
                        onChange(digitsOnly(event.target.value))
                    }
                    inputMode="numeric"
                    aria-label={label}
                    placeholder="0"
                    className="rz-ipt rz-num h-full min-w-0 flex-1 border-none bg-transparent pr-[13px] text-[15px] font-bold text-[var(--rz-fg)] outline-none"
                />
            </div>
            <div className="mt-[7px] text-[10.5px] leading-[1.45] text-[var(--rz-hint)]">
                {hint}
            </div>
            {children}
        </div>
    );
}

/**
 * What a business is left with each month, or the warning that it is left with
 * nothing at all.
 */
function SurplusStrip({
    overspent,
    surplus,
}: {
    overspent: boolean;
    surplus: number;
}) {
    return (
        <div
            className="mt-[9px] flex items-center justify-between gap-2.5 rounded-[10px] border px-[11px] py-[9px]"
            style={{
                background: overspent
                    ? 'rgba(229,72,77,.08)'
                    : 'rgba(18,161,80,.08)',
                borderColor: overspent
                    ? 'rgba(229,72,77,.3)'
                    : 'rgba(18,161,80,.28)',
                color: overspent ? 'var(--rz-error)' : 'var(--rz-green-fg)',
            }}
        >
            <span className="text-[10.5px] font-bold tracking-[0.08em]">
                {overspent
                    ? 'COSTS EXCEED WHAT YOU MADE'
                    : 'LEFT OVER EACH MONTH'}
            </span>
            <span className="rz-num text-[13px] font-bold">
                {overspent ? 'Check the figures' : formatFull(surplus)}
            </span>
        </div>
    );
}

function PanelSelect({
    label,
    value,
    placeholder,
    options,
    onChange,
}: {
    label: string;
    value: string;
    placeholder: string;
    options: string[];
    onChange: (value: string) => void;
}) {
    return (
        <select
            value={value}
            aria-label={label}
            onChange={(event) => onChange(event.target.value)}
            className="rz-ipt h-[42px] w-full cursor-pointer appearance-none rounded-[10px] border border-[var(--rz-input-border)] bg-[var(--rz-input-bg)] px-[11px] text-[13.5px] text-[var(--rz-fg)] outline-none"
        >
            <option value="" style={{ background: '#12141b' }}>
                {placeholder}
            </option>
            {options.map((option) => (
                <option
                    key={option}
                    value={option}
                    style={{ background: '#12141b' }}
                >
                    {option}
                </option>
            ))}
        </select>
    );
}

function FieldLabel({ children }: { children: ReactNode }) {
    return (
        <div className="mb-1.5 text-[11px] font-semibold text-[var(--rz-muted)]">
            {children}
        </div>
    );
}

function ResultFigure({
    label,
    color,
    last = false,
    children,
}: {
    label: string;
    color: string;
    last?: boolean;
    children: ReactNode;
}) {
    return (
        <div
            className={`flex-1 py-3 text-center ${
                last ? '' : 'border-r border-[var(--rz-line-soft)]'
            }`}
        >
            <div className="rz-num text-[16px] font-bold" style={{ color }}>
                {children}
            </div>
            <div className="mt-1 text-[9.5px] tracking-[0.06em] text-[var(--rz-dim)]">
                {label}
            </div>
        </div>
    );
}

/**
 * The band and score a business is sized at.
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
                className={`rz-num ${compact ? 'text-[9px]' : 'text-[11.5px]'} font-bold text-[var(--rz-rating)] opacity-[0.62] dark:text-[var(--rz-rating-dark)]`}
            >
                {rating.score}
            </span>
        </span>
    );
}
