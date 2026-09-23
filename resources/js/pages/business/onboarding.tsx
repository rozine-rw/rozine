import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { StepBank } from '@/components/business/onboarding/step-bank';
import { StepConfirm } from '@/components/business/onboarding/step-confirm';
import { StepDocuments } from '@/components/business/onboarding/step-documents';
import { StepFinish } from '@/components/business/onboarding/step-finish';
import { ErrorBanner, PrimaryButton } from '@/components/rozine/form';
import { IconGradients } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import type { BusinessOnboardingProps, OnboardingStep } from '@/types/business';

const STEPS: OnboardingStep[] = ['confirm', 'documents', 'bank', 'finish'];

/**
 * Business registration onboarding (MVP-BUSINESS-SCR-09, design L1569–1715): confirm the RDB
 * record, file the certificate and showcase, link the payout account, then finish. Each step is its
 * own server state, so a refresh or a return visit resumes where the business left off.
 */
export default function BusinessOnboarding({
    step,
    registry,
    industry,
    industries,
    documents,
    banks,
    bank_account,
    signatories,
    signatories_required,
    contact_masked,
    links,
    actions,
}: BusinessOnboardingProps) {
    const { t } = useTranslation();
    const index = STEPS.indexOf(step);
    const percent = `${Math.round((index / 3) * 100)}%`;
    const form = useForm({ industry });
    const [blocked, setBlocked] = useState<string | null>(null);
    const current = industries.find(
        (option) => option.value === form.data.industry,
    );
    const error = blocked ?? Object.values(form.errors)[0];

    const next = () => {
        if (step === 'confirm') {
            form.post(actions.confirm.url, { preserveScroll: true });
        } else if (step === 'finish') {
            form.post(actions.finish.url);
        } else if (
            step === 'documents' &&
            documents.certificate.status !== 'verified'
        ) {
            setBlocked(t('business.onboarding.error.certificate'));
        } else if (step === 'bank' && bank_account === null) {
            setBlocked(t('business.onboarding.error.bank'));
        } else {
            router.visit(links.next);
        }
    };

    return (
        <div
            data-audience="business"
            className="rz-surface min-h-svh bg-rz-surface"
        >
            <Head title={t(`business.onboarding.${step}.title`)} />
            <IconGradients app="business" />
            <div className="mx-auto w-full max-w-[412px] px-5 pt-[calc(env(safe-area-inset-top)+56px)] pb-10 lg:pt-[60px]">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.back}
                        aria-label={t('business.onboarding.back')}
                        className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <div
                        role="progressbar"
                        aria-label={t('business.onboarding.progress')}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={Math.round((index / 3) * 100)}
                        className="h-1.5 flex-1 overflow-hidden rounded-[4px] bg-rz-border"
                    >
                        <div
                            className="h-full bg-rz-accent-fill transition-[width] duration-400"
                            style={{ width: percent }}
                        />
                    </div>
                    <span
                        aria-hidden
                        className="text-[12.5px] font-semibold text-rz-secondary"
                    >
                        {percent}
                    </span>
                </div>
                <p className="mt-2 text-[12.5px] font-semibold tracking-[.05em] text-rz-accent-app-text">
                    {t(`business.onboarding.step_label.${step}`)}
                </p>

                {step === 'confirm' && (
                    <StepConfirm
                        registry={registry}
                        industry={form.data.industry}
                        industries={industries}
                        onIndustry={(value) => form.setData('industry', value)}
                    />
                )}
                {step === 'documents' && (
                    <StepDocuments documents={documents} actions={actions} />
                )}
                {step === 'bank' && (
                    <StepBank
                        companyName={registry.name}
                        banks={banks}
                        account={bank_account}
                        signatories={signatories}
                        required={signatories_required}
                        action={actions.bank}
                    />
                )}
                {step === 'finish' && (
                    <StepFinish
                        registry={registry}
                        industry={current?.label ?? industry}
                        contact={contact_masked}
                        account={bank_account}
                        finishing={form.processing}
                    />
                )}

                {error !== undefined && (
                    <div className="mt-4">
                        <ErrorBanner>{error}</ErrorBanner>
                    </div>
                )}
                <div className="mt-6">
                    <PrimaryButton
                        type="button"
                        busy={form.processing}
                        onClick={next}
                    >
                        {step === 'finish' && form.processing
                            ? t('business.onboarding.finish.setting_up')
                            : t(`business.onboarding.cta.${step}`)}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    );
}
