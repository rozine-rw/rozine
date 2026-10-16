import { useAuditorCommands } from '@/components/auditor/commands';
import { Toggle } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AuditorAvailability, DispatchStanding } from '@/types/auditor';

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

/**
 * What the partner is told about receiving work, from both facts (#96): the saved preference and
 * the server's fresh standing. Accepting with standing lapsed is paused, never "accepting"; the
 * switch still shows the saved choice, and turning it on while lapsed says up front why no work
 * will come — the server still decides, and refuses with its own code.
 */
export function useReceiving(
    availability: AuditorAvailability,
    standing: DispatchStanding,
): { receiving: boolean; reason: string | null; note: string | null } {
    const { t } = useTranslation();

    if (standing.current) {
        return { receiving: availability.accepting, reason: null, note: null };
    }

    return {
        receiving: false,
        reason: t(`auditor.standing.reason.${standing.reason}`),
        note: availability.accepting
            ? t('auditor.standing.paused_until_restored')
            : t('auditor.standing.turning_on'),
    };
}

/** The note beneath the switch while standing has lapsed. */
export function StandingNote({ note }: { note: string | null }) {
    return (
        note !== null && (
            <p
                role="note"
                className="mt-2 rounded-[10px] border border-[#f2d69a] bg-rz-surface px-3 py-2 text-[11px] leading-[1.5] text-[#8a6d2b] dark:border-[rgba(240,160,96,.3)] dark:text-[#e3b56a]"
            >
                {note}
            </p>
        )
    );
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
    standing,
}: {
    availability: AuditorAvailability;
    standing: DispatchStanding;
}) {
    const { t } = useTranslation();
    const { allowed, busy, disabled, toggle } =
        useAvailabilityToggle(availability);
    const { receiving, reason, note } = useReceiving(availability, standing);
    const on = availability.accepting;

    return (
        <div className="mt-3.5 rounded-2xl border border-rz-border bg-rz-surface px-3.5 py-3">
            <div className="flex items-center gap-3">
                <span
                    aria-hidden
                    className={cn(
                        'size-[9px] shrink-0 rounded-full',
                        receiving ? 'bg-rz-positive' : 'bg-rz-secondary',
                    )}
                />
                <div className="min-w-0 flex-1">
                    <p className="text-[13.5px] font-bold text-rz-ink">
                        {receiving
                            ? t('auditor.availability.accepting')
                            : t('auditor.availability.paused')}
                    </p>
                    <p
                        className={cn(
                            'mt-px text-[11px] text-rz-secondary',
                            reason === null ? 'truncate' : 'leading-[1.45]',
                        )}
                    >
                        {reason ??
                            (receiving
                                ? t('auditor.availability.home_sub', {
                                      radius: availability.radius_km,
                                      max: availability.max_active,
                                  })
                                : allowed
                                  ? t('auditor.availability.home_paused')
                                  : t(
                                        'auditor.availability.home_paused_locked',
                                    ))}
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
            <StandingNote note={note} />
            {!allowed && <AvailabilityLocked className="mt-2" />}
        </div>
    );
}
