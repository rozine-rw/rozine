import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminPrimaryMarket from '@/pages/admin/primary-market';
import type {
    AdminPrimaryMarketProps,
    PrimaryMarketRaise,
} from '@/types/admin';
import shellFixture from '../../../resources/fixtures/ui/admin-exceptions-empty.json';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const raise = (overrides: Partial<PrimaryMarketRaise>): PrimaryMarketRaise => ({
    id: '01K0000000000000000000AAAA',
    reference: 'RSE-00AAAA',
    business: 'Ubuki Crafts',
    sector: 'Agriculture',
    district: 'Gasabo',
    rating: { band: 'strong', score: '4.2' },
    rate_pct: '15',
    term_months: 12,
    funded_pct: '75.0',
    raised: { currency: 'RWF', amount: '3000000' },
    target: { currency: 'RWF', amount: '4000000' },
    investors: 3,
    lifecycle: 'live',
    opened_at: '2026-10-01T10:00:00+02:00',
    closes_at: '2026-10-20T10:00:00+02:00',
    days_left: 5,
    ...overrides,
});

const props = (
    overrides: Partial<AdminPrimaryMarketProps> = {},
): AdminPrimaryMarketProps => {
    const { viewer, nav, badges, server_time } = structuredClone(
        shellFixture.props,
    );

    return {
        viewer,
        nav: {
            ...nav,
            primary_market: { url: '/admin/primary-market', method: 'get' },
        },
        badges,
        server_time,
        search: '',
        contract_version: 'staff-primary-market-v1',
        staff_access_version: 'staff-access-v1',
        allowed_actions: [],
        stats: [
            { key: 'active', value: { kind: 'count', value: 2 } },
            {
                key: 'in_flight',
                value: {
                    kind: 'money',
                    value: { currency: 'RWF', amount: '4000000' },
                },
            },
            { key: 'fill', value: { kind: 'percent', value: '40' } },
            { key: 'completion', value: { kind: 'percent', value: '75' } },
        ],
        trend: {
            from: '2026-10-02T00:00:00+02:00',
            to: null,
            grain: 'day',
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
        },
        raises: [
            raise({}),
            raise({
                id: '01K0000000000000000000BBBB',
                reference: 'RSE-00BBBB',
                business: 'GreenLeaf Agro',
                funded_pct: '100.0',
                investors: 1,
                term_months: 1,
                lifecycle: 'closing_pending_settlement',
                days_left: 0,
            }),
        ],
        pagination: { next: null },
        ...overrides,
    } as AdminPrimaryMarketProps;
};

const rowOf = (reference: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) =>
            within(candidate).queryByText(reference, { exact: false }),
        );

    return row;
};

beforeEach(resetInertia);

