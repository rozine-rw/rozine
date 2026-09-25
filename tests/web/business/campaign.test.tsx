import type * as InertiaCore from '@inertiajs/core';
import {
    act,
    fireEvent,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { POLL_INTERVAL_MS, POLL_LIMIT } from '@/hooks/use-bounded-poll';
import BusinessCampaign from '@/pages/business/campaign';
import type { BusinessCampaignProps, CampaignProgress } from '@/types/business';
import cancelRefusedFixture from '../../../resources/fixtures/ui/business-campaign-cancel-refused.json';
import cancelUnconfirmedFixture from '../../../resources/fixtures/ui/business-campaign-cancel-unconfirmed.json';
import cancelledFixture from '../../../resources/fixtures/ui/business-campaign-cancelled.json';
import disbursedFixture from '../../../resources/fixtures/ui/business-campaign-disbursed.json';
import expiredFixture from '../../../resources/fixtures/ui/business-campaign-expired.json';
import failedClosingFixture from '../../../resources/fixtures/ui/business-campaign-failed-closing.json';
import fullyReservedFixture from '../../../resources/fixtures/ui/business-campaign-fully-reserved.json';
import awaitingFixture from '../../../resources/fixtures/ui/business-campaign-funded-awaiting.json';
import inFlightFixture from '../../../resources/fixtures/ui/business-campaign-funded-in-flight.json';
import unknownFixture from '../../../resources/fixtures/ui/business-campaign-funded-unknown.json';
import liveMinimalFixture from '../../../resources/fixtures/ui/business-campaign-live-minimal.json';
import liveFixture from '../../../resources/fixtures/ui/business-campaign-raising-live.json';
import restrictedFixture from '../../../resources/fixtures/ui/business-campaign-raising-restricted.json';
import { answers, fails, inertia, operation } from '../auditor/inertia';
import { renderWithUser } from '../helpers/render-with-user';

vi.mock('@inertiajs/react', () => import('../auditor/inertia'));

vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaCore>()),
    http: { onResponse: () => () => undefined },
}));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessCampaignProps;

type Phase<P extends CampaignProgress['phase']> = Extract<
    CampaignProgress,
    { phase: P }
>;

const raising = (page: BusinessCampaignProps) =>
    page.note.progress as Phase<'raising'>;

const campaignSheet = () =>
    screen.getByRole('dialog', { name: 'Cold-Chain Hub' });

/** Names, identity kinds and per-Investor amounts the Phase 1B note used to show (H16). */
const IDENTITIES = [
    'Jean Kamanzi',
    'Marie Niyonsaba',
    'Pension Fund RW',
    'David Okello',
    'Recent investors',
    'Institution',
    'Individual',
    'SACCO',
];

/** Words a coarse in-flight closing must never read as (H15). */
const SETTLED = /\b(paid|failed|refunded|issued|disbursed)\b/i;

beforeEach(() => inertia.reset());

afterEach(() => {
    vi.useRealTimers();
});

