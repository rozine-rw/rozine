import { router } from '@inertiajs/react';
import { useEffect, useEffectEvent, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import {
    OPERATION_CODES,
    refusalRefreshes,
} from '@/components/business/apply/operation-outcome';
import { OutcomeBanner } from '@/components/business/apply/outcome-banner';
import { StepBusiness } from '@/components/business/apply/step-business';
import { StepRaise } from '@/components/business/apply/step-raise';
import type { RaiseFields } from '@/components/business/apply/step-raise';
import { StepReview } from '@/components/business/apply/step-review';
import type { ReviewFields } from '@/components/business/apply/step-review';
import { Submitted } from '@/components/business/apply/submitted';
import { useApplicationCommand } from '@/components/business/apply/use-application-command';
import { WizardCta } from '@/components/business/apply/wizard-cta';
import { WizardFrame } from '@/components/business/apply/wizard-frame';
import { BusinessShell } from '@/components/business/business-shell';
import { BlankBody, HomeBody } from '@/components/business/home/home-body';
import { ErrorBanner } from '@/components/rozine/form';
import { useToast } from '@/components/rozine/toast';
import { useTranslation } from '@/hooks/use-translation';
import type {
    ApplicationCommand,
    ApplicationCommandName,
    ApplicationSnapshot,
    ApplyStep,
    BusinessApplyProps,
    TermMonths,
} from '@/types/business';

const PAGE = { business: 1, raise: 2, review: 3 } as const;

/** The fields each step marks inline; a server error for any other field shows as a banner. */
const SHOWN_FIELDS: Record<ApplyStep, string[]> = {
    business: [],
    raise: ['title', 'target', 'term_months', 'story'],
    review: ['disclosures', 'signature_name'],
    submitted: [],
};

/** The documents every signature accepts, each by its own version and hash. */
const AGREEMENTS = ['terms', 'privacy'] as const;

/** How long typing settles before the draft is saved and evaluated. */
const QUOTE_DEBOUNCE_MS = 450;

/** The props a confirmed command does not carry; they are refreshed once its snapshot is shown. */
const REMAINING_PROPS = [
    'allowed_actions',
    'identity_context_revision',
    'server_time',
    'evidence',
];

type Snapshot = Omit<ApplicationSnapshot, 'next'> & {
    allowed_actions: string[];
};

/** The original request an evaluation answers: the target and term, exactly as typed. */
const requestKey = (
    target: string | null | undefined,
    term: TermMonths | null,
): string => `${target ?? ''}|${term ?? ''}`;

/** The request a save or evaluation carried. */
const payloadKey = (payload: ApplicationCommand['payload']): string =>
    requestKey(
        payload.target as string,
        payload.term_months as TermMonths | null,
    );

/**
 * Apply for a raise (MVP-BUSINESS-SCR-02, business-application-v1). The draft, the quote and the
 * signatures are the server's: this page collects input, sends the JSON commands the server
 * allows, and shows exactly what comes back. A page load only reads the current quote; a new one
 * exists only after the draft is saved and `application.evaluate` runs. A changed request is a new
 * evaluation and a new decision — the page never shrinks a refused request on its own.
 */
export default function BusinessApply(props: BusinessApplyProps) {
    const { t } = useTranslation();
    const { toast, show } = useToast();
    const { step, links, actions } = props;
    const [snapshot, setSnapshot] = useState<Snapshot | null>(null);
    const application = snapshot?.application ?? props.application;
    const quote = snapshot === null ? props.quote : snapshot.quote;
    const acceptance = snapshot?.acceptance ?? props.acceptance;
    const submission =
        snapshot === null ? props.submission : snapshot.submission;
    const allowedActions: string[] =
        snapshot?.allowed_actions ?? props.allowed_actions;
    const readyQuote = quote?.status === 'ready' ? quote : null;
    const canSave = allowedActions.includes('application.save');
    const canEvaluate =
        canSave && allowedActions.includes('application.evaluate');
    /*
     * Missing legal text is an empty document or disclosure list (no submit capability either):
     * nothing to sign, so no Sign button even if a submit were allowed, and no stand-in text. A
     * business signs the Terms and the Privacy Note, so both must be there to read.
     */
    const agreementAvailable =
        acceptance.disclosures.length > 0 &&
        AGREEMENTS.every((kind) =>
            acceptance.documents.some((document) => document.kind === kind),
        );
    const canSign =
        agreementAvailable && allowedActions.includes('application.submit');

    const revision = useRef(props.application.revision);
    const savedKey = useRef(
        requestKey(
            props.application.target?.amount,
            props.application.term_months,
        ),
    );

    useEffect(() => {
        revision.current = props.application.revision;
    }, [props.application.revision]);

    const [raise, setRaise] = useState<RaiseFields>({
        title: application.title,
        target: application.target?.amount ?? '',
        term_months: application.term_months,
        use_of_funds: application.use_of_funds,
        story: application.story,
    });
    const [review, setReview] = useState<ReviewFields>({
        disclosures: [],
        terms: false,
        privacy: false,
        accept_offer: false,
        signature_name: '',
    });
    const key = requestKey(raise.target, raise.term_months);
    const [evaluatedKey, setEvaluatedKey] = useState<string | null>(() => {
        if (props.quote === null) {
            return null;
        }

        /* A refusal answers the saved draft; a ready quote names its own request. */
        return props.quote.status === 'ready'
            ? requestKey(
                  props.quote.requested_principal.amount,
                  props.quote.term_months,
              )
            : requestKey(
                  props.application.target?.amount,
                  props.application.term_months,
              );
    });
    const [haltedKey, setHaltedKey] = useState<string | null>(null);

    const context = () => ({
        identity_context_revision: props.identity_context_revision,
        expected_revision: revision.current,
        request_id: crypto.randomUUID(),
    });

    const command = useApplicationCommand({
        actions,
        lookup: links.operation,
        identityContextRevision: props.identity_context_revision,
        preview: props.preview_outcome,
        onCompleted: (sent, resource, { recovered }) => {
            const { data } = resource;

            if (data === null) {
                router.reload();

                return;
            }

            /*
             * A completion the lookup recovered is a historical receipt: a later revision may have
             * overtaken it, so its snapshot is never shown as current. The page follows `next` (a
             * fresh read) or reloads all of its facts instead.
             */
            if (recovered) {
                setSnapshot(null);
            } else {
                revision.current = data.application.revision;
                setSnapshot({
                    application: data.application,
                    quote: data.quote,
                    acceptance: data.acceptance,
                    submission: data.submission,
                    allowed_actions: resource.allowed_actions,
                });
            }

            if (sent.name === 'evaluate') {
                /* A new quote is a new decision: its offer is accepted afresh. */
                setReview((fields) => ({ ...fields, accept_offer: false }));
            }

            if (sent.name !== 'submit') {
                const sentKey = payloadKey(sent.payload);

                savedKey.current = sentKey;

                if (sent.name === 'evaluate') {
                    setEvaluatedKey(sentKey);
                }
            }

            if (sent.advance || resource.code === OPERATION_CODES.submitted) {
                router.visit(data.next);

                return;
            }

            if (recovered) {
                router.reload();

                return;
            }

            router.reload({ only: REMAINING_PROPS });
        },
        onRefused: (sent, code, status) => {
            if (sent.name !== 'submit') {
                setHaltedKey(payloadKey(sent.payload));
            }

            if (code === 'QUOTE_STALE') {
                setReview((fields) => ({ ...fields, accept_offer: false }));
            }

            if (code === 'MANDATE_STALE') {
                setReview((fields) => ({ ...fields, signature_name: '' }));
            }

            if (code === 'DOCUMENT_VERSION_STALE') {
                setReview((fields) => ({
                    ...fields,
                    disclosures: [],
                    terms: false,
                    privacy: false,
                }));
            }

            if (refusalRefreshes(code, status)) {
                setSnapshot(null);
                router.reload();
            }
        },
    });

    /** The last command sent, so the busy label names what is running. */
    const [sending, setSending] = useState<ApplicationCommandName | null>(null);

    const send = (next: ApplicationCommand) => {
        if (command.send(next)) {
            setSending(next.name);
        }
    };

    /**
     * The draft as typed. Only a Continue names a resume pointer (`step`), asking to advance; the
     * server validates the move and answers with `next`. An autosave (`pointer` null) sends no
     * `step`, so the server keeps the stored pointer — a Raise reached by Back never moves it.
     */
    const draftPayload = (pointer: 'raise' | 'review' | null) => ({
        title: raise.title,
        /* An empty target is no request yet: the draft holds null, never an empty amount. */
        target: raise.target === '' ? null : raise.target,
        term_months: raise.term_months,
        use_of_funds: raise.use_of_funds,
        story: raise.story,
        ...(pointer === null ? {} : { step: pointer }),
        ...context(),
    });

    /* Save the typed request, then evaluate the saved draft — one command at a time. */
    const startEvaluation = useEffectEvent((draftSaved: boolean) => {
        if (draftSaved) {
            send({
                name: 'evaluate',
                advance: false,
                payload: {
                    target: raise.target,
                    term_months: raise.term_months,
                    evidence_version: props.evidence.version,
                    ...context(),
                },
            });

            return;
        }

        send({
            name: 'save',
            advance: false,
            payload: draftPayload(null),
        });
    });

    const armed =
        step === 'raise' &&
        canEvaluate &&
        !command.busy &&
        !command.unresolved &&
        raise.target !== '' &&
        raise.term_months !== null &&
        key !== evaluatedKey &&
        key !== haltedKey;

    useEffect(() => {
        if (!armed) {
            return;
        }

        const draftSaved = key === savedKey.current;
        const timer = window.setTimeout(
            () => startEvaluation(draftSaved),
            draftSaved ? 0 : QUOTE_DEBOUNCE_MS,
        );

        return () => window.clearTimeout(timer);
    }, [armed, key]);

    const remind = () => show(t('business.apply.incomplete'));
    const idle = !command.busy && !command.unresolved;

    const raiseReady =
        raise.title.trim() !== '' &&
        readyQuote !== null &&
        evaluatedKey === key &&
        idle;

    const reviewReady =
        readyQuote !== null &&
        review.accept_offer &&
        acceptance.disclosures.every((disclosure) =>
            review.disclosures.includes(disclosure.key),
        ) &&
        review.terms &&
        review.privacy &&
        review.signature_name.trim().length >= 2 &&
        idle;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (step === 'business') {
            send({
                name: 'save',
                advance: true,
                payload: draftPayload('raise'),
            });

            return;
        }

        if (step === 'raise') {
            if (!raiseReady) {
                if (haltedKey === key) {
                    setHaltedKey(null);
                }

                remind();

                return;
            }

            send({
                name: 'save',
                advance: true,
                payload: draftPayload('review'),
            });

            return;
        }

        if (!reviewReady) {
            remind();

            return;
        }

        send({
            name: 'submit',
            advance: false,
            payload: {
                ...context(),
                quote_id: readyQuote.quote_id,
                quote_revision: readyQuote.quote_revision,
                evidence_version: readyQuote.evidence_version,
                mandate_version: acceptance.mandate_version,
                accepted_principal: readyQuote.principal.amount,
                disclosures: acceptance.disclosures.map(
                    ({ key: disclosure, version, sha256 }) => ({
                        key: disclosure,
                        version,
                        sha256,
                    }),
                ),
                documents: acceptance.documents.map(
                    ({ kind, version, sha256 }) => ({ kind, version, sha256 }),
                ),
                terms: review.terms,
                privacy: review.privacy,
                signature_name: review.signature_name.trim(),
            },
        });
    };

    /**
     * A lower accepted principal for a ready offer: the saved request is evaluated again with
     * `accepted_principal` (or without it, for the full offer), and the new quote is accepted
     * afresh. It is never offered on a refusal.
     */
    const reducible =
        step === 'review' && readyQuote !== null && canSign && canEvaluate;
    const reduce = reducible
        ? {
              busy: !idle,
              error: command.errors.accepted_principal,
              onReduce: (acceptedPrincipal: string | null) => {
                  send({
                      name: 'evaluate',
                      advance: false,
                      payload: {
                          target: application.target?.amount,
                          term_months: application.term_months,
                          evidence_version: props.evidence.version,
                          ...(acceptedPrincipal === null
                              ? {}
                              : { accepted_principal: acceptedPrincipal }),
                          ...context(),
                      },
                  });
              },
          }
        : null;

    const offersCommand = {
        business: props.evidence.eligibility.status === 'eligible' && canSave,
        raise: canSave,
        review: canSign,
        submitted: false,
    }[step];

    const cta = offersCommand ? (
        <WizardCta
            ready={
                {
                    business: idle,
                    raise: raiseReady,
                    review: reviewReady,
                    submitted: false,
                }[step]
            }
            busy={command.busy}
            form="business-apply"
        >
            {step === 'review'
                ? command.busy
                    ? sending === 'evaluate'
                        ? t('business.apply.recalculating')
                        : t('business.apply.submitting')
                    : t('business.apply.submit')
                : command.busy
                  ? t('business.apply.saving')
                  : t('business.apply.continue')}
        </WizardCta>
    ) : null;

    const errors = command.errors;
    const shownFields = reducible
        ? [...SHOWN_FIELDS[step], 'accepted_principal']
        : SHOWN_FIELDS[step];
    /* A field error this step has no field for (e.g. `step`) still reaches the business. */
    const unshownError = Object.entries(errors).find(
        ([field, message]) =>
            message !== undefined && !shownFields.includes(field),
    )?.[1];

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
            {command.notice && (
                <OutcomeBanner
                    notice={command.notice}
                    busy={command.busy}
                    onCheckAgain={command.checkAgain}
                    onRetry={command.retry}
                />
            )}
            {step === 'raise' && !canSave && (
                <p
                    role="status"
                    className="mb-4 rounded-2xl border border-[#dbe7ff] bg-rz-surface p-4 text-xs leading-[1.55] text-rz-secondary dark:border-rz-border"
                >
                    {t('business.apply.view_only')}
                </p>
            )}
            {unshownError && (
                <div className="mb-4">
                    <ErrorBanner>{unshownError}</ErrorBanner>
                </div>
            )}
            <form id="business-apply" onSubmit={submit} noValidate>
                {step === 'business' && (
                    <StepBusiness evidence={props.evidence} />
                )}
                {step === 'raise' && (
                    <StepRaise
                        fields={raise}
                        errors={errors}
                        quote={quote}
                        quoting={command.busy || key !== evaluatedKey}
                        onChange={(field, value) =>
                            setRaise((fields) => ({
                                ...fields,
                                [field]: value,
                            }))
                        }
                    />
                )}
                {step === 'review' && (
                    <StepReview
                        acceptance={acceptance}
                        quote={readyQuote}
                        canSign={canSign}
                        agreementAvailable={agreementAvailable}
                        reduce={reduce}
                        fields={review}
                        errors={errors}
                        onChange={(field, value) =>
                            setReview((fields) => ({
                                ...fields,
                                [field]: value,
                            }))
                        }
                    />
                )}
            </form>
            {step === 'submitted' && submission !== null && (
                <Submitted submission={submission} home={links.close} />
            )}
        </WizardFrame>
    );

    return (
        <BusinessShell
            title={t('business.apply.head_title')}
            tab="home"
            links={props.shell_links}
            showTabBar={false}
        >
            {props.home === null ? (
                <BlankBody overlay={{ column: 'right', content: sheet }} />
            ) : (
                <HomeBody
                    {...props.home}
                    backdrop
                    overlay={{ column: 'right', content: sheet }}
                />
            )}
            {toast}
        </BusinessShell>
    );
}
