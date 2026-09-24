import { render, screen } from '@testing-library/react';
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
});