describe('A raising campaign', () => {
    it('shows aggregate progress only, with no investor list', () => {
        renderWithUser(<BusinessCampaign {...props(liveFixture)} />);
        const sheet = campaignSheet();
        const view = within(sheet);

        expect(
            view.getByRole('heading', { name: 'Cold-Chain Hub' }),
        ).toBeInTheDocument();
        expect(view.getByText('Raising · live')).toBeInTheDocument();
        expect(view.getByText('RWF 14.1M')).toBeInTheDocument();
        expect(view.getByText('78.1%')).toBeInTheDocument();
        expect(view.getByText('318')).toBeInTheDocument();
        expect(
            view.getByRole('progressbar', { name: 'Funding tracker' }),
        ).toHaveValue(78.1);
        expect(view.getByText('RWF 14,058,000')).toBeInTheDocument();
        expect(view.getByText('RWF 900,000')).toBeInTheDocument();
        expect(view.getByText('RWF 3,042,000')).toBeInTheDocument();
        expect(
            view.getByText(
                '14,058 of 18,000 notes committed · 900 reserved · 3,042 available',
            ),
        ).toBeInTheDocument();
        expect(view.getByText('Closes 2 Oct 2026 · 09:00')).toBeInTheDocument();
        expect(view.queryByRole('alert')).not.toBeInTheDocument();

        for (const text of IDENTITIES) {
            expect(sheet).not.toHaveTextContent(text);
        }

        expect(
            view.queryByRole('link', { name: /View all/ }),
        ).not.toBeInTheDocument();
        expect(view.getByText('Photos')).toBeInTheDocument();
        expect(view.getByText('Performance trend')).toBeInTheDocument();
    });

    it('counts down from the server clock, not the browser clock', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2031-01-01T00:00:00Z'));
        const page = props(liveFixture);

        const { rerender } = renderWithUser(<BusinessCampaign {...page} />);
        const timer = () =>
            within(campaignSheet()).getByRole('timer', { name: 'Closes in' });

        expect(timer()).toHaveTextContent('7 days');

        raising(page).clock.expires_at = '2026-09-26T09:00:00+02:00';
        rerender(<BusinessCampaign {...structuredClone(page)} />);

        expect(timer()).toHaveTextContent('1 day');

        raising(page).clock.expires_at = '2026-09-25T12:00:00+02:00';
        rerender(<BusinessCampaign {...structuredClone(page)} />);

        expect(timer()).toHaveTextContent('03:00:00');

        act(() => {
            vi.advanceTimersByTime(1000);
        });

        expect(timer()).toHaveTextContent('02:59:59');

        raising(page).clock.expires_at = '2026-09-25T08:00:00+02:00';
        rerender(<BusinessCampaign {...structuredClone(page)} />);

        expect(timer()).toHaveTextContent('Closing');
    });

    it('keeps the lifecycle beside an active restriction', () => {
        renderWithUser(<BusinessCampaign {...props(restrictedFixture)} />);
        const view = within(campaignSheet());

        expect(view.getByText('Raising · live')).toBeInTheDocument();
        expect(view.getByRole('alert')).toHaveTextContent(
            "Restricted since 24 Sept 2026. New commitments are paused while the restriction lasts; what's already committed stays.",
        );
        expect(
            view.queryByRole('button', { name: 'Cancel this raise' }),
        ).not.toBeInTheDocument();
    });

    it('names a note that is no longer eligible', () => {
        const page = props(restrictedFixture);

        raising(page).restriction = {
            code: 'NOTE_INELIGIBLE',
            since: '2026-09-24T11:30:00+02:00',
        };
        renderWithUser(<BusinessCampaign {...page} />);

        expect(within(campaignSheet()).getByRole('alert')).toHaveTextContent(
            'Not eligible for new commitments since 24 Sept 2026',
        );
    });

    it('says when every note is reserved in a live checkout', () => {
        renderWithUser(<BusinessCampaign {...props(fullyReservedFixture)} />);
        const view = within(campaignSheet());

        expect(view.getByText('Raising · fully reserved')).toBeInTheDocument();
        expect(view.getByRole('status')).toHaveTextContent(
            'Every note is reserved in a live checkout.',
        );
        expect(
            view.getByText(
                '16,200 of 18,000 notes committed · 1,800 reserved · 0 available',
            ),
        ).toBeInTheDocument();
    });

    it('clamps the tracker to its range', () => {
        const page = props(liveFixture);

        raising(page).funded_pct = '104.0';
        renderWithUser(<BusinessCampaign {...page} />);

        expect(
            within(campaignSheet()).getByRole('progressbar', {
                name: 'Funding tracker',
            }),
        ).toHaveValue(100);
    });
});

