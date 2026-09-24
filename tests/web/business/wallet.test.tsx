import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import BusinessWallet from '@/pages/business/wallet';
import type { BusinessWalletProps } from '@/types/business';
import emptyFixture from '../../../resources/fixtures/ui/business-wallet-empty.json';
import quoteFixture from '../../../resources/fixtures/ui/business-wallet-quote.json';
import refusedFixture from '../../../resources/fixtures/ui/business-wallet-refused.json';
import statesFixture from '../../../resources/fixtures/ui/business-wallet-states.json';
import walletFixture from '../../../resources/fixtures/ui/business-wallet.json';

const inertia = vi.hoisted(() => ({
    posts: [] as { url: string; data: Record<string, unknown> }[],
    reloads: [] as unknown[],
    gets: [] as { url: string; data: unknown }[],
    errors: {} as Record<string, string>,
    processing: false,
}));

vi.mock('@inertiajs/react', async () => {
    const { useState } = await import('react');

    return {
        Head: () => null,
        Link: ({
            href,
            children,
            ...props
        }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
            <a href={href.url} {...props}>
                {children}
            </a>
        ),
        router: {
            reload: (options: unknown) => inertia.reloads.push(options),
            get: (url: string, data: unknown) =>
                inertia.gets.push({ url, data }),
        },
        useForm: <T extends Record<string, unknown>>(initial: T) => {
            const [data, setState] = useState(initial);

            return {
                data,
                setData: (key: keyof T, value: unknown) =>
                    setState((current) => ({ ...current, [key]: value })),
                errors: inertia.errors,
                processing: inertia.processing,
                post: (url: string, options: { onSuccess: () => void }) => {
                    inertia.posts.push({ url, data });
                    options.onSuccess();
                },
            };
        },
    };
});

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessWalletProps;

beforeEach(() => {
    inertia.posts = [];
    inertia.reloads = [];
    inertia.gets = [];
    inertia.errors = {};
    inertia.processing = false;
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Business wallet', () => {
    it('shows the balance and the latest transactions', async () => {
        const user = userEvent.setup();

        render(<BusinessWallet {...props(walletFixture)} />);

        expect(
            screen.getByRole('heading', { name: 'Wallet' }),
        ).toBeInTheDocument();
        expect(screen.getByText('RWF 12,383,800')).toBeInTheDocument();
        expect(screen.getByText('Active')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Back to Home' }),
        ).toHaveAttribute('href', '/preview/business-home');

        const history = within(
            screen.getByRole('region', { name: 'Transaction history' }),
        );

        expect(history.getAllByRole('listitem')).toHaveLength(3);
        expect(
            history.getByRole('button', {
                name: /Disbursement · Fleet Expansion.*\+RWF 24,700,000/,
            }),
        ).toBeInTheDocument();

        await user.click(history.getByRole('button', { name: 'Show more' }));

        expect(history.getAllByRole('listitem')).toHaveLength(5);

        await user.click(history.getByRole('button', { name: 'Show less' }));

        expect(history.getAllByRole('listitem')).toHaveLength(3);
    });

    it('marks a frozen wallet', () => {
        const page = props(walletFixture);

        page.wallet.status = 'frozen';
        render(<BusinessWallet {...page} />);

        expect(screen.getByText('Frozen')).toBeInTheDocument();
    });
});

