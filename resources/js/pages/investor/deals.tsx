import { DealsBody } from '@/components/investor/deals/deals-body';
import { InvestorShell } from '@/components/investor/investor-shell';
import { useTranslation } from '@/hooks/use-translation';
import type { InvestorDealsProps } from '@/types/investor';

/**
 * Deals (MVP-INVESTOR-SCR-01, design L529–692 phone, L134–528 desktop): rated deals with their
 * filters and live fill, and the invest bar for the deal in front.
 */
export default function InvestorDeals(props: InvestorDealsProps) {
    const { t } = useTranslation();

    return (
        <InvestorShell
            title={t('investor.deals.head_title')}
            tab="deals"
            links={props.links}
        >
            <DealsBody {...props} />
        </InvestorShell>
    );
}
