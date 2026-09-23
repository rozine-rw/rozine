import { useState } from 'react';
import type { ReactNode } from 'react';
import { StepHeading } from '@/components/business/apply/step-heading';
import { FieldError } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AcceptanceDocument,
    ApplicationAcceptance,
} from '@/types/business';

export const DISCLOSURES = [
    'accuracy',
    'obligations',
    'statements',
    'repayment',
] as const;

export type Disclosure = (typeof DISCLOSURES)[number];

export type ReviewFields = {
    disclosures: Disclosure[];
    terms: boolean;
    privacy: boolean;
    signature_name: string;
};

type StepReviewProps = {
    acceptance: ApplicationAcceptance;
    fields: ReviewFields;
    errors: Partial<Record<keyof ReviewFields, string>>;
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

/** The summary popup behind each "Read" link (design L2646–2672). */
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
                    <div className="mt-4 flex flex-col gap-4">
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

/**
 * Step 3 — "Review & sign" (design L559–601). Acceptance is recorded by the server against the
 * exact document versions shown here; the typed name is the signature it records.
 */
export function StepReview({
    acceptance,
    fields,
    errors,
    onChange,
}: StepReviewProps) {
    const { t } = useTranslation();
    const [reading, setReading] = useState<AcceptanceDocument | null>(null);

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

            <SectionLabel>
                {t('business.apply.review.risk_disclosures')}
            </SectionLabel>
            <div className="mt-[11px] flex flex-col gap-2.5">
                {DISCLOSURES.map((disclosure) => {
                    const on = fields.disclosures.includes(disclosure);

                    return (
                        <button
                            key={disclosure}
                            type="button"
                            role="checkbox"
                            aria-checked={on}
                            onClick={() =>
                                onChange(
                                    'disclosures',
                                    on
                                        ? fields.disclosures.filter(
                                              (item) => item !== disclosure,
                                          )
                                        : [...fields.disclosures, disclosure],
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
                                {t(
                                    `business.apply.review.disclosure.${disclosure}`,
                                )}
                            </span>
                        </button>
                    );
                })}
            </div>
            <FieldError id="disclosures-error">{errors.disclosures}</FieldError>

            <SectionLabel>{t('business.apply.review.agreements')}</SectionLabel>
            <div className="mt-[11px] flex flex-col gap-2.5">
                {agreement('terms')}
                {agreement('privacy')}
            </div>

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
                    className="w-full rounded-xl border border-rz-field-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border"
                />
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
            <p className="mt-3 text-[11.5px] text-rz-secondary">
                {t('business.apply.review.binding')}
            </p>
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
