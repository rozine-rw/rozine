import { useState } from 'react';
import type { ReactNode } from 'react';
import { PendingReview } from '@/components/business/apply/pending-review';
import { StepHeading } from '@/components/business/apply/step-heading';
import { InstalmentSchedule } from '@/components/business/apply/step-raise';
import { FieldError } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatAmount, formatDayMonth, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type {
    AcceptanceDocument,
    AcceptanceSigner,
    ApplicationAcceptance,
    ApplicationQuote,
} from '@/types/business';

export type ReviewFields = {
    /** The keys of the server's disclosures the business has acknowledged. */
    disclosures: string[];
    terms: boolean;
    privacy: boolean;
    /** Explicit acceptance of the exact offer shown: the server's principal and schedule. */
    accept_offer: boolean;
    /** An attestation only: the verified account, not this name, decides who signs. */
    signature_name: string;
};

type StepReviewProps = {
    acceptance: ApplicationAcceptance;
    quote: Extract<ApplicationQuote, { status: 'ready' }> | null;
    /** Whether `allowed_actions` lets the current person sign now, with the legal text to sign. */
    canSign: boolean;
    /**
     * Whether the server published the legal text to sign: the documents and the disclosures.
     * Without it there is nothing to sign, and nothing is put in its place.
     */
    agreementAvailable: boolean;
    /** The application under review that blocks signing this one, when there is one. */
    pendingReview: RouteLink | null;
    /** Present when the current person may evaluate a lower amount for this ready offer. */
    reduce: ReduceControl | null;
    fields: ReviewFields;
    errors: Partial<Record<string, string>>;
    onChange: <K extends keyof ReviewFields>(
        field: K,
        value: ReviewFields[K],
    ) => void;
};

function Tick({ on, tone }: { on: boolean; tone: 'blue' | 'green' }) {
    return (
        <span
            aria-hidden
            className={cn(
                'flex size-[22px] shrink-0 items-center justify-center rounded-[10px] border-2 text-[13px] text-white',
                !on && 'border-rz-border bg-transparent',
                on && tone === 'blue' && 'border-[#1a5cff] bg-rz-investor',
                on &&
                    tone === 'green' &&
                    'border-rz-accent-fill bg-rz-accent-fill',
            )}
        >
            {on ? '✓' : ''}
        </span>
    );
}

function SectionLabel({ children }: { children: ReactNode }) {
    return (
        <p className="mt-[22px] text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase first:mt-5">
            {children}
        </p>
    );
}

/**
 * The document behind each "Read" link (design L2646–2672): the approved summary, then the complete
 * text its hash binds, as escaped plain text with its line breaks.
 */
