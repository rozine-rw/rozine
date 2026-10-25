import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Icon, IconGradients } from '@/components/rozine/icon';
import { LogoLockup } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard, home } from '@/routes';
import { verification as investorVerification } from '@/routes/investor';

type Props = {
    /** The server's reason code; kept for support, never shown. */
    code?: string;
    /**
     * The HTTP status the server answered with: 403 and 409 refuse, 404 found nothing, 500 and
     * above failed on the server's side.
     */
    status?: number;
};

/**
 * The branded frame every answer of this page shares: the Rozine lockup, one card with a warning
 * mark, the title and the body, then the recovery actions. It draws its own surface, so a refusal
 * no longer sits inside the generic app shell.
 */
function ErrorFrame({
    headTitle,
    title,
    body,
    alert,
    children,
}: {
    /** The browser tab title; the card title when left out. */
    headTitle?: string;
    title: string;
    body: string;
    /** Whether the card announces itself; the not-found answer is not an alert. */
    alert: 'card' | 'body' | 'none';
    children: ReactNode;
}) {
    const { t } = useTranslation();

    return (
        <main
            data-audience="investor"
            className="rz-surface min-h-svh bg-rz-page"
        >
            <Head title={headTitle ?? title} />
            <IconGradients />
            <div className="mx-auto w-full max-w-[520px] px-4 pt-[calc(env(safe-area-inset-top)+20px)] pb-10 sm:pt-12">
                <LogoLockup
                    title={t('common.brand.name')}
                    className="h-7 w-auto text-rz-accent-lockup"
                />
                <section
                    role={alert === 'card' ? 'alert' : undefined}
                    className="mt-7 flex items-start gap-3 rounded-2xl border border-rz-border bg-rz-surface p-4"
                >
                    <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                        <Icon name="warning" tone="amber" />
                    </span>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-[17px] font-bold text-rz-ink">
                            {title}
                        </h1>
                        <p
                            role={alert === 'body' ? 'alert' : undefined}
                            className="mt-1 text-[13px] leading-[1.55] text-rz-secondary"
                        >
                            {body}
                        </p>
                    </div>
                </section>
                <div className="mt-4 flex flex-wrap items-center gap-x-5 gap-y-3">
                    {children}
                </div>
            </div>
        </main>
    );
}

const PRIMARY =
    'inline-flex h-11 items-center justify-center rounded-xl bg-rz-accent-fill px-5 text-[14px] font-bold text-white';
const SECONDARY =
    'inline-flex min-h-11 items-center text-[14px] font-semibold text-rz-accent-text underline underline-offset-4';

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
        <ErrorFrame
            headTitle={t('errors.not_found.head_title')}
            title={t('errors.not_found.title')}
            body={t('errors.not_found.body')}
            alert="none"
        >
            <Link href={signedIn ? dashboard() : home()} className={PRIMARY}>
                {signedIn
                    ? t('identity.home.back')
                    : t('errors.not_found.home')}
            </Link>
        </ErrorFrame>
    );
}

/**
 * A full-page browser read the server failed on (500 and above). It says only that Rozine could
 * not load the page, never why: no exception, reference or status reaches the copy. Try again
 * reads the same URL afresh with a plain GET, so it can never resend a command, and it needs no
 * session, which the failure may have taken with it.
 */
function ServiceUnavailable() {
    const { t } = useTranslation();
    const { url } = usePage();

    return (
        <ErrorFrame
            headTitle={t('errors.unavailable.head_title')}
            title={t('errors.unavailable.title')}
            body={t('errors.unavailable.body')}
            alert="card"
        >
            <a href={url} className={PRIMARY}>
                {t('errors.unavailable.retry')}
            </a>
        </ErrorFrame>
    );
}

/**
 * The error page for a browser read the server did not answer with its page. A 404 is a
 * not-found page and a 5xx a could-not-load page. A refusal (403 or 409) keeps the account-access wording for most codes; an
 * offer whose acceptance window closed is no access problem, so it says the offer closed. The
 * status and its meaning are the server's; only the wording follows it.
 */
export default function AccessDenied({ code, status }: Props) {
    const { t } = useTranslation();

    if (status === 404) {
        return <NotFound />;
    }

    if (status !== undefined && status >= 500) {
        return <ServiceUnavailable />;
    }

    const expired = code === 'ASSIGNMENT_ACCEPTANCE_EXPIRED';
    const verificationRequired = code === 'IDENTITY_VERIFICATION_REQUIRED';
    /* An app the account never had, or a staff page, is not access it lost. */
    const copy = expired
        ? 'identity.denied.expired_offer'
        : verificationRequired
          ? 'identity.denied.verification_required'
          : code === 'ROLE_NOT_AVAILABLE'
            ? 'identity.denied.role_not_available'
            : code === 'STAFF_ACCESS_REQUIRED'
              ? 'identity.denied.staff_only'
              : 'identity.denied';

    return (
        <ErrorFrame
            title={t(`${copy}.title`)}
            body={t(`${copy}.body`)}
            alert="body"
        >
            {verificationRequired && (
                <Link href={investorVerification()} className={PRIMARY}>
                    {t('identity.denied.verification_required.action')}
                </Link>
            )}
            <Link
                href={dashboard()}
                className={verificationRequired ? SECONDARY : PRIMARY}
            >
                {t('identity.home.back')}
            </Link>
        </ErrorFrame>
    );
}