describe('Deposit and withdraw', () => {
    it('opens one panel at a time and closes it again', async () => {
        const user = userEvent.setup();

        render(<BusinessWallet {...props(walletFixture)} />);

        await user.click(screen.getByRole('button', { name: 'Deposit' }));

        expect(
            screen.getByRole('form', { name: 'Add money to wallet' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Pay from')).toBeInTheDocument();
        expect(screen.getByText('New available balance')).toBeInTheDocument();
        expect(screen.getAllByText('—')).toHaveLength(2);

        await user.click(screen.getByRole('button', { name: 'Withdraw' }));

        expect(
            screen.getByRole('form', { name: 'Withdraw to account' }),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Withdraw' }));

        expect(screen.queryByRole('form')).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Deposit' }));
        await user.click(screen.getByRole('button', { name: 'Close' }));

        expect(screen.queryByRole('form')).not.toBeInTheDocument();
    });

    it('asks for a fresh quote once the amount and method settle', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({
            advanceTimers: vi.advanceTimersByTime,
        });

        render(<BusinessWallet {...props(quoteFixture)} />);

        await user.click(screen.getByRole('button', { name: 'Withdraw' }));

        expect(inertia.reloads).toEqual([]);

        await user.click(screen.getByRole('button', { name: 'RWF 500,000' }));
        await user.click(screen.getByRole('radio', { name: 'Bank of Kigali' }));

        expect(
            screen.getByRole('radio', { name: 'Bank of Kigali' }),
        ).toBeChecked();
        expect(screen.getByRole('textbox', { name: 'Amount' })).toHaveValue(
            '500,000',
        );

        await act(() => vi.advanceTimersByTimeAsync(450));

        expect(inertia.reloads).toEqual([
            {
                only: ['quote'],
                data: { flow: 'withdraw', amount: '500000', method: 'bk' },
            },
        ]);
        expect(screen.getByText('RWF 2,500')).toBeInTheDocument();
        expect(screen.getByText('RWF 497,500')).toBeInTheDocument();
    });

    it('keeps typed amounts to plain francs', async () => {
        const user = userEvent.setup();

        render(<BusinessWallet {...props(walletFixture)} />);

        await user.click(screen.getByRole('button', { name: 'Deposit' }));

        const amount = screen.getByRole('textbox', { name: 'Amount' });

        await user.type(amount, '0012a00');

        expect(amount).toHaveValue('1,200');

        await user.clear(amount);

        expect(amount).toHaveValue('');
    });

    it('shows the new balance a deposit would leave', async () => {
        const user = userEvent.setup();
        const page = props(quoteFixture);

        page.quote = {
            status: 'ready',
            flow: 'deposit',
            fee: { currency: 'RWF', amount: '0' },
            receive: { currency: 'RWF', amount: '1000000' },
            new_balance: { currency: 'RWF', amount: '13383800' },
        };
        render(<BusinessWallet {...page} />);

        await user.click(screen.getByRole('button', { name: 'Deposit' }));

        expect(screen.getByText('RWF 13,383,800')).toBeInTheDocument();
        expect(screen.getByText('RWF 0')).toBeInTheDocument();
    });

    it('shows why the server refused the amount', async () => {
        const user = userEvent.setup();

        render(<BusinessWallet {...props(refusedFixture)} />);

        await user.click(screen.getByRole('button', { name: 'Withdraw' }));

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Amount exceeds your available balance',
        );
        expect(screen.getByRole('textbox', { name: 'Amount' })).toHaveAttribute(
            'aria-invalid',
            'true',
        );

        await user.click(screen.getByRole('button', { name: 'Withdraw' }));
        await user.click(screen.getByRole('button', { name: 'Deposit' }));

        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('sends the transfer and closes the panel', async () => {
        const user = userEvent.setup();

        render(<BusinessWallet {...props(walletFixture)} />);

        await user.click(screen.getByRole('button', { name: 'Deposit' }));
        await user.click(screen.getByRole('button', { name: 'RWF 1,000,000' }));
        await user.click(
            screen.getByRole('button', { name: 'Confirm deposit' }),
        );

        expect(inertia.posts).toEqual([
            {
                url: '/preview/business-wallet',
                data: { amount: '1000000', method: 'mtn' },
            },
        ]);
        expect(screen.queryByRole('form')).not.toBeInTheDocument();
    });

    it('shows a transfer in progress and a refused method', async () => {
        const user = userEvent.setup();

        inertia.processing = true;
        inertia.errors = { method: 'This network is not available right now' };
        render(<BusinessWallet {...props(walletFixture)} />);

        await user.click(screen.getByRole('button', { name: 'Withdraw' }));

        expect(
            screen.getByRole('button', { name: 'Processing…' }),
        ).toBeDisabled();
        expect(screen.getByRole('alert')).toHaveTextContent(
            'This network is not available right now',
        );
    });
});

describe('Transaction history', () => {
    it('narrows the history to a quick range', async () => {
        const user = userEvent.setup();

        render(<BusinessWallet {...props(walletFixture)} />);

        const range = screen.getByRole('button', {
            name: 'Date range: All time',
        });

        await user.click(range);
        await user.click(range);

        expect(
            screen.queryByRole('button', { name: '7d' }),
        ).not.toBeInTheDocument();

        await user.click(range);
        await user.click(screen.getByRole('button', { name: '7d' }));

        expect(inertia.gets).toEqual([
            {
                url: '/preview/business-wallet',
                data: { from: '2026-09-16', to: '2026-09-23' },
            },
        ]);
        expect(range).toHaveAttribute('aria-expanded', 'false');
    });

    it('applies and clears a custom range', async () => {
        const user = userEvent.setup();

        render(<BusinessWallet {...props(walletFixture)} />);

        await user.click(
            screen.getByRole('button', { name: 'Date range: All time' }),
        );
        await user.click(screen.getByRole('button', { name: 'Apply' }));
        await user.click(
            screen.getByRole('button', { name: 'Date range: All time' }),
        );
        await user.type(screen.getByLabelText('From'), '2026-05-01');
        await user.type(screen.getByLabelText('To'), '2026-06-30');
        await user.click(screen.getByRole('button', { name: 'Apply' }));
        await user.click(
            screen.getByRole('button', { name: 'Date range: All time' }),
        );
        await user.click(screen.getByRole('button', { name: 'Clear' }));

        expect(inertia.gets.map((get) => get.data)).toEqual([
            {},
            { from: '2026-05-01', to: '2026-06-30' },
            {},
        ]);
    });

    it('says when a range holds nothing', () => {
        render(<BusinessWallet {...props(emptyFixture)} />);

        expect(
            screen.getByRole('button', {
                name: 'Date range: 16 Sept – 23 Sept',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('No transactions in this range.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Show more' }),
        ).not.toBeInTheDocument();
    });

    it('labels an open-ended range', () => {
        const page = props(emptyFixture);

        page.transactions.from = null;
        const { unmount } = render(<BusinessWallet {...page} />);

        expect(
            screen.getByRole('button', { name: 'Date range: Start – 23 Sept' }),
        ).toBeInTheDocument();

        unmount();
        page.transactions.from = '2026-09-16';
        page.transactions.to = null;
        render(<BusinessWallet {...page} />);

        expect(
            screen.getByRole('button', { name: 'Date range: 16 Sept – Now' }),
        ).toBeInTheDocument();
    });

    it('offers the range as PDF and Excel from the server', async () => {
        const user = userEvent.setup();

        render(<BusinessWallet {...props(walletFixture)} />);

        const exportButton = screen.getByRole('button', { name: 'Export' });

        await user.click(exportButton);

        expect(
            screen.getByRole('link', { name: 'Download PDF' }),
        ).toHaveAttribute('href', '/preview/business-wallet.pdf');
        expect(
            screen.getByRole('link', { name: 'Download Excel' }),
        ).toHaveAttribute('href', '/preview/business-wallet.csv');

        await user.click(screen.getByRole('link', { name: 'Download Excel' }));

        expect(exportButton).toHaveAttribute('aria-expanded', 'false');

        await user.click(exportButton);
        await user.click(exportButton);

        expect(
            screen.queryByRole('link', { name: 'Download PDF' }),
        ).not.toBeInTheDocument();
    });

    it('flags pending and failed transfers and explains a failure', async () => {
        const user = userEvent.setup();

        render(<BusinessWallet {...props(statesFixture)} />);

        expect(
            screen.getByRole('button', {
                name: /MTN Mobile Money21 Sept 2026\s?· Pending/,
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: /Airtel Money20 Sept 2026\s?· Failed/,
            }),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: /Withdrawal · Airtel Money/ }),
        );

        const sheet = within(
            screen.getByRole('dialog', { name: 'Withdrawal' }),
        );

        expect(sheet.getByText('Airtel Money')).toBeInTheDocument();
        expect(sheet.getByText('Failed')).toBeInTheDocument();
        expect(
            sheet.getByText(
                'The network declined the transfer. Nothing left your wallet.',
            ),
        ).toBeInTheDocument();
        expect(sheet.getByText('-RWF 500,000')).toBeInTheDocument();
        expect(sheet.getByText('Debit')).toBeInTheDocument();

        await user.click(sheet.getByRole('button', { name: 'Done' }));

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('shows a transaction receipt with its balances and charges', async () => {
        const user = userEvent.setup();

        render(<BusinessWallet {...props(statesFixture)} />);

        await user.click(
            screen.getByRole('button', {
                name: /Withdrawal · MTN Mobile Money/,
            }),
        );

        let sheet = within(screen.getByRole('dialog', { name: 'Withdrawal' }));

        expect(sheet.getByText('Pending')).toBeInTheDocument();
        expect(sheet.getByText('21 Sept 2026 · 16:20')).toBeInTheDocument();
        expect(sheet.getByText('TXN-5102-0921')).toBeInTheDocument();
        expect(sheet.getByText('RWF 1,000,000')).toBeInTheDocument();
        expect(sheet.getByText('RWF 5,000')).toBeInTheDocument();
        expect(sheet.getByText('RWF 995,000')).toBeInTheDocument();

        await user.click(sheet.getAllByRole('button', { name: 'Close' })[0]);
        await user.click(
            screen.getByRole('button', {
                name: /Disbursement · Fleet Expansion/,
            }),
        );
        sheet = within(screen.getByRole('dialog', { name: 'Disbursement' }));

        expect(sheet.getByText('Completed')).toBeInTheDocument();
        expect(sheet.getByText('Credit')).toBeInTheDocument();
        expect(sheet.getByText('+RWF 24,700,000')).toBeInTheDocument();
        expect(sheet.queryByText('Gross amount')).not.toBeInTheDocument();

        await user.click(screen.getAllByRole('button', { name: 'Close' })[0]);

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
});
