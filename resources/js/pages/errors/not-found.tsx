import { Head, Link, usePage } from '@inertiajs/react';
import { Icon, IconGradients } from '@/components/rozine/icon';
import { LogoLockup } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard, home } from '@/routes';

/**
 * What a browser read gets when the server found nothing to show (a 404): an unknown or withdrawn
 * report, offer or document. It is not an account-access problem, and the public seal check
 * reaches it with no account at all, so it says nothing about roles or access. A signed-in person
 * goes back to their apps; anyone else to the Rozine home page. The server's `code` is kept for
 * support but not shown.
 */
export default function NotFound() {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    /* The shared `auth.user` is null for a visitor with no session. */
    const signedIn = Boolean(auth.user);

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
