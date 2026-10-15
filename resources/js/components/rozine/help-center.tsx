import { useState } from 'react';
import { LocalSheet, SheetClose } from '@/components/investor/local-sheet';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDateLong } from '@/lib/rozine/format';

import type { MessageCode } from '@/lib/i18n/types';

/** One group of answers: its heading, its glyph and its questions, each a pair of message codes. */
export type HelpGroup = {
    key: string;
    icon: string;
    heading: MessageCode;
    questions: readonly { q: MessageCode; a: MessageCode }[];
};

type Open = { group: HelpGroup; question: HelpGroup['questions'][number] };

/**
 * A help center (design Investor `isHelp`, Business `isSupport`): answers to the questions people
 * ask most, each card opening its full answer in a pull-up that ends with a way to message
 * support. `updated` is when the answers were last checked against the BRS.
 */
export function HelpCenter({
    intro,
    groups,
    updated,
    onMessage,
}: {
    intro: MessageCode;
    groups: readonly HelpGroup[];
    updated: string;
    onMessage: () => void;
}) {
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState<Open | null>(null);

    return (
        <>
            <p className="mt-1 text-[13px] leading-normal text-rz-secondary">
                {t(intro)}
            </p>
            {groups.map((group) => (
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
                            {t(group.heading)}
                        </h3>
                        <span className="text-[11px] text-rz-muted">
                            {t('support.help.count', {
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
                                    {t('support.help.updated', {
                                        date: formatDate(updated, locale),
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
                                {t(open.group.heading)}
                            </p>
                            <h3 className="mt-[3px] text-base leading-[1.35] font-semibold text-rz-ink">
                                {t(open.question.q)}
                            </h3>
                        </div>
                        <SheetClose onClose={() => setOpen(null)} />
                    </div>
                    <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-4 pb-[22px]">
                        <span className="inline-flex rounded-[10px] border border-rz-border px-2.5 py-[5px] text-[11px] font-semibold text-rz-secondary">
                            {t('support.help.updated_long', {
                                date: formatDateLong(updated, locale),
                            })}
                        </span>
                        <p className="mt-3.5 text-sm leading-[1.7] whitespace-pre-line text-rz-secondary">
                            {t(open.question.a)}
                        </p>
                        <div className="mt-5 flex items-center gap-3 rounded-2xl border border-rz-border px-[15px] py-[15px]">
                            <div className="min-w-0 flex-1">
                                <p className="text-[13px] font-semibold text-rz-ink">
                                    {t('support.help.still_title')}
                                </p>
                                <p className="mt-0.5 text-[12px] text-rz-secondary">
                                    {t('support.help.still_body')}
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
                                {t('support.help.still_action')}
                            </button>
                        </div>
                    </div>
                </LocalSheet>
            )}
        </>
    );
}
