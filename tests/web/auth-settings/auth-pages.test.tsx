// eslint-disable-next-line testing-library/no-manual-cleanup
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import type { ComponentProps, ReactNode } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import ConfirmPassword from '@/pages/auth/confirm-password';
import ForgotPassword from '@/pages/auth/forgot-password';
import Login from '@/pages/auth/login';
import Register from '@/pages/auth/register';
import ResetPassword from '@/pages/auth/reset-password';
import TwoFactorChallenge from '@/pages/auth/two-factor-challenge';
import VerifyEmail from '@/pages/auth/verify-email';

type FormState = {
    processing: boolean;
    errors: Record<string, string>;
    clearErrors: ReturnType<typeof vi.fn>;
    resetAndClearErrors: ReturnType<typeof vi.fn>;
};

type FormProps = {
    children: (state: FormState) => ReactNode;
    transform?: (data: Record<string, string>) => Record<string, string>;
    resetOnSuccess?: boolean | string[];
    [key: string]: unknown;
};

const inertia = vi.hoisted(() => ({
    forms: [] as FormProps[],
    layoutProps: vi.fn(),
    state: {
        processing: false,
        errors: {} as Record<string, string>,
        clearErrors: vi.fn(),
        resetAndClearErrors: vi.fn(),
    },
}));

vi.mock('@inertiajs/react', () => ({
    Form: (props: FormProps) => {
        inertia.forms.push(props);

        return <form>{props.children(inertia.state)}</form>;
    },
    Head: ({ title }: { title: string }) => (
        <div data-testid="head">{title}</div>
    ),
    setLayoutProps: inertia.layoutProps,
}));

vi.mock('@/components/input-error', () => ({
    default: ({ message }: { message?: string }) =>
        message ? <p>{message}</p> : null,
}));

vi.mock('@/components/passkey-verify', () => ({
    default: ({ label = 'Sign in with a passkey' }: { label?: string }) => (
        <button type="button">{label}</button>
    ),
}));

vi.mock('@/components/password-input', () => ({
    default: (props: ComponentProps<'input'>) => <input {...props} />,
}));

