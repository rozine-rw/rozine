import { act, renderHook } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { useResaleCommand } from '@/components/investor/market/resale';

/**
 * The real `useHttp` and Inertia's own XHR client, with only the browser's XMLHttpRequest swapped:
 * Inertia rejects a 422 and hands it to its error handlers, never to its response handlers, so the
 * refusal's code must be read there (QA #663 retest: the Market still showed REQUEST_FAILED).
 */
class RefusingRequest {
    static body = '';

    status = 0;
    responseText = '';
    upload = {};
    onload: (() => void) | null = null;
    onerror: (() => void) | null = null;
    onabort: (() => void) | null = null;

    open(): void {}

    setRequestHeader(): void {}

    getAllResponseHeaders(): string {
        return 'content-type: application/json\r\n';
    }

    abort(): void {}

    send(): void {
        this.status = 422;
        this.responseText = RefusingRequest.body;
        queueMicrotask(() => this.onload?.());
    }
}

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('Resale refusals over the real transport', () => {
    it('keeps the code and facts of a 422 refusal', async () => {
        RefusingRequest.body = JSON.stringify({
            code: 'HOLDING_LIMIT_EXCEEDED',
            data: { maximum_units: '0' },
        });
        vi.stubGlobal('XMLHttpRequest', RefusingRequest);
        const { result } = renderHook(() => useResaleCommand(3));
        let sent: boolean | undefined;

        await act(async () => {
            sent = await result.current.send(
                { url: '/investor/market/listings/l1/reserve', method: 'post' },
                { units: '5' },
            );
        });

        expect(sent).toBe(false);
        expect(result.current.refusal).toEqual({
            code: 'HOLDING_LIMIT_EXCEEDED',
            data: { maximum_units: '0' },
        });
    });
});
