import type { ReactNode } from 'react';
import { BusinessHero } from '@/components/business/home/business-hero';
import { CapitalSection } from '@/components/business/home/capital-section';
import { GrowSection } from '@/components/business/home/grow-section';
import { LiveRaiseCard } from '@/components/business/home/live-raise-card';
import { NotesSection } from '@/components/business/home/notes-section';
import { TodaySection } from '@/components/business/home/today-section';
import { TopRow } from '@/components/business/home/top-row';
import { cn } from '@/lib/utils';
import type { BusinessHomeProps } from '@/types/business';

/**
 * Desktop columns are two white cards that scroll on their own; on a phone they dissolve into one
 * flow. Each column is also the anchor for any sheet opened from it (design CLAUDE.md: overlays
 * open inside their own column).
 */
const COLUMN =
    'lg:relative lg:flex lg:min-h-0 lg:min-w-0 lg:flex-[0_0_calc(50%-8px)] lg:flex-col lg:overflow-hidden lg:rounded-2xl lg:border lg:border-rz-border lg:bg-rz-surface';

export type ColumnOverlay = {
    column: 'left' | 'right';
    content: ReactNode;
};

type HomeBodyProps = BusinessHomeProps & {
    overlay?: ColumnOverlay;
    /** Under a detail screen: a phone shows only the detail; a wide screen keeps Home beneath it. */
    backdrop?: boolean;
};

export function HomeBody({
    overlay,
    backdrop = false,
    ...props
}: HomeBodyProps) {
    const sheet = (column: ColumnOverlay['column']) =>
        overlay?.column === column ? overlay.content : null;

    return (
        <div
            className={cn(
                'lg:flex lg:h-full lg:flex-col lg:gap-3.5 lg:px-5 lg:pt-4 lg:pb-5',
                !backdrop && 'pb-[92px]',
            )}
        >
            <div
                className={cn(
                    'px-5 pt-[calc(env(safe-area-inset-top)+4px)] lg:shrink-0 lg:p-0',
                    backdrop && 'max-lg:hidden',
                )}
            >
                <TopRow
                    available={props.wallet.available}
                    unread={props.unread_notifications}
                    links={props.links}
                />
            </div>
            <div className="lg:flex lg:min-h-0 lg:flex-1 lg:gap-4">
                <div data-rzcol className={COLUMN}>
                    <div
                        className={cn(
                            'rz-scroll lg:flex-1 lg:overflow-y-auto',
                            backdrop && 'max-lg:hidden',
                        )}
                        inert={overlay?.column === 'left' || undefined}
                    >
                        <div className="px-5 lg:px-[18px] lg:pt-[18px] lg:pb-4">
                            <div className="mt-3.5 lg:mt-0">
                                <BusinessHero
                                    business={props.business}
                                    rating={props.rating}
                                    ratingLink={props.links.rating}
                                />
                            </div>
                            {props.live_raise && (
                                <LiveRaiseCard raise={props.live_raise} />
                            )}
                            <TodaySection items={props.today} />
                        </div>
                    </div>
                    {sheet('left')}
                </div>
                <div data-rzcol className={COLUMN}>
                    <div
                        className={cn(
                            'rz-scroll lg:flex-1 lg:overflow-y-auto',
                            backdrop && 'max-lg:hidden',
                        )}
                        inert={overlay?.column === 'right' || undefined}
                    >
                        <div className="px-5 lg:px-[18px] lg:pt-[18px]">
                            <CapitalSection capital={props.capital} />
                        </div>
                        <NotesSection notes={props.notes} />
                        <GrowSection
                            headroom={props.headroom}
                            links={props.links}
                        />
                    </div>
                    {sheet('right')}
                </div>
            </div>
        </div>
    );
}

/**
 * The same two columns with nothing in them, for a sheet opened without Home (no stand-in
 * balances). A phone shows only the sheet.
 */
export function BlankBody({ overlay }: { overlay: ColumnOverlay }) {
    return (
        <div className="lg:flex lg:h-full lg:gap-4 lg:px-5 lg:pt-4 lg:pb-5">
            {(['left', 'right'] as const).map((column) => (
                <div key={column} data-rzcol className={COLUMN}>
                    {overlay.column === column && overlay.content}
                </div>
            ))}
        </div>
    );
}
