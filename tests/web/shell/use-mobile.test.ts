import { afterEach, describe, expect, it, vi } from 'vitest';

afterEach(() => {
    vi.doUnmock('react');
    vi.resetModules();
    vi.unstubAllGlobals();
});

describe('useIsMobile', () => {
    it('subscribes to the shared media query and returns its snapshot', async () => {
        const addEventListener = vi.fn();
        const removeEventListener = vi.fn();
        const mediaQuery = {
            matches: true,
            addEventListener,
            removeEventListener,
        } as unknown as MediaQueryList;
        const matchMedia = vi.fn(() => mediaQuery);
        const useSyncExternalStore = vi.fn(
            (
                subscribe: (
                    callback: (event: MediaQueryListEvent) => void,
                ) => (() => void) | undefined,
                getSnapshot: () => boolean,
            ) => {
                const callback = vi.fn();
                const unsubscribe = subscribe(callback);

                unsubscribe?.();

                return getSnapshot();
            },
        );

        vi.stubGlobal('window', { matchMedia });
        vi.doMock('react', () => ({ useSyncExternalStore }));

        const { useIsMobile } = await import('@/hooks/use-mobile');

        expect(useIsMobile()).toBe(true);
        expect(matchMedia).toHaveBeenCalledWith('(max-width: 767px)');
        expect(addEventListener).toHaveBeenCalledWith(
            'change',
            expect.any(Function),
        );
        expect(removeEventListener).toHaveBeenCalledWith(
            'change',
            expect.any(Function),
        );
    });

    it('uses the server snapshot and a no-op subscription without window', async () => {
        const useSyncExternalStore = vi.fn(
            (
                subscribe: (
                    callback: (event: MediaQueryListEvent) => void,
                ) => (() => void) | undefined,
                _getSnapshot: () => boolean,
                getServerSnapshot: () => boolean,
            ) => {
                subscribe(vi.fn())?.();
                expect(_getSnapshot()).toBe(false);

                return getServerSnapshot();
            },
        );

        vi.stubGlobal('window', undefined);
        vi.doMock('react', () => ({ useSyncExternalStore }));

        const { useIsMobile } = await import('@/hooks/use-mobile');

        expect(useIsMobile()).toBe(false);
        expect(useSyncExternalStore).toHaveBeenCalledOnce();
    });
});
