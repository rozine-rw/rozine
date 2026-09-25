import { useState } from 'react';
import type { FormEvent } from 'react';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/** The contract's limit on the note retained with a Party's signature. */
export const NOTE_LIMIT = 100;

/**
 * The current person's co-signature: an explicit, unticked acceptance of the sealed statements
 * and findings, and an optional recap kept with the signature. The verified account is the
 * signer, so no typed name is asked for. Nothing here edits the sealed report.
 */
export function CosignForm({
    initialNote,
    busy,
    locked,
    errors,
    onCosign,
}: {
    initialNote: string;
    busy: boolean;
    /** A command is out or unresolved, or a fresh read is pending: nothing new is sent. */
    locked: boolean;
    errors: Partial<Record<string, string>>;
    onCosign: (note: string) => void;
}) {
    const { t } = useTranslation();
    const [accepted, setAccepted] = useState(false);
    const [note, setNote] = useState(initialNote.slice(0, NOTE_LIMIT));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        onCosign(note.trim());
    };

    return (
        <form onSubmit={submit} noValidate className="mt-[11px]">
            <button
                type="button"
                role="checkbox"
                aria-checked={accepted}
                onClick={() => setAccepted((value) => !value)}
                className={cn(
                    'flex w-full items-start gap-3 rounded-2xl border bg-rz-surface p-3.5 text-left',
                    accepted
                        ? 'border-[#cfe9d8] dark:border-rz-accent-fill'
                        : 'border-rz-border',
                )}
            >
                <span
                    aria-hidden
                    className={cn(
                        'flex size-[22px] shrink-0 items-center justify-center rounded-[10px] border-2 text-[13px] text-white',
                        accepted
                            ? 'border-rz-accent-fill bg-rz-accent-fill'
                            : 'border-rz-border bg-transparent',
                    )}
                >
                    {accepted ? '✓' : ''}
                </span>
                <span className="text-[13px] leading-normal text-rz-ink">
                    {t('business.audit_cosign.yours.accept')}
                </span>
            </button>
            <FieldError id="accepted-error">{errors.accepted}</FieldError>

            <div className="mt-3.5">
                <div className="mb-1.5 flex items-baseline justify-between gap-3">
                    <label
                        htmlFor="cosign-note"
                        className="text-xs font-semibold text-rz-label uppercase"
                    >
                        {t('business.audit_cosign.yours.note')}
                    </label>
                    <span
                        id="cosign-note-count"
                        aria-live="polite"
                        className="text-[11px] font-semibold text-rz-secondary tabular-nums"
                    >
                        {t('business.audit_cosign.count', {
                            count: note.length,
                            limit: NOTE_LIMIT,
                        })}
                    </span>
                </div>
                <textarea
                    id="cosign-note"
                    rows={2}
                    maxLength={NOTE_LIMIT}
                    value={note}
                    onChange={(event) =>
                        setNote(event.target.value.slice(0, NOTE_LIMIT))
                    }
                    aria-invalid={errors.note !== undefined || undefined}
                    aria-describedby="cosign-note-help cosign-note-count"
                    className="w-full resize-none rounded-xl border border-rz-field-border bg-rz-field px-3.5 py-[11px] text-sm text-rz-ink outline-none focus:border-rz-focus-border"
                />
                <p
                    id="cosign-note-help"
                    className="mt-1.5 text-[11px] leading-[1.45] text-rz-secondary"
                >
                    {t('business.audit_cosign.yours.note_help')}
                </p>
                <FieldError id="note-error">{errors.note}</FieldError>
            </div>

            <p className="mt-3 text-[11.5px] leading-normal text-rz-secondary">
                {t('business.audit_cosign.yours.identity')}
            </p>
            <button
                type="submit"
                disabled={!accepted || locked}
                aria-busy={busy || undefined}
                className="mt-3.5 h-[50px] w-full rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white disabled:bg-rz-page disabled:text-rz-secondary"
            >
                {busy
                    ? t('business.audit_cosign.yours.submitting')
                    : t('business.audit_cosign.yours.submit')}
            </button>
        </form>
    );
}
