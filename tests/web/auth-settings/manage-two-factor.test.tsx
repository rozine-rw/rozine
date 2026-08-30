/* eslint-disable testing-library/no-manual-cleanup */
import {
    act,
    cleanup,
    fireEvent,
    render,
    screen,
} from '@testing-library/react';
import type { ComponentProps, ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import ManageTwoFactor from '@/components/manage-two-factor';

type FormProps = {
    children: (state: { processing: boolean }) => ReactNode;
    onSuccess?: () => void;
    [key: string]: unknown;
};

const doubles = vi.hoisted(() => ({
    forms: [] as FormProps[],
    processing: false,
    auth: {
        qrCodeSvg: null as string | null,
        hasSetupData: false,
        manualSetupKey: null as string | null,
        clearSetupData: vi.fn(),
        clearTwoFactorAuthData: vi.fn(),
        fetchSetupData: vi.fn().mockResolvedValue(undefined),
        recoveryCodesList: [] as string[],
        fetchRecoveryCodes: vi.fn().mockResolvedValue(undefined),
        errors: [] as string[],
    },
}));

vi.mock('@inertiajs/react', () => ({
    Form: (props: FormProps) => {
        doubles.forms.push(props);

        return (
            <form>{props.children({ processing: doubles.processing })}</form>
        );
    },
}));

vi.mock('@/components/heading', () => ({
    default: ({ title }: { title: string }) => <h2>{title}</h2>,
}));

vi.mock('@/components/two-factor-recovery-codes', () => ({
    default: ({ recoveryCodesList }: { recoveryCodesList: string[] }) => (
        <div>Recovery codes: {recoveryCodesList.length}</div>
    ),
}));

vi.mock('@/components/two-factor-setup-modal', () => ({
    default: ({
        isOpen,
        onClose,
        requiresConfirmation,
        twoFactorEnabled,
    }: {
        isOpen: boolean;
        onClose: () => void;
        requiresConfirmation: boolean;
        twoFactorEnabled: boolean;
    }) => (
        <div>
            Modal open: {String(isOpen)} / confirm:{' '}
            {String(requiresConfirmation)} / enabled: {String(twoFactorEnabled)}
            <button type="button" onClick={onClose}>
                Close setup modal
            </button>
        </div>
    ),
}));

vi.mock('@/components/ui/button', () => ({
    Button: (props: ComponentProps<'button'>) => <button {...props} />,
}));

vi.mock('@/hooks/use-two-factor-auth', () => ({
    useTwoFactorAuth: () => doubles.auth,
}));

beforeEach(() => {
    doubles.forms.length = 0;
    doubles.processing = false;
    doubles.auth.hasSetupData = false;
    doubles.auth.recoveryCodesList = [];
});

afterEach(() => {
    cleanup();
});

describe('ManageTwoFactor', () => {
    it('stays hidden unless two-factor management is allowed', () => {
        const { container, rerender } = render(<ManageTwoFactor />);

        expect(container).toBeEmptyDOMElement();
        rerender(<ManageTwoFactor canManageTwoFactor={false} />);
        expect(container).toBeEmptyDOMElement();
    });

    it('shows enabled state, disabled processing, and clears on disable', () => {
        doubles.processing = true;
        doubles.auth.recoveryCodesList = ['recovery'];
        const { rerender } = render(
            <ManageTwoFactor
                canManageTwoFactor
                requiresConfirmation
                twoFactorEnabled
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Disable 2FA' }),
        ).toBeDisabled();
        expect(screen.getByText('Recovery codes: 1')).toBeInTheDocument();

        doubles.processing = false;
        rerender(
            <ManageTwoFactor
                canManageTwoFactor
                requiresConfirmation={false}
                twoFactorEnabled={false}
            />,
        );
        expect(doubles.auth.clearTwoFactorAuthData).toHaveBeenCalledOnce();
    });

    it('opens and closes setup after enabling two factor', () => {
        render(<ManageTwoFactor canManageTwoFactor />);

        expect(screen.getByText(/Modal open: false/)).toBeInTheDocument();
        act(() => doubles.forms.at(-1)?.onSuccess?.());
        expect(screen.getByText(/Modal open: true/)).toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('button', { name: 'Close setup modal' }),
        );
        expect(screen.getByText(/Modal open: false/)).toBeInTheDocument();
    });

    it('continues an existing setup session', () => {
        doubles.auth.hasSetupData = true;
        render(
            <ManageTwoFactor
                canManageTwoFactor
                requiresConfirmation
                twoFactorEnabled={false}
            />,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Continue setup' }));
        expect(screen.getByText(/Modal open: true/)).toBeInTheDocument();
        expect(screen.getByText(/confirm: true/)).toBeInTheDocument();
    });
});
