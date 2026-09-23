import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
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
 * full-screen detail page; a wide screen gets the design's column sheet over Home.
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
        <>
            {dismissible ? (
                <Link
                    href={close}
                    aria-label={t('business.apply.close')}
                    className="absolute inset-0 z-[55] hidden animate-[rz-scrim_.22s_ease] bg-[rgba(8,14,28,.42)] backdrop-blur-[3px] lg:block"
                />
            ) : (
                <div className="absolute inset-0 z-[55] hidden bg-[rgba(8,14,28,.42)] backdrop-blur-[3px] lg:block" />
            )}
            <section
                role="dialog"
                aria-modal="true"
                aria-label={t('business.apply.label')}
                className="relative min-h-svh bg-rz-surface lg:absolute lg:inset-x-0 lg:top-[12%] lg:bottom-0 lg:z-[56] lg:min-h-0 lg:animate-[rz-sheetup_.34s_cubic-bezier(.16,1,.3,1)_both] lg:rounded-[20px_20px_18px_18px] lg:border lg:border-rz-border lg:shadow-[0_-18px_50px_-16px_rgba(20,45,95,.4)]"
            >
                <div className="rz-scroll h-full overflow-y-auto">
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
                                        aria-label={t(
                                            'business.apply.progress',
                                        )}
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
                    <div className="p-5 lg:px-5 lg:pt-4 lg:pb-[100px]">
                        {children}
                    </div>
                </div>
                {cta && (
                    <div className="fixed inset-x-0 bottom-0 z-25 bg-[linear-gradient(180deg,rgba(244,247,252,0)_0%,rgba(244,247,252,.86)_24%,#f4f7fc_52%)] px-5 pt-4 pb-7 lg:absolute lg:inset-x-px lg:bottom-px lg:h-[88px] lg:rounded-b-[18px] lg:bg-[linear-gradient(180deg,rgba(255,255,255,0)_0%,rgba(255,255,255,.94)_26%,#ffffff_56%)] lg:px-[18px] lg:pt-4 lg:pb-5 dark:bg-[linear-gradient(180deg,rgba(16,26,46,0)_0%,rgba(16,26,46,.9)_24%,#101a2e_52%)] dark:lg:bg-[linear-gradient(180deg,rgba(16,26,46,0)_0%,rgba(16,26,46,.94)_26%,#101a2e_56%)]">
                        {cta}
                    </div>
                )}
            </section>
        </>
    );
}
