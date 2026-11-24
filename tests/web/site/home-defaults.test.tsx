import { act, render, screen } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import Home from '@/pages/home';

vi.mock('@inertiajs/react', () => import('./landing-inertia'));

const h1 = () => screen.getByRole('heading', { level: 1 });

beforeEach(() => {
    vi.spyOn(window, 'scrollTo').mockImplementation(() => undefined);
});

afterEach(() => {
    window.history.replaceState(null, '', '/');
    vi.restoreAllMocks();
});

describe('the landing page address', () => {
    it('opens the view a shared link names', () => {
        window.history.replaceState(null, '', '/#how-it-works');
        render(<Home />);

        expect(h1()).toHaveTextContent(
            'Your money sits in a separate bank account',
        );
    });

    it("opens Invest for the earlier site's investor link", () => {
        window.history.replaceState(null, '', '/#investors');
        render(<Home />);

        expect(h1()).toHaveTextContent('Earn up to 15%');
    });

    it('follows back and forward between views', () => {
        render(<Home />);

        act(() => {
            window.history.replaceState(null, '', '/#help');
            window.dispatchEvent(new HashChangeEvent('hashchange'));
        });
        expect(h1()).toHaveTextContent('Every question, answered plainly.');

        act(() => {
            window.history.replaceState(null, '', '/');
            window.dispatchEvent(new PopStateEvent('popstate'));
        });
        expect(h1()).toHaveTextContent('Earn up to 15%');
    });

    it('stays on the current view when the waitlist anchor is followed', () => {
        window.history.replaceState(null, '', '/#businesses');
        render(<Home />);

        act(() => {
            window.history.replaceState(null, '', '/#join');
            window.dispatchEvent(new HashChangeEvent('hashchange'));
        });
        expect(h1()).toHaveTextContent('Raise up to RWF 100M');
    });

    it('stops listening once the page is left', () => {
        const remove = vi.spyOn(window, 'removeEventListener');
        const { unmount } = render(<Home />);

        unmount();

        expect(remove).toHaveBeenCalledWith('hashchange', expect.any(Function));
        expect(remove).toHaveBeenCalledWith('popstate', expect.any(Function));
    });
});
