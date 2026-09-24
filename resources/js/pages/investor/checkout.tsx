import { CheckoutSheet } from '@/components/investor/deals/checkout-sheet';
import { DealsBody } from '@/components/investor/deals/deals-body';
import { InvestorShell } from '@/components/investor/investor-shell';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import type { InvestorCheckoutProps } from '@/types/investor';

/**
 * Checkout (MVP-INVESTOR-SCR-03): the purchase sheet over Deals — a bottom sheet on a phone
 * (design L4782–4939), and on a wide screen a card centred over the deck and invest bar, inside
 * that column (L247–324).
 */
export default function InvestorCheckout({
    home,
    ...props
}: InvestorCheckoutProps) {
    const { t } = useTranslation();
    const wide = useWide();

    return (
        <InvestorShell
            title={t('investor.checkout.title')}
            tab="deals"
            links={home.links}
        >
            <DealsBody
                {...home}
                overlay={
                    <CheckoutSheet
                        {...props}
                        variant={wide ? 'desk' : 'phone'}
                    />
                }
            />
        </InvestorShell>
    );
}
