import { useState } from 'react';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatOrdinal } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';

const STEPS: { key: 'opens' | 'visit' | 'cosign'; icon: IconName }[] = [
    { key: 'opens', icon: 'receipt' },
    { key: 'visit', icon: 'schedule' },
    { key: 'cosign', icon: 'trend-up' },
];

/**
 * "How monthly audits work" (design L948–981). A phone folds it away behind its heading; a wide
 * screen keeps it open as the page's right column.
 */
export function AuditGuide({
    sealDay,
    cosignMinutes,
}: {
    sealDay: number;
    cosignMinutes: number;
}) {
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState(false);

    const heading = (
        <>
            <span className="flex size-[22px] shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft text-xs">
                <Icon name="info" />
            </span>
            <span className="flex-1 text-[13.5px] font-semibold text-rz-accent-app-text">
                {t('business.reports.guide.title')}
            </span>
        </>
    );

    return (
        <section
            data-rzcol
            aria-label={t('business.reports.guide.title')}
            className="mt-4 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:mt-0 lg:min-h-0 lg:overflow-y-auto lg:px-1 lg:pt-1 lg:pb-2"
        >
            <button
                type="button"
                aria-expanded={open}
                onClick={() => setOpen((value) => !value)}
                className="flex w-full items-center gap-[9px] bg-rz-surface p-3.5 text-left lg:hidden"
            >
                {heading}
                <svg
                    viewBox="0 0 16 16"
                    fill="none"
                    aria-hidden
                    className={cn(
                        'size-4 shrink-0 transition-transform duration-200',
                        open && 'rotate-180',
                    )}
                >
                    <path
                        d="M4 6l4 4 4-4"
                        stroke="currentColor"
                        className="text-rz-accent-app-text"
                        strokeWidth="1.8"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </button>
            <div className="hidden items-center gap-[9px] p-3.5 lg:flex">
                {heading}
            </div>
            <ol
                className={cn(
                    'border-t border-[#eef1f6] px-3.5 pt-1.5 pb-1 lg:block dark:border-rz-divider',
                    !open && 'hidden',
                )}
            >
                {STEPS.map(({ key, icon }, index) => (
                    <li
                        key={key}
                        className={cn(
                            'flex items-start gap-[11px] py-3',
                            index > 0 &&
                                'border-t border-[#eef1f6] dark:border-rz-divider',
                        )}
                    >
                        <span className="flex size-[30px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-[15px]">
                            <Icon name={icon} />
                        </span>
                        <div>
                            <p className="text-[13px] font-semibold text-rz-ink">
                                {t(`business.reports.guide.${key}.title`)}
                            </p>
                            <p className="mt-[3px] text-xs leading-normal text-rz-slate">
                                {key === 'opens' &&
                                    t('business.reports.guide.opens.body')}
                                {key === 'visit' && (
                                    <>
                                        {t(
                                            'business.reports.guide.visit.body_before',
                                        )}{' '}
                                        <strong className="text-rz-ink">
                                            {formatOrdinal(sealDay, locale, t)}
                                        </strong>
                                        {t(
                                            'business.reports.guide.visit.body_after',
                                        )}
                                    </>
                                )}
                                {key === 'cosign' &&
                                    t('business.reports.guide.cosign.body', {
                                        minutes: cosignMinutes,
                                    })}
                            </p>
                        </div>
                    </li>
                ))}
            </ol>
        </section>
    );
}
