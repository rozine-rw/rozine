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
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';

type ConfirmationErrors = {
    confirmTwoFactorAuthentication?: {
        code?: string;
    };
};

type FormProps = {
    children: (state: {
        processing: boolean;
        errors?: ConfirmationErrors;
    }) => ReactNode;
    onSuccess?: () => void;
};

const doubles = vi.hoisted(() => ({
    forms: [] as FormProps[],
    formState: {
        processing: false,
        errors: undefined as ConfirmationErrors | undefined,
    },
    appearance: 'light' as 'light' | 'dark',
    copiedText: null as string | null,
    copy: vi.fn().mockResolvedValue(true),
    showOtpInput: true,
}));

vi.mock('@inertiajs/react', () => ({
    Form: (props: FormProps) => {
        doubles.forms.push(props);

        return <form>{props.children(doubles.formState)}</form>;
    },
}));

vi.mock('lucide-react', () => ({
    Check: () => <span>Copied</span>,
    Copy: () => <span>Copy</span>,
    ScanLine: () => <span>Scan</span>,
}));

vi.mock('@/components/alert-error', () => ({
    default: ({ errors }: { errors: string[] }) => (
        <div role="alert">{errors.join(', ')}</div>
    ),
}));

vi.mock('@/components/input-error', () => ({
    default: ({ message }: { message?: string }) =>
        message ? <p>{message}</p> : null,
}));

vi.mock('@/components/ui/button', () => ({
    Button: (props: ComponentProps<'button'>) => <button {...props} />,
}));

vi.mock('@/components/ui/dialog', () => ({
    Dialog: ({
        children,
        onOpenChange,
    }: {
        children: ReactNode;
        onOpenChange: (open: boolean) => void;
    }) => (
        <div>
            <button type="button" onClick={() => onOpenChange(true)}>
                Keep dialog open
            </button>
            <button type="button" onClick={() => onOpenChange(false)}>
                Dismiss dialog
            </button>
            {children}
        </div>
    ),
    DialogContent: ({ children }: { children: ReactNode }) => (
        <div>{children}</div>
    ),
    DialogDescription: ({ children }: { children: ReactNode }) => (
        <p>{children}</p>
    ),
    DialogHeader: ({ children }: { children: ReactNode }) => (
        <header>{children}</header>
    ),
    DialogTitle: ({ children }: { children: ReactNode }) => <h2>{children}</h2>,
}));

vi.mock('@/components/ui/input-otp', () => ({
    InputOTP: ({
        children,
        onChange,
        ...props
    }: Omit<ComponentProps<'input'>, 'onChange'> & {
        children?: ReactNode;
        onChange?: (value: string) => void;
    }) => (
        <div>
            {doubles.showOtpInput && (
                <input
                    {...props}
                    aria-label="Authentication code"
                    onChange={(event) => onChange?.(event.target.value)}
                />
            )}
            {children}
        </div>
    ),
    InputOTPGroup: ({ children }: { children: ReactNode }) => (
        <div>{children}</div>
    ),
    InputOTPSlot: ({ index }: { index: number }) => <span>{index}</span>,
}));

vi.mock('@/components/ui/spinner', () => ({
    Spinner: () => <span data-testid="spinner">Loading</span>,
}));

vi.mock('@/hooks/use-appearance', () => ({
    useAppearance: () => ({
        resolvedAppearance: doubles.appearance,
    }),
}));

vi.mock('@/hooks/use-clipboard', () => ({
    useClipboard: () => [doubles.copiedText, doubles.copy],
}));

beforeEach(() => {
    doubles.forms.length = 0;
    doubles.formState.processing = false;
    doubles.formState.errors = undefined;
    doubles.appearance = 'light';
    doubles.copiedText = null;
    doubles.showOtpInput = true;
});

afterEach(() => {
    cleanup();
});

function baseProps() {
    return {
        isOpen: false,
        onClose: vi.fn(),
        requiresConfirmation: false,
        twoFactorEnabled: false,
        qrCodeSvg: null,
        manualSetupKey: null,
        clearSetupData: vi.fn(),
        fetchSetupData: vi.fn().mockResolvedValue(undefined),
        errors: [] as string[],
    };
}

