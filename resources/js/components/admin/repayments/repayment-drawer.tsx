import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { CommandStage } from '@/components/admin/disbursements/command-stage';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import { formatTimestamp } from '@/components/admin/format';
import {
    AllocationPanel,
    ReceiptsPanel,
    ReconciliationPanel,
    RepaymentStateChip,
    ServicingPanel,
    SourcePanel,
} from '@/components/admin/repayments/panels';
import { TrailList } from '@/components/admin/trail-list';
import { CAPTION } from '@/components/admin/ui';
import { C3Notice } from '@/components/rozine/c3-notice';
import { useC3Command } from '@/hooks/use-c3-command';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    RepaymentAllowedAction,
    RepaymentDetail,
    StaffViewer,
} from '@/types/admin';
import type { C3PreviewOutcome } from '@/types/settlement';

/** What a command or a refresh reads again: fresh authority and actions each time. */
export const REPAYMENT_RELOAD = [
    'repayment',
    'repayments',
    'counts',
    'allowed_actions',
    'server_time',
];

const TILE =
    'min-w-0 rounded-[11px] border border-rz-hairline bg-rz-surface px-3 py-2.5';

/**
 * One repayment (AC-13, C4 v1 §4b): where the money came from, the note's servicing before and
 * after, the reconciliation at the policy tolerance, the allocation the core posted, and each
 * immutable receipt. There is no amount field and no balance edit. The only command is requery,
 * offered only through `allowed_actions`: it asks about the same provider operation and never
 * resends, and an uncertain answer is looked up rather than sent again.
 */
export function RepaymentDrawer({
    repayment,
    viewer,
    preview,
}: {
    repayment: RepaymentDetail;
    viewer: StaffViewer;
    preview?: C3PreviewOutcome<RepaymentAllowedAction>;
}) {
    const { t } = useTranslation();
    const [staging, setStaging] = useState(false);
    const command = useC3Command<RepaymentAllowedAction>({
        actions: { 'repayment.requery': repayment.actions.requery ?? null },
        lookup: repayment.links.operation,
        allowed: repayment.allowed_actions,
        preview,
        only: REPAYMENT_RELOAD,
    });
    const canRequery =
        repayment.allowed_actions.includes('repayment.requery') &&
        repayment.actions.requery !== undefined;

    const tile = (label: string, value: string) => (
        <div className={TILE}>
            <dt className={cn('text-[10.5px] font-semibold', CAPTION)}>
                {label}
            </dt>
            <dd className="mt-[3px] truncate text-[15px] font-bold text-rz-ink">
                {value}
            </dd>
        </div>
    );

    return (
        <Drawer
            label={t('admin.repayments.drawer_label', {
                reference: repayment.reference,
            })}
            close={repayment.links.close}
        >
            <div className="shrink-0 border-b border-rz-hairline bg-rz-surface px-[18px] py-4">
                <div className="flex items-center gap-3">
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="font-mono text-[17px] font-bold text-rz-ink">
                                {repayment.reference}
                            </h2>
                            <RepaymentStateChip state={repayment.state} />
                        </div>
                        <p className="mt-0.5 truncate text-[12px] text-[#7b8699] dark:text-rz-muted">
                            {repayment.business} · {repayment.note_title}
                        </p>
                    </div>
                    <DrawerClose close={repayment.links.close} />
                </div>
            </div>
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-[18px] pb-[26px]">
                <dl className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    {tile(
                        t('admin.repayments.amount'),
                        formatRwf(repayment.amount),
                    )}
                    {tile(
                        t('admin.repayments.received_at'),
                        formatTimestamp(repayment.received_at),
                    )}
                    {tile(
                        t('admin.repayments.dpd_at_receipt'),
                        repayment.dpd_at_receipt === null
                            ? '—'
                            : String(repayment.dpd_at_receipt),
                    )}
                </dl>

                <SourcePanel source={repayment.source_detail} />
                <ReconciliationPanel check={repayment.reconciliation} />
                <ServicingPanel
                    before={repayment.servicing_before}
                    after={repayment.servicing_after}
                />
                <AllocationPanel allocation={repayment.allocation} />
                <ReceiptsPanel receipts={repayment.receipts} />

                {repayment.links.ledger !== null && (
                    <Link
                        href={repayment.links.ledger}
                        className="mt-3.5 block w-full rounded-[11px] border border-rz-hairline bg-rz-surface p-[11px] text-center text-[13px] font-bold text-rz-accent-app-text"
                    >
                        {t('admin.disbursements.ledger')}
                    </Link>
                )}
                {repayment.links.business !== null && (
                    <Link
                        href={repayment.links.business}
                        className="mt-2 block w-full rounded-[11px] border border-rz-hairline bg-rz-surface p-[11px] text-center text-[13px] font-bold text-rz-accent-app-text"
                    >
                        {t('admin.repayments.business')}
                    </Link>
                )}

                <TrailList
                    title={t('admin.repayments.trail')}
                    entries={repayment.trail}
                    empty={t('admin.disbursements.trail_empty')}
                />

                <C3Notice command={command} className="mt-[18px]" />

                {canRequery && !staging && (
                    <div className="mt-[18px] flex flex-wrap gap-2.5 border-t border-rz-hairline pt-[18px]">
                        <button
                            type="button"
                            disabled={command.unresolved}
                            onClick={() => setStaging(true)}
                            className="h-[46px] min-w-[150px] flex-1 rounded-xl border border-transparent bg-rz-accent-fill text-[14px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-45"
                        >
                            {t('admin.disbursements.command.requery')}
                        </button>
                    </div>
                )}

                {canRequery && staging && (
                    <CommandStage
                        title={t('admin.disbursements.stage.requery.title')}
                        body={t('admin.repayments.requery_body')}
                        cta={t('admin.disbursements.stage.requery.cta')}
                        placeholder={t(
                            'admin.disbursements.stage.requery.placeholder',
                        )}
                        tone="blue"
                        viewer={viewer}
                        busy={command.busy}
                        locked={command.unresolved}
                        error={command.errors.reason}
                        onSubmit={(reason) => {
                            command.send('repayment.requery', {
                                repayment_id: repayment.id,
                                expected_revision: repayment.revision,
                                reason,
                            });
                        }}
                        onCancel={() => setStaging(false)}
                    />
                )}
            </div>
        </Drawer>
    );
}
