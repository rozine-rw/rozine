import { Link } from '@inertiajs/react';
import { initialOf } from '@/components/admin/format';
import { CAPTION, CARD_SHADOW } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RoleApplicationQueue } from '@/types/role-application';

export const APPLICATION_TABS = [
    'submitted',
    'changes_requested',
    'approved',
    'rejected',
] as const;

const ACCENT = { business: '#17795a', auditor: '#a3480a' } as const;

/**
 * The applications to switch to this page's role (Erastus, 23 Nov 2026): business KYB on the
 * Businesses page and Auditor licences on the Auditors page, so each is decided in one place. The
 * tabs filter by status on the server; Review opens the application in the drawer.
 */
export function RoleApplicationQueuePanel({
    queue,
    path,
}: {
    queue: RoleApplicationQueue;
    path: string;
}) {
    const { t, locale } = useTranslation();
    const accent = ACCENT[queue.role];
    const href = (data: Record<string, string>) =>
        `${path}?${new URLSearchParams(data).toString()}`;

    return (
        <section
            aria-label={t(`admin.role_applications.${queue.role}.title`)}
            className="mb-6"
        >
            <h2 className="mb-1 text-[15px] font-semibold text-rz-ink">
                {t(`admin.role_applications.${queue.role}.title`)}
            </h2>
            <p className={cn('mb-3 text-[12px]', CAPTION)}>
                {t(`admin.role_applications.${queue.role}.caption`)}
            </p>
            <nav
                aria-label={t('admin.role_applications.tabs')}
                className="mb-3 flex flex-wrap gap-2"
            >
                {APPLICATION_TABS.map((tab) => (
                    <Link
                        key={tab}
                        href={href({ kyb: tab })}
                        preserveScroll
                        aria-current={queue.tab === tab ? 'page' : undefined}
                        className={cn(
                            'rounded-full border px-3 py-1.5 text-[12px] font-semibold',
                            queue.tab === tab
                                ? 'border-transparent bg-rz-ink text-rz-surface'
                                : 'border-rz-hairline bg-rz-surface text-rz-slate',
                        )}
                    >
                        {t(`admin.role_applications.tab.${tab}`)}
                        <span className="ml-1.5 opacity-70">
                            {queue.counts[tab] ?? 0}
                        </span>
                    </Link>
                ))}
            </nav>
            {queue.entries.length === 0 ? (
                <div
                    className={cn(
                        'rounded-[18px] border border-rz-hairline bg-rz-surface p-7 text-center text-[13px] text-rz-muted',
                        CARD_SHADOW,
                    )}
                >
                    {t(`admin.role_applications.empty.${queue.tab}`)}
                </div>
            ) : (
                <ul className="flex flex-col gap-3">
                    {queue.entries.map((entry) => (
                        <li
                            key={entry.id}
                            className={cn(
                                'rounded-2xl border border-l-4 border-rz-hairline bg-rz-surface px-[18px] py-4',
                                CARD_SHADOW,
                            )}
                            style={{ borderLeftColor: accent }}
                        >
                            <div className="flex flex-wrap items-center gap-3">
                                <div
                                    aria-hidden="true"
                                    className="flex size-[42px] shrink-0 items-center justify-center rounded-xl text-[16px] font-bold text-white"
                                    style={{ background: accent }}
                                >
                                    {initialOf(entry.company ?? entry.name) ||
                                        '?'}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-[14.5px] font-bold text-rz-ink">
                                        {entry.company ?? entry.name}
                                    </p>
                                    <p className="truncate text-[12px] text-rz-muted">
                                        {entry.company !== null
                                            ? `${entry.name} · RDB ${entry.company_code ?? ''}`
                                            : entry.email}
                                    </p>
                                </div>
                                <span
                                    className={cn(
                                        'rounded-md px-2 py-0.5 text-[10.5px] font-bold whitespace-nowrap',
                                        entry.kyc === 'approved'
                                            ? 'bg-[rgba(29,158,117,.12)] text-[#17795a] dark:text-[#5fd3a5]'
                                            : 'bg-[rgba(194,102,31,.12)] text-[#a3480a] dark:text-[#f0a060]',
                                    )}
                                >
                                    {t(
                                        entry.kyc === 'approved'
                                            ? 'admin.role_applications.kyc_approved'
                                            : 'admin.role_applications.kyc_pending',
                                    )}
                                </span>
                                <Link
                                    href={href({
                                        kyb: queue.tab,
                                        application: entry.id,
                                    })}
                                    preserveScroll
                                    className="w-full rounded-[10px] px-4 py-[9px] text-center text-[12.5px] font-bold text-white sm:w-auto"
                                    style={{ background: accent }}
                                >
                                    {t('admin.role_applications.review')}
                                </Link>
                            </div>
                            {entry.submitted_at !== null && (
                                <p className="mt-2 text-[11.5px] text-rz-muted">
                                    {t('admin.role_applications.sent_on', {
                                        date: formatDate(
                                            entry.submitted_at,
                                            locale,
                                        ),
                                    })}
                                </p>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
