import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import {
    FORM_PRIMARY,
    SECONDARY_BUTTON,
} from '@/components/auditor/sheets/reason-sheet';
import { Eyebrow } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type { Accreditation, AuditorIdentity } from '@/types/auditor';

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

/** The renewal request (design L808–828); Rozine staff clear it against the ICPAR register. */
function RenewalForm({
    licence,
    action,
    onCancel,
}: {
    licence: string;
    action: RouteAction;
    onCancel: () => void;
}) {
    const { t } = useTranslation();
    const form = useForm<{
        licence: string;
        expires_on: string;
        certificate: File | null;
    }>({ licence, expires_on: '', certificate: null });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(action.url, { forceFormData: true, preserveScroll: true });
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
                error={form.errors.licence}
            >
                <input
                    id="auditor-licence"
                    value={form.data.licence}
                    maxLength={16}
                    placeholder={t('auditor.accreditation.licence_placeholder')}
                    onChange={(event) =>
                        form.setData('licence', event.target.value)
                    }
                    className={INPUT}
                />
            </Field>
            <Field
                id="auditor-expiry"
                label={t('auditor.accreditation.expiry_label')}
                error={form.errors.expires_on}
            >
                <input
                    id="auditor-expiry"
                    type="date"
                    value={form.data.expires_on}
                    onChange={(event) =>
                        form.setData('expires_on', event.target.value)
                    }
                    className={INPUT}
                />
            </Field>
            <Field
                id="auditor-certificate"
                label={t('auditor.accreditation.certificate_label')}
                error={form.errors.certificate}
            >
                <label
                    htmlFor="auditor-certificate"
                    className="flex h-[104px] cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-[#d8e0ec] text-center text-[12px] font-semibold text-rz-secondary dark:border-rz-border"
                >
                    {form.data.certificate?.name ??
                        t('auditor.accreditation.certificate_drop')}
                </label>
                <input
                    id="auditor-certificate"
                    type="file"
                    accept="application/pdf,image/png,image/jpeg"
                    onChange={(event) =>
                        form.setData(
                            'certificate',
                            event.target.files?.[0] ?? null,
                        )
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
                    disabled={form.processing}
                    className={cn(FORM_PRIMARY, 'flex-[2]')}
                >
                    {t('auditor.accreditation.submit')}
                </button>
            </div>
        </form>
    );
}

/**
 * Accreditation status and renewal (design L784–829). The standing, expiry and badge are the
 * server's record of the manual ICPAR check; the bar only draws the days left against a year.
 * There is no first-time accreditation screen in the design — that remains an open question.
 */
export function AccreditationSection({
    auditor,
    accreditation,
}: {
    auditor: AuditorIdentity;
    accreditation: Accreditation;
}) {
    const { t, locale } = useTranslation();
    const [editing, setEditing] = useState(false);
    const [withdrawing, setWithdrawing] = useState(false);
    const { renewal, days_left: days } = accreditation;
    const pending = renewal.status === 'pending';
    const expired = accreditation.status === 'expired';
    const suspended = accreditation.status === 'suspended';
    const badge = pending
        ? {
              label: t('auditor.accreditation.badge.pending'),
              tone: 'bg-rz-accent-soft text-rz-accent-app-text',
          }
        : expired || suspended
          ? {
                label: t(`auditor.accreditation.badge.${accreditation.status}`),
                tone: 'bg-[rgba(192,57,43,.10)] text-rz-danger-text',
            }
          : {
                label: t('auditor.accreditation.badge.active'),
                tone: 'bg-[rgba(29,158,117,.10)] text-rz-positive',
            };
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
        <section>
            <Eyebrow as="h2">{t('auditor.accreditation.title')}</Eyebrow>
            <div className="mt-2.5 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                <div className="flex items-center gap-[13px]">
                    <span
                        className={cn(
                            'flex size-11 shrink-0 items-center justify-center rounded-xl',
                            expired || suspended
                                ? 'bg-[rgba(192,57,43,.10)] text-rz-danger-text'
                                : 'bg-[rgba(29,158,117,.10)] text-rz-positive',
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
                    <div className="min-w-0 flex-1">
                        <p className="text-[14px] font-bold text-rz-ink">
                            {auditor.accreditation}
                        </p>
                        <p className="mt-0.5 text-[11.5px] text-rz-secondary">
                            {t('auditor.accreditation.licence_line', {
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
                        ? t('auditor.accreditation.expired_ago', {
                              count: -days,
                          })
                        : t('auditor.accreditation.days_left', { count: days })}
                </p>
            </div>

            {renewal.status === 'pending' && (
                <div className="mt-2.5 flex items-center gap-3 rounded-2xl border border-[#f2dfba] bg-rz-surface px-[15px] py-3.5 dark:border-[rgba(240,160,96,.3)]">
                    <div className="min-w-0 flex-1">
                        <p className="text-[12.5px] font-bold text-rz-ink">
                            {t('auditor.accreditation.pending_title')}
                        </p>
                        <p className="mt-0.5 text-[11.5px] leading-[1.5] text-rz-secondary">
                            {t('auditor.accreditation.pending_line', {
                                id: renewal.id,
                                licence: renewal.licence,
                                date: formatDate(renewal.expires_on, locale),
                                submitted: formatDate(
                                    renewal.submitted_on,
                                    locale,
                                ),
                            })}
                        </p>
                    </div>
                    <button
                        type="button"
                        disabled={withdrawing}
                        onClick={() =>
                            router.post(
                                renewal.withdraw.url,
                                {},
                                {
                                    preserveScroll: true,
                                    onStart: () => setWithdrawing(true),
                                    onFinish: () => setWithdrawing(false),
                                },
                            )
                        }
                        className={cn(
                            SECONDARY_BUTTON,
                            'h-[34px] shrink-0 text-[12px]',
                        )}
                    >
                        {t('auditor.accreditation.withdraw')}
                    </button>
                </div>
            )}
            {renewal.status === 'rejected' && (
                <div className="mt-2.5 rounded-2xl border border-[#f5cfcb] bg-rz-surface px-[15px] py-3.5 dark:border-[rgba(255,107,111,.3)]">
                    <p className="text-[12.5px] font-bold text-rz-ink">
                        {t('auditor.accreditation.rejected_title')}
                    </p>
                    <p className="mt-0.5 text-[11.5px] leading-[1.5] text-[#8a5a55] dark:text-rz-danger-text">
                        {t('auditor.accreditation.rejected_line', {
                            id: renewal.id,
                            reason: renewal.reason,
                        })}
                    </p>
                </div>
            )}
            {renewal.status !== 'pending' &&
                (editing ? (
                    <RenewalForm
                        licence={accreditation.licence}
                        action={renewal.submit}
                        onCancel={() => setEditing(false)}
                    />
                ) : (
                    <button
                        type="button"
                        onClick={() => setEditing(true)}
                        className="mt-2.5 h-11 w-full rounded-xl bg-rz-surface px-4 text-[13px] font-bold text-rz-ink"
                    >
                        {t('auditor.accreditation.renew')}
                    </button>
                ))}
        </section>
    );
}
