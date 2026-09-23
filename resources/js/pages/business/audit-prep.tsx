import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { DetailSheet } from '@/components/business/detail-sheet';
import { HomeBody } from '@/components/business/home/home-body';
import { useTranslation } from '@/hooks/use-translation';
import { formatDayMonth, formatMonthYearLong } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessAuditPrepProps } from '@/types/business';

const READY = ['statements', 'stock', 'access', 'papers', 'person'] as const;
const FLOW = ['closes', 'visit', 'sealed', 'cosign'] as const;

/**
 * "Get ready for your audit" (MVP-BUSINESS-SCR-07, design L1006–1057), opened from Today's audit
 * window. Read-only: the Audit Partner runs the audit. The checklist is the business's own note to
 * itself and stays on this device's screen only.
 */
export default function BusinessAuditPrep({
    home,
    audit,
    policy,
    links,
}: BusinessAuditPrepProps) {
    const { t, locale } = useTranslation();
    const [ready, setReady] = useState<Record<string, boolean>>({});
    const month = formatMonthYearLong(audit.period, locale);
    const seal = formatDayMonth(audit.seal_by, locale);

    const sheet = (
        <DetailSheet
            label={t('business.audit_prep.title')}
            close={links.close}
            closeLabel={t('business.note.close')}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+2px)] pb-10 lg:pt-[18px]">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.close}
                        aria-label={t('business.note.back')}
                        className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <h1 className="text-xl font-semibold text-rz-ink">
                        {t('business.audit_prep.title')}
                    </h1>
                </div>
                <div className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4">
                    <div className="flex items-center justify-between gap-2.5">
                        <div className="min-w-0">
                            <p className="text-[11px] font-bold tracking-[.04em] text-rz-ink uppercase">
                                {t(
                                    audit.window_open
                                        ? 'business.audit_prep.window_open'
                                        : audit.first
                                          ? 'business.audit_prep.first'
                                          : 'business.audit_prep.next',
                                )}
                            </p>
                            <p className="mt-[3px] text-[17px] font-bold text-rz-ink">
                                {t('business.audit_prep.month', { month })}
                            </p>
                        </div>
                        <div className="shrink-0 text-right">
                            <p className="text-2xl leading-none font-bold text-rz-ink">
                                {audit.days_left}
                            </p>
                            <p className="mt-0.5 text-[10px] font-bold text-rz-slate uppercase">
                                {t(
                                    audit.days_left === 1
                                        ? 'business.audit_prep.day_left'
                                        : 'business.audit_prep.days_left',
                                )}
                            </p>
                        </div>
                    </div>
                    <p className="mt-3 text-[12.5px] leading-[1.55] text-rz-secondary">
                        {t('business.audit_prep.intro', { month, seal })}
                    </p>
                    {audit.reassigned !== null && (
                        <p
                            role="status"
                            className="mt-3 rounded-xl border border-[#fbe4cc] bg-[#fff8f1] px-3 py-2.5 text-xs leading-normal text-[#8a4a14] dark:border-transparent dark:bg-[rgba(194,102,31,.12)] dark:text-[#f0a060]"
                        >
                            {t(
                                'business.audit_prep.reassigned',
                                audit.reassigned,
                            )}
                        </p>
                    )}
                </div>

                <h2 className="mt-5 text-xs font-bold tracking-[.04em] text-rz-slate uppercase">
                    {t('business.audit_prep.ready')}
                </h2>
                <ul className="mt-2.5 flex flex-col gap-[9px]">
                    {READY.map((key) => {
                        const on = ready[key] === true;

                        return (
                            <li key={key}>
                                <button
                                    type="button"
                                    role="checkbox"
                                    aria-checked={on}
                                    onClick={() =>
                                        setReady((current) => ({
                                            ...current,
                                            [key]: !on,
                                        }))
                                    }
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
                                        <span className="block text-[13.5px] font-semibold text-rz-ink">
                                            {t(
                                                `business.audit_prep.item.${key}.title`,
                                            )}
                                        </span>
                                        <span className="mt-[3px] block text-xs leading-normal text-rz-secondary">
                                            {t(
                                                `business.audit_prep.item.${key}.body`,
                                            )}
                                        </span>
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ul>

                <h2 className="mt-5 text-xs font-bold tracking-[.04em] text-rz-slate uppercase">
                    {t('business.audit_prep.how')}
                </h2>
                <ol className="mt-2.5 rounded-2xl border border-rz-border bg-rz-surface px-[15px] py-1.5">
                    {FLOW.map((key, index) => (
                        <li
                            key={key}
                            className={cn(
                                'flex items-start gap-3 py-[13px]',
                                index > 0 &&
                                    'border-t border-[#eef2f9] dark:border-rz-divider',
                            )}
                        >
                            <span
                                aria-hidden
                                className={cn(
                                    'flex size-6 shrink-0 items-center justify-center rounded-[10px] text-[11.5px] font-bold',
                                    key === 'cosign'
                                        ? 'bg-rz-accent-soft text-rz-accent-app-text'
                                        : 'bg-[rgba(194,102,31,.10)] text-rz-ink',
                                )}
                            >
                                {index + 1}
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block text-[13px] font-semibold text-rz-ink">
                                    {t(`business.audit_prep.flow.${key}.title`)}
                                </span>
                                <span className="mt-[3px] block text-xs leading-normal text-rz-secondary">
                                    {t(`business.audit_prep.flow.${key}.body`, {
                                        minutes: policy.cosign_minutes,
                                    })}
                                </span>
                            </span>
                        </li>
                    ))}
                </ol>
                <p className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-3.5 text-[12.5px] leading-[1.55] text-rz-slate">
                    {t('business.audit_prep.closing')}
                </p>
            </div>
        </DetailSheet>
    );

    return (
        <BusinessShell
            title={t('business.audit_prep.title')}
            tab="home"
            links={home.links}
            showTabBar={false}
        >
            <HomeBody
                {...home}
                backdrop
                overlay={{ column: 'left', content: sheet }}
            />
        </BusinessShell>
    );
}
