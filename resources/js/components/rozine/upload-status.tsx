import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/**
 * How far a file on its way to the server has got: "Uploading…" until the browser reports
 * progress, then the share sent, then "Checking the file…" once every byte is out and the server
 * is storing it. `overlay` covers the tile the file was picked from; otherwise it sits inline
 * beside the control that sent it.
 */
export function UploadStatus({
    percent,
    overlay = false,
    className,
}: {
    /** The share of the file sent so far, or null while the browser has not said. */
    percent: number | null;
    overlay?: boolean;
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <span
            role="status"
            className={cn(
                'text-[12px] font-semibold text-rz-slate',
                overlay
                    ? 'absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 rounded-[inherit] bg-rz-page/90'
                    : 'flex items-center gap-2',
                className,
            )}
        >
            <span
                aria-hidden
                className={cn(
                    'shrink-0 animate-spin rounded-full border-2 border-rz-accent-fill border-t-transparent',
                    overlay ? 'size-[22px]' : 'size-4',
                )}
            />
            {percent === null
                ? t('common.upload.sending')
                : percent >= 100
                  ? t('common.upload.checking')
                  : t('common.upload.percent', {
                        percent: Math.round(percent),
                    })}
        </span>
    );
}
