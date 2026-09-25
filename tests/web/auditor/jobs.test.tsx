import { render, screen, waitFor, within } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import AuditorJobs from '@/pages/auditor/jobs';
import type {
    AuditorJobsProps,
    AuditorOutcome,
    MonthlyReportCard,
} from '@/types/auditor';
import conflictPendingFixture from '../../../resources/fixtures/ui/auditor-jobs-conflict-pending.json';
import conflictRecordedFixture from '../../../resources/fixtures/ui/auditor-jobs-conflict-recorded.json';
import conflictFixture from '../../../resources/fixtures/ui/auditor-jobs-conflict.json';
import emptyFixture from '../../../resources/fixtures/ui/auditor-jobs-empty.json';
import liveMinimalFixture from '../../../resources/fixtures/ui/auditor-jobs-live-minimal.json';
import overdueFixture from '../../../resources/fixtures/ui/auditor-jobs-overdue.json';
import pagedLastFixture from '../../../resources/fixtures/ui/auditor-jobs-paged-last.json';
import pagedFixture from '../../../resources/fixtures/ui/auditor-jobs-paged.json';
import scopedFixture from '../../../resources/fixtures/ui/auditor-jobs-scoped.json';
import jobsFixture from '../../../resources/fixtures/ui/auditor-jobs.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, inertia, invalid, operation } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown } = jobsFixture) =>
    structuredClone(fixture.props) as AuditorJobsProps;

const UUID = /^[0-9a-f-]{36}$/u;

beforeEach(() => inertia.reset());

