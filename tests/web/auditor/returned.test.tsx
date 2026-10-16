import { render, screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorAudit from '@/pages/auditor/audit';
import type { RouteAction } from '@/types';
import type { AuditProcedureProps } from '@/types/auditor';
import flashLedger from '../../../resources/fixtures/ui/auditor-audit-ledger.json';
import returnedAmended from '../../../resources/fixtures/ui/auditor-audit-returned-amended.json';
import returnedChanges from '../../../resources/fixtures/ui/auditor-audit-returned-changes-requested.json';
import returnedRejected from '../../../resources/fixtures/ui/auditor-audit-returned-rejected.json';
import statementsReject from '../../../resources/fixtures/ui/auditor-audit-statements-reject.json';
import statementsRequestChanges from '../../../resources/fixtures/ui/auditor-audit-statements-request-changes.json';
import statements from '../../../resources/fixtures/ui/auditor-audit-statements.json';
import { renderWithUser } from '../helpers/render-with-user';
import {
    answers,
    fails,
    inertia,
    invalid,
    offline,
    operation,
} from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AuditProcedureProps;

const sheet = () =>
    screen.getByRole('dialog', { name: 'GreenLeaf Agro audit' });

const AMEND: RouteAction = {
    url: '/preview/auditor-audit-amendment',
    method: 'post',
};

const COMMON = {
    identity_context_revision: 3,
    request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u),
};

const AMENDED = operation({
    code: 'AUDIT_AMENDMENT_CREATED',
    data: {
        next: { url: '/preview/auditor-audit-amendment', method: 'get' },
    },
});

beforeEach(() => inertia.reset());

