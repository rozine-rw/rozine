import type * as InertiaCore from '@inertiajs/core';
import { act, fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    CartBand,
    readLineRefusals,
} from '@/components/investor/cart/whole-cart';
import InvestorCart from '@/pages/investor/cart';
import type { Money } from '@/types';
import type {
    C3InvestorCartProps,
    CartCheckout,
    CartQuote,
} from '@/types/investor';
import cartFixture from '../../../resources/fixtures/ui/investor-cart.json';
import {
    answers,
    fails,
    finishReloads,
    inertia,
    invalid,
    offline,
    resetInertia,
    setWide,
} from './inertia-mock';

type ResponseReader = (response: { status: number; data: unknown }) => unknown;

/* The response readers the command hook installs while a command is out, as Inertia's http. */
const responses = vi.hoisted(() => ({ readers: [] as ResponseReader[] }));

vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaCore>()),
    http: {
        onResponse: (reader: ResponseReader) => {
            responses.readers.push(reader);

            return () => {
                responses.readers.splice(responses.readers.indexOf(reader), 1);
            };
        },
    },
}));

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const rwf = (amount: string): Money => ({ currency: 'RWF', amount });
const QUOTE_URL = '/preview/investor-cart-quote';
const RESERVE_URL = '/preview/investor-cart-reserve';
const CONFIRM_URL = '/preview/investor-cart-confirm';
const RELEASE_URL = '/preview/investor-cart-release';

const quoteData = (overrides: Partial<CartQuote> = {}): CartQuote => ({
    id: '01jc3quote0000000000000000',
    revision: 1,
    cart_id: '01jc3cart0000000000000000a',
    cart_revision: 5,
    disclosure_sha256: 'a'.repeat(64),
    principal: rwf('70000'),
    earnings_fee: rwf('1094'),
    net_return: rwf('6200'),
    fee_band: {
        tier: 'standard',
        rate_bps: 1500,
        rate_pct: '15.00',
        held_note_principal: rwf('400000'),
        checkout_total: rwf('70000'),
        active_capital_after_checkout: rwf('470000'),
        next_band_at: rwf('1000000'),
        next_band_distance: rwf('530000'),
    },
    next_fee_band: { tier: 'bronze', rate_bps: 1200, rate_pct: '12.00' },
    lines: [
        {
            cart_line_id: '01jc3line000000000000000a1',
            campaign_id: 'cmp_greenleaf',
            units: '10',
            principal: rwf('50000'),
            contractual_return: rwf('6750'),
            earnings_fee: rwf('1013'),
            net_return: rwf('5737'),
            terms: { rate_pct: '13.5', term_months: 6 },
        },
        {
            cart_line_id: '01jc3line000000000000000a2',
            campaign_id: 'cmp_volt',
            units: '1',
            principal: rwf('20000'),
            contractual_return: rwf('540'),
            earnings_fee: rwf('81'),
            net_return: rwf('459'),
            terms: { rate_pct: '10.8', term_months: 6 },
        },
    ],
    expires_at: '2026-09-23T07:05:00Z',
    ...overrides,
});

const quoted = (data: CartQuote = quoteData()) =>
    answers({
        operation_id: null,
        status: 'completed',
        code: 'CART_QUOTED',
        data,
    });

/** The cart with only open lines, so the whole cart can be quoted. */
const page = (): C3InvestorCartProps => {
    const props = structuredClone(cartFixture.props) as C3InvestorCartProps;

    props.cart!.lines = props.cart!.lines.filter((line) => !line.filled);
    props.actions = {
        quote: { url: QUOTE_URL, method: 'post' },
        reserve: { url: RESERVE_URL, method: 'post' },
    };

    return props;
};

