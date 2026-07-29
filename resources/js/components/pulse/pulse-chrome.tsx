import type { CSSProperties, ReactNode } from 'react';
import {
    DownloadIcon,
    LinkedInIcon,
    MailIcon,
    MoonIcon,
    ShieldIcon,
    SunIcon,
} from '@/components/pulse/icons';
import { PulseWordmark } from '@/components/pulse/pulse-wordmark';
import { useAppearance } from '@/hooks/use-appearance';
import { STEPS, TRUST_MARKERS } from '@/lib/pulse';

/**
 * Flips the page between its light and dark cut.
 */
function ThemeToggle() {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const goingDark = resolvedAppearance === 'light';

    return (
        <button
            type="button"
            onClick={() => updateAppearance(goingDark ? 'dark' : 'light')}
            title={goingDark ? 'Switch to dark' : 'Switch to light'}
            aria-label={goingDark ? 'Switch to dark' : 'Switch to light'}
            className="inline-flex h-[30px] w-[30px] cursor-pointer items-center justify-center rounded-lg border border-[var(--rz-chip-border)] bg-[var(--rz-icon-btn-bg)] text-[var(--rz-strong)]"
        >
            {goingDark ? <MoonIcon /> : <SunIcon />}
        </button>
    );
}

/**
 * The glow and grid that sit behind the page.
 */
export function PulseBackdrop() {
    return (
        <div className="pointer-events-none absolute inset-0 overflow-hidden">
            <div className="absolute -top-[340px] left-1/2 h-[700px] w-[1200px] -translate-x-1/2 bg-[radial-gradient(closest-side,var(--rz-glow),transparent_72%)]" />
            <div className="absolute inset-0 bg-[linear-gradient(var(--rz-grid-line)_1px,transparent_1px),linear-gradient(90deg,var(--rz-grid-line)_1px,transparent_1px)] [mask-image:linear-gradient(#000,transparent_70%)] [background-size:52px_52px] [-webkit-mask-image:linear-gradient(#000,transparent_70%)]" />
        </div>
    );
}

/**
 * The sticky masthead.
 */
export function PulseTopbar() {
    return (
        <div className="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-[var(--rz-topbar-border)] bg-[var(--rz-topbar-bg)] py-[13px] backdrop-blur-[14px] md:py-4">
            <div className="mx-auto flex w-full max-w-[1120px] items-center justify-between gap-4 px-2 md:px-[26px]">
                <div className="flex items-center gap-[11px]">
                    <PulseWordmark className="h-[27px] w-auto object-contain" />
                    <span className="mx-[3px] h-5 w-px bg-[var(--rz-divider)]" />
                    <span className="text-[11px] font-bold tracking-[0.24em] text-[var(--rz-dim-2)]">
                        PULSE
                    </span>
                </div>
                <div className="flex items-center gap-3">
                    <ThemeToggle />
                    <div className="flex items-center gap-[9px] text-[11px] font-semibold tracking-[0.14em] text-[var(--rz-muted)]">
                        <span className="h-1.5 w-1.5 animate-[rzp-blink_1.6s_ease-in-out_infinite] rounded-full bg-[var(--rz-green-strong)]" />
                        LIVE
                    </div>
                </div>
            </div>
        </div>
    );
}

/**
 * The three-step explainer.
 */
export function PulseSteps() {
    return (
        <div className="mt-[72px] grid grid-cols-1 gap-4 md:grid-cols-3">
            {STEPS.map((step) => (
                <div
                    key={step.no}
                    className="border-t border-[var(--rz-line)] py-5"
                >
                    <div className="rz-num text-[13px] font-bold text-[var(--rz-dim-2)]">
                        {step.no}
                    </div>
                    <div className="mt-3 text-[15px] font-semibold text-[var(--rz-fg)]">
                        {step.title}
                    </div>
                    <div className="mt-[5px] text-[13px] leading-[1.5] text-[var(--rz-dim-2)]">
                        {step.sub}
                    </div>
                </div>
            ))}
        </div>
    );
}

/**
 * The two briefs that explain what Pulse is sizing, one for each side of the
 * waitlist. Both are handed over as a PDF rather than opened in a tab.
 */
