import { http } from '@inertiajs/core';
import type { HttpRequestConfig, HttpResponse } from '@inertiajs/core';
import { act, renderHook } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import {
    LOOKUP_TIMEOUT_MS,
    useOperationCommand,
} from '@/hooks/use-operation-command';
import type { OperationCommand, OperationResource } from '@/types/operation';

/**
 * The shared operation command over the real `useHttp`, with only the transport swapped and the
 * clock faked: a lost answer's lookup that never responds is cancelled once its time limit passes,
 * and the page offers "Check again" for the same held command — never a resend.
 */
const REQUEST_ID = '5f0c9a8e-2d4b-4c1a-9e3f-7b6a5d4c3b2a';

const LOOKUP_URL = `/operations/${REQUEST_ID}?command=audit.save_step`;

const completed: OperationResource<null> = {
    operation_id: 'op-1',
    status: 'completed',
    code: 'AUDIT_STEP_SAVED',
    data: null,
    revision: 8,
    policy_version: null,
    recorded_at: '2026-10-03T17:00:02Z',
    server_time: '2026-10-03T17:00:03Z',
    allowed_actions: [],
    field_errors: {},
};

const command: OperationCommand = {
    name: 'audit.save_step',
    payload: { step: 'ledger', request_id: REQUEST_ID },
};

type Answer = (config: HttpRequestConfig) => Promise<HttpResponse>;

/** Every request the transport saw, and the answers queued for the next ones, in order. */
const sent: HttpRequestConfig[] = [];
const answers: Answer[] = [];

const respond = (status: number, body: unknown): Promise<HttpResponse> =>
    Promise.resolve({ status, data: JSON.stringify(body), headers: {} });

/** An answer that never arrives: the request only ends when it is cancelled. */
const silent: Answer = (config) =>
    new Promise((_, reject) => {
        config.signal?.addEventListener('abort', () =>
            reject(new DOMException('Aborted', 'AbortError')),
        );
    });

/** An answer that arrives after `delay`, whether or not the request was cancelled meanwhile. */
const late =
    (delay: number, body: unknown): Answer =>
    () =>
        new Promise((resolve) =>
            setTimeout(
                () =>
                    resolve({
                        status: 200,
                        data: JSON.stringify(body),
                        headers: {},
                    }),
                delay,
            ),
        );

const lostAnswer: Answer = () => respond(503, {});

const posts = () => sent.filter((config) => config.method === 'post');
const lookups = () => sent.filter((config) => config.method === 'get');

const mount = (lookupTimeout?: number) => {
    const onCompleted = vi.fn();
    const view = renderHook(() =>
        useOperationCommand<OperationCommand, typeof completed>({
            actions: { 'audit.save_step': { url: '/save', method: 'post' } },
            lookup: { url: '/operations/{request_id}', method: 'get' },
            lookupTimeout,
            refresh: () => Promise.resolve(),
            onCompleted,
            onRefused: vi.fn(),
        }),
    );

    return { ...view, onCompleted };
};

/** Moves the fake clock on, letting every answer and state update it releases settle. */
const elapse = (ms: number) =>
    act(async () => {
        await vi.advanceTimersByTimeAsync(ms);
    });

beforeEach(() => {
    vi.useFakeTimers();
    http.setClient({
        request: (config) => {
            sent.push(config);
            const answer = answers.shift();

            return answer ? answer(config) : silent(config);
        },
    });
});

afterEach(() => {
    vi.useRealTimers();
    sent.length = 0;
    answers.length = 0;
});

