import { act, fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { POLL_INTERVAL_MS } from '@/hooks/use-bounded-poll';
import InvestorWallet from '@/pages/investor/wallet';
import type { C3InvestorWalletProps } from '@/types/investor';
import emptyFixture from '../../../resources/fixtures/ui/investor-wallet-empty.json';
import restrictedFixture from '../../../resources/fixtures/ui/investor-wallet-restricted.json';
import pendingFixture from '../../../resources/fixtures/ui/investor-wallet-withdraw-pending.json';
import withdrawFixture from '../../../resources/fixtures/ui/investor-wallet-withdraw.json';
import receiptFixture from '../../../resources/fixtures/ui/investor-wallet-withdrawal-receipt.json';
import { answers, inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const wallet = (fixture: { props: unknown } = withdrawFixture) =>
    structuredClone(fixture.props) as C3InvestorWalletProps;

beforeEach(() => {
    resetInertia();
    setWide(false);
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Wallet withdrawals (LED-R04)', () => {
    it('confirms a paid-out withdrawal at what the account received after the charge', async () => {
        const props = wallet();
        const quote = props.funding.withdrawal!.quote!;

        quote.charge = { currency: 'RWF', amount: '1000' };
        quote.received = { currency: 'RWF', amount: '99000' };
        render(<InvestorWallet {...props} />);

        const panel = screen.getByRole('form', { name: 'Withdraw' });

        fireEvent.click(within(panel).getByRole('radio', { name: /Airtel/u }));
        inertia.queue.push(
            answers({
                status: 'completed',
                code: 'WITHDRAWAL_RECORDED',
                data: {
                    receipt: { code: 'WITHDRAWAL_RECORDED' },
                    current: { state: 'succeeded' },
                    next: null,
                },
            }),
        );
        fireEvent.submit(panel);
        await act(async () => {
            await Promise.resolve();
        });

        expect(within(panel).getByRole('status')).toHaveTextContent(
            /^RWF 99,000 was paid out to .*Airtel/u,
        );
    });

    it('quotes a withdrawal to a verified account and records it, paying nothing out yet', async () => {
        vi.useFakeTimers();
        const { rerender } = render(<InvestorWallet {...wallet()} />);

        const panel = screen.getByRole('form', { name: 'Withdraw' });

        expect(screen.getByRole('link', { name: 'Withdraw' })).toHaveAttribute(
            'aria-current',
            'true',
        );
        expect(within(panel).getByLabelText('AMOUNT')).toHaveValue('100,000');
        expect(panel).toHaveTextContent('Available to withdraw: RWF 1,253,485');
        expect(panel).toHaveTextContent('Processing feeRWF 0');
        expect(panel).toHaveTextContent('You receiveRWF 100,000');
        expect(panel).toHaveTextContent(
            'Withdraw between RWF 1,000 and RWF 2,000,000 at a time. Terms LED-R04/2026-10.',
        );
        expect(within(panel).getByText('PAYMENT METHOD')).toBeInTheDocument();
        fireEvent.click(within(panel).getByRole('button', { name: '50K' }));
        expect(within(panel).getByLabelText('AMOUNT')).toHaveValue('50,000');
        expect(within(panel).getByTestId('withdraw-amount-field')).toHaveClass(
            'rz-prefixed-field',
        );
        expect(
            within(
                within(panel).getByTestId('withdraw-amount-field'),
            ).getByLabelText('AMOUNT'),
        ).toBeInTheDocument();

        fireEvent.change(within(panel).getByLabelText('AMOUNT'), {
            target: { value: '40,000' },
        });
        expect(
            within(panel).getByRole('button', { name: 'Confirm withdrawal' }),
        ).toBeDisabled();
        void act(() => vi.advanceTimersByTime(300));
        expect(inertia.reload).toHaveBeenLastCalledWith(
            expect.objectContaining({
                only: ['funding'],
                data: { kind: 'withdraw', amount: '40000' },
            }),
        );

        const reload = inertia.reload.mock.lastCall?.[0] as {
            onSuccess: (page: { props: C3InvestorWalletProps }) => void;
            onFinish: () => void;
        };

        const quoted = wallet();

        quoted.funding.withdrawal!.quote!.amount.amount = '40000';
        quoted.funding.withdrawal!.quote!.received.amount = '40000';
        rerender(<InvestorWallet {...quoted} />);
        act(() => {
            reload.onSuccess({ props: quoted });
            reload.onFinish();
        });
        fireEvent.click(within(panel).getByRole('radio', { name: /Airtel/u }));

        inertia.queue.push(
            answers({
                status: 'completed',
                code: 'WITHDRAWAL_RECORDED',
                data: {
                    receipt: { code: 'WITHDRAWAL_RECORDED' },
                    current: null,
                    next: {
                        url: '/preview/investor-wallet-withdraw-pending',
                        method: 'get',
                    },
                },
            }),
        );
        fireEvent.submit(panel);

        expect(
            within(panel).getByRole('button', { name: 'Sending withdrawal…' }),
        ).toBeDisabled();
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/investor-wallet-withdraw-pending',
            method: 'post',
            body: {
                identity_context_revision: 5,
                amount: { currency: 'RWF', amount: '40000' },
                method_id: 'airtel-1',
            },
        });
        await act(async () => {
            await Promise.resolve();
        });
        expect(inertia.visit).toHaveBeenCalledWith(
            {
                url: '/preview/investor-wallet-withdraw-pending',
                method: 'get',
            },
            { onFinish: expect.any(Function) },
        );
    });

    it('states a quote refusal and keeps the withdrawal closed', () => {
        const props = wallet();

        props.funding.withdrawal!.quote = {
            amount: { currency: 'RWF', amount: '9000000' },
            charge: { currency: 'RWF', amount: '0' },
            received: { currency: 'RWF', amount: '9000000' },
            refusal: 'INSUFFICIENT_AVAILABLE_FUNDS',
        };
        render(<InvestorWallet {...props} />);

        expect(screen.getByRole('alert')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Confirm withdrawal' }),
        ).toBeDisabled();
    });

    it('names an unverified account refusal and an empty quote plainly', () => {
        const props = wallet();

        props.funding.withdrawal!.quote = null;
        const { unmount } = render(<InvestorWallet {...props} />);

        expect(
            screen.getByRole('form', { name: 'Withdraw' }),
        ).toHaveTextContent('You receive—');
        unmount();

        props.funding.withdrawal!.quote = {
            amount: { currency: 'RWF', amount: '5000' },
            charge: { currency: 'RWF', amount: '0' },
            received: { currency: 'RWF', amount: '5000' },
            refusal: 'WITHDRAWAL_METHOD_UNVERIFIED',
        };
        render(<InvestorWallet {...props} />);
        expect(screen.getByRole('alert')).toHaveTextContent(
            'Choose a verified account to withdraw to.',
        );
    });

    it('shows a field error the server returns', async () => {
        const user = userEvent.setup();

        inertia.httpErrors = { amount: 'Withdraw at least RWF 1000.' };
        inertia.queue.push(() => Promise.resolve(undefined));
        render(<InvestorWallet {...wallet()} />);
        await user.click(
            screen.getByRole('button', { name: 'Confirm withdrawal' }),
        );
        expect(
            await screen.findByText('Withdraw at least RWF 1000.'),
        ).toBeInTheDocument();
    });

    it('offers no form while the server withholds withdrawal, and closes back to the wallet', () => {
        const props = wallet(restrictedFixture);

        props.funding.kind = 'withdraw';
        render(<InvestorWallet {...props} />);

        const panel = screen.getByRole('region', { name: 'Withdraw' });

        expect(panel).toHaveTextContent(
            "Withdrawals aren't open on this account yet. They need a verified payout account under Profile → Linked accounts, and open once the account is cleared for money movement.",
        );
        expect(
            within(panel).queryByRole('button', { name: 'Confirm withdrawal' }),
        ).not.toBeInTheDocument();
        expect(
            within(panel).getByRole('link', { name: 'Close' }),
        ).toHaveAttribute('href', '/preview/investor-wallet');
    });

    it('offers no form when no withdrawal provider is configured', () => {
        const props = wallet();

        props.funding.withdrawal = null;
        render(<InvestorWallet {...props} />);

        expect(
            screen.getByRole('region', { name: 'Withdraw' }),
        ).toHaveTextContent(
            "Withdrawals aren't open on this account yet. They need a verified payout account under Profile → Linked accounts, and open once the account is cleared for money movement.",
        );
    });

    it('keeps a withdrawal on its way out of the total and polls until it settles', () => {
        vi.useFakeTimers();
        render(<InvestorWallet {...wallet(pendingFixture)} />);

        const balance = screen.getByRole('region', {
            name: 'Available balance',
        });

        expect(balance).toHaveTextContent('RWF 1,633,485');
        expect(balance).toHaveTextContent('RWF 100,000 withdrawal pending');

        const list = screen.getByRole('region', { name: 'Withdrawals' });
        const [waiting, paid] = within(list).getAllByRole('listitem');

        expect(waiting).toHaveTextContent('Withdrawal · MTN MoMo');
        expect(waiting).toHaveTextContent('Sending');
        expect(waiting).toHaveTextContent('RWF 100,000');
        expect(paid).toHaveTextContent('Paid out');
        expect(within(paid).getByRole('link')).toHaveAttribute(
            'href',
            '/preview/investor-wallet-withdrawal-receipt',
        );

        void act(() => vi.advanceTimersByTime(POLL_INTERVAL_MS));
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['wallet', 'deposits', 'withdrawals', 'history'],
        });
    });

    it('opens a paid withdrawal with its request and payout receipts', () => {
        render(<InvestorWallet {...wallet(receiptFixture)} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent('Withdrawal · MTN MoMo');
        expect(sheet).toHaveTextContent('RWF 200,000');
        expect(sheet).toHaveTextContent('StatusPaid out');
        expect(sheet).toHaveTextContent('Provider chargeRWF 0');
        expect(sheet).toHaveTextContent('You receiveRWF 200,000');
        expect(sheet).toHaveTextContent('RZX-WD00000001');
        expect(sheet).toHaveTextContent('Payout receipt');
        expect(sheet).toHaveTextContent('RZW-WD00000001');
        expect(sheet).toHaveTextContent('LED-R04/2026-10');
    });

    it('reads a returned withdrawal as back in the wallet', () => {
        const props = wallet(receiptFixture);
        const receipt =
            props.receipt as C3InvestorWalletProps['withdrawals'][number];

        receipt.state = 'failed';
        receipt.settled_receipt!.code = 'WITHDRAWAL_RETURNED';
        render(<InvestorWallet {...props} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent(
            "Didn't go through — returned to your wallet",
        );
        expect(sheet).toHaveTextContent('Return receipt');
        expect(sheet).not.toHaveTextContent('Payout receipt');
    });

    it('reads an unsettled withdrawal as held until the provider confirms', () => {
        const props = wallet(pendingFixture);

        props.receipt = structuredClone(props.withdrawals[0]);
        render(<InvestorWallet {...props} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent('StatusSending');
        expect(sheet).toHaveTextContent(
            'Held for this withdrawal until the provider confirms.',
        );
        expect(sheet).not.toHaveTextContent(/Payout receipt|RZW-/u);
    });

    it('labels paid and returned withdrawals in the cash history', () => {
        const props = wallet(pendingFixture);
        const [first] = props.history.items;

        props.history.items = [
            {
                ...first,
                id: 'payout-1',
                kind: 'withdrawal',
                direction: 'out',
                counterparty: 'MTN MoMo',
            },
            {
                ...first,
                id: 'return-1',
                kind: 'withdrawal',
                direction: 'in',
                counterparty: 'MTN MoMo',
            },
        ] as C3InvestorWalletProps['history']['items'];
        render(<InvestorWallet {...props} />);

        const history = screen.getByRole('region', {
            name: 'Transaction history',
        });

        expect(history).toHaveTextContent('Withdrawal · MTN MoMo');
        expect(
            within(history).getByText('Returned from MTN MoMo'),
        ).toBeInTheDocument();
    });

    it('keeps the withdrawal panel open beside the history on a wide screen, with no close', () => {
        setWide(true);
        render(<InvestorWallet {...wallet()} />);

        const panel = screen.getByRole('form', { name: 'Withdraw' });

        expect(
            within(panel).queryByRole('link', { name: 'Close' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('form', { name: 'Add money' }),
        ).not.toBeInTheDocument();
    });

    it('offers no withdrawal form without a verified account', () => {
        const props = wallet(emptyFixture);

        props.funding.kind = 'withdraw';
        render(<InvestorWallet {...props} />);

        expect(
            screen.getByRole('region', { name: 'Withdraw' }),
        ).toHaveTextContent(
            "Withdrawals aren't open on this account yet. They need a verified payout account under Profile → Linked accounts, and open once the account is cleared for money movement.",
        );
    });
});
