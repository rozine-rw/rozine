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
const pulseAnswers = (answers: unknown[]) => {
    const fetcher = vi.fn(async () => {
        const body = answers.shift();

        if (body === undefined) {
            throw new TypeError('offline');
        }

        return { ok: true, json: async () => body } as Response;
    });
    vi.stubGlobal('fetch', fetcher);

    return fetcher;
};
const tick = async (ms = 5000) => {
    await act(async () => {
        await vi.advanceTimersByTimeAsync(ms);
    });
};
it('asks the pulse while visible and reloads only when it differs from what is shown', async () => {
    vi.useFakeTimers();
    const fetcher = pulseAnswers([
        { version: 'a' },
        { version: 'a' },
        { version: 'b' },
        { nothing: true },
    ]);
    const { rerender, unmount } = renderHook(
        ({ shown }) =>
            useAccessRefresh(['identity', 'hub'], true, true, {
                url: '/dashboard/version',
                shown,
            }),
        { initialProps: { shown: 'a' } },
    );
    await tick(0);
    expect(fetcher).toHaveBeenCalledWith(
        '/dashboard/version',
        expect.objectContaining({ credentials: 'same-origin' }),
    );
    expect(reload).not.toHaveBeenCalled();
    await tick();
    expect(reload).not.toHaveBeenCalled();
    await tick();
    expect(reload).toHaveBeenCalledOnce();
    expect(reload).toHaveBeenCalledWith(
        expect.objectContaining({ only: ['identity', 'hub'] }),
    );
    act(() => {
        reload.mock.calls[0][0].onFinish();
    });
    rerender({ shown: 'b' });
    // A malformed answer and a network failure change nothing.
    await tick(10000);
    expect(reload).toHaveBeenCalledOnce();
    // A hidden page does not ask at all.
    vi.spyOn(document, 'hidden', 'get').mockReturnValue(true);
    const calls = fetcher.mock.calls.length;
    await tick();
    expect(fetcher).toHaveBeenCalledTimes(calls);
    unmount();
    vi.unstubAllGlobals();
    vi.useRealTimers();
});
it('reloads at once when the facts moved on before the first ask', async () => {
    vi.useFakeTimers();
    pulseAnswers([{ version: 'newer' }]);
    renderHook(() =>
        useAccessRefresh(['hub'], true, true, {
            url: '/dashboard/version',
            shown: 'rendered',
        }),
    );
    await tick(0);
    expect(reload).toHaveBeenCalledOnce();
    vi.unstubAllGlobals();
    vi.useRealTimers();
});
it('tries again until the newer facts are shown when a reload overlaps or fails', async () => {
    vi.useFakeTimers();
    pulseAnswers([
        { version: 'b' },
        { version: 'b' },
        { version: 'b' },
        { version: 'b' },
    ]);
    const { rerender } = renderHook(
        ({ shown }) =>
            useAccessRefresh(['hub'], true, true, {
                url: '/dashboard/version',
                shown,
            }),
        { initialProps: { shown: 'a' } },
    );
    // The first reload is still in flight at the next ask, which does not start another.
    await tick(0);
    expect(reload).toHaveBeenCalledOnce();
    await tick();
    expect(reload).toHaveBeenCalledOnce();
    // It ends without new facts (a failed reload): the next ask tries again.
    act(() => {
        reload.mock.calls[0][0].onFinish();
    });
    await tick();
    expect(reload).toHaveBeenCalledTimes(2);
    // Once the newer facts are on screen the asks stop reloading.
    act(() => {
        reload.mock.calls[1][0].onFinish();
    });
    rerender({ shown: 'b' });
    await tick();
    expect(reload).toHaveBeenCalledTimes(2);
    vi.unstubAllGlobals();
    vi.useRealTimers();
});
it('holds a pulse reload while a command is unsettled', async () => {
    vi.useFakeTimers();
    pulseAnswers([{ version: 'b' }]);
    const { rerender } = renderHook(
        ({ settled }) =>
            useAccessRefresh(['hub'], true, settled, {
                url: '/dashboard/version',
                shown: 'a',
            }),
        { initialProps: { settled: false } },
    );
    await tick(0);
    expect(reload).not.toHaveBeenCalled();
    rerender({ settled: true });
    expect(reload).toHaveBeenCalledOnce();
    vi.unstubAllGlobals();
    vi.useRealTimers();
});
it('ignores an error answer and a late answer after unmounting', async () => {
    vi.useFakeTimers();
    let release: (value: Response) => void = () => undefined;
    const fetcher = vi
        .fn()
        .mockResolvedValueOnce({ ok: false } as Response)
        .mockImplementationOnce(
            () => new Promise<Response>((resolve) => (release = resolve)),
        );
    vi.stubGlobal('fetch', fetcher);
    const { unmount } = renderHook(() =>
        useAccessRefresh(null, true, true, {
            url: '/dashboard/version',
            shown: 'y',
        }),
    );
    await act(async () => {
        await vi.advanceTimersByTimeAsync(5000);
    });
    unmount();
    await act(async () => {
        release({ ok: true, json: async () => ({ version: 'z' }) } as Response);
        await vi.advanceTimersByTimeAsync(0);
    });
    expect(reload).not.toHaveBeenCalled();
    vi.unstubAllGlobals();
    vi.useRealTimers();
});
