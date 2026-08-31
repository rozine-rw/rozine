import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';

type InertiaOptions = {
    layout: (name: string) => unknown;
    progress: { color: string };
    strictMode: boolean;
    title: (title: string) => string;
    withApp: (app: ReactNode) => ReactNode;
};

const state = vi.hoisted(() => ({
    appLayout: vi.fn(),
    authLayout: vi.fn(),
    initializeTheme: vi.fn(),
    options: undefined as InertiaOptions | undefined,
    settingsLayout: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    createInertiaApp: (options: InertiaOptions) => {
        state.options = options;
    },
}));

vi.mock('@/components/ui/sonner', () => ({
    Toaster: () => <span>Toast outlet</span>,
}));

vi.mock('@/components/ui/tooltip', () => ({
    TooltipProvider: ({ children }: { children?: ReactNode }) => (
        <section aria-label="Tooltip provider">{children}</section>
    ),
}));

vi.mock('@/hooks/use-appearance', () => ({
    initializeTheme: state.initializeTheme,
}));

vi.mock('@/layouts/app-layout', () => ({ default: state.appLayout }));
vi.mock('@/layouts/auth-layout', () => ({ default: state.authLayout }));
vi.mock('@/layouts/settings/layout', () => ({ default: state.settingsLayout }));

async function loadApplication(appName: string): Promise<InertiaOptions> {
    vi.resetModules();
    vi.stubEnv('VITE_APP_NAME', appName);
    state.options = undefined;

    await import('@/app');

    if (!state.options) {
        throw new Error('The Inertia application was not configured.');
    }

    return state.options;
}

afterEach(() => {
    vi.unstubAllEnvs();
    vi.clearAllMocks();
});

describe('application entry point', () => {
    it('configures fallback titles, layouts, progress, and providers', async () => {
        const options = await loadApplication('');

        expect(options.title('Dashboard')).toBe('Dashboard - Laravel');
        expect(options.title('')).toBe('Laravel');
        expect(options.layout('welcome')).toBeNull();
        expect(options.layout('pulse')).toBeNull();
        expect(options.layout('auth/login')).toBe(state.authLayout);
        expect(options.layout('settings/profile')).toEqual([
            state.appLayout,
            state.settingsLayout,
        ]);
        expect(options.layout('dashboard')).toBe(state.appLayout);
        expect(options.strictMode).toBe(true);
        expect(options.progress).toEqual({ color: '#4B5563' });
        expect(state.initializeTheme).toHaveBeenCalledOnce();

        render(options.withApp(<span>Application page</span>));

        expect(
            screen.getByRole('region', { name: 'Tooltip provider' }),
        ).toHaveTextContent('Application page');
        expect(screen.getByText('Toast outlet')).toBeInTheDocument();
    });

    it('uses the configured application name', async () => {
        const options = await loadApplication('Rozine');

        expect(options.title('Dashboard')).toBe('Dashboard - Rozine');
        expect(options.title('')).toBe('Rozine');
        expect(state.initializeTheme).toHaveBeenCalledOnce();
    });
});
