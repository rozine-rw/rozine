import { act, render, screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorConflicts from '@/pages/auditor/conflicts';
import AuditorFile from '@/pages/auditor/file';
import AuditorJobs from '@/pages/auditor/jobs';
import type {
    AuditorConflictsProps,
    AuditorFileProps,
    AuditorJobsProps,
} from '@/types/auditor';
import conflictsFixture from '../../../resources/fixtures/ui/auditor-conflicts.json';
import fileFixture from '../../../resources/fixtures/ui/auditor-file-live-minimal.json';
import jobsFixture from '../../../resources/fixtures/ui/auditor-jobs-live-minimal.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, inertia, operation } from './inertia';
import type { Responder } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const props = <T,>(fixture: { props: unknown }) =>
    structuredClone(fixture.props) as T;

/** A command answer the test releases when it chooses, so the command stays in flight until then. */
function held(result: unknown): { responder: Responder; release: () => void } {
    let release = () => {};
    const gate = new Promise<void>((resolve) => {
        release = resolve;
    });

    return {
        responder: async (options) => {
            await gate;

            return answers(result)(options);
        },
        release: () => release(),
    };
}

const signal = (event: 'focus' | 'online') =>
    act(() => {
        window.dispatchEvent(new Event(event));
    });

beforeEach(() => {
    inertia.reset();
    vi.spyOn(document, 'hidden', 'get').mockReturnValue(false);
});

describe('Auditor pages reconcile their access', () => {
    it('reloads the full Jobs facts when the window regains focus', () => {
        render(<AuditorJobs {...props<AuditorJobsProps>(jobsFixture)} />);

        signal('focus');

        expect(inertia.reloads).toHaveLength(1);
        expect(inertia.reloads[0]).not.toHaveProperty('only');
    });

    it('reloads the file summary when the browser comes back online', () => {
        render(<AuditorFile {...props<AuditorFileProps>(fileFixture)} />);

        signal('online');

        expect(inertia.reloads).toHaveLength(1);
        expect(inertia.reloads[0]).not.toHaveProperty('only');
    });

    it('reloads the conflict receipts when the tab becomes visible again', () => {
        render(
            <AuditorConflicts
                {...props<AuditorConflictsProps>(conflictsFixture)}
            />,
        );

        act(() => {
            document.dispatchEvent(new Event('visibilitychange'));
        });

        expect(inertia.reloads).toHaveLength(1);
    });

    it('waits for an accept in flight, then reloads once it settles', async () => {
        const answer = held(
            operation({
                code: 'ASSIGNMENT_ACCEPTED',
                data: {
                    next: {
                        url: '/auditor/jobs/01k6m3x2v8q4r7t9w1y5z0b3c6',
                        method: 'get',
                    },
                },
            }),
        );
        inertia.queue.push(answer.responder);
        const { user } = renderWithUser(
            <AuditorJobs {...props<AuditorJobsProps>(jobsFixture)} />,
        );
        const card = screen.getByRole('article', {
            name: 'Kimisagara Hardware',
        });

        await user.click(within(card).getByRole('button', { name: /^Accept/ }));
        signal('focus');
        signal('online');

        expect(inertia.reloads).toHaveLength(0);

        answer.release();

        await waitFor(() => expect(inertia.reloads).toHaveLength(1));
        expect(inertia.visits).toEqual([
            { url: '/auditor/jobs/01k6m3x2v8q4r7t9w1y5z0b3c6' },
        ]);
        expect(inertia.calls).toHaveLength(1);
    });

    it('waits for a conflict declaration in flight, then reloads once it settles', async () => {
        const answer = held(
            operation({
                code: 'CONFLICT_RECORDED',
                data: {
                    next: { url: '/auditor/conflicts', method: 'get' },
                    conflict: {
                        conflict_id: 'cf_live',
                        kind: 'other',
                        declared_at: '2026-10-03T17:00:00Z',
                        note: 'A tie.',
                        blocking: false,
                        status: 'recorded',
                    },
                },
            }),
        );
        inertia.queue.push(answer.responder);
        const { user } = renderWithUser(
            <AuditorJobs {...props<AuditorJobsProps>(jobsFixture)} />,
        );
        const card = screen.getByRole('article', {
            name: 'Kimisagara Hardware',
        });

        await user.click(
            within(card).getByRole('button', { name: 'Declare a conflict' }),
        );

        const sheet = screen.getByRole('dialog', {
            name: 'Declare an interest in Kimisagara Hardware',
        });

        await user.click(within(sheet).getByRole('radio', { name: 'Other' }));
        await user.type(
            within(sheet).getByLabelText('Factual explanation (required)'),
            'A tie.',
        );
        await user.click(
            within(sheet).getByRole('button', { name: 'Declare interest' }),
        );
        signal('focus');

        expect(inertia.reloads).toHaveLength(0);

        answer.release();

        await waitFor(() => expect(inertia.reloads).toHaveLength(1));
        expect(inertia.calls).toHaveLength(1);
    });
});
