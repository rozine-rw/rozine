import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminPayments from '@/pages/admin/payments';
import type { AdminPaymentsProps, PayoutInFlight } from '@/types/admin';
import shellFixture from '../../../resources/fixtures/ui/admin-exceptions-empty.json';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const payout = (overrides: Partial<PayoutInFlight>): PayoutInFlight => ({
    id: '01K0000000000000000000AAAA',
    reference: 'WDR-00AAAA',
    owner: 'investor',
    name: 'Aline Uwase',
    amount: { currency: 'RWF', amount: '20000' },
    received: { currency: 'RWF', amount: '20000' },
    account: 'MTN MoMo •••• 4521',
    state: 'pending',
    requested_at: '2026-10-15T08:00:00+00:00',
    ...overrides,
});

const props = (
    overrides: Partial<AdminPaymentsProps> = {},
): AdminPaymentsProps => {
    const { viewer, nav, badges, server_time } = structuredClone(
        shellFixture.props,
    );

    return {
        viewer,
        nav: { ...nav, payments: { url: '/admin/payments', method: 'get' } },
        badges,
        server_time,
        search: '',
        contract_version: 'staff-payments-v1',
        staff_access_version: 'staff-access-v1',
        allowed_actions: [],
        from: '2026-10-02T00:00:00+02:00',
        trend: { from: '2026-10-02T00:00:00+02:00', to: null, grain: 'day' },
        stats: [
            {
                key: 'deposits',
                value: { currency: 'RWF', amount: '2500000' },
                count: 3,
            },
            {
                key: 'withdrawals',
                value: { currency: 'RWF', amount: '100000' },
                count: 1,
            },
            {
                key: 'disbursed',
                value: { currency: 'RWF', amount: '0' },
                count: 0,
            },
        ],
        in_flight: 2,
        policy: [
            { key: 'platform_fee', value: '3%' },
            { key: 'cpa_share', value: '40%' },
        ],
        bars: [
            {
                label: '10-14',
                amount: { currency: 'RWF', amount: '1000000' },
                height_pct: 40,
            },
            {
                label: '10-15',
                amount: { currency: 'RWF', amount: '2500000' },
                height_pct: 0,
            },
        ],
        payouts: [
            payout({}),
            payout({
                id: '01K0000000000000000000BBBB',
                reference: 'WDR-00BBBB',
                owner: 'business',
                name: null,
                state: 'unknown',
                amount: { currency: 'RWF', amount: '5000' },
            }),
        ],
        pagination: { next: null },
        ...overrides,
    } as AdminPaymentsProps;
};

const rowOf = (reference: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(reference));

    return row;
};

beforeEach(resetInertia);

describe('Payment Center', () => {
    it('shows the fourteen-day money movement and every payout in flight, with no release or hold', () => {
        render(<AdminPayments {...props()} />);

        expect(
            within(
                screen.getByRole('navigation', { name: 'Console navigation' }),
            ).getByRole('link', { name: /^Payments/ }),
        ).toHaveAttribute('aria-current', 'page');
        expect(screen.getByText('Deposits')).toBeInTheDocument();
        expect(screen.getByText('3 txns · last 14 days')).toBeInTheDocument();
        expect(screen.getByText('1 txn · last 14 days')).toBeInTheDocument();
        expect(
            screen.getByText('0 payouts to businesses · last 14 days'),
        ).toBeInTheDocument();
        expect(screen.getByText('Awaiting the provider')).toBeInTheDocument();
        expect(
            screen.getByRole('region', { name: 'Fee schedule in effect' }),
        ).toHaveTextContent(
            'Platform fee · at disbursement3%CPA share — Kigali40%',
        );

        const movement = screen.getByRole('region', {
            name: 'Money movement',
        });

        expect(movement).toHaveTextContent('Last 14 days · grouped by day');
        expect(within(movement).getByText('10-14')).toBeInTheDocument();
        expect(within(movement).getByText('2.5M')).toBeInTheDocument();

        const held = rowOf('WDR-00AAAA');

        expect(held).toHaveTextContent('Aline Uwase');
        expect(held).toHaveTextContent('Investor withdrawal');
        expect(held).toHaveTextContent('RWF 20,000');
        expect(held).toHaveTextContent('MTN MoMo •••• 4521');
        expect(held).toHaveTextContent('Awaiting provider');

        const unknown = rowOf('WDR-00BBBB');

        expect(unknown).toHaveTextContent('Unnamed account');
        expect(unknown).toHaveTextContent('Business withdrawal');
        expect(unknown).toHaveTextContent('Unconfirmed, re-querying');

        expect(
            screen.queryAllByRole('button', {
                name: /release|hold|reverse/i,
            }),
        ).toEqual([]);
        expect(
            screen.queryByRole('link', { name: 'Show all payouts' }),
        ).not.toBeInTheDocument();
    });

    it('reloads money movement over the chosen dates, grouped by day or month', () => {
        render(<AdminPayments {...props()} />);

        const movement = screen.getByRole('region', {
            name: 'Money movement',
        });

        expect(movement).toHaveTextContent('Last 14 days · grouped by day');
        fireEvent.change(within(movement).getByLabelText('From date'), {
            target: { value: '2026-05-01' },
        });
        expect(inertia.reload.at(-1)).toMatchObject({
            data: { from: '2026-05-01', to: null },
            only: ['bars', 'trend'],
        });
    });

    it('keeps a single day of movement to one bar wide', () => {
        render(
            <AdminPayments
                {...props({
                    bars: [
                        {
                            label: '10-14',
                            amount: { currency: 'RWF', amount: '1000000' },
                            height_pct: 100,
                        },
                    ],
                })}
            />,
        );

        expect(screen.getByTestId('chart-bar')).toHaveClass('max-w-14');
    });

    it('offers a further page when more payouts are in flight', () => {
        render(
            <AdminPayments
                {...props({
                    in_flight: 0,
                    payouts: [payout({})],
                    pagination: {
                        next: {
                            url: '/admin/payments?limit=500',
                            method: 'get',
                        },
                    },
                })}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'Show all payouts' }),
        ).toHaveAttribute('href', '/admin/payments?limit=500');
    });

    it('tells a search that matched nothing apart from no payouts in flight', () => {
        const { rerender } = render(
            <AdminPayments {...props({ payouts: [], search: 'Kivu' })} />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(/Kivu/);
        expect(
            screen.queryByText('No payouts in flight'),
        ).not.toBeInTheDocument();

        rerender(<AdminPayments {...props({ payouts: [], bars: [] })} />);

        expect(screen.getByText('No payouts in flight')).toBeInTheDocument();
        expect(
            screen.getByText('No money movement to chart yet.'),
        ).toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });
});
