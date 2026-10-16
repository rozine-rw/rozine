import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    AuditorCommandNotice,
    useAuditorCommands,
} from '@/components/auditor/commands';
import { BottomSheet } from '@/components/auditor/sheets/bottom-sheet';
import {
    FORM_PRIMARY,
    NOTE_FIELD,
    SECONDARY_BUTTON,
    SHEET_LABEL,
} from '@/components/auditor/sheets/reason-sheet';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type { AuditStage } from '@/types/auditor';

/** The longest reason the server records with an upheld dispute. */
export const UPHOLD_REASON_MAX = 1000;

/** The reason field and its counter, in the sheet. */
function UpholdSheet({
    business,
    onSubmit,
    onClose,
}: {
    business: string;
    onSubmit: (reason: string) => void;
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const center = useAuditorCommands();
    const [reason, setReason] = useState('');
    const ready = reason.trim() !== '' && center.idle;

    return (
        <BottomSheet
            title={t('auditor.sealed.dispute.uphold')}
            lead={t('auditor.dispute_uphold.lead', { business })}
            onClose={onClose}
        >
            <AuditorCommandNotice
                placement="sheet"
                shown={['reason']}
                className="mt-3.5"
            />
            <div className="mt-3.5">
                <label htmlFor="auditor-uphold-reason" className={SHEET_LABEL}>
                    {t('auditor.dispute_uphold.label')}
                </label>
                <textarea
                    id="auditor-uphold-reason"
                    value={reason}
                    maxLength={UPHOLD_REASON_MAX}
                    onChange={(event) =>
                        setReason(
                            event.target.value.slice(0, UPHOLD_REASON_MAX),
                        )
                    }
                    placeholder={t('auditor.dispute_uphold.placeholder')}
                    aria-invalid={center.errors.reason ? true : undefined}
                    aria-describedby={
                        center.errors.reason
                            ? 'auditor-uphold-reason-error'
                            : undefined
                    }
                    className={cn(NOTE_FIELD, 'min-h-[96px]')}
                />
                <p className="mt-1 text-right text-[10.5px] text-rz-secondary">
                    {t('auditor.reason.count', {
                        count: reason.length,
                        max: UPHOLD_REASON_MAX,
                    })}
                </p>
                <FieldError id="auditor-uphold-reason-error">
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
                        onClick={() => onSubmit(reason.trim())}
                        disabled={!ready}
                        aria-busy={center.busy || undefined}
                        className={cn(FORM_PRIMARY, 'flex-[2]')}
                    >
                        {t('auditor.dispute_uphold.submit')}
                    </button>
                </div>
            </div>
        </BottomSheet>
    );
}

/**
 * Upholding the findings against a Business dispute (N6, `audit.dispute.uphold`): the CPA's
 * required plain-text reason goes with the dispute's own revision, the report's revision and its
 * digest. The dispute then escalates to Rozine staff; it never publishes the report, and the page
 * continues at the server's `data.next`. Offered only on a sealed report with a dispute, where
 * `allowed_actions` lists the command and its route is sent.
 */
export function useDisputeUphold({
    stage,
    business,
    reportRevision,
    route,
}: {
    stage: AuditStage;
    business: string;
    reportRevision: number;
    route: RouteAction | null;
}): {
    uphold: { open: () => void; disabled: boolean } | null;
    sheet: ReactNode;
} {
    const center = useAuditorCommands();
    const [open, setOpen] = useState(false);

    if (
        stage.step !== 'sealed' ||
        stage.dispute === null ||
        route === null ||
        !center.allowed('audit.dispute.uphold')
    ) {
        return { uphold: null, sheet: null };
    }

    const { dispute, digest } = stage;
    const close = () => setOpen(false);

    const send = (reason: string) =>
        center.send(
            {
                name: 'audit.dispute.uphold',
                business,
                route,
                payload: {
                    expected_revision: dispute.revision,
                    report_revision: reportRevision,
                    digest,
                    reason,
                },
            },
            { onCompleted: close },
        );

    return {
        uphold: { open: () => setOpen(true), disabled: !center.idle },
        sheet: open ? (
            <UpholdSheet business={business} onSubmit={send} onClose={close} />
        ) : null,
    };
}
