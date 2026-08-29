import { fireEvent, render, screen } from '@testing-library/react';
import type { LucideIcon } from 'lucide-react';
import type {
    ComponentProps,
    MouseEvent as ReactMouseEvent,
    ReactNode,
} from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { Breadcrumbs } from '@/components/breadcrumbs';
import Heading from '@/components/heading';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import type { NavItem, User } from '@/types';

const state = vi.hoisted(() => ({
    current: false,
    isMobile: false,
    page: {
        name: 'Rozine',
        auth: { user: undefined as User | undefined },
        sidebarOpen: true,
    },
    sidebarState: 'expanded' as 'expanded' | 'collapsed',
    sidebarDefaultOpen: undefined as boolean | undefined,
    cleanup: vi.fn(),
    flushAll: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({
        as,
        children,
        href,
        onClick,
        ...props
    }: {
        as?: string;
        children?: ReactNode;
        href: string | { url: string };
        onClick?: () => void;
    }) => {
        const resolvedHref = typeof href === 'string' ? href : href.url;
        const handleClick = (event: ReactMouseEvent<HTMLElement>) => {
            event.preventDefault();
            onClick?.();
        };

        return as === 'button' ? (
            <button onClick={handleClick} {...props}>
                {children}
            </button>
        ) : (
            <a href={resolvedHref} onClick={handleClick} {...props}>
                {children}
            </a>
        );
    },
    router: { flushAll: state.flushAll },
    usePage: () => ({ props: state.page, url: '/dashboard' }),
}));

vi.mock('@/hooks/use-current-url', () => ({
    useCurrentUrl: () => ({
        currentUrl: '/dashboard',
        isCurrentUrl: () => state.current,
        isCurrentOrParentUrl: () => state.current,
        whenCurrentUrl: <T,>(_url: unknown, ifTrue: T, ifFalse: null = null) =>
            state.current ? ifTrue : ifFalse,
    }),
}));

vi.mock('@/hooks/use-initials', () => ({
    useInitials: () => (name: string) =>
        name
            .split(' ')
            .filter(Boolean)
            .map((part) => part[0])
            .join(''),
}));

vi.mock('@/hooks/use-mobile', () => ({
    useIsMobile: () => state.isMobile,
}));

vi.mock('@/hooks/use-mobile-navigation', () => ({
    useMobileNavigation: () => state.cleanup,
}));

vi.mock('@/components/ui/sidebar', () => {
    const Wrapper = ({
        children,
        className,
    }: {
        children?: ReactNode;
        className?: string;
    }) => (
        <div className={className} data-testid={className || undefined}>
            {children}
        </div>
    );

    return {
        Sidebar: Wrapper,
        SidebarContent: Wrapper,
        SidebarFooter: Wrapper,
        SidebarGroup: Wrapper,
        SidebarGroupContent: Wrapper,
        SidebarGroupLabel: Wrapper,
        SidebarHeader: Wrapper,
        SidebarInset: ({ children, ...props }: ComponentProps<'main'>) => (
            <main {...props} data-testid="sidebar-inset">
                {children}
            </main>
        ),
        SidebarMenu: Wrapper,
        SidebarMenuButton: ({
            children,
            isActive,
        }: {
            children?: ReactNode;
            isActive?: boolean;
        }) => (
            <div
                data-active={String(Boolean(isActive))}
                data-testid={`sidebar-menu-${String(Boolean(isActive))}`}
            >
                {children}
            </div>
        ),
        SidebarMenuItem: Wrapper,
        SidebarProvider: ({
            children,
            defaultOpen,
        }: {
            children?: ReactNode;
            defaultOpen?: boolean;
        }) => {
            state.sidebarDefaultOpen = defaultOpen;

            return <div data-testid="sidebar-provider">{children}</div>;
        },
        SidebarTrigger: (props: ComponentProps<'button'>) => (
            <button aria-label="Toggle sidebar" {...props} />
        ),
        useSidebar: () => ({ state: state.sidebarState }),
    };
});

