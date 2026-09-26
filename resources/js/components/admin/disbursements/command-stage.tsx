import { useId, useState } from 'react';
import type { FormEvent } from 'react';
import { EXPLAIN } from '@/components/admin/ui';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { StaffViewer } from '@/types/admin';

export type CommandTone = 'green' | 'red' | 'blue' | 'amber';

const TONES: Record<CommandTone, { edge: string; fill: string }> = {
    green: { edge: 'border-[#1d9e75]', fill: 'bg-[#1d9e75]' },
    red: { edge: 'border-[#e5484d]', fill: 'bg-[#e5484d]' },
    blue: { edge: 'border-rz-accent-fill', fill: 'bg-rz-accent-fill' },
    amber: { edge: 'border-[#c2661f]', fill: 'bg-[#c2661f]' },
};

/**
 * The reason-required stage for a checkpoint 3 staff command. It looks like the console's
 * `ReasonStage`, but it sends through the shared operation command (`useC3Command`) instead of an
 * Inertia form post: nothing commits without a written reason, the stage says whose name the
 * trail will carry (the role is a label, never a permission), and it stays locked while a command
 * is out or its outcome is still unknown.
 */
export function CommandStage({
    title,
    body,
    cta,
    placeholder,
    tone,
    viewer,
    busy,
    locked,
    error,
    onSubmit,
    onCancel,
}: {
    title: string;
    body: string;
    cta: string;
    placeholder: string;
    tone: CommandTone;
    viewer: StaffViewer;
    /** A command is in flight. */
    busy: boolean;
    /** A command's outcome is still unknown: nothing new may be sent until it is. */
    locked: boolean;
    /** The server's field error on the reason, if any. */
    error: string | undefined;
    onSubmit: (reason: string) => void;
    onCancel: () => void;
}) {
    const { t } = useTranslation();
    const [reason, setReason] = useState('');
    const fieldId = `reason-${useId().replace(/[^a-z0-9]+/gi, '')}`;
    const empty = reason.trim() === '';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        onSubmit(reason);
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
                onChange={(event) => setReason(event.target.value)}
                required
                placeholder={placeholder}
                aria-invalid={error !== undefined || undefined}
                aria-describedby={`${fieldId}-who`}
                className="mt-3 min-h-[82px] w-full resize-y rounded-[11px] border border-rz-hairline bg-[#f7f9fd] px-[13px] py-[11px] text-[13px] leading-[1.5] text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] dark:bg-rz-field"
            />
            <FieldError id={`${fieldId}-error`}>{error}</FieldError>
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
                    disabled={empty || busy || locked}
                    aria-busy={busy || undefined}
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
