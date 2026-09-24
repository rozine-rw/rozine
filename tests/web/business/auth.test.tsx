import { render, screen, within } from '@testing-library/react';
import type { ComponentProps, ReactNode } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import BusinessLogin from '@/pages/business/auth/login';
import BusinessRegister from '@/pages/business/auth/register';
import matchedFixture from '../../../resources/fixtures/ui/business-register-matched.json';
import registerFixture from '../../../resources/fixtures/ui/business-register.json';
import { renderWithUser } from '../helpers/render-with-user';

type FormState = { processing: boolean; errors: Record<string, string> };

type FormProps = {
    children: (state: FormState) => ReactNode;
    action?: string;
    method?: string;
};

const inertia = vi.hoisted(() => ({
    forms: [] as FormProps[],
    state: { processing: false, errors: {} as Record<string, string> },
    post: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    Form: (props: FormProps) => {
        inertia.forms.push(props);

        return <form aria-label="form">{props.children(inertia.state)}</form>;
    },
    Head: ({ title }: { title: string }) => (
        <span data-testid="head">{title}</span>
    ),
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
    router: { post: inertia.post },
}));

beforeEach(() => {
    inertia.forms = [];
    inertia.state = { processing: false, errors: {} };
});

const registerLink = { url: '/business/register', method: 'get' as const };

describe('Business login', () => {
    it('signs in through the shared login with the design copy', () => {
        render(<BusinessLogin links={{ register: registerLink }} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Business log in');
        expect(
            screen.getByRole('heading', { name: 'Welcome back' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Log in to your issuer dashboard.'),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Work email')).toHaveAttribute(
            'placeholder',
            'you@company.rw',
        );
        expect(screen.getByLabelText('Password')).toHaveAttribute(
            'type',
            'password',
        );
        expect(inertia.forms[0].action).toBe('/login');
        expect(screen.getByRole('button', { name: 'Log in' })).toBeEnabled();
        expect(
            screen.getByRole('link', { name: 'Register a business' }),
        ).toHaveAttribute('href', '/business/register');
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        expect(screen.getByText('RDB verified')).toBeInTheDocument();
    });

    it('explains a rejected sign-in and keeps the button green while it works', () => {
        inertia.state = {
            processing: true,
            errors: { password: 'These credentials do not match our records.' },
        };
        render(
            <BusinessLogin
                status="Your password was reset."
                links={{ register: registerLink }}
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            'These credentials do not match our records.',
        );
        expect(screen.getByLabelText('Password')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(screen.getByLabelText('Work email')).not.toHaveAttribute(
            'aria-invalid',
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            'Your password was reset.',
        );

        const submit = screen.getByRole('button', { name: 'Please wait…' });

        expect(submit).toBeDisabled();
        expect(submit).toHaveAttribute('aria-busy', 'true');
        expect(submit).not.toHaveAttribute('data-inactive');
    });

    it('prefers the email error when both fields fail', () => {
        inertia.state.errors = {
            email: 'Enter a valid work email',
            password: 'Too short',
        };
        render(<BusinessLogin links={{ register: registerLink }} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Enter a valid work email',
        );
    });
});

describe('Business registration', () => {
    const registerProps = registerFixture.props as ComponentProps<
        typeof BusinessRegister
    >;
    const matchedProps = matchedFixture.props as ComponentProps<
        typeof BusinessRegister
    >;

    it('asks for the RDB company code first and never a tax identifier', () => {
        render(<BusinessRegister {...registerProps} />);

        expect(
            screen.getByRole('heading', { name: 'Register your business' }),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('RDB company code')).toHaveAttribute(
            'maxlength',
            '9',
        );
        expect(screen.queryByText(/TIN/)).not.toBeInTheDocument();
        expect(
            screen.queryByLabelText('One-time code'),
        ).not.toBeInTheDocument();
        expect(inertia.forms[0].action).toBe(registerProps.actions.lookup.url);
        expect(
            screen.getByRole('button', { name: 'Verify company code' }),
        ).toBeEnabled();
        expect(screen.getByRole('link', { name: 'Log in' })).toHaveAttribute(
            'href',
            '/preview/business-login',
        );
    });

    it('reports a registry refusal and shows progress while RDB answers', () => {
        inertia.state = {
            processing: true,
            errors: { company_code: 'No company matches this code' },
        };
        render(<BusinessRegister {...registerProps} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'No company matches this code',
        );
        expect(
            screen.getByRole('button', { name: 'Verifying with RDB…' }),
        ).toBeDisabled();
    });

    it('shows the matched company and its masked contacts, then verifies the code', () => {
        render(<BusinessRegister {...matchedProps} />);

        expect(screen.getByLabelText('RDB company code')).toHaveValue(
            '102938475',
        );
        expect(
            screen.getByText('✓ Company matched at RDB'),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Registered business name')).toHaveValue(
            'Karongi Freight Ltd',
        );
        expect(screen.getByText('+250 788 *** 245')).toBeInTheDocument();
        expect(screen.getByText('op***@ka***.rw')).toBeInTheDocument();
        expect(screen.getByLabelText('One-time code')).toHaveAttribute(
            'autocomplete',
            'one-time-code',
        );
        expect(inertia.forms[0].action).toBe(matchedProps.actions.verify.url);
        expect(
            screen.getByRole('button', { name: 'Verify & continue' }),
        ).toBeEnabled();
    });

    it('marks a wrong code inline and resends to the same company', async () => {
        inertia.state = {
            processing: true,
            errors: {
                code: 'Enter the 6-digit code sent to your phone and email',
            },
        };
        const { user } = renderWithUser(
            <BusinessRegister {...matchedProps} status="New code sent" />,
        );

        expect(screen.getByLabelText('One-time code')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(
            screen.getByText(
                'Enter the 6-digit code sent to your phone and email',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent('New code sent');
        expect(
            screen.getByRole('button', { name: 'Verifying code…' }),
        ).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Resend code' }));

        expect(inertia.post).toHaveBeenCalledWith(
            matchedProps.actions.resend.url,
            {
                company_code: '102938475',
            },
        );
    });

    it('surfaces a name problem in the banner', () => {
        inertia.state.errors = { registered_name: 'Enter the registered name' };
        render(<BusinessRegister {...matchedProps} />);

        expect(
            within(screen.getByRole('alert')).getByText(
                'Enter the registered name',
            ),
        ).toBeInTheDocument();
    });
});
