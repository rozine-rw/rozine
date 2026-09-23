import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { DetailSheet } from '@/components/business/detail-sheet';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteLink } from '@/types';

type WizardFrameProps = {
    /** 1-based page and total, or null on the submitted screen. */
    progress: { page: number; total: number } | null;
    back: RouteLink;
    close: RouteLink;
    /** The scrim may close the sheet only while nothing typed could be lost (design `_dtBusy`). */
    dismissible: boolean;
    cta: ReactNode;
    children: ReactNode;
};

/**
 * The raise wizard's container (design L349–361 and the sticky CTA L2455–2459). A phone gets a
 * full-screen detail page; a wide screen gets the design's detail sheet over Home.
 */
export function WizardFrame({
    progress,
    back,
    close,
    dismissible,
    cta,
    children,
}: WizardFrameProps) {
    const { t } = useTranslation();

    return (
        <DetailSheet
            label={t('business.apply.label')}
            close={close}
            closeLabel={t('business.apply.close')}
            dismissible={dismissible}
            footer={
                cta && (
                    <div className="fixed inset-x-0 bottom-0 z-25 bg-[linear-gradient(180deg,rgba(244,247,252,0)_0%,rgba(244,247,252,.86)_24%,#f4f7fc_52%)] px-5 pt-4 pb-7 lg:absolute lg:inset-x-px lg:bottom-px lg:h-[88px] lg:rounded-b-[18px] lg:bg-[linear-gradient(180deg,rgba(255,255,255,0)_0%,rgba(255,255,255,.94)_26%,#ffffff_56%)] lg:px-[18px] lg:pt-4 lg:pb-5 dark:bg-[linear-gradient(180deg,rgba(16,26,46,0)_0%,rgba(16,26,46,.9)_24%,#101a2e_52%)] dark:lg:bg-[linear-gradient(180deg,rgba(16,26,46,0)_0%,rgba(16,26,46,.94)_26%,#101a2e_56%)]">
                        {cta}
                    </div>
                )
            }
        >
            {progress !== null && (
                <div className="px-5 pt-[calc(env(safe-area-inset-top)+2px)] lg:pt-[18px]">
                    <div className="flex items-center gap-3">
                        <Link
                            href={back}
                            aria-label={t('business.apply.back')}
                            className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                        >
                            ←
                        </Link>
                        <div className="flex-1">
                            <div
                                role="progressbar"
                                aria-label={t('business.apply.progress')}
                                aria-valuemin={1}
                                aria-valuemax={progress.total}
                                aria-valuenow={progress.page}
                                className="h-1.5 overflow-hidden rounded-[4px] bg-rz-border"
                            >
                                <div
                                    className="h-full bg-rz-accent-fill transition-[width] duration-400 ease-in-out"
                                    style={{
                                        width: `${Math.round((progress.page / progress.total) * 100)}%`,
                                    }}
                                />
                            </div>
                        </div>
                        <span className="text-[12.5px] font-bold whitespace-nowrap text-rz-ink">
                            {progress.page}/{progress.total}
                        </span>
                    </div>
                </div>
            )}
            <div className="p-5 lg:px-5 lg:pt-4 lg:pb-[100px]">{children}</div>
        </DetailSheet>
    );
}