describe('TwoFactorSetupModal', () => {
    it('loads setup data only when an open modal is missing its QR code', () => {
        const firstFetch = vi.fn().mockResolvedValue(undefined);
        const nextFetch = vi.fn().mockResolvedValue(undefined);
        const props = {
            ...baseProps(),
            fetchSetupData: firstFetch,
        };
        const { rerender } = render(<TwoFactorSetupModal {...props} />);

        expect(firstFetch).not.toHaveBeenCalled();
        expect(
            screen.getByRole('heading', {
                name: 'Enable two-factor authentication',
            }),
        ).toBeInTheDocument();
        expect(screen.getAllByTestId('spinner')).toHaveLength(2);

        rerender(
            <TwoFactorSetupModal
                {...props}
                isOpen
                fetchSetupData={nextFetch}
            />,
        );
        expect(nextFetch).toHaveBeenCalledOnce();

        rerender(
            <TwoFactorSetupModal
                {...props}
                isOpen
                fetchSetupData={nextFetch}
                qrCodeSvg={'<svg data-testid="qr" />'}
            />,
        );
        expect(nextFetch).toHaveBeenCalledOnce();
    });

    it('closes setup without confirmation and handles dialog open changes', () => {
        const props = baseProps();
        render(<TwoFactorSetupModal {...props} isOpen />);

        fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
        expect(props.clearSetupData).toHaveBeenCalledOnce();
        expect(props.onClose).toHaveBeenCalledOnce();

        fireEvent.click(
            screen.getByRole('button', { name: 'Keep dialog open' }),
        );
        expect(props.onClose).toHaveBeenCalledOnce();

        fireEvent.click(screen.getByRole('button', { name: 'Dismiss dialog' }));
        expect(props.onClose).toHaveBeenCalledTimes(2);
    });

    it('renders errors instead of setup controls', () => {
        const props = baseProps();
        render(
            <TwoFactorSetupModal
                {...props}
                isOpen
                errors={['Unable to load setup.']}
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Unable to load setup.',
        );
        expect(
            screen.queryByRole('button', { name: 'Continue' }),
        ).not.toBeInTheDocument();
    });

    it('renders and copies manual setup data in light and dark modes', () => {
        doubles.appearance = 'dark';
        const props = {
            ...baseProps(),
            isOpen: true,
            qrCodeSvg: '<svg data-testid="qr-code"></svg>',
            manualSetupKey: 'MANUAL-KEY',
        };
        const { rerender } = render(<TwoFactorSetupModal {...props} />);

        expect(screen.getByTestId('qr-code')).toBeInTheDocument();
        expect(screen.getByDisplayValue('MANUAL-KEY')).toHaveAttribute(
            'readonly',
        );
        fireEvent.click(screen.getByRole('button', { name: 'Copy' }));
        expect(doubles.copy).toHaveBeenCalledWith('MANUAL-KEY');

        doubles.appearance = 'light';
        doubles.copiedText = 'MANUAL-KEY';
        rerender(<TwoFactorSetupModal {...props} />);

        expect(
            screen.getByRole('button', { name: 'Copied' }),
        ).toBeInTheDocument();
        expect(screen.getByTestId('qr-code')).toBeInTheDocument();
    });

    it('verifies a complete code, shows errors, and returns to setup', async () => {
        doubles.formState.errors = {
            confirmTwoFactorAuthentication: {
                code: 'Code is invalid.',
            },
        };
        const props = {
            ...baseProps(),
            isOpen: true,
            requiresConfirmation: true,
            qrCodeSvg: '<svg></svg>',
            manualSetupKey: 'KEY',
        };
        render(<TwoFactorSetupModal {...props} />);

        fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
        expect(
            screen.getByRole('heading', {
                name: 'Verify authentication code',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('Code is invalid.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Confirm' })).toBeDisabled();
        await waitFor(() =>
            expect(screen.getByLabelText('Authentication code')).toHaveFocus(),
        );

        fireEvent.change(screen.getByLabelText('Authentication code'), {
            target: { value: '123456' },
        });
        expect(screen.getByRole('button', { name: 'Confirm' })).toBeEnabled();

        fireEvent.click(screen.getByRole('button', { name: 'Back' }));
        expect(
            screen.getByRole('heading', {
                name: 'Enable two-factor authentication',
            }),
        ).toBeInTheDocument();
    });

    it('disables verification while processing and closes on success', () => {
        doubles.formState.processing = true;
        doubles.formState.errors = {};
        const props = {
            ...baseProps(),
            isOpen: true,
            requiresConfirmation: true,
            qrCodeSvg: '<svg></svg>',
            manualSetupKey: 'KEY',
        };
        render(<TwoFactorSetupModal {...props} />);
        fireEvent.click(screen.getByRole('button', { name: 'Continue' }));

        expect(screen.getByRole('button', { name: 'Back' })).toBeDisabled();
        expect(screen.getByRole('button', { name: 'Confirm' })).toBeDisabled();

        act(() => doubles.forms.at(-1)?.onSuccess?.());
        expect(props.onClose).toHaveBeenCalledOnce();
    });

    it('shows enabled configuration and clears setup data when closed', () => {
        const props = {
            ...baseProps(),
            isOpen: true,
            twoFactorEnabled: true,
            qrCodeSvg: '<svg></svg>',
            manualSetupKey: 'KEY',
        };
        render(<TwoFactorSetupModal {...props} />);

        expect(
            screen.getByRole('heading', {
                name: 'Two-factor authentication enabled',
            }),
        ).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Close' }));

        expect(props.clearSetupData).toHaveBeenCalledTimes(2);
        expect(props.onClose).toHaveBeenCalledOnce();
    });

    it('safely handles verification focus when no OTP input remains', async () => {
        doubles.showOtpInput = false;
        const props = {
            ...baseProps(),
            isOpen: true,
            requiresConfirmation: true,
            qrCodeSvg: '<svg></svg>',
            manualSetupKey: 'KEY',
        };
        const view = render(<TwoFactorSetupModal {...props} />);

        fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
        view.unmount();

        await new Promise((resolve) => setTimeout(resolve, 0));
    });
});
