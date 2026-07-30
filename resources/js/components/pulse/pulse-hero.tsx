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
                labelColor="var(--rz-hero-label-blue)"
                figure={formatNumber(pledged)}
            />
            <TractionCard
                accent="#12a150"
                label="AVG PRE-QUALIFIED LOAN"
                labelColor="var(--rz-hero-label-green)"
                figure={averageLoan === null ? '—' : formatAverage(averageLoan)}
                currency={averageLoan !== null}
            />
        </div>
    );
}

function TractionCard({
    accent,
    label,
    labelColor,
    figure,
    currency = true,
}: {
    accent: string;
    label: string;
    labelColor: string;
    figure: string;
    currency?: boolean;
}) {
    return (
        <div className="flex items-center gap-[7px] rounded-[10px] border border-[var(--rz-hero-card-border)] bg-[var(--rz-hero-card-bg)] px-[9px] py-2.5 md:gap-[14px] md:rounded-[14px] md:px-5 md:py-[17px]">
            <span
                className="w-[2.5px] shrink-0 self-stretch rounded-[var(--rz-hero-bar-radius)] md:w-1"
                style={{ background: accent }}
            />
            <div className="min-w-0">
                <div
                    className="text-[8px] font-extrabold tracking-[var(--rz-hero-label-tracking)] whitespace-nowrap md:text-[12px]"
                    style={{ color: labelColor }}
                >
                    {label}
                </div>
                <div className="rz-num mt-1 text-[16px] font-bold tracking-[var(--rz-hero-fig-tracking)] whitespace-nowrap text-[var(--rz-fg-strong)] md:mt-[7px] md:text-[29px]">
                    {currency ? `RWF ${figure}` : figure}
                </div>
            </div>
        </div>
    );
}

/**
 * The two intents the page opens on: back a business, or raise on your own
 * revenue. Each jumps to the panel that carries it out.
 */
export function PulseHero({
    onInvest,
    onBorrow,
}: {
    onInvest: () => void;
    onBorrow: () => void;
}) {
    return (
        <div className="pt-8 pb-[22px] md:pt-[74px] md:pb-[34px]">
            <div className="inline-flex items-center gap-2 rounded-full border border-[rgba(10,92,255,0.28)] bg-[rgba(10,92,255,0.08)] px-[13px] py-1.5 text-[9.5px] font-bold tracking-[0.1em] whitespace-nowrap text-[var(--rz-badge-fg)] md:text-[11px] md:tracking-[0.14em]">
                <span className="h-1.5 w-1.5 rounded-full bg-[#0a5cff]" />
                LAUNCHING SOON · REGISTER NOW
            </div>
            <div className="mt-[22px] grid grid-cols-1 gap-[22px] md:grid-cols-2 md:gap-[18px]">
                <IntentPanel
                    label="FOR INVESTORS"
                    labelColor="var(--rz-blue-fg)"
                    background="var(--rz-intent-blue-bg)"
                    borderColor="var(--rz-intent-blue-border)"
                    action="I want to invest"
                    actionBackground="#0a5cff"
                    onClick={onInvest}
                    heading={
                        <>
                            Earn up to <HeroMark tone="blue">15%</HeroMark>{' '}
                            backing profitable Rwandan businesses.
                        </>
                    }
                >
                    Invest in businesses of your choice from as little as{' '}
                    <strong className="font-semibold text-[var(--rz-fg)]">
                        RWF 5,000
                    </strong>
                    .
                </IntentPanel>
                <IntentPanel
                    label="FOR BUSINESSES"
                    labelColor="var(--rz-green-fg)"
                    background="var(--rz-intent-green-bg)"
                    borderColor="var(--rz-intent-green-border)"
                    action="I want a business loan"
                    actionBackground="#0f7a3d"
                    onClick={onBorrow}
                    heading={
                        <>
                            Raise <HeroMark tone="green">RWF 5M–50M</HeroMark>{' '}
                            on your revenue. No collateral.
                        </>
                    }
                >
                    For businesses with&nbsp;
                    <strong className="font-semibold text-[var(--rz-fg)]">
                        RWF 15M and up
                    </strong>{' '}
                    in revenue a year and{' '}
                    <b className="font-semibold text-[var(--rz-fg)]">
                        clear profit
                    </b>
                    .
                </IntentPanel>
            </div>
        </div>
    );
}

function IntentPanel({
    label,
    labelColor,
    background,
    borderColor,
    heading,
    action,
    actionBackground,
    onClick,
    children,
}: {
    label: string;
    labelColor: string;
    background: string;
    borderColor: string;
    heading: ReactNode;
    action: string;
    actionBackground: string;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <div
            className="flex flex-col items-start rounded-2xl border px-[14px] py-[15px] md:p-[22px]"
            style={{ background, borderColor }}
        >
            <span
                className="text-[10.5px] font-bold tracking-[0.16em]"
                style={{ color: labelColor }}
            >
                {label}
            </span>
            <h2 className="mt-3 text-[21px] leading-[1.07] font-bold tracking-[-0.028em] text-[var(--rz-fg-strong)] md:text-[clamp(22px,2.4vw,31px)]">
                {heading}
            </h2>
            <p className="mt-3 text-[14px] leading-[1.5] text-[var(--rz-strong)]">
                {children}
            </p>
            <button
                type="button"
                onClick={onClick}
                className="mt-auto cursor-pointer border-none bg-transparent pt-[18px]"
            >
                <span
                    className="flex items-center justify-center gap-[7px] rounded-[11px] px-2 py-3 text-[12px] font-bold whitespace-nowrap text-white md:px-5 md:py-[13px] md:text-[13.5px]"
                    style={{ background: actionBackground }}
                >
                    {action} <span className="shrink-0">→</span>
                </span>
            </button>
        </div>
    );
}

/**
 * The highlighted run inside an intent heading.
 */
function HeroMark({
    tone,
    children,
}: {
    tone: 'blue' | 'green';
    children: ReactNode;
}) {
    const color =
        tone === 'blue' ? 'var(--rz-mark-blue-fg)' : 'var(--rz-mark-green-fg)';
    const background =
        tone === 'blue' ? 'var(--rz-mark-blue-bg)' : 'var(--rz-mark-green-bg)';

    return (
        <span
            className="rounded-[0.14em]"
            style={{
                color,
                background,
                boxShadow: `-.1em 0 0 ${background}, .1em 0 0 ${background}`,
            }}
        >
            {children}
        </span>
    );
}