function DocumentSheet({
    document,
    onClose,
}: {
    document: AcceptanceDocument;
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const title = t(`business.apply.review.document.${document.kind}`);

    return (
        <div className="fixed inset-0 z-[70] flex items-end justify-center lg:absolute">
            <button
                type="button"
                aria-label={t('business.apply.close')}
                onClick={onClose}
                className="absolute inset-0 animate-[rz-scrim_.22s_ease] bg-[rgba(8,14,28,.5)] backdrop-blur-[3px]"
            />
            <div
                role="dialog"
                aria-modal="true"
                aria-label={title}
                className="relative flex max-h-[88%] w-full animate-[rz-sheetup_.34s_cubic-bezier(.16,1,.3,1)_both] flex-col overflow-hidden rounded-t-3xl bg-rz-surface shadow-[0_-18px_50px_-16px_rgba(20,45,95,.4)]"
            >
                <div className="shrink-0 border-b border-[#eef2f9] px-5 pt-3.5 pb-3 dark:border-rz-divider">
                    <div className="flex justify-center">
                        <span className="h-1 w-[38px] rounded-[3px] bg-[#e2e7f0] dark:bg-rz-border" />
                    </div>
                    <div className="mt-3 flex items-center justify-between gap-3">
                        <h3 className="text-lg font-bold text-rz-ink">
                            {title}
                        </h3>
                        <button
                            type="button"
                            onClick={onClose}
                            aria-label={t('business.apply.close')}
                            className="flex size-8 shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-[#f3f6fc] text-base text-rz-slate dark:bg-rz-surface-muted"
                        >
                            ✕
                        </button>
                    </div>
                </div>
                <div className="rz-scroll flex-1 overflow-y-auto px-5 pt-[18px] pb-6">
                    <p className="text-[11.5px] leading-normal text-rz-secondary">
                        {t('business.apply.review.document_intro', {
                            version: document.version,
                        })}
                    </p>
                    <h4 className="mt-4 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                        {t('business.apply.review.document_summary')}
                    </h4>
                    <div className="mt-2.5 flex flex-col gap-4">
                        {document.summary.map((clause) => (
                            <div key={clause.heading}>
                                <p className="text-[13.5px] font-bold text-rz-ink">
                                    {clause.heading}
                                </p>
                                <p className="mt-[5px] text-[12.5px] leading-[1.6] text-rz-secondary">
                                    {clause.body}
                                </p>
                            </div>
                        ))}
                    </div>
                    <h4 className="mt-6 border-t border-[#eef2f9] pt-4 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase dark:border-rz-divider">
                        {t('business.apply.review.document_full_text')}
                    </h4>
                    <p className="mt-2.5 text-[12.5px] leading-[1.6] whitespace-pre-line text-rz-secondary">
                        {document.body}
                    </p>
                </div>
                <div className="shrink-0 border-t border-[#eef2f9] px-5 pt-3.5 pb-[22px] dark:border-rz-divider">
                    <button
                        type="button"
                        onClick={onClose}
                        className="h-[50px] w-full rounded-xl bg-rz-accent-fill text-[15px] font-semibold text-white"
                    >
                        {t('business.apply.review.got_it')}
                    </button>
                </div>
            </div>
        </div>
    );
}

/** Asking for less than a ready offer: a new evaluation, never a way round a refusal. */
export type ReduceControl = {
    busy: boolean;
    error: string | undefined;
    /** Evaluates again at this principal, or at the full offer when null. */
    onReduce: (acceptedPrincipal: string | null) => void;
};

/**
 * "Take a smaller amount": the business names a lower principal and the server evaluates it again
 * against the saved request (business-application-v1, `accepted_principal`). The server decides
 * whether the amount is acceptable; the page only collects it.
 */
function ReduceOffer({
    quote,
    control,
}: {
    quote: Extract<ApplicationQuote, { status: 'ready' }>;
    control: ReduceControl;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(control.error !== undefined);
    const [amount, setAmount] = useState('');
    const recalculate = () => {
        if (amount !== '') {
            control.onReduce(amount);
        }
    };

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="mt-3 p-0 text-[12.5px] font-bold text-rz-accent-app-text"
            >
                {t('business.apply.review.reduce.open')}
            </button>
        );
    }

    return (
        <div className="mt-3.5 border-t border-[#eef2f9] pt-3.5 dark:border-rz-divider">
            <label
                htmlFor="accepted_principal"
                className="mb-1.5 block text-xs font-semibold text-rz-label uppercase"
            >
                {t('business.apply.review.reduce.label')}
            </label>
            <input
                id="accepted_principal"
                inputMode="numeric"
                value={
                    amount === ''
                        ? ''
                        : formatAmount({ currency: 'RWF', amount })
                }
                onChange={(event) =>
                    setAmount(
                        event.target.value
                            .replace(/\D/gu, '')
                            .replace(/^0+/u, ''),
                    )
                }
                onKeyDown={(event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        recalculate();
                    }
                }}
                aria-invalid={control.error !== undefined || undefined}
                aria-describedby="accepted-principal-help"
                className="w-full rounded-xl border border-rz-field-border bg-rz-field px-3.5 py-[13px] text-[15px] font-semibold text-rz-ink outline-none focus:border-rz-focus-border"
            />
            <p
                id="accepted-principal-help"
                className="mt-1.5 text-[11px] leading-[1.45] text-rz-secondary"
            >
                {t('business.apply.review.reduce.help', {
                    unit: formatRwf(quote.unit_price),
                })}
            </p>
            <FieldError id="accepted-principal-error">
                {control.error}
            </FieldError>
            <div className="mt-3 flex items-center gap-4">
                <button
                    type="button"
                    disabled={amount === '' || control.busy}
                    onClick={recalculate}
                    className="h-10 rounded-xl bg-rz-accent-fill px-4 text-[13px] font-semibold text-white disabled:opacity-50"
                >
                    {t('business.apply.review.reduce.submit')}
                </button>
                <button
                    type="button"
                    onClick={() => {
                        setAmount('');
                        setOpen(false);
                    }}
                    className="p-0 text-[12.5px] font-semibold text-rz-slate"
                >
                    {t('business.apply.review.reduce.cancel')}
                </button>
            </div>
        </div>
    );
}

