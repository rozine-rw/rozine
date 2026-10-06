import { act, fireEvent, render, screen } from '@testing-library/react';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupAction,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInput,
    SidebarInset,
    SidebarMenu,
    SidebarMenuAction,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSkeleton,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    SidebarProvider,
    SidebarRail,
    SidebarSeparator,
    SidebarTrigger,
    useSidebar,
} from '@/components/ui/sidebar';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({
    useIsMobile: vi.fn(() => false),
}));

function SidebarStateProbe() {
    const sidebar = useSidebar();

    return (
        <section aria-label="Sidebar state">
            <output aria-label="Sidebar mode">{sidebar.state}</output>
            <output aria-label="Desktop open">{String(sidebar.open)}</output>
            <output aria-label="Mobile open">
                {String(sidebar.openMobile)}
            </output>
            <output aria-label="Mobile viewport">
                {String(sidebar.isMobile)}
            </output>
            <button type="button" onClick={() => sidebar.setOpen(false)}>
                Close desktop
            </button>
            <button type="button" onClick={() => sidebar.setOpen(true)}>
                Open desktop
            </button>
            <button type="button" onClick={() => sidebar.setOpenMobile(true)}>
                Open mobile
            </button>
        </section>
    );
}

function renderSidebar(children: React.ReactNode, defaultOpen = true) {
    return render(
        <TooltipProvider>
            <SidebarProvider defaultOpen={defaultOpen}>
                {children}
            </SidebarProvider>
        </TooltipProvider>,
    );
}

