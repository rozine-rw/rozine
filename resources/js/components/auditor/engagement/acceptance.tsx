import { useId, useState } from 'react';
import { shortHash } from '@/components/auditor/engagement/terms-document';
import { CARD, PRIMARY, Tick } from '@/components/auditor/ui';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { EngagementAcceptance, EngagementRelease } from '@/types/auditor';

/**
 * The explicit acceptance: a checkbox of its own, unticked when the page opens, and Accept, which
 * stays disabled until it is ticked. The page keys this form by release, so a replacement release
 * starts unticked again and has to be read afresh. A recorded "acceptance required" refusal shows
 * here, beside the box.
 */
export function AcceptForm({
    busy,
    locked,
    acceptanceRequired,
    onAccept,
}: {
    /** The acceptance is on its way. */
    busy: boolean;
    /** An earlier acceptance is in flight or its outcome is not yet known. */
    locked: boolean;
    /** The server recorded that the box was not ticked. */
    acceptanceRequired: boolean;
    onAccept: () => void;
}) {
    const { t } = useTranslation();
    const [checked, setChecked] = useState(false);
    const errorId = useId();

    return (
        <div className={cn(CARD, 'mt-3.5 p-[15px]')}>
            <label className="flex cursor-pointer items-start gap-2.5">
                {/* The native checkbox is the visible box, so pointer, keyboard and assistive technology all reach the same control. */}
                <span className="relative mt-px flex size-5 shrink-0">
                    <input
                        type="checkbox"
                        checked={checked}
                        onChange={(event) => setChecked(event.target.checked)}
                        aria-invalid={acceptanceRequired || undefined}
                        aria-describedby={
                            acceptanceRequired ? errorId : undefined
                        }
                        className="size-5 cursor-pointer appearance-none rounded-[7px] border-2 border-[#d3dae6] bg-rz-surface outline-none checked:border-rz-accent-fill checked:bg-rz-accent-fill focus-visible:ring-2 focus-visible:ring-rz-focus-border dark:border-rz-border"
                    />
                    {checked && (
                        <Tick className="pointer-events-none absolute inset-0 m-auto size-3.5 text-white" />
                    )}
                </span>
                <span className="text-[13px] leading-[1.5] text-rz-ink">
                    {t('auditor.engagement.accept_label')}
                </span>
            </label>
            <FieldError id={errorId}>
                {acceptanceRequired
                    ? t('auditor.engagement.acceptance_required')
                    : undefined}
            </FieldError>
            <button
                type="button"
                disabled={!checked || busy || locked}
                aria-busy={busy || undefined}
                onClick={onAccept}
                className={cn(
                    PRIMARY,
                    'mt-3.5 flex h-[50px] w-full items-center justify-center rounded-2xl text-[15px]',
                )}
            >
                {busy
                    ? t('auditor.engagement.accepting')
                    : t('auditor.engagement.accept')}
            </button>
        </div>
    );
}

/** "You accepted version X on DATE", from this partner's retained acceptance receipt. */
export function AcceptedCard({
    release,
    acceptance,
}: {
    release: EngagementRelease;
    acceptance: EngagementAcceptance;
}) {
    const { t, locale } = useTranslation();

    return (
        <div
            role="status"
            className={cn(CARD, 'mt-3.5 flex items-start gap-3 p-[15px]')}
        >
            <span className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-[rgba(29,158,117,.10)] text-rz-positive">
                <Tick className="size-[18px]" />
            </span>
            <div className="min-w-0 flex-1">
                <p className="text-[14px] leading-[1.4] font-bold text-rz-ink">
                    {t('auditor.engagement.accepted', {
                        version: release.version,
                        date: formatDate(acceptance.accepted_at, locale),
                    })}
                </p>
                <p className="mt-1 text-[11.5px] break-all text-rz-secondary">
                    {t('auditor.engagement.accepted_receipt', {
                        hash: shortHash(acceptance.sha256),
                    })}
                </p>
            </div>
        </div>
    );
}
