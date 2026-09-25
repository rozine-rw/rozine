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
import InvestorCheckout from '@/pages/investor/checkout';
import type { C3InvestorCheckoutProps } from '@/types/investor';
import capHitFixture from '../../../resources/fixtures/ui/investor-checkout-cap-hit.json';
import capReachedFixture from '../../../resources/fixtures/ui/investor-checkout-cap-reached.json';
import committedFixture from '../../../resources/fixtures/ui/investor-checkout-committed.json';
import identityFixture from '../../../resources/fixtures/ui/investor-checkout-identity-required.json';
import minimalFixture from '../../../resources/fixtures/ui/investor-checkout-live-minimal.json';
import notRecordedFixture from '../../../resources/fixtures/ui/investor-checkout-not-recorded.json';
import expiredFixture from '../../../resources/fixtures/ui/investor-checkout-reservation-expired.json';
import reservedFixture from '../../../resources/fixtures/ui/investor-checkout-reserved.json';
import unconfirmedFixture from '../../../resources/fixtures/ui/investor-checkout-unconfirmed.json';
import unitsGoneFixture from '../../../resources/fixtures/ui/investor-checkout-units-unavailable.json';
import shortFixture from '../../../resources/fixtures/ui/investor-checkout-wallet-short.json';
import checkoutFixture from '../../../resources/fixtures/ui/investor-checkout.json';
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

const props = (fixture: { props: unknown } = checkoutFixture) =>
    structuredClone(fixture.props) as C3InvestorCheckoutProps;

const flush = () =>
    act(async () => {
        await Promise.resolve();
        await Promise.resolve();
    });