vi.mock('@/components/text-link', () => ({
    default: ({
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
}));

vi.mock('@/components/ui/button', () => ({
    Button: (props: ComponentProps<'button'>) => <button {...props} />,
}));

vi.mock('@/components/ui/checkbox', () => ({
    Checkbox: (props: ComponentProps<'input'>) => (
        <input type="checkbox" {...props} />
    ),
}));

vi.mock('@/components/ui/input', () => ({
    Input: (props: ComponentProps<'input'>) => <input {...props} />,
}));

vi.mock('@/components/ui/input-otp', () => ({
    InputOTP: ({
        children,
        onChange,
        ...props
    }: ComponentProps<'input'> & {
        children?: ReactNode;
        onChange?: (value: string) => void;
    }) => (
        <div>
            <input
                {...props}
                aria-label="One-time code"
                onChange={(event) => onChange?.(event.target.value)}
            />
            {children}
        </div>
    ),
    InputOTPGroup: ({ children }: { children: ReactNode }) => (
        <div>{children}</div>
    ),
    InputOTPSlot: ({ index }: { index: number }) => <span>{index}</span>,
}));

vi.mock('@/components/ui/label', () => ({
    Label: (props: ComponentProps<'label'>) => <label {...props} />,
}));

vi.mock('@/components/ui/spinner', () => ({
    Spinner: () => <span data-testid="spinner">Loading</span>,
}));

beforeEach(() => {
    inertia.forms.length = 0;
    inertia.state.processing = false;
    inertia.state.errors = {};
});

afterEach(() => {
    cleanup();
});

describe('authentication pages', () => {
    it('renders confirm-password idle, processing, and error states', () => {
        const { rerender } = render(<ConfirmPassword />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Confirm password',
        );
        expect(
            screen.getByRole('button', { name: 'Confirm with passkey' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Confirm password' }),
        ).toBeEnabled();

        inertia.state.processing = true;
        inertia.state.errors = { password: 'Password is required.' };
        rerender(<ConfirmPassword key="processing" />);

        expect(screen.getByText('Password is required.')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: /Confirm password/ }),
        ).toBeDisabled();
        expect(screen.getByTestId('spinner')).toBeInTheDocument();
    });

    it('renders forgot-password status, errors, processing, and login link', () => {
        inertia.state.processing = true;
        inertia.state.errors = { email: 'Email is invalid.' };
        const { rerender } = render(<ForgotPassword status="Email sent." />);

        expect(screen.getByText('Email sent.')).toBeInTheDocument();
        expect(screen.getByText('Email is invalid.')).toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: /Email password reset link/,
            }),
        ).toBeDisabled();
        expect(screen.getByRole('link', { name: 'log in' })).toHaveAttribute(
            'href',
            '/login',
        );

        inertia.state.processing = false;
        inertia.state.errors = {};
        rerender(<ForgotPassword />);

        expect(screen.queryByText('Email sent.')).not.toBeInTheDocument();
        expect(screen.queryByTestId('spinner')).not.toBeInTheDocument();
    });

    it('renders all login branches', () => {
        inertia.state.processing = true;
        inertia.state.errors = {
            email: 'Unknown email.',
            password: 'Wrong password.',
        };
        const { rerender } = render(
            <Login canResetPassword status="Password reset." />,
        );

        expect(screen.getByText('Password reset.')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Forgot your password?' }),
        ).toHaveAttribute('href', '/forgot-password');
        expect(screen.getByText('Unknown email.')).toBeInTheDocument();
        expect(screen.getByText('Wrong password.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Log in/ })).toBeDisabled();

        inertia.state.processing = false;
        inertia.state.errors = {};
        rerender(<Login canResetPassword={false} />);

        expect(
            screen.queryByRole('link', { name: 'Forgot your password?' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByText('Password reset.')).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Sign up' })).toHaveAttribute(
            'href',
            '/register',
        );
    });

    it('renders register processing, password rules, and all errors', () => {
        inertia.state.processing = true;
        inertia.state.errors = {
            name: 'Name required.',
            email: 'Email required.',
            password: 'Password weak.',
            password_confirmation: 'Passwords differ.',
        };
        const { rerender } = render(<Register passwordRules="minlength:12" />);

        expect(screen.getByText('Name required.')).toBeInTheDocument();
        expect(screen.getByText('Email required.')).toBeInTheDocument();
        expect(screen.getByText('Password weak.')).toBeInTheDocument();
        expect(screen.getByText('Passwords differ.')).toBeInTheDocument();
        expect(screen.getAllByTestId('spinner')).toHaveLength(1);
        expect(screen.getAllByRole('textbox')).toHaveLength(4);
        expect(screen.getByRole('link', { name: 'Log in' })).toHaveAttribute(
            'href',
            '/login',
        );

        inertia.state.processing = false;
        inertia.state.errors = {};
        rerender(<Register key="idle" passwordRules="minlength:12" />);
        expect(screen.queryByTestId('spinner')).not.toBeInTheDocument();
    });

    it('transforms reset-password data and renders both processing states', () => {
        inertia.state.processing = true;
        inertia.state.errors = {
            email: 'Email expired.',
            password: 'Password weak.',
            password_confirmation: 'Passwords differ.',
        };
        const { rerender } = render(
            <ResetPassword
                token="reset-token"
                email="member@example.com"
                passwordRules="minlength:12"
            />,
        );

        expect(screen.getByDisplayValue('member@example.com')).toHaveAttribute(
            'readonly',
        );
        expect(
            screen.getByRole('button', { name: /Reset password/ }),
        ).toBeDisabled();
        expect(screen.getByTestId('spinner')).toBeInTheDocument();

        const transform = inertia.forms.at(-1)?.transform;
        expect(transform?.({ password: 'new-password' })).toEqual({
            password: 'new-password',
            token: 'reset-token',
            email: 'member@example.com',
        });

        inertia.state.processing = false;
        inertia.state.errors = {};
        rerender(
            <ResetPassword
                key="idle"
                token="reset-token"
                email="member@example.com"
                passwordRules="minlength:12"
            />,
        );
        expect(screen.queryByTestId('spinner')).not.toBeInTheDocument();
    });

    it('switches between authentication and recovery codes', () => {
        inertia.state.processing = true;
        inertia.state.errors = {
            code: 'Code invalid.',
            recovery_code: 'Recovery code invalid.',
        };
        render(<TwoFactorChallenge />);

        expect(inertia.layoutProps).toHaveBeenLastCalledWith({
            title: 'Authentication code',
            description:
                'Enter the authentication code provided by your authenticator application.',
        });
        expect(screen.getByLabelText('One-time code')).toBeDisabled();
        expect(screen.getByText('Code invalid.')).toBeInTheDocument();

        fireEvent.change(screen.getByLabelText('One-time code'), {
            target: { value: '123456' },
        });
        fireEvent.click(
            screen.getByRole('button', {
                name: 'login using a recovery code',
            }),
        );

        expect(inertia.state.clearErrors).toHaveBeenCalled();
        expect(
            screen.getByPlaceholderText('Enter recovery code'),
        ).toBeInTheDocument();
        expect(screen.getByText('Recovery code invalid.')).toBeInTheDocument();
        expect(inertia.layoutProps).toHaveBeenLastCalledWith({
            title: 'Recovery code',
            description:
                'Please confirm access to your account by entering one of your emergency recovery codes.',
        });
        expect(inertia.forms.at(-1)?.resetOnSuccess).toBe(false);

        fireEvent.click(
            screen.getByRole('button', {
                name: 'login using an authentication code',
            }),
        );
        expect(screen.getByLabelText('One-time code')).toHaveValue('');
    });

    it('renders verification status and processing branches', () => {
        inertia.state.processing = true;
        const { rerender } = render(
            <VerifyEmail status="verification-link-sent" />,
        );

        expect(
            screen.getByText(/A new verification link has been sent/),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: /Resend verification email/,
            }),
        ).toBeDisabled();
        expect(screen.getByRole('link', { name: 'Log out' })).toHaveAttribute(
            'href',
            '/logout',
        );

        inertia.state.processing = false;
        rerender(<VerifyEmail key="idle" status="unrelated-status" />);
        expect(
            screen.queryByText(/A new verification link has been sent/),
        ).not.toBeInTheDocument();
        expect(screen.queryByTestId('spinner')).not.toBeInTheDocument();
    });
});
