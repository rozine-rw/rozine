import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { ReadFailureNotice } from '@/components/rozine/read-failure-notice';

type Handler = (event: {
    detail: { response: { status: number } };
    preventDefault: () => void;
}) => void;

const handlers: Record<string, Handler> = {};
const off = vi.fn();
const reload = vi.fn();

vi.mock('@inertiajs/react', () => ({
    router: {
        on: (name: string, handler: Handler) => {
            handlers[name] = handler;

            return off;
        },
        reload: () => reload(),
    },
}));

const fire = (name: string, status = 0) => {
    const preventDefault = vi.fn();

    act(() => {
        handlers[name]({ detail: { response: { status } }, preventDefault });
    });

    return preventDefault;
};

beforeEach(() => {
    reload.mockClear();
    off.mockClear();
});

describe('The read failure notice', () => {
    it('replaces the raw error modal for a server error, and re-reads on Try again', async () => {
        const user = userEvent.setup();

        render(<ReadFailureNotice />);
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();

        expect(fire('httpException', 503)).toHaveBeenCalled();
        expect(screen.getByRole('alert')).toHaveTextContent(
            "Rozine couldn't load this just now. Anything you already sent is unaffected.",
        );

        await user.click(screen.getByRole('button', { name: 'Try again' }));
        expect(reload).toHaveBeenCalledTimes(1);
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('leaves client errors to their own pages', () => {
        render(<ReadFailureNotice />);

        expect(fire('httpException', 404)).not.toHaveBeenCalled();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('says when Rozine could not be reached, and clears once a page loads', () => {
        render(<ReadFailureNotice />);

        expect(fire('networkError')).toHaveBeenCalled();
        expect(screen.getByRole('alert')).toHaveTextContent(
            "Couldn't reach Rozine. Check your connection, then try again.",
        );

        fire('success');
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('stops listening when it unmounts', () => {
        const { unmount } = render(<ReadFailureNotice />);

        unmount();
        expect(off).toHaveBeenCalledTimes(3);
    });
});