const held = (state: CartCheckout['state'] = 'held'): CartCheckout => ({
    id: '01jc3checkout00000000000000',
    cart_id: '01jc3cart0000000000000000a',
    revision: 1,
    state,
    quote: quoteData(),
    expires_at: '2026-09-23T07:03:00Z',
    lines: [
        /* Reserved fees round per scheduled payout, so they can differ from the quote's. */
        {
            cart_line_id: '01jc3line000000000000000a1',
            campaign_id: 'cmp_greenleaf',
            reservation_id: '01jc3res00000000000000000a',
            principal: rwf('50000'),
            contractual_return: rwf('6750'),
            earnings_fee: rwf('1014'),
            net_return: rwf('5736'),
            disclosure_sha256: 'b'.repeat(64),
        },
        {
            cart_line_id: '01jc3line000000000000000a2',
            campaign_id: 'cmp_volt',
            reservation_id: '01jc3res00000000000000000b',
            principal: rwf('20000'),
            contractual_return: rwf('540'),
            earnings_fee: rwf('81'),
            net_return: rwf('459'),
            disclosure_sha256: 'c'.repeat(64),
        },
    ],
    total_held: rwf('70000'),
    reserved: {
        contractual_return: rwf('7290'),
        earnings_fee: rwf('1095'),
        net_return: rwf('6195'),
    },
    actions:
        state === 'held'
            ? {
                  confirm: { url: CONFIRM_URL, method: 'post' },
                  release: { url: RELEASE_URL, method: 'post' },
              }
            : { confirm: null, release: null },
});

const heldPage = (state: CartCheckout['state'] = 'held') => {
    const props = page();

    props.checkout = held(state);
    props.actions = { quote: null, reserve: null };

    return props;
};

const completed = (code: string, receipt: unknown = {}) =>
    answers({
        operation_id: '01jc3op0000000000000000000',
        status: 'completed',
        code,
        data: { receipt, current: null, next: null },
        revision: 2,
        policy_version: 'cart-checkout-1',
        recorded_at: '2026-09-23T07:00:00Z',
        server_time: '2026-09-23T07:00:00Z',
        allowed_actions: [],
        field_errors: {},
    });

/** A refusal as the cart commands answer it: an operation resource, its facts in `receipt`. */
const refusedBody = (code: string) => ({
    operation_id: '01jc3op0000000000000000000',
    status: 'rejected',
    code,
    data: {
        receipt: {
            atomic: true,
            financial_effect: 'none',
            line_refusals: [
                {
                    cart_line_id: '01jc3line000000000000000a2',
                    campaign_id: 'cmp_volt',
                    code: 'INSUFFICIENT_AVAILABLE_FUNDS',
                    http_status: 422,
                    field_errors: [],
                },
            ],
            cart_refusals: [],
        },
        current: null,
        next: null,
    },
    revision: 5,
    allowed_actions: [],
    field_errors: {},
});

/** A recorded 422 refusal: Inertia hands its body only to response readers, then no answer. */
const refused422 = (body: unknown) => () => {
    responses.readers.forEach((read) =>
        read({ status: 422, data: JSON.stringify(body) }),
    );

    return Promise.resolve(undefined);
};

const flush = () =>
    act(async () => {
        await Promise.resolve();
        await Promise.resolve();
    });

beforeEach(() => {
    resetInertia();
    setWide(false);
    finishReloads();
});

