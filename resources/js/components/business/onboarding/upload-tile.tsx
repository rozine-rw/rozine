import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';

type UploadTileProps = {
    /** What the server files this upload under: "logo", "certificate" or a showcase slot. */
    slot: string;
    action: RouteAction;
    label: string;
    accept: string;
    className: string;
    children: ReactNode;
};

/**
 * A dashed design tile that is really a file picker. The chosen file goes straight to the server,
 * which stores it and re-renders the page with its new state.
 */
export function UploadTile({
    slot,
    action,
    label,
    accept,
    className,
    children,
}: UploadTileProps) {
    return (
        <label className={cn('cursor-pointer', className)}>
            <input
                type="file"
                accept={accept}
                aria-label={label}
                className="sr-only"
                onChange={(event) => {
                    const file = event.target.files?.[0];

                    if (file) {
                        router.post(
                            action.url,
                            { slot, file },
                            { forceFormData: true, preserveScroll: true },
                        );
                    }
                }}
            />
            {children}
        </label>
    );
}
