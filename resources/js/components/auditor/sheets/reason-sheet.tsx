import { useState } from 'react';
import {
    AuditorCommandNotice,
    useAuditorCommands,
} from '@/components/auditor/commands';
import { BottomSheet } from '@/components/auditor/sheets/bottom-sheet';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { ServerOption } from '@/types/auditor';

/** Secondary button: `#f1f4f9` / `#46526b`, 42px (design L823). */
export const SECONDARY_BUTTON =
    'h-[42px] rounded-xl bg-rz-surface-muted px-4 text-[13px] font-bold text-[#46526b] dark:text-rz-secondary';

/** Primary button in a form row (design L824). */
export const FORM_PRIMARY =
    'h-[42px] rounded-xl bg-rz-accent-fill px-4 text-[13px] font-bold text-white disabled:cursor-not-allowed disabled:bg-rz-disabled disabled:text-rz-secondary';

/** The design's note field (L1419): white, 12px radius, 13.5px at 1.5. */
export const NOTE_FIELD =
    'w-full resize-none rounded-xl border border-rz-border bg-rz-surface px-[13px] py-3 text-[13.5px] leading-[1.5] text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border';

/** A sheet form field's label: 10px capitals (design L1419). */
export const SHEET_LABEL =
    'mb-[5px] block text-[10px] font-bold tracking-[.04em] text-rz-slate uppercase';

/** One choice among several, as the design's chips (L396–404). */
export function ChoiceChips<Code extends string>({
    legend,
    choices,
    value,
    onChange,
    error,
    errorId,
}: {
    legend: string;
    choices: { code: Code; label: string }[];
    value: Code | null;
    onChange: (code: Code) => void;
    error?: string;
    errorId: string;
}) {
    return (
        <fieldset>
            <legend className="text-[10px] font-bold tracking-[.05em] text-rz-secondary uppercase">
                {legend}
            </legend>
            <div
                role="radiogroup"
                aria-label={legend}
                className="mt-[7px] flex flex-wrap gap-[7px]"
            >
                {choices.map((choice) => {
                    const on = value === choice.code;

                    return (
                        <button
                            key={choice.code}
                            type="button"
                            role="radio"
                            aria-checked={on}
                            onClick={() => onChange(choice.code)}
                            className={cn(
                                'rounded-[10px] border px-[11px] py-[7px] text-left text-[12px] font-semibold',
                                on
                                    ? 'border-[#f0dcb8] bg-rz-accent-soft text-rz-ink dark:border-[rgba(240,160,96,.4)]'
                                    : 'border-rz-border bg-[#f8fafc] text-rz-secondary dark:bg-rz-surface-sunken',
                            )}
                        >
                            {choice.label}
                        </button>
                    );
                })}
            </div>
            <FieldError id={errorId}>{error}</FieldError>
        </fieldset>
    );
}

type ReasonSheetProps = {
    title: string;
    lead: string;
    placeholder: string;
    submitLabel: string;
    /** The server's labelled reasons; each says whether it needs an explanation. */
    options: ServerOption[];
    initialReason?: string | null;
    destructive?: boolean;
    /** Every reason needs an explanation, whatever the chosen option says. */
    explanationRequired?: boolean;
    /** The longest explanation the server records; shown as a counter. */
    maxLength?: number;
    onSubmit: (fields: { reason_code: string; reason: string }) => void;
    onClose: () => void;
};

/**
 * A command that needs a coded reason from the server's list and, where that reason asks for it,
 * a factual explanation in the partner's own words: declining a job, requesting changes to a
 * monthly filing, or rejecting it. The server records and validates both; nothing here decides
 * the outcome, and no reason is a verdict on the business.
 */
export function ReasonSheet({
    title,
    lead,
    placeholder,
    submitLabel,
    options,
    initialReason = null,
    destructive = false,
    explanationRequired = false,
    maxLength,
    onSubmit,
    onClose,
}: ReasonSheetProps) {
    const { t } = useTranslation();
    const center = useAuditorCommands();
    const [code, setCode] = useState<string | null>(initialReason);
    const [reason, setReason] = useState('');
    const chosen = options.find((option) => option.code === code);
    const explain =
        explanationRequired || (chosen?.requires_explanation ?? false);
    const ready =
        chosen !== undefined &&
        (!explain || reason.trim() !== '') &&
        center.idle;

    /*
     * The button is enabled only once a reason is chosen, so `code` is set here. It reads the
     * chosen code, never `chosen.code`: the React Compiler memoises this closure on the property
     * it reads, which it would evaluate while nothing is chosen yet and crash the sheet.
     */
    const submit = () =>
        onSubmit({ reason_code: code as string, reason: reason.trim() });

    return (
        <BottomSheet title={title} lead={lead} onClose={onClose}>
            <AuditorCommandNotice
                placement="sheet"
                shown={['reason_code', 'reason']}
                className="mt-3.5"
            />
            <div className="mt-3.5">
                <ChoiceChips
                    legend={t('auditor.reason.label')}
                    choices={options}
                    value={code}
                    onChange={setCode}
                    error={center.errors.reason_code}
                    errorId="auditor-reason-code-error"
                />
                <label
                    htmlFor="auditor-reason"
                    className={cn(SHEET_LABEL, 'mt-3.5')}
                >
                    {explain
                        ? t('auditor.reason.explanation_required')
                        : t('auditor.reason.explanation_optional')}
                </label>
                <textarea
                    id="auditor-reason"
                    value={reason}
                    maxLength={maxLength}
                    onChange={(event) => setReason(event.target.value)}
                    placeholder={placeholder}
                    aria-invalid={center.errors.reason ? true : undefined}
                    aria-describedby={
                        center.errors.reason
                            ? 'auditor-reason-error'
                            : undefined
                    }
                    className={cn(NOTE_FIELD, 'min-h-[84px]')}
                />
                {maxLength !== undefined && (
                    <p className="mt-1 text-right text-[10.5px] text-rz-secondary">
                        {t('auditor.reason.count', {
                            count: reason.length,
                            max: maxLength,
                        })}
                    </p>
                )}
                <FieldError id="auditor-reason-error">
                    {center.errors.reason}
                </FieldError>
                <div className="mt-3 flex gap-[9px]">
                    <button
                        type="button"
                        onClick={onClose}
                        className={cn(SECONDARY_BUTTON, 'flex-1')}
                    >
                        {t('auditor.sheet.cancel')}
                    </button>
                    <button
                        type="button"
                        onClick={submit}
                        disabled={!ready}
                        aria-busy={center.busy || undefined}
                        className={cn(
                            FORM_PRIMARY,
                            'flex-[2]',
                            destructive && 'bg-[#c0392b] dark:bg-[#b3383c]',
                        )}
                    >
                        {submitLabel}
                    </button>
                </div>
            </div>
        </BottomSheet>
    );
}
