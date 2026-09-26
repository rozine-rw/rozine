import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';

/**
 * A browser read the server refused (403 or 409), with the server's reason `code`. Most codes are
 * about the account or its selected role, and read as such; an offer whose acceptance window
 * closed is no access problem, so it says the offer closed. The status and its meaning are the
 * server's; only the wording follows the code.
 */
export default function AccessDenied({ code }: { code?: string }) {
    const { t } = useTranslation();
    const expired = code === 'ASSIGNMENT_ACCEPTANCE_EXPIRED';
    const title = expired
        ? t('identity.denied.expired_offer.title')
        : t('identity.denied.title');

    return (
        <main className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
            <Head title={title} />
            <h1 className="text-2xl font-semibold">{title}</h1>
            <p role="alert">
                {expired
                    ? t('identity.denied.expired_offer.body')
                    : t('identity.denied.body')}
            </p>
            <Link href={dashboard()}>{t('identity.home.back')}</Link>
        </main>
    );
}
