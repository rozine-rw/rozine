import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { InfoTip } from '@/components/admin/ui';
import { ConnectivityNotice } from '@/components/rozine/connectivity-notice';
import { IconGradients } from '@/components/rozine/icon';
import { LogoLockup } from '@/components/rozine/logo';
import { useChangeBeacon } from '@/hooks/use-change-beacon';
import type { ChangeBeacon } from '@/hooks/use-change-beacon';
import { useReconnectRefresh } from '@/hooks/use-reconnect-refresh';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import type { AdminFrameSection, AdminFrameShellProps } from '@/types/admin';

/**
 * The design's 16px sidebar glyphs (S5233–5257), drawn in `currentColor` at stroke 1.8. The Phase 2
 * sections have no design glyph; theirs follow the same grid and stroke.
 */
const GLYPHS: Record<AdminFrameSection, ReactNode> = {
    notes: (
        <>
            <rect x="5" y="3" width="14" height="18" rx="2.5" />
            <path d="M9 8h6M9 12h6M9 16h6" strokeLinecap="round" />
        </>
    ),
    primary_market: (
        <path
            d="M3 17l5-5 4 3 8-8"
            strokeLinecap="round"
            strokeLinejoin="round"
        />
    ),
    secondary_market: (
        <path d="M4 7h16M4 12h16M4 17h16" strokeLinecap="round" />
    ),
    risk: (
        <>
            <path
                d="M12 3 20 6v5c0 5-4 8-8 10-4-2-8-5-8-10V6Z"
                strokeLinejoin="round"
            />
            <path d="M12 8.5v4M12 15.5v.5" strokeLinecap="round" />
        </>
    ),
    compliance: (
        <>
            <path
                d="M12 3 20 6v5c0 5-4 8-8 10-4-2-8-5-8-10V6Z"
                strokeLinejoin="round"
            />
            <path
                d="M9 12l2 2 4-4"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </>
    ),
    payments: (
        <>
            <rect x="3" y="6" width="18" height="12" rx="2.5" />
            <path d="M3 10h18" />
        </>
    ),
    ratings: (
        <path
            d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"
            strokeLinejoin="round"
        />
    ),
    deferrals: (
        <>
            <path d="M4 12a8 8 0 1 0 2.3-5.6" strokeLinecap="round" />
            <path d="M4 4v4h4" strokeLinecap="round" strokeLinejoin="round" />
        </>
    ),
    plus: (
        <path
            d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"
            strokeLinejoin="round"
        />
    ),
    finance: (
        <path
            d="M12 3v18M8 7h6a3 3 0 0 1 0 6H9a3 3 0 0 0 0 6h7"
            strokeLinecap="round"
        />
    ),
    messaging: (
        <>
            <path d="M4 5h16v11H9l-5 4Z" strokeLinejoin="round" />
            <path d="M8 9h8M8 12h5" strokeLinecap="round" />
        </>
    ),
    academies: (
        <>
            <path d="M2 9l10-5 10 5-10 5Z" strokeLinejoin="round" />
            <path d="M6 11v5c3 2 9 2 12 0v-5" strokeLinecap="round" />
        </>
    ),
    app_control: (
        <>
            <path d="M4 7h16M4 12h16M4 17h16" strokeLinecap="round" />
            <circle cx="9" cy="7" r="1.8" />
            <circle cx="15" cy="12" r="1.8" />
            <circle cx="8" cy="17" r="1.8" />
        </>
    ),
    policies: (
        <>
            <circle cx="12" cy="12" r="3.5" />
            <path
                d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"
                strokeLinecap="round"
            />
        </>
    ),
    system_health: (
        <path
            d="M3 12h4l2-5 4 10 2-5h6"
            strokeLinecap="round"
            strokeLinejoin="round"
        />
    ),
    today: (
        <>
            <rect x="3" y="3" width="8" height="8" rx="2" />
            <rect x="13" y="3" width="8" height="5" rx="2" />
            <rect x="13" y="10" width="8" height="11" rx="2" />
            <rect x="3" y="13" width="8" height="8" rx="2" />
        </>
    ),
    businesses: (
        <>
            <rect x="4" y="4" width="9" height="16" rx="1.5" />
            <path d="M13 9h7v11h-7" />
        </>
    ),
    investors: (
        <>
            <circle cx="9" cy="8" r="3.2" />
            <path d="M3 19c0-3 3-4.5 6-4.5s6 1.5 6 4.5" />
            <path d="M16 6a3 3 0 0 1 0 6M18 19c0-2-1-3.3-2.5-4" />
        </>
    ),
    auditors: (
        <>
            <path
                d="M12 3 20 6v5c0 5-4 8-8 10-4-2-8-5-8-10V6Z"
                strokeLinejoin="round"
            />
            <path
                d="M9.5 11.5l2 2 3.5-3.6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </>
    ),
    applications: (
        <>
            <path d="M9 7h6M9 11h6M9 15h3" strokeLinecap="round" />
            <rect x="5" y="3" width="14" height="18" rx="2.5" />
        </>
    ),
    disbursements: (
        <>
            <rect x="3" y="6" width="18" height="12" rx="2.5" />
            <path d="M3 10h18" />
        </>
    ),
    repayments: (
        <>
            <rect x="3" y="6" width="18" height="12" rx="2.5" />
            <path
                d="M8 12h8M13 9l3 3-3 3"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </>
    ),
    book: (
        <>
            <rect x="4" y="4" width="16" height="16" rx="2.5" />
            <path d="M8 15v-3M12 15V9M16 15v-5" strokeLinecap="round" />
        </>
    ),
    exceptions: (
        <>
            <path d="M12 4 21 19H3Z" strokeLinejoin="round" />
            <path d="M12 10v4M12 16.5v.5" strokeLinecap="round" />
        </>
    ),
    reconciliation: (
        <>
            <path d="M12 4v16M5 20h14" strokeLinecap="round" />
            <path
                d="M4 8h16M6.5 8 4 14h5ZM17.5 8 15 14h5Z"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </>
    ),
    coverage: (
        <>
            <path
                d="M12 21s6-5.6 6-10.5a6 6 0 0 0-12 0C6 15.4 12 21 12 21Z"
                strokeLinejoin="round"
            />
            <circle cx="12" cy="10.5" r="2.2" />
        </>
    ),
    mail_testers: (
        <>
            <rect x="3" y="5" width="18" height="14" rx="2.5" />
            <path
                d="m4 7 8 6 8-6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </>
    ),
    reports: (
        <>
            <path d="M6 3h8l4 4v14H6Z" strokeLinejoin="round" />
            <path
                d="M14 3v4h4M9 17v-3M12 17v-5M15 17v-2"
                strokeLinecap="round"
            />
        </>
    ),
    ledger: (
        <path
            d="M12 3v18M8 7h6a3 3 0 0 1 0 6H9a3 3 0 0 0 0 6h7"
            strokeLinecap="round"
        />
    ),
    staff: (
        <>
            <rect x="3" y="4" width="18" height="16" rx="2.5" />
            <circle cx="9" cy="10" r="2.2" />
            <path
                d="M5.5 16c.6-1.7 2-2.4 3.5-2.4s2.9.7 3.5 2.4M15 9h4M15 13h3"
                strokeLinecap="round"
            />
        </>
    ),
    events: (
        <>
            <rect x="5" y="3" width="14" height="18" rx="2" />
            <path d="M9 8h6M9 12h6M9 16h4" strokeLinecap="round" />
        </>
    ),
};

