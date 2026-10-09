import { Head, Link } from '@inertiajs/react';
import { useAccessRefresh } from '@/hooks/use-access-refresh';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';
import type { StaffAccess } from '@/types/identity';
import type { RouteLink } from '@/types/routing';

/** The live console sections, in the console's own order, the Operations Center first. */
const SECTIONS = [
    'today',
    'investors',
    'applications',
    'disbursements',
] as const;

export default function StaffHome({
    staff_access,
    sections = {},
    staging_mail_testers = null,
}: {
    staff_access: StaffAccess;
    /** Each console section this account may open; the server sends null for the others. */
    sections?: Partial<Record<(typeof SECTIONS)[number], RouteLink | null>>;
    /** Sent only on staging, to staff who may manage the staging mail testers. */
    staging_mail_testers?: RouteLink | null;
}) {
    const { t } = useTranslation();
    useAccessRefresh(['staff_access', 'sections', 'staging_mail_testers']);

    return (
        <main className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
            <Head title={t('identity.home.admin')} />
            <Link href={dashboard()}>{t('identity.home.back')}</Link>
            <h1 className="text-2xl font-semibold">
                {t('identity.home.admin')}
            </h1>
            <p role="status">
                {t(
                    staff_access.can_open_admin
                        ? 'identity.home.staff_ready'
                        : 'identity.denied.body',
                )}
            </p>
            {SECTIONS.map((section) => {
                const link = sections[section];

                return (
                    link && (
                        <Link key={section} href={link.url}>
                            {t(`admin.section.${section}.title`)}
                        </Link>
                    )
                );
            })}
            {staging_mail_testers && (
                <Link href={staging_mail_testers.url}>
                    {t('identity.home.staging_mail_testers')}
                </Link>
            )}
            <Link href={edit()}>{t('identity.home.settings')}</Link>
        </main>
    );
}
