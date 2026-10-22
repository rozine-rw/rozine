import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { CommandStage } from '@/components/admin/disbursements/command-stage';
import type { CommandTone } from '@/components/admin/disbursements/command-stage';
import { DisbursementStateChip } from '@/components/admin/disbursements/disbursement-status';
import {
    AutomationPanel,
    DispatchPanel,
    HoldPanel,
    IntentPanel,
    IssuePanel,
    PrecheckPanel,
    ProviderPanel,
    RefundPanel,
} from '@/components/admin/disbursements/panels';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import { TrailList } from '@/components/admin/trail-list';
import { CAPTION, EXPLAIN } from '@/components/admin/ui';
import { C3Notice, PollStopped } from '@/components/rozine/c3-notice';
import { useBoundedPoll } from '@/hooks/use-bounded-poll';
import { useC3Command } from '@/hooks/use-c3-command';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    C3DisbursementDetail,
    DisbursementAllowedAction,
    DisbursementCommandKey,
    StaffViewer,
} from '@/types/admin';
import type { C3PreviewOutcome } from '@/types/settlement';

/** What a command, a poll or a refresh reads again: fresh authority and actions each time. */
export const DISBURSEMENT_RELOAD = [
    'disbursement',
    'disbursements',
    'needs_attention',
    'allowed_actions',
    'badges',
    'server_time',
];

const COMMANDS: {
    key: DisbursementCommandKey;
    tone: CommandTone;
    button: string;
}[] = [
    {
        key: 'requery',
        tone: 'blue',
        button: 'border-transparent bg-rz-accent-fill text-white',
    },
    {
        key: 'release_hold',
        tone: 'blue',
        button: 'border-rz-hairline bg-rz-surface text-rz-accent-app-text',
    },
    {
        key: 'hold',
        tone: 'amber',
        button: 'border-[#f6e7c8] bg-rz-surface text-[#a4521a] dark:border-rz-border dark:text-[#f0a060]',
    },
];

const BUTTON =
    'h-[46px] min-w-[150px] flex-1 rounded-xl border text-[14px] font-bold disabled:cursor-not-allowed disabled:opacity-45';

const TILE =
    'min-w-0 rounded-[11px] border border-rz-hairline bg-rz-surface px-3 py-2.5';

const commandName = (key: DisbursementCommandKey): DisbursementAllowedAction =>
    `disbursement.${key}`;

/**
 * Still waiting on the provider: queued for the worker, or sent with no confirmed outcome. Only
 * these are polled; a verified outcome is not.
 */
const unsettled = (disbursement: C3DisbursementDetail): boolean =>
    disbursement.state === 'queued' ||
    (disbursement.state === 'dispatched' &&
        (disbursement.provider === null ||
            disbursement.provider.state === 'pending' ||
            disbursement.provider.state === 'unknown'));

/**
 * One disbursement (MVP-ADMIN-SCR-03, BRS v2.0 SVC-R01): automatic payment and what it waits for,
 * the precheck it ran, the recorded intent (not a payment), the worker's dispatch, the provider's
 * outcome and its reconciliation, any hold, and what issue or failed closing recorded. Nobody
 * approves a payment; one authorised staff member may hold it and release the hold (ADM-R02).
 * Every command comes from `allowed_actions` only, carries a written reason and goes through the
 * shared operation command: an uncertain answer is looked up, never resent on its own. The route
 * names the disbursement, so no body carries its id (#96 answer 3): each body is
 * `{request_id, expected_revision, reason}`.
 */
