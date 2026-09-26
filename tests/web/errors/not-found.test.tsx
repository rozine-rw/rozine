import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { catalogFor } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import NotFound from '@/pages/errors/not-found';

const page = vi.hoisted(() => ({
    props: { auth: { user: null as { name: string } | null } },
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => (
        <span data-testid="head">{title}</span>
    ),
    Link: ({
        href,
        children,
        ...props
    }: {
        href: { url: string };
        children: ReactNode;
        className?: string;
    }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
    usePage: () => page,
}));

beforeEach(() => {
    page.props.auth.user = null;
});

describe('Not found page', () => {
    it('tells a visitor with no session that nothing was found, with no account-access copy', () => {
        render(<NotFound />);

        expect(screen.getByTestId('head')).toHaveTextContent('Not found');
        expect(screen.getByRole('img', { name: 'Rozine' })).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: "We couldn't find that page" }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/The link may be mistyped/u),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Go to the Rozine home page' }),
        ).toHaveAttribute('href', '/');
        expect(screen.queryByText(/access|role/iu)).not.toBeInTheDocument();
    });

    it('sends a signed-in person back to their apps', () => {
        page.props.auth.user = { name: 'Synthetic Partner' };
        render(<NotFound />);

        expect(
            screen.getByRole('link', { name: 'Choose an app' }),
        ).toHaveAttribute('href', '/dashboard');
    });

    it('is worded in French and Kinyarwanda', () => {
        const { rerender } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <NotFound />
            </I18nContext>,
        );

        expect(
            screen.getByRole('heading', {
                name: 'Nous ne trouvons pas cette page',
            }),
        ).toBeInTheDocument();

        rerender(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <NotFound />
            </I18nContext>,
        );

        expect(
            screen.getByRole('heading', { name: 'Ntitwabonye uru rupapuro' }),
        ).toBeInTheDocument();
    });
});
