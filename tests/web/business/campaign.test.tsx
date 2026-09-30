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
import type {
    BusinessCampaignProps,
    BusinessCampaignV2Props,
    CampaignProgress,
    CampaignProgressV2,
} from '@/types/business';
import cancelRefusedFixture from '../../../resources/fixtures/ui/business-campaign-cancel-refused.json';
import cancelUnconfirmedFixture from '../../../resources/fixtures/ui/business-campaign-cancel-unconfirmed.json';
import cancelledEmptyFixture from '../../../resources/fixtures/ui/business-campaign-cancelled-empty.json';
import cancelledFixture from '../../../resources/fixtures/ui/business-campaign-cancelled.json';
import closingPendingFixture from '../../../resources/fixtures/ui/business-campaign-closing-pending.json';
import disbursedFixture from '../../../resources/fixtures/ui/business-campaign-disbursed.json';
import expiredFixture from '../../../resources/fixtures/ui/business-campaign-expired.json';
import failedClosingFixture from '../../../resources/fixtures/ui/business-campaign-failed-closing.json';
import fullyReservedFixture from '../../../resources/fixtures/ui/business-campaign-fully-reserved.json';
import awaitingFixture from '../../../resources/fixtures/ui/business-campaign-funded-awaiting.json';
import inFlightFixture from '../../../resources/fixtures/ui/business-campaign-funded-in-flight.json';
import unknownFixture from '../../../resources/fixtures/ui/business-campaign-funded-unknown.json';
import inventoryUnavailableFixture from '../../../resources/fixtures/ui/business-campaign-inventory-unavailable.json';
import liveMinimalFixture from '../../../resources/fixtures/ui/business-campaign-live-minimal.json';
import liveFixture from '../../../resources/fixtures/ui/business-campaign-raising-live.json';
import restrictedFixture from '../../../resources/fixtures/ui/business-campaign-raising-restricted.json';
import repaidFixture from '../../../resources/fixtures/ui/business-campaign-repaid.json';
import overdueFixture from '../../../resources/fixtures/ui/business-campaign-repaying-overdue.json';
import repayingFixture from '../../../resources/fixtures/ui/business-campaign-repaying.json';
import soldOutFixture from '../../../resources/fixtures/ui/business-campaign-sold-out-pending.json';
import v2LiveMinimalFixture from '../../../resources/fixtures/ui/business-campaign-v2-live-minimal.json';
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
            view.getByRole('progressbar', { name: 'Commitment tracker' }),
        ).toHaveValue(78.1);
        expect(view.getByText('78.1% committed')).toBeInTheDocument();
        expect(view.getAllByText('Committed')).toHaveLength(3);
        expect(
            view.getByText('Not yet committed or reserved'),
        ).toBeInTheDocument();
        expect(sheet).not.toHaveTextContent(/funded|funding/i);
        expect(sheet).not.toHaveTextContent(/left to raise/i);
        expect(view.getByText('RWF 14,058,000')).toBeInTheDocument();
        expect(view.getByText('RWF 900,000')).toBeInTheDocument();
        expect(view.getByText('RWF 3,042,000')).toBeInTheDocument();
        expect(
            view.getByText(
                '14,058 of 18,000 notes committed · 900 reserved · 3,042 available · 0 unavailable',
            ),
        ).toBeInTheDocument();
        expect(sheet).not.toHaveTextContent(/aren't on sale/);
        expect(view.getByText('Closes 2 Oct 2026 · 09:00')).toBeInTheDocument();
        expect(
            view.getByText(
                "Notes held in an investor's checkout. They aren't confirmed yet.",
            ),
        ).toBeInTheDocument();
        expect(sheet).not.toHaveTextContent(/released after 5 minutes/i);
        expect(sheet).not.toHaveTextContent(/back on sale/i);
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
            "Every available note is currently held in a checkout. New investors can't reserve until holds are confirmed or end.",
        );
        expect(campaignSheet()).not.toHaveTextContent(/back on sale/i);
        expect(campaignSheet()).not.toHaveTextContent(/5 minutes/i);
        expect(
            view.getByText(
                '16,200 of 18,000 notes committed · 1,800 reserved · 0 available · 0 unavailable',
            ),
        ).toBeInTheDocument();
    });

    it('clamps the tracker to its range', () => {
        const page = props(liveFixture);

        raising(page).funded_pct = '104.0';
        renderWithUser(<BusinessCampaign {...page} />);

        expect(
            within(campaignSheet()).getByRole('progressbar', {
                name: 'Commitment tracker',
            }),
        ).toHaveValue(100);
    });
});

