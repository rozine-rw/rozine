import {
    act,
    fireEvent,
    render,
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
import AdminDisbursements from '@/pages/admin/disbursements';
import type {
    C3AdminDisbursementsProps,
    C3DisbursementDetail,
} from '@/types/admin';
import awaitingFixture from '../../../resources/fixtures/ui/admin-disbursements-awaiting-second.json';
import dispatchedPendingFixture from '../../../resources/fixtures/ui/admin-disbursements-dispatched-pending.json';
import dispatchedUnknownFixture from '../../../resources/fixtures/ui/admin-disbursements-dispatched-unknown.json';
import exceptionFixture from '../../../resources/fixtures/ui/admin-disbursements-exception.json';
import failedClosingFixture from '../../../resources/fixtures/ui/admin-disbursements-failed-closing.json';
import liveFixture from '../../../resources/fixtures/ui/admin-disbursements-live-minimal.json';
import notRecordedFixture from '../../../resources/fixtures/ui/admin-disbursements-not-recorded.json';
import onHoldFixture from '../../../resources/fixtures/ui/admin-disbursements-on-hold.json';
import queuedFixture from '../../../resources/fixtures/ui/admin-disbursements-queued.json';
import readyFixture from '../../../resources/fixtures/ui/admin-disbursements-ready.json';
import refusedFixture from '../../../resources/fixtures/ui/admin-disbursements-refused.json';
import succeededFixture from '../../../resources/fixtures/ui/admin-disbursements-succeeded.json';
import unconfirmedFixture from '../../../resources/fixtures/ui/admin-disbursements-unconfirmed.json';
import failureFixture from '../../../resources/fixtures/ui/admin-disbursements-verified-failure-unreconciled.json';
import lostFixture from '../../../resources/fixtures/ui/admin-disbursements-visibility-lost.json';
import queueFixture from '../../../resources/fixtures/ui/admin-disbursements.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, invalid, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as C3AdminDisbursementsProps;

/** The fixture's open disbursement, for tests that reshape it. */
const opened = (fixture: C3AdminDisbursementsProps): C3DisbursementDetail => {
    if (fixture.disbursement === null) {
        throw new Error('the fixture opens a disbursement');
    }

    return fixture.disbursement;
};

const drawer = () => screen.getByRole('dialog', { name: /^Disbursement / });

/** The drawer's labelled buttons, in order. */
const drawerButtons = () =>
    within(drawer())
        .getAllByRole('button')
        .map((button) => button.textContent)
        .filter((label) => label !== '');

const completed = (code: string, data: unknown) => ({
    operation_id: '01k5zo0000000000000000000a',
    status: 'completed',
    code,
    data,
    revision: 4,
    policy_version: 'staff-disbursement-policy-synthetic-0',
    recorded_at: '2026-09-25T10:00:02+02:00',
    server_time: '2026-09-25T10:00:03+02:00',
    allowed_actions: [],
    field_errors: {},
});

const next = {
    url: '/preview/admin-disbursements-on-hold',
    method: 'get',
};

const RELOAD = [
    'disbursement',
    'disbursements',
    'needs_attention',
    'allowed_actions',
    'badges',
    'server_time',
];

const WAITING_FOR_DESTINATION =
    "Waiting for a verified payout destination. Payment starts automatically once the business's destination is verified.";

const WAITING_FOR_RUN = 'Payment starts automatically on the next run.';

beforeEach(resetInertia);

afterEach(() => {
    vi.useRealTimers();
});

describe('Disbursements queue', () => {
    it('lists each disbursement with its state and provider outcome, with no deadline', () => {
        render(<AdminDisbursements {...props(queueFixture)} />);

        expect(screen.getByText('4 need attention')).toBeInTheDocument();
        expect(
            screen.getByRole('columnheader', { name: 'Provider' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('columnheader', { name: 'Due' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByText(/Overdue|Tomorrow/)).not.toBeInTheDocument();
        expect(screen.getByText('PAY-2026-0612')).toBeInTheDocument();
        expect(screen.getByText('MoMo ••• 4521')).toBeInTheDocument();
        expect(screen.getByText('RWF 18M')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Open PAY-2026-0605' }),
        ).toHaveTextContent('Open');
        expect(
            screen.getByRole('link', { name: 'Open PAY-2026-0607' }),
        ).toHaveTextContent('Open');
        expect(
            screen.getByRole('link', { name: 'Open PAY-2026-0601' }),
        ).toHaveTextContent('Inspect');
        expect(screen.getAllByText('Not sent')).toHaveLength(6);
        expect(
            screen.getByText('Pending · not yet confirmed'),
        ).toBeInTheDocument();
        expect(screen.getAllByText('Unknown · not yet confirmed')).toHaveLength(
            2,
        );
        expect(screen.getByText('Failed · verified')).toBeInTheDocument();
        expect(screen.getByText('Succeeded · verified')).toBeInTheDocument();
        expect(screen.getByText('Paid · reconciled')).toBeInTheDocument();
        expect(
            screen.getByText('Failed closing · refunded'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Intent recorded · queued'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Waiting for automatic payment'),
        ).toBeInTheDocument();
        expect(screen.getByText('On hold')).toBeInTheDocument();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Release all' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByText(/Authorize/)).not.toBeInTheDocument();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('labels a disbursement waiting for automatic payment, and a legacy awaiting one, as to check', () => {
        render(<AdminDisbursements {...props(queueFixture)} />);

        expect(
            screen.getByRole('link', { name: 'Open PAY-2026-0612' }),
        ).toHaveTextContent('Check');
        expect(
            screen.getByRole('link', { name: 'Open PAY-2026-0611' }),
        ).toHaveTextContent('Check');
        expect(
            screen
                .getAllByRole('link', { name: /^Open PAY-/ })
                .filter((link) => link.textContent === 'Check'),
        ).toHaveLength(3);
    });

    it('never shows an unconfirmed provider outcome as paid or failed', () => {
        render(<AdminDisbursements {...props(queueFixture)} />);

        for (const reference of ['PAY-2026-0605', 'PAY-2026-0604']) {
            const [row] = screen
                .getAllByRole('row')
                .filter((candidate) =>
                    within(candidate).queryByText(reference),
                );

            expect(row).toHaveTextContent(/not yet confirmed/);
            expect(row).not.toHaveTextContent(/Paid|Failed|Succeeded/);
        }
    });

    it('shows the empty and search states, and a further page, with no attention badge', () => {
        const fixture = props(queueFixture);

        fixture.needs_attention = 0;
        fixture.pagination = {
            next: { url: '/preview/admin-disbursements', method: 'get' },
        };
        render(
            <AdminDisbursements
                {...fixture}
                disbursements={[]}
                search="kivu"
            />,
        );

        expect(screen.queryByText(/need attention/)).not.toBeInTheDocument();
        expect(
            screen.getByText('All disbursements released'),
        ).toBeInTheDocument();
        /* Outside the table's horizontal scroller, so it stays centred on a phone. */
        expect(
            within(screen.getByRole('table')).queryByText(
                'All disbursements released',
            ),
        ).not.toBeInTheDocument();
        expect(screen.getByText('No matches on this page')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Older disbursements' }),
        ).toHaveAttribute('href', '/preview/admin-disbursements');
    });

    it('counts the disbursements that need attention', () => {
        const fixture = props(queueFixture);

        fixture.needs_attention = 1;
        render(<AdminDisbursements {...fixture} />);

        expect(screen.getByText('1 need attention')).toBeInTheDocument();
    });

    it('says when the viewer can no longer view the record they opened', () => {
        render(<AdminDisbursements {...props(lostFixture)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'You can no longer view this record.',
        );
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        expect(screen.getByText('PAY-2026-0612')).toBeInTheDocument();
    });
});

describe('Disbursement drawer', () => {
    it('shows the platform fee taken from the amount and what the Business receives', () => {
        const page = props(readyFixture);
        opened(page).platform_fee = {
            fee: { currency: 'RWF', amount: '540000' },
            audit_partner_share: { currency: 'RWF', amount: '216000' },
            rozine_share: { currency: 'RWF', amount: '324000' },
            payout: { currency: 'RWF', amount: '17460000' },
            version: 'brs-2.0-fee-r01',
        };
        render(<AdminDisbursements {...page} />);

        expect(
            within(drawer()).getByText(
                '3% platform fee RWF 540,000, of which RWF 216,000 to the Audit Partner. The Business receives RWF 17,460,000.',
            ),
        ).toBeInTheDocument();
    });

    it('shows a disbursement waiting for automatic payment with no deadline and only its allowed actions', () => {
        render(<AdminDisbursements {...props(readyFixture)} />);
        const panel = drawer();

        expect(panel).toHaveAccessibleName('Disbursement PAY-2026-0612');
        expect(
            within(panel).getByText('Waiting for automatic payment'),
        ).toBeInTheDocument();
        expect(
            within(panel).getAllByText('RWF 18,000,000').length,
        ).toBeGreaterThan(0);
        expect(
            within(panel).getByText('No deadline sourced'),
        ).toBeInTheDocument();
        expect(
            within(screen.getByRole('region', { name: 'Precheck' })).getByText(
                'Not run yet',
            ),
        ).toBeInTheDocument();
        expect(drawerButtons()).toEqual(['Hold']);
        expect(
            screen.queryByRole('region', { name: 'Hold' }),
        ).not.toBeInTheDocument();
        expect(
            within(panel).queryByText(/step-up|Approv|Authoriz|Independence/),
        ).not.toBeInTheDocument();
        expect(
            within(panel).queryByRole('link', { name: 'Open in the ledger' }),
        ).not.toBeInTheDocument();
    });

    it('shows the automatic payment rule, the payout and what it waits for', () => {
        render(<AdminDisbursements {...props(readyFixture)} />);
        const automation = screen.getByRole('region', {
            name: 'Automatic payment',
        });

        expect(automation).toHaveTextContent('Business receivesRWF 18,000,000');
        expect(automation).toHaveTextContent('Rulebrs-2.0-svc-r01');
        expect(automation).toHaveTextContent(WAITING_FOR_DESTINATION);
        expect(automation).not.toHaveTextContent(WAITING_FOR_RUN);
    });

    it('says a disbursement with a verified destination pays on the next run', () => {
        const page = props(readyFixture);

        opened(page).automation.waiting_for = 'next_run';
        render(<AdminDisbursements {...page} />);
        const automation = screen.getByRole('region', {
            name: 'Automatic payment',
        });

        expect(automation).toHaveTextContent(WAITING_FOR_RUN);
        expect(automation).not.toHaveTextContent(WAITING_FOR_DESTINATION);
    });

    it('shows a legacy awaiting disbursement as paid automatically, with no approval to give and nothing awaited', () => {
        render(<AdminDisbursements {...props(awaitingFixture)} />);
        const automation = screen.getByRole('region', {
            name: 'Automatic payment',
        });

        expect(
            within(drawer()).getByText('Awaiting second approver'),
        ).toBeInTheDocument();
        expect(automation).toHaveTextContent('Business receivesRWF 24,100,000');
        expect(automation).not.toHaveTextContent(WAITING_FOR_DESTINATION);
        expect(automation).not.toHaveTextContent(WAITING_FOR_RUN);
        expect(automation).not.toHaveTextContent(/Waiting for|Payment starts/);
        expect(
            within(screen.getByRole('region', { name: 'Precheck' })).getByText(
                'Passed',
            ),
        ).toBeInTheDocument();
        expect(drawerButtons()).toEqual(['Hold']);
        expect(
            screen.queryByRole('checkbox', { name: /Independence/ }),
        ).not.toBeInTheDocument();
    });

    it('offers nothing the server has not allowed, even with a route', () => {
        const fixture = props(readyFixture);
        const disbursement = opened(fixture);

        disbursement.allowed_actions = ['disbursement.requery'];
        render(<AdminDisbursements {...fixture} />);

        expect(
            within(drawer()).queryByRole('button', {
                name: /Hold|Release|Ask the provider/,
            }),
        ).not.toBeInTheDocument();
    });

    it('holds with a reason through the operation command and follows the next page', async () => {
        inertia.queue.push(
            answers(
                completed('DISBURSEMENT_HELD', {
                    receipt: {},
                    current: null,
                    next,
                }),
            ),
        );
        const { user } = renderWithUser(
            <AdminDisbursements {...props(readyFixture)} />,
        );

        await user.click(screen.getByRole('button', { name: 'Hold' }));
        const stage = screen.getByRole('form', {
            name: 'Put this release on hold',
        });

        expect(stage).toHaveTextContent(
            'Nothing is paid while it is held. Say why, so the next person knows what to clear.',
        );
        expect(stage).toHaveTextContent(
            'Logged to the audit trail as A. Diane · Approver',
        );
        expect(
            within(stage).getByRole('button', { name: 'Hold release' }),
        ).toBeDisabled();
        await user.type(within(stage).getByRole('textbox'), 'Check number.');
        await user.click(
            within(stage).getByRole('button', { name: 'Hold release' }),
        );

        await waitFor(() => expect(inertia.visits).toEqual([next]));
        expect(
            within(drawer()).getByText('Waiting for automatic payment'),
        ).toBeInTheDocument();
        expect(inertia.calls).toEqual([
            {
                url: '/preview/admin-disbursements-on-hold',
                method: 'post',
                body: {
                    request_id: expect.any(String),
                    expected_revision: 3,
                    reason: 'Check number.',
                },
            },
        ]);
        expect(inertia.posts).toEqual([]);
    });

    it('reloads when a completed command returns no record, and cancels a stage', async () => {
        inertia.queue.push(answers(completed('DISBURSEMENT_HELD', null)));
        const { user } = renderWithUser(
            <AdminDisbursements {...props(readyFixture)} />,
        );

        await user.click(screen.getByRole('button', { name: 'Hold' }));
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(screen.queryByRole('form')).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Hold' }));
        const stage = screen.getByRole('form', {
            name: 'Put this release on hold',
        });

        await user.type(within(stage).getByRole('textbox'), 'Check number.');
        await user.click(
            within(stage).getByRole('button', { name: 'Hold release' }),
        );

        await waitFor(() => expect(inertia.reload).toHaveLength(1));
        expect(inertia.reload[0]).toMatchObject({
            only: expect.arrayContaining(['disbursement', 'allowed_actions']),
        });
        expect(inertia.visits).toEqual([]);
    });

    it('shows who held a disbursement and why, and lets staff release it so automatic payment runs again', async () => {
        const { user } = renderWithUser(
            <AdminDisbursements {...props(onHoldFixture)} />,
        );
        const hold = screen.getByRole('region', { name: 'Hold' });

        expect(hold).toHaveTextContent('Grace Kalisa · 2026-09-24 10:05:00');
        expect(hold).toHaveTextContent(
            'Reason: Waiting for the business to confirm its new MoMo number.',
        );
        expect(hold).toHaveTextContent(
            'Releasing the hold lets automatic payment run again. Any authorised staff member may release it, including the one who placed it.',
        );
        expect(
            screen.getByRole('region', { name: 'Automatic payment' }),
        ).not.toHaveTextContent(/Payment starts automatically/);
        expect(drawerButtons()).toEqual(['Release hold']);

        await user.click(screen.getByRole('button', { name: 'Release hold' }));
        expect(
            screen.getByRole('form', { name: 'Release this hold' }),
        ).toHaveTextContent(
            'Automatic payment runs again once the hold is released. It goes back to where it was before the hold.',
        );
    });

    it('lets the staff member who placed a hold release it', async () => {
        const page = props(onHoldFixture);
        const disbursement = opened(page);

        if (disbursement.hold === null) {
            throw new Error('the fixture holds the disbursement');
        }

        disbursement.hold.placed_by.actor = page.viewer.name;
        const { user } = renderWithUser(<AdminDisbursements {...page} />);

        expect(screen.getByRole('region', { name: 'Hold' })).toHaveTextContent(
            'A. Diane · 2026-09-24 10:05:00',
        );
        const release = screen.getByRole('button', { name: 'Release hold' });

        expect(release).toBeEnabled();
        expect(release).not.toHaveAttribute('aria-describedby');
        expect(drawer()).not.toHaveTextContent(/You placed this hold/);

        await user.click(release);
        await user.type(screen.getByRole('textbox'), 'Number confirmed.');
        await user.click(
            within(
                screen.getByRole('form', { name: 'Release this hold' }),
            ).getByRole('button', { name: 'Release hold' }),
        );

        expect(inertia.calls).toEqual([
            {
                url: disbursement.actions.release_hold?.url,
                method: 'post',
                body: {
                    request_id: expect.any(String),
                    expected_revision: 5,
                    reason: 'Number confirmed.',
                },
            },
        ]);
    });

    it('shows a queued intent as recorded, not paid, and keeps its receipt while the current state moves on', () => {
        const fixture = props(queuedFixture);
        const { rerender } = render(<AdminDisbursements {...fixture} />);
        const intent = () =>
            screen.getByRole('region', { name: 'Payment intent' });
        const receipt = intent().textContent;

        expect(intent()).toHaveTextContent(
            'Intent recorded — not a payment. The payment worker sends it only after its own recheck.',
        );
        expect(intent()).toHaveTextContent('DISBURSEMENT_INTENT_RECORDED');
        expect(intent()).toHaveTextContent('RWF 3,100,000');
        expect(intent()).toHaveTextContent(
            "Not sent yet: the worker hasn't sent this payment.",
        );
        expect(
            screen.queryByRole('region', { name: 'Provider outcome' }),
        ).not.toBeInTheDocument();
        expect(within(drawer()).queryAllByRole('button')).toEqual([]);

        const moved = structuredClone(fixture);
        const current = opened(moved);

        current.state = 'dispatched';
        current.provider_state = 'pending';
        current.revision = 9;
        current.amount = { currency: 'RWF', amount: '1' };
        current.dispatch = {
            sent_at: '2026-09-24T11:41:10+02:00',
            recheck: {
                state: 'passed',
                checked_at: '2026-09-24T11:41:08+02:00',
                causes: [],
            },
        };
        rerender(<AdminDisbursements {...moved} />);

        expect(
            intent().textContent?.replace(
                "Not sent yet: the worker hasn't sent this payment.",
                '',
            ),
        ).toBe(
            receipt?.replace(
                "Not sent yet: the worker hasn't sent this payment.",
                '',
            ),
        );
        expect(
            screen.getByRole('region', { name: 'Dispatch' }),
        ).toHaveTextContent('Worker recheckPassed');
    });

    it('says an intent is not sent only while it is queued', () => {
        const fixture = props(queuedFixture);

        opened(fixture).state = 'on_hold';
        render(<AdminDisbursements {...fixture} />);

        expect(
            screen.getByRole('region', { name: 'Payment intent' }),
        ).not.toHaveTextContent('Not sent yet');
    });

    it('explains a pending and an unknown provider outcome as neither paid nor failed, with requery only', async () => {
        const { user, unmount } = renderWithUser(
            <AdminDisbursements {...props(dispatchedPendingFixture)} />,
        );
        const outcome = screen.getByRole('region', {
            name: 'Provider outcome',
        });

        expect(outcome).toHaveTextContent(
            "Pending: the provider hasn't confirmed an outcome. This is not paid and not failed.",
        );
        expect(outcome).toHaveTextContent('Not reconciled yet');
        expect(outcome).toHaveTextContent('It never sends the payment again.');
        expect(drawerButtons()).toEqual(['Ask the provider again']);

        await user.click(
            screen.getByRole('button', { name: 'Ask the provider again' }),
        );
        expect(
            screen.getByRole('form', { name: 'Ask the provider again' }),
        ).toHaveTextContent(
            'This asks the provider about the same operation. It never sends the payment again,',
        );
        unmount();

        render(<AdminDisbursements {...props(dispatchedUnknownFixture)} />);
        const unknown = screen.getByRole('region', {
            name: 'Provider outcome',
        });

        expect(unknown).toHaveTextContent(
            "Unknown: the provider's outcome isn't confirmed. This is not paid and not failed.",
        );
        expect(unknown).toHaveTextContent('MM-240924-77120');
        expect(drawer()).not.toHaveTextContent(/Paid|Refunded|Failed:/);
    });

    it('keeps a verified failure open until it is reconciled', () => {
        render(<AdminDisbursements {...props(failureFixture)} />);
        const outcome = screen.getByRole('region', {
            name: 'Provider outcome',
        });

        expect(outcome).toHaveTextContent(
            'Failed: the provider verified a final failure.',
        );
        expect(outcome).toHaveTextContent(
            'Not reconciled yet: nothing is closed or refunded until the failure is reconciled.',
        );
        expect(outcome).toHaveTextContent('DESTINATION_ACCOUNT_CLOSED');
        expect(outcome).toHaveTextContent('EQ-240924-30551');
        expect(
            screen.queryByRole('region', { name: 'Refunds' }),
        ).not.toBeInTheDocument();
    });

    it('shows a reconciliation exception as blocked, never reconciled', () => {
        render(<AdminDisbursements {...props(exceptionFixture)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'This disbursement stays blocked and is not reconciled.',
        );
        expect(
            screen.getByText('Exception · blocked, not reconciled'),
        ).toBeInTheDocument();
        expect(screen.queryByText('Reconciled')).not.toBeInTheDocument();
    });

    it('shows a failed closing with its causes, refunds and receipt', () => {
        render(<AdminDisbursements {...props(failedClosingFixture)} />);
        const precheck = screen.getByRole('region', { name: 'Precheck' });

        expect(precheck).toHaveTextContent('Failed');
        expect(precheck).toHaveTextContent('DESTINATION_UNVERIFIED');
        const refunds = screen.getByRole('region', { name: 'Refunds' });

        expect(refunds).toHaveTextContent('Commitments refunded14');
        expect(refunds).toHaveTextContent('RWF 2,600,000');
        expect(refunds).toHaveTextContent('COMMITMENT_REFUNDED');
        expect(
            screen.getByRole('link', { name: 'Open in the ledger' }),
        ).toHaveAttribute('href', '/preview/admin-ledger');
        expect(screen.queryByText(/step-up/)).not.toBeInTheDocument();
    });

    it('shows a success as verified, reconciled and issued', () => {
        render(<AdminDisbursements {...props(succeededFixture)} />);

        const outcome = screen.getByRole('region', {
            name: 'Provider outcome',
        });

        expect(outcome).toHaveTextContent(
            'Succeeded: the provider verified the payment.',
        );
        expect(outcome).toHaveTextContent('ReconciliationReconciled');
        const issue = screen.getByRole('region', { name: 'Issue' });

        expect(issue).toHaveTextContent('Holdings issued31');
        expect(issue).toHaveTextContent('2026-09-24 12:00:07');
        expect(issue).toHaveTextContent('24 Sept 2026');
    });

    it.each([
        ['hold', readyFixture, 'Hold', 'Hold release', 3],
        ['release_hold', onHoldFixture, 'Release hold', 'Release hold', 5],
        [
            'requery',
            dispatchedUnknownFixture,
            'Ask the provider again',
            'Ask the provider',
            7,
        ],
    ])(
        'sends %s only with a written reason, to its own route, carrying the revision it read',
        async (key, fixture, open, cta, revision) => {
            const page = props(fixture);
            const disbursement = opened(page);
            const { user } = renderWithUser(<AdminDisbursements {...page} />);

            await user.click(
                within(drawer()).getByRole('button', { name: open }),
            );
            const submit = within(drawer()).getByRole('button', {
                name: cta,
            });

            expect(submit).toBeDisabled();
            await user.type(screen.getByRole('textbox'), '   ');
            expect(submit).toBeDisabled();
            await user.type(screen.getByRole('textbox'), 'Checked.');
            await user.click(submit);

            expect(inertia.calls).toEqual([
                {
                    url: disbursement.actions[
                        key as keyof typeof disbursement.actions
                    ]?.url,
                    method: 'post',
                    body: {
                        request_id: expect.any(String),
                        expected_revision: revision,
                        reason: '   Checked.',
                    },
                },
            ]);
        },
    );

    it.each([
        ['queued', queuedFixture],
        ['dispatched and pending', dispatchedPendingFixture],
        ['dispatched and unknown', dispatchedUnknownFixture],
        ['a verified failure not yet reconciled', failureFixture],
        ['a reconciliation exception', exceptionFixture],
        ['a failed closing', failedClosingFixture],
    ])('issues nothing while %s', (_label, fixture) => {
        render(<AdminDisbursements {...props(fixture)} />);

        expect(
            screen.queryByRole('region', { name: 'Issue' }),
        ).not.toBeInTheDocument();
        expect(drawer()).not.toHaveTextContent(/Holdings issued|Paid ·/);
    });

    it('renders a live payload, with no preview seed and no preview URL, and sends to the live route', async () => {
        const live = JSON.parse(
            JSON.stringify(liveFixture.props).replaceAll('/preview/', '/'),
        ) as C3AdminDisbursementsProps;
        const disbursement = opened(live);

        disbursement.allowed_actions = ['disbursement.hold'];
        live.allowed_actions = ['disbursement.hold'];
        inertia.queue.push(
            fails(503),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        const { user } = renderWithUser(<AdminDisbursements {...live} />);

        expect(live).not.toHaveProperty('preview_outcome');
        expect(screen.queryByRole('status')).not.toBeInTheDocument();

        for (const link of screen.queryAllByRole('link')) {
            expect(link.getAttribute('href')).not.toMatch(/^\/preview\//);
        }

        await user.click(screen.getByRole('button', { name: 'Hold' }));
        await user.type(screen.getByRole('textbox'), 'Check number.');
        await user.click(screen.getByRole('button', { name: 'Hold release' }));

        await screen.findByText('Nothing was recorded');
        const sent = inertia.calls[0].body as { request_id: string };

        expect(inertia.calls).toEqual([
            {
                url: '/admin/disbursements/01k5zd7f3g9h2j6k0m4n8p1q5r/hold',
                method: 'post',
                body: expect.objectContaining({ reason: 'Check number.' }),
            },
            {
                url: `/admin/disbursements/operations/${sent.request_id}`,
                method: 'get',
                body: { command: 'disbursement.hold' },
            },
        ]);
    });

    it('renders the live-minimal contract with nothing offered', () => {
        render(
            <AdminDisbursements
                {...(structuredClone(
                    liveFixture.props,
                ) as unknown as C3AdminDisbursementsProps)}
            />,
        );

        expect(within(drawer()).queryAllByRole('button')).toEqual([]);
        expect(screen.getByText('Nothing recorded yet.')).toBeInTheDocument();
        expect(
            screen.getByRole('region', { name: 'Automatic payment' }),
        ).toHaveTextContent(WAITING_FOR_DESTINATION);
    });
});

describe('Disbursement commands', () => {
    it('looks up an uncertain answer and never resends it on its own', async () => {
        inertia.queue.push(
            fails(503, { code: 'RETRYABLE_CONTENTION' }),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        const { user } = renderWithUser(
            <AdminDisbursements {...props(readyFixture)} />,
        );

        await user.click(screen.getByRole('button', { name: 'Hold' }));
        await user.type(screen.getByRole('textbox'), 'Check number.');
        await user.click(screen.getByRole('button', { name: 'Hold release' }));

        await screen.findByText('Nothing was recorded');
        expect(inertia.calls).toHaveLength(2);
        expect(inertia.calls[1]).toEqual({
            url: `/preview/admin-disbursements-operation-${String((inertia.calls[0].body as { request_id: string }).request_id)}`,
            method: 'get',
            body: { command: 'disbursement.hold' },
        });
        expect(inertia.reload).toHaveLength(1);
        expect(
            screen.getByRole('button', { name: 'Hold release' }),
        ).toBeDisabled();

        inertia.queue.push(
            answers(
                completed('DISBURSEMENT_HELD', {
                    receipt: {},
                    current: null,
                    next,
                }),
            ),
        );
        await user.click(
            screen.getByRole('button', { name: 'Send the same request again' }),
        );

        await waitFor(() => expect(inertia.visits).toEqual([next]));
        expect(inertia.calls).toHaveLength(3);
        expect(inertia.calls[2]).toEqual(inertia.calls[0]);
    });

    it('looks up a lost requery answer under the same key, and never asks the provider twice on its own', async () => {
        inertia.queue.push(
            fails(503, { code: 'RETRYABLE_CONTENTION' }),
            fails(502),
            answers(
                completed('PROVIDER_QUERY_RECORDED', {
                    receipt: {},
                    current: null,
                    next,
                }),
            ),
        );
        const { user } = renderWithUser(
            <AdminDisbursements {...props(dispatchedUnknownFixture)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Ask the provider again' }),
        );
        await user.type(screen.getByRole('textbox'), 'Provider silent.');
        await user.click(
            screen.getByRole('button', { name: 'Ask the provider' }),
        );

        await screen.findByText('Not yet confirmed');
        expect(
            screen.getByRole('button', { name: 'Ask the provider' }),
        ).toBeDisabled();
        const sent = inertia.calls[0].body as { request_id: string };

        await user.click(screen.getByRole('button', { name: 'Check again' }));
        await waitFor(() => expect(inertia.visits).toEqual([next]));

        expect(inertia.calls.map(({ method }) => method)).toEqual([
            'post',
            'get',
            'get',
        ]);

        for (const lookup of inertia.calls.slice(1)) {
            expect(lookup).toEqual({
                url: `/preview/admin-disbursements-operation-${sent.request_id}`,
                method: 'get',
                body: { command: 'disbursement.requery' },
            });
        }

        expect(
            screen.getByRole('region', { name: 'Provider outcome' }),
        ).toHaveTextContent('Unknown:');
    });

    it('holds an unreachable outcome as not yet confirmed until checked again', async () => {
        inertia.queue.push(fails(503), fails(502));
        const { user } = renderWithUser(
            <AdminDisbursements {...props(readyFixture)} />,
        );

        await user.click(screen.getByRole('button', { name: 'Hold' }));
        await user.type(screen.getByRole('textbox'), 'Check number.');
        await user.click(screen.getByRole('button', { name: 'Hold release' }));

        await screen.findByText('Not yet confirmed');
        expect(inertia.calls).toHaveLength(2);
        expect(
            screen.getByRole('button', { name: 'Hold release' }),
        ).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Check again' }));
        expect(inertia.calls).toHaveLength(3);
        expect(inertia.calls[2].method).toBe('get');
    });

    it.each([
        [
            403,
            'STAFF_PERMISSION_REQUIRED',
            "Your staff permissions don't include this action.",
            0,
        ],
        [
            409,
            'VERSION_CONFLICT',
            'Something changed since this page loaded.',
            1,
        ],
    ])(
        'shows a %i %s refusal and refreshes only when fresh facts can help',
        async (status, code, text, reloads) => {
            inertia.queue.push(fails(status, { code }));
            const { user } = renderWithUser(
                <AdminDisbursements {...props(readyFixture)} />,
            );

            await user.click(screen.getByRole('button', { name: 'Hold' }));
            await user.type(screen.getByRole('textbox'), 'Check number.');
            await user.click(
                screen.getByRole('button', { name: 'Hold release' }),
            );

            await screen.findByText(new RegExp(text.slice(0, 30)));
            expect(inertia.reload).toHaveLength(reloads);
            expect(inertia.calls).toHaveLength(1);
        },
    );

    it('shows the server error on the reason', async () => {
        inertia.queue.push(invalid({ reason: 'Give a reason.' }));
        const { user } = renderWithUser(
            <AdminDisbursements {...props(readyFixture)} />,
        );

        await user.click(screen.getByRole('button', { name: 'Hold' }));
        await user.type(screen.getByRole('textbox'), ' x');
        await user.click(screen.getByRole('button', { name: 'Hold release' }));

        expect(await screen.findByText('Give a reason.')).toBeInTheDocument();
        expect(screen.getByRole('textbox')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
    });

    it('reads a refusal with no field to correct as a refusal of the command', async () => {
        inertia.queue.push(invalid({}));
        const { user } = renderWithUser(
            <AdminDisbursements {...props(readyFixture)} />,
        );

        await user.click(screen.getByRole('button', { name: 'Hold' }));
        await user.type(screen.getByRole('textbox'), 'Check number.');
        await user.click(screen.getByRole('button', { name: 'Hold release' }));

        expect(
            await screen.findByText(
                'This request was refused. Refresh and try again.',
            ),
        ).toBeInTheDocument();
        expect(inertia.calls).toHaveLength(1);
    });

    it('shows a seeded unconfirmed command with no action offered until it is known', () => {
        render(<AdminDisbursements {...props(unconfirmedFixture)} />);

        expect(screen.getByText('Not yet confirmed')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Check again' }),
        ).toBeEnabled();
        expect(screen.getByRole('button', { name: 'Hold' })).toBeDisabled();
    });

    it('offers the same-key retry only while the action is still allowed', async () => {
        const held = props(notRecordedFixture);

        if (held.preview_outcome?.kind !== 'not_recorded') {
            throw new Error(
                'the fixture seeds a command that was not recorded',
            );
        }

        held.preview_outcome.command.name = 'disbursement.hold';
        const { user, unmount } = renderWithUser(
            <AdminDisbursements {...held} />,
        );

        inertia.queue.push(
            answers(
                completed('DISBURSEMENT_HELD', {
                    receipt: {},
                    current: null,
                    next,
                }),
            ),
        );
        await user.click(
            screen.getByRole('button', { name: 'Send the same request again' }),
        );
        await waitFor(() => expect(inertia.visits).toEqual([next]));
        expect(inertia.calls[0]).toEqual({
            url: opened(held).actions.hold?.url,
            method: 'post',
            body: notRecordedFixture.props.preview_outcome.command.payload,
        });
        unmount();

        const fixture = structuredClone(held);

        opened(fixture).allowed_actions = [];
        render(<AdminDisbursements {...fixture} />);

        expect(
            screen.getByText(
                'Nothing was recorded, and this action is no longer available with the current details.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', {
                name: 'Send the same request again',
            }),
        ).not.toBeInTheDocument();
    });

    it('shows a seeded refusal', () => {
        render(<AdminDisbursements {...props(refusedFixture)} />);

        expect(
            screen.getByText(
                'The payment intent is already recorded, so this can no longer change it.',
            ),
        ).toBeInTheDocument();
    });
});

describe('Disbursement requery', () => {
    it.each([
        ['a verified failure not yet reconciled', failureFixture, 7],
        ['an unresolved reconciliation exception', exceptionFixture, 7],
    ])(
        'offers requery for %s, as another observation of the same operation, never as reconciling it',
        async (_label, fixture, revision) => {
            const page = props(fixture);
            const disbursement = opened(page);

            inertia.queue.push(
                answers(
                    completed('PROVIDER_QUERY_RECORDED', {
                        receipt: {},
                        current: null,
                        next,
                    }),
                ),
            );
            const { user } = renderWithUser(<AdminDisbursements {...page} />);

            expect(drawerButtons()).toEqual(['Ask the provider again']);
            await user.click(
                screen.getByRole('button', { name: 'Ask the provider again' }),
            );
            const stage = screen.getByRole('form', {
                name: 'Ask the provider again',
            });

            expect(stage).toHaveTextContent(
                "It never sends the payment again, and it doesn't mark anything reconciled",
            );
            expect(drawer()).not.toHaveTextContent(
                /mark(ed)? (as )?reconciled/i,
            );
            await user.type(within(stage).getByRole('textbox'), 'Recheck.');
            await user.click(
                within(stage).getByRole('button', { name: 'Ask the provider' }),
            );
            await waitFor(() => expect(inertia.visits).toEqual([next]));

            expect(inertia.calls).toEqual([
                {
                    url: disbursement.actions.requery?.url,
                    method: 'post',
                    body: {
                        request_id: expect.any(String),
                        expected_revision: revision,
                        reason: 'Recheck.',
                    },
                },
            ]);
        },
    );

    it.each([
        ['a verified failure', failureFixture],
        ['an exception', exceptionFixture],
        ['a reconciled success', succeededFixture],
    ])(
        'offers no requery for %s unless the server lists it and its route',
        (_label, fixture) => {
            const unlisted = props(fixture);

            opened(unlisted).allowed_actions = [];
            opened(unlisted).actions.requery = {
                url: '/preview/admin-disbursements-dispatched-unknown',
                method: 'post',
            };
            const { unmount } = render(<AdminDisbursements {...unlisted} />);

            expect(
                screen.queryByRole('button', {
                    name: 'Ask the provider again',
                }),
            ).not.toBeInTheDocument();
            unmount();

            const routeless = props(fixture);

            opened(routeless).allowed_actions = ['disbursement.requery'];
            delete opened(routeless).actions.requery;
            render(<AdminDisbursements {...routeless} />);

            expect(
                screen.queryByRole('button', {
                    name: 'Ask the provider again',
                }),
            ).not.toBeInTheDocument();
            expect(inertia.calls).toEqual([]);
        },
    );
});

describe('Bounded polling', () => {
    it.each([
        ['queued', queuedFixture],
        ['dispatched and pending', dispatchedPendingFixture],
        ['dispatched and unknown', dispatchedUnknownFixture],
    ])(
        'polls a %s disbursement every ten seconds, then stops for a manual refresh',
        (_label, fixture) => {
            vi.useFakeTimers();
            render(<AdminDisbursements {...props(fixture)} />);

            act(() => {
                vi.advanceTimersByTime(POLL_INTERVAL_MS - 1);
            });
            expect(inertia.reload).toHaveLength(0);

            for (let poll = 0; poll < POLL_LIMIT; poll++) {
                act(() => {
                    vi.advanceTimersByTime(POLL_INTERVAL_MS);
                });
            }

            expect(inertia.reload).toHaveLength(POLL_LIMIT);
            expect(inertia.reload[0]).toEqual({ only: RELOAD });
            act(() => {
                vi.advanceTimersByTime(POLL_INTERVAL_MS * 3);
            });
            expect(inertia.reload).toHaveLength(POLL_LIMIT);
            expect(
                screen.getByText(/We've stopped checking automatically/),
            ).toBeInTheDocument();

            fireEvent.click(screen.getByRole('button', { name: 'Refresh' }));
            expect(inertia.reload).toHaveLength(POLL_LIMIT + 1);
            expect(
                screen.queryByText(/We've stopped checking automatically/),
            ).not.toBeInTheDocument();
        },
    );

    it('polls a dispatched disbursement the provider has not answered yet', () => {
        vi.useFakeTimers();
        const fixture = props(dispatchedPendingFixture);

        opened(fixture).provider = null;
        render(<AdminDisbursements {...fixture} />);

        act(() => {
            vi.advanceTimersByTime(POLL_INTERVAL_MS);
        });
        expect(inertia.reload).toHaveLength(1);
    });

    it.each([
        ['waiting for automatic payment', readyFixture],
        ['on hold', onHoldFixture],
        ['a verified failure', failureFixture],
        ['succeeded', succeededFixture],
    ])('does not poll %s', (_label, fixture) => {
        vi.useFakeTimers();
        render(<AdminDisbursements {...props(fixture)} />);

        act(() => {
            vi.advanceTimersByTime(POLL_INTERVAL_MS * 5);
        });
        expect(inertia.reload).toHaveLength(0);
    });
});
