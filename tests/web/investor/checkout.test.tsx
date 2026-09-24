import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorCheckout from '@/pages/investor/checkout';
import type { InvestorCheckoutProps } from '@/types/investor';
import confirmedFixture from '../../../resources/fixtures/ui/investor-checkout-confirmed.json';
import shortFixture from '../../../resources/fixtures/ui/investor-checkout-wallet-short.json';
import checkoutFixture from '../../../resources/fixtures/ui/investor-checkout.json';
import { inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown } = checkoutFixture) =>
    structuredClone(fixture.props) as InvestorCheckoutProps;

beforeEach(() => {
    resetInertia();
    setWide(false);
});

describe('Checkout', () => {
    it('shows the server’s quote, the wallet source and the disclosure before any confirm', () => {
        render(<InvestorCheckout {...props()} />);

        const sheet = screen.getByRole('dialog', { name: 'Checkout' });

        expect(screen.getByTestId('head')).toHaveTextContent('Checkout');
        expect(
            within(sheet).getByText('13.5% return · 6 months'),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText('1 note · RWF 5,000 each'),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByLabelText('INVESTMENT AMOUNT'),
        ).toHaveTextContent('5,000');
        expect(within(sheet).getByText('+RWF 675')).toBeInTheDocument();
        expect(
            within(sheet).getByText('Repayment fee (1% per payout)'),
        ).toBeInTheDocument();
        expect(within(sheet).getByText('RWF 54')).toBeInTheDocument();
        expect(
            within(sheet).getByText('Maturity value · Mar 2027'),
        ).toBeInTheDocument();
        expect(within(sheet).getByText('RWF 5,621')).toBeInTheDocument();
        expect(
            within(sheet).getByRole('radio', { name: 'Wallet' }),
        ).toBeChecked();
        expect(
            within(sheet).getByText('Rozine Wallet · RWF 1,253,485 available'),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText(/you can lose some or all of your money/u),
        ).toBeInTheDocument();
        expect(within(sheet).queryByText('CLOSES IN')).not.toBeInTheDocument();
        expect(
            within(sheet).getByRole('button', { name: 'Confirm · RWF 5,000' }),
        ).toBeDisabled();
        expect(
            within(sheet).getByRole('button', { name: 'One note fewer' }),
        ).toBeDisabled();
        expect(screen.getByRole('link', { name: 'Close' })).toHaveAttribute(
            'href',
            '/preview/investor-deals',
        );
    });

    it('re-quotes each quantity and confirms only after the disclosure is acknowledged', async () => {
        const user = userEvent.setup();

        render(<InvestorCheckout {...props()} />);

        const sheet = screen.getByRole('dialog', { name: 'Checkout' });

        await user.click(
            within(sheet).getByRole('button', { name: 'One note more' }),
        );
        await vi.waitFor(() =>
            expect(inertia.reload).toHaveBeenLastCalledWith(
                expect.objectContaining({
                    only: ['quote', 'wallet', 'refusal'],
                    data: { units: 2 },
                }),
            ),
        );
        await user.click(
            within(sheet).getByRole('button', { name: 'One note fewer' }),
        );
        await user.click(within(sheet).getByRole('button', { name: '25K' }));
        await vi.waitFor(() =>
            expect(inertia.reload).toHaveBeenLastCalledWith(
                expect.objectContaining({ data: { units: 5 } }),
            ),
        );
        expect(
            within(sheet).getByRole('button', { name: '25K' }),
        ).toHaveAttribute('aria-pressed', 'true');

        const reload = inertia.reload.mock.lastCall?.[0] as {
            onStart: () => void;
            onFinish: () => void;
        };

        act(() => reload.onStart());
        await user.click(within(sheet).getByRole('checkbox'));
        expect(
            within(sheet).getByRole('button', { name: 'Confirm · RWF 5,000' }),
        ).toBeDisabled();
        act(() => reload.onFinish());

        await user.click(
            within(sheet).getByRole('button', { name: 'Confirm · RWF 5,000' }),
        );
        expect(inertia.posts).toEqual([
            {
                url: '/preview/investor-checkout-confirmed',
                data: {
                    deal: 'greenleaf',
                    units: 1,
                    revision: 7,
                    disclosure_version: 'disclosure-2026-09.1',
                    acknowledged: true,
                },
            },
        ]);
    });

    it('refuses a short wallet with the reason and a way to deposit', () => {
        render(<InvestorCheckout {...props(shortFixture)} />);

        const alerts = screen.getAllByRole('alert');

        expect(alerts[0]).toHaveTextContent(
            'Amount exceeds your wallet balance. Deposit to your wallet, or lower the amount.',
        );
        expect(
            within(alerts[0]).getByRole('link', { name: 'Deposit' }),
        ).toHaveAttribute('href', '/preview/investor-wallet');
        expect(alerts[1]).toHaveTextContent(
            'Amount exceeds your wallet balance.',
        );
    });

    it('shows progress, a field error, the closing countdown and caps', async () => {
        const user = userEvent.setup();
        const data = props();

        inertia.processing = true;
        inertia.errors = { units: 'Pick at least one note.' };
        data.deal.closes_at = '2026-09-23T20:00:00+02:00';
        data.limits.max_units = 1;
        data.quote.units = 2;
        render(<InvestorCheckout {...data} />);

        expect(
            screen.getByText('2 notes · RWF 5,000 each'),
        ).toBeInTheDocument();
        expect(screen.getByText('CLOSES IN')).toBeInTheDocument();
        expect(
            screen.getByText(/^1[01]:[0-5]\d:[0-5]\d$/u),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Confirming…' }),
        ).toBeDisabled();
        expect(screen.getByRole('alert')).toHaveTextContent(
            'Pick at least one note.',
        );
        expect(
            screen.getByRole('button', { name: 'One note more' }),
        ).toBeDisabled();
        expect(screen.getByRole('button', { name: '10K' })).toBeDisabled();
        await user.click(screen.getByRole('button', { name: '5K' }));
    });

    it('shows the receipt the server returns', () => {
        render(<InvestorCheckout {...props(confirmedFixture)} />);

        const done = screen.getByRole('dialog', { name: 'Done' });

        expect(
            within(done).getByText('Investment confirmed'),
        ).toBeInTheDocument();
        expect(done).toHaveTextContent(
            "You invested RWF 5,000 in GreenLeaf Agro. It's now in your portfolio.",
        );
        expect(
            within(done).getByText('TXN-2026-0923-4417'),
        ).toBeInTheDocument();
        expect(within(done).getByText('RCPT-551204')).toBeInTheDocument();
        expect(within(done).getByText('31 Mar 2027')).toBeInTheDocument();
        expect(
            within(done).getByRole('link', { name: 'View receipt' }),
        ).toHaveAttribute('href', '/preview/investor-wallet-receipt');
        expect(
            within(done).getByRole('link', { name: 'View in portfolio' }),
        ).toHaveAttribute('href', '/preview/investor-portfolio');
        expect(
            within(done).getByRole('link', { name: 'Explore more deals' }),
        ).toBeInTheDocument();
    });

    it('centres the sheet over the deck and invest bar on a wide screen', () => {
        setWide(true);
        const { unmount } = render(<InvestorCheckout {...props()} />);

        expect(screen.getByRole('dialog', { name: 'Checkout' })).toHaveClass(
            'w-[376px]',
        );
        unmount();
        render(<InvestorCheckout {...props(confirmedFixture)} />);
        expect(screen.getByRole('dialog', { name: 'Done' })).toHaveClass(
            'w-[376px]',
        );
    });
});