describe('Cart: whole-cart quote', () => {
    it('prices the saved cart on the server and shows its band and the way to the next one', async () => {
        inertia.queue.push(quoted());
        render(<InvestorCart {...page()} />);
        await flush();

        expect(inertia.calls[0]).toEqual({
            url: QUOTE_URL,
            method: 'post',
            body: { identity_context_revision: 3, expected_cart_revision: 5 },
        });
        const band = screen.getByTestId('cart-band');
        expect(band).toHaveTextContent('FEE ON EARNINGS15.0% (Standard)');
        expect(band).toHaveTextContent(
            'RWF 530,000 more unlocks 12.0% (Bronze)',
        );
        expect(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        ).toBeEnabled();
        expect(
            screen.getByText(
                'Holds every note in your cart for 5 minutes while you confirm. Nothing is committed until you confirm.',
            ),
        ).toBeInTheDocument();
    });

    it('keeps the last band pulsing while a quantity change waits to be saved', async () => {
        inertia.queue.push(quoted());
        render(<InvestorCart {...page()} />);
        await flush();

        fireEvent.change(
            screen.getByRole('slider', {
                name: 'Number of notes in GreenLeaf Agro',
            }),
            { target: { value: '20' } },
        );

        expect(screen.getByTestId('cart-band')).toHaveClass('animate-pulse');
        expect(
            screen.getByRole('button', { name: 'Pricing your cart…' }),
        ).toBeDisabled();
    });

    it('keeps the last band pulsing while a changed cart is priced again', async () => {
        inertia.queue.push(quoted());
        inertia.queue.push(() => new Promise(() => undefined));
        const props = page();
        const { rerender } = render(<InvestorCart {...props} />);
        await flush();
        const next = structuredClone(props);
        next.cart!.revision = 6;
        rerender(<InvestorCart {...next} />);

        expect(screen.getByTestId('cart-band')).toHaveClass('animate-pulse');
        expect(inertia.calls[1].body).toEqual({
            identity_context_revision: 3,
            expected_cart_revision: 6,
        });
        /* The old total is never offered for the changed cart (#531). */
        expect(
            screen.queryByRole('button', { name: /RWF 70,000/u }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Pricing your cart…' }),
        ).toBeDisabled();
    });

    it('shows a pulsing band while the cart is priced and offers no checkout yet', () => {
        render(<InvestorCart {...page()} />);

        expect(screen.getByTestId('cart-band-loading')).toHaveAttribute(
            'aria-label',
            'Pricing your cart…',
        );
        expect(
            screen.getByRole('button', { name: 'Pricing your cart…' }),
        ).toBeDisabled();
    });

    it('holds every line of the quoted cart at once and then reloads the cart', async () => {
        inertia.queue.push(quoted());
        inertia.queue.push(completed('CART_RESERVED'));
        render(<InvestorCart {...page()} />);
        await flush();

        fireEvent.click(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        );
        await flush();

        expect(inertia.calls[1]).toMatchObject({
            url: RESERVE_URL,
            method: 'post',
            body: {
                identity_context_revision: 3,
                expected_cart_revision: 5,
                quote_id: '01jc3quote0000000000000000',
                expected_quote_revision: 1,
                quote_disclosure_sha256: 'a'.repeat(64),
                request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u),
            },
        });
        expect(inertia.reload).toHaveBeenCalled();
    });

    it('says it is holding the notes while the hold is on its way', async () => {
        inertia.queue.push(quoted());
        inertia.queue.push(() => new Promise(() => undefined));
        render(<InvestorCart {...page()} />);
        await flush();

        fireEvent.click(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        );
        await flush();

        expect(
            screen.getByRole('button', { name: 'Holding your notes…' }),
        ).toBeDisabled();
    });

    it('stays busy until the held checkout has loaded', async () => {
        inertia.queue.push(quoted());
        inertia.queue.push(completed('CART_RESERVED'));
        inertia.reload.mockImplementation(() => undefined);
        render(<InvestorCart {...page()} />);
        await flush();

        fireEvent.click(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        );
        await flush();

        expect(inertia.reload).toHaveBeenCalled();
        expect(
            screen.getByRole('button', { name: 'Holding your notes…' }),
        ).toHaveAttribute('aria-busy', 'true');
    });

    it('names a refused hold as a whole, and each line it stopped on', async () => {
        inertia.queue.push(quoted());
        inertia.queue.push(fails(409, refusedBody('CART_LINES_REFUSED')));
        inertia.queue.push(quoted());
        render(<InvestorCart {...page()} />);
        await flush();

        fireEvent.click(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        );
        await flush();

        expect(
            screen.getByText(
                "Some deals in your cart can't be bought right now, so nothing was held.",
            ),
        ).toBeInTheDocument();
        expect(screen.getByTestId('line-refusal-cmp_volt')).toHaveTextContent(
            "Your available balance doesn't cover this amount.",
        );
        expect(
            screen.queryByTestId('line-refusal-cmp_greenleaf'),
        ).not.toBeInTheDocument();
    });

    it('names each line a recorded 422 refusal stopped on', async () => {
        inertia.queue.push(quoted());
        inertia.queue.push(
            refused422(refusedBody('INSUFFICIENT_AVAILABLE_FUNDS')),
        );
        inertia.queue.push(quoted());
        render(<InvestorCart {...page()} />);
        await flush();

        fireEvent.click(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        );
        await flush();

        expect(screen.getByTestId('line-refusal-cmp_volt')).toHaveTextContent(
            "Your available balance doesn't cover this amount.",
        );
    });

    it('names each line a refusal recovered by the operation lookup stopped on', async () => {
        inertia.queue.push(quoted());
        inertia.queue.push(offline());
        inertia.queue.push(fails(409, refusedBody('CART_LINES_REFUSED')));
        inertia.queue.push(quoted());
        render(<InvestorCart {...page()} />);
        await flush();

        fireEvent.click(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        );
        await flush();
        await flush();

        expect(inertia.calls[2]).toMatchObject({
            url: expect.stringContaining('/preview/investor-cart-operations/'),
        });
        expect(screen.getByTestId('line-refusal-cmp_volt')).toHaveTextContent(
            "Your available balance doesn't cover this amount.",
        );
    });

    it('names each line a recorded 422 refusal recovered by the operation lookup stopped on', async () => {
        inertia.queue.push(quoted());
        inertia.queue.push(offline());
        inertia.queue.push(
            refused422(refusedBody('INSUFFICIENT_AVAILABLE_FUNDS')),
        );
        inertia.queue.push(quoted());
        render(<InvestorCart {...page()} />);
        await flush();

        fireEvent.click(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        );
        await flush();
        await flush();

        expect(inertia.calls[2]).toMatchObject({
            url: expect.stringContaining('/preview/investor-cart-operations/'),
        });
        expect(screen.getByTestId('line-refusal-cmp_volt')).toHaveTextContent(
            "Your available balance doesn't cover this amount.",
        );
    });

    it('names each line a refusal replayed as a recorded answer stopped on', async () => {
        inertia.queue.push(quoted());
        inertia.queue.push(answers(refusedBody('CART_LINES_REFUSED')));
        inertia.queue.push(quoted());
        render(<InvestorCart {...page()} />);
        await flush();

        fireEvent.click(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        );
        await flush();

        expect(screen.getByTestId('line-refusal-cmp_volt')).toBeInTheDocument();
    });

    it('prices the same cart again after a refused hold and holds it at the fresh quote', async () => {
        inertia.queue.push(quoted());
        inertia.queue.push(fails(409, { code: 'CART_QUOTE_EXPIRED' }));
        inertia.queue.push(
            quoted(quoteData({ id: '01jc3quote0000000000000002' })),
        );
        inertia.queue.push(completed('CART_RESERVED'));
        render(<InvestorCart {...page()} />);
        await flush();

        fireEvent.click(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        );
        await flush();
        await flush();

        expect(inertia.calls[2]).toMatchObject({
            url: QUOTE_URL,
            body: { expected_cart_revision: 5 },
        });
        expect(
            screen.getByText(
                'Your cart price expired. We priced it again — check it and try again.',
            ),
        ).toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        );
        await flush();

        expect(inertia.calls[3]).toMatchObject({
            url: RESERVE_URL,
            body: { quote_id: '01jc3quote0000000000000002' },
        });
        expect(
            screen.queryByText(
                'Your cart price expired. We priced it again — check it and try again.',
            ),
        ).not.toBeInTheDocument();
    });

    it('prices the cart again as its quote expires, by the server clock', async () => {
        vi.useFakeTimers();
        inertia.queue.push(
            answers({
                operation_id: null,
                status: 'completed',
                code: 'CART_QUOTED',
                data: quoteData(),
                server_time: '2026-09-23T07:04:00Z',
            }),
        );
        inertia.queue.push(quoted());
        render(<InvestorCart {...page()} />);
        await act(async () => {
            await Promise.resolve();
            await Promise.resolve();
        });
        expect(inertia.calls).toHaveLength(1);

        await act(async () => {
            vi.advanceTimersByTime(59_000);
        });
        expect(inertia.calls).toHaveLength(1);
        await act(async () => {
            vi.advanceTimersByTime(1_000);
        });

        expect(inertia.calls).toHaveLength(2);
        expect(inertia.calls[1]).toMatchObject({ url: QUOTE_URL });
        vi.useRealTimers();
    });

    it.each([
        [
            fails(409, { code: 'CART_LINE_UNAVAILABLE' }),
            'A deal in your cart is no longer open. Remove it to check out.',
        ],
        [
            invalid({}),
            'Some deals are above what you can take now. Adjust them and try again.',
        ],
        [offline(), 'This request was refused. Refresh and try again.'],
        [
            answers({
                status: 'rejected',
                code: 'CART_REVISION_CONFLICT',
                data: {},
            }),
            'Your cart changed. We refreshed it — check it and try again.',
        ],
    ])(
        'says why the whole cart cannot be priced (%#)',
        async (answer, copy) => {
            inertia.queue.push(answer);
            render(<InvestorCart {...page()} />);
            await flush();

            expect(screen.getByText(copy)).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Check out all · RWF 0' }),
            ).toBeDisabled();
        },
    );

    it('sends the Investor to a deal checkout for the risk statements first', async () => {
        inertia.queue.push(
            fails(409, { code: 'RISK_ACKNOWLEDGEMENT_REQUIRED' }),
        );
        render(<InvestorCart {...page()} />);
        await flush();

        expect(
            screen.getByText(
                /Confirm the risk statements on a deal’s checkout first/u,
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Open checkout' }),
        ).toHaveAttribute('href', '/preview/investor-checkout');
    });

    it('offers no checkout link for the risk statements when no line has one', async () => {
        const props = page();
        props.cart!.lines.forEach((line) => {
            line.checkout = null;
        });
        inertia.queue.push(
            fails(409, { code: 'RISK_ACKNOWLEDGEMENT_REQUIRED' }),
        );
        render(<InvestorCart {...props} />);
        await flush();

        expect(
            screen.queryByRole('link', { name: 'Open checkout' }),
        ).not.toBeInTheDocument();
    });

    it('asks to remove fully funded deals before quoting the whole cart', () => {
        const props = structuredClone(cartFixture.props) as C3InvestorCartProps;
        props.actions = page().actions;
        render(<InvestorCart {...props} />);

        expect(
            screen.getByText(
                'Remove the fully funded deals to check out the whole cart.',
            ),
        ).toBeInTheDocument();
        expect(inertia.calls).toHaveLength(0);
    });

    it('marks a line above the cap, fits it with one tap and prices the cart again once saved', async () => {
        vi.useFakeTimers();
        const props = page();
        props.cart!.lines[0].units = '2000';
        inertia.queue.push(answers({ data: { units: 1840 } }));
        render(<InvestorCart {...props} />);

        expect(
            screen.getByText(
                'Some deals are above what you can take now. Fit them, or remove them, to check out the whole cart.',
            ),
        ).toBeInTheDocument();
        const line = within(screen.getByTestId('cart-line-cmp_greenleaf'));
        expect(
            line.getByText('This line is above what you can take now.'),
        ).toBeInTheDocument();

        fireEvent.click(
            line.getByRole('button', { name: 'Fit to 1840 notes' }),
        );
        await act(async () => {
            vi.advanceTimersByTime(400);
        });
        await act(async () => {
            await Promise.resolve();
        });

        expect(inertia.calls[0].body).toEqual({
            identity_context_revision: 3,
            units: 1840,
        });
        expect(inertia.reload).toHaveBeenCalledWith({ only: ['cart'] });
        vi.useRealTimers();
    });

    it('puts an unsaved quantity back and reloads the cart, and ignores a superseded save', async () => {
        vi.useFakeTimers();
        let finishFirst!: (answer: unknown) => void;
        inertia.queue.push(
            () =>
                new Promise((resolve) => {
                    finishFirst = resolve;
                }),
        );
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        const props = page();
        props.actions = { quote: null, reserve: null };
        render(<InvestorCart {...props} />);
        const slider = screen.getByRole('slider', {
            name: 'Number of notes in GreenLeaf Agro',
        });

        fireEvent.change(slider, { target: { value: '20' } });
        await act(async () => {
            vi.advanceTimersByTime(400);
        });
        fireEvent.change(slider, { target: { value: '30' } });
        await act(async () => {
            vi.advanceTimersByTime(400);
        });
        await act(async () => {
            finishFirst({ data: { units: 20 } });
        });
        await act(async () => {
            await Promise.resolve();
        });

        expect(inertia.calls).toHaveLength(2);
        expect(inertia.reload).toHaveBeenCalledTimes(1);
        expect(slider).toHaveValue('10');
        vi.useRealTimers();
    });

    it('checks out the saved quantity, never an edit that failed to save', async () => {
        vi.useFakeTimers();
        inertia.queue.push(quoted());
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        inertia.queue.push(quoted());
        render(<InvestorCart {...page()} />);
        await act(async () => {
            await Promise.resolve();
        });
        const slider = screen.getByRole('slider', {
            name: 'Number of notes in GreenLeaf Agro',
        });

        fireEvent.change(slider, { target: { value: '30' } });
        expect(
            screen.getByRole('button', { name: 'Pricing your cart…' }),
        ).toBeDisabled();
        await act(async () => {
            vi.advanceTimersByTime(400);
        });
        await act(async () => {
            await Promise.resolve();
            await Promise.resolve();
        });

        expect(slider).toHaveValue('10');
        expect(inertia.reload).toHaveBeenCalledTimes(1);
        expect(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        ).toBeEnabled();
        vi.useRealTimers();
    });

    it('ignores the answer for a cart that has since changed', async () => {
        let finishFirst!: (answer: unknown) => void;
        inertia.queue.push(
            () =>
                new Promise((resolve) => {
                    finishFirst = resolve;
                }),
        );
        inertia.queue.push(quoted(quoteData({ cart_revision: 6 })));
        const props = page();
        const { rerender } = render(<InvestorCart {...props} />);
        const next = structuredClone(props);
        next.cart!.revision = 6;
        rerender(<InvestorCart {...next} />);
        await flush();
        await act(async () => {
            finishFirst({
                operation_id: null,
                status: 'completed',
                code: 'CART_QUOTED',
                data: quoteData({ principal: rwf('99000') }),
            });
        });

        expect(
            screen.getByRole('button', { name: 'Check out all · RWF 70,000' }),
        ).toBeEnabled();
    });

    it('ignores a refusal for a cart that has since changed', async () => {
        let failFirst!: () => void;
        inertia.queue.push(
            () =>
                new Promise((_resolve, reject) => {
                    failFirst = () => reject(new Error('offline'));
                }),
        );
        inertia.queue.push(quoted(quoteData({ cart_revision: 6 })));
        const props = page();
        const { rerender } = render(<InvestorCart {...props} />);
        const next = structuredClone(props);
        next.cart!.revision = 6;
        rerender(<InvestorCart {...next} />);
        await flush();
        await act(async () => failFirst());

        expect(
            screen.queryByText(
                'This request was refused. Refresh and try again.',
            ),
        ).not.toBeInTheDocument();
    });

    it('draws no whole-cart checkout when the server offers none', () => {
        const props = page();
        props.actions = { quote: null, reserve: null };
        render(<InvestorCart {...props} />);

        expect(
            screen.queryByRole('button', { name: /Check out all/u }),
        ).not.toBeInTheDocument();
    });
});

