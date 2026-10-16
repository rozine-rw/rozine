import { render, screen, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { AppEntry } from '@/components/site/app-entry';
import Home from '@/pages/home';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    router: { post: vi.fn() },
}));

describe('Site app entry', () => {
    it('stays hidden on the live site', () => {
        const { container } = render(
            <AppEntry environment={null} signedIn={false} />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('offers sign-in and account creation to a signed-out visitor on staging', () => {
        render(<AppEntry environment="uat" signedIn={false} />);

        const group = screen.getByRole('group', { name: 'Rozine apps' });

        expect(
            within(group).getByRole('link', { name: 'Sign in' }),
        ).toHaveAttribute('href', '/login');
        expect(
            within(group).getByRole('link', { name: 'Create account' }),
        ).toHaveAttribute('href', '/register');
        expect(
            within(group).queryByRole('link', { name: 'Open the app' }),
        ).not.toBeInTheDocument();
    });

    it('takes a signed-in visitor straight to the launcher', () => {
        render(<AppEntry environment="demo" signedIn />);

        expect(
            screen.getByRole('link', { name: 'Open the app' }),
        ).toHaveAttribute('href', '/dashboard');
        expect(
            screen.queryByRole('link', { name: 'Sign in' }),
        ).not.toBeInTheDocument();
    });

    it('is placed in the site header from the shared page props', () => {
        const { unmount } = render(
            <Home nonLiveEnvironment="uat" auth={{ user: null }} />,
        );

        expect(
            within(screen.getByRole('banner')).getByRole('link', {
                name: 'Sign in',
            }),
        ).toHaveAttribute('href', '/login');
        unmount();

        render(<Home nonLiveEnvironment="uat" auth={{ user: { id: 1 } }} />);
        expect(
            within(screen.getByRole('banner')).getByRole('link', {
                name: 'Open the app',
            }),
        ).toHaveAttribute('href', '/dashboard');
    });

    it('leaves the live header without an app entry', () => {
        render(<Home nonLiveEnvironment={null} auth={{ user: null }} />);

        expect(
            screen.queryByRole('group', { name: 'Rozine apps' }),
        ).not.toBeInTheDocument();
    });
});
