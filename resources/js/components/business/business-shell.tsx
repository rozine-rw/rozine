import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppFrame } from '@/components/rozine/app-frame';
import { useTranslation } from '@/hooks/use-translation';
import type { BusinessShellLinks } from '@/types/business';

export type BusinessTab = 'home' | 'reports' | 'profile';

/** The Business tab glyphs, drawn exactly as the design's tab bar and sidebar draw them. */
const GLYPHS: Record<BusinessTab, ReactNode> = {
    home: (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden>
            <path
                d="M4 11 12 4l8 7v8a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1Z"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinejoin="round"
            />
        </svg>
    ),
    reports: (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden>
            <path
                d="M6 20V10m6 10V4m6 16v-7"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinecap="round"
            />
        </svg>
    ),
    profile: (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden>
            <circle
                cx="12"
                cy="8"
                r="4"
                stroke="currentColor"
                strokeWidth="1.8"
            />
            <path
                d="M4 20c0-4 4-6 8-6s8 2 8 6"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinecap="round"
            />
        </svg>
    ),
};

type BusinessShellProps = {
    title: string;
    tab: BusinessTab;
    links: BusinessShellLinks;
    showTabBar?: boolean;
    children: ReactNode;
};

/**
 * The Business app frame. The design's fourth tab, Market, is secondary trading, which is outside
 * the MVP for issuers, so it is left out rather than shipped as a dead end. A destination the
 * server leaves null is hidden from the sidebar and the tab bar alike.
 */
export function BusinessShell({
    title,
    tab,
    links,
    showTabBar,
    children,
}: BusinessShellProps) {
    const { t } = useTranslation();
    const nav = (['home', 'reports', 'profile'] as const).flatMap((key) => {
        const href = links[key];

        return href === null
            ? []
            : [
                  {
                      key,
                      label: t(`business.nav.${key}`),
                      href,
                      glyph: GLYPHS[key],
                  },
              ];
    });

    return (
        <AppFrame
            audience="business"
            nav={nav}
            active={tab}
            launcher={links.launcher}
            showTabBar={showTabBar}
        >
            <Head title={title} />
            {children}
        </AppFrame>
    );
}
