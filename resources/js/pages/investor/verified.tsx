import { Link } from '@inertiajs/react';
import { InvestorAuthFrame } from '@/components/investor/auth/auth-frame';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import type { InvestorVerifiedProps } from '@/types/investor';

/**
 * "You're verified" (design L3657–3672): verification is done, the wallet is ready, and the open
 * deals are one tap away. The count and the balance are the server's.
 */
export default function InvestorVerified({
    wallet,
    open_deals: openDeals,
    links,
}: InvestorVerifiedProps) {
    const { t } = useTranslation();

    return (
        <InvestorAuthFrame title={t('investor.verified.title')} layout="column">
            <div className="flex min-h-svh flex-1 flex-col items-center justify-center px-7 text-center lg:min-h-[694px]">
                <span className="flex size-24 animate-[rz-pop_.5s_ease] items-center justify-center rounded-full bg-[rgba(29,158,117,.10)]">
                    <span className="flex size-[66px] items-center justify-center rounded-full bg-[#17795a]">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className="size-[34px]"
                        >
                            <path
                                d="M5 13l4 4L19 7"
                                stroke="#fff"
                                strokeWidth="3"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                    </span>
                </span>
                <h1 className="mt-[26px] flex items-center gap-1.5 text-[25px] font-semibold text-rz-ink">
                    {t('investor.verified.title')}
                    <Icon name="celebrate" />
                </h1>
                <p className="mt-2.5 text-[14.5px] leading-[1.6] text-rz-secondary">
                    {t('investor.verified.body', { count: openDeals })}
                </p>
                <div className="mt-6 flex w-full gap-3">
                    <div className="flex-1 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                        <p className="text-[11px] font-semibold text-rz-secondary uppercase">
                            {t('investor.verified.kyc')}
                        </p>
                        <p className="mt-1 text-[15px] font-semibold text-[#17795a] dark:text-[#3fcda0]">
                            {t('investor.verified.verified')}
                        </p>
                    </div>
                    <div className="flex-1 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                        <p className="text-[11px] font-semibold text-rz-secondary uppercase">
                            {t('investor.verified.wallet')}
                        </p>
                        <p className="mt-1 text-[15px] font-semibold text-rz-ink">
                            {formatRwf(wallet.available)}
                        </p>
                    </div>
                </div>
                <Link
                    href={links.deals}
                    className="mt-6 flex h-[52px] w-full items-center justify-center rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white"
                >
                    {t('investor.verified.cta')}
                </Link>
            </div>
        </InvestorAuthFrame>
    );
}
