import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminComplaints from '@/pages/admin/complaints';
import type { AdminComplaintsProps, ComplaintRow } from '@/types/admin';
import coverageFixture from '../../../resources/fixtures/ui/admin-coverage.json';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const ROWS: ComplaintRow[] = [
    {
        id: '01k7a0c0000000000000000001',
        reference: 'CMP-00000001',
        customer: 'Aline Uwase',
        role: 'investor',
        status: 'awaiting',
        late: 'acknowledge',
        opened_at: '2026-10-14T06:00:00+00:00',
        acknowledge_by: '2026-10-14T10:00:00+00:00',
        resolve_by: '2026-10-16T06:00:00+00:00',
        messages: 1,
        link: {
            url: '/admin/complaints?complaint=01k7a0c0000000000000000001',
            method: 'get',
        },
    },
    {
        id: '01k7a0c0000000000000000002',
        reference: 'CMP-00000002',
        customer: 'Jean Habimana',
        role: 'investor',
        status: 'answered',
        late: null,
        opened_at: '2026-10-14T07:00:00+00:00',
        acknowledge_by: '2026-10-14T11:00:00+00:00',
        resolve_by: '2026-10-16T07:00:00+00:00',
        messages: 2,
        link: {
            url: '/admin/complaints?complaint=01k7a0c0000000000000000002',
            method: 'get',
        },
    },
];

const page = (
    complaint: AdminComplaintsProps['complaint'] = null,
): AdminComplaintsProps => {
    const frame = structuredClone(coverageFixture.props) as unknown as Record<
        string,
        unknown
    >;

    delete frame.districts;

    return {
        ...(frame as unknown as AdminComplaintsProps),
        contract_version: 'staff-complaints-v1',
        nav: {
            ...(frame.nav as AdminComplaintsProps['nav']),
            complaints: { url: '/admin/complaints', method: 'get' },
        },
        search: '',
        stats: [
            { key: 'unresolved', value: { kind: 'count', value: 2 } },
            { key: 'awaiting', value: { kind: 'count', value: 1 } },
            { key: 'late', value: { kind: 'count', value: 1 } },
        ],
        complaints: ROWS,
        complaint,
    };
};

const OPEN: NonNullable<AdminComplaintsProps['complaint']> = {
    id: ROWS[0].id,
    reference: 'CMP-00000001',
    customer: 'Aline Uwase',
    role: 'investor',
    status: 'awaiting',
    late: 'acknowledge',
    opened_at: ROWS[0].opened_at,
    acknowledge_by: ROWS[0].acknowledge_by,
    resolve_by: ROWS[0].resolve_by,
    messages: [
        {
            id: 'm1',
            author: 'customer',
            body: 'I was charged twice.',
            resolves: false,
            at: ROWS[0].opened_at,
        },
    ],
    close: { url: '/admin/complaints', method: 'get' },
    answer: {
        url: '/admin/complaints/01k7a0c0000000000000000001/answers',
        method: 'post',
    },
};

beforeEach(resetInertia);

describe('Complaints', () => {
    it('lists every complaint with its status and the clock it is past or due', () => {
        render(<AdminComplaints {...page()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Complaints');
        expect(
            within(
                screen.getByRole('navigation', { name: 'Console navigation' }),
            ).getByRole('link', { name: 'Compliance' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(screen.getByText('Past a clock')).toBeInTheDocument();

        const late = screen
            .getAllByRole('row')
            .find((row) => within(row).queryByText('CMP-00000001'));

        expect(late).toHaveTextContent('Aline Uwase · Investor');
        expect(late).toHaveTextContent('Awaiting');
        expect(late).toHaveTextContent('Not acknowledged within 4 hours');
        expect(
            screen.getByRole('link', { name: 'Open complaint CMP-00000002' }),
        ).toHaveAttribute('href', ROWS[1].link.url);
        expect(screen.getByText(/Resolve by/u)).toBeInTheDocument();
    });

    it('answers the open complaint, as a resolution when ticked, with a fresh request', async () => {
        const user = userEvent.setup();

        render(<AdminComplaints {...page(OPEN)} />);

        const drawer = screen.getByRole('dialog', {
            name: 'Complaint CMP-00000001',
        });

        expect(
            within(drawer).getByText('I was charged twice.'),
        ).toBeInTheDocument();
        await user.type(
            within(drawer).getByLabelText('Your answer'),
            'Refunded to your wallet.',
        );
        await user.click(
            within(drawer).getByLabelText('This answer resolves the complaint'),
        );
        await user.click(
            within(drawer).getByRole('button', { name: 'Send answer' }),
        );

        expect(inertia.posts).toHaveLength(1);
        expect(inertia.posts[0].url).toBe(OPEN.answer?.url);
        expect(inertia.posts[0].data).toMatchObject({
            body: 'Refunded to your wallet.',
            resolves: true,
        });
        expect(inertia.posts[0].data.request_id).toMatch(
            /^[0-9a-f-]{36}$/u,
        );
    });

    it('offers no answer once a complaint is resolved', () => {
        render(
            <AdminComplaints
                {...page({
                    ...OPEN,
                    status: 'resolved',
                    late: null,
                    answer: null,
                })}
            />,
        );

        expect(
            screen.getByText(
                'This complaint is resolved. Nothing more can be added.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Send answer' }),
        ).not.toBeInTheDocument();
    });
});
