import { router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { formatDayMonth } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessVisitReady } from '@/types/business';

/**
 * The visit-ready checklist (BRS v3 VIS-R03), inside the repayment reminder from D−14: exactly what
 * the CPA will ask for on this month's visit, in the visit's order, as the server generates it from
 * the monthly guided-visit steps. Each item says what it is, what it must show and where to get it.
 * The Business ticks an item when it is ready; each tick is saved on the server
 * (`business.visit_ready.tick`) so the D−11 reminder can list only what is still open. The box
 * changes at once and returns to its saved state if the save is refused. The ten sampled
 * transactions are named only three days before the visit.
 */
export function VisitReadyChecklist({ ready }: { ready: BusinessVisitReady }) {
    const { t, locale } = useTranslation();
    const [ticked, setTicked] = useState<Record<string, boolean>>(() =>
        Object.fromEntries(ready.ticked.map((key) => [key, true])),
    );
    const [failed, setFailed] = useState(false);
    const done = ready.items.filter((item) => ticked[item.key] === true).length;

    const toggle = (key: string, on: boolean) => {
        setFailed(false);
        setTicked((current) => ({ ...current, [key]: on }));
        router.post(
            ready.tick.url,
            {
                request_id: crypto.randomUUID(),
                due_on: ready.due_on,
                item: key,
                ticked: on,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => {
                    setTicked((current) => ({ ...current, [key]: !on }));
                    setFailed(true);
                },
            },
        );
    };

    return (
        <section aria-labelledby="visit-ready-title" className="mt-4">
            <div className="rounded-2xl border border-rz-border bg-rz-surface p-4">
                <h2
                    id="visit-ready-title"
                    className="text-base font-semibold text-rz-ink"
                >
                    {t('business.visit_ready.title')}
                </h2>
                <p className="mt-1.5 text-[12.5px] leading-[1.55] text-rz-secondary">
                    {t('business.visit_ready.lede', {
                        due: formatDayMonth(ready.due_on, locale),
                        from: formatDayMonth(ready.visit_from, locale),
                        by: formatDayMonth(ready.visit_by, locale),
                    })}
                </p>
                <p
                    role="status"
                    className="mt-2 text-xs font-semibold text-rz-ink tabular-nums"
                >
                    {t('business.visit_ready.progress', {
                        done,
                        total: ready.items.length,
                    })}
                </p>
                {failed && (
                    <p
                        role="alert"
                        className="mt-2 text-xs font-semibold text-rz-danger-text"
                    >
                        {t('business.visit_ready.failed')}
                    </p>
                )}
            </div>
            <ul className="mt-2.5 flex flex-col gap-[9px]">
                {ready.items.map((item) => {
                    const on = ticked[item.key] === true;
                    const base =
                        `business.visit_ready.item.${item.key}` as const;
                    const id = `visit-ready-${item.key}`;

                    return (
                        <li key={item.key}>
                            <button
                                type="button"
                                role="checkbox"
                                aria-checked={on}
                                aria-labelledby={`${id}-title`}
                                aria-describedby={`${id}-shows ${id}-where`}
                                onClick={() => toggle(item.key, !on)}
                                className={cn(
                                    'flex w-full items-start gap-3 rounded-2xl border bg-rz-surface p-3.5 text-left',
                                    on
                                        ? 'border-[#cfe9d8] dark:border-rz-accent-fill'
                                        : 'border-rz-border',
                                )}
                            >
                                <span
                                    aria-hidden
                                    className={cn(
                                        'mt-px flex size-[22px] shrink-0 items-center justify-center rounded-[10px] border-[1.5px] text-xs font-bold text-white',
                                        on
                                            ? 'border-[#cfe9d8] bg-rz-accent-fill dark:border-rz-accent-fill'
                                            : 'border-rz-border bg-rz-surface',
                                    )}
                                >
                                    {on ? '✓' : ''}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span
                                        id={`${id}-title`}
                                        className="block text-[13.5px] font-semibold text-rz-ink"
                                    >
                                        {t(`${base}.title`)}
                                    </span>
                                    <span
                                        id={`${id}-shows`}
                                        className="mt-[3px] block text-xs leading-normal text-rz-secondary"
                                    >
                                        {t('business.visit_ready.shows', {
                                            text: t(`${base}.shows`),
                                        })}
                                    </span>
                                    <span
                                        id={`${id}-where`}
                                        className="mt-[3px] block text-xs leading-normal text-rz-secondary"
                                    >
                                        {t('business.visit_ready.where', {
                                            text: t(`${base}.where`),
                                        })}
                                    </span>
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ul>
            <p className="mt-3 rounded-2xl border border-rz-border bg-rz-surface p-3.5 text-[12.5px] leading-[1.55] text-rz-slate">
                {t('business.visit_ready.footer')}
            </p>
        </section>
    );
}
