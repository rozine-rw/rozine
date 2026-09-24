import { useState } from 'react';
import type { ReactNode } from 'react';
import { ConflictSheet } from '@/components/auditor/sheets/conflict-sheet';
import { ReasonSheet } from '@/components/auditor/sheets/reason-sheet';
import { useTranslation } from '@/hooks/use-translation';
import type { JobActions } from '@/types/auditor';

type Sheet = 'decline' | 'conflict' | null;

/**
 * Decline and conflict for one file, as two quiet text actions and the sheets they open. A
 * conflict can be declared at any point, including mid-procedure (MVP-AUDITOR-AC-08).
 */
export function useJobCommands({
    fileId,
    business,
    actions,
}: {
    fileId: string;
    business: string;
    actions: Pick<JobActions, 'conflict'> &
        Partial<Pick<JobActions, 'decline'>>;
}): { links: ReactNode; conflictButton: ReactNode; sheet: ReactNode } {
    const { t } = useTranslation();
    const [open, setOpen] = useState<Sheet>(null);
    const close = () => setOpen(null);
    const decline = actions.decline;

    const conflictButton = (
        <button
            type="button"
            onClick={() => setOpen('conflict')}
            className="text-[11.5px] font-bold text-rz-secondary"
        >
            {t('auditor.jobs.declare_conflict')}
        </button>
    );

    const links = (
        <div className="flex items-center justify-between gap-3">
            {decline ? (
                <button
                    type="button"
                    onClick={() => setOpen('decline')}
                    className="text-[11.5px] font-bold text-rz-secondary"
                >
                    {t('auditor.jobs.decline')}
                </button>
            ) : (
                <span />
            )}
            {conflictButton}
        </div>
    );

    let sheet: ReactNode = null;

    if (open === 'decline' && decline) {
        sheet = (
            <ReasonSheet
                title={t('auditor.decline.title', { business })}
                lead={t('auditor.decline.lead')}
                label={t('auditor.decline.label')}
                placeholder={t('auditor.decline.placeholder')}
                submitLabel={t('auditor.decline.submit')}
                action={decline}
                onClose={close}
            />
        );
    } else if (open === 'conflict') {
        sheet = (
            <ConflictSheet
                business={business}
                fileId={fileId}
                action={actions.conflict}
                onClose={close}
            />
        );
    }

    return { links, conflictButton, sheet };
}
