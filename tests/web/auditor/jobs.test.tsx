import { act, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorJobs from '@/pages/auditor/jobs';
import type {
    AuditorJobsProps,
    AuditorOutcome,
    MonthlyReportCard,
} from '@/types/auditor';
import emptyFixture from '../../../resources/fixtures/ui/auditor-jobs-empty.json';
import overdueFixture from '../../../resources/fixtures/ui/auditor-jobs-overdue.json';
import jobsFixture from '../../../resources/fixtures/ui/auditor-jobs.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

const props = (fixture: { props: unknown } = jobsFixture) =>
    structuredClone(fixture.props) as AuditorJobsProps;

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
                name: 'Map of your 30 km dispatch radius with 1 open jobs',
            }),
        ).toBeInTheDocument();
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

    it('accepts a job through the server and shows it is in flight', async () => {
        inertia.hold = true;
        const { user } = renderWithUser(<AuditorJobs {...props()} />);
        const accept = screen.getByRole('button', {
            name: 'Accept & start 24h clock',
        });

        await user.click(accept);

        expect(inertia.posts[0].url).toBe('/preview/auditor-audit-review');
        expect(accept).toBeDisabled();

        act(() => inertia.finish.forEach((finish) => finish()));
        expect(accept).toBeEnabled();
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

    it('declines with a reason the server records', async () => {
        const { user } = renderWithUser(<AuditorJobs {...props()} />);

        await user.click(screen.getByRole('button', { name: 'Decline' }));

        const sheet = screen.getByRole('dialog', {
            name: 'Decline Huye Motors',
        });
        const submit = within(sheet).getByRole('button', {
            name: 'Decline job',
        });

        expect(submit).toBeDisabled();
        await user.type(
            within(sheet).getByLabelText('Reason'),
            'Too far today',
        );
        await user.click(submit);

        expect(inertia.posts[0]).toMatchObject({
            url: '/preview/auditor-jobs-declined',
            data: { reason: 'Too far today' },
        });

        await user.click(within(sheet).getByRole('button', { name: 'Cancel' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('shows a reason error from the server', async () => {
        inertia.errors = { reason: 'Give a reason.' };
        const { user } = renderWithUser(<AuditorJobs {...props()} />);

        await user.click(screen.getByRole('button', { name: 'Decline' }));

        expect(screen.getByText('Give a reason.')).toBeInTheDocument();
        expect(screen.getByLabelText('Reason')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
    });

    it('declares a conflict only after the kind is chosen', async () => {
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
        await user.type(
            within(sheet).getByLabelText('Details (optional)'),
            'Shareholder',
        );
        await user.click(submit);

        expect(inertia.posts[0]).toMatchObject({
            url: '/preview/auditor-jobs-conflict',
            data: {
                file_id: 'fa_huye',
                kind: 'financial_interest',
                note: 'Shareholder',
            },
        });

        await user.click(within(sheet).getByRole('button', { name: 'Close' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('closes the conflict sheet from its scrim and its cancel', async () => {
        inertia.errors = { kind: 'Choose one.', note: 'Too long.' };
        const { user } = renderWithUser(<AuditorJobs {...props()} />);

        await user.click(
            screen.getByRole('button', { name: 'Declare a conflict' }),
        );
        expect(screen.getByText('Choose one.')).toBeInTheDocument();
        expect(screen.getByText('Too long.')).toBeInTheDocument();
        await user.click(screen.getAllByRole('button', { name: 'Close' })[0]);
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
                reassigned_to: 'Chantal Rwema, CPA',
            },
            'Interest declared',
            'Huye Motors now sits with Chantal Rwema, CPA. You are off the file and the declaration is on the record.',
        ],
        [
            {
                kind: 'conflict_declared',
                business: 'Huye Motors',
                resolution: 'queued',
                reassigned_to: null,
            },
            'Interest declared',
            'Huye Motors is off your list and waiting for operations to reassign it. You cannot verify it in the meantime.',
        ],
        [
            {
                kind: 'conflict_declared',
                business: 'Huye Motors',
                resolution: 'reassigned',
                reassigned_to: null,
            },
            'Interest declared',
            'Your interest in Huye Motors is on the record. You cannot verify this file.',
        ],
        [
            {
                kind: 'report_sealed',
                business: 'Huye Motors',
                month: null,
                cosign_due_on: '2026-10-04',
            },
            'Audit sealed and filed',
            'The field report for Huye Motors is sealed. Huye Motors co-signs by 4 Oct 2026; the engine rates the business from your findings.',
        ],
        [
            {
                kind: 'report_sealed',
                business: 'GreenLeaf Agro',
                month: '2026-09-01',
                cosign_due_on: '2026-10-07',
            },
            'Audit sealed and filed',
            'September 2026 evidence vault for GreenLeaf Agro is sealed. GreenLeaf Agro has until 7 Oct 2026 to sign off.',
        ],
        [
            { kind: 'changes_requested', business: 'GreenLeaf Agro' },
            'Sent back to founder',
            'The founder will revise and resubmit for your audit.',
        ],
        [
            { kind: 'report_rejected', business: 'GreenLeaf Agro' },
            'Flagged to Rozine',
            'This report has been rejected and escalated to Rozine Admin.',
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
            await user.click(
                within(dialog).getByRole('button', { name: 'Done' }),
            );
            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        },
    );
});
