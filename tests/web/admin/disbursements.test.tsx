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
import holdSelfFixture from '../../../resources/fixtures/ui/admin-disbursements-hold-self.json';
import liveFixture from '../../../resources/fixtures/ui/admin-disbursements-live-minimal.json';
import notRecordedFixture from '../../../resources/fixtures/ui/admin-disbursements-not-recorded.json';
import onHoldFixture from '../../../resources/fixtures/ui/admin-disbursements-on-hold.json';
import queuedFixture from '../../../resources/fixtures/ui/admin-disbursements-queued.json';
import readyFixture from '../../../resources/fixtures/ui/admin-disbursements-ready.json';
import refusedFixture from '../../../resources/fixtures/ui/admin-disbursements-refused.json';
import selfFixture from '../../../resources/fixtures/ui/admin-disbursements-self.json';
import succeededFixture from '../../../resources/fixtures/ui/admin-disbursements-succeeded.json';
import unconfirmedFixture from '../../../resources/fixtures/ui/admin-disbursements-unconfirmed.json';
import failureFixture from '../../../resources/fixtures/ui/admin-disbursements-verified-failure-unreconciled.json';
import lostFixture from '../../../resources/fixtures/ui/admin-disbursements-visibility-lost.json';
import queueFixture from '../../../resources/fixtures/ui/admin-disbursements.json';
import { renderWithUser } from '../helpers/render-with-user';
import {
    answers,
    fails,
    inertia,
    invalid,
    offline,
    resetInertia,
} from './inertia-mock';

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
    url: '/preview/admin-disbursements-awaiting-second',
    method: 'get',
};

/** A staff step-up route, as S3-D will list it in `step_up.route`; synthetic here. */
const STEP_UP_ROUTE = {
    url: '/preview/admin-disbursements-step-up',
    method: 'post',
} as const;

const DIGEST =
    'sha256:4f1c9a0e7b2d58c36e91f0a4d7b3c2e815a96f0d2b4c7e1a3f58d09c6b2e7a41';

/** The awaiting disbursement with a step-up route listed, so approval can be given. */
const approvable = (): C3AdminDisbursementsProps => {
    const fixture = props(awaitingFixture);

    opened(fixture).step_up = {
        purpose: 'disbursement.approve',
        route: STEP_UP_ROUTE,
    };

    return fixture;
};

/** The step-up exchange's answer, in the seal exchange's `{proof, expires_at}` shape. */
const proofOf = (proof: string, expiresAt = '2026-09-25T10:05:00+02:00') =>
    answers({ proof, expires_at: expiresAt });

beforeEach(resetInertia);

afterEach(() => {
    vi.useRealTimers();
});