/**
 * The console's navigation, in the design's groups and order. Sections whose screens are not wired
 * yet still appear: their link opens the frame with an empty state. Pages the design's sidebar does
 * not name light the entry they sit under (see LIT_BY): Payments for disbursements, repayments and
 * the ledger; Notes for the book; Risk Center for exceptions; Finance for reconciliation; Auditors
 * for partner coverage. Staging mail testers is a staging-only tool the server sends to whoever may
 * manage it.
 */
const GROUPS: {
    group:
        | 'accounts'
        | 'capital'
        | 'oversight'
        | 'treasury'
        | 'engagement'
        | 'console'
        | null;
    items: AdminFrameSection[];
}[] = [
    { group: null, items: ['today'] },
    { group: 'accounts', items: ['businesses', 'investors', 'auditors'] },
    {
        group: 'capital',
        items: ['applications', 'notes', 'primary_market', 'secondary_market'],
    },
    { group: 'oversight', items: ['reports', 'risk', 'compliance'] },
    {
        group: 'treasury',
        items: ['payments', 'ratings', 'deferrals', 'plus', 'finance'],
    },
    { group: 'engagement', items: ['messaging', 'academies'] },
    {
        group: 'console',
        items: [
            'app_control',
            'policies',
            'staff',
            'events',
            'system_health',
            'mail_testers',
        ],
    },
];

