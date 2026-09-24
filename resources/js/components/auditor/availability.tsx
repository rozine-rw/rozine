import { useAuditorCommands } from '@/components/auditor/commands';
import { Toggle } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AuditorAvailability } from '@/types/auditor';

/**
 * The partner's own dispatch switch, as the `availability.update` command. It is offered only
 * when `allowed_actions` lists it. The page shows the server's state, not a guess: the switch
 * waits for the server to record the change, and the page reloads from its answer. The radius and
 * the number of active jobs stay the server's.
 */
export function useAvailabilityToggle(availability: AuditorAvailability) {
    const center = useAuditorCommands();
    const allowed = center.allowed('availability.update');

    const toggle = () =>
        center.send({
            name: 'availability.update',
            business: '',
            route: availability.update,
            payload: {
                accepting: !availability.accepting,
                expected_revision: availability.revision,
            },
        });

    return {
        allowed,
        busy: center.busy,
        disabled: !allowed || !center.idle,
        toggle,
    };
}

/** Why the switch cannot be moved, when the server does not offer the change. */
export function AvailabilityLocked({ className }: { className: string }) {
    const { t } = useTranslation();

    return (
        <p
            className={cn(
                'text-[11px] leading-[1.5] text-rz-secondary',
                className,
            )}
        >
            {t('auditor.availability.locked')}
        </p>
    );
}

/** Home's availability row (design L137–142). */
export function AvailabilityRow({
    availability,
}: {
    availability: AuditorAvailability;
}) {
    const { t } = useTranslation();
    const { allowed, busy, disabled, toggle } =
        useAvailabilityToggle(availability);
    const on = availability.accepting;

    return (
        <div className="mt-3.5 rounded-2xl border border-rz-border bg-rz-surface px-3.5 py-3">
            <div className="flex items-center gap-3">
                <span
                    aria-hidden
                    className={cn(
                        'size-[9px] shrink-0 rounded-full',
                        on ? 'bg-rz-positive' : 'bg-rz-secondary',
                    )}
                />
                <div className="min-w-0 flex-1">
                    <p className="text-[13.5px] font-bold text-rz-ink">
                        {on
                            ? t('auditor.availability.accepting')
                            : t('auditor.availability.paused')}
                    </p>
                    <p className="mt-px truncate text-[11px] text-rz-secondary">
                        {on
                            ? t('auditor.availability.home_sub', {
                                  radius: availability.radius_km,
                                  max: availability.max_active,
                              })
                            : allowed
                              ? t('auditor.availability.home_paused')
                              : t('auditor.availability.home_paused_locked')}
                    </p>
                </div>
                <Toggle
                    on={on}
                    label={t('auditor.availability.toggle')}
                    disabled={disabled}
                    aria-busy={busy || undefined}
                    onClick={toggle}
                />
            </div>
            {!allowed && <AvailabilityLocked className="mt-2" />}
        </div>
    );
}
