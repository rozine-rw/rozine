import { use } from 'react';
import { translate } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import type { Locale, MessageCode, TranslationValues } from '@/lib/i18n/types';

type Translator = {
    t: (code: MessageCode, values?: TranslationValues) => string;
    locale: Locale;
};

/**
 * `t` is typed against the English key set, so a mistyped or retired message code is a build error
 * rather than a string that renders its own code at a user.
 */
export function useTranslation(): Translator {
    const { locale, catalog } = use(I18nContext);

    return {
        locale,
        t: (code, values) => translate(catalog, locale, code, values),
    };
}
