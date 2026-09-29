import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';

type BottomSheetProps = {
    title: string;
    lead?: string;
    onClose: () => void;
    children: ReactNode;
};

/**
 * The design's bottom sheet (toolkit L1525–1537, box L2684–2688): grabber, title and lead, a ✕,
 * over a scrim. A phone gets it across the screen; a wide screen anchors it to the column that
 * opened it, which must be positioned.
 */
export function BottomSheet({
    title,
    lead,
    onClose,
    children,
}: BottomSheetProps) {
    const { t } = useTranslation();

    return (
        <div className="fixed inset-0 z-[60] flex items-end lg:absolute">
            <button
                type="button"
                aria-label={t('auditor.sheet.close')}
                onClick={onClose}
                className="absolute inset-0 animate-[rz-scrim_.24s_ease] bg-[rgba(10,18,38,.36)] lg:rounded-2xl lg:bg-[rgba(10,18,38,.32)]"
            />
            <div
                role="dialog"
                aria-modal="true"
                aria-label={title}
                className="rz-scroll relative max-h-[calc(100%-40px)] w-full animate-[rz-sheetup_.3s_cubic-bezier(.22,.72,.25,1)] overflow-y-auto rounded-t-[24px] bg-rz-surface px-[18px] pt-3.5 pb-[26px] shadow-[0_-18px_44px_-16px_rgba(20,45,95,.4)] lg:rounded-[20px_22px_0_0] lg:pb-5"
            >
                <div className="mb-3 flex justify-center">
                    <span className="h-1 w-[38px] rounded-[3px] bg-[#e2e7f0] dark:bg-rz-border" />
                </div>
                <div className="flex items-start justify-between gap-2.5">
                    <div className="min-w-0">
                        <h2 className="text-[15px] font-bold text-rz-ink">
                            {title}
                        </h2>
                        {lead && (
                            <p className="mt-0.5 text-[11.5px] leading-[1.5] text-rz-secondary">
                                {lead}
                            </p>
                        )}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label={t('auditor.sheet.close')}
                        className="flex size-[26px] shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-[#f3f6fc] text-[14px] text-rz-secondary dark:bg-rz-surface-muted"
                    >
                        ✕
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}
