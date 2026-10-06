import { render, screen } from '@testing-library/react';
import type { ComponentProps, ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import AppHeaderLayout from '@/layouts/app/app-header-layout';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import AppLayout from '@/layouts/app-layout';
import AuthCardLayout from '@/layouts/auth/auth-card-layout';
import AuthSimpleLayout from '@/layouts/auth/auth-simple-layout';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';
import AuthLayout from '@/layouts/auth-layout';
import Welcome from '@/pages/welcome';
import type { User } from '@/types';

const state = vi.hoisted(() => ({
    page: {
        name: 'Rozine',
        auth: { user: undefined as User | undefined },
        nonLiveEnvironment: null as 'demo' | 'uat' | null,
    },
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => (
        <span data-testid="page-title">{title}</span>
    ),
    Link: ({
        children,
        href,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & {
        href: string | { url: string };
    }) => (
        <a href={typeof href === 'string' ? href : href.url} {...props}>
            {children}
        </a>
    ),
    usePage: () => ({ props: state.page }),
}));

vi.mock('@/components/app-content', () => ({
    AppContent: ({
        children,
        variant,
    }: {
        children?: ReactNode;
        variant?: string;
    }) => <main data-testid={`app-content-${variant}`}>{children}</main>,
}));

vi.mock('@/components/app-header', () => ({
    AppHeader: ({ breadcrumbs = [] }: { breadcrumbs?: unknown[] }) => (
        <header data-testid="app-header">{breadcrumbs.length}</header>
    ),
}));

vi.mock('@/components/app-logo-icon', () => ({
    default: () => <span aria-label="Rozine mark" role="img" />,
}));

vi.mock('@/components/app-shell', () => ({
    AppShell: ({
        children,
        variant,
    }: {
        children?: ReactNode;
        variant?: string;
    }) => <section data-testid={`app-shell-${variant}`}>{children}</section>,
}));

vi.mock('@/components/app-sidebar', () => ({
    AppSidebar: () => <aside aria-label="Application sidebar" />,
}));

vi.mock('@/components/app-sidebar-header', () => ({
    AppSidebarHeader: ({ breadcrumbs = [] }: { breadcrumbs?: unknown[] }) => (
        <nav aria-label="Sidebar breadcrumbs">{breadcrumbs.length}</nav>
    ),
}));

vi.mock('@/components/ui/card', () => {
    const Wrapper = ({ children }: { children?: ReactNode }) => (
        <div>{children}</div>
    );

    return {
        Card: Wrapper,
        CardContent: Wrapper,
        CardDescription: Wrapper,
        CardHeader: Wrapper,
        CardTitle: Wrapper,
    };
});

vi.mock('@/components/ui/placeholder-pattern', () => ({
    PlaceholderPattern: () => <svg aria-label="Placeholder pattern" />,
}));

const user: User = {
    id: 1,
    name: 'Ada Lovelace',
    email: 'ada@example.test',
    avatar: undefined,
    email_verified_at: '2026-08-24T00:00:00Z',
    created_at: '2026-08-24T00:00:00Z',
    updated_at: '2026-08-24T00:00:00Z',
};

afterEach(() => {
    state.page.auth.user = undefined;
    state.page.nonLiveEnvironment = null;
});

describe('application layouts', () => {
    it('shows one notice in each application and authentication shell', () => {
        state.page.nonLiveEnvironment = 'uat';
        const { rerender } = render(<AppLayout>Content</AppLayout>);
        expect(
            screen.getAllByRole('note', { name: 'UAT — not live' }),
        ).toHaveLength(1);

        rerender(<AppHeaderLayout>Content</AppHeaderLayout>);
        expect(
            screen.getAllByRole('note', { name: 'UAT — not live' }),
        ).toHaveLength(1);

        rerender(<AuthLayout>Content</AuthLayout>);
        expect(
            screen.getAllByRole('note', { name: 'UAT — not live' }),
        ).toHaveLength(1);
    });

    it('forwards default and supplied breadcrumbs through the app layout', () => {
        const { rerender } = render(<AppLayout>Default content</AppLayout>);

        expect(
            screen.getByRole('navigation', { name: 'Sidebar breadcrumbs' }),
        ).toHaveTextContent('0');
        expect(screen.getByText('Default content')).toBeInTheDocument();

        rerender(
            <AppLayout
                breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}
            >
                Supplied content
            </AppLayout>,
        );

        expect(
            screen.getByRole('navigation', { name: 'Sidebar breadcrumbs' }),
        ).toHaveTextContent('1');
    });

    it('composes header and sidebar layout variants', () => {
        const breadcrumbs = [{ title: 'Dashboard', href: '/dashboard' }];
        const { rerender } = render(
            <AppHeaderLayout breadcrumbs={breadcrumbs}>
                Header page
            </AppHeaderLayout>,
        );

        expect(screen.getByTestId('app-shell-header')).toBeInTheDocument();
        expect(screen.getByTestId('app-header')).toHaveTextContent('1');
        expect(screen.getByTestId('app-content-header')).toHaveTextContent(
            'Header page',
        );

        rerender(<AppSidebarLayout>Sidebar page</AppSidebarLayout>);
        expect(screen.getByTestId('app-shell-sidebar')).toBeInTheDocument();
        expect(
            screen.getByRole('complementary', { name: 'Application sidebar' }),
        ).toBeInTheDocument();
        expect(screen.getByTestId('app-content-sidebar')).toHaveTextContent(
            'Sidebar page',
        );

        rerender(
            <AppSidebarLayout breadcrumbs={breadcrumbs}>
                Breadcrumb page
            </AppSidebarLayout>,
        );
        expect(
            screen.getByRole('navigation', { name: 'Sidebar breadcrumbs' }),
        ).toHaveTextContent('1');
    });
});

describe('authentication layouts', () => {
    it('renders the auth wrapper defaults and supplied copy', () => {
        const { rerender } = render(<AuthLayout>Default form</AuthLayout>);

        expect(screen.getByText('Default form')).toBeInTheDocument();

        rerender(
            <AuthLayout title="Welcome back" description="Sign in to continue">
                Login form
            </AuthLayout>,
        );
        expect(
            screen.getByRole('heading', { name: 'Welcome back' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Sign in to continue')).toBeInTheDocument();
    });

    it('renders simple, card, and split auth presentations', () => {
        const { rerender } = render(
            <AuthSimpleLayout title="Simple" description="Simple description">
                Simple form
            </AuthSimpleLayout>,
        );

        expect(
            screen.getByRole('heading', { name: 'Simple' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Simple form')).toBeInTheDocument();

        rerender(
            <AuthCardLayout title="Card" description="Card description">
                Card form
            </AuthCardLayout>,
        );
        expect(screen.getByText('Card')).toBeInTheDocument();
        expect(screen.getByText('Card description')).toBeInTheDocument();
        expect(screen.getByText('Card form')).toBeInTheDocument();

        rerender(
            <AuthSplitLayout title="Split" description="Split description">
                Split form
            </AuthSplitLayout>,
        );
        expect(screen.getByText('Rozine')).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Split' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Split form')).toBeInTheDocument();
        expect(screen.getAllByRole('link')).toHaveLength(2);
    });
});

describe('starter pages', () => {
    it('switches the welcome navigation after authentication', () => {
        const { rerender } = render(<Welcome />);

        expect(
            screen.getByRole('link', { name: 'Log in' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Register' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Dashboard' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: "Let's get started" }),
        ).toBeInTheDocument();

        state.page.auth.user = user;
        rerender(<Welcome />);

        expect(
            screen.getByRole('link', { name: 'Dashboard' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Log in' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Register' }),
        ).not.toBeInTheDocument();
    });
});
