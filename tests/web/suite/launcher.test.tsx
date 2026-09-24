import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { LogoLockup, LogoMark } from '@/components/rozine/logo';
import Launcher from '@/pages/dashboard';
import type { IdentityCode, IdentityContext } from '@/types';
import auditorBlockedFixture from '../../../resources/fixtures/ui/launcher-auditor-mfa-required.json';
import auditorFixture from '../../../resources/fixtures/ui/launcher-auditor.json';
import readyFixture from '../../../resources/fixtures/ui/launcher-ready.json';
import selectedFixture from '../../../resources/fixtures/ui/launcher-selected.json';
import pendingFixture from '../../../resources/fixtures/ui/launcher-verification-pending.json';

type HttpOptions = {
    onHttpException: (response: { status: number; data: string }) => void;
};

const inertia = vi.hoisted(() => ({
    posts: [] as { url: string; body: unknown }[],
    respond: (() => Promise.resolve(undefined)) as (
        options: HttpOptions,
    ) => Promise<unknown>,
    visits: [] as unknown[],
    reloads: [] as unknown[],
    processing: false,
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => (
        <span data-testid="page-title">{title}</span>
    ),
    Link: ({
        children,
        href,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & {
        href: string | { url: string };
    }) => (
        <a href={typeof href === 'string' ? href : href.url} {...props}>
            {children}
        </a>
    ),
    router: {
        visit: (target: unknown) => inertia.visits.push(target),
        reload: (options: unknown) => inertia.reloads.push(options),
    },
    useHttp: () => {
        let transform = (data: unknown) => data;

        return {
            processing: inertia.processing,
            transform: (callback: (data: unknown) => unknown) => {
                transform = callback;
            },
            post: (url: string, options: HttpOptions) => {
                inertia.posts.push({ url, body: transform({}) });

                return inertia.respond(options);
            },
        };
    },
}));

const identityFrom = (fixture: { props: { identity: unknown } }) =>
    fixture.props.identity as IdentityContext;

const answer = (identity: IdentityContext) => () =>
    Promise.resolve({ data: identity });

const refuse = (status: number, data: string) => (options: HttpOptions) => {
    options.onHttpException({ status, data });

    return Promise.reject(new Error(`HTTP ${status}`));
};

const command = (role: string, request: string, revision = 0) => ({
    url: '/identity/active-role',
    body: { role, expected_revision: revision, request_id: request },
});

let uuid = 0;

beforeEach(() => {
    inertia.posts = [];
    inertia.visits = [];
    inertia.reloads = [];
    inertia.processing = false;
    inertia.respond = () => Promise.resolve(undefined);
    uuid = 0;
    vi.spyOn(crypto, 'randomUUID').mockImplementation(
        () =>
            `00000000-0000-4000-8000-00000000000${++uuid}` as `${string}-${string}-${string}-${string}-${string}`,
    );
});

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

        const investor = within(apps[0]).getByRole('button');

        expect(investor).toHaveTextContent('Investor');
        expect(investor).toHaveTextContent(
            'Discover verified businesses, invest, track returns.',
        );
        expect(investor).toHaveTextContent('Open app →');
        expect(screen.queryByText('Auditor')).not.toBeInTheDocument();
        expect(screen.queryByRole('link')).not.toBeInTheDocument();
    });

    it('selects a role on the server before opening its app', async () => {
        const user = userEvent.setup();
        const identity = identityFrom(readyFixture);

        inertia.respond = answer({ ...identity, active_role: 'business' });
        render(<Launcher identity={identity} />);

        await user.click(screen.getByRole('button', { name: /Business/ }));

        expect(inertia.posts).toEqual([
            command('business', '00000000-0000-4000-8000-000000000001'),
        ]);
        expect(inertia.visits).toEqual(['/business']);
    });

    it('opens the already active role straight away and switches to another', async () => {
        const user = userEvent.setup();
        const identity = identityFrom(selectedFixture);

        inertia.respond = answer({ ...identity, active_role: 'investor' });
        render(
            <Launcher
                identity={identity}
                links={readyFixture.props.links as never}
            />,
        );

        const [investor, business] = screen.getAllByRole('listitem');

        expect(within(business).getByRole('link')).toHaveAttribute(
            'href',
            '/preview/business-home',
        );

        await user.click(within(investor).getByRole('button'));

        expect(inertia.posts).toEqual([
            command('investor', '00000000-0000-4000-8000-000000000001', 4),
        ]);
        expect(inertia.visits).toEqual([
            { url: '/preview/investor-deals', method: 'get' },
        ]);
    });

    it('shows an Audit Partner only the Auditor app', () => {
        render(<Launcher identity={identityFrom(auditorFixture)} />);

        const [auditor] = screen.getAllByRole('listitem');

        expect(screen.getAllByRole('listitem')).toHaveLength(1);
        expect(auditor.dataset.audience).toBe('auditor');
        expect(within(auditor).getByRole('button')).toHaveTextContent(
            'Field-verify on site, audit reports, earn yield.',
        );
    });

    it('sends an Audit Partner without two-factor authentication to set it up', () => {
        render(<Launcher identity={identityFrom(auditorBlockedFixture)} />);

        const [auditor] = screen.getAllByRole('listitem');

        expect(within(auditor).queryByRole('button')).not.toBeInTheDocument();
        expect(auditor).toHaveTextContent('Needs two-factor authentication');
        expect(
            within(auditor).getByRole('link', {
                name: 'Set up two-factor authentication',
            }),
        ).toHaveAttribute('href', '/settings/security');
    });

    it('holds the cards while a switch is in flight', () => {
        inertia.processing = true;
        render(<Launcher identity={identityFrom(readyFixture)} />);

        for (const card of screen.getAllByRole('button')) {
            expect(card).toBeDisabled();
            expect(card).toHaveTextContent('Opening…');
        }
    });

    it('refreshes identity when the role changed in another window', async () => {
        const user = userEvent.setup();

        inertia.respond = refuse(
            409,
            JSON.stringify({ code: 'ACTIVE_ROLE_REVISION_CONFLICT' }),
        );
        render(<Launcher identity={identityFrom(readyFixture)} />);

        await user.click(screen.getByRole('button', { name: /Investor/ }));

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Your apps changed in another window. Choose again.',
        );
        expect(inertia.reloads).toEqual([{ only: ['identity'] }]);
        expect(inertia.visits).toEqual([]);
    });

    it('asks for two-factor authentication when the server requires it', async () => {
        const user = userEvent.setup();

        inertia.respond = refuse(403, JSON.stringify({ code: 'MFA_REQUIRED' }));
        render(<Launcher identity={identityFrom(auditorFixture)} />);

        await user.click(screen.getByRole('button', { name: /Auditor/ }));

        const alert = within(screen.getByRole('alert'));

        expect(
            alert.getByText(
                'Set up two-factor authentication to open Auditor.',
            ),
        ).toBeInTheDocument();
        expect(
            alert.getByRole('link', {
                name: 'Set up two-factor authentication',
            }),
        ).toHaveAttribute('href', '/settings/security');
        expect(inertia.reloads).toEqual([]);
    });

    it('clears a role the account no longer holds', async () => {
        const user = userEvent.setup();

        inertia.respond = refuse(
            403,
            JSON.stringify({ code: 'ROLE_NOT_AVAILABLE' }),
        );
        render(<Launcher identity={identityFrom(readyFixture)} />);

        await user.click(screen.getByRole('button', { name: /Business/ }));

        expect(screen.getByRole('alert')).toHaveTextContent(
            'You no longer have access to Business.',
        );
        expect(inertia.reloads).toEqual([{ only: ['identity'] }]);
    });

    it('never silently retries a request ID the server has seen with other input', async () => {
        const user = userEvent.setup();

        inertia.respond = refuse(
            409,
            JSON.stringify({ code: 'IDEMPOTENCY_KEY_REUSED' }),
        );
        render(<Launcher identity={identityFrom(readyFixture)} />);

        await user.click(screen.getByRole('button', { name: /Business/ }));

        expect(screen.getByRole('alert')).toHaveTextContent(
            'That request was already used for another choice. Choose Business again.',
        );
        expect(
            screen.queryByRole('button', { name: 'Try again' }),
        ).not.toBeInTheDocument();
        expect(inertia.posts).toHaveLength(1);
    });

    it.each([
        ['an unknown code', JSON.stringify({ code: 'SOMETHING_ELSE' })],
        ['a body without a code', JSON.stringify({ message: 'Nope' })],
        ['a body that is not JSON', '<html>Server error</html>'],
    ])(
        'says the app did not open on %s, and tries again with a new request',
        async (_, body) => {
            const user = userEvent.setup();
            const identity = identityFrom(readyFixture);

            inertia.respond = refuse(500, body);
            render(<Launcher identity={identity} />);

            await user.click(screen.getByRole('button', { name: /Investor/ }));

            expect(screen.getByRole('alert')).toHaveTextContent(
                "Investor didn't open.",
            );

            inertia.respond = answer({ ...identity, active_role: 'investor' });
            await user.click(screen.getByRole('button', { name: 'Try again' }));

            expect(inertia.posts).toEqual([
                command('investor', '00000000-0000-4000-8000-000000000001'),
                command('investor', '00000000-0000-4000-8000-000000000002'),
            ]);
            expect(inertia.visits).toEqual(['/investor']);
        },
    );

    it('does not open an app the server did not make active', async () => {
        const user = userEvent.setup();
        const identity = identityFrom(readyFixture);

        inertia.respond = answer({ ...identity, active_role: 'investor' });
        render(<Launcher identity={identity} />);

        await user.click(screen.getByRole('button', { name: /Business/ }));

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Business didn't open.",
        );
        expect(inertia.visits).toEqual([]);
    });

    it('treats a rejected command as not opened', async () => {
        const user = userEvent.setup();

        render(<Launcher identity={identityFrom(readyFixture)} />);

        await user.click(screen.getByRole('button', { name: /Business/ }));

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Business didn't open.",
        );
    });

    it('resends the same command after a lost connection', async () => {
        const user = userEvent.setup();
        const identity = identityFrom(readyFixture);

        inertia.respond = () => Promise.reject(new Error('Network down'));
        render(<Launcher identity={identity} />);

        await user.click(screen.getByRole('button', { name: /Investor/ }));

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Couldn't reach Rozine to open Investor. Check your connection.",
        );

        inertia.respond = answer({ ...identity, active_role: 'investor' });
        await user.click(screen.getByRole('button', { name: 'Try again' }));

        expect(inertia.posts).toEqual([
            command('investor', '00000000-0000-4000-8000-000000000001'),
            command('investor', '00000000-0000-4000-8000-000000000001'),
        ]);
        expect(inertia.visits).toEqual(['/investor']);
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
