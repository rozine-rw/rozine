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
import {
    DEADLINE_GRACE_MS,
    RECOVERY_WINDOW_MS,
    jobsDeadlines,
    nextDeadline,
} from '@/components/auditor/refresh';
import AuditorFile from '@/pages/auditor/file';
import AuditorJobs from '@/pages/auditor/jobs';
import type { AuditorFileProps, AuditorJobsProps } from '@/types/auditor';
import blockedFixture from '../../../resources/fixtures/ui/auditor-file-conflict-blocked.json';
import liveFileFixture from '../../../resources/fixtures/ui/auditor-file-live-minimal.json';
import fileFixture from '../../../resources/fixtures/ui/auditor-file.json';
import emptyFixture from '../../../resources/fixtures/ui/auditor-jobs-empty.json';
import liveFixture from '../../../resources/fixtures/ui/auditor-jobs-live-minimal.json';
import overdueFixture from '../../../resources/fixtures/ui/auditor-jobs-overdue.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, operation } from './inertia';
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

const MINUTE = 60_000;
const HOUR = 60 * MINUTE;

/*
 * The live fixtures render at 17:00Z. The device clock here is a day and a half behind the server,
 * so every timer must run from `server_time`, never from the device clock alone.
 */
const DEVICE_NOW = Date.parse('2026-10-02T05:00:00Z');

const advance = (ms: number) =>
    act(() => {
        vi.advanceTimersByTime(ms);
    });

const signal = (event: 'focus' | 'online') =>
    act(() => {
        window.dispatchEvent(new Event(event));
    });

const hidden = vi.fn(() => false);

const becomeVisible = (visible: boolean) => {
    hidden.mockReturnValue(!visible);
    act(() => {
        document.dispatchEvent(new Event('visibilitychange'));
    });
};

/** Pretend the window is desktop-wide (Tailwind `lg`) or phone-narrow for `useWide`. */
const setWide = (wide: boolean) => {
    window.matchMedia = ((query: string) => ({
        matches: wide,
        media: query,
        addEventListener: () => {},
        removeEventListener: () => {},
    })) as unknown as typeof window.matchMedia;
};

beforeEach(() => {
    inertia.reset();
    /* A read delivers a page rendered after every deadline here, unless a test says otherwise. */
    inertia.reloadProps = { server_time: '2026-12-01T00:00:00Z' };
    hidden.mockReturnValue(false);
    vi.spyOn(document, 'hidden', 'get').mockImplementation(hidden);
    vi.useFakeTimers({ now: DEVICE_NOW });
});

afterEach(() => {
    vi.useRealTimers();
    Reflect.deleteProperty(window, 'matchMedia');
});

