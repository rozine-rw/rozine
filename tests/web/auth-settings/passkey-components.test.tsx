/* eslint-disable testing-library/no-manual-cleanup */
import {
    act,
    cleanup,
    fireEvent,
    render,
    screen,
    waitFor,
} from '@testing-library/react';
import type { ComponentProps, ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import PasskeyItem from '@/components/passkey-item';
import PasskeyRegistration from '@/components/passkey-register';
import PasskeyVerify from '@/components/passkey-verify';

type RegisterOptions = {
    onSuccess: () => void;
};

type VerifyOptions = {
    routes?: {
        options: string;
        submit: string;
    };
    onSuccess: (response: { redirect?: string }) => void;
};

const doubles = vi.hoisted(() => ({
    register: vi.fn(),
    registerOptions: undefined as RegisterOptions | undefined,
    registerState: {
        isLoading: false,
        error: null as string | null,
        isSupported: true,
    },
    verify: vi.fn(),
    verifyOptions: undefined as VerifyOptions | undefined,
    verifyState: {
        isLoading: false,
        error: null as string | null,
        isSupported: true,
    },
    visit: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    router: {
        visit: doubles.visit,
    },
}));

vi.mock('@laravel/passkeys/react', () => ({
    usePasskeyRegister: (options: RegisterOptions) => {
        doubles.registerOptions = options;

        return {
            register: doubles.register,
            ...doubles.registerState,
        };
    },
    usePasskeyVerify: (options: VerifyOptions) => {
        doubles.verifyOptions = options;

        return {
            verify: doubles.verify,
            ...doubles.verifyState,
        };
    },
}));

vi.mock('@/components/input-error', () => ({
    default: ({ message }: { message?: string }) =>
        message ? <p>{message}</p> : null,
}));

vi.mock('@/components/ui/button', () => ({
    Button: (props: ComponentProps<'button'>) => <button {...props} />,
}));

vi.mock('@/components/ui/dialog', () => ({
    Dialog: ({ children }: { children: ReactNode }) => <div>{children}</div>,
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
    DialogTitle: ({ children }: { children: ReactNode }) => <h2>{children}</h2>,
    DialogTrigger: ({ children }: { children: ReactNode }) => <>{children}</>,
}));

vi.mock('@/components/ui/input', () => ({
    Input: (props: ComponentProps<'input'>) => <input {...props} />,
}));

vi.mock('@/components/ui/label', () => ({
    Label: (props: ComponentProps<'label'>) => <label {...props} />,
}));

vi.mock('@/components/ui/separator', () => ({
    Separator: () => <hr />,
}));

vi.mock('@/components/ui/spinner', () => ({
    Spinner: () => <span data-testid="spinner">Loading</span>,
}));

beforeEach(() => {
    doubles.registerState.isLoading = false;
    doubles.registerState.error = null;
    doubles.registerState.isSupported = true;
    doubles.verifyState.isLoading = false;
    doubles.verifyState.error = null;
    doubles.verifyState.isSupported = true;
});

afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
});

describe('PasskeyItem', () => {
    it('shows metadata and recovers when passkey deletion fails', () => {
        let recover: (() => void) | undefined;
        const onDelete = vi.fn((_id: number, onError: () => void) => {
            recover = onError;
        });
        render(
            <PasskeyItem
                passkey={{
                    id: 4,
                    name: 'Office laptop',
                    authenticator: 'platform',
                    created_at_diff: 'today',
                    last_used_at_diff: 'an hour ago',
                }}
                onDelete={onDelete}
            />,
        );

        expect(screen.getByText('platform')).toBeInTheDocument();
        expect(screen.getByText(/Last used an hour ago/)).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Remove passkey' }));
        expect(onDelete).toHaveBeenCalledWith(4, expect.any(Function));
        expect(
            screen.getByRole('button', { name: 'Removing...' }),
        ).toBeDisabled();

        act(() => recover?.());
        expect(
            screen.getByRole('button', { name: 'Remove passkey' }),
        ).toBeEnabled();
    });

    it('omits optional authenticator and last-used metadata', () => {
        render(
            <PasskeyItem
                passkey={{
                    id: 5,
                    name: 'Security key',
                    authenticator: null,
                    created_at_diff: 'yesterday',
                    last_used_at_diff: null,
                }}
                onDelete={vi.fn()}
            />,
        );

        expect(screen.queryByText('platform')).not.toBeInTheDocument();
        expect(screen.queryByText(/Last used/)).not.toBeInTheDocument();
    });
});

