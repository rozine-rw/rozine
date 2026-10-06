import type * as InertiaCore from '@inertiajs/core';
import { act, renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    isUncertainStatus,
    readErrorCode,
    refusalRefreshes,
} from '@/components/business/apply/operation-outcome';
import { useApplicationCommand } from '@/components/business/apply/use-application-command';
import type { ApplicationCommand } from '@/types/business';

type HttpOptions = {
    onHttpException?: (response: { status: number; data: string }) => void;
};

const http = vi.hoisted(() => ({
    calls: [] as { url: string; body: unknown }[],
    body: undefined as unknown,
    responses: [] as ((options: HttpOptions) => Promise<unknown>)[],
}));

const transport = vi.hoisted(() => ({
    handler: null as
        | null
        | ((response: {
              status: number;
              data: string;
              headers: Record<string, string>;
          }) => unknown),
}));

vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaCore>()),
    http: {
        onResponse: (handler: typeof transport.handler) => {
            transport.handler = handler;

            return () => {
                transport.handler = null;
            };
        },
    },
}));

vi.mock('@inertiajs/react', () => ({
    useHttp: () => ({
        errors: {},
        clearErrors: () => undefined,
        transform: (callback: () => unknown) => {
            http.body = callback();
        },
        submit: (route: { url: string }, options: HttpOptions) => {
            http.calls.push({ url: route.url, body: http.body });
            const respond = http.responses.shift();

            return respond ? respond(options) : new Promise(() => undefined);
        },
    }),
}));

const route = (url: string) => ({ url, method: 'post' as const });

const options = {
    actions: {
        save: route('/save'),
        evaluate: route('/evaluate'),
        submit: route('/submit'),
    },
    lookup: { url: '/operations/{request_id}', method: 'get' as const },
    identityContextRevision: 4,
    onCompleted: vi.fn(),
    onRefused: vi.fn(),
};

const command = (request_id: string): ApplicationCommand => ({
    name: 'save',
    advance: false,
    payload: { request_id },
});

beforeEach(() => {
    http.calls = [];
    http.body = undefined;
    http.responses = [];
});

describe('Application commands', () => {
    it('reads only a 422 for a recorded code, and passes every response on unchanged', async () => {
        const onCompleted = vi.fn();
        const completed = { status: 'completed', code: 'APPLICATION_SAVED' };
        const other = { status: 200, data: '{}', headers: {} };
        let passed: unknown = null;
        const { result } = renderHook(() =>
            useApplicationCommand({ ...options, onCompleted }),
        );

        http.responses.push(() => {
            passed = transport.handler?.(other);

            return Promise.resolve(completed);
        });
        act(() => {
            result.current.send(command('plain'));
        });

        await waitFor(() => expect(onCompleted).toHaveBeenCalled());
        expect(passed).toBe(other);
        expect(transport.handler).toBeNull();
    });

    it('says whether a completion was answered directly or recovered by the lookup', async () => {
        const onCompleted = vi.fn();
        const completed = { status: 'completed', code: 'APPLICATION_SAVED' };
        const { result } = renderHook(() =>
            useApplicationCommand({ ...options, onCompleted }),
        );

        http.responses.push(() => Promise.resolve(completed));
        act(() => {
            result.current.send(command('direct'));
        });
        await waitFor(() =>
            expect(onCompleted).toHaveBeenCalledWith(
                command('direct'),
                completed,
                { recovered: false },
            ),
        );

        http.responses.push(
            () => Promise.reject(new Error('offline')),
            () => Promise.resolve(completed),
        );
        act(() => {
            result.current.send(command('lost'));
        });
        await waitFor(() =>
            expect(onCompleted).toHaveBeenLastCalledWith(
                command('lost'),
                completed,
                { recovered: true },
            ),
        );
    });

    it('keeps one command in flight and refuses a second meanwhile', () => {
        const { result } = renderHook(() => useApplicationCommand(options));
        let first = false;
        let second = true;

        act(() => {
            first = result.current.send(command('a'));
            second = result.current.send(command('b'));
        });

        expect(first).toBe(true);
        expect(second).toBe(false);
        expect(http.calls).toEqual([
            { url: '/save', body: { request_id: 'a' } },
        ]);
        expect(result.current.busy).toBe(true);
    });

    it('ignores a retry or check with nothing held, and a retry the lookup has not cleared', async () => {
        const { result } = renderHook(() => useApplicationCommand(options));

        act(() => {
            result.current.retry();
            result.current.checkAgain();
        });
        expect(http.calls).toHaveLength(0);

        http.responses.push(() => Promise.reject(new Error('offline')));
        http.responses.push(() => Promise.reject(new Error('offline')));
        act(() => {
            result.current.send(command('c'));
        });

        await waitFor(() =>
            expect(result.current.notice).toEqual({ kind: 'unconfirmed' }),
        );
        expect(result.current.unresolved).toBe(true);

        act(() => {
            result.current.retry();
        });
        expect(http.calls).toHaveLength(2);

        act(() => {
            result.current.checkAgain();
            result.current.checkAgain();
        });
        expect(http.calls).toHaveLength(3);
        expect(http.calls[2]).toEqual({
            url: '/operations/c',
            body: { command: 'save', identity_context_revision: 4 },
        });
    });
});

