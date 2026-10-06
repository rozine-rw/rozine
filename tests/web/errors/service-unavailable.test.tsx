import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { catalogFor } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import AccessDenied from '@/pages/identity/access-denied';

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => (
        <span data-testid="head">{title}</span>
    ),
    Link: ({
        href,
        children,
    }: {
        href: { url: string };
        children: ReactNode;
    }) => <a href={href.url}>{children}</a>,
    usePage: () => ({
        url: '/investor/wallet?page=2',
        props: { auth: { user: null } },
    }),
}));

describe('The error page for a full-page read the server failed on (5xx)', () => {
    it('says Rozine could not load the page and retries the same URL, with no detail or access copy', () => {
        render(<AccessDenied code="SERVICE_UNAVAILABLE" status={503} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Temporarily unavailable',
        );
        expect(screen.getByRole('img', { name: 'Rozine' })).toBeInTheDocument();
        expect(screen.getByRole('alert')).toHaveTextContent(
            "Rozine couldn't load this page",
        );
        expect(
            screen.getByText(/Anything you already sent is unaffected/u),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Try again' })).toHaveAttribute(
            'href',
            '/investor/wallet?page=2',
        );
        expect(screen.getAllByRole('link')).toHaveLength(1);
        expect(
            screen.queryByText(/SERVICE_UNAVAILABLE|503|access|role/iu),
        ).not.toBeInTheDocument();
    });

    it('answers a 500 the same way', () => {
        render(<AccessDenied code="SERVICE_UNAVAILABLE" status={500} />);

        expect(
            screen.getByRole('heading', {
                name: "Rozine couldn't load this page",
            }),
        ).toBeInTheDocument();
    });

    it('is worded in French and Kinyarwanda', () => {
        const { rerender } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <AccessDenied status={503} />
            </I18nContext>,
        );

        expect(
            screen.getByRole('heading', {
                name: "Rozine n'a pas pu charger cette page",
            }),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Réessayer' })).toBeVisible();

        rerender(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <AccessDenied status={502} />
            </I18nContext>,
        );

        expect(
            screen.getByRole('heading', {
                name: 'Rozine ntiyashoboye gufungura iyi paji',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Ongera ugerageze' }),
        ).toBeVisible();
    });
});
