import { useState } from 'react';
import type { ReactNode } from 'react';
import { useAuditorCommands } from '@/components/auditor/commands';
import { ConflictSheet } from '@/components/auditor/sheets/conflict-sheet';
import { ReasonSheet } from '@/components/auditor/sheets/reason-sheet';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteAction } from '@/types';
import type {
    AuditorAllowedAction,
    DeclineReason,
    RecordRef,
    ServerOption,
} from '@/types/auditor';

type Sheet = 'decline' | 'conflict' | null;

const QUIET_ACTION =
    'text-[11.5px] font-bold text-rz-secondary disabled:cursor-not-allowed disabled:opacity-60';

/**
 * Decline and conflict for one assignment, as two quiet text actions and the sheets they open.
 * Each is offered only when the scoped `allowed_actions` lists it; a conflict stays declarable
 * mid-procedure (MVP-AUDITOR-AC-08). Decline takes one of the server's labelled reasons.
 */
export function useJobCommands({
    assignment,
    business,
    allowed,
    conflict,
    decline,
    initialSheet = null,
}: {
    assignment: RecordRef;
    business: string;
    allowed: (action: AuditorAllowedAction) => boolean;
    conflict: RouteAction;
    /** Where a decline goes and the server's reasons, or null where declining is not offered. */
    decline: {
        route: RouteAction;
        options: ServerOption<DeclineReason>[];
    } | null;
    /** A synthetic preview may open the decline sheet with a reason chosen. */
    initialSheet?: { sheet: 'decline'; reason: string | null } | null;
}): { links: ReactNode; conflictButton: ReactNode; sheet: ReactNode } {
    const { t } = useTranslation();
    const center = useAuditorCommands();
    const [open, setOpen] = useState<Sheet>(initialSheet?.sheet ?? null);
    const close = () => setOpen(null);
    const declining = allowed('assignment.decline') ? decline : null;
    const canConflict = allowed('conflict.declare');

    const conflictButton = canConflict ? (
        <button
            type="button"
            onClick={() => setOpen('conflict')}
            disabled={!center.idle}
            className={QUIET_ACTION}
        >
            {t('auditor.jobs.declare_conflict')}
        </button>
    ) : null;

    const links =
        declining !== null || canConflict ? (
            <div className="flex items-center justify-between gap-3">
                {declining !== null ? (
                    <button
                        type="button"
                        onClick={() => setOpen('decline')}
                        disabled={!center.idle}
                        className={QUIET_ACTION}
                    >
                        {t('auditor.jobs.decline')}
                    </button>
                ) : (
                    <span />
                )}
                {conflictButton}
            </div>
        ) : null;

    let sheet: ReactNode = null;

    if (open === 'decline' && declining !== null) {
        sheet = (
            <ReasonSheet
                title={t('auditor.decline.title', { business })}
                lead={t('auditor.decline.lead')}
                placeholder={t('auditor.decline.placeholder')}
                submitLabel={t('auditor.decline.submit')}
                options={declining.options}
                initialReason={initialSheet?.reason}
                onSubmit={(fields) =>
                    center.send(
                        {
                            name: 'assignment.decline',
                            business,
                            route: declining.route,
                            payload: {
                                assignment_id: assignment.id,
                                expected_revision: assignment.revision,
                                ...fields,
                            },
                        },
                        { onCompleted: close },
                    )
                }
                onClose={close}
            />
        );
    } else if (open === 'conflict' && canConflict) {
        sheet = (
            <ConflictSheet
                business={business}
                assignment={assignment}
                action={conflict}
                onClose={close}
            />
        );
    }

    return { links, conflictButton, sheet };
}
