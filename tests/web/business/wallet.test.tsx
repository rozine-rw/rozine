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
import { POLL_INTERVAL_MS, POLL_LIMIT } from '@/hooks/use-bounded-poll';
import BusinessWallet from '@/pages/business/wallet';
import type { BusinessWalletProps } from '@/types/business';
import failedFixture from '../../../resources/fixtures/ui/business-wallet-deposit-failed.json';
import pendingFixture from '../../../resources/fixtures/ui/business-wallet-deposit-pending.json';
import quoteFixture from '../../../resources/fixtures/ui/business-wallet-deposit-quote.json';
import intentReceiptFixture from '../../../resources/fixtures/ui/business-wallet-deposit-receipt.json';
import unknownFixture from '../../../resources/fixtures/ui/business-wallet-deposit-unknown.json';
import depositFixture from '../../../resources/fixtures/ui/business-wallet-deposit.json';
import emptyFixture from '../../../resources/fixtures/ui/business-wallet-empty.json';
import entryReceiptFixture from '../../../resources/fixtures/ui/business-wallet-entry-receipt.json';
import internalReceiptFixture from '../../../resources/fixtures/ui/business-wallet-internal-receipt.json';
import internalFixture from '../../../resources/fixtures/ui/business-wallet-internal.json';
import minimalFixture from '../../../resources/fixtures/ui/business-wallet-live-minimal.json';
import missingFixture from '../../../resources/fixtures/ui/business-wallet-policy-missing.json';
import restrictedFixture from '../../../resources/fixtures/ui/business-wallet-restricted.json';
import unconfirmedFixture from '../../../resources/fixtures/ui/business-wallet-unconfirmed.json';
import walletFixture from '../../../resources/fixtures/ui/business-wallet.json';
import { answers, fails, inertia, operation } from '../auditor/inertia';

vi.mock('@inertiajs/react', () => import('../auditor/inertia'));

vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaCore>()),
    http: { onResponse: () => () => undefined },
}));

const wallet = (fixture: { props: unknown } = walletFixture) =>
    structuredClone(fixture.props) as BusinessWalletProps;

