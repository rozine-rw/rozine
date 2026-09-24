import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { EXPLAIN } from '@/components/admin/ui';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type { StaffViewer } from '@/types/admin';

export type StageTone =
    | 'green'
    | 'red'
    | 'purple'
    | 'orange'
    | 'blue'
    | 'amber';

const TONES: Record<StageTone, { edge: string; fill: string }> = {
    green: { edge: 'border-[#1d9e75]', fill: 'bg-[#1d9e75]' },
    red: { edge: 'border-[#e5484d]', fill: 'bg-[#e5484d]' },
    purple: { edge: 'border-[#7c3aed]', fill: 'bg-[#7c3aed]' },
    orange: { edge: 'border-[#c2410c]', fill: 'bg-[#c2410c]' },
    blue: {
        edge: 'border-rz-accent-fill',
        fill: 'bg-rz-accent-fill',
    },
    amber: { edge: 'border-[#c2661f]', fill: 'bg-[#c2661f]' },
};

type ReasonStageProps = {
    title: string;
    body: string;
    cta: string;
    placeholder: string;
    tone: StageTone;
    action: RouteAction;
    viewer: StaffViewer;
    onCancel: () => void;
    /** Extra facts the server needs with the reason, e.g. the record revision. */
    payload?: Record<string, string | number>;
};

/**
 * The reason-required confirmation stage (design T2997–3009). Every privileged command in the
 * console goes through it: nothing commits without a written reason, and the stage says whose
 * name the audit trail will carry (MVP-ADMIN-AC-01). The confirm button stays inactive until a
 * reason is written; the server rechecks both.
 */
export function ReasonStage({
    title,
    body,
    cta,
    placeholder,
    tone,
    action,
    viewer,
    onCancel,
    payload = {},
}: ReasonStageProps) {
    const { t } = useTranslation();
    const form = useForm({ reason: '', ...payload });
    const reason = String(form.data.reason);
    const empty = reason.trim() === '';
    const fieldId = `reason-${action.url.replace(/[^a-z0-9]+/gi, '-')}`;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(action.url, { preserveScroll: true, onSuccess: onCancel });
    };

    return (
        <form
            onSubmit={submit}
            aria-label={title}
            className={cn(
                'mt-[18px] rounded-[15px] border-[1.5px] bg-rz-surface p-[18px]',
                TONES[tone].edge,
            )}
        >
            <div className="text-[15px] font-bold text-rz-ink">{title}</div>
            <p className={cn('mt-[5px] text-[12.5px] leading-[1.55]', EXPLAIN)}>
                {body}
            </p>
            <label htmlFor={fieldId} className="sr-only">
                {t('admin.stage.reason_label')}
            </label>
            <textarea
                id={fieldId}
                value={reason}
                onChange={(event) =>
                    form.setData((data) => ({
                        ...data,
                        reason: event.target.value,
                    }))
                }
                required
                placeholder={placeholder}
                aria-invalid={form.errors.reason !== undefined || undefined}
                aria-describedby={`${fieldId}-who`}
                className="mt-3 min-h-[82px] w-full resize-y rounded-[11px] border border-rz-hairline bg-[#f7f9fd] px-[13px] py-[11px] text-[13px] leading-[1.5] text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] dark:bg-rz-field"
            />
            <FieldError id={`${fieldId}-error`}>
                {form.errors.reason}
            </FieldError>
            <p
                id={`${fieldId}-who`}
                className="mt-2 flex items-center gap-1.5 text-[11.5px] text-rz-muted"
            >
                <span className="size-1.5 shrink-0 rounded-full bg-[#1d9e75]" />
                {t('admin.stage.logged_as', {
                    name: viewer.name,
                    role: t(`admin.role.${viewer.role}`),
                })}
            </p>
            <div className="mt-3.5 flex gap-2.5">
                <button
                    type="button"
                    onClick={onCancel}
                    className="h-[42px] rounded-[11px] border border-rz-hairline bg-rz-surface px-[18px] text-[13px] font-semibold text-rz-slate"
                >
                    {t('admin.stage.cancel')}
                </button>
                <button
                    type="submit"
                    disabled={empty || form.processing}
                    aria-busy={form.processing || undefined}
                    className={cn(
                        'h-[42px] flex-1 rounded-[11px] text-[13.5px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50',
                        TONES[tone].fill,
                    )}
                >
                    {cta}
                </button>
            </div>
        </form>
    );
}
