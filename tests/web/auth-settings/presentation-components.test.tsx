// eslint-disable-next-line testing-library/no-manual-cleanup
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import AlertError from '@/components/alert-error';
import AppearanceTabs from '@/components/appearance-tabs';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';

const appearance = vi.hoisted(() => ({
    mode: 'system' as 'light' | 'dark' | 'system',
    update: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({
        children,
        href,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & {
        href: string | { url: string; method: string };
    }) => (
        <a href={typeof href === 'string' ? href : href.url} {...props}>
            {children}
        </a>
    ),
}));

vi.mock('@/hooks/use-appearance', () => ({
    useAppearance: () => ({
        appearance: appearance.mode,
        resolvedAppearance: appearance.mode === 'dark' ? 'dark' : 'light',
        updateAppearance: appearance.update,
    }),
}));

afterEach(() => {
    cleanup();
    appearance.mode = 'system';
});

describe('small authentication and settings components', () => {
    it('renders an input error only when a message is available', () => {
        const { container, rerender } = render(<InputError />);

        expect(container).toBeEmptyDOMElement();

        rerender(
            <InputError
                message="The email is invalid."
                className="mt-2"
                aria-live="polite"
            />,
        );

        expect(screen.getByText('The email is invalid.')).toHaveClass('mt-2');
        expect(screen.getByText('The email is invalid.')).toHaveAttribute(
            'aria-live',
            'polite',
        );
    });

    it('deduplicates alert errors and supports default and custom titles', () => {
        const { rerender } = render(
            <AlertError errors={['Network error', 'Network error']} />,
        );

        expect(screen.getByText('Something went wrong.')).toBeInTheDocument();
        expect(screen.getAllByText('Network error')).toHaveLength(1);

        rerender(
            <AlertError
                title="Unable to continue"
                errors={['First error', 'Second error']}
            />,
        );

        expect(screen.getByText('Unable to continue')).toBeInTheDocument();
        expect(screen.getAllByRole('listitem')).toHaveLength(2);
    });

    it('merges text-link classes and forwards link properties', () => {
        const { rerender } = render(<TextLink href="/login">Log in</TextLink>);

        expect(screen.getByRole('link', { name: 'Log in' })).toHaveAttribute(
            'href',
            '/login',
        );

        rerender(
            <TextLink
                href={{ url: '/register', method: 'get' }}
                className="custom-link"
            >
                Register
            </TextLink>,
        );

        expect(screen.getByRole('link', { name: 'Register' })).toHaveClass(
            'custom-link',
        );
    });

    it('reveals and hides passwords while forwarding class names and refs', () => {
        const inputRef = vi.fn();
        const { rerender } = render(
            <PasswordInput aria-label="Password" ref={inputRef} />,
        );
        const input = screen.getByLabelText('Password');

        expect(input).toHaveAttribute('type', 'password');
        expect(inputRef).toHaveBeenCalledWith(input);

        fireEvent.click(screen.getByRole('button', { name: 'Show password' }));
        expect(input).toHaveAttribute('type', 'text');

        fireEvent.click(screen.getByRole('button', { name: 'Hide password' }));
        expect(input).toHaveAttribute('type', 'password');

        rerender(
            <PasswordInput aria-label="Password" className="custom-password" />,
        );
        expect(screen.getByLabelText('Password')).toHaveClass(
            'custom-password',
        );
    });

    it.each(['light', 'dark', 'system'] as const)(
        'selects and updates the %s appearance',
        (mode) => {
            appearance.mode = mode;
            render(
                <AppearanceTabs className="custom-tabs" data-testid="tabs" />,
            );

            expect(screen.getByTestId('tabs')).toHaveClass('custom-tabs');

            for (const choice of ['Light', 'Dark', 'System']) {
                fireEvent.click(screen.getByRole('button', { name: choice }));
            }

            expect(appearance.update).toHaveBeenCalledWith('light');
            expect(appearance.update).toHaveBeenCalledWith('dark');
            expect(appearance.update).toHaveBeenCalledWith('system');
        },
    );
});
