import { http } from '@inertiajs/core';
import type { HttpRequestConfig } from '@inertiajs/core';
import { act, renderHook, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { useOperationCommand } from '@/hooks/use-operation-command';
import type { OperationCommand, OperationResource } from '@/types/operation';

/**
 * The real `useHttp` under the shared operation command, with only the transport swapped: a
 * command whose body holds a File leaves as multipart form data, fields and file together.
 */
const sent: HttpRequestConfig[] = [];

const resource: OperationResource<{ next: { url: string; method: 'get' } }> = {
    operation_id: 'op-1',
    status: 'completed',
    code: 'AUDIT_STEP_SAVED',
    data: { next: { url: '/next', method: 'get' } },
    revision: 8,
    policy_version: null,
    recorded_at: '2026-10-03T17:00:02Z',
    server_time: '2026-10-03T17:00:03Z',
    allowed_actions: [],
    field_errors: {},
};

afterEach(() => {
    sent.length = 0;
});

describe('Operation commands over the real transport', () => {
    it('sends a command carrying a file as multipart form data', async () => {
        http.setClient({
            request: (config) => {
                sent.push(config);

                return Promise.resolve({
                    status: 200,
                    data: JSON.stringify(resource),
                    headers: {},
                });
            },
        });
        const onCompleted = vi.fn();
        const { result } = renderHook(() =>
            useOperationCommand<OperationCommand, typeof resource>({
                actions: {
                    'audit.save_step': { url: '/save', method: 'post' },
                },
                lookup: { url: '/operations/{request_id}', method: 'get' },
                refresh: () => Promise.resolve(),
                onCompleted,
                onRefused: vi.fn(),
            }),
        );
        const ledger = new File(['%PDF'], 'ledger.pdf', {
            type: 'application/pdf',
        });

        act(() => {
            result.current.send({
                name: 'audit.save_step',
                payload: {
                    step: 'ledger',
                    document: ledger,
                    expected_revision: 7,
                    identity_context_revision: 3,
                    request_id: 'c2b1d7e0-5a4f-4e3b-9c8d-7f6e5d4c3b2a',
                },
            });
        });

        await waitFor(() => expect(onCompleted).toHaveBeenCalled());

        const body = sent[0].data as FormData;

        expect(sent[0]).toMatchObject({ method: 'post', url: '/save' });
        expect(body).toBeInstanceOf(FormData);
        expect(body.get('document')).toBeInstanceOf(File);
        expect((body.get('document') as File).name).toBe('ledger.pdf');
        expect(body.get('step')).toBe('ledger');
        expect(body.get('expected_revision')).toBe('7');
        expect(body.get('request_id')).toBe(
            'c2b1d7e0-5a4f-4e3b-9c8d-7f6e5d4c3b2a',
        );
    });
});

describe('Operation lookups over the real transport', () => {
    type Reply = () => Promise<{
        status: number;
        data: string;
        headers: Record<string, string>;
    }>;

    const replies: Reply[] = [];
    const offline: Reply = () => Promise.reject(new Error('Network down'));
    const replay =
        (status: number, body: unknown): Reply =>
        () =>
            Promise.resolve({
                status,
                data: JSON.stringify(body),
                headers: {},
            });

    const lostThen = (...rest: Reply[]) => {
        replies.push(offline, ...rest);
        http.setClient({
            request: (config) => {
                sent.push(config);

                return (replies.shift() ?? offline)();
            },
        });
    };

    const hook = () =>
        renderHook(() =>
            useOperationCommand<OperationCommand, typeof resource>({
                actions: {
                    'application.evaluate': {
                        url: '/evaluate',
                        method: 'post',
                    },
                },
                lookup: { url: '/operations/{request_id}', method: 'get' },
                refresh: () => Promise.resolve(),
                onCompleted: vi.fn(),
                onRefused: vi.fn(),
            }),
        );

    const evaluate = (request_id: string): OperationCommand => ({
        name: 'application.evaluate',
        payload: { target: '99000000', request_id },
    });

    afterEach(() => {
        replies.length = 0;
    });

    it('settles a lost command whose lookup replays a recorded 422, keeping its field errors for a new request', async () => {
        lostThen(
            replay(422, {
                code: 'APPLICATION_INPUT_INVALID',
                errors: { target: ['Enter a whole RWF amount.'] },
            }),
        );
        const { result } = hook();

        act(() => {
            result.current.send(evaluate('a1'));
        });

        await waitFor(() =>
            expect(result.current.errors).toEqual({
                target: 'Enter a whole RWF amount.',
            }),
        );
        expect(result.current.notice).toBeNull();
        expect(result.current.unresolved).toBe(false);

        /* The held command is released: a correction goes under a new request ID. */
        let accepted = false;

        act(() => {
            accepted = result.current.send(evaluate('a2'));
        });
        expect(accepted).toBe(true);
        expect(sent.at(-1)?.url).toBe('/evaluate');
    });

    it('reports a replayed 422 that carries no field errors as refused', async () => {
        lostThen(replay(422, { code: 'APPLICATION_INPUT_INVALID' }));
        const { result } = hook();

        act(() => {
            result.current.send(evaluate('b1'));
        });

        await waitFor(() =>
            expect(result.current.notice).toEqual({
                kind: 'refused',
                code: 'REQUEST_FAILED',
                status: 422,
            }),
        );
        expect(result.current.unresolved).toBe(false);
    });

    it('keeps a command unknown when the lookup itself cannot be reached', async () => {
        lostThen(offline);
        const { result } = hook();

        act(() => {
            result.current.send(evaluate('c1'));
        });

        await waitFor(() =>
            expect(result.current.notice).toEqual({ kind: 'unconfirmed' }),
        );
        expect(result.current.unresolved).toBe(true);
    });

    it('keeps a first 422 as field errors on the page', async () => {
        replies.push(replay(422, { errors: { target: ['Too large'] } }));
        http.setClient({
            request: (config) => {
                sent.push(config);

                return (replies.shift() ?? offline)();
            },
        });
        const { result } = hook();

        act(() => {
            result.current.send(evaluate('d1'));
        });

        await waitFor(() =>
            expect(result.current.errors).toEqual({ target: 'Too large' }),
        );
        expect(result.current.notice).toBeNull();
        expect(sent).toHaveLength(1);
    });
});
