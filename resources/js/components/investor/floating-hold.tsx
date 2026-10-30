import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { useServerNow } from '@/lib/investor/server-clock';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { SharedCheckoutHolds } from '@/types/investor';
import type { RouteLink } from '@/types/routing';

/** Inside the last minute the countdown turns red, as the checkout's own hold clock does when it ends. */
const URGENT_SECONDS = 60;

const pad = (value: number) => String(value).padStart(2, '0');

function ClockGlyph() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden
            className="size-[15px] shrink-0"
        >
            <circle
                cx="12"
                cy="13"
                r="8"
                stroke="currentColor"
                strokeWidth="2"
            />
            <path
                d="M12 9v4l2.5 2M9.5 2.8h5"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

/**
 * The floating "Held for checkout" countdown on every Investor page (the shared `checkout_holds`
 * prop). Collapsed, it is a small pill with the soonest hold's MM:SS against the server clock; on
 * hover or keyboard focus it opens to the deal, its notes and amount, and "Continue to checkout".
 * The pill itself always opens that deal's checkout, so a tap on a phone goes straight there. A
 * hold whose clock has run out is dropped; the server still decides expiry.
 */
export function FloatingHold({
    wallet,
    raised,
}: {
    /** The wallet, which lists every hold, for "+N more"; null before the identity is verified. */
    wallet: RouteLink | null;
    /** Sit above the phone's bottom tab bar. */
    raised: boolean;
}) {
    const shared = usePage().props.checkout_holds as
        | SharedCheckoutHolds
        | null
        | undefined;

    if (!shared || shared.holds.length === 0) {
        return null;
    }

    return <HoldCountdown shared={shared} wallet={wallet} raised={raised} />;
}

function HoldCountdown({
    shared,
    wallet,
    raised,
}: {
    shared: SharedCheckoutHolds;
    wallet: RouteLink | null;
    raised: boolean;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const now = useServerNow(shared.server_time, true);
    const live = shared.holds.filter(
        (hold) => Date.parse(hold.expires_at) > now,
    );
    const hold = live[0];

    if (hold === undefined) {
        return null;
    }

    const seconds = Math.max(
        0,
        Math.floor((Date.parse(hold.expires_at) - now) / 1000),
    );
    const time = `${pad(Math.floor(seconds / 60))}:${pad(seconds % 60)}`;
    const urgent = seconds <= URGENT_SECONDS;
    const more = live.length - 1;

    return (
        <aside
            aria-label={t('investor.wallet.c3.holds')}
            data-testid="floating-hold"
            onMouseEnter={() => setOpen(true)}
            onMouseLeave={() => setOpen(false)}
            onFocus={() => setOpen(true)}
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget)) {
                    setOpen(false);
                }
            }}
            className={cn(
                'fixed right-4 z-30 flex flex-col items-end lg:right-6 lg:bottom-6',
                raised
                    ? 'bottom-[calc(75px+env(safe-area-inset-bottom))]'
                    : 'bottom-[calc(16px+env(safe-area-inset-bottom))]',
            )}
        >
            <Link
                href={hold.link}
                aria-label={t('investor.hold_float.label', { time })}
                className={cn(
                    'block overflow-hidden border border-white/10 bg-[#0b1c3a] text-white shadow-[0_14px_34px_-14px_rgba(11,28,58,.65)] transition-[width,border-radius] duration-200 motion-reduce:transition-none',
                    open ? 'w-[284px] rounded-[18px]' : 'w-auto rounded-full',
                )}
            >
                {open ? (
                    <span className="block p-3.5">
                        <span className="flex items-center justify-between gap-3">
                            <span className="text-[10px] font-bold tracking-[.08em] text-white/70 uppercase">
                                {t('investor.wallet.c3.holds')}
                            </span>
                            <span
                                role="timer"
                                aria-live="off"
                                className={cn(
                                    'flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[12.5px] font-bold tabular-nums',
                                    urgent
                                        ? 'bg-[#fdeaea] text-[#b42318]'
                                        : 'bg-white/12 text-white',
                                )}
                            >
                                <ClockGlyph />
                                {time}
                            </span>
                        </span>
                        <span className="mt-2 block truncate text-[14px] font-semibold">
                            {t('investor.wallet.c3.hold_line', {
                                name: hold.deal_name,
                                count: Number(hold.units),
                            })}
                        </span>
                        <span className="mt-0.5 block text-[12px] text-white/70 tabular-nums">
                            {formatRwf(hold.amount)} ·{' '}
                            {t('investor.wallet.c3.hold_expires', { time })}
                        </span>
                        <span className="mt-3 flex items-center justify-center gap-1.5 rounded-[11px] bg-white px-3 py-2 text-[12.5px] font-semibold text-[#0b1c3a]">
                            {t('investor.hold_float.continue')}
                            <span aria-hidden>→</span>
                        </span>
                    </span>
                ) : (
                    <span
                        role="timer"
                        aria-live="off"
                        className={cn(
                            'flex items-center gap-2 py-2 pr-3.5 pl-3 text-[12.5px] font-semibold tabular-nums',
                            urgent && 'text-[#ffb4ab]',
                        )}
                    >
                        <ClockGlyph />
                        <span>{time}</span>
                        {more > 0 && (
                            <span className="rounded-full bg-white/15 px-1.5 text-[11px]">
                                +{more}
                            </span>
                        )}
                    </span>
                )}
            </Link>
            {open && more > 0 && wallet !== null && (
                <Link
                    href={wallet}
                    className="mt-1.5 rounded-full border border-rz-border bg-rz-surface px-3 py-1 text-[11.5px] font-semibold text-rz-secondary shadow-sm"
                >
                    {t('investor.hold_float.more', { count: more })}
                </Link>
            )}
        </aside>
    );
}
