import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    AcademyNudge,
    DeskDeck,
    EXTRA_AT,
    PhoneDeck,
    wrapIndex,
} from '@/components/investor/deals/deal-deck';
import {
    IndustryTabs,
    SortChips,
} from '@/components/investor/deals/deal-filters';
import { DealPanel } from '@/components/investor/deals/deal-panel';
import {
    DeskTopBar,
    PhoneTopBar,
} from '@/components/investor/deals/deal-top-bar';
import {
    canReserve,
    CartToggle,
    InvestBar,
} from '@/components/investor/deals/invest-bar';
import type { CartToggleState } from '@/components/investor/deals/invest-bar';
import { useQuotedUnits } from '@/components/investor/deals/use-quote';
import { useAcademyNudge } from '@/components/investor/profile/academy';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { withQuery } from '@/lib/investor/links';
import { useFitScale } from '@/lib/investor/use-fit-scale';
import { useWide } from '@/lib/investor/use-wide';
import { cn } from '@/lib/utils';
import type { C3InvestorDealsProps, InvestGate } from '@/types/investor';

type DealsBodyProps = C3InvestorDealsProps & {
    /** A sheet drawn over the deck and invest bar (checkout), anchored to that column. */
    overlay?: ReactNode;
};

/** The design's Deals canvas height (L136); a shorter pane scales the canvas down. */
const CANVAS_HEIGHT = 720;

/**
 * "No deals open" (crosswalk SCR-01-ST-01), in the design's caught-up layout (L596–603). A person
 * still to verify their identity also gets the way to verify, so they are ready when one opens.
 */
function EmptyDeals({ gate }: { gate: InvestGate }) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-1 flex-col items-center justify-center px-[22px] py-16 text-center">
            <span className="flex size-[72px] items-center justify-center rounded-[20px] bg-rz-accent-soft text-[26px]">
                <Icon name="sparkle" />
            </span>
            <p className="mt-4 text-lg font-semibold text-rz-ink">
                {t('investor.deals.empty_title')}
            </p>
            <p className="mt-1.5 max-w-[230px] text-[13px] leading-normal text-rz-secondary">
                {t('investor.deals.empty_body')}
            </p>
            {gate.status === 'verification_required' && (
                <Link
                    href={gate.link}
                    className="mt-4 flex h-11 items-center justify-center rounded-[14px] bg-rz-accent-fill px-5 text-sm font-semibold text-white"
                >
                    {t('investor.deals.verify_to_invest')}
                </Link>
            )}
        </div>
    );
}

/**
 * "All sold out" (crosswalk I-01-ST-02): the deck has deals but none can be reserved, so one calm
 * line says so above the cards instead of hiding them.
 */
function AllClosedNote({ className }: { className: string }) {
    const { t } = useTranslation();

    return (
        <p
            role="status"
            className={cn(
                'text-center text-xs leading-normal text-rz-secondary',
                className,
            )}
        >
            {t('investor.deals.all_closed')}
        </p>
    );
}

/**
 * The detail panel while the card in front has no detail yet: another card's detail is never
 * shown beside it, so the panel pulses until the focused deal's facts arrive.
 */
function DealPanelLoading() {
    const { t } = useTranslation();

    return (
        <div
            role="status"
            aria-busy="true"
            aria-label={t('investor.deal.loading')}
            className="flex min-w-[300px] flex-[1_1_400px] flex-col gap-3 rounded-[20px] border border-rz-border bg-rz-surface p-[18px] shadow-[0_10px_30px_-18px_rgba(20,45,95,.25)]"
        >
            <div className="grid grid-cols-3 gap-2.5">
                <div className="h-[62px] animate-pulse rounded-xl bg-rz-surface-sunken" />
                <div className="h-[62px] animate-pulse rounded-xl bg-rz-surface-sunken" />
                <div className="h-[62px] animate-pulse rounded-xl bg-rz-surface-sunken" />
            </div>
            <div className="h-[150px] animate-pulse rounded-2xl bg-rz-surface-sunken" />
            <div className="h-[120px] animate-pulse rounded-2xl bg-rz-surface-sunken" />
        </div>
    );
}

