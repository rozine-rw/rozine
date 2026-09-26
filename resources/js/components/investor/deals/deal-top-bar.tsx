import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { formatCompact } from '@/lib/investor/format';
import { formatDayMonth, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { WalletSummary } from '@/types/investor';

type DealTopBarProps = {
    wallet: WalletSummary;
    unread: number;
    links: {
        wallet: RouteLink;
        deposit: RouteLink;
        notifications: RouteLink;
    };
};

/** The design's wallet glyph (Investor L536 / L141): a card with a clasp. */
function WalletGlyph({ className }: { className: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden className={className}>
            <path
                d="M4.2 8.2V6.9c0-1.3.9-2.4 2.2-2.6l8.7-1.5c.9-.16 1.7.5 1.7 1.4v2.6"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
            />
            <rect
                x="2.9"
                y="7.3"
                width="18.2"
                height="12.9"
                rx="3.4"
                fill="currentColor"
                fillOpacity=".12"
                stroke="currentColor"
                strokeWidth="1.6"
            />
            <path
                d="M21.1 11.7h-4.4a2.1 2.1 0 0 0 0 4.2h4.4"
                className="fill-rz-surface"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinejoin="round"
            />
            <circle cx="18.4" cy="13.8" r="1.05" fill="currentColor" />
        </svg>
    );
}

function Bell({
    unread,
    href,
    size,
}: {
    unread: number;
    href: RouteLink;
    size: 'phone' | 'desk';
}) {
    const { t } = useTranslation();

    return (
        <Link
            href={href}
            aria-label={
                unread > 0
                    ? t('investor.deals.notifications_unread', {
                          count: unread,
                      })
                    : t('investor.deals.notifications')
            }
            className={cn(
                'relative flex shrink-0 items-center justify-center border border-rz-border bg-rz-surface text-[#16233c] dark:text-rz-ink',
                size === 'phone'
                    ? 'size-[34px] rounded-xl'
                    : 'ml-auto size-[46px] rounded-2xl text-[#46526b] dark:text-rz-slate',
            )}
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden
                className={size === 'phone' ? 'size-[17px]' : 'size-5'}
            >
                <path
                    d="M6 9a6 6 0 1 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    strokeLinejoin="round"
                />
                <path
                    d="M10 19a2 2 0 0 0 4 0"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    strokeLinecap="round"
                />
            </svg>
            {unread > 0 && (
                <span
                    className={cn(
                        'absolute rounded-full border-2 border-rz-surface bg-[#b3383c]',
                        size === 'phone'
                            ? 'top-1.5 right-[7px] size-[7px]'
                            : 'top-[11px] right-3 size-2',
                    )}
                />
            )}
        </Link>
    );
}

function nextPayoutLabel(wallet: WalletSummary, locale: string): string | null {
    if (wallet.next_payout === null) {
        return null;
    }

    return `+${formatCompact(wallet.next_payout.amount)} · ${formatDayMonth(wallet.next_payout.due_on, locale)}`;
}

/**
 * Phone top bar (design L533–557): the wallet pill (balance and the next payout) and the bell.
 * The design's "10%" charge chip is the Rozine Plus band ladder, outside the MVP, so the pill
 * takes its width.
 */
export function PhoneTopBar({ wallet, unread, links }: DealTopBarProps) {
    const { t, locale } = useTranslation();
    const payout = nextPayoutLabel(wallet, locale);

    return (
        <div className="flex items-center gap-[9px] px-3.5 pt-[calc(env(safe-area-inset-top)+12px)] pb-1 lg:hidden">
            <div className="flex h-[34px] min-w-0 flex-auto items-center gap-[7px] rounded-xl border border-rz-border bg-rz-surface px-[9px]">
                <span className="flex size-6 shrink-0 items-center justify-center rounded-lg bg-rz-accent-soft text-rz-accent-app-text">
                    <WalletGlyph className="size-3.5" />
                </span>
                <Link
                    href={links.wallet}
                    aria-label={t('investor.deals.wallet_balance')}
                    className="flex min-w-0 items-baseline gap-1.5 text-left"
                >
                    <span className="text-[13.5px] font-bold tracking-[-.3px] whitespace-nowrap text-rz-ink">
                        {formatRwf(wallet.available)}
                    </span>
                    {payout !== null && (
                        <span className="truncate text-[10px] font-semibold text-[#17795a] dark:text-[#3fcda0]">
                            {payout}
                        </span>
                    )}
                </Link>
            </div>
            <Bell unread={unread} href={links.notifications} size="phone" />
        </div>
    );
}

/**
 * Wide-screen top bar (design L140–194): balance card with deposit, and the bell. Withdrawal stays
 * hidden until it has its own contract (C3 v2, H6).
 */
export function DeskTopBar({ wallet, unread, links }: DealTopBarProps) {
    const { t, locale } = useTranslation();
    const payout = nextPayoutLabel(wallet, locale);

    return (
        <div className="flex shrink-0 items-center gap-3.5 pt-1.5 pb-3.5">
            <div className="flex items-center gap-2.5 rounded-2xl border border-rz-border bg-rz-surface px-3 py-[9px]">
                <span className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft text-rz-accent-app-text">
                    <WalletGlyph className="size-[21px]" />
                </span>
                <Link
                    href={links.wallet}
                    aria-label={t('investor.deals.wallet_balance')}
                    className="leading-[1.15]"
                >
                    <span className="block text-lg font-bold tracking-[-.3px] text-rz-ink">
                        {formatRwf(wallet.available)}
                    </span>
                    {payout !== null && (
                        <span className="mt-px block text-[10px] font-semibold whitespace-nowrap text-[#17795a] dark:text-[#3fcda0]">
                            {payout}
                        </span>
                    )}
                </Link>
                <Link
                    href={links.deposit}
                    aria-label={t('investor.deals.deposit')}
                    className="flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-fill"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="size-4"
                    >
                        <path
                            d="M12 5v14M5 12l7 7 7-7"
                            stroke="#fff"
                            strokeWidth="2.2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </Link>
            </div>
            <Bell unread={unread} href={links.notifications} size="desk" />
        </div>
    );
}
