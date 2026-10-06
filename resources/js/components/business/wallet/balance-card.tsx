import { Link } from '@inertiajs/react';
import { LogoMark } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessWalletProps } from '@/types/business';

/**
 * The wallet card (design L1321–1334) in C4 terms: the available balance repayments are paid
 * from, with recorded deposits that are not yet credited beneath and outside it. A restriction
 * never withholds deposits or repayments (§11.4), so the card says so rather than blocking.
 */
export function BalanceCard({
    wallet,
    basis,
}: {
    wallet: BusinessWalletProps['wallet'];
    basis: BusinessWalletProps['bases']['available'];
}) {
    const { t, locale } = useTranslation();
    const amount = formatRwf(wallet.available);

    return (
        <section
            aria-label={t('business.wallet.available')}
            className="relative mt-3.5 overflow-hidden rounded-[20px] bg-[linear-gradient(135deg,#2440ff_0%,#17795a_58%,#10228a_100%)] p-5"
        >
            <span className="pointer-events-none absolute -top-[42px] -right-8 size-[150px] rounded-full bg-[radial-gradient(circle,rgba(255,255,255,.16),rgba(255,255,255,0)_70%)]" />
            <span className="pointer-events-none absolute -bottom-[54px] -left-6 size-[132px] rounded-full bg-[radial-gradient(circle,rgba(255,255,255,.09),rgba(255,255,255,0)_70%)]" />
            <div className="relative flex items-center justify-between">
                <span className="text-[10.5px] font-bold tracking-[.08em] text-white uppercase">
                    {t('business.wallet.available')}
                </span>
                <span className="inline-flex items-center gap-[5px] rounded-[10px] bg-[rgba(0,0,0,.14)] px-[9px] py-1 text-[10px] font-semibold text-white">
                    <span
                        className={cn(
                            'size-1.5 rounded-full',
                            wallet.status === 'active'
                                ? 'bg-[#6ae8bf]'
                                : 'bg-[#ffc44f]',
                        )}
                    />
                    {t(`business.servicing.wallet.status.${wallet.status}`)}
                </span>
            </div>
            <p className="relative mt-2.5 text-[30px] font-bold tracking-[-.6px] text-white">
                {basis === undefined ? (
                    amount
                ) : (
                    <Link
                        href={basis}
                        className="underline decoration-white/40 underline-offset-4"
                    >
                        {amount}
                    </Link>
                )}
            </p>
            {wallet.restriction !== null && (
                <p
                    role="status"
                    className="relative mt-2 rounded-[10px] bg-white/[.14] px-2.5 py-1.5 text-[11px] text-white"
                >
                    {t('business.servicing.wallet.restricted', {
                        date: formatDate(wallet.restriction.since, locale),
                    })}
                </p>
            )}
            <div className="relative mt-4 flex items-center justify-between border-t border-[rgba(255,255,255,.16)] pt-[13px]">
                <span className="text-[11.5px] text-white">
                    {wallet.pending_deposits.amount === '0'
                        ? t('business.servicing.wallet.no_pending')
                        : t('business.servicing.wallet.pending', {
                              amount: formatRwf(wallet.pending_deposits),
                          })}
                </span>
                <span className="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[rgba(255,255,255,.9)]">
                    <LogoMark className="size-[15px] text-white opacity-90" />
                    {t('business.wallet.brand')}
                </span>
            </div>
        </section>
    );
}
