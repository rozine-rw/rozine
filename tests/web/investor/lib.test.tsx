import { act, render, renderHook, screen } from '@testing-library/react';
import { renderToString } from 'react-dom/server';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    formatCompact,
    formatCompactBare,
    formatSigned,
    formatSignedCompact,
    formatSignedPct,
    intlTag,
    isNegative,
} from '@/lib/investor/format';
import { withQuery } from '@/lib/investor/links';
import { timeLeft, useServerNow } from '@/lib/investor/server-clock';
import { useFitScale } from '@/lib/investor/use-fit-scale';
import { useWide } from '@/lib/investor/use-wide';

const rwf = (amount: string) => ({ currency: 'RWF' as const, amount });

afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
    vi.useRealTimers();
});

describe('Investor formatting', () => {
    it('writes compact, signed and percentage figures as the design does', () => {
        expect(formatCompactBare(rwf('950'))).toBe('950');
        expect(formatCompactBare(rwf('581400'))).toBe('581K');
        expect(formatCompactBare(rwf('1500000'))).toBe('1.5M');
        expect(formatCompactBare(rwf('6000000'))).toBe('6M');
        expect(formatCompactBare(rwf('6000000'), true)).toBe('6.0M');
        expect(formatCompact(rwf('-250000'))).toBe('RWF 250K');
        expect(formatSigned(rwf('106667'))).toBe('+RWF 106,667');
        expect(formatSigned(rwf('-5000'))).toBe('-RWF 5,000');
        expect(formatSignedCompact(rwf('3100000'))).toBe('+RWF 3.1M');
        expect(formatSignedCompact(rwf('-250000'))).toBe('-RWF 250K');
        expect(isNegative(rwf('-1'))).toBe(true);
        expect(isNegative(rwf('0'))).toBe(false);
        expect(formatSignedPct('13.3')).toBe('+13.3%');
        expect(formatSignedPct('-2.0')).toBe('-2.0%');
        expect(intlTag('en')).toBe('en-GB');
        expect(intlTag('fr')).toBe('fr');
    });

    it('adds the investor’s own choices to a server link', () => {
        const link = { url: '/checkout', method: 'get' as const };

        expect(withQuery(link, { deal: 'g', units: 2 })).toEqual({
            url: '/checkout?deal=g&units=2',
            method: 'get',
        });
        expect(
            withQuery({ ...link, url: '/checkout?from=deck' }, { units: 1 })
                .url,
        ).toBe('/checkout?from=deck&units=1');
    });
});

describe('The server clock', () => {
    it('counts whole days, then the last day as a clock, then closed', () => {
        const now = Date.parse('2026-09-23T09:00:00+02:00');

        expect(timeLeft('2026-10-05T09:00:00+02:00', now)).toEqual({
            kind: 'days',
            days: 12,
        });
        expect(timeLeft('2026-09-23T10:02:03+02:00', now)).toEqual({
            kind: 'clock',
            label: '01:02:03',
        });
        expect(timeLeft('2026-09-23T09:00:00+02:00', now)).toEqual({
            kind: 'closed',
        });
    });

    it('offsets the browser clock to the server’s and ticks only when live', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-01-01T00:00:00Z'));

        const { result, unmount } = renderHook(() =>
            useServerNow('2026-09-23T07:00:00Z', true),
        );

        expect(result.current).toBe(Date.parse('2026-09-23T07:00:00Z'));
        act(() => vi.advanceTimersByTime(2000));
        expect(result.current).toBe(Date.parse('2026-09-23T07:00:02Z'));
        unmount();
    });
});

describe('useWide', () => {
    it('reads the lg breakpoint and follows its changes', () => {
        const listeners: (() => void)[] = [];
        let matches = false;

        window.matchMedia = ((query: string) => ({
            get matches() {
                return matches;
            },
            media: query,
            addEventListener: (_: string, callback: () => void) =>
                listeners.push(callback),
            removeEventListener: vi.fn(),
        })) as unknown as typeof window.matchMedia;

        const { result } = renderHook(() => useWide());

        expect(result.current).toBe(false);
        matches = true;
        act(() => listeners.forEach((listener) => listener()));
        expect(result.current).toBe(true);
    });

    it('renders the phone layout on the server', () => {
        function Probe() {
            return <span>{useWide() ? 'wide' : 'narrow'}</span>;
        }

        expect(renderToString(<Probe />)).toContain('narrow');
    });

    it('treats a browser without matchMedia as a phone', () => {
        vi.stubGlobal('matchMedia', undefined);
        const { result, unmount } = renderHook(() => useWide());

        expect(result.current).toBe(false);
        unmount();
    });
});

function Fitted() {
    const { frame, scale, width } = useFitScale(720);

    return (
        <div ref={frame} data-testid="frame">
            {scale}:{width ?? 'none'}
        </div>
    );
}

describe('useFitScale', () => {
    it('keeps the design scale without ResizeObserver', () => {
        vi.stubGlobal('ResizeObserver', undefined);
        render(<Fitted />);

        expect(screen.getByTestId('frame')).toHaveTextContent('1:none');
    });

    it('ignores a frame that has not been laid out yet', () => {
        vi.stubGlobal(
            'ResizeObserver',
            class {
                observe = vi.fn();
                disconnect = vi.fn();
            },
        );
        const height = vi
            .spyOn(HTMLElement.prototype, 'clientHeight', 'get')
            .mockReturnValue(0);
        const { unmount } = render(<Fitted />);

        expect(screen.getByTestId('frame')).toHaveTextContent('1:none');
        unmount();

        height.mockReturnValue(668);
        vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(
            0,
        );
        render(<Fitted />);
        expect(screen.getByTestId('frame')).toHaveTextContent('1:none');
    });

    it('scales a short pane down and widens the canvas to match', () => {
        vi.stubGlobal(
            'ResizeObserver',
            class {
                observe = vi.fn();
                disconnect = vi.fn();
            },
        );
        vi.spyOn(HTMLElement.prototype, 'clientHeight', 'get').mockReturnValue(
            668,
        );
        vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(
            815,
        );
        render(<Fitted />);

        expect(screen.getByTestId('frame')).toHaveTextContent('0.928:878');
    });
});
