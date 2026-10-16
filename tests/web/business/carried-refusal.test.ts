import { afterEach, describe, expect, it } from 'vite-plus/test';
import {
    carriedRefusal,
    carryRefusal,
    clearCarriedRefusal,
} from '@/components/business/apply/carried-refusal';

const refusal = {
    url: 'http://localhost/business/a/applications/b',
    code: 'VERSION_CONFLICT',
    status: 409,
    haltedKey: '25000000|4',
};

afterEach(() => clearCarriedRefusal());

describe('A refusal carried across one remount', () => {
    it('reaches only the page at its own URL, until it is cleared', () => {
        expect(carriedRefusal(refusal.url)).toBeNull();

        carryRefusal(refusal);

        expect(carriedRefusal('http://localhost/business')).toBeNull();
        expect(carriedRefusal(refusal.url)).toEqual(refusal);

        clearCarriedRefusal();

        expect(carriedRefusal(refusal.url)).toBeNull();
    });
});
