import { InfoNotice } from '@/components/investor/deals/deal-status';
import { InvestorShell } from '@/components/investor/investor-shell';
import {
    HoldingBanner,
    HoldingFigures,
    HoldingSchedule,
} from '@/components/investor/portfolio/holding-detail';
import {
    ExitOptions,
    InstalmentList,
    LateFeeBreakdownPanel,
    PayoutList,
    ServicingSummary,
} from '@/components/investor/portfolio/holding-servicing';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import type {
    C3HoldingDetail,
    C3InvestorHoldingProps,
    C4HoldingDetail,
    C4InvestorHoldingProps,
} from '@/types/investor';

/**
 * The figures column reads the C3 detail. A C4 holding carries its arrears with the server's
 * `dpd` in place of `days_overdue`; the value is passed through as sent, never recomputed.
 */
const asFigures = (
    holding: C3HoldingDetail | C4HoldingDetail,
): C3HoldingDetail =>
    'servicing' in holding
        ? {
              ...holding,
              arrears:
                  holding.arrears === null
                      ? null
                      : {
                            days_overdue: holding.arrears.dpd,
                            steps: holding.arrears.steps,
                        },
          }
        : holding;

/**
 * Note detail (MVP-INVESTOR-SCR-05, design L1304–1694): one holding's figures, schedule, rating
 * changes, recovery disclosures and audited reports. A phone opens it full screen without the tab
 * bar; a wide screen shows it in the Portfolio pane as two columns under a short banner. An
 * `investor-servicing-v1` page (C4 v1 §4c) adds the servicing summary and exit options beside the
 * figures, and the instalments, late-fee breakdown and payouts beside the schedule.
 */
export default function InvestorHolding(
    props: C3InvestorHoldingProps | C4InvestorHoldingProps,
) {
    const { t } = useTranslation();
    const wide = useWide();
    const { holding, links } = props;
    const figures = asFigures(holding);
    const servicing =
        props.contract_version === 'investor-servicing-v1' ? props : null;
    const frozen =
        holding.health === 'frozen' ? (
            <InfoNotice
                title={t('investor.holding.frozen_title')}
                body={t('investor.holding.frozen_body')}
            />
        ) : null;
    const summary = servicing !== null && (
        <>
            <ServicingSummary
                servicing={servicing.holding.servicing}
                bases={servicing.bases}
            />
            <ExitOptions
                eligibility={servicing.holding.secondary.eligibility}
            />
        </>
    );
    const history = servicing !== null && (
        <>
            <InstalmentList instalments={servicing.holding.instalments} />
            <LateFeeBreakdownPanel breakdown={servicing.holding.late_fees} />
            <PayoutList payouts={servicing.holding.payouts} />
        </>
    );

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
                            holding={figures}
                            back={links.back}
                            wide
                        />
                    </div>
                    <div className="flex min-h-0 flex-1 items-stretch gap-4 px-5 pt-4 pb-5">
                        <div className="rz-scroll min-h-0 min-w-0 flex-[0_0_calc(50%-8px)] overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface px-[18px] pt-4 pb-[18px]">
                            {frozen}
                            <HoldingFigures holding={figures} />
                            {summary}
                        </div>
                        <div className="rz-scroll relative min-h-0 min-w-0 flex-[0_0_calc(50%-8px)] overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface px-[18px] pt-0.5 pb-[18px]">
                            <HoldingSchedule holding={figures} />
                            {history}
                        </div>
                    </div>
                </div>
            ) : (
                <div className="pb-10">
                    <HoldingBanner
                        holding={figures}
                        back={links.back}
                        wide={false}
                    />
                    <div className="px-5 pt-[18px]">
                        {frozen}
                        <HoldingFigures holding={figures} />
                        {summary}
                        <HoldingSchedule holding={figures} />
                        {history}
                    </div>
                </div>
            )}
        </InvestorShell>
    );
}
