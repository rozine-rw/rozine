import {
    render,
    renderHook,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { useAuditorCommands } from '@/components/auditor/commands';
import { BottomSheet } from '@/components/auditor/sheets/bottom-sheet';
import AuditorAudit from '@/pages/auditor/audit';
import AuditorJobs from '@/pages/auditor/jobs';
import type { AuditProcedureProps, AuditorJobsProps } from '@/types/auditor';
import review from '../../../resources/fixtures/ui/auditor-audit-review.json';
import unknown from '../../../resources/fixtures/ui/auditor-audit-seal-unknown.json';
import jobsFixture from '../../../resources/fixtures/ui/auditor-jobs.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, operation } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const jobs = () => structuredClone(jobsFixture.props) as AuditorJobsProps;

const accept = () =>
    screen.getByRole('button', { name: 'Accept & start 24h clock' });

beforeEach(() => inertia.reset());

describe('Auditor commands', () => {
    it('needs a page that provides them', () => {
        vi.spyOn(console, 'error').mockImplementation(() => undefined);

        expect(() => renderHook(() => useAuditorCommands())).toThrow(
            'Auditor commands need an AuditorCommandProvider.',
        );
    });

    it('draws a sheet on a page without commands', () => {
        render(
            <BottomSheet title="Sheet" onClose={() => undefined}>
                <p>Body</p>
            </BottomSheet>,
        );

        expect(screen.getByRole('dialog', { name: 'Sheet' })).toHaveTextContent(
            'Body',
        );
    });

    it('reloads when a completed command returns no record', async () => {
        inertia.queue.push(
            answers(operation({ code: 'ASSIGNMENT_ACCEPTED', data: null })),
        );
        const { user } = renderWithUser(<AuditorJobs {...jobs()} />);

        await user.click(accept());

        await waitFor(() => expect(inertia.reloads).toEqual([undefined]));
        expect(inertia.visits).toEqual([]);
    });

    it.each([
        [
            409,
            { code: 'VERSION_CONFLICT' },
            'This record changed since you opened it. The page has been refreshed — check it and try again.',
            true,
        ],
        [
            403,
            { code: 'ACTION_FORBIDDEN' },
            "You can't do this on this assignment any more. Your access changed.",
            false,
        ],
        [
            403,
            { code: 'CAPACITY_REACHED' },
            "Your access changed, so this wasn't done.",
            false,
        ],
        [
            409,
            { code: 'RADIUS_EXCEEDED' },
            "This couldn't be done. Refresh the page and try again.",
            true,
        ],
        [404, undefined, "This record isn't available to you.", false],
    ])(
        'explains a refused command (%i %j) and refreshes only stale facts',
        async (status, body, text, reloads) => {
            inertia.queue.push(fails(status, body));
            const { user } = renderWithUser(<AuditorJobs {...jobs()} />);

            await user.click(accept());

            expect(await screen.findByText(text)).toBeInTheDocument();
            expect(inertia.reloads).toHaveLength(reloads ? 1 : 0);
            expect(accept()).toBeEnabled();
        },
    );

    it('shows a refusal inside the open sheet, and on the page once it closes', async () => {
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        const { user } = renderWithUser(<AuditorJobs {...jobs()} />);

        await user.click(screen.getByRole('button', { name: 'Decline' }));

        const sheet = screen.getByRole('dialog', {
            name: 'Decline Huye Motors',
        });

        await user.click(
            within(sheet).getByRole('radio', {
                name: 'At capacity with active work',
            }),
        );
        await user.click(
            within(sheet).getByRole('button', { name: 'Decline job' }),
        );

        expect(
            await within(sheet).findByText(/This record changed/u),
        ).toBeInTheDocument();
        expect(screen.getAllByText(/This record changed/u)).toHaveLength(1);

        await user.click(within(sheet).getByRole('button', { name: 'Cancel' }));
        expect(screen.getAllByText(/This record changed/u)).toHaveLength(1);
    });

    it('settles a held command the lookup finds refused', async () => {
        inertia.queue.push(
            answers(
                operation({
                    status: 'rejected',
                    code: 'DIGEST_STALE',
                    data: null,
                }),
            ),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...(structuredClone(unknown.props) as AuditProcedureProps)}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Check again' }));

        expect(
            await screen.findByText(
                'The evidence changed after your preview. Review the new preview and confirm with a new code.',
            ),
        ).toBeInTheDocument();
        expect(inertia.reloads).toEqual([undefined]);
        expect(
            screen.getByRole('button', { name: 'Preview findings' }),
        ).toBeEnabled();
    });

    it('sends nothing the page itself does not allow', async () => {
        const { user } = renderWithUser(
            <AuditorAudit
                {...(structuredClone(review.props) as AuditProcedureProps)}
            />,
        );

        /* The Jobs backdrop allows accepting, but the procedure page does not. */
        await user.click(accept());

        expect(inertia.calls).toEqual([]);
    });
});
