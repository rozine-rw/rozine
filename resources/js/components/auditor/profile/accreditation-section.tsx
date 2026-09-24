import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { useAuditorCommands } from '@/components/auditor/commands';
import {
    FORM_PRIMARY,
    SECONDARY_BUTTON,
} from '@/components/auditor/sheets/reason-sheet';
import { Eyebrow } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    Accreditation,
    AuditorAllowedAction,
    AuditorIdentity,
} from '@/types/auditor';

/** Days of standing the bar measures against (design L3245). */
const WINDOW_DAYS = 365;

const INPUT =
    'h-[42px] w-full rounded-xl border border-rz-divider bg-[#f8fafc] px-[13px] text-[13.5px] text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border dark:bg-rz-surface-sunken';

function Field({
    id,
    label,
    error,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div>
            <label
                htmlFor={id}
                className="mb-[5px] block text-[10px] font-bold tracking-[.04em] text-rz-slate uppercase"
            >
                {label}
            </label>
            {children}
            {error && (
                <p className="mt-1 text-[11.5px] text-rz-danger-text">
                    {error}
                </p>
            )}
        </div>
    );
}

type SubmissionFields = {
    licence: string;
    expires_on: string;
    certificate: File | null;
};

/**
 * The accreditation request (design L808–828): a first-time submission or a renewal, with the
 * certificate as evidence. Rozine staff clear it against the ICPAR register; submitting confers
 * nothing until they record that check.
 */
function SubmissionForm({
    licence,
    firstTime,
    onSubmit,
    onCancel,
}: {
    licence: string;
    firstTime: boolean;
    onSubmit: (fields: SubmissionFields) => void;
    onCancel: () => void;
}) {
    const { t } = useTranslation();
    const center = useAuditorCommands();
    const [fields, setFields] = useState<SubmissionFields>({
        licence,
        expires_on: '',
        certificate: null,
    });
    const set = <K extends keyof SubmissionFields>(
        key: K,
        value: SubmissionFields[K],
    ) => setFields((current) => ({ ...current, [key]: value }));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        onSubmit(fields);
    };

    return (
        <form
            onSubmit={submit}
            noValidate
            className="mt-2.5 flex flex-col gap-3 rounded-2xl border border-rz-border bg-rz-surface p-[15px]"
        >
            <Field
                id="auditor-licence"
                label={t('auditor.accreditation.licence_label')}
                error={center.errors.licence}
            >
                <input
                    id="auditor-licence"
                    value={fields.licence}
                    maxLength={32}
                    placeholder={t('auditor.accreditation.licence_placeholder')}
                    onChange={(event) => set('licence', event.target.value)}
                    className={INPUT}
                />
            </Field>
            <Field
                id="auditor-expiry"
                label={
                    firstTime
                        ? t('auditor.accreditation.expiry_label_first')
                        : t('auditor.accreditation.expiry_label')
                }
                error={center.errors.expires_on}
            >
                <input
                    id="auditor-expiry"
                    type="date"
                    value={fields.expires_on}
                    onChange={(event) => set('expires_on', event.target.value)}
                    className={INPUT}
                />
            </Field>
            <Field
                id="auditor-certificate"
                label={t('auditor.accreditation.certificate_label')}
                error={center.errors.certificate}
            >
                <label
                    htmlFor="auditor-certificate"
                    className="flex h-[104px] cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-[#d8e0ec] text-center text-[12px] font-semibold text-rz-secondary dark:border-rz-border"
                >
                    {fields.certificate?.name ??
                        t('auditor.accreditation.certificate_drop')}
                </label>
                <input
                    id="auditor-certificate"
                    type="file"
                    accept="application/pdf,image/png,image/jpeg"
                    onChange={(event) =>
                        set('certificate', event.target.files?.[0] ?? null)
                    }
                    className="sr-only"
                />
            </Field>
            <div className="flex gap-[9px]">
                <button
                    type="button"
                    onClick={onCancel}
                    className={cn(SECONDARY_BUTTON, 'flex-1')}
                >
                    {t('auditor.sheet.cancel')}
                </button>
                <button
                    type="submit"
                    disabled={!center.idle}
                    aria-busy={center.busy || undefined}
                    className={cn(FORM_PRIMARY, 'flex-[2]')}
                >
                    {t('auditor.accreditation.submit')}
                </button>
            </div>
        </form>
    );
}