vi.mock('@/components/ui/avatar', () => ({
    Avatar: ({ children }: { children?: ReactNode }) => <div>{children}</div>,
    AvatarFallback: ({ children }: { children?: ReactNode }) => (
        <span>{children}</span>
    ),
    AvatarImage: ({ alt, src }: { alt?: string; src?: string }) => (
        <img alt={alt} src={src} />
    ),
}));

vi.mock('@/components/ui/breadcrumb', () => ({
    Breadcrumb: ({ children }: { children?: ReactNode }) => (
        <nav aria-label="Breadcrumb">{children}</nav>
    ),
    BreadcrumbItem: ({ children }: { children?: ReactNode }) => (
        <li>{children}</li>
    ),
    BreadcrumbLink: ({ children }: { children?: ReactNode }) => <>{children}</>,
    BreadcrumbList: ({ children }: { children?: ReactNode }) => (
        <ol>{children}</ol>
    ),
    BreadcrumbPage: ({ children }: { children?: ReactNode }) => (
        <span aria-current="page">{children}</span>
    ),
    BreadcrumbSeparator: () => (
        <span data-testid="breadcrumb-separator">/</span>
    ),
}));

vi.mock('@/components/ui/button', () => ({
    Button: ({ children, ...props }: ComponentProps<'button'>) => (
        <button {...props}>{children}</button>
    ),
}));

vi.mock('@/components/ui/dropdown-menu', () => {
    const Wrapper = ({ children }: { children?: ReactNode }) => (
        <div>{children}</div>
    );

    return {
        DropdownMenu: Wrapper,
        DropdownMenuContent: ({
            children,
            side,
        }: {
            children?: ReactNode;
            side?: string;
        }) => (
            <div aria-label={`user-menu-${side}`} data-side={side} role="menu">
                {children}
            </div>
        ),
        DropdownMenuGroup: Wrapper,
        DropdownMenuItem: Wrapper,
        DropdownMenuLabel: Wrapper,
        DropdownMenuSeparator: () => <hr />,
        DropdownMenuTrigger: Wrapper,
    };
});

vi.mock('@/components/ui/navigation-menu', () => {
    const Wrapper = ({ children }: { children?: ReactNode }) => (
        <div>{children}</div>
    );

    return {
        NavigationMenu: Wrapper,
        NavigationMenuItem: Wrapper,
        NavigationMenuList: Wrapper,
        navigationMenuTriggerStyle: () => 'navigation-trigger',
    };
});

vi.mock('@/components/ui/sheet', () => {
    const Wrapper = ({ children }: { children?: ReactNode }) => (
        <div>{children}</div>
    );

    return {
        Sheet: Wrapper,
        SheetContent: Wrapper,
        SheetHeader: Wrapper,
        SheetTitle: Wrapper,
        SheetTrigger: Wrapper,
    };
});

vi.mock('@/components/ui/tooltip', () => {
    const Wrapper = ({ children }: { children?: ReactNode }) => (
        <div>{children}</div>
    );

    return {
        Tooltip: Wrapper,
        TooltipContent: Wrapper,
        TooltipTrigger: Wrapper,
    };
});

const user: User = {
    id: 1,
    name: 'Ada Lovelace',
    email: 'ada@example.test',
    avatar: '/ada.png',
    email_verified_at: '2026-08-24T00:00:00Z',
    created_at: '2026-08-24T00:00:00Z',
    updated_at: '2026-08-24T00:00:00Z',
};

afterEach(() => {
    state.current = false;
    state.isMobile = false;
    state.page.auth.user = undefined;
    state.page.sidebarOpen = true;
    state.sidebarState = 'expanded';
    state.sidebarDefaultOpen = undefined;
    vi.clearAllMocks();
});

