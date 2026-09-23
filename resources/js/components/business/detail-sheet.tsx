import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { RouteLink } from '@/types';

type DetailSheetProps = {
    label: string;
    close: RouteLink;
    closeLabel: string;
    /** The scrim may close the sheet only while nothing typed could be lost (design `_dtBusy`). */
    dismissible?: boolean;
    /** Pinned under the scrolling content, e.g. the wizard's sticky CTA. */
    footer?: ReactNode;
    children: ReactNode;
};

/**
 * The design's detail screen (`detailCard`, L4318–4319). A phone gets a full-screen page; a wide
 * screen gets a sheet rising to 12% from the top of the column it opened from, over a scrim that
 * leads back to Home. The parent column must be `relative`.
 */
export function DetailSheet({
    label,
    close,
    closeLabel,
    dismissible = true,
    footer,
    children,
}: DetailSheetProps) {
    return (
        <>
            {dismissible ? (
                <Link
                    href={close}
                    aria-label={closeLabel}
                    className="absolute inset-0 z-[55] hidden animate-[rz-scrim_.22s_ease] bg-[rgba(8,14,28,.42)] backdrop-blur-[3px] lg:block"
                />
            ) : (
                <div className="absolute inset-0 z-[55] hidden bg-[rgba(8,14,28,.42)] backdrop-blur-[3px] lg:block" />
            )}
            <section
                role="dialog"
                aria-modal="true"
                aria-label={label}
                className="relative min-h-svh bg-rz-surface lg:absolute lg:inset-x-0 lg:top-[12%] lg:bottom-0 lg:z-[56] lg:min-h-0 lg:animate-[rz-sheetup_.34s_cubic-bezier(.16,1,.3,1)_both] lg:rounded-[20px_20px_18px_18px] lg:border lg:border-rz-border lg:shadow-[0_-18px_50px_-16px_rgba(20,45,95,.4)]"
            >
                <div className="rz-scroll h-full overflow-y-auto">
                    {children}
                </div>
                {footer}
            </section>
        </>
    );
}
