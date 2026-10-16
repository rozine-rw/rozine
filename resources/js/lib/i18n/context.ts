import { createContext } from 'react';
import { catalogFor, DEFAULT_LOCALE } from '@/lib/i18n';
import type { Catalog, Locale } from '@/lib/i18n/types';

export type I18nContextValue = {
    locale: Locale;
    catalog: Catalog;
};

/**
 * Defaults to English so a component rendered outside the provider — in a unit test, or in a tree
 * mounted before the provider exists — still renders real copy rather than message codes.
 */
export const I18nContext = createContext<I18nContextValue>({
    locale: DEFAULT_LOCALE,
    catalog: catalogFor(DEFAULT_LOCALE),
});