describe('Auditor Jobs', () => {
    it('shows the dispatch radius and each eligible job with its engine figures', () => {
        render(<AuditorJobs {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Jobs');
        expect(
            screen.getByRole('heading', { name: 'Flash Audits' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                "Open field checks within 30km. First to accept locks the file. Each flash audit is due 24 hours after it's sent out.",
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('img', {
                name: 'Map of your 30 km dispatch radius with 1 open jobs at approximate positions',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('Approximate positions')).toBeInTheDocument();
        expect(screen.getByText('30km radius · 1 open')).toBeInTheDocument();

        const card = screen.getByRole('article', { name: 'Huye Motors' });

        expect(
            within(card).getByText('Logistics · Gasabo · 3h ago'),
        ).toBeInTheDocument();
        expect(within(card).getByText('11.2km')).toBeInTheDocument();
        expect(within(card).getByText('RWF 51.2M')).toBeInTheDocument();
        expect(within(card).getByText('1.02×')).toBeInTheDocument();
        expect(within(card).getByText('6mo')).toBeInTheDocument();
        expect(
            within(card).getByRole('link', { name: /View full application/ }),
        ).toHaveAttribute('href', '/preview/auditor-file');
        expect(screen.queryByText('Assigned to you')).not.toBeInTheDocument();
    });

    it('accepts a job as a JSON command and moves to the next page the server names', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'ASSIGNMENT_ACCEPTED',
                    data: {
                        next: {
                            url: '/preview/auditor-audit-review',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(<AuditorJobs {...props()} />);
        const accept = screen.getByRole('button', {
            name: 'Accept · due 4 Oct · 16:00',
        });

        await user.click(accept);

        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/auditor-audit-review',
            method: 'post',
            body: {
                assignment_id: 'fa_huye',
                expected_revision: 2,
                identity_context_revision: 3,
            },
        });
        expect(
            (inertia.calls[0].body as { request_id: string }).request_id,
        ).toMatch(UUID);
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-audit-review' },
            ]),
        );
    });

    it('holds a command whose answer is still coming, allowing only a conflict declaration meanwhile', async () => {
        const { user } = renderWithUser(<AuditorJobs {...props()} />);
        const accept = screen.getByRole('button', {
            name: 'Accept · due 4 Oct · 16:00',
        });

        await user.click(accept);

        expect(accept).toBeDisabled();
        expect(accept).toHaveAttribute('aria-busy', 'true');
        expect(screen.getByRole('button', { name: 'Decline' })).toBeDisabled();
        expect(
            screen.getByRole('button', { name: 'Declare a conflict' }),
        ).toBeEnabled();
    });

    it('offers only the commands the record allows', () => {
        const base = props();

        render(
            <AuditorJobs
                {...base}
                eligible={[
                    {
                        ...base.eligible[0],
                        allowed_actions: ['conflict.declare'],
                    },
                ]}
            />,
        );

        expect(
            screen.queryByRole('button', { name: /Accept/ }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Decline' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Declare a conflict' }),
        ).toBeInTheDocument();
    });

    it('offers no quiet actions at all when neither is allowed', () => {
        const base = props();

        render(
            <AuditorJobs
                {...base}
                eligible={[
                    {
                        ...base.eligible[0],
                        allowed_actions: ['assignment.accept'],
                    },
                ]}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Declare a conflict' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Decline' }),
        ).not.toBeInTheDocument();
    });

    it('gates each offer by its own actions, not the page’s', async () => {
        inertia.queue.push(answers(operation({ code: 'ASSIGNMENT_ACCEPTED' })));
        const { user } = renderWithUser(
            <AuditorJobs {...props(scopedFixture)} />,
        );
        const open = screen.getByRole('article', { name: 'Huye Motors' });
        const full = screen.getByRole('article', { name: 'Isoko Energy' });

        expect(
            within(full).queryByRole('button', { name: /Accept/ }),
        ).not.toBeInTheDocument();
        expect(
            within(full).queryByRole('button', { name: 'Decline' }),
        ).not.toBeInTheDocument();
        expect(
            within(full).getByRole('button', { name: 'Declare a conflict' }),
        ).toBeInTheDocument();

        await user.click(
            within(open).getByRole('button', {
                name: 'Accept · due 4 Oct · 16:00',
            }),
        );
        expect(inertia.calls[0].body).toMatchObject({
            assignment_id: 'fa_huye',
        });
    });

    it('shows an unrated case without a DSCR', () => {
        const base = props();

        render(
            <AuditorJobs
                {...base}
                eligible={[{ ...base.eligible[0], dscr: null }]}
            />,
        );

        expect(screen.getByText('—')).toBeInTheDocument();
    });

    it('declines with one of the server’s reasons, and an explanation where the reason asks', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'ASSIGNMENT_DECLINED',
                    data: {
                        next: {
                            url: '/preview/auditor-jobs-declined',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(<AuditorJobs {...props()} />);

        await user.click(screen.getByRole('button', { name: 'Decline' }));

        const sheet = screen.getByRole('dialog', {
            name: 'Decline Huye Motors',
        });
        const submit = within(sheet).getByRole('button', {
            name: 'Decline job',
        });

        expect(submit).toBeDisabled();
        expect(
            within(sheet).getByRole('radiogroup', { name: 'Reason' }),
        ).toBeInTheDocument();
        await user.click(
            within(sheet).getByRole('radio', { name: 'Other reason' }),
        );
        expect(
            within(sheet).getByLabelText('Factual explanation (required)'),
        ).toBeInTheDocument();
        expect(submit).toBeDisabled();

        await user.click(
            within(sheet).getByRole('radio', {
                name: 'Cannot reach the site',
            }),
        );
        expect(
            within(sheet).getByLabelText('Explanation (optional)'),
        ).toBeInTheDocument();
        expect(submit).toBeEnabled();

        await user.click(
            within(sheet).getByRole('radio', { name: 'Other reason' }),
        );
        await user.click(
            within(sheet).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('  Road to the site is closed for works.  ');
        await user.click(submit);

        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/auditor-jobs-declined',
            body: {
                assignment_id: 'fa_huye',
                expected_revision: 2,
                reason_code: 'other',
                reason: 'Road to the site is closed for works.',
                identity_context_revision: 3,
            },
        });

        const result = await screen.findByRole('alertdialog', {
            name: 'Job declined',
        });

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        await user.click(within(result).getByRole('button', { name: 'Done' }));
        expect(inertia.visits).toEqual([
            { url: '/preview/auditor-jobs-declined' },
        ]);
    });

    it('shows the server’s field errors in the sheet', async () => {
        inertia.queue.push(
            invalid({ reason_code: 'Choose a reason.', reason: 'Say why.' }),
        );
        const { user } = renderWithUser(<AuditorJobs {...props()} />);

        await user.click(screen.getByRole('button', { name: 'Decline' }));
        await user.click(
            screen.getByRole('radio', { name: 'At capacity with active work' }),
        );
        await user.click(screen.getByRole('button', { name: 'Decline job' }));

        expect(await screen.findByText('Choose a reason.')).toBeInTheDocument();
        expect(screen.getByText('Say why.')).toBeInTheDocument();
        expect(screen.getByLabelText('Explanation (optional)')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(
            screen.getByRole('button', { name: 'Decline job' }),
        ).toBeEnabled();
    });

    it('declares a conflict with its kind and a factual explanation', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'CONFLICT_RECORDED',
                    data: {
                        next: {
                            url: '/preview/auditor-jobs-conflict',
                            method: 'get',
                        },
                        conflict: {
                            conflict_id: 'cf_9',
                            kind: 'other',
                            declared_at: '2026-10-03T17:00:00Z',
                            note: 'x',
                            blocking: false,
                            status: 'recorded',
                        },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(<AuditorJobs {...props()} />);

        await user.click(
            screen.getByRole('button', { name: 'Declare a conflict' }),
        );

        const sheet = screen.getByRole('dialog', {
            name: 'Declare an interest in Huye Motors',
        });
        const submit = within(sheet).getByRole('button', {
            name: 'Declare interest',
        });

        expect(submit).toBeDisabled();
        await user.click(
            within(sheet).getByRole('radio', { name: 'Financial interest' }),
        );
        expect(
            within(sheet).getByRole('radio', { name: 'Financial interest' }),
        ).toBeChecked();
        expect(submit).toBeDisabled();
        await user.type(
            within(sheet).getByLabelText('Factual explanation (required)'),
            'Shareholder',
        );
        await user.click(submit);

        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/auditor-jobs-conflict',
            body: {
                assignment_id: 'fa_huye',
                expected_revision: 2,
                kind: 'financial_interest',
                reason: 'Shareholder',
            },
        });

        const result = await screen.findByRole('alertdialog', {
            name: 'Interest declared',
        });

        expect(result).toHaveAccessibleDescription(
            'Your declaration about Huye Motors is on the record. It does not stop your work on this assignment.',
        );
    });

    it('shows the server’s field errors on a declaration', async () => {
        inertia.queue.push(
            invalid({ kind: 'Choose one.', reason: 'Too long.' }),
        );
        const { user } = renderWithUser(<AuditorJobs {...props()} />);

        await user.click(
            screen.getByRole('button', { name: 'Declare a conflict' }),
        );
        await user.click(screen.getByRole('radio', { name: 'Other' }));
        await user.type(
            screen.getByLabelText('Factual explanation (required)'),
            'x',
        );
        await user.click(
            screen.getByRole('button', { name: 'Declare interest' }),
        );

        expect(await screen.findByText('Choose one.')).toBeInTheDocument();
        expect(screen.getByText('Too long.')).toBeInTheDocument();
        expect(
            screen.getByLabelText('Factual explanation (required)'),
        ).toHaveAttribute('aria-invalid', 'true');
    });

    it('closes the conflict sheet from its scrim, its ✕ and its cancel', async () => {
        const { user } = renderWithUser(<AuditorJobs {...props()} />);

        await user.click(
            screen.getByRole('button', { name: 'Declare a conflict' }),
        );
        await user.click(screen.getAllByRole('button', { name: 'Close' })[0]);
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Declare a conflict' }),
        );
        await user.click(screen.getAllByRole('button', { name: 'Close' })[1]);
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Declare a conflict' }),
        );
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('opens monthly windows and expands the waiting list', async () => {
        const { user } = renderWithUser(<AuditorJobs {...props()} />);

        expect(screen.getByText('4 waiting')).toBeInTheDocument();
        expect(
            screen.getAllByRole('button', { name: 'Open the audit' }),
        ).toHaveLength(3);

        await user.click(
            screen.getByRole('button', { name: 'Show all 4 open windows' }),
        );
        expect(
            screen.getAllByRole('button', { name: 'Open the audit' }),
        ).toHaveLength(4);
        await user.click(
            screen.getByRole('button', { name: 'Show fewer · 3 of 4' }),
        );
        expect(
            screen.getAllByRole('button', { name: 'Open the audit' }),
        ).toHaveLength(3);

        await user.click(
            screen.getAllByRole('button', { name: 'Open the audit' })[0],
        );
        expect(inertia.posts[0].url).toBe('/preview/auditor-audit-statements');
        expect(
            screen.getAllByText('September 2026 · audit file not opened yet'),
        ).toHaveLength(3);
    });

    it('labels every monthly report state and its statement figures', () => {
        const base = props();
        const report = base.monthly!.reports[0];
        const reports: MonthlyReportCard[] = [
            report,
            { ...report, id: 'b', status: 'overdue', cover: null },
            { ...report, id: 'c', status: 'changes_requested' },
            { ...report, id: 'd', status: 'awaiting_cosign' },
        ];

        render(<AuditorJobs {...base} monthly={{ windows: [], reports }} />);

        expect(screen.getByText('In progress')).toBeInTheDocument();
        expect(screen.getByText('Overdue')).toBeInTheDocument();
        expect(screen.getByText('Sent back')).toBeInTheDocument();
        expect(screen.getByText('Awaiting co-sign')).toBeInTheDocument();
        expect(screen.getAllByText('RWF 162M')).toHaveLength(4);
        expect(screen.getAllByText('1.3×')).toHaveLength(3);
        expect(
            screen.getAllByText('Motors Working Capital · Due 7 Oct'),
        ).toHaveLength(4);
        expect(screen.queryByText('Audits to open')).not.toBeInTheDocument();
    });

    it('explains an empty area and an empty month', () => {
        render(<AuditorJobs {...props(emptyFixture)} />);

        expect(
            screen.getByText(
                "No open jobs in your area. We'll ping you the moment a Tier 2 deal needs a field check within 30km.",
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'No monthly reports awaiting your audit right now.',
            ),
        ).toBeInTheDocument();
    });

    it('lists overdue and reassigned work first', () => {
        render(<AuditorJobs {...props(overdueFixture)} />);

        expect(screen.getByText('Assigned to you')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Sebeya Logistics/ }),
        ).toHaveAttribute('href', '/preview/auditor-audit-ledger');
        expect(screen.getByText('Reassigned to you')).toBeInTheDocument();
        expect(screen.queryByText(/Chantal Rwema/)).not.toBeInTheDocument();
    });
});

