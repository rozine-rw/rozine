import { mergeDataIntoQueryString } from '@inertiajs/core';
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
import { ACADEMY } from '@/components/investor/profile/academy';
import InvestorDeal from '@/pages/investor/deal';
import InvestorDeals from '@/pages/investor/deals';
import type {
    C3InvestorDealProps,
    C3InvestorDealsProps,
} from '@/types/investor';
import cancelledFixture from '../../../resources/fixtures/ui/investor-deal-cancelled.json';
import expiredFixture from '../../../resources/fixtures/ui/investor-deal-expired.json';
import failedClosingFixture from '../../../resources/fixtures/ui/investor-deal-failed-closing.json';
import fullyReservedFixture from '../../../resources/fixtures/ui/investor-deal-fully-reserved.json';
import fundedFixture from '../../../resources/fixtures/ui/investor-deal-funded.json';
import issuedFixture from '../../../resources/fixtures/ui/investor-deal-issued.json';
import photosUnavailableFixture from '../../../resources/fixtures/ui/investor-deal-photos-unavailable.json';
import dealFixture from '../../../resources/fixtures/ui/investor-deal.json';
import allClosedFixture from '../../../resources/fixtures/ui/investor-deals-all-closed.json';
import disbursingFixture from '../../../resources/fixtures/ui/investor-deals-disbursing.json';
import emptyFixture from '../../../resources/fixtures/ui/investor-deals-empty.json';
import gatedFixture from '../../../resources/fixtures/ui/investor-deals-gated.json';
import minimalFixture from '../../../resources/fixtures/ui/investor-deals-live-minimal.json';
import restrictedFixture from '../../../resources/fixtures/ui/investor-deals-restricted.json';
import dealsFixture from '../../../resources/fixtures/ui/investor-deals.json';
import { answers, fails, inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

/* The deck renders many cards; under coverage instrumentation a journey can pass five seconds. */
vi.setConfig({ testTimeout: 30_000 });

const deals = (fixture: { props: unknown } = dealsFixture) =>
    structuredClone(fixture.props) as C3InvestorDealsProps;
const deal = (fixture: { props: unknown } = dealFixture) =>
    structuredClone(fixture.props) as C3InvestorDealProps;

beforeEach(() => {
    resetInertia();
    setWide(false);
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Deals on a phone', () => {
    it('shows the wallet, filters, the deck and the invest bar from server facts', () => {
        render(<InvestorDeals {...deals()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Deals');
        /* On a short phone the photo gives up height, never the figures, so nothing is clipped. */
        expect(screen.getAllByTestId('swipe-card-photo')[0]).toHaveClass(
            'flex-[1_1_46%]',
        );
        expect(screen.getAllByTestId('swipe-card-figures')[0]).toHaveClass(
            'shrink-0',
        );
        expect(
            screen.getByRole('link', { name: 'Wallet balance' }),
        ).toHaveTextContent('RWF 1,253,485+RWF 912K · 3 Oct');
        expect(
            screen.getByRole('button', { name: 'Notifications, 2 unread' }),
        ).toHaveAttribute('aria-haspopup', 'dialog');

        const sorts = screen.getByRole('navigation', { name: 'Sort deals' });

        expect(
            within(sorts).getByRole('link', { name: 'All' }),
        ).toHaveAttribute('aria-current', 'true');
        expect(
            within(sorts).getByRole('link', { name: 'Top Interest' }),
        ).not.toHaveAttribute('aria-current');

        const industries = screen.getByRole('navigation', {
            name: 'Filter by industry',
        });

        expect(
            within(industries).getByRole('link', { name: /All\s*6/u }),
        ).toHaveAttribute('aria-current', 'true');

        const front = screen.getByRole('article', { name: 'GreenLeaf Agro' });

        expect(within(front).getByText('RWF 32,800,000')).toBeInTheDocument();
        expect(within(front).getByText('/ RWF 42M')).toBeInTheDocument();
        expect(within(front).getByText('AUDITED')).toBeInTheDocument();
        expect(within(front).getByText('Strong')).toBeInTheDocument();
        expect(within(front).getByText('12 days')).toBeInTheDocument();
        expect(within(front).getByText('RWF 9,200,000')).toBeInTheDocument();
        expect(
            within(front).getByRole('progressbar', {
                name: 'GreenLeaf Agro funding progress',
            }),
        ).toHaveValue(78.1);
        expect(
            within(front).getByRole('link', { name: 'Open GreenLeaf Agro' }),
        ).toHaveAttribute('href', '/preview/investor-deal');

        const bar = screen.getByRole('region', {
            name: 'Invest in GreenLeaf Agro',
        });

        expect(bar).toHaveTextContent('of 1,840 left');
        expect(bar).toHaveTextContent('RWF5,000');
        expect(bar).toHaveTextContent('13.5%');
        expect(bar).toHaveTextContent('RWF5,574');
        expect(
            within(bar).getByRole('link', { name: 'Invest' }),
        ).toHaveAttribute(
            'href',
            '/preview/investor-checkout?deal=cmp_greenleaf&units=1',
        );
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
        expect(screen.queryByText(/withdraw/iu)).not.toBeInTheDocument();
    });

    it('fills Get back from the price and rate while the server sends no quote', () => {
        const unquoted = deals();

        unquoted.quote = null;
        render(<InvestorDeals {...unquoted} />);

        const bar = screen.getByRole('region', {
            name: 'Invest in GreenLeaf Agro',
        });

        expect(bar).toHaveTextContent('RWF5,000');
        expect(bar).toHaveTextContent('RWF5,675');
        expect(bar).not.toHaveTextContent('RWF—');
    });

    it('re-quotes a new quantity from the server instead of multiplying', () => {
        vi.useFakeTimers();
        render(<InvestorDeals {...deals()} />);

        const bar = screen.getByRole('region', {
            name: 'Invest in GreenLeaf Agro',
        });
        const [box, slider] = within(bar).getAllByLabelText('Number of notes');

        fireEvent.focus(box);
        fireEvent.change(box, { target: { value: '3x' } });
        expect(box).toHaveValue('3x');
        fireEvent.keyDown(box, { key: 'Tab' });
        fireEvent.keyDown(box, { key: 'Enter' });
        expect(box).toHaveValue('3');
        act(() => vi.advanceTimersByTime(250));

        expect(inertia.reload).toHaveBeenLastCalledWith(
            expect.objectContaining({
                only: ['quote'],
                data: { deal: 'cmp_greenleaf', units: 3 },
            }),
        );

        const options = inertia.reload.mock.lastCall?.[0] as {
            onStart: () => void;
            onFinish: () => void;
        };

        act(() => options.onStart());
        act(() => options.onFinish());

        fireEvent.change(box, { target: { value: '0' } });
        fireEvent.blur(box);
        expect(box).toHaveValue('3');

        fireEvent.change(box, { target: { value: '999' } });
        fireEvent.blur(box);
        expect(box).toHaveValue('200');

        fireEvent.change(slider, { target: { value: '2' } });
        act(() => vi.advanceTimersByTime(250));
        expect(inertia.reload).toHaveBeenLastCalledWith(
            expect.objectContaining({
                data: { deal: 'cmp_greenleaf', units: 2 },
            }),
        );
        expect(
            within(bar).getByRole('link', { name: 'Invest' }),
        ).toHaveAttribute(
            'href',
            '/preview/investor-checkout?deal=cmp_greenleaf&units=2',
        );
    });

    it('browses the deck by drag or by the keyboard buttons and reloads the focus', async () => {
        const user = userEvent.setup();

        render(<InvestorDeals {...deals()} />);

        await user.click(screen.getByRole('button', { name: 'Next deal' }));
        expect(inertia.visit).toHaveBeenLastCalledWith(
            { url: '/preview/investor-deals?deal=cmp_sebeya', method: 'get' },
            { preserveState: true, preserveScroll: true, replace: true },
        );
        const other = screen.getByRole('region', {
            name: 'Invest in Sebeya Logistics',
        });

        expect(other).toHaveTextContent('RWF—');
        expect(
            within(other).getByRole('button', { name: 'Invest' }),
        ).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Previous deal' }));
        await user.click(screen.getByRole('button', { name: 'Previous deal' }));
        expect(inertia.visit).toHaveBeenLastCalledWith(
            { url: '/preview/investor-deals?deal=cmp_inzovu', method: 'get' },
            { preserveState: true, preserveScroll: true, replace: true },
        );

        const deck = screen.getByRole('article', { name: 'Inzovu Coffee Co.' });

        fireEvent.pointerMove(deck, { clientX: 10 });
        fireEvent.pointerDown(deck, { clientX: 200 });
        fireEvent.pointerMove(deck, { clientX: 100 });
        fireEvent.pointerUp(deck);
        expect(inertia.visit).toHaveBeenLastCalledWith(
            {
                url: '/preview/investor-deals?deal=cmp_greenleaf',
                method: 'get',
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );

        fireEvent.pointerDown(deck, { clientX: 100 });
        fireEvent.pointerMove(deck, { clientX: 200 });
        fireEvent.pointerCancel(deck);
        expect(inertia.visit).toHaveBeenLastCalledWith(
            { url: '/preview/investor-deals?deal=cmp_inzovu', method: 'get' },
            { preserveState: true, preserveScroll: true, replace: true },
        );

        fireEvent.pointerDown(deck, { clientX: 100 });
        fireEvent.pointerMove(deck, { clientX: 110 });
        fireEvent.pointerUp(deck);
        const open = screen.getByRole('link', {
            name: 'Open Inzovu Coffee Co.',
        });

        expect(fireEvent.click(open)).toBe(false);
        expect(fireEvent.click(open)).toBe(true);

        fireEvent.pointerDown(deck, { clientX: 100 });
        fireEvent.pointerMove(deck, { clientX: 103 });
        fireEvent.pointerUp(deck);
        expect(fireEvent.click(open)).toBe(true);
    });

    it('steps through a card’s photos without opening the deal', async () => {
        const user = userEvent.setup();
        const props = deals();

        props.deals[0].photos[1].url = 'https://example.test/ops.jpg';
        render(<InvestorDeals {...props} />);

        const card = screen.getByRole('article', { name: 'GreenLeaf Agro' });
        const previous = within(card).getByRole('button', {
            name: 'Previous photo of GreenLeaf Agro',
        });
        const next = within(card).getByRole('button', {
            name: 'Next photo of GreenLeaf Agro',
        });

        expect(previous).toBeDisabled();
        await user.click(next);
        expect(previous).toBeEnabled();
        expect(within(card).getByRole('presentation')).toHaveAttribute(
            'src',
            'https://example.test/ops.jpg',
        );
        await user.click(next);
        await user.click(next);
        await user.click(next);
        expect(next).toBeDisabled();
        await user.click(previous);
        expect(next).toBeEnabled();
    });

    it('shows an unverified person with no deal open the way to verify, and no wallet', () => {
        const { unmount } = render(<InvestorDeals {...deals(gatedFixture)} />);

        expect(
            screen.getByRole('link', { name: 'Verify to invest' }),
        ).toHaveAttribute('href', '/preview/investor-verification');
        expect(screen.getByText('No deals open right now')).toBeInTheDocument();
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Wallet balance' }),
        ).not.toBeInTheDocument();
        unmount();
        setWide(true);
        render(<InvestorDeals {...deals(gatedFixture)} />);
        expect(screen.getByText('No deals open right now')).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Wallet balance' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Deposit' }),
        ).not.toBeInTheDocument();
    });

    it('explains the other invest states', () => {
        const pending = deals();

        pending.gate = { status: 'verification_pending' };
        pending.quote = null;
        pending.deals[0].lifecycle = 'funded';
        pending.wallet = null;
        pending.links.wallet = null;
        pending.links.deposit = null;
        pending.unread_notifications = 0;
        const { unmount: unmountSecond } = render(
            <InvestorDeals {...pending} />,
        );

        expect(
            screen.getByRole('button', { name: 'Fully funded' }),
        ).toBeDisabled();
        expect(screen.getAllByText('Fully funded').length).toBeGreaterThan(1);
        expect(screen.getByRole('status')).toHaveTextContent(
            'We’re verifying your identity'.replace('’', "'"),
        );
        expect(
            screen.queryByRole('link', { name: 'Wallet balance' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Notifications' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('region', { name: 'Invest in GreenLeaf Agro' }),
        ).toHaveTextContent('RWF—');
        unmountSecond();

        const restricted = deals();

        restricted.gate = { status: 'restricted' };
        restricted.links.checkout = null;
        render(<InvestorDeals {...restricted} />);
        expect(screen.getByRole('button', { name: 'Invest' })).toBeDisabled();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Investing is paused on this account.',
        );
    });

    it('says when nothing is open, without inventing a next listing date', () => {
        render(<InvestorDeals {...deals(emptyFixture)} />);

        expect(screen.getByText('No deals open right now')).toBeInTheDocument();
        expect(screen.queryByRole('region')).not.toBeInTheDocument();
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
    });

    it('runs the last-day countdown against the server clock, then says closing', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-09-23T09:00:00+02:00'));
        const props = deals();

        props.deals = [props.deals[1], props.deals[2], props.deals[4]];
        props.deals[2].clock.expires_at = '2026-09-22T09:00:00+02:00';
        props.focus = null;
        render(<InvestorDeals {...props} />);

        const card = screen.getByRole('article', { name: 'Sebeya Logistics' });

        expect(within(card).getByText('14:08:21')).toBeInTheDocument();
        act(() => vi.advanceTimersByTime(1000));
        expect(within(card).getByText('14:08:20')).toBeInTheDocument();
        expect(screen.getByText('Closing')).toBeInTheDocument();
        expect(screen.getByText('21 days')).toBeInTheDocument();
        expect(screen.getByText('JUST LISTED')).toBeInTheDocument();
    });
});

