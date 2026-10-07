import { Head, Link } from '@inertiajs/react';
import { useAccessRefresh } from '@/hooks/use-access-refresh';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';
import type { StaffAccess } from '@/types/identity';
import type { RouteLink } from '@/types/routing';

export default function StaffHome({
    staff_access,
    staging_mail_testers = null,
}: {
    staff_access: StaffAccess;
    /** Sent only on staging, to staff who may manage the staging mail testers. */
    staging_mail_testers?: RouteLink | null;
}) {
    const { t } = useTranslation();
    useAccessRefresh(['staff_access']);

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
            {staging_mail_testers && (
                <Link href={staging_mail_testers.url}>
                    {t('identity.home.staging_mail_testers')}
                </Link>
            )}
            <Link href={edit()}>{t('identity.home.settings')}</Link>
        </main>
    );
}
