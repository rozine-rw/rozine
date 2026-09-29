import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Toggle } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AuditorAvailability } from '@/types/auditor';

/**
 * The partner's own dispatch switch. The page shows the server's state, not a guess: the switch
 * waits for the server to record the change and redraws from its answer.
 */
export function useAvailabilityToggle(availability: AuditorAvailability) {
    const [busy, setBusy] = useState(false);

    const toggle = () =>
        router.post(
            availability.toggle.url,
            { accepting: !availability.accepting },
            {
                preserveScroll: true,
                onStart: () => setBusy(true),
                onFinish: () => setBusy(false),
            },
        );

    return { busy, toggle };
}

/** Home's availability row (design L137–142). */
export function AvailabilityRow({
    availability,
}: {
    availability: AuditorAvailability;
}) {
    const { t } = useTranslation();
    const { busy, toggle } = useAvailabilityToggle(availability);
    const on = availability.accepting;

    return (
        <div className="mt-3.5 flex items-center gap-3 rounded-2xl border border-rz-border bg-rz-surface px-3.5 py-3">
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
                        : t('auditor.availability.home_paused')}
                </p>
            </div>
            <Toggle
                on={on}
                label={t('auditor.availability.toggle')}
                disabled={busy}
                onClick={toggle}
            />
        </div>
    );
}
