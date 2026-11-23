import { Form, Head, Link } from '@inertiajs/react';
import { LegalConsentFields } from '@/components/legal/legal-consent-fields';
import { LogoLockup } from '@/components/rozine/logo';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { logout } from '@/routes';
import type { LegalReviewProps } from '@/types/legal';

/**
 * Shown before a signed-in person acts in any app when they have not agreed to the current Terms,
 * Privacy Notice and Disclaimers: at first use after this page shipped, and whenever the wording
 * changes. Agreeing returns them to where they were going.
 */
export default function LegalReview({ documents, accept }: LegalReviewProps) {
    const { t } = useTranslation();

    return (
        <main
            data-audience="investor"
            className="rz-surface min-h-svh bg-rz-page"
        >
            <Head title={t('legal.review.head_title')} />
            <div className="mx-auto w-full max-w-[520px] px-4 pt-[calc(env(safe-area-inset-top)+20px)] pb-12 sm:pt-12">
                <LogoLockup
                    title={t('common.brand.name')}
                    className="h-7 w-auto text-rz-accent-lockup"
                />
                <h1 className="mt-7 text-2xl font-semibold text-rz-ink">
                    {t('legal.review.title')}
                </h1>
                <p className="mt-1.5 text-sm leading-[1.55] text-rz-secondary">
                    {t('legal.review.lead')}
                </p>

                <Form
                    action={accept}
                    method="post"
                    disableWhileProcessing
                    className="mt-6 flex flex-col gap-5 rounded-2xl border border-rz-border bg-rz-surface p-5 text-rz-ink"
                >
                    {({ processing, errors }) => (
                        <>
                            <LegalConsentFields
                                documents={documents}
                                errors={errors}
                            />
                            <button
                                type="submit"
                                disabled={processing}
                                aria-busy={processing || undefined}
                                className="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-rz-accent-fill text-[15px] font-semibold text-white disabled:opacity-70"
                            >
                                {processing && <Spinner />}
                                {processing
                                    ? t('legal.review.processing')
                                    : t('legal.review.submit')}
                            </button>
                        </>
                    )}
                </Form>

                <p className="mt-4 text-center text-sm text-rz-secondary">
                    <Link href={logout()} as="button" className="underline">
                        {t('suite.sign_out')}
                    </Link>
                </p>
            </div>
        </main>
    );
}
