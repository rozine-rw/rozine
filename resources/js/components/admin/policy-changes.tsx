import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { formatTimestamp } from '@/components/admin/format';
import { ReasonStage } from '@/components/admin/reason-stage';
import {
    CardTitle,
    Chip,
    EXPLAIN,
    Panel,
    ROW_RULE,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    PolicyChange,
    PolicyChangeBoard,
    PolicySetting,
    PolicySettingKey,
    StaffViewer,
} from '@/types/admin';

type Stage =
    | { kind: 'propose'; setting: PolicySetting; value: number }
    | {
          kind: 'approve' | 'reject';
          setting: PolicySetting;
          change: PolicyChange;
      };

const FIELD =
    'h-[38px] w-[150px] rounded-[10px] border border-rz-hairline bg-[#f7f9fd] px-3 text-[13px] text-rz-ink outline-none focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] dark:bg-rz-field';

const BUTTON =
    'h-[34px] rounded-[10px] px-3.5 text-[12.5px] font-bold disabled:cursor-not-allowed disabled:opacity-50';

/**
 * The policies that change only once both founders approve (Erastus, 26 Nov 2026): the caps one
 * investor may hold of a raise, and the institution terms. Staff who may propose enter a new value
 * and a reason; each founder then approves or refuses it separately, and the value in force changes
 * only after the last approval. The server rechecks every permission, range and decision.
 */
