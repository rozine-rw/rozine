import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppFrame } from '@/components/rozine/app-frame';
import { useTranslation } from '@/hooks/use-translation';
import type { InvestorAppLinks } from '@/types/investor';

export type InvestorTab = 'deals' | 'portfolio' | 'profile';

const stroke = {
    stroke: 'currentColor',
    strokeWidth: 1.8,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
} as const;

/** The Investor tab glyphs, drawn exactly as the design's tab bar and sidebar draw them. */
const GLYPHS: Record<InvestorTab, ReactNode> = {
    deals: (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden>
            <rect x="3" y="5" width="12" height="15" rx="2.4" {...stroke} />
            <path d="M17 7.5l3.2 1.1a1.6 1.6 0 0 1 1 2l-3 9" {...stroke} />
        </svg>
    ),
    portfolio: (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden>
            <path d="M4 18V9m5 9V5m5 13v-6m5 6V8" {...stroke} />
        </svg>
    ),
    profile: (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden>
            <circle cx="12" cy="8" r="4" {...stroke} />
            <path d="M4 20c0-4 4-6 8-6s8 2 8 6" {...stroke} />
        </svg>
    ),
};

type InvestorShellProps = {
    title: string;
    /** The tab to mark current; Wallet has no tab of its own, as in the design. */
    tab: InvestorTab | null;
    links: InvestorAppLinks;
    showTabBar?: boolean;
    children: ReactNode;
};

/**
 * The Investor app frame. The design's third tab, Market, is secondary trading: the order book,
 * resale and "sell back to Rozine" are outside the MVP (crosswalk MVP-INVESTOR-SCR-06/07 are
 * Phase 3, and policy rejects the sell-back), so the tab is left out rather than shipped as a
 * dead end.
 */
export function InvestorShell({
    title,
    tab,
    links,
    showTabBar,
    children,
}: InvestorShellProps) {
    const { t } = useTranslation();
    const nav = (['deals', 'portfolio', 'profile'] as const).map((key) => ({
        key,
        label: t(`investor.nav.${key}`),
        href: links[key],
        glyph: GLYPHS[key],
    }));

    return (
        <AppFrame
            audience="investor"
            nav={nav}
            active={tab ?? ''}
            launcher={links.launcher}
            showTabBar={showTabBar}
        >
            <Head title={title} />
            {children}
        </AppFrame>
    );
}
