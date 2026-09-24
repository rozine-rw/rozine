import { InfoNotice } from '@/components/investor/deals/deal-status';
import { InvestorShell } from '@/components/investor/investor-shell';
import {
    HoldingBanner,
    HoldingFigures,
    HoldingSchedule,
} from '@/components/investor/portfolio/holding-detail';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import type { InvestorHoldingProps } from '@/types/investor';

/**
 * Note detail (MVP-INVESTOR-SCR-05, design L1304–1694): one holding's figures, schedule, rating
 * changes, recovery disclosures and audited reports. A phone opens it full screen without the tab
 * bar; a wide screen shows it in the Portfolio pane as two columns under a short banner.
 */
export default function InvestorHolding({
    holding,
    links,
}: InvestorHoldingProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const frozen =
        holding.health === 'frozen' ? (
            <InfoNotice
                title={t('investor.holding.frozen_title')}
                body={t('investor.holding.frozen_body')}
            />
        ) : null;

    return (
        <InvestorShell
            title={holding.name}
            tab="portfolio"
            links={links}
            showTabBar={false}
        >
            {wide ? (
                <div className="flex h-full flex-col px-[30px] pt-3 pb-3.5">
                    <div className="px-5 pt-4">
                        <HoldingBanner
                            holding={holding}
                            back={links.back}
                            wide
                        />
                    </div>
                    <div className="flex min-h-0 flex-1 items-stretch gap-4 px-5 pt-4 pb-5">
                        <div className="rz-scroll min-h-0 min-w-0 flex-[0_0_calc(50%-8px)] overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface px-[18px] pt-4 pb-[18px]">
                            {frozen}
                            <HoldingFigures holding={holding} />
                        </div>
                        <div className="rz-scroll relative min-h-0 min-w-0 flex-[0_0_calc(50%-8px)] overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface px-[18px] pt-0.5 pb-[18px]">
                            <HoldingSchedule holding={holding} />
                        </div>
                    </div>
                </div>
            ) : (
                <div className="pb-10">
                    <HoldingBanner
                        holding={holding}
                        back={links.back}
                        wide={false}
                    />
                    <div className="px-5 pt-[18px]">
                        {frozen}
                        <HoldingFigures holding={holding} />
                        <HoldingSchedule holding={holding} />
                    </div>
                </div>
            )}
        </InvestorShell>
    );
}
