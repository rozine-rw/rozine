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

    it('keeps a filled buy under Buy Orders and opens its detail beside the list (QA #656)', async () => {
        const user = userEvent.setup();

        setWide(true);
        render(<InvestorMarket {...page()} />);
        await user.click(screen.getByRole('tab', { name: 'Orders' }));
        const filled = screen.getByRole('list', { name: 'Filled orders' });

        await user.click(
            within(filled).getByRole('button', {
                name: 'Bought · Kivu Harvest Ltd',
            }),
        );
        const detail = screen.getByRole('complementary', {
            name: 'Order detail',
        });

        expect(within(detail).getByText('Filled')).toBeInTheDocument();
        expect(within(detail).getByText('You paid')).toBeInTheDocument();
        expect(within(detail).getByText('RWF 50,000')).toBeInTheDocument();
    });

    it('opens a sell listing in the detail pane with Withdraw (QA #655)', async () => {
        const user = userEvent.setup();

        setWide(true);
        render(<InvestorMarket {...page()} />);
        await user.click(screen.getByRole('tab', { name: 'Orders' }));
        const sides = screen.getByRole('tablist', { name: 'Orders' });
        expect(
            within(sides).getByRole('tab', { name: 'Buy Orders' }),
        ).toHaveAttribute('aria-selected', 'true');
        await user.click(
            within(sides).getByRole('tab', { name: 'Sell Orders' }),
        );
        const orders = screen.getByRole('list', { name: 'Sell Orders' });

        await user.click(within(orders).getAllByRole('listitem')[0]);
        const detail = screen.getByRole('complementary', {
            name: 'Order detail',
        });

        expect(within(detail).getByText('Open')).toBeInTheDocument();
        expect(
            within(detail).getByRole('button', { name: 'Withdraw' }),
        ).toBeInTheDocument();
    });

    it('opens an order as a sheet on a phone', async () => {
        const user = userEvent.setup();

        render(<InvestorMarket {...page()} />);
        await user.click(screen.getByRole('tab', { name: 'Orders' }));
        await user.click(
            screen.getByRole('button', { name: 'Bought · Kivu Harvest Ltd' }),
        );

        expect(
            within(
                screen.getByRole('dialog', { name: 'Order detail' }),
            ).getByText('You paid'),
        ).toBeInTheDocument();
    });

    it('confirms a settled purchase with a link to the Portfolio (QA #656)', async () => {
        const user = userEvent.setup();

        inertia.queue.push(
            answers({ status: 'completed', code: 'SECONDARY_SETTLED' }),
        );
        render(<InvestorMarket {...page()} />);
        await user.click(
            screen.getByRole('button', { name: 'Confirm purchase' }),
        );
        const status = await screen.findByRole('status');

        expect(status).toHaveTextContent(/You bought \d+ units? of /);
        expect(
            within(status).getByRole('link', { name: 'See it in Portfolio' }),
        ).toHaveAttribute('href', '/preview/investor-portfolio?tab=secondary');
    });

    it('says how many units are listed when more are asked for', async () => {
        const user = userEvent.setup();

        render(<InvestorMarket {...page()} />);
        await user.click(screen.getAllByRole('button', { name: 'Buy' })[0]);
        const sheet = screen.getByRole('dialog', {
            name: 'Buy units of Kivu Harvest Ltd',
        });
        const units = within(sheet).getByLabelText('Units');

        await user.clear(units);
        await user.type(units, '41');

        expect(within(sheet).getByRole('alert')).toHaveTextContent(
            'Only 40 units are listed.',
        );
        expect(
            within(sheet).getByRole('button', { name: 'Reserve units' }),
        ).toBeDisabled();
        // No price is shown for an amount that can't be bought (QA #663 item 4).
        expect(within(sheet).queryByText(/You pay/)).not.toBeInTheDocument();
    });

    it('says why a sell listing is refused: too many units, or a price above the cap (QA #663 item 3)', async () => {
        const user = userEvent.setup();

        render(<InvestorMarket {...page()} />);
        await user.click(screen.getByRole('button', { name: 'Sell units' }));
        const sheet = screen.getByRole('dialog', {
            name: 'Sell units on the market',
        });
        const free = page().market.sellable[0].free_units;

        await user.type(
            within(sheet).getByLabelText('Units'),
            String(Number(free) + 1),
        );
        expect(within(sheet).getByRole('alert')).toHaveTextContent(
            `You have ${free} units free to list.`,
        );
        expect(
            within(sheet).queryByText(/Listing total/),
        ).not.toBeInTheDocument();

        await user.clear(within(sheet).getByLabelText('Units'));
        await user.type(within(sheet).getByLabelText('Units'), '1');
        await user.clear(within(sheet).getByLabelText('Price per unit (RWF)'));
        await user.type(
            within(sheet).getByLabelText('Price per unit (RWF)'),
            '5500',
        );
        expect(within(sheet).getByRole('alert')).toHaveTextContent(
            'At most RWF 5,400 per unit',
        );
    });

    it('shows how many units are still available while another buyer holds some (QA #663 item 5)', () => {
        const props = page();

        props.market.listings[0].reserved_units = '15';
        render(<InvestorMarket {...props} />);
        const card = within(
            screen.getByRole('list', { name: 'Resale listings' }),
        ).getAllByRole('listitem')[0];

        expect(
            within(card).getByText('25 available · 15 reserved by a buyer'),
        ).toBeInTheDocument();
    });

    it('asks before withdrawing a listing, then confirms it and keeps it under Cancelled (QA #663 item 1)', async () => {
        const user = userEvent.setup();

        inertia.queue.push(
            answers({
                status: 'completed',
                code: 'SECONDARY_LISTING_CANCELLED',
            }),
        );
        render(<InvestorMarket {...page()} />);
        const mine = within(
            screen.getByRole('list', { name: 'Resale listings' }),
        ).getAllByRole('listitem')[2];

        await user.click(
            within(mine).getByRole('button', { name: 'Withdraw' }),
        );
        expect(inertia.calls).toHaveLength(0);
        await user.click(
            within(mine).getByRole('button', { name: 'Keep listing' }),
        );
        expect(inertia.calls).toHaveLength(0);
        await user.click(
            within(mine).getByRole('button', { name: 'Withdraw' }),
        );
        await user.click(
            within(mine).getByRole('button', { name: 'Yes, withdraw' }),
        );

        expect(inertia.calls[0]?.url).toMatch(/\/withdraw$/u);
        const status = await screen.findByRole('status');

        expect(status).toHaveTextContent(/Listing withdrawn\. \d+ units? of /);
        await user.click(
            within(status).getByRole('button', { name: 'See it in Cancelled' }),
        );
        const cancelled = screen.getByRole('list', { name: 'Cancelled' });

        expect(within(cancelled).getByText('Withdrawn')).toBeInTheDocument();
        await user.click(
            within(cancelled).getByRole('button', {
                name: /Listing · Kivu Harvest Ltd/u,
            }),
        );
        expect(
            within(
                screen.getByRole('dialog', { name: 'Order detail' }),
            ).getByText('Units listed'),
        ).toBeInTheDocument();
    });
});
