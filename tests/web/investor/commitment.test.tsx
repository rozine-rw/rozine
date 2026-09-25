import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { POLL_INTERVAL_MS, POLL_LIMIT } from '@/hooks/use-bounded-poll';
import InvestorCommitment from '@/pages/investor/commitment';
import type { C3InvestorCommitmentProps } from '@/types/investor';
import cancelUnconfirmedFixture from '../../../resources/fixtures/ui/investor-commitment-cancel-unconfirmed.json';
import byBusinessFixture from '../../../resources/fixtures/ui/investor-commitment-cancelled-by-business.json';
import cancelledFixture from '../../../resources/fixtures/ui/investor-commitment-cancelled.json';
import confirmedFixture from '../../../resources/fixtures/ui/investor-commitment-confirmed.json';
import expiredFixture from '../../../resources/fixtures/ui/investor-commitment-expired.json';
import failedClosingFixture from '../../../resources/fixtures/ui/investor-commitment-failed-closing.json';
import awaitingFixture from '../../../resources/fixtures/ui/investor-commitment-funded-awaiting.json';
import pendingFixture from '../../../resources/fixtures/ui/investor-commitment-funded-pending.json';
import unknownFixture from '../../../resources/fixtures/ui/investor-commitment-funded-unknown.json';
import issuedFixture from '../../../resources/fixtures/ui/investor-commitment-issued.json';
import minimalFixture from '../../../resources/fixtures/ui/investor-commitment-live-minimal.json';
import lostFixture from '../../../resources/fixtures/ui/investor-commitment-visibility-lost.json';
import { answers, inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown } = confirmedFixture) =>
    structuredClone(fixture.props) as C3InvestorCommitmentProps;

