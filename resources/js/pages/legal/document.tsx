import { Head, Link } from '@inertiajs/react';
import { LegalDocumentBody } from '@/components/legal/legal-document-body';
import { LogoLockup } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import type { LegalDocumentPageProps } from '@/types/legal';

/**
 * A public legal document: the Terms, the Privacy Notice or the Disclaimers, exactly as issued,
 * with its version and the fingerprint a consent records. A signed-in person who has not agreed
 * to the current wording is pointed to the review page.
 */
export default function LegalDocumentPage({
    document,
    documents,
    review,
}: LegalDocumentPageProps) {
    const { t } = useTranslation();

    return (
        <main
            data-audience="investor"
            className="rz-surface min-h-svh bg-rz-page"
        >
            <Head title={document.title} />
            <div className="mx-auto w-full max-w-[760px] px-4 pt-[calc(env(safe-area-inset-top)+20px)] pb-16 sm:pt-12">
                <div className="flex items-center justify-between gap-4">
                    <Link href={home()} aria-label={t('legal.back_home')}>
                        <LogoLockup
                            title={t('common.brand.name')}
                            className="h-7 w-auto text-rz-accent-lockup"
                        />
                    </Link>
                    <Link
                        href={home()}
                        className="text-sm font-semibold text-rz-accent-app-text"
                    >
                        {t('legal.back_home')}
                    </Link>
                </div>

                <nav
                    aria-label={t('legal.nav_label')}
                    className="mt-6 flex flex-wrap gap-2"
                >
                    {documents.map((item) => (
                        <Link
                            key={item.key}
                            href={item.url}
                            aria-current={
                                item.key === document.key ? 'page' : undefined
                            }
                            className={cn(
                                'rounded-full border px-3.5 py-1.5 text-[13px] font-semibold',
                                item.key === document.key
                                    ? 'border-rz-accent-fill bg-rz-accent-fill text-white'
                                    : 'border-rz-border bg-rz-surface text-rz-ink',
                            )}
                        >
                            {item.title}
                        </Link>
                    ))}
                </nav>

                {review !== null && (
                    <section
                        role="status"
                        className="mt-5 flex flex-col gap-3 rounded-2xl border border-rz-border bg-rz-surface p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p className="text-sm leading-[1.55] text-rz-ink">
                            {t('legal.review_banner')}
                        </p>
                        <Link
                            href={review}
                            className="shrink-0 rounded-xl bg-rz-accent-fill px-4 py-2.5 text-center text-sm font-semibold text-white"
                        >
                            {t('legal.review_banner_cta')}
                        </Link>
                    </section>
                )}

                <LegalDocumentBody
                    document={document}
                    targets={{}}
                    className="mt-6 rounded-2xl border border-rz-border bg-rz-surface p-5 sm:p-8"
                />
            </div>
        </main>
    );
}
