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
