import { useMemo } from 'react';
import type { ReactNode } from 'react';
import { catalogFor, resolveLocale } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';

type Props = {
    /**
     * Usually `document.documentElement.lang`, which Laravel renders from `app()->getLocale()`.
     * Passing it in rather than reading it here keeps the provider free of Inertia and DOM coupling
     * and makes locale switching a matter of re-rendering with a different value.
     */
    locale?: string | null;
    children: ReactNode;
};

export default function I18nProvider({ locale, children }: Props) {
    const value = useMemo(() => {
        const resolved = resolveLocale(locale);

        return { locale: resolved, catalog: catalogFor(resolved) };
    }, [locale]);

    return <I18nContext value={value}>{children}</I18nContext>;
}
