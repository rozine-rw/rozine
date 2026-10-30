import { Link, useForm } from '@inertiajs/react';
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

type Command = 'flash' | 'redispatch' | 'close';

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

const FACTS = [
    'financial_interest',
    'current_role_tie',
    'family_or_business_conflict',
    'unresolved_conflict',
] as const;

type Partner = BusinessAudit['partners'][number];

/**
 * An independence review of one nearby Audit Partner against this Business, under the viewer's name:
 * the conflict facts checked, when any past role at the Business ended, what was checked and why.
 * Dispatch offers the partner this Business's audits only once one is recorded for the current mandate.
 */
function IndependenceForm({
    partner,
    action,
    viewer,
    onCancel,
}: {
    partner: Partner;
    action: RouteAction;
    viewer: StaffViewer;
    onCancel: () => void;
}) {
    const { t } = useTranslation();
    const [requestId] = useState(() => crypto.randomUUID());
    const form = useForm({
        financial_interest: false,
        current_role_tie: false,
        family_or_business_conflict: false,
        unresolved_conflict: false,
        role_tie_ended_on: '',
        reference: '',
        reason: '',
        request_id: requestId,
        expected_revision: partner.revision,
        checked_at: partner.checked_at,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            role_tie_ended_on:
                data.role_tie_ended_on === '' ? null : data.role_tie_ended_on,
        }));
        form.post(action.url, { preserveScroll: true, onSuccess: onCancel });
    };
    const field =
        'mt-1 w-full rounded-[11px] border border-rz-hairline bg-[#f7f9fd] px-3 py-2.5 text-[13px] text-rz-ink outline-none focus:border-rz-focus-border dark:bg-rz-field';

    return (
        <form
            onSubmit={submit}
            aria-label={t('admin.parties.audit.independence.form', {
                name: partner.name,
            })}
            className="mt-3 rounded-[13px] border-[1.5px] border-[#1d9e75] bg-rz-surface p-4"
        >
            <p className={cn('text-[12.5px] leading-[1.55]', EXPLAIN)}>
                {t('admin.parties.audit.independence.form_body')}
            </p>
            {FACTS.map((fact) => (
                <label
                    key={fact}
                    className="mt-2.5 flex items-start gap-2 text-[12.5px] text-rz-body"
                >
                    <input
                        type="checkbox"
                        checked={form.data[fact]}
                        onChange={(event) =>
                            form.setData(fact, event.target.checked)
                        }
                        className="mt-0.5"
                    />
                    {t(`admin.parties.audit.independence.fact.${fact}`)}
                </label>
            ))}
            <label className="mt-2.5 block text-[11px] font-bold text-rz-slate">
                {t('admin.parties.audit.independence.field.ended')}
                <input
                    type="date"
                    value={form.data.role_tie_ended_on}
                    onChange={(event) =>
                        form.setData('role_tie_ended_on', event.target.value)
                    }
                    className={field}
                />
            </label>
            <label className="mt-2.5 block text-[11px] font-bold text-rz-slate">
                {t('admin.parties.audit.independence.field.reference')}
                <input
                    value={form.data.reference}
                    onChange={(event) =>
                        form.setData('reference', event.target.value)
                    }
                    placeholder={t(
                        'admin.parties.audit.independence.placeholder.reference',
                    )}
                    className={field}
                />
            </label>
            <label className="mt-2.5 block text-[11px] font-bold text-rz-slate">
                {t('admin.parties.audit.field.reason')}
                <textarea
                    value={form.data.reason}
                    onChange={(event) =>
                        form.setData('reason', event.target.value)
                    }
                    aria-invalid={form.errors.reason !== undefined || undefined}
                    aria-describedby="audit-independence-reason-error"
                    className={cn('min-h-[60px] resize-y', field)}
                />
            </label>
            <FieldError id="audit-independence-reason-error">
                {form.errors.reason}
            </FieldError>
            <p className="mt-2 text-[11.5px] text-rz-muted">
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
                    disabled={
                        form.data.reference.trim() === '' ||
                        form.data.reason.trim() === '' ||
                        form.processing
                    }
                    aria-busy={form.processing || undefined}
                    className="h-[40px] flex-1 rounded-[11px] bg-[#1d9e75] text-[13px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {t('admin.parties.audit.independence.cta')}
                </button>
            </div>
        </form>
    );
}

/**
 * The Audit Partners whose verified office is within 30 km of this Business, whether each has been
 * reviewed as independent of it for the current mandate, and a review for staff on the console.
 */
function NearbyPartners({
    partners,
    viewer,
}: {
    partners: Partner[];
    viewer: StaffViewer;
}) {
    const { t } = useTranslation();
    const [reviewing, setReviewing] = useState<string | null>(null);

    return (
        <div className="mt-4 border-t border-[#e3e9f3] pt-3 dark:border-rz-border">
            <h4 className="text-[11.5px] font-bold text-rz-slate">
                {t('admin.parties.audit.partners.title')}
            </h4>
            <ul className="mt-1.5">
                {partners.map((partner) => (
                    <li key={partner.party_id} className="py-1.5">
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-[13px] text-rz-ink">
                                {partner.name}
                            </span>
                            <Chip tone={partner.reviewed ? 'green' : 'amber'}>
                                {t(
                                    partner.reviewed
                                        ? 'admin.parties.audit.partners.reviewed'
                                        : 'admin.parties.audit.partners.unreviewed',
                                )}
                            </Chip>
                        </div>
                        {partner.review !== null &&
                            reviewing !== partner.party_id && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        setReviewing(partner.party_id)
                                    }
                                    className="mt-1 text-[12px] font-semibold text-[#157357] underline"
                                >
                                    {t(
                                        'admin.parties.audit.independence.open',
                                        {
                                            name: partner.name,
                                        },
                                    )}
                                </button>
                            )}
                        {partner.review !== null &&
                            reviewing === partner.party_id && (
                                <IndependenceForm
                                    partner={partner}
                                    action={partner.review}
                                    viewer={viewer}
                                    onCancel={() => setReviewing(null)}
                                />
                            )}
                    </li>
                ))}
            </ul>
        </div>
    );
}

/**
 * A Business's open audit for staff who manage audit assignments (#446): its kind and where it
 * stands, a Flash audit request when none is open (BR-48), and, while it waits on Audit Operations,
 * whether a partner can take it now or why not with where to fix that, and the redispatch or close
 * the server allows; then the partners within 30 km and their independence reviews.
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
                className={cn(
                    'mt-3 h-[38px] w-full rounded-[10px] text-[12.5px] font-bold',
                    command === 'close'
                        ? 'border border-[#fdeaea] bg-rz-surface text-[#b3352a] dark:border-[rgba(255,107,111,.3)] dark:text-[#ff6b6f]'
                        : 'bg-[#157357] text-white',
                )}
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
                            {t(
                                audit.closed
                                    ? 'admin.parties.audit.closed_last'
                                    : 'admin.parties.audit.none',
                            )}
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
                        {audit.remedy !== null && (
                            <Link
                                href={audit.remedy}
                                className="mt-1.5 inline-block text-[12.5px] font-semibold text-[#157357] underline"
                            >
                                {t('admin.parties.audit.remedy')}
                            </Link>
                        )}
                        {start(
                            'redispatch',
                            audit.redispatch,
                            engagement.revision,
                        )}
                        {start('close', audit.close, engagement.revision)}
                    </>
                )}
                {audit.partners.length > 0 && (
                    <NearbyPartners partners={audit.partners} viewer={viewer} />
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
