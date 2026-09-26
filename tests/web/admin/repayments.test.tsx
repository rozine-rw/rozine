import { render, screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminRepayments from '@/pages/admin/repayments';
import type { AdminRepaymentsProps, RepaymentDetail } from '@/types/admin';
import allocatedFixture from '../../../resources/fixtures/ui/admin-repayments-allocated.json';
import exceptionFixture from '../../../resources/fixtures/ui/admin-repayments-exception-mismatch.json';
import liveFixture from '../../../resources/fixtures/ui/admin-repayments-live-minimal.json';
import receivedFixture from '../../../resources/fixtures/ui/admin-repayments-received.json';
import requeryFixture from '../../../resources/fixtures/ui/admin-repayments-requery-unknown.json';
import unconfirmedFixture from '../../../resources/fixtures/ui/admin-repayments-unconfirmed.json';
import queueFixture from '../../../resources/fixtures/ui/admin-repayments.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminRepaymentsProps;

/** The fixture's open repayment, for tests that reshape it. */
const opened = (page: AdminRepaymentsProps): RepaymentDetail => {
    if (page.repayment === null) {
        throw new Error('the fixture opens a repayment');
    }

    return page.repayment;
};

const drawer = () => screen.getByRole('dialog', { name: /^Repayment / });

const section = (name: string) =>
    within(drawer()).getByRole('region', { name });

const rowOf = (reference: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(reference));

    return row;
};

beforeEach(resetInertia);

describe('Repayments queue', () => {
    it('lists each repayment with its source, DPD and state, read-only', () => {
        render(<AdminRepayments {...props(queueFixture)} />);

        expect(
            screen.getByRole('link', { name: 'Repayments' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(screen.getByText('3 due today')).toBeInTheDocument();
        expect(screen.getByText('1 overdue')).toBeInTheDocument();
        expect(screen.getByText('1 exception')).toBeInTheDocument();
        expect(rowOf('RPY-2026-0301')).toHaveTextContent('Business wallet');
        expect(rowOf('RPY-2026-0301')).toHaveTextContent(
            'Allocated · reconciled',
        );
        expect(rowOf('RPY-2026-0302')).toHaveTextContent(
            'Received · allocating',
        );
        expect(rowOf('RPY-2026-0303')).toHaveTextContent('Inbound receipt');
        expect(rowOf('RPY-2026-0303')).toHaveTextContent('Exception · blocked');
        expect(
            screen.getByRole('link', { name: 'Open RPY-2026-0303' }),
        ).toHaveTextContent('Inspect');
        expect(
            screen.getByRole('link', { name: 'Open RPY-2026-0301' }),
        ).toHaveTextContent('Open');
        expect(rowOf('RPY-2026-0301')).toHaveTextContent('RWF 3.5M');
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /requery|ask/i }),
        ).not.toBeInTheDocument();
    });

    it('shows a dash for an unknown DPD, the empty and search states, and a further page', () => {
        const page = props(queueFixture);

        page.repayments[0].dpd_at_receipt = null;
        const { rerender } = render(<AdminRepayments {...page} />);

        expect(rowOf('RPY-2026-0301')).toHaveTextContent('—');

        rerender(
            <AdminRepayments
                {...page}
                repayments={[]}
                search="Kivu"
                counts={{ due_today: 0, overdue: 0, exceptions: 0 }}
                pagination={{
                    next: { url: '/preview/admin-repayments', method: 'get' },
                }}
            />,
        );

        expect(screen.getByText('No repayments yet')).toBeInTheDocument();
        expect(screen.getByText(/Kivu/)).toBeInTheDocument();
        expect(screen.queryByText(/due today/)).not.toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Older repayments' }),
        ).toHaveAttribute('href', '/preview/admin-repayments');
    });
});

