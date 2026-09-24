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
import InvestorWallet from '@/pages/investor/wallet';
import type { InvestorWalletProps } from '@/types/investor';
import depositFixture from '../../../resources/fixtures/ui/investor-wallet-deposit.json';
import emptyFixture from '../../../resources/fixtures/ui/investor-wallet-empty.json';
import pendingFixture from '../../../resources/fixtures/ui/investor-wallet-pending.json';
import receiptFixture from '../../../resources/fixtures/ui/investor-wallet-receipt.json';
import withdrawFixture from '../../../resources/fixtures/ui/investor-wallet-withdraw.json';
import walletFixture from '../../../resources/fixtures/ui/investor-wallet.json';
import { inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const wallet = (fixture: { props: unknown } = walletFixture) =>
    structuredClone(fixture.props) as InvestorWalletProps;

beforeEach(() => {
    resetInertia();
    setWide(false);
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Wallet', () => {
    it('shows the balance, the funding switch, earnings and every movement with its status', async () => {
        const user = userEvent.setup();

        render(<InvestorWallet {...wallet()} />);

        expect(
            screen.getByRole('heading', { name: 'Wallet' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/investor-deals',
        );

        const balance = screen.getByRole('region', {
            name: 'AVAILABLE BALANCE',
        });

        expect(balance).toHaveTextContent('RWF 1,253,485');
        expect(balance).toHaveTextContent('Active');
        expect(
            screen.getByRole('link', { name: 'Deposit' }),
        ).not.toHaveAttribute('aria-current');
        expect(screen.queryByRole('form')).not.toBeInTheDocument();

        const earnings = screen.getByRole('region', {
            name: 'Earnings history',
        });

        expect(
            within(earnings).getByRole('link', { name: '6 months' }),
        ).toHaveAttribute('aria-current', 'true');
        expect(within(earnings).getByText('RWF 413K')).toBeInTheDocument();
        expect(within(earnings).getByText('14')).toBeInTheDocument();
        expect(within(earnings).getAllByRole('row')).toHaveLength(7);

        fireEvent.change(within(earnings).getByLabelText('From date'), {
            target: { value: '2026-01-01' },
        });
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['earnings'],
            data: { from: '2026-01-01', to: '2026-09-23' },
        });
        fireEvent.change(within(earnings).getByLabelText('To date'), {
            target: { value: '2026-06-30' },
        });
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['earnings'],
            data: { from: '2026-03-23', to: '2026-06-30' },
        });

        const history = screen.getByRole('region', {
            name: 'Transaction history',
        });
        const rows = () => within(history).getAllByRole('listitem');

        expect(rows()).toHaveLength(4);
        expect(rows()[0]).toHaveTextContent('Payout · GreenLeaf Agro');
        expect(rows()[0]).toHaveTextContent('+RWF 150,117');
        expect(rows()[2]).toHaveTextContent('· Pending');
        await user.click(
            within(history).getByRole('button', { name: 'Show more' }),
        );
        expect(rows()).toHaveLength(7);
        expect(rows()[4]).toHaveTextContent('· Failed');
        await user.click(
            within(history).getByRole('button', { name: 'Show less' }),
        );

        await user.click(
            within(history).getByRole('button', { name: 'Export' }),
        );
        expect(
            within(history).getByRole('link', { name: 'Download PDF' }),
        ).toHaveAttribute('href', '/preview/investor-wallet');
        expect(
            within(history).getByRole('link', { name: 'Download Excel' }),
        ).toBeInTheDocument();
    });

    it('prices a deposit on the server and sends the chosen amount and account', async () => {
        vi.useFakeTimers();
        render(<InvestorWallet {...wallet(depositFixture)} />);

        const panel = screen.getByRole('form', { name: 'Add money' });

        expect(screen.getByRole('link', { name: 'Deposit' })).toHaveAttribute(
            'aria-current',
            'true',
        );
        expect(within(panel).getByLabelText('AMOUNT')).toHaveValue('100,000');
        expect(within(panel).getByText('RWF 0')).toBeInTheDocument();
        expect(within(panel).getByText('RWF 1,353,485')).toBeInTheDocument();
        expect(
            within(panel).getByRole('radio', { name: /MTN MoMo/u }),
        ).toBeChecked();
        expect(
            within(panel).getByRole('link', { name: 'Close' }),
        ).toHaveAttribute('href', '/preview/investor-wallet');

        fireEvent.change(within(panel).getByLabelText('AMOUNT'), {
            target: { value: '25,0x00' },
        });
        expect(within(panel).getByLabelText('AMOUNT')).toHaveValue('25,000');
        fireEvent.click(within(panel).getByRole('button', { name: '500K' }));
        act(() => vi.advanceTimersByTime(300));
        expect(inertia.reload).toHaveBeenCalledTimes(1);
        expect(inertia.reload).toHaveBeenLastCalledWith(
            expect.objectContaining({
                only: ['funding'],
                data: { kind: 'deposit', amount: '500000' },
            }),
        );

        const options = inertia.reload.mock.lastCall?.[0] as {
            onStart: () => void;
            onFinish: () => void;
        };

        act(() => options.onStart());
        expect(
            within(panel).getByRole('button', { name: 'Confirm deposit' }),
        ).toBeDisabled();
        act(() => options.onFinish());

        fireEvent.click(
            within(panel).getByRole('radio', { name: /Bank of Kigali/u }),
        );
        fireEvent.click(
            within(panel).getByRole('button', { name: 'Confirm deposit' }),
        );
        expect(inertia.posts).toEqual([
            {
                url: '/preview/investor-wallet',
                data: { amount: '500000', method: 'bk-1' },
            },
        ]);
    });

    it('explains a refused withdrawal and a pending one', () => {
        const { unmount } = render(
            <InvestorWallet {...wallet(withdrawFixture)} />,
        );

        const panel = screen.getByRole('form', { name: 'Withdraw' });

        expect(within(panel).getByText('SEND TO')).toBeInTheDocument();
        expect(within(panel).getByText('You receive')).toBeInTheDocument();
        expect(within(panel).getByRole('alert')).toHaveTextContent(
            'Amount exceeds your available balance',
        );
        expect(
            within(panel).getByRole('button', { name: 'Confirm withdrawal' }),
        ).toBeDisabled();
        unmount();

        render(<InvestorWallet {...wallet(pendingFixture)} />);
        expect(
            screen.getByText('RWF 300,000 withdrawal pending'),
        ).toBeInTheDocument();
    });

    it('shows progress and a field error while sending', () => {
        inertia.processing = true;
        inertia.errors = { amount: 'Enter an amount.' };
        const props = wallet(depositFixture);

        props.wallet.status = 'restricted';
        props.funding.quote = null;
        render(<InvestorWallet {...props} />);

        expect(screen.getByText('Restricted')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Sending…' })).toBeDisabled();
        expect(screen.getByRole('alert')).toHaveTextContent('Enter an amount.');
        expect(screen.getAllByText('—')).toHaveLength(2);
    });

    it('opens a receipt that traces a payout to its principal, return and fee', async () => {
        const user = userEvent.setup();

        render(<InvestorWallet {...wallet(receiptFixture)} />);

        const receipt = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(
            within(receipt).getByText('Monthly repayment'),
        ).toBeInTheDocument();
        expect(within(receipt).getByText('+RWF 150,117')).toBeInTheDocument();
        expect(within(receipt).getByText('Completed')).toBeInTheDocument();
        expect(within(receipt).getByText('Credit')).toBeInTheDocument();
        expect(
            within(receipt).getByText('TXN-2026-0903-1180'),
        ).toBeInTheDocument();
        expect(within(receipt).getByText('RWF 133,334')).toBeInTheDocument();
        expect(
            within(receipt).getByText('Investor fee (1%)'),
        ).toBeInTheDocument();
        expect(within(receipt).getByText('RWF 1,517')).toBeInTheDocument();

        await user.click(within(receipt).getByRole('button', { name: 'Done' }));
        expect(inertia.visit).toHaveBeenCalledWith(
            { url: '/preview/investor-wallet', method: 'get' },
            { preserveScroll: true },
        );
    });

    it('breaks a failed withdrawal receipt into gross, fee and net', () => {
        const props = wallet(receiptFixture);
        const receipt = props.receipt as NonNullable<
            InvestorWalletProps['receipt']
        >;

        receipt.kind = 'withdrawal';
        receipt.status = 'failed';
        receipt.amount = { currency: 'RWF', amount: '-300000' };
        receipt.payout = null;
        receipt.breakdown = {
            gross: { currency: 'RWF', amount: '300000' },
            fee: { currency: 'RWF', amount: '0' },
            net: { currency: 'RWF', amount: '300000' },
        };
        render(<InvestorWallet {...props} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(within(sheet).getByText('Debit')).toBeInTheDocument();
        expect(within(sheet).getByText('Failed')).toBeInTheDocument();
        expect(within(sheet).getByText('-RWF 300,000')).toHaveClass(
            'line-through',
        );
        expect(within(sheet).getByText('Net received')).toBeInTheDocument();
    });

    it('explains an empty wallet with nothing linked', () => {
        const props = wallet(emptyFixture);

        props.funding.methods = [];
        props.funding.kind = 'deposit';
        render(<InvestorWallet {...props} />);

        expect(
            screen.getByText('No transactions in this range.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('No payouts landed between those dates.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Link an account' }),
        ).toHaveAttribute('href', '/preview/investor-profile-linked');
    });

    it('keeps the deposit panel open beside the history on a wide screen', () => {
        setWide(true);
        const { unmount } = render(<InvestorWallet {...wallet()} />);

        const panel = screen.getByRole('form', { name: 'Add money' });

        expect(
            within(panel).queryByRole('link', { name: 'Close' }),
        ).not.toBeInTheDocument();
        expect(
            within(panel).getByRole('radio', { name: 'MTN MoMo' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('region', { name: 'Transaction history' }),
        ).toHaveTextContent('Transactions');
        expect(screen.getAllByRole('listitem')).toHaveLength(6);
        unmount();

        render(<InvestorWallet {...wallet(withdrawFixture)} />);
        expect(
            screen.getByRole('form', { name: 'Withdraw' }),
        ).toBeInTheDocument();
        unmount();
    });
});