export function DisbursementDrawer({
    disbursement,
    viewer,
    preview,
}: {
    disbursement: C3DisbursementDetail;
    viewer: StaffViewer;
    preview?: C3PreviewOutcome<DisbursementAllowedAction>;
}) {
    const { t } = useTranslation();
    const [stage, setStage] = useState<(typeof COMMANDS)[number] | null>(null);
    const command = useC3Command<DisbursementAllowedAction>({
        actions: Object.fromEntries(
            COMMANDS.map(({ key }) => [
                commandName(key),
                disbursement.actions[key] ?? null,
            ]),
        ),
        lookup: disbursement.links.operation,
        allowed: disbursement.allowed_actions,
        preview,
        only: DISBURSEMENT_RELOAD,
    });
    const poll = useBoundedPoll(unsettled(disbursement), DISBURSEMENT_RELOAD);
    const buttons = COMMANDS.filter(
        ({ key }) =>
            disbursement.allowed_actions.includes(commandName(key)) &&
            disbursement.actions[key] !== undefined,
    );

    const send = (key: DisbursementCommandKey, reason: string) => {
        command.send(commandName(key), {
            expected_revision: disbursement.revision,
            reason,
        });
    };

    return (
        <Drawer
            label={t('admin.disbursements.drawer_label', {
                reference: disbursement.reference,
            })}
            close={disbursement.links.close}
        >
            <div className="shrink-0 border-b border-rz-hairline bg-rz-surface px-[18px] py-4">
                <div className="flex items-center gap-3">
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="font-mono text-[17px] font-bold text-rz-ink">
                                {disbursement.reference}
                            </h2>
                            <DisbursementStateChip state={disbursement.state} />
                        </div>
                        <p className="mt-0.5 truncate text-[12px] text-[#636e86] dark:text-rz-muted">
                            {disbursement.business} · {disbursement.note_title}
                        </p>
                    </div>
                    <DrawerClose close={disbursement.links.close} />
                </div>
            </div>
            <div
                tabIndex={0}
                className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-[18px] pb-[26px]"
            >
                <dl className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div className={TILE}>
                        <dt
                            className={cn(
                                'text-[10.5px] font-semibold',
                                CAPTION,
                            )}
                        >
                            {t('admin.disbursements.amount')}
                        </dt>
                        <dd className="mt-[3px] text-[15px] font-bold text-rz-ink">
                            {formatRwf(disbursement.amount)}
                        </dd>
                        {disbursement.platform_fee && (
                            <dd className={cn('mt-[3px] text-[11px]', EXPLAIN)}>
                                {t('admin.disbursements.platform_fee', {
                                    fee: formatRwf(
                                        disbursement.platform_fee.fee,
                                    ),
                                    share: formatRwf(
                                        disbursement.platform_fee
                                            .audit_partner_share,
                                    ),
                                    payout: formatRwf(
                                        disbursement.platform_fee.payout,
                                    ),
                                })}
                            </dd>
                        )}
                    </div>
                    <div className={TILE}>
                        <dt
                            className={cn(
                                'text-[10.5px] font-semibold',
                                CAPTION,
                            )}
                        >
                            {t('admin.disbursements.destination')}
                        </dt>
                        <dd className="mt-[3px] truncate text-[15px] font-bold text-rz-ink">
                            {disbursement.destination}
                        </dd>
                    </div>
                    <div className={TILE}>
                        <dt
                            className={cn(
                                'text-[10.5px] font-semibold',
                                CAPTION,
                            )}
                        >
                            {t('admin.disbursements.deadline')}
                        </dt>
                        <dd className="mt-[3px] text-[13px] font-semibold text-rz-faint">
                            {t('admin.disbursements.deadline_unavailable')}
                        </dd>
                    </div>
                </dl>

                <PollStopped
                    exhausted={poll.exhausted}
                    refresh={poll.refresh}
                    className="mt-3.5"
                />

                <AutomationPanel automation={disbursement.automation} />

                <HoldPanel hold={disbursement.hold} />

                <PrecheckPanel precheck={disbursement.precheck} />

                <IntentPanel disbursement={disbursement} />
                <DispatchPanel dispatch={disbursement.dispatch} />
                <ProviderPanel provider={disbursement.provider} />
                <IssuePanel issue={disbursement.issue} />
                <RefundPanel refund={disbursement.refund} />

                {disbursement.links.ledger !== null && (
                    <Link
                        href={disbursement.links.ledger}
                        className="mt-3.5 block w-full rounded-[11px] border border-rz-hairline bg-rz-surface p-[11px] text-center text-[13px] font-bold text-rz-accent-app-text"
                    >
                        {t('admin.disbursements.ledger')}
                    </Link>
                )}

                <TrailList
                    title={t('admin.disbursements.trail')}
                    entries={disbursement.trail}
                    empty={t('admin.disbursements.trail_empty')}
                />

                <C3Notice command={command} className="mt-[18px]" />

                {stage === null && buttons.length > 0 && (
                    <div className="mt-[18px] flex flex-wrap gap-2.5 border-t border-rz-hairline pt-[18px]">
                        {buttons.map((entry) => (
                            <button
                                key={entry.key}
                                type="button"
                                disabled={command.unresolved}
                                onClick={() => setStage(entry)}
                                className={cn(BUTTON, entry.button)}
                            >
                                {t(`admin.disbursements.command.${entry.key}`)}
                            </button>
                        ))}
                    </div>
                )}

                {stage !== null && (
                    <CommandStage
                        key={stage.key}
                        title={t(
                            `admin.disbursements.stage.${stage.key}.title`,
                        )}
                        body={t(`admin.disbursements.stage.${stage.key}.body`, {
                            amount: formatRwf(disbursement.amount),
                            business: disbursement.business,
                        })}
                        cta={t(`admin.disbursements.stage.${stage.key}.cta`)}
                        placeholder={t(
                            `admin.disbursements.stage.${stage.key}.placeholder`,
                        )}
                        tone={stage.tone}
                        viewer={viewer}
                        busy={command.busy}
                        locked={command.unresolved}
                        error={command.errors.reason}
                        onSubmit={(reason) => send(stage.key, reason)}
                        onCancel={() => setStage(null)}
                    />
                )}
            </div>
        </Drawer>
    );
}
