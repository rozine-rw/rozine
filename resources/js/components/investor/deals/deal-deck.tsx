import { Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import type { PointerEvent as ReactPointerEvent, ReactNode } from 'react';
import { DeskCard } from '@/components/investor/deals/desk-card';
import { SwipeCard } from '@/components/investor/deals/swipe-card';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { C3DealCard } from '@/types/investor';

type DeckProps = {
    deals: C3DealCard[];
    index: number;
    serverTime: string;
    onMove: (index: number) => void;
};

type PhoneDeckProps = DeckProps & {
    /** The front card's Add to cart, or null where the Investor has no cart. */
    cart?: ReactNode;
    /** A card dealt after the first deal, such as the Academy nudge; `index` then counts it too. */
    extra?: ReactNode;
};

/** Where the phone deck deals its extra card: after the first deal, so the front is a deal. */
export const EXTRA_AT = 1;

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
 * horizontal drag browses; a tap opens the deal. An extra card, the Academy nudge, may be dealt
 * after the first deal, as the design interleaves its update cards. Save/pass swipes, the feed
 * refresh and the other update cards are engagement extras outside the MVP.
 */
export function PhoneDeck({
    deals,
    index,
    serverTime,
    onMove,
    cart = null,
    extra = null,
}: PhoneDeckProps) {
    const [drag, setDrag] = useState(0);
    const start = useRef<number | null>(null);
    const moved = useRef(false);
    const items: (C3DealCard | null)[] =
        extra === null
            ? deals
            : [...deals.slice(0, EXTRA_AT), null, ...deals.slice(EXTRA_AT)];
    const used = new Set<number>();
    const cards = FAN_ORDER.flatMap((offset) => {
        const at = wrapIndex(index + offset, items.length);

        if (used.has(at)) {
            return [];
        }

        used.add(at);

        return [{ offset, deal: items[at] }];
    });

    const down = (event: ReactPointerEvent<HTMLDivElement>) => {
        start.current = event.clientX;
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
        <div className="relative mt-3 max-h-[540px] min-h-[364px] shrink-0 grow-[100] basis-[364px] overflow-visible">
            <div
                className="absolute inset-y-0 right-[30px] left-[30px] animate-[rz-fade_.5s_cubic-bezier(.22,.9,.3,1)_both]"
                onPointerDownCapture={() => {
                    // A fresh press is a new gesture even when a card control stops it bubbling.
                    moved.current = false;
                }}
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
                            key={deal?.campaign_id ?? 'extra'}
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
                            {deal === null ? (
                                extra
                            ) : (
                                <SwipeCard
                                    deal={deal}
                                    serverTime={serverTime}
                                    front={front}
                                    cart={front ? cart : null}
                                />
                            )}
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
            <div className="relative h-[91%] max-h-[460px] w-full max-w-[340px]">
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
                            key={deal.campaign_id}
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

/**
 * The deck's Academy nudge (design `_updateDefs` u-lesson, card L608–622): the Investor has started
 * the Academy and not finished it. The design's promise of fee discounts is left out, since lessons
 * earn nothing; the body says how far the Investor has got instead.
 */
export function AcademyNudge({
    done,
    total,
    href,
    onLater,
}: {
    done: number;
    total: number;
    href: RouteLink;
    onLater: () => void;
}) {
    const { t } = useTranslation();

    return (
        <section
            aria-label={t('investor.deals.nudge.title')}
            className="flex size-full flex-col items-center justify-center rounded-[20px] border border-rz-border bg-rz-surface px-[22px] py-[26px] text-center shadow-[0_26px_50px_-22px_rgba(20,45,95,.30)]"
        >
            <span className="rounded-[10px] bg-[rgba(30,58,255,.10)] px-[11px] py-[5px] text-[10.5px] font-bold tracking-[.08em] text-rz-accent-app-text uppercase">
                {t('investor.deals.nudge.tag')}
            </span>
            <span className="mt-[18px] flex size-[70px] items-center justify-center rounded-[20px] bg-[rgba(30,58,255,.10)] text-[30px]">
                <Icon name="graduation" tone="blue" />
            </span>
            <h2 className="mt-[18px] text-[19px] font-semibold text-rz-ink">
                {t('investor.deals.nudge.title')}
            </h2>
            <p className="mt-2 max-w-[250px] text-[13px] leading-[1.55] text-[#5b6680] dark:text-rz-body">
                {t('investor.deals.nudge.body', { done, total })}
            </p>
            <div className="mt-[22px] flex w-full gap-2.5">
                <button
                    type="button"
                    onPointerDown={(event) => event.stopPropagation()}
                    onClick={onLater}
                    className="h-[46px] flex-1 rounded-xl border border-rz-border bg-rz-surface text-sm font-semibold text-rz-slate"
                >
                    {t('investor.deals.nudge.later')}
                </button>
                <Link
                    href={href}
                    onPointerDown={(event) => event.stopPropagation()}
                    className="flex h-[46px] flex-1 items-center justify-center rounded-xl bg-rz-accent-fill text-sm font-semibold text-white"
                >
                    {t('investor.deals.nudge.continue')}
                </Link>
            </div>
        </section>
    );
}
