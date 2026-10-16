import { Link } from '@inertiajs/react';
import { InvestorShell } from '@/components/investor/investor-shell';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import { cn } from '@/lib/utils';
import type { InvestorShellPageProps } from '@/types/investor';

/**
 * Cart (design L2832–2950): notes picked from Deals, each with its own amount, checked out at once.
 * There is no cart read or command yet, so the page shows the design's empty cart and its way back
 * to Deals.
 */
export default function InvestorCart({ links }: InvestorShellPageProps) {
    const { t } = useTranslation();
    const wide = useWide();

    const header = (
        <div
            className={
                wide ? 'pb-3' : 'px-5 pt-[calc(env(safe-area-inset-top)+56px)]'
            }
        >
            <h1
                className={cn(
                    'text-rz-ink',
                    wide ? 'text-[19px] font-bold' : 'text-2xl font-semibold',
                )}
            >
                {t('investor.cart.title')}
            </h1>
        </div>
    );
    const empty = (
        <div className="flex flex-col items-center px-6 py-[70px] text-center">
            <span className="flex size-14 items-center justify-center rounded-[18px] bg-rz-accent-soft text-rz-accent-text">
                <svg
                    width="26"
                    height="26"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden
                >
                    <path
                        d="M2.8 4h2.3l2.2 10.3a1.6 1.6 0 0 0 1.6 1.3h8.3a1.6 1.6 0 0 0 1.56-1.22L20.6 8H6"
                        stroke="currentColor"
                        strokeWidth="1.8"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                    <circle cx="9.6" cy="19.6" r="1.35" fill="currentColor" />
                    <circle cx="17" cy="19.6" r="1.35" fill="currentColor" />
                </svg>
            </span>
            <p className="mt-3.5 text-base font-semibold text-rz-ink">
                {t('investor.cart.empty_title')}
            </p>
            <p className="mt-1.5 max-w-[260px] text-[13px] leading-normal text-pretty text-rz-secondary">
                {t('investor.cart.empty_body')}
            </p>
            {links.deals !== null && (
                <Link
                    href={links.deals}
                    className="mt-[18px] rounded-xl bg-rz-accent-fill px-[22px] py-3 text-sm font-semibold text-white"
                >
                    {t('investor.cart.browse_deals')}
                </Link>
            )}
        </div>
    );

    return (
        <InvestorShell
            title={t('investor.cart.title')}
            tab="cart"
            links={links}
        >
            {wide ? (
                <div className="grid h-full grid-cols-[minmax(0,1fr)_300px] gap-[18px] px-[18px] py-4">
                    <div className="min-w-0">
                        {header}
                        {empty}
                    </div>
                </div>
            ) : (
                <div className="flex min-h-full flex-col">
                    {header}
                    {empty}
                </div>
            )}
        </InvestorShell>
    );
}
