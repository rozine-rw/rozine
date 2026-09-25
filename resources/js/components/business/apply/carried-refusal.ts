/**
 * A refusal that needs fresh facts is followed by a non-preserving visit that remounts the Apply
 * page, which would otherwise drop the banner explaining it. The refusal is carried across that
 * one remount here, in memory and keyed to the page's URL, and consumed when the page mounts; a
 * full browser reload or any other page starts without it.
 */
export type CarriedRefusal = {
    url: string;
    code: string;
    status: number;
    /** The request the refused save or evaluation carried, so it is not sent again unchanged. */
    haltedKey: string | null;
};

let pending: CarriedRefusal | null = null;

/** Holds the refusal for the page that mounts next at `refusal.url`. */
export function carryRefusal(refusal: CarriedRefusal): void {
    pending = refusal;
}

/** The refusal carried to `url`, if any; it is not consumed until `clearCarriedRefusal`. */
export function carriedRefusal(url: string): CarriedRefusal | null {
    return pending !== null && pending.url === url ? pending : null;
}

/** Drops the carried refusal once a page has mounted with it (or without it). */
export function clearCarriedRefusal(): void {
    pending = null;
}