/**
 * The offer being signed (business-application-v1 points 4 and 6): the server's resized and
 * quantized principal with its full schedule, accepted explicitly — never recomputed after signing.
 */
function OfferCard({
    quote,
    acceptable,
    on,
    onToggle,
    reduce,
}: {
    quote: Extract<ApplicationQuote, { status: 'ready' }>;
    /** Only a person who may sign now is asked to accept the offer. */
    acceptable: boolean;
    on: boolean;
    onToggle: () => void;
    reduce: ReduceControl | null;
}) {
    const { t } = useTranslation();
    const reduced = quote.principal.amount !== quote.offered_principal.amount;

    return (
        <div
            className={cn(
                'mt-[11px] rounded-2xl border bg-rz-surface p-4',
                on
                    ? 'border-[#cfe9d8] dark:border-rz-accent-fill'
                    : 'border-rz-border',
            )}
        >
            <div className="flex flex-col gap-3">
                <div className="flex items-baseline justify-between gap-3">
                    <span className="text-[13.5px] text-rz-slate">
                        {t('business.apply.raise.you_receive')}
                    </span>
                    <span className="text-base font-bold whitespace-nowrap text-rz-ink">
                        {formatRwf(quote.principal)}
                    </span>
                </div>
                <div className="flex items-baseline justify-between gap-3">
                    <span className="text-[13.5px] text-rz-slate">
                        {t('business.apply.raise.term')}
                    </span>
                    <span className="text-[13px] font-semibold whitespace-nowrap text-rz-ink">
                        {t('business.apply.raise.term_months', {
                            months: quote.term_months,
                        })}{' '}
                        ·{' '}
                        {t('business.apply.review.flat_rate', {
                            rate: quote.rate_pct,
                        })}
                    </span>
                </div>
                <div className="flex items-baseline justify-between gap-2.5 whitespace-nowrap">
                    <span className="text-sm font-bold text-rz-ink">
                        {t('business.apply.raise.you_repay')}
                    </span>
                    <span className="text-lg font-extrabold tracking-[-.3px] text-rz-ink">
                        {formatRwf(quote.total)}
                    </span>
                </div>
                <InstalmentSchedule schedule={quote.schedule} />
            </div>
            {reduced && (
                <p className="mt-3 text-xs leading-normal text-rz-secondary">
                    {t('business.apply.review.reduced', {
                        principal: formatRwf(quote.principal),
                        offered: formatRwf(quote.offered_principal),
                    })}
                    {reduce && (
                        <>
                            {' '}
                            <button
                                type="button"
                                disabled={reduce.busy}
                                onClick={() => reduce.onReduce(null)}
                                className="p-0 font-bold text-rz-accent-app-text disabled:opacity-50"
                            >
                                {t('business.apply.review.use_full')}
                            </button>
                        </>
                    )}
                </p>
            )}
            {reduce && <ReduceOffer quote={quote} control={reduce} />}
            {acceptable && (
                <button
                    type="button"
                    role="checkbox"
                    aria-checked={on}
                    onClick={onToggle}
                    className="mt-3.5 flex w-full items-start gap-3 border-t border-[#eef2f9] pt-3.5 text-left dark:border-rz-divider"
                >
                    <Tick on={on} tone="green" />
                    <span className="text-[13px] leading-normal text-rz-ink">
                        {t('business.apply.review.accept_offer', {
                            principal: formatRwf(quote.principal),
                            months: quote.term_months,
                        })}
                    </span>
                </button>
            )}
        </div>
    );
}

