import { DealDetailPage } from '@/components/investor/deals/deal-detail';
import { DealsBody } from '@/components/investor/deals/deals-body';
import { InvestorShell } from '@/components/investor/investor-shell';
import { useWide } from '@/lib/investor/use-wide';
import type { C3InvestorDealProps } from '@/types/investor';

/**
 * Deal detail (MVP-INVESTOR-SCR-02). A phone opens it full screen without the tab bar (design
 * L729–997); a wide screen shows the same deal in the Deals detail panel, as the design's desktop
 * does (L375–466).
 */
export default function InvestorDeal({ home, ...props }: C3InvestorDealProps) {
    const wide = useWide();

    return (
        <InvestorShell
            title={props.deal.name}
            tab="deals"
            links={home.links}
            showTabBar={false}
        >
            {wide ? <DealsBody {...home} /> : <DealDetailPage {...props} />}
        </InvestorShell>
    );
}
