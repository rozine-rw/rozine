// eslint-disable-next-line testing-library/no-manual-cleanup
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import type { ComponentProps, ReactElement, ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import DeleteUser from '@/components/delete-user';

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
    DialogTitle: ({ children }: { children: ReactNode }) => <h3>{children}</h3>,
    DialogTrigger: ({ children }: { children: ReactNode }) => <>{children}</>,
}));

vi.mock('@/components/ui/label', () => ({
    Label: (props: ComponentProps<'label'>) => <label {...props} />,
}));

beforeEach(() => {
    form.props.length = 0;
    form.state.processing = false;
    form.state.errors = {};
});

afterEach(() => {
    cleanup();
});

describe('DeleteUser', () => {
    it('resets cancellation state and focuses password after an error', () => {
        form.state.errors = { password: 'Password is incorrect.' };
        const view = render(<DeleteUser />);

        expect(
            screen.getByRole('heading', { name: 'Delete account' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Password is incorrect.')).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(form.state.resetAndClearErrors).toHaveBeenCalledOnce();

        const onError = form.props.at(-1)?.onError;
        onError?.();
        expect(screen.getByPlaceholderText('Password')).toHaveFocus();

        view.unmount();
        expect(() => onError?.()).not.toThrow();
    });

    it('disables destructive confirmation while processing', () => {
        form.state.processing = true;
        render(<DeleteUser />);

        expect(
            screen.getAllByRole('button', { name: 'Delete account' })[1],
        ).toBeDisabled();
    });
});
