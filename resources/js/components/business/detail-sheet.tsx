import { Link, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useModalDialog } from '@/hooks/use-modal-dialog';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';

type DetailSheetProps = {
    label: string;
    close: RouteLink;
    closeLabel: string;
    /** The scrim may close the sheet only while nothing typed could be lost (design `_dtBusy`). */
    dismissible?: boolean;
    /** A long form takes the whole column on a wide screen, so nothing peeks blurred above it. */
    fullHeight?: boolean;
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
    fullHeight = false,
    footer,
    children,
}: DetailSheetProps) {
    const dialog = useModalDialog<HTMLElement>(
        dismissible
            ? () => router.visit(close.url, { preserveScroll: true })
            : undefined,
    );

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
                ref={dialog}
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
                aria-label={label}
                className={cn(
                    'relative flex min-h-svh flex-col bg-rz-surface lg:absolute lg:inset-x-0 lg:bottom-0 lg:z-[56] lg:min-h-0 lg:animate-[rz-sheetup_.34s_cubic-bezier(.16,1,.3,1)_both] lg:rounded-[20px_20px_18px_18px] lg:border lg:border-rz-border lg:shadow-[0_-18px_50px_-16px_rgba(20,45,95,.4)]',
                    fullHeight ? 'lg:top-0' : 'lg:top-[12%]',
                )}
            >
                <div
                    data-testid="sheet-scroll"
                    className="rz-scroll flex-1 lg:min-h-0 lg:overflow-y-auto"
                >
                    {children}
                </div>
                {footer}
            </section>
        </>
    );
}