describe('Cancelling a raise', () => {
    it('is offered only while raising and while the server lists campaign.cancel', () => {
        const withoutAction = props(liveFixture);

        withoutAction.allowed_actions = [];
        const { rerender } = renderWithUser(
            <BusinessCampaign {...withoutAction} />,
        );
        const cancelButton = () =>
            within(campaignSheet()).queryByRole('button', {
                name: 'Cancel this raise',
            });

        expect(cancelButton()).not.toBeInTheDocument();

        const withoutRoute = props(liveFixture);

        withoutRoute.actions.cancel = null;
        rerender(<BusinessCampaign {...withoutRoute} />);

        expect(cancelButton()).not.toBeInTheDocument();

        const funded = props(awaitingFixture);

        funded.allowed_actions = ['campaign.cancel'];
        funded.actions.cancel = {
            url: '/preview/business-campaign-cancel',
            method: 'post',
        };
        rerender(<BusinessCampaign {...funded} />);

        expect(cancelButton()).not.toBeInTheDocument();

        rerender(<BusinessCampaign {...props(liveFixture)} />);

        expect(cancelButton()).toBeInTheDocument();
    });

    it('sends campaign.cancel with the reason and follows the server', async () => {
        const { user } = renderWithUser(
            <BusinessCampaign {...props(liveFixture)} />,
        );

        await user.click(
            within(campaignSheet()).getByRole('button', {
                name: 'Cancel this raise',
            }),
        );

        const dialog = screen.getByRole('dialog', {
            name: 'Cancel this raise?',
        });

        expect(dialog).toHaveTextContent(
            "Every investor's commitment goes back to them in full, without fee",
        );

        await user.type(
            within(dialog).getByLabelText('Reason (optional)'),
            '  Supplier fell through  ',
        );
        await user.click(
            within(dialog).getByRole('button', { name: 'Cancel raise' }),
        );

        expect(
            screen.queryByRole('dialog', { name: 'Cancel this raise?' }),
        ).not.toBeInTheDocument();
        expect(
            within(campaignSheet()).getByRole('button', {
                name: 'Cancelling…',
            }),
        ).toBeDisabled();
        expect(inertia.calls).toHaveLength(1);
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/business-campaign-cancel',
            method: 'post',
            body: {
                identity_context_revision: 4,
                campaign_id: 'CMP-2026-0118',
                expected_campaign_revision: 12,
                reason: 'Supplier fell through',
            },
        });
    });

    it('sends no reason when none is given, and follows the completed cancel', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'CAMPAIGN_CANCELLED',
                    data: {
                        next: {
                            url: '/preview/business-campaign-cancelled',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(
            <BusinessCampaign {...props(liveFixture)} />,
        );

        await user.click(
            within(campaignSheet()).getByRole('button', {
                name: 'Cancel this raise',
            }),
        );
        await user.click(screen.getByRole('button', { name: 'Cancel raise' }));

        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/business-campaign-cancelled' },
            ]),
        );
        expect(inertia.calls[0].body).toMatchObject({ reason: null });
    });

    it('closes without sending when the business keeps raising', async () => {
        const { user } = renderWithUser(
            <BusinessCampaign {...props(liveFixture)} />,
        );
        const open = () =>
            user.click(
                within(campaignSheet()).getByRole('button', {
                    name: 'Cancel this raise',
                }),
            );

        await open();
        await user.click(screen.getByRole('button', { name: 'Keep raising' }));

        expect(
            screen.queryByRole('dialog', { name: 'Cancel this raise?' }),
        ).not.toBeInTheDocument();

        await open();
        await user.click(screen.getByRole('button', { name: 'Close' }));

        expect(
            screen.queryByRole('dialog', { name: 'Cancel this raise?' }),
        ).not.toBeInTheDocument();
        expect(inertia.calls).toHaveLength(0);
    });

    it('never resends a cancel whose answer was lost', async () => {
        inertia.queue.push(
            fails(503, { code: 'RETRYABLE_CONTENTION' }),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        const { user } = renderWithUser(
            <BusinessCampaign {...props(liveFixture)} />,
        );

        await user.click(
            within(campaignSheet()).getByRole('button', {
                name: 'Cancel this raise',
            }),
        );
        await user.click(screen.getByRole('button', { name: 'Cancel raise' }));

        await waitFor(() =>
            expect(
                within(campaignSheet()).getByText('Nothing was recorded'),
            ).toBeInTheDocument(),
        );
        expect(inertia.calls).toHaveLength(2);
        expect(inertia.calls[1]).toMatchObject({
            method: 'get',
            body: { identity_context_revision: 4, command: 'campaign.cancel' },
        });
        expect(
            within(campaignSheet()).getByRole('button', {
                name: 'Cancel this raise',
            }),
        ).toBeDisabled();
    });

    it('holds a seeded unconfirmed cancel instead of offering another', () => {
        renderWithUser(
            <BusinessCampaign {...props(cancelUnconfirmedFixture)} />,
        );
        const view = within(campaignSheet());

        expect(view.getByText('Not yet confirmed')).toBeInTheDocument();
        expect(
            view.getByRole('button', { name: 'Cancel this raise' }),
        ).toBeDisabled();
        expect(inertia.calls).toHaveLength(0);
    });

    it('shows a locked cancel as refused once the raise is funded', () => {
        renderWithUser(<BusinessCampaign {...props(cancelRefusedFixture)} />);

        expect(
            within(campaignSheet()).getByText(
                'The raise is fully funded, so this can no longer be cancelled.',
            ),
        ).toBeInTheDocument();
    });
});

