import en from '@/lib/i18n/catalogs/en';
import type {
    Catalog,
    Locale,
    Message,
    MessageCode,
    TranslationValues,
} from '@/lib/i18n/types';

/**
 * Resolves one message code against a catalog.
 *
 * Three deliberate behaviours:
 *
 *   - a code missing from the active catalog falls back to English rather than rendering blank, so a
 *     partial translation degrades to a readable page instead of an empty one;
 *   - a code missing from English too returns the code itself, which is loud in the UI and easy to
 *     grep for — silence would let a missing string ship;
 *   - a plural message with no `count` falls back to `other`, which is the only category guaranteed
 *     to exist in every CLDR locale.
 */
function selectForm(
    message: Message,
    locale: Locale,
    values: TranslationValues | undefined,
): string {
    if (typeof message === 'string') {
        return message;
    }

    const count = values?.count;

    if (typeof count !== 'number') {
        return message.other;
    }

    const category = new Intl.PluralRules(pluralLocale(locale)).select(count);

    return message[category] ?? message.other;
}

/**
 * The pseudo locale is not a real BCP 47 locale for plural purposes; it is English with the text
 * mangled, so its plural categories must come from English.
 */
function pluralLocale(locale: Locale): string {
    return locale === 'en-XA' ? 'en' : locale;
}

function interpolate(
    template: string,
    values: TranslationValues | undefined,
): string {
    if (!values) {
        return template;
    }

    return template.replace(/\{(\w+)\}/gu, (match, name: string) =>
        name in values ? String(values[name]) : match,
    );
}

export function translate(
    catalog: Catalog,
    locale: Locale,
    code: MessageCode,
    values?: TranslationValues,
): string {
    const message = catalog[code] ?? (en as Catalog)[code];

    if (message === undefined) {
        return code;
    }

    return interpolate(selectForm(message, locale, values), values);
}
