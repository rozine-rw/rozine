import { act, fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorCart from '@/pages/investor/cart';
import InvestorMarket from '@/pages/investor/market';
import type {
    C3InvestorCartProps,
    InvestorShellPageProps,
} from '@/types/investor';
import emptyCartFixture from '../../../resources/fixtures/ui/investor-cart-empty.json';
import cartFixture from '../../../resources/fixtures/ui/investor-cart.json';
import marketFixture from '../../../resources/fixtures/ui/investor-market.json';
import { answers, fails, inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as InvestorShellPageProps;
const cart = (fixture: { props: unknown } = cartFixture) =>
    structuredClone(fixture.props) as C3InvestorCartProps;

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
        const page = cart(emptyCartFixture);
        page.links.market = null;

        render(<InvestorCart {...page} />);

        expect(
            screen.queryByRole('link', { name: 'Market' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getAllByRole('link', { name: 'Cart' })[0],
        ).toHaveAttribute('aria-current', 'page');
    });

    it('counts the cart lines on the Cart tab, and draws no badge for an empty cart', () => {
        inertia.shared = { cart_count: 3 };
        const { unmount } = render(
            <InvestorCart {...cart(emptyCartFixture)} />,
        );

        const tabs = screen
            .getAllByRole('link')
            .filter((link) => link.textContent.includes('Cart'));
        expect(tabs.map((link) => link.textContent)).toEqual([
            '3Cart',
            '3Cart (3)',
        ]);
        unmount();

        inertia.shared = { cart_count: 0 };
        render(<InvestorCart {...cart(emptyCartFixture)} />);
        expect(
            screen
                .getAllByRole('link')
                .filter((link) => link.textContent.includes('Cart'))
                .map((link) => link.textContent),
        ).toEqual(['Cart', 'Cart']);
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
        render(<InvestorCart {...cart(emptyCartFixture)} />);

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
        const page = cart(emptyCartFixture);
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

describe('Cart with notes', () => {
    it('shows each line with the deal facts and its return, and a total of the open lines only', () => {
        render(<InvestorCart {...cart()} />);

        expect(screen.getByText('2 notes')).toBeInTheDocument();
        expect(screen.getByTestId('cart-total')).toHaveTextContent('70,000');
        expect(screen.getByTestId('cart-get-back')).toHaveTextContent(
            'RWF 79,550',
        );
        expect(
            screen.getByText(
                "1 note is fully funded and won't be checked out.",
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Each note checks out on its own for now: open its checkout to reserve and pay.',
            ),
        ).toBeInTheDocument();

        const greenleaf = within(screen.getByTestId('cart-line-cmp_greenleaf'));

        expect(
            greenleaf.getByRole('link', { name: 'GreenLeaf Agro' }),
        ).toHaveAttribute('href', '/preview/investor-deal');
        expect(greenleaf.getByText('Agriculture · Gasabo')).toBeInTheDocument();
        expect(greenleaf.getByText('RWF 32,800,000')).toBeInTheDocument();
        expect(greenleaf.getByText('of RWF 42M')).toBeInTheDocument();
        expect(greenleaf.getByText('RWF 32,000,000')).toBeInTheDocument();
        expect(greenleaf.getByText('RWF 9,200,000')).toBeInTheDocument();
        expect(greenleaf.getByText('6,560')).toBeInTheDocument();
        expect(greenleaf.getByText('13.5%')).toBeInTheDocument();
        expect(greenleaf.getByTestId('line-invest')).toHaveTextContent(
            '50,000',
        );
        expect(greenleaf.getByTestId('line-get-back')).toHaveTextContent(
            '56,750',
        );
        expect(
            greenleaf.getByRole('slider', {
                name: 'Number of notes in GreenLeaf Agro',
            }),
        ).toHaveValue('10');
        expect(
            greenleaf.getByRole('link', { name: 'Check out' }),
        ).toHaveAttribute('href', '/preview/investor-checkout?units=10');

        const sebeya = within(screen.getByTestId('cart-line-cmp_sebeya'));

        expect(sebeya.queryByRole('slider')).not.toBeInTheDocument();
        expect(
            sebeya.queryByRole('link', { name: 'Check out' }),
        ).not.toBeInTheDocument();
        expect(
            sebeya.getByRole('button', { name: 'Fully funded · Remove' }),
        ).toBeInTheDocument();
        expect(sebeya.getByText('Fully funded')).toBeInTheDocument();
    });

    it('moves the total but saves nothing for a line the server offers no change for', () => {
        vi.useFakeTimers();
        const page = cart();
        const line = page.cart?.lines?.find(
            (entry) => entry.campaign_id === 'cmp_greenleaf',
        );

        if (line !== undefined) {
            line.actions.units = null;
        }

        render(<InvestorCart {...page} />);

        const slider = within(
            screen.getByTestId('cart-line-cmp_greenleaf'),
        ).getByRole('slider', { name: 'Number of notes in GreenLeaf Agro' });
        fireEvent.change(slider, { target: { value: '20' } });
        act(() => {
            vi.advanceTimersByTime(400);
        });

        expect(screen.getByTestId('cart-total')).toHaveTextContent('120,000');
        expect(inertia.calls).toHaveLength(0);
        vi.useRealTimers();
    });

    it('moves the total at once with a slider, saves the quantity once it rests and names the cap at the most allowed', () => {
        vi.useFakeTimers();
        inertia.queue.push(answers({ data: { units: 20 } }));
        render(<InvestorCart {...cart()} />);

        const greenleaf = within(screen.getByTestId('cart-line-cmp_greenleaf'));
        const slider = greenleaf.getByRole('slider', {
            name: 'Number of notes in GreenLeaf Agro',
        });

        fireEvent.change(slider, { target: { value: '20' } });

        expect(screen.getByTestId('cart-total')).toHaveTextContent('120,000');
        expect(greenleaf.getByTestId('line-invest')).toHaveTextContent(
            '100,000',
        );
        expect(
            greenleaf.getByRole('link', { name: 'Check out' }),
        ).toHaveAttribute('href', '/preview/investor-checkout?units=20');
        expect(inertia.calls).toHaveLength(0);

        fireEvent.change(slider, { target: { value: '1840' } });
        fireEvent.change(slider, { target: { value: '20' } });
        act(() => {
            vi.advanceTimersByTime(400);
        });

        expect(inertia.calls).toEqual([
            {
                url: '/preview/investor-cart',
                method: 'post',
                body: { identity_context_revision: 3, units: 20 },
            },
        ]);

        fireEvent.change(slider, { target: { value: '1840' } });

        expect(
            greenleaf.getByText(
                "Up to 1840 notes: that's every note still available.",
            ),
        ).toBeInTheDocument();
        vi.useRealTimers();
    });

    it('takes a line out and reloads the cart, or says when a change did not land', async () => {
        inertia.queue.push(answers({ data: { removed: true } }));
        render(<InvestorCart {...cart()} />);

        await userEvent.click(
            screen.getByRole('button', {
                name: 'Remove Volt Energy Rwanda from cart',
            }),
        );

        expect(inertia.calls[0]).toEqual({
            url: '/preview/investor-cart',
            method: 'post',
            body: { identity_context_revision: 3 },
        });
        expect(inertia.reload).toHaveBeenCalledWith({ only: ['cart'] });

        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));

        await userEvent.click(
            screen.getByRole('button', { name: 'Fully funded · Remove' }),
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            "We couldn't update your cart. Refresh and try again.",
        );
        expect(inertia.reload).toHaveBeenCalledTimes(1);
    });

    it('names the cap instead of a slider when no note may be taken, and keeps the total in the side column on a wide screen', () => {
        setWide(true);
        const page = cart();
        const [line] = page.cart?.lines ?? [];

        line.max_units = '0';
        line.binding = 'raise_cap';
        line.checkout = null;
        line.actions.units = null;

        render(<InvestorCart {...page} />);

        const greenleaf = within(screen.getByTestId('cart-line-cmp_greenleaf'));

        expect(greenleaf.queryByRole('slider')).not.toBeInTheDocument();
        expect(
            greenleaf.getByText(
                "You can't take any more notes here: your single-investor limit for this raise.",
            ),
        ).toBeInTheDocument();
        expect(screen.getByTestId('cart-total')).toHaveTextContent('20,000');
        expect(screen.getByText('1 note')).toBeInTheDocument();
        expect(
            screen.getByRole('region', { name: 'CART TOTAL' }),
        ).toBeInTheDocument();
    });

    it('shows the empty cart to a person with no cart yet', () => {
        const page = cart();

        page.cart = null;
        render(<InvestorCart {...page} />);

        expect(screen.getByText('Your cart is empty')).toBeInTheDocument();
        expect(screen.queryByTestId('cart-total')).not.toBeInTheDocument();
    });
});