beforeEach(() => {
    inertia.reset();
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Business wallet (business-servicing-v1)', () => {
    it('shows the available balance, the repayments link and the deposit history, with deposit and withdraw offered', () => {
        render(<BusinessWallet {...wallet()} />);

        const balance = screen.getByRole('region', {
            name: 'Available balance',
        });

        expect(balance).toHaveTextContent('RWF 4,200,000');
        expect(balance).toHaveTextContent('Active');
        expect(balance).toHaveTextContent(
            'Fees, payouts & disbursements settle here',
        );
        expect(
            within(balance).getByRole('link', { name: 'RWF 4,200,000' }),
        ).toHaveAttribute('href', '/preview/business-wallet-internal');
        expect(
            screen.getByRole('link', { name: 'Go to repayments' }),
        ).toHaveAttribute('href', '/preview/business-repayments');
        expect(screen.getByRole('link', { name: 'Deposit' })).toHaveAttribute(
            'href',
            '/preview/business-wallet-deposit',
        );
        expect(screen.getByRole('link', { name: 'Withdraw' })).toHaveAttribute(
            'href',
            '/preview/business-wallet-withdraw',
        );
        expect(
            screen.queryByRole('region', { name: 'Withdrawals' }),
        ).not.toBeInTheDocument();

        // The credited deposit shows once, in the history, not again under Deposits.
        expect(
            screen.queryByRole('region', { name: 'Deposits' }),
        ).not.toBeInTheDocument();

        const history = screen.getByRole('region', {
            name: 'Transaction history',
        });

        expect(
            within(history).getByRole('link', { name: 'Deposits' }),
        ).toHaveAttribute('aria-current', 'true');
        expect(
            within(history).getByRole('link', { name: 'Repayments' }),
        ).not.toHaveAttribute('aria-current');
        expect(history).toHaveTextContent('Deposit · Bank of Kigali ····2231');
        expect(
            within(history).queryByRole('link', { name: 'Show more' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Back to Home' }),
        ).toHaveAttribute('href', '/preview/business-home');
    });

    it('prices a deposit and records it as business.wallet.deposit for this Business', async () => {
        vi.useFakeTimers();
        const { rerender } = render(
            <BusinessWallet {...wallet(quoteFixture)} />,
        );

        const panel = screen.getByRole('form', { name: 'Add money' });

        expect(panel).toHaveTextContent('Credited once confirmedRWF 1,000,000');
        fireEvent.change(within(panel).getByLabelText('AMOUNT'), {
            target: { value: '2,000,000' },
        });
        act(() => vi.advanceTimersByTime(300));
        expect(inertia.reloads.at(-1)).toMatchObject({
            only: ['funding'],
            data: { kind: 'deposit', amount: '2000000' },
        });
        vi.useRealTimers();
        /* The stale 1,000,000 quote cannot be sent; the 2,000,000 one can. */
        fireEvent.submit(panel);
        expect(inertia.calls).toHaveLength(0);

        const requoted = wallet(quoteFixture);

        requoted.funding.quote!.amount = { currency: 'RWF', amount: '2000000' };
        requoted.funding.quote!.credited = {
            currency: 'RWF',
            amount: '2000000',
        };
        rerender(<BusinessWallet {...requoted} />);

        inertia.queue.push(
            answers(
                operation({
                    status: 'pending',
                    code: 'DEPOSIT_INTENT_RECORDED',
                    data: null,
                }),
            ),
        );
        fireEvent.submit(panel);

        await waitFor(() => expect(inertia.calls).toHaveLength(1));
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/business-wallet-deposit-pending',
            method: 'post',
            body: {
                business_id: '01k6r4m2n6p8q0r2s4t6v8b1z2',
                identity_context_revision: 4,
                amount: { currency: 'RWF', amount: '2000000' },
                method_id: 'bk-1',
            },
        });
        expect(
            (inertia.calls[0].body as { request_id: string }).request_id,
        ).toMatch(/^[0-9a-f-]{36}$/u);
    });

    it('opens the deposit panel with no quote yet, closing back to the wallet', () => {
        render(<BusinessWallet {...wallet(depositFixture)} />);

        const panel = screen.getByRole('form', { name: 'Add money' });

        expect(
            within(panel).getByRole('button', { name: 'Confirm deposit' }),
        ).toBeDisabled();
        expect(
            within(panel).getByRole('link', { name: 'Close' }),
        ).toHaveAttribute('href', '/preview/business-wallet');
    });

    it('holds a seeded unconfirmed deposit and only looks it up again', () => {
        render(<BusinessWallet {...wallet(unconfirmedFixture)} />);

        expect(screen.getByText('Not yet confirmed')).toBeInTheDocument();
        expect(inertia.calls).toHaveLength(0);
    });

    it('never resends a deposit whose answer was lost', async () => {
        render(<BusinessWallet {...wallet(quoteFixture)} />);
        inertia.queue.push(
            fails(503, { code: 'RETRYABLE_CONTENTION' }),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        fireEvent.submit(screen.getByRole('form', { name: 'Add money' }));

        expect(
            await screen.findByText('Nothing was recorded'),
        ).toBeInTheDocument();
        expect(inertia.calls).toHaveLength(2);
        expect(inertia.calls[1]).toMatchObject({
            method: 'get',
            body: {
                identity_context_revision: 4,
                command: 'business.wallet.deposit',
            },
        });
        expect(inertia.calls[1].url).toBe(
            `/preview/business-wallet-operation-${(inertia.calls[0].body as { request_id: string }).request_id}`,
        );
    });

    it('keeps pending and unknown deposits outside the balance and polls them boundedly', () => {
        vi.useFakeTimers();
        const { rerender } = render(
            <BusinessWallet {...wallet(pendingFixture)} />,
        );

        expect(
            screen.getByRole('region', { name: 'Available balance' }),
        ).toHaveTextContent('RWF 1,000,000 waiting to be confirmed');
        expect(
            screen.getByRole('region', { name: 'Deposits' }),
        ).toHaveTextContent('Not yet confirmed');

        for (let tick = 0; tick < POLL_LIMIT; tick++) {
            act(() => vi.advanceTimersByTime(POLL_INTERVAL_MS));
        }

        expect(inertia.reloads).toHaveLength(POLL_LIMIT);
        expect(inertia.reloads[0]).toMatchObject({
            only: ['wallet', 'deposits', 'withdrawals', 'history'],
        });

        rerender(<BusinessWallet {...wallet(unknownFixture)} />);
        expect(
            screen.getByRole('region', { name: 'Deposits' }),
        ).toHaveTextContent('Deposit · Bank of Kigali');
    });

    it('states a restriction without withholding deposits (§11.4)', () => {
        render(<BusinessWallet {...wallet(restrictedFixture)} />);

        const balance = screen.getByRole('region', {
            name: 'Available balance',
        });

        expect(balance).toHaveTextContent('Restricted');
        expect(balance).toHaveTextContent(
            'Restricted since 30 Nov 2026. Deposits and repayments still go through.',
        );
        expect(screen.getByRole('link', { name: 'Deposit' })).toBeVisible();
    });

    it('explains a missing deposit policy instead of offering a form', () => {
        render(<BusinessWallet {...wallet(missingFixture)} />);

        expect(
            screen.queryByRole('form', { name: 'Add money' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('region', { name: 'Add money' }),
        ).toHaveTextContent(/deposit/iu);
    });

    it('lists repayments apart from deposits, and pages older entries', () => {
        render(<BusinessWallet {...wallet(internalFixture)} />);

        const history = screen.getByRole('region', {
            name: 'Transaction history',
        });

        expect(
            within(history).getByRole('link', { name: 'Repayments' }),
        ).toHaveAttribute('aria-current', 'true');
        expect(history).toHaveTextContent('Repayment · Fleet Expansion');
        expect(history).toHaveTextContent('Instalments 2');
        expect(
            within(history).getByRole('link', { name: 'Show more' }),
        ).toHaveAttribute('href', '/preview/business-wallet-internal');
    });

    it('opens a deposit entry receipt with the provider fee, and closes it', () => {
        render(<BusinessWallet {...wallet(entryReceiptFixture)} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent('RWF 3,500,000');
        expect(sheet).toHaveTextContent('Provider feeRWF 0');
        expect(sheet).toHaveTextContent('ReferenceRZ-BWE-1128');
        expect(sheet).toHaveTextContent(
            'Policy versionsynthetic-deposit-policy-0',
        );
        fireEvent.click(within(sheet).getByRole('button', { name: 'Done' }));
        expect(inertia.visits).toEqual([{ url: '/preview/business-wallet' }]);
    });

    it('opens a repayment receipt without a provider fee', () => {
        render(<BusinessWallet {...wallet(internalReceiptFixture)} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent('Repayment · Fleet Expansion');
        expect(sheet).toHaveTextContent('ReferenceRZ-RPY-1123');
        expect(sheet).not.toHaveTextContent('Provider fee');
    });

    it('opens a deposit intent through its intent and credit receipts', () => {
        render(<BusinessWallet {...wallet(intentReceiptFixture)} />);

        expect(
            screen.getByRole('dialog', { name: 'Transaction receipt' }),
        ).toHaveTextContent('RZ-BDEP-1128C');
    });

    it('shows an empty wallet with no history and no repayments link', () => {
        render(<BusinessWallet {...wallet(emptyFixture)} />);

        expect(
            screen.getByRole('region', { name: 'Available balance' }),
        ).toHaveTextContent('RWF 0');
        expect(screen.getByText('Nothing here yet.')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Deposit' })).toBeVisible();
        expect(
            screen.queryByRole('link', { name: 'Go to repayments' }),
        ).not.toBeInTheDocument();
    });

    it('shows a failed deposit as not credited, without polling', () => {
        vi.useFakeTimers();
        render(<BusinessWallet {...wallet(failedFixture)} />);

        expect(
            screen.getByRole('region', { name: 'Deposits' }),
        ).toHaveTextContent("Didn't go through — nothing credited");
        expect(
            screen.getByRole('dialog', { name: 'Transaction receipt' }),
        ).toHaveTextContent(/nothing/iu);
        act(() => vi.advanceTimersByTime(POLL_INTERVAL_MS * 2));
        expect(inertia.reloads).toHaveLength(0);
    });

    it('renders the live-minimal shape with nothing offered and no links it lacks', () => {
        render(<BusinessWallet {...wallet(minimalFixture)} />);

        expect(
            screen.getByRole('region', { name: 'Available balance' }),
        ).toHaveTextContent('RWF 0');
        expect(
            screen.queryByRole('link', { name: 'RWF 0' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Go to repayments' }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('Nothing here yet.')).toBeInTheDocument();
        expect(
            screen.queryByRole('region', { name: 'Deposits' }),
        ).not.toBeInTheDocument();
    });
});

describe('Business wallet history range and statement', () => {
    it('applies a date range by reloading the history with it, and clears back to all time', () => {
        render(<BusinessWallet {...wallet()} />);

        fireEvent.click(
            screen.getByRole('button', { name: 'Date range: All time' }),
        );
        fireEvent.change(screen.getByLabelText('From'), {
            target: { value: '2026-11-01' },
        });
        fireEvent.change(screen.getByLabelText('To'), {
            target: { value: '2026-11-30' },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Apply' }));

        expect(inertia.visits).toEqual([
            {
                url: '/preview/business-wallet?from=2026-11-01&to=2026-11-30',
            },
        ]);

        fireEvent.click(
            screen.getByRole('button', { name: 'Date range: All time' }),
        );
        fireEvent.click(screen.getByRole('button', { name: 'All time' }));

        expect(inertia.visits.at(-1)).toEqual({
            url: '/preview/business-wallet',
        });
    });

    it('names the chosen range and offers the statement for it as PDF or Excel downloads', () => {
        const props = wallet();
        props.history.range = {
            from: '2026-11-01',
            to: null,
            base: {
                url: '/business/b1/wallet?movement=external',
                method: 'get',
            },
        };
        render(<BusinessWallet {...props} />);

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Date range: 1 Nov 2026 – Now',
            }),
        );
        expect(screen.getByLabelText('From')).toHaveValue('2026-11-01');
        fireEvent.click(screen.getByRole('button', { name: 'Apply' }));
        expect(inertia.visits).toEqual([
            { url: '/business/b1/wallet?movement=external&from=2026-11-01' },
        ]);

        fireEvent.click(
            screen.getByRole('button', { name: 'Download statement' }),
        );
        expect(
            screen.getByRole('link', { name: /Download PDF/ }),
        ).toHaveAttribute('href', '/preview/business-wallet-statement.pdf');
        expect(
            screen.getByRole('link', { name: /Download CSV/ }),
        ).toHaveAttribute('href', '/preview/business-wallet-statement.csv');
    });

    it('names a range open at its start, clears it back to the base and closes the statement menu on a download', () => {
        const props = wallet();
        props.history.range = {
            from: null,
            to: '2026-11-30',
            base: { url: '/business/b1/wallet', method: 'get' },
        };
        render(<BusinessWallet {...props} />);

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Date range: Start – 30 Nov 2026',
            }),
        );
        fireEvent.click(screen.getByRole('button', { name: 'All time' }));
        expect(inertia.visits).toEqual([{ url: '/business/b1/wallet' }]);

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Date range: Start – 30 Nov 2026',
            }),
        );
        fireEvent.click(screen.getByRole('button', { name: 'Apply' }));
        expect(inertia.visits).toEqual([
            { url: '/business/b1/wallet' },
            { url: '/business/b1/wallet?to=2026-11-30' },
        ]);

        fireEvent.click(
            screen.getByRole('button', { name: 'Download statement' }),
        );
        fireEvent.click(screen.getByRole('link', { name: /Download PDF/ }));
        expect(
            screen.queryByRole('link', { name: /Download PDF/ }),
        ).not.toBeInTheDocument();
    });

    it('shows neither control where the server serves no range or statement', () => {
        render(<BusinessWallet {...wallet(emptyFixture)} />);

        expect(
            screen.queryByRole('button', { name: /Date range/ }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Download statement' }),
        ).not.toBeInTheDocument();
    });
});
