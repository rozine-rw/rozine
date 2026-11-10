import { usePage } from '@inertiajs/react';
import { RiskStatementsStep } from '@/components/investor/deals/risk-statements';
import type { InvestorRiskAcknowledgementDue } from '@/types/investor';

/**
 * The once-only risk acknowledgement (BRS v3.0 KYC-R05). While the shared
 * `risk_acknowledgement` prop names statements the verified Investor has not yet acknowledged in
 * their current wording, every Investor page opens on them, so a first checkout has nothing extra
 * to do. Accepting reloads the page, which then carries no prop.
 */
export function RiskGate() {
    const due = usePage().props.risk_acknowledgement as
        | InvestorRiskAcknowledgementDue
        | null
        | undefined;

    if (!due) {
        return null;
    }

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="risk-statements-title"
            className="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center"
        >
            <div className="max-h-full w-full max-w-[480px] overflow-y-auto">
                <RiskStatementsStep
                    risks={{ ...due, acknowledged: false }}
                    action={due.action}
                    identityRevision={due.identity_context_revision}
                />
            </div>
        </div>
    );
}