describe('Operation lookup time limit', () => {
    it('stops checking once an unanswered lookup reaches its time limit, cancelling it', async () => {
        answers.push(lostAnswer, silent);
        const { result } = mount();

        act(() => {
            result.current.send(command);
        });
        await elapse(0);

        expect(result.current.notice).toEqual({ kind: 'checking' });
        expect(lookups()).toHaveLength(1);
        expect(lookups()[0].url).toBe(LOOKUP_URL);

        await elapse(LOOKUP_TIMEOUT_MS - 1);

        expect(result.current.notice).toEqual({ kind: 'checking' });
        expect(result.current.busy).toBe(true);
        expect(lookups()[0].signal?.aborted).toBe(false);

        await elapse(1);

        expect(result.current.notice).toEqual({ kind: 'unconfirmed' });
        expect(result.current.unresolved).toBe(true);
        expect(result.current.busy).toBe(false);
        expect(lookups()[0].signal?.aborted).toBe(true);
        expect(posts()).toHaveLength(1);
    });

    it('asks the lookup again about the same request_id on "Check again", never resending the command', async () => {
        answers.push(lostAnswer, silent);
        const { result, onCompleted } = mount();

        act(() => {
            result.current.send(command);
        });
        await elapse(LOOKUP_TIMEOUT_MS);

        expect(result.current.notice).toEqual({ kind: 'unconfirmed' });

        /* A new command cannot start while the first is still unresolved. */
        act(() => {
            expect(
                result.current.send({
                    ...command,
                    payload: { ...command.payload, request_id: 'other' },
                }),
            ).toBe(false);
        });

        answers.push(silent);
        act(() => {
            result.current.checkAgain();
        });
        await elapse(0);

        expect(result.current.notice).toEqual({ kind: 'checking' });
        expect(lookups()).toHaveLength(2);
        expect(lookups()[1].url).toBe(LOOKUP_URL);

        await elapse(LOOKUP_TIMEOUT_MS);

        expect(result.current.notice).toEqual({ kind: 'unconfirmed' });

        answers.push(() => respond(200, completed));
        act(() => {
            result.current.checkAgain();
        });
        await elapse(0);

        expect(lookups()).toHaveLength(3);
        expect(lookups().map((config) => config.url)).toEqual([
            LOOKUP_URL,
            LOOKUP_URL,
            LOOKUP_URL,
        ]);
        expect(onCompleted).toHaveBeenCalledWith(command, completed, {
            recovered: true,
        });
        expect(result.current.notice).toBeNull();
        expect(posts()).toHaveLength(1);
        expect(posts()[0].data).toBe(JSON.stringify(command.payload));
    });

    it('ignores an answer that arrives after the time limit', async () => {
        answers.push(lostAnswer, late(LOOKUP_TIMEOUT_MS + 5_000, completed));
        const { result, onCompleted } = mount();

        act(() => {
            result.current.send(command);
        });
        await elapse(LOOKUP_TIMEOUT_MS);

        expect(result.current.notice).toEqual({ kind: 'unconfirmed' });

        await elapse(10_000);

        expect(onCompleted).not.toHaveBeenCalled();
        expect(result.current.notice).toEqual({ kind: 'unconfirmed' });
        expect(result.current.unresolved).toBe(true);
        expect(result.current.busy).toBe(false);
        expect(posts()).toHaveLength(1);
        expect(lookups()).toHaveLength(1);
    });

    it('leaves a lookup answered in time alone, and honours a page’s own limit', async () => {
        answers.push(lostAnswer, late(4_000, completed));
        const { result, onCompleted } = mount(5_000);

        act(() => {
            result.current.send(command);
        });
        await elapse(4_000);

        expect(onCompleted).toHaveBeenCalledTimes(1);
        expect(result.current.notice).toBeNull();

        await elapse(LOOKUP_TIMEOUT_MS);

        expect(result.current.notice).toBeNull();
        expect(lookups()[0].signal?.aborted).toBe(false);

        answers.push(lostAnswer, silent);
        act(() => {
            result.current.send({
                ...command,
                payload: { ...command.payload, request_id: 'second' },
            });
        });
        await elapse(5_000);

        expect(result.current.notice).toEqual({ kind: 'unconfirmed' });
        expect(lookups()[1].signal?.aborted).toBe(true);
        expect(posts()).toHaveLength(2);
    });
});
