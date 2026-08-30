import { fireEvent, render, screen } from '@testing-library/react';
import { OTPInputContext } from 'input-otp';
import type { RenderProps } from 'input-otp';
import { describe, expect, it, vi } from 'vitest';

import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuPortal,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSeparator,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuIndicator,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { Toggle, toggleVariants } from '@/components/ui/toggle';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/hooks/use-appearance';
import { useFlashToast } from '@/hooks/use-flash-toast';

vi.mock('@/hooks/use-appearance', () => ({
    useAppearance: vi.fn(() => ({ appearance: 'light' })),
}));

vi.mock('@/hooks/use-flash-toast', () => ({
    useFlashToast: vi.fn(),
}));

class TestResizeObserver implements ResizeObserver {
    disconnect() {}

    observe() {}

    unobserve() {}
}

vi.stubGlobal('ResizeObserver', TestResizeObserver);
Object.defineProperty(Element.prototype, 'scrollIntoView', {
    configurable: true,
    value: vi.fn(),
});

const inactiveOtpContext: RenderProps = {
    isFocused: false,
    isHovering: false,
    slots: [
        {
            char: null,
            hasFakeCaret: false,
            isActive: false,
            placeholderChar: null,
        },
    ],
};

const activeOtpContext: RenderProps = {
    isFocused: true,
    isHovering: true,
    slots: [
        {
            char: '7',
            hasFakeCaret: true,
            isActive: true,
            placeholderChar: null,
        },
    ],
};

