import { fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorCart from '@/pages/investor/cart';
import InvestorMarket from '@/pages/investor/market';
import type { InvestorShellPageProps } from '@/types/investor';
import cartFixture from '../../../resources/fixtures/ui/investor-cart.json';
import marketFixture from '../../../resources/fixtures/ui/investor-market.json';
import { resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as InvestorShellPageProps;

beforeEach(() => {
    resetInertia();
    setWide(false);
});

describe('Investor tabs', () => {
    it('lists the design five tabs in order, with Market current on Market', () => {
        render(<InvestorMarket {...props(marketFixture)} />);

        const nav = screen.getAllByRole('navigation')[0];
        const tabs = within(nav)
            .getAllByRole('link')
            .map((link) => link.textContent);

        expect(tabs).toEqual([
            'Deals',
            'Portfolio',
            'Market',
            'Cart',
            'Profile',
        ]);
        expect(
            within(nav).getByRole('link', { name: 'Market' }),
        ).toHaveAttribute('href', '/preview/investor-market');
        expect(within(nav).getByRole('link', { name: 'Cart' })).toHaveAttribute(
            'href',
            '/preview/investor-cart',
        );
        expect(
            within(nav).getByRole('link', { name: 'Market' }),
        ).toHaveAttribute('aria-current', 'page');
    });

    it('leaves out a tab the server does not link', () => {
        const page = props(cartFixture);
        page.links.market = null;

        render(<InvestorCart {...page} />);

        expect(
            screen.queryByRole('link', { name: 'Market' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getAllByRole('link', { name: 'Cart' })[0],
        ).toHaveAttribute('aria-current', 'page');
    });
});

describe('Market', () => {
    it('opens on Browse with its filters and the design empty book on a phone', () => {
        render(<InvestorMarket {...props(marketFixture)} />);

        expect(screen.getByRole('heading', { name: 'Market' })).toHaveClass(
            'text-2xl',
        );
        expect(
            screen.getByText('Trade active Rozine Notes before maturity.'),
        ).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Browse' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        for (const label of ['Performance', 'Status', 'Industry']) {
            expect(screen.getByText(label)).toBeInTheDocument();
        }
        expect(
            screen.getByText('No notes match these filters'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Try adjusting the filters above.'),
        ).toBeInTheDocument();
        expect(screen.getByText('🗂')).toBeInTheDocument();
    });

    it('draws the desktop header without the subtitle, and its Orders view', async () => {
        const user = userEvent.setup();
        setWide(true);

        render(<InvestorMarket {...props(marketFixture)} />);

        expect(screen.getByRole('heading', { name: 'Market' })).toHaveClass(
            'text-[19px]',
        );
        expect(
            screen.queryByText('Trade active Rozine Notes before maturity.'),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Orders' })).toHaveClass(
            'text-xs',
        );

        expect(screen.queryByText('No order selected')).not.toBeInTheDocument();

        await user.click(screen.getByRole('tab', { name: 'Orders' }));

        expect(screen.getByText('No orders in this tab.')).toBeInTheDocument();
        expect(
            screen.getByRole('complementary', { name: 'Order detail' }),
        ).toHaveTextContent(
            "Order detailNo order selectedPick an order on the left to see why it has or hasn't filled.",
        );
    });

    it('switches to Saved and to Orders, each with its own empty state', async () => {
        const user = userEvent.setup();

        render(<InvestorMarket {...props(marketFixture)} />);

        await user.click(screen.getByRole('tab', { name: 'Saved' }));

        expect(screen.getByText('No saved notes yet')).toBeInTheDocument();
        expect(
            screen.getByText('Swipe a note up to save it here for later.'),
        ).toBeInTheDocument();
        expect(screen.getByText('🔖')).toBeInTheDocument();

        await user.click(screen.getByRole('tab', { name: 'Orders' }));

        expect(screen.getByRole('tab', { name: 'Orders' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(screen.queryByText('Performance')).not.toBeInTheDocument();
        expect(screen.getByText('No orders in this tab.')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: /Buy Orders/ }));
        await user.click(screen.getByRole('option', { name: 'Cancelled' }));

        expect(
            screen.getByRole('button', { name: /Cancelled/ }),
        ).toBeInTheDocument();
        expect(screen.getByText('No orders in this tab.')).toBeInTheDocument();
    });

    it('opens a filter, picks an option and closes on a pick, Escape or a click outside', async () => {
        const user = userEvent.setup();

        render(<InvestorMarket {...props(marketFixture)} />);

        const industry = screen.getAllByRole('button', { name: /All/ })[2];

        await user.click(industry);

        expect(industry).toHaveAttribute('aria-expanded', 'true');

        const list = screen.getByRole('listbox', { name: 'Industry' });

        expect(
            within(list)
                .getAllByRole('option')
                .map((option) => option.textContent),
        ).toEqual([
            'All',
            'Agriculture',
            'Manufacturing',
            'Energy',
            'Technology',
            'Logistics',
            'Finance',
        ]);
        expect(
            within(list).getByRole('option', { name: 'All' }),
        ).toHaveAttribute('aria-selected', 'true');

        await user.click(within(list).getByRole('option', { name: 'Energy' }));

        expect(screen.queryByRole('listbox')).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: /Energy/ }),
        ).toHaveTextContent('▼');
        expect(screen.getByText('Energy', { selector: 'span' })).toHaveClass(
            'text-rz-ink',
        );

        await user.click(screen.getByRole('button', { name: /Energy/ }));
        fireEvent.mouseDown(screen.getByRole('listbox'));

        expect(screen.getByRole('listbox')).toBeInTheDocument();

        fireEvent.keyDown(document, { key: 'Tab' });

        expect(screen.getByRole('listbox')).toBeInTheDocument();

        fireEvent.keyDown(document, { key: 'Escape' });

        expect(screen.queryByRole('listbox')).not.toBeInTheDocument();

        await user.click(screen.getAllByRole('button', { name: /All/ })[0]);

        expect(
            screen.getByRole('listbox', { name: 'Performance' }),
        ).toBeInTheDocument();

        fireEvent.mouseDown(document.body);

        expect(screen.queryByRole('listbox')).not.toBeInTheDocument();

        await user.click(screen.getAllByRole('button', { name: /All/ })[1]);
        await user.click(screen.getByRole('option', { name: 'Sold' }));

        expect(
            screen.getByRole('button', { name: /Sold/ }),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: /All/ }));
        await user.click(screen.getByRole('option', { name: 'Highest Yield' }));

        expect(
            screen.getByRole('button', { name: /Highest Yield/ }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('No notes match these filters'),
        ).toBeInTheDocument();
    });
});

describe('Cart', () => {
    it('shows the design empty cart and its way back to Deals on a phone', () => {
        render(<InvestorCart {...props(cartFixture)} />);

        expect(screen.getByRole('heading', { name: 'Cart' })).toHaveClass(
            'text-2xl',
        );
        expect(screen.getByText('Your cart is empty')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Add notes from Deals, set how much you want in each, and check them all out at once.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Browse deals' }),
        ).toHaveAttribute('href', '/preview/investor-deals');
    });

    it('draws the desktop header and offers no way to Deals the server does not link', () => {
        setWide(true);
        const page = props(cartFixture);
        page.links.deals = null;

        render(<InvestorCart {...page} />);

        expect(screen.getByRole('heading', { name: 'Cart' })).toHaveClass(
            'text-[19px]',
        );
        expect(
            screen.queryByRole('link', { name: 'Browse deals' }),
        ).not.toBeInTheDocument();
    });
});
