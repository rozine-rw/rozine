import type { ComponentProps, ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { SectorCode } from '@/types/auditor';

/**
 * The Auditor design's repeated pieces (inventory §4), with dark-mode pairs for every literal the
 * design paints. Pieces are small on purpose: screens compose them rather than restyle them.
 */

/** Inset stat tiles and inputs: `#f8fafc` on `#eef2f9` (design L229, L1045). */
export const INSET =
    'border border-[#eef2f9] bg-[#f8fafc] dark:border-rz-divider dark:bg-rz-surface-sunken';

/** Job-card stat tiles: `#f6f9fd` on `#eef2f9` (design L229). */
export const TILE =
    'border border-[#eef2f9] bg-[#f6f9fd] dark:border-rz-divider dark:bg-rz-surface-sunken';

/** Row divider inside a card: `#eef2f9`. */
export const DIVIDER = 'border-[#eef2f9] dark:border-rz-divider';

/** The amber note ink (`#8a6d2b`) and its lifted dark pair. */
export const AMBER_TEXT = 'text-[#8a6d2b] dark:text-[#e3b56a]';

/** White card, 16px radius, 1px border (design L189). */
export const CARD = 'rounded-2xl border border-rz-border bg-rz-surface';

/** The dark-orange primary action (design L235). */
export const PRIMARY =
    'bg-rz-accent-fill font-bold text-white disabled:cursor-not-allowed disabled:bg-rz-disabled disabled:text-rz-secondary';

/** Uppercase eyebrow: 11px/700, .06em, slate (design L175). */
export function Eyebrow({
    className,
    children,
    as: Tag = 'p',
}: {
    className?: string;
    children: ReactNode;
    as?: 'p' | 'h2' | 'h3' | 'h4' | 'span';
}) {
    return (
        <Tag
            className={cn(
                'text-[11px] font-bold tracking-[.06em] text-rz-slate uppercase',
                className,
            )}
        >
            {children}
        </Tag>
    );
}

/** Tab screen title and its lead (design L210–211). */
export function ScreenTitle({ title, lead }: { title: string; lead?: string }) {
    return (
        <>
            <h1 className="text-[24px] font-bold text-rz-ink">{title}</h1>
            {lead && (
                <p className="mt-1 text-[12.5px] leading-[1.45] text-rz-secondary">
                    {lead}
                </p>
            )}
        </>
    );
}

/** Dashed empty state (design L241). */
export function EmptyState({
    className,
    children,
}: {
    className?: string;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'mt-3.5 rounded-2xl border border-dashed border-[#dbe3f0] bg-rz-surface px-5 py-[30px] text-center text-[13px] text-rz-secondary dark:border-rz-border',
                className,
            )}
        >
            {children}
        </div>
    );
}

/** Label-over-value tile (design L229): 10.5px/700 label, 12.5px/700 value. */
export function StatTile({
    label,
    value,
    className,
    labelClassName,
    valueClassName,
}: {
    label: string;
    value: ReactNode;
    className?: string;
    labelClassName?: string;
    valueClassName?: string;
}) {
    return (
        <div
            className={cn(
                'min-w-0 flex-1 rounded-[10px] px-[11px] py-[9px]',
                className ?? TILE,
            )}
        >
            <p
                className={cn(
                    'text-[10.5px] font-bold text-rz-slate uppercase',
                    labelClassName,
                )}
            >
                {label}
            </p>
            <p
                className={cn(
                    'mt-0.5 truncate text-[12.5px] font-bold text-rz-ink',
                    valueClassName,
                )}
            >
                {value}
            </p>
        </div>
    );
}

export type PillTone = 'green' | 'amber' | 'red' | 'blue' | 'neutral';

const PILL: Record<PillTone, string> = {
    green: 'bg-[rgba(29,158,117,.10)] text-rz-positive',
    amber: 'bg-rz-accent-soft text-rz-ink',
    red: 'bg-[rgba(192,57,43,.10)] text-rz-danger-text',
    blue: 'bg-[rgba(30,58,255,.10)] text-[#1e3aff] dark:text-rz-investor-text',
    neutral: 'bg-[rgba(122,134,153,.10)] text-rz-secondary',
};

/** Status pill: 10px/700 on a 10% tint (design L532). */
export function StatusPill({
    tone,
    children,
    className,
}: {
    tone: PillTone;
    children: ReactNode;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'shrink-0 rounded-[10px] px-[9px] py-1 text-[10px] font-bold whitespace-nowrap',
                PILL[tone],
                className,
            )}
        >
            {children}
        </span>
    );
}

/** The 52×30 switch (design L141). Ink when on, secondary grey when off. */
export function Toggle({
    on,
    label,
    ...props
}: Omit<ComponentProps<'button'>, 'className' | 'children'> & {
    on: boolean;
    label: string;
}) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={on}
            aria-label={label}
            className={cn(
                'flex h-[30px] w-[52px] shrink-0 cursor-pointer rounded-2xl p-[3px] transition-colors disabled:cursor-wait disabled:opacity-70',
                on
                    ? 'justify-end bg-[#0c1830] dark:bg-rz-accent-fill'
                    : 'justify-start bg-rz-secondary dark:bg-rz-surface-muted',
            )}
            {...props}
        >
            <span className="size-6 rounded-full bg-white shadow-[0_2px_6px_-1px_rgba(20,45,95,.4)]" />
        </button>
    );
}

/** Sector tile colours, as the design keys them (L3113). */
const SECTOR_TONE: Record<SectorCode, string> = {
    agriculture: 'bg-[rgba(29,158,117,.10)] text-rz-positive',
    logistics:
        'bg-[rgba(30,58,255,.10)] text-[#1e3aff] dark:text-rz-investor-text',
    manufacturing:
        'bg-[rgba(124,58,237,.10)] text-[#6425c9] dark:text-[#b199fb]',
    retail: 'bg-rz-accent-soft text-rz-ink',
    energy: 'bg-rz-accent-soft text-rz-ink',
    technology:
        'bg-[rgba(30,58,255,.10)] text-[#1e3aff] dark:text-rz-investor-text',
    services:
        'bg-[rgba(30,58,255,.10)] text-[#1e3aff] dark:text-rz-investor-text',
};

/** "Huye Motors" → "HM". */
export const initials = (name: string): string =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join('');

/** The business initials tile, tinted by sector (design L224). */
export function SectorTile({
    name,
    sector,
    className,
}: {
    name: string;
    sector: SectorCode;
    className?: string;
}) {
    return (
        <span
            aria-hidden
            className={cn(
                'flex size-[46px] shrink-0 items-center justify-center rounded-xl text-[17px] font-bold',
                SECTOR_TONE[sector],
                className,
            )}
        >
            {initials(name)}
        </span>
    );
}

/** A ✓ mark in the given colour (design L3045). */
export function Tick({
    className,
    strokeWidth = 2.6,
}: {
    className?: string;
    strokeWidth?: number;
}) {
    return (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden className={className}>
            <path
                d="M5 13l4 4L19 7"
                stroke="currentColor"
                strokeWidth={strokeWidth}
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
