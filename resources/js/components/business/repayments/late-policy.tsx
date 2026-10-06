import { useTranslation } from '@/hooks/use-translation';
import type { LateFeeLadder } from '@/types/settlement';

/**
 * "If a payment is late" (design L1133–1189), from the server's versioned ladder: each step's
 * day and rate as disclosed, not an assessment. Without a ladder the policy is unavailable, and no
 * fee is shown or implied. Provisional with the steps themselves (#99 R1/R2).
 */
export function LatePolicy({ ladder }: { ladder: LateFeeLadder | null }) {
    const { t } = useTranslation();

    return (
        <section
            aria-label={t('business.repayments.late.title')}
            className="mt-[18px] rounded-2xl border border-rz-border bg-rz-surface p-4"
        >
            <h2 className="text-sm font-semibold text-rz-ink">
                {t('business.repayments.late.title')}
            </h2>
            {ladder === null ? (
                <p role="status" className="mt-2 text-xs text-rz-secondary">
                    {t('business.servicing.repay.ladder_unavailable')}
                </p>
            ) : (
                <>
                    <ol className="mt-2.5 flex flex-col gap-2">
                        {ladder.steps.map((step) => (
                            <li
                                key={step.step}
                                className="flex items-center justify-between gap-3 rounded-xl bg-rz-surface-sunken px-3 py-2.5 text-xs"
                            >
                                <span className="font-semibold text-rz-ink">
                                    {t(
                                        `business.servicing.repay.step.${step.step}`,
                                    )}
                                </span>
                                <span className="font-semibold text-rz-danger-text">
                                    {t('business.servicing.repay.step_rate', {
                                        percent: (step.rate_bps / 100).toFixed(
                                            step.rate_bps % 100 === 0 ? 0 : 2,
                                        ),
                                    })}
                                </span>
                            </li>
                        ))}
                    </ol>
                    <p className="mt-2 text-[10.5px] text-rz-secondary">
                        {t('business.servicing.repay.ladder_version', {
                            version: ladder.policy_version,
                        })}
                    </p>
                </>
            )}
        </section>
    );
}
