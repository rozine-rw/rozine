import { useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    ComplaintStatus,
    SupportCenter,
    SupportComplaint,
} from '@/types/support';

/** The panel follows the newest complaint not yet resolved, whichever it is after a reload. */
const LATEST = 'latest';

const STATUS_TONE: Record<ComplaintStatus, string> = {
    awaiting:
        'bg-[#fff4e5] text-[#a35a00] dark:bg-[rgba(255,170,60,.14)] dark:text-[#ffb35c]',
    answered: 'bg-rz-accent-soft text-rz-accent-app-text',
    resolved:
        'bg-[#e8f6f0] text-[#17795a] dark:bg-[rgba(63,205,160,.14)] dark:text-[#3fcda0]',
};

/** The bubble icon the design's support button and header share. */
function ChatGlyph({ className }: { className?: string }) {
    return (
        <svg aria-hidden viewBox="0 0 24 24" fill="none" className={className}>
            <path
                d="M21 11.5a8.5 8.5 0 0 1-12.4 7.55L3.5 20.5l1.45-5.05A8.5 8.5 0 1 1 21 11.5Z"
                stroke="currentColor"
                strokeWidth="1.7"
                strokeLinejoin="round"
            />
            <circle cx="8.5" cy="11.6" r="1.05" fill="currentColor" />
            <circle cx="12" cy="11.6" r="1.05" fill="currentColor" />
            <circle cx="15.5" cy="11.6" r="1.05" fill="currentColor" />
        </svg>
    );
}

/** One complaint's reference and status, as the support center lists it. */
function ComplaintRow({
    complaint,
    onOpen,
}: {
    complaint: SupportComplaint;
    onOpen: () => void;
}) {
    const { t, locale } = useTranslation();

    return (
        <li>
            <button
                type="button"
                onClick={onOpen}
                className="flex w-full items-center gap-3 rounded-2xl border border-rz-border bg-rz-surface px-[15px] py-3 text-left transition-colors hover:border-rz-accent-fill"
            >
                <span className="min-w-0 flex-1">
                    <span className="block text-[13.5px] font-semibold text-rz-ink">
                        {complaint.reference}
                    </span>
                    <span className="mt-0.5 block text-[11.5px] text-rz-secondary">
                        {t('support.chat.opened', {
                            at: formatDateTime(complaint.opened_at, locale),
                        })}
                    </span>
                </span>
                <span
                    className={cn(
                        'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                        STATUS_TONE[complaint.status],
                    )}
                >
                    {t(`support.chat.status.${complaint.status}`)}
                </span>
            </button>
        </li>
    );
}

/** The message box: the text and a fresh `request_id`, to open a complaint or add to one. */
function MessageForm({
    action,
    onSent,
}: {
    action: { url: string };
    onSent: () => void;
}) {
    const { t } = useTranslation();
    const form = useForm<{ body: string }>({ body: '' });

    const send = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            request_id: crypto.randomUUID(),
        }));
        form.post(action.url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('body');
                onSent();
            },
        });
    };

    return (
        <form
            onSubmit={send}
            className="shrink-0 border-t border-rz-border bg-rz-surface px-3 py-2.5"
        >
            <div className="flex items-center gap-2">
                <label htmlFor="support-message" className="sr-only">
                    {t('support.chat.message')}
                </label>
                <textarea
                    id="support-message"
                    rows={1}
                    value={form.data.body}
                    onChange={(event) =>
                        form.setData('body', event.target.value)
                    }
                    placeholder={t('support.chat.placeholder')}
                    aria-invalid={form.errors.body !== undefined || undefined}
                    className="min-w-0 flex-1 resize-none rounded-[20px] border border-rz-border bg-[#f7f9fc] px-[15px] py-[11px] text-[13.5px] text-rz-ink outline-none dark:bg-rz-surface-sunken"
                />
                <button
                    type="submit"
                    disabled={form.processing}
                    aria-label={t('support.chat.send')}
                    className="flex size-[42px] shrink-0 items-center justify-center rounded-full bg-[#1e3aff] text-white disabled:opacity-60"
                >
                    <svg
                        aria-hidden
                        viewBox="0 0 24 24"
                        fill="none"
                        className="size-[18px]"
                    >
                        <path
                            d="M4.5 12 20 4.5 13 20l-2.4-6.4L4.5 12Z"
                            stroke="currentColor"
                            strokeWidth="1.7"
                            strokeLinejoin="round"
                        />
                    </svg>
                </button>
            </div>
            {form.errors.body !== undefined && (
                <p
                    role="alert"
                    className="mt-1.5 px-1 text-[12px] font-semibold text-[#e5484d]"
                >
                    {form.errors.body}
                </p>
            )}
        </form>
    );
}

/**
 * The support chat (design Investor T4591–4625 and the Business support center), backed by real
 * complaints (BRS v2.0 CMP-R03). The floating button opens the panel on the newest complaint not yet resolved, or on a
 * fresh conversation; what the customer sends opens a complaint with its reference, or adds to the
 * open one. Rozine's answers come from staff, never from a script, so the panel promises only the
 * BRS clocks: acknowledged within 4 hours, resolved within 48. Every complaint is listed with its
 * status and opens the panel on its conversation.
 */
