import { act, renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    isUncertainStatus,
    readErrorCode,
    refusalRefreshes,
} from '@/components/business/apply/operation-outcome';
import { useApplicationCommand } from '@/components/business/apply/use-application-command';
import type { ApplicationCommand } from '@/types/business';

const http = vi.hoisted(() => ({
    calls: [] as { url: string; body: unknown }[],
    body: undefined as unknown,
    responses: [] as (() => Promise<unknown>)[],
}));

vi.mock('@inertiajs/react', () => ({
    useHttp: () => ({
        errors: {},
        clearErrors: () => undefined,
        transform: (callback: () => unknown) => {
            http.body = callback();
        },
        submit: (route: { url: string }) => {
            http.calls.push({ url: route.url, body: http.body });
            const respond = http.responses.shift();

            return respond ? respond() : new Promise(() => undefined);
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
            body: { command: 'save' },
        });
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