/**
 * Deals (MVP-INVESTOR-SCR-01). Phone (design L529–692): wallet and bell, the performance chips,
 * the fanned deck, the industry tabs and the invest bar. Desktop (L134–528): the same on a scaled
 * canvas, with the focused deal's detail panel beside the deck.
 */
export function DealsBody({ overlay, ...props }: DealsBodyProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const title = <h1 className="sr-only">{t('investor.deals.head_title')}</h1>;
    const { frame, scale, width, canvasHeight } = useFitScale(CANVAS_HEIGHT);
    const start = Math.max(
        0,
        props.deals.findIndex(
            (deal) => deal.campaign_id === props.focus?.campaign_id,
        ),
    );
    const [selected, setSelected] = useState(
        props.deals[start]?.campaign_id ?? null,
    );
    const academy = useAcademyNudge();
    const [nudgeFront, setNudgeFront] = useState(false);
    const [nudgeLater, setNudgeLater] = useState(false);
    // A cart removal can shorten or reorder the deck while this component stays mounted.
    const index = Math.max(
        0,
        props.deals.findIndex((deal) => deal.campaign_id === selected),
    );
    const current = props.deals[index] ?? null;
    /* Only read beside a deck: an empty list has its own "no deals open" state. */
    const allClosed = !props.deals.some(
        (deal) => deal.lifecycle === 'live' && deal.restriction === null,
    );
    const quoted = useQuotedUnits(Number(props.quote?.units ?? '1'), {
        deal: current?.campaign_id ?? '',
    });
    /* The quote is the focused deal's; another card in front shows none until it is focused. */
    const quote =
        current !== null &&
        props.focus !== null &&
        props.focus.campaign_id === current.campaign_id
            ? props.quote
            : null;

    const move = (next: number) => {
        const at = wrapIndex(next, props.deals.length);

        const target = props.deals[at];
        const destination =
            props.sorts.find((sort) => sort.active)?.link ??
            target.links.detail;

        setSelected(target.campaign_id);
        quoted.resetUnits();
        // The deck route honors ?deal; a detail or checkout route is bound to its path campaign.
        // Refresh the complete contract together, including campaign-bound cart and checkout URLs.
        router.visit(withQuery(destination, { deal: target.campaign_id }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    /* The phone deck deals the Academy nudge after the first deal while the Academy is under way. */
    const nudge =
        !wide && academy !== null && !nudgeLater && props.links.profile !== null
            ? {
                  ...academy,
                  href: {
                      ...props.links.profile,
                      url: `${props.links.profile.url}#academy`,
                  },
              }
            : null;
    const position = nudgeFront
        ? EXTRA_AT
        : nudge !== null && index >= EXTRA_AT
          ? index + 1
          : index;
    const moveDeck = (next: number) => {
        const at = wrapIndex(
            next,
            props.deals.length + (nudge === null ? 0 : 1),
        );

        setNudgeFront(nudge !== null && at === EXTRA_AT);

        if (nudge === null || at !== EXTRA_AT) {
            move(nudge !== null && at > EXTRA_AT ? at - 1 : at);
        }
    };
    const dealInFront = !(nudge !== null && nudgeFront);

    /* The checkout link is the focused deal's, so another card in front waits for its focus. */
    const checkout =
        current !== null &&
        props.focus?.campaign_id === current.campaign_id &&
        props.links.checkout !== null &&
        canReserve({
            deal: current,
            gate: props.gate,
            allowed: props.allowed_actions,
            quote,
        })
            ? withQuery(props.links.checkout, {
                  deal: current.campaign_id,
                  units: quoted.units,
              })
            : null;

    /* Like checkout, the cart command is the focused deal's; another card in front waits for it. */
    const inCart =
        current !== null &&
        props.cart !== null &&
        props.cart.campaign_ids.includes(current.campaign_id);
    const focused =
        current !== null && props.focus?.campaign_id === current.campaign_id;
    const cart: CartToggleState | null =
        props.cart === null
            ? null
            : {
                  inCart,
                  action: !focused
                      ? null
                      : inCart
                        ? props.cart.remove
                        : props.cart.add,
                  identityRevision: props.identity_context_revision,
              };

    const bar = (size: 'phone' | 'desk') =>
        current !== null && (
            <InvestBar
                deal={current}
                quote={quote}
                units={quoted.units}
                onUnits={quoted.setUnits}
                quoting={quoted.quoting}
                gate={props.gate}
                checkout={checkout}
                size={size}
            />
        );

    if (!wide) {
        return (
            <div className="flex min-h-svh flex-col pb-[calc(63px+env(safe-area-inset-bottom)+10px)]">
                {title}
                <PhoneTopBar
                    wallet={props.wallet}
                    unread={props.unread_notifications}
                    links={props.links}
                />
                <div className="px-4 pt-2">
                    <SortChips sorts={props.sorts} size="phone" />
                </div>
                {current === null ? (
                    <EmptyDeals gate={props.gate} />
                ) : (
                    <>
                        {allClosed && (
                            <AllClosedNote className="px-[22px] pt-2.5" />
                        )}
                        <PhoneDeck
                            deals={props.deals}
                            index={position}
                            serverTime={props.server_time}
                            onMove={moveDeck}
                            extra={
                                nudge === null ? null : (
                                    <AcademyNudge
                                        done={nudge.done}
                                        total={nudge.total}
                                        href={nudge.href}
                                        onLater={() => {
                                            setNudgeLater(true);
                                            setNudgeFront(false);
                                        }}
                                    />
                                )
                            }
                            cart={
                                cart !== null && (
                                    <CartToggle
                                        deal={current}
                                        cart={cart}
                                        units={quoted.units}
                                        size="card"
                                    />
                                )
                            }
                        />
                        {dealInFront && (
                            <IndustryTabs
                                industries={props.industries}
                                className="shrink-0 gap-4 px-[22px] pt-[26px] pb-px"
                            />
                        )}
                    </>
                )}
                <div className="min-h-3 flex-auto" />
                {dealInFront && bar('phone')}
                {overlay}
            </div>
        );
    }

    return (
        <div className="h-full px-[30px] pt-3 pb-3.5">
            {title}
            <div ref={frame} className="h-full overflow-hidden">
                <div
                    className="flex shrink-0 origin-top-left flex-col"
                    style={{
                        width: width ?? '100%',
                        height: canvasHeight,
                        transform: `scale(${scale})`,
                    }}
                >
                    <DeskTopBar
                        wallet={props.wallet}
                        unread={props.unread_notifications}
                        links={props.links}
                        status={
                            current !== null &&
                            allClosed && (
                                <AllClosedNote className="min-w-0 flex-1 px-2 text-left" />
                            )
                        }
                    />
                    <div className="flex min-h-0 flex-1 items-stretch gap-[26px]">
                        <div className="relative flex min-h-0 min-w-[420px] flex-[1_1_508px] flex-col">
                            <SortChips sorts={props.sorts} size="desk" />
                            {current === null ? (
                                <EmptyDeals gate={props.gate} />
                            ) : (
                                <>
                                    <DeskDeck
                                        deals={props.deals}
                                        index={index}
                                        serverTime={props.server_time}
                                        onMove={move}
                                    />
                                    <IndustryTabs
                                        industries={props.industries}
                                        className="mt-3.5 w-full shrink-0 flex-nowrap gap-[18px] overflow-y-hidden pb-0.5"
                                    />
                                </>
                            )}
                            {bar('desk')}
                            {overlay}
                        </div>
                        {props.focus !== null &&
                            (props.focus.campaign_id ===
                            current?.campaign_id ? (
                                <DealPanel
                                    key={props.focus.campaign_id}
                                    deal={props.focus}
                                    serverTime={props.server_time}
                                    cart={cart}
                                    units={quoted.units}
                                />
                            ) : (
                                <DealPanelLoading />
                            ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
