import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { ClockChip } from '@/components/auditor/clock';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';

type DetailSheetProps = {
    label: string;
    close: RouteLink;
    /** The scrim may close the sheet only while nothing entered could be lost (design `_wfDirty`). */
    dismissible: boolean;
    header: ReactNode;
    footer?: ReactNode;
    /** Nested sheets (the seal preview) cover the whole detail sheet. */
    nested?: ReactNode;
    children: ReactNode;
};

/**
 * The design's workflow sheet (L1017–1235, box L2154–2169). A phone gets a full-screen page with
 * the tab bar hidden; a wide screen gets a sheet that rises inside its column, 58px below the
 * column top, over a scrim on that column only.
 */
export function DetailSheet({
    label,
    close,
    dismissible,
    header,
    footer,
    nested,
    children,
}: DetailSheetProps) {
    const { t } = useTranslation();
    const scrim =
        'absolute inset-0 z-[48] hidden animate-[rz-scrim_.26s_ease] rounded-2xl bg-[rgba(8,14,28,.28)] lg:block';

    return (
        <>
            {dismissible ? (
                <Link
                    href={close}
                    aria-label={t('auditor.sheet.close')}
                    className={scrim}
                />
            ) : (
                <div className={cn(scrim, 'cursor-default')} />
            )}
            <section
                role="dialog"
                aria-modal="true"
                aria-label={label}
                className="relative flex min-h-svh animate-[rz-aud-screen_.3s_ease] flex-col bg-[#f6f8fb] lg:absolute lg:inset-x-0 lg:top-[58px] lg:bottom-0 lg:z-[49] lg:min-h-0 lg:animate-[rz-sheetup_.34s_cubic-bezier(.22,.72,.25,1)] lg:overflow-hidden lg:rounded-[16px_18px_0_0] lg:border lg:border-b-0 lg:border-rz-border lg:shadow-[0_-14px_44px_-14px_rgba(20,45,95,.34)] dark:bg-rz-page"
            >
                <div className="border-b border-rz-border bg-rz-surface px-[18px] pt-[calc(env(safe-area-inset-top)+14px)] pb-3.5 lg:pt-4">
                    {header}
                </div>
                <div className="rz-scroll flex-1 px-5 pt-[18px] pb-5 lg:min-h-0 lg:overflow-y-auto">
                    {children}
                </div>
                {footer && (
                    <div className="sticky bottom-0 z-10 flex flex-col gap-[9px] border-t border-rz-border bg-rz-surface px-5 pt-3 pb-[calc(env(safe-area-inset-bottom)+12px)] lg:pb-3">
                        {footer}
                    </div>
                )}
                {nested}
            </section>
        </>
    );
}

/** The back button: 36px, `#eef2f8` (design L1023). */
export function BackButton({
    href,
    label,
}: {
    href: RouteLink;
    label: string;
}) {
    return (
        <Link
            href={href}
            aria-label={label}
            className="flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-[#eef2f8] text-[17px] text-rz-ink dark:bg-rz-surface-muted"
        >
            ←
        </Link>
    );
}

type JobHeaderProps = {
    eyebrow: string;
    close: RouteLink;
    business: string;
    district: string;
    distanceKm: string;
    serverTime: string;
    /** The job's deadline; null for a routine offer, whose deadline the report calendar owns. */
    dueAt: string | null;
    steps?: ReactNode;
    /** Whether the clock shows; work stopped by a blocking conflict has none to run. */
    clock?: boolean;
};

/** A Flash file's header (design L1021–1035): back, eyebrow, business, clock and step bar. */
export function JobHeader({
    eyebrow,
    close,
    business,
    district,
    distanceKm,
    serverTime,
    dueAt,
    steps,
    clock = true,
}: JobHeaderProps) {
    const { t } = useTranslation();

    return (
        <>
            <div className="flex items-center gap-3">
                <BackButton href={close} label={t('auditor.sheet.back')} />
                <span className="text-[12px] font-bold tracking-[.08em] text-rz-slate uppercase">
                    {eyebrow}
                </span>
            </div>
            <div className="mt-3.5 flex items-center justify-between gap-2.5">
                <div className="min-w-0">
                    <h2 className="truncate text-[17px] font-bold text-rz-ink">
                        {business}
                    </h2>
                    <p className="mt-px text-[11.5px] text-rz-secondary">
                        {t('auditor.file.place', {
                            district,
                            distance: distanceKm,
                        })}
                    </p>
                </div>
                {clock && dueAt !== null && (
                    <ClockChip
                        serverTime={serverTime}
                        dueAt={dueAt}
                        size="sheet"
                    />
                )}
            </div>
            {steps}
        </>
    );
}
