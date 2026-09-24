import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/** Reconcile access after another tab or a disconnected session may have changed it. */
export function useAccessRefresh(
    properties: string[],
    enabled = true,
): boolean {
    const keys = properties.join(',');
    const inFlight = useRef(false);
    const [refreshing, setRefreshing] = useState(false);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        const refresh = () => {
            if (inFlight.current) {
                return;
            }

            inFlight.current = true;
            setRefreshing(true);
            router.reload({
                only: keys.split(','),
                onFinish: () => {
                    inFlight.current = false;
                    setRefreshing(false);
                },
            });
        };
        const visibility = () => {
            if (!document.hidden) {
                refresh();
            }
        };
        window.addEventListener('focus', refresh);
        window.addEventListener('online', refresh);
        document.addEventListener('visibilitychange', visibility);

        return () => {
            window.removeEventListener('focus', refresh);
            window.removeEventListener('online', refresh);
            document.removeEventListener('visibilitychange', visibility);
        };
    }, [enabled, keys]);

    return refreshing;
}
