import { useCallback, useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';

/**
 * The design's toast (Business L2674–2679): white card, top-centre, a 3px accent edge and dot,
 * gone after two seconds. Announced politely so screen readers hear it too.
 */
export function useToast(): {
    toast: ReactNode;
    show: (message: string) => void;
} {
    const [message, setMessage] = useState<string | null>(null);
    const timer = useRef<number | undefined>(undefined);

    const show = useCallback((next: string) => {
        window.clearTimeout(timer.current);
        setMessage(next);
        timer.current = window.setTimeout(() => setMessage(null), 2000);
    }, []);

    useEffect(() => () => window.clearTimeout(timer.current), []);

    const toast = (
        <div
            aria-live="polite"
            className="pointer-events-none fixed inset-x-0 top-[max(env(safe-area-inset-top),16px)] z-[80] flex justify-center px-4"
        >
            {message !== null && (
                <div className="flex animate-[rz-fade_.25s_ease] items-center gap-2 rounded-xl border border-l-[3px] border-rz-border border-l-rz-accent-fill bg-rz-surface px-4 py-3 text-[13px] font-semibold text-rz-ink shadow-[0_14px_32px_-12px_rgba(20,45,95,.45)]">
                    <span className="size-[7px] shrink-0 rounded-full bg-rz-accent-fill" />
                    {message}
                </div>
            )}
        </div>
    );

    return { toast, show };
}
