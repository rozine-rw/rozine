import { KeyValues } from '@/components/business/onboarding/key-values';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { CompanyProfile } from '@/types/business';

/**
 * The certificate and signatories on file (MVP-BUSINESS-SCR-09). The design has no screen for
 * these, so they reuse its onboarding key–value card and people rows, read-only.
 */
export function CompanyRecords({ company }: { company: CompanyProfile }) {
    const { t, locale } = useTranslation();
    const { certificate } = company;
    const expired = certificate.status === 'expired';

    return (
        <>
            <p className="mt-6 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                {t('business.profile.records.certificate')}
            </p>
            {expired && (
                <p
                    role="alert"
                    className="mt-2.5 rounded-[10px] border border-[#fdeaea] bg-[rgba(229,72,77,.08)] px-[13px] py-[11px] text-[12.5px] font-semibold text-rz-danger-text dark:border-[rgba(255,107,111,.25)]"
                >
                    {t('business.profile.records.expired_notice')}
                </p>
            )}
            <div className="mt-2.5">
                <KeyValues
                    rows={[
                        {
                            label: t('business.profile.records.number'),
                            value: certificate.number,
                        },
                        {
                            label: t('business.profile.records.status'),
                            value: (
                                <span
                                    className={cn(
                                        expired
                                            ? 'text-rz-danger-text'
                                            : 'text-rz-accent-app-text',
                                    )}
                                >
                                    {t(
                                        `business.profile.records.status_${certificate.status}`,
                                    )}
                                </span>
                            ),
                        },
                        {
                            label: t('business.profile.records.expires'),
                            value:
                                certificate.expires_on === null
                                    ? t('business.profile.records.no_expiry')
                                    : formatDate(
                                          certificate.expires_on,
                                          locale,
                                      ),
                        },
                    ]}
                />
            </div>
            <div className="mt-5 flex items-center justify-between">
                <p className="text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                    {t('business.profile.records.signatories')}
                </p>
                <span className="text-[11px] font-semibold text-rz-secondary">
                    {t('business.profile.records.mandate', {
                        count: company.signatories.length,
                        required: company.signatories_required,
                    })}
                </span>
            </div>
            <ul className="mt-[9px] flex flex-col gap-[9px]">
                {company.signatories.map((person) => (
                    <li
                        key={`${person.name}-${person.role}`}
                        className="flex items-center gap-[11px] rounded-xl border border-rz-border bg-rz-surface px-[13px] py-[11px]"
                    >
                        <span className="flex size-[34px] items-center justify-center rounded-full bg-rz-page text-[13px]">
                            <Icon name="person" />
                        </span>
                        <span className="flex-1">
                            <span className="block text-[13.5px] font-semibold text-rz-ink">
                                {person.name}
                            </span>
                            <span className="block text-[11.5px] text-rz-secondary">
                                {person.role}
                            </span>
                        </span>
                    </li>
                ))}
            </ul>
        </>
    );
}
