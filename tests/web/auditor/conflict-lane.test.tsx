import { screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorAudit from '@/pages/auditor/audit';
import type { AuditProcedureProps, ConflictReceipt } from '@/types/auditor';
import review from '../../../resources/fixtures/ui/auditor-audit-review.json';
import unknownConflict from '../../../resources/fixtures/ui/auditor-audit-seal-unknown-conflict.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, offline, operation } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AuditProcedureProps;

const sheet = () => screen.getByRole('dialog', { name: 'Huye Motors audit' });

const declaration = () =>
    screen.getByRole('dialog', { name: 'Declare an interest in Huye Motors' });

const recorded = (blocking: boolean) =>
    answers(
        operation({
            code: 'CONFLICT_RECORDED',
            data: {
                next: { url: '/preview/auditor-jobs', method: 'get' },
                conflict: {
                    conflict_id: 'cf_31',
                    kind: 'role_tie',
                    declared_at: '2026-10-03T17:00:00Z',
                    note: 'I advised them until March.',
                    blocking,
                    status: blocking ? 'reassignment_pending' : 'recorded',
                } satisfies ConflictReceipt,
            },
        }),
    );

type User = ReturnType<typeof renderWithUser>['user'];

/** Fills and sends the declaration sheet that is open. */
const declare = async (user: User) => {
    const form = declaration();

    await user.click(
        within(form).getByRole('radio', {
            name: 'Owner, director, employee or adviser tie',
        }),
    );
    await user.click(
        within(form).getByLabelText('Factual explanation (required)'),
    );
    await user.paste('I advised them until March.');
    await user.click(
        within(form).getByRole('button', { name: 'Declare interest' }),
    );
};

beforeEach(() => inertia.reset());

describe('Conflict declarations in their own lane', () => {
    it('can be declared while a step save is in flight, leaving the save held', async () => {
        const { user } = renderWithUser(<AuditorAudit {...props(review)} />);
        const next = within(sheet()).getByRole('button', { name: 'Continue' });

        /* The save's answer never comes back. */
        await user.click(next);
        expect(next).toBeDisabled();

        inertia.queue.push(recorded(false));
        await user.click(
            within(sheet()).getByRole('button', { name: 'Declare a conflict' }),
        );
        await declare(user);

        expect(inertia.calls).toHaveLength(2);
        expect(inertia.calls[1].body).toMatchObject({
            assignment_id: 'asg_fa_huye',
            kind: 'role_tie',
        });
        expect(inertia.calls[1].body).not.toMatchObject({
            request_id: (inertia.calls[0].body as { request_id: string })
                .request_id,
        });
        expect(
            await screen.findByRole('alertdialog', {
                name: 'Interest declared',
            }),
        ).toBeInTheDocument();
        expect(next).toBeDisabled();
        expect(next).toHaveAttribute('aria-busy', 'true');
    });

    it('opens over an uncertain seal, and a blocking one clears the page and follows its receipt at once', async () => {
        inertia.queue.push(recorded(true));
        const { user } = renderWithUser(
            <AuditorAudit {...props(unknownConflict)} />,
        );

        await declare(user);

        await waitFor(() =>
            expect(inertia.visits).toEqual([{ url: '/preview/auditor-jobs' }]),
        );
        expect(
            within(sheet()).getByRole('heading', { name: 'Conflict recorded' }),
        ).toBeInTheDocument();
        expect(
            within(sheet()).queryByText('Sign off & seal'),
        ).not.toBeInTheDocument();
        expect(
            within(sheet()).queryByRole('button', { name: 'Preview findings' }),
        ).not.toBeInTheDocument();

        /* The seal is still held; its late answer moves nothing. */
        expect(
            screen.getByText("We couldn't confirm your last action"),
        ).toBeInTheDocument();
        inertia.queue.push(
            answers(
                operation({
                    code: 'AUDIT_SEALED',
                    data: {
                        next: {
                            url: '/preview/auditor-audit-sealed',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        await user.click(screen.getByRole('button', { name: 'Check again' }));

        await waitFor(() => expect(inertia.calls).toHaveLength(2));
        expect(inertia.visits).toEqual([{ url: '/preview/auditor-jobs' }]);
    });

    it('lets neither a late refusal nor a late lookup miss reload the page after a blocking receipt', async () => {
        inertia.queue.push(recorded(true));
        const { user } = renderWithUser(
            <AuditorAudit {...props(unknownConflict)} />,
        );

        await declare(user);
        await waitFor(() =>
            expect(inertia.visits).toEqual([{ url: '/preview/auditor-jobs' }]),
        );

        /* The held seal's lookup now answers with a stale refusal. */
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        await user.click(screen.getByRole('button', { name: 'Check again' }));
        await waitFor(() => expect(inertia.calls).toHaveLength(2));

        expect(inertia.reloads).toHaveLength(0);
        expect(inertia.visits).toEqual([{ url: '/preview/auditor-jobs' }]);
    });

    it('does not refresh after a blocking receipt when the held command has no recorded result', async () => {
        inertia.queue.push(recorded(true));
        const { user } = renderWithUser(
            <AuditorAudit {...props(unknownConflict)} />,
        );

        await declare(user);
        await waitFor(() =>
            expect(inertia.visits).toEqual([{ url: '/preview/auditor-jobs' }]),
        );

        inertia.queue.push(fails(404, { code: 'OPERATION_NOT_FOUND' }));
        await user.click(screen.getByRole('button', { name: 'Check again' }));

        /* The original request stays held for an explicit retry; nothing reloads. */
        expect(
            await screen.findByRole('button', { name: 'Try again' }),
        ).toBeInTheDocument();
        expect(inertia.reloads).toHaveLength(0);
        expect(inertia.visits).toEqual([{ url: '/preview/auditor-jobs' }]);
    });

    it('keeps a stale declaration in the form for the partner to send again', async () => {
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        const view = renderWithUser(<AuditorAudit {...props(review)} />);

        await view.user.click(
            within(sheet()).getByRole('button', { name: 'Declare a conflict' }),
        );
        await declare(view.user);

        expect(
            await within(declaration()).findByText(/This record changed/u),
        ).toBeInTheDocument();
        expect(inertia.reloads).toEqual([undefined]);
        expect(
            within(declaration()).getByRole('radio', {
                name: 'Owner, director, employee or adviser tie',
            }),
        ).toBeChecked();
        expect(
            within(declaration()).getByLabelText(
                'Factual explanation (required)',
            ),
        ).toHaveValue('I advised them until March.');
        expect(inertia.calls).toHaveLength(1);

        /* The refreshed assignment arrives; the partner sends the same words again. */
        const base = props(review);

        view.rerender(
            <AuditorAudit
                {...base}
                assignment={{ ...base.assignment, revision: 5 }}
            />,
        );
        inertia.queue.push(recorded(false));
        await view.user.click(
            within(declaration()).getByRole('button', {
                name: 'Declare interest',
            }),
        );

        expect(inertia.calls[1].body).toMatchObject({
            expected_revision: 5,
            reason: 'I advised them until March.',
        });
        expect(
            (inertia.calls[1].body as { request_id: string }).request_id,
        ).not.toBe(
            (inertia.calls[0].body as { request_id: string }).request_id,
        );
    });

    it('holds back only a second declaration while its own result is unknown', async () => {
        inertia.queue.push(fails(503), offline());
        const { user } = renderWithUser(<AuditorAudit {...props(review)} />);

        await user.click(
            within(sheet()).getByRole('button', { name: 'Declare a conflict' }),
        );
        await declare(user);

        expect(
            await within(declaration()).findByText(
                "We couldn't confirm your last action",
            ),
        ).toBeInTheDocument();
        expect(
            within(declaration()).getByRole('button', {
                name: 'Declare interest',
            }),
        ).toBeDisabled();

        await user.click(
            within(declaration()).getByRole('button', { name: 'Cancel' }),
        );
        expect(
            within(sheet()).getByRole('button', { name: 'Declare a conflict' }),
        ).toBeDisabled();
        expect(
            within(sheet()).getByText("We couldn't confirm your last action"),
        ).toBeInTheDocument();
        expect(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        ).toBeEnabled();
    });
});
