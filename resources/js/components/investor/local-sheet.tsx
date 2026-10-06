import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

type LocalSheetProps = {
    label: string;
    onClose: () => void;
    /** Wide-screen height cap as a share of the column (design pull-ups use 88–92%). */
    maxHeight?: string;
    className?: string;
    children: ReactNode;
};

/**
 * A client-side pull-up for detail that is already on the page (a monthly report, a receipt
 * row). Phone: a bottom sheet over a blurred scrim. Wide screen: anchored to the column that
 * opened it (design CLAUDE.md: overlays open inside their own column), so that column must be
 * `relative`. Escape and the scrim both dismiss it.
 */
export function LocalSheet({
    label,
    onClose,
    maxHeight = 'lg:max-h-[92%]',
    className,
    children,
}: LocalSheetProps) {
    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [onClose]);

    return (
        <>
            <button
                type="button"
                aria-hidden
                tabIndex={-1}
                onClick={onClose}
                className="fixed inset-0 z-[60] animate-[rz-scrim_.2s_ease_both] bg-[rgba(8,14,28,.5)] backdrop-blur-[3px] lg:absolute lg:z-[44] lg:rounded-[20px] lg:bg-[rgba(8,14,28,.28)]"
            />
            <section
                role="dialog"
                aria-modal="true"
                aria-label={label}
                className={cn(
                    'fixed inset-x-0 bottom-0 z-[61] flex max-h-[92%] animate-[rz-sheetup_.32s_cubic-bezier(.16,1,.3,1)_both] flex-col overflow-hidden rounded-t-3xl bg-rz-surface shadow-[0_-18px_50px_-16px_rgba(20,45,95,.4)] lg:absolute lg:z-[45] lg:rounded-[20px] lg:border lg:border-rz-border lg:shadow-[0_-18px_46px_-18px_rgba(20,45,95,.4)]',
                    maxHeight,
                    className,
                )}
            >
                <div className="flex shrink-0 justify-center pt-[9px]">
                    <span className="h-1 w-[38px] rounded-[3px] bg-[#e2e7f0] dark:bg-rz-border" />
                </div>
                {children}
            </section>
        </>
    );
}

/** The sheets' small ✕ (28×28, 10px radius, soft fill). */
export function SheetClose({ onClose }: { onClose: () => void }) {
    const { t } = useTranslation();

    return (
        <button
            type="button"
            aria-label={t('app.sheet.close')}
            onClick={onClose}
            className="flex size-7 shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-[#f3f6fc] text-sm text-rz-secondary dark:bg-rz-surface-muted"
        >
            <span aria-hidden>✕</span>
        </button>
    );
}
