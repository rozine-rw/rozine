import type { ReactNode } from 'react';
import { formatAverage, formatNumber } from '@/lib/pulse';

/**
 * The pair of traction figures that open the page.
 */
export function TractionCards({
    investors,
    pledged,
    averageLoan,
}: {
    investors: number;
    pledged: number;
    averageLoan: number | null;
}) {
    return (
        <div className="grid grid-cols-2 gap-2 pt-3">
            <TractionCard
                accent="#0a5cff"
                label={`${formatNumber(investors)} INVESTORS PLEDGED`}
                figure={formatNumber(pledged)}
            />
            <TractionCard
                accent="#12a150"
                label="AVG PRE-QUALIFIED LOAN"
                figure={formatAverage(averageLoan)}
                currency={averageLoan !== null}
            />
        </div>
    );
}

function TractionCard({
    accent,
    label,
    figure,
    currency = true,
}: {
    accent: string;
    label: string;
    figure: string;
    currency?: boolean;
}) {
    return (
        <div className="flex items-center gap-[9px] rounded-[10px] border border-[var(--rz-hero-card-border)] bg-[var(--rz-hero-card-bg)] px-[11px] py-[9px] md:gap-[14px] md:rounded-[14px] md:px-5 md:py-4">
            <span
                className="w-[2.5px] shrink-0 self-stretch rounded-[var(--rz-hero-bar-radius)] md:w-1"
                style={{ background: accent }}
            />
            <div className="min-w-0">
                <div className="text-[7.5px] font-bold tracking-[var(--rz-hero-label-tracking)] whitespace-nowrap text-[var(--rz-hero-label)] md:text-[13px]">
                    {label}
                </div>
                <div className="rz-num mt-[3px] text-[12.5px] font-bold tracking-[var(--rz-hero-fig-tracking)] whitespace-nowrap text-[var(--rz-fg-strong)] md:mt-1.5 md:text-[26px]">
                    {currency ? `RWF ${figure}` : figure}
                </div>
            </div>
        </div>
    );
}

/**
 * The headline, promise and the mobile-only jump links into each panel.
 */
export function PulseHero({
    onInvest,
    onBorrow,
}: {
    onInvest: () => void;
    onBorrow: () => void;
}) {
    return (
        <div className="max-w-[820px] pt-8 pb-[22px] md:pt-[74px] md:pb-[34px]">
            <div className="inline-flex items-center gap-2 rounded-full border border-[rgba(10,92,255,0.28)] bg-[rgba(10,92,255,0.08)] px-[13px] py-1.5 text-[11px] font-bold tracking-[0.14em] text-[var(--rz-badge-fg)]">
                <span className="h-1.5 w-1.5 rounded-full bg-[#0a5cff]" />
                EARLY ACCESS REGISTRATION
            </div>
            <h1 className="mt-5 text-[23px] leading-[1.05] font-bold tracking-[-0.033em] text-[var(--rz-fg-strong)] md:text-[clamp(30px,4.6vw,52px)]">
                Invest in profitable Rwandan businesses{' '}
                <span className="text-[var(--rz-hero-accent)]">
                    from just RWF 5,000.
                </span>{' '}
                <span className="text-[#0a5cff]">Earn up to 15%.</span>
            </h1>
            <p className="mt-5 text-[15px] leading-[1.55] text-[var(--rz-muted)]">
                <span className="whitespace-normal md:whitespace-nowrap">
                    Collateral-free growth loans for businesses{' '}
                    <span className="text-[#5f6472]">&nbsp;·&nbsp;</span>{' '}
                    Audited, profitable deals for investors.
                </span>
            </p>
            <p className="mt-4 text-[16px] font-semibold text-[var(--rz-strong)]">
                Save your spot to get priority at launch.
            </p>
            <div className="mt-4 grid grid-cols-2 gap-2.5 md:hidden">
                <HeroButton onClick={onInvest} background="#0a5cff">
                    I want to invest
                </HeroButton>
                <HeroButton onClick={onBorrow} background="#12a150">
                    I want a business loan
                </HeroButton>
            </div>
        </div>
    );
}

function HeroButton({
    onClick,
    background,
    children,
}: {
    onClick: () => void;
    background: string;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            style={{ background }}
            className="flex cursor-pointer items-center justify-center gap-1.5 rounded-[11px] border-none px-2 py-3 text-[12px] font-bold whitespace-nowrap text-white"
        >
            {children} <span className="shrink-0 font-bold">→</span>
        </button>
    );
}
