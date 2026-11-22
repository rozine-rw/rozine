// eslint-disable-next-line testing-library/no-manual-cleanup
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import type { ComponentProps, ReactElement, ReactNode } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import DeleteUser, { DeleteAccountRow } from '@/components/delete-user';
import type { AccountClosure } from '@/components/delete-user';

type FormState = {
    resetAndClearErrors: ReturnType<typeof vi.fn>;
    processing: boolean;
    errors: Record<string, string>;
};

type FormProps = {
    children: (state: FormState) => ReactNode;
    onError?: () => void;
    [key: string]: unknown;
};

const form = vi.hoisted(() => ({
    props: [] as FormProps[],
    state: {
        resetAndClearErrors: vi.fn(),
        processing: false,
        errors: {} as Record<string, string>,
    },
}));

vi.mock('@inertiajs/react', () => ({
    Form: (props: FormProps) => {
        form.props.push(props);

        return <form>{props.children(form.state)}</form>;
    },
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & {
        href: string | { url: string };
    }) => (
        <a href={typeof href === 'string' ? href : href.url} {...props}>
            {children}
        </a>
    ),
}));

vi.mock('@/components/heading', () => ({
    default: ({ title }: { title: string }) => <h2>{title}</h2>,
}));

vi.mock('@/components/input-error', () => ({
    default: ({ message }: { message?: string }) =>
        message ? <p>{message}</p> : null,
}));

vi.mock('@/components/password-input', async () => {
    const { forwardRef } = await import('react');

    return {
        default: forwardRef<HTMLInputElement, ComponentProps<'input'>>(
            (props, ref) => <input {...props} ref={ref} />,
        ),
    };
});

vi.mock('@/components/ui/button', async () => {
    const { cloneElement, isValidElement } = await import('react');

    return {
        Button: ({
            asChild,
            children,
            ...props
        }: ComponentProps<'button'> & { asChild?: boolean }) => {
            if (asChild && isValidElement(children)) {
                return cloneElement(children as ReactElement, props);
            }

            return <button {...props}>{children}</button>;
        },
    };
});

vi.mock('@/components/ui/dialog', () => ({
    Dialog: ({
        children,
        open,
        onOpenChange,
    }: {
        children: ReactNode;
        open: boolean;
        onOpenChange: (open: boolean) => void;
    }) => (
        <div data-open={open ? 'yes' : 'no'} data-testid="dialog">
            <button type="button" onClick={() => onOpenChange(!open)}>
                Toggle dialog
            </button>
            {children}
        </div>
    ),
    DialogClose: ({ children }: { children: ReactNode }) => <>{children}</>,
    DialogContent: ({ children }: { children: ReactNode }) => (
        <div>{children}</div>
    ),
    DialogDescription: ({ children }: { children: ReactNode }) => (
        <p>{children}</p>
    ),
    DialogFooter: ({ children }: { children: ReactNode }) => (
        <div>{children}</div>
    ),
    DialogTitle: ({ children }: { children: ReactNode }) => <h3>{children}</h3>,
    DialogTrigger: ({ children }: { children: ReactNode }) => <>{children}</>,
}));

vi.mock('@/components/ui/input', () => ({
    Input: (props: ComponentProps<'input'>) => <input {...props} />,
}));

vi.mock('@/components/ui/label', () => ({
    Label: (props: ComponentProps<'label'>) => <label {...props} />,
}));

const READY: AccountClosure = { roles: ['investor'], blockers: [] };

beforeEach(() => {
    window.location.hash = '';
    form.props.length = 0;
    form.state.processing = false;
    form.state.errors = {};
});

afterEach(() => {
    cleanup();
});

describe('DeleteUser', () => {
    it('resets cancellation state and focuses password after an error', () => {
        form.state.errors = {
            password: 'Password is incorrect.',
            confirmation: 'Type DELETE to confirm.',
            reason: 'Too long.',
        };
        const view = render(<DeleteUser closure={READY} />);

        expect(
            screen.getByRole('heading', { name: 'Delete account' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Closing your account cannot be undone from the app.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('Password is incorrect.')).toBeInTheDocument();
        expect(screen.getByText('Type DELETE to confirm.')).toBeInTheDocument();
        expect(screen.getByText('Too long.')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Rozine keeps your records for the period Rwandan financial and tax law requires, then deletes them.',
            ),
        ).toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('button', { name: 'Keep my account' }),
        );
        expect(form.state.resetAndClearErrors).toHaveBeenCalledOnce();

        const onError = form.props.at(-1)?.onError;
        onError?.();
        expect(screen.getByLabelText('Password')).toHaveFocus();

        view.unmount();
        expect(() => onError?.()).not.toThrow();
    });

    it('enables the deletion only once DELETE is typed, and clears it when the dialog closes', () => {
        render(<DeleteUser closure={READY} />);
        const submit = screen.getByRole('button', {
            name: 'Permanently delete account',
        });
        const confirmation = screen.getByLabelText('Type DELETE to confirm');

        expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'no');
        expect(submit).toBeDisabled();

        fireEvent.change(confirmation, { target: { value: ' delete ' } });
        expect(submit).toBeEnabled();

        fireEvent.click(screen.getByRole('button', { name: 'Toggle dialog' }));
        expect(screen.getByTestId('dialog')).toHaveAttribute(
            'data-open',
            'yes',
        );
        expect(confirmation).toHaveValue('');
        expect(submit).toBeDisabled();
    });

    it('opens on its fragment and disables the deletion while processing', () => {
        window.location.hash = '#close-account';
        form.state.processing = true;
        render(<DeleteUser closure={READY} />);

        expect(screen.getByTestId('dialog')).toHaveAttribute(
            'data-open',
            'yes',
        );
        fireEvent.change(screen.getByLabelText('Type DELETE to confirm'), {
            target: { value: 'DELETE' },
        });
        expect(
            screen.getByRole('button', { name: 'Deleting…' }),
        ).toBeDisabled();
    });

    it('lists what blocks closure with a way to settle each, and offers no deletion', () => {
        render(
            <DeleteUser
                closure={{
                    roles: ['investor', 'staff'],
                    blockers: [
                        {
                            code: 'INVESTOR_WALLET_BALANCE',
                            role: 'investor',
                            link: { url: '/investor/wallet', method: 'get' },
                        },
                        { code: 'STAFF_ACCOUNT', role: 'staff', link: null },
                    ],
                }}
            />,
        );

        expect(
            screen.getByText(
                'Some items are still open. Settle them before you close your account.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Before you can delete your account'),
        ).toBeInTheDocument();
        expect(screen.getByText('Investor')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Your wallet still holds money. Withdraw it to your own account first.',
            ),
        ).toBeInTheDocument();
        expect(screen.getAllByRole('link', { name: 'Go there' })).toHaveLength(
            1,
        );
        expect(screen.getByRole('link', { name: 'Go there' })).toHaveAttribute(
            'href',
            '/investor/wallet',
        );
        expect(
            screen.getByText(
                'Staff accounts are closed by an administrator, not from settings.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', {
                name: 'Permanently delete account',
            }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Keep my account' }),
        ).toBeInTheDocument();
    });
});

describe('DeleteAccountRow', () => {
    it('opens the Close account dialog on the profile settings', () => {
        render(<DeleteAccountRow />);

        expect(screen.getByText('DANGER ZONE')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Delete account/ }),
        ).toHaveAttribute('href', '/settings/profile#close-account');
    });
});
