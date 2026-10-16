import { render, screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorFile from '@/pages/auditor/file';
import type { AuditorFileProps, BusinessFile } from '@/types/auditor';
import blockedFixture from '../../../resources/fixtures/ui/auditor-file-conflict-blocked.json';
import declineOtherFixture from '../../../resources/fixtures/ui/auditor-file-decline-other.json';
import liveMinimalFixture from '../../../resources/fixtures/ui/auditor-file-live-minimal.json';
import noApplicationFixture from '../../../resources/fixtures/ui/auditor-file-no-application.json';
import reassignedFixture from '../../../resources/fixtures/ui/auditor-file-reassigned.json';
import startFixture from '../../../resources/fixtures/ui/auditor-file-start.json';
import fileFixture from '../../../resources/fixtures/ui/auditor-file.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, offline, operation } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown } = fileFixture) =>
    structuredClone(fixture.props) as AuditorFileProps;

const sheetFor = (business = 'Huye Motors') =>
    screen.getByRole('dialog', { name: `${business} business file` });

beforeEach(() => inertia.reset());

describe('Auditor business file', () => {
    it('introduces a submitted, screened file as everything submitted and screened', () => {
        render(<AuditorFile {...props()} />);

        expect(
            within(sheetFor()).getByText(
                "Everything Huye Motors submitted, screened against Rozine's thresholds. Your field check resolves what the engine can't confirm remotely.",
            ),
        ).toBeInTheDocument();
    });

    it('previews an offered file read-only, over Jobs, its flash clock already running from dispatch', () => {
        render(<AuditorFile {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Huye Motors · file',
        );

        const sheet = sheetFor();

        expect(
            within(sheet).getByText('Application preview'),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByRole('timer', { name: 'Time left on this job' }),
        ).toHaveTextContent(/2[01]:[0-5][0-9]:[0-5][0-9]/u);
        expect(within(sheet).getByText('Gasabo · 11.2km')).toBeInTheDocument();
        expect(within(sheet).getByText('RWF 51.2M')).toBeInTheDocument();
        expect(within(sheet).getByText('6 months')).toBeInTheDocument();
        expect(within(sheet).getByText('10.0% total')).toBeInTheDocument();
        expect(
            within(sheet).queryByText(/rate \/ yr/i),
        ).not.toBeInTheDocument();
        expect(within(sheet).getByText(/· Logistics/)).toBeInTheDocument();
        expect(within(sheet).getAllByText('✓ OCR')).toHaveLength(2);
        expect(within(sheet).getAllByText('✓ Verified')).toHaveLength(2);
        expect(within(sheet).getAllByText('Met')).toHaveLength(3);
        expect(within(sheet).getByText('Flag')).toBeInTheDocument();
        expect(within(sheet).getByText('First visit')).toBeInTheDocument();
        expect(
            within(sheet).getByText('No flags on file.'),
        ).toBeInTheDocument();
        expect(within(sheet).getAllByRole('listitem').length).toBeGreaterThan(
            10,
        );
        expect(
            screen.getAllByRole('link', { name: 'Close' })[0],
        ).toHaveAttribute('href', '/preview/auditor-jobs');
        expect(
            screen.getAllByRole('navigation', { name: 'App navigation' }),
        ).toHaveLength(1);
    });

    it('accepts the offered file as a command and offers decline and conflict', async () => {
        const { user } = renderWithUser(<AuditorFile {...props()} />);
        const sheet = sheetFor();
        const accept = within(sheet).getByRole('button', {
            name: 'Accept · due 4 Oct · 16:00',
        });

        await user.click(accept);
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/auditor-audit-review',
            body: {
                assignment_id: 'fa_huye',
                expected_revision: 2,
                identity_context_revision: 3,
            },
        });
        expect(accept).toBeDisabled();
        expect(
            within(sheet).getByRole('button', { name: 'Decline' }),
        ).toBeDisabled();
    });

    it('hides accept when the server does not allow it', () => {
        render(
            <AuditorFile {...props()} allowed_actions={['conflict.declare']} />,
        );

        expect(
            within(sheetFor()).queryByRole('button', { name: /Accept/ }),
        ).not.toBeInTheDocument();
    });

    it('opens the decline sheet with “Other” chosen, which needs an explanation', async () => {
        const { user } = renderWithUser(
            <AuditorFile {...props(declineOtherFixture)} />,
        );
        const sheet = screen.getByRole('dialog', {
            name: 'Decline Huye Motors',
        });

        expect(
            within(sheet).getByRole('radio', { name: 'Other reason' }),
        ).toBeChecked();
        expect(
            within(sheet).getByRole('button', { name: 'Decline job' }),
        ).toBeDisabled();

        await user.click(
            within(sheet).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('Family event on the day of the visit.');
        expect(
            within(sheet).getByRole('button', { name: 'Decline job' }),
        ).toBeEnabled();
    });

    it('withdraws the file the moment a blocking conflict is recorded', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'CONFLICT_RECORDED',
                    data: {
                        next: { url: '/preview/auditor-jobs', method: 'get' },
                        conflict: {
                            conflict_id: 'cf_2026_0217',
                            kind: 'role_tie',
                            declared_at: '2026-10-03T16:48:00Z',
                            note: 'Advised them until March.',
                            blocking: true,
                            status: 'reassignment_pending',
                        },
                        outcome: { resolution: 'reassignment_pending' },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(<AuditorFile {...props()} />);

        await user.click(
            within(sheetFor()).getByRole('button', {
                name: 'Declare a conflict',
            }),
        );
        await user.click(
            screen.getByRole('radio', {
                name: 'Owner, director, employee or adviser tie',
            }),
        );
        await user.click(
            screen.getByLabelText('Factual explanation (required)'),
        );
        await user.paste('Advised them until March.');
        await user.click(
            screen.getByRole('button', { name: 'Declare interest' }),
        );

        /* The receipt's destination is followed at once; no result card waits in between. */
        await waitFor(() =>
            expect(inertia.visits).toEqual([{ url: '/preview/auditor-jobs' }]),
        );
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();

        const sheet = sheetFor();

        expect(
            within(sheet).getByRole('heading', { name: 'Conflict recorded' }),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText('Application preview'),
        ).toBeInTheDocument();
        expect(within(sheet).queryByText('RWF 51.2M')).not.toBeInTheDocument();
        expect(within(sheet).queryByRole('timer')).not.toBeInTheDocument();
        expect(
            within(sheet).queryByRole('button', { name: /Accept/ }),
        ).not.toBeInTheDocument();
        expect(
            within(sheet).getByRole('link', { name: 'Back to jobs' }),
        ).toHaveAttribute('href', '/preview/auditor-jobs');
    });

    it('shows only the receipt for a file a blocking conflict has withdrawn', () => {
        render(<AuditorFile {...props(blockedFixture)} />);

        const sheet = sheetFor();

        expect(within(sheet).getByText('Reassigned')).toBeInTheDocument();
        expect(
            within(sheet).getByText(
                'Your conflict has been recorded and the assignment has been reassigned. You no longer have access to its file.',
            ),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText(
                'I advised Huye Motors on its 2025 bookkeeping set-up until March 2026.',
            ),
        ).toBeInTheDocument();
        /* The receipt is the declarant's own kind, note, date and status: no reference or name. */
        expect(
            within(sheet).queryByText('cf_2026_0217'),
        ).not.toBeInTheDocument();
        expect(within(sheet).queryByText('Reference')).not.toBeInTheDocument();
        expect(
            within(sheet).queryByText('First visit'),
        ).not.toBeInTheDocument();
        expect(
            within(sheet).queryByRole('button', { name: 'Declare a conflict' }),
        ).not.toBeInTheDocument();
    });

    it('shows a reassigned file with its history and continues the procedure', async () => {
        const { user } = renderWithUser(
            <AuditorFile {...props(reassignedFixture)} />,
        );
        const sheet = sheetFor('Sebeya Logistics');

        expect(screen.getByText('Business file')).toBeInTheDocument();
        /* The note says the file was reassigned, and never names the partner it came from. */
        expect(screen.getByRole('note')).toHaveTextContent('Reassigned to you');
        expect(screen.getByRole('note')).not.toHaveTextContent('Chantal');
        expect(
            screen.getByText(
                'Last audit 14 Apr 2026 · Flash Audit · Chantal Rwema, CPA',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText('August 2026 statements uploaded after the 3rd'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Continue the audit' }),
        ).toHaveAttribute('href', '/preview/auditor-audit-ledger');
        expect(
            within(sheet).queryByRole('button', { name: 'Decline' }),
        ).not.toBeInTheDocument();

        await user.click(
            within(sheet).getByRole('button', { name: 'Declare a conflict' }),
        );
        expect(
            screen.getByRole('dialog', {
                name: 'Declare an interest in Sebeya Logistics',
            }),
        ).toBeInTheDocument();
    });

    it('reads a monthly last audit, a missing document and no procedure link', () => {
        const base = props(reassignedFixture);
        const file = base.file as BusinessFile;

        render(
            <AuditorFile
                {...base}
                links={{ ...base.links, procedure: null }}
                file={{
                    ...file,
                    documents: [{ ...file.documents[0], status: 'missing' }],
                    history: {
                        ...file.history,
                        last_audit: {
                            ...file.history.last_audit!,
                            kind: 'monthly',
                        },
                    },
                }}
                blocked={null}
            />,
        );

        expect(screen.getByText('Missing')).toBeInTheDocument();
        expect(
            screen.getByText(/Monthly report · Chantal Rwema, CPA/),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Continue the audit' }),
        ).not.toBeInTheDocument();
    });
});

describe('Auditor business file on the live S-C projection', () => {
    const live = () => props(liveMinimalFixture);
    const liveSheet = () => sheetFor('Gikondo Metal Works');

    it('introduces the draft fallback as the application as it stands, with no pre-screen on record', () => {
        render(<AuditorFile {...live()} />);

        const sheet = liveSheet();

        expect(
            within(sheet).getByText(
                "Gikondo Metal Works's application as it currently stands. No automated pre-screen is on record. Your field check confirms what can't be verified remotely.",
            ),
        ).toBeInTheDocument();
        expect(
            within(sheet).queryByText(/^Everything .* submitted/u),
        ).not.toBeInTheDocument();
    });

    it('does not claim a pre-screen is missing when one is on record for a draft', () => {
        const page = live();

        page.file = {
            ...(page.file as BusinessFile),
            prescreen: (props().file as BusinessFile).prescreen,
        };
        render(<AuditorFile {...page} />);

        expect(
            within(liveSheet()).getByText(
                "Gikondo Metal Works's application as it currently stands. Your field check confirms what can't be verified remotely.",
            ),
        ).toBeInTheDocument();
    });

    it('does not claim everything was screened for a submitted application without a pre-screen', () => {
        const page = props();

        page.file = { ...(page.file as BusinessFile), prescreen: [] };
        render(<AuditorFile {...page} />);

        expect(
            within(sheetFor()).getByText(
                "Huye Motors's application as it currently stands. No automated pre-screen is on record. Your field check confirms what can't be verified remotely.",
            ),
        ).toBeInTheDocument();
    });

    it('reads a figure the draft lacks as a dash and invents nothing in an empty list', () => {
        render(<AuditorFile {...live()} />);

        const sheet = liveSheet();

        expect(within(sheet).getByText('Business file')).toBeInTheDocument();
        /* Requested, term and total return. */
        expect(within(sheet).getAllByText('—')).toHaveLength(3);
        expect(
            within(sheet).getByText(/· Sector unavailable$/u),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText(
                'No submitted documents are on record for this file yet.',
            ),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText(
                'No automated pre-screen result has been published for this file yet.',
            ),
        ).toBeInTheDocument();
        expect(within(sheet).getByText('First visit')).toBeInTheDocument();
        expect(
            within(sheet).getByText('No flags on file.'),
        ).toBeInTheDocument();
    });

    it('leaves out the reason and the mandate until they are published', () => {
        render(<AuditorFile {...live()} />);

        const sheet = liveSheet();

        expect(
            within(sheet).queryByText('Why a field audit is required'),
        ).not.toBeInTheDocument();
        expect(
            within(sheet).queryByText('What you must clear on site'),
        ).not.toBeInTheDocument();
    });

    it('offers no dead button while the procedure is not served, only the conflict declaration', () => {
        render(<AuditorFile {...live()} />);

        const sheet = liveSheet();

        expect(
            within(sheet).queryByRole('link', { name: 'Continue the audit' }),
        ).not.toBeInTheDocument();
        expect(
            within(sheet).queryByRole('button', { name: /Accept/u }),
        ).not.toBeInTheDocument();
        expect(
            within(sheet).queryByRole('button', { name: 'Decline' }),
        ).not.toBeInTheDocument();
        expect(
            within(sheet).getByRole('button', { name: 'Declare a conflict' }),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByRole('link', { name: 'Back' }),
        ).toHaveAttribute('href', '/auditor/jobs');
    });

    it('shows the mandate alone under its own heading when no reason is persisted', () => {
        const base = live();

        render(
            <AuditorFile
                {...base}
                file={{
                    ...(base.file as BusinessFile),
                    mandate: ['Count the steel stock on site.'],
                }}
                blocked={null}
            />,
        );

        const sheet = liveSheet();

        expect(
            within(sheet).getByRole('heading', {
                name: 'What you must clear on site',
            }),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText('Count the steel stock on site.'),
        ).toBeInTheDocument();
        expect(
            within(sheet).queryByText('Why a field audit is required'),
        ).not.toBeInTheDocument();
    });

    it('shows the reason alone when no mandate is published', () => {
        const base = live();

        render(
            <AuditorFile
                {...base}
                file={{
                    ...(base.file as BusinessFile),
                    reason: 'Stock levels need a field check.',
                }}
                blocked={null}
            />,
        );

        const sheet = liveSheet();

        expect(
            within(sheet).getByRole('heading', {
                name: 'Why a field audit is required',
            }),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText('Stock levels need a field check.'),
        ).toBeInTheDocument();
        expect(
            within(sheet).queryByText('What you must clear on site'),
        ).not.toBeInTheDocument();
    });

    it('gives the district alone and leaves out an empty use of funds', () => {
        const base = live();
        const file = base.file as BusinessFile;

        render(
            <AuditorFile
                {...base}
                job={{ ...base.job, distance_km: null }}
                file={{ ...file, raise: { ...file.raise, use_of_funds: '' } }}
                blocked={null}
            />,
        );

        const sheet = liveSheet();

        expect(within(sheet).getByText('Kicukiro')).toBeInTheDocument();
        expect(
            within(sheet).queryByText(/Working capital/u),
        ).not.toBeInTheDocument();
    });
});

describe('Auditor business file — starting the report', () => {
    const STARTED = operation({
        code: 'AUDIT_STARTED',
        data: { next: { url: '/preview/auditor-audit-review', method: 'get' } },
    });

    it('starts the report with the job revision and the application pins, then follows next', async () => {
        inertia.queue.push(answers(STARTED));
        const { user } = renderWithUser(
            <AuditorFile {...props(startFixture)} />,
        );
        const start = within(sheetFor()).getByRole('button', {
            name: 'Start the audit',
        });

        expect(
            within(sheetFor()).queryByRole('link', {
                name: 'Continue the audit',
            }),
        ).not.toBeInTheDocument();
        await user.click(start);

        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-audit-review',
            method: 'post',
            body: {
                assignment_id: 'fa_huye',
                expected_revision: 2,
                application_id: 'app_huye_motors',
                application_revision: 4,
                identity_context_revision: 3,
                request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u),
            },
        });
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-audit-review' },
            ]),
        );
    });

    it('says it is starting while the start is in flight', async () => {
        const { user } = renderWithUser(
            <AuditorFile {...props(startFixture)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Start the audit' }),
        );

        const starting = screen.getByRole('button', { name: 'Starting…' });

        expect(starting).toBeDisabled();
        expect(starting).toHaveAttribute('aria-busy', 'true');
    });

    it('recovers a start whose answer was lost through the report lookup, not the assignment one', async () => {
        inertia.queue.push(fails(503), answers(STARTED));
        const { user } = renderWithUser(
            <AuditorFile {...props(startFixture)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Start the audit' }),
        );

        await waitFor(() => expect(inertia.calls).toHaveLength(2));
        const sent = inertia.calls[0].body as { request_id: string };

        expect(inertia.calls[1]).toEqual({
            url: `/preview/auditor-report-operation-${sent.request_id}`,
            method: 'get',
            body: { command: 'audit.start' },
        });
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-audit-review' },
            ]),
        );
    });

    it('keeps the assignment lookup for an assignment command on the same page', async () => {
        inertia.queue.push(fails(503), offline());
        const { user } = renderWithUser(<AuditorFile {...props()} />);

        await user.click(
            within(sheetFor()).getByRole('button', { name: /^Accept/u }),
        );

        await waitFor(() => expect(inertia.calls).toHaveLength(2));
        expect(inertia.calls[1].url).toMatch(
            /^\/preview\/auditor-operation-[0-9a-f-]{36}$/u,
        );
    });

    it('continues an existing report instead of starting another', () => {
        const page = props(startFixture);

        render(
            <AuditorFile
                {...page}
                actions={{ ...page.actions, start: null }}
                links={{
                    ...page.links,
                    procedure: {
                        url: '/preview/auditor-audit-review',
                        method: 'get',
                    },
                }}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'Continue the audit' }),
        ).toHaveAttribute('href', '/preview/auditor-audit-review');
        expect(
            screen.queryByRole('button', { name: 'Start the audit' }),
        ).not.toBeInTheDocument();
    });

    it('says there is no application to audit, with nothing to start', () => {
        render(<AuditorFile {...props(noApplicationFixture)} />);

        expect(within(sheetFor()).getByRole('note')).toHaveTextContent(
            'No submitted application is available to audit yet.',
        );
        expect(
            screen.queryByRole('button', { name: 'Start the audit' }),
        ).not.toBeInTheDocument();
    });

    it('offers no start the server does not allow, and no unavailable line either', () => {
        render(
            <AuditorFile
                {...props(startFixture)}
                allowed_actions={['conflict.declare']}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Start the audit' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText(
                'No submitted application is available to audit yet.',
            ),
        ).not.toBeInTheDocument();
    });

    it.each([
        [
            'VERSION_CONFLICT',
            409,
            'This record changed since you opened it. The page has been refreshed — check it and try again.',
            true,
        ],
        [
            'APPLICATION_VERSION_CONFLICT',
            409,
            "The business's application changed since you opened this file. The page has been refreshed — check it and start again.",
            true,
        ],
        [
            'APPLICATION_NOT_SUBMITTED',
            409,
            "This application hasn't been submitted, so there is nothing to audit yet. The page has been refreshed.",
            true,
        ],
        [
            'APPLICATION_NOT_FOUND',
            404,
            'This application is no longer available to you, so nothing was started.',
            false,
        ],
        [
            'AUDIT_APPLICATION_BOUND',
            409,
            'A report is already bound to this application, so no new one was started. The page has been refreshed — continue from there.',
            true,
        ],
        [
            'AUDIT_REPORT_REASSIGNMENT_REQUIRED',
            409,
            'This report has to be reassigned before work on it can continue, so nothing was started. The page has been refreshed.',
            true,
        ],
        [
            'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED',
            403,
            'Accept the current engagement terms to continue.',
            false,
        ],
    ] as const)(
        'explains a %s refusal of the start',
        async (code, status, text, fresh) => {
            inertia.queue.push(fails(status, { code }));
            const { user } = renderWithUser(
                <AuditorFile {...props(startFixture)} />,
            );

            await user.click(
                screen.getByRole('button', { name: 'Start the audit' }),
            );

            expect(await screen.findByText(text)).toBeInTheDocument();
            expect(inertia.reloads).toHaveLength(fresh ? 1 : 0);
            expect(inertia.visits).toHaveLength(0);
        },
    );
});
