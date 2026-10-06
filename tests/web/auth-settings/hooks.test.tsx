// eslint-disable-next-line testing-library/no-manual-cleanup
import { act, cleanup, renderHook } from '@testing-library/react';
import { renderToString } from 'react-dom/server';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { initializeTheme, useAppearance } from '@/hooks/use-appearance';
import { useClipboard } from '@/hooks/use-clipboard';
import { OTP_MAX_LENGTH, useTwoFactorAuth } from '@/hooks/use-two-factor-auth';

const http = vi.hoisted(() => ({
    submit: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    useHttp: () => ({ submit: http.submit }),
}));

type MediaListener = () => void;

let prefersDark = false;
let mediaListener: MediaListener | undefined;

function installMatchMedia(): void {
    Object.defineProperty(window, 'matchMedia', {
        configurable: true,
        value: vi.fn(() => ({
            matches: prefersDark,
            media: '(prefers-color-scheme: dark)',
            onchange: null,
            addEventListener: (_event: string, listener: MediaListener) => {
                mediaListener = listener;
            },
            removeEventListener: vi.fn(),
            addListener: vi.fn(),
            removeListener: vi.fn(),
            dispatchEvent: vi.fn(),
        })),
    });
}

beforeEach(() => {
    prefersDark = false;
    mediaListener = undefined;
    localStorage.clear();
    document.cookie = 'appearance=;max-age=0;path=/';
    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = '';
    installMatchMedia();
});

afterEach(() => {
    vi.unstubAllGlobals();
    cleanup();
    vi.restoreAllMocks();
});

describe('useClipboard', () => {
    it('copies text and records the copied value', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', {
            configurable: true,
            value: { writeText },
        });
        const { result } = renderHook(() => useClipboard());

        await act(async () => {
            expect(await result.current[1]('secret')).toBe(true);
        });

        expect(writeText).toHaveBeenCalledWith('secret');
        expect(result.current[0]).toBe('secret');
    });

    it('reports unavailable and failed clipboard writes', async () => {
        const warning = vi.spyOn(console, 'warn').mockImplementation(() => {});
        Object.defineProperty(navigator, 'clipboard', {
            configurable: true,
            value: undefined,
        });
        const { result, rerender } = renderHook(() => useClipboard());

        await act(async () => {
            expect(await result.current[1]('unavailable')).toBe(false);
        });
        expect(warning).toHaveBeenCalledWith('Clipboard not supported');

        Object.defineProperty(navigator, 'clipboard', {
            configurable: true,
            value: {
                writeText: vi.fn().mockRejectedValue(new Error('denied')),
            },
        });
        rerender();

        await act(async () => {
            expect(await result.current[1]('denied')).toBe(false);
        });
        expect(warning).toHaveBeenCalledWith('Copy failed', expect.any(Error));
        expect(result.current[0]).toBeNull();
    });
});

describe('useAppearance', () => {
    it('initializes the system default and reacts to system theme changes', () => {
        prefersDark = true;
        initializeTheme();

        expect(localStorage.getItem('appearance')).toBe('system');
        expect(document.cookie).toContain('appearance=system');
        expect(document.documentElement).toHaveClass('dark');
        expect(document.documentElement).toHaveStyle({
            colorScheme: 'dark',
        });

        prefersDark = false;
        mediaListener?.();

        expect(document.documentElement).not.toHaveClass('dark');
        expect(document.documentElement).toHaveStyle({
            colorScheme: 'light',
        });
    });

    it('loads a stored appearance and notifies subscribers of updates', () => {
        localStorage.setItem('appearance', 'light');
        initializeTheme();
        const { result: firstResult } = renderHook(() => useAppearance());
        const { result: secondResult, unmount: unmountSecond } = renderHook(
            () => useAppearance(),
        );

        expect(firstResult.current.appearance).toBe('light');
        expect(firstResult.current.resolvedAppearance).toBe('light');

        act(() => firstResult.current.updateAppearance('dark'));

        expect(firstResult.current.appearance).toBe('dark');
        expect(secondResult.current.appearance).toBe('dark');
        expect(secondResult.current.resolvedAppearance).toBe('dark');
        expect(localStorage.getItem('appearance')).toBe('dark');
        expect(document.cookie).toContain('appearance=dark');

        unmountSecond();
        act(() => firstResult.current.updateAppearance('system'));
        expect(firstResult.current.appearance).toBe('system');
    });

    it('safely initializes and resolves system appearance without a window', () => {
        vi.stubGlobal('window', undefined);

        expect(() => initializeTheme()).not.toThrow();

        function ServerAppearance() {
            const value = useAppearance();

            return <span>{value.resolvedAppearance}</span>;
        }

        expect(renderToString(<ServerAppearance />)).toContain('light');
    });

    it('updates persistence safely when document is unavailable', () => {
        initializeTheme();
        const { result } = renderHook(() => useAppearance());

        vi.stubGlobal('document', undefined);

        act(() => result.current.updateAppearance('light'));
        expect(localStorage.getItem('appearance')).toBe('light');
    });
});

describe('useTwoFactorAuth', () => {
    it('fetches and clears all two-factor setup data', async () => {
        http.submit
            .mockResolvedValueOnce({ svg: '<svg />', url: 'otpauth://url' })
            .mockResolvedValueOnce({ secretKey: 'SETUP-KEY' })
            .mockResolvedValueOnce(['one', 'two']);
        const { result } = renderHook(() => useTwoFactorAuth());

        expect(OTP_MAX_LENGTH).toBe(6);
        expect(result.current.hasSetupData).toBe(false);

        await act(async () => result.current.fetchSetupData());
        expect(result.current.qrCodeSvg).toBe('<svg />');
        expect(result.current.manualSetupKey).toBe('SETUP-KEY');
        expect(result.current.hasSetupData).toBe(true);

        await act(async () => result.current.fetchRecoveryCodes());
        expect(result.current.recoveryCodesList).toEqual(['one', 'two']);

        act(() => result.current.clearErrors());
        act(() => result.current.clearSetupData());
        expect(result.current.qrCodeSvg).toBeNull();
        expect(result.current.manualSetupKey).toBeNull();

        act(() => result.current.clearTwoFactorAuthData());
        expect(result.current.recoveryCodesList).toEqual([]);
    });

    it('collects independent setup and recovery-code failures', async () => {
        http.submit
            .mockRejectedValueOnce(new Error('qr failed'))
            .mockRejectedValueOnce(new Error('key failed'))
            .mockRejectedValueOnce(new Error('codes failed'));
        const { result } = renderHook(() => useTwoFactorAuth());

        await act(async () => result.current.fetchSetupData());
        expect(result.current.errors).toEqual([
            'Failed to fetch QR code',
            'Failed to fetch a setup key',
        ]);
        expect(result.current.qrCodeSvg).toBeNull();
        expect(result.current.manualSetupKey).toBeNull();

        await act(async () => result.current.fetchRecoveryCodes());
        expect(result.current.errors).toEqual([
            'Failed to fetch recovery codes',
        ]);
        expect(result.current.recoveryCodesList).toEqual([]);
    });
});