describe('A funded campaign closing', () => {
    it('waits for disbursement without polling', () => {
        vi.useFakeTimers();
        renderWithUser(<BusinessCampaign {...props(awaitingFixture)} />);
        const view = within(campaignSheet());

        expect(
            view.getByText(
                'Fully funded on 20 Sept 2026. The raise can no longer be cancelled.',
            ),
        ).toBeInTheDocument();
        expect(view.getByText('RWF 18M')).toBeInTheDocument();
        expect(view.getByText('402')).toBeInTheDocument();
        expect(view.getByText('Awaiting disbursement')).toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(POLL_INTERVAL_MS * 3);
        });

        expect(inertia.reloads).toHaveLength(0);
    });

    it.each([
        [inFlightFixture, 'The payment to your account is in progress.'],
        [unknownFixture, "The payment's outcome hasn't been confirmed yet."],
    ])(
        'reads an in-flight payment as not yet confirmed, never settled',
        (fixture, body) => {
            const page = props(fixture);

            page.note.performance = null;
            page.note.photos = [];
            renderWithUser(<BusinessCampaign {...page} />);
            const sheet = campaignSheet();

            expect(
                within(sheet).getByText('Not yet confirmed'),
            ).toBeInTheDocument();
            expect(sheet).toHaveTextContent(body);
            expect(sheet).not.toHaveTextContent(SETTLED);
            expect(sheet).not.toHaveTextContent('provider');
        },
    );

    it('polls an in-flight closing within bounds, then hands over to a manual refresh', () => {
        vi.useFakeTimers();
        renderWithUser(<BusinessCampaign {...props(unknownFixture)} />);

        act(() => {
            vi.advanceTimersByTime(POLL_INTERVAL_MS);
        });

        expect(inertia.reloads).toEqual([
            {
                only: [
                    'server_time',
                    'allowed_actions',
                    'actions',
                    'campaign',
                    'note',
                ],
            },
        ]);

        for (let poll = 1; poll < POLL_LIMIT + 5; poll += 1) {
            act(() => {
                vi.advanceTimersByTime(POLL_INTERVAL_MS);
            });
        }

        expect(inertia.reloads).toHaveLength(POLL_LIMIT);

        const stopped = within(campaignSheet()).getByText(
            "Not yet confirmed. We've stopped checking automatically — refresh to see the latest.",
        );

        expect(stopped).toBeInTheDocument();

        fireEvent.click(
            within(campaignSheet()).getByRole('button', { name: 'Refresh' }),
        );

        expect(inertia.reloads).toHaveLength(POLL_LIMIT + 1);
        expect(
            within(campaignSheet()).queryByRole('button', { name: 'Refresh' }),
        ).not.toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(POLL_INTERVAL_MS);
        });

        expect(inertia.reloads).toHaveLength(POLL_LIMIT + 2);
    });
});