describe('shell building blocks', () => {
    it('renders sidebar and header content variants', () => {
        const { rerender } = render(
            <AppContent data-testid="content">Sidebar content</AppContent>,
        );

        expect(screen.getByTestId('sidebar-inset')).toHaveTextContent(
            'Sidebar content',
        );

        rerender(
            <AppContent variant="header" data-testid="content">
                Header content
            </AppContent>,
        );

        expect(screen.getByTestId('content').tagName).toBe('MAIN');
        expect(screen.getByTestId('content')).toHaveTextContent(
            'Header content',
        );
    });

    it('renders the application logo and merges caller icon styles', () => {
        state.page.auth.user = user;
        render(
            <>
                <AppLogo />
                <AppLogoIcon
                    className="custom-mark"
                    title="Brand mark"
                    style={{ backgroundColor: 'red' }}
                />
            </>,
        );

        expect(screen.getByText('Rozine')).toBeInTheDocument();
        expect(screen.getByRole('presentation')).toHaveAttribute(
            'src',
            '/images/rozine-wing-white.png',
        );
        expect(screen.getByTitle('Brand mark')).toHaveClass('custom-mark');
        expect(screen.getByTitle('Brand mark')).toHaveStyle(
            'background-color: rgb(255, 0, 0); mask-size: contain',
        );
    });

    it('renders both shell variants and forwards the saved sidebar state', () => {
        const { rerender } = render(<AppShell>Sidebar child</AppShell>);

        expect(screen.getByTestId('sidebar-provider')).toHaveTextContent(
            'Sidebar child',
        );
        expect(state.sidebarDefaultOpen).toBe(true);

        state.page.sidebarOpen = false;
        rerender(<AppShell variant="sidebar">Closed sidebar</AppShell>);
        expect(state.sidebarDefaultOpen).toBe(false);

        rerender(<AppShell variant="header">Header child</AppShell>);
        expect(screen.getByText('Header child')).toBeInTheDocument();
    });

    it('renders sidebar header defaults and supplied breadcrumbs', () => {
        const { rerender } = render(<AppSidebarHeader />);

        expect(
            screen.queryByRole('navigation', { name: 'Breadcrumb' }),
        ).not.toBeInTheDocument();

        rerender(
            <AppSidebarHeader
                breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}
            />,
        );

        expect(screen.getByText('Dashboard')).toHaveAttribute(
            'aria-current',
            'page',
        );
    });
});