describe('The Jobs list reads again only at its next deadline', () => {
    it('does not reload while nothing is due, however long the page stays open', () => {
        render(<AuditorJobs {...props<AuditorJobsProps>(emptyFixture)} />);

        advance(48 * HOUR);

        expect(inertia.reloads).toHaveLength(0);
    });

    it('keeps its countdowns ticking from the server clock without reloading', () => {
        render(<AuditorJobs {...props<AuditorJobsProps>(liveFixture)} />);
        const card = screen.getByRole('article', {
            name: 'Kimisagara Hardware',
        });
        const timer = within(card).getByRole('timer', { name: /accept/u });

        expect(timer).toHaveTextContent('00:50:00 left');

        advance(10 * MINUTE);

        expect(timer).toHaveTextContent('00:40:00 left');
        expect(inertia.reloads).toHaveLength(0);
    });

    it('reloads once, just after the earliest offer closes, then waits for the next deadline', () => {
        const live = props<AuditorJobsProps>(liveFixture);
        const { rerender } = render(<AuditorJobs {...live} />);

        /* Kimisagara's offer closes at 17:50Z; the read waits out the grace as well. */
        advance(50 * MINUTE + DEADLINE_GRACE_MS - 1);

        expect(inertia.reloads).toHaveLength(0);

        advance(1);

        expect(inertia.reloads).toHaveLength(1);
        expect(inertia.reloads[0]).not.toHaveProperty('only');
        expect(inertia.reloads[0]).not.toHaveProperty('except');

        /* The server still lists the closed offer: that deadline is never read for again. */
        rerender(<AuditorJobs {...live} server_time="2026-10-03T17:49:59Z" />);
        advance(HOUR);

        expect(inertia.reloads).toHaveLength(1);

        /* Fresh props reschedule from their own clock: Nyamirambo's offer closes at 19:10Z. */
        rerender(
            <AuditorJobs
                {...live}
                server_time="2026-10-03T17:50:06Z"
                eligible={[live.eligible[1]]}
            />,
        );
        advance(HOUR + 19 * MINUTE);

        expect(inertia.reloads).toHaveLength(1);

        advance(MINUTE);

        expect(inertia.reloads).toHaveLength(2);
    });

    it('reads when work in progress turns overdue, but not for work already overdue', () => {
        const overdue = props<AuditorJobsProps>(overdueFixture);

        render(<AuditorJobs {...overdue} eligible={[]} />);

        /* Murakoze Tech is due at 13:59:55Z tomorrow; Sebeya Logistics is already overdue. */
        advance(20 * HOUR + 59 * MINUTE + 55_000);

        expect(inertia.reloads).toHaveLength(0);

        advance(DEADLINE_GRACE_MS);

        expect(inertia.reloads).toHaveLength(1);
    });

    it('checks a deadline beyond the longest browser timer again, never reading early', () => {
        const live = props<AuditorJobsProps>(liveFixture);
        const [job] = live.assigned;

        render(
            <AuditorJobs
                {...live}
                eligible={[]}
                assigned={[
                    { ...job, deadline: { due_at: '2026-11-03T17:00:00Z' } },
                ]}
            />,
        );

        advance(2 ** 31);

        expect(inertia.reloads).toHaveLength(0);

        advance(31 * 24 * HOUR - 2 ** 31 + DEADLINE_GRACE_MS);

        expect(inertia.reloads).toHaveLength(1);
    });

    it('pauses a deadline read while the tab is hidden and catches up once it is visible', () => {
        render(<AuditorJobs {...props<AuditorJobsProps>(liveFixture)} />);

        becomeVisible(false);
        advance(2 * HOUR);

        expect(inertia.reloads).toHaveLength(0);

        becomeVisible(true);
        signal('focus');

        expect(inertia.reloads).toHaveLength(1);

        /* The catch-up answered for the missed deadline: no second read for it. */
        advance(MINUTE);

        expect(inertia.reloads).toHaveLength(1);
    });

    describe('when the deadline passes during a read that started before it', () => {
        /* A recovery read starts at 17:50:00Z, and Kimisagara's offer closes while it is out. */
        const crossDuringRead = () => {
            inertia.holdReload = true;
            inertia.reloadProps = null;
            const view = render(
                <AuditorJobs {...props<AuditorJobsProps>(liveFixture)} />,
            );

            advance(50 * MINUTE);
            signal('online');
            advance(DEADLINE_GRACE_MS);

            expect(inertia.reloads).toHaveLength(1);

            return view;
        };

        const finishReads = () =>
            act(() => {
                const finishing = inertia.finishReload;

                inertia.finishReload = [];
                finishing.forEach((finish) => finish());
            });

        it('catches up exactly once when that read delivers no fresh page', () => {
            crossDuringRead();
            finishReads();

            expect(inertia.reloads).toHaveLength(2);

            /* The catch-up fails too: it queues nothing further. */
            finishReads();
            advance(HOUR);

            expect(inertia.reloads).toHaveLength(2);
        });

        it('catches up when the page it delivers was rendered before the deadline', () => {
            crossDuringRead();
            inertia.reloadProps = { server_time: '2026-10-03T17:49:59Z' };
            finishReads();

            expect(inertia.reloads).toHaveLength(2);
        });

        it('does not catch up when the page it delivers was rendered after the deadline', () => {
            crossDuringRead();
            inertia.reloadProps = { server_time: '2026-10-03T17:50:06Z' };
            finishReads();
            advance(HOUR);

            expect(inertia.reloads).toHaveLength(1);
        });

        it('does not catch up when the server sent the partner to another page', () => {
            crossDuringRead();
            inertia.reloadProps = { status: 403 };
            finishReads();

            expect(inertia.reloads).toHaveLength(1);
        });

        it('does not catch up once the page has gone', () => {
            const { unmount } = crossDuringRead();

            unmount();
            finishReads();

            expect(inertia.reloads).toHaveLength(1);
        });
    });

    it('keeps waiting for the next deadline after reads that deliver nothing', () => {
        inertia.reloadProps = null;
        render(<AuditorJobs {...props<AuditorJobsProps>(liveFixture)} />);

        /* 17:50:05Z: the deadline read and its one catch-up. */
        advance(50 * MINUTE + DEADLINE_GRACE_MS);

        expect(inertia.reloads).toHaveLength(2);

        /* No new props arrived, yet Nyamirambo's offer still closes at 19:10Z. */
        advance(HOUR + 20 * MINUTE - 1);

        expect(inertia.reloads).toHaveLength(2);

        advance(1);

        expect(inertia.reloads).toHaveLength(4);
    });

    it('holds a deadline read while a command is in flight, then reads once it settles', async () => {
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
        render(<AuditorJobs {...props<AuditorJobsProps>(liveFixture)} />);
        const card = screen.getByRole('article', {
            name: 'Kimisagara Hardware',
        });

        advance(49 * MINUTE);
        fireEvent.click(within(card).getByRole('button', { name: /^Accept/ }));
        advance(MINUTE + DEADLINE_GRACE_MS);

        expect(inertia.calls).toHaveLength(1);
        expect(inertia.reloads).toHaveLength(0);

        await act(async () => {
            answer.release();
            await vi.advanceTimersByTimeAsync(0);
        });

        expect(inertia.visits).toEqual([
            { url: '/auditor/jobs/01k6m3x2v8q4r7t9w1y5z0b3c6' },
        ]);
        expect(inertia.reloads).toHaveLength(1);
    });
});