describe('A repayment', () => {
    it('shows an allocated wallet repayment: reconciled at the tolerance, balanced and posted', () => {
        render(<AdminRepayments {...props(allocatedFixture)} />);

        expect(drawer()).toHaveTextContent('Umucyo Foods · Cold-Chain Hub');
        expect(section('Source')).toHaveTextContent('FromBusiness wallet');
        expect(section('Source')).toHaveTextContent(
            'no provider is involved and no staff step is needed',
        );

        const reconciliation = section('Reconciliation');

        expect(reconciliation).toHaveTextContent('Matched · reconciled');
        expect(reconciliation).toHaveTextContent('ExpectedRWF 3,465,000');
        expect(reconciliation).toHaveTextContent('DifferenceRWF 0');
        expect(reconciliation).toHaveTextContent('ToleranceRWF 0');
        expect(
            within(reconciliation).queryByRole('alert'),
        ).not.toBeInTheDocument();

        const servicing = section('Note servicing');

        expect(servicing).toHaveTextContent('Before this receipt');
        expect(servicing).toHaveTextContent('StateDue today');
        expect(servicing).toHaveTextContent('StateCurrent');
        expect(servicing).toHaveTextContent('OutstandingRWF 10,395,000');

        const allocation = section('Allocation');

        expect(allocation).toHaveTextContent('Balanced');
        expect(allocation).toHaveTextContent('Receipt RWF 3,465,000');
        expect(
            within(allocation).getByRole('table', {
                name: 'What the receipt paid',
            }),
        ).toHaveTextContent(
            'Principal· instalment 41310-NOTE-PRNRWF 3,000,000',
        );
        expect(
            within(allocation).getByRole('table', { name: 'Where it went' }),
        ).toHaveTextContent(
            'Investors, gross· instalment 4· 318 holdings2200-INV-ENTRWF 3,405,000',
        );
        expect(allocation).toHaveTextContent(
            'Distributed to 318 holdings by largest remainder, record date 23 Jan 2027.',
        );
        expect(allocation).toHaveTextContent('Posted 2027-01-23 09:12:06');
        expect(
            within(allocation).getByRole('link', { name: 'Open the postings' }),
        ).toHaveAttribute('href', '/preview/admin-ledger-entry');
        expect(
            within(section('Receipts')).getAllByText('Receipt'),
        ).toHaveLength(2);
        expect(section('Receipts')).toHaveTextContent('REPAYMENT_ALLOCATED');
        expect(
            within(drawer()).getByRole('link', { name: 'Open in the ledger' }),
        ).toHaveAttribute('href', '/preview/admin-ledger');
        expect(
            within(drawer()).getByRole('link', { name: 'Open the business' }),
        ).toHaveAttribute('href', '/preview/admin-businesses');
        expect(within(drawer()).queryAllByRole('button')).toEqual([]);
    });

    it('keeps a received repayment as allocating, with nothing posted yet', () => {
        render(<AdminRepayments {...props(receivedFixture)} />);

        expect(section('Allocation')).toHaveTextContent(
            'Not allocated yet: the receipt is recorded and the allocation has not posted.',
        );
        expect(section('Note servicing')).toHaveTextContent('Not posted yet.');
        expect(
            within(drawer()).queryByRole('link', {
                name: 'Open in the ledger',
            }),
        ).not.toBeInTheDocument();
    });

    it('blocks an exception and never calls it reconciled', () => {
        render(<AdminRepayments {...props(exceptionFixture)} />);
        const reconciliation = section('Reconciliation');

        expect(reconciliation).toHaveTextContent(
            'Exception · blocked, not reconciled',
        );
        expect(within(reconciliation).getByRole('alert')).toHaveTextContent(
            'This repayment is blocked',
        );
        expect(
            within(reconciliation).getByRole('list', { name: 'Causes' }),
        ).toHaveTextContent('AMOUNT_MISMATCH');
        expect(reconciliation).toHaveTextContent('DifferenceRWF 115,000');
        expect(reconciliation).not.toHaveTextContent('Matched');
        expect(section('Source')).toHaveTextContent('Succeeded · verified');
        expect(section('Source')).toHaveTextContent('MM-270122-40517');
        expect(within(drawer()).queryAllByRole('button')).toEqual([]);
    });

    it('shows an unbalanced allocation and unposted lines as the core states them', () => {
        const page = props(allocatedFixture);
        const allocation = opened(page).allocation;

        if (allocation === null) {
            throw new Error('the fixture is allocated');
        }

        allocation.balanced = false;
        allocation.posted_at = null;
        allocation.ledger = null;
        allocation.paid = [
            {
                kind: 'unapplied',
                instalment_index: null,
                amount: { currency: 'RWF', amount: '1000' },
                account_code: '2500-BIZ-UNA',
                holders: null,
            },
        ];
        opened(page).servicing_before.dpd = null;
        render(<AdminRepayments {...page} />);
        const panel = section('Allocation');

        expect(panel).toHaveTextContent('Not balanced');
        expect(panel).toHaveTextContent('Posted —');
        expect(panel).toHaveTextContent('Unapplied2500-BIZ-UNARWF 1,000');
        expect(
            within(panel).queryByRole('link', { name: 'Open the postings' }),
        ).not.toBeInTheDocument();
        expect(section('Note servicing')).toHaveTextContent('DPD—');
    });

    it('renders the live-minimal contract with nothing offered', () => {
        render(<AdminRepayments {...props(liveFixture)} />);

        expect(within(drawer()).queryAllByRole('button')).toEqual([]);
        expect(screen.getByText('Nothing recorded yet.')).toBeInTheDocument();
        expect(
            within(drawer()).queryByRole('region', { name: 'Receipts' }),
        ).not.toBeInTheDocument();
        expect(
            within(drawer()).queryByRole('link', { name: 'Open the business' }),
        ).not.toBeInTheDocument();
    });
});

