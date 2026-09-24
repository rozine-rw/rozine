import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { IconGradients } from '@/components/rozine/icon';
import { LogoLockup } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';

export type AppNavItem = {
    key: string;
    label: string;
    href: RouteLink;
    /** The 24-unit stroke glyph, drawn in `currentColor`. */
    glyph: ReactNode;
};

type Audience = 'investor' | 'business' | 'auditor';

type AppFrameProps = {
    audience: Audience;
    nav: AppNavItem[];
    active: string;
    launcher: RouteLink;
    /** The phone tab bar only shows on tab screens; detail screens hide it, as the design does. */
    showTabBar?: boolean;
    children: ReactNode;
};

/** Phone tab bar height per app, as each design draws it. */
const TAB_BAR_HEIGHT: Record<Audience, string> = {
    investor: 'h-[calc(63px+env(safe-area-inset-bottom))]',
    business: 'h-[84px]',
    auditor: 'h-[84px]',
};

/** The current sidebar item, as each design draws it. */
const SIDEBAR_ACTIVE: Record<Audience, string> = {
    investor: 'bg-rz-page text-rz-accent-app-text',
    business: 'bg-rz-page text-rz-accent-app-text',
    auditor: 'bg-rz-accent-soft text-rz-ink',
};

/**
 * The role-app shell. On a phone: the screen fills the viewport with the design's frosted bottom
 * tab bar. On a wide screen: the design's desktop frame — a 182px sidebar beside the main pane on
 * the grey page — which fills the window up to a capped width instead of the prototype's fixed
 * 1113×750 box, so it matches the design exactly at that size and grows sensibly beyond it.
 */
export function AppFrame({
    audience,
    nav,
    active,
    launcher,
    showTabBar = true,
    children,
}: AppFrameProps) {
    const { t } = useTranslation();

    return (
        <div
            data-audience={audience}
            className="rz-surface min-h-svh bg-rz-surface lg:bg-rz-frame"
        >
            <IconGradients
                app={audience === 'business' ? 'business' : undefined}
            />
            <div className="lg:mx-auto lg:flex lg:h-svh lg:min-h-[640px] lg:max-w-[var(--rz-desktop-max)] lg:p-7">
                <aside className="hidden w-[182px] shrink-0 flex-col rounded-l-[20px] border-r border-rz-border bg-rz-surface px-3 py-[18px] lg:flex">
                    <div className="flex items-center gap-[11px] px-2 pt-1 pb-[22px]">
                        <LogoLockup
                            title={t('common.brand.name')}
                            className="block h-[26px] w-auto text-rz-accent-lockup"
                        />
                    </div>
                    <nav
                        aria-label={t('app.nav.label')}
                        className="flex flex-col gap-[3px]"
                    >
                        {nav.map((item) => (
                            <Link
                                key={item.key}
                                href={item.href}
                                aria-current={
                                    item.key === active ? 'page' : undefined
                                }
                                className={cn(
                                    'flex w-full items-center gap-[11px] rounded-[10px] px-3 py-[11px] text-left text-[13.5px] font-semibold',
                                    item.key === active
                                        ? SIDEBAR_ACTIVE[audience]
                                        : 'text-[#46526b] hover:bg-rz-page/60 dark:text-rz-secondary',
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex size-5 shrink-0 items-center justify-center [&>svg]:size-[19px]',
                                        item.key !== active &&
                                            'text-rz-secondary',
                                    )}
                                >
                                    {item.glyph}
                                </span>
                                <span className="flex-1">{item.label}</span>
                            </Link>
                        ))}
                    </nav>
                    <div className="mt-auto">
                        <Link
                            href={launcher}
                            className="flex w-full items-center gap-[9px] rounded-[10px] border border-rz-border bg-rz-page px-3 py-2.5 text-[13px] font-semibold text-rz-secondary"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden
                                className="size-[17px]"
                            >
                                <path
                                    d="M15 5l-7 7 7 7"
                                    stroke="currentColor"
                                    strokeWidth="2.2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                            </svg>
                            <span>{t('app.nav.launcher')}</span>
                        </Link>
                    </div>
                </aside>

                <main className="relative min-w-0 flex-1 lg:overflow-hidden lg:rounded-r-[20px] lg:bg-rz-page">
                    {children}
                </main>
            </div>

            {showTabBar && (
                <nav
                    aria-label={t('app.nav.label')}
                    className={cn(
                        'fixed inset-x-0 bottom-0 z-20 flex items-start border-t border-[#eef2f9] bg-rz-tabbar pt-[11px] backdrop-blur-[16px] lg:hidden dark:border-rz-divider',
                        TAB_BAR_HEIGHT[audience],
                    )}
                >
                    {nav.map((item) => (
                        <Link
                            key={item.key}
                            href={item.href}
                            aria-current={
                                item.key === active ? 'page' : undefined
                            }
                            className={cn(
                                'flex flex-1 flex-col items-center gap-[5px]',
                                item.key === active
                                    ? 'text-rz-accent-app-text'
                                    : 'text-rz-secondary',
                            )}
                        >
                            <span className="flex [&>svg]:size-[22px]">
                                {item.glyph}
                            </span>
                            <span className="text-[10.5px] font-semibold">
                                {item.label}
                            </span>
                        </Link>
                    ))}
                </nav>
            )}
        </div>
    );
}