describe('A raise that is no longer simply live', () => {
    it('reads a fully committed raise as awaiting settlement, never funded, with the countdown stopped', () => {
        renderWithUser(<BusinessCampaign {...props(soldOutFixture)} />);
        const sheet = campaignSheet();
        const view = within(sheet);

        expect(view.getByText('Fully committed')).toBeInTheDocument();
        expect(
            view.getByText('Fully committed · awaiting settlement'),
        ).toBeInTheDocument();
        expect(view.getByRole('status')).toHaveTextContent(
            "Every note is committed, so new investors can't join. The raise isn't funded until Rozine completes settlement.",
        );
        expect(sheet).not.toHaveTextContent(/fully funded|\bFunded\b/);
        expect(view.queryByRole('timer')).not.toBeInTheDocument();
        expect(view.getByText('Deadline')).toBeInTheDocument();
        expect(view.getByText('25 Sept 2026')).toBeInTheDocument();
        expect(
            view.getByText('Closes 25 Sept 2026 · 21:30'),
        ).toBeInTheDocument();
        expect(view.getByText('100.0% committed')).toBeInTheDocument();
        expect(sheet).not.toHaveTextContent(/investor's checkout/);
        expect(view.getByText('RWF 18,000,000')).toBeInTheDocument();
        expect(
            view.getByText(
                '18,000 of 18,000 notes committed · 0 reserved · 0 available · 0 unavailable',
            ),
        ).toBeInTheDocument();
        expect(
            view.queryByRole('button', { name: 'Cancel this raise' }),
        ).not.toBeInTheDocument();
    });

    it('says when no notes are left to reserve, and why some are unavailable', () => {
        renderWithUser(
            <BusinessCampaign {...props(inventoryUnavailableFixture)} />,
        );
        const sheet = campaignSheet();
        const view = within(sheet);

        expect(view.getByText('No notes available')).toBeInTheDocument();
        expect(
            view.getByText('Raising · no notes available'),
        ).toBeInTheDocument();
        expect(view.getByRole('status')).toHaveTextContent(
            "No notes are available to reserve and none are held in a checkout, so new investors can't commit right now.",
        );
        expect(
            view.getByText(
                '16,200 of 18,000 notes committed · 0 reserved · 0 available · 1,800 unavailable',
            ),
        ).toBeInTheDocument();
        expect(
            view.getByText(
                "Unavailable notes were in a checkout or commitment that has ended. They aren't on sale.",
            ),
        ).toBeInTheDocument();
        expect(view.getByText('RWF 1,800,000')).toBeInTheDocument();
        expect(
            view.getByText('Not yet committed or reserved'),
        ).toBeInTheDocument();
        expect(sheet).not.toHaveTextContent(/back on sale|left to raise/i);
        expect(
            view.getByRole('timer', { name: 'Closes in' }),
        ).toBeInTheDocument();
    });

    it('stops counting down once the deadline has passed, and says the raise is closing', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-09-20T00:00:00Z'));
        renderWithUser(<BusinessCampaign {...props(closingPendingFixture)} />);
        const sheet = campaignSheet();
        const view = within(sheet);

        expect(view.getByText('Closing')).toBeInTheDocument();
        expect(view.getByText('Deadline passed · closing')).toBeInTheDocument();
        expect(view.getByRole('status')).toHaveTextContent(
            "The deadline has passed, so new investors can't commit. Rozine is closing the raise and will show the outcome here.",
        );
        expect(view.queryByRole('timer')).not.toBeInTheDocument();
        expect(
            view.getByText('Deadline passed 25 Sept 2026 · 21:30'),
        ).toBeInTheDocument();
        expect(sheet).not.toHaveTextContent(/Closes 25 Sept/);
        expect(
            view.getByText(
                '14,058 of 18,000 notes committed · 0 reserved · 3,042 available · 900 unavailable',
            ),
        ).toBeInTheDocument();
        expect(sheet).not.toHaveTextContent(/fully funded|\bFunded\b/);
        expect(
            view.queryByRole('button', { name: 'Cancel this raise' }),
        ).not.toBeInTheDocument();
    });
});