describe('Recovery reads', () => {
    it('share one read when focus, visibility and reconnect arrive together', () => {
        render(<AuditorJobs {...props<AuditorJobsProps>(liveFixture)} />);

        becomeVisible(true);
        signal('focus');
        signal('online');

        expect(inertia.reloads).toHaveLength(1);

        advance(RECOVERY_WINDOW_MS - 1);
        signal('focus');

        expect(inertia.reloads).toHaveLength(1);

        advance(1);
        signal('focus');

        expect(inertia.reloads).toHaveLength(2);
    });

    it('holds a reconnect while the tab is hidden and reads once it is visible again', () => {
        render(<AuditorJobs {...props<AuditorJobsProps>(emptyFixture)} />);

        becomeVisible(false);
        signal('online');
        signal('focus');

        expect(inertia.reloads).toHaveLength(0);

        becomeVisible(true);
        signal('focus');

        expect(inertia.reloads).toHaveLength(1);
    });

    it('holds a reconnect while the tab is hidden and reads on the focus that shows it', () => {
        render(<AuditorJobs {...props<AuditorJobsProps>(emptyFixture)} />);

        becomeVisible(false);
        signal('online');

        expect(inertia.reloads).toHaveLength(0);

        hidden.mockReturnValue(false);
        signal('focus');
        signal('online');

        expect(inertia.reloads).toHaveLength(1);
    });
});

describe('The file page reads only what it shows', () => {
    it('leaves the Jobs list out of a phone’s recovery read', () => {
        render(<AuditorFile {...props<AuditorFileProps>(liveFileFixture)} />);

        signal('focus');

        expect(inertia.reloads).toEqual([
            expect.objectContaining({ except: ['jobs'] }),
        ]);
        expect(inertia.reloads[0]).not.toHaveProperty('only');
    });

    it('does not read for the background list’s deadlines', () => {
        render(<AuditorFile {...props<AuditorFileProps>(liveFileFixture)} />);

        /* The list beneath has offers closing at 17:50Z and 19:10Z; the file itself is assigned. */
        advance(24 * HOUR);

        expect(inertia.reloads).toHaveLength(0);
    });

    it('reads its own props once its offer closes', () => {
        render(<AuditorFile {...props<AuditorFileProps>(fileFixture)} />);

        advance(40 * MINUTE + DEADLINE_GRACE_MS);

        expect(inertia.reloads).toEqual([
            expect.objectContaining({ except: ['jobs'] }),
        ]);
    });

    it('has no deadline to read for once a conflict withholds the file', () => {
        const blocked = props<AuditorFileProps>(blockedFixture);

        render(
            <AuditorFile
                {...blocked}
                job={{
                    ...blocked.job,
                    state: 'offered',
                    accept_by: '2026-10-03T17:40:00Z',
                }}
            />,
        );

        advance(24 * HOUR);

        expect(inertia.reloads).toHaveLength(0);
    });

    it('keeps the Jobs list out of the reload after a refused command', async () => {
        vi.useRealTimers();
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        const { user } = renderWithUser(
            <AuditorFile {...props<AuditorFileProps>(fileFixture)} />,
        );

        const sheet = screen.getByRole('dialog', {
            name: 'Huye Motors business file',
        });

        await user.click(
            within(sheet).getByRole('button', { name: /^Accept/ }),
        );

        await waitFor(() =>
            expect(inertia.reloads).toEqual([{ except: ['jobs'] }]),
        );
    });

    it('reads the Jobs list too where a wide screen draws it beneath the sheet', () => {
        setWide(true);
        render(<AuditorFile {...props<AuditorFileProps>(liveFileFixture)} />);

        signal('online');

        expect(inertia.reloads).toHaveLength(1);
        expect(inertia.reloads[0]).not.toHaveProperty('except');
        expect(inertia.reloads[0]).not.toHaveProperty('only');
    });
});

describe('The deadline policy', () => {
    it('picks the earliest deadline after the server’s render and the last one read for', () => {
        const at = ['2026-10-03T18:00:00Z', '2026-10-03T17:30:00Z', 'soon'];

        expect(nextDeadline(at, '2026-10-03T17:00:00Z')).toBe(
            Date.parse('2026-10-03T17:30:00Z'),
        );
        expect(
            nextDeadline(
                at,
                '2026-10-03T17:00:00Z',
                Date.parse('2026-10-03T17:30:00Z'),
            ),
        ).toBe(Date.parse('2026-10-03T18:00:00Z'));
        expect(nextDeadline(at, '2026-10-03T18:00:00Z')).toBeNull();
        expect(nextDeadline([], '2026-10-03T17:00:00Z')).toBeNull();
    });

    it('takes every offer’s close and only work still in progress', () => {
        expect(jobsDeadlines(props<AuditorJobsProps>(overdueFixture))).toEqual([
            '2026-10-03T17:40:00Z',
            '2026-10-04T13:59:55Z',
        ]);
    });
});
