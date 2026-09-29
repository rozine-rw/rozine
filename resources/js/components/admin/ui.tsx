import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import {
    formatCount,
    formatDate,
    formatRwfShort,
    relativeTime,
} from '@/components/admin/format';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { PolicyItem, Rating, StatValue, Tone } from '@/types/admin';

/*
 * The console's building blocks, each drawn to the design's canonical style strings
 * (inventory A.3). Light values are the design's; dark values come from the approved tokens.
 * Gradient fills behind content are flattened to their dominant stop (brand rule 08).
 */

/** #7b8699 — column headers and labels. */
export const LABEL = 'text-[#7b8699] dark:text-rz-muted';
/** #8895ab — small captions inside cards. */
export const CAPTION = 'text-[#8895ab] dark:text-rz-muted';
/** #5a6a86 — explanatory body copy in drawers. */
export const EXPLAIN = 'text-[#5a6a86] dark:text-rz-secondary';
/** The white card with the design's #edf1f8 edge. */
export const CARD =
    'border border-[#edf1f8] bg-rz-surface dark:border-rz-border';
/** The resting card shadow: 0 1px 2px + 0 12px 28px -20px. */
export const CARD_SHADOW =
    'shadow-[0_1px_2px_rgba(16,32,58,.04),0_12px_28px_-20px_rgba(16,32,58,.18)] dark:shadow-none';
/** The design's #eef3fb tonal fill. */
export const TONAL = 'bg-[#eef3fb] dark:bg-rz-surface-muted';
/** Table header band. */
export const TABLE_HEAD =
    'border-b border-[#e3e9f4] bg-[#f4f7fc] dark:border-rz-border dark:bg-rz-surface-sunken';
/** Table row rule. */
export const ROW_RULE = 'border-b border-[#f0f4fa] dark:border-rz-divider';

export const TONE_TEXT: Record<Tone, string> = {
    blue: 'text-rz-accent-app-text',
    green: 'text-[#1d9e75] dark:text-[#3fcda0]',
    amber: 'text-[#c2661f] dark:text-[#f0a060]',
    red: 'text-[#e5484d] dark:text-[#ff6b6f]',
    purple: 'text-[#7c3aed] dark:text-[#b199fb]',
    grey: 'text-rz-body',
};

export const TONE_CHIP: Record<Tone, string> = {
    blue: 'bg-[rgba(30,58,255,.12)] text-rz-accent-app-text dark:bg-[rgba(99,120,255,.18)]',
    green: 'bg-[rgba(29,158,117,.12)] text-[#1d9e75] dark:text-[#3fcda0]',
    amber: 'bg-[rgba(210,120,45,.14)] text-[#c2661f] dark:text-[#f0a060]',
    red: 'bg-[rgba(255,77,79,.12)] text-[#e5484d] dark:text-[#ff6b6f]',
    purple: 'bg-[rgba(124,58,237,.12)] text-[#7c3aed] dark:text-[#b199fb]',
    grey: 'bg-[rgba(105,116,138,.12)] text-rz-body',
};

export const TONE_DOT: Record<Tone, string> = {
    blue: 'bg-[#1e3aff] dark:bg-[#5b74ff]',
    green: 'bg-[#1d9e75]',
    amber: 'bg-[#c2661f]',
    red: 'bg-[#e5484d]',
    purple: 'bg-[#7c3aed]',
    grey: 'bg-[#69748a]',
};

/** The eight-accent KPI palette (design `_KPI_TINTS`): rail colour, tile edge and label colour. */
const KPI_ACCENTS = [
    ['bg-[#1e3aff]', 'border-[#e2ebfd]', 'text-[#5b7099]'],
    ['bg-[#1d9e75]', 'border-[#dbf0e3]', 'text-[#5a8570]'],
    ['bg-[#5b2bd9]', 'border-[#e7e1fb]', 'text-[#6f6396]'],
    ['bg-[#c2661f]', 'border-[#f6e6cc]', 'text-[#96774a]'],
    ['bg-[#0891b2]', 'border-[#d8eef4]', 'text-[#4f7f8c]'],
    ['bg-[#db2777]', 'border-[#f8dfe9]', 'text-[#95637a]'],
    ['bg-[#0c1830] dark:bg-[#93a1bd]', 'border-[#e6ecf5]', 'text-[#6b7688]'],
    ['bg-[#e5484d]', 'border-[#f7dcdd]', 'text-[#96666a]'],
] as const;