describe('PasskeyRegistration', () => {
    it.each([
        ['Edg Windows', 'Edge on Windows'],
        ['OPR Android', 'Opera on Android'],
        ['FxiOS iPhone', 'Firefox on iPhone'],
        ['CriOS iPad', 'Chrome on iPad'],
        ['Safari Mac', 'Safari on Mac'],
        ['Unknown Linux', ''],
    ])('derives a device name from %s as %s', (userAgent, expected) => {
        vi.spyOn(window.navigator, 'userAgent', 'get').mockReturnValue(
            userAgent,
        );
        doubles.registerState.isSupported = true;
        const { unmount } = render(<PasskeyRegistration onSuccess={vi.fn()} />);

        fireEvent.click(screen.getByRole('button', { name: 'Add passkey' }));
        expect(screen.getByLabelText('Passkey name')).toHaveValue(expected);
        unmount();
    });

    it('reports unsupported browsers', () => {
        doubles.registerState.isSupported = false;
        render(<PasskeyRegistration onSuccess={vi.fn()} />);

        expect(
            screen.getByText('Passkeys are not supported in this browser.'),
        ).toBeInTheDocument();
    });

    it('validates, registers, cancels, and handles successful registration', async () => {
        vi.spyOn(window.navigator, 'userAgent', 'get').mockReturnValue(
            'Chrome Mac',
        );
        doubles.register.mockResolvedValue(undefined);
        const onSuccess = vi.fn();
        const { rerender } = render(
            <PasskeyRegistration onSuccess={onSuccess} />,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Add passkey' }));
        const name = screen.getByLabelText('Passkey name');
        expect(name).toHaveValue('Chrome on Mac');

        fireEvent.change(name, { target: { value: '   ' } });
        fireEvent.submit(name);
        expect(doubles.register).not.toHaveBeenCalled();

        fireEvent.change(name, { target: { value: 'Work Mac' } });
        fireEvent.submit(name);
        await waitFor(() =>
            expect(doubles.register).toHaveBeenCalledWith('Work Mac'),
        );

        doubles.registerState.error = 'Registration failed.';
        doubles.registerState.isLoading = true;
        rerender(<PasskeyRegistration key="loading" onSuccess={onSuccess} />);
        fireEvent.click(screen.getByRole('button', { name: 'Add passkey' }));
        expect(screen.getByText('Registration failed.')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Registering...' }),
        ).toBeDisabled();

        fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(
            screen.getByRole('button', { name: 'Add passkey' }),
        ).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Add passkey' }));
        act(() => doubles.registerOptions?.onSuccess());
        expect(onSuccess).toHaveBeenCalledOnce();
        expect(
            screen.getByRole('button', { name: 'Add passkey' }),
        ).toBeInTheDocument();
    });
});

describe('PasskeyVerify', () => {
    it('renders nothing for unsupported browsers', () => {
        doubles.verifyState.isSupported = false;
        const { container } = render(<PasskeyVerify />);

        expect(container).toBeEmptyDOMElement();
    });

    it('verifies with default labels and fallback redirect', () => {
        render(<PasskeyVerify />);

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Sign in with a passkey',
            }),
        );
        expect(doubles.verify).toHaveBeenCalledOnce();
        expect(screen.getByText('Or continue with email')).toBeInTheDocument();
        expect(doubles.verifyOptions?.routes).toBeUndefined();

        act(() => doubles.verifyOptions?.onSuccess({}));
        expect(doubles.visit).toHaveBeenCalledWith('/dashboard');
    });

    it('uses custom routes, loading copy, errors, and redirect', () => {
        doubles.verifyState.isLoading = true;
        doubles.verifyState.error = 'Passkey rejected.';
        render(
            <PasskeyVerify
                routes={{
                    options: { url: '/passkey/options', method: 'get' },
                    submit: { url: '/passkey/submit', method: 'post' },
                }}
                label="Confirm with passkey"
                loadingLabel="Confirming..."
                separator="Or use a password"
            />,
        );

        expect(
            screen.getByRole('button', { name: /Confirming/ }),
        ).toBeDisabled();
        expect(screen.getByTestId('spinner')).toBeInTheDocument();
        expect(screen.getByText('Passkey rejected.')).toBeInTheDocument();
        expect(screen.getByText('Or use a password')).toBeInTheDocument();
        expect(doubles.verifyOptions?.routes).toEqual({
            options: '/passkey/options',
            submit: '/passkey/submit',
        });

        act(() => doubles.verifyOptions?.onSuccess({ redirect: '/settings' }));
        expect(doubles.visit).toHaveBeenCalledWith('/settings');
    });

    it('uses the default loading copy', () => {
        doubles.verifyState.isLoading = true;
        render(<PasskeyVerify />);

        expect(
            screen.getByRole('button', { name: /Authenticating/ }),
        ).toBeDisabled();
    });
});
