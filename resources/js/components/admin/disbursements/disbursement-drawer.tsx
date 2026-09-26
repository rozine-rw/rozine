import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { CommandStage } from '@/components/admin/disbursements/command-stage';
import type { CommandTone } from '@/components/admin/disbursements/command-stage';
import { DisbursementStateChip } from '@/components/admin/disbursements/disbursement-status';
import {
    BindingPanel,
    DispatchPanel,
    HOLD_SELF_NOTE,
    HoldPanel,
    IntentPanel,
    IssuePanel,
    PrecheckPanel,
    ProviderPanel,
    RefundPanel,
    STEP_UP_NOTE,
} from '@/components/admin/disbursements/panels';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import { MakerChecker } from '@/components/admin/maker-checker';
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
    'awaiting_second_approver',
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
        key: 'authorize',
        tone: 'green',
        button: 'border-transparent bg-[#1d9e75] text-white',
    },
    {
        key: 'approve',
        tone: 'green',
        button: 'border-transparent bg-[#1d9e75] text-white',
    },
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
        button: 'border-[#f6e7c8] bg-rz-surface text-[#c2661f] dark:border-rz-border dark:text-[#f0a060]',
    },
    {
        key: 'reject',
        tone: 'red',
        button: 'border-[#fdd9da] bg-rz-surface text-[#e5484d] dark:border-[rgba(255,107,111,.3)] dark:text-[#ff6b6f]',
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
 * Why a command the viewer might expect is withheld: approve for the maker (no self-approval) or
 * while the step-up it needs has no route yet, and hold release for the staff member who placed
 * the hold. The server withholds all three too; this only explains it.
 */
const withheld = (
    disbursement: C3DisbursementDetail,
    key: DisbursementCommandKey,
): string | null => {
    if (key === 'approve') {
        if (
            disbursement.viewer_is_maker &&
            disbursement.state === 'awaiting_second_approver'
        ) {
            return 'disbursement-maker-note';
        }

        if (
            disbursement.allowed_actions.includes('disbursement.approve') &&
            disbursement.step_up.route === null
        ) {
            return STEP_UP_NOTE;
        }
    }

    if (
        key === 'release_hold' &&
        disbursement.hold !== null &&
        disbursement.viewer_placed_hold
    ) {
        return HOLD_SELF_NOTE;
    }

    return null;
};

/**
 * One disbursement (MVP-ADMIN-SCR-03, C3 proposal v2 §2e): precheck, the two distinct staff, what
 * an approval binds and its step-up, the recorded intent (not a payment), the worker's dispatch,
 * the provider's outcome and its reconciliation, any hold, and what issue or failed closing
 * recorded. Every command comes from `allowed_actions` only, carries a written reason and goes
 * through the shared operation command: an uncertain answer is looked up, never resent on its own.
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
    const waiting = disbursement.state === 'awaiting_second_approver';
    const buttons = COMMANDS.flatMap(
        (
            entry,
        ): ((typeof COMMANDS)[number] & { blockedBy: string | null })[] => {
            const blockedBy = withheld(disbursement, entry.key);
            const offered =
                disbursement.allowed_actions.includes(commandName(entry.key)) &&
                disbursement.actions[entry.key] !== undefined;

            return blockedBy !== null || offered
                ? [{ ...entry, blockedBy }]
                : [];
        },
    );

    const send = (key: DisbursementCommandKey, reason: string) => {
        command.send(commandName(key), {
            disbursement_id: disbursement.id,
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
                        <p className="mt-0.5 truncate text-[12px] text-[#7b8699] dark:text-rz-muted">
                            {disbursement.business} · {disbursement.note_title}
                        </p>
                    </div>
                    <DrawerClose close={disbursement.links.close} />
                </div>
            </div>
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-[18px] pb-[26px]">
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

                <HoldPanel disbursement={disbursement} />

                <PrecheckPanel precheck={disbursement.precheck} />

                <section
                    aria-label={t('admin.disbursements.approvals')}
                    className="mt-5"
                >
                    <h3 className="mb-2.5 text-[12px] font-bold tracking-[.05em] text-[#7b8699] uppercase dark:text-rz-muted">
                        {t('admin.disbursements.approvals')}
                    </h3>
                    <MakerChecker
                        maker={disbursement.maker}
                        checker={disbursement.checker}
                        viewerIsMaker={disbursement.viewer_is_maker}
                        waiting={waiting}
                        noteId="disbursement-maker-note"
                    />
                    <p
                        className={cn(
                            'mt-2.5 text-[12px] leading-[1.5]',
                            EXPLAIN,
                        )}
                    >
                        {t('admin.disbursements.rule_two_staff')}
                    </p>
                </section>

                <BindingPanel disbursement={disbursement} />
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
                                disabled={
                                    entry.blockedBy !== null ||
                                    command.unresolved
                                }
                                aria-describedby={entry.blockedBy ?? undefined}
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