describe('Auditor outcomes', () => {
    const outcomes: [AuditorOutcome, string, string][] = [
        [
            { kind: 'job_declined', business: 'Huye Motors' },
            'Job declined',
            'Huye Motors is back with dispatch and your reason is on the record.',
        ],
        [
            {
                kind: 'conflict_declared',
                business: 'Huye Motors',
                resolution: 'reassigned',
                blocking: true,
            },
            'Interest declared',
            'Your conflict has been recorded and Huye Motors has been reassigned. Your work on this assignment has stopped.',
        ],
        [
            {
                kind: 'conflict_declared',
                business: 'Huye Motors',
                resolution: 'reassignment_pending',
                blocking: true,
            },
            'Interest declared',
            'Your conflict has been recorded. Work on this assignment is stopped while Audit Operations arranges reassignment.',
        ],
        [
            {
                kind: 'conflict_declared',
                business: 'Huye Motors',
                resolution: 'recorded',
                blocking: true,
            },
            'Interest declared',
            'Your conflict has been recorded. Work on this assignment is stopped.',
        ],
        [
            {
                kind: 'conflict_declared',
                business: 'Huye Motors',
                resolution: 'closed',
                blocking: true,
            },
            'Interest declared',
            'Your conflict has been recorded. Audit Operations has closed this assignment, and your work on it has stopped.',
        ],
        [
            {
                kind: 'report_sealed',
                business: 'Huye Motors',
                month: null,
                cosign_due_on: '2026-10-07',
            },
            'Audit sealed and filed',
            'The field report for Huye Motors is sealed. Huye Motors co-signs by 7 Oct 2026; the engine rates the business from your findings.',
        ],
        [
            {
                kind: 'report_sealed',
                business: 'GreenLeaf Agro',
                month: '2026-09-01',
                cosign_due_on: '2026-10-07',
            },
            'Audit sealed and filed',
            'The September 2026 report for GreenLeaf Agro — its uploaded statements and your factual findings — is sealed. GreenLeaf Agro co-signs by 7 Oct 2026.',
        ],
        [
            { kind: 'changes_requested', business: 'GreenLeaf Agro' },
            'Changes requested',
            'The business has your reason and explanation, and can resubmit the filing for your audit.',
        ],
        [
            { kind: 'report_rejected', business: 'GreenLeaf Agro' },
            'Filing rejected',
            "This filing version can't be verified and is closed with your reason on the record. It is not a credit judgement, and its evidence and report history are kept.",
        ],
    ];

    it.each(outcomes)(
        'says what the server did: %#',
        async (outcome, title, body) => {
            const { user } = renderWithUser(
                <AuditorJobs {...props()} outcome={outcome} />,
            );
            const dialog = screen.getByRole('alertdialog', { name: title });

            expect(dialog).toHaveAccessibleDescription(body);
            expect(dialog).not.toHaveTextContent(/Pass|Fail/u);
            await user.click(
                within(dialog).getByRole('button', { name: 'Done' }),
            );
            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            expect(inertia.visits).toEqual([]);
        },
    );

    it('renders each conflict resolution fixture without naming the next partner', () => {
        for (const fixture of [
            conflictFixture,
            conflictPendingFixture,
            conflictRecordedFixture,
        ]) {
            const { unmount } = render(<AuditorJobs {...props(fixture)} />);

            expect(
                screen.getByRole('alertdialog', { name: 'Interest declared' }),
            ).not.toHaveTextContent(/Chantal|CPA/u);
            unmount();
        }
    });
});