export function KpiTile({
    index,
    label,
    value,
    sub,
    subClassName,
    inlineLabel = false,
}: {
    index: number;
    label: string;
    value: string;
    sub?: string;
    subClassName?: string;
    /** The dashboard sets its label as an inline span, so it sits in a 16px line box. */
    inlineLabel?: boolean;
}) {
    const [rail, edge, tint] = KPI_ACCENTS[index % KPI_ACCENTS.length];

    return (
        <div
            className={cn(
                'relative overflow-hidden rounded-[18px] border bg-rz-surface px-[17px] py-4 shadow-[0_1px_2px_rgba(16,32,58,.04),0_12px_26px_-22px_rgba(16,32,58,.3)] dark:border-rz-border dark:shadow-none',
                edge,
            )}
        >
            <span
                className={cn(
                    'absolute top-3.5 bottom-3.5 left-0 w-[3px] rounded-r-[3px]',
                    rail,
                )}
            />
            <div className={inlineLabel ? 'text-[16px]' : 'text-[10.5px]'}>
                <span
                    className={cn(
                        'text-[10.5px] font-bold tracking-[.04em] dark:text-rz-muted',
                        tint,
                    )}
                >
                    {label}
                </span>
            </div>
            <div className="mt-2 text-[23px] font-bold tracking-[-.5px] text-rz-ink">
                {value}
            </div>
            {sub !== undefined && (
                <div
                    className={cn(
                        'mt-[3px] text-[11.5px] font-semibold',
                        subClassName ?? cn(tint, 'dark:text-rz-muted'),
                    )}
                >
                    {sub}
                </div>
            )}
        </div>
    );
}

/** A figure formatted exactly as its kind reads. */
export function useStatFormatter(): (stat: StatValue) => string {
    const { t, locale } = useTranslation();

    return (stat) => {
        switch (stat.kind) {
            case 'money':
                return formatRwfShort(stat.value);
            case 'count':
                return formatCount(stat.value);
            case 'percent':
                return `${stat.value}%`;
            case 'rating':
                return stat.value === null
                    ? t('admin.rating.pending')
                    : t('admin.rating.of_five', {
                          band: t(`admin.rating.band.${stat.value.band}`),
                          score: stat.value.score,
                      });
            case 'date':
                return formatDate(stat.value, locale);
            case 'text':
                return stat.value;
        }
    };
}

const RATING_TONE = {
    strong: 'bg-[rgba(29,158,117,.12)] text-[#17795a] dark:text-[#3fcda0]',
    stable: 'bg-[rgba(30,58,255,.1)] text-[#1832c8] dark:text-[#99a3ff]',
    weak: 'bg-[rgba(194,102,31,.13)] text-[#a55418] dark:text-[#f0a060]',
    distressed: 'bg-[rgba(229,72,77,.12)] text-[#9c3a0a] dark:text-[#ff8285]',
} as const;

export const RATING_TEXT = {
    strong: 'text-[#17795a] dark:text-[#3fcda0]',
    stable: 'text-[#1832c8] dark:text-[#99a3ff]',
    weak: 'text-[#a55418] dark:text-[#f0a060]',
    distressed: 'text-[#9c3a0a] dark:text-[#ff8285]',
} as const;