describe('Raising progress the server sent incompletely', () => {
    const unreadable = (page: BusinessCampaignProps) => {
        renderWithUser(<BusinessCampaign {...page} />);
        const sheet = campaignSheet();
        const view = within(sheet);

        expect(view.getByRole('status')).toHaveTextContent(
            "This raise's progress can't be shown right now. Refresh the page to try again.",
        );
        expect(view.getByText('Status unavailable')).toBeInTheDocument();
        expect(view.queryByRole('progressbar')).not.toBeInTheDocument();
        expect(view.queryByRole('timer')).not.toBeInTheDocument();

        for (const figure of [
            'RWF 14.1M',
            '78.1%',
            'Commitment tracker',
            'notes committed',
            'Not yet committed or reserved',
            'Closes',
        ]) {
            expect(sheet).not.toHaveTextContent(figure);
        }

        expect(
            view.queryByRole('button', { name: 'Cancel this raise' }),
        ).not.toBeInTheDocument();
    };

    it('fails closed on a lifecycle it does not know', () => {
        const page = props(liveFixture);

        (raising(page) as { lifecycle: string }).lifecycle = 'recycling';
        (page.campaign as { lifecycle: string }).lifecycle = 'recycling';
        unreadable(page);
    });

    it('fails closed when the page and its progress disagree on the lifecycle', () => {
        const page = props(liveFixture);

        page.campaign.lifecycle = 'fully_reserved';
        unreadable(page);
    });

    it('fails closed without the unavailable units', () => {
        const page = props(liveFixture);

        delete (raising(page).units as Partial<Phase<'raising'>['units']>)
            .unavailable;
        unreadable(page);
    });

    it('fails closed on a unit count that is not a whole number', () => {
        const page = props(liveFixture);

        raising(page).units.available = '-3';
        unreadable(page);
    });

    it('fails closed without an amount it would show', () => {
        const missing = props(liveFixture);

        (raising(missing) as { remaining: unknown }).remaining = null;
        unreadable(missing);
    });

    it('fails closed on an amount with no value', () => {
        const page = props(liveFixture);

        (raising(page) as { reserved: unknown }).reserved = { currency: 'RWF' };
        unreadable(page);
    });

    it('fails closed on an amount that is not an object', () => {
        const page = props(liveFixture);

        (raising(page) as { committed: unknown }).committed = '14058000';
        unreadable(page);
    });
});

