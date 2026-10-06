// eslint-disable-next-line testing-library/no-manual-cleanup
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import ManagePasskeys from '@/components/manage-passkeys';

const router = vi.hoisted(() => ({
    delete: vi.fn(),
    reload: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    router,
}));

vi.mock('@/components/heading', () => ({
    default: ({ title }: { title: string }) => <h2>{title}</h2>,
}));

vi.mock('@/components/passkey-item', () => ({
    default: ({
        passkey,
        onDelete,
    }: {
        passkey: { id: number; name: string };
        onDelete: (id: number, onError: () => void) => void;
    }) => (
        <button type="button" onClick={() => onDelete(passkey.id, vi.fn())}>
            Delete {passkey.name}
        </button>
    ),
}));

vi.mock('@/components/passkey-register', () => ({
    default: ({ onSuccess }: { onSuccess: () => void }) => (
        <button type="button" onClick={onSuccess}>
            Complete registration
        </button>
    ),
}));

beforeEach(() => {
    router.delete.mockImplementation(
        (
            _url: string,
            options: {
                onError: () => void;
            },
        ) => options.onError(),
    );
});

afterEach(() => {
    cleanup();
});

describe('ManagePasskeys', () => {
    it('stays hidden unless passkey management is allowed', () => {
        const { container, rerender } = render(<ManagePasskeys />);

        expect(container).toBeEmptyDOMElement();

        rerender(<ManagePasskeys canManagePasskeys={false} />);
        expect(container).toBeEmptyDOMElement();
    });

    it('renders the empty state and reloads after registration', () => {
        render(<ManagePasskeys canManagePasskeys />);

        expect(screen.getByText('No passkeys yet')).toBeInTheDocument();
        fireEvent.click(
            screen.getByRole('button', { name: 'Complete registration' }),
        );
        expect(router.reload).toHaveBeenCalledOnce();
    });

    it('renders passkeys and deletes through Inertia with error recovery', () => {
        render(
            <ManagePasskeys
                canManagePasskeys
                passkeys={[
                    {
                        id: 7,
                        name: 'Work laptop',
                        authenticator: 'platform',
                        created_at_diff: 'today',
                        last_used_at_diff: 'today',
                    },
                    {
                        id: 8,
                        name: 'Phone',
                        authenticator: null,
                        created_at_diff: 'yesterday',
                        last_used_at_diff: null,
                    },
                ]}
            />,
        );

        fireEvent.click(
            screen.getByRole('button', { name: 'Delete Work laptop' }),
        );

        expect(router.delete).toHaveBeenCalledWith(
            '/user/passkeys/7',
            expect.objectContaining({
                preserveScroll: true,
                onError: expect.any(Function),
            }),
        );
        expect(screen.queryByText('No passkeys yet')).not.toBeInTheDocument();
    });
});