describe('Primary Market Monitor', () => {
    it('shows the market figures and every raise still filling, with no moderation', () => {
        render(<AdminPrimaryMarket {...props()} />);

        expect(
            within(
                screen.getByRole('navigation', { name: 'Console navigation' }),
            ).getByRole('link', { name: 'Primary Market' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(screen.getByText('Active Raises')).toBeInTheDocument();
        expect(screen.getByText('Raised in Live Raises')).toBeInTheDocument();
        expect(screen.getByText('40%')).toBeInTheDocument();
        expect(screen.getByText('75%')).toBeInTheDocument();

        const trend = screen.getByRole('region', { name: 'Funding trend' });

        expect(trend).toHaveTextContent('Last 14 days · grouped by day');
        expect(within(trend).getByText('10-14')).toBeInTheDocument();
        expect(within(trend).getByText('2.5M')).toBeInTheDocument();
        expect(
            within(trend).queryByText('No funding to chart yet.'),
        ).not.toBeInTheDocument();

        const live = rowOf('RSE-00AAAA');

        expect(live).toHaveTextContent('Ubuki Crafts');
        expect(live).toHaveTextContent(
            'RSE-00AAAA · Agriculture · 15% yield · 12 months',
        );
        expect(live).toHaveTextContent('75.0%');
        expect(live).toHaveTextContent('RWF 3,000,000 of RWF 4,000,000');
        expect(live).toHaveTextContent('3 investors');
        expect(live).toHaveTextContent('5 days left');
        expect(live).not.toHaveTextContent('Closing');

        const closing = rowOf('RSE-00BBBB');

        expect(closing).toHaveTextContent('GreenLeaf Agro');
        expect(closing).toHaveTextContent('1 month');
        expect(closing).toHaveTextContent('1 investor');
        expect(closing).toHaveTextContent('100.0%');
        expect(within(closing).getAllByText('Closing')).toHaveLength(2);

        expect(
            screen.queryAllByRole('button', {
                name: /feature|pause|fill|fail/i,
            }),
        ).toEqual([]);
        expect(
            screen.queryByRole('link', { name: 'More live raises' }),
        ).not.toBeInTheDocument();
    });

    it('reloads the funding trend over the chosen dates and clears back to the last 14 days', () => {
        render(
            <AdminPrimaryMarket
                {...props({
                    trend: {
                        from: '2026-05-01T00:00:00+02:00',
                        to: '2026-10-14T00:00:00+02:00',
                        grain: 'month',
                        custom: true,
                        bars: [],
                    },
                })}
            />,
        );

        const trend = screen.getByRole('region', { name: 'Funding trend' });

        expect(trend).toHaveTextContent('grouped by month');
        expect(within(trend).getByLabelText('From date')).toHaveValue(
            '2026-05-01',
        );
        fireEvent.change(within(trend).getByLabelText('To date'), {
            target: { value: '2026-10-20' },
        });
        expect(inertia.reload.at(-1)).toMatchObject({
            data: { from: '2026-05-01', to: '2026-10-20' },
            only: ['trend'],
        });
        fireEvent.click(within(trend).getByRole('button', { name: 'Clear' }));
        expect(inertia.reload.at(-1)).toMatchObject({
            data: { from: null, to: null },
        });
        expect(trend).toHaveTextContent('Last 14 days');
        expect(
            within(trend).queryByRole('button', { name: 'Clear' }),
        ).not.toBeInTheDocument();
    });

    it('shows a From-only window as chosen, and says when the dates were refused', () => {
        inertia.errors = {
            to: 'The to field must be a date after or equal to from.',
        };
        render(
            <AdminPrimaryMarket
                {...props({
                    trend: {
                        from: '2026-09-01T00:00:00+02:00',
                        to: null,
                        grain: 'day',
                        custom: true,
                        bars: [],
                    },
                })}
            />,
        );

        const trend = screen.getByRole('region', { name: 'Funding trend' });
        const from = within(trend).getByLabelText('From date');
        expect(from).toHaveValue('2026-09-01');
        expect(trend).not.toHaveTextContent('Last 14 days');
        expect(within(trend).getByLabelText('To date')).toHaveAttribute(
            'min',
            '2026-09-01',
        );
        expect(within(trend).getByRole('alert')).toHaveTextContent(
            'Choose a To date on or after the From date.',
        );
        expect(from).toHaveAttribute('aria-invalid', 'true');
    });

    it('keeps a single day of commitments to one bar wide', () => {
        render(
            <AdminPrimaryMarket
                {...props({
                    trend: {
                        from: '2026-10-14T00:00:00+02:00',
                        to: null,
                        grain: 'day',
                        custom: false,
                        bars: [
                            {
                                label: '10-14',
                                amount: { currency: 'RWF', amount: '1000000' },
                                height_pct: 100,
                            },
                        ],
                    },
                })}
            />,
        );

        expect(screen.getByTestId('chart-bar')).toHaveClass('max-w-14');
    });

    it('shows one day left in the singular and offers a further page', () => {
        render(
            <AdminPrimaryMarket
                {...props({
                    raises: [raise({ days_left: 1 })],
                    pagination: {
                        next: {
                            url: '/admin/primary-market?limit=500',
                            method: 'get',
                        },
                    },
                })}
            />,
        );

        expect(rowOf('RSE-00AAAA')).toHaveTextContent('1 day left');
        expect(
            screen.getByRole('link', { name: 'More live raises' }),
        ).toHaveAttribute('href', '/admin/primary-market?limit=500');
    });

    it('tells a search that matched nothing apart from no live raises', () => {
        const { rerender } = render(
            <AdminPrimaryMarket {...props({ raises: [], search: 'Kivu' })} />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(/Kivu/);
        expect(screen.queryByText('No live raises')).not.toBeInTheDocument();

        rerender(
            <AdminPrimaryMarket
                {...props({
                    raises: [],
                    trend: {
                        from: '',
                        to: null,
                        grain: 'day',
                        custom: false,
                        bars: [],
                    },
                })}
            />,
        );

        expect(screen.getByText('No live raises')).toBeInTheDocument();
        expect(
            screen.getByText('No funding to chart yet.'),
        ).toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });
});