/** The band word leads, the number supports (brand 03). */
export function RatingPill({ rating }: { rating: Rating | null }) {
    const { t } = useTranslation();

    if (rating === null) {
        return (
            <span className="inline-flex justify-self-start rounded-[7px] bg-rz-surface-muted px-[9px] py-[3px] text-[11.5px] font-bold whitespace-nowrap text-rz-body">
                {t('admin.rating.pending')}
            </span>
        );
    }

    return (
        <span
            className={cn(
                'inline-flex items-baseline gap-[5px] justify-self-start rounded-[7px] px-[9px] py-[3px] text-[11.5px] font-bold whitespace-nowrap',
                RATING_TONE[rating.band],
            )}
        >
            {t(`admin.rating.band.${rating.band}`)}
            <span className="text-[10.5px] font-semibold">{rating.score}</span>
        </span>
    );
}

export function Chip({
    tone,
    children,
    className,
}: {
    tone: Tone;
    children: ReactNode;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center justify-self-start rounded-[7px] px-[9px] py-1 text-[10.5px] font-bold whitespace-nowrap',
                TONE_CHIP[tone],
                className,
            )}
        >
            {children}
        </span>
    );
}

/** FROZEN / RESTRICTED micro-badge: 9.5px/800, red on 12% red. */
export function MicroBadge({ children }: { children: ReactNode }) {
    return (
        <span className="shrink-0 rounded-[5px] bg-[rgba(255,77,79,.12)] px-[7px] py-0.5 text-[9.5px] font-extrabold tracking-[.05em] text-[#e5484d] uppercase dark:text-[#ff6b6f]">
            {children}
        </span>
    );
}

/** The green-check empty state used under every queue. */
export function EmptyState({
    title,
    body,
    action,
}: {
    title: string;
    body?: string;
    action?: ReactNode;
}) {
    return (
        <div className="p-[50px] text-center text-[#7b8699] dark:text-rz-muted">
            <div className="flex justify-center">
                <svg
                    width="34"
                    height="34"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9.2"
                        fill="url(#rz-g-green)"
                        fillOpacity=".16"
                    />
                    <path
                        d="M8 12.4l2.6 2.6L16 9.2"
                        stroke="url(#rz-g-green)"
                        strokeWidth="2.1"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </div>
            <div
                className={cn(
                    'mt-2.5 text-[14px]',
                    body !== undefined && 'font-semibold text-rz-ink',
                )}
            >
                {title}
            </div>
            {body !== undefined && (
                <div className="mt-1 text-[12.5px]">{body}</div>
            )}
            {action}
        </div>
    );
}

/** The ⓘ that keeps long explanations off the canvas; opens on hover or keyboard focus. */
export function InfoTip({
    label,
    children,
    align = 'left',
}: {
    label: string;
    children: string;
    align?: 'left' | 'right';
}) {
    return (
        <span
            tabIndex={0}
            role="button"
            aria-label={label}
            className="group relative inline-flex size-[15px] shrink-0 cursor-help items-center justify-center rounded-full bg-[#e5eaf2] align-middle text-[9.5px] leading-none font-extrabold text-[#7b8699] not-italic transition-colors outline-none hover:bg-[#1e3aff] hover:text-white focus:bg-[#1e3aff] focus:text-white dark:bg-rz-surface-muted dark:text-rz-muted"
        >
            <span aria-hidden>i</span>
            <span
                role="tooltip"
                className={cn(
                    'pointer-events-none invisible absolute top-[calc(100%+9px)] z-[90] w-[244px] max-w-[62vw] -translate-y-1 rounded-[10px] bg-[#0c1830] px-3 py-2.5 text-left text-[11.5px] leading-[1.5] font-medium tracking-[.003em] whitespace-normal text-[#e8edf6] opacity-0 shadow-[0_16px_38px_-10px_rgba(12,24,48,.5)] transition-[opacity,transform] duration-150 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus:visible group-focus:translate-y-0 group-focus:opacity-100 after:absolute after:bottom-full after:border-[5px] after:border-transparent after:border-b-[#0c1830] dark:bg-[#1c2b4a] dark:after:border-b-[#1c2b4a]',
                    align === 'left'
                        ? 'left-0 after:left-3'
                        : 'right-0 after:right-3',
                )}
            >
                {children}
            </span>
        </span>
    );
}

