import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * A tab's two desktop columns (design `_emb` L3497–3526): white cards that scroll on their own,
 * side by side under an optional header row. On a phone they dissolve into one flow. Each column
 * is also the anchor for any sheet opened from it (design CLAUDE.md: overlays open inside their
 * own column, never over the whole window).
 */
export const COLUMN =
    'lg:relative lg:flex lg:min-h-0 lg:min-w-0 lg:flex-[0_0_calc(50%-8px)] lg:flex-col lg:overflow-hidden lg:rounded-2xl lg:border lg:border-rz-border lg:bg-rz-surface';

export type ColumnOverlay = {
    column: 'left' | 'right';
    content: ReactNode;
};

type TabColumnsProps = {
    /** Home's wallet-and-bell row, drawn above the columns. */
    header?: ReactNode;
    left: ReactNode;
    /**
     * Null leaves the right column out altogether, rather than drawing an empty card, unless an
     * overlay opens in it: the overlay then keeps its column, with nothing beneath it.
     */
    right: ReactNode | null;
    overlay?: ColumnOverlay | null;
    /** Under a detail screen: a phone shows only the detail; a wide screen keeps the tab beneath. */
    backdrop?: boolean;
};

export function TabColumns({
    header,
    left,
    right,
    overlay = null,
    backdrop = false,
}: TabColumnsProps) {
    const column = (side: ColumnOverlay['column'], content: ReactNode) => (
        <div
            data-rzcol={side}
            data-testid={`column-${side}`}
            className={COLUMN}
        >
            <div
                className={cn(
                    'rz-scroll lg:flex-1 lg:overflow-y-auto',
                    backdrop && 'max-lg:hidden',
                )}
                inert={overlay?.column === side || undefined}
            >
                {content}
            </div>
            {overlay?.column === side && overlay.content}
        </div>
    );

    return (
        <div
            className={cn(
                'lg:flex lg:h-full lg:flex-col lg:gap-3.5 lg:px-5 lg:pt-4 lg:pb-5',
                !backdrop && 'pb-[92px] lg:pb-5',
            )}
        >
            {header && (
                <div className="px-5 pt-[calc(env(safe-area-inset-top)+4px)] lg:shrink-0 lg:p-0">
                    {header}
                </div>
            )}
            <div className="lg:flex lg:min-h-0 lg:flex-1 lg:gap-4">
                {column('left', left)}
                {(right !== null || overlay?.column === 'right') &&
                    column('right', right)}
            </div>
        </div>
    );
}

/** Column padding: the left column's `18px 18px 16px` and the right's `18px 18px 0` (L3502–3503). */
export function ColumnPad({
    side,
    tab = false,
    children,
}: {
    side: 'left' | 'right';
    /** Tab screens start with a title that sits 10px under the phone's safe area (L3509). */
    tab?: boolean;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'px-5 lg:px-[18px] lg:pt-[18px]',
                side === 'left' ? 'lg:pb-4' : 'lg:pb-0',
                side === 'left' &&
                    tab &&
                    'pt-[calc(env(safe-area-inset-top)+10px)]',
            )}
        >
            {children}
            {side === 'right' && <div className="h-3.5" />}
        </div>
    );
}
