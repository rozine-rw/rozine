import { useState } from 'react';
import {
    AuditorCommandNotice,
    useAuditorCommands,
} from '@/components/auditor/commands';
import { BottomSheet } from '@/components/auditor/sheets/bottom-sheet';
import {
    ChoiceChips,
    FORM_PRIMARY,
    NOTE_FIELD,
    SECONDARY_BUTTON,
    SHEET_LABEL,
} from '@/components/auditor/sheets/reason-sheet';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    AuditorAllowedAction,
    ConflictKind,
    RecordRef,
} from '@/types/auditor';

export const CONFLICT_KINDS: ConflictKind[] = [
    'financial_interest',
    'role_tie',
    'family_or_business',
    'other',
];

type ConflictSheetProps = {
    business: string;
    /** The assignment the declaration targets, at the revision the page read. */
    assignment: RecordRef;
    action: RouteAction;
    /** The record's own `allowed_actions` on a list page. */
    scope?: AuditorAllowedAction[];
    onClose: () => void;
};

/**
 * Declaring an interest (design L390–414, AC-08; auditor-filing-v1 point 3). The design declares
 * on one tap; this asks which of the four kinds of interest it is and for a factual explanation,
 * because the declaration goes on the permanent record. The server decides whether it blocks the
 * work and where the assignment goes. The declaration runs in its own command lane; if the
 * assignment turns out stale, the page refreshes and the typed kind and explanation stay here for
 * the partner to send again — never discarded, never resent on their own.
 */
export function ConflictSheet({
    business,
    assignment,
    action,
    scope,
    onClose,
}: ConflictSheetProps) {
    const { t } = useTranslation();
    const center = useAuditorCommands();
    const [kind, setKind] = useState<ConflictKind | null>(null);
    const [note, setNote] = useState('');
    /* Its own lane: only an unresolved declaration holds another one back. */
    const lane = center.conflict;
    /* The scope is read afresh each render: a refresh can take the declaration away. */
    const permitted =
        scope?.includes('conflict.declare') ??
        center.allowed('conflict.declare');
    const ready = permitted && kind !== null && note.trim() !== '' && lane.idle;

    /* The button is enabled only once a kind is chosen, so `kind` is set here. */
    const submit = () =>
        center.send(
            {
                name: 'conflict.declare',
                business,
                route: action,
                scope,
                payload: {
                    assignment_id: assignment.id,
                    expected_revision: assignment.revision,
                    kind: kind!,
                    note: note.trim(),
                },
            },
            { onCompleted: onClose },
        );

    return (
        <BottomSheet
            title={t('auditor.conflict.sheet_title', { business })}
            lead={t('auditor.conflict.body')}
            onClose={onClose}
        >
            <AuditorCommandNotice
                placement="sheet"
                lane="conflict"
                shown={['kind', 'note']}
                className="mt-3.5"
            />
            {!permitted && (
                <p
                    role="note"
                    className="mt-3.5 rounded-xl border border-rz-border px-3.5 py-3 text-[11.5px] leading-[1.5] text-rz-secondary"
                >
                    {t('auditor.conflict.not_allowed')}
                </p>
            )}
            <div className="mt-3.5">
                <ChoiceChips
                    legend={t('auditor.conflict.kind_label')}
                    choices={CONFLICT_KINDS.map((code) => ({
                        code,
                        label: t(`auditor.conflict.kind.${code}`),
                    }))}
                    value={kind}
                    onChange={setKind}
                    error={lane.errors.kind}
                    errorId="auditor-conflict-kind-error"
                />
                <label
                    htmlFor="auditor-conflict-note"
                    className={cn(SHEET_LABEL, 'mt-3.5')}
                >
                    {t('auditor.conflict.note_label')}
                </label>
                <textarea
                    id="auditor-conflict-note"
                    value={note}
                    onChange={(event) => setNote(event.target.value)}
                    placeholder={t('auditor.conflict.note_placeholder')}
                    aria-invalid={lane.errors.note ? true : undefined}
                    className={cn(NOTE_FIELD, 'min-h-[72px]')}
                />
                <FieldError id="auditor-conflict-note-error">
                    {lane.errors.note}
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
                        aria-busy={lane.busy || undefined}
                        className={cn(FORM_PRIMARY, 'flex-[2]')}
                    >
                        {t('auditor.conflict.submit')}
                    </button>
                </div>
            </div>
        </BottomSheet>
    );
}
