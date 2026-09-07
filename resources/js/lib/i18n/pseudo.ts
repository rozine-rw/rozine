import type { Catalog, Message } from '@/lib/i18n/types';

/**
 * Pseudo-localization.
 *
 * Builds a synthetic locale from English so two classes of defect show up before any real
 * translation exists:
 *
 *   - a string that renders un-accented is hard-coded rather than translated;
 *   - a layout that breaks under padded text will break in French and Kinyarwanda too.
 *
 * Placeholders are preserved verbatim, because mangling them would hide interpolation bugs behind
 * pseudo-localization noise rather than exposing them.
 */
const ACCENTS: Record<string, string> = {
    a: 'á',
    b: 'ƀ',
    c: 'ç',
    d: 'ð',
    e: 'é',
    f: 'ƒ',
    g: 'ĝ',
    h: 'ĥ',
    i: 'í',
    j: 'ĵ',
    k: 'ķ',
    l: 'ł',
    m: 'ɱ',
    n: 'ñ',
    o: 'ó',
    p: 'þ',
    q: ' q',
    r: 'ŕ',
    s: 'š',
    t: 'ţ',
    u: 'ú',
    v: 'ṽ',
    w: 'ŵ',
    x: 'ẋ',
    y: 'ý',
    z: 'ž',
    A: 'Á',
    B: 'Ɓ',
    C: 'Ç',
    D: 'Ð',
    E: 'É',
    F: 'Ƒ',
    G: 'Ĝ',
    H: 'Ĥ',
    I: 'Í',
    J: 'Ĵ',
    K: 'Ķ',
    L: 'Ł',
    M: 'Ṁ',
    N: 'Ñ',
    O: 'Ó',
    P: 'Þ',
    Q: 'Q',
    R: 'Ŕ',
    S: 'Š',
    T: 'Ţ',
    U: 'Ú',
    V: 'Ṽ',
    W: 'Ŵ',
    X: 'Ẋ',
    Y: 'Ý',
    Z: 'Ž',
};

/** Padding proves the layout survives languages that run longer than English. */
const PADDING_RATIO = 0.3;

export function pseudoLocalize(source: string): string {
    const accented = source.replace(/\{[^{}]*\}|./gu, (segment) =>
        segment.startsWith('{') ? segment : (ACCENTS[segment] ?? segment),
    );

    const padding = '·'.repeat(Math.ceil(source.length * PADDING_RATIO));

    return `⟦${accented}${padding}⟧`;
}

function pseudoMessage(message: Message): Message {
    if (typeof message === 'string') {
        return pseudoLocalize(message);
    }

    return Object.fromEntries(
        Object.entries(message).map(([category, form]) => [
            category,
            pseudoLocalize(form),
        ]),
    ) as Message;
}

export function pseudoCatalog(source: Catalog): Catalog {
    return Object.fromEntries(
        Object.entries(source).map(([code, message]) => [
            code,
            pseudoMessage(message),
        ]),
    ) as Catalog;
}
