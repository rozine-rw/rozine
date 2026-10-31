import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { UploadStatus } from '@/components/rozine/upload-status';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';

/** The file on its way to the server: its slot and how much of it has gone (null until known). */
export type SendingUpload = { slot: string; percent: number | null };

type UploadTileProps = {
    /** What the server files this upload under: "logo", "certificate" or a showcase slot. */
    slot: string;
    action: RouteAction;
    label: string;
    accept: string;
    className: string;
    /** The upload in flight on this step, if any; every tile waits for it. */
    sending: SendingUpload | null;
    onSending: (sending: SendingUpload | null) => void;
    children: ReactNode;
};

/**
 * A dashed design tile that is really a file picker. The chosen file goes straight to the server,
 * which stores it and re-renders the page with its new state. While it travels the tile shows how
 * far it has got, and the step's other tiles wait: a second visit would cancel this one.
 */
export function UploadTile({
    slot,
    action,
    label,
    accept,
    className,
    sending,
    onSending,
    children,
}: UploadTileProps) {
    const mine = sending?.slot === slot;

    return (
        <label
            aria-busy={mine || undefined}
            className={cn(
                'relative',
                sending === null ? 'cursor-pointer' : 'cursor-wait',
                className,
            )}
        >
            <input
                type="file"
                accept={accept}
                aria-label={label}
                disabled={sending !== null}
                className="sr-only"
                onChange={(event) => {
                    const file = event.target.files?.[0];

                    // Picking the same file again must still send it.
                    event.target.value = '';

                    if (file) {
                        onSending({ slot, percent: null });
                        router.post(
                            action.url,
                            { slot, file },
                            {
                                forceFormData: true,
                                preserveScroll: true,
                                onProgress: (progress) =>
                                    onSending({
                                        slot,
                                        percent: progress?.percentage ?? null,
                                    }),
                                onFinish: () => onSending(null),
                            },
                        );
                    }
                }}
            />
            {children}
            {mine && <UploadStatus percent={sending.percent} overlay />}
        </label>
    );
}