describe('A disbursed campaign', () => {
    it('shows the amount, masked destination, effective instant and Kigali date, and the receipt', () => {
        renderWithUser(<BusinessCampaign {...props(disbursedFixture)} />);
        const view = within(campaignSheet());

        expect(
            view.getByText(
                'RWF 18,000,000 was disbursed to Bank of Kigali ···· 4471.',
            ),
        ).toBeInTheDocument();
        expect(view.getByText('23 Sept 2026 · 10:12')).toBeInTheDocument();
        expect(view.getByText('23 Sept 2026')).toBeInTheDocument();

        const receipt = view.getByRole('region', {
            name: 'Disbursement receipt',
        });

        expect(receipt).toHaveTextContent('AmountRWF 18,000,000');
        expect(receipt).toHaveTextContent('ReferenceDSB-2026-0118');
        expect(receipt).toHaveTextContent('Recorded23 Sept 2026 · 10:14');
        expect(
            within(receipt).getByRole('link', { name: 'View receipt' }),
        ).toHaveAttribute('href', '/preview/business-campaign-disbursed');
    });

    it('keeps the receipt as recorded while the current projection changes', () => {
        const page = props(disbursedFixture);
        const { rerender } = renderWithUser(<BusinessCampaign {...page} />);
        const moved = structuredClone(page);

        (moved.note.progress as Phase<'disbursed'>).amount = {
            currency: 'RWF',
            amount: '17500000',
        };
        rerender(<BusinessCampaign {...moved} />);

        const receipt = within(campaignSheet()).getByRole('region', {
            name: 'Disbursement receipt',
        });

        expect(receipt).toHaveTextContent('AmountRWF 18,000,000');
        expect(
            within(campaignSheet()).getByText(
                'RWF 17,500,000 was disbursed to Bank of Kigali ···· 4471.',
            ),
        ).toBeInTheDocument();
    });
});

describe('A closed campaign', () => {
    it.each([
        [
            expiredFixture,
            'RWF 11.9M',
            '284',
            'This raise closed on 20 Sept 2026 before it was fully funded. RWF 11,900,000 went back to investors in full, without fee.',
        ],
        [
            cancelledFixture,
            'RWF 6.4M',
            '151',
            'This raise was cancelled on 18 Sept 2026. RWF 6,400,000 went back to investors in full, without fee.',
        ],
        [
            failedClosingFixture,
            'RWF 18M',
            '402',
            "This raise couldn't close: a check before disbursement failed on 22 Sept 2026. RWF 18,000,000 went back to investors in full, without fee.",
        ],
    ])(
        'refunds every commitment without fee',
        (fixture, tile, count, notice) => {
            renderWithUser(<BusinessCampaign {...props(fixture)} />);
            const view = within(campaignSheet());

            expect(view.getByText('Failed')).toBeInTheDocument();
            expect(view.getByText('Refunded')).toBeInTheDocument();
            expect(view.getByText(tile)).toBeInTheDocument();
            expect(view.getByText(count)).toBeInTheDocument();
            expect(view.getByRole('status')).toHaveTextContent(notice);
            expect(
                view.queryByRole('button', { name: 'Cancel this raise' }),
            ).not.toBeInTheDocument();
        },
    );
});

describe('The live-minimal campaign', () => {
    it('renders the future live shape with its nullable parts empty', () => {
        const page = props(liveMinimalFixture);

        renderWithUser(<BusinessCampaign {...page} />);
        const view = within(campaignSheet());

        expect(view.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/business',
        );
        expect(view.queryByText('Photos')).not.toBeInTheDocument();
        expect(view.queryByText('Performance trend')).not.toBeInTheDocument();
        expect(
            view.queryByRole('button', { name: 'Cancel this raise' }),
        ).not.toBeInTheDocument();
    });
});
