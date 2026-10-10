import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminReconciliation from '@/pages/admin/reconciliation';
import type { AdminReconciliationProps } from '@/types/admin';
import feedFixture from '../../../resources/fixtures/ui/admin-reconciliation-feed-unavailable.json';
import reconciliationFixture from '../../../resources/fixtures/ui/admin-reconciliation.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminReconciliationProps;

const rowOf = (text: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(text));

    return row;
};

const nav = () =>
    screen.getByRole('navigation', { name: 'Console navigation' });

beforeEach(resetInertia);

describe('Reconciliation', () => {
    it('sets each account’s three balances side by side and ties the difference to its open break, read-only', () => {
        render(<AdminReconciliation {...props(reconciliationFixture)} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Reconciliation');
        expect(
            within(nav()).getByRole('link', { name: 'Finance' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(nav()).getByRole('link', { name: 'Risk Center' }),
        ).not.toHaveAttribute('aria-current');

        const close = screen.getByRole('region', { name: 'Day close' });

        expect(close).toHaveTextContent('Day close · 22 Jan 2027');
        expect(close).toHaveTextContent('Open break');
        expect(close).toHaveTextContent(
            'The day cannot close while a break is unexplained.',
        );

        const momo = rowOf('Mobile money collections account');

        expect(momo).toHaveTextContent('As of 22 Jan 2027 · 23:59');
        expect(within(momo).getAllByText('RWF 48,215,600')).toHaveLength(2);
        expect(within(momo).getByText('RWF 0')).toBeInTheDocument();
        expect(momo).toHaveTextContent('Receiving');

        const bank = within(
            screen.getByRole('table', { name: 'Account balances' }),
        )
            .getAllByRole('row')
            .find((row) =>
                within(row).queryByText('Bank settlement account'),
            ) as HTMLElement;

        expect(bank).toHaveTextContent('RWF 126,480,000');
        expect(bank).toHaveTextContent('RWF 126,476,900');
        expect(within(bank).getByText('RWF 3,100')).toBeInTheDocument();

        const brk = rowOf('STM-2027-0122');

        expect(brk).toHaveTextContent(
            'Bank charge on the 22 Jan 2027 statement with no ledger entry',
        );
        expect(brk).toHaveTextContent('Bank settlement account');
        expect(brk).toHaveTextContent('RWF 3,100');
        expect(brk).toHaveTextContent('Open 1 day');
        expect(brk).toHaveTextContent('Unassigned');
        expect(
            screen.getByRole('link', { name: 'Open STM-2027-0122' }),
        ).toHaveAttribute('href', '/preview/admin-ledger');

        expect(
            screen.queryAllByRole('button', { name: /assign|escalate|close/i }),
        ).toEqual([]);
        expect(screen.queryByText('No open breaks')).not.toBeInTheDocument();
    });

    it('shows a feed that stopped, with no statement or difference, and the day not yet reconciled', () => {
        render(<AdminReconciliation {...props(feedFixture)} />);

        expect(
            screen.getByRole('region', { name: 'Day close' }),
        ).toHaveTextContent('Not yet reconciled');

        const momo = rowOf('Mobile money collections account');

        expect(momo).toHaveTextContent('RWF 48,215,600');
        expect(momo).toHaveTextContent('No statement');
        expect(momo).toHaveTextContent('—');
        expect(momo).toHaveTextContent('Unavailable');
        expect(momo).toHaveTextContent('Since 22 Jan 2027 · 21:10');
        expect(momo).not.toHaveTextContent('Receiving');
        expect(screen.getByText('No open breaks')).toBeInTheDocument();
    });

    it('shows a reconciled day, and a break with an owner on an account not listed', () => {
        const page = props(reconciliationFixture);

        page.day_close = {
            business_date: '2027-01-22',
            state: 'reconciled',
            reconciled_at: '2027-01-23T00:20:00+02:00',
        };
        page.breaks[0] = {
            ...page.breaks[0],
            account_id: 'acct_other',
            owner: {
                actor: 'Grace Kalisa',
                at: '2027-01-22T09:00:00+02:00',
                reason: null,
            },
        };
        render(<AdminReconciliation {...page} />);

        const close = screen.getByRole('region', { name: 'Day close' });

        expect(close).toHaveTextContent('Reconciled');
        expect(close).toHaveTextContent(
            'the day closed at 23 Jan 2027 · 00:20',
        );

        const brk = rowOf('STM-2027-0122');

        expect(brk).toHaveTextContent('Owner: Grace Kalisa');
        expect(brk).not.toHaveTextContent('Bank settlement account');
    });

    it('tells a search that matched no account apart from none reported', () => {
        const page = props(feedFixture);

        page.accounts = [];
        page.search = 'Kivu';
        const { rerender } = render(<AdminReconciliation {...page} />);

        expect(screen.getByText('No accounts reported')).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(/Kivu/);

        rerender(<AdminReconciliation {...page} search="" />);

        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('renders when the server sends no legacy links or queue sizes', () => {
        const fixture = props(reconciliationFixture);

        fixture.nav = {
            ...fixture.nav,
            today: null,
            applications: null,
            disbursements: null,
            repayments: null,
            businesses: null,
            investors: null,
            auditors: null,
            staff: null,
            ledger: null,
            events: null,
            book: null,
            exceptions: null,
        };
        fixture.badges = { applications: null, disbursements: null };
        render(<AdminReconciliation {...fixture} />);

        expect(
            within(nav()).getByRole('link', { name: 'Finance' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(nav()).queryByRole('link', { name: /Applications/u }),
        ).not.toBeInTheDocument();
        expect(
            within(nav()).queryByRole('link', { name: 'Exceptions' }),
        ).not.toBeInTheDocument();
    });
});
