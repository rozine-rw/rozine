import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorProfile from '@/pages/investor/profile';
import type { InvestorProfileProps } from '@/types/investor';
import linkedFixture from '../../../resources/fixtures/ui/investor-profile-linked.json';
import statementsFixture from '../../../resources/fixtures/ui/investor-profile-statements.json';
import unverifiedFixture from '../../../resources/fixtures/ui/investor-profile-unverified.json';
import profileFixture from '../../../resources/fixtures/ui/investor-profile.json';
import { inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const profile = (fixture: { props: unknown } = profileFixture) =>
    structuredClone(fixture.props) as InvestorProfileProps;

beforeEach(() => {
    resetInertia();
    setWide(false);
});

describe('Profile', () => {
    it('shows identity, verification, the built menu rows and sign out', async () => {
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

        expect(within(menu).getAllByRole('link')).toHaveLength(5);
        expect(
            within(menu).getByRole('link', { name: /Linked accounts/u }),
        ).toHaveAttribute('href', '/preview/investor-profile-linked');
        expect(
            within(menu).queryByText(/Rozine Plus/u),
        ).not.toBeInTheDocument();
        expect(
            within(menu).queryByText(/Help center/u),
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
        expect(inertia.posts[0].url).toBe('/preview/investor-profile-linked');

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
        await user.selectOptions(within(form).getByLabelText('BANK'), 'EQUITY');
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
                bank: 'EQUITY',
                number: '40012345678',
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

    it('lists statements, marks an open period and never asks for a tax identifier', () => {
        render(<InvestorProfile {...profile(statementsFixture)} />);

        expect(
            screen.getByRole('heading', { name: 'Statements' }),
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

        statements.annual = null;
        statements.monthly = [];
        render(<InvestorProfile {...props} />);
        expect(screen.getByText(/No statements yet/u)).toBeInTheDocument();
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
            unmount();
        });
    });

    it('keeps the menu beside the open sub-page on a wide screen', () => {
        setWide(true);
        const { unmount } = render(<InvestorProfile {...profile()} />);

        expect(
            screen.getByRole('link', { name: /Linked accounts/u }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            screen.getByRole('heading', { name: 'Linked accounts' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Back' }),
        ).not.toBeInTheDocument();
        unmount();

        render(<InvestorProfile {...profile(statementsFixture)} />);
        expect(
            screen.getByRole('link', { name: /Statements/u }),
        ).toHaveAttribute('aria-current', 'page');
    });
});
