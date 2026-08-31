import { fireEvent, render, screen } from '@testing-library/react';
import { CircleIcon } from 'lucide-react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge, badgeVariants } from '@/components/ui/badge';
import {
    Breadcrumb,
    BreadcrumbEllipsis,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Icon } from '@/components/ui/icon';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';

describe('static UI primitives', () => {
    it('renders alerts and every alert variant', () => {
        const { rerender } = render(
            <Alert className="custom-alert">
                <AlertTitle className="custom-title">Account notice</AlertTitle>
                <AlertDescription className="custom-description">
                    Review the terms.
                </AlertDescription>
            </Alert>,
        );

        expect(screen.getByRole('alert')).toHaveClass('custom-alert');
        expect(screen.getByText('Account notice')).toHaveClass('custom-title');
        expect(screen.getByText('Review the terms.')).toHaveClass(
            'custom-description',
        );

        rerender(
            <Alert variant="destructive">
                <AlertTitle>Blocked</AlertTitle>
                <AlertDescription>Try again.</AlertDescription>
            </Alert>,
        );

        expect(screen.getByRole('alert')).toHaveClass(
            'text-destructive-foreground',
        );
    });

    it('renders avatar image and fallback states', () => {
        const { rerender } = render(
            <Avatar className="custom-avatar" data-testid="avatar">
                <AvatarImage
                    className="custom-image"
                    src="/avatar.png"
                    alt="Ada Lovelace"
                />
                <AvatarFallback className="custom-fallback">AL</AvatarFallback>
            </Avatar>,
        );

        const image = screen.queryByRole('img', { name: 'Ada Lovelace' });

        if (image) {
            fireEvent.load(image);
        }

        expect(screen.getByTestId('avatar')).toHaveClass('custom-avatar');

        rerender(
            <Avatar>
                <AvatarFallback>AL</AvatarFallback>
            </Avatar>,
        );

        expect(screen.getByText('AL')).toHaveAttribute(
            'data-slot',
            'avatar-fallback',
        );
    });

    it('renders badges as spans or delegated children', () => {
        const { rerender } = render(
            <Badge className="custom-badge">Verified</Badge>,
        );

        expect(screen.getByText('Verified').tagName).toBe('SPAN');
        expect(screen.getByText('Verified')).toHaveClass('custom-badge');

        rerender(
            <Badge asChild variant="secondary">
                <a href="/verified">Verified account</a>
            </Badge>,
        );

        expect(
            screen.getByRole('link', { name: 'Verified account' }),
        ).toHaveClass('bg-secondary');

        expect(badgeVariants({ variant: 'destructive' })).toContain(
            'bg-destructive',
        );
        expect(badgeVariants({ variant: 'outline' })).toContain(
            'text-foreground',
        );
    });

    it('composes breadcrumb navigation with default and custom separators', () => {
        const { rerender } = render(
            <Breadcrumb data-testid="breadcrumb">
                <BreadcrumbList className="custom-list">
                    <BreadcrumbItem className="custom-item">
                        <BreadcrumbLink className="custom-link" href="/home">
                            Home
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator className="default-separator" />
                    <BreadcrumbItem>
                        <BreadcrumbPage className="custom-page">
                            Portfolio
                        </BreadcrumbPage>
                    </BreadcrumbItem>
                    <BreadcrumbEllipsis className="custom-ellipsis" />
                </BreadcrumbList>
            </Breadcrumb>,
        );

        expect(screen.getByTestId('breadcrumb')).toHaveAccessibleName(
            'breadcrumb',
        );
        expect(screen.getByRole('link', { name: 'Home' })).toHaveClass(
            'custom-link',
        );
        expect(screen.getByRole('link', { name: 'Portfolio' })).toHaveAttribute(
            'aria-current',
            'page',
        );
        expect(screen.getByText('More')).toBeInTheDocument();

        rerender(
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink asChild>
                            <button type="button">Delegated home</button>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator>
                        <span>→</span>
                    </BreadcrumbSeparator>
                </BreadcrumbList>
            </Breadcrumb>,
        );

        expect(
            screen.getByRole('button', { name: 'Delegated home' }),
        ).toHaveAttribute('data-slot', 'breadcrumb-link');
        expect(screen.getByText('→')).toBeInTheDocument();
    });

    it('renders button variants and delegated buttons', () => {
        const onClick = vi.fn();
        const { rerender } = render(
            <Button className="custom-button" onClick={onClick}>
                Continue
            </Button>,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
        expect(onClick).toHaveBeenCalledOnce();
        expect(screen.getByRole('button', { name: 'Continue' })).toHaveClass(
            'custom-button',
        );

        rerender(
            <Button asChild variant="outline" size="sm">
                <a href="/continue">Continue as link</a>
            </Button>,
        );

        expect(
            screen.getByRole('link', { name: 'Continue as link' }),
        ).toHaveClass('border-input');

        expect(
            buttonVariants({ variant: 'destructive', size: 'lg' }),
        ).toContain('bg-destructive');
        expect(
            buttonVariants({ variant: 'secondary', size: 'icon' }),
        ).toContain('bg-secondary');
        expect(buttonVariants({ variant: 'ghost' })).toContain(
            'hover:bg-accent',
        );
        expect(buttonVariants({ variant: 'link' })).toContain(
            'underline-offset-4',
        );
    });

    it('composes every card section', () => {
        render(
            <Card className="custom-card" data-testid="opportunity-card">
                <CardHeader className="custom-header">
                    <CardTitle className="custom-title">Opportunity</CardTitle>
                    <CardDescription className="custom-description">
                        Open for investment
                    </CardDescription>
                </CardHeader>
                <CardContent className="custom-content">RWF 50,000</CardContent>
                <CardFooter className="custom-footer">View details</CardFooter>
            </Card>,
        );

        expect(screen.getByTestId('opportunity-card')).toHaveClass(
            'custom-card',
        );
        expect(screen.getByText('Opportunity')).toHaveClass('custom-title');
        expect(screen.getByText('Open for investment')).toHaveClass(
            'custom-description',
        );
        expect(screen.getByText('RWF 50,000')).toHaveClass('custom-content');
        expect(screen.getByText('View details')).toHaveClass('custom-footer');
    });

    it('renders icon, input, label, separator, skeleton, and spinner primitives', () => {
        const { rerender } = render(
            <div>
                <Icon iconNode={CircleIcon} className="custom-icon" />
                <Label className="custom-label" htmlFor="name">
                    Name
                </Label>
                <Input
                    className="custom-input"
                    id="name"
                    type="text"
                    defaultValue="Ada"
                />
                <Separator
                    className="custom-separator"
                    data-testid="separator"
                />
                <Skeleton className="custom-skeleton">Loading card</Skeleton>
                <Spinner className="custom-spinner" />
            </div>,
        );

        expect(
            renderToStaticMarkup(
                <Icon iconNode={CircleIcon} className="custom-icon" />,
            ),
        ).toContain('custom-icon');
        expect(screen.getByLabelText('Name')).toHaveValue('Ada');
        expect(screen.getByLabelText('Name')).toHaveClass('custom-input');
        expect(screen.getByTestId('separator')).toHaveAttribute(
            'data-orientation',
            'horizontal',
        );
        expect(screen.getByText('Loading card')).toHaveClass('custom-skeleton');
        expect(screen.getByRole('status', { name: 'Loading' })).toHaveClass(
            'custom-spinner',
        );

        rerender(
            <div>
                <Icon iconNode={null} />
                <Icon />
                <Input aria-label="Untyped input" />
                <Label>Standalone label</Label>
                <Separator orientation="vertical" decorative={false} />
                <Skeleton />
                <Spinner />
            </div>,
        );

        expect(screen.getByRole('separator')).toHaveAttribute(
            'aria-orientation',
            'vertical',
        );
        expect(screen.getByLabelText('Untyped input')).not.toHaveAttribute(
            'type',
        );
    });

    it('creates isolated SVG pattern identifiers', () => {
        const view = renderToStaticMarkup(
            <PlaceholderPattern className="custom-pattern" />,
        );
        const patternId = view.match(/<pattern id="([^"]+)"/)?.[1];

        expect(patternId).toBeDefined();
        expect(view).toContain(`fill="url(#${patternId})"`);
        expect(view).toContain('class="custom-pattern"');
        expect(renderToStaticMarkup(<PlaceholderPattern />)).not.toContain(
            'custom-pattern',
        );
        expect(renderToStaticMarkup(<Icon iconNode={null} />)).toBe('');
        expect(renderToStaticMarkup(<Icon />)).toBe('');
    });
});