describe('The Academy nudge in the phone deck', () => {
    const ACADEMY_KEY = 'rozine.investor.academy.v1';

    beforeEach(() => window.localStorage.clear());
    afterEach(() => window.localStorage.clear());

    it.each(['pointerUp', 'pointerCancel'] as const)(
        'honors the first Later tap after swiping to the Academy nudge with %s',
        async (end) => {
            window.localStorage.setItem(
                ACADEMY_KEY,
                JSON.stringify({ notes: 1 }),
            );
            render(<InvestorDeals {...deals()} />);
            const first = screen.getByRole('article', {
                name: 'GreenLeaf Agro',
            });

            fireEvent.pointerDown(first, { clientX: 200 });
            fireEvent.pointerMove(first, { clientX: 100 });
            fireEvent[end](first);
            const nudge = screen.getByRole('region', {
                name: 'Continue Investor Academy',
            });
            await userEvent.click(
                within(nudge).getByRole('button', { name: 'Later' }),
            );

            expect(
                screen.queryByRole('region', {
                    name: 'Continue Investor Academy',
                }),
            ).not.toBeInTheDocument();
            expect(
                screen.getByRole('region', {
                    name: 'Invest in GreenLeaf Agro',
                }),
            ).toBeInTheDocument();
        },
    );

    it('deals the nudge after the first deal while the Academy is under way, and puts it away for Later', async () => {
        const user = userEvent.setup();

        window.localStorage.setItem(ACADEMY_KEY, JSON.stringify({ notes: 1 }));
        render(<InvestorDeals {...deals()} />);

        expect(
            screen.getByRole('region', { name: 'Invest in GreenLeaf Agro' }),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Next deal' }));

        const nudge = screen.getByRole('region', {
            name: 'Continue Investor Academy',
        });

        expect(nudge).toHaveTextContent('Keep learning');
        expect(nudge).toHaveTextContent(
            '0 of 8 lessons done. Pick up where you left off.',
        );
        expect(
            within(nudge).getByRole('link', { name: 'Continue' }),
        ).toHaveAttribute('href', '/preview/investor-profile#academy');
        // A press on Continue is not the start of a swipe.
        fireEvent.pointerDown(
            within(nudge).getByRole('link', { name: 'Continue' }),
            { clientX: 100 },
        );
        fireEvent.pointerUp(nudge);
        expect(inertia.reload).not.toHaveBeenCalled();
        expect(inertia.visit).not.toHaveBeenCalled();
        expect(
            screen.queryByRole('region', { name: /^Invest in/u }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('navigation', { name: 'Filter by industry' }),
        ).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Next deal' }));
        expect(inertia.visit).toHaveBeenLastCalledWith(
            { url: '/preview/investor-deals?deal=cmp_sebeya', method: 'get' },
            { preserveState: true, preserveScroll: true, replace: true },
        );

        await user.click(screen.getByRole('button', { name: 'Previous deal' }));
        await user.click(
            within(
                screen.getByRole('region', {
                    name: 'Continue Investor Academy',
                }),
            ).getByRole('button', { name: 'Later' }),
        );

        expect(
            screen.queryByRole('region', { name: 'Continue Investor Academy' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('region', { name: 'Invest in Sebeya Logistics' }),
        ).toBeInTheDocument();
    });

    it('wraps past the nudge to the last deal, and deals none once every lesson is done', async () => {
        const user = userEvent.setup();

        window.localStorage.setItem(ACADEMY_KEY, JSON.stringify({ notes: 1 }));
        const { unmount } = render(<InvestorDeals {...deals()} />);

        await user.click(screen.getByRole('button', { name: 'Previous deal' }));
        expect(inertia.visit).toHaveBeenLastCalledWith(
            { url: '/preview/investor-deals?deal=cmp_inzovu', method: 'get' },
            { preserveState: true, preserveScroll: true, replace: true },
        );
        unmount();

        window.localStorage.setItem(
            ACADEMY_KEY,
            JSON.stringify(
                Object.fromEntries(
                    ACADEMY.flatMap((group) => group.lessons).map((lesson) => [
                        lesson.key,
                        lesson.topics.length,
                    ]),
                ),
            ),
        );
        render(<InvestorDeals {...deals()} />);
        await user.click(screen.getByRole('button', { name: 'Next deal' }));

        expect(
            screen.queryByRole('region', { name: 'Continue Investor Academy' }),
        ).not.toBeInTheDocument();
        expect(inertia.visit).toHaveBeenLastCalledWith(
            { url: '/preview/investor-deals?deal=cmp_sebeya', method: 'get' },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    it('deals no nudge without a profile to continue in', async () => {
        const user = userEvent.setup();
        const page = deals();

        page.links.profile = null;
        window.localStorage.setItem(ACADEMY_KEY, JSON.stringify({ notes: 1 }));
        render(<InvestorDeals {...page} />);
        await user.click(screen.getByRole('button', { name: 'Next deal' }));

        expect(
            screen.queryByRole('region', { name: 'Continue Investor Academy' }),
        ).not.toBeInTheDocument();
    });
});

describe('Deals on a wide screen', () => {
    it('draws the desktop deck, invest bar and the focused deal’s panel on a fitted canvas', async () => {
        setWide(true);
        const observe = vi.fn();
        const disconnect = vi.fn();
        let fit: () => void = () => {};

        vi.stubGlobal(
            'ResizeObserver',
            class {
                constructor(callback: () => void) {
                    fit = callback;
                }

                observe = observe;
                disconnect = disconnect;
            },
        );
        vi.spyOn(HTMLElement.prototype, 'clientHeight', 'get').mockReturnValue(
            668,
        );
        vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(
            815,
        );

        const user = userEvent.setup();
        const { unmount } = render(<InvestorDeals {...deals()} />);

        act(() => fit());
        expect(observe).toHaveBeenCalled();
        expect(screen.getByRole('link', { name: 'Deposit' })).toHaveAttribute(
            'href',
            '/preview/investor-wallet-deposit',
        );
        expect(screen.getByRole('link', { name: 'Withdraw' })).toHaveAttribute(
            'href',
            '/preview/investor-wallet-withdraw',
        );

        const card = screen.getByRole('article', { name: 'GreenLeaf Agro' });

        expect(within(card).getByText('RWF 32.8M')).toBeInTheDocument();
        expect(within(card).getByText('12 days')).toBeInTheDocument();

        const panel = screen.getByRole('main');

        expect(within(panel).getByText('Strong 4.2')).toBeInTheDocument();
        expect(
            within(panel).getByText('6,560 of 8,400 notes'),
        ).toBeInTheDocument();
        expect(within(panel).getByText('RWF 96M')).toBeInTheDocument();
        expect(within(panel).queryByText('103847291')).not.toBeInTheDocument();
        expect(
            within(panel).getByText(
                /Grows, cools and distributes fresh produce/u,
            ),
        ).toBeInTheDocument();
        expect(
            within(panel).getAllByText('Agriculture · Gasabo').length,
        ).toBeGreaterThan(0);
        expect(within(panel).getByText('Live')).toBeInTheDocument();

        await user.click(
            within(panel).getByRole('button', {
                name: /Independently audited/u,
            }),
        );
        expect(
            within(panel).getByText('sha256:c41d7a9038…'),
        ).toBeInTheDocument();
        expect(panel).toHaveTextContent('Governed toleranceRWF 50,000');
        expect(panel).toHaveTextContent(
            /reconcile to the reported inflow, within the governed tolerance/u,
        );
        expect(panel).not.toHaveTextContent(
            /GEO-TAGGED|Download signed|Cash \/ MoMo|Variance/u,
        );

        await user.click(
            within(panel).getByRole('button', { name: /August 2026/u }),
        );
        expect(
            screen.getByRole('dialog', { name: 'August 2026 monthly report' }),
        ).toBeInTheDocument();
        await user.keyboard('{Escape}');
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Next deal' }));
        expect(inertia.visit).toHaveBeenLastCalledWith(
            { url: '/preview/investor-deals?deal=cmp_sebeya', method: 'get' },
            { preserveState: true, preserveScroll: true, replace: true },
        );
        await user.click(screen.getByRole('button', { name: 'Previous deal' }));
        expect(inertia.visit).toHaveBeenLastCalledWith(
            {
                url: '/preview/investor-deals?deal=cmp_greenleaf',
                method: 'get',
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );

        unmount();
        expect(disconnect).toHaveBeenCalled();
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('keeps an empty desktop Deals honest', () => {
        setWide(true);
        render(<InvestorDeals {...deals(emptyFixture)} />);

        expect(screen.getByText('No deals open right now')).toBeInTheDocument();
    });

    it('opens a deal as the desktop panel rather than a separate page', () => {
        setWide(true);
        render(<InvestorDeal {...deal()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('GreenLeaf Agro');
        expect(
            screen.getByRole('region', { name: 'Invest in GreenLeaf Agro' }),
        ).toBeInTheDocument();
    });
});

describe('Deal detail on a phone', () => {
    it('lays out the evidence with its provenance and the investment card', async () => {
        const user = userEvent.setup();

        render(<InvestorDeal {...deal()} />);

        expect(
            screen.getByRole('heading', { name: 'GreenLeaf Agro' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/investor-deals',
        );
        expect(
            screen.getByText('6,560 of 8,400 notes sold'),
        ).toBeInTheDocument();
        expect(screen.getAllByText('Reported by business')).toHaveLength(3);
        expect(screen.getByText('RWF 96M')).toBeInTheDocument();
        expect(screen.queryByText('RWF 46M')).not.toBeInTheDocument();
        expect(screen.queryByText('RWF 12M')).not.toBeInTheDocument();
        expect(screen.getByText('4.2 / 5')).toBeInTheDocument();
        expect(screen.getByText('6 months')).toBeInTheDocument();
        expect(screen.queryByText('RDB company code')).not.toBeInTheDocument();
        expect(screen.queryByText(/\bTIN\b/u)).not.toBeInTheDocument();
        expect(
            screen.queryByText('KG 11 Ave, Gasabo, Kigali'),
        ).not.toBeInTheDocument();
        expect(screen.getAllByText('Image unavailable').length).toBeGreaterThan(
            0,
        );

        const card = screen.getByRole('region', { name: 'YOUR INVESTMENT' });

        expect(card).toHaveTextContent('RWF 5,000 × 1 note');
        expect(card).toHaveTextContent('+RWF 675');
        expect(card).toHaveTextContent(
            'Fee on earnings · 15.0% (Standard)RWF 101',
        );
        expect(card).toHaveTextContent('RWF 5,574');
        expect(card).not.toHaveTextContent('1%');
        expect(
            within(card).getByRole('button', { name: 'One note fewer' }),
        ).toBeDisabled();

        await user.click(
            within(card).getByRole('button', { name: 'One note more' }),
        );
        await vi.waitFor(() =>
            expect(inertia.reload).toHaveBeenLastCalledWith(
                expect.objectContaining({
                    data: { deal: 'cmp_greenleaf', units: 2 },
                }),
            ),
        );
        expect(
            within(card).getByRole('link', { name: 'Invest' }),
        ).toHaveAttribute(
            'href',
            '/preview/investor-checkout?deal=cmp_greenleaf&units=2',
        );
        await user.click(
            within(card).getByRole('button', { name: 'One note fewer' }),
        );
        expect(within(card).getByText('1')).toBeInTheDocument();

        const updates = screen.getByRole('region', { name: 'Monthly updates' });

        expect(
            within(updates).getAllByRole('button', { name: /net/u }),
        ).toHaveLength(3);
        await user.click(
            within(updates).getByRole('button', { name: 'Show more' }),
        );
        expect(
            within(updates).getAllByRole('button', { name: /net/u }),
        ).toHaveLength(4);
        await user.click(
            within(updates).getByRole('button', { name: 'Show less' }),
        );

        await user.click(
            within(updates).getAllByRole('button', { name: /net/u })[1],
        );
        const sheet = screen.getByRole('dialog', {
            name: 'July 2026 monthly report',
        });

        expect(within(sheet).getByText('On watch')).toBeInTheDocument();
        expect(within(sheet).getByText('-RWF 250,000')).toBeInTheDocument();
        expect(
            within(sheet).getByText('Audited on site by Diane Uwase, CPA'),
        ).toBeInTheDocument();

        await user.click(
            within(sheet).getByRole('button', {
                name: 'Open photo: Warehouse stock',
            }),
        );
        const photo = screen.getByRole('dialog', { name: 'Warehouse stock' });

        expect(within(photo).getByText('1 / 3')).toBeInTheDocument();
        expect(photo).not.toHaveTextContent(
            /Required shot|Added by auditor|GPS|-1\.94/u,
        );
        await user.click(
            within(photo).getByRole('button', { name: 'Previous photo' }),
        );
        expect(
            screen.getByRole('dialog', { name: 'Cold room' }),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Next photo' }));
        await user.click(screen.getByRole('button', { name: 'Next photo' }));
        expect(
            screen.getByRole('dialog', { name: 'POS register' }),
        ).toBeInTheDocument();
        await user.click(
            within(
                screen.getByRole('dialog', { name: 'POS register' }),
            ).getByRole('button', { name: 'Close' }),
        );
        expect(
            screen.queryByRole('dialog', { name: 'POS register' }),
        ).not.toBeInTheDocument();

        await user.click(within(sheet).getByRole('button', { name: 'Close' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('shows a fully reserved deal as unbuyable and explains why', () => {
        render(<InvestorDeal {...deal(fullyReservedFixture)} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Every note is reserved right now',
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            "Every available note is currently held in other investors' checkouts. Check back later.",
        );
        expect(document.body).not.toHaveTextContent(/lapse/i);
        expect(document.body).not.toHaveTextContent(/come back to this raise/i);
        expect(
            screen.getByRole('button', { name: 'Fully reserved' }),
        ).toBeDisabled();
        const card = screen.getByRole('region', { name: 'YOUR INVESTMENT' });

        expect(card).toHaveTextContent('—');
        expect(card).toHaveTextContent('Fee on earnings—');
    });

    it('handles a deal with no audit, record, photos, reports, and an overdue report', async () => {
        const user = userEvent.setup();
        const props = deal();

        props.deal.audit = null;
        props.deal.track_record = null;
        props.deal.photos = [];
        props.deal.updates = [];
        props.deal.overdue_report = { month: '2026-08-01T00:00:00+02:00' };
        props.deal.restriction = {
            code: 'NOTE_INELIGIBLE',
            since: '2026-09-20T00:00:00+02:00',
        };
        props.gate = {
            status: 'verification_required',
            link: { url: '/verify', method: 'get' },
        };
        const { unmount } = render(<InvestorDeal {...props} />);

        expect(
            screen.getByText("The business hasn't published any photos."),
        ).toBeInTheDocument();
        expect(
            screen.queryByText(/Independently audited/u),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('Track record on Rozine'),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('No monthly reports published yet.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('August 2026 report overdue · compliance watch'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                "Investing is paused: this note isn't eligible right now",
            ),
        ).toBeInTheDocument();
        expect(screen.getByText(/Since 20 Sept 2026/u)).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Verify to invest' }),
        ).toHaveAttribute('href', '/verify');
        unmount();

        const other = deal();

        other.deal.track_record = {
            raises: 1,
            on_time_pct: null,
            repaid: { currency: 'RWF', amount: '0' },
        };
        other.deal.updates[0].auditor_note = '';
        other.deal.updates[0].business_note = '';
        other.deal.updates[0].photos = [];
        other.gate = { status: 'restricted' };
        render(<InvestorDeal {...other} />);
        expect(screen.getByText('—')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Invest' })).toBeDisabled();
        await user.click(screen.getAllByRole('button', { name: /net/u })[0]);
        const sheet = screen.getByRole('dialog');

        expect(
            within(sheet).queryByText("AUDITOR'S NOTE"),
        ).not.toBeInTheDocument();
        expect(
            within(sheet).queryByText('FROM THE BUSINESS'),
        ).not.toBeInTheDocument();
        expect(
            within(sheet).queryByText('PUBLISHED PHOTOS'),
        ).not.toBeInTheDocument();
    });

    it('states a cancelled raise and an audit with no cited tolerance on the desktop panel', async () => {
        setWide(true);
        const user = userEvent.setup();
        const props = deals();

        const focus = props.focus as NonNullable<C3InvestorDealsProps['focus']>;
        const audit = focus.audit as NonNullable<typeof focus.audit>;

        audit.tolerance = null;
        focus.lifecycle = 'cancelled';
        focus.financials.ebitda = {
            value: null,
            unavailable: 'NOT_SOURCED_AS_EBITDA',
        };
        focus.track_record = {
            raises: 2,
            on_time_pct: null,
            repaid: { currency: 'RWF', amount: '0' },
        };
        render(<InvestorDeals {...props} />);

        expect(
            screen.getByText('The business cancelled this raise'),
        ).toBeInTheDocument();
        expect(screen.getByText('Cancelled')).toBeInTheDocument();
        expect(screen.getByText('Not sourced as EBITDA')).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: /Independently audited/u }),
        );
        expect(
            screen.queryByText('Governed tolerance'),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('RECONCILIATION STATEMENT'),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: /Hide$/u }));
        expect(
            screen.queryByText('RECONCILIATION STATEMENT'),
        ).not.toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Show more' }));
        expect(
            screen.getByRole('button', { name: 'Show less' }),
        ).toBeInTheDocument();
    });
});

describe('Deal edge states', () => {
    it('fans only four cards each side of a long desktop deck and marks a sold-out card', () => {
        setWide(true);
        const props = deals();
        const extra = props.deals.map((entry, index) => ({
            ...entry,
            campaign_id: `${entry.campaign_id}-${index}`,
            name: `${entry.name} ${index}`,
        }));

        props.deals = [...props.deals, ...extra];
        props.deals[0].lifecycle = 'funded';
        props.industries[0].active = false;
        props.industries[1].active = true;
        render(<InvestorDeals {...props} />);

        expect(
            screen.getAllByRole('article', { hidden: true }).length,
        ).toBeLessThan(12);
        expect(
            screen.getByRole('article', { name: 'GreenLeaf Agro' }),
        ).toHaveTextContent('Fully funded');

        /* The photo takes up any spare height and the figures keep theirs, so the card is full. */
        const card = screen.getByRole('article', { name: 'GreenLeaf Agro' });

        expect(within(card).getByTestId('desk-card-photo')).toHaveClass(
            'flex-1',
        );
        expect(within(card).getByTestId('desk-card-figures')).toHaveClass(
            'shrink-0',
        );
        expect(screen.getByTestId('desk-deck-stack')).toHaveClass(
            'max-h-[560px]',
        );

        const industries = screen.getByRole('navigation', {
            name: 'Filter by industry',
        });

        expect(
            within(industries).getByRole('link', { name: /Agriculture/u }),
        ).toHaveAttribute('aria-current', 'true');
        expect(
            within(industries).getByRole('link', { name: /Energy/u }),
        ).toHaveClass('blur-[.4px]');
    });

    it('labels a multi-note quote and shows a proof photo when its file exists', async () => {
        const user = userEvent.setup();
        const props = deal();
        const quote = props.quote as NonNullable<C3InvestorDealProps['quote']>;

        quote.units = '2';
        quote.amount = { currency: 'RWF', amount: '10000' };
        props.deal.updates[0].photos[0].url = 'https://example.test/stock.jpg';
        render(<InvestorDeal {...props} />);

        expect(
            screen.getByRole('region', { name: 'YOUR INVESTMENT' }),
        ).toHaveTextContent('RWF 5,000 × 2 notes');
        await user.click(screen.getByRole('button', { name: 'One note more' }));
        await vi.waitFor(() => expect(inertia.reload).toHaveBeenCalled());
        const reload = inertia.reload.mock.lastCall?.[0] as {
            onStart: () => void;
        };

        act(() => reload.onStart());
        await user.click(screen.getAllByRole('button', { name: /net/u })[0]);
        await user.keyboard('a');
        expect(
            screen.getByRole('dialog', { name: 'August 2026 monthly report' }),
        ).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Open photo: Warehouse stock' }),
        );
        expect(
            within(
                screen.getByRole('dialog', { name: 'Warehouse stock' }),
            ).getAllByRole('presentation')[0],
        ).toHaveAttribute('src', 'https://example.test/stock.jpg');
    });
});

describe('Deals in C3 states', () => {
    it('keeps a restricted campaign live but unreservable, naming the restriction apart from its lifecycle', () => {
        setWide(true);
        render(<InvestorDeals {...deals(restrictedFixture)} />);

        const panel = screen.getByRole('main');

        expect(
            within(panel).getByText(
                'Investing is paused: a restriction applies',
            ),
        ).toBeInTheDocument();
        expect(within(panel).getByText('Live')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Paused' })).toBeDisabled();
        expect(
            screen.queryByRole('link', { name: 'Invest' }),
        ).not.toBeInTheDocument();
    });

    it('shows a disbursing campaign as paying out, not yet confirmed', () => {
        render(<InvestorDeals {...deals(disbursingFixture)} />);

        expect(
            screen.getByRole('button', { name: 'Paying out' }),
        ).toBeDisabled();
        expect(
            screen.getByRole('article', { name: 'GreenLeaf Agro' }),
        ).toHaveTextContent('Paying out');
    });

    it('lets a person still being verified browse the deck, with no wallet and verify in place of invest', () => {
        const props = deals();

        props.gate = {
            status: 'verification_required',
            link: { url: '/preview/investor-verification', method: 'get' },
        };
        props.wallet = null;
        props.links.wallet = null;
        props.links.deposit = null;
        const { unmount } = render(<InvestorDeals {...props} />);

        expect(
            screen.getByRole('article', { name: 'GreenLeaf Agro' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Verify to invest' }),
        ).toHaveAttribute('href', '/preview/investor-verification');
        expect(
            screen.queryByRole('link', { name: 'Wallet balance' }),
        ).not.toBeInTheDocument();
        unmount();
        setWide(true);
        render(<InvestorDeals {...props} />);
        expect(
            screen.getAllByRole('link', { name: 'Verify to invest' })[0],
        ).toHaveAttribute('href', '/preview/investor-verification');
        expect(
            screen.queryByRole('link', { name: 'Wallet balance' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Deposit' }),
        ).not.toBeInTheDocument();
    });

    it('shows the balance without a deposit link when the server sends none', () => {
        const props = deals();

        props.links.deposit = null;
        setWide(true);
        render(<InvestorDeals {...props} />);

        expect(
            screen.getByRole('link', { name: 'Wallet balance' }),
        ).toHaveTextContent('RWF 1,253,485');
        expect(
            screen.queryByRole('link', { name: 'Deposit' }),
        ).not.toBeInTheDocument();
    });

    it('offers Invest only while the server lists primary.reserve', () => {
        const props = deals();

        props.allowed_actions = [];
        render(<InvestorDeals {...props} />);

        expect(screen.getByRole('button', { name: 'Invest' })).toBeDisabled();
    });

    it('opens the checkout from the focused live deal when the server sends no quote, as the live Deals page does', async () => {
        const user = userEvent.setup();
        const props = deals();

        props.quote = null;
        props.allowed_actions = [];
        render(<InvestorDeals {...props} />);

        const bar = screen.getByRole('region', {
            name: 'Invest in GreenLeaf Agro',
        });

        expect(bar).toHaveTextContent('RWF5,000');
        expect(
            within(bar).getByRole('link', { name: 'Invest' }),
        ).toHaveAttribute(
            'href',
            '/preview/investor-checkout?deal=cmp_greenleaf&units=1',
        );

        fireEvent.change(within(bar).getByRole('slider'), {
            target: { value: '3' },
        });
        expect(bar).toHaveTextContent('RWF15,000');
        expect(
            within(bar).getByRole('link', { name: 'Invest' }),
        ).toHaveAttribute(
            'href',
            '/preview/investor-checkout?deal=cmp_greenleaf&units=3',
        );

        await user.click(screen.getByRole('button', { name: 'Next deal' }));
        expect(
            within(
                screen.getByRole('region', {
                    name: 'Invest in Sebeya Logistics',
                }),
            ).getByRole('button', { name: 'Invest' }),
        ).toBeDisabled();
    });

    it('opens the checkout from an unquoted deal page at the deal’s unit price', async () => {
        const user = userEvent.setup();
        const props = deal();

        props.quote = null;
        props.allowed_actions = [];
        render(<InvestorDeal {...props} />);

        expect(screen.getByText('RWF 5,000 × 1 note')).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'One note more' }));
        expect(screen.getByText('RWF 5,000 × 2 notes')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Invest' })).toHaveAttribute(
            'href',
            '/preview/investor-checkout?deal=cmp_greenleaf&units=2',
        );
    });

    it('counts a single investor in the singular', () => {
        const props = deals();

        props.deals[0].investors = 1;
        render(<InvestorDeals {...props} />);

        expect(
            screen.getByRole('article', { name: 'GreenLeaf Agro' }),
        ).toHaveTextContent(/1\s*investor(?!s)/);
    });

    it('names the cap that binds once the chosen quantity reaches it', () => {
        const props = deals();
        const quote = props.quote as NonNullable<C3InvestorDealsProps['quote']>;

        quote.capacity.max_units = '1';
        quote.capacity.binding = 'raise_cap';
        const { unmount } = render(<InvestorDeals {...props} />);

        expect(
            screen.getByText(
                'Up to 1 note: your single-investor limit for this raise.',
            ),
        ).toBeInTheDocument();
        unmount();

        const none = deal();
        const noneQuote = none.quote as NonNullable<
            C3InvestorDealProps['quote']
        >;

        noneQuote.capacity.max_units = '0';
        noneQuote.capacity.binding = 'connected_party';
        render(<InvestorDeal {...none} />);
        expect(
            screen.getByText(
                "You can't take any more notes here: you're connected to this business.",
            ),
        ).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Invest' })).toBeDisabled();
    });

    it('shows no published photos and an unsourced EBITDA as unavailable', () => {
        render(<InvestorDeal {...deal(photosUnavailableFixture)} />);

        expect(
            screen.getByText("The business hasn't published any photos."),
        ).toBeInTheDocument();
        expect(screen.getByText('Unavailable')).toBeInTheDocument();
        expect(screen.getByText('Not sourced as EBITDA')).toBeInTheDocument();
        expect(screen.queryByText(/-1\.94/u)).not.toBeInTheDocument();
    });

    it('shows no bell and no rating basis until the live server supplies them', async () => {
        const rationale = /Six years of profitable trading/u;
        const home = deals();
        home.links.notifications = null;
        home.focus =
            home.focus === null ? null : { ...home.focus, rationale: null };
        const { unmount: leavePhone } = render(<InvestorDeals {...home} />);

        expect(
            screen.queryByRole('button', { name: /^Notifications/u }),
        ).not.toBeInTheDocument();
        leavePhone();

        const page = deal();
        page.deal.rationale = null;
        const { unmount: leaveDetail } = render(<InvestorDeal {...page} />);

        expect(screen.queryByText(rationale)).not.toBeInTheDocument();
        leaveDetail();

        setWide(true);
        render(<InvestorDeals {...home} />);

        expect(await screen.findByTestId('head')).toBeInTheDocument();
        expect(screen.queryByText(rationale)).not.toBeInTheDocument();
        // No rationale means no empty "Rozine assessment" heading either.
        expect(screen.queryByText('Rozine assessment')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /^Notifications/u }),
        ).not.toBeInTheDocument();
    });

    it('renders the live-minimal shape', () => {
        render(<InvestorDeals {...deals(minimalFixture)} />);

        expect(screen.getByText('No deals open right now')).toBeInTheDocument();
    });
});

describe('Deals in every closed lifecycle', () => {
    it.each([
        ['funded', fundedFixture, 'Fully funded', 'Fully funded'],
        ['issued', issuedFixture, 'This raise has closed', 'Notes issued'],
        [
            'expired',
            expiredFixture,
            "This raise didn't fill in time",
            "Didn't fill",
        ],
        [
            'cancelled',
            cancelledFixture,
            'The business cancelled this raise',
            'Cancelled',
        ],
        [
            'failed_closing',
            failedClosingFixture,
            'This raise closed without paying out',
            'Closed and refunded',
        ],
    ] as const)(
        'names a %s raise and offers no way to invest, on a phone and a wide screen',
        (lifecycle, fixture, title, label) => {
            const props = deal(fixture);

            expect(props.deal.lifecycle).toBe(lifecycle);
            expect(props.allowed_actions).not.toContain('primary.reserve');

            const { unmount } = render(<InvestorDeal {...props} />);

            expect(screen.getByRole('status')).toHaveTextContent(title);
            expect(screen.getByRole('button', { name: label })).toBeDisabled();
            expect(screen.getByText('TIME LEFT')).toBeInTheDocument();
            expect(
                screen.queryAllByText(/^\d+ days?$|^\d\d:\d\d:\d\d$/u),
            ).toHaveLength(0);
            expect(
                screen.queryByRole('link', { name: 'Invest' }),
            ).not.toBeInTheDocument();
            expect(
                screen.queryByRole('button', { name: 'Invest' }),
            ).not.toBeInTheDocument();
            unmount();

            setWide(true);
            render(<InvestorDeal {...deal(fixture)} />);

            expect(
                within(screen.getByRole('main')).getAllByRole('status')[0],
            ).toHaveTextContent(title);
            expect(screen.getByRole('button', { name: label })).toBeDisabled();
            expect(
                screen.queryByRole('link', { name: 'Invest' }),
            ).not.toBeInTheDocument();
        },
    );
});

describe('A live audit summary', () => {
    it('names the Audit Partner without a standard or reconciliation the seal does not record', async () => {
        const user = userEvent.setup();
        const props = deal();

        if (props.deal.audit === null) {
            throw new Error('fixture has an audit');
        }

        props.deal.audit = {
            ...props.deal.audit,
            standard: null,
            reconciliation_statement: null,
        };
        render(<InvestorDeal {...props} />);

        const summary = screen.getByRole('button', {
            name: /^Independently audited/u,
        });

        expect(summary).toHaveTextContent(props.deal.audit.partner);
        await user.click(summary);
        expect(screen.queryByText('Standard')).not.toBeInTheDocument();
        expect(
            screen.queryByText('RECONCILIATION STATEMENT'),
        ).not.toBeInTheDocument();
        expect(screen.getByText('Audit Partner')).toBeInTheDocument();
    });
});

describe('An open raise awaiting settlement', () => {
    it.each([
        [
            'sold_out_pending_settlement',
            'Every note is committed',
            'Fully committed',
            false,
        ],
        [
            'inventory_unavailable',
            'No notes are available right now',
            'No notes available',
            true,
        ],
        [
            'closing_pending_settlement',
            'This raise has closed',
            'Closing',
            false,
        ],
    ] as const)(
        'names a %s raise without claiming it was funded or refunded',
        (lifecycle, title, label, counting) => {
            const props = deal();
            props.deal.lifecycle = lifecycle;
            render(<InvestorDeal {...props} />);

            expect(screen.getByRole('status')).toHaveTextContent(title);
            expect(screen.getByRole('status')).not.toHaveTextContent(
                /refunded|Fully funded/u,
            );
            expect(screen.getByRole('button', { name: label })).toBeDisabled();
            expect(
                screen.queryAllByText(/^\d+ days?$|^\d\d:\d\d:\d\d$/u).length >
                    0,
            ).toBe(counting);
        },
    );
});

describe('A failed closing', () => {
    it('names no cause, since a failed check and a verified, reconciled payout failure both end here', () => {
        render(<InvestorDeal {...deal(failedClosingFixture)} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            "The payout to the business didn't go ahead, so every commitment was refunded in full.",
        );
        expect(screen.getByRole('status')).not.toHaveTextContent(
            /check|provider/iu,
        );
    });
});

describe('A deck with nothing open to reserve', () => {
    const line =
        'Every open raise is fully reserved or closed right now. New raises appear here as they go live.';

    it('says so in one line on a phone, keeping every card', () => {
        render(<InvestorDeals {...deals(allClosedFixture)} />);

        expect(screen.getByRole('status')).toHaveTextContent(line);
        expect(
            screen.getByRole('article', { name: 'GreenLeaf Agro' }),
        ).toHaveTextContent('Fully reserved');
        expect(
            screen.getByRole('button', { name: 'Fully reserved' }),
        ).toBeDisabled();
        expect(
            screen.queryByRole('link', { name: 'Invest' }),
        ).not.toBeInTheDocument();
    });

    it('says so in the top bar on a wide screen, so the deck keeps its height', async () => {
        const user = userEvent.setup();

        setWide(true);
        render(<InvestorDeals {...deals(allClosedFixture)} />);

        expect(screen.getByText(line)).toHaveAttribute('role', 'status');
        expect(
            within(
                screen.getByRole('article', { name: 'GreenLeaf Agro' }),
            ).queryByText(line),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Fully reserved' }),
        ).toBeDisabled();
        expect(screen.getByText(/of [\d,]+ left/u)).toBeInTheDocument();

        /* A funded raise in front: no countdown, no live pulse, no notes left to pick, and never
         * the previous deal's detail beside it while its own is on the way. */
        await user.click(screen.getByRole('button', { name: 'Next deal' }));

        const sebeya = screen.getByRole('article', {
            name: 'Sebeya Logistics',
        });

        expect(
            within(sebeya).queryByText(/^\d\d:\d\d:\d\d$|^\d+ days?$/u),
        ).not.toBeInTheDocument();
        expect(screen.queryByText(/of \d+ left/u)).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Fully funded' }),
        ).toBeDisabled();
        expect(
            screen.getByRole('status', { name: 'Loading deal details' }),
        ).toHaveAttribute('aria-busy', 'true');
        expect(
            screen.queryByText('Every note is reserved right now'),
        ).not.toBeInTheDocument();
    });

    it('drops the countdown and live pulse from a closed card on a phone', () => {
        const props = deals(allClosedFixture);

        props.focus = null;
        props.deals = [props.deals[1], ...props.deals.slice(2)];
        render(<InvestorDeals {...props} />);

        const sebeya = screen.getByRole('article', {
            name: 'Sebeya Logistics',
        });

        expect(
            within(sebeya).queryByText(/^\d\d:\d\d:\d\d$|^\d+ days?$/u),
        ).not.toBeInTheDocument();
        expect(within(sebeya).getByText('Fully funded')).toBeInTheDocument();
    });

    it('counts a live but restricted raise as closed to new reservations', () => {
        const props = deals(allClosedFixture);

        props.deals[1].lifecycle = 'live';
        props.deals[1].restriction = {
            code: 'RESTRICTION_ACTIVE',
            since: '2026-09-20T00:00:00+02:00',
        };
        render(<InvestorDeals {...props} />);

        expect(screen.getByText(line)).toBeInTheDocument();
    });

    it('stays quiet once any raise is live and unrestricted', () => {
        const props = deals(allClosedFixture);

        props.deals[1].lifecycle = 'live';
        const { unmount } = render(<InvestorDeals {...props} />);

        expect(screen.queryByText(line)).not.toBeInTheDocument();
        unmount();

        setWide(true);
        render(<InvestorDeals {...deals(restrictedFixture)} />);

        expect(screen.queryByText(line)).not.toBeInTheDocument();
    });
});

describe('Add to cart', () => {
    it.each(['pointerUp', 'pointerCancel'] as const)(
        'honors the first cart tap after a drag ending with %s',
        async (end) => {
            const page = deals();
            const next = page.deals[1];
            inertia.queue.push(answers({ data: { in_cart: true } }));
            const { rerender } = render(<InvestorDeals {...page} />);
            const first = screen.getByRole('article', {
                name: page.deals[0].name,
            });

            fireEvent.pointerDown(first, { clientX: 200 });
            fireEvent.pointerMove(first, { clientX: 100 });
            fireEvent[end](first);

            const refreshed = structuredClone(page);
            refreshed.focus = { ...refreshed.focus!, ...next };
            refreshed.cart = {
                campaign_ids: [],
                add: {
                    url: `/investor/deals/${next.campaign_id}/cart`,
                    method: 'post',
                },
                remove: null,
            };
            rerender(<InvestorDeals {...refreshed} />);
            await userEvent.click(
                within(
                    screen.getByRole('article', { name: next.name }),
                ).getByRole('button', { name: 'Add to cart' }),
            );

            expect(inertia.calls).toHaveLength(1);
            expect(inertia.calls[0].url).toBe(
                `/investor/deals/${next.campaign_id}/cart`,
            );
        },
    );

    it('opens the selected deal by its detail link when no sort supplies a destination', async () => {
        const page = deals();
        page.sorts = page.sorts.map((sort) => ({ ...sort, active: false }));
        const next = page.deals[1];
        next.links.detail = {
            url: `/investor/deals/${next.campaign_id}`,
            method: 'get',
        };
        render(<InvestorDeals {...page} />);

        await userEvent.click(
            screen.getByRole('button', { name: 'Next deal' }),
        );

        expect(inertia.visit).toHaveBeenLastCalledWith(
            {
                url: `/investor/deals/${next.campaign_id}?deal=${next.campaign_id}`,
                method: 'get',
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
        expect(
            screen.getByRole('region', { name: `Invest in ${next.name}` }),
        ).toBeInTheDocument();
    });

    it('refreshes campaign-bound commands and checkout after moving, keeping the server filters', async () => {
        const page = deals();
        const next = page.deals[1];
        page.quote = null;
        page.sorts[0].link = {
            url: '/investor/deals?industry=Agriculture',
            method: 'get',
        };
        page.cart = {
            campaign_ids: [],
            add: { url: '/investor/deals/first/cart', method: 'post' },
            remove: null,
        };
        page.links.checkout = {
            url: '/investor/deals/first/checkout',
            method: 'get',
        };
        const { rerender } = render(<InvestorDeals {...page} />);

        await userEvent.click(
            screen.getByRole('button', { name: 'Next deal' }),
        );
        expect(inertia.visit).toHaveBeenLastCalledWith(
            {
                url: `/investor/deals?industry=Agriculture&deal=${next.campaign_id}`,
                method: 'get',
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );

        const refreshed = structuredClone(page);
        refreshed.focus = { ...refreshed.focus!, ...next };
        refreshed.links.checkout = {
            url: `/investor/deals/${next.campaign_id}/checkout`,
            method: 'get',
        };
        refreshed.cart!.add = {
            url: `/investor/deals/${next.campaign_id}/cart`,
            method: 'post',
        };
        rerender(<InvestorDeals {...refreshed} />);
        const bar = within(
            screen.getByRole('region', { name: `Invest in ${next.name}` }),
        );
        expect(bar.getByRole('link', { name: 'Invest' })).toHaveAttribute(
            'href',
            `/investor/deals/${next.campaign_id}/checkout?deal=${next.campaign_id}&units=1`,
        );
        inertia.queue.push(answers({ data: { in_cart: true } }));
        await userEvent.click(
            within(screen.getByRole('article', { name: next.name })).getByRole(
                'button',
                { name: 'Add to cart' },
            ),
        );
        expect(inertia.calls[0].url).toBe(
            `/investor/deals/${next.campaign_id}/cart`,
        );
    });

    it('keeps a shortened In cart deck usable after its last selected card is removed', async () => {
        const page = deals();
        page.deals = page.deals.slice(0, 2);
        page.focus = { ...page.focus!, ...page.deals[1] };
        page.cart = {
            campaign_ids: page.deals.map((entry) => entry.campaign_id),
            add: null,
            remove: { url: '/investor/cart/second/remove', method: 'post' },
        };
        inertia.shared = { cart_count: 2 };
        inertia.queue.push(answers({ data: { removed: true } }));
        const { rerender } = render(<InvestorDeals {...page} />);
        await userEvent.click(
            screen.getByRole('button', { name: 'In cart · Remove' }),
        );

        const reload = inertia.reload.mock.lastCall![0];
        expect(reload).not.toHaveProperty('only');
        expect(reload.data).toEqual({ deal: undefined });
        expect(
            mergeDataIntoQueryString(
                'get',
                '/investor/deals?sort=in_cart&deal=removed',
                reload.data,
            )[0],
        ).toBe('/investor/deals?sort=in_cart');
        const refreshed = structuredClone(page);
        refreshed.deals = refreshed.deals.slice(0, 1);
        refreshed.focus = { ...refreshed.focus!, ...refreshed.deals[0] };
        refreshed.cart!.campaign_ids = [refreshed.deals[0].campaign_id];
        refreshed.cart!.remove = {
            url: '/investor/cart/first/remove',
            method: 'post',
        };
        inertia.shared = { cart_count: 1 };
        rerender(<InvestorDeals {...refreshed} />);
        await act(async () => reload.onFinish());

        expect(
            screen.getByRole('region', { name: 'Invest in GreenLeaf Agro' }),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('link', { name: /^Cart\s*\(1\)$/u }),
        ).toHaveLength(2);
        expect(
            screen.getByRole('button', { name: 'In cart · Remove' }),
        ).toBeEnabled();
    });

    it('refreshes the nested home and badge after a desktop detail cart change', async () => {
        setWide(true);
        const page = deal();
        page.home.cart = {
            campaign_ids: [page.deal.campaign_id],
            add: null,
            remove: { url: '/investor/cart/first/remove', method: 'post' },
        };
        inertia.shared = { cart_count: 1 };
        inertia.queue.push(answers({ data: { removed: true } }));
        const { rerender } = render(<InvestorDeal {...page} />);
        await userEvent.click(
            screen.getByRole('button', { name: 'In cart · Remove' }),
        );
        const reload = inertia.reload.mock.lastCall![0];
        expect(reload).not.toHaveProperty('only');

        const refreshed = structuredClone(page);
        refreshed.home.cart = {
            campaign_ids: [],
            add: { url: '/investor/deals/first/cart', method: 'post' },
            remove: null,
        };
        inertia.shared = { cart_count: 0 };
        rerender(<InvestorDeal {...refreshed} />);
        await act(async () => reload.onFinish());
        expect(
            screen.getByRole('button', { name: 'Add to cart' }),
        ).toBeEnabled();
        expect(
            screen.queryByRole('button', { name: 'In cart · Remove' }),
        ).not.toBeInTheDocument();
        expect(screen.getAllByRole('link', { name: 'Cart' })).toHaveLength(1);

        await userEvent.click(
            screen.getByRole('button', { name: 'Next deal' }),
        );
        expect(inertia.visit).toHaveBeenLastCalledWith(
            expect.objectContaining({
                url: expect.stringContaining(
                    `deal=${page.home.deals[1].campaign_id}`,
                ),
            }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    it('reads In cart · Remove on the phone card for a deal already in the cart and takes it out', async () => {
        inertia.queue.push(
            answers({ data: { removed: true, in_cart: false } }),
        );
        render(<InvestorDeals {...deals()} />);

        expect(
            within(
                screen.getByRole('navigation', { name: 'Sort deals' }),
            ).getByRole('link', { name: 'In cart' }),
        ).toHaveAttribute('href', '/preview/investor-deals');

        const bar = screen.getByRole('article', { name: 'GreenLeaf Agro' });
        const toggle = within(bar).getByRole('button', {
            name: 'In cart · Remove',
        });

        expect(toggle).toHaveAttribute('aria-pressed', 'true');

        await userEvent.click(toggle);

        expect(inertia.calls).toEqual([
            {
                url: '/preview/investor-cart',
                method: 'post',
                body: { identity_context_revision: 5 },
            },
        ]);
        expect(inertia.reload).toHaveBeenCalledWith(
            expect.objectContaining({
                data: { deal: undefined },
            }),
        );
    });

    it('adds the chosen notes, and says when the cart could not be changed', async () => {
        const page = deals();

        page.cart = {
            campaign_ids: [],
            add: { url: '/preview/investor-cart-add', method: 'post' },
            remove: null,
        };
        inertia.queue.push(
            answers(undefined),
            answers({ data: { units: 1, in_cart: true } }),
            fails(409, { code: 'DEAL_FULLY_FUNDED' }),
        );
        render(<InvestorDeals {...page} />);

        const bar = screen.getByRole('article', { name: 'GreenLeaf Agro' });
        const toggle = within(bar).getByRole('button', {
            name: 'Add to cart',
        });

        expect(toggle).toHaveAttribute('aria-pressed', 'false');

        await userEvent.click(toggle);

        expect(inertia.calls[0]).toEqual({
            url: '/preview/investor-cart-add',
            method: 'post',
            body: { identity_context_revision: 5, units: 1 },
        });
        expect(within(bar).getByRole('alert')).toHaveTextContent(
            "We couldn't update your cart. Refresh and try again.",
        );
        expect(inertia.reload).not.toHaveBeenCalled();

        await userEvent.click(toggle);

        expect(within(bar).queryByRole('alert')).not.toBeInTheDocument();
        expect(inertia.reload).toHaveBeenCalledTimes(1);
        // Until the reload brings the new cart, the toggle cannot repeat the change.
        expect(toggle).toBeDisabled();
        act(() =>
            (
                inertia.reload.mock.calls[0][0] as { onFinish: () => void }
            ).onFinish(),
        );
        expect(toggle).toBeEnabled();

        await userEvent.click(toggle);

        expect(within(bar).getByRole('alert')).toBeInTheDocument();
        expect(inertia.calls).toHaveLength(3);
    });

    it('cannot add a fully funded deal', () => {
        const page = deals();

        page.deals[0].units.available = '0';
        page.cart = {
            campaign_ids: [],
            add: { url: '/preview/investor-cart-add', method: 'post' },
            remove: null,
        };
        render(<InvestorDeals {...page} />);

        expect(
            screen.getByRole('button', { name: 'Fully funded' }),
        ).toBeDisabled();
        expect(
            screen.queryByRole('button', { name: 'Add to cart' }),
        ).not.toBeInTheDocument();
    });

    it('waits for the server’s command and offers no cart where the Investor has none', () => {
        const page = deals();

        page.cart = { campaign_ids: [], add: null, remove: null };
        const { unmount } = render(<InvestorDeals {...page} />);

        expect(
            screen.getByRole('button', { name: 'Add to cart' }),
        ).toBeDisabled();
        unmount();

        render(<InvestorDeals {...deals(restrictedFixture)} />);

        expect(
            screen.queryByRole('button', { name: /cart/u }),
        ).not.toBeInTheDocument();
    });

    it('says when the cart could not be changed from the wide panel', async () => {
        setWide(true);
        const page = deals();

        page.cart = {
            campaign_ids: [],
            add: { url: '/preview/investor-cart-add', method: 'post' },
            remove: null,
        };
        inertia.queue.push(fails(409, { code: 'DEAL_FULLY_FUNDED' }));
        render(<InvestorDeals {...page} />);

        await userEvent.click(
            screen.getAllByRole('button', { name: 'Add to cart' })[0],
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            "We couldn't update your cart. Refresh and try again.",
        );
    });

    it('counts a single investor in the singular, on a phone and a wide screen', () => {
        const page = deals();

        page.deals[0].investors = 1;
        const { unmount } = render(
            <InvestorDeals {...structuredClone(page)} />,
        );
        expect(screen.getAllByText(/^investor$/u).length).toBeGreaterThan(0);
        unmount();

        setWide(true);
        render(<InvestorDeals {...page} />);
        expect(screen.getAllByText(/^investor$/u).length).toBeGreaterThan(0);
    });

    it('counts a single investor in the singular on the deal page', () => {
        const page = deal();

        page.deal.investors = 1;
        render(<InvestorDeal {...page} />);
        expect(screen.getAllByText(/^investor$/u).length).toBeGreaterThan(0);
    });

    it('puts the toggle in the focused deal’s panel on a wide screen', async () => {
        setWide(true);
        inertia.queue.push(answers({ data: { removed: true } }));
        render(<InvestorDeals {...deals()} />);

        const toggles = screen.getAllByRole('button', {
            name: 'In cart · Remove',
        });

        expect(toggles).toHaveLength(1);

        await userEvent.click(toggles[0]);

        expect(inertia.calls).toHaveLength(1);
        expect(inertia.reload).toHaveBeenCalledWith(
            expect.objectContaining({
                data: { deal: undefined },
            }),
        );
    });
});
