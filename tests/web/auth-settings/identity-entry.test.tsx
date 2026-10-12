import { render, screen } from '@testing-library/react';
import { expect, it, vi } from 'vite-plus/test';
import { catalogFor } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import type { Locale } from '@/lib/i18n/types';
import AccessDenied from '@/pages/identity/access-denied';

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => (
        <span data-testid="head">{title}</span>
    ),
    usePage: () => ({
        props: { auth: { user: null } },
        url: '/investor/wallet',
    }),
    Link: ({
        href,
        children,
    }: {
        href: string | { url: string };
        children: React.ReactNode;
    }) => <a href={typeof href === 'string' ? href : href.url}>{children}</a>,
}));

it('replaces denied role pages with an accessible recovery page', () => {
    render(<AccessDenied />);
    expect(screen.getByRole('alert')).toHaveTextContent(
        'Choose an app to refresh your access.',
    );
    expect(screen.getByRole('link', { name: 'Choose an app' })).toHaveAttribute(
        'href',
        '/dashboard',
    );
});

it('says an offer closed, not that access must be checked, for an expired offer', () => {
    render(<AccessDenied code="ASSIGNMENT_ACCEPTANCE_EXPIRED" />);
    expect(
        screen.getByRole('heading', { name: 'This offer has closed' }),
    ).toBeInTheDocument();
    expect(screen.getByRole('alert')).toHaveTextContent(
        'The time to accept this job ran out',
    );
    expect(
        screen.queryByText(/Access needs to be checked/u),
    ).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Choose an app' })).toHaveAttribute(
        'href',
        '/dashboard',
    );
});

it('keeps the account-access wording for every other refusal code', () => {
    render(<AccessDenied code="ROLE_MEMBERSHIP_REQUIRED" />);
    expect(
        screen.getByRole('heading', { name: 'Access needs to be checked' }),
    ).toBeInTheDocument();
});

it.each([
    [
        'en',
        'Identity verification required',
        'Your identity needs to be verified',
        'Choose an app',
        'Access needs to be checked',
    ],
    [
        'fr',
        'Vérification d’identité requise',
        'Votre identité doit être vérifiée',
        'Choisir une application',
        'Votre accès doit être vérifié',
    ],
    [
        'rw',
        'Igenzura ry’umwirondoro rirakenewe',
        'Umwirondoro wawe ugomba kugenzurwa',
        'Hitamo porogaramu',
        'Uburenganzira bugomba kugenzurwa',
    ],
] satisfies [Locale, string, string, string, string][])(
    'explains the verification refusal in %s without claiming lost role access',
    (locale, title, body, link, fallbackTitle) => {
        const { rerender } = render(
            <I18nContext value={{ locale, catalog: catalogFor(locale) }}>
                <AccessDenied
                    code="IDENTITY_VERIFICATION_REQUIRED"
                    status={403}
                />
            </I18nContext>,
        );
        expect(
            screen.getByRole('heading', { name: title }),
        ).toBeInTheDocument();
        expect(screen.getByTestId('head')).toHaveTextContent(title);
        expect(screen.getByRole('alert')).toHaveTextContent(body);
        expect(screen.getByRole('link', { name: link })).toHaveAttribute(
            'href',
            '/dashboard',
        );
        expect(
            screen.queryByText(/IDENTITY_VERIFICATION_REQUIRED/u),
        ).not.toBeInTheDocument();

        rerender(
            <I18nContext value={{ locale, catalog: catalogFor(locale) }}>
                <AccessDenied code="ACTIVE_ROLE_REQUIRED" status={409} />
            </I18nContext>,
        );
        expect(
            screen.queryByRole('heading', { name: title }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('heading')).toHaveTextContent(fallbackTitle);

        rerender(
            <I18nContext value={{ locale, catalog: catalogFor(locale) }}>
                <AccessDenied
                    code="IDENTITY_VERIFICATION_REQUIRED"
                    status={403}
                />
            </I18nContext>,
        );
        expect(
            screen.getByRole('heading', { name: title }),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: link })).toHaveAttribute(
            'href',
            '/dashboard',
        );
    },
);

it.each([404, 500, 503])(
    'keeps status %s ahead of a verification code',
    (status) => {
        render(
            <AccessDenied
                code="IDENTITY_VERIFICATION_REQUIRED"
                status={status}
            />,
        );
        expect(
            screen.queryByRole('heading', {
                name: 'Identity verification required',
            }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('heading')).toHaveTextContent(
            status === 404
                ? "We couldn't find that page"
                : "Rozine couldn't load this page",
        );
        expect(
            screen.queryByText(/IDENTITY_VERIFICATION_REQUIRED/u),
        ).not.toBeInTheDocument();
    },
);

it('keeps unknown support codes private and on the generic recovery path', () => {
    render(<AccessDenied code="UNKNOWN_SUPPORT_CODE" status={403} />);
    expect(
        screen.getByRole('heading', { name: 'Access needs to be checked' }),
    ).toBeInTheDocument();
    expect(screen.queryByText(/UNKNOWN_SUPPORT_CODE/u)).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Choose an app' })).toHaveAttribute(
        'href',
        '/dashboard',
    );
});
