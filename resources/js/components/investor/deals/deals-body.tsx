import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    DeskDeck,
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
import { canReserve, InvestBar } from '@/components/investor/deals/invest-bar';
import { useQuotedUnits } from '@/components/investor/deals/use-quote';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { withQuery } from '@/lib/investor/links';
import { useFitScale } from '@/lib/investor/use-fit-scale';
import { useWide } from '@/lib/investor/use-wide';
import type { C3InvestorDealsProps, InvestGate } from '@/types/investor';

type DealsBodyProps = C3InvestorDealsProps & {
    /** A sheet drawn over the deck and invest bar (checkout), anchored to that column. */
    overlay?: ReactNode;
};

/** The design's Deals canvas height (L136); a shorter pane scales the canvas down. */
const CANVAS_HEIGHT = 720;

/**
 * "No deals open" (crosswalk SCR-01-ST-01), in the design's caught-up layout (L596–603). An
 * unverified Investor gets the gate only (H8): no deal, name or figure is sent, just the way to
 * verify.
 */
function EmptyDeals({ gate }: { gate: InvestGate }) {
    const { t } = useTranslation();

    if (gate.status === 'verification_required') {
        return (
            <div className="flex flex-1 flex-col items-center justify-center px-[22px] py-16 text-center">
                <span className="flex size-[72px] items-center justify-center rounded-[20px] bg-rz-accent-soft text-[26px]">
                    <Icon name="shield" />
                </span>
                <p className="mt-4 text-lg font-semibold text-rz-ink">
                    {t('investor.deals.gated_title')}
                </p>
                <p className="mt-1.5 max-w-[250px] text-[13px] leading-normal text-rz-secondary">
                    {t('investor.deals.gated_body')}
                </p>
                <Link
                    href={gate.link}
                    className="mt-4 flex h-11 items-center justify-center rounded-[14px] bg-rz-accent-fill px-5 text-sm font-semibold text-white"
                >
                    {t('investor.deals.verify_to_invest')}
                </Link>
            </div>
        );
    }

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
        </div>
    );
}

/**
 * Deals (MVP-INVESTOR-SCR-01). Phone (design L529–692): wallet and bell, the performance chips,
 * the fanned deck, the industry tabs and the invest bar. Desktop (L134–528): the same on a scaled
 * canvas, with the focused deal's detail panel beside the deck.
 */
export function DealsBody({ overlay, ...props }: DealsBodyProps) {
    const wide = useWide();
    const { frame, scale, width } = useFitScale(CANVAS_HEIGHT);
    const start = Math.max(
        0,
        props.deals.findIndex(
            (deal) => deal.campaign_id === props.focus?.campaign_id,
        ),
    );
    const [index, setIndex] = useState(start);
    const current = props.deals.length > 0 ? props.deals[index] : null;
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

        setIndex(at);
        quoted.resetUnits();
        router.reload({
            only: ['focus', 'quote'],
            data: { deal: props.deals[at].campaign_id },
        });
    };

    const checkout =
        current !== null &&
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
                        <PhoneDeck
                            deals={props.deals}
                            index={index}
                            serverTime={props.server_time}
                            onMove={move}
                        />
                        <IndustryTabs
                            industries={props.industries}
                            className="shrink-0 gap-4 px-[22px] pt-[26px] pb-px"
                        />
                    </>
                )}
                <div className="min-h-3 flex-auto" />
                {bar('phone')}
                {overlay}
            </div>
        );
    }

    return (
        <div className="h-full px-[30px] pt-3 pb-3.5">
            <div ref={frame} className="h-full overflow-hidden">
                <div
                    className="flex h-[720px] shrink-0 origin-top-left flex-col"
                    style={{
                        width: width ?? '100%',
                        transform: `scale(${scale})`,
                    }}
                >
                    <DeskTopBar
                        wallet={props.wallet}
                        unread={props.unread_notifications}
                        links={props.links}
                    />
                    <div className="flex min-h-0 flex-1 items-stretch gap-[26px]">
                        <div className="relative flex min-h-0 min-w-0 flex-[0_0_508px] flex-col">
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
                        {props.focus !== null && (
                            <DealPanel
                                key={props.focus.campaign_id}
                                deal={props.focus}
                                serverTime={props.server_time}
                            />
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