describe('Requery', () => {
    it('is offered only when the server lists it and gives a route', () => {
        const page = props(requeryFixture);

        opened(page).actions = {};
        render(<AdminRepayments {...page} />);

        expect(
            within(drawer()).queryByRole('button', {
                name: 'Ask the provider again',
            }),
        ).not.toBeInTheDocument();
    });

    it('asks about the same inbound receipt with a reason and follows the server', async () => {
        const next = { url: '/preview/admin-repayments', method: 'get' };

        inertia.queue.push(
            answers({
                operation_id: '01k7c0000000000000000000rq',
                status: 'completed',
                code: 'PROVIDER_QUERY_RECORDED',
                data: { receipt: {}, current: null, next },
                revision: 3,
                policy_version: 'synthetic-servicing-policy-0',
                recorded_at: '2027-01-23T10:00:02+02:00',
                server_time: '2027-01-23T10:00:03+02:00',
                allowed_actions: [],
                field_errors: {},
            }),
        );
        const { user } = renderWithUser(
            <AdminRepayments {...props(requeryFixture)} />,
        );

        expect(section('Source')).toHaveTextContent(
            'Unknown · not yet confirmed',
        );
        expect(section('Reconciliation')).toHaveTextContent(
            'Not reconciled yet',
        );

        await user.click(
            within(drawer()).getByRole('button', {
                name: 'Ask the provider again',
            }),
        );
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        await user.click(
            within(drawer()).getByRole('button', {
                name: 'Ask the provider again',
            }),
        );
        expect(
            screen.getByText(
                'This asks the provider about the same inbound receipt. It never collects the money again.',
            ),
        ).toBeInTheDocument();
        await user.type(screen.getByRole('textbox'), 'Bank shows the credit.');
        await user.click(
            screen.getByRole('button', { name: 'Ask the provider' }),
        );

        await waitFor(() => expect(inertia.visits).toEqual([next]));
        expect(inertia.calls).toHaveLength(1);
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/admin-repayments-requery-unknown',
            method: 'post',
            body: {
                repayment_id: 'rpy_0304',
                expected_revision: 2,
                reason: 'Bank shows the credit.',
            },
        });
    });

    it('looks up an uncertain answer and never resends it on its own', async () => {
        inertia.queue.push(fails(503), fails(502));
        const { user } = renderWithUser(
            <AdminRepayments {...props(requeryFixture)} />,
        );

        await user.click(
            within(drawer()).getByRole('button', {
                name: 'Ask the provider again',
            }),
        );
        await user.type(screen.getByRole('textbox'), 'Check again.');
        await user.click(
            screen.getByRole('button', { name: 'Ask the provider' }),
        );

        await screen.findByText('Not yet confirmed');
        expect(inertia.calls).toHaveLength(2);
        expect(inertia.calls[1].method).toBe('get');
        expect(
            screen.getByRole('button', { name: 'Ask the provider' }),
        ).toBeDisabled();
    });

    it('holds a seeded unconfirmed requery instead of offering another', () => {
        render(<AdminRepayments {...props(unconfirmedFixture)} />);

        expect(
            within(drawer()).getByText('Not yet confirmed'),
        ).toBeInTheDocument();
        expect(
            within(drawer()).getByRole('button', {
                name: 'Ask the provider again',
            }),
        ).toBeDisabled();
    });
});
