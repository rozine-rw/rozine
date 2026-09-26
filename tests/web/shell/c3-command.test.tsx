import { act, renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { seedFromPreview, useC3Command } from '@/hooks/use-c3-command';
import { inertia, resetInertia } from '../investor/inertia-mock';

vi.mock('@inertiajs/react', () => import('../investor/inertia-mock'));

const lookup = { url: '/operations/{request_id}', method: 'get' as const };
const route = { url: '/commands/reserve', method: 'post' as const };

beforeEach(() => {
    resetInertia();
});

describe('C3 commands', () => {
    it('never sends a command whose route the page does not carry', () => {
        const { result } = renderHook(() =>
            useC3Command<'primary.reserve' | 'primary.confirm'>({
                actions: { 'primary.reserve': route, 'primary.confirm': null },
                lookup,
                allowed: ['primary.reserve', 'primary.confirm'],
            }),
        );

        let sent = true;

        act(() => {
            sent = result.current.send('primary.confirm', {});
        });
        expect(sent).toBe(false);
        expect(inertia.calls).toHaveLength(0);
        expect(result.current.retryAllowed).toBe(false);
    });

    it('sends one command at a time and offers a retry only for the one it holds', () => {
        const { result } = renderHook(() =>
            useC3Command<'primary.reserve'>({
                actions: { 'primary.reserve': route },
                lookup,
                allowed: ['primary.reserve'],
            }),
        );

        let first = false;
        let second = true;

        act(() => {
            first = result.current.send('primary.reserve', { units: '1' });
        });
        act(() => {
            second = result.current.send('primary.reserve', { units: '2' });
        });
        expect(first).toBe(true);
        expect(second).toBe(false);
        expect(inertia.calls).toHaveLength(1);
        expect(result.current.retryAllowed).toBe(true);
    });

    it('seeds nothing without a preview outcome', () => {
        expect(seedFromPreview(undefined)).toEqual({
            held: null,
            notice: null,
        });
    });
});