describe('navigation components', () => {
    it('renders empty, linked, separated, and current breadcrumbs', () => {
        const { rerender } = render(<Breadcrumbs breadcrumbs={[]} />);

        expect(
            screen.queryByRole('navigation', { name: 'Breadcrumb' }),
        ).not.toBeInTheDocument();

        rerender(
            <Breadcrumbs
                breadcrumbs={[
                    { title: 'Dashboard', href: '/dashboard' },
                    { title: 'Profile', href: '/settings/profile' },
                ]}
            />,
        );

        expect(screen.getByRole('link', { name: 'Dashboard' })).toHaveAttribute(
            'href',
            '/dashboard',
        );
        expect(screen.getByText('Profile')).toHaveAttribute(
            'aria-current',
            'page',
        );
        expect(screen.getByTestId('breadcrumb-separator')).toBeInTheDocument();
    });

    it('renders heading variants with and without descriptions', () => {
        const { rerender } = render(<Heading title="Profile" />);

        expect(screen.getByRole('heading', { name: 'Profile' })).toHaveClass(
            'text-xl',
        );
        expect(screen.queryByText('Description')).not.toBeInTheDocument();

        rerender(
            <Heading
                title="Security"
                description="Description"
                variant="small"
            />,
        );

        expect(screen.getByRole('heading', { name: 'Security' })).toHaveClass(
            'text-base',
        );
        expect(screen.getByText('Description')).toBeInTheDocument();
    });

    it('renders footer links with optional icons and class names', () => {
        const icon = vi.fn((props: ComponentProps<'svg'>) => (
            <svg aria-label="Repository icon" {...props} />
        )) as unknown as LucideIcon;
        const items: NavItem[] = [
            {
                title: 'Repository',
                href: { url: 'https://example.test', method: 'get' },
                icon,
            },
            { title: 'Plain link', href: 'https://plain.test', icon: null },
        ];
        const { rerender } = render(
            <NavFooter items={items} className="footer-extra" />,
        );

        expect(screen.getByTestId(/footer-extra/)).toHaveClass('footer-extra');
        expect(screen.getByLabelText('Repository icon')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Plain link' }),
        ).toHaveAttribute('href', 'https://plain.test');

        rerender(<NavFooter items={[]} />);
        expect(screen.queryByRole('link')).not.toBeInTheDocument();
    });

    it('renders main links with optional icons and active state', () => {
        const icon = vi.fn(() => (
            <svg aria-label="Dashboard icon" />
        )) as unknown as LucideIcon;
        const items: NavItem[] = [
            { title: 'Dashboard', href: '/dashboard', icon },
            { title: 'Profile', href: '/profile' },
        ];
        const { rerender } = render(<NavMain items={items} />);

        expect(screen.getByLabelText('Dashboard icon')).toBeInTheDocument();
        expect(screen.getAllByTestId('sidebar-menu-false')).not.toHaveLength(0);

        state.current = true;
        rerender(<NavMain items={items} />);
        expect(screen.getAllByTestId('sidebar-menu-true')).not.toHaveLength(0);

        rerender(<NavMain items={[]} />);
        expect(screen.queryByRole('link')).not.toBeInTheDocument();
    });

    it('positions the user menu for mobile and collapsed or expanded sidebars', () => {
        const { container, rerender } = render(<NavUser />);

        expect(container).toBeEmptyDOMElement();

        state.page.auth.user = user;
        state.isMobile = true;
        rerender(<NavUser />);
        expect(
            screen.getByRole('menu', { name: 'user-menu-bottom' }),
        ).toBeInTheDocument();

        state.isMobile = false;
        state.sidebarState = 'collapsed';
        rerender(<NavUser />);
        expect(
            screen.getByRole('menu', { name: 'user-menu-left' }),
        ).toBeInTheDocument();

        state.sidebarState = 'expanded';
        rerender(<NavUser />);
        expect(
            screen.getByRole('menu', { name: 'user-menu-bottom' }),
        ).toBeInTheDocument();
    });

    it('shows user identity with optional email', () => {
        const { rerender } = render(<UserInfo user={user} />);

        expect(screen.getByText('Ada Lovelace')).toBeInTheDocument();
        expect(screen.getByText('AL')).toBeInTheDocument();
        expect(screen.queryByText('ada@example.test')).not.toBeInTheDocument();

        rerender(<UserInfo user={user} showEmail />);
        expect(screen.getByText('ada@example.test')).toBeInTheDocument();
    });

    it('cleans navigation state for settings and logout actions', () => {
        render(<UserMenuContent user={user} />);

        fireEvent.click(screen.getByRole('link', { name: /settings/i }));
        fireEvent.click(screen.getByRole('button', { name: /log out/i }));

        expect(state.cleanup).toHaveBeenCalledTimes(2);
        expect(state.flushAll).toHaveBeenCalledOnce();
    });

    it('renders the complete sidebar composition', () => {
        state.page.auth.user = user;

        render(<AppSidebar />);

        expect(screen.getAllByText('Rozine')).not.toHaveLength(0);
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
        expect(screen.getByText('Repository')).toBeInTheDocument();
        expect(screen.getByText('Documentation')).toBeInTheDocument();
        expect(screen.getAllByText('Ada Lovelace')).not.toHaveLength(0);
    });
});

describe('header navigation', () => {
    it('renders anonymous and authenticated branches, activity, and breadcrumbs', () => {
        const { rerender } = render(<AppHeader />);

        expect(screen.getAllByText('Dashboard').length).toBeGreaterThan(0);
        expect(
            screen.getAllByRole('link', { name: 'Dashboard' }),
        ).not.toHaveLength(0);

        state.page.auth.user = user;
        state.current = true;
        rerender(
            <AppHeader
                breadcrumbs={[
                    { title: 'Dashboard', href: '/dashboard' },
                    { title: 'Profile', href: '/settings/profile' },
                ]}
            />,
        );

        expect(screen.getAllByText('AL').length).toBeGreaterThan(0);
        expect(screen.getByText('Profile')).toHaveAttribute(
            'aria-current',
            'page',
        );
        expect(
            screen
                .getAllByRole('link', { name: 'Dashboard' })
                .some((link) => link.classList.contains('text-neutral-900')),
        ).toBe(true);
    });
});