describe('Auditor Jobs on the live S-C projection', () => {
    const LIVE_FLASH = '01k6m3x2v8q4r7t9w1y5z0b3c6';
    const LIVE_MONTHLY = '01k6m2h7n4c8d2f5g9j3k6p1q4';

    it('shows a figure the draft lacks as a dash or unavailable, never 0', () => {
        render(<AuditorJobs {...props(liveMinimalFixture)} />);

        const card = screen.getByRole('article', {
            name: 'Kimisagara Hardware',
        });

        expect(
            within(card).getByText('Sector unavailable · Nyarugenge · 40m ago'),
        ).toBeInTheDocument();
        /* Distance, requested, DSCR and term: four dashes, and no zero anywhere. */
        expect(within(card).getAllByText('—')).toHaveLength(4);
        expect(
            within(card).queryByText(/^RWF 0|^0mo$|^0km$/u),
        ).not.toBeInTheDocument();
        expect(
            within(card).queryByText('Monthly visit'),
        ).not.toBeInTheDocument();
        expect(
            within(card).getByRole('link', { name: /View full application/ }),
        ).toHaveAttribute('href', `/auditor/jobs/${LIVE_FLASH}`);
    });

    it('marks a monthly offer, whose deadline the calendar owns, and accepts it on its own route', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'ASSIGNMENT_ACCEPTED',
                    data: { next: { url: '/auditor/jobs', method: 'get' } },
                }),
            ),
        );
        const { user } = renderWithUser(
            <AuditorJobs {...props(liveMinimalFixture)} />,
        );
        const card = screen.getByRole('article', {
            name: 'Nyamirambo Grocers',
        });

        expect(within(card).getByText('Monthly visit')).toBeInTheDocument();
        expect(
            within(card).getByText('Retail · Nyarugenge · 2h ago'),
        ).toBeInTheDocument();
        expect(within(card).getByText('RWF 18.5M')).toBeInTheDocument();
        expect(within(card).getByText('9mo')).toBeInTheDocument();

        await user.click(within(card).getByRole('button', { name: 'Accept' }));

        expect(inertia.calls[0]).toMatchObject({
            url: `/auditor/jobs/${LIVE_MONTHLY}/accept`,
            method: 'post',
            body: { assignment_id: LIVE_MONTHLY, expected_revision: 2 },
        });
    });

    it('pins only the offers with a position and never centres a missing one', () => {
        render(<AuditorJobs {...props(liveMinimalFixture)} />);

        const map = screen.getByRole('img', {
            name: 'Map of your 30 km dispatch radius with 2 jobs on this page at approximate positions',
        });
        const pins = within(map).getAllByTestId('radius-pin');

        expect(
            within(map).getByText('30km radius · 2 on this page'),
        ).toBeInTheDocument();
        expect(pins).toHaveLength(1);
        expect(pins[0]).toHaveStyle({
            left: 'calc(50% + -7.233333333333333px)',
        });
    });

    it('leaves the Monthly section out while the server sends none', () => {
        render(<AuditorJobs {...props(liveMinimalFixture)} />);

        expect(
            screen.queryByRole('heading', { name: 'Monthly reports' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText(
                'No monthly reports awaiting your audit right now.',
            ),
        ).not.toBeInTheDocument();
    });

    it('hides step progress until the procedure publishes its steps', () => {
        const base = props(liveMinimalFixture);
        const job = base.assigned[0];

        const { unmount } = render(<AuditorJobs {...base} />);
        const card = screen.getByRole('link', { name: /Gikondo Metal Works/ });

        expect(card).toHaveAttribute('href', `/auditor/jobs/${job.id}`);
        expect(within(card).getByText('Kicukiro · 6.4km')).toBeInTheDocument();
        expect(within(card).queryByText(/Step/u)).not.toBeInTheDocument();
        expect(
            within(card).queryByText(/Review|sign off/u),
        ).not.toBeInTheDocument();
        unmount();

        render(
            <AuditorJobs
                {...base}
                assigned={[{ ...job, step: 0, steps: 0 }]}
            />,
        );

        expect(
            screen.getByRole('link', { name: /Gikondo Metal Works/ }),
        ).not.toHaveTextContent('Step 0 of 0');
    });

    it('declines with the labels the server sends, relying on no code of its own', async () => {
        const base = props(liveMinimalFixture);
        const { user } = renderWithUser(
            <AuditorJobs
                {...base}
                decline_options={[
                    {
                        code: 'capacity',
                        label: 'Fully booked this week',
                        requires_explanation: false,
                    },
                ]}
            />,
        );
        const card = screen.getByRole('article', {
            name: 'Kimisagara Hardware',
        });

        await user.click(within(card).getByRole('button', { name: 'Decline' }));

        expect(
            screen.getByRole('radio', { name: 'Fully booked this week' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('radio', { name: 'Other reason' }),
        ).not.toBeInTheDocument();
    });
});

describe('Auditor Jobs, live details', () => {
    it('links to the partner’s own conflict receipts when the server sends that link', () => {
        render(<AuditorJobs {...props(liveMinimalFixture)} />);

        expect(
            screen.getByRole('link', { name: 'Your declared conflicts →' }),
        ).toHaveAttribute('href', '/auditor/conflicts');
    });

    it('gives the district alone when the distance cannot be stated', () => {
        const base = props(liveMinimalFixture);

        render(
            <AuditorJobs
                {...base}
                assigned={[
                    {
                        ...base.assigned[0],
                        distance_km: null,
                        step: 2,
                        steps: 5,
                    },
                ]}
            />,
        );

        expect(
            within(
                screen.getByRole('link', { name: /Gikondo Metal Works/u }),
            ).getByText('Kicukiro · Step 2 of 5'),
        ).toBeInTheDocument();
    });
});

describe('Auditor Jobs, paged', () => {
    const NEXT = '/auditor/jobs?before=01k6kz9w3e7r1t5y8u2i6o0p3a&limit=25';

    it('ends the page in Show more and counts only this page', () => {
        render(<AuditorJobs {...props(pagedFixture)} />);

        expect(screen.getByRole('link', { name: 'Show more' })).toHaveAttribute(
            'href',
            NEXT,
        );
        expect(
            screen.getByRole('img', {
                name: 'Map of your 30 km dispatch radius with 2 jobs on this page at approximate positions',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('30km radius · 2 on this page'),
        ).toBeInTheDocument();
        expect(screen.queryByText(/2 open/u)).not.toBeInTheDocument();
        /* A page count is never a total: the Jobs tab carries no badge. */
        expect(screen.queryByLabelText(/open offers/u)).not.toBeInTheDocument();
        expect(screen.getByText('Assigned to you')).toBeInTheDocument();
    });

    it('keeps offering Show more after an empty page, which is not the end of the history', () => {
        render(
            <AuditorJobs
                {...props(pagedFixture)}
                eligible={[]}
                assigned={[]}
            />,
        );

        expect(
            screen.getByText(
                'Nothing to show on this page. Earlier jobs may follow.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByText(/No open jobs/u)).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Show more' })).toHaveAttribute(
            'href',
            NEXT,
        );
    });

    it('shows the assigned work alone when a page holds no offers', () => {
        render(<AuditorJobs {...props(pagedFixture)} eligible={[]} />);

        expect(screen.getByText('Assigned to you')).toBeInTheDocument();
        expect(
            screen.queryByText(/Nothing to show on this page/u),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Show more' }),
        ).toBeInTheDocument();
    });

    it('offers no Show more on the last page, yet still counts only that page', () => {
        render(<AuditorJobs {...props(pagedLastFixture)} />);

        expect(
            screen.queryByRole('link', { name: 'Show more' }),
        ).not.toBeInTheDocument();
        /* The last page after `?before=` holds a subset: no badge, and the map says so. */
        expect(screen.queryByLabelText(/open offers/u)).not.toBeInTheDocument();
        expect(
            screen.getByText('30km radius · 2 on this page'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('img', {
                name: 'Map of your 30 km dispatch radius with 2 jobs on this page at approximate positions',
            }),
        ).toBeInTheDocument();
    });

    it('counts a synthetic preview without paging as the complete list, monthly offers included', () => {
        const base = props(liveMinimalFixture);

        delete base.pagination;
        render(<AuditorJobs {...base} />);

        expect(
            screen.getAllByLabelText('2 open offers').length,
        ).toBeGreaterThan(0);
        expect(screen.getByText('30km radius · 2 open')).toBeInTheDocument();
        expect(screen.getByText('Monthly visit')).toBeInTheDocument();
        expect(
            screen.queryByLabelText(/Flash Audits/u),
        ).not.toBeInTheDocument();
    });
});

describe('Auditor Jobs on a wide screen', () => {
    /** Whether the page sees the lg breakpoint: jsdom has no matchMedia of its own. */
    const wide = (matches: boolean) =>
        vi.stubGlobal('matchMedia', (query: string) => ({
            matches,
            media: query,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        }));

    afterEach(() => vi.unstubAllGlobals());

    const columns = () => screen.getAllByTestId(/^column-/u);

    const assignedHeading = () =>
        screen.getByRole('heading', { name: 'Assigned to you' });

    it('puts the assigned work in the right column while no monthly section is sent', () => {
        wide(true);
        render(<AuditorJobs {...props(liveMinimalFixture)} />);
        const [left, right] = columns();

        expect(columns()).toHaveLength(2);
        expect(right).toContainElement(assignedHeading());
        expect(right).toContainElement(
            screen.getByRole('link', { name: /Gikondo Metal Works/u }),
        );
        expect(left).toContainElement(
            screen.getByRole('article', { name: 'Kimisagara Hardware' }),
        );
        expect(screen.getAllByText('Assigned to you')).toHaveLength(1);
    });

    it('keeps Show more with the offers, and says so when a page holds no assigned work', () => {
        wide(true);
        render(
            <AuditorJobs
                {...props(pagedFixture)}
                eligible={[]}
                assigned={[]}
            />,
        );
        const [left, right] = columns();

        expect(left).toContainElement(
            screen.getByRole('link', { name: 'Show more' }),
        );
        expect(left).toContainElement(
            screen.getByText(
                'Nothing to show on this page. Earlier jobs may follow.',
            ),
        );
        expect(right).toContainElement(
            screen.getByText('Nothing assigned to you on this page.'),
        );
    });

    it('says when no accepted work is on the clock on the last page', () => {
        wide(true);
        render(<AuditorJobs {...props(liveMinimalFixture)} assigned={[]} />);

        expect(assignedHeading()).toBeInTheDocument();
        expect(
            screen.getByText('No accepted work is on the clock right now.'),
        ).toBeInTheDocument();
    });

    it('keeps the monthly section beside the offers when it is sent', () => {
        wide(true);
        render(<AuditorJobs {...props(overdueFixture)} />);
        const [left, right] = columns();

        expect(left).toContainElement(assignedHeading());
        expect(right).toContainElement(
            screen.getByRole('heading', { name: 'Monthly reports' }),
        );
    });

    it('keeps one flow on a phone, assigned above the offers', () => {
        wide(false);
        render(<AuditorJobs {...props(liveMinimalFixture)} />);

        expect(columns()).toHaveLength(1);
        expect(screen.getByTestId('column-left')).toHaveTextContent(
            /Assigned to you[\s\S]*Kimisagara Hardware/u,
        );
    });
});
