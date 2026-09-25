import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCommandsSettled } from '@/components/auditor/commands';
import { AuditorKeyframes } from '@/components/auditor/keyframes';
import { useAuditorRefresh } from '@/components/auditor/refresh';
import type { AuditorRefresh } from '@/components/auditor/refresh';
import { AppFrame } from '@/components/rozine/app-frame';
import { useTranslation } from '@/hooks/use-translation';
import type { AuditorAppLinks } from '@/types/auditor';

export type AuditorTab = 'home' | 'jobs' | 'portfolio' | 'profile';

/** A 24-unit stroke glyph in the current colour. */
function Glyph({
    className,
    children,
}: {
    className: string;
    children: ReactNode;
}) {
    return (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden className={className}>
            {children}
        </svg>
    );
}

const stroke = {
    stroke: 'currentColor',
    strokeWidth: 1.8,
} as const;

const phoneStroke = {
    stroke: 'currentColor',
    strokeWidth: 1.9,
} as const;

/**
 * The design draws two icon sets: the desktop sidebar's (Desktop L77–92) and the phone tab bar's
 * (L3034–3040, where Jobs is a bell and Portfolio a clipboard). Each tab carries both; the frame's
 * sidebar only ever shows the first and its tab bar only the second.
 */
const DESKTOP: Record<AuditorTab, ReactNode> = {
    home: (
        <path
            d="M4 11 12 4l8 7v8a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1Z"
            {...stroke}
            strokeLinejoin="round"
        />
    ),
    jobs: (
        <>
            <rect x="3.5" y="7" width="17" height="13" rx="2.5" {...stroke} />
            <path
                d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7"
                {...stroke}
            />
        </>
    ),
    portfolio: (
        <path d="M6 20V10m6 10V4m6 16v-7" {...stroke} strokeLinecap="round" />
    ),
    profile: (
        <>
            <circle cx="12" cy="8" r="4" {...stroke} />
            <path
                d="M4 20c0-4 4-6 8-6s8 2 8 6"
                {...stroke}
                strokeLinecap="round"
            />
        </>
    ),
};

const PHONE: Record<AuditorTab, ReactNode> = {
    home: (
        <path
            d="M4 11l8-6 8 6v8a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1z"
            {...phoneStroke}
            strokeLinejoin="round"
        />
    ),
    jobs: (
        <>
            <path
                d="M12 3a6 6 0 0 0-6 6c0 5-2 6-2 6h16s-2-1-2-6a6 6 0 0 0-6-6z"
                {...phoneStroke}
                strokeLinejoin="round"
            />
            <path
                d="M10.5 20a1.8 1.8 0 0 0 3 0"
                {...phoneStroke}
                strokeLinecap="round"
            />
        </>
    ),
    portfolio: (
        <>
            <rect x="4" y="5" width="16" height="15" rx="2" {...phoneStroke} />
            <path
                d="M8 3v4M16 3v4M8 11h8M8 15h5"
                {...phoneStroke}
                strokeLinecap="round"
            />
        </>
    ),
    profile: (
        <>
            <circle cx="12" cy="8" r="3.4" {...phoneStroke} />
            <path
                d="M5.5 20a6.5 6.5 0 0 1 13 0"
                {...phoneStroke}
                strokeLinecap="round"
            />
        </>
    ),
};

type AuditorShellProps = {
    title: string;
    tab: AuditorTab;
    links: AuditorAppLinks;
    /** Eligible Flash Audits, shown as the phone Jobs tab's count badge (design L1911). */
    openJobs: number;
    showTabBar?: boolean;
    /** What the page reads in the background, and the deadlines it reads again at. */
    refresh?: AuditorRefresh;
    children: ReactNode;
};

/**
 * The Auditor app frame. All four design tabs ship: Portfolio carries the partner's filed reports
 * (MVP-AUDITOR-SCR-07) and conflict register (AC-08); its earnings, origination and managed-deal
 * panels are Phase 2 and are left out rather than shipped as dead ends. A tab the server sends
 * without a route is left out too.
 *
 * Every Auditor page reconciles its access here: on focus, reconnect or a return to the tab it
 * reads its current facts once, so authority withdrawn meanwhile — by another tab switching the
 * active role or by an operator — lands on the page the server now renders. A page with deadlines
 * on screen also reads once each passes (`useAuditorRefresh`). A read waits while a command is in
 * flight or held for its lookup, and runs once it settles. A fixture preview reloads its own
 * fixture, which changes nothing.
 */
export function AuditorShell({
    title,
    tab,
    links,
    openJobs,
    showTabBar,
    refresh = {},
    children,
}: AuditorShellProps) {
    const { t } = useTranslation();

    useAuditorRefresh({ ...refresh, settled: useCommandsSettled() });
    const nav = (['home', 'jobs', 'portfolio', 'profile'] as const).flatMap(
        (key) => {
            const href = links[key];

            /* A tab whose route is not published yet is left out, never a dead link. */
            return href === null
                ? []
                : [
                      {
                          key,
                          label: t(`auditor.nav.${key}`),
                          href,
                          glyph: (
                              <>
                                  <Glyph className="max-lg:hidden">
                                      {DESKTOP[key]}
                                  </Glyph>
                                  <span className="relative flex lg:hidden">
                                      <Glyph className="size-[22px]">
                                          {PHONE[key]}
                                      </Glyph>
                                      {key === 'jobs' && openJobs > 0 && (
                                          <span
                                              aria-label={t(
                                                  'auditor.nav.jobs_badge',
                                                  {
                                                      count: openJobs,
                                                  },
                                              )}
                                              className="absolute -top-[5px] -right-[9px] flex h-4 min-w-4 items-center justify-center rounded-[10px] bg-rz-accent-fill px-1 text-[10.5px] font-bold text-white"
                                          >
                                              {openJobs}
                                          </span>
                                      )}
                                  </span>
                              </>
                          ),
                      },
                  ];
        },
    );

    return (
        <AppFrame
            audience="auditor"
            nav={nav}
            active={tab}
            launcher={links.launcher}
            showTabBar={showTabBar}
        >
            <Head title={title} />
            <AuditorKeyframes />
            <div className="min-h-svh bg-[#f6f8fb] lg:h-full lg:min-h-0 dark:bg-rz-page">
                {children}
            </div>
        </AppFrame>
    );
}
