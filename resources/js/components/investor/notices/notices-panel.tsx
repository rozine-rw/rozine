import { router, useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatDate,
    formatDateTime,
    formatMonthYearLong,
    formatRwf,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { InvestorNotice, InvestorNotices } from '@/types/investor';
import type { RouteAction, RouteLink } from '@/types/routing';

type Tone = { bar: string; tile: string; icon: ReactNode };

const stroke = {
    stroke: 'currentColor',
    strokeWidth: 1.9,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
} as const;

/** The design's tile per kind (Investor notifications panel): a coloured bar, tile and glyph. */
const TONES: Record<InvestorNotice['kind'], Tone> = {
    'wallet.deposit_received': {
        bar: 'bg-[#17795a]',
        tile: 'bg-[rgba(29,158,117,.10)] text-[#17795a]',
        icon: <path d="M12 5v14M5 12l7 7 7-7" {...stroke} />,
    },
    'wallet.withdrawal_sent': {
        bar: 'bg-[#0c1830] dark:bg-rz-slate',
        tile: 'bg-[rgba(12,24,48,.08)] text-[#0c1830] dark:text-rz-ink',
        icon: <path d="M12 19V5M5 12l7-7 7 7" {...stroke} />,
    },
    'wallet.withdrawal_returned': {
        bar: 'bg-[#b07a12]',
        tile: 'bg-[rgba(194,102,31,.10)] text-[#a55418]',
        icon: (
            <path
                d="M9 14 4 9l5-5M4 9h10.5a5.5 5.5 0 0 1 0 11H11"
                {...stroke}
            />
        ),
    },
    'primary.refunded': {
        bar: 'bg-[#17795a]',
        tile: 'bg-[rgba(29,158,117,.10)] text-[#17795a]',
        icon: (
            <path
                d="M9 14 4 9l5-5M4 9h10.5a5.5 5.5 0 0 1 0 11H11"
                {...stroke}
            />
        ),
    },
    'primary.report_published': {
        bar: 'bg-[#17795a]',
        tile: 'bg-[rgba(29,158,117,.10)] text-[#17795a]',
        icon: (
            <path
                d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6zM9 12l2 2 4-4.5"
                {...stroke}
            />
        ),
    },
    'verification.approved': {
        bar: 'bg-[#1e3aff]',
        tile: 'bg-[rgba(30,58,255,.1)] text-[#1e3aff]',
        icon: <path d="m5 12.5 4.5 4.5L19 7.5" {...stroke} />,
    },
    'security.password_changed': {
        bar: 'bg-[#b07a12]',
        tile: 'bg-[rgba(176,122,18,.12)] text-[#b07a12]',
        icon: (
            <>
                <rect x="5" y="11" width="14" height="9" rx="2" {...stroke} />
                <path d="M8 11V8a4 4 0 0 1 8 0v3" {...stroke} />
            </>
        ),
    },
    'security.new_device': {
        bar: 'bg-[#b07a12]',
        tile: 'bg-[rgba(176,122,18,.12)] text-[#b07a12]',
        icon: (
            <>
                <rect x="4" y="5" width="16" height="11" rx="2" {...stroke} />
                <path d="M9 20h6M12 16v4" {...stroke} />
            </>
        ),
    },
    'security.payout_account_linked': {
        bar: 'bg-[#b07a12]',
        tile: 'bg-[rgba(176,122,18,.12)] text-[#b07a12]',
        icon: <path d="M4 9h16M4 15h16M7 5v14M17 5v14" {...stroke} />,
    },
    'security.payout_account_unlinked': {
        bar: 'bg-[#b07a12]',
        tile: 'bg-[rgba(176,122,18,.12)] text-[#b07a12]',
        icon: <path d="M5 12h14" {...stroke} />,
    },
    'verification.rejected': {
        bar: 'bg-[#b3383c]',
        tile: 'bg-[rgba(179,56,60,.1)] text-[#b3383c]',
        icon: <path d="M12 7v6M12 16.5v.5" {...stroke} />,
    },
    'business.deposit_received': {
        bar: 'bg-[#17795a]',
        tile: 'bg-[rgba(29,158,117,.10)] text-[#17795a]',
        icon: <path d="M12 5v14M5 12l7 7 7-7" {...stroke} />,
    },
    'business.application_approved': {
        bar: 'bg-[#17795a]',
        tile: 'bg-[rgba(29,158,117,.10)] text-[#17795a]',
        icon: <path d="M5 12.5l4.5 4.5L19 7.5" {...stroke} />,
    },
    'business.withdrawal_sent': {
        bar: 'bg-[#0c1830] dark:bg-rz-slate',
        tile: 'bg-[rgba(12,24,48,.08)] text-[#0c1830] dark:text-rz-ink',
        icon: <path d="M12 19V5M5 12l7-7 7 7" {...stroke} />,
    },
    'business.withdrawal_returned': {
        bar: 'bg-[#b07a12]',
        tile: 'bg-[rgba(194,102,31,.10)] text-[#a55418]',
        icon: (
            <path
                d="M9 14 4 9l5-5M4 9h10.5a5.5 5.5 0 0 1 0 11H11"
                {...stroke}
            />
        ),
    },
    'business.report_sealed': {
        bar: 'bg-[#1e3aff]',
        tile: 'bg-[rgba(30,58,255,.1)] text-[#1e3aff]',
        icon: (
            <>
                <rect
                    x="5"
                    y="3.5"
                    width="14"
                    height="17"
                    rx="2.4"
                    {...stroke}
                />
                <path d="M8.6 9h6.8M8.6 13h6.8M8.6 17h4" {...stroke} />
            </>
        ),
    },
    'business.report_review_closing': {
        bar: 'bg-[#b07a12]',
        tile: 'bg-[rgba(176,122,18,.12)] text-[#b07a12]',
        icon: (
            <>
                <circle cx="12" cy="12" r="8" {...stroke} />
                <path d="M12 8v4l2.5 2.5" {...stroke} />
            </>
        ),
    },
    'auditor.accreditation_approved': {
        bar: 'bg-[#1e3aff]',
        tile: 'bg-[rgba(30,58,255,.1)] text-[#1e3aff]',
        icon: <path d="m5 12.5 4.5 4.5L19 7.5" {...stroke} />,
    },
    'auditor.accreditation_rejected': {
        bar: 'bg-[#b3383c]',
        tile: 'bg-[rgba(179,56,60,.1)] text-[#b3383c]',
        icon: <path d="M12 7v6M12 16.5v.5" {...stroke} />,
    },
    'auditor.job_offered': {
        bar: 'bg-[#0c1830] dark:bg-rz-slate',
        tile: 'bg-[rgba(194,102,31,.10)] text-[#a55418]',
        icon: (
            <>
                <path
                    d="M12 21s-6-5.3-6-10a6 6 0 0 1 12 0c0 4.7-6 10-6 10Z"
                    {...stroke}
                />
                <circle cx="12" cy="11" r="2.2" {...stroke} />
            </>
        ),
    },
    'business.raise_expired': {
        bar: 'bg-[#b07a12]',
        tile: 'bg-[rgba(176,122,18,.12)] text-[#b07a12]',
        icon: (
            <>
                <circle cx="12" cy="12" r="8" {...stroke} />
                <path d="M12 8v4l2.5 2.5" {...stroke} />
            </>
        ),
    },
};

/** The account's notices, loaded when the bell opens them and again after a retry or a read. */
export function useNotices(href: RouteLink) {
    const http = useHttp<Record<string, never>, { data: InvestorNotices }>();
    const [inbox, setInbox] = useState<InvestorNotices | null>(null);
    const [failed, setFailed] = useState(false);

    const load = async () => {
        setFailed(false);

        try {
            const answer = await http.submit(href, {});

            setInbox(answer.data);
        } catch {
            setFailed(true);
        }
    };

    const send = async (action: RouteAction) => {
        try {
            await http.submit(action, {});
        } catch {
            /* A read that did not land stays unread; the notice still opens. */
        }
    };

    return { inbox, failed, load, send };
}

/**
 * The bell's slide-in notices panel (design Investor notifications panel): newest first, an
 * unread bar and dot, and "Mark all read". Each notice opens the screen that already holds its
 * record, marking it read on the way. The notices are the server's; wording comes from the
 * catalogs by kind.
 */
export function NoticesPanel({
    notices: { inbox, failed, load, send },
    onClose,
}: {
    notices: ReturnType<typeof useNotices>;
    onClose: () => void;
}) {
    const { t, locale } = useTranslation();

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [onClose]);

    const open = async (notice: InvestorNotice) => {
        if (notice.read_action !== null) {
            await send(notice.read_action);
        }

        if (notice.link !== null) {
            router.visit(notice.link);
        }
    };

    const readAll = async (action: RouteAction) => {
        await send(action);
        router.reload({ only: ['unread_notifications'] });
        await load();
    };

    return (
        <>
            <button
                type="button"
                aria-hidden
                tabIndex={-1}
                data-testid="notices-scrim"
                onClick={onClose}
                className="fixed inset-0 z-[70] animate-[rz-scrim_.22s_ease_both] bg-[rgba(10,15,28,.32)]"
            />
            <section
                role="dialog"
                aria-modal="true"
                aria-label={t('investor.notices.title')}
                className="dark:bg-rz-bg fixed inset-y-0 right-0 z-[71] flex w-[88%] max-w-[352px] animate-[rz-slide_.28s_cubic-bezier(.22,.61,.36,1)_both] flex-col bg-[#f4f7fc] shadow-[-18px_0_44px_-10px_rgba(20,45,95,.34)] lg:w-[380px] lg:max-w-none"
            >
                <header className="flex shrink-0 items-center gap-[9px] border-b border-rz-border bg-rz-surface px-4 pt-[calc(env(safe-area-inset-top)+20px)] pb-3.5">
                    <div className="flex min-w-0 flex-1 items-center gap-2">
                        <h2 className="text-lg font-bold text-rz-ink">
                            {t('investor.notices.title')}
                        </h2>
                        {inbox !== null && inbox.unread > 0 && (
                            <span
                                aria-label={t('investor.notices.unread_count', {
                                    count: inbox.unread,
                                })}
                                className="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-[10px] bg-[#1e3aff] px-[5px] text-[10.5px] font-bold text-white"
                            >
                                {inbox.unread}
                            </span>
                        )}
                    </div>
                    {inbox?.actions.read_all && (
                        <button
                            type="button"
                            onClick={() =>
                                void readAll(
                                    inbox.actions.read_all as RouteAction,
                                )
                            }
                            className="text-xs font-semibold text-[#1e3aff] dark:text-rz-accent-app-text"
                        >
                            {t('investor.notices.mark_all')}
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label={t('investor.notices.close')}
                        className="dark:bg-rz-surface-2 size-8 shrink-0 rounded-[10px] bg-[#f1f4f9] text-[15px] text-rz-secondary"
                    >
                        ✕
                    </button>
                </header>
                <div className="rz-scroll flex min-h-0 flex-1 flex-col gap-2.5 overflow-y-auto p-3.5">
                    {failed ? (
                        <div role="alert" className="py-8 text-center">
                            <p className="text-[13px] text-rz-secondary">
                                {t('investor.notices.failed')}
                            </p>
                            <button
                                type="button"
                                onClick={() => void load()}
                                className="mt-3 text-[13px] font-semibold text-rz-accent-app-text"
                            >
                                {t('investor.notices.retry')}
                            </button>
                        </div>
                    ) : inbox === null ? (
                        <div
                            aria-busy="true"
                            aria-label={t('investor.notices.loading')}
                            className="flex flex-col gap-2.5"
                        >
                            {[0, 1, 2].map((row) => (
                                <span
                                    key={row}
                                    className="h-[72px] animate-pulse rounded-2xl border border-rz-border bg-rz-surface"
                                />
                            ))}
                        </div>
                    ) : inbox.notices.length === 0 ? (
                        <p className="py-8 text-center text-[13px] text-rz-secondary">
                            {t('investor.notices.empty')}
                        </p>
                    ) : (
                        inbox.notices.map((notice) => {
                            const tone = TONES[notice.kind];

                            return (
                                <button
                                    key={notice.id}
                                    type="button"
                                    onClick={() => void open(notice)}
                                    className="relative flex w-full shrink-0 items-start gap-3 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface py-[13px] pr-[13px] pl-3.5 text-left"
                                >
                                    <span
                                        aria-hidden
                                        className={cn(
                                            'absolute inset-y-0 left-0 w-[3px]',
                                            tone.bar,
                                            notice.read && 'opacity-0',
                                        )}
                                    />
                                    <span
                                        className={cn(
                                            'flex size-[38px] shrink-0 items-center justify-center rounded-[10px]',
                                            tone.tile,
                                        )}
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            aria-hidden
                                            className="size-[18px]"
                                        >
                                            {tone.icon}
                                        </svg>
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="flex items-center justify-end gap-1.5">
                                            <time
                                                dateTime={notice.created_at}
                                                className="text-[10.5px] whitespace-nowrap text-rz-secondary"
                                            >
                                                {formatDateTime(
                                                    notice.created_at,
                                                    locale,
                                                )}
                                            </time>
                                            {!notice.read && (
                                                <span
                                                    aria-label={t(
                                                        'investor.notices.unread',
                                                    )}
                                                    className={cn(
                                                        'size-[7px] shrink-0 rounded-full',
                                                        tone.bar,
                                                    )}
                                                />
                                            )}
                                        </span>
                                        <span className="mt-px block text-[13px] leading-[1.35] font-semibold text-rz-ink">
                                            {t(
                                                `investor.notices.${notice.kind}.title`,
                                            )}
                                        </span>
                                        <span className="mt-[3px] block text-xs leading-normal text-rz-secondary">
                                            {body(notice, t, locale)}
                                        </span>
                                    </span>
                                </button>
                            );
                        })
                    )}
                </div>
            </section>
        </>
    );
}

function body(
    notice: InvestorNotice,
    t: ReturnType<typeof useTranslation>['t'],
    locale: string,
): string {
    switch (notice.kind) {
        case 'wallet.deposit_received':
            return t('investor.notices.wallet.deposit_received.body', {
                amount: formatRwf(notice.facts),
            });
        case 'wallet.withdrawal_sent':
            return t('investor.notices.wallet.withdrawal_sent.body', {
                amount: formatRwf({
                    currency: notice.facts.currency,
                    amount: notice.facts.received,
                }),
                account: notice.facts.account,
            });
        case 'wallet.withdrawal_returned':
            return t('investor.notices.wallet.withdrawal_returned.body', {
                amount: formatRwf(notice.facts),
                account: notice.facts.account,
            });
        case 'primary.refunded':
            return t('investor.notices.primary.refunded.body', {
                amount: formatRwf(notice.facts),
                business: notice.facts.business,
            });
        case 'primary.report_published':
            return t('investor.notices.primary.report_published.body', {
                business: notice.facts.business,
                month: formatMonthYearLong(
                    `${notice.facts.period}-01T00:00:00+02:00`,
                    locale,
                ),
                partner: notice.facts.partner,
            });
        case 'verification.rejected':
            return t('investor.notices.verification.rejected.body', {
                reason: notice.facts.reason,
            });
        case 'verification.approved':
            return t('investor.notices.verification.approved.body');
        case 'security.password_changed':
            return t('investor.notices.security.password_changed.body');
        case 'security.new_device':
            return t('investor.notices.security.new_device.body', {
                device: notice.facts.device,
            });
        case 'security.payout_account_linked':
            return t('investor.notices.security.payout_account_linked.body', {
                account: notice.facts.account,
            });
        case 'security.payout_account_unlinked':
            return t('investor.notices.security.payout_account_unlinked.body', {
                account: notice.facts.account,
            });
        case 'business.deposit_received':
            return t('investor.notices.business.deposit_received.body', {
                amount: formatRwf(notice.facts),
            });
        case 'business.application_approved':
            return t('investor.notices.business.application_approved.body', {
                title: notice.facts.title,
                amount: formatRwf(notice.facts),
                months: notice.facts.term_months,
            });
        case 'business.withdrawal_sent':
            return t('investor.notices.business.withdrawal_sent.body', {
                amount: formatRwf({
                    currency: notice.facts.currency,
                    amount: notice.facts.received,
                }),
                account: notice.facts.account,
            });
        case 'business.withdrawal_returned':
            return t('investor.notices.business.withdrawal_returned.body', {
                amount: formatRwf(notice.facts),
                account: notice.facts.account,
            });
        case 'business.report_sealed':
            return notice.facts.due_at === null
                ? t('investor.notices.business.report_sealed.body', {
                      auditor: notice.facts.auditor,
                  })
                : t('investor.notices.business.report_sealed.body_due', {
                      auditor: notice.facts.auditor,
                      time: formatDateTime(notice.facts.due_at, locale),
                  });
        case 'business.report_review_closing':
            return t('investor.notices.business.report_review_closing.body', {
                time: formatDateTime(notice.facts.due_at, locale),
            });
        case 'auditor.accreditation_approved':
            return t('investor.notices.auditor.accreditation_approved.body', {
                licence: notice.facts.licence,
                date: formatDate(notice.facts.expires_on, locale),
            });
        case 'auditor.accreditation_rejected':
            return t('investor.notices.auditor.accreditation_rejected.body', {
                reason: notice.facts.reason,
            });
        case 'auditor.job_offered':
            return t(
                notice.facts.type === 'flash'
                    ? 'investor.notices.auditor.job_offered.body_flash'
                    : 'investor.notices.auditor.job_offered.body_routine',
                {
                    business: notice.facts.business,
                    location: notice.facts.location,
                    time: formatDateTime(notice.facts.accept_by, locale),
                },
            );
        case 'business.raise_expired':
            return t('investor.notices.business.raise_expired.body', {
                title: notice.facts.title,
                committed: formatRwf({
                    currency: 'RWF',
                    amount: notice.facts.committed,
                }),
                target: formatRwf({
                    currency: 'RWF',
                    amount: notice.facts.target,
                }),
            });
    }
}
