import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { BottomSheet } from '@/components/auditor/sheets/bottom-sheet';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';

type ReasonSheetProps = {
    title: string;
    lead: string;
    label: string;
    placeholder: string;
    submitLabel: string;
    action: RouteAction;
    /** Extra facts the command carries, such as the aggregate revision it was read at. */
    payload?: Record<string, string | number>;
    destructive?: boolean;
    onClose: () => void;
};

/** Secondary button: `#f1f4f9` / `#46526b`, 42px (design L823). */
export const SECONDARY_BUTTON =
    'h-[42px] rounded-xl bg-rz-surface-muted px-4 text-[13px] font-bold text-[#46526b] dark:text-rz-secondary';

/** Primary button in a form row (design L824). */
export const FORM_PRIMARY =
    'h-[42px] rounded-xl bg-rz-accent-fill px-4 text-[13px] font-bold text-white disabled:cursor-not-allowed disabled:bg-rz-disabled disabled:text-rz-secondary';

/** The design's note field (L1419): white, 12px radius, 13.5px at 1.5. */
export const NOTE_FIELD =
    'w-full resize-none rounded-xl border border-rz-border bg-rz-surface px-[13px] py-3 text-[13.5px] leading-[1.5] text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border';

/**
 * A command that needs the partner's reason in their own words: declining a job, sending a
 * monthly report back, or rejecting it. The server records the reason with the command and
 * validates it; nothing here decides the outcome.
 */
export function ReasonSheet({
    title,
    lead,
    label,
    placeholder,
    submitLabel,
    action,
    payload = {},
    destructive = false,
    onClose,
}: ReasonSheetProps) {
    const { t } = useTranslation();
    const form = useForm({ reason: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, ...payload }));
        form.post(action.url, { preserveScroll: true });
    };

    return (
        <BottomSheet title={title} lead={lead} onClose={onClose}>
            <form onSubmit={submit} noValidate className="mt-3.5">
                <label
                    htmlFor="auditor-reason"
                    className="mb-[5px] block text-[10px] font-bold tracking-[.04em] text-rz-slate uppercase"
                >
                    {label}
                </label>
                <textarea
                    id="auditor-reason"
                    value={form.data.reason}
                    onChange={(event) =>
                        form.setData('reason', event.target.value)
                    }
                    placeholder={placeholder}
                    aria-invalid={form.errors.reason ? true : undefined}
                    aria-describedby={
                        form.errors.reason ? 'auditor-reason-error' : undefined
                    }
                    className={cn(NOTE_FIELD, 'min-h-[84px]')}
                />
                <FieldError id="auditor-reason-error">
                    {form.errors.reason}
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
                        type="submit"
                        disabled={
                            form.processing || form.data.reason.trim() === ''
                        }
                        className={cn(
                            FORM_PRIMARY,
                            'flex-[2]',
                            destructive && 'bg-[#c0392b] dark:bg-[#b3383c]',
                        )}
                    >
                        {submitLabel}
                    </button>
                </div>
            </form>
        </BottomSheet>
    );
}
