import { render, screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
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
import overdueFixture from '../../../resources/fixtures/ui/auditor-jobs-overdue.json';
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
                'Open field checks within 30km. First to accept locks the file and starts a 24-hour clock.',
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
            name: 'Accept & start 24h clock',
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
            name: 'Accept & start 24h clock',
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
                name: 'Accept & start 24h clock',
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
                note: 'Shareholder',
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
        inertia.queue.push(invalid({ kind: 'Choose one.', note: 'Too long.' }));
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
        const report = base.monthly.reports[0];
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
        expect(
            screen.getByText('Reassigned from Chantal Rwema, CPA'),
        ).toBeInTheDocument();
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