describe('interactive UI primitives', () => {
    it('toggles checkbox and collapsible state from user actions', () => {
        const onCheckedChange = vi.fn();
        const { rerender } = render(
            <Checkbox
                aria-label="Accept terms"
                className="custom-checkbox"
                onCheckedChange={onCheckedChange}
            />,
        );

        fireEvent.click(screen.getByRole('checkbox', { name: 'Accept terms' }));
        expect(onCheckedChange).toHaveBeenCalledWith(true);
        expect(
            screen.getByRole('checkbox', { name: 'Accept terms' }),
        ).toHaveClass('custom-checkbox');

        rerender(
            <Collapsible defaultOpen>
                <CollapsibleTrigger>Toggle details</CollapsibleTrigger>
                <CollapsibleContent>Investment details</CollapsibleContent>
            </Collapsible>,
        );

        expect(screen.getByText('Investment details')).toBeVisible();
        fireEvent.click(screen.getByRole('button', { name: 'Toggle details' }));
        expect(
            screen.queryByText('Investment details'),
        ).not.toBeInTheDocument();
    });

    it('opens and closes an accessible dialog with composed sections', () => {
        render(
            <Dialog>
                <DialogTrigger>Review order</DialogTrigger>
                <DialogContent className="custom-dialog">
                    <DialogHeader className="custom-header">
                        <DialogTitle className="custom-title">
                            Confirm investment
                        </DialogTitle>
                        <DialogDescription className="custom-description">
                            Verify the requested amount.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="custom-footer">
                        <DialogClose>Cancel investment</DialogClose>
                    </DialogFooter>
                </DialogContent>
            </Dialog>,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Review order' }));

        expect(
            screen.getByRole('dialog', { name: 'Confirm investment' }),
        ).toHaveClass('custom-dialog');
        expect(screen.getByText('Verify the requested amount.')).toHaveClass(
            'custom-description',
        );

        fireEvent.click(
            screen.getByRole('button', { name: 'Cancel investment' }),
        );
        expect(
            screen.queryByRole('dialog', { name: 'Confirm investment' }),
        ).not.toBeInTheDocument();
    });

    it.each(['right', 'left', 'top', 'bottom'] as const)(
        'renders and closes the %s sheet',
        (side) => {
            const view = render(
                <Sheet defaultOpen>
                    <SheetTrigger>Open {side} sheet</SheetTrigger>
                    <SheetContent className={`sheet-${side}`} side={side}>
                        <SheetHeader className="custom-sheet-header">
                            <SheetTitle>{side} panel</SheetTitle>
                            <SheetDescription>
                                Panel description
                            </SheetDescription>
                        </SheetHeader>
                        <SheetFooter className="custom-sheet-footer">
                            <SheetClose>Dismiss {side} panel</SheetClose>
                        </SheetFooter>
                    </SheetContent>
                </Sheet>,
            );

            expect(
                screen.getByRole('dialog', { name: `${side} panel` }),
            ).toHaveClass(`sheet-${side}`);
            fireEvent.click(
                screen.getByRole('button', { name: `Dismiss ${side} panel` }),
            );
            expect(
                screen.queryByRole('dialog', { name: `${side} panel` }),
            ).not.toBeInTheDocument();
            view.unmount();
        },
    );

    it('renders every dropdown role and handles selection', () => {
        const onSelect = vi.fn();

        render(
            <DropdownMenu defaultOpen>
                <DropdownMenuTrigger>Account actions</DropdownMenuTrigger>
                <DropdownMenuContent className="custom-menu">
                    <DropdownMenuGroup>
                        <DropdownMenuLabel inset>Account</DropdownMenuLabel>
                        <DropdownMenuItem onSelect={onSelect}>
                            Profile
                            <DropdownMenuShortcut>⌘P</DropdownMenuShortcut>
                        </DropdownMenuItem>
                        <DropdownMenuItem inset variant="destructive">
                            Delete
                        </DropdownMenuItem>
                        <DropdownMenuCheckboxItem checked>
                            Notifications
                        </DropdownMenuCheckboxItem>
                        <DropdownMenuRadioGroup value="daily">
                            <DropdownMenuRadioItem value="daily">
                                Daily
                            </DropdownMenuRadioItem>
                        </DropdownMenuRadioGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuSub defaultOpen>
                            <DropdownMenuSubTrigger inset>
                                More
                            </DropdownMenuSubTrigger>
                            <DropdownMenuSubContent>
                                <DropdownMenuItem>Export</DropdownMenuItem>
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>
                    </DropdownMenuGroup>
                </DropdownMenuContent>
                <DropdownMenuPortal>
                    <div data-testid="dropdown-portal">Portaled status</div>
                </DropdownMenuPortal>
            </DropdownMenu>,
        );

        expect(screen.getByRole('menu')).toHaveClass('custom-menu');
        expect(screen.getByRole('menuitemcheckbox')).toHaveAttribute(
            'data-state',
            'checked',
        );
        expect(screen.getByRole('menuitemradio')).toHaveAttribute(
            'data-state',
            'checked',
        );
        expect(screen.getByTestId('dropdown-portal')).toHaveTextContent(
            'Portaled status',
        );
        fireEvent.click(screen.getByRole('menuitem', { name: /Profile/ }));
        expect(onSelect).toHaveBeenCalledOnce();
    });

    it('renders OTP structure for active and inactive slots', () => {
        const { rerender } = render(
            <InputOTP
                aria-label="One-time code"
                maxLength={1}
                value="7"
                onChange={vi.fn()}
            >
                <InputOTPGroup className="custom-otp-group">
                    <InputOTPSlot className="custom-otp-slot" index={0} />
                </InputOTPGroup>
                <InputOTPSeparator data-testid="otp-separator" />
            </InputOTP>,
        );

        expect(screen.getByLabelText('One-time code')).toHaveValue('7');
        expect(screen.getByText('7')).toHaveClass('custom-otp-slot');
        expect(screen.getByTestId('otp-separator')).toHaveAttribute(
            'role',
            'separator',
        );

        rerender(
            <OTPInputContext.Provider value={activeOtpContext}>
                <InputOTPSlot index={0} />
            </OTPInputContext.Provider>,
        );

        expect(screen.getByText('7')).toHaveClass('ring-ring');

        rerender(
            <OTPInputContext.Provider value={inactiveOtpContext}>
                <InputOTPSlot data-testid="inactive-otp-slot" index={0} />
            </OTPInputContext.Provider>,
        );

        expect(screen.getByTestId('inactive-otp-slot')).not.toHaveClass(
            'ring-ring',
        );
    });

    it('composes navigation with and without its viewport', () => {
        const { rerender } = render(
            <NavigationMenu defaultValue="invest" className="custom-nav">
                <NavigationMenuList className="custom-nav-list">
                    <NavigationMenuItem
                        value="invest"
                        className="custom-nav-item"
                    >
                        <NavigationMenuTrigger className="custom-nav-trigger">
                            Invest
                        </NavigationMenuTrigger>
                        <NavigationMenuContent className="custom-nav-content">
                            <NavigationMenuLink
                                className="custom-nav-link"
                                href="/invest"
                            >
                                Opportunities
                            </NavigationMenuLink>
                        </NavigationMenuContent>
                    </NavigationMenuItem>
                    <NavigationMenuIndicator className="custom-nav-indicator" />
                </NavigationMenuList>
            </NavigationMenu>,
        );

        expect(screen.getByRole('navigation')).toHaveClass('custom-nav');
        expect(screen.getByRole('button', { name: 'Invest' })).toHaveClass(
            'custom-nav-trigger',
        );

        rerender(
            <NavigationMenu viewport={false}>
                <NavigationMenuList>
                    <NavigationMenuItem>
                        <NavigationMenuLink href="/portfolio">
                            Portfolio
                        </NavigationMenuLink>
                    </NavigationMenuItem>
                </NavigationMenuList>
            </NavigationMenu>,
        );

        expect(screen.getByRole('link', { name: 'Portfolio' })).toHaveAttribute(
            'href',
            '/portfolio',
        );
        expect(navigationMenuTriggerStyle()).toContain('inline-flex');
    });

    it('selects a value and renders both content positioning modes', () => {
        const onValueChange = vi.fn();
        const view = render(
            <Select
                defaultOpen
                defaultValue="equity"
                onValueChange={onValueChange}
            >
                <SelectTrigger
                    className="custom-select"
                    aria-label="Asset class"
                >
                    <SelectValue placeholder="Choose asset" />
                </SelectTrigger>
                <SelectContent className="custom-select-content">
                    <SelectGroup>
                        <SelectLabel>Asset class</SelectLabel>
                        <SelectItem value="equity">Equity</SelectItem>
                        <SelectSeparator />
                        <SelectItem value="debt">Debt</SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>,
        );

        expect(
            screen.getByRole('combobox', {
                hidden: true,
                name: 'Asset class',
            }),
        ).toHaveClass('custom-select');
        expect(screen.getByRole('option', { name: 'Equity' })).toHaveAttribute(
            'data-state',
            'checked',
        );

        view.unmount();

        render(
            <Select open value="debt">
                <SelectTrigger size="sm" aria-label="Positioned asset">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent
                    position="item-aligned"
                    side="top"
                    sideOffset={8}
                    align="start"
                >
                    <SelectItem value="debt">Debt</SelectItem>
                </SelectContent>
            </Select>,
        );

        expect(
            screen.getByRole('option', { name: 'Debt' }),
        ).toBeInTheDocument();
    });

    it('opens tooltip and toggles standalone and grouped controls', () => {
        const onPressedChange = vi.fn();

        render(
            <TooltipProvider>
                <Tooltip defaultOpen>
                    <TooltipTrigger>Explain fee</TooltipTrigger>
                    <TooltipContent className="custom-tooltip">
                        Charged once
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>,
        );

        expect(screen.getByRole('tooltip')).toHaveClass('custom-tooltip');

        const { rerender } = render(
            <Toggle
                aria-label="Bold"
                className="custom-toggle"
                onPressedChange={onPressedChange}
            >
                B
            </Toggle>,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Bold' }));
        expect(onPressedChange).toHaveBeenCalledWith(true);

        rerender(
            <ToggleGroup type="single" variant="outline" size="sm">
                <ToggleGroupItem value="day" aria-label="Daily">
                    D
                </ToggleGroupItem>
                <ToggleGroupItem value="week" aria-label="Weekly">
                    W
                </ToggleGroupItem>
            </ToggleGroup>,
        );

        fireEvent.click(screen.getByRole('radio', { name: 'Daily' }));
        expect(screen.getByRole('radio', { name: 'Daily' })).toHaveAttribute(
            'data-state',
            'on',
        );

        rerender(
            <ToggleGroup type="single">
                <ToggleGroupItem
                    value="month"
                    variant="outline"
                    size="lg"
                    aria-label="Monthly"
                >
                    M
                </ToggleGroupItem>
            </ToggleGroup>,
        );

        expect(screen.getByRole('radio', { name: 'Monthly' })).toHaveAttribute(
            'data-variant',
            'outline',
        );
        expect(screen.getByRole('radio', { name: 'Monthly' })).toHaveAttribute(
            'data-size',
            'lg',
        );
        expect(toggleVariants({ variant: 'outline', size: 'lg' })).toContain(
            'border-input',
        );
    });

    it('configures Sonner from appearance and flash-toast hooks', () => {
        vi.mocked(useAppearance).mockReturnValue({
            appearance: 'dark',
            resolvedAppearance: 'dark',
            updateAppearance: vi.fn(),
        });

        render(<Toaster duration={1000} />);

        expect(useAppearance).toHaveBeenCalled();
        expect(useFlashToast).toHaveBeenCalled();
        expect(screen.getByLabelText(/^Notifications/)).toBeInTheDocument();
    });
});
