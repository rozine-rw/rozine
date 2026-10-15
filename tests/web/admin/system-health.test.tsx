import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminSystemHealth from '@/pages/admin/system-health';
import type { AdminSystemHealthProps, HealthSignal } from '@/types/admin';
import shellFixture from '../../../resources/fixtures/ui/admin-exceptions-empty.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const untracked = (
    key: 'default_rate' | 'arrears' | 'outstanding' | 'treasury' | 'frozen',
): HealthSignal => ({ key, status: 'untracked', facts: [], action: null });

const healthy: HealthSignal[] = [
    {
        key: 'ledger',
        status: 'healthy',
        facts: { accounts: 2, unmatched: 0, breaks: 0 },
        action: null,
    },
    {
        key: 'capital',
        status: 'healthy',
        facts: { amount: '5000000', raises: 1 },
        action: null,
    },
    { key: 'queue', status: 'healthy', facts: { pending: 2 }, action: null },
    {
        key: 'events',
        status: 'healthy',
        facts: { last_hour: 7, total: 90 },
        action: null,
    },
    untracked('default_rate'),
    untracked('arrears'),
    untracked('outstanding'),
    untracked('treasury'),
    untracked('frozen'),
];

const props = (
    overrides: Partial<AdminSystemHealthProps> = {},
): AdminSystemHealthProps => {
    const { viewer, nav, badges, server_time } = structuredClone(
        shellFixture.props,
    );

    return {
        viewer,
        nav: {
            ...nav,
            system_health: { url: '/admin/system-health', method: 'get' },
        },
        badges,
        server_time,
        search: '',
        contract_version: 'staff-system-health-v1',
        staff_access_version: 'staff-access-v1',
        allowed_actions: [],
        overall: { critical: 0, watch: 0 },
        signals: healthy,
        ...overrides,
    } as AdminSystemHealthProps;
};

const card = (label: string): HTMLElement =>
    screen.getByRole('article', { name: label });

describe('Admin System Health', () => {
    beforeEach(() => resetInertia());

    it('says every tracked signal is healthy, and that the rest are not tracked yet', () => {
        render(<AdminSystemHealth {...props()} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'All systems healthy',
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            'Every tracked signal is within tolerance',
        );
        expect(screen.getAllByRole('article')).toHaveLength(9);
        expect(card('Ledger integrity')).toHaveTextContent('Balanced');
        expect(card('Ledger integrity')).toHaveTextContent(
            '2 provider accounts reconciled',
        );
        expect(card('Capital in flight')).toHaveTextContent(
            '1 raise filling or awaiting disbursement',
        );
        expect(card('Underwriting queue')).toHaveTextContent(
            'The queue is clear.',
        );
        expect(card('Event throughput')).toHaveTextContent('7/h');
        expect(card('Event throughput')).toHaveTextContent(
            '90 actions recorded in total',
        );
        expect(card('Treasury position')).toHaveTextContent('Not tracked yet');
        expect(card('Treasury position')).toHaveTextContent(
            'No treasury balance is recorded yet',
        );
        expect(
            screen.queryByRole('link', { name: /Open/ }),
        ).not.toBeInTheDocument();
    });

    it('leads with a critical ledger and links what needs a look', () => {
        render(
            <AdminSystemHealth
                {...props({
                    overall: { critical: 1, watch: 2 },
                    signals: [
                        {
                            key: 'ledger',
                            status: 'critical',
                            facts: { accounts: 2, unmatched: 1, breaks: 1 },
                            action: {
                                url: '/admin/reconciliation',
                                method: 'get',
                            },
                        },
                        {
                            key: 'queue',
                            status: 'watch',
                            facts: { pending: 6 },
                            action: {
                                url: '/admin/applications',
                                method: 'get',
                            },
                        },
                        {
                            key: 'events',
                            status: 'watch',
                            facts: { last_hour: 0, total: 1 },
                            action: null,
                        },
                    ],
                })}
            />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            'Attention required',
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            '1 critical, 2 watch signals',
        );
        const ledger = card('Ledger integrity');
        expect(ledger).toHaveTextContent('Out of balance');
        expect(ledger).toHaveTextContent('Critical');
        expect(
            within(ledger).getByRole('link', { name: /Open Reconciliation/ }),
        ).toHaveAttribute('href', '/admin/reconciliation');
        const queue = card('Underwriting queue');
        expect(queue).toHaveTextContent('A backlog is building.');
        expect(
            within(queue).getByRole('link', { name: /Open the queue/ }),
        ).toHaveAttribute('href', '/admin/applications');
        expect(card('Event throughput')).toHaveTextContent(
            'No actions were recorded in the last hour.',
        );
    });

    it('counts open breaks while the books still balance', () => {
        render(
            <AdminSystemHealth
                {...props({
                    overall: { critical: 0, watch: 1 },
                    signals: [
                        {
                            key: 'ledger',
                            status: 'watch',
                            facts: { accounts: 1, unmatched: 0, breaks: 3 },
                            action: null,
                        },
                    ],
                })}
            />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            'Operating with warnings',
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            '1 signal needs a look',
        );
        expect(card('Ledger integrity')).toHaveTextContent('3 open breaks');
        expect(card('Ledger integrity')).toHaveTextContent(
            '1 provider account reconciled',
        );
    });

    it('filters the signals by the search, and says when none match', () => {
        const { rerender } = render(
            <AdminSystemHealth {...props({ search: 'not tracked' })} />,
        );

        expect(screen.getAllByRole('article')).toHaveLength(5);

        rerender(<AdminSystemHealth {...props({ search: 'zzz' })} />);

        expect(screen.queryAllByRole('article')).toHaveLength(0);
        expect(screen.getAllByRole('status')[1]).toHaveTextContent('zzz');
    });
});
