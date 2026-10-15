import { useState } from 'react';
import { LocalSheet, SheetClose } from '@/components/investor/local-sheet';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDateLong } from '@/lib/rozine/format';

/** When the answers were last checked against the BRS (v2.0). */
const UPDATED = '2026-10-15T12:00:00+02:00';

/**
 * The help center's answers, in the design's groups (Investor L3884–4140). Each answer restates a
 * BRS v2.0 rule; the design's Learning & rewards group is left out, since no points, tiers,
 * discounts or referral pay are approved.
 */
const GROUPS = [
    {
        key: 'start',
        icon: '🚀',
        questions: [
            {
                q: 'investor.help.start.what.q',
                a: 'investor.help.start.what.a',
            },
            {
                q: 'investor.help.start.first.q',
                a: 'investor.help.start.first.a',
            },
            {
                q: 'investor.help.start.minimum.q',
                a: 'investor.help.start.minimum.a',
            },
        ],
    },
    {
        key: 'returns',
        icon: '💸',
        questions: [
            {
                q: 'investor.help.returns.when.q',
                a: 'investor.help.returns.when.a',
            },
            {
                q: 'investor.help.returns.yield.q',
                a: 'investor.help.returns.yield.a',
            },
            {
                q: 'investor.help.returns.guaranteed.q',
                a: 'investor.help.returns.guaranteed.a',
            },
        ],
    },
    {
        key: 'risk',
        icon: '🛡️',
        questions: [
            {
                q: 'investor.help.risk.protected.q',
                a: 'investor.help.risk.protected.a',
            },
            {
                q: 'investor.help.risk.vetting.q',
                a: 'investor.help.risk.vetting.a',
            },
            {
                q: 'investor.help.risk.rating.q',
                a: 'investor.help.risk.rating.a',
            },
            { q: 'investor.help.risk.late.q', a: 'investor.help.risk.late.a' },
        ],
    },
    {
        key: 'portfolio',
        icon: '📊',
        questions: [
            {
                q: 'investor.help.portfolio.diversify.q',
                a: 'investor.help.portfolio.diversify.a',
            },
            {
                q: 'investor.help.portfolio.track.q',
                a: 'investor.help.portfolio.track.a',
            },
            {
                q: 'investor.help.portfolio.reinvest.q',
                a: 'investor.help.portfolio.reinvest.a',
            },
        ],
    },
    {
        key: 'secondary',
        icon: '🔁',
        questions: [
            {
                q: 'investor.help.secondary.exit.q',
                a: 'investor.help.secondary.exit.a',
            },
            {
                q: 'investor.help.secondary.price.q',
                a: 'investor.help.secondary.price.a',
            },
            {
                q: 'investor.help.secondary.guaranteed.q',
                a: 'investor.help.secondary.guaranteed.a',
            },
        ],
    },
    {
        key: 'wallet',
        icon: '👛',
        questions: [
            {
                q: 'investor.help.wallet.move.q',
                a: 'investor.help.wallet.move.a',
            },
            {
                q: 'investor.help.wallet.fees.q',
                a: 'investor.help.wallet.fees.a',
            },
            {
                q: 'investor.help.wallet.speed.q',
                a: 'investor.help.wallet.speed.a',
            },
        ],
    },
    {
        key: 'safety',
        icon: '🏦',
        questions: [
            {
                q: 'investor.help.safety.held.q',
                a: 'investor.help.safety.held.a',
            },
            {
                q: 'investor.help.safety.stop.q',
                a: 'investor.help.safety.stop.a',
            },
        ],
    },
    {
        key: 'account',
        icon: '🔒',
        questions: [
            {
                q: 'investor.help.account.secure.q',
                a: 'investor.help.account.secure.a',
            },
            {
                q: 'investor.help.account.complain.q',
                a: 'investor.help.account.complain.a',
            },
        ],
    },
] as const;

type Group = (typeof GROUPS)[number];
type Open = { group: Group; question: Group['questions'][number] };

