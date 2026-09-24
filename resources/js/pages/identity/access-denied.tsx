import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';

export default function AccessDenied() {
    const { t } = useTranslation();

    return (
        <main className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
            <Head title={t('identity.denied.title')} />
            <h1 className="text-2xl font-semibold">
                {t('identity.denied.title')}
            </h1>
            <p role="alert">{t('identity.denied.body')}</p>
            <Link href={dashboard()}>{t('identity.home.back')}</Link>
        </main>
    );
}
