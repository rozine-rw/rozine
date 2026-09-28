import { act, renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { useReconnectRefresh } from '@/hooks/use-reconnect-refresh';

const reload = vi.fn();

vi.mock('@inertiajs/react', () => ({ router: { reload: () => reload() } }));

beforeEach(() => {
    reload.mockClear();
});

describe('Reconnect refresh', () => {
    it('reads the page again when the connection returns, and only then', () => {
        const { unmount } = renderHook(() => useReconnectRefresh());

        act(() => {
            window.dispatchEvent(new Event('focus'));
            window.dispatchEvent(new Event('offline'));
        });
        expect(reload).not.toHaveBeenCalled();

        act(() => {
            window.dispatchEvent(new Event('online'));
        });
        expect(reload).toHaveBeenCalledTimes(1);

        unmount();
        act(() => {
            window.dispatchEvent(new Event('online'));
        });
        expect(reload).toHaveBeenCalledTimes(1);
    });

    it('stays out of the way where the app reads on reconnect itself', () => {
        renderHook(() => useReconnectRefresh(false));

        act(() => {
            window.dispatchEvent(new Event('online'));
        });
        expect(reload).not.toHaveBeenCalled();
    });
});
