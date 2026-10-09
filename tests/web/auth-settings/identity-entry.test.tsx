import { render, screen } from '@testing-library/react';
import { expect, it, vi } from 'vite-plus/test';
import AccessDenied from '@/pages/identity/access-denied';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
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
