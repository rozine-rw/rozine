import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';
import type { StaffAccess } from '@/types/identity';

export default function StaffHome({
    staff_access,
}: {
    staff_access: StaffAccess;
}) {
    const { t } = useTranslation();

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
            <Link href={edit()}>{t('identity.home.settings')}</Link>
        </main>
    );
}