export function SupportChat({ support }: { support: SupportCenter }) {
    const { t, locale } = useTranslation();
    const current =
        support.complaints.find((item) => item.status !== 'resolved') ?? null;
    const [openId, setOpenId] = useState<string | null | undefined>(undefined);
    const shown =
        openId === LATEST
            ? current
            : (support.complaints.find((item) => item.id === openId) ?? null);
    const isOpen = openId !== undefined;
    const target = shown === null ? support.open : shown.reply;
    const listRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (listRef.current !== null) {
            listRef.current.scrollTop = listRef.current.scrollHeight;
        }
    }, [shown?.messages.length, isOpen]);

    return (
        <>
            {support.complaints.length > 0 && (
                <section className="mt-2" aria-labelledby="support-complaints">
                    <h3
                        id="support-complaints"
                        className="mb-2.5 text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase"
                    >
                        {t('support.chat.list')}
                    </h3>
                    <ul className="flex flex-col gap-2">
                        {support.complaints.map((complaint) => (
                            <ComplaintRow
                                key={complaint.id}
                                complaint={complaint}
                                onOpen={() => setOpenId(complaint.id)}
                            />
                        ))}
                    </ul>
                </section>
            )}

            {!isOpen && (
                <button
                    type="button"
                    onClick={() => setOpenId(LATEST)}
                    aria-label={t('support.chat.open')}
                    className="fixed right-5 bottom-24 z-30 flex size-14 items-center justify-center rounded-full bg-[#1e3aff] text-white shadow-[0_14px_30px_-10px_rgba(30,58,255,.6)] lg:bottom-8"
                >
                    <ChatGlyph className="size-[27px]" />
                </button>
            )}

            {isOpen && (
                <div className="fixed inset-0 z-40 flex items-end justify-center sm:items-center">
                    <div
                        aria-hidden
                        data-testid="support-scrim"
                        onClick={() => setOpenId(undefined)}
                        className="absolute inset-0 bg-[rgba(12,24,48,.32)]"
                    />
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-label={t('support.chat.title')}
                        className="relative flex h-[min(560px,88vh)] w-full max-w-[400px] flex-col overflow-hidden rounded-t-[22px] bg-rz-surface shadow-[0_30px_70px_-20px_rgba(12,24,48,.45)] sm:rounded-[22px]"
                    >
                        <div className="flex shrink-0 items-center gap-[11px] bg-[#1e3aff] px-[15px] py-3.5">
                            <span className="flex size-[38px] items-center justify-center rounded-full bg-[rgba(255,255,255,.2)] text-white">
                                <ChatGlyph className="size-5" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="text-[14.5px] font-semibold text-white">
                                    {t('support.chat.title')}
                                </p>
                                <p className="text-[11px] text-white">
                                    {shown === null
                                        ? t('support.chat.clocks')
                                        : t('support.chat.reference', {
                                              reference: shown.reference,
                                          })}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setOpenId(undefined)}
                                aria-label={t('app.sheet.close')}
                                className="size-[30px] rounded-[10px] bg-[rgba(0,0,0,.14)] text-[15px] text-white"
                            >
                                ✕
                            </button>
                        </div>
                        <div
                            ref={listRef}
                            className="flex min-h-0 flex-1 flex-col gap-2.5 overflow-y-auto bg-[#f7f9fc] p-4 dark:bg-rz-surface-sunken"
                        >
                            {shown === null ? (
                                <div className="max-w-[85%] rounded-2xl bg-rz-surface px-[13px] py-2.5 text-[13px] leading-normal text-rz-ink shadow-[0_1px_2px_rgba(20,45,95,.06)]">
                                    {t('support.chat.greeting')}
                                </div>
                            ) : (
                                shown.messages.map((message) => (
                                    <div
                                        key={message.id}
                                        className={cn(
                                            'flex flex-col',
                                            message.author === 'customer'
                                                ? 'items-end'
                                                : 'items-start',
                                        )}
                                    >
                                        <div
                                            className={cn(
                                                'max-w-[78%] rounded-2xl px-[13px] py-2.5 text-[13px] leading-normal whitespace-pre-line shadow-[0_1px_2px_rgba(20,45,95,.06)]',
                                                message.author === 'customer'
                                                    ? 'bg-[#1e3aff] text-white'
                                                    : 'bg-rz-surface text-rz-ink',
                                            )}
                                        >
                                            {message.body}
                                        </div>
                                        <span className="mt-1 text-[10.5px] text-rz-secondary">
                                            {message.resolves
                                                ? t('support.chat.resolution', {
                                                      at: formatDateTime(
                                                          message.at,
                                                          locale,
                                                      ),
                                                  })
                                                : formatDateTime(
                                                      message.at,
                                                      locale,
                                                  )}
                                        </span>
                                    </div>
                                ))
                            )}
                            <p className="mt-auto pt-2 text-center text-[11px] leading-normal text-rz-secondary">
                                {t('support.chat.cma')}
                            </p>
                        </div>
                        {target === null ? (
                            <div className="flex shrink-0 items-center justify-between gap-2 border-t border-rz-border px-3.5 py-3">
                                <p className="text-[12px] text-rz-secondary">
                                    {t('support.chat.closed')}
                                </p>
                                <button
                                    type="button"
                                    onClick={() => setOpenId(null)}
                                    className="shrink-0 rounded-xl border border-rz-border px-3 py-2 text-[12px] font-semibold text-rz-ink"
                                >
                                    {t('support.chat.new')}
                                </button>
                            </div>
                        ) : (
                            <MessageForm
                                key={target.url}
                                action={target}
                                // A first message opens a complaint: follow it, so its reference and replies show.
                                onSent={() =>
                                    setOpenId(
                                        shown === null ? LATEST : shown.id,
                                    )
                                }
                            />
                        )}
                    </div>
                </div>
            )}
        </>
    );
}
