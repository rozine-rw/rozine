import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { catalogFor } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import AccessDenied from '@/pages/identity/access-denied';

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

describe('The error page for a read that found nothing (404)', () => {
    it('tells a visitor with no session that nothing was found, with no account-access copy', () => {
        render(<AccessDenied code="AUDIT_REPORT_NOT_FOUND" status={404} />);

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
        expect(
            screen.queryByText(/AUDIT_REPORT_NOT_FOUND/u),
        ).not.toBeInTheDocument();
    });

    it('sends a signed-in person back to their apps', () => {
        page.props.auth.user = { name: 'Synthetic Partner' };
        render(<AccessDenied code="ASSIGNMENT_NOT_FOUND" status={404} />);

        expect(
            screen.getByRole('link', { name: 'Choose an app' }),
        ).toHaveAttribute('href', '/dashboard');
    });

    it('is worded in French and Kinyarwanda', () => {
        const { rerender } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <AccessDenied status={404} />
            </I18nContext>,
        );

        expect(
            screen.getByRole('heading', {
                name: 'Nous ne trouvons pas cette page',
            }),
        ).toBeInTheDocument();

        rerender(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <AccessDenied status={404} />
            </I18nContext>,
        );

        expect(
            screen.getByRole('heading', { name: 'Ntitwabonye uru rupapuro' }),
        ).toBeInTheDocument();
    });

    it('keeps the refusal wording for a 403 or 409', () => {
        render(<AccessDenied code="ROLE_MEMBERSHIP_REQUIRED" status={403} />);

        expect(
            screen.getByRole('heading', { name: 'Access needs to be checked' }),
        ).toBeInTheDocument();
        expect(screen.getByTestId('head')).toHaveTextContent(
            'Access needs to be checked',
        );
    });
});
