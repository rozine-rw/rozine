import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { DealSort, IndustryFilter } from '@/types/investor';

/** The design's industry-tab dot palette, by position (L8634). */
const DOTS = [
    '#0c1830',
    '#17795a',
    '#c2661f',
    '#6425c9',
    '#046a86',
    '#c0392b',
    '#1e3aff',
    '#0f766e',
];

type SortChipsProps = {
    sorts: DealSort[];
    size: 'phone' | 'desk';
};

/**
 * Performance filters: All, Top Interest, Top Rated, Top Picks (phone L585–591, desk L198–202).
 * The server orders the deck; each chip is a link to that ordering. The design's "Saved" chip is
 * the watchlist, outside the MVP, so it is left out.
 */
export function SortChips({ sorts, size }: SortChipsProps) {
    const { t } = useTranslation();

    return (
        <nav
            aria-label={t('investor.deals.sort_label')}
            className={cn(
                'rz-hscroll flex items-center overflow-x-auto',
                size === 'phone' ? 'gap-[7px]' : 'shrink-0 gap-[9px] pb-3.5',
            )}
        >
            {sorts.map((sort) => (
                <Link
                    key={sort.key}
                    href={sort.link}
                    preserveScroll
                    aria-current={sort.active ? 'true' : undefined}
                    className={cn(
                        'shrink-0 tracking-[-.1px] whitespace-nowrap',
                        size === 'phone'
                            ? sort.active
                                ? 'rounded-[10px] bg-rz-accent-fill px-3.5 py-[7px] text-[13px] font-bold text-white'
                                : 'rounded-[10px] border border-rz-border bg-[#f4f6fa] px-2.5 py-[5px] text-[11.5px] font-medium text-rz-secondary dark:bg-rz-surface-muted'
                            : sort.active
                              ? 'rounded-[20px] border border-rz-accent-fill bg-rz-accent-fill px-[15px] py-[7px] text-[12.5px] font-semibold text-white'
                              : 'rounded-[20px] border border-rz-border bg-[#f4f6fa] px-[15px] py-[7px] text-[12.5px] font-semibold text-rz-secondary dark:bg-rz-surface-muted',
                    )}
                >
                    {t(`investor.deals.sort.${sort.key}`)}
                </Link>
            ))}
        </nav>
    );
}

type IndustryTabsProps = {
    industries: IndustryFilter[];
    className: string;
};

/**
 * Industry filter (phone L638–644, desk L328–332): a coloured dot, the industry and its live
 * count, underlined when active. Counts are the server's.
 */
export function IndustryTabs({ industries, className }: IndustryTabsProps) {
    const { t } = useTranslation();
    const filtered = industries.some(
        (entry) => entry.active && entry.industry !== null,
    );

    return (
        <nav
            aria-label={t('investor.deals.industry_label')}
            className={cn('rz-hscroll flex overflow-x-auto', className)}
        >
            {industries.map((entry, index) => {
                const slot = index % DOTS.length;
                /* The first slot is the ink dot, which must follow the theme. */
                const dot = slot === 0 ? undefined : DOTS[slot];

                return (
                    <Link
                        key={entry.industry ?? 'all'}
                        href={entry.link}
                        preserveScroll
                        aria-current={entry.active ? 'true' : undefined}
                        style={
                            entry.active && dot !== undefined
                                ? { borderBottomColor: dot }
                                : undefined
                        }
                        className={cn(
                            'inline-flex shrink-0 items-center gap-1.5 border-b-2 px-0.5 pt-1 pb-1.5 text-xs tracking-[-.1px] whitespace-nowrap transition-[opacity,filter] duration-200',
                            entry.active
                                ? 'border-b-rz-ink font-bold text-rz-ink'
                                : 'border-b-transparent font-semibold text-rz-secondary',
                            !entry.active &&
                                filtered &&
                                'opacity-[.72] blur-[.4px]',
                        )}
                    >
                        <span
                            className={cn(
                                'size-1.5 shrink-0 rounded-full bg-rz-ink',
                                !entry.active && 'opacity-75',
                            )}
                            style={
                                dot === undefined
                                    ? undefined
                                    : { background: dot }
                            }
                        />
                        <span>
                            {entry.industry ?? t('investor.deals.industry_all')}
                        </span>
                        <span
                            className={cn(
                                'text-[11px]',
                                entry.active
                                    ? 'font-bold text-rz-ink opacity-55'
                                    : 'font-semibold text-[#8b95a8]',
                            )}
                        >
                            {entry.count}
                        </span>
                    </Link>
                );
            })}
        </nav>
    );
}
