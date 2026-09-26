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
import { POLL_INTERVAL_MS, POLL_LIMIT } from '@/hooks/use-bounded-poll';
import InvestorWallet from '@/pages/investor/wallet';
import type { C3InvestorWalletProps } from '@/types/investor';
import failedFixture from '../../../resources/fixtures/ui/investor-wallet-deposit-failed.json';
import pendingFixture from '../../../resources/fixtures/ui/investor-wallet-deposit-pending.json';
import unknownFixture from '../../../resources/fixtures/ui/investor-wallet-deposit-unknown.json';
import depositFixture from '../../../resources/fixtures/ui/investor-wallet-deposit.json';
import emptyFixture from '../../../resources/fixtures/ui/investor-wallet-empty.json';
import internalReceiptFixture from '../../../resources/fixtures/ui/investor-wallet-internal-receipt.json';
import internalFixture from '../../../resources/fixtures/ui/investor-wallet-internal.json';
import minimalFixture from '../../../resources/fixtures/ui/investor-wallet-live-minimal.json';
import unverifiedFixture from '../../../resources/fixtures/ui/investor-wallet-method-unverified.json';
import pagedFixture from '../../../resources/fixtures/ui/investor-wallet-paged.json';
import missingFixture from '../../../resources/fixtures/ui/investor-wallet-policy-missing.json';
import receiptFixture from '../../../resources/fixtures/ui/investor-wallet-receipt.json';
import restrictedFixture from '../../../resources/fixtures/ui/investor-wallet-restricted.json';
import unconfirmedFixture from '../../../resources/fixtures/ui/investor-wallet-unconfirmed.json';
import walletFixture from '../../../resources/fixtures/ui/investor-wallet.json';
import {
    answers,
    fails,
    finishReloads,
    inertia,
    resetInertia,
    setWide,
} from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const wallet = (fixture: { props: unknown } = walletFixture) =>
    structuredClone(fixture.props) as C3InvestorWalletProps;