/** The mandate's signers and where each signature stands (point 6). */
function Signers({
    signers,
    required,
}: {
    signers: AcceptanceSigner[];
    required: number;
}) {
    const { t, locale } = useTranslation();

    return (
        <>
            <p className="mt-[11px] text-[12.5px] text-rz-secondary">
                {t('business.apply.review.signatures_required', {
                    count: required,
                })}
            </p>
            <ul
                aria-label={t('business.apply.review.signatories')}
                className="mt-2 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface"
            >
                {signers.map((signer) => (
                    <li
                        key={signer.party_id}
                        className="flex items-center justify-between gap-3 border-b border-[#eef2f9] px-[15px] py-[13px] last:border-b-0 dark:border-rz-divider"
                    >
                        <div className="min-w-0">
                            <p className="text-[13.5px] font-semibold text-rz-ink">
                                {signer.name}
                            </p>
                            <p className="text-[11.5px] text-rz-secondary">
                                {signer.role}
                            </p>
                        </div>
                        <span
                            className={cn(
                                'inline-flex shrink-0 items-center gap-[5px] rounded-[10px] px-[9px] py-1 text-[11px] font-semibold',
                                signer.state === 'signed'
                                    ? 'bg-rz-accent-soft text-rz-accent-app-text'
                                    : 'bg-[rgba(105,116,138,.10)] text-rz-secondary',
                            )}
                        >
                            {signer.state === 'signed' &&
                            signer.signed_at !== null
                                ? t('business.apply.review.signed_on', {
                                      date: formatDayMonth(
                                          signer.signed_at,
                                          locale,
                                      ),
                                  })
                                : t(
                                      `business.apply.review.signer.${signer.state}`,
                                  )}
                        </span>
                    </li>
                ))}
            </ul>
        </>
    );
}

/**
 * Step 3 — "Review & sign" (design L559–601). The business accepts the exact offer, the server's
 * disclosures and the document versions shown here; the server records each acceptance against
 * their versions and hashes. The typed name is an attestation; the verified account is the signer.
 */
