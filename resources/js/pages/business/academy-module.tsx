import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AcademyQuestion,
    BusinessAcademyModuleProps,
} from '@/types/business';

/** One flashcard at a time: its front, turned over to its back. */
function Flashcards({
    cards,
    onDone,
}: {
    cards: BusinessAcademyModuleProps['cards'];
    onDone: (() => void) | null;
}) {
    const { t } = useTranslation();
    const [index, setIndex] = useState(0);
    const [turned, setTurned] = useState(false);
    const card = cards[index];
    const last = index === cards.length - 1;
    const move = (to: number) => {
        setIndex(to);
        setTurned(false);
    };

    return (
        <section aria-labelledby="academy-cards" className="mt-5">
            <div className="flex items-center justify-between">
                <h2
                    id="academy-cards"
                    className="text-base font-semibold text-rz-ink"
                >
                    {t('business.academy.flashcards')}
                </h2>
                <p className="text-[12px] text-rz-secondary">
                    {t('business.academy.card_progress', {
                        current: index + 1,
                        total: cards.length,
                    })}
                </p>
            </div>
            <div
                aria-live="polite"
                className="mt-3 min-h-[150px] rounded-2xl border border-rz-border bg-rz-surface p-5"
            >
                <p className="text-[17px] font-semibold text-rz-ink">
                    {card.front}
                </p>
                {turned && (
                    <p className="mt-3 text-[13.5px] leading-[1.6] text-rz-ink">
                        {card.back}
                    </p>
                )}
            </div>
            <div className="mt-3 flex gap-2.5">
                <button
                    type="button"
                    onClick={() => setTurned(!turned)}
                    className="flex-1 rounded-xl border border-rz-border bg-rz-surface px-4 py-3 text-sm font-semibold text-rz-ink"
                >
                    {turned
                        ? t('business.academy.show_front')
                        : t('business.academy.show_back')}
                </button>
            </div>
            <div className="mt-2.5 flex gap-2.5">
                <button
                    type="button"
                    disabled={index === 0}
                    onClick={() => move(index - 1)}
                    className="flex-1 rounded-xl border border-rz-border bg-rz-surface px-4 py-3 text-sm font-semibold text-rz-ink disabled:opacity-50"
                >
                    {t('business.academy.previous')}
                </button>
                {!last ? (
                    <button
                        type="button"
                        onClick={() => move(index + 1)}
                        className="flex-1 rounded-xl bg-rz-accent-fill px-4 py-3 text-sm font-semibold text-white"
                    >
                        {t('business.academy.next')}
                    </button>
                ) : (
                    onDone !== null && (
                        <button
                            type="button"
                            onClick={onDone}
                            className="flex-1 rounded-xl bg-rz-accent-fill px-4 py-3 text-sm font-semibold text-white"
                        >
                            {t('business.academy.start_test')}
                        </button>
                    )
                )}
            </div>
        </section>
    );
}

