import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { EXPLAIN } from '@/components/admin/ui';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';

const ROLES = [
    'analyst',
    'approver',
    'treasury',
    'compliance',
    'superadmin',
] as const;

const FIELD =
    'w-full rounded-[11px] border border-rz-hairline bg-[#f7f9fd] px-[13px] py-[10px] text-[13px] text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] dark:bg-rz-field';

/**
 * "+ Invite operator": a superadmin gives an existing dedicated account staff access with one role
 * and a reason (ADM-R01/R02). The server refuses an account without a verified email and confirmed
 * two-factor sign-in, so the operator signs up and sets up two-factor first.
 */
export function InviteOperator({ action }: { action: RouteAction }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        request_id: crypto.randomUUID(),
        email: '',
        role: 'analyst' as (typeof ROLES)[number],
        reason: '',
    });

    const close = () => {
        form.reset();
        form.setData('request_id', crypto.randomUUID());
        setOpen(false);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(action.url, { preserveScroll: true, onSuccess: close });
    };

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="h-[34px] shrink-0 rounded-[9px] bg-[#1e3aff] px-3.5 text-[12.5px] font-bold text-white"
            >
                {t('admin.staff.invite.open')}
            </button>
        );
    }

    return (
        <form
            onSubmit={submit}
            aria-label={t('admin.staff.invite.title')}
            className="mb-4 w-full rounded-[15px] border-[1.5px] border-[#c9d6ff] bg-rz-surface p-[18px] dark:border-rz-border"
        >
            <div className="text-[15px] font-bold text-rz-ink">
                {t('admin.staff.invite.title')}
            </div>
            <p className={cn('mt-[5px] text-[12.5px] leading-[1.55]', EXPLAIN)}>
                {t('admin.staff.invite.body')}
            </p>
            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                <label className="block">
                    <span className="text-[11.5px] font-semibold text-rz-muted">
                        {t('admin.staff.invite.email')}
                    </span>
                    <input
                        type="email"
                        required
                        value={form.data.email}
                        onChange={(event) =>
                            form.setData('email', event.target.value)
                        }
                        aria-invalid={
                            form.errors.email !== undefined || undefined
                        }
                        aria-describedby="invite-email-error"
                        className={cn('mt-1', FIELD)}
                    />
                    <FieldError id="invite-email-error">
                        {form.errors.email}
                    </FieldError>
                </label>
                <label className="block">
                    <span className="text-[11.5px] font-semibold text-rz-muted">
                        {t('admin.staff.invite.role')}
                    </span>
                    <select
                        value={form.data.role}
                        onChange={(event) =>
                            form.setData(
                                'role',
                                event.target.value as (typeof ROLES)[number],
                            )
                        }
                        aria-describedby="invite-role-error"
                        className={cn('mt-1', FIELD)}
                    >
                        {ROLES.map((role) => (
                            <option key={role} value={role}>
                                {t(`admin.role.${role}`)}
                            </option>
                        ))}
                    </select>
                    <FieldError id="invite-role-error">
                        {form.errors.role}
                    </FieldError>
                </label>
            </div>
            <label className="mt-3 block">
                <span className="text-[11.5px] font-semibold text-rz-muted">
                    {t('admin.stage.reason_label')}
                </span>
                <textarea
                    required
                    value={form.data.reason}
                    onChange={(event) =>
                        form.setData('reason', event.target.value)
                    }
                    aria-invalid={form.errors.reason !== undefined || undefined}
                    aria-describedby="invite-reason-error"
                    className={cn('mt-1 min-h-[72px] resize-y', FIELD)}
                />
                <FieldError id="invite-reason-error">
                    {form.errors.reason}
                </FieldError>
            </label>
            <div className="mt-3.5 flex justify-end gap-2">
                <button
                    type="button"
                    onClick={close}
                    className="rounded-[10px] border border-rz-hairline px-3.5 py-2 text-[12.5px] font-semibold text-rz-body"
                >
                    {t('admin.staff.invite.cancel')}
                </button>
                <button
                    type="submit"
                    disabled={
                        form.processing ||
                        form.data.reason.trim() === '' ||
                        form.data.email.trim() === ''
                    }
                    className="rounded-[10px] bg-[#1e3aff] px-3.5 py-2 text-[12.5px] font-bold text-white disabled:opacity-50"
                >
                    {t('admin.staff.invite.submit')}
                </button>
            </div>
        </form>
    );
}