describe('Operation lookups', () => {
    const replays =
        (status: number, code?: string) => (options: HttpOptions) => {
            options.onHttpException?.({
                status,
                data: code === undefined ? '' : JSON.stringify({ code }),
            });

            return Promise.reject(new Error(`HTTP ${status}`));
        };

    it.each([
        [409, 'VERSION_CONFLICT'],
        [404, 'NOT_FOUND'],
        [403, undefined],
    ])(
        'settles a lost command as refused when the lookup replays its recorded %i',
        async (status, code) => {
            const onRefused = vi.fn();
            const { result } = renderHook(() =>
                useApplicationCommand({ ...options, onRefused }),
            );

            http.responses.push(
                () => Promise.reject(new Error('offline')),
                replays(status, code),
            );
            act(() => {
                result.current.send(command('d'));
            });

            await waitFor(() =>
                expect(result.current.notice).toEqual({
                    kind: 'refused',
                    code: code ?? 'ACTION_FORBIDDEN',
                    status,
                }),
            );
            expect(onRefused).toHaveBeenCalledWith(
                command('d'),
                code ?? 'ACTION_FORBIDDEN',
                status,
            );
            expect(result.current.unresolved).toBe(false);
        },
    );

    it('keeps the outcome unknown when the lookup itself fails without a recorded answer', async () => {
        const { result } = renderHook(() => useApplicationCommand(options));

        http.responses.push(
            () => Promise.reject(new Error('offline')),
            replays(502),
        );
        act(() => {
            result.current.send(command('e'));
        });

        await waitFor(() =>
            expect(result.current.notice).toEqual({ kind: 'unconfirmed' }),
        );
    });
});

describe('Operation outcomes', () => {
    it('reads a stable code from a JSON body, and nothing from anything else', () => {
        expect(readErrorCode('{"code":"QUOTE_STALE"}')).toBe('QUOTE_STALE');
        expect(readErrorCode({ code: 'VERSION_CONFLICT' })).toBe(
            'VERSION_CONFLICT',
        );
        expect(readErrorCode({ code: 5 })).toBeNull();
        expect(readErrorCode(null)).toBeNull();
        expect(readErrorCode('not json')).toBeNull();
    });

    it('treats only timeouts and server failures as unknown outcomes', () => {
        expect(isUncertainStatus(408)).toBe(true);
        expect(isUncertainStatus(502)).toBe(true);
        expect(isUncertainStatus(409)).toBe(false);
    });

    it('refreshes stale facts but not denials', () => {
        expect(refusalRefreshes('VERSION_CONFLICT', 409)).toBe(true);
        expect(refusalRefreshes('MANDATE_REQUIRED', 200)).toBe(false);
        expect(refusalRefreshes('ACTION_FORBIDDEN', 403)).toBe(false);
        expect(refusalRefreshes('NOT_FOUND', 404)).toBe(false);
    });
});