/** The drawn test: every question answered, then sent once to be scored on the server. */
function Test({
    test,
    attempt,
    passMark,
    revision,
    url,
}: {
    test: AcademyQuestion[];
    attempt: number;
    passMark: number;
    revision: number;
    url: string;
}) {
    const { t } = useTranslation();
    const form = useForm({ answers: {} as Record<number, number> });
    const { data, errors, processing } = form;
    const fieldErrors = errors as Record<string, string | undefined>;
    const left = test.filter(
        (question) => data.answers[question.id] === undefined,
    ).length;

    return (
        <form
            aria-labelledby="academy-test"
            className="mt-6"
            onSubmit={(event) => {
                event.preventDefault();
                form.transform((current) => ({
                    ...current,
                    attempt,
                    identity_context_revision: revision,
                    request_id: crypto.randomUUID(),
                }));
                form.post(url, { preserveScroll: true, preserveState: true });
            }}
        >
            <h2
                id="academy-test"
                className="text-base font-semibold text-rz-ink"
            >
                {t('business.academy.test_title')}
            </h2>
            <p className="mt-1 text-[12.5px] text-rz-secondary">
                {t('business.academy.test_intro', {
                    count: test.length,
                    pass: passMark,
                })}
            </p>
            <ol className="mt-3 space-y-3">
                {test.map((question, index) => (
                    <li key={question.id}>
                        <fieldset
                            aria-describedby={`academy-answer-${question.id}-error`}
                            className="rounded-2xl border border-rz-border bg-rz-surface p-3.5"
                        >
                            <legend className="sr-only">
                                {t('business.academy.question', {
                                    current: index + 1,
                                    total: test.length,
                                })}
                            </legend>
                            <p className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                                {t('business.academy.question', {
                                    current: index + 1,
                                    total: test.length,
                                })}
                            </p>
                            <p className="mt-1 text-[14px] font-semibold text-rz-ink">
                                {question.question}
                            </p>
                            <div className="mt-2.5 flex flex-col gap-2">
                                {question.options.map((option) => (
                                    <label
                                        key={option.id}
                                        className={cn(
                                            'flex cursor-pointer items-center gap-2.5 rounded-xl border px-3 py-2.5 text-[13px]',
                                            data.answers[question.id] ===
                                                option.id
                                                ? 'border-rz-accent-fill bg-rz-accent-soft text-rz-accent-app-text'
                                                : 'border-rz-border text-rz-ink',
                                        )}
                                    >
                                        <input
                                            type="radio"
                                            name={`question-${question.id}`}
                                            checked={
                                                data.answers[question.id] ===
                                                option.id
                                            }
                                            onChange={() =>
                                                form.setData('answers', {
                                                    ...data.answers,
                                                    [question.id]: option.id,
                                                })
                                            }
                                        />
                                        {option.text}
                                    </label>
                                ))}
                            </div>
                            <FieldError
                                id={`academy-answer-${question.id}-error`}
                            >
                                {fieldErrors[`answers.${question.id}`]}
                            </FieldError>
                        </fieldset>
                    </li>
                ))}
            </ol>
            {left > 0 && (
                <p className="mt-3 text-[12.5px] text-rz-secondary">
                    {t('business.academy.left', { count: left })}
                </p>
            )}
            <FieldError id="academy-form-error">{fieldErrors.form}</FieldError>
            <button
                type="submit"
                disabled={left > 0 || processing}
                aria-busy={processing || undefined}
                className="mt-4 h-[50px] w-full rounded-xl bg-rz-accent-fill text-[14.5px] font-semibold text-white disabled:opacity-50"
            >
                {processing
                    ? t('business.academy.sending')
                    : t('business.academy.send')}
            </button>
        </form>
    );
}

/**
 * One Academy module (BRS v3.0 RDY-R07): its flashcards, then, while its test is open, the 10
 * questions drawn for the next attempt, scored on the server. A passed module and a preview keep
 * their cards to revise; a failed test says when it reopens.
 */
export default function BusinessAcademyModule({
    identity_context_revision,
    role,
    title,
    summary,
    cards,
    status,
    attempt,
    test,
    last,
    pass_mark,
    links,
}: BusinessAcademyModuleProps) {
    const { t, locale } = useTranslation();
    const [testing, setTesting] = useState(false);
    const open = test !== null && attempt !== null;
    const total = test?.length ?? 10;
    const result = (() => {
        if (last === null || status === null) {
            return null;
        }

        if (last.passed) {
            return t('business.academy.result.passed', {
                score: last.score,
                total,
            });
        }

        return status.retake_at === null
            ? t('business.academy.result.open', { score: last.score, total })
            : t('business.academy.result.waiting', {
                  score: last.score,
                  total,
                  date: formatDateTime(status.retake_at, locale),
              });
    })();

    return (
        <BusinessShell
            title={title}
            tab="readiness"
            links={links}
            showTabBar={false}
        >
            <div className="mx-auto max-w-[620px] px-5 pt-[calc(env(safe-area-inset-top)+4px)] pb-10 lg:pt-6">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.close.url}
                        aria-label={t('business.academy.back')}
                        className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <h1 className="text-xl font-semibold text-rz-ink">
                        {title}
                    </h1>
                </div>
                <p className="mt-3 text-[13px] leading-[1.55] text-rz-secondary">
                    {summary}
                </p>
                {result !== null && (
                    <p
                        role="status"
                        className="mt-3 rounded-xl bg-rz-page px-3.5 py-2.5 text-[13px] text-rz-ink"
                    >
                        {result}
                    </p>
                )}
                {role === 'preview' && (
                    <p className="mt-3 rounded-xl bg-rz-page px-3.5 py-2.5 text-[13px] text-rz-ink">
                        {t('business.academy.note.preview')}
                    </p>
                )}
                {status?.state === 'passed' && (
                    <p className="mt-3 text-[12.5px] text-rz-secondary">
                        {t('business.academy.note.passed')}
                    </p>
                )}
                <Flashcards
                    cards={cards}
                    onDone={open ? () => setTesting(true) : null}
                />
                {open && testing && (
                    <Test
                        test={test}
                        attempt={attempt}
                        passMark={pass_mark}
                        revision={identity_context_revision}
                        url={links.submit.url}
                    />
                )}
            </div>
        </BusinessShell>
    );
}