beforeEach(() => {
    resetInertia();
    setWide(false);
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Checkout: the indicative step', () => {
    it('shows the server’s indicative quote with no maturity date before issue, then offers Reserve', () => {
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
        expect(sheet).toHaveTextContent(
            'Indicative until you reserve: the exact rights of your notes are fixed when they are reserved.',
        );
        expect(within(sheet).getByText('+RWF 675')).toBeInTheDocument();
        expect(sheet).toHaveTextContent('Back over 6 monthsRWF 5,621');
        expect(sheet).toHaveTextContent(
            'Maturity dateSet when notes are issued',
        );
        expect(
            within(sheet).getByText('Rozine Wallet · RWF 1,253,485 available'),
        ).toBeInTheDocument();
        expect(within(sheet).queryByText('CLOSES IN')).not.toBeInTheDocument();
        expect(within(sheet).queryByRole('checkbox')).not.toBeInTheDocument();
        expect(
            within(sheet).getByRole('button', { name: 'Reserve · RWF 5,000' }),
        ).toBeEnabled();
        expect(
            within(sheet).getByRole('button', { name: 'One note fewer' }),
        ).toBeDisabled();
        expect(
            screen.getByRole('article', { name: 'GreenLeaf Agro' }),
        ).toBeInTheDocument();
    });

    it('re-quotes each quantity from the server and holds Reserve while it does', async () => {
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
        expect(
            within(sheet).getByRole('button', { name: 'Reserve · RWF 5,000' }),
        ).toBeDisabled();
        act(() => reload.onFinish());
    });

    it('reserves with a fresh request and follows the server to the held step', async () => {
        const data = props();

        render(<InvestorCheckout {...data} />);
        inertia.queue.push(
            answers({
                status: 'completed',
                code: 'PRIMARY_RESERVED',
                data: {
                    receipt: { code: 'PRIMARY_RESERVED' },
                    current: null,
                    next: {
                        url: '/preview/investor-checkout-reserved',
                        method: 'get',
                    },
                },
            }),
        );
        fireEvent.click(
            screen.getByRole('button', { name: 'Reserve · RWF 5,000' }),
        );
        expect(screen.getByRole('button', { name: 'Sending…' })).toBeDisabled();
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/investor-checkout-reserved',
            method: 'post',
            body: {
                identity_context_revision: 5,
                campaign_id: 'cmp_greenleaf',
                units: '1',
                expected_campaign_revision: 3,
                quote_revision: 7,
            },
        });
        await flush();
        expect(inertia.visit).toHaveBeenCalledWith({
            url: '/preview/investor-checkout-reserved',
            method: 'get',
        });
        expect(
            screen.queryByRole('dialog', { name: 'Committed' }),
        ).not.toBeInTheDocument();
    });

    it('looks an uncertain reserve up and never resends it on its own', async () => {
        finishReloads();
        render(<InvestorCheckout {...props()} />);

        inertia.queue.push(fails(503, { code: 'RETRYABLE_CONTENTION' }));
        inertia.queue.push(fails(404, { code: 'OPERATION_NOT_FOUND' }));
        fireEvent.click(
            screen.getByRole('button', { name: 'Reserve · RWF 5,000' }),
        );
        expect(
            await screen.findByText('Nothing was recorded'),
        ).toBeInTheDocument();
        expect(inertia.calls).toHaveLength(2);
        expect(inertia.calls[1]).toMatchObject({
            method: 'get',
            body: { identity_context_revision: 5, command: 'primary.reserve' },
        });
        expect(
            screen.getByRole('button', { name: 'Reserve · RWF 5,000' }),
        ).toBeDisabled();
    });

    it('offers the same-key retry after a not-recorded lookup, while reserve is still allowed', async () => {
        render(<InvestorCheckout {...props(notRecordedFixture)} />);

        inertia.queue.push(fails(409, { code: 'UNITS_UNAVAILABLE' }));
        fireEvent.click(
            screen.getByRole('button', { name: 'Send the same request again' }),
        );
        expect(inertia.calls[0].body).toEqual({
            request_id: '3c9b1e20-6f4a-4b8d-9e12-0a1b2c3d4e5f',
            identity_context_revision: 5,
            campaign_id: 'cmp_greenleaf',
            units: '6',
            expected_campaign_revision: 3,
            quote_revision: 7,
        });
        expect(
            await screen.findByText(/Those notes are no longer available/u),
        ).toBeInTheDocument();
    });

    it('refuses a short wallet with the reason and a way to deposit', () => {
        render(<InvestorCheckout {...props(shortFixture)} />);

        const alert = screen.getByRole('alert');

        expect(alert).toHaveTextContent(
            'Amount exceeds your wallet balance. Deposit to your wallet, or lower the amount.',
        );
        expect(
            within(alert).getByRole('link', { name: 'Deposit' }),
        ).toHaveAttribute('href', '/preview/investor-wallet-deposit');
        expect(
            screen.getByRole('button', { name: 'Reserve · RWF 5,000' }),
        ).toBeDisabled();
    });

    it('names the cap that binds, and offers no reserve when none is left', async () => {
        const user = userEvent.setup();
        const { unmount } = render(
            <InvestorCheckout {...props(capReachedFixture)} />,
        );

        expect(
            screen.queryByText(/limit for this business/u),
        ).not.toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'One note more' }));
        expect(
            screen.getByText('Up to 2 notes: your limit for this business.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'One note more' }),
        ).toBeDisabled();
        expect(screen.getByRole('button', { name: '25K' })).toBeDisabled();
        unmount();

        render(<InvestorCheckout {...props(capHitFixture)} />);
        expect(
            screen.getByText(
                "You can't take any more notes here: your overall investment limit.",
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText("Reserving isn't available right now."),
        ).toBeInTheDocument();
    });

    it('states a refused reserve and an unverified investor’s refusal', () => {
        const { unmount } = render(
            <InvestorCheckout {...props(unitsGoneFixture)} />,
        );

        expect(
            screen.getByText(
                'Those notes are no longer available. Choose fewer notes or try again later.',
            ),
        ).toBeInTheDocument();
        unmount();

        render(<InvestorCheckout {...props(identityFixture)} />);
        expect(
            screen.getByText('Verify your identity to invest.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /Reserve/u }),
        ).not.toBeInTheDocument();
    });

    it('explains an expired hold and lets the investor reserve again', () => {
        render(<InvestorCheckout {...props(expiredFixture)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Your 5-minute hold ended and its notes were released. Reserve again to continue.',
        );
        expect(
            screen.getByRole('button', { name: 'Reserve · RWF 5,000' }),
        ).toBeEnabled();
    });

    it('explains a restricted deal and counts down its last day', () => {
        vi.useFakeTimers({ now: new Date('2026-09-23T07:00:00Z') });
        const data = props();

        data.deal.restriction = {
            code: 'RESTRICTION_ACTIVE',
            since: '2026-09-22T00:00:00+02:00',
        };
        data.deal.clock.expires_at = '2026-09-23T20:00:00+02:00';
        render(<InvestorCheckout {...data} />);

        expect(
            screen.getByText('Investing is paused: a restriction applies'),
        ).toBeInTheDocument();
        expect(screen.getByText('CLOSES IN')).toBeInTheDocument();
        expect(screen.getByText('11:00:00')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Reserve · RWF 5,000' }),
        ).toBeDisabled();
    });
});

