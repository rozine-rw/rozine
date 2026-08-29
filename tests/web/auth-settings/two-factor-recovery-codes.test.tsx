/* eslint-disable testing-library/no-manual-cleanup */
import {
    act,
    cleanup,
    fireEvent,
    render,
    screen,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps, ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';

type FormProps = {
    children: (state: { processing: boolean }) => ReactNode;
    onSuccess?: () => void;
};

const form = vi.hoisted(() => ({
    props: [] as FormProps[],
    processing: false,
}));

vi.mock('@inertiajs/react', () => ({
    Form: (props: FormProps) => {
        form.props.push(props);

        return <form>{props.children({ processing: form.processing })}</form>;
    },
}));

vi.mock('@/components/alert-error', () => ({
    default: ({ errors }: { errors: string[] }) => (
        <div role="alert">{errors.join(', ')}</div>
    ),
}));

vi.mock('@/components/ui/button', () => ({
    Button: (props: ComponentProps<'button'>) => <button {...props} />,
}));

vi.mock('@/components/ui/card', () => ({
    Card: ({ children }: { children: ReactNode }) => (
        <section>{children}</section>
    ),
    CardContent: ({ children }: { children: ReactNode }) => (
        <div>{children}</div>
    ),
    CardDescription: ({ children }: { children: ReactNode }) => (
        <p>{children}</p>
    ),
    CardHeader: ({ children }: { children: ReactNode }) => (
        <header>{children}</header>
    ),
    CardTitle: ({ children }: { children: ReactNode }) => <h2>{children}</h2>,
}));

beforeEach(() => {
    form.props.length = 0;
    form.processing = false;
    Object.defineProperty(Element.prototype, 'scrollIntoView', {
        configurable: true,
        value: vi.fn(),
    });
});

afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
});

describe('TwoFactorRecoveryCodes', () => {
    it('loads, reveals, scrolls, and hides initially empty codes', async () => {
        const user = userEvent.setup();
        const fetchRecoveryCodes = vi.fn().mockResolvedValue(undefined);
        render(
            <TwoFactorRecoveryCodes
                recoveryCodesList={[]}
                fetchRecoveryCodes={fetchRecoveryCodes}
                errors={[]}
            />,
        );

        expect(fetchRecoveryCodes).toHaveBeenCalledOnce();
        await user.click(
            screen.getByRole('button', {
                name: 'View recovery codes',
            }),
        );
        expect(fetchRecoveryCodes).toHaveBeenCalledTimes(2);
        expect(
            await screen.findByRole('button', {
                name: 'Hide recovery codes',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByLabelText('Loading recovery codes'),
        ).toBeInTheDocument();

        expect(Element.prototype.scrollIntoView).toHaveBeenCalled();

        fireEvent.click(
            screen.getByRole('button', { name: 'Hide recovery codes' }),
        );
        expect(
            screen.getByRole('button', { name: 'View recovery codes' }),
        ).toHaveAttribute('aria-expanded', 'false');
    });

    it('shows existing codes and regenerates them in both processing states', async () => {
        const fetchRecoveryCodes = vi.fn().mockResolvedValue(undefined);
        render(
            <TwoFactorRecoveryCodes
                recoveryCodesList={['alpha', 'beta']}
                fetchRecoveryCodes={fetchRecoveryCodes}
                errors={[]}
            />,
        );

        expect(fetchRecoveryCodes).not.toHaveBeenCalled();
        fireEvent.click(
            screen.getByRole('button', { name: 'View recovery codes' }),
        );
        expect(screen.getAllByRole('listitem')).toHaveLength(2);
        expect(
            screen.getByRole('button', { name: /Regenerate codes/ }),
        ).toBeEnabled();
        await act(async () => form.props.at(-1)?.onSuccess?.());
        expect(fetchRecoveryCodes).toHaveBeenCalledOnce();

        cleanup();
        form.processing = true;
        render(
            <TwoFactorRecoveryCodes
                key="processing"
                recoveryCodesList={['alpha']}
                fetchRecoveryCodes={fetchRecoveryCodes}
                errors={[]}
            />,
        );
        fireEvent.click(
            screen.getByRole('button', { name: 'View recovery codes' }),
        );
        expect(
            screen.getByRole('button', { name: /Regenerate codes/ }),
        ).toBeDisabled();
    });

    it('shows loading errors and safely skips scrolling without a code list', () => {
        const fetchRecoveryCodes = vi.fn().mockResolvedValue(undefined);
        render(
            <TwoFactorRecoveryCodes
                recoveryCodesList={['alpha']}
                fetchRecoveryCodes={fetchRecoveryCodes}
                errors={['Codes unavailable.']}
            />,
        );

        fireEvent.click(
            screen.getByRole('button', { name: 'View recovery codes' }),
        );
        expect(screen.getByRole('alert')).toHaveTextContent(
            'Codes unavailable.',
        );
    });
});
