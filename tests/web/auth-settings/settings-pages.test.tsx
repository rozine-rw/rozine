// eslint-disable-next-line testing-library/no-manual-cleanup
import { cleanup, render, screen } from '@testing-library/react';
import type { ComponentProps, ReactNode } from 'react';
import { forwardRef } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import SettingsLayout from '@/layouts/settings/layout';
import Appearance from '@/pages/settings/appearance';
import Profile from '@/pages/settings/profile';
import Security from '@/pages/settings/security';

type FormState = {
    processing: boolean;
    errors: Record<string, string>;
};

type FormProps = {
    children: (state: FormState) => ReactNode;
    onError?: (errors: Record<string, string>) => void;
    [key: string]: unknown;
};

const doubles = vi.hoisted(() => ({
    auth: {
        user: {
            id: 1,
            name: 'Ada Lovelace',
            email: 'ada@example.com',
            email_verified_at: null as string | null,
            created_at: '2026-01-01',
            updated_at: '2026-01-01',
        },
    },
    forms: [] as FormProps[],
    state: {
        processing: false,
        errors: {} as Record<string, string>,
    },
    currentUrl: vi.fn((href: unknown): boolean => {
        void href;

        return false;
    }),
}));

vi.mock('@inertiajs/react', () => ({
    Form: (props: FormProps) => {
        doubles.forms.push(props);

        return <form>{props.children(doubles.state)}</form>;
    },
    Head: ({ title }: { title: string }) => (
        <div data-testid="head">{title}</div>
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
    usePage: () => ({ props: { auth: doubles.auth } }),
}));

vi.mock('@/components/appearance-tabs', () => ({
    default: () => <div>Appearance controls</div>,
}));

vi.mock('@/components/delete-user', () => ({
    default: () => <div>Delete user controls</div>,
}));

vi.mock('@/components/heading', () => ({
    default: ({
        title,
        description,
    }: {
        title: string;
        description: string;
    }) => (
        <header>
            <h2>{title}</h2>
            <p>{description}</p>
        </header>
    ),
}));

vi.mock('@/components/input-error', () => ({
    default: ({ message }: { message?: string }) =>
        message ? <p>{message}</p> : null,
}));

vi.mock('@/components/manage-passkeys', () => ({
    default: ({
        canManagePasskeys,
        passkeys,
    }: {
        canManagePasskeys?: boolean;
        passkeys?: unknown[];
    }) => (
        <div>
            Passkeys: {String(canManagePasskeys)} / {passkeys?.length ?? 0}
        </div>
    ),
}));

vi.mock('@/components/manage-two-factor', () => ({
    default: ({
        canManageTwoFactor,
        requiresConfirmation,
        twoFactorEnabled,
    }: {
        canManageTwoFactor?: boolean;
        requiresConfirmation?: boolean;
        twoFactorEnabled?: boolean;
    }) => (
        <div>
            Two factor: {String(canManageTwoFactor)} /{' '}
            {String(requiresConfirmation)} / {String(twoFactorEnabled)}
        </div>
    ),
}));

vi.mock('@/components/password-input', () => ({
    default: forwardRef<HTMLInputElement, ComponentProps<'input'>>(
        (props, ref) => <input {...props} ref={ref} />,
    ),
}));

vi.mock('@/components/ui/button', () => ({
    Button: ({
        asChild,
        children,
        ...props
    }: ComponentProps<'button'> & { asChild?: boolean }) => (
        <button {...props} data-as-child={asChild || undefined}>
            {children}
        </button>
    ),
}));

vi.mock('@/components/ui/input', () => ({
    Input: (props: ComponentProps<'input'>) => <input {...props} />,
}));

vi.mock('@/components/ui/label', () => ({
    Label: (props: ComponentProps<'label'>) => <label {...props} />,
}));

vi.mock('@/components/ui/separator', () => ({
    Separator: (props: ComponentProps<'hr'>) => <hr {...props} />,
}));

vi.mock('@/hooks/use-current-url', () => ({
    useCurrentUrl: () => ({
        isCurrentOrParentUrl: doubles.currentUrl,
    }),
}));

beforeEach(() => {
    doubles.forms.length = 0;
    doubles.state.processing = false;
    doubles.state.errors = {};
    doubles.auth.user.email_verified_at = null;
    doubles.currentUrl.mockImplementation((href: unknown) => {
        return JSON.stringify(href).includes('profile');
    });
});

afterEach(() => {
    cleanup();
});

describe('settings pages and layout', () => {
    it('renders appearance settings and its breadcrumb metadata', () => {
        render(<Appearance />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Appearance settings',
        );
        expect(screen.getByText('Appearance controls')).toBeInTheDocument();
        expect(Appearance.layout.breadcrumbs).toHaveLength(1);
    });

    it('renders profile validation, verification, and processing states', () => {
        doubles.state.processing = true;
        doubles.state.errors = {
            name: 'Name is required.',
            email: 'Email is invalid.',
        };
        const { rerender } = render(
            <Profile mustVerifyEmail status="verification-link-sent" />,
        );

        expect(screen.getByDisplayValue('Ada Lovelace')).toBeInTheDocument();
        expect(screen.getByDisplayValue('ada@example.com')).toBeInTheDocument();
        expect(screen.getByText('Name is required.')).toBeInTheDocument();
        expect(screen.getByText('Email is invalid.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Save' })).toBeDisabled();
        expect(
            screen.getByRole('link', {
                name: /Click here to re-send/,
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/A new verification link has been sent/),
        ).toBeInTheDocument();

        doubles.auth.user.email_verified_at = '2026-08-24T00:00:00Z';
        doubles.state.processing = false;
        doubles.state.errors = {};
        rerender(<Profile key="verified" mustVerifyEmail status="unrelated" />);
        expect(
            screen.queryByRole('link', {
                name: /Click here to re-send/,
            }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Save' })).toBeEnabled();

        doubles.auth.user.email_verified_at = null;
        rerender(
            <Profile key="verification-disabled" mustVerifyEmail={false} />,
        );
        expect(
            screen.queryByText(/Your email address is unverified/),
        ).not.toBeInTheDocument();
        expect(screen.getByText('Delete user controls')).toBeInTheDocument();
    });

    it('focuses invalid security fields and forwards management props', () => {
        doubles.state.processing = true;
        doubles.state.errors = {
            current_password: 'Current password is wrong.',
            password: 'Password is weak.',
            password_confirmation: 'Passwords differ.',
        };
        const passkeys = [
            {
                id: 1,
                name: 'Laptop',
                authenticator: 'platform',
                created_at_diff: 'today',
                last_used_at_diff: null,
            },
        ];
        const view = render(
            <Security
                passwordRules="minlength:12"
                canManagePasskeys
                passkeys={passkeys}
                canManageTwoFactor
                requiresConfirmation
                twoFactorEnabled={false}
            />,
        );

        expect(
            screen.getByText('Current password is wrong.'),
        ).toBeInTheDocument();
        expect(screen.getByText('Password is weak.')).toBeInTheDocument();
        expect(screen.getByText('Passwords differ.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Save' })).toBeDisabled();
        expect(screen.getByText('Passkeys: true / 1')).toBeInTheDocument();
        expect(
            screen.getByText('Two factor: true / true / false'),
        ).toBeInTheDocument();

        const onError = doubles.forms.at(-1)?.onError;
        onError?.({});
        onError?.({ password: 'invalid' });
        expect(screen.getByPlaceholderText('New password')).toHaveFocus();
        onError?.({ current_password: 'invalid' });
        expect(screen.getByPlaceholderText('Current password')).toHaveFocus();
        onError?.({
            password: 'invalid',
            current_password: 'invalid',
        });
        expect(screen.getByPlaceholderText('Current password')).toHaveFocus();

        view.unmount();
        expect(() =>
            onError?.({
                password: 'invalid',
                current_password: 'invalid',
            }),
        ).not.toThrow();
    });

    it('renders settings navigation and highlights only matching routes', () => {
        render(
            <SettingsLayout>
                <p>Settings content</p>
            </SettingsLayout>,
        );

        expect(
            screen.getByRole('navigation', { name: 'Settings' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Profile' })).toHaveAttribute(
            'href',
            '/settings/profile',
        );
        expect(screen.getByRole('link', { name: 'Security' })).toHaveAttribute(
            'href',
            '/settings/security',
        );
        expect(
            screen.getByRole('link', { name: 'Appearance' }),
        ).toHaveAttribute('href', '/settings/appearance');
        expect(screen.getByText('Settings content')).toBeInTheDocument();
        expect(doubles.currentUrl).toHaveBeenCalledTimes(3);
        expect(screen.getByRole('button', { name: 'Profile' })).toHaveClass(
            'bg-muted',
        );
        expect(
            screen.getByRole('button', { name: 'Security' }),
        ).not.toHaveClass('bg-muted');
    });
});