describe('Checkout: quantities and ordinals', () => {
    it('labels a multi-note quote and lists split ordinal ranges exactly', () => {
        const indicative = props();

        indicative.quote.units = '2';
        const { unmount } = render(<InvestorCheckout {...indicative} />);

        expect(
            screen.getByText('2 notes · RWF 5,000 each'),
        ).toBeInTheDocument();
        unmount();

        const held = props(reservedFixture);
        const reservation = held.reservation as NonNullable<
            C3InvestorCheckoutProps['reservation']
        >;

        reservation.ordinals = [
            { first: '1201', last: '1205' },
            { first: '1300', last: '1300' },
        ];
        render(<InvestorCheckout {...held} />);
        expect(
            screen.getByText('6 notes · #1201–#1205, #1300'),
        ).toBeInTheDocument();
    });
});

describe('Checkout: the reserved step', () => {
    it('counts the 5-minute hold down against the server clock and shows the exact rights', () => {
        vi.useFakeTimers({ now: new Date('2026-09-23T07:00:00Z') });
        render(<InvestorCheckout {...props(reservedFixture)} />);

        const sheet = screen.getByRole('dialog', {
            name: 'Confirm your notes',
        });

        expect(within(sheet).getByRole('timer')).toHaveTextContent(
            'Held for you · 04:50 left to confirm',
        );
        act(() => vi.advanceTimersByTime(60_000));
        expect(within(sheet).getByRole('timer')).toHaveTextContent('03:50');
        expect(sheet).toHaveTextContent('Units6 notes · #1201–#1206');
        expect(sheet).toHaveTextContent('Held from AvailableRWF 30,000');

        const rights = within(sheet).getByRole('table');

        expect(within(rights).getAllByRole('row')).toHaveLength(7);
        expect(rights).toHaveTextContent('No. 1RWF 5,000RWF 675');
        expect(sheet).toHaveTextContent('Total returnRWF 4,050');
        expect(sheet).toHaveTextContent(
            'Due dates are set when the notes are issued',
        );
        expect(sheet).toHaveTextContent(
            'Maturity dateSet when notes are issued',
        );

        act(() => vi.advanceTimersByTime(240_000));
        expect(within(sheet).getByRole('timer')).toHaveTextContent(
            'Your hold has ended — these notes may have been released',
        );
    });

    it('confirms only after the disclosure is acknowledged, with its version and digest', async () => {
        const user = userEvent.setup();

        render(<InvestorCheckout {...props(reservedFixture)} />);

        const confirm = screen.getByRole('button', {
            name: 'Confirm · RWF 30,000',
        });

        expect(confirm).toBeDisabled();
        await user.click(screen.getByRole('checkbox'));
        expect(confirm).toBeEnabled();
        await user.click(screen.getByRole('checkbox'));
        await user.click(screen.getByRole('checkbox'));
        await user.click(confirm);

        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/investor-checkout-committed',
            method: 'post',
            body: {
                identity_context_revision: 5,
                reservation_id: 'res_01k6r2b4c6d8',
                expected_reservation_revision: 1,
                disclosure_version: 'disclosure-2026-09.1',
                disclosure_sha256:
                    '5d1c0a7f3b9e2d48c6a1f07e93b25c4d8a6e1f0b7c2d93e4a5b6c7d8e9f0a1b2',
                acknowledged: true,
            },
        });
    });

    it('releases the held notes', async () => {
        const user = userEvent.setup();

        render(<InvestorCheckout {...props(reservedFixture)} />);
        await user.click(
            screen.getByRole('button', { name: 'Release these notes' }),
        );

        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/investor-checkout',
            body: {
                identity_context_revision: 5,
                reservation_id: 'res_01k6r2b4c6d8',
                expected_reservation_revision: 1,
            },
        });
    });

    it('holds an unconfirmed confirm and only looks it up again', async () => {
        const user = userEvent.setup();

        render(<InvestorCheckout {...props(unconfirmedFixture)} />);

        expect(screen.getByText('Not yet confirmed')).toBeInTheDocument();
        await user.click(screen.getByRole('checkbox'));
        expect(
            screen.getByRole('button', { name: 'Confirm · RWF 30,000' }),
        ).toBeDisabled();
        expect(
            screen.getByRole('button', { name: 'Release these notes' }),
        ).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Check again' }));
        expect(inertia.calls).toEqual([
            {
                url: '/preview/investor-primary-operation-8e2d4f60-1a3b-4c5d-8e6f-7a8b9c0d1e2f',
                method: 'get',
                body: {
                    identity_context_revision: 5,
                    command: 'primary.confirm',
                },
            },
        ]);
    });

    it('shows neither confirm nor release when the server offers neither', () => {
        const data = props(reservedFixture);

        data.allowed_actions = [];
        render(<InvestorCheckout {...data} />);

        expect(
            screen.queryByRole('button', { name: /Confirm/u }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Release these notes' }),
        ).not.toBeInTheDocument();
    });
});

