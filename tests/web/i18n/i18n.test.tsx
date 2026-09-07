import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import I18nProvider from '@/components/i18n-provider';
import { useTranslation } from '@/hooks/use-translation';
import {
    catalogFor,
    DEFAULT_LOCALE,
    isLocale,
    LOCALES,
    PSEUDO_LOCALE,
    resolveLocale,
    translate,
} from '@/lib/i18n';
import en from '@/lib/i18n/catalogs/en';
import { pseudoCatalog, pseudoLocalize } from '@/lib/i18n/pseudo';
import type { Catalog, Locale } from '@/lib/i18n/types';

function Probe({ count }: { count?: number }) {
    const { t, locale } = useTranslation();

    return (
        <div>
            <p data-testid="locale">{locale}</p>
            <p data-testid="label">{t('auth.login.email_label')}</p>
            <p data-testid="plural">
                {t('auth.two_factor.recovery_codes_remaining', {
                    count: count ?? 1,
                })}
            </p>
        </div>
    );
}

function renderAt(locale: string | null | undefined) {
    return render(
        <I18nProvider locale={locale}>
            <Probe />
        </I18nProvider>,
    );
}

describe('locale resolution', () => {
    it('accepts every locale it advertises', () => {
        for (const locale of LOCALES) {
            expect(isLocale(locale)).toBe(true);
            expect(resolveLocale(locale)).toBe(locale);
        }
    });

    it('rejects a locale it does not carry', () => {
        expect(isLocale('sw')).toBe(false);
    });

    it('falls back to the default when nothing is requested', () => {
        expect(resolveLocale(null)).toBe(DEFAULT_LOCALE);
        expect(resolveLocale(undefined)).toBe(DEFAULT_LOCALE);
        expect(resolveLocale('')).toBe(DEFAULT_LOCALE);
    });

    it('reads a regional variant as its base language rather than as English', () => {
        expect(resolveLocale('fr-RW')).toBe('fr');
        expect(resolveLocale('rw-RW')).toBe('rw');
    });

    it('falls back to the default for a language it does not carry', () => {
        expect(resolveLocale('de-DE')).toBe(DEFAULT_LOCALE);
        expect(resolveLocale('sw')).toBe(DEFAULT_LOCALE);
    });
});

describe('translation', () => {
    it('returns the message for the active locale', () => {
        expect(
            translate(catalogFor('fr'), 'fr', 'auth.login.email_label'),
        ).toBe('Adresse e-mail');
        expect(
            translate(catalogFor('rw'), 'rw', 'auth.login.email_label'),
        ).toBe('Aderesi imeyili');
    });

    it('falls back to English rather than rendering blank when a locale is missing a code', () => {
        const partial = { 'common.brand.name': 'Rozine' } as unknown as Catalog;

        expect(translate(partial, 'fr', 'auth.login.email_label')).toBe(
            'Email address',
        );
    });

    it('renders the code itself when no catalog has the message, so it cannot ship silently', () => {
        const empty = {} as Catalog;
        const unknown = 'auth.nonexistent.code' as never;

        expect(translate(empty, 'en', unknown)).toBe('auth.nonexistent.code');
    });

    it('interpolates named values', () => {
        expect(
            translate(
                catalogFor('en'),
                'en',
                'auth.two_factor.recovery_codes_remaining',
                {
                    count: 4,
                },
            ),
        ).toBe('4 recovery codes remaining');
    });

    it('leaves a placeholder untouched when no value is supplied for it', () => {
        const catalog = {
            'common.brand.name': 'Hello {missing}',
        } as unknown as Catalog;

        expect(
            translate(catalog, 'en', 'common.brand.name', { other: 'x' }),
        ).toBe('Hello {missing}');
    });

    it('returns the template unchanged when no values are passed at all', () => {
        expect(
            translate(catalogFor('en'), 'en', 'auth.login.email_label'),
        ).toBe('Email address');
    });

    it('selects the plural category for the count', () => {
        const catalog = catalogFor('en');
        const code = 'auth.two_factor.recovery_codes_remaining';

        expect(translate(catalog, 'en', code, { count: 1 })).toBe(
            '1 recovery code remaining',
        );
        expect(translate(catalog, 'en', code, { count: 0 })).toBe(
            '0 recovery codes remaining',
        );
    });

    it('falls back to the other form when the count is absent', () => {
        expect(
            translate(
                catalogFor('en'),
                'en',
                'auth.two_factor.recovery_codes_remaining',
            ),
        ).toBe('{count} recovery codes remaining');
    });

    it('falls back to the other form when the locale has no entry for the selected category', () => {
        const catalog = {
            'auth.two_factor.recovery_codes_remaining': {
                other: '{count} left',
            },
        } as unknown as Catalog;

        expect(
            translate(
                catalog,
                'en',
                'auth.two_factor.recovery_codes_remaining',
                { count: 1 },
            ),
        ).toBe('1 left');
    });

    it('uses English plural categories for the pseudo locale', () => {
        const result = translate(
            catalogFor(PSEUDO_LOCALE),
            PSEUDO_LOCALE,
            'auth.two_factor.recovery_codes_remaining',
            { count: 1 },
        );

        expect(result).toContain('1');
        expect(result).toContain('⟦');
    });
});

describe('pseudo-localization', () => {
    it('accents every letter so an untranslated string is visibly plain', () => {
        expect(pseudoLocalize('Email')).toContain('Éɱáíł');
    });

    it('leaves placeholders intact so interpolation bugs stay visible', () => {
        expect(pseudoLocalize('Hi {name}')).toContain('{name}');
    });

    it('pads the string so a layout that cannot take longer copy fails here first', () => {
        expect(pseudoLocalize('Email').length).toBeGreaterThan('Email'.length);
    });

    it('passes through characters that have no accented form', () => {
        expect(pseudoLocalize('1 + 2')).toContain('1 + 2');
    });

    it('transforms plural forms as well as plain strings', () => {
        const catalog = pseudoCatalog(en as Catalog);
        const plural = catalog['auth.two_factor.recovery_codes_remaining'];

        expect(typeof plural).toBe('object');
        expect(JSON.stringify(plural)).toContain('⟦');
        expect(typeof catalog['auth.login.email_label']).toBe('string');
    });

    it('covers every code in the canonical catalog', () => {
        expect(Object.keys(pseudoCatalog(en as Catalog))).toEqual(
            Object.keys(en),
        );
    });
});

describe('useTranslation in a component tree', () => {
    it('renders English by default when no provider wraps the tree', () => {
        render(<Probe />);

        expect(screen.getByTestId('locale')).toHaveTextContent('en');
        expect(screen.getByTestId('label')).toHaveTextContent('Email address');
    });

    it.each([
        ['fr', 'Adresse e-mail'],
        ['rw', 'Aderesi imeyili'],
        ['en', 'Email address'],
    ] as [Locale, string][])(
        'renders %s copy through the provider',
        (locale, expected) => {
            renderAt(locale);

            expect(screen.getByTestId('locale')).toHaveTextContent(locale);
            expect(screen.getByTestId('label')).toHaveTextContent(expected);
        },
    );

    it('falls back to English when the document declares a locale we do not carry', () => {
        renderAt('de-DE');

        expect(screen.getByTestId('locale')).toHaveTextContent('en');
        expect(screen.getByTestId('label')).toHaveTextContent('Email address');
    });

    it('pluralises through the provider for the active locale', () => {
        renderAt('fr');

        expect(screen.getByTestId('plural')).toHaveTextContent(
            '1 code de récupération restant',
        );
    });
});
