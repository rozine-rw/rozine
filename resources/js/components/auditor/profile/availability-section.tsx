import { useAvailabilityToggle } from '@/components/auditor/availability';
import { DIVIDER, Eyebrow, Toggle } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AuditorAvailability } from '@/types/auditor';

function PolicyRow({
    title,
    sub,
    value,
}: {
    title: string;
    sub: string;
    value: string;
}) {
    return (
        <div
            className={cn(
                'mt-3.5 flex items-center gap-[13px] border-t pt-3.5',
                DIVIDER,
            )}
        >
            <div className="min-w-0 flex-1">
                <p className="text-[13px] font-bold text-rz-ink">{title}</p>
                <p className="mt-0.5 text-[11.5px] text-rz-secondary">{sub}</p>
            </div>
            <span className="min-w-[22px] shrink-0 text-center text-[16px] font-bold text-rz-ink">
                {value}
            </span>
        </div>
    );
}

/**
 * Availability and coverage (design L890–916). Only the partner's on/off choice is theirs; the
 * design's editable weekly cap and district picker contradict the dispatch policy (30 km from the
 * registered office, three active engagements, CFG-05), so those rows show the policy instead.
 */
export function AvailabilitySection({
    availability,
}: {
    availability: AuditorAvailability;
}) {
    const { t } = useTranslation();
    const { busy, toggle } = useAvailabilityToggle(availability);
    const on = availability.accepting;

    return (
        <section>
            <Eyebrow as="h2">{t('auditor.availability.title')}</Eyebrow>
            <div className="mt-2.5 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                <div className="flex items-center gap-[13px]">
                    <div className="min-w-0 flex-1">
                        <p className="text-[14px] font-bold text-rz-ink">
                            {on
                                ? t('auditor.availability.accepting')
                                : t('auditor.availability.paused')}
                        </p>
                        <p className="mt-0.5 text-[11.5px] leading-[1.5] text-rz-secondary">
                            {on
                                ? t('auditor.availability.accepting_sub')
                                : t('auditor.availability.paused_sub')}
                        </p>
                    </div>
                    <Toggle
                        on={on}
                        label={t('auditor.availability.toggle')}
                        disabled={busy}
                        onClick={toggle}
                    />
                </div>
                <PolicyRow
                    title={t('auditor.availability.max_title')}
                    sub={t('auditor.availability.max_sub')}
                    value={String(availability.max_active)}
                />
                <PolicyRow
                    title={t('auditor.availability.radius_title')}
                    sub={t('auditor.availability.radius_sub')}
                    value={t('auditor.jobs.km', {
                        distance: availability.radius_km,
                    })}
                />
            </div>
        </section>
    );
}
