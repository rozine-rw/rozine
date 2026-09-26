import { useState } from 'react';
import { CommandStage } from '@/components/admin/disbursements/command-stage';
import { ReceiptFacts } from '@/components/admin/disbursements/panels';
import { CAPTION, Chip, EXPLAIN } from '@/components/admin/ui';
import { C3Notice } from '@/components/rozine/c3-notice';
import { useC3Command } from '@/hooks/use-c3-command';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type {
    C3AdminApplicationsProps,
    StaffRelease,
    StaffViewer,
    Tone,
} from '@/types/admin';

const STATE_TONE: Record<StaffRelease['state'], Tone> = {
    awaiting_staff_review: 'purple',
    released: 'green',
    refused: 'red',
};

/**
 * The minimal staff release (C3 proposal v2 §1, H1): a reviewed application is released for the
 * business to publish only when the engine, authority and report gates all pass. It is offered
 * only through the server's `allowed_actions` (the existing `applications.review` permission),
 * and a failed gate always shows as blocking — release can never override it, so no Release
 * control appears while any gate has failed.
 */
export function ReleasePanel({
    applicationId,
    release,
    viewer,
    lookup,
    preview,
}: {
    applicationId: string;
    release: StaffRelease;
    viewer: StaffViewer;
    lookup: RouteLink;
    preview?: C3AdminApplicationsProps['preview_outcome'];
}) {
    const { t } = useTranslation();
    const [staged, setStaged] = useState(false);
    const command = useC3Command<'application.release'>({
        actions: { 'application.release': release.actions.release },
        lookup,
        allowed: release.allowed_actions,
        preview,
        only: ['review', 'applications', 'tabs', 'badges'],
    });
    const failed = release.gates.filter((gate) => gate.state === 'failed');
    const offered =
        failed.length === 0 &&
        release.allowed_actions.includes('application.release') &&
        release.actions.release !== null;

    return (
        <section
            aria-label={t('admin.applications.release.title')}
            className="mt-4 rounded-[13px] border border-[#eaeef6] bg-rz-surface px-[17px] py-[15px] dark:border-rz-border"
        >
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h3 className="text-[12.5px] font-bold text-rz-slate">
                    {t('admin.applications.release.title')}
                </h3>
                <Chip tone={STATE_TONE[release.state]}>
                    {t(`admin.applications.release.state.${release.state}`)}
                </Chip>
            </div>
            <p className={cn('mt-1.5 text-[12px] leading-[1.5]', EXPLAIN)}>
                {t('admin.applications.release.explain')}
            </p>
            <ul
                aria-label={t('admin.applications.release.gates')}
                className="mt-3 flex flex-col gap-1.5"
            >
                {release.gates.map((gate) => (
                    <li
                        key={gate.key}
                        className="flex flex-wrap items-center gap-2 text-[12.5px]"
                    >
                        <span className="flex-1 font-semibold text-rz-ink">
                            {t(`admin.applications.release.gate.${gate.key}`)}
                        </span>
                        {gate.cause !== null && (
                            <span
                                className={cn('font-mono text-[11px]', CAPTION)}
                            >
                                {gate.cause}
                            </span>
                        )}
                        <Chip tone={gate.state === 'passed' ? 'green' : 'red'}>
                            {t(
                                `admin.applications.release.gate_state.${gate.state}`,
                            )}
                        </Chip>
                    </li>
                ))}
            </ul>
            {failed.length > 0 && (
                <p
                    role="status"
                    className="mt-3 rounded-xl border border-[#fdeaea] bg-[rgba(255,77,79,.06)] px-3.5 py-2.5 text-[12.5px] leading-[1.5] text-[#c4373c] dark:border-[rgba(255,107,111,.25)] dark:text-[#ff6b6f]"
                >
                    {t('admin.applications.release.blocked')}
                </p>
            )}
            {release.receipt !== null && (
                <div className="mt-3 border-t border-rz-hairline pt-3">
                    <div
                        className={cn(
                            'mb-1.5 text-[11px] font-semibold',
                            CAPTION,
                        )}
                    >
                        {t('admin.applications.release.receipt')}
                    </div>
                    <ReceiptFacts receipt={release.receipt} />
                </div>
            )}
            <C3Notice command={command} className="mt-3" />
            {offered && !staged && (
                <button
                    type="button"
                    disabled={command.unresolved}
                    onClick={() => setStaged(true)}
                    className="mt-3 h-[42px] w-full rounded-[11px] bg-[#1d9e75] text-[13.5px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-45"
                >
                    {t('admin.applications.release.command')}
                </button>
            )}
            {offered && staged && (
                <CommandStage
                    title={t('admin.applications.release.stage.title')}
                    body={t('admin.applications.release.stage.body')}
                    cta={t('admin.applications.release.stage.cta')}
                    placeholder={t(
                        'admin.applications.release.stage.placeholder',
                    )}
                    tone="green"
                    viewer={viewer}
                    busy={command.busy}
                    locked={command.unresolved}
                    error={command.errors.reason}
                    onSubmit={(reason) =>
                        command.send('application.release', {
                            application_id: applicationId,
                            expected_revision: release.revision,
                            reason,
                        })
                    }
                    onCancel={() => setStaged(false)}
                />
            )}
        </section>
    );
}
