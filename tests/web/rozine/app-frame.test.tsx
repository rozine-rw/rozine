import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { AppFrame } from '@/components/rozine/app-frame';

vi.mock('@inertiajs/react', () => ({
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
}));

const link = (url: string) => ({ url, method: 'get' as const });

describe('AppFrame', () => {
    it('drops the phone tab bar on detail screens but keeps the sidebar', () => {
        render(
            <AppFrame
                audience="investor"
                nav={[
                    {
                        key: 'deals',
                        label: 'Deals',
                        href: link('/deals'),
                        glyph: <svg />,
                    },
                ]}
                active="deals"
                launcher={link('/launcher')}
                showTabBar={false}
            >
                <p>Deal detail</p>
            </AppFrame>,
        );

        expect(screen.getAllByRole('navigation')).toHaveLength(1);
        expect(screen.getByText('Deal detail')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Deals' })).toHaveAttribute(
            'aria-current',
            'page',
        );
    });

    it('starts with a link that skips past the navigation to the page', async () => {
        const user = userEvent.setup();

        render(
            <AppFrame
                audience="business"
                nav={[]}
                active="home"
                launcher={link('/launcher')}
            >
                <p>Home</p>
            </AppFrame>,
        );

        await user.tab();
        const skip = screen.getByRole('link', { name: 'Skip to content' });

        expect(skip).toHaveFocus();
        expect(skip).toHaveAttribute('href', '#main-content');
        expect(screen.getByRole('main')).toHaveAttribute('id', 'main-content');
        expect(screen.getByRole('main')).toHaveAttribute('tabindex', '-1');
    });
});