export function StepReview({
    acceptance,
    quote,
    canSign,
    agreementAvailable,
    pendingReview,
    reduce,
    fields,
    errors,
    onChange,
}: StepReviewProps) {
    const { t } = useTranslation();
    const [reading, setReading] = useState<AcceptanceDocument | null>(null);
    const pending = acceptance.signers
        .filter((signer) => signer.state === 'pending')
        .map((signer) => signer.name);

    const agreement = (kind: 'terms' | 'privacy') => {
        const document = acceptance.documents.find(
            (candidate) => candidate.kind === kind,
        );
        const on = fields[kind];

        return (
            <div
                className={cn(
                    'flex items-center gap-3 rounded-2xl border bg-rz-surface p-3.5',
                    on
                        ? 'border-[#cfe9d8] dark:border-rz-accent-fill'
                        : 'border-rz-border',
                )}
            >
                <button
                    type="button"
                    role="checkbox"
                    aria-checked={on}
                    aria-labelledby={`agree-${kind}`}
                    onClick={() => onChange(kind, !on)}
                    className="shrink-0 p-0"
                >
                    <Tick on={on} tone="green" />
                </button>
                <p
                    id={`agree-${kind}`}
                    className="flex-1 text-[13px] leading-[1.45] text-rz-ink"
                >
                    {t(`business.apply.review.agree_${kind}_prefix`)}{' '}
                    <b>{t(`business.apply.review.document.${kind}`)}</b>.
                </p>
                {document && (
                    <button
                        type="button"
                        onClick={() => setReading(document)}
                        className="shrink-0 p-0 text-[12.5px] font-bold text-rz-accent-app-text"
                    >
                        {t('business.apply.review.read')}
                    </button>
                )}
            </div>
        );
    };

    return (
        <>
            <StepHeading
                step={3}
                title={t('business.apply.review.title')}
                subtitle={t('business.apply.review.subtitle')}
            />

            <SectionLabel>{t('business.apply.review.your_offer')}</SectionLabel>
            {quote ? (
                <OfferCard
                    quote={quote}
                    acceptable={canSign}
                    on={fields.accept_offer}
                    onToggle={() =>
                        onChange('accept_offer', !fields.accept_offer)
                    }
                    reduce={reduce}
                />
            ) : (
                <p className="mt-[11px] text-[12.5px] text-rz-secondary">
                    {t('business.apply.review.no_offer')}
                </p>
            )}

            {canSign && (
                <>
                    <SectionLabel>
                        {t('business.apply.review.risk_disclosures')}
                    </SectionLabel>
                    <div className="mt-[11px] flex flex-col gap-2.5">
                        {acceptance.disclosures.map((disclosure) => {
                            const on = fields.disclosures.includes(
                                disclosure.key,
                            );

                            return (
                                <button
                                    key={disclosure.key}
                                    type="button"
                                    role="checkbox"
                                    aria-checked={on}
                                    onClick={() =>
                                        onChange(
                                            'disclosures',
                                            on
                                                ? fields.disclosures.filter(
                                                      (item) =>
                                                          item !==
                                                          disclosure.key,
                                                  )
                                                : [
                                                      ...fields.disclosures,
                                                      disclosure.key,
                                                  ],
                                        )
                                    }
                                    className={cn(
                                        'flex items-start gap-3 rounded-2xl border bg-rz-surface p-3.5 text-left',
                                        on
                                            ? 'border-[#cfe9d8] dark:border-rz-accent-fill'
                                            : 'border-rz-border',
                                    )}
                                >
                                    <Tick on={on} tone="blue" />
                                    <span className="text-[13px] leading-normal text-rz-ink">
                                        {disclosure.text}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    <FieldError id="disclosures-error">
                        {errors.disclosures}
                    </FieldError>

                    <SectionLabel>
                        {t('business.apply.review.agreements')}
                    </SectionLabel>
                    <div className="mt-[11px] flex flex-col gap-2.5">
                        {agreement('terms')}
                        {agreement('privacy')}
                    </div>
                </>
            )}

            <SectionLabel>
                {t('business.apply.review.signatories')}
            </SectionLabel>
            <Signers
                signers={acceptance.signers}
                required={acceptance.required_signatures}
            />

            {!agreementAvailable ? (
                <div
                    role="status"
                    className="mt-3 flex items-start gap-3 rounded-2xl border border-[#dbe7ff] bg-rz-surface p-4 dark:border-rz-border"
                >
                    <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                        <Icon name="lock" />
                    </span>
                    <p className="flex-1 text-xs leading-[1.55] text-rz-secondary">
                        <b className="block text-[13px] text-rz-ink">
                            {t('business.apply.review.agreement_unavailable')}
                        </b>
                        {t('business.apply.review.agreement_unavailable_body')}
                    </p>
                </div>
            ) : canSign ? (
                <>
                    <SectionLabel>
                        {t('business.apply.review.sign_submit')}
                    </SectionLabel>
                    <div className="mt-[11px]">
                        <label
                            htmlFor="signature_name"
                            className="mb-1.5 block text-xs font-semibold text-rz-label uppercase"
                        >
                            {t('business.apply.review.full_name')}
                        </label>
                        <input
                            id="signature_name"
                            value={fields.signature_name}
                            onChange={(event) =>
                                onChange('signature_name', event.target.value)
                            }
                            autoComplete="name"
                            placeholder={t(
                                'business.apply.review.full_name_placeholder',
                            )}
                            aria-invalid={
                                errors.signature_name !== undefined || undefined
                            }
                            aria-describedby="signature-help"
                            className="w-full rounded-xl border border-rz-field-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border"
                        />
                        <p
                            id="signature-help"
                            className="mt-1.5 text-[11px] leading-[1.45] text-rz-secondary"
                        >
                            {t('business.apply.review.attestation')}
                        </p>
                        <FieldError id="signature-error">
                            {errors.signature_name}
                        </FieldError>
                    </div>
                    <div className="mt-3">
                        <p className="mb-1.5 text-xs font-semibold text-rz-label uppercase">
                            {t('business.apply.review.signature')}
                        </p>
                        <div className="flex h-[84px] items-center justify-center rounded-2xl border-[1.5px] border-[#dbe3f0] bg-[#f3f6fc] dark:border-rz-border dark:bg-rz-surface-sunken">
                            <span
                                aria-hidden
                                className="font-['Brush_Script_MT',cursive] text-[25px] text-rz-accent-app-text italic"
                            >
                                {fields.signature_name.trim() === ''
                                    ? t('business.apply.review.sign_here')
                                    : fields.signature_name}
                            </span>
                        </div>
                    </div>
                </>
            ) : pendingReview !== null ? (
                <PendingReview link={pendingReview} className="mt-3" />
            ) : (
                <div
                    role="status"
                    className="mt-3 flex items-start gap-3 rounded-2xl border border-[#dbe7ff] bg-rz-surface p-4 dark:border-rz-border"
                >
                    <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                        <Icon
                            name={pending.length > 0 ? 'hourglass' : 'lock'}
                        />
                    </span>
                    <p className="flex-1 text-xs leading-[1.55] text-rz-secondary">
                        {pending.length > 0
                            ? t('business.apply.review.waiting', {
                                  names: pending.join(', '),
                              })
                            : t('business.apply.review.cannot_sign')}
                    </p>
                </div>
            )}

            <div className="mt-3.5 rounded-2xl border border-[#dbe7ff] bg-rz-surface p-4 dark:border-rz-border">
                <div className="flex items-center gap-[7px]">
                    <span className="text-[13px]">
                        <Icon name="lock" />
                    </span>
                    <span className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('business.apply.review.due_on_approval')}
                    </span>
                </div>
                <div className="mt-[11px] flex items-start justify-between gap-3">
                    <span className="text-[13px] leading-[1.4] text-rz-slate">
                        {t('business.apply.review.application_fee')}
                        <br />
                        <span className="text-[11px] text-rz-secondary">
                            {t('business.apply.review.application_fee_when')}
                        </span>
                    </span>
                    <span className="text-[17px] font-bold whitespace-nowrap text-rz-ink">
                        {formatRwf(acceptance.fee_on_approval)}
                    </span>
                </div>
                <p className="mt-[11px] border-t border-[#dbe7ff] pt-[11px] text-[11px] leading-[1.55] text-rz-secondary dark:border-rz-divider">
                    {t('business.apply.review.fee_note')}
                </p>
            </div>
            {agreementAvailable && (
                <p className="mt-3 text-[11.5px] text-rz-secondary">
                    {t('business.apply.review.binding')}
                </p>
            )}
            <div className="h-24 lg:hidden" />

            {reading && (
                <DocumentSheet
                    document={reading}
                    onClose={() => setReading(null)}
                />
            )}
        </>
    );
}
