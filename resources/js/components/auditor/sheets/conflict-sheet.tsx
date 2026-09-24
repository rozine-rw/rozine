import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { BottomSheet } from '@/components/auditor/sheets/bottom-sheet';
import {
    FORM_PRIMARY,
    NOTE_FIELD,
    SECONDARY_BUTTON,
} from '@/components/auditor/sheets/reason-sheet';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type { ConflictKind } from '@/types/auditor';

export const CONFLICT_KINDS: ConflictKind[] = [
    'financial_interest',
    'role_tie',
    'family_or_business',
    'other',
];

type ConflictSheetProps = {
    business: string;
    fileId: string;
    action: RouteAction;
    onClose: () => void;
};

/**
 * Declaring an interest (design L390–414, AC-08). The design declares on one tap; this asks which
 * kind of interest and confirms first, because the declaration goes on the permanent record. The
 * server re-dispatches the file and reports what it did.
 */
export function ConflictSheet({
    business,
    fileId,
    action,
    onClose,
}: ConflictSheetProps) {
    const { t } = useTranslation();
    const form = useForm<{
        file_id: string;
        kind: ConflictKind | null;
        note: string;
    }>({
        file_id: fileId,
        kind: null,
        note: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(action.url, { preserveScroll: true });
    };

    return (
        <BottomSheet
            title={t('auditor.conflict.sheet_title', { business })}
            lead={t('auditor.conflict.body')}
            onClose={onClose}
        >
            <form onSubmit={submit} noValidate className="mt-3.5">
                <fieldset>
                    <legend className="text-[10px] font-bold tracking-[.05em] text-rz-secondary uppercase">
                        {t('auditor.conflict.kind_label')}
                    </legend>
                    <div className="mt-[7px] flex flex-wrap gap-[7px]">
                        {CONFLICT_KINDS.map((kind) => {
                            const on = form.data.kind === kind;

                            return (
                                <button
                                    key={kind}
                                    type="button"
                                    role="radio"
                                    aria-checked={on}
                                    onClick={() => form.setData('kind', kind)}
                                    className={cn(
                                        'rounded-[10px] border px-[11px] py-[7px] text-[12px] font-semibold',
                                        on
                                            ? 'border-[#f0dcb8] bg-rz-accent-soft text-rz-ink dark:border-[rgba(240,160,96,.4)]'
                                            : 'border-rz-border bg-[#f8fafc] text-rz-secondary dark:bg-rz-surface-sunken',
                                    )}
                                >
                                    {t(`auditor.conflict.kind.${kind}`)}
                                </button>
                            );
                        })}
                    </div>
                    <FieldError id="auditor-conflict-kind-error">
                        {form.errors.kind}
                    </FieldError>
                </fieldset>
                <label
                    htmlFor="auditor-conflict-note"
                    className="mt-3.5 mb-[5px] block text-[10px] font-bold tracking-[.04em] text-rz-slate uppercase"
                >
                    {t('auditor.conflict.note_label')}
                </label>
                <textarea
                    id="auditor-conflict-note"
                    value={form.data.note}
                    onChange={(event) =>
                        form.setData('note', event.target.value)
                    }
                    placeholder={t('auditor.conflict.note_placeholder')}
                    className={cn(NOTE_FIELD, 'min-h-[72px]')}
                />
                <FieldError id="auditor-conflict-note-error">
                    {form.errors.note}
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
                        disabled={form.processing || form.data.kind === null}
                        className={cn(FORM_PRIMARY, 'flex-[2]')}
                    >
                        {t('auditor.conflict.submit')}
                    </button>
                </div>
            </form>
        </BottomSheet>
    );
}