describe('sidebar primitives', () => {
    beforeEach(() => {
        vi.mocked(useIsMobile).mockReturnValue(false);
    });

    it('requires every sidebar consumer to be inside its provider', () => {
        expect(() => render(<SidebarStateProbe />)).toThrow(
            'useSidebar must be used within a SidebarProvider.',
        );
    });

    it('toggles uncontrolled desktop state from buttons and shortcuts', () => {
        const onTriggerClick = vi.fn();

        renderSidebar(
            <>
                <SidebarStateProbe />
                <Sidebar side="left" variant="sidebar" collapsible="offcanvas">
                    <SidebarHeader>Header</SidebarHeader>
                    <SidebarTrigger
                        data-testid="desktop-sidebar-trigger"
                        onClick={onTriggerClick}
                    />
                    <SidebarRail data-testid="desktop-sidebar-rail" />
                    <SidebarContent>Content</SidebarContent>
                </Sidebar>
            </>,
        );

        expect(screen.getByLabelText('Sidebar mode')).toHaveTextContent(
            'expanded',
        );
        fireEvent.click(screen.getByTestId('desktop-sidebar-trigger'));
        expect(onTriggerClick).toHaveBeenCalledOnce();
        expect(screen.getByLabelText('Sidebar mode')).toHaveTextContent(
            'collapsed',
        );

        fireEvent.click(screen.getByTestId('desktop-sidebar-rail'));
        expect(screen.getByLabelText('Sidebar mode')).toHaveTextContent(
            'expanded',
        );

        const ignoredKey = new KeyboardEvent('keydown', {
            cancelable: true,
            key: 'b',
        });
        act(() => window.dispatchEvent(ignoredKey));
        expect(ignoredKey.defaultPrevented).toBe(false);

        const unrelatedKey = new KeyboardEvent('keydown', {
            cancelable: true,
            ctrlKey: true,
            key: 'x',
        });
        act(() => window.dispatchEvent(unrelatedKey));
        expect(unrelatedKey.defaultPrevented).toBe(false);

        const metaShortcut = new KeyboardEvent('keydown', {
            cancelable: true,
            key: 'b',
            metaKey: true,
        });
        act(() => window.dispatchEvent(metaShortcut));
        expect(metaShortcut.defaultPrevented).toBe(true);
        expect(screen.getByLabelText('Sidebar mode')).toHaveTextContent(
            'collapsed',
        );

        const controlShortcut = new KeyboardEvent('keydown', {
            cancelable: true,
            ctrlKey: true,
            key: 'b',
        });
        act(() => window.dispatchEvent(controlShortcut));
        expect(controlShortcut.defaultPrevented).toBe(true);
        expect(screen.getByLabelText('Sidebar mode')).toHaveTextContent(
            'expanded',
        );

        fireEvent.click(screen.getByRole('button', { name: 'Close desktop' }));
        expect(screen.getByLabelText('Desktop open')).toHaveTextContent(
            'false',
        );
        fireEvent.click(screen.getByRole('button', { name: 'Open desktop' }));
        expect(screen.getByLabelText('Desktop open')).toHaveTextContent('true');
    });

    it('supports externally controlled desktop state', () => {
        const onOpenChange = vi.fn();

        function ControlledSidebar() {
            const [open, setOpen] = useState(true);

            return (
                <TooltipProvider>
                    <SidebarProvider
                        open={open}
                        onOpenChange={(nextOpen) => {
                            onOpenChange(nextOpen);
                            setOpen(nextOpen);
                        }}
                    >
                        <SidebarStateProbe />
                        <SidebarTrigger />
                    </SidebarProvider>
                </TooltipProvider>
            );
        }

        render(<ControlledSidebar />);
        fireEvent.click(screen.getByRole('button', { name: 'Toggle sidebar' }));

        expect(onOpenChange).toHaveBeenCalledWith(false);
        expect(screen.getByLabelText('Desktop open')).toHaveTextContent(
            'false',
        );

        fireEvent.click(screen.getByRole('button', { name: 'Open desktop' }));
        expect(onOpenChange).toHaveBeenLastCalledWith(true);
    });

    it('opens the mobile sheet and uses the mobile toggle branch', () => {
        vi.mocked(useIsMobile).mockReturnValue(true);

        renderSidebar(
            <>
                <SidebarStateProbe />
                <Sidebar side="right">
                    <SidebarContent>Mobile navigation</SidebarContent>
                </Sidebar>
                <SidebarTrigger />
            </>,
        );

        expect(screen.getByLabelText('Mobile viewport')).toHaveTextContent(
            'true',
        );
        expect(screen.queryByText('Mobile navigation')).not.toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Open mobile' }));

        expect(screen.getByLabelText('Mobile open')).toHaveTextContent('true');
        expect(screen.getByText('Mobile navigation')).toBeVisible();
        expect(
            screen.getByRole('dialog', { name: 'Sidebar' }),
        ).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Close' }));
        expect(screen.getByLabelText('Mobile open')).toHaveTextContent('false');

        fireEvent.click(screen.getByRole('button', { name: 'Toggle sidebar' }));
        expect(screen.getByLabelText('Mobile open')).toHaveTextContent('true');
    });

    it('renders non-collapsible and every desktop layout variant', () => {
        const { rerender } = renderSidebar(
            <Sidebar collapsible="none" className="always-visible">
                Always visible
            </Sidebar>,
        );

        expect(screen.getByText('Always visible')).toHaveClass(
            'always-visible',
        );

        rerender(
            <TooltipProvider>
                <SidebarProvider defaultOpen={false}>
                    <Sidebar
                        side="right"
                        variant="floating"
                        collapsible="icon"
                        className="floating-sidebar"
                    >
                        Floating
                    </Sidebar>
                </SidebarProvider>
            </TooltipProvider>,
        );

        expect(screen.getByText('Floating')).toBeInTheDocument();

        rerender(
            <TooltipProvider>
                <SidebarProvider defaultOpen={false}>
                    <Sidebar side="left" variant="inset" collapsible="icon">
                        Inset
                    </Sidebar>
                </SidebarProvider>
            </TooltipProvider>,
        );

        expect(screen.getByText('Inset')).toBeInTheDocument();

        rerender(
            <TooltipProvider>
                <SidebarProvider>
                    <Sidebar side="left" variant="sidebar" collapsible="icon">
                        Standard
                    </Sidebar>
                </SidebarProvider>
            </TooltipProvider>,
        );

        expect(screen.getByText('Standard')).toBeInTheDocument();
    });

    it('composes the structural, group, menu, and submenu primitives', () => {
        const random = vi.spyOn(Math, 'random').mockReturnValue(0.5);

        renderSidebar(
            <>
                <SidebarInset className="custom-inset">
                    Main content
                </SidebarInset>
                <SidebarInput
                    className="custom-input"
                    aria-label="Search menu"
                />
                <SidebarHeader className="custom-header">Header</SidebarHeader>
                <SidebarFooter className="custom-footer">Footer</SidebarFooter>
                <SidebarSeparator decorative={false} />
                <SidebarContent className="custom-content">
                    Content
                </SidebarContent>
                <SidebarGroup className="custom-group">
                    <SidebarGroupLabel className="plain-group-label">
                        Accounts
                    </SidebarGroupLabel>
                    <SidebarGroupLabel asChild>
                        <h2>Delegated accounts</h2>
                    </SidebarGroupLabel>
                    <SidebarGroupAction aria-label="Add account">
                        +
                    </SidebarGroupAction>
                    <SidebarGroupAction asChild>
                        <a href="/accounts/new">Delegated add</a>
                    </SidebarGroupAction>
                    <SidebarGroupContent className="custom-group-content">
                        Group content
                    </SidebarGroupContent>
                </SidebarGroup>
                <SidebarMenu className="custom-menu">
                    <SidebarMenuItem className="custom-menu-item">
                        <SidebarMenuButton className="plain-menu-button">
                            Overview
                        </SidebarMenuButton>
                        <SidebarMenuAction aria-label="Pin item">
                            Pin
                        </SidebarMenuAction>
                        <SidebarMenuAction
                            showOnHover
                            aria-label="More actions"
                        >
                            More
                        </SidebarMenuAction>
                        <SidebarMenuAction asChild>
                            <a href="/overview/edit">Edit</a>
                        </SidebarMenuAction>
                        <SidebarMenuBadge>8</SidebarMenuBadge>
                        <SidebarMenuSkeleton data-testid="skeleton-without-icon" />
                        <SidebarMenuSkeleton
                            data-testid="skeleton-with-icon"
                            showIcon
                        />
                        <SidebarMenuSub>
                            <SidebarMenuSubItem>
                                <SidebarMenuSubButton
                                    href="/transactions"
                                    size="sm"
                                >
                                    Transactions
                                </SidebarMenuSubButton>
                                <SidebarMenuSubButton
                                    asChild
                                    size="md"
                                    isActive
                                >
                                    <a href="/statements">Statements</a>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </SidebarMenuItem>
                </SidebarMenu>
            </>,
        );

        expect(screen.getByText('Main content')).toHaveClass('custom-inset');
        expect(screen.getByLabelText('Search menu')).toHaveClass(
            'custom-input',
        );
        expect(screen.getByText('Accounts')).toHaveClass('plain-group-label');
        expect(
            screen.getByRole('heading', { name: 'Delegated accounts' }),
        ).toHaveAttribute('data-sidebar', 'group-label');
        expect(
            screen.getByRole('link', { name: 'Delegated add' }),
        ).toHaveAttribute('data-sidebar', 'group-action');
        expect(screen.getByRole('button', { name: 'Overview' })).toHaveClass(
            'plain-menu-button',
        );
        expect(
            screen.getByRole('link', { name: 'Statements' }),
        ).toHaveAttribute('data-active', 'true');
        expect(screen.getByTestId('skeleton-with-icon')).toHaveTextContent('');
        expect(screen.getByTestId('skeleton-without-icon')).toHaveTextContent(
            '',
        );
        expect(random).toHaveBeenCalledTimes(2);
    });

    it('delegates menu buttons and handles every tooltip visibility branch', () => {
        const { rerender } = renderSidebar(
            <SidebarMenuButton asChild isActive variant="outline" size="lg">
                <a href="/dashboard">Dashboard</a>
            </SidebarMenuButton>,
            false,
        );

        expect(screen.getByRole('link', { name: 'Dashboard' })).toHaveAttribute(
            'data-active',
            'true',
        );

        rerender(
            <TooltipProvider>
                <SidebarProvider defaultOpen={false}>
                    <SidebarMenuButton tooltip="Collapsed help">
                        Collapsed item
                    </SidebarMenuButton>
                </SidebarProvider>
            </TooltipProvider>,
        );

        expect(
            screen.getByRole('button', { name: 'Collapsed item' }),
        ).toHaveAttribute('data-sidebar', 'menu-button');

        rerender(
            <TooltipProvider>
                <SidebarProvider>
                    <SidebarMenuButton
                        tooltip={{ children: 'Expanded help', sideOffset: 8 }}
                    >
                        Expanded item
                    </SidebarMenuButton>
                </SidebarProvider>
            </TooltipProvider>,
        );

        expect(
            screen.getByRole('button', { name: 'Expanded item' }),
        ).toBeInTheDocument();

        vi.mocked(useIsMobile).mockReturnValue(true);
        rerender(
            <TooltipProvider>
                <SidebarProvider defaultOpen={false}>
                    <SidebarMenuButton tooltip="Mobile help">
                        Mobile item
                    </SidebarMenuButton>
                </SidebarProvider>
            </TooltipProvider>,
        );

        expect(
            screen.getByRole('button', { name: 'Mobile item' }),
        ).toBeInTheDocument();
    });
});
