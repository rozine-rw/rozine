import { KeyValues } from '@/components/business/onboarding/key-values';
import { useTranslation } from '@/hooks/use-translation';
import type { LinkedBankAccount, RegistryRecord } from '@/types/business';

type StepFinishProps = {
    registry: RegistryRecord;
    industry: string;
    contact: string;
    account: LinkedBankAccount | null;
    finishing: boolean;
};

/** Step 4 of 4 — "You're all set" (design L1696–1709): one last look before the workspace opens. */
export function StepFinish({
    registry,
    industry,
    contact,
    account,
    finishing,
}: StepFinishProps) {
    const { t } = useTranslation();
    const verified = (
        <span className="inline-flex shrink-0 items-center gap-[3px] rounded-[10px] border border-[#cdeddb] bg-[#e7f7ee] px-1.5 py-0.5 text-[10.5px] font-bold text-rz-accent-app-text dark:border-transparent dark:bg-rz-accent-soft">
            {t('business.onboarding.finish.rdb')}
        </span>
    );

    return (
        <>
            <h2 className="mt-1.5 text-[22px] font-semibold text-rz-ink">
                {t('business.onboarding.finish.title')}
            </h2>
            <p className="mt-1.5 text-[13.5px] text-rz-secondary">
                {t('business.onboarding.finish.subtitle')}
            </p>
            <div className="mt-[18px]">
                <KeyValues
                    rows={[
                        {
                            label: t('business.onboarding.finish.company'),
                            value: (
                                <span className="flex items-center justify-end gap-1.5">
                                    {registry.name}
                                    {verified}
                                </span>
                            ),
                        },
                        {
                            label: t(
                                'business.onboarding.confirm.company_code',
                            ),
                            value: registry.company_code,
                        },
                        {
                            label: t('business.onboarding.confirm.industry'),
                            value: industry,
                        },
                        {
                            label: t('business.onboarding.finish.contact'),
                            value: contact,
                        },
                        {
                            label: t('business.onboarding.finish.payout'),
                            value: (
                                <span className="flex items-center justify-end gap-1.5">
                                    {account === null
                                        ? '—'
                                        : `${account.bank_name} ··${account.masked_number.slice(-4)}`}
                                    {account !== null && (
                                        <span className="text-xs font-bold text-rz-accent-app-text">
                                            ✓
                                        </span>
                                    )}
                                </span>
                            ),
                        },
                    ]}
                />
            </div>
            {finishing && (
                <p
                    role="status"
                    className="mt-[18px] flex items-center justify-center gap-2.5 text-[13px] font-semibold text-rz-secondary"
                >
                    <span className="size-[18px] animate-spin rounded-full border-2 border-rz-accent-fill border-t-transparent" />
                    {t('business.onboarding.finish.setting_up')}
                </p>
            )}
        </>
    );
}
