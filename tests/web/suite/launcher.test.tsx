import { render, screen, within } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { LogoLockup, LogoMark } from '@/components/rozine/logo';
import Launcher from '@/pages/dashboard';
import type { IdentityCode, IdentityContext } from '@/types';
import auditorFixture from '../../../resources/fixtures/ui/launcher-auditor.json';
import readyFixture from '../../../resources/fixtures/ui/launcher-ready.json';
import pendingFixture from '../../../resources/fixtures/ui/launcher-verification-pending.json';

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => (
        <span data-testid="page-title">{title}</span>
    ),
    Link: ({ children, ...props }: ComponentProps<'a'>) => (
        <a {...props}>{children}</a>
    ),
}));

const identityFrom = (fixture: { props: { identity: unknown } }) =>
    fixture.props.identity as IdentityContext;

describe('Suite launcher', () => {
    it('lists exactly the authorized role apps, each in its own audience colour', () => {
        render(<Launcher identity={identityFrom(readyFixture)} />);

        expect(screen.getByTestId('page-title')).toHaveTextContent('Your apps');
        expect(screen.getByRole('img', { name: 'Rozine' })).toBeInTheDocument();
        expect(
            screen.getByText(
                'The Retail Capital Markets Layer for Emerging Economies',
            ),
        ).toBeInTheDocument();

        const apps = screen.getAllByRole('listitem');

        expect(apps).toHaveLength(2);
        expect(apps.map((app) => app.dataset.audience)).toEqual([
            'investor',
            'business',
        ]);

        const investor = within(apps[0]).getByRole('link');

        expect(investor).toHaveAttribute('href', '/investor');
        expect(investor).toHaveTextContent('Investor');
        expect(investor).toHaveTextContent(
            'Discover verified businesses, invest, track returns.',
        );
        expect(investor).toHaveTextContent('Open app →');
        expect(within(apps[1]).getByRole('link')).toHaveAttribute(
            'href',
            '/business',
        );
        expect(screen.queryByText('Auditor')).not.toBeInTheDocument();
    });

    it('shows an Audit Partner only the Auditor app', () => {
        render(<Launcher identity={identityFrom(auditorFixture)} />);

        const [auditor] = screen.getAllByRole('listitem');

        expect(screen.getAllByRole('listitem')).toHaveLength(1);
        expect(auditor.dataset.audience).toBe('auditor');
        expect(within(auditor).getByRole('link')).toHaveAttribute(
            'href',
            '/auditor',
        );
        expect(auditor).toHaveTextContent(
            'Field-verify on site, audit reports, earn yield.',
        );
    });

    it('explains a pending verification and offers a real next step instead of apps', () => {
        render(<Launcher identity={identityFrom(pendingFixture)} />);

        expect(screen.queryByRole('list')).not.toBeInTheDocument();

        const notice = screen.getByRole('status');

        expect(notice).toHaveTextContent('Your identity is being verified');
        expect(notice).toHaveTextContent(
            'Your apps open once your identity verification is complete.',
        );
        expect(
            within(notice).getByRole('link', {
                name: 'Contact Rozine support →',
            }),
        ).toHaveAttribute('href', 'mailto:hello@rozine.rw');
    });

    it.each<[Exclude<IdentityCode, 'IDENTITY_READY'>, string, string, string]>([
        [
            'EMAIL_VERIFICATION_REQUIRED',
            'Verify your email first',
            'Verify your email →',
            '/email/verify',
        ],
        [
            'IDENTITY_NOT_LINKED',
            'Your identity is not set up yet',
            'Contact Rozine support →',
            'mailto:hello@rozine.rw',
        ],
        [
            'PARTY_AUTHORITY_REQUIRED',
            'Signing authority is needed',
            'Contact Rozine support →',
            'mailto:hello@rozine.rw',
        ],
        [
            'ROLE_MEMBERSHIP_INVALID',
            'Your account needs attention',
            'Contact Rozine support →',
            'mailto:hello@rozine.rw',
        ],
        [
            'ROLE_MEMBERSHIP_CONFLICT',
            'These apps cannot be combined',
            'Contact Rozine support →',
            'mailto:hello@rozine.rw',
        ],
        [
            'ROLE_MEMBERSHIP_REQUIRED',
            'No apps yet',
            'Contact Rozine support →',
            'mailto:hello@rozine.rw',
        ],
    ])(
        'gives %s a titled notice and its next step',
        (code, title, action, href) => {
            render(
                <Launcher
                    identity={{
                        ...identityFrom(pendingFixture),
                        code,
                        available_roles: [],
                    }}
                />,
            );

            const notice = screen.getByRole('status');

            expect(notice).toHaveTextContent(title);
            expect(
                within(notice).getByRole('link', { name: action }),
            ).toHaveAttribute('href', href);
        },
    );
});

describe('Rozine logo', () => {
    it('names itself once when titled and hides itself when decorative', () => {
        render(
            <>
                <LogoLockup title="Rozine" data-testid="lockup" />
                <LogoMark data-testid="mark" />
            </>,
        );

        expect(screen.getByRole('img', { name: 'Rozine' })).toBe(
            screen.getByTestId('lockup'),
        );
        expect(screen.getByTestId('mark')).toHaveAttribute(
            'aria-hidden',
            'true',
        );
        expect(screen.getByTestId('mark')).not.toHaveAttribute('role');
    });
});
