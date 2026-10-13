import { router, useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime, formatRwf } from '@/lib/rozine/format';
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
    'verification.approved': {
        bar: 'bg-[#1e3aff]',
        tile: 'bg-[rgba(30,58,255,.1)] text-[#1e3aff]',
        icon: <path d="m5 12.5 4.5 4.5L19 7.5" {...stroke} />,
    },
    'verification.rejected': {
        bar: 'bg-[#b3383c]',
        tile: 'bg-[rgba(179,56,60,.1)] text-[#b3383c]',
        icon: <path d="M12 7v6M12 16.5v.5" {...stroke} />,
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

        router.visit(notice.link);
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
                                            {body(notice, t)}
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
): string {
    switch (notice.kind) {
        case 'wallet.deposit_received':
            return t('investor.notices.wallet.deposit_received.body', {
                amount: formatRwf(notice.facts),
            });
        case 'verification.rejected':
            return t('investor.notices.verification.rejected.body', {
                reason: notice.facts.reason,
            });
        case 'verification.approved':
            return t('investor.notices.verification.approved.body');
    }
}
