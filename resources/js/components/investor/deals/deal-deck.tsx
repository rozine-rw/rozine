import { useRef, useState } from 'react';
import type { PointerEvent as ReactPointerEvent } from 'react';
import { DeskCard } from '@/components/investor/deals/desk-card';
import { SwipeCard } from '@/components/investor/deals/swipe-card';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { DealCard } from '@/types/investor';

type DeckProps = {
    deals: DealCard[];
    index: number;
    serverTime: string;
    onMove: (index: number) => void;
};

/** Wrap an index into the deck, which loops as the design's does. */
export const wrapIndex = (index: number, length: number): number =>
    ((index % length) + length) % length;

/** The design's fan (`_spec`, L6571): x offsets and scales per step away from the front card. */
const FAN_X = [0, 24, 42, 55, 68];
const FAN_SCALE = [1, 0.93, 0.87, 0.815, 0.77];
const FAN_ORDER = [0, -1, 1, -2, 2, -3, 3, -4, 4];

/** How far a drag must travel before it counts as a browse rather than a tap. */
const SWIPE_THRESHOLD = 56;

function NavButton({
    direction,
    onClick,
    className,
}: {
    direction: 'previous' | 'next';
    onClick: () => void;
    className: string;
}) {
    const { t } = useTranslation();

    return (
        <button
            type="button"
            aria-label={t(`investor.deals.deal_${direction}`)}
            onClick={onClick}
            className={className}
        >
            <svg viewBox="0 0 24 24" fill="none" aria-hidden className="size-5">
                <path
                    d={
                        direction === 'previous'
                            ? 'M15 6l-6 6 6 6'
                            : 'M9 6l6 6-6 6'
                    }
                    stroke="currentColor"
                    strokeWidth="2.4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        </button>
    );
}

/**
 * The phone deck (design L595–634): the front card with up to four fanned on each side. A
 * horizontal drag browses; a tap opens the deal. Save/pass swipes, the feed refresh and the
 * interleaved update cards are engagement extras outside the MVP.
 */
export function PhoneDeck({ deals, index, serverTime, onMove }: DeckProps) {
    const [drag, setDrag] = useState(0);
    const start = useRef<number | null>(null);
    const moved = useRef(false);
    const used = new Set<number>();
    const cards = FAN_ORDER.flatMap((offset) => {
        const at = wrapIndex(index + offset, deals.length);

        if (used.has(at)) {
            return [];
        }

        used.add(at);

        return [{ offset, deal: deals[at] }];
    });

    const down = (event: ReactPointerEvent<HTMLDivElement>) => {
        start.current = event.clientX;
        moved.current = false;
    };

    const move = (event: ReactPointerEvent<HTMLDivElement>) => {
        if (start.current === null) {
            return;
        }

        const dx = event.clientX - start.current;

        if (Math.abs(dx) > 6) {
            moved.current = true;
        }

        setDrag(dx);
    };

    const up = () => {
        if (drag <= -SWIPE_THRESHOLD) {
            onMove(index + 1);
        } else if (drag >= SWIPE_THRESHOLD) {
            onMove(index - 1);
        }

        start.current = null;
        setDrag(0);
    };

    return (
        <div className="relative mt-3 h-[364px] flex-none overflow-visible">
            <div
                className="absolute inset-y-0 right-[30px] left-[30px] animate-[rz-fade_.5s_cubic-bezier(.22,.9,.3,1)_both]"
                onPointerDown={down}
                onPointerMove={move}
                onPointerUp={up}
                onPointerCancel={up}
                onClickCapture={(event) => {
                    if (moved.current) {
                        event.preventDefault();
                        event.stopPropagation();
                        moved.current = false;
                    }
                }}
            >
                {cards.map(({ offset, deal }) => {
                    const step = Math.min(4, Math.abs(offset));
                    const front = offset === 0;
                    const x = Math.sign(offset) * FAN_X[step];

                    return (
                        <div
                            key={deal.id}
                            aria-hidden={front ? undefined : true}
                            inert={front ? undefined : true}
                            className={cn(
                                'absolute inset-0 origin-center transition-transform duration-[340ms] ease-[cubic-bezier(.22,.85,.2,1)]',
                                front
                                    ? 'cursor-grab touch-pan-y'
                                    : 'pointer-events-none brightness-[.985] saturate-[.96]',
                            )}
                            style={{
                                zIndex: 40 - step * 8,
                                transform: `translateX(${front ? drag : x}px) scale(${FAN_SCALE[step]})`,
                                transitionDuration:
                                    front && drag !== 0 ? '0ms' : undefined,
                            }}
                        >
                            <SwipeCard
                                deal={deal}
                                serverTime={serverTime}
                                front={front}
                            />
                        </div>
                    );
                })}
            </div>
            <NavButton
                direction="previous"
                onClick={() => onMove(index - 1)}
                className="sr-only focus:not-sr-only focus:absolute focus:top-1/2 focus:left-1 focus:z-50 focus:flex focus:size-9 focus:items-center focus:justify-center focus:rounded-full focus:bg-rz-surface focus:text-rz-ink"
            />
            <NavButton
                direction="next"
                onClick={() => onMove(index + 1)}
                className="sr-only focus:not-sr-only focus:absolute focus:top-1/2 focus:right-1 focus:z-50 focus:flex focus:size-9 focus:items-center focus:justify-center focus:rounded-full focus:bg-rz-surface focus:text-rz-ink"
            />
        </div>
    );
}

