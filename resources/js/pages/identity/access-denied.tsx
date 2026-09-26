import { Head, Link, usePage } from '@inertiajs/react';
import { Icon, IconGradients } from '@/components/rozine/icon';
import { LogoLockup } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard, home } from '@/routes';

type Props = {
    /** The server's reason code; kept for support, never shown. */
    code?: string;
    /** The HTTP status the server answered with: 403 and 409 refuse, 404 found nothing. */
    status?: number;
};

/**
 * A browser read found nothing (404): an unknown or withdrawn report, offer or document. It is no
 * account-access problem, and the public seal check reaches it with no account at all, so it says
 * nothing about roles or access and takes the public layout. A signed-in person goes back to their
 * apps; anyone else to the Rozine home page.
 */
function NotFound() {
    const { t } = useTranslation();
    /* The shared `auth.user` is null for a visitor with no session. */
    const signedIn = Boolean(usePage().props.auth.user);

    return (
        <main
            data-audience="investor"
            className="rz-surface min-h-svh bg-rz-page"
        >
            <Head title={t('errors.not_found.head_title')} />
            <IconGradients />
            <div className="mx-auto w-full max-w-[520px] px-4 pt-[calc(env(safe-area-inset-top)+20px)] pb-10 sm:pt-12">
                <LogoLockup
                    title={t('common.brand.name')}
                    className="h-7 w-auto text-rz-accent-lockup"
                />
                <section className="mt-7 flex items-start gap-3 rounded-2xl border border-rz-border bg-rz-surface p-4">
                    <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                        <Icon name="warning" tone="amber" />
                    </span>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-[17px] font-bold text-rz-ink">
                            {t('errors.not_found.title')}
                        </h1>
                        <p className="mt-1 text-[13px] leading-[1.55] text-rz-secondary">
                            {t('errors.not_found.body')}
                        </p>
                    </div>
                </section>
                <Link
                    href={signedIn ? dashboard() : home()}
                    className="mt-4 inline-flex h-11 items-center justify-center rounded-xl bg-rz-accent-fill px-5 text-[14px] font-bold text-white"
                >
                    {signedIn
                        ? t('identity.home.back')
                        : t('errors.not_found.home')}
                </Link>
            </div>
        </main>
    );
}

/**
 * The error page for a browser read the server did not answer with its page. A 404 is a
 * not-found page. A refusal (403 or 409) keeps the account-access wording for most codes; an
 * offer whose acceptance window closed is no access problem, so it says the offer closed. The
 * status and its meaning are the server's; only the wording follows it.
 */
export default function AccessDenied({ code, status }: Props) {
    const { t } = useTranslation();

    if (status === 404) {
        return <NotFound />;
    }

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