/**
 * Help center (design Investor `isHelp`): answers to the questions investors ask most, each card
 * opening its full answer in a pull-up that ends with a way to message support.
 */
export function HelpCenter({ onMessage }: { onMessage: () => void }) {
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState<Open | null>(null);

    return (
        <>
            <p className="mt-1 text-[13px] leading-normal text-rz-secondary">
                {t('investor.help.intro')}
            </p>
            {GROUPS.map((group) => (
                <section
                    key={group.key}
                    aria-labelledby={`help-${group.key}`}
                    className="mt-5"
                >
                    <div className="mb-2.5 flex items-center gap-2">
                        <span aria-hidden className="text-[15px]">
                            {group.icon}
                        </span>
                        <h3
                            id={`help-${group.key}`}
                            className="flex-1 text-[13px] font-semibold text-rz-ink"
                        >
                            {t(`investor.help.group.${group.key}`)}
                        </h3>
                        <span className="text-[11px] text-rz-muted">
                            {t('investor.help.count', {
                                count: group.questions.length,
                            })}
                        </span>
                    </div>
                    <div className="flex flex-col gap-2">
                        {group.questions.map((question) => (
                            <button
                                key={question.q}
                                type="button"
                                onClick={() => setOpen({ group, question })}
                                className="rounded-2xl border border-rz-border bg-rz-surface px-4 py-3.5 text-left transition hover:border-[#1e3aff]"
                            >
                                <span className="flex items-start justify-between gap-3">
                                    <span className="text-[13.5px] font-semibold text-rz-ink">
                                        {t(question.q)}
                                    </span>
                                    <span aria-hidden className="text-rz-muted">
                                        ›
                                    </span>
                                </span>
                                <span className="mt-1 line-clamp-2 block text-[12px] leading-normal text-rz-secondary">
                                    {t(question.a)}
                                </span>
                                <span className="mt-2 block text-[10.5px] text-rz-muted">
                                    {t('investor.help.updated', {
                                        date: formatDate(UPDATED, locale),
                                    })}
                                </span>
                            </button>
                        ))}
                    </div>
                </section>
            ))}
            {open !== null && (
                <LocalSheet
                    label={t(open.question.q)}
                    onClose={() => setOpen(null)}
                >
                    <div className="flex shrink-0 items-start gap-3 border-b border-rz-border px-[18px] py-3.5">
                        <span
                            aria-hidden
                            className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft text-lg"
                        >
                            {open.group.icon}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                                {t(`investor.help.group.${open.group.key}`)}
                            </p>
                            <h3 className="mt-[3px] text-base leading-[1.35] font-semibold text-rz-ink">
                                {t(open.question.q)}
                            </h3>
                        </div>
                        <SheetClose onClose={() => setOpen(null)} />
                    </div>
                    <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-4 pb-[22px]">
                        <span className="inline-flex rounded-[10px] border border-rz-border px-2.5 py-[5px] text-[11px] font-semibold text-rz-secondary">
                            {t('investor.help.updated_long', {
                                date: formatDateLong(UPDATED, locale),
                            })}
                        </span>
                        <p className="mt-3.5 text-sm leading-[1.7] whitespace-pre-line text-rz-secondary">
                            {t(open.question.a)}
                        </p>
                        <div className="mt-5 flex items-center gap-3 rounded-2xl border border-rz-border px-[15px] py-[15px]">
                            <div className="min-w-0 flex-1">
                                <p className="text-[13px] font-semibold text-rz-ink">
                                    {t('investor.help.still_title')}
                                </p>
                                <p className="mt-0.5 text-[12px] text-rz-secondary">
                                    {t('investor.help.still_body')}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => {
                                    setOpen(null);
                                    onMessage();
                                }}
                                className="shrink-0 rounded-[10px] bg-[#1e3aff] px-3.5 py-2 text-[12.5px] font-semibold text-white"
                            >
                                {t('investor.help.still_action')}
                            </button>
                        </div>
                    </div>
                </LocalSheet>
            )}
        </>
    );
}
