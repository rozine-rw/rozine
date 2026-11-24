import { renderHook } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { useAuditorPulse } from '@/components/auditor/pulse';
import { PULSE_INTERVAL_MS } from '@/hooks/use-access-refresh';
import { inertia } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

const LIVE = { url: '/auditor/version', method: 'get' as const };

const answer = (version: unknown) =>
    vi.fn(async () => new Response(JSON.stringify({ version })));

const tick = async () => {
    await vi.advanceTimersByTimeAsync(PULSE_INTERVAL_MS);
};

beforeEach(() => {
    inertia.reset();
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

describe('The live Auditor Home', () => {
    it('reloads the page once the server fingerprint differs from the one shown', async () => {
        const fetch = answer('next');

        vi.stubGlobal('fetch', fetch);
        renderHook(() => useAuditorPulse(LIVE, 'shown', true));

        await tick();
        expect(fetch).toHaveBeenCalledWith('/auditor/version', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        expect(inertia.reloads).toHaveLength(1);
    });

    it('leaves the page alone while the fingerprint matches or a command is unsettled', async () => {
        vi.stubGlobal('fetch', answer('shown'));
        const { unmount } = renderHook(() =>
            useAuditorPulse(LIVE, 'shown', true),
        );

        await tick();
        expect(inertia.reloads).toHaveLength(0);
        unmount();

        const fetch = answer('next');

        vi.stubGlobal('fetch', fetch);
        renderHook(() => useAuditorPulse(LIVE, 'shown', false));
        await tick();
        expect(fetch).not.toHaveBeenCalled();
        expect(inertia.reloads).toHaveLength(0);
    });

    it('never asks when the page was rendered without a fingerprint, as a preview is', async () => {
        const fetch = answer('next');

        vi.stubGlobal('fetch', fetch);
        renderHook(() => useAuditorPulse(LIVE, null, true));
        await tick();
        expect(fetch).not.toHaveBeenCalled();
    });
});
