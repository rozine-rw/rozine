import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorProfile from '@/pages/investor/profile';
import type { InvestorProfileProps } from '@/types/investor';
import automationFixture from '../../../resources/fixtures/ui/investor-profile-automation.json';
import helpFixture from '../../../resources/fixtures/ui/investor-profile-help.json';
import expiredFixture from '../../../resources/fixtures/ui/investor-profile-kyc-expired.json';
import linkedFixture from '../../../resources/fixtures/ui/investor-profile-linked.json';
import personalFixture from '../../../resources/fixtures/ui/investor-profile-personal.json';
import planFixture from '../../../resources/fixtures/ui/investor-profile-plan.json';
import privacyFixture from '../../../resources/fixtures/ui/investor-profile-privacy.json';
import securityFixture from '../../../resources/fixtures/ui/investor-profile-security.json';
import emptyStatementsFixture from '../../../resources/fixtures/ui/investor-profile-statements-empty.json';
import statementsFixture from '../../../resources/fixtures/ui/investor-profile-statements.json';
import termsFixture from '../../../resources/fixtures/ui/investor-profile-terms.json';
import unverifiedFixture from '../../../resources/fixtures/ui/investor-profile-unverified.json';
import profileFixture from '../../../resources/fixtures/ui/investor-profile.json';
import { inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const profile = (fixture: { props: unknown } = profileFixture) =>
    structuredClone(fixture.props) as InvestorProfileProps;

/** The design's eight menu rows, in its order (Investor.dc.html L9933–9941). */
const MENU_ROWS = [
    'Personal information›',
    'Rozine Plus · how to unlock›',
    'Security center›',
    'Linked accounts›',
    'Statements & tax›',
    'Help center›',
    'Terms & Conditions›',
    'Privacy Note›',
];

beforeEach(() => {
    resetInertia();
    setWide(false);
});

describe('Profile', () => {
    it("shows identity, verification, the design's menu rows and sign out", async () => {
        const user = userEvent.setup();

        render(<InvestorProfile {...profile()} />);

        expect(
            screen.getByRole('heading', { name: 'Profile' }),
        ).toBeInTheDocument();
        expect(screen.getByText('RM')).toBeInTheDocument();
        expect(screen.getByText('Robert Mugisha')).toBeInTheDocument();
        expect(screen.getByText('✓ KYC Verified')).toBeInTheDocument();
        expect(screen.getByText('Individual')).toBeInTheDocument();
        expect(screen.getByText('Member since March 2026')).toBeInTheDocument();

        const menu = screen.getByRole('navigation', { name: 'Profile menu' });

        expect(
            within(menu)
                .getAllByRole('link')
                .map((link) => link.textContent),
        ).toEqual(MENU_ROWS);
        expect(
            within(menu)
                .getAllByRole('link')
                .map((link) => link.getAttribute('href')),
        ).toEqual([
            '/preview/investor-profile-personal',
            '/preview/investor-profile-plan',
            '/preview/investor-profile-security',
            '/preview/investor-profile-linked',
            '/preview/investor-profile-statements',
            '/preview/investor-profile-help',
            '/preview/investor-profile-terms',
            '/preview/investor-profile-privacy',
        ]);
        expect(
            within(menu).queryByText('Identity verification'),
        ).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Sign out' }));
        expect(inertia.posts).toEqual([
            { url: '/preview/investor-login', data: {} },
        ]);
    });

    it('opens linked accounts as its own phone page and links a bank account', async () => {
        const user = userEvent.setup();

        inertia.succeed = true;
        render(<InvestorProfile {...profile(linkedFixture)} />);

        expect(
            screen.getByRole('heading', { name: 'Linked accounts' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/investor-profile',
        );
        expect(
            screen.queryByRole('navigation', { name: 'Profile menu' }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('+250 788 ···· 456')).toBeInTheDocument();
        expect(screen.getByText('· Verifying')).toBeInTheDocument();

        await user.click(screen.getAllByRole('button', { name: 'Unlink' })[0]);
        expect(inertia.posts[0]).toEqual({
            url: '/preview/investor-profile-linked',
            data: { request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u) },
        });

        await user.click(
            screen.getByRole('button', { name: '+ Link a new account' }),
        );

        const form = screen.getByRole('form', { name: 'Link a new account' });

        expect(
            within(form).getByRole('radio', { name: 'Mobile money' }),
        ).toBeChecked();
        await user.click(
            within(form).getByRole('radio', { name: /Airtel Money/u }),
        );
        expect(
            within(form).getByRole('radio', { name: /Airtel Money/u }),
        ).toBeChecked();
        expect(
            within(form).getByRole('button', { name: 'Link account' }),
        ).toBeDisabled();

        await user.click(
            within(form).getByRole('radio', { name: 'Bank account' }),
        );
        await user.selectOptions(within(form).getByLabelText('BANK'), 'equity');
        await user.type(
            within(form).getByLabelText('ACCOUNT NUMBER'),
            '40-01 2345678',
        );
        expect(within(form).getByLabelText('ACCOUNT NUMBER')).toHaveValue(
            '40012345678',
        );
        await user.click(
            within(form).getByRole('button', { name: 'Link account' }),
        );

        expect(inertia.posts[1]).toEqual({
            url: '/preview/investor-profile-linked',
            data: {
                type: 'bank',
                network: 'airtel',
                bank: 'equity',
                number: '40012345678',
                request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u),
            },
        });
        expect(
            screen.queryByRole('form', { name: 'Link a new account' }),
        ).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: '+ Link a new account' }),
        );
        await user.click(screen.getByRole('button', { name: 'Close' }));
        expect(
            screen.queryByRole('form', { name: 'Link a new account' }),
        ).not.toBeInTheDocument();
    });

    it('keeps the form open on a refusal and explains an unverified investor with nothing linked', async () => {
        const user = userEvent.setup();

        inertia.errors = { number: 'That account is already linked.' };
        inertia.processing = true;
        const props = profile(unverifiedFixture);

        props.section = 'linked';
        props.linked = { accounts: [], banks: [] };
        render(<InvestorProfile {...props} />);

        expect(screen.getByText(/No payout account yet/u)).toBeInTheDocument();

        const form = screen.getByRole('form', { name: 'Link a new account' });

        expect(
            within(form).queryByRole('button', { name: 'Close' }),
        ).not.toBeInTheDocument();
        expect(within(form).getByRole('alert')).toHaveTextContent(
            'That account is already linked.',
        );
        expect(within(form).getByLabelText('MOBILE NUMBER')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        await user.click(
            within(form).getByRole('radio', { name: 'Bank account' }),
        );
        expect(
            within(within(form).getByLabelText('BANK')).queryAllByRole(
                'option',
            ),
        ).toHaveLength(0);
    });

    it('offers only the commands the server links, as the live profile sends them', () => {
        setWide(true);
        const props = profile(linkedFixture);

        props.links.verification = null;
        props.actions.link_account = null;
        props.linked = {
            accounts: profile().linked!.accounts.map((account) => ({
                ...account,
                unlink: null,
            })),
            banks: [],
        };
        const { unmount } = render(<InvestorProfile {...props} />);
        const menu = screen.getByRole('navigation', { name: 'Profile menu' });

        expect(
            within(menu)
                .getAllByRole('link')
                .map((link) => link.textContent),
        ).toEqual(MENU_ROWS);
        expect(screen.getByText('+250 788 ···· 456')).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Unlink' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: '+ Link a new account' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('form', { name: 'Link a new account' }),
        ).not.toBeInTheDocument();
        unmount();

        props.linked = { accounts: [], banks: [] };
        render(<InvestorProfile {...props} />);
        expect(
            screen.getByText('No payout account linked yet.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('form', { name: 'Link a new account' }),
        ).not.toBeInTheDocument();
    });

    it('lists statements, marks an open period and never asks for a tax identifier', () => {
        render(<InvestorProfile {...profile(statementsFixture)} />);

        expect(
            screen.getByRole('heading', { name: 'Statements & tax' }),
        ).toBeInTheDocument();
        expect(screen.getByText('TAX YEAR 2025')).toBeInTheDocument();
        expect(
            screen.getByText('Total interest earned · fees paid'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Download annual summary' }),
        ).toHaveAttribute('href', '/preview/investor-profile-statements');
        expect(
            screen.getByText('Sept 2026 still open · figures may change'),
        ).toBeInTheDocument();
        expect(screen.getAllByText('PDF statement')).toHaveLength(3);
        expect(screen.queryByText(/\bTIN\b/u)).not.toBeInTheDocument();
    });

    it('explains an open year and an empty history', () => {
        const props = profile(statementsFixture);
        const statements = props.statements as NonNullable<
            InvestorProfileProps['statements']
        >;

        statements.annual = {
            ...(statements.annual as NonNullable<typeof statements.annual>),
            complete: false,
        };
        const { unmount } = render(<InvestorProfile {...props} />);

        expect(
            screen.getByText('Year still open · figures still moving'),
        ).toBeInTheDocument();
        unmount();

        render(<InvestorProfile {...profile(emptyStatementsFixture)} />);
        expect(screen.getByText(/No statements yet/u)).toBeInTheDocument();
        expect(screen.queryByText('PDF statement')).not.toBeInTheDocument();
    });

    it('shows each verification state', () => {
        const states = ['pending', 'unverified', 'expired'] as const;
        const labels = [
            'Verification pending',
            'Not verified',
            'Document expired',
        ];

        states.forEach((kyc, index) => {
            const props = profile();

            props.identity.kyc = kyc;
            props.identity.investor_type = 'institution';
            const { unmount } = render(<InvestorProfile {...props} />);

            expect(screen.getByText(labels[index])).toBeInTheDocument();
            expect(screen.getByText('Institution')).toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: /Identity verification/u }),
            ).toHaveAttribute('href', '/preview/investor-verification');
            unmount();
        });
    });

    it('reads an expired identity document from the server', () => {
        render(<InvestorProfile {...profile(expiredFixture)} />);

        expect(screen.getByText('Document expired')).toBeInTheDocument();
        expect(screen.queryByText('✓ KYC Verified')).not.toBeInTheDocument();
    });

    it('explains Auto-Deploy as gated, with the missing approval and a next step, and nothing to switch on', () => {
        render(<InvestorProfile {...profile(automationFixture)} />);

        expect(
            screen.getByRole('heading', { name: 'Auto-Deploy' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            "Auto-Deploy isn't available yet",
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            'product, fee and legal approval',
        );
        expect(
            screen.getByText(/Nothing is set up, running or charged/u),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Choose a deal yourself' }),
        ).toHaveAttribute('href', '/preview/investor-deals');
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/investor-profile',
        );
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(screen.queryByRole('switch')).not.toBeInTheDocument();
        expect(screen.queryByText(/%/u)).not.toBeInTheDocument();
    });

    it('leaves Auto-Deploy out of the menu, as the design does outside Rozine Plus', () => {
        setWide(true);
        render(<InvestorProfile {...profile(automationFixture)} />);
        const menu = screen.getByRole('navigation', { name: 'Profile menu' });

        expect(
            within(menu)
                .getAllByRole('link')
                .map((link) => link.textContent),
        ).toEqual(MENU_ROWS);
        expect(
            within(menu).queryByRole('link', { name: /Auto-Deploy/u }),
        ).not.toBeInTheDocument();
        expect(
            within(menu).queryByRole('link', { current: 'page' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Auto-Deploy' }),
        ).toBeInTheDocument();
    });

    it('keeps the menu beside the open sub-page on a wide screen', () => {
        setWide(true);
        const { unmount } = render(<InvestorProfile {...profile()} />);

        expect(
            screen.getByRole('link', { name: /Personal information/u }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            screen.getByRole('heading', { name: 'Personal information' }),
        ).toBeInTheDocument();
        expect(screen.getByText('•••• •••• •••• 5678')).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Back' }),
        ).not.toBeInTheDocument();
        unmount();

        render(<InvestorProfile {...profile(statementsFixture)} />);
        expect(
            screen.getByRole('link', { name: /Statements/u }),
        ).toHaveAttribute('aria-current', 'page');
    });

    it('shows personal information read-only, with the masked ID number and no save', () => {
        const { unmount } = render(
            <InvestorProfile {...profile(personalFixture)} />,
        );
        const field = (label: string) =>
            screen.getByText(label).nextElementSibling?.textContent;

        expect(
            screen.getByRole('heading', { name: 'Personal information' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/investor-profile',
        );
        expect(field('FULL NAME')).toBe('Robert Mugisha');
        expect(field('EMAIL')).toBe('robert.m@example.rw');
        expect(field('PHONE')).toBe('Not provided');
        expect(field('ID TYPE')).toBe('National ID');
        expect(field('ID NUMBER')).toBe('•••• •••• •••• 5678');
        expect(screen.getByText('ADDRESS')).toBeInTheDocument();
        expect(field('COUNTRY')).toBe('Unknown');

        for (const label of ['PROVINCE', 'DISTRICT', 'SECTOR', 'CELL']) {
            expect(field(label)).toBe('Not provided');
        }

        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Save changes' }),
        ).not.toBeInTheDocument();
        unmount();

        const passport = profile(personalFixture);

        passport.personal.id_type = 'passport';
        passport.personal.id_number = '•••••4567';
        const { unmount: unmountPassport } = render(
            <InvestorProfile {...passport} />,
        );

        expect(field('ID TYPE')).toBe('Passport');
        expect(field('PASSPORT NUMBER')).toBe('•••••4567');
        unmountPassport();

        const unverified = profile(personalFixture);

        unverified.personal.id_type = null;
        unverified.personal.id_number = null;
        render(<InvestorProfile {...unverified} />);
        expect(field('ID TYPE')).toBe('Not provided');
        expect(field('ID NUMBER')).toBe('Not provided');
        expect(field('COUNTRY')).toBe('Unknown');
    });

    it('reports two-factor sign-in and opens the security settings for it and the password', () => {
        const { unmount } = render(
            <InvestorProfile {...profile(securityFixture)} />,
        );

        expect(
            screen.getByRole('heading', { name: 'Security center' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Two-factor authentication/u }),
        ).toHaveTextContent('On · authenticator app code at sign-in');
        expect(
            screen.getByRole('link', { name: /Two-factor authentication/u }),
        ).toHaveAttribute('href', '/preview/investor-profile-security');
        expect(
            screen.getByRole('link', { name: /Change password/u }),
        ).toHaveAttribute('href', '/preview/investor-profile-security');
        expect(screen.queryByText(/Delete account/u)).not.toBeInTheDocument();
        unmount();

        const off = profile(securityFixture);

        off.security.two_factor = false;
        render(<InvestorProfile {...off} />);
        expect(
            screen.getByRole('link', { name: /Two-factor authentication/u }),
        ).toHaveTextContent('Off · add an authenticator app code at sign-in');
    });

    it('switches the login alert e-mail off and on', async () => {
        const user = userEvent.setup();
        const { unmount } = render(
            <InvestorProfile {...profile(securityFixture)} />,
        );

        expect(
            screen.getByText('Email me on new device sign-in'),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('switch', { name: 'Login alerts' }));
        expect(inertia.posts).toEqual([
            {
                url: '/preview/investor-profile-security',
                data: { enabled: '0' },
            },
        ]);
        expect(inertia.forms).toContainEqual({
            action: '/preview/investor-profile-security',
            method: 'put',
        });
        unmount();

        const off = profile(securityFixture);

        off.security.login_alerts = false;
        render(<InvestorProfile {...off} />);
        expect(
            screen.getByRole('switch', { name: 'Login alerts' }),
        ).not.toBeChecked();
        await user.click(screen.getByRole('switch', { name: 'Login alerts' }));
        expect(inertia.posts[1]).toEqual({
            url: '/preview/investor-profile-security',
            data: { enabled: '1' },
        });
    });

    it.each([
        [planFixture, 'Rozine Plus', 'Rozine Plus is not open yet'],
        [termsFixture, 'Terms & Conditions', 'Coming to the app soon'],
        [privacyFixture, 'Privacy Note', 'Coming to the app soon'],
    ])(
        'shows %#: the design page, empty until its content is approved',
        (fixture, heading, title) => {
            render(<InvestorProfile {...profile(fixture)} />);

            expect(
                screen.getByRole('heading', { name: heading }),
            ).toBeInTheDocument();
            expect(screen.getByText(title)).toBeInTheDocument();
            expect(screen.queryByText(/%|RWF/u)).not.toBeInTheDocument();
            expect(screen.queryByRole('button')).not.toBeInTheDocument();
        },
    );

    it('opens a Help center answer in full, and messages support from it', async () => {
        const user = userEvent.setup();

        render(<InvestorProfile {...profile(helpFixture)} />);

        expect(screen.getAllByRole('heading', { level: 3 })).toHaveLength(9);
        expect(screen.getByText('4 answers')).toBeInTheDocument();
        expect(
            screen.queryByText(/Academy|points|referral/iu),
        ).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', {
                name: /What is the Rozine rating\?/u,
            }),
        );
        let sheet = screen.getByRole('dialog', {
            name: 'What is the Rozine rating?',
        });

        expect(
            within(sheet).getByText('Risk & how businesses are vetted'),
        ).toBeInTheDocument();
        expect(sheet).toHaveTextContent(
            'Strong (4.0 to 5.0), Stable (3.0 to 3.9) or Distressed (below 3.0)',
        );
        expect(sheet).toHaveTextContent('Last updated 15 October 2026');

        await user.click(within(sheet).getByRole('button', { name: 'Close' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', {
                name: /Will a sale be guaranteed\?/u,
            }),
        );
        sheet = screen.getByRole('dialog', {
            name: 'Will a sale be guaranteed?',
        });
        await user.click(
            within(sheet).getByRole('button', { name: 'Message support' }),
        );

        expect(
            screen.queryByRole('dialog', {
                name: 'Will a sale be guaranteed?',
            }),
        ).not.toBeInTheDocument();
        expect(
            within(
                screen.getByRole('dialog', { name: 'Rozine Support' }),
            ).getByText('Reference CMP-00000001'),
        ).toBeInTheDocument();
    });

    it('shows the Help center answers, with the support chat over real complaints', async () => {
        const user = userEvent.setup();

        inertia.succeed = true;
        render(<InvestorProfile {...profile(helpFixture)} />);

        expect(
            screen.getByRole('heading', { name: 'Help center' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByText('Coming to the app soon'),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Getting started' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: /CMP-00000001/u }),
        ).toHaveTextContent('Answered');
        expect(
            screen.getByRole('button', { name: /CMP-00000002/u }),
        ).toHaveTextContent('Resolved');

        /* The floating button opens the complaint not yet resolved, and a reply goes to it. */
        await user.click(
            screen.getByRole('button', { name: 'Contact Rozine Support' }),
        );
        let chat = screen.getByRole('dialog', { name: 'Rozine Support' });

        expect(
            within(chat).getByText('Reference CMP-00000001'),
        ).toBeInTheDocument();
        expect(
            within(chat).getByText(/We are checking it with the provider/u),
        ).toBeInTheDocument();
        expect(
            within(chat).getByText(
                'You can take a complaint to the Capital Market Authority at any stage.',
            ),
        ).toBeInTheDocument();
        await user.type(
            within(chat).getByLabelText('Your message'),
            'Any news?',
        );
        await user.click(within(chat).getByRole('button', { name: 'Send' }));
        expect(inertia.posts).toHaveLength(1);
        expect(inertia.posts[0].url).toBe('/preview/investor-profile-help');
        expect(inertia.posts[0].data).toMatchObject({ body: 'Any news?' });
        expect(String(inertia.posts[0].data.request_id)).toMatch(
            /^[0-9a-f-]{36}$/u,
        );
        expect(within(chat).getByLabelText('Your message')).toHaveValue('');

        /* A resolved complaint takes nothing more; a new message starts a new one, then follows it. */
        await user.click(within(chat).getByRole('button', { name: 'Close' }));
        await user.click(screen.getByRole('button', { name: /CMP-00000002/u }));
        chat = screen.getByRole('dialog', { name: 'Rozine Support' });

        expect(
            within(chat).getByText('This complaint is resolved.'),
        ).toBeInTheDocument();
        expect(within(chat).getByText(/^Resolution ·/u)).toBeInTheDocument();
        await user.click(
            within(chat).getByRole('button', { name: 'New message' }),
        );
        expect(
            within(chat).getByText(/Your message opens a complaint/u),
        ).toBeInTheDocument();
        await user.type(
            within(chat).getByLabelText('Your message'),
            'Something else.',
        );
        await user.click(within(chat).getByRole('button', { name: 'Send' }));
        expect(inertia.posts[1].url).toBe('/preview/investor-profile-help');
        expect(
            within(chat).getByText('Reference CMP-00000001'),
        ).toBeInTheDocument();

        /* The scrim closes the panel too. */
        await user.click(screen.getByTestId('support-scrim'));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('opens a fresh conversation when nothing is open, and shows a refusal on the message', async () => {
        const user = userEvent.setup();
        const page = profile(helpFixture);

        page.support.complaints = [];
        inertia.errors = { body: 'Tell us what happened.' };
        render(<InvestorProfile {...page} />);

        expect(screen.queryByText('Your complaints')).not.toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Contact Rozine Support' }),
        );
        const chat = screen.getByRole('dialog', { name: 'Rozine Support' });

        expect(
            within(chat).getByText(
                'We acknowledge within 4 hours and resolve within 48',
            ),
        ).toBeInTheDocument();
        expect(within(chat).getByRole('alert')).toHaveTextContent(
            'Tell us what happened.',
        );
        expect(within(chat).getByLabelText('Your message')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
    });
});
