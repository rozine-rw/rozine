import { Link } from '@inertiajs/react';
import type { CSSProperties, ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteLink } from '@/types';

type ColumnSheetProps = {
    /** Accessible name of the sheet. */
    label: string;
    /** Where dismissing goes: the scrim and any close control lead back to the page beneath. */
    close: RouteLink;
    /**
     * Share of its column the sheet occupies on a wide screen (design `_colSheetK` fractions,
     * e.g. .8 for publish, .72 for a transaction). On a phone it is a bottom sheet up to 92%.
     */
    fraction: number;
    /** Tallest the bottom sheet may grow on a phone; most design sheets stop at 92%. */
    phoneFraction?: number;
    children: ReactNode;
};

/**
 * The design's overlay sheet. Phone: a bottom sheet over a blurred scrim. Wide screen: anchored to
 * the column that opened it — the design's locked rule that no overlay covers the whole app frame.
 * The parent column must be `relative`.
 */
export function ColumnSheet({
    label,
    close,
    fraction,
    phoneFraction = 0.92,
    children,
}: ColumnSheetProps) {
    const { t } = useTranslation();

    return (
        <>
            <Link
                href={close}
                aria-label={t('app.sheet.close')}
                className="fixed inset-0 z-[48] animate-[rz-scrim_.22s_ease_both] bg-[rgba(8,14,28,.5)] backdrop-blur-[3px] lg:absolute lg:rounded-2xl lg:bg-[rgba(8,14,28,.42)]"
            />
            <section
                role="dialog"
                aria-modal="true"
                aria-label={label}
                style={
                    {
                        '--rz-sheet-h': `${Math.round(fraction * 100)}%`,
                        '--rz-sheet-max': `${Math.round(phoneFraction * 100)}%`,
                    } as CSSProperties
                }
                className="fixed inset-x-0 bottom-0 z-[49] flex max-h-[var(--rz-sheet-max)] animate-[rz-sheetup_.34s_cubic-bezier(.16,1,.3,1)_both] flex-col overflow-hidden rounded-t-3xl bg-rz-surface shadow-[0_-18px_50px_-16px_rgba(20,45,95,.4)] lg:absolute lg:h-[var(--rz-sheet-h)] lg:max-h-none lg:rounded-[20px_20px_18px_18px]"
            >
                <div className="flex shrink-0 justify-center pt-[9px]">
                    <span className="h-1 w-[38px] rounded-[3px] bg-[#e2e7f0] dark:bg-rz-border" />
                </div>
                {children}
            </section>
        </>
    );
}
