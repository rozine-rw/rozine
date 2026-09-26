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
import InvestorDeal from '@/pages/investor/deal';
import InvestorDeals from '@/pages/investor/deals';
import type {
    C3InvestorDealProps,
    C3InvestorDealsProps,
} from '@/types/investor';
import fullyReservedFixture from '../../../resources/fixtures/ui/investor-deal-fully-reserved.json';
import photosUnavailableFixture from '../../../resources/fixtures/ui/investor-deal-photos-unavailable.json';
import dealFixture from '../../../resources/fixtures/ui/investor-deal.json';
import disbursingFixture from '../../../resources/fixtures/ui/investor-deals-disbursing.json';
import emptyFixture from '../../../resources/fixtures/ui/investor-deals-empty.json';
import gatedFixture from '../../../resources/fixtures/ui/investor-deals-gated.json';
import minimalFixture from '../../../resources/fixtures/ui/investor-deals-live-minimal.json';
import restrictedFixture from '../../../resources/fixtures/ui/investor-deals-restricted.json';
import dealsFixture from '../../../resources/fixtures/ui/investor-deals.json';
import { inertia, resetInertia, setWide } from './inertia-mock';

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
        expect(
            screen.getByRole('link', { name: 'Wallet balance' }),
        ).toHaveTextContent('RWF 1,253,485+RWF 912K · 3 Oct');
        expect(
            screen.getByRole('link', { name: 'Notifications, 2 unread' }),
        ).toHaveAttribute('href', '/preview/investor-deals');

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
        expect(bar).toHaveTextContent('RWF5,621');
        expect(
            within(bar).getByRole('link', { name: 'Invest' }),
        ).toHaveAttribute(
            'href',
            '/preview/investor-checkout?deal=cmp_greenleaf&units=1',
        );
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
        expect(screen.queryByText(/withdraw/iu)).not.toBeInTheDocument();
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
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['focus', 'quote'],
            data: { deal: 'cmp_sebeya' },
        });
        const other = screen.getByRole('region', {
            name: 'Invest in Sebeya Logistics',
        });

        expect(other).toHaveTextContent('RWF—');
        expect(
            within(other).getByRole('button', { name: 'Invest' }),
        ).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Previous deal' }));
        await user.click(screen.getByRole('button', { name: 'Previous deal' }));
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['focus', 'quote'],
            data: { deal: 'cmp_inzovu' },
        });

        const deck = screen.getByRole('article', { name: 'Inzovu Coffee Co.' });

        fireEvent.pointerMove(deck, { clientX: 10 });
        fireEvent.pointerDown(deck, { clientX: 200 });
        fireEvent.pointerMove(deck, { clientX: 100 });
        fireEvent.pointerUp(deck);
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['focus', 'quote'],
            data: { deal: 'cmp_greenleaf' },
        });

        fireEvent.pointerDown(deck, { clientX: 100 });
        fireEvent.pointerMove(deck, { clientX: 200 });
        fireEvent.pointerCancel(deck);
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['focus', 'quote'],
            data: { deal: 'cmp_inzovu' },
        });

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

    it('gives an unverified investor the gate only, with no deal or business named', () => {
        const { unmount } = render(<InvestorDeals {...deals(gatedFixture)} />);

        expect(
            screen.getByRole('link', { name: 'Verify to invest' }),
        ).toHaveAttribute('href', '/preview/investor-verification');
        expect(
            screen.getByText('Verify to see open deals'),
        ).toBeInTheDocument();
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
        expect(screen.queryByRole('region')).not.toBeInTheDocument();
        expect(screen.queryByText(/GreenLeaf/u)).not.toBeInTheDocument();
        unmount();
        setWide(true);
        render(<InvestorDeals {...deals(gatedFixture)} />);
        expect(
            screen.getByText('Verify to see open deals'),
        ).toBeInTheDocument();
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
        expect(
            screen.queryByText(/Independently audited/u),
        ).not.toBeInTheDocument();
    });

    it('explains the other invest states', () => {
        const pending = deals();

        pending.gate = { status: 'verification_pending' };
        pending.quote = null;
        pending.deals[0].lifecycle = 'funded';
        pending.wallet.next_payout = null;
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
            screen.getByRole('link', { name: 'Wallet balance' }),
        ).toHaveTextContent(/^RWF 1,253,485$/u);
        expect(
            screen.getByRole('link', { name: 'Notifications' }),
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
        expect(
            screen.queryByRole('link', { name: 'Withdraw' }),
        ).not.toBeInTheDocument();

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
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['focus', 'quote'],
            data: { deal: 'cmp_sebeya' },
        });
        await user.click(screen.getByRole('button', { name: 'Previous deal' }));
        expect(inertia.reload).toHaveBeenLastCalledWith({
            only: ['focus', 'quote'],
            data: { deal: 'cmp_greenleaf' },
        });

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
        expect(card).toHaveTextContent('Repayment fee (1% per payout)RWF 54');
        expect(card).toHaveTextContent('RWF 5,621');
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
        expect(
            screen.getByRole('button', { name: 'Fully reserved' }),
        ).toBeDisabled();
        expect(
            screen.getByRole('region', { name: 'YOUR INVESTMENT' }),
        ).toHaveTextContent('—');
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

    it('still points to verification if a gated page ever carries a deal', () => {
        const props = deals();

        props.gate = {
            status: 'verification_required',
            link: { url: '/preview/investor-verification', method: 'get' },
        };
        render(<InvestorDeals {...props} />);

        expect(
            screen.getByRole('link', { name: 'Verify to invest' }),
        ).toHaveAttribute('href', '/preview/investor-verification');
    });

    it('offers Invest only while the server lists primary.reserve', () => {
        const props = deals();

        props.allowed_actions = [];
        render(<InvestorDeals {...props} />);

        expect(screen.getByRole('button', { name: 'Invest' })).toBeDisabled();
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

    it('renders the live-minimal shape', () => {
        render(<InvestorDeals {...deals(minimalFixture)} />);

        expect(screen.getByText('No deals open right now')).toBeInTheDocument();
    });
});
