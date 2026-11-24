import {
    AvailabilityLocked,
    StandingNote,
    useAvailabilityToggle,
    useReceiving,
} from '@/components/auditor/availability';
import { useAuditorCommands } from '@/components/auditor/commands';
import { DIVIDER, Eyebrow, Toggle } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type {
    AuditorAvailability,
    DispatchStanding,
    RwandaProvinces,
} from '@/types/auditor';

/** The weekly limit's bounds, as the server checks them. */
export const MIN_JOBS_PER_WEEK = 1;
export const MAX_JOBS_PER_WEEK = 20;

/** How many chosen districts the footer names before "+N". */
const NAMED = 3;

const STEP =
    'flex size-[34px] items-center justify-center rounded-[10px] bg-rz-page text-[18px] font-bold text-rz-ink transition-colors hover:bg-rz-hairline disabled:cursor-not-allowed disabled:opacity-40 dark:bg-rz-surface-muted';

/**
 * Availability & coverage (design L890–916; Erastus, 24 Nov 2026): the accepting switch, the most
 * audits a week the engine may offer, and the districts the partner accepts. Dispatch offers an
 * audit when the Business's district is one of them, so the partner's chips are the whole of their
 * coverage — there is no radius. Each change is the `availability.update` command, sent at once
 * with the other two settings as they stand, and the page shows what the server recorded.
 */
export function AvailabilitySection({
    availability,
    standing,
    provinces,
}: {
    availability: AuditorAvailability;
    standing: DispatchStanding;
    provinces: RwandaProvinces;
}) {
    const { t } = useTranslation();
    const center = useAuditorCommands();
    const { allowed, busy, disabled, toggle } =
        useAvailabilityToggle(availability);
    const { receiving, reason, note } = useReceiving(availability, standing);
    const on = availability.accepting;
    const all = Object.values(provinces).flat();
    const chosen = availability.districts;
    const max = availability.max_jobs_per_week;

    const save = (districts: string[], maxJobs: number) =>
        center.send({
            name: 'availability.update',
            business: '',
            route: availability.update,
            payload: {
                accepting: availability.accepting,
                districts,
                max_jobs_per_week: maxJobs,
                expected_revision: availability.revision,
            },
        });
    const flip = (district: string) =>
        save(
            chosen.includes(district)
                ? chosen.filter((item) => item !== district)
                : all.filter(
                      (item) => item === district || chosen.includes(item),
                  ),
            max,
        );
    const summary = [
        t('auditor.availability.districts_count', {
            count: chosen.length,
            total: all.length,
        }),
        chosen.length === 0
            ? null
            : [
                  chosen.slice(0, NAMED).join(', '),
                  chosen.length > NAMED ? ` +${chosen.length - NAMED}` : '',
              ].join(''),
    ]
        .filter((part) => part !== null)
        .join(' · ');

    return (
        <section>
            <Eyebrow as="h2">{t('auditor.availability.title')}</Eyebrow>
            <div className="mt-2.5 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                <div className="flex items-center gap-[13px]">
                    <div className="min-w-0 flex-1">
                        <p className="text-[14px] font-bold text-rz-ink">
                            {receiving
                                ? t('auditor.availability.accepting')
                                : t('auditor.availability.paused')}
                        </p>
                        <p className="mt-0.5 text-[11.5px] leading-[1.5] text-rz-secondary">
                            {reason ??
                                (receiving
                                    ? t('auditor.availability.accepting_sub')
                                    : t('auditor.availability.paused_sub'))}
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
                <div
                    className={cn(
                        'mt-3.5 flex items-center gap-[13px] border-t pt-3.5',
                        DIVIDER,
                    )}
                >
                    <div className="min-w-0 flex-1">
                        <p
                            id="weekly-limit"
                            className="text-[13px] font-bold text-rz-ink"
                        >
                            {t('auditor.availability.weekly_title')}
                        </p>
                        <p className="mt-0.5 text-[11.5px] text-rz-secondary">
                            {t('auditor.availability.weekly_sub')}
                        </p>
                    </div>
                    <div
                        role="group"
                        aria-labelledby="weekly-limit"
                        className="flex shrink-0 items-center gap-2.5"
                    >
                        <button
                            type="button"
                            className={STEP}
                            aria-label={t('auditor.availability.weekly_less')}
                            disabled={disabled || max <= MIN_JOBS_PER_WEEK}
                            onClick={() => save(chosen, max - 1)}
                        >
                            −
                        </button>
                        <span
                            aria-live="polite"
                            className="min-w-[22px] text-center text-[16px] font-bold text-rz-ink"
                        >
                            {max}
                        </span>
                        <button
                            type="button"
                            className={STEP}
                            aria-label={t('auditor.availability.weekly_more')}
                            disabled={disabled || max >= MAX_JOBS_PER_WEEK}
                            onClick={() => save(chosen, max + 1)}
                        >
                            +
                        </button>
                    </div>
                </div>
                <div className={cn('mt-3.5 border-t pt-3.5', DIVIDER)}>
                    <p
                        id="districts-accepted"
                        className="text-[13px] font-bold text-rz-ink"
                    >
                        {t('auditor.availability.districts_title')}
                    </p>
                    <div
                        role="group"
                        aria-labelledby="districts-accepted"
                        className="mt-2.5 flex flex-wrap gap-2"
                    >
                        {all.map((district) => {
                            const active = chosen.includes(district);

                            return (
                                <button
                                    key={district}
                                    type="button"
                                    aria-pressed={active}
                                    disabled={disabled}
                                    onClick={() => flip(district)}
                                    className={cn(
                                        'rounded-xl border px-3 py-[7px] text-[12.5px] font-semibold transition-colors disabled:cursor-not-allowed',
                                        active
                                            ? 'border-[#e6cfa3] bg-[#fbf3ea] text-rz-ink dark:border-[rgba(240,160,96,.4)] dark:bg-[rgba(240,160,96,.14)]'
                                            : 'border-rz-border bg-rz-page text-rz-secondary hover:text-rz-ink dark:bg-rz-surface-muted',
                                    )}
                                >
                                    {district}
                                </button>
                            );
                        })}
                    </div>
                    <p className="mt-3 text-[11.5px] text-rz-secondary">
                        {summary}
                    </p>
                    {chosen.length === 0 && (
                        <p
                            role="status"
                            className="mt-2 rounded-[10px] border border-[#f2d69a] px-3 py-2 text-[11px] leading-[1.5] text-[#8a6d2b] dark:border-[rgba(240,160,96,.3)] dark:text-[#e3b56a]"
                        >
                            {t('auditor.availability.districts_none')}
                        </p>
                    )}
                </div>
            </div>
        </section>
    );
}