describe('Checkout: committed', () => {
    it('shows a commitment, issued after disbursement, never as a holding', () => {
        render(<InvestorCheckout {...props(committedFixture)} />);

        const done = screen.getByRole('dialog', { name: 'Committed' });

        expect(done).toHaveTextContent(
            'Committed — your notes are issued after the business is paid. Until then this is a commitment, not a holding.',
        );
        expect(done).toHaveTextContent('Committed — issued after disbursement');
        expect(done).toHaveTextContent('Units6 notes · #1201–#1206');
        expect(done).toHaveTextContent('PrincipalRWF 30,000');
        expect(done).toHaveTextContent('RZ-CMT-CMT1');
        expect(done).not.toHaveTextContent(
            /Investment confirmed|in your portfolio/u,
        );
        expect(
            within(done).queryByRole('link', { name: 'View holding' }),
        ).not.toBeInTheDocument();
        expect(
            within(done).getByRole('link', {
                name: 'See it in Awaiting issue',
            }),
        ).toHaveAttribute('href', '/preview/investor-portfolio');
        expect(
            within(done).getByRole('link', { name: 'Explore more deals' }),
        ).toHaveAttribute('href', '/preview/investor-deals');
    });

    it('centres the sheet over the deck on a wide screen, and over an empty backdrop without a home', () => {
        setWide(true);
        const { unmount } = render(<InvestorCheckout {...props()} />);

        expect(screen.getByRole('dialog', { name: 'Checkout' })).toHaveClass(
            'w-[376px]',
        );
        unmount();

        render(<InvestorCheckout {...props(minimalFixture)} />);
        expect(screen.getByRole('dialog', { name: 'Checkout' })).toHaveClass(
            'w-[376px]',
        );
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
        expect(
            screen.getByText("Reserving isn't available right now."),
        ).toBeInTheDocument();
    });
});