/** The shield in the status card; red once standing has lapsed. */
function Shield({ tone }: { tone: string }) {
    return (
        <span
            className={cn(
                'flex size-11 shrink-0 items-center justify-center rounded-xl',
                tone,
            )}
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden
                className="size-[22px]"
            >
                <path
                    d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    strokeLinejoin="round"
                />
                <path
                    d="M9 12l2 2 4-4.5"
                    stroke="currentColor"
                    strokeWidth="1.9"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        </span>
    );
}

const GREEN = 'bg-[rgba(29,158,117,.10)] text-rz-positive';
const RED = 'bg-[rgba(192,57,43,.10)] text-rz-danger-text';
const NEUTRAL = 'bg-rz-page text-rz-slate dark:bg-rz-surface-muted';

/**
 * Accreditation status and submissions (design L784–829; auditor-filing-v1 point 3). The standing,
 * expiry and badge are the server's record of the manual ICPAR check; the bar only draws the days
 * left against a year. A first-time partner has no licence on record (`status: 'none'`), and the
 * page says plainly that submitting confers no standing until staff record the ICPAR check. A
 * submission or withdrawal is offered only when `allowed_actions` lists it, and a pending
 * submission shows the certificate's evidence identity.
 */
export function AccreditationSection({
    auditor,
    accreditation,
    allowed,
    actions,
}: {
    auditor: AuditorIdentity;
    accreditation: Accreditation;
    allowed: (action: AuditorAllowedAction) => boolean;
    actions: { submit: RouteAction; withdraw: RouteAction };
}) {
    const { t, locale } = useTranslation();
    const center = useAuditorCommands();
    const [editing, setEditing] = useState(false);
    const { submission } = accreditation;
    const firstTime = accreditation.status === 'none';
    const pending = submission.status === 'pending';
    const lapsed =
        accreditation.status === 'expired' ||
        accreditation.status === 'suspended';
    const submitAs: AuditorAllowedAction | null = allowed(
        'accreditation.submit',
    )
        ? 'accreditation.submit'
        : allowed('accreditation.renew')
          ? 'accreditation.renew'
          : null;

    let badge: { label: string; tone: string };

    if (pending) {
        badge = {
            label: firstTime
                ? t('auditor.accreditation.badge.first_pending')
                : t('auditor.accreditation.badge.pending'),
            tone: 'bg-rz-accent-soft text-rz-accent-app-text',
        };
    } else if (firstTime) {
        badge = { label: t('auditor.accreditation.badge.none'), tone: NEUTRAL };
    } else if (lapsed) {
        badge = {
            label: t(`auditor.accreditation.badge.${accreditation.status}`),
            tone: RED,
        };
    } else {
        badge = { label: t('auditor.accreditation.badge.active'), tone: GREEN };
    }

    const submit = (name: AuditorAllowedAction, fields: SubmissionFields) =>
        center.send(
            {
                name,
                business: auditor.name,
                route: actions.submit,
                payload: {
                    ...fields,
                    expected_revision: accreditation.revision,
                },
            },
            { onCompleted: () => setEditing(false) },
        );

    const withdraw = (id: string) =>
        center.send({
            name: 'accreditation.withdraw',
            business: auditor.name,
            route: actions.withdraw,
            payload: {
                submission_id: id,
                expected_revision: accreditation.revision,
            },
        });

    return (
        <section>
            <Eyebrow as="h2">{t('auditor.accreditation.title')}</Eyebrow>
            <div className="mt-2.5 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                <div className="flex items-center gap-[13px]">
                    <Shield tone={firstTime ? NEUTRAL : lapsed ? RED : GREEN} />
                    <div className="min-w-0 flex-1">
                        <p className="text-[14px] font-bold text-rz-ink">
                            {auditor.accreditation}
                        </p>
                        <p className="mt-0.5 text-[11.5px] text-rz-secondary">
                            {accreditation.status === 'none'
                                ? t('auditor.accreditation.none_line')
                                : t('auditor.accreditation.licence_line', {
                                      licence: accreditation.licence,
                                      date: formatDate(
                                          accreditation.expires_on,
                                          locale,
                                      ),
                                  })}
                        </p>
                    </div>
                    <span
                        className={cn(
                            'shrink-0 rounded-[10px] px-[9px] py-1 text-[10px] font-bold uppercase',
                            badge.tone,
                        )}
                    >
                        {badge.label}
                    </span>
                </div>
                {accreditation.status === 'none' ? (
                    <p className="mt-3 text-[11.5px] leading-[1.5] text-rz-secondary">
                        {t('auditor.accreditation.none_body')}
                    </p>
                ) : (
                    <StandingBar days={accreditation.days_left} />
                )}
            </div>

            {submission.status === 'pending' && (
                <div className="mt-2.5 flex items-center gap-3 rounded-2xl border border-[#f2dfba] bg-rz-surface px-[15px] py-3.5 dark:border-[rgba(240,160,96,.3)]">
                    <div className="min-w-0 flex-1">
                        <p className="text-[12.5px] font-bold text-rz-ink">
                            {firstTime
                                ? t('auditor.accreditation.first_pending_title')
                                : t('auditor.accreditation.pending_title')}
                        </p>
                        <p className="mt-0.5 text-[11.5px] leading-[1.5] text-rz-secondary">
                            {t('auditor.accreditation.pending_line', {
                                id: submission.id,
                                licence: submission.licence,
                                date: formatDate(submission.expires_on, locale),
                                submitted: formatDate(
                                    submission.submitted_on,
                                    locale,
                                ),
                            })}
                        </p>
                        <p className="mt-1 truncate text-[10.5px] text-rz-secondary tabular-nums">
                            {t('auditor.accreditation.evidence_line', {
                                id: submission.evidence.evidence_id,
                                digest: submission.evidence.sha256.slice(0, 12),
                            })}
                        </p>
                    </div>
                    {allowed('accreditation.withdraw') && (
                        <button
                            type="button"
                            disabled={!center.idle}
                            onClick={() => withdraw(submission.id)}
                            className={cn(
                                SECONDARY_BUTTON,
                                'h-[34px] shrink-0 text-[12px]',
                            )}
                        >
                            {t('auditor.accreditation.withdraw')}
                        </button>
                    )}
                </div>
            )}
            {submission.status === 'rejected' && (
                <div className="mt-2.5 rounded-2xl border border-[#f5cfcb] bg-rz-surface px-[15px] py-3.5 dark:border-[rgba(255,107,111,.3)]">
                    <p className="text-[12.5px] font-bold text-rz-ink">
                        {t('auditor.accreditation.rejected_title')}
                    </p>
                    <p className="mt-0.5 text-[11.5px] leading-[1.5] text-[#8a5a55] dark:text-rz-danger-text">
                        {t('auditor.accreditation.rejected_line', {
                            id: submission.id,
                            reason: submission.reason,
                        })}
                    </p>
                </div>
            )}
            {submitAs !== null &&
                !pending &&
                (editing ? (
                    <SubmissionForm
                        licence={accreditation.licence ?? ''}
                        firstTime={firstTime}
                        onSubmit={(fields) => submit(submitAs, fields)}
                        onCancel={() => setEditing(false)}
                    />
                ) : (
                    <button
                        type="button"
                        onClick={() => setEditing(true)}
                        className={cn(
                            'mt-2.5 h-11 w-full rounded-xl px-4 text-[13px] font-bold',
                            firstTime
                                ? 'bg-rz-accent-fill text-white'
                                : 'bg-rz-surface text-rz-ink',
                        )}
                    >
                        {firstTime
                            ? t('auditor.accreditation.first_submit')
                            : t('auditor.accreditation.renew')}
                    </button>
                ))}
        </section>
    );
}

/** Days of standing left against a year (design L3245), from the server's count. */
function StandingBar({ days }: { days: number }) {
    const { t } = useTranslation();
    const bar =
        days < 60
            ? 'bg-[#c0392b]'
            : days < 180
              ? 'bg-[#c2661f]'
              : 'bg-[#17795a]';
    const width = Math.max(
        0,
        Math.min(100, Math.round((days / WINDOW_DAYS) * 100)),
    );

    return (
        <>
            <div
                role="progressbar"
                aria-label={t('auditor.accreditation.standing')}
                aria-valuemin={0}
                aria-valuemax={WINDOW_DAYS}
                aria-valuenow={Math.max(0, days)}
                className="mt-3 h-1.5 overflow-hidden rounded-[4px] bg-[#eef2f9] dark:bg-rz-surface-muted"
            >
                <div
                    data-testid="accreditation-bar"
                    className={cn('h-full rounded-[4px]', bar)}
                    style={{ width: `${width}%` }}
                />
            </div>
            <p className="mt-1.5 text-[11px] text-rz-secondary">
                {days < 0
                    ? t('auditor.accreditation.expired_ago', { count: -days })
                    : t('auditor.accreditation.days_left', { count: days })}
            </p>
        </>
    );
}