describe('Cart band', () => {
    it('says the top band has no next one, and counts the band basis when no next band is named', () => {
        const band = quoteData().fee_band;
        const { rerender } = render(
            <CartBand
                band={{ ...band, next_band_at: null, next_band_distance: null }}
                next={null}
            />,
        );

        expect(screen.getByTestId('cart-band')).toHaveTextContent(
            "You're on the lowest fee band.",
        );

        rerender(<CartBand band={band} next={undefined} />);

        expect(screen.getByTestId('cart-band')).toHaveTextContent(
            'Your band counts the notes you hold plus this cart, never wallet cash.',
        );

        rerender(
            <CartBand
                band={{
                    ...band,
                    held_note_principal: rwf('0'),
                    checkout_total: rwf('0'),
                    next_band_at: null,
                    next_band_distance: null,
                }}
                next={null}
            />,
        );

        expect(screen.getByTestId('cart-band')).toBeInTheDocument();
    });
});

describe('Cart: the held checkout', () => {
    it('shows every held line, the hold clock and the band, and confirms only once acknowledged', async () => {
        inertia.queue.push(completed('CART_REQUOTED'));
        render(<InvestorCart {...heldPage()} />);

        const panel = screen.getByRole('region', { name: 'Confirm your cart' });
        expect(within(panel).getByRole('timer')).toHaveTextContent(
            /left to confirm/u,
        );
        const greenleaf = within(screen.getByTestId('held-line-cmp_greenleaf'));
        expect(greenleaf.getByText('GreenLeaf Agro')).toBeInTheDocument();
        expect(
            greenleaf.getByText('10 notes · 13.5% return'),
        ).toBeInTheDocument();
        expect(
            greenleaf.getByText('Return RWF 6,750 · fee RWF 1,014'),
        ).toBeInTheDocument();
        expect(greenleaf.getByText('RWF 50,000')).toBeInTheDocument();
        expect(
            within(screen.getByTestId('held-line-cmp_volt')).getByText(
                '1 note · 10.8% return',
            ),
        ).toBeInTheDocument();
        expect(screen.getByTestId('held-total')).toHaveTextContent(
            'RWF 70,000',
        );
        expect(panel).toHaveTextContent('Expected return after feeRWF 6,195');
        expect(
            screen.queryByRole('slider', {
                name: 'Number of notes in GreenLeaf Agro',
            }),
        ).not.toBeInTheDocument();

        const confirm = screen.getByRole('button', {
            name: 'Confirm all · RWF 70,000',
        });
        expect(confirm).toBeDisabled();

        fireEvent.click(
            screen.getByRole('checkbox', {
                name: 'I have read these terms and confirm every note in this cart.',
            }),
        );
        fireEvent.click(confirm);
        await flush();

        expect(inertia.calls[0]).toMatchObject({
            url: CONFIRM_URL,
            body: {
                identity_context_revision: 3,
                checkout_id: '01jc3checkout00000000000000',
                expected_revision: 1,
                quote_id: '01jc3quote0000000000000000',
                expected_quote_revision: 1,
                acknowledged_disclosure_sha256: 'a'.repeat(64),
                acknowledged: true,
            },
        });
        expect(screen.getByRole('alert')).toHaveTextContent(
            'The terms changed before you confirmed. Check the new total and band, then confirm again.',
        );
    });

    it('asks for the acknowledgement again once the terms change', () => {
        const props = heldPage();
        const { rerender } = render(<InvestorCart {...props} />);

        fireEvent.click(screen.getByRole('checkbox'));
        expect(screen.getByRole('checkbox')).toBeChecked();

        const requoted = structuredClone(props);
        requoted.checkout!.revision = 2;
        rerender(<InvestorCart {...requoted} />);

        expect(screen.getByRole('checkbox')).not.toBeChecked();

        fireEvent.click(screen.getByRole('checkbox'));
        fireEvent.click(screen.getByRole('checkbox'));
        expect(screen.getByRole('checkbox')).not.toBeChecked();
    });

    it('asks for the acknowledgement again when the reserved terms of one held line change', () => {
        const props = heldPage();
        const { rerender } = render(<InvestorCart {...props} />);

        fireEvent.click(screen.getByRole('checkbox'));
        const changed = structuredClone(props);
        changed.checkout!.lines[1].disclosure_sha256 = 'd'.repeat(64);
        rerender(<InvestorCart {...changed} />);

        expect(screen.getByRole('checkbox')).not.toBeChecked();
    });

    it('names the held line a refused confirmation stopped on', async () => {
        inertia.queue.push(fails(409, refusedBody('CART_LINES_REFUSED')));
        render(<InvestorCart {...heldPage()} />);

        fireEvent.click(screen.getByRole('checkbox'));
        fireEvent.click(
            screen.getByRole('button', { name: 'Confirm all · RWF 70,000' }),
        );
        await flush();

        expect(
            within(screen.getByTestId('held-line-cmp_volt')).getByRole('alert'),
        ).toHaveTextContent(
            "Your available balance doesn't cover this amount.",
        );
        expect(
            screen.queryByTestId('line-refusal-cmp_greenleaf'),
        ).not.toBeInTheDocument();
    });

    it('releases every held line at once', async () => {
        inertia.queue.push(completed('CART_RELEASED'));
        render(<InvestorCart {...heldPage()} />);

        fireEvent.click(
            screen.getByRole('button', { name: 'Release all notes' }),
        );
        await flush();

        expect(inertia.calls[0]).toMatchObject({
            url: RELEASE_URL,
            body: {
                identity_context_revision: 3,
                checkout_id: '01jc3checkout00000000000000',
                expected_revision: 1,
            },
        });
        expect(inertia.reload).toHaveBeenCalled();
    });

    it('says it is confirming while the confirmation is on its way', async () => {
        inertia.queue.push(() => new Promise(() => undefined));
        render(<InvestorCart {...heldPage()} />);

        fireEvent.click(screen.getByRole('checkbox'));
        fireEvent.click(
            screen.getByRole('button', { name: 'Confirm all · RWF 70,000' }),
        );
        await flush();

        expect(
            screen.getByRole('button', { name: 'Confirming…' }),
        ).toBeDisabled();
        expect(
            screen.getByRole('button', { name: 'Release all notes' }),
        ).toBeDisabled();
    });

    it('says it is releasing while the release is on its way', async () => {
        inertia.queue.push(() => new Promise(() => undefined));
        render(<InvestorCart {...heldPage()} />);

        fireEvent.click(
            screen.getByRole('button', { name: 'Release all notes' }),
        );
        await flush();

        expect(
            screen.getByRole('button', { name: 'Releasing…' }),
        ).toHaveAttribute('aria-busy', 'true');
        expect(
            screen.getByRole('button', { name: 'Confirm all · RWF 70,000' }),
        ).toBeDisabled();
    });

    it('offers no confirm or release once the hold runs out, and reads the cart until it settles', async () => {
        /* The page's server time is 07:00; the hold runs to 07:03. */
        vi.useFakeTimers({ now: Date.parse('2026-09-23T07:00:00Z') });
        render(<InvestorCart {...heldPage()} />);

        expect(
            screen.getByRole('button', { name: 'Release all notes' }),
        ).toBeEnabled();

        await act(async () => {
            vi.advanceTimersByTime(181_000);
        });

        expect(
            screen.getByText(
                'Your hold has ended. These notes are being released back to your wallet.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /Confirm all/u }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Release all notes' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Go to Deals' }),
        ).toBeInTheDocument();
        expect(inertia.reload).not.toHaveBeenCalled();

        await act(async () => {
            vi.advanceTimersByTime(10_000);
        });

        expect(inertia.reload).toHaveBeenCalledTimes(1);
        vi.useRealTimers();
    });

    it('only waits for an ended hold to settle, on a wide screen too', () => {
        setWide(true);
        const props = heldPage('release_pending');
        props.checkout!.lines[1].campaign_id = 'cmp_unknown';
        props.links.deals = null;
        render(<InvestorCart {...props} />);

        expect(
            screen.getByText(
                'Your hold has ended. These notes are being released back to your wallet.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('cmp_unknown')).toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /Confirm all/u }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Release all notes' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Go to Deals' }),
        ).not.toBeInTheDocument();
    });

    it('shows what the confirmed cart bought once the cart reloads without it', async () => {
        inertia.queue.push(
            completed('CART_CONFIRMED', {
                checkout_id: '01jc3checkout00000000000000',
                fee_band: { tier: 'standard', rate_bps: 1500 },
                total_principal: rwf('70000'),
                lines: [
                    {
                        campaign_id: 'cmp_greenleaf',
                        commitment_id: '01jc3com00000000000000000a',
                        principal: rwf('50000'),
                    },
                    {
                        campaign_id: 'cmp_gone',
                        commitment_id: '01jc3com00000000000000000b',
                        principal: rwf('20000'),
                    },
                ],
            }),
        );
        const props = heldPage();
        const { rerender } = render(<InvestorCart {...props} />);

        fireEvent.click(screen.getByRole('checkbox'));
        fireEvent.click(
            screen.getByRole('button', { name: 'Confirm all · RWF 70,000' }),
        );
        await flush();

        const after = structuredClone(props);
        after.checkout = null;
        after.cart!.lines = [];
        rerender(<InvestorCart {...after} />);

        const done = screen.getByRole('status', { name: 'Cart checked out' });
        expect(done).toHaveTextContent(
            'You committed RWF 70,000 across 2 deals. Your notes are issued after each business is paid.',
        );
        expect(within(done).getByText('GreenLeaf Agro')).toBeInTheDocument();
        expect(within(done).getByText('cmp_gone')).toBeInTheDocument();
        expect(
            within(done).getAllByText('Fee on earnings 15.0% (Standard)'),
        ).toHaveLength(2);
        expect(
            within(done).getByRole('link', { name: 'Go to Portfolio' }),
        ).toHaveAttribute('href', '/preview/investor-portfolio');
        expect(
            within(done).getByRole('link', { name: 'Go to Deals' }),
        ).toHaveAttribute('href', '/preview/investor-deals');

        setWide(true);
        const narrow = structuredClone(after);
        narrow.links.portfolio = null;
        narrow.links.deals = null;
        rerender(<InvestorCart {...narrow} />);

        expect(
            screen.queryByRole('link', { name: 'Go to Portfolio' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Go to Deals' }),
        ).not.toBeInTheDocument();
    });
});

describe('Cart line refusals', () => {
    it('reads only well-formed line refusals', () => {
        const volt = {
            cart_line_id: '01jc3line000000000000000a2',
            campaign_id: 'cmp_volt',
            code: 'CART_LINE_UNAVAILABLE',
        };

        expect(
            readLineRefusals({
                line_refusals: [
                    null,
                    'cmp_volt',
                    { campaign_id: 7, code: 'X' },
                    { campaign_id: 'cmp_volt', code: 7 },
                    volt,
                ],
            }),
        ).toEqual([volt]);
        expect(
            readLineRefusals({ receipt: { line_refusals: [volt] } }),
        ).toEqual([volt]);
        expect(readLineRefusals({ receipt: null })).toEqual([]);
        expect(readLineRefusals({ line_refusals: 'cmp_volt' })).toEqual([]);
        expect(readLineRefusals({ cart_id: 'c' })).toEqual([]);
        expect(readLineRefusals(null)).toEqual([]);
    });
});
