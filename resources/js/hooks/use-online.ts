import { useSyncExternalStore } from 'react';

const subscribe = (callback: () => void) => {
    window.addEventListener('online', callback);
    window.addEventListener('offline', callback);

    return () => {
        window.removeEventListener('online', callback);
        window.removeEventListener('offline', callback);
    };
};

/**
 * Whether the browser reports a network connection. It is a hint, not proof the server is
 * reachable: commands still settle through their own recorded outcome and lookup.
 */
export function useOnline(): boolean {
    return useSyncExternalStore(
        subscribe,
        () => navigator.onLine,
        () => true,
    );
}
