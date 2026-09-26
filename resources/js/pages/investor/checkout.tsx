import { CheckoutSheet } from '@/components/investor/deals/checkout-sheet';
import { DealsBody } from '@/components/investor/deals/deals-body';
import { InvestorShell } from '@/components/investor/investor-shell';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import type { C3InvestorCheckoutProps } from '@/types/investor';

/**
 * Checkout (MVP-INVESTOR-SCR-03, C3 v2 §2c): reserve, then confirm, as a sheet over Deals — a
 * bottom sheet on a phone (design L4782–4939), and on a wide screen a card centred over the deck
 * and invest bar, inside that column (L247–324). With no Deals home the sheet opens over an empty
 * backdrop, with no stand-in figures.
 */
export default function InvestorCheckout({
    home,
    ...props
}: C3InvestorCheckoutProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const sheet = (
        <CheckoutSheet {...props} variant={wide ? 'desk' : 'phone'} />
    );

    return (
        <InvestorShell
            title={t('investor.checkout.title')}
            tab="deals"
            links={props.links}
        >
            {home === null ? (
                <div className="relative min-h-svh lg:h-full">{sheet}</div>
            ) : (
                <DealsBody {...home} overlay={sheet} />
            )}
        </InvestorShell>
    );
}