export function PolicyChanges({
    board,
    viewer,
}: {
    board: PolicyChangeBoard;
    viewer: StaffViewer;
}) {
    const { t } = useTranslation();
    const { errors } = usePage<{ errors: Record<string, string> }>().props;
    const [stage, setStage] = useState<Stage | null>(null);
    const [editing, setEditing] = useState<string | null>(null);
    const [draft, setDraft] = useState('');
    const [requestId, setRequestId] = useState(() => crypto.randomUUID());

    const value = (setting: Pick<PolicySetting, 'unit'>, amount: number) =>
        setting.unit === 'percent'
            ? `${formatCount(amount)}%`
            : setting.unit === 'rwf'
              ? `RWF ${formatCount(amount)}`
              : t('admin.policy_changes.day', { day: amount });
    const unitOf = (key: PolicySettingKey) =>
        board.settings.find((setting) => setting.key === key) ?? {
            unit: 'percent' as const,
        };
    const open = (next: Stage) => {
        setRequestId(crypto.randomUUID());
        setStage(next);
        setEditing(null);
    };
    const parsed = Number(draft.replace(/[\s,]/g, ''));
    const valid = (setting: PolicySetting) =>
        draft.trim() !== '' &&
        Number.isInteger(parsed) &&
        parsed >= setting.min &&
        parsed <= setting.max &&
        parsed !== setting.value;
    const stageProposal = (event: FormEvent, setting: PolicySetting) => {
        event.preventDefault();

        if (valid(setting)) {
            open({ kind: 'propose', setting, value: parsed });
        }
    };
    const pageError = errors.form ?? errors.value ?? errors.key;

    return (
        <Panel label={t('admin.policy_changes.title')} className="mb-4">
            <CardTitle>{t('admin.policy_changes.title')}</CardTitle>
            <p className={cn('mt-1 max-w-[720px] text-[12.5px]', EXPLAIN)}>
                {t('admin.policy_changes.intro', {
                    count: board.approvals_needed,
                })}
            </p>
            {pageError && (
                <p
                    role="alert"
                    className="mt-3 rounded-[11px] bg-[rgba(229,72,77,.1)] px-3.5 py-2.5 text-[12.5px] font-semibold text-[#c2292e] dark:text-rz-danger-text"
                >
                    {pageError}
                </p>
            )}
            <ul className="mt-3">
                {board.settings.map((setting) => {
                    const pending = setting.pending;
                    const approvals =
                        pending?.decisions.filter(
                            (decision) => decision.decision === 'approve',
                        ) ?? [];

                    return (
                        <li
                            key={setting.key}
                            className={cn(
                                'flex flex-wrap items-start justify-between gap-3 py-3 last:border-b-0',
                                ROW_RULE,
                            )}
                        >
                            <div className="min-w-[220px] flex-1">
                                <div className="text-[13px] font-semibold text-rz-ink">
                                    {t(`admin.policy.${setting.key}`)}
                                </div>
                                <div
                                    className={cn(
                                        'mt-0.5 text-[12px]',
                                        EXPLAIN,
                                    )}
                                >
                                    {t(`admin.policy.hint.${setting.key}`)}
                                </div>
                                {pending && (
                                    <div className="mt-2 flex flex-wrap items-center gap-2 text-[12px] text-rz-ink">
                                        <Chip tone="amber">
                                            {t('admin.policy_changes.waiting', {
                                                done: approvals.length,
                                                count: board.approvals_needed,
                                            })}
                                        </Chip>
                                        <span>
                                            {t(
                                                'admin.policy_changes.proposed',
                                                {
                                                    from: value(
                                                        setting,
                                                        pending.from_value,
                                                    ),
                                                    to: value(
                                                        setting,
                                                        pending.to_value,
                                                    ),
                                                    name: pending.proposed_by,
                                                },
                                            )}
                                        </span>
                                        {approvals.map((decision) => (
                                            <span
                                                key={decision.by}
                                                className="text-rz-muted"
                                            >
                                                {t(
                                                    'admin.policy_changes.approved_by',
                                                    { name: decision.by },
                                                )}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="text-[15px] font-bold text-rz-ink tabular-nums">
                                    {value(setting, setting.value)}
                                </span>
                                {pending?.approve && (
                                    <button
                                        type="button"
                                        className={cn(
                                            BUTTON,
                                            'bg-[#1d9e75] text-white',
                                        )}
                                        onClick={() =>
                                            open({
                                                kind: 'approve',
                                                setting,
                                                change: pending,
                                            })
                                        }
                                    >
                                        {t('admin.policy_changes.approve')}
                                    </button>
                                )}
                                {pending?.reject && (
                                    <button
                                        type="button"
                                        className={cn(
                                            BUTTON,
                                            'border border-rz-hairline bg-rz-surface text-[#c2292e] dark:text-rz-danger-text',
                                        )}
                                        onClick={() =>
                                            open({
                                                kind: 'reject',
                                                setting,
                                                change: pending,
                                            })
                                        }
                                    >
                                        {t('admin.policy_changes.reject')}
                                    </button>
                                )}
                                {pending?.viewer_decided && (
                                    <span className="text-[12px] text-rz-muted">
                                        {t('admin.policy_changes.you_decided')}
                                    </span>
                                )}
                                {board.propose && !pending && (
                                    <button
                                        type="button"
                                        className={cn(
                                            BUTTON,
                                            'border border-rz-hairline bg-rz-surface text-rz-ink',
                                        )}
                                        onClick={() => {
                                            setStage(null);
                                            setDraft(String(setting.value));
                                            setEditing(setting.key);
                                        }}
                                    >
                                        {t('admin.policy_changes.change')}
                                    </button>
                                )}
                            </div>
                            {editing === setting.key && (
                                <form
                                    onSubmit={(event) =>
                                        stageProposal(event, setting)
                                    }
                                    aria-label={t(
                                        'admin.policy_changes.new_value',
                                        {
                                            name: t(
                                                `admin.policy.${setting.key}`,
                                            ),
                                        },
                                    )}
                                    className="flex w-full flex-wrap items-center gap-2"
                                >
                                    <label
                                        htmlFor={`policy-${setting.key}`}
                                        className="text-[12px] text-rz-muted"
                                    >
                                        {t('admin.policy_changes.range', {
                                            min: value(setting, setting.min),
                                            max: value(setting, setting.max),
                                        })}
                                    </label>
                                    <input
                                        id={`policy-${setting.key}`}
                                        inputMode="numeric"
                                        value={draft}
                                        onChange={(event) =>
                                            setDraft(event.target.value)
                                        }
                                        className={FIELD}
                                    />
                                    <button
                                        type="submit"
                                        disabled={!valid(setting)}
                                        className={cn(
                                            BUTTON,
                                            'bg-rz-accent-fill text-white',
                                        )}
                                    >
                                        {t('admin.policy_changes.next')}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setEditing(null)}
                                        className={cn(BUTTON, 'text-rz-muted')}
                                    >
                                        {t('admin.policy_changes.cancel')}
                                    </button>
                                </form>
                            )}
                            {stage?.setting.key === setting.key && (
                                <div className="w-full">
                                    <ReasonStage
                                        key={requestId}
                                        title={t(
                                            `admin.policy_changes.stage.${stage.kind}.title`,
                                            {
                                                name: t(
                                                    `admin.policy.${setting.key}`,
                                                ),
                                                from: value(
                                                    setting,
                                                    stage.kind === 'propose'
                                                        ? setting.value
                                                        : stage.change
                                                              .from_value,
                                                ),
                                                to: value(
                                                    setting,
                                                    stage.kind === 'propose'
                                                        ? stage.value
                                                        : stage.change.to_value,
                                                ),
                                            },
                                        )}
                                        body={t(
                                            `admin.policy_changes.stage.${stage.kind}.body`,
                                        )}
                                        cta={t(
                                            `admin.policy_changes.stage.${stage.kind}.cta`,
                                        )}
                                        placeholder={t(
                                            `admin.policy_changes.stage.${stage.kind}.placeholder`,
                                        )}
                                        tone={
                                            stage.kind === 'reject'
                                                ? 'red'
                                                : stage.kind === 'approve'
                                                  ? 'green'
                                                  : 'blue'
                                        }
                                        action={
                                            stage.kind === 'propose'
                                                ? board.propose!
                                                : stage.kind === 'approve'
                                                  ? stage.change.approve!
                                                  : stage.change.reject!
                                        }
                                        viewer={viewer}
                                        onCancel={() => setStage(null)}
                                        payload={
                                            stage.kind === 'propose'
                                                ? {
                                                      request_id: requestId,
                                                      key: setting.key,
                                                      value: String(
                                                          stage.value,
                                                      ),
                                                  }
                                                : { request_id: requestId }
                                        }
                                    />
                                </div>
                            )}
                        </li>
                    );
                })}
            </ul>
            {board.history.length > 0 && (
                <section
                    aria-label={t('admin.policy_changes.history')}
                    className="mt-4"
                >
                    <h3 className="text-[12px] font-bold tracking-[.05em] text-rz-muted uppercase">
                        {t('admin.policy_changes.history')}
                    </h3>
                    <ul className="mt-2 flex flex-col gap-1.5">
                        {board.history.map((change) => (
                            <li
                                key={change.id}
                                className="flex flex-wrap items-center gap-2 text-[12.5px] text-rz-ink"
                            >
                                <Chip
                                    tone={
                                        change.status === 'applied'
                                            ? 'green'
                                            : 'red'
                                    }
                                >
                                    {t(
                                        `admin.policy_changes.status.${change.status}`,
                                    )}
                                </Chip>
                                <span>
                                    {t('admin.policy_changes.history_row', {
                                        name: t(`admin.policy.${change.key}`),
                                        from: value(
                                            unitOf(change.key),
                                            change.from_value,
                                        ),
                                        to: value(
                                            unitOf(change.key),
                                            change.to_value,
                                        ),
                                        by: change.decisions
                                            .map((decision) => decision.by)
                                            .join(', '),
                                    })}
                                </span>
                                {change.decided_at && (
                                    <span className="text-rz-muted">
                                        {formatTimestamp(change.decided_at)}
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </Panel>
    );
}
