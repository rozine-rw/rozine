import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { StepBusiness } from '@/components/business/apply/step-business';
import { StepRaise } from '@/components/business/apply/step-raise';
import type { RaiseFields } from '@/components/business/apply/step-raise';
import {
    DISCLOSURES,
    StepReview,
} from '@/components/business/apply/step-review';
import type { ReviewFields } from '@/components/business/apply/step-review';
import { Submitted } from '@/components/business/apply/submitted';
import { WizardCta } from '@/components/business/apply/wizard-cta';
import { WizardFrame } from '@/components/business/apply/wizard-frame';
import { BusinessShell } from '@/components/business/business-shell';
import { HomeBody } from '@/components/business/home/home-body';
import { useToast } from '@/components/rozine/toast';
import { useTranslation } from '@/hooks/use-translation';
import type { BusinessApplyProps } from '@/types/business';

const PAGE = { business: 1, raise: 2, review: 3 } as const;

/** How long typing settles before the server is asked for a fresh quote. */
const QUOTE_DEBOUNCE_MS = 450;

/**
 * Apply for a raise (MVP-BUSINESS-SCR-02). The draft, the quote and the submission are the
 * server's; this page collects input, asks for quotes and shows exactly what comes back.
 */
export default function BusinessApply(props: BusinessApplyProps) {
    const { t } = useTranslation();
    const { toast, show } = useToast();
    const { application, step, quote, links, actions } = props;
    const [quoting, setQuoting] = useState(false);
    const firstQuote = useRef(true);

    const raise = useForm<RaiseFields>({
        title: application.title,
        target: application.target?.amount ?? '',
        term_months: application.term_months,
        use_of_funds: application.use_of_funds,
        story: application.story,
    });

    const review = useForm<ReviewFields>({
        disclosures: [],
        terms: false,
        privacy: false,
        signature_name: '',
    });

    const { target, term_months: termMonths } = raise.data;

    useEffect(() => {
        if (step !== 'raise') {
            return;
        }

        if (firstQuote.current) {
            firstQuote.current = false;

            return;
        }

        const timer = window.setTimeout(() => {
            router.reload({
                only: ['quote'],
                data: { target, term_months: termMonths },
                onStart: () => setQuoting(true),
                onFinish: () => setQuoting(false),
            });
        }, QUOTE_DEBOUNCE_MS);

        return () => window.clearTimeout(timer);
    }, [step, target, termMonths]);

    const remind = () => show(t('business.apply.incomplete'));

    const raiseReady =
        raise.data.title.trim() !== '' &&
        raise.data.target !== '' &&
        raise.data.term_months !== null &&
        quote?.status === 'ready' &&
        !quoting;

    const reviewReady =
        DISCLOSURES.every((item) => review.data.disclosures.includes(item)) &&
        review.data.terms &&
        review.data.privacy &&
        review.data.signature_name.trim().length >= 2;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (step === 'business') {
            if (links.next !== null) {
                router.visit(links.next);
            }

            return;
        }

        if (step === 'raise') {
            if (!raiseReady) {
                remind();

                return;
            }

            raise.transform((data) => ({
                ...data,
                step,
                revision: application.revision,
            }));
            raise.post(actions.save.url, { preserveScroll: true });

            return;
        }

        if (!reviewReady) {
            remind();

            return;
        }

        review.transform((data) => ({
            ...data,
            revision: application.revision,
            documents: props.acceptance.documents.map(({ kind, version }) => ({
                kind,
                version,
            })),
        }));
        review.post(actions.submit.url, { preserveScroll: true });
    };

    const cta =
        step === 'submitted' ? null : (
            <WizardCta
                ready={
                    step === 'business' ||
                    (step === 'raise' ? raiseReady : reviewReady)
                }
                busy={raise.processing || review.processing}
                form="business-apply"
            >
                {step === 'review'
                    ? review.processing
                        ? t('business.apply.submitting')
                        : t('business.apply.submit')
                    : raise.processing
                      ? t('business.apply.saving')
                      : t('business.apply.continue')}
            </WizardCta>
        );

    const sheet = (
        <WizardFrame
            progress={
                step === 'submitted' ? null : { page: PAGE[step], total: 3 }
            }
            back={links.back}
            close={links.close}
            dismissible={step === 'business'}
            cta={cta}
        >
            <form id="business-apply" onSubmit={submit} noValidate>
                {step === 'business' && (
                    <StepBusiness evidence={props.evidence} />
                )}
                {step === 'raise' && (
                    <StepRaise
                        fields={raise.data}
                        errors={raise.errors}
                        quote={quote}
                        quoting={quoting}
                        onChange={(field, value) =>
                            raise.setData((data) => ({
                                ...data,
                                [field]: value,
                            }))
                        }
                    />
                )}
                {step === 'review' && (
                    <StepReview
                        acceptance={props.acceptance}
                        fields={review.data}
                        errors={review.errors}
                        onChange={(field, value) =>
                            review.setData((data) => ({
                                ...data,
                                [field]: value,
                            }))
                        }
                    />
                )}
            </form>
            {step === 'submitted' && props.submission !== null && (
                <Submitted submission={props.submission} home={links.close} />
            )}
        </WizardFrame>
    );

    return (
        <BusinessShell
            title={t('business.apply.head_title')}
            tab="home"
            links={props.home.links}
            showTabBar={false}
        >
            <HomeBody
                {...props.home}
                backdrop
                overlay={{ column: 'right', content: sheet }}
            />
            {toast}
        </BusinessShell>
    );
}