export function PulseBriefs() {
    return (
        <div className="mt-11 border-t border-[var(--rz-line)] pt-[26px]">
            <div className="text-[11px] font-bold tracking-[0.16em] text-[var(--rz-dim-2)]">
                LEARN MORE ABOUT ROZINE
            </div>
            <div className="mt-3.5 grid grid-cols-1 gap-3 md:grid-cols-2">
                <BriefCard
                    href="/briefs/rozine-investor-brief.pdf"
                    title="Investor brief"
                    accent="#0a5cff"
                    tint="rgba(10,92,255,.09)"
                >
                    How investing on Rozine works: Yields, ratings and how your
                    money is handled.
                </BriefCard>
                <BriefCard
                    href="/briefs/rozine-business-brief.pdf"
                    title="Business brief"
                    accent="var(--rz-green-fg)"
                    tint="rgba(16,161,80,.1)"
                >
                    How business loans work on Rozine: What you qualify for,
                    what it costs, how we calculate.
                </BriefCard>
            </div>
        </div>
    );
}

function BriefCard({
    href,
    title,
    accent,
    tint,
    children,
}: {
    href: string;
    title: string;
    accent: string;
    tint: string;
    children: ReactNode;
}) {
    return (
        <a
            href={href}
            download
            className="flex items-center gap-[13px] rounded-[13px] border border-[var(--rz-hero-card-border)] bg-[var(--rz-hero-card-bg)] px-4 py-[15px] no-underline"
        >
            <span
                className="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[10px]"
                style={{ background: tint, color: accent }}
            >
                <DownloadIcon size={18} />
            </span>
            <span className="min-w-0">
                <span className="block text-[14px] font-semibold text-[var(--rz-fg)]">
                    {title}
                </span>
                <span className="mt-0.5 block text-[11.5px] leading-[1.4] text-[var(--rz-muted)]">
                    {children}
                </span>
            </span>
        </a>
    );
}

/**
 * The reminder that no money changes hands.
 */
export function PulseDisclaimer() {
    return (
        <div className="mt-9 flex justify-center">
            <div className="inline-flex items-center gap-[11px] rounded-xl border border-[var(--rz-line)] bg-[var(--rz-tile-bg)] px-5 py-3 text-[var(--rz-hint)]">
                <ShieldIcon />
                <span className="text-[12.5px] text-[var(--rz-muted-2)]">
                    <span className="font-semibold text-[var(--rz-strong)]">
                        Non-binding demand simulation.
                    </span>{' '}
                    No funds are collected or issued.
                </span>
            </div>
        </div>
    );
}

/**
 * The trust markers and contact details.
 */
export function PulseFooter() {
    return (
        <div className="mt-9 flex flex-wrap items-center justify-between gap-[14px] border-t border-[var(--rz-line)] pt-4 pb-[60px]">
            <div className="flex items-center gap-[22px]">
                {TRUST_MARKERS.map((marker) => (
                    <span
                        key={marker.label}
                        className="flex items-center gap-[7px] text-[11.5px] font-semibold tracking-[0.04em] text-[var(--rz-dim-2)]"
                    >
                        <span
                            className="h-1.5 w-1.5 rounded-full bg-[var(--rz-marker)] dark:bg-[var(--rz-marker-dark)]"
                            style={
                                {
                                    '--rz-marker': marker.color.light,
                                    '--rz-marker-dark': marker.color.dark,
                                } as CSSProperties
                            }
                        />
                        {marker.label}
                    </span>
                ))}
            </div>
            <div className="mt-[14px] flex items-center gap-[11px] text-[13px] font-semibold text-[var(--rz-strong)]">
                <span className="flex items-center gap-[9px]">
                    From <PulseWordmark className="h-4 w-auto opacity-95" />
                </span>
                <a
                    href="mailto:hello@rozine.rw"
                    title="Email"
                    className="inline-flex h-[30px] w-[30px] items-center justify-center rounded-lg border border-[var(--rz-chip-border)] bg-[var(--rz-icon-btn-bg)] text-[var(--rz-strong)]"
                >
                    <MailIcon />
                </a>
                <a
                    href="https://www.linkedin.com/company/rozine"
                    title="LinkedIn"
                    className="inline-flex h-[30px] w-[30px] items-center justify-center rounded-lg border border-[var(--rz-chip-border)] bg-[var(--rz-icon-btn-bg)] text-[var(--rz-strong)]"
                >
                    <LinkedInIcon />
                </a>
            </div>
        </div>
    );
}
