import { render, screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorFile from '@/pages/auditor/file';
import type { AuditorFileProps, BusinessFile } from '@/types/auditor';
import blockedFixture from '../../../resources/fixtures/ui/auditor-file-conflict-blocked.json';
import declineOtherFixture from '../../../resources/fixtures/ui/auditor-file-decline-other.json';
import liveMinimalFixture from '../../../resources/fixtures/ui/auditor-file-live-minimal.json';
import reassignedFixture from '../../../resources/fixtures/ui/auditor-file-reassigned.json';
import fileFixture from '../../../resources/fixtures/ui/auditor-file.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, inertia, operation } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown } = fileFixture) =>
    structuredClone(fixture.props) as AuditorFileProps;

const sheetFor = (business = 'Huye Motors') =>
    screen.getByRole('dialog', { name: `${business} business file` });

beforeEach(() => inertia.reset());

describe('Auditor business file', () => {
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
});
