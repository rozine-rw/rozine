import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';

type Failure = 'server' | 'network';

/**
 * Says so plainly when a page could not be read (Phase 2: retry on every read screen), instead of
 * Inertia's raw error modal. It answers server errors (5xx, including 503) and dropped
 * connections on page visits; 4xx answers keep their own pages. Try again reads the page afresh
 * and never resends a command: commands settle through their own recorded outcome and lookup.
 */
export function ReadFailureNotice() {
    const { t } = useTranslation();
    const [failure, setFailure] = useState<Failure | null>(null);

    useEffect(() => {
        const offServer = router.on('httpException', (event) => {
            if (event.detail.response.status < 500) {
                return;
            }

            event.preventDefault();
            setFailure('server');
        });
        const offNetwork = router.on('networkError', (event) => {
            event.preventDefault();
            setFailure('network');
        });
        const offSuccess = router.on('success', () => setFailure(null));

        return () => {
            offServer();
            offNetwork();
            offSuccess();
        };
    }, []);

    if (failure === null) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex justify-center px-4 pb-[calc(env(safe-area-inset-bottom)+16px)]">
            <div
                role="alert"
                className="pointer-events-auto flex max-w-[560px] items-center gap-3 rounded-xl border border-rz-border bg-rz-surface px-3.5 py-2.5 text-[12px] leading-[1.5] text-rz-ink shadow-[0_6px_20px_rgba(15,23,42,.12)]"
            >
                <span>{t(`app.read_failure.${failure}`)}</span>
                <button
                    type="button"
                    onClick={() => {
                        setFailure(null);
                        router.reload();
                    }}
                    className="shrink-0 rounded-lg bg-rz-accent-fill px-3 py-1.5 text-[12px] font-semibold text-white"
                >
                    {t('app.read_failure.retry')}
                </button>
            </div>
        </div>
    );
}
