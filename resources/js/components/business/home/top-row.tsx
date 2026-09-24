import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import type { Money, RouteLink } from '@/types';

type TopRowProps = {
    available: Money;
    unread: number;
    links: {
        wallet: RouteLink;
        deposit: RouteLink;
        withdraw: RouteLink;
        notifications: RouteLink;
    };
};

/** Wallet pill with deposit/withdraw, and the notifications bell (design L103–121). */
export function TopRow({ available, unread, links }: TopRowProps) {
    const { t } = useTranslation();

    return (
        <div className="relative flex items-center gap-[9px]">
            <div className="flex min-w-0 flex-[0_1_auto] items-center gap-[7px] rounded-xl border border-rz-border bg-rz-surface py-[5px] pr-1.5 pl-2.5">
                <span className="flex size-7 shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="size-[15px] text-rz-accent-app-text"
                    >
                        <rect
                            x="3"
                            y="7"
                            width="18"
                            height="12"
                            rx="2.5"
                            stroke="currentColor"
                            strokeWidth="1.8"
                        />
                        <path
                            d="M3 9c0-1.7 1-2.6 2.6-3L16 3.3"
                            stroke="currentColor"
                            strokeWidth="1.8"
                            strokeLinecap="round"
                        />
                        <circle cx="16.5" cy="13" r="1.4" fill="currentColor" />
                    </svg>
                </span>
                <Link
                    href={links.wallet}
                    className="min-w-0 pr-0.5 text-left leading-[1.05]"
                >
                    <span className="block text-[10.5px] font-bold tracking-[.07em] text-rz-slate uppercase">
                        {t('business.home.wallet_balance')}
                    </span>
                    <span className="block truncate text-[13.5px] font-semibold text-rz-ink">
                        {formatRwf(available)}
                    </span>
                </Link>
                <Link
                    href={links.deposit}
                    aria-label={t('business.home.deposit')}
                    className="flex size-[30px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-fill"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="size-[15px]"
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
                <Link
                    href={links.withdraw}
                    aria-label={t('business.home.withdraw')}
                    className="flex size-[30px] shrink-0 items-center justify-center rounded-xl border border-[#e4e9f2] bg-rz-surface-muted dark:border-rz-border"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="size-[15px] text-[#46526b] dark:text-rz-secondary"
                    >
                        <path
                            d="M12 19V5M5 12l7-7 7 7"
                            stroke="currentColor"
                            strokeWidth="2.2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </Link>
            </div>
            <Link
                href={links.notifications}
                aria-label={
                    unread > 0
                        ? t('business.home.notifications_unread', {
                              count: unread,
                          })
                        : t('business.home.notifications')
                }
                className="relative ml-auto flex size-[42px] shrink-0 items-center justify-center rounded-xl border border-rz-border bg-rz-surface"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden
                    className="size-[19px] text-[#16233c] dark:text-rz-ink"
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
                    />
                </svg>
                {unread > 0 && (
                    <span className="absolute top-[9px] right-2.5 size-2 rounded-full border-2 border-rz-surface bg-[#b3383c]" />
                )}
            </Link>
        </div>
    );
}
