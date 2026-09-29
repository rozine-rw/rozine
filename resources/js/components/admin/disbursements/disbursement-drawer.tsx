import { useState } from 'react';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import { formatTimestamp } from '@/components/admin/format';
import { MakerChecker } from '@/components/admin/maker-checker';
import { ReasonStage } from '@/components/admin/reason-stage';
import type { StageTone } from '@/components/admin/reason-stage';
import { TrailList } from '@/components/admin/trail-list';
import { CAPTION, Chip, EXPLAIN } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    DisbursementDetail,
    DisbursementState,
    StaffViewer,
    Tone,
} from '@/types/admin';

export const STATE_TONE: Record<DisbursementState, Tone> = {
    ready: 'blue',
    awaiting_second_approver: 'purple',
    on_hold: 'amber',
    dispatched: 'blue',
    paid: 'green',
    failed: 'red',
};

type Command = 'authorize' | 'approve' | 'reject' | 'hold' | 'retry';

const COMMANDS: { key: Command; tone: StageTone; button: string }[] = [
    {
        key: 'authorize',
        tone: 'green',
        button: 'bg-[#1d9e75] text-white border-transparent',
    },
    {
        key: 'approve',
        tone: 'green',
        button: 'bg-[#1d9e75] text-white border-transparent',
    },
    {
        key: 'retry',
        tone: 'blue',
        button: 'bg-rz-accent-fill text-white border-transparent',
    },
    {
        key: 'hold',
        tone: 'amber',
        button: 'bg-rz-surface border-[#f6e7c8] text-[#c2661f] dark:border-rz-border dark:text-[#f0a060]',
    },
    {
        key: 'reject',
        tone: 'red',
        button: 'bg-rz-surface border-[#fdd9da] text-[#e5484d] dark:border-[rgba(255,107,111,.3)] dark:text-[#ff6b6f]',
    },
];

const TILE =
    'min-w-0 rounded-[11px] border border-rz-hairline bg-rz-surface px-3 py-2.5';

/**
 * One release, opened from the queue (MVP-ADMIN-SCR-03). It shows the two-person rule for this
 * amount, who authorized and who checked (with reasons and times), and — for a failed payout —
 * the provider's failure in full before any retry is offered (SCR-03-ST-02).
 */
export function DisbursementDrawer({
    disbursement,
    viewer,
}: {
    disbursement: DisbursementDetail;
    viewer: StaffViewer;
}) {
    const { t, locale } = useTranslation();
    const [stage, setStage] = useState<{
        key: Command;
        tone: StageTone;
        action: RouteAction;
    } | null>(null);
    const waiting = disbursement.state === 'awaiting_second_approver';
    const selfBlocked = waiting && disbursement.viewer_is_maker;
    const failure = disbursement.failure;
    const available = COMMANDS.flatMap((command) => {
        const action = disbursement.actions[command.key];

        return action === undefined ? [] : [{ ...command, action }];
    });

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
                            <Chip tone={STATE_TONE[disbursement.state]}>
                                {t(
                                    `admin.disbursements.state.${disbursement.state}`,
                                )}
                            </Chip>
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
                            {t('admin.disbursements.due')}
                        </dt>
                        <dd className="mt-[3px] text-[15px] font-bold text-rz-ink">
                            {formatDate(disbursement.due_on, locale)}
                        </dd>
                    </div>
                </dl>

                <p className="mt-3.5 rounded-xl bg-[rgba(30,58,255,.07)] px-3.5 py-2.5 text-[12.5px] leading-[1.5] text-[#5f6fc8] dark:text-[#99a3ff]">
                    {disbursement.requires_second_approver
                        ? t('admin.disbursements.rule_dual', {
                              threshold: formatRwf(disbursement.threshold),
                          })
                        : t('admin.disbursements.rule_single', {
                              threshold: formatRwf(disbursement.threshold),
                          })}
                </p>

                {failure !== null && (
                    <section
                        aria-label={t('admin.disbursements.failure_title')}
                        className="mt-3.5 rounded-[14px] border border-[#fdeaea] bg-[rgba(255,77,79,.06)] px-[17px] py-3.5 dark:border-[rgba(255,107,111,.25)]"
                    >
                        <h3 className="text-[13px] font-bold text-[#e5484d] dark:text-[#ff6b6f]">
                            {t('admin.disbursements.failure_title')}
                        </h3>
                        <p
                            className={cn(
                                'mt-1 text-[12.5px] leading-[1.5]',
                                EXPLAIN,
                            )}
                        >
                            {failure.message}
                        </p>
                        <dl className="mt-2.5 grid grid-cols-2 gap-x-3 gap-y-1.5 text-[12px]">
                            <dt className={CAPTION}>
                                {t('admin.disbursements.failure_code')}
                            </dt>
                            <dd className="font-mono font-semibold text-rz-ink">
                                {failure.code}
                            </dd>
                            <dt className={CAPTION}>
                                {t('admin.disbursements.failure_provider')}
                            </dt>
                            <dd className="font-mono font-semibold text-rz-ink">
                                {failure.provider_reference}
                            </dd>
                            <dt className={CAPTION}>
                                {t('admin.disbursements.failure_at')}
                            </dt>
                            <dd className="font-semibold text-rz-ink tabular-nums">
                                {formatTimestamp(failure.failed_at)}
                            </dd>
                            <dt className={CAPTION}>
                                {t('admin.disbursements.failure_attempts')}
                            </dt>
                            <dd className="font-semibold text-rz-ink">
                                {failure.attempts}
                            </dd>
                        </dl>
                        <p
                            className={cn(
                                'mt-2.5 text-[12px] leading-[1.5]',
                                EXPLAIN,
                            )}
                        >
                            {t('admin.disbursements.failure_inspect')}
                        </p>
                    </section>
                )}

                <h3 className="mt-5 mb-2.5 text-[12px] font-bold tracking-[.05em] text-[#7b8699] uppercase dark:text-rz-muted">
                    {t('admin.disbursements.approvals')}
                </h3>
                <MakerChecker
                    maker={disbursement.maker}
                    checker={disbursement.checker}
                    viewerIsMaker={disbursement.viewer_is_maker}
                    waiting={waiting}
                />

                <TrailList
                    title={t('admin.disbursements.trail')}
                    entries={disbursement.trail}
                    empty={t('admin.disbursements.trail_empty')}
                />

                {stage === null && (available.length > 0 || selfBlocked) && (
                    <div className="mt-[18px] flex flex-wrap gap-2.5 border-t border-rz-hairline pt-[18px]">
                        {selfBlocked && (
                            <button
                                type="button"
                                disabled
                                className="h-[46px] min-w-[150px] flex-1 cursor-not-allowed rounded-xl bg-[#1d9e75] text-[14px] font-bold text-white opacity-45"
                            >
                                {t('admin.disbursements.command.approve')}
                            </button>
                        )}
                        {available.map((command) => (
                            <button
                                key={command.key}
                                type="button"
                                onClick={() => setStage(command)}
                                className={cn(
                                    'h-[46px] min-w-[150px] flex-1 rounded-xl border text-[14px] font-bold',
                                    command.button,
                                )}
                            >
                                {t(
                                    `admin.disbursements.command.${command.key}`,
                                )}
                            </button>
                        ))}
                    </div>
                )}

                {stage !== null && (
                    <ReasonStage
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
                        action={stage.action}
                        viewer={viewer}
                        onCancel={() => setStage(null)}
                    />
                )}
            </div>
        </Drawer>
    );
}