/** "SET IN POLICIES" — the rules governing this queue, read from the policy registry. */
export function PolicyPeek({
    title,
    items,
}: {
    title: string;
    items: PolicyItem[];
}) {
    const { t } = useTranslation();

    if (items.length === 0) {
        return null;
    }

    return (
        <section
            aria-label={title}
            className="relative mb-[18px] overflow-hidden rounded-2xl border border-[#e2e9f7] bg-[#f5f8fe] py-[15px] pr-5 pl-[22px] dark:border-rz-border dark:bg-rz-surface"
        >
            <div className="absolute inset-y-0 left-0 w-[3px] bg-[#1e3aff] dark:bg-[#5b74ff]" />
            <div className="flex flex-wrap items-center gap-4">
                <div className="max-w-[210px] min-w-0 flex-none">
                    <div className="flex items-center gap-1.5">
                        <svg
                            width="11"
                            height="11"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                        >
                            <path
                                d="M12 3 20 6v5c0 5-4 8-8 10-4-2-8-5-8-10V6Z"
                                stroke="currentColor"
                                strokeWidth="1.9"
                                strokeLinejoin="round"
                                className="text-rz-accent-app-text"
                            />
                        </svg>
                        <span className="text-[9.5px] font-extrabold tracking-[.09em] text-rz-accent-app-text uppercase">
                            {t('admin.policy.eyebrow')}
                        </span>
                    </div>
                    <div className="mt-1 text-[13.5px] font-bold tracking-[-.01em] text-rz-ink">
                        {title}
                    </div>
                </div>
                <dl className="grid min-w-0 flex-1 auto-cols-[minmax(0,1fr)] grid-flow-row gap-px overflow-hidden rounded-xl border border-[#e2e9f7] bg-[#e2e9f7] max-lg:basis-full max-sm:grid-cols-2 sm:grid-flow-col dark:border-rz-border dark:bg-rz-border">
                    {items.map((item) => (
                        <div
                            key={item.key}
                            className="min-w-0 bg-rz-surface px-[13px] py-[9px]"
                        >
                            <dt
                                className={cn(
                                    'truncate text-[10px] font-semibold',
                                    CAPTION,
                                )}
                            >
                                {t(`admin.policy.${item.key}`)}
                            </dt>
                            <dd className="mt-[3px] truncate text-[13.5px] font-bold tracking-[-.01em] text-rz-ink">
                                {item.value}
                            </dd>
                        </div>
                    ))}
                </dl>
            </div>
        </section>
    );
}

/** Applications-style pill tabs: 8×15 padding, 10px radius, solid blue when current. */
export function PillTabs({
    label,
    tabs,
}: {
    label: string;
    tabs: {
        key: string;
        label: string;
        count: number;
        link: RouteLink;
        active: boolean;
    }[];
}) {
    return (
        <nav
            aria-label={label}
            className="rz-hscroll mb-[18px] flex gap-2 overflow-x-auto"
        >
            {tabs.map((tab) => (
                <Link
                    key={tab.key}
                    href={tab.link}
                    aria-current={tab.active ? 'page' : undefined}
                    className={cn(
                        'shrink-0 rounded-[10px] border px-[15px] py-2 text-[13px] font-semibold whitespace-nowrap',
                        tab.active
                            ? 'border-rz-accent-fill bg-rz-accent-fill text-white'
                            : 'border-rz-hairline bg-rz-surface text-rz-slate',
                    )}
                >
                    {tab.label} <span className="opacity-70">{tab.count}</span>
                </Link>
            ))}
        </nav>
    );
}

