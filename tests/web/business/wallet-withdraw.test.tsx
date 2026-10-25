import type * as InertiaCore from '@inertiajs/core';
import {
    act,
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { POLL_INTERVAL_MS } from '@/hooks/use-bounded-poll';
import BusinessWallet from '@/pages/business/wallet';
import type { BusinessWalletProps } from '@/types/business';
import type { WalletWithdrawal } from '@/types/investor';
import pendingFixture from '../../../resources/fixtures/ui/business-wallet-withdraw-pending.json';
import withdrawFixture from '../../../resources/fixtures/ui/business-wallet-withdraw.json';
import receiptFixture from '../../../resources/fixtures/ui/business-wallet-withdrawal-receipt.json';
import { answers, fails, inertia, operation } from '../auditor/inertia';

vi.mock('@inertiajs/react', () => import('../auditor/inertia'));

vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaCore>()),
    http: { onResponse: () => () => undefined },
}));

const wallet = (fixture: { props: unknown } = withdrawFixture) =>
    structuredClone(fixture.props) as BusinessWalletProps;

beforeEach(() => {
    inertia.reset();
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Business wallet withdrawals (APP-B01, LED-R04)', () => {
    it('quotes a withdrawal to the Business account and records it as business.wallet.withdraw', async () => {
        vi.useFakeTimers();
        const { rerender } = render(<BusinessWallet {...wallet()} />);

        const panel = screen.getByRole('form', { name: 'Withdraw' });

        expect(screen.getByRole('link', { name: 'Withdraw' })).toHaveAttribute(
            'aria-current',
            'true',
        );
        expect(screen.getByRole('link', { name: 'Withdraw' })).toHaveAttribute(
            'href',
            '/preview/business-wallet',
        );
        expect(screen.getByRole('link', { name: 'Deposit' })).toHaveAttribute(
            'href',
            '/preview/business-wallet-deposit',
        );
        expect(within(panel).getByLabelText('AMOUNT')).toHaveValue('100,000');
        expect(panel).toHaveTextContent('Available to withdraw: RWF 4,200,000');
        expect(panel).toHaveTextContent('You receiveRWF 100,000');
        expect(
            screen.queryByRole('form', { name: 'Add money' }),
        ).not.toBeInTheDocument();

        inertia.holdReload = true;
        fireEvent.change(within(panel).getByLabelText('AMOUNT'), {
            target: { value: '40,000' },
        });
        expect(
            within(panel).getByRole('button', { name: 'Confirm withdrawal' }),
        ).toBeDisabled();
        void act(() => vi.advanceTimersByTime(300));
        expect(inertia.reloads.at(-1)).toMatchObject({
            only: ['funding'],
            data: { kind: 'withdraw', amount: '40000' },
        });
        const quoted = wallet();

        quoted.funding.withdrawal!.quote!.amount.amount = '40000';
        quoted.funding.withdrawal!.quote!.received.amount = '40000';
        inertia.reloadProps = quoted;
        rerender(<BusinessWallet {...quoted} />);
        act(() => {
            inertia.finishReload[0]();
        });
        vi.useRealTimers();
        fireEvent.click(within(panel).getByRole('radio', { name: /MTN/u }));

        inertia.queue.push(
            answers(
                operation({
                    status: 'completed',
                    code: 'WITHDRAWAL_RECORDED',
                    data: null,
                }),
            ),
        );
        fireEvent.submit(panel);

        await waitFor(() => expect(inertia.calls).toHaveLength(1));
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/business-wallet-withdraw-pending',
            method: 'post',
            body: {
                business_id: '01k6r4m2n6p8q0r2s4t6v8b1z2',
                identity_context_revision: 4,
                amount: { currency: 'RWF', amount: '40000' },
                method_id: 'mtn-1',
            },
        });
    });

    it('looks a lost withdrawal answer up by its own command, never resending it', async () => {
        render(<BusinessWallet {...wallet()} />);
        inertia.queue.push(
            fails(503, { code: 'RETRYABLE_CONTENTION' }),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        fireEvent.submit(screen.getByRole('form', { name: 'Withdraw' }));

        expect(
            await screen.findByText('Nothing was recorded'),
        ).toBeInTheDocument();
        expect(inertia.calls[1]).toMatchObject({
            method: 'get',
            body: {
                identity_context_revision: 4,
                command: 'business.wallet.withdraw',
            },
        });
        expect(inertia.calls[1].url).toBe(
            `/preview/business-wallet-withdraw-operation-${(inertia.calls[0].body as { request_id: string }).request_id}`,
        );
    });

    it('offers no form while the mandate withholds withdrawal', () => {
        const props = wallet();

        props.allowed_actions = ['business.wallet.deposit'];
        render(<BusinessWallet {...props} />);

        const panel = screen.getByRole('region', { name: 'Withdraw' });

        expect(panel).toHaveTextContent(
            "Withdrawals aren't open on this account yet. They need a verified payout account under Profile → Linked accounts, and open once the account is cleared for money movement.",
        );
        expect(
            within(panel).getByRole('link', { name: 'Close' }),
        ).toHaveAttribute('href', '/preview/business-wallet');
    });

    it('keeps a withdrawal on its way out of the balance and polls until it settles', () => {
        vi.useFakeTimers();
        render(<BusinessWallet {...wallet(pendingFixture)} />);

        const balance = screen.getByRole('region', {
            name: 'Available balance',
        });

        expect(balance).toHaveTextContent('RWF 3,900,000');
        expect(balance).toHaveTextContent('RWF 300,000 withdrawal pending');

        const list = screen.getByRole('region', { name: 'Withdrawals' });
        const [waiting, paid] = within(list).getAllByRole('listitem');

        expect(waiting).toHaveTextContent('Withdrawal · Bank of Kigali');
        expect(waiting).toHaveTextContent('Sending');
        expect(paid).toHaveTextContent('Paid out');
        expect(within(paid).getByRole('link')).toHaveAttribute(
            'href',
            '/preview/business-wallet-withdrawal-receipt',
        );

        void act(() => vi.advanceTimersByTime(POLL_INTERVAL_MS));
        expect(inertia.reloads.at(-1)).toMatchObject({
            only: ['wallet', 'deposits', 'withdrawals', 'history'],
        });
    });

    it('opens a paid withdrawal with its request and payout receipts', () => {
        render(<BusinessWallet {...wallet(receiptFixture)} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent('Withdrawal · Bank of Kigali');
        expect(sheet).toHaveTextContent('RWF 800,000');
        expect(sheet).toHaveTextContent('StatusPaid out');
        expect(sheet).toHaveTextContent('RZX-BWD0000000');
        expect(sheet).toHaveTextContent('Payout receipt');
        expect(sheet).toHaveTextContent('RZW-BWD0000000');
        fireEvent.click(within(sheet).getByRole('button', { name: 'Done' }));
        expect(inertia.visits).toEqual([{ url: '/preview/business-wallet' }]);
    });

    it('reads a returned withdrawal as back in the wallet', () => {
        const props = wallet(receiptFixture);
        const receipt = props.receipt as WalletWithdrawal;

        receipt.state = 'failed';
        receipt.settled_receipt!.code = 'WITHDRAWAL_RETURNED';
        render(<BusinessWallet {...props} />);

        expect(
            screen.getByRole('dialog', { name: 'Transaction receipt' }),
        ).toHaveTextContent('Return receipt');
    });
});