/** Pages that sit under a sidebar entry with another name. */
const LIT_BY: Partial<Record<AdminFrameSection, AdminFrameSection>> = {
    disbursements: 'payments',
    repayments: 'payments',
    ledger: 'payments',
    book: 'notes',
    exceptions: 'risk',
    reconciliation: 'finance',
    coverage: 'auditors',
};

type AdminFrameProps = AdminFrameShellProps & {
    section: AdminFrameSection;
    /**
     * How the page search reaches the server: the query parameter it fills (`q` by default) and
     * the parameters a new search clears, such as a paging cursor or an open drawer.
     */
    searchQuery?: { param: string; clears: string[] };
    /** A drawer over the content column. */
    overlay?: ReactNode;
    /** The live page's change beacon; a preview passes none, so nothing polls. */
    beacon?: ChangeBeacon;
    children: ReactNode;
};

/**
 * The Admin console frame (design T87–182): a 216px grouped sidebar on its soft gradient, a 54px
 * glass top bar with the section title, its ⓘ, the page search and the operator menu, and the
 * content column that anchors every drawer. It fills the window up to the desktop cap and matches
 * the design's 1113×750 frame at that size. On a phone the sidebar becomes a menu sheet.
 */
export function AdminFrame({
    section,
    viewer,
    nav,
    badges,
    search,
    searchQuery,
    overlay,
    beacon,
    children,
}: AdminFrameProps) {
    const { t } = useTranslation();
    useChangeBeacon(beacon);
    const [menuOpen, setMenuOpen] = useState(false);
    const [accountOpen, setAccountOpen] = useState(false);
    useReconnectRefresh();
    const title = t(`admin.section.${section}.title`);

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const q = new FormData(event.currentTarget).get('q');

        if (searchQuery === undefined) {
            router.reload({ data: { q } });

            return;
        }

        router.reload({
            data: Object.fromEntries([
                [searchQuery.param, q],
                ...searchQuery.clears.map((key) => [key, undefined]),
            ]),
        });
    };

    const sidebar = (
        <>
            <div className="flex items-center gap-[11px] border-b border-[#eef2f8] px-[18px] py-[17px] dark:border-[rgba(255,255,255,.07)]">
                <LogoLockup
                    title={t('common.brand.name')}
                    className="block h-auto w-[83px] text-rz-accent-lockup"
                />
            </div>
            <nav
                aria-label={t('admin.nav.label')}
                className="rz-scroll min-h-0 flex-1 overflow-y-auto px-3 pt-3.5 pb-[22px]"
            >
                {GROUPS.map(({ group, items }) => {
                    /*
                     * A live Resource sends only the destinations it serves, and an optional
                     * section may be absent altogether; a group with none is left out.
                     */
                    const served = items.flatMap((key) => {
                        const href = nav[key];

                        return href === null || href === undefined
                            ? []
                            : [{ key, href }];
                    });

                    if (served.length === 0) {
                        return null;
                    }

                    return (
                        <div key={group ?? 'home'}>
                            {group !== null && (
                                <div className="px-3 pt-5 pb-[7px] text-[9.5px] font-extrabold tracking-[.15em] text-rz-group uppercase">
                                    {t(`admin.nav.group.${group}`)}
                                </div>
                            )}
                            {served.map(({ key, href }) => {
                                const active =
                                    key === (LIT_BY[section] ?? section);
                                const badge =
                                    key === 'applications'
                                        ? (badges.applications ?? 0)
                                        : key === 'payments'
                                          ? (badges.disbursements ?? 0)
                                          : 0;

                                return (
                                    <Link
                                        key={key}
                                        href={href}
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                        className={cn(
                                            'relative mb-[3px] flex w-full items-center gap-[11px] rounded-[11px] py-2.5 pr-3 pl-[13px] text-left text-[13.5px] transition-[background,color] duration-100',
                                            active
                                                ? 'bg-[linear-gradient(90deg,rgba(30,58,255,.13),rgba(91,43,217,.07))] font-bold text-[#1e3aff] dark:bg-[linear-gradient(90deg,rgba(30,58,255,.32),rgba(30,58,255,.12))] dark:text-white'
                                                : 'font-medium text-[#8c97ad] hover:bg-[rgba(30,58,255,.04)] dark:text-[#93a1bd]',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'absolute top-2 bottom-2 left-0 w-[3px] rounded-[3px]',
                                                active &&
                                                    'bg-[#1e3aff] dark:bg-[#5b74ff]',
                                            )}
                                        />
                                        <span
                                            className={cn(
                                                'flex w-[18px] justify-center',
                                                active
                                                    ? 'text-[#1e3aff] dark:text-[#99a3ff]'
                                                    : 'text-[#8c97ad] dark:text-[#6b7a99]',
                                            )}
                                        >
                                            <svg
                                                width="16"
                                                height="16"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="1.8"
                                                aria-hidden
                                            >
                                                {GLYPHS[key]}
                                            </svg>
                                        </span>
                                        <span className="min-w-0 flex-1 truncate">
                                            {t(`admin.nav.${key}`)}
                                        </span>
                                        {badge > 0 && (
                                            <span className="rounded-[20px] bg-[#1e3aff] px-[7px] py-0.5 text-[10px] font-semibold text-white dark:bg-[#3d57ff]">
                                                {badge}
                                            </span>
                                        )}
                                    </Link>
                                );
                            })}
                        </div>
                    );
                })}
            </nav>
            <Link
                href={nav.launcher}
                className="m-3 flex items-center gap-[9px] rounded-[10px] border border-rz-hairline bg-white px-3 py-[11px] text-[13px] font-semibold text-rz-body dark:border-[rgba(255,255,255,.1)] dark:bg-[rgba(255,255,255,.05)] dark:text-[#93a1bd]"
            >
                {t('admin.nav.all_apps')}
            </Link>
        </>
    );

    return (
        <div
            data-audience="admin"
            className="rz-surface min-h-svh bg-rz-page-console lg:bg-rz-frame"
        >
            <Head title={title} />
            <IconGradients />
            <ConnectivityNotice />
            <div className="relative lg:mx-auto lg:flex lg:h-svh lg:min-h-[640px] lg:max-w-[var(--rz-desktop-max)] lg:overflow-hidden lg:bg-rz-page-console">
                <aside className="hidden h-full w-[216px] shrink-0 flex-col border-r border-[#e8eef7] bg-[linear-gradient(190deg,#ffffff_0%,#f3f6fd_55%,#eef3fc_100%)] lg:flex dark:border-[#0c1830] dark:bg-[linear-gradient(185deg,#0e1b34,#0a1428_60%,#0b1730)]">
                    {sidebar}
                </aside>

                {menuOpen && (
                    <div className="lg:hidden">
                        <button
                            type="button"
                            aria-label={t('admin.nav.close_menu')}
                            onClick={() => setMenuOpen(false)}
                            className="fixed inset-0 z-[60] animate-[rz-scrim_.18s_ease] bg-[rgba(8,14,28,.34)]"
                        />
                        <aside className="fixed inset-y-0 left-0 z-[61] flex w-[264px] max-w-[85vw] flex-col bg-[linear-gradient(190deg,#ffffff_0%,#f3f6fd_55%,#eef3fc_100%)] shadow-rz-lifted dark:bg-[linear-gradient(185deg,#0e1b34,#0a1428_60%,#0b1730)]">
                            {sidebar}
                        </aside>
                    </div>
                )}

                <div className="flex min-w-0 flex-1 flex-col lg:min-h-0">
                    <header className="sticky top-0 z-10 flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2.5 border-b border-[rgba(226,233,245,.75)] bg-[rgba(255,255,255,.72)] px-4 py-2.5 backdrop-blur-[10px] lg:h-[54px] lg:flex-nowrap lg:px-[22px] lg:py-0 dark:border-[rgba(255,255,255,.07)] dark:bg-[rgba(10,18,32,.85)]">
                        <div className="flex min-w-0 flex-1 items-center gap-2">
                            <button
                                type="button"
                                aria-label={t('admin.nav.open_menu')}
                                aria-expanded={menuOpen}
                                onClick={() => setMenuOpen(true)}
                                className="-ml-1 flex size-9 shrink-0 items-center justify-center rounded-[10px] text-rz-slate lg:hidden"
                            >
                                <svg
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden
                                >
                                    <path
                                        d="M4 7h16M4 12h16M4 17h16"
                                        stroke="currentColor"
                                        strokeWidth="1.8"
                                        strokeLinecap="round"
                                    />
                                </svg>
                            </button>
                            <h1 className="truncate text-[15px] font-bold tracking-[-.01em] text-rz-ink">
                                {title}
                            </h1>
                            <InfoTip
                                label={t('admin.section.about', { title })}
                            >
                                {t(`admin.section.${section}.subtitle`)}
                            </InfoTip>
                        </div>
                        <form
                            role="search"
                            onSubmit={submitSearch}
                            className="order-3 flex w-full items-center gap-2 rounded-[10px] border border-rz-hairline bg-rz-surface px-3 py-2 lg:order-none lg:w-[280px]"
                        >
                            <svg
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden
                            >
                                <circle
                                    cx="11"
                                    cy="11"
                                    r="7"
                                    stroke="#8a94a8"
                                    strokeWidth="2"
                                />
                                <path
                                    d="m20 20-3-3"
                                    stroke="#8a94a8"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                />
                            </svg>
                            <input
                                type="search"
                                name="q"
                                defaultValue={search}
                                aria-label={t('admin.search.label')}
                                placeholder={t(
                                    `admin.section.${section}.search`,
                                )}
                                className="min-w-0 flex-1 border-none bg-transparent text-[13px] text-rz-ink outline-none placeholder:text-rz-muted"
                            />
                        </form>
                        <div className="relative">
                            <button
                                type="button"
                                aria-label={t('admin.account.open', {
                                    name: viewer.name,
                                })}
                                aria-expanded={accountOpen}
                                onClick={() => setAccountOpen((open) => !open)}
                                className="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#1e3aff] text-[13px] font-semibold text-white dark:bg-[#3d57ff]"
                            >
                                {viewer.initials}
                            </button>
                            {accountOpen && (
                                <>
                                    <button
                                        type="button"
                                        tabIndex={-1}
                                        aria-label={t('admin.account.close')}
                                        onClick={() => setAccountOpen(false)}
                                        className="fixed inset-0 z-40 cursor-default"
                                    />
                                    <div className="absolute top-[46px] right-0 z-[41] w-[264px] overflow-hidden rounded-[18px] border border-[#edf1f8] bg-rz-surface shadow-[0_20px_48px_-16px_rgba(16,32,58,.32)] dark:border-rz-border">
                                        <div className="flex items-center gap-3 border-b border-[#eef1f7] px-[17px] py-4 dark:border-rz-divider">
                                            <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#1e3aff] text-[15px] font-bold text-white dark:bg-[#3d57ff]">
                                                {viewer.initials}
                                            </div>
                                            <div className="min-w-0">
                                                <div className="text-[14px] font-bold text-rz-ink">
                                                    {viewer.name}
                                                </div>
                                                <div className="truncate text-[11.5px] text-[#8895ab] dark:text-rz-muted">
                                                    {viewer.email}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2 px-[17px] py-2.5">
                                            <span className="size-[7px] rounded-full bg-[#1d9e75]" />
                                            <span className="text-[11.5px] font-semibold text-[#5a6a86] dark:text-rz-secondary">
                                                {t(`admin.role.${viewer.role}`)}
                                            </span>
                                            <span className="ml-auto rounded-md bg-[rgba(29,158,117,.12)] px-2 py-0.5 text-[10px] font-bold text-[#1d9e75] dark:text-[#3fcda0]">
                                                {t(
                                                    `admin.role.access.${viewer.role}`,
                                                )}
                                            </span>
                                        </div>
                                        <div className="border-t border-[#eef1f7] p-1.5 dark:border-rz-divider">
                                            <Link
                                                href={logout()}
                                                as="button"
                                                className="flex w-full items-center gap-[11px] rounded-[10px] px-[11px] py-2.5 text-left text-[13px] font-semibold text-[#e5484d] hover:bg-[rgba(255,77,79,.07)] dark:text-[#ff6b6f]"
                                            >
                                                <svg
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden
                                                >
                                                    <path
                                                        d="M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3M10 8l-4 4 4 4M6 12h11"
                                                        stroke="currentColor"
                                                        strokeWidth="1.8"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                    />
                                                </svg>
                                                {t('admin.account.sign_out')}
                                            </Link>
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>
                    </header>

                    <div className="relative flex flex-col lg:min-h-0 lg:flex-1">
                        <main className="rz-scroll px-4 pt-5 pb-10 lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:px-7 lg:pt-[26px] lg:pb-[34px]">
                            {children}
                        </main>
                        {overlay}
                    </div>
                </div>
            </div>
        </div>
    );
}