describe('Disbursements queue', () => {
    it('lists each disbursement with its state and provider outcome, with no deadline', () => {
        render(<AdminDisbursements {...props(queueFixture)} />);

        expect(
            screen.getByText('2 awaiting second approver'),
        ).toBeInTheDocument();
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
            screen.getByRole('link', { name: 'Open PAY-2026-0612' }),
        ).toHaveTextContent('Authorize');
        expect(
            screen.getByRole('link', { name: 'Open PAY-2026-0611' }),
        ).toHaveTextContent('Check');
        expect(
            screen.getByRole('link', { name: 'Open PAY-2026-0605' }),
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
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Release all' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
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

    it('shows the empty and search states, and a further page', () => {
        const fixture = props(queueFixture);

        fixture.awaiting_second_approver = 0;
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

        expect(
            screen.queryByText(/awaiting second approver/),
        ).not.toBeInTheDocument();
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
    it('shows a ready disbursement with no deadline and only its allowed actions', () => {
        render(<AdminDisbursements {...props(readyFixture)} />);
        const panel = drawer();

        expect(panel).toHaveAccessibleName('Disbursement PAY-2026-0612');
        expect(within(panel).getByText('RWF 18,000,000')).toBeInTheDocument();
        expect(
            within(panel).getByText('No deadline sourced'),
        ).toBeInTheDocument();
        expect(
            within(screen.getByRole('region', { name: 'Precheck' })).getByText(
                'Not run yet',
            ),
        ).toBeInTheDocument();
        expect(
            within(
                screen.getByRole('region', { name: 'What approval binds' }),
            ).getByText('Set when the release is authorized.'),
        ).toBeInTheDocument();
        expect(
            within(panel).getByText(/There's no amount threshold/),
        ).toBeInTheDocument();
        expect(
            within(panel).getByText('Nobody has authorized this release yet.'),
        ).toBeInTheDocument();
        expect(
            within(panel)
                .getAllByRole('button')
                .map((button) => button.textContent)
                .filter((label) => label !== ''),
        ).toEqual(['Authorize release', 'Hold']);
        expect(within(panel).queryByText(/step-up/)).not.toBeInTheDocument();
        expect(
            within(panel).queryByRole('link', { name: 'Open in the ledger' }),
        ).not.toBeInTheDocument();
    });

    it('offers nothing the server has not allowed, even with a route', () => {
        const fixture = props(readyFixture);
        const disbursement = opened(fixture);

        disbursement.allowed_actions = ['disbursement.requery'];
        render(<AdminDisbursements {...fixture} />);

        expect(
            within(drawer()).queryByRole('button', {
                name: /Authorize|Hold|Ask the provider/,
            }),
        ).not.toBeInTheDocument();
    });

    it('authorizes with a reason through the operation command and follows the next page', async () => {
        inertia.queue.push(
            answers(
                completed('DISBURSEMENT_AUTHORIZED', {
                    receipt: {},
                    current: null,
                    next,
                }),
            ),
        );
        const { user } = renderWithUser(
            <AdminDisbursements {...props(readyFixture)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Authorize release' }),
        );
        const stage = screen.getByRole('form', {
            name: 'Authorize this release',
        });

        expect(stage).toHaveTextContent(
            'You authorize paying RWF 18,000,000 to GreenLeaf Agro. This runs the precheck',
        );
        expect(stage).toHaveTextContent(
            'Logged to the audit trail as A. Diane · Approver',
        );
        expect(
            within(stage).getByRole('button', { name: 'Authorize' }),
        ).toBeDisabled();
        await user.type(within(stage).getByRole('textbox'), 'Funded.');
        await user.click(
            within(stage).getByRole('button', { name: 'Authorize' }),
        );

        await waitFor(() => expect(inertia.visits).toEqual([next]));
        expect(
            within(drawer()).getByText('Ready to authorize'),
        ).toBeInTheDocument();
        expect(
            within(drawer()).getByText(
                'Nobody has authorized this release yet.',
            ),
        ).toBeInTheDocument();
        expect(inertia.calls).toEqual([
            {
                url: '/preview/admin-disbursements-awaiting-second',
                method: 'post',
                body: {
                    request_id: expect.any(String),
                    expected_revision: 3,
                    reason: 'Funded.',
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

    it('asks a different staff member to approve, and withholds approval while step-up has no route', async () => {
        const { user } = renderWithUser(
            <AdminDisbursements {...props(awaitingFixture)} />,
        );
        const panel = drawer();
        const approvals = within(panel).getByRole('list', {
            name: 'Approvals',
        });

        expect(
            within(approvals).getByText('Awaiting second approver'),
        ).toBeInTheDocument();
        expect(
            within(approvals).getByText('Eric Ndoli · 2026-09-24 09:12:00'),
        ).toBeInTheDocument();
        const binding = screen.getByRole('region', {
            name: 'What approval binds',
        });

        expect(binding).toHaveTextContent('RWF 24,100,000');
        expect(binding).toHaveTextContent('Equity ••• 0934');
        expect(binding).toHaveTextContent('sha256:4f1c9a0e');
        expect(
            within(screen.getByRole('region', { name: 'Precheck' })).getByText(
                'Passed',
            ),
        ).toBeInTheDocument();
        const approve = within(panel).getByRole('button', {
            name: 'Approve release',
        });

        expect(approve).toBeDisabled();
        expect(approve).toHaveAccessibleDescription(
            /That confirmation isn't available yet, so approval can't be given here./,
        );

        await user.click(within(panel).getByRole('button', { name: 'Reject' }));
        expect(
            screen.getByRole('form', { name: 'Reject this release' }),
        ).toHaveTextContent(
            "This voids the authorization only and records your reason. The raise isn't cancelled",
        );
    });

    it('withholds approval while nothing binds yet, even with a step-up route listed', () => {
        const fixture = props(awaitingFixture);

        opened(fixture).step_up = {
            purpose: 'disbursement.approve',
            route: STEP_UP_ROUTE,
        };
        opened(fixture).approval_binding = null;
        render(<AdminDisbursements {...fixture} />);
        const approve = screen.getByRole('button', {
            name: 'Approve release',
        });

        expect(approve).toBeDisabled();
        expect(approve).toHaveAttribute(
            'aria-describedby',
            'disbursement-step-up',
        );
        fireEvent.click(approve);
        expect(
            screen.queryByRole('form', { name: 'Approve this release' }),
        ).not.toBeInTheDocument();
        expect(inertia.calls).toEqual([]);
    });

    it('never lets the maker approve their own authorization', () => {
        render(<AdminDisbursements {...props(selfFixture)} />);
        const approve = screen.getByRole('button', {
            name: 'Approve release',
        });

        expect(approve).toBeDisabled();
        expect(approve).toHaveAccessibleDescription(
            "You authorized this release, so you can't approve it too. A different approver must check it.",
        );
        expect(screen.getByRole('button', { name: 'Hold' })).toBeEnabled();
    });

    it('lets another staff member release a hold, which neither approves nor pays', async () => {
        const { user } = renderWithUser(
            <AdminDisbursements {...props(onHoldFixture)} />,
        );
        const hold = screen.getByRole('region', { name: 'Hold' });

        expect(hold).toHaveTextContent('Grace Kalisa · 2026-09-24 10:05:00');
        expect(hold).toHaveTextContent(
            'Reason: Waiting for the business to confirm its new MoMo number.',
        );
        expect(hold).toHaveTextContent(
            "Releasing the hold doesn't approve this disbursement or send any payment.",
        );
        expect(hold).not.toHaveTextContent('You placed this hold');

        await user.click(screen.getByRole('button', { name: 'Release hold' }));
        expect(
            screen.getByRole('form', { name: 'Release this hold' }),
        ).toHaveTextContent(
            "Releasing the hold doesn't approve this disbursement or send any payment.",
        );
    });

    it('withholds hold release from the staff member who placed the hold', () => {
        render(<AdminDisbursements {...props(holdSelfFixture)} />);
        const release = screen.getByRole('button', { name: 'Release hold' });

        expect(release).toBeDisabled();
        expect(release).toHaveAccessibleDescription(
            'You placed this hold, so a different staff member must release it.',
        );
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
        expect(
            within(drawer())
                .getAllByRole('button')
                .map((button) => button.textContent)
                .filter((label) => label !== ''),
        ).toEqual(['Ask the provider again']);

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
        expect(
            screen.queryByText(/step-up confirmation/),
        ).not.toBeInTheDocument();
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
        ['authorize', readyFixture, 'Authorize release', 'Authorize', 3],
        ['hold', readyFixture, 'Hold', 'Hold release', 3],
        ['reject', awaitingFixture, 'Reject', 'Reject release', 4],
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

        disbursement.allowed_actions = ['disbursement.authorize'];
        live.allowed_actions = ['disbursement.authorize'];
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

        await user.click(
            screen.getByRole('button', { name: 'Authorize release' }),
        );
        await user.type(screen.getByRole('textbox'), 'Funded.');
        await user.click(screen.getByRole('button', { name: 'Authorize' }));

        await screen.findByText('Nothing was recorded');
        const sent = inertia.calls[0].body as { request_id: string };

        expect(inertia.calls).toEqual([
            {
                url: '/admin/disbursements/01k5zd7f3g9h2j6k0m4n8p1q5r/authorize',
                method: 'post',
                body: expect.objectContaining({ reason: 'Funded.' }),
            },
            {
                url: `/admin/disbursements/operations/${sent.request_id}`,
                method: 'get',
                body: { command: 'disbursement.authorize' },
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

        await user.click(
            screen.getByRole('button', { name: 'Authorize release' }),
        );
        await user.type(screen.getByRole('textbox'), 'Funded.');
        await user.click(screen.getByRole('button', { name: 'Authorize' }));

        await screen.findByText('Nothing was recorded');
        expect(inertia.calls).toHaveLength(2);
        expect(inertia.calls[1]).toEqual({
            url: `/preview/admin-disbursements-operation-${String((inertia.calls[0].body as { request_id: string }).request_id)}`,
            method: 'get',
            body: { command: 'disbursement.authorize' },
        });
        expect(inertia.reload).toHaveLength(1);
        expect(
            screen.getByRole('button', { name: 'Authorize' }),
        ).toBeDisabled();

        inertia.queue.push(
            answers(
                completed('DISBURSEMENT_AUTHORIZED', {
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
            'SELF_APPROVAL_FORBIDDEN',
            "A different staff member must do this: you can't check your own action.",
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

            await user.click(
                screen.getByRole('button', { name: 'Authorize release' }),
            );
            await user.type(screen.getByRole('textbox'), 'Funded.');
            await user.click(screen.getByRole('button', { name: 'Authorize' }));

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

        await user.click(
            screen.getByRole('button', { name: 'Authorize release' }),
        );
        await user.type(screen.getByRole('textbox'), ' x');
        await user.click(screen.getByRole('button', { name: 'Authorize' }));

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
        expect(
            screen.getByRole('button', { name: 'Authorize release' }),
        ).toBeDisabled();
        expect(screen.getByRole('button', { name: 'Hold' })).toBeDisabled();
    });

    it('offers the same-key retry only while the action is still allowed', async () => {
        const { user, unmount } = renderWithUser(
            <AdminDisbursements {...props(notRecordedFixture)} />,
        );

        inertia.queue.push(
            answers(
                completed('DISBURSEMENT_AUTHORIZED', {
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
        expect(inertia.calls[0].body).toEqual(
            notRecordedFixture.props.preview_outcome.command.payload,
        );
        unmount();

        const fixture = props(notRecordedFixture);

        opened(fixture).allowed_actions = ['disbursement.hold'];
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

describe('Disbursement approval step-up', () => {
    const openApproval = async (
        user: ReturnType<typeof renderWithUser>['user'],
    ) => {
        await user.click(
            within(drawer()).getByRole('button', { name: 'Approve release' }),
        );
    };

    const stepUp = async (
        user: ReturnType<typeof renderWithUser>['user'],
        code = '123456',
    ) => {
        await user.type(
            screen.getByRole('textbox', {
                name: 'Six-digit authenticator code',
            }),
            code,
        );
        await user.click(screen.getByRole('button', { name: 'Confirm code' }));
    };

    const approveWith = async (
        user: ReturnType<typeof renderWithUser>['user'],
        reason = 'Totals and destination match.',
    ) => {
        const stage = screen.getByRole('form', {
            name: 'Approve this release',
        });

        const field = within(stage).getByRole<HTMLTextAreaElement>('textbox');

        if (field.value === '') {
            await user.type(field, reason);
        }

        await user.click(
            within(stage).getByRole('button', {
                name: 'Approve and record intent',
            }),
        );
    };

    it('exchanges a code for a bound proof and sends it once, as step_up_proof, on approve', async () => {
        const fixture = approvable();
        const disbursement = opened(fixture);

        inertia.queue.push(
            proofOf('opaque-proof-1'),
            answers(
                completed('DISBURSEMENT_INTENT_RECORDED', {
                    receipt: {},
                    current: null,
                    next,
                }),
            ),
        );
        const { user } = renderWithUser(<AdminDisbursements {...fixture} />);

        expect(
            screen.queryByText(/That confirmation isn't available yet/),
        ).not.toBeInTheDocument();
        await openApproval(user);

        const stage = screen.getByRole('form', {
            name: 'Approve this release',
        });
        const submit = within(stage).getByRole('button', {
            name: 'Approve and record intent',
        });

        await user.type(within(stage).getByRole('textbox'), 'Checked.');
        expect(submit).toBeDisabled();
        expect(
            screen.getByRole('button', { name: 'Confirm code' }),
        ).toBeDisabled();

        await stepUp(user);
        expect(
            await screen.findByText(
                /Confirmed until .*It's used once, for this approval/,
            ),
        ).toBeInTheDocument();
        expect(inertia.calls).toEqual([
            {
                url: STEP_UP_ROUTE.url,
                method: 'post',
                body: {
                    request_id: expect.any(String),
                    expected_revision: 4,
                    intent_digest: DIGEST,
                    code: '123456',
                },
            },
        ]);
        expect(submit).toBeEnabled();

        await user.click(submit);
        await waitFor(() => expect(inertia.visits).toEqual([next]));

        expect(inertia.calls[1]).toEqual({
            url: disbursement.actions.approve?.url,
            method: 'post',
            body: {
                request_id: expect.any(String),
                expected_revision: 4,
                reason: 'Checked.',
                step_up_proof: 'opaque-proof-1',
            },
        });
        expect(inertia.calls).toHaveLength(2);
        expect(screen.queryByText(/Confirmed until/)).not.toBeInTheDocument();
    });

    it.each([
        [
            'a new revision',
            (detail: C3DisbursementDetail) => {
                detail.revision = 5;
            },
        ],
        [
            'a new amount',
            (detail: C3DisbursementDetail) => {
                detail.approval_binding = {
                    ...(detail.approval_binding as NonNullable<
                        C3DisbursementDetail['approval_binding']
                    >),
                    amount: { currency: 'RWF', amount: '24000000' },
                };
            },
        ],
        [
            'a new destination',
            (detail: C3DisbursementDetail) => {
                detail.approval_binding = {
                    ...(detail.approval_binding as NonNullable<
                        C3DisbursementDetail['approval_binding']
                    >),
                    destination: 'MTN MoMo ••• 4410',
                };
            },
        ],
    ])(
        'clears a held proof when a reload brings %s',
        async (_label, reshape) => {
            inertia.queue.push(proofOf('opaque-proof-1'));
            const { user, rerender } = renderWithUser(
                <AdminDisbursements {...approvable()} />,
            );

            await openApproval(user);
            await stepUp(user);
            await screen.findByText(/Confirmed until/);

            const fresh = approvable();

            reshape(opened(fresh));
            rerender(<AdminDisbursements {...fresh} />);

            expect(
                await screen.findByText(
                    'The details this approval binds changed, so your confirmation was cleared. Check them, then enter a new code.',
                ),
            ).toBeInTheDocument();
            expect(
                screen.queryByText(/Confirmed until/),
            ).not.toBeInTheDocument();
            await user.type(
                within(
                    screen.getByRole('form', { name: 'Approve this release' }),
                ).getByRole('textbox'),
                'Checked.',
            );
            expect(
                screen.getByRole('button', {
                    name: 'Approve and record intent',
                }),
            ).toBeDisabled();
            expect(inertia.calls).toHaveLength(1);
        },
    );

    it('clears a held proof when it expires against server_time, whatever the browser clock says', async () => {
        /* The browser clock is hours ahead of the server's; only server_time decides. */
        vi.useFakeTimers({ now: new Date('2026-09-25T20:00:00Z') });
        inertia.queue.push(proofOf('opaque-proof-1'));
        render(<AdminDisbursements {...approvable()} />);

        fireEvent.click(
            screen.getByRole('button', { name: 'Approve release' }),
        );
        fireEvent.change(
            screen.getByRole('textbox', {
                name: 'Six-digit authenticator code',
            }),
            { target: { value: '123456' } },
        );
        fireEvent.click(screen.getByRole('button', { name: 'Confirm code' }));
        await act(async () => {
            await Promise.resolve();
            await Promise.resolve();
        });

        expect(screen.getByText(/Confirmed until/)).toBeInTheDocument();
        act(() => vi.advanceTimersByTime(299_000));
        expect(screen.getByText(/Confirmed until/)).toBeInTheDocument();

        act(() => vi.advanceTimersByTime(1_000));
        expect(screen.queryByText(/Confirmed until/)).not.toBeInTheDocument();
        expect(screen.getByRole('alert')).toHaveTextContent(
            'That confirmation expired. Enter a new code from your authenticator.',
        );
        fireEvent.change(
            within(
                screen.getByRole('form', { name: 'Approve this release' }),
            ).getByRole('textbox'),
            { target: { value: 'Checked.' } },
        );
        expect(
            screen.getByRole('button', { name: 'Approve and record intent' }),
        ).toBeDisabled();
    });

    it('asks for a fresh step-up after STEP_UP_EXPIRED, then approves with a new request and a new proof', async () => {
        const fixture = approvable();

        inertia.queue.push(
            proofOf('opaque-proof-1'),
            fails(409, { code: 'STEP_UP_EXPIRED' }),
            proofOf('opaque-proof-2'),
            answers(
                completed('DISBURSEMENT_INTENT_RECORDED', {
                    receipt: {},
                    current: null,
                    next,
                }),
            ),
        );
        const { user } = renderWithUser(<AdminDisbursements {...fixture} />);

        await openApproval(user);
        await stepUp(user, '111111');
        await screen.findByText(/Confirmed until/);
        await approveWith(user);

        expect(
            await screen.findByText(
                'That confirmation expired. Enter a new code from your authenticator.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Approve and record intent' }),
        ).toBeDisabled();

        await stepUp(user, '222222');
        await screen.findByText(/Confirmed until/);
        await approveWith(user);
        await waitFor(() => expect(inertia.visits).toEqual([next]));

        const [firstStepUp, firstApprove, secondStepUp, secondApprove] =
            inertia.calls.map((call) => call.body as Record<string, unknown>);

        expect(inertia.calls.map(({ url }) => url)).toEqual([
            STEP_UP_ROUTE.url,
            opened(fixture).actions.approve?.url,
            STEP_UP_ROUTE.url,
            opened(fixture).actions.approve?.url,
        ]);
        expect(firstApprove.step_up_proof).toBe('opaque-proof-1');
        expect(secondApprove.step_up_proof).toBe('opaque-proof-2');
        expect(secondApprove.request_id).not.toBe(firstApprove.request_id);
        expect(secondStepUp.request_id).not.toBe(firstStepUp.request_id);
        expect(secondStepUp.code).toBe('222222');
    });

    it('looks a lost approve up by its own request, and the lookup never carries the proof', async () => {
        inertia.queue.push(
            proofOf('opaque-proof-1'),
            fails(503, { code: 'RETRYABLE_CONTENTION' }),
            fails(502),
            answers(
                completed('DISBURSEMENT_INTENT_RECORDED', {
                    receipt: {},
                    current: null,
                    next,
                }),
            ),
        );
        const { user } = renderWithUser(
            <AdminDisbursements {...approvable()} />,
        );

        await openApproval(user);
        await stepUp(user);
        await screen.findByText(/Confirmed until/);
        await approveWith(user);

        await screen.findByText('Not yet confirmed');
        expect(
            screen.getByRole('button', { name: 'Approve and record intent' }),
        ).toBeDisabled();
        await user.type(
            screen.getByRole('textbox', {
                name: 'Six-digit authenticator code',
            }),
            '654321',
        );
        expect(
            screen.getByRole('button', { name: 'Confirm code' }),
        ).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Check again' }));
        await waitFor(() => expect(inertia.visits).toEqual([next]));

        const sent = inertia.calls[1].body as { request_id: string };

        expect(inertia.calls.map(({ method }) => method)).toEqual([
            'post',
            'post',
            'get',
            'get',
        ]);

        for (const lookup of inertia.calls.slice(2)) {
            expect(lookup).toEqual({
                url: `/preview/admin-disbursements-operation-${sent.request_id}`,
                method: 'get',
                body: { command: 'disbursement.approve' },
            });
            expect(JSON.stringify(lookup)).not.toContain('opaque-proof-1');
        }

        expect(
            inertia.calls.filter((call) =>
                JSON.stringify(call.body).includes('opaque-proof-1'),
            ),
        ).toHaveLength(1);
    });

    it.each([
        [
            'a lost answer',
            offline(),
            "Rozine couldn't be reached to check your code.",
        ],
        [
            'an uncertain status',
            fails(503),
            "Rozine couldn't be reached to check your code.",
        ],
        [
            'STEP_UP_INVALID',
            fails(403, { code: 'STEP_UP_INVALID' }),
            "That confirmation didn't go through. Enter a new code from your authenticator.",
        ],
        [
            'a throttled attempt with no code',
            fails(429),
            'This request was refused. Refresh and try again.',
        ],
        [
            'a wrong code with the server’s words',
            invalid({ code: 'That code has already been used.' }),
            'That code has already been used.',
        ],
        [
            'a wrong code without them',
            invalid({}),
            "That code didn't match. Enter the current code from your authenticator.",
        ],
    ])(
        'asks for a fresh step-up after %s, never resending the code',
        async (_label, respond, text) => {
            inertia.queue.push(respond);
            const { user } = renderWithUser(
                <AdminDisbursements {...approvable()} />,
            );

            await openApproval(user);
            await stepUp(user, '123456');

            expect(await screen.findByRole('alert')).toHaveTextContent(text);
            expect(inertia.calls).toHaveLength(1);
            expect(
                screen.getByRole('textbox', {
                    name: 'Six-digit authenticator code',
                }),
            ).toHaveValue('');
            expect(
                screen.getByRole('button', {
                    name: 'Approve and record intent',
                }),
            ).toBeDisabled();

            inertia.queue.push(proofOf('opaque-proof-2'));
            await stepUp(user, '777777');
            await screen.findByText(/Confirmed until/);

            const [first, second] = inertia.calls.map(
                (call) => call.body as Record<string, unknown>,
            );

            expect(second.code).toBe('777777');
            expect(second.request_id).not.toBe(first.request_id);
        },
    );

    it('drops a proof that arrives after the binding changed, and shows the check in progress', async () => {
        let answer: (value: unknown) => void = () => undefined;

        inertia.queue.push(
            () =>
                new Promise((resolve) => {
                    answer = resolve;
                }),
        );
        const { user, rerender } = renderWithUser(
            <AdminDisbursements {...approvable()} />,
        );

        await openApproval(user);
        await stepUp(user);
        expect(
            screen.getByRole('button', { name: 'Confirm code' }),
        ).toHaveAttribute('aria-busy', 'true');

        const fresh = approvable();

        opened(fresh).revision = 5;
        rerender(<AdminDisbursements {...fresh} />);
        await act(async () => {
            answer({
                proof: 'late-proof',
                expires_at: '2026-09-25T10:05:00+02:00',
            });
            await Promise.resolve();
        });

        expect(
            await screen.findByText(
                'The details this approval binds changed, so your confirmation was cleared. Check them, then enter a new code.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByText(/Confirmed until/)).not.toBeInTheDocument();
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

            expect(
                within(drawer())
                    .getAllByRole('button')
                    .map((button) => button.textContent)
                    .filter((label) => label !== ''),
            ).toEqual(['Ask the provider again']);
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
            expect(inertia.reload[0]).toEqual({
                only: [
                    'disbursement',
                    'disbursements',
                    'awaiting_second_approver',
                    'allowed_actions',
                    'badges',
                    'server_time',
                ],
            });
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
        ['ready', readyFixture],
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