/**
 * The wide-screen deck (design L203–245): a fanned stack of the desk cards, four peeking each
 * side, browsed with the round arrows either side.
 */
export function DeskDeck({ deals, index, serverTime, onMove }: DeckProps) {
    return (
        <div className="flex min-h-0 flex-1 items-center justify-center">
            <div className="relative h-[91%] w-full max-w-[340px]">
                {deals.map((deal, at) => {
                    let offset = wrapIndex(at - index, deals.length);

                    if (offset > deals.length / 2) {
                        offset -= deals.length;
                    }

                    const step = Math.abs(offset);
                    const visible = step <= 4;
                    const front = offset === 0;

                    return (
                        <div
                            key={deal.id}
                            aria-hidden={front ? undefined : true}
                            inert={front ? undefined : true}
                            className={cn(
                                'absolute inset-0 origin-center rounded-3xl transition-[transform,opacity] duration-500 ease-[cubic-bezier(.4,.85,.3,1)]',
                                front
                                    ? 'overflow-visible'
                                    : 'pointer-events-none overflow-hidden',
                            )}
                            style={{
                                zIndex: 30 - Math.min(step, 5),
                                opacity: visible ? 1 : 0,
                                transform: visible
                                    ? `translateX(${offset * 21}px) scale(${1 - step * 0.035})`
                                    : 'scale(.7)',
                            }}
                        >
                            {visible && (
                                <DeskCard deal={deal} serverTime={serverTime} />
                            )}
                        </div>
                    );
                })}
                <NavButton
                    direction="previous"
                    onClick={() => onMove(index - 1)}
                    className="absolute top-1/2 -left-[52px] z-40 flex size-12 -translate-y-1/2 items-center justify-center rounded-full border border-rz-border bg-rz-surface text-[#46526b] shadow-[0_8px_20px_-8px_rgba(20,45,95,.4)] dark:text-rz-slate"
                />
                <NavButton
                    direction="next"
                    onClick={() => onMove(index + 1)}
                    className="absolute top-1/2 -right-[52px] z-40 flex size-12 -translate-y-1/2 items-center justify-center rounded-full border border-rz-border bg-rz-surface text-[#46526b] shadow-[0_8px_20px_-8px_rgba(20,45,95,.4)] dark:text-rz-slate"
                />
            </div>
        </div>
    );
}
