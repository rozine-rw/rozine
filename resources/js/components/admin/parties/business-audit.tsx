import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Chip, EXPLAIN } from '@/components/admin/ui';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { BusinessAudit, StaffViewer } from '@/types/admin';
import type { RouteAction } from '@/types/routing';

const STATUS_TONE = {
    operations: 'amber',
    offered: 'blue',
    accepted: 'green',
} as const;

type Command = 'flash' | 'redispatch';

/**
 * Why the command is needed, under the viewer's name. Each opening is one request, so a double or
 * delayed submit replays it; a redispatch also names the audit revision it answers.
 */
function AuditReasonForm({
    command,
    action,
    revision,
    viewer,
    onCancel,
}: {
    command: Command;
    action: RouteAction;
    revision: number | null;
    viewer: StaffViewer;
    onCancel: () => void;
}) {
    const { t } = useTranslation();
    const [requestId] = useState(() => crypto.randomUUID());
    const form = useForm<{
        reason: string;
        request_id: string;
        expected_revision?: number;
    }>({
        reason: '',
        request_id: requestId,
        ...(revision === null ? {} : { expected_revision: revision }),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(action.url, { preserveScroll: true, onSuccess: onCancel });
    };

    return (
        <form
            onSubmit={submit}
            aria-label={t(`admin.parties.audit.${command}.form`)}
            className="mt-3 rounded-[13px] border-[1.5px] border-[#1d9e75] bg-rz-surface p-4"
        >
            <p className={cn('text-[12.5px] leading-[1.55]', EXPLAIN)}>
                {t(`admin.parties.audit.${command}.form_body`)}
            </p>
            <label
                htmlFor={`audit-${command}-reason`}
                className="mt-2.5 block text-[11px] font-bold text-rz-slate"
            >
                {t('admin.parties.audit.field.reason')}
            </label>
            <textarea
                id={`audit-${command}-reason`}
                value={form.data.reason}
                onChange={(event) => form.setData('reason', event.target.value)}
                placeholder={t(`admin.parties.audit.${command}.placeholder`)}
                aria-invalid={form.errors.reason !== undefined || undefined}
                aria-describedby={`audit-${command}-reason-error`}
                className="mt-1 min-h-[70px] w-full resize-y rounded-[11px] border border-rz-hairline bg-[#f7f9fd] px-3 py-2.5 text-[13px] leading-[1.5] text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] dark:bg-rz-field"
            />
            <FieldError id={`audit-${command}-reason-error`}>
                {form.errors.reason}
            </FieldError>
            <p className="mt-2 flex items-center gap-1.5 text-[11.5px] text-rz-muted">
                <span className="size-1.5 shrink-0 rounded-full bg-[#1d9e75]" />
                {t('admin.stage.logged_as', {
                    name: viewer.name,
                    role: t(`admin.role.${viewer.role}`),
                })}
            </p>
            <div className="mt-3 flex gap-2.5">
                <button
                    type="button"
                    onClick={onCancel}
                    className="h-[40px] rounded-[11px] border border-rz-hairline bg-rz-surface px-4 text-[13px] font-semibold text-rz-slate"
                >
                    {t('admin.stage.cancel')}
                </button>
                <button
                    type="submit"
                    disabled={form.data.reason.trim() === '' || form.processing}
                    aria-busy={form.processing || undefined}
                    className="h-[40px] flex-1 rounded-[11px] bg-[#1d9e75] text-[13px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {t(`admin.parties.audit.${command}.cta`)}
                </button>
            </div>
        </form>
    );
}

/**
 * A Business's open audit for staff who manage audit assignments (#446): its kind and where it
 * stands, a Flash audit request when none is open (BR-48), and a redispatch while it waits on
 * Audit Operations for a partner.
 */
export function BusinessAuditSection({
    audit,
    viewer,
    section,
    box,
}: {
    audit: BusinessAudit;
    viewer: StaffViewer;
    section: string;
    box: string;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState<{
        command: Command;
        action: RouteAction;
        revision: number | null;
    } | null>(null);
    const engagement = audit.engagement;
    const start = (
        command: Command,
        action: RouteAction | null,
        revision: number | null,
    ) =>
        action !== null &&
        open === null && (
            <button
                type="button"
                onClick={() => setOpen({ command, action, revision })}
                className="mt-3 h-[38px] w-full rounded-[10px] bg-[#157357] text-[12.5px] font-bold text-white"
            >
                {t(`admin.parties.audit.${command}.open`)}
            </button>
        );

    return (
        <section aria-label={t('admin.parties.audit.title')} className="mb-5">
            <h3 className={section}>{t('admin.parties.audit.title')}</h3>
            <div className={cn(box, 'mt-2.5 px-4 py-[15px]')}>
                {engagement === null ? (
                    <>
                        <p
                            className={cn(
                                'text-[12.5px] leading-[1.5]',
                                EXPLAIN,
                            )}
                        >
                            {t('admin.parties.audit.none')}
                        </p>
                        {start('flash', audit.request_flash, null)}
                    </>
                ) : (
                    <>
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-[13px] text-rz-slate">
                                {t(
                                    `admin.parties.audit.kind.${engagement.kind}`,
                                )}
                            </span>
                            <Chip tone={STATUS_TONE[engagement.status]}>
                                {t(
                                    `admin.parties.audit.status.${engagement.status}`,
                                )}
                            </Chip>
                        </div>
                        {engagement.operations_reason !== null && (
                            <p
                                className={cn(
                                    'mt-2 text-[12.5px] leading-[1.5]',
                                    EXPLAIN,
                                )}
                            >
                                {t(
                                    `admin.parties.audit.reason.${engagement.operations_reason}`,
                                    { attempts: String(engagement.attempt) },
                                )}
                            </p>
                        )}
                        {start(
                            'redispatch',
                            audit.redispatch,
                            engagement.revision,
                        )}
                    </>
                )}
                {open !== null && (
                    <AuditReasonForm
                        command={open.command}
                        action={open.action}
                        revision={open.revision}
                        viewer={viewer}
                        onCancel={() => setOpen(null)}
                    />
                )}
            </div>
        </section>
    );
}