/** Directory status chips: a 4px-padded tonal track holding 7×13 chips. */
export function SegmentedChips({
    label,
    chips,
}: {
    label: string;
    chips: {
        key: string;
        label: string;
        count: number;
        link: RouteLink;
        active: boolean;
    }[];
}) {
    return (
        <nav
            aria-label={label}
            className={cn(
                'rz-hscroll flex max-w-full items-center gap-1 overflow-x-auto rounded-[11px] border border-rz-hairline p-1',
                TONAL,
            )}
        >
            {chips.map((chip) => (
                <Link
                    key={chip.key}
                    href={chip.link}
                    aria-current={chip.active ? 'page' : undefined}
                    className={cn(
                        'shrink-0 rounded-lg px-[13px] py-[7px] text-[12.5px] font-semibold whitespace-nowrap',
                        chip.active
                            ? 'bg-rz-accent-fill text-white'
                            : 'text-rz-slate',
                    )}
                >
                    {chip.label}{' '}
                    <span className="font-semibold opacity-55">
                        {chip.count}
                    </span>
                </Link>
            ))}
        </nav>
    );
}

/** Column header cell text. */
export function HeadCell({
    children,
    end = false,
}: {
    children: ReactNode;
    end?: boolean;
}) {
    return (
        <span
            role="columnheader"
            className={cn(
                'text-[11px] font-semibold uppercase',
                LABEL,
                end && 'text-right',
            )}
        >
            {children}
        </span>
    );
}

/** A table card: rounded 20, the resting shadow, and horizontal scroll on a phone. */
export function TableCard({
    label,
    minWidth,
    children,
    footer,
    className,
}: {
    label: string;
    minWidth: string;
    children: ReactNode;
    footer?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'overflow-hidden rounded-[20px]',
                CARD,
                CARD_SHADOW,
                className,
            )}
        >
            <div className="rz-hscroll overflow-x-auto">
                <div
                    role="table"
                    aria-label={label}
                    className={cn(minWidth, 'lg:min-w-0')}
                >
                    {children}
                </div>
            </div>
            {footer}
        </div>
    );
}

/** "See all N entries ▾" at the foot of a long table. */
export function ShowMoreLink({
    link,
    children,
}: {
    link: RouteLink;
    children: ReactNode;
}) {
    return (
        <Link
            href={link}
            preserveScroll
            className="flex w-full items-center justify-center gap-[7px] border-t border-[#eef2f8] bg-rz-surface-sunken p-3.5 text-[12.5px] font-bold text-rz-accent-app-text dark:border-rz-divider"
        >
            {children}
            <svg
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden
            >
                <path
                    d="M6 9l6 6 6-6"
                    stroke="currentColor"
                    strokeWidth="2.2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        </Link>
    );
}

/** Card section title, 15px/600. */
export function CardTitle({ children }: { children: ReactNode }) {
    return (
        <h2 className="text-[15px] font-semibold text-rz-ink">{children}</h2>
    );
}

/** The dashboard card: white, 18 radius, 20 padding. */
export function Panel({
    children,
    className,
    label,
}: {
    children: ReactNode;
    className?: string;
    label?: string;
}) {
    return (
        <section
            aria-label={label}
            className={cn(
                'rounded-[18px] border border-[#eaeef6] bg-rz-surface p-5 shadow-[0_1px_2px_rgba(16,32,58,.04),0_10px_26px_-18px_rgba(16,32,58,.18)] dark:border-rz-border dark:shadow-none',
                className,
            )}
        >
            {children}
        </section>
    );
}

/** Capacity bar colour, as the review reads it: red over 100, amber over 85. */
export const capacityFill = (pct: number): string =>
    pct > 100 ? 'bg-[#e5484d]' : pct > 85 ? 'bg-[#c2661f]' : 'bg-[#1d9e75]';

export const capacityText = (pct: number): string =>
    pct > 100 ? TONE_TEXT.red : pct > 85 ? TONE_TEXT.amber : TONE_TEXT.green;

/** "3m ago", "2h ago", "5d ago" or "just now", counted from the server's clock. */
export function useRelativeLabel(serverTime: string): (iso: string) => string {
    const { t } = useTranslation();

    return (iso) => {
        const elapsed = relativeTime(iso, serverTime);

        return elapsed.unit === 'now'
            ? t('admin.time.now')
            : t(`admin.time.${elapsed.unit}`, { count: elapsed.count });
    };
}
