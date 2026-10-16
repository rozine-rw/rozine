import en from '@/lib/i18n/catalogs/en';
import { pseudoCatalog } from '@/lib/i18n/pseudo';
import type { Catalog, Locale } from '@/lib/i18n/types';

export const DEFAULT_LOCALE: Locale = 'en';

/** `en-XA` is the pseudo locale. It is a development instrument and never a launch language. */
export const PSEUDO_LOCALE: Locale = 'en-XA';

export const LOCALES: readonly Locale[] = ['en', 'fr', 'rw', PSEUDO_LOCALE];

/**
 * French and Kinyarwanda are fetched only for a page rendered in them, so an English page does not
 * download two other catalogs (about two thirds of the shell script) before it can respond.
 */
const LOADERS: Partial<Record<Locale, () => Promise<{ default: Catalog }>>> = {
    fr: () => import('@/lib/i18n/catalogs/fr'),
    rw: () => import('@/lib/i18n/catalogs/rw'),
};

const loaded: Partial<Record<Locale, Catalog>> = {};

/** Fetches a locale's catalog once; English and the pseudo locale are always at hand. */
export async function loadCatalog(locale: Locale): Promise<void> {
    const load = LOADERS[locale];

    if (load !== undefined && loaded[locale] === undefined) {
        loaded[locale] = (await load()).default;
    }
}

export function isLocale(value: string): value is Locale {
    return (LOCALES as readonly string[]).includes(value);
}

/**
 * Maps whatever the server or the document put in front of us onto a supported locale.
 *
 * Region subtags are dropped (`fr-RW` resolves to `fr`) so a regional variant reads its base
 * language rather than silently falling back to English. Anything unrecognised resolves to the
 * default, because rendering message codes at users is worse than rendering the wrong language.
 */
export function resolveLocale(requested: string | null | undefined): Locale {
    if (!requested) {
        return DEFAULT_LOCALE;
    }

    if (isLocale(requested)) {
        return requested;
    }

    const base = requested.split('-')[0];

    return isLocale(base) ? base : DEFAULT_LOCALE;
}

/**
 * The catalog for a locale. One not yet fetched with `loadCatalog` (or whose fetch failed) reads as
 * English, which is also what each missing message falls back to.
 */
export function catalogFor(locale: Locale): Catalog {
    if (locale === PSEUDO_LOCALE) {
        return pseudoCatalog(en as Catalog);
    }

    return loaded[locale] ?? (en as Catalog);
}

export type {
    Catalog,
    Locale,
    MessageCode,
    TranslationValues,
} from '@/lib/i18n/types';
export { translate } from '@/lib/i18n/translate';
