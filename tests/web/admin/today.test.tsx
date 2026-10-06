import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminToday from '@/pages/admin/today';
import type { AdminTodayProps } from '@/types/admin';
import clearFixture from '../../../resources/fixtures/ui/admin-today-clear.json';
import todayFixture from '../../../resources/fixtures/ui/admin-today.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminTodayProps;

beforeEach(resetInertia);

describe('Admin frame', () => {
    it('draws the grouped MVP sidebar with live badges and no post-MVP sections', () => {
        render(<AdminToday {...props(todayFixture)} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Rozine Operations Center',
        );
        const nav = screen.getByRole('navigation', {
            name: 'Console navigation',
        });

        expect(
            within(nav).getByRole('link', { name: 'Dashboard' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(nav).getByRole('link', { name: /^Applications\s*6$/ }),
        ).toHaveAttribute('href', '/preview/admin-applications');
        expect(
            within(nav).getByRole('link', { name: /^Disbursements\s*2$/ }),
        ).toBeInTheDocument();
        expect(
            within(nav).getByRole('link', { name: 'Ledger' }),
        ).not.toHaveAttribute('aria-current');
        expect(within(nav).getByText('Treasury')).toBeInTheDocument();

        for (const gone of ['Ratings', 'Policies', 'Engines', 'Notes']) {
            expect(
                within(nav).queryByRole('link', { name: gone }),
            ).not.toBeInTheDocument();
        }

        expect(
            screen.getByRole('link', { name: '← All apps' }),
        ).toHaveAttribute('href', '/preview/launcher-ready');
        expect(
            screen.getByRole('button', {
                name: 'About Rozine Operations Center',
            }),
        ).toHaveTextContent('Platform health at a glance');
    });

    it('hides a zero badge', () => {
        render(<AdminToday {...props(clearFixture)} />);

        expect(
            screen.getByRole('link', { name: 'Applications' }),
        ).toBeInTheDocument();
    });

    it('opens and closes the phone menu sheet', async () => {
        const { user } = renderWithUser(
            <AdminToday {...props(todayFixture)} />,
        );

        expect(
            screen.getAllByRole('navigation', { name: 'Console navigation' }),
        ).toHaveLength(1);
        await user.click(screen.getByRole('button', { name: 'Open menu' }));
        expect(
            screen.getAllByRole('navigation', { name: 'Console navigation' }),
        ).toHaveLength(2);
        await user.click(screen.getByRole('button', { name: 'Close menu' }));
        expect(
            screen.getAllByRole('navigation', { name: 'Console navigation' }),
        ).toHaveLength(1);
    });

    it('opens the operator menu with role and sign-out, and closes it again', async () => {
        const { user } = renderWithUser(
            <AdminToday {...props(todayFixture)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Account, A. Diane' }),
        );
        expect(screen.getByText('ops@rozine.rw')).toBeInTheDocument();
        expect(screen.getByText('Approver access')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Sign out' }),
        ).toHaveAttribute('data-method', 'post');
        await user.click(
            screen.getByRole('button', { name: 'Close account menu' }),
        );
        expect(screen.queryByText('ops@rozine.rw')).not.toBeInTheDocument();
    });

    it('searches the page on the server', async () => {
        const { user } = renderWithUser(
            <AdminToday {...props(todayFixture)} />,
        );

        await user.type(
            screen.getByRole('searchbox', { name: 'Search this page' }),
            'kivu{Enter}',
        );
        expect(inertia.reload).toEqual([{ data: { q: 'kivu' } }]);
    });
});

describe('Admin Today', () => {
    it('shows the headline figures without a second score', () => {
        render(<AdminToday {...props(todayFixture)} />);

        expect(screen.getAllByText('RWF 3.7B')).toHaveLength(2);
        expect(screen.getByText('Fees in, minus payouts')).toBeInTheDocument();
        expect(screen.getByText('9 of 165 notes')).toBeInTheDocument();
        expect(screen.getByText('RWF 753,000')).toBeInTheDocument();
        expect(screen.getByText('5.5%')).toBeInTheDocument();
        expect(screen.queryByText(/Trust Index/)).not.toBeInTheDocument();
    });

    it('reads a negative treasury and a default rate inside tolerance', () => {
        const fixture = props(todayFixture);

        fixture.kpis = fixture.kpis.map((kpi) =>
            kpi.key === 'treasury_position'
                ? { ...kpi, negative: true }
                : kpi.key === 'default_rate'
                  ? { ...kpi, breach: false }
                  : kpi,
        );
        render(<AdminToday {...fixture} />);

        expect(
            screen.getByText('Paid out more than collected'),
        ).toBeInTheDocument();
    });

    it('links attention tiles only to built queues', () => {
        render(<AdminToday {...props(todayFixture)} />);

        expect(
            screen.getByRole('link', { name: /^1\s*Applications pending$/ }),
        ).toHaveAttribute('href', '/preview/admin-applications');
        expect(
            screen.queryByRole('link', { name: /Notes late on payment/ }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('Notes late on payment')).toBeInTheDocument();
    });

    it('ages every break and takes ownership only with a reason', async () => {
        inertia.succeed = true;
        const { user } = renderWithUser(
            <AdminToday {...props(todayFixture)} />,
        );

        expect(
            screen.getByText(/Open 3 days · Unassigned/),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/Open 1 days · Owner: Grace Kalisa/),
        ).toBeInTheDocument();
        expect(screen.getByText('RWF 12,400')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Assign to me' }));
        const stage = screen.getByRole('form', {
            name: 'Take ownership of TXN-99214',
        });
        const confirm = within(stage).getByRole('button', {
            name: 'Take ownership',
        });

        expect(confirm).toBeDisabled();
        expect(
            within(stage).getByText(
                'Logged to the audit trail as A. Diane · Approver',
            ),
        ).toBeInTheDocument();
        await user.type(within(stage).getByRole('textbox'), '   ');
        expect(confirm).toBeDisabled();
        await user.type(within(stage).getByRole('textbox'), 'Matching MoMo');
        await user.click(confirm);
        expect(inertia.posts).toEqual([
            {
                url: '/admin/breaks/brk_1/assign',
                data: { reason: '   Matching MoMo' },
            },
        ]);
        expect(
            screen.queryByRole('form', { name: 'Take ownership of TXN-99214' }),
        ).not.toBeInTheDocument();
    });

    it('escalates a break and can cancel the stage', async () => {
        const { user } = renderWithUser(
            <AdminToday {...props(todayFixture)} />,
        );

        await user.click(
            screen.getAllByRole('button', { name: 'Escalate' })[1],
        );
        const stage = screen.getByRole('form', { name: 'Escalate TXN-99180' });

        await user.click(within(stage).getByRole('button', { name: 'Cancel' }));
        expect(
            screen.queryByRole('form', { name: 'Escalate TXN-99180' }),
        ).not.toBeInTheDocument();
    });

    it('says when everything is reconciled', () => {
        render(<AdminToday {...props(clearFixture)} />);

        expect(screen.getByText('Ledger fully reconciled')).toBeInTheDocument();
        expect(screen.getByText('No open breaks.')).toBeInTheDocument();
    });

    it('refetches the capital chart for a chosen window and clears it', () => {
        const fixture = props(todayFixture);

        fixture.capital_raised = {
            ...fixture.capital_raised,
            from: '2026-01-01T08:30:00+02:00',
            to: null,
            grain: 'month',
        };
        render(<AdminToday {...fixture} />);

        expect(
            screen.getByText('1 Jan 2026 → … · grouped by month'),
        ).toBeInTheDocument();
        fireEvent.change(screen.getByLabelText('To date'), {
            target: { value: '2026-06-30' },
        });
        fireEvent.change(screen.getByLabelText('From time'), {
            target: { value: '' },
        });
        fireEvent.change(screen.getByLabelText('To time'), {
            target: { value: '18:00' },
        });
        expect(inertia.reload).toEqual([
            {
                data: { from: '2026-01-01T08:30', to: '2026-06-30T00:00' },
                only: ['capital_raised'],
            },
            {
                data: { from: '2026-01-01T00:00', to: '2026-06-30T00:00' },
                only: ['capital_raised'],
            },
            {
                data: { from: '2026-01-01T00:00', to: '2026-06-30T18:00' },
                only: ['capital_raised'],
            },
        ]);
        fireEvent.change(screen.getByLabelText('From date'), {
            target: { value: '' },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Clear' }));
        expect(inertia.reload.slice(-2)).toEqual([
            {
                data: { from: null, to: '2026-06-30T18:00' },
                only: ['capital_raised'],
            },
            { data: { from: null, to: null }, only: ['capital_raised'] },
        ]);
    });

    it('labels an open-ended window from its end', () => {
        const fixture = props(todayFixture);

        fixture.capital_raised = {
            ...fixture.capital_raised,
            from: null,
            to: '2026-06-30T00:00:00+02:00',
        };
        render(<AdminToday {...fixture} />);

        expect(
            screen.getByText('… → 30 Jun 2026 · grouped by year'),
        ).toBeInTheDocument();
    });

    it('lays out the book panels from server facts', () => {
        render(<AdminToday {...props(todayFixture)} />);

        expect(
            screen.getByRole('img', { name: '87% of notes healthy' }),
        ).toBeInTheDocument();
        expect(screen.getByText('143 · 87%')).toBeInTheDocument();
        expect(screen.getByText('3.7B')).toBeInTheDocument();
        expect(screen.getByText('Audit Partner share')).toBeInTheDocument();
        expect(screen.getByText('3m ago')).toBeInTheDocument();
        expect(screen.getByText('56m ago')).toBeInTheDocument();
        expect(screen.getByText('1h ago')).toBeInTheDocument();
        expect(
            screen.getByRole('link', {
                name: 'See all activity in the ledger',
            }),
        ).toHaveAttribute('href', '/preview/admin-ledger');
        expect(screen.getByText('Repaying')).toBeInTheDocument();
        expect(screen.getByText('Stable 3.8')).toBeInTheDocument();
        expect(screen.getAllByText('Pending audit')).toHaveLength(1);
        expect(
            screen.getByRole('link', { name: 'Review Akabanga Foods' }),
        ).toHaveAttribute('href', '/preview/admin-applications-review');
        expect(screen.getByText('RWF 418.9M')).toBeInTheDocument();
        expect(screen.getByText('24 notes')).toBeInTheDocument();
        expect(screen.getByText('RWF 1.2B')).toBeInTheDocument();
        expect(screen.getByText('57 notes in repayment')).toBeInTheDocument();
        expect(screen.getByText('RWF 118.9M')).toBeInTheDocument();
    });

    it('explains quiet panels', () => {
        const fixture = props(todayFixture);

        fixture.activity = [
            {
                ...fixture.activity[0],
                at: '2026-09-23T17:39:40+02:00',
            },
            {
                ...fixture.activity[1],
                id: 'old',
                at: '2026-09-20T17:39:40+02:00',
            },
        ];
        render(<AdminToday {...fixture} />);

        expect(screen.getByText('just now')).toBeInTheDocument();
        expect(screen.getByText('3d ago')).toBeInTheDocument();
    });

    it('says when there is no activity or no application waiting', () => {
        render(
            <AdminToday
                {...props(todayFixture)}
                activity={[]}
                pending_applications={[]}
            />,
        );

        expect(screen.getByText('No money has moved yet.')).toBeInTheDocument();
        expect(
            screen.getByText('No applications waiting.'),
        ).toBeInTheDocument();
    });
});
