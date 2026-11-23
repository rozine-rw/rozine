import { Head, Link } from '@inertiajs/react';
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

                <article
                    className={cn(
                        'mt-6 rounded-2xl border border-rz-border bg-rz-surface p-5 text-[15px] leading-[1.65] text-rz-ink sm:p-8',
                        '[&_h1]:text-2xl [&_h1]:leading-tight [&_h1]:font-semibold sm:[&_h1]:text-3xl',
                        '[&_h1+p]:mt-2 [&_h1+p]:text-[13px] [&_h1+p]:text-rz-secondary',
                        '[&_h2]:mt-9 [&_h2]:text-lg [&_h2]:font-semibold',
                        '[&_li]:mt-2 [&_ol]:mt-3 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mt-3 [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-5',
                        '[&_blockquote]:mt-3 [&_blockquote]:rounded-xl [&_blockquote]:border-l-4 [&_blockquote]:border-rz-accent-fill [&_blockquote]:bg-rz-page [&_blockquote]:px-4 [&_blockquote]:py-2 [&_blockquote_p]:mt-1',
                        '[&_a]:font-semibold [&_a]:text-rz-accent-app-text [&_a]:underline [&_a]:underline-offset-4',
                        '[&_code]:rounded [&_code]:bg-rz-page [&_code]:px-1 [&_code]:text-[13px]',
                        '[&_table]:mt-4 [&_table]:block [&_table]:w-full [&_table]:overflow-x-auto [&_table]:text-sm [&_td]:border-t [&_td]:border-rz-border [&_td]:px-2 [&_td]:py-2 [&_td]:align-top [&_th]:px-2 [&_th]:py-2 [&_th]:text-left [&_th]:font-semibold',
                    )}
                    // The wording is the server's own published Markdown, rendered with raw HTML stripped.
                    dangerouslySetInnerHTML={{ __html: document.html }}
                />

                <p className="mt-4 text-xs leading-normal break-all text-rz-secondary">
                    {t('legal.fingerprint', {
                        version: document.version,
                        hash: document.sha256,
                    })}
                </p>
            </div>
        </main>
    );
}
