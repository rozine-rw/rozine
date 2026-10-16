import { useState } from 'react';
import type { ReactNode } from 'react';
import type { StepContext } from '@/components/auditor/audit/parts';
import { useAuditorCommands } from '@/components/auditor/commands';
import { ReasonSheet } from '@/components/auditor/sheets/reason-sheet';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteAction } from '@/types';
import type { AuditorPreviewOutcome, ReasonOptions } from '@/types/auditor';

type Open = 'request_changes' | 'reject' | null;

/** The longest factual explanation the server records for a return (#96). */
export const RETURN_REASON_MAX = 2000;

type ReturnControlsOptions = {
    /** The server's labelled reasons, or null where the report cannot be returned. */
    options: ReasonOptions | null;
    context: StepContext;
    business: string;
    /** Null while the delivered stage does not enable that command: it then has no button. */
    actions: {
        request_changes: RouteAction | null;
        reject: RouteAction | null;
    };
    preview?: AuditorPreviewOutcome;
};

/**
 * Returning a monthly filing (delivery 2, #96): request changes from the business, or reject the
 * filing as unverifiable, each with one of the server's coded reasons and a factual explanation —
 * about verifiability and procedure, never a credit verdict. Neither waits for the evidence to be
 * complete or for the step to be able to continue, since missing originals are a reason to return
 * a filing; each is offered only where `allowed_actions` lists it and its route is present.
 */
export function useReturnControls({
    options,
    context,
    business,
    actions,
    preview,
}: ReturnControlsOptions): { buttons: ReactNode; sheet: ReactNode } {
    const { t } = useTranslation();
    const center = useAuditorCommands();
    const [open, setOpen] = useState<Open>(() =>
        preview?.kind === 'sheet' && preview.sheet !== 'decline'
            ? preview.sheet
            : null,
    );
    const initialReason = preview?.kind === 'sheet' ? preview.reason : null;
    const requestChangesRoute =
        options !== null && center.allowed('audit.request_changes')
            ? actions.request_changes
            : null;
    const rejectRoute =
        options !== null && center.allowed('audit.reject')
            ? actions.reject
            : null;
    const close = () => setOpen(null);

    const send = (
        name: 'audit.request_changes' | 'audit.reject',
        route: RouteAction,
        fields: { reason_code: string; reason: string },
    ) =>
        center.send(
            {
                name,
                business,
                route,
                payload: {
                    audit_id: context.auditId,
                    expected_revision: context.revision,
                    ...fields,
                },
            },
            { onCompleted: close },
        );

    const buttons = (requestChangesRoute !== null || rejectRoute !== null) && (
        <div className="flex gap-[9px]">
            {requestChangesRoute !== null && (
                <button
                    type="button"
                    disabled={!center.idle}
                    onClick={() => setOpen('request_changes')}
                    className="h-11 flex-1 rounded-xl border border-rz-border bg-rz-surface text-[13px] font-bold text-rz-slate disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {t('auditor.seal.suggest')}
                </button>
            )}
            {rejectRoute !== null && (
                <button
                    type="button"
                    disabled={!center.idle}
                    onClick={() => setOpen('reject')}
                    className="h-11 flex-1 rounded-xl border border-[#f2c4c4] bg-rz-surface text-[13px] font-bold text-[#d0342c] disabled:cursor-not-allowed disabled:opacity-60 dark:border-[rgba(255,107,111,.3)] dark:text-rz-danger-text"
                >
                    {t('auditor.seal.reject')}
                </button>
            )}
        </div>
    );

    let sheet: ReactNode = null;

    /* A route is present only while its options are, so each sheet has its reasons. */
    if (open === 'request_changes' && requestChangesRoute !== null) {
        sheet = (
            <ReasonSheet
                title={t('auditor.seal.suggest')}
                lead={t('auditor.seal.suggest_lead', { business })}
                placeholder={t('auditor.seal.suggest_placeholder')}
                submitLabel={t('auditor.seal.suggest_submit')}
                options={(options as ReasonOptions).request_changes}
                initialReason={initialReason}
                explanationRequired
                maxLength={RETURN_REASON_MAX}
                onSubmit={(fields) =>
                    send('audit.request_changes', requestChangesRoute, fields)
                }
                onClose={close}
            />
        );
    } else if (open === 'reject' && rejectRoute !== null) {
        sheet = (
            <ReasonSheet
                title={t('auditor.seal.reject')}
                lead={t('auditor.seal.reject_lead', { business })}
                placeholder={t('auditor.seal.reject_placeholder')}
                submitLabel={t('auditor.seal.reject_submit')}
                options={(options as ReasonOptions).reject}
                initialReason={initialReason}
                explanationRequired
                maxLength={RETURN_REASON_MAX}
                destructive
                onSubmit={(fields) => send('audit.reject', rejectRoute, fields)}
                onClose={close}
            />
        );
    }

    return { buttons, sheet };
}