describe('A funded campaign the page cannot read yet', () => {
    const closed = (page: BusinessCampaignProps) => {
        renderWithUser(<BusinessCampaign {...page} />);
        const sheet = campaignSheet();
        const view = within(sheet);

        expect(view.getByRole('status')).toHaveTextContent(
            "This raise's progress can't be shown right now. Refresh the page to try again.",
        );
        expect(view.getByText('Status unavailable')).toBeInTheDocument();
        expect(sheet).not.toHaveTextContent(/Fully funded|Disbursement/);
        expect(
            view.queryByRole('button', { name: 'Cancel this raise' }),
        ).not.toBeInTheDocument();
    };

    const funded = (page: BusinessCampaignProps) =>
        page.note.progress as unknown as Record<string, unknown>;

    it('fails closed on the durable funding lock the server reports before it is bound', () => {
        vi.useFakeTimers();
        const page = props(soldOutFixture);

        /* The server's shape at #175 3bb74427: the raising figures under a funded phase. */
        funded(page).phase = 'funded';
        funded(page).lifecycle = 'funded_pending_disbursement';
        (page.campaign as { lifecycle: string }).lifecycle =
            'funded_pending_disbursement';
        closed(page);

        act(() => {
            vi.advanceTimersByTime(POLL_INTERVAL_MS * 2);
        });

        expect(inertia.reloads).toHaveLength(0);
    });

    it('fails closed when a funded campaign carries a lifecycle that is not funded', () => {
        const page = props(awaitingFixture);

        page.campaign.lifecycle = 'live';
        closed(page);
    });

    it('fails closed without the funding instant', () => {
        const page = props(awaitingFixture);

        delete funded(page).funded_at;
        closed(page);
    });

    it('fails closed without the committed amount', () => {
        const page = props(awaitingFixture);

        funded(page).committed = { currency: 'RWF' };
        closed(page);
    });

    it('fails closed without a closing', () => {
        const page = props(awaitingFixture);

        funded(page).closing = null;
        closed(page);
    });

    it('fails closed on a closing that is not an object', () => {
        const page = props(awaitingFixture);

        funded(page).closing = 'awaiting_disbursement';
        closed(page);
    });

    it('fails closed on a closing stage it does not know', () => {
        const page = props(awaitingFixture);

        funded(page).closing = { stage: 'settled' };
        closed(page);
    });

    it('fails closed on an in-flight closing with an outcome it does not know', () => {
        const page = props(inFlightFixture);

        funded(page).closing = { stage: 'in_flight', provider: 'paid' };
        closed(page);
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
            'Any unconfirmed holds are released to investors in full, and the raise closes for good.',
        );
        expect(dialog).not.toHaveTextContent(/commitment goes back/i);
        expect(dialog).not.toHaveTextContent(/without fee/i);

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

    it('explains a cancel refused because investors have committed', async () => {
        inertia.queue.push(
            fails(409, { code: 'CAMPAIGN_SETTLEMENT_REQUIRED' }),
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

        expect(
            await within(campaignSheet()).findByText(
                "Investors have already committed to this raise, so it can't be cancelled here. Their commitments have to be settled first.",
            ),
        ).toBeInTheDocument();
        expect(
            within(campaignSheet()).queryByText(
                'This request was refused. Refresh and try again.',
            ),
        ).not.toBeInTheDocument();
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
            "Didn't fill",
        ],
        [
            cancelledFixture,
            'RWF 6.4M',
            '151',
            'This raise was cancelled on 18 Sept 2026. RWF 6,400,000 went back to investors in full, without fee.',
            'Cancelled',
        ],
        [
            failedClosingFixture,
            'RWF 18M',
            '402',
            'This raise closed on 22 Sept 2026 without paying out. RWF 18,000,000 went back to investors in full, without fee.',
            'Closed and refunded',
        ],
    ])(
        'refunds every commitment without fee',
        (fixture, tile, count, notice, state) => {
            renderWithUser(<BusinessCampaign {...props(fixture)} />);
            const view = within(campaignSheet());

            expect(view.getByText(state)).toHaveClass('text-rz-danger-text');
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

describe('A failed closing', () => {
    it('names no cause, since a failed check and a verified, reconciled payout failure both end here', () => {
        renderWithUser(<BusinessCampaign {...props(failedClosingFixture)} />);
        const status = within(campaignSheet()).getByRole('status');

        expect(status).toHaveTextContent('without paying out');
        expect(status).not.toHaveTextContent(/check|provider/iu);
    });
});

describe('A raise closed before anyone committed', () => {
    it.each([
        [
            'cancelled',
            'This raise was cancelled on 18 Sept 2026 before any investor committed, so there was nothing to refund.',
        ],
        [
            'expired',
            'This raise closed on 18 Sept 2026 before any investor committed, so there was nothing to refund.',
        ],
        [
            'failed_closing',
            'This raise closed on 18 Sept 2026 without paying out. No investor had committed, so there was nothing to refund.',
        ],
    ] as const)('says there was nothing to refund when %s', (phase, notice) => {
        const page = props(cancelledEmptyFixture);

        (page.note.progress as { phase: string }).phase = phase;
        renderWithUser(<BusinessCampaign {...page} />);
        const view = within(campaignSheet());

        expect(view.getByRole('status')).toHaveTextContent(notice);
        expect(
            view.queryByText(/went back to investors/u),
        ).not.toBeInTheDocument();
    });
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

    it('opens over an empty backdrop without Home, with the shell from shell_links', () => {
        const page = props(liveMinimalFixture);

        expect(page.home).toBeNull();
        renderWithUser(<BusinessCampaign {...page} />);

        expect(campaignSheet()).toBeInTheDocument();
        expect(screen.queryByText('GreenLeaf Agro')).not.toBeInTheDocument();

        const nav = screen.getByRole('navigation', { name: 'App navigation' });

        expect(within(nav).getByRole('link', { name: 'Home' })).toHaveAttribute(
            'href',
            '/business',
        );
        expect(
            within(nav).queryByRole('link', { name: 'Reports' }),
        ).not.toBeInTheDocument();
        expect(
            within(nav).queryByRole('link', { name: 'Profile' }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Launcher' })).toHaveAttribute(
            'href',
            '/dashboard',
        );
    });

    it('draws Home beneath the sheet but reads its navigation from shell_links', () => {
        const page = props(liveFixture);

        page.shell_links = { ...page.shell_links, profile: null };
        renderWithUser(<BusinessCampaign {...page} />);

        expect(screen.getAllByText('GreenLeaf Agro').length).toBeGreaterThan(0);

        const nav = screen.getByRole('navigation', { name: 'App navigation' });

        expect(
            within(nav).getByRole('link', { name: 'Reports' }),
        ).toHaveAttribute('href', '/preview/business-reports');
        expect(
            within(nav).queryByRole('link', { name: 'Profile' }),
        ).not.toBeInTheDocument();
    });
});

const v2Props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessCampaignV2Props;

const repaying = (page: BusinessCampaignV2Props) =>
    page.note.progress as Extract<CampaignProgressV2, { phase: 'repaying' }>;

describe('A repaying campaign (business-campaign-v2)', () => {
    it('shows repayment progress, the next instalment and the repayments link, with no investor list', () => {
        renderWithUser(<BusinessCampaign {...v2Props(repayingFixture)} />);
        const view = within(campaignSheet());

        expect(view.getByText('Notes issued')).toHaveClass(
            'text-rz-accent-app-text',
        );
        expect(view.getByText('Repaying · on track')).toBeInTheDocument();
        expect(view.queryByText(/days? past due/)).not.toBeInTheDocument();
        expect(view.queryByRole('alert')).not.toBeInTheDocument();
        expect(view.getByText('2 of 6')).toBeInTheDocument();
        expect(view.getByText('318')).toBeInTheDocument();
        expect(
            view.getByRole('progressbar', { name: 'Repayment tracker' }),
        ).toHaveValue(33.3);
        expect(view.getByText('4 instalments left')).toBeInTheDocument();
        expect(view.getByText('Total to repay')).toBeInTheDocument();
        expect(view.getByText('RWF 20,790,000')).toBeInTheDocument();

        const next = view.getByRole('region', { name: 'Next instalment' });

        expect(next).toHaveTextContent('Instalment 3 · due 23 Dec 2026');
        expect(next).toHaveTextContent('PrincipalRWF 3,000,000');
        expect(next).toHaveTextContent('ReturnRWF 405,000');
        expect(next).toHaveTextContent('Service feeRWF 60,000');
        expect(next).toHaveTextContent('Total dueRWF 3,465,000');
        expect(
            view.getByRole('link', { name: 'Open repayments' }),
        ).toHaveAttribute('href', '/preview/business-repayments');

        for (const name of IDENTITIES) {
            expect(view.queryByText(name)).not.toBeInTheDocument();
        }

        expect(
            view.queryByRole('button', { name: 'Cancel this raise' }),
        ).not.toBeInTheDocument();
    });

    it('keeps the arrears restriction beside how late the payment is', () => {
        renderWithUser(<BusinessCampaign {...v2Props(overdueFixture)} />);
        const view = within(campaignSheet());

        expect(view.getByText('Payment overdue')).toHaveClass(
            'text-rz-danger-text',
        );
        expect(view.getByText('8 days past due')).toBeInTheDocument();
        expect(view.getByRole('alert')).toHaveTextContent(
            'In arrears since 24 Dec 2026. Resale of this note is paused until the overdue payment is received and reconciled.',
        );
        expect(
            view.getByRole('region', { name: 'Next instalment' }),
        ).toHaveTextContent('Instalment 4 · due 23 Jan 2027');
    });

    it.each([
        ['due_today', 0, 'Payment due today', null],
        ['overdue', 1, 'Payment overdue', '1 day past due'],
        ['repaid', null, 'Fully repaid', null],
        ['defaulted', 40, 'In default', '40 days past due'],
    ] as const)(
        'names the %s state from the server, never from the browser clock',
        (state, dpd, label, late) => {
            const page = v2Props(repayingFixture);

            repaying(page).servicing.state = state;
            repaying(page).servicing.dpd = dpd;
            repaying(page).servicing.restriction = {
                code: 'RESTRICTION_ACTIVE',
                since: '2026-11-30T09:00:00+02:00',
            };
            renderWithUser(<BusinessCampaign {...page} />);
            const view = within(campaignSheet());

            expect(view.getByText(label)).toBeInTheDocument();
            expect(view.getByRole('alert')).toHaveTextContent(
                'A restriction has applied since 30 Nov 2026. Repayments are still accepted.',
            );

            if (late === null) {
                expect(
                    view.queryByText(/days? past due/),
                ).not.toBeInTheDocument();
            } else {
                expect(view.getByText(late)).toBeInTheDocument();
            }
        },
    );

    it('clamps the repayment tracker to its range', () => {
        const page = v2Props(repayingFixture);

        repaying(page).servicing.progress.repaid_pct = '-2.0';
        renderWithUser(<BusinessCampaign {...page} />);

        expect(
            within(campaignSheet()).getByRole('progressbar', {
                name: 'Repayment tracker',
            }),
        ).toHaveValue(0);
    });

    it('shows a fully repaid note with its total and completion date', () => {
        renderWithUser(<BusinessCampaign {...v2Props(repaidFixture)} />);
        const view = within(campaignSheet());

        expect(view.getByText('RWF 20.8M')).toBeInTheDocument();
        expect(view.getByRole('status')).toHaveTextContent(
            'Every instalment is paid: RWF 20,790,000 repaid by 23 Mar 2027.',
        );
        expect(
            view.queryByRole('link', { name: 'Open repayments' }),
        ).not.toBeInTheDocument();
    });

    it('renders the v2 live-minimal shape with no next instalment and nothing offered', () => {
        renderWithUser(<BusinessCampaign {...v2Props(v2LiveMinimalFixture)} />);
        const view = within(campaignSheet());

        expect(
            view.queryByRole('region', { name: 'Next instalment' }),
        ).not.toBeInTheDocument();
        expect(view.getByText('6 instalments left')).toBeInTheDocument();
        expect(
            view.getByRole('link', { name: 'Open repayments' }),
        ).toHaveAttribute(
            'href',
            '/business/01k6r3d9t4e1g5h0j3k7m2n8p4/notes/01k6r3d9t4e1g5h0j3k7m2n8p4/repayments',
        );
        expect(view.queryAllByRole('button')).toEqual([]);
    });
});