beforeEach(() => {
    resetInertia();
    setWide(false);
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Wallet (C3)', () => {
    it('shows the total with its breakdown, the live hold and the history, with withdrawal hidden', () => {
        vi.useFakeTimers({ now: new Date('2026-10-02T07:00:00Z') });
        render(<InvestorWallet {...wallet()} />);

        const balance = screen.getByRole('region', { name: 'Wallet total' });

        expect(balance).toHaveTextContent('RWF 1,733,485');
        expect(balance).toHaveTextContent('AvailableRWF 1,253,485');
        expect(balance).toHaveTextContent('HeldRWF 30,000');
        expect(balance).toHaveTextContent('CommittedRWF 450,000');
        expect(balance).toHaveTextContent(
            'Only Available can be spent. Held is in a live checkout; Committed awaits issue.',
        );
        expect(balance).toHaveTextContent('No deposits waiting');
        expect(screen.getByRole('link', { name: 'Deposit' })).toHaveAttribute(
            'href',
            '/preview/investor-wallet-deposit',
        );
        expect(screen.queryByText(/withdraw/iu)).not.toBeInTheDocument();
        expect(screen.queryByText('Earnings history')).not.toBeInTheDocument();

        const holds = screen.getByRole('region', { name: 'Held for checkout' });

        expect(holds).toHaveTextContent('GreenLeaf Agro · 6 notes');
        expect(holds).toHaveTextContent(
            'Released in 00:03:00 unless you confirm',
        );
        act(() => vi.advanceTimersByTime(60_000));
        expect(holds).toHaveTextContent('00:02:00');

        const deposits = screen.getByRole('region', { name: 'Deposits' });

        expect(deposits).toHaveTextContent('Deposit from MTN MoMo');
        expect(deposits).toHaveTextContent('Credited');

        const history = screen.getByRole('region', {
            name: 'Transaction history',
        });

        expect(
            within(history).getByRole('link', { name: 'Cash in and out' }),
        ).toHaveAttribute('aria-current', 'true');
        expect(history).toHaveTextContent('Deposit from Bank of Kigali');
        expect(
            within(history).queryByRole('link', { name: 'Show older' }),
        ).not.toBeInTheDocument();
        expect(inertia.reload).not.toHaveBeenCalled();
    });

    it('prices a deposit under the synthetic policy and records the intent without crediting it', async () => {
        vi.useFakeTimers();
        const props = wallet(depositFixture);

        render(<InvestorWallet {...props} />);

        const panel = screen.getByRole('form', { name: 'Add money' });

        expect(panel).toHaveTextContent(
            'Synthetic deposit policy synthetic-deposit-policy-0 — preview figures, not live policy. Minimum RWF 1,000. Maximum RWF 5,000,000.',
        );
        expect(panel).toHaveTextContent('Credited once confirmedRWF 100,000');
        expect(panel).toHaveTextContent(
            'This records your deposit request. Nothing is credited until the payment is confirmed.',
        );
        expect(within(panel).getByLabelText('AMOUNT')).toHaveValue('100,000');

        fireEvent.change(within(panel).getByLabelText('AMOUNT'), {
            target: { value: '250,000' },
        });
        act(() => vi.advanceTimersByTime(300));
        expect(inertia.reload).toHaveBeenLastCalledWith(
            expect.objectContaining({
                only: ['funding'],
                data: { kind: 'deposit', amount: '250000' },
            }),
        );

        const reload = inertia.reload.mock.lastCall?.[0] as {
            onStart: () => void;
            onFinish: () => void;
        };

        act(() => reload.onStart());
        expect(
            within(panel).getByRole('button', { name: 'Confirm deposit' }),
        ).toBeDisabled();
        act(() => reload.onFinish());

        fireEvent.click(within(panel).getByRole('button', { name: '100K' }));
        fireEvent.click(within(panel).getByRole('radio', { name: /Airtel/u }));
        expect(
            within(panel).getByRole('radio', { name: /Airtel/u }),
        ).toBeChecked();

        inertia.queue.push(
            answers({
                status: 'completed',
                code: 'DEPOSIT_INTENT_RECORDED',
                data: {
                    receipt: { code: 'DEPOSIT_INTENT_RECORDED' },
                    current: null,
                    next: {
                        url: '/preview/investor-wallet-deposit-pending',
                        method: 'get',
                    },
                },
            }),
        );
        fireEvent.submit(panel);

        expect(
            within(panel).getByRole('button', { name: 'Sending…' }),
        ).toBeDisabled();
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/investor-wallet-deposit-pending',
            method: 'post',
            body: {
                identity_context_revision: 5,
                amount: { currency: 'RWF', amount: '100000' },
                method_id: 'airtel-1',
            },
        });
        expect(
            (inertia.calls[0].body as { request_id: string }).request_id,
        ).toMatch(/^[0-9a-f-]{36}$/u);
        await act(async () => {
            await Promise.resolve();
        });
        expect(inertia.visit).toHaveBeenCalledWith({
            url: '/preview/investor-wallet-deposit-pending',
            method: 'get',
        });
        expect(
            screen.getByRole('region', { name: 'Wallet total' }),
        ).toHaveTextContent('RWF 1,733,485');
    });

    it('reloads the facts when the server refuses, and resends nothing after an uncertain answer', async () => {
        finishReloads();
        render(<InvestorWallet {...wallet(depositFixture)} />);

        const panel = screen.getByRole('form', { name: 'Add money' });

        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        fireEvent.submit(panel);
        expect(
            await screen.findByText(
                /Something changed since this page loaded/u,
            ),
        ).toBeInTheDocument();
        expect(inertia.reload).toHaveBeenCalledTimes(1);

        inertia.queue.push(fails(503, { code: 'RETRYABLE_CONTENTION' }));
        inertia.queue.push(fails(404, { code: 'OPERATION_NOT_FOUND' }));
        fireEvent.submit(panel);
        expect(
            await screen.findByText('Nothing was recorded'),
        ).toBeInTheDocument();
        expect(inertia.calls).toHaveLength(3);
        expect(inertia.calls[2]).toMatchObject({
            method: 'get',
            body: { identity_context_revision: 5, command: 'wallet.deposit' },
        });
        expect(inertia.calls[2].url).toBe(
            `/preview/investor-wallet-operation-${(inertia.calls[1].body as { request_id: string }).request_id}`,
        );
        expect(
            within(panel).getByRole('button', { name: 'Confirm deposit' }),
        ).toBeDisabled();

        inertia.queue.push(
            answers({ status: 'pending', code: 'DEPOSIT_INTENT_RECORDED' }),
        );
        fireEvent.click(
            screen.getByRole('button', { name: 'Send the same request again' }),
        );
        expect(
            await screen.findByText('Recorded, not yet confirmed'),
        ).toBeInTheDocument();
        expect(inertia.calls[3].body).toEqual(inertia.calls[1].body);
    });

    it('seeds an unconfirmed deposit from the preview and only looks it up again', async () => {
        render(<InvestorWallet {...wallet(unconfirmedFixture)} />);

        expect(screen.getByText('Not yet confirmed')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Confirm deposit' }),
        ).toBeDisabled();

        inertia.queue.push(
            answers({
                status: 'completed',
                code: 'DEPOSIT_INTENT_RECORDED',
                data: null,
            }),
        );
        fireEvent.click(screen.getByRole('button', { name: 'Check again' }));
        await act(async () => {
            await Promise.resolve();
        });
        expect(inertia.calls).toEqual([
            {
                url: '/preview/investor-wallet-operation-9d3e7a10-2b4c-4d6e-8f10-a1b2c3d4e5f6',
                method: 'get',
                body: {
                    identity_context_revision: 5,
                    command: 'wallet.deposit',
                },
            },
        ]);
        expect(inertia.reload).toHaveBeenCalledWith(
            expect.objectContaining({
                only: [
                    'wallet',
                    'funding',
                    'deposits',
                    'history',
                    'allowed_actions',
                ],
            }),
        );
    });

    it('offers no same-key retry once deposit is no longer allowed', () => {
        const props = wallet(unconfirmedFixture);

        props.allowed_actions = [];
        props.preview_outcome = {
            ...(props.preview_outcome as {
                kind: 'unconfirmed';
                command: never;
            }),
            kind: 'not_recorded',
        };
        render(<InvestorWallet {...props} />);

        expect(
            screen.getByText(
                'Nothing was recorded, and this action is no longer available with the current details.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', {
                name: 'Send the same request again',
            }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText("Deposit isn't available to you right now."),
        ).toBeInTheDocument();
    });

    it('keeps pending and unknown deposits “not yet confirmed”, outside the total, and polls boundedly', () => {
        vi.useFakeTimers();
        render(<InvestorWallet {...wallet(pendingFixture)} />);

        expect(
            screen.getByRole('region', { name: 'Wallet total' }),
        ).toHaveTextContent('RWF 200,000 not yet confirmed — not in the total');

        const deposits = screen.getByRole('region', { name: 'Deposits' });

        expect(deposits).toHaveTextContent('Not yet confirmed');
        expect(deposits).not.toHaveTextContent(/failed|paid/iu);

        act(() => vi.advanceTimersByTime(POLL_INTERVAL_MS));
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['wallet', 'deposits', 'history'],
        });

        for (let poll = 1; poll < POLL_LIMIT; poll += 1) {
            act(() => vi.advanceTimersByTime(POLL_INTERVAL_MS));
        }

        expect(inertia.reload).toHaveBeenCalledTimes(POLL_LIMIT);
        act(() => vi.advanceTimersByTime(POLL_INTERVAL_MS * 3));
        expect(inertia.reload).toHaveBeenCalledTimes(POLL_LIMIT);

        expect(
            screen.getByText(/We've stopped checking automatically/u),
        ).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Refresh' }));
        expect(inertia.reload).toHaveBeenCalledTimes(POLL_LIMIT + 1);
        expect(
            screen.queryByText(/We've stopped checking automatically/u),
        ).not.toBeInTheDocument();
        act(() => vi.advanceTimersByTime(POLL_INTERVAL_MS));
        expect(inertia.reload).toHaveBeenCalledTimes(POLL_LIMIT + 2);
    });

    it('opens an unknown deposit’s receipt without any provider reference, and never as credited', () => {
        render(<InvestorWallet {...wallet(unknownFixture)} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent('Deposit from Airtel');
        expect(sheet).toHaveTextContent(
            "Not yet confirmed — we're checking with the provider",
        );
        expect(sheet).toHaveTextContent(
            'Recorded, not yet confirmed. Nothing has been credited',
        );
        expect(sheet).toHaveTextContent('synthetic-deposit-policy-0');
        expect(sheet).not.toHaveTextContent(
            /Credit receipt|provider_reference/u,
        );

        fireEvent.click(within(sheet).getByRole('button', { name: 'Done' }));
        expect(inertia.visit).toHaveBeenCalledWith(
            { url: '/preview/investor-wallet', method: 'get' },
            { preserveScroll: true },
        );
    });

    it('shows a verified failure as not credited', () => {
        render(<InvestorWallet {...wallet(failedFixture)} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent("Didn't go through — nothing credited");
        expect(sheet).toHaveTextContent(
            'The payment was confirmed as not completed. Nothing was credited.',
        );
    });

    it('shows a credited deposit’s immutable receipt', () => {
        render(<InvestorWallet {...wallet(receiptFixture)} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent('Deposit from MTN MoMo');
        expect(sheet).toHaveTextContent('RWF 500,000');
        expect(sheet).toHaveTextContent('RZ-DEP-5520');
        expect(sheet).toHaveTextContent('30 Sept 2026 · 10:04');
    });

    it('reads a credited intent with its credit receipt', () => {
        const props = wallet();

        props.receipt = structuredClone(props.deposits[0]);
        render(<InvestorWallet {...props} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent('Credit receipt');
        expect(sheet).toHaveTextContent('RZ-DEP-0930C');
    });

    it('lists holds and commitments apart from external cash', () => {
        render(<InvestorWallet {...wallet(internalFixture)} />);

        const history = screen.getByRole('region', {
            name: 'Transaction history',
        });

        expect(
            within(history).getByRole('link', {
                name: 'Holds and commitments',
            }),
        ).toHaveAttribute('aria-current', 'true');
        expect(history).toHaveTextContent('Held for GreenLeaf Agro');
        expect(history).toHaveTextContent('Available → Held');
        expect(history).toHaveTextContent('Committed to Kivu Coffee');
        expect(history).toHaveTextContent('Hold released · Umucyo Solar');
        expect(history).toHaveTextContent('Refund · Nyamirambo Bakery');
    });

    it('opens an internal transfer’s receipt', () => {
        render(<InvestorWallet {...wallet(internalReceiptFixture)} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Transaction receipt',
        });

        expect(sheet).toHaveTextContent('Committed to Kivu Coffee');
        expect(sheet).toHaveTextContent('RZ-CMT-7731');
    });

    it('pages older history and states a restriction', () => {
        const { unmount } = render(
            <InvestorWallet {...wallet(pagedFixture)} />,
        );

        expect(
            screen.getByRole('link', { name: 'Show older' }),
        ).toHaveAttribute('href', '/preview/investor-wallet-paged-older');
        unmount();
        render(<InvestorWallet {...wallet(restrictedFixture)} />);
        expect(
            screen.getByRole('region', { name: 'Wallet total' }),
        ).toHaveTextContent(
            'A restriction has applied since 25 Sept 2026. Deposits still work.',
        );
        expect(screen.getByText('Restricted')).toBeInTheDocument();
    });

    it('explains a missing deposit policy instead of offering a form', () => {
        render(<InvestorWallet {...wallet(missingFixture)} />);

        const panel = screen.getByRole('region', { name: 'Add money' });

        expect(panel).toHaveTextContent(
            "Deposits aren't available yet: no deposit policy has been set.",
        );
        expect(within(panel).queryByRole('button')).not.toBeInTheDocument();
        fireEvent.click(within(panel).getByRole('link', { name: 'Close' }));
    });

    it('names a live policy plainly and reads an unlisted refusal generically', () => {
        const props = wallet(depositFixture);

        props.funding.policy = {
            version: 'deposit-policy-1',
            synthetic: false,
            fee: { currency: 'RWF', amount: '0' },
            minimum: null,
            maximum: null,
        };
        props.preview_outcome = {
            kind: 'refused',
            code: 'SOMETHING_NEW',
            status: 409,
        };
        render(<InvestorWallet {...props} />);

        expect(
            screen.getByText('Deposit policy deposit-policy-1.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'This request was refused. Refresh and try again.',
            ),
        ).toBeInTheDocument();
    });

    it('states a quote refusal and keeps deposit closed', () => {
        render(<InvestorWallet {...wallet(unverifiedFixture)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            "That account isn't verified for deposits yet.",
        );
        expect(
            screen.getByRole('button', { name: 'Confirm deposit' }),
        ).toBeDisabled();
    });

    it('shows a field error the server returns', async () => {
        const user = userEvent.setup();

        inertia.httpErrors = { amount: 'Enter at least RWF 1,000.' };
        inertia.queue.push(() => Promise.resolve(undefined));
        render(<InvestorWallet {...wallet(depositFixture)} />);
        await user.click(
            screen.getByRole('button', { name: 'Confirm deposit' }),
        );
        expect(
            await screen.findByText('Enter at least RWF 1,000.'),
        ).toBeInTheDocument();
    });

    it('shows an empty wallet with a way to link an account', () => {
        render(<InvestorWallet {...wallet(emptyFixture)} />);

        expect(
            screen.getByRole('link', { name: 'Link an account' }),
        ).toHaveAttribute('href', '/preview/investor-profile-linked');
        expect(
            screen.getByText('No transactions in this range.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('region', { name: 'Held for checkout' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('region', { name: 'Deposits' }),
        ).not.toBeInTheDocument();
    });

    it('keeps the deposit panel open beside the history on a wide screen', () => {
        setWide(true);
        const { unmount } = render(<InvestorWallet {...wallet()} />);

        const panel = screen.getByRole('form', { name: 'Add money' });

        expect(
            within(panel).queryByRole('link', { name: 'Close' }),
        ).not.toBeInTheDocument();
        expect(panel).toHaveTextContent('Credited once confirmed—');
        unmount();

        const props = wallet(emptyFixture);

        render(<InvestorWallet {...props} />);
        expect(
            screen.getByText("Deposit isn't available to you right now."),
        ).toBeInTheDocument();
    });

    it('renders the live-minimal shape', () => {
        render(<InvestorWallet {...wallet(minimalFixture)} />);

        expect(
            screen.getByRole('region', { name: 'Wallet total' }),
        ).toHaveTextContent('RWF 0');
        expect(screen.getByRole('link', { name: 'Deposit' })).toHaveAttribute(
            'href',
            '/investor/wallet?kind=deposit',
        );
    });
});
