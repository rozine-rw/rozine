import en from '@/lib/i18n/catalogs/en';
import fr from '@/lib/i18n/catalogs/fr';
import rw from '@/lib/i18n/catalogs/rw';
import { pseudoCatalog } from '@/lib/i18n/pseudo';
import type { Catalog, Locale } from '@/lib/i18n/types';

export const DEFAULT_LOCALE: Locale = 'en';

/** `en-XA` is the pseudo locale. It is a development instrument and never a launch language. */
export const PSEUDO_LOCALE: Locale = 'en-XA';

export const LOCALES: readonly Locale[] = ['en', 'fr', 'rw', PSEUDO_LOCALE];

const CATALOGS: Record<Locale, () => Catalog> = {
    en: () => en as Catalog,
    fr: () => fr,
    rw: () => rw,
    [PSEUDO_LOCALE]: () => pseudoCatalog(en as Catalog),
};

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

export function catalogFor(locale: Locale): Catalog {
    return CATALOGS[locale]();
}

export type {
    Catalog,
    Locale,
    MessageCode,
    TranslationValues,
} from '@/lib/i18n/types';
export { translate } from '@/lib/i18n/translate';
