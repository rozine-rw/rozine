import { useTranslation } from '@/hooks/use-translation';

/** The id the app frames give their `<main>`, which the skip link moves focus to. */
export const MAIN_CONTENT_ID = 'main-content';

/**
 * "Skip to content" (WCAG 2.2 SC 2.4.1): the first thing a keyboard user reaches on every app page.
 * It stays off screen until focused, then jumps past the sidebar and navigation to the page itself.
 */
export function SkipLink() {
    const { t } = useTranslation();

    return (
        <a
            href={`#${MAIN_CONTENT_ID}`}
            className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-xl focus:bg-rz-surface focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-rz-ink focus:shadow-[var(--rz-shadow-lifted)]"
        >
            {t('app.skip_to_content')}
        </a>
    );
}
