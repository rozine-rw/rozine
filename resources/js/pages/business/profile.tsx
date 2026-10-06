import { Link } from '@inertiajs/react';
import { BusinessShell } from '@/components/business/business-shell';
import { CompanyForm } from '@/components/business/profile/company-form';
import { CompanyRecords } from '@/components/business/profile/company-records';
import { LegalDocument } from '@/components/business/profile/legal-document';
import { LinkedAccounts } from '@/components/business/profile/linked-accounts';
import { ProfileMenu } from '@/components/business/profile/profile-menu';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { BusinessProfileProps } from '@/types/business';

const COLUMN =
    'lg:min-h-0 lg:min-w-0 lg:flex-[0_0_calc(50%-8px)] lg:overflow-y-auto lg:rounded-2xl lg:border lg:border-rz-border lg:bg-rz-surface';

/**
 * Profile (MVP-BUSINESS-SCR-09, design L1460–1479 and L1718–1891). A phone shows the menu, then
 * each section as its own page; a wide screen keeps the menu beside the open section.
 */
export default function BusinessProfile({
    business,
    section,
    landing,
    company,
    provinces,
    linked,
    legal,
    links,
    actions,
}: BusinessProfileProps) {
    const { t } = useTranslation();

    return (
        <BusinessShell
            title={t(`business.profile.section.${section}`)}
            tab="profile"
            links={links}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+4px)] pb-[92px] lg:flex lg:h-full lg:gap-4 lg:px-5 lg:pt-4 lg:pb-5">
                <div
                    data-rzcol
                    className={cn(
                        COLUMN,
                        'rz-scroll lg:px-[18px] lg:pt-4 lg:pb-5',
                        !landing && 'max-lg:hidden',
                    )}
                >
                    <ProfileMenu
                        business={business}
                        section={section}
                        links={links}
                    />
                </div>
                <section
                    data-rzcol
                    aria-labelledby="profile-section-title"
                    className={cn(
                        COLUMN,
                        'rz-scroll lg:px-5 lg:pt-[18px] lg:pb-6',
                        landing && 'max-lg:hidden',
                    )}
                >
                    <div className="flex items-center gap-3">
                        <Link
                            href={links.back}
                            aria-label={t('business.profile.back')}
                            className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink lg:hidden"
                        >
                            <span aria-hidden>←</span>
                        </Link>
                        <h1
                            id="profile-section-title"
                            className="text-xl font-semibold text-rz-ink"
                        >
                            {t(`business.profile.section.${section}`)}
                        </h1>
                    </div>
                    {section === 'company' && (
                        <>
                            <CompanyForm
                                company={company}
                                provinces={provinces}
                                action={actions.save_company}
                            />
                            <CompanyRecords company={company} />
                        </>
                    )}
                    {section === 'linked' && linked !== null && (
                        <LinkedAccounts linked={linked} />
                    )}
                    {(section === 'terms' || section === 'privacy') &&
                        legal !== null && (
                            <LegalDocument kind={section} document={legal} />
                        )}
                </section>
            </div>
        </BusinessShell>
    );
}
