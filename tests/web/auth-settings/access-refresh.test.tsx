import { act, renderHook } from '@testing-library/react';
import { beforeEach, expect, it, vi } from 'vite-plus/test';
import { useAccessRefresh } from '@/hooks/use-access-refresh';

const reload = vi.hoisted(() => vi.fn());
vi.mock('@inertiajs/react', () => ({ router: { reload } }));
beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(document, 'hidden', 'get').mockReturnValue(false);
});

it('refreshes on focus and reconnect, coalesces concurrent signals and releases its listeners', () => {
    const { result, unmount } = renderHook(() =>
        useAccessRefresh(['identity']),
    );
    act(() => {
        window.dispatchEvent(new Event('focus'));
        window.dispatchEvent(new Event('online'));
    });
    expect(result.current).toBe(true);
    expect(reload).toHaveBeenCalledOnce();
    expect(reload).toHaveBeenCalledWith(
        expect.objectContaining({ only: ['identity'] }),
    );
    act(() => {
        reload.mock.calls[0][0].onFinish();
    });
    expect(result.current).toBe(false);
    act(() => {
        window.dispatchEvent(new Event('online'));
    });
    expect(reload).toHaveBeenCalledTimes(2);
    unmount();
    act(() => {
        window.dispatchEvent(new Event('focus'));
        document.dispatchEvent(new Event('visibilitychange'));
    });
    expect(reload).toHaveBeenCalledTimes(2);
});
it('refreshes a newly visible document but does not fetch in the background', () => {
    renderHook(() => useAccessRefresh(['staff_access']));
    vi.spyOn(document, 'hidden', 'get').mockReturnValue(true);
    act(() => {
        document.dispatchEvent(new Event('visibilitychange'));
    });
    expect(reload).not.toHaveBeenCalled();
    vi.spyOn(document, 'hidden', 'get').mockReturnValue(false);
    act(() => {
        document.dispatchEvent(new Event('visibilitychange'));
    });
    expect(reload).toHaveBeenCalledWith(
        expect.objectContaining({ only: ['staff_access'] }),
    );
});
it('does not reconcile fixture previews against a real session', () => {
    renderHook(() => useAccessRefresh(['identity'], false));
    act(() => {
        window.dispatchEvent(new Event('focus'));
        window.dispatchEvent(new Event('online'));
    });
    expect(reload).not.toHaveBeenCalled();
});
it('reloads the full page facts when no properties are named', () => {
    renderHook(() => useAccessRefresh(null));
    act(() => {
        window.dispatchEvent(new Event('focus'));
    });
    expect(reload).toHaveBeenCalledOnce();
    expect(reload.mock.calls[0][0]).not.toHaveProperty('only');
});
it('holds a signal while a command is unsettled and refreshes once it settles', () => {
    const { rerender } = renderHook(
        ({ settled }) => useAccessRefresh(null, true, settled),
        { initialProps: { settled: false } },
    );
    act(() => {
        window.dispatchEvent(new Event('focus'));
        window.dispatchEvent(new Event('online'));
    });
    expect(reload).not.toHaveBeenCalled();
    rerender({ settled: true });
    expect(reload).toHaveBeenCalledOnce();
    rerender({ settled: false });
    rerender({ settled: true });
    expect(reload).toHaveBeenCalledOnce();
});
it('keeps a held refresh to the named properties', () => {
    const { rerender } = renderHook(
        ({ settled }) => useAccessRefresh(['identity'], true, settled),
        { initialProps: { settled: false } },
    );
    act(() => {
        window.dispatchEvent(new Event('focus'));
    });
    rerender({ settled: true });
    expect(reload).toHaveBeenCalledWith(
        expect.objectContaining({ only: ['identity'] }),
    );
});
