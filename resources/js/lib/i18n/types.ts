import type en from '@/lib/i18n/catalogs/en';

/** Locales the application can resolve. Which of these ship at launch is still D-07. */
export type Locale = 'en' | 'fr' | 'rw' | 'en-XA';

/**
 * A plural message. Categories are whatever `Intl.PluralRules` returns for the active locale, so a
 * locale needing `few`/`many` declares them without any change here. `other` is always required
 * because every CLDR locale has it and it is the safe fallback.
 */
export type PluralForms = Partial<Record<Intl.LDMLPluralRule, string>> & {
    other: string;
};

export type Message = string | PluralForms;

/** Stable message identifiers. English is the source of truth for the key set. */
export type MessageCode = keyof typeof en;

export type Catalog = Record<MessageCode, Message>;

/** Values interpolated into `{placeholder}` slots. `count` additionally drives plural selection. */
export type TranslationValues = Record<string, string | number>;