beforeEach(() => {
    resetInertia();
    setWide(false);
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Commitment', () => {
    it('shows a confirmed commitment with its units, undated rights, terms and receipt', () => {
        render(<InvestorCommitment {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Commitment');
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/investor-portfolio-awaiting-issue',
        );
        expect(
            screen.getByText('Committed — issued after disbursement'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                "The raise is still open. You can cancel, fee-free, until it's fully funded.",
            ),
        ).toBeInTheDocument();

        const page = document.body;

        expect(page).toHaveTextContent('Units6 notes · #1201–#1206');
        expect(page).toHaveTextContent('PrincipalRWF 30,000');
        expect(page).toHaveTextContent('Terms13.5% return · 6 months');
        expect(page).toHaveTextContent(
            'Maturity dateSet when notes are issued',
        );
        expect(page).toHaveTextContent(
            'Policy · disclosuresynthetic-primary-policy-0 · disclosure-2026-09.1',
        );
        expect(page).toHaveTextContent('Total returnRWF 4,050');

        const receipt = screen.getByRole('region', {
            name: 'CONFIRMATION RECEIPT',
        });

        expect(receipt).toHaveTextContent('AmountRWF 30,000');
        expect(receipt).toHaveTextContent('Recorded23 Sept 2026 · 09:02');
        expect(receipt).toHaveTextContent('ReferenceRZ-CMT-CMT1');
        expect(
            screen.queryByRole('region', { name: 'REFUND RECEIPT' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'View holding' }),
        ).not.toBeInTheDocument();
    });

    it('cancels a confirmed commitment only after a second step, fee-free', async () => {
        const user = userEvent.setup();

        render(<InvestorCommitment {...props()} />);
        await user.click(
            screen.getByRole('button', { name: 'Cancel commitment' }),
        );

        const step = screen.getByRole('group', {
            name: 'Cancel this commitment?',
        });

        expect(step).toHaveTextContent(
            'Your principal goes back to Available in full, with no fee, and these notes are released.',
        );
        await user.click(within(step).getByRole('button', { name: 'Keep it' }));
        expect(
            screen.queryByRole('group', { name: 'Cancel this commitment?' }),
        ).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Cancel commitment' }),
        );
        inertia.queue.push(
            answers({
                status: 'completed',
                code: 'PRIMARY_COMMITMENT_CANCELLED',
                data: {
                    receipt: { code: 'COMMITMENT_REFUNDED' },
                    current: null,
                    next: {
                        url: '/preview/investor-commitment-cancelled',
                        method: 'get',
                    },
                },
            }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Yes, cancel and refund' }),
        );
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/investor-commitment-cancelled',
            method: 'post',
            body: {
                identity_context_revision: 5,
                commitment_id: 'cmt1_01k6r3',
                expected_commitment_revision: 2,
            },
        });
        expect(inertia.visit).toHaveBeenCalledWith({
            url: '/preview/investor-commitment-cancelled',
            method: 'get',
        });
    });

    it('offers no cancel once the server stops listing it', () => {
        const data = props();

        data.allowed_actions = [];
        render(<InvestorCommitment {...data} />);

        expect(
            screen.queryByRole('button', { name: 'Cancel commitment' }),
        ).not.toBeInTheDocument();
    });

    it('holds an unconfirmed cancel and keeps it from being sent again', async () => {
        const user = userEvent.setup();

        render(<InvestorCommitment {...props(cancelUnconfirmedFixture)} />);

        expect(screen.getByText('Not yet confirmed')).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Cancel commitment' }),
        );
        expect(
            screen.getByRole('button', { name: 'Yes, cancel and refund' }),
        ).toBeDisabled();
    });

    it('shows a funded commitment locked, awaiting the payout', () => {
        render(<InvestorCommitment {...props(awaitingFixture)} />);

        expect(
            screen.getByText('Fully funded — awaiting payout'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Cancel commitment' }),
        ).not.toBeInTheDocument();
        expect(inertia.reload).not.toHaveBeenCalled();
    });

    it('keeps a pending or unknown payout “not yet confirmed” and polls it boundedly', () => {
        vi.useFakeTimers();
        const { unmount } = render(
            <InvestorCommitment {...props(pendingFixture)} />,
        );

        expect(
            screen.getByText('Payout not yet confirmed'),
        ).toBeInTheDocument();
        unmount();

        render(<InvestorCommitment {...props(unknownFixture)} />);
        expect(
            screen.getByText('Payout not yet confirmed — checking'),
        ).toBeInTheDocument();
        expect(document.body).toHaveTextContent(
            "It isn't paid, failed or refunded",
        );
        expect(document.body).not.toHaveTextContent(/provider_reference|op_/u);

        for (let poll = 0; poll < POLL_LIMIT; poll += 1) {
            act(() => vi.advanceTimersByTime(POLL_INTERVAL_MS));
        }

        expect(inertia.reload).toHaveBeenCalledTimes(POLL_LIMIT);
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['commitment', 'allowed_actions'],
        });
        expect(
            screen.getByText(/We've stopped checking automatically/u),
        ).toBeInTheDocument();
    });

    it('shows each refunded ending with its refund receipt', () => {
        const { unmount } = render(
            <InvestorCommitment {...props(cancelledFixture)} />,
        );

        expect(screen.getByText('Cancelled — refunded')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Cancelled by you. Your principal was refunded in full, with no fee.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('region', { name: 'REFUND RECEIPT' }),
        ).toHaveTextContent('RZ-RFD-RFD1');
        unmount();

        const { unmount: second } = render(
            <InvestorCommitment {...props(byBusinessFixture)} />,
        );

        expect(
            screen.getByText(/Cancelled by the business/u),
        ).toBeInTheDocument();
        second();

        const { unmount: third } = render(
            <InvestorCommitment {...props(expiredFixture)} />,
        );

        expect(screen.getByText("Didn't fill — refunded")).toBeInTheDocument();
        third();

        const data = props(failedClosingFixture);

        render(<InvestorCommitment {...data} />);
        expect(screen.getByText('Closed — refunded')).toBeInTheDocument();
        expect(
            screen.getByText(/A final check before payout didn't pass/u),
        ).toBeInTheDocument();
    });

    it('links an issued commitment to its holding', () => {
        render(<InvestorCommitment {...props(issuedFixture)} />);

        expect(screen.getByText('Issued')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'View holding' }),
        ).toHaveAttribute('href', '/preview/investor-holding-issued');
    });

    it('shows only a scoped refusal once the investor may no longer read the commitment', () => {
        const { unmount } = render(
            <InvestorCommitment {...props(lostFixture)} />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            'You can no longer view this record.',
        );
        expect(
            screen.getByRole('link', { name: 'Back to portfolio' }),
        ).toHaveAttribute('href', '/preview/investor-portfolio');
        expect(document.body).not.toHaveTextContent(/GreenLeaf|RWF/u);
        unmount();

        const data = props(lostFixture);

        data.refusal = null;
        render(<InvestorCommitment {...data} />);
        expect(screen.getByRole('alert')).toHaveTextContent(
            'You can no longer view this record.',
        );
    });

    it('renders the live-minimal shape on a wide screen', () => {
        setWide(true);
        render(<InvestorCommitment {...props(minimalFixture)} />);

        expect(
            screen.getByText('Committed — issued after disbursement'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Cancel commitment' }),
        ).not.toBeInTheDocument();
    });
});