describe('Returned report', () => {
    it('shows the decision, its retained reason and when it was recorded, with only the amendment to start', () => {
        render(<AuditorAudit {...props(returnedChanges)} />);
        const dialog = sheet();

        expect(
            within(dialog).getByRole('heading', { name: 'Changes requested' }),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('Original documents missing'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText(
                'The September MoMo statement covers 1–18 September only; the rest of the month was not supplied.',
            ),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('5 Oct 2026 · 11:12'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByRole('button', {
                name: 'Start a linked amendment',
            }),
        ).toBeEnabled();

        /* A terminal version: nothing to save, seal, step up or return again. */
        for (const name of [
            'Save note',
            'Preview findings',
            'Request changes',
            'Reject filing',
            'Declare a conflict',
        ]) {
            expect(
                within(dialog).queryByRole('button', { name }),
            ).not.toBeInTheDocument();
        }

        expect(
            within(dialog).getByRole('link', { name: 'Back to jobs' }),
        ).toHaveAttribute('href', '/preview/auditor-jobs');
    });

    it('says a rejection concerns the filing, never credit, and offers no amendment it does not allow', () => {
        render(<AuditorAudit {...props(returnedRejected)} />);
        const dialog = sheet();

        expect(
            within(dialog).getByRole('heading', { name: 'Filing rejected' }),
        ).toBeInTheDocument();
        expect(dialog).toHaveTextContent(
            "This concerns the filing, not the business's credit.",
        );
        expect(
            within(dialog).getByText('Evidence cannot be verified'),
        ).toBeInTheDocument();
        expect(
            within(dialog).queryByRole('button', {
                name: 'Start a linked amendment',
            }),
        ).not.toBeInTheDocument();
        expect(
            within(dialog).queryByRole('link', { name: 'View the amendment' }),
        ).not.toBeInTheDocument();
    });

    it('links the amendment already created instead of offering another, even if one is allowed', () => {
        const page = props(returnedAmended);

        render(
            <AuditorAudit
                {...page}
                allowed_actions={['audit.amend']}
                actions={{ ...page.actions, amend: AMEND }}
            />,
        );

        expect(
            screen.getByText(/An amendment mr_greenleaf_a1 was started/u),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'View the amendment' }),
        ).toHaveAttribute('href', '/preview/auditor-audit-amendment');
        expect(
            screen.queryByRole('button', { name: 'Start a linked amendment' }),
        ).not.toBeInTheDocument();
    });

    it.each([
        ['allowed without a route', ['audit.amend'], null],
        ['a route that is not allowed', [], AMEND],
    ] as const)('offers no amendment for %s', (_, allowed, route) => {
        const page = props(returnedChanges);

        render(
            <AuditorAudit
                {...page}
                allowed_actions={[...allowed]}
                actions={{ ...page.actions, amend: route }}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Start a linked amendment' }),
        ).not.toBeInTheDocument();
    });

    it('offers no return from a report that is already returned', () => {
        const page = props(returnedChanges);

        render(
            <AuditorAudit
                {...page}
                allowed_actions={[
                    'audit.amend',
                    'audit.request_changes',
                    'audit.reject',
                ]}
                reason_options={props(statements).reason_options}
                actions={{
                    ...page.actions,
                    request_changes: AMEND,
                    reject: AMEND,
                }}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Request changes' }),
        ).not.toBeInTheDocument();
    });

    it('starts the amendment with the report revision and follows next to the child draft', async () => {
        inertia.queue.push(answers(AMENDED));
        const { user } = renderWithUser(
            <AuditorAudit {...props(returnedChanges)} />,
        );
        const amend = screen.getByRole('button', {
            name: 'Start a linked amendment',
        });

        await user.click(amend);

        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-audit-amendment',
            method: 'post',
            body: { audit_id: 'mr_greenleaf', expected_revision: 4, ...COMMON },
        });
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-audit-amendment' },
            ]),
        );
    });

    it('recovers an amendment whose answer was lost through the lookup, with the same request', async () => {
        inertia.queue.push(fails(503), answers(AMENDED));
        const { user } = renderWithUser(
            <AuditorAudit {...props(returnedChanges)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Start a linked amendment' }),
        );

        await waitFor(() => expect(inertia.calls).toHaveLength(2));
        const sent = inertia.calls[0].body as { request_id: string };

        expect(inertia.calls[1]).toEqual({
            url: `/preview/auditor-operation-${sent.request_id}`,
            method: 'get',
            body: { command: 'audit.amend' },
        });
        await waitFor(() => expect(inertia.visits).toHaveLength(1));
    });

    it('holds the amendment while its outcome is unknown', async () => {
        inertia.queue.push(fails(503), offline());
        const { user } = renderWithUser(
            <AuditorAudit {...props(returnedChanges)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Start a linked amendment' }),
        );

        expect(
            await screen.findByText("We couldn't confirm your last action"),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Start a linked amendment' }),
        ).toBeDisabled();
    });

    it('reads afresh when the amendment finds the report changed', async () => {
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        const { user } = renderWithUser(
            <AuditorAudit {...props(returnedChanges)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Start a linked amendment' }),
        );

        expect(
            await screen.findByText(
                'This record changed since you opened it. The page has been refreshed — check it and try again.',
            ),
        ).toBeInTheDocument();
        expect(inertia.reloads).toHaveLength(1);
        expect(inertia.visits).toHaveLength(0);
    });

    it('banners a field error the amendment names', async () => {
        inertia.queue.push(
            invalid({ audit_id: 'This report cannot be amended.' }),
        );
        const { user } = renderWithUser(
            <AuditorAudit {...props(returnedChanges)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Start a linked amendment' }),
        );

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'This report cannot be amended.',
        );
    });
});

describe('Returned report — step states and repeat decisions', () => {
    const bar = () => screen.getByRole('list', { name: 'Audit steps' });

    it('keeps the steps an early rejection never reached as not done, with no returned step', () => {
        render(<AuditorAudit {...props(returnedRejected)} />);
        const items = within(bar()).getAllByRole('listitem');

        expect(items.map((item) => item.textContent)).toEqual([
            'Statements',
            'Count & cash',
            'Photos',
            'Seal',
        ]);
        expect(items[0]).toHaveAttribute('aria-current', 'step');

        for (const item of items.slice(1)) {
            expect(item).not.toHaveAttribute('aria-current');
            expect(within(item).getByText(item.textContent ?? '')).toHaveClass(
                'text-rz-secondary',
            );
        }
    });

    it('keeps the steps a return at the count reached', () => {
        render(<AuditorAudit {...props(returnedAmended)} />);
        const items = within(bar()).getAllByRole('listitem');

        expect(items[0]).not.toHaveAttribute('aria-current');
        expect(within(items[0]).getByText('Statements')).toHaveClass(
            'text-rz-ink',
        );
        expect(items[1]).toHaveAttribute('aria-current', 'step');
        expect(within(items[2]).getByText('Photos')).toHaveClass(
            'text-rz-secondary',
        );
    });

    it('follows a repeated amendment to the child already created', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'AUDIT_AMENDMENT_CREATED',
                    data: {
                        next: {
                            url: '/preview/auditor-audit-amendment?child=mr_greenleaf_a1',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(
            <AuditorAudit {...props(returnedChanges)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Start a linked amendment' }),
        );

        await waitFor(() =>
            expect(inertia.visits).toEqual([
                {
                    url: '/preview/auditor-audit-amendment?child=mr_greenleaf_a1',
                },
            ]),
        );
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('reads afresh when the report is no longer amendable', async () => {
        inertia.queue.push(fails(409, { code: 'AUDIT_REPORT_NOT_AMENDABLE' }));
        const { user } = renderWithUser(
            <AuditorAudit {...props(returnedChanges)} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Start a linked amendment' }),
        );

        expect(
            await screen.findByText(
                "This report can't be amended now, so no amendment was started. The page has been refreshed.",
            ),
        ).toBeInTheDocument();
        expect(inertia.reloads).toHaveLength(1);
        expect(inertia.visits).toHaveLength(0);
    });
});

describe('Returning a filing before its seal step', () => {
    it('offers both returns beside the step, even while the step cannot continue', () => {
        render(<AuditorAudit {...props(statements)} can_continue={false} />);
        const dialog = sheet();

        expect(
            within(dialog).getByRole('button', { name: 'Start the count' }),
        ).toBeDisabled();
        expect(
            within(dialog).getByRole('button', { name: 'Request changes' }),
        ).toBeEnabled();
        expect(
            within(dialog).getByRole('button', { name: 'Reject filing' }),
        ).toBeEnabled();
    });

    it('requests changes with a coded reason and the report revision, then shows the result', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'AUDIT_CHANGES_REQUESTED',
                    data: {
                        next: { url: '/preview/auditor-jobs', method: 'get' },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(
            <AuditorAudit {...props(statements)} can_continue={false} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Request changes' }),
        );

        const reasons = screen.getByRole('dialog', { name: 'Request changes' });

        await user.click(
            within(reasons).getByRole('radio', {
                name: 'Original documents missing',
            }),
        );
        await user.click(
            within(reasons).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('The MoMo statement stops on 18 September.');
        await user.click(
            within(reasons).getByRole('button', { name: 'Request changes' }),
        );

        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-jobs',
            method: 'post',
            body: {
                audit_id: 'mr_greenleaf',
                expected_revision: 3,
                reason_code: 'missing_originals',
                reason: 'The MoMo statement stops on 18 September.',
                ...COMMON,
            },
        });

        const result = await screen.findByRole('alertdialog', {
            name: 'Changes requested',
        });

        await user.click(within(result).getByRole('button', { name: 'Done' }));
        expect(inertia.visits).toEqual([{ url: '/preview/auditor-jobs' }]);
    });

    it('rejects from a previewed sheet with its reason chosen', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'AUDIT_REJECTED',
                    data: {
                        next: { url: '/preview/auditor-jobs', method: 'get' },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(
            <AuditorAudit {...props(statementsReject)} />,
        );
        const reasons = screen.getByRole('dialog', { name: 'Reject filing' });

        expect(
            within(reasons).getByRole('radio', {
                name: 'Evidence cannot be verified',
            }),
        ).toBeChecked();
        await user.click(
            within(reasons).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('The statements do not match the ledger.');
        await user.click(
            within(reasons).getByRole('button', { name: 'Reject filing' }),
        );

        expect(inertia.calls[0].body).toMatchObject({
            reason_code: 'evidence_unverifiable',
            expected_revision: 3,
        });
        expect(
            await screen.findByRole('alertdialog', { name: 'Filing rejected' }),
        ).toBeInTheDocument();
    });

    it('opens a previewed request-changes sheet, and closes it again', async () => {
        const { user } = renderWithUser(
            <AuditorAudit {...props(statementsRequestChanges)} />,
        );

        expect(
            within(
                screen.getByRole('dialog', { name: 'Request changes' }),
            ).getByRole('radio', { name: 'Original documents missing' }),
        ).toBeChecked();
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(
            screen.queryByRole('dialog', { name: 'Request changes' }),
        ).not.toBeInTheDocument();
    });

    it('needs a factual explanation for every reason, and counts it against its limit', async () => {
        const { user } = renderWithUser(
            <AuditorAudit {...props(statementsRequestChanges)} />,
        );
        const reasons = screen.getByRole('dialog', { name: 'Request changes' });
        const field = within(reasons).getByLabelText(
            'Factual explanation (required)',
        );
        const submit = within(reasons).getByRole('button', {
            name: 'Request changes',
        });

        expect(within(reasons).getByText('0 / 2000')).toBeInTheDocument();
        expect(field).toHaveAttribute('maxlength', '2000');
        expect(submit).toBeDisabled();

        await user.click(field);
        await user.paste('   ');
        expect(submit).toBeDisabled();

        await user.paste('x'.repeat(2100));
        expect(within(reasons).getByText('2000 / 2000')).toBeInTheDocument();
        expect(submit).toBeEnabled();
    });

    it('shows a recorded invalid decision beside its fields', async () => {
        inertia.queue.push(
            invalid({
                reason_code: 'Choose one of the listed reasons.',
                reason: 'Say what is missing.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit {...props(statementsReject)} />,
        );
        const reasons = screen.getByRole('dialog', { name: 'Reject filing' });

        await user.click(
            within(reasons).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('Unclear.');
        await user.click(
            within(reasons).getByRole('button', { name: 'Reject filing' }),
        );

        expect(
            await within(reasons).findByText(
                'Choose one of the listed reasons.',
            ),
        ).toBeInTheDocument();
        expect(
            within(reasons).getByText('Say what is missing.'),
        ).toBeInTheDocument();
        expect(
            within(reasons).getByLabelText('Factual explanation (required)'),
        ).toHaveAttribute('aria-invalid', 'true');
        expect(inertia.reloads).toHaveLength(0);
    });

    it('reads afresh when the report can no longer be returned', async () => {
        inertia.queue.push(
            fails(409, { code: 'AUDIT_REPORT_DECISION_NOT_ALLOWED' }),
        );
        const { user } = renderWithUser(
            <AuditorAudit {...props(statementsReject)} />,
        );
        const reasons = screen.getByRole('dialog', { name: 'Reject filing' });

        await user.click(
            within(reasons).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('The statements do not match the ledger.');
        await user.click(
            within(reasons).getByRole('button', { name: 'Reject filing' }),
        );

        expect(
            await within(reasons).findByText(
                'This report can no longer be returned or rejected, so nothing was recorded. The page has been refreshed.',
            ),
        ).toBeInTheDocument();
        expect(inertia.reloads).toHaveLength(1);
    });

    it('explains a refused return inside its sheet and reads afresh', async () => {
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        const { user } = renderWithUser(
            <AuditorAudit {...props(statementsReject)} />,
        );
        const reasons = screen.getByRole('dialog', { name: 'Reject filing' });

        await user.click(
            within(reasons).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('The statements do not match the ledger.');
        await user.click(
            within(reasons).getByRole('button', { name: 'Reject filing' }),
        );

        expect(
            await within(reasons).findByText(
                'This record changed since you opened it. The page has been refreshed — check it and try again.',
            ),
        ).toBeInTheDocument();
        expect(inertia.reloads).toHaveLength(1);
    });

    it.each([
        [
            'without reason options',
            { ...props(statements), reason_options: null },
        ],
        ['on a Flash Audit', props(flashLedger)],
        [
            'when the server does not allow them',
            {
                ...props(statements),
                allowed_actions: ['audit.save_step'],
            },
        ],
        [
            'without their routes',
            (() => {
                const page = props(statementsRequestChanges);

                return {
                    ...page,
                    actions: {
                        ...page.actions,
                        request_changes: null,
                        reject: null,
                    },
                };
            })(),
        ],
    ] as const)('offers no return %s', (_, page) => {
        render(<AuditorAudit {...(page as AuditProcedureProps)} />);

        expect(
            screen.queryByRole('button', { name: 'Request changes' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Reject filing' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('dialog', { name: 'Request changes' }),
        ).not.toBeInTheDocument();
    });
});
