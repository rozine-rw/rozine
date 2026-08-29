import { renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { useInitials } from '@/hooks/use-initials';

describe('useInitials', () => {
    it.each([
        ['Ada Lovelace', 'AL'],
        ['  Grace   Brewster   Hopper  ', 'GH'],
        ['Prince', 'P'],
        ['', ''],
        ['   ', ''],
    ])('derives initials from %j as %j', (fullName, initials) => {
        const { result } = renderHook(() => useInitials());

        expect(result.current(fullName)).toBe(initials);
    });
});
