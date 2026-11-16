import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorMarket from '@/pages/investor/market';
import type { InvestorMarketProps } from '@/types/investor';
import listingsFixture from '../../../resources/fixtures/ui/investor-market-listings.json';
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

const page = () =>
    structuredClone(listingsFixture.props) as unknown as InvestorMarketProps;

beforeEach(() => {
    resetInertia();
    finishReloads();
    setWide(false);
});

describe('Market resale (BRS v3.0 §12)', () => {
    it('lists live listings with what can be done with each', () => {
        render(<InvestorMarket {...page()} />);

        const list = screen.getByRole('list', { name: 'Resale listings' });
        const cards = within(list).getAllByRole('listitem');

        expect(cards).toHaveLength(3);
        expect(
            within(cards[0]).getByText('40 units at RWF 5,200 each'),
        ).toBeInTheDocument();
        expect(within(cards[0]).getByText('RWF 208,000')).toBeInTheDocument();
        expect(
            within(cards[0]).getByRole('button', { name: 'Buy' }),
        ).toBeInTheDocument();
        expect(
            within(cards[1]).getByRole('button', { name: 'Confirm purchase' }),
        ).toBeInTheDocument();
        expect(within(cards[2]).getByText('Your listing')).toBeInTheDocument();
        expect(
            within(cards[2]).getByRole('button', { name: 'Withdraw' }),
        ).toBeInTheDocument();
    });

    it('reserves the chosen units and reloads the board', async () => {
        const user = userEvent.setup();

        inertia.queue.push(
            answers({ status: 'completed', code: 'SECONDARY_RESERVED' }),
        );
        render(<InvestorMarket {...page()} />);

        await user.click(screen.getAllByRole('button', { name: 'Buy' })[0]);
        const sheet = screen.getByRole('dialog', {
            name: 'Buy units of Kivu Harvest Ltd',
        });
        const units = within(sheet).getByLabelText('Units');

        await user.clear(units);
        await user.type(units, '15');
        expect(
            within(sheet).getByText('You pay RWF 78,000'),
        ).toBeInTheDocument();
        await user.click(
            within(sheet).getByRole('button', { name: 'Reserve units' }),
        );

        expect(inertia.calls[0]).toMatchObject({
            url: '/investor/market/listings/01kc0000000000000000000001/reserve',
            body: { units: '15', identity_context_revision: 3 },
        });
        expect(inertia.reload).toHaveBeenCalled();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('confirms a reservation, and shows a refusal by its code', async () => {
        const user = userEvent.setup();

        inertia.queue.push(fails(409, { code: 'TRADING_PAUSED' }));
        render(<InvestorMarket {...page()} />);

        await user.click(
            screen.getByRole('button', { name: 'Confirm purchase' }),
        );

        expect(inertia.calls[0]).toMatchObject({
            url: '/investor/market/listings/01kc0000000000000000000002/settle',
            body: { reservation_id: '01kc00000000000000000000r1' },
        });
        expect(screen.getByRole('alert')).toHaveTextContent(/TRADING_PAUSED/);
        expect(inertia.reload).not.toHaveBeenCalled();
    });

    it('lists units of a held lot no higher than the cap', async () => {
        const user = userEvent.setup();

        inertia.queue.push(
            answers({ status: 'completed', code: 'SECONDARY_LISTED' }),
        );
        render(<InvestorMarket {...page()} />);

        await user.click(screen.getByRole('button', { name: 'Sell units' }));
        const sheet = screen.getByRole('dialog', {
            name: 'Sell units on the market',
        });
        const submit = within(sheet).getByRole('button', {
            name: 'List for sale',
        });

        await user.type(within(sheet).getByLabelText('Units'), '20');
        await user.clear(within(sheet).getByLabelText('Price per unit (RWF)'));
        await user.type(
            within(sheet).getByLabelText('Price per unit (RWF)'),
            '5500',
        );
        expect(submit).toBeDisabled();
        await user.clear(within(sheet).getByLabelText('Price per unit (RWF)'));
        await user.type(
            within(sheet).getByLabelText('Price per unit (RWF)'),
            '5400',
        );
        expect(
            within(sheet).getByText('Listing total RWF 108,000'),
        ).toBeInTheDocument();
        await user.click(submit);

        expect(inertia.calls[0]).toMatchObject({
            url: '/investor/market/listings',
            body: {
                lot_id: '01kc00000000000000000000l1',
                units: '20',
                price: '5400',
            },
        });
    });

    it("shows the Investor's own reservation under Buy Orders", async () => {
        const user = userEvent.setup();

        render(<InvestorMarket {...page()} />);
        await user.click(screen.getByRole('tab', { name: 'Orders' }));
        const orders = screen.getByRole('list', { name: 'Buy Orders' });

        expect(within(orders).getAllByRole('listitem')).toHaveLength(1);
        expect(
            within(orders).getByRole('button', { name: 'Confirm purchase' }),
        ).toBeInTheDocument();
    });
});
