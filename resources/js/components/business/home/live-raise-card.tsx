import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatRwfShort } from '@/lib/rozine/format';
import type { LiveRaise } from '@/types/business';

/** "RAISING NOW" — the note currently filling (design L166–181). */
export function LiveRaiseCard({ raise }: { raise: LiveRaise }) {
    const { t } = useTranslation();

    return (
        <Link
            href={raise.link}
            className="relative mt-3.5 block w-full rounded-2xl border border-rz-border bg-rz-surface px-[15px] py-3.5 text-left lg:mt-2.5 lg:px-[13px] lg:py-[11px]"
        >
            <span className="flex items-center gap-2">
                <span className="size-[7px] shrink-0 animate-[rz-glowdot_1.6s_ease-in-out_infinite] rounded-full bg-rz-accent-app-text shadow-[0_0_0_3px_rgba(29,158,117,.10)]" />
                <span className="text-[10.5px] font-bold tracking-[.07em] text-rz-slate uppercase">
                    {t('business.home.raising_now')}
                </span>
                <span className="flex-1 truncate text-[13px] font-bold text-rz-ink">
                    {raise.title}
                </span>
                <span className="shrink-0 text-sm font-bold tracking-[-.2px] text-rz-accent-app-text">
                    {raise.funded_pct}%
                </span>
            </span>
            <span
                role="progressbar"
                aria-valuenow={raise.funded_pct}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-label={t('business.home.funded_progress', {
                    title: raise.title,
                })}
                className="mt-2 block h-1.5 overflow-hidden rounded-full bg-[#eef2f7] dark:bg-rz-surface-muted"
            >
                <span
                    className="block h-full rounded-full bg-[linear-gradient(90deg,#17795a,#38d67f)]"
                    style={{ width: `${raise.funded_pct}%` }}
                />
            </span>
            <span className="mt-1.5 flex items-center justify-between gap-2 text-[10.5px] font-semibold text-rz-secondary">
                <span>
                    {t('business.home.raised_of', {
                        raised: formatRwfShort(raise.raised),
                        target: formatRwfShort(raise.target),
                    })}
                </span>
                <span>
                    {t('business.home.investors_link', {
                        count: formatCount(raise.investors),
                    })}
                </span>
            </span>
        </Link>
    );
}
