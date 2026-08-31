import { act, renderHook } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { cn, toUrl } from '@/lib/utils';

const mocks = vi.hoisted(() => ({
    pageUrl: '/settings/profile?tab=account',
    flashListener: undefined as
        | ((event: CustomEvent<{ flash?: unknown }>) => void)
        | undefined,
    routerCleanup: vi.fn(),
    routerOn: vi.fn(),
    success: vi.fn(),
    info: vi.fn(),
    warning: vi.fn(),
    error: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    router: {
        on: mocks.routerOn.mockImplementation(
            (
                _event: string,
                listener: (event: CustomEvent<{ flash?: unknown }>) => void,
            ) => {
                mocks.flashListener = listener;

                return mocks.routerCleanup;
            },
        ),
    },
    usePage: () => ({ url: mocks.pageUrl }),
}));

vi.mock('sonner', () => ({
    toast: {
        success: mocks.success,
        info: mocks.info,
        warning: mocks.warning,
        error: mocks.error,
    },
}));

afterEach(() => {
    mocks.pageUrl = '/settings/profile?tab=account';
    mocks.flashListener = undefined;
    vi.clearAllMocks();
    vi.unstubAllGlobals();
});

describe('current URL behavior', () => {
    it('compares relative, object, parent, and absolute URLs', () => {
        const { result } = renderHook(() => useCurrentUrl());

        expect(result.current.currentUrl).toBe('/settings/profile');
        expect(result.current.isCurrentUrl('/settings/profile')).toBe(true);
        expect(result.current.isCurrentUrl('/settings')).toBe(false);
        expect(result.current.isCurrentOrParentUrl('/settings')).toBe(true);
        expect(
            result.current.isCurrentUrl(
                { url: '/override', method: 'get' },
                '/override',
            ),
        ).toBe(true);
        expect(
            result.current.isCurrentUrl(
                'https://rozine.test/settings/profile?source=test',
            ),
        ).toBe(true);
        expect(
            result.current.isCurrentUrl(
                'https://rozine.test/dashboard',
                '/dashboard',
            ),
        ).toBe(true);
        expect(result.current.isCurrentUrl('http://[invalid')).toBe(false);
    });

    it('uses a deterministic origin when rendering without a browser window', () => {
        vi.stubGlobal('window', undefined);

        expect(useCurrentUrl().currentUrl).toBe('/settings/profile');
    });

    it('returns the requested current and fallback values', () => {
        const { result } = renderHook(() => useCurrentUrl());

        expect(
            result.current.whenCurrentUrl('/settings/profile', 'active'),
        ).toBe('active');
        expect(
            result.current.whenCurrentUrl('/dashboard', 'active', 'inactive'),
        ).toBe('inactive');
        expect(
            result.current.whenCurrentUrl('/dashboard', 'active'),
        ).toBeNull();
    });
});

describe('flash notifications', () => {
    it('ignores empty flashes and dispatches typed toast messages', () => {
        const { unmount } = renderHook(() => useFlashToast());

        expect(mocks.routerOn).toHaveBeenCalledWith(
            'flash',
            expect.any(Function),
        );

        act(() => mocks.flashListener?.(new CustomEvent('flash')));

        for (const type of ['success', 'info', 'warning', 'error'] as const) {
            act(() =>
                mocks.flashListener?.(
                    new CustomEvent('flash', {
                        detail: {
                            flash: {
                                toast: { type, message: `${type} message` },
                            },
                        },
                    }),
                ),
            );
        }

        expect(mocks.success).toHaveBeenCalledWith('success message');
        expect(mocks.info).toHaveBeenCalledWith('info message');
        expect(mocks.warning).toHaveBeenCalledWith('warning message');
        expect(mocks.error).toHaveBeenCalledWith('error message');

        unmount();
        expect(mocks.routerCleanup).toHaveBeenCalledOnce();
    });
});

describe('navigation and class utilities', () => {
    it('cleans pointer-event state for mobile navigation', () => {
        document.body.style.pointerEvents = 'none';
        const { result } = renderHook(() => useMobileNavigation());

        act(() => result.current());

        expect(document.body).toHaveStyle({ pointerEvents: '' });
    });

    it('merges classes and resolves string and object links', () => {
        expect(cn('px-2', false, 'px-4')).toBe('px-4');
        expect(toUrl('/dashboard')).toBe('/dashboard');
        expect(toUrl({ url: '/profile', method: 'get' })).toBe('/profile');
    });
});
