import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';

type InertiaOptions = {
    layout: (
        name: string,
        page?: { props: Record<string, unknown> },
    ) => unknown;
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
    publicLayout: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    createInertiaApp: (options: InertiaOptions) => {
        state.options = options;
    },
}));

vi.mock('@/components/rozine/read-failure-notice', () => ({
    ReadFailureNotice: () => <span>Read failure outlet</span>,
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
vi.mock('@/layouts/public-layout', () => ({ default: state.publicLayout }));

/** Reads the catalog through the module instance the freshly loaded app provides. */
async function i18nProbe(): Promise<() => ReactNode> {
    const { useTranslation } = await import('@/hooks/use-translation');

    return function Probe() {
        const { t, locale } = useTranslation();

        return <span>{`${locale}: ${t('auth.login.email_label')}`}</span>;
    };
}

async function loadApplication(appName: string): Promise<InertiaOptions> {
    vi.resetModules();
    vi.stubEnv('VITE_APP_NAME', appName);
    state.options = undefined;

    await import('@/app');
    await vi.waitFor(() => {
        if (!state.options) {
            throw new Error('Not started yet.');
        }
    });

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

        expect(options.title('Dashboard')).toBe('Dashboard - Rozine');
        expect(options.title('')).toBe('Rozine');
        expect(options.layout('home')).toBe(state.publicLayout);
        expect(options.layout('welcome')).toBe(state.publicLayout);
        expect(options.layout('pulse')).toBe(state.publicLayout);
        expect(options.layout('audit/verify-seal')).toBe(state.publicLayout);
        /* The error page's not-found and 5xx answers are public; its refusals draw their own page. */
        expect(
            options.layout('identity/access-denied', {
                props: { status: 404 },
            }),
        ).toBe(state.publicLayout);
        expect(
            options.layout('identity/access-denied', {
                props: { status: 503 },
            }),
        ).toBe(state.publicLayout);
        expect(
            options.layout('identity/access-denied', {
                props: { status: 500 },
            }),
        ).toBe(state.publicLayout);
        expect(
            options.layout('identity/access-denied', {
                props: { status: 403 },
            }),
        ).toBeUndefined();
        expect(options.layout('auth/login')).toBe(state.authLayout);
        expect(options.layout('settings/profile')).toEqual([
            state.appLayout,
            state.settingsLayout,
        ]);
        expect(options.layout('dashboard')).toBeUndefined();
        expect(options.layout('anything-else')).toBe(state.appLayout);
        expect(options.strictMode).toBe(true);
        expect(options.progress).toEqual({ color: '#4B5563' });
        expect(state.initializeTheme).toHaveBeenCalledOnce();

        render(options.withApp(<span>Application page</span>));

        expect(
            screen.getByRole('region', { name: 'Tooltip provider' }),
        ).toHaveTextContent('Application page');
        expect(screen.getByText('Toast outlet')).toBeInTheDocument();
        expect(screen.getByText('Read failure outlet')).toBeInTheDocument();
    });

    it('fetches the page’s own catalog before it starts, and starts in English if that fails', async () => {
        document.documentElement.lang = 'fr';
        const french = await loadApplication('');
        const FrenchProbe = await i18nProbe();

        render(french.withApp(<FrenchProbe />));
        expect(screen.getByText('fr: Adresse e-mail')).toBeInTheDocument();

        vi.doMock('@/lib/i18n/catalogs/rw', () => {
            throw new Error('offline');
        });
        document.documentElement.lang = 'rw';
        const offline = await loadApplication('');
        const OfflineProbe = await i18nProbe();

        render(offline.withApp(<OfflineProbe />));
        expect(screen.getByText('rw: Email address')).toBeInTheDocument();

        vi.doUnmock('@/lib/i18n/catalogs/rw');
        document.documentElement.lang = '';
    });

    it('titles every tab Rozine, whatever name the environment carries', async () => {
        const options = await loadApplication('Laravel');

        expect(options.title('Dashboard')).toBe('Dashboard - Rozine');
        expect(options.title('')).toBe('Rozine');
        expect(state.initializeTheme).toHaveBeenCalledOnce();
    });
});
