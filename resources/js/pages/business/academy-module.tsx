import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AcademyQuestion,
    BusinessAcademyModuleProps,
} from '@/types/business';

type Card = BusinessAcademyModuleProps['cards'][number];

const primaryButton =
    'h-[52px] w-full rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white shadow-rz-resting transition active:scale-[.98] disabled:opacity-50 motion-reduce:transition-none';
const secondaryButton =
    'h-[48px] w-full rounded-2xl border border-rz-border bg-rz-surface text-[14px] font-semibold text-rz-ink transition active:scale-[.98] disabled:opacity-50 motion-reduce:transition-none';
const textButton =
    'rounded-lg px-1.5 py-1 text-[13px] font-semibold text-rz-accent-app-text underline-offset-4 hover:underline disabled:text-rz-disabled disabled:no-underline';

/** A star the learner earns for each card they know, drawn so it reads the same in both themes. */
function Star({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" aria-hidden className={className}>
            <path
                d="M12 2.8l2.83 5.73 6.33.92-4.58 4.46 1.08 6.3L12 17.24l-5.66 2.97 1.08-6.3L2.84 9.45l6.33-.92L12 2.8z"
                fill="#f5b100"
                stroke="#e09a00"
                strokeWidth="1"
                strokeLinejoin="round"
            />
        </svg>
    );
}

/**
 * The deck's progress: one segment per card in the round, green once known, amber while it is
 * still being learned, and the current card outlined.
 */
function DeckProgress({
    marks,
    current,
    label,
}: {
    marks: ('known' | 'learning' | null)[];
    current: number;
    label: string;
}) {
    return (
        <ol aria-label={label} className="flex flex-1 gap-1">
            {marks.map((mark, index) => (
                <li
                    key={index}
                    className={cn(
                        'h-2 flex-1 rounded-full transition-colors duration-300 motion-reduce:transition-none',
                        mark === 'known' && 'bg-rz-positive',
                        mark === 'learning' && 'bg-[#f0a060]',
                        mark === null &&
                            (index === current
                                ? 'bg-rz-accent-fill/35'
                                : 'bg-rz-surface-sunken'),
                    )}
                />
            ))}
        </ol>
    );
}

/**
 * The flashcards, one at a time (after Hoxhunt's training moments): tap a card to turn it over,
 * then say whether you knew it. A known card earns a star; one still being learned comes back at
 * the end of the deck until it is known. The deck ends on a finish screen that leads to the test
 * while one is open, so the learner is never left without a next step.
 */
function Flashcards({
    cards,
    testOpen,
    testCount,
    passMark,
    closeUrl,
    onStartTest,
}: {
    cards: Card[];
    testOpen: boolean;
    testCount: number;
    passMark: number;
    closeUrl: string;
    onStartTest: () => void;
}) {
    const { t } = useTranslation();
    const fresh = () => cards.map((_, index) => index);
    const [queue, setQueue] = useState<number[]>(fresh);
    const [position, setPosition] = useState(0);
    const [turned, setTurned] = useState(false);
    const [known, setKnown] = useState<number[]>([]);
    const [missed, setMissed] = useState<number[]>([]);
    const [feedback, setFeedback] = useState<'known' | 'learning' | null>(null);
    const done = position >= queue.length;
    const index = queue[position];
    const card = done ? null : cards[index];
    const firstRound = position < cards.length;
    const left = queue.length - position;
    const marks = cards.map((_, cardIndex) => {
        if (known.includes(cardIndex)) {
            return 'known' as const;
        }

        return missed.includes(cardIndex) ? ('learning' as const) : null;
    });

    const restart = () => {
        setQueue(fresh());
        setPosition(0);
        setTurned(false);
        setKnown([]);
        setMissed([]);
        setFeedback(null);
    };

    const rate = (knewIt: boolean) => {
        if (knewIt) {
            setKnown([...known, index]);
        } else {
            if (!missed.includes(index)) {
                setMissed([...missed, index]);
            }

            setQueue([...queue, index]);
        }

        setFeedback(knewIt ? 'known' : 'learning');
        setPosition(position + 1);
        setTurned(false);
    };

    const back = () => {
        const previous = queue[position - 1];
        setKnown(known.filter((cardIndex) => cardIndex !== previous));
        setPosition(position - 1);
        setTurned(false);
        setFeedback(null);
    };

    return (
        <section aria-labelledby="academy-cards" className="mt-5">
            <div className="flex items-center justify-between gap-3">
                <h2
                    id="academy-cards"
                    className="text-[12px] font-bold tracking-[.06em] text-rz-slate uppercase"
                >
                    {t('business.academy.flashcards')}
                </h2>
                <p
                    className="flex items-center gap-1 rounded-full bg-[#fff6db] px-2.5 py-1 text-[12.5px] font-bold text-[#8a5a00] dark:bg-[rgba(245,177,0,.14)] dark:text-[#f5c542]"
                    aria-label={t('business.academy.stars', {
                        count: known.length,
                    })}
                >
                    <Star className="size-4" />
                    <span aria-hidden>{known.length}</span>
                </p>
            </div>
            <div className="mt-3 flex items-center gap-3">
                <DeckProgress
                    marks={marks}
                    current={done ? -1 : index}
                    label={t('business.academy.progress')}
                />
            </div>

            {card !== null ? (
                <>
                    <div className="mt-2 flex items-center justify-between gap-3">
                        <p className="text-[12px] font-semibold text-rz-secondary">
                            {firstRound
                                ? t('business.academy.card_progress', {
                                      current: position + 1,
                                      total: cards.length,
                                  })
                                : t('business.academy.practice_round', {
                                      count: left,
                                  })}
                        </p>
                        {testOpen && (
                            <button
                                type="button"
                                onClick={onStartTest}
                                className={textButton}
                            >
                                {t('business.academy.skip_to_test')}
                            </button>
                        )}
                    </div>
                    <p
                        aria-live="polite"
                        className="mt-2 min-h-[20px] text-center text-[12.5px] font-semibold"
                    >
                        {feedback === 'known' && (
                            <span
                                key={`known-${position}`}
                                className="inline-flex animate-[rz-fade_.35s_ease_both] items-center gap-1 text-rz-positive motion-reduce:animate-none"
                            >
                                <Star className="size-3.5" />
                                {t('business.academy.feedback.known')}
                            </span>
                        )}
                        {feedback === 'learning' && (
                            <span
                                key={`learning-${position}`}
                                className="inline-block animate-[rz-fade_.35s_ease_both] text-rz-warning-text motion-reduce:animate-none"
                            >
                                {t('business.academy.feedback.learning')}
                            </span>
                        )}
                    </p>
                    <div className="mt-2 [perspective:1200px]">
                        <button
                            key={position}
                            type="button"
                            aria-expanded={turned}
                            aria-controls="academy-card-back"
                            onClick={() => setTurned(!turned)}
                            className={cn(
                                'relative grid min-h-[260px] w-full animate-[rz-fade_.35s_ease_both] rounded-[22px] text-left transition-transform duration-500 [transform-style:preserve-3d] motion-reduce:animate-none motion-reduce:transition-none',
                                turned && '[transform:rotateY(180deg)]',
                            )}
                        >
                            <span
                                aria-hidden={turned}
                                className="col-start-1 row-start-1 flex flex-col rounded-[22px] border border-[#d6e4ff] bg-gradient-to-br from-rz-accent-soft to-rz-surface p-6 shadow-rz-lifted [backface-visibility:hidden] dark:border-rz-border"
                            >
                                <span className="text-[11px] font-bold tracking-[.08em] text-rz-accent-app-text uppercase">
                                    {t('business.academy.term')}
                                </span>
                                <span className="flex flex-1 items-center py-6 text-[24px] leading-tight font-semibold text-rz-ink sm:text-[26px]">
                                    {card.front}
                                </span>
                                <span className="flex items-center gap-1.5 text-[12.5px] font-medium text-rz-secondary">
                                    <span aria-hidden>👆</span>
                                    {t('business.academy.flip_hint')}
                                </span>
                            </span>
                            <span
                                id="academy-card-back"
                                aria-hidden={!turned}
                                className="col-start-1 row-start-1 flex [transform:rotateY(180deg)] flex-col rounded-[22px] border border-rz-border bg-rz-surface p-6 shadow-rz-lifted [backface-visibility:hidden]"
                            >
                                {turned && (
                                    <>
                                        <span className="text-[11px] font-bold tracking-[.08em] text-rz-secondary uppercase">
                                            {card.front}
                                        </span>
                                        <span className="flex flex-1 items-center py-5 text-[16px] leading-[1.6] text-rz-ink">
                                            {card.back}
                                        </span>
                                    </>
                                )}
                            </span>
                        </button>
                    </div>
                    <div className="mt-4">
                        {!turned ? (
                            <button
                                type="button"
                                onClick={() => setTurned(true)}
                                className={primaryButton}
                            >
                                {t('business.academy.show_back')}
                            </button>
                        ) : (
                            <div className="grid grid-cols-2 gap-2.5">
                                <button
                                    type="button"
                                    onClick={() => rate(false)}
                                    className="h-[52px] rounded-2xl border-2 border-[#f0a060] bg-rz-surface text-[14.5px] font-semibold text-rz-warning-text transition active:scale-[.98] motion-reduce:transition-none"
                                >
                                    {t('business.academy.still_learning')}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => rate(true)}
                                    className="h-[52px] rounded-2xl bg-rz-positive text-[14.5px] font-semibold text-white shadow-rz-resting transition active:scale-[.98] motion-reduce:transition-none"
                                >
                                    {t('business.academy.got_it')}
                                </button>
                            </div>
                        )}
                    </div>
                    <div className="mt-2 flex justify-center">
                        <button
                            type="button"
                            disabled={position === 0}
                            onClick={back}
                            className={textButton}
                        >
                            {t('business.academy.previous')}
                        </button>
                    </div>
                </>
            ) : (
                <div
                    role="group"
                    aria-labelledby="academy-deck-done"
                    className="mt-5 animate-[rz-fade_.4s_ease_both] rounded-[22px] border border-rz-border bg-rz-surface p-6 text-center shadow-rz-lifted motion-reduce:animate-none"
                >
                    <span className="mx-auto flex size-[84px] items-center justify-center rounded-full bg-[#fff6db] dark:bg-[rgba(245,177,0,.14)]">
                        <Star className="size-12" />
                    </span>
                    <h3
                        id="academy-deck-done"
                        className="mt-4 text-[22px] font-semibold text-rz-ink"
                    >
                        {t('business.academy.deck_done_title')}
                    </h3>
                    <p className="mt-2 text-[14px] leading-normal text-rz-slate">
                        {t('business.academy.deck_done_body', {
                            known: cards.length - missed.length,
                            total: cards.length,
                        })}
                    </p>
                    <p className="mt-1 text-[13px] leading-normal text-rz-secondary">
                        {testOpen
                            ? t('business.academy.deck_done_ready', {
                                  count: testCount,
                                  pass: passMark,
                              })
                            : t('business.academy.deck_done_revise')}
                    </p>
                    <div className="mt-5 flex flex-col gap-2.5">
                        {testOpen ? (
                            <>
                                <button
                                    type="button"
                                    onClick={onStartTest}
                                    className={primaryButton}
                                >
                                    {t('business.academy.start_test')}
                                </button>
                                <button
                                    type="button"
                                    onClick={restart}
                                    className={secondaryButton}
                                >
                                    {t('business.academy.restart_cards')}
                                </button>
                            </>
                        ) : (
                            <>
                                <button
                                    type="button"
                                    onClick={restart}
                                    className={primaryButton}
                                >
                                    {t('business.academy.restart_cards')}
                                </button>
                                <Link
                                    href={closeUrl}
                                    className={cn(
                                        secondaryButton,
                                        'flex items-center justify-center',
                                    )}
                                >
                                    {t('business.academy.back_to_academy')}
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            )}
        </section>
    );
}

/** One answer of a test question: a large tappable choice with its letter. */
function Choice({
    name,
    letter,
    checked,
    onChoose,
    children,
}: {
    name: string;
    letter: string;
    checked: boolean;
    onChoose: () => void;
    children: ReactNode;
}) {
    return (
        <label
            className={cn(
                'flex cursor-pointer items-center gap-3 rounded-2xl border-2 bg-rz-surface px-3.5 py-3 text-[14px] font-medium transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-rz-accent-fill motion-reduce:transition-none',
                checked
                    ? 'border-rz-accent-fill bg-rz-accent-soft text-rz-accent-app-text'
                    : 'border-rz-border text-rz-ink hover:border-rz-accent-fill/50',
            )}
        >
            <input
                type="radio"
                name={name}
                checked={checked}
                onChange={onChoose}
                className="sr-only"
            />
            <span
                aria-hidden
                className={cn(
                    'flex size-8 shrink-0 items-center justify-center rounded-full text-[13px] font-bold',
                    checked
                        ? 'bg-rz-accent-fill text-white'
                        : 'bg-rz-surface-sunken text-rz-slate',
                )}
            >
                {letter}
            </span>
            <span className="flex-1">{children}</span>
        </label>
    );
}

const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];

/**
 * The drawn test, one question at a time, then a check of every answer before it is sent once to
 * be scored on the server. Scoring stays on the server, so no answer is marked right or wrong here.
 */
function Test({
    test,
    attempt,
    passMark,
    revision,
    url,
    onBackToCards,
}: {
    test: AcademyQuestion[];
    attempt: number;
    passMark: number;
    revision: number;
    url: string;
    onBackToCards: () => void;
}) {
    const { t } = useTranslation();
    const form = useForm({ answers: {} as Record<number, number> });
    const { data, errors, processing } = form;
    const fieldErrors = errors as Record<string, string | undefined>;
    const [step, setStep] = useState(0);
    const reviewing = step >= test.length;
    const answered = test.filter(
        (question) => data.answers[question.id] !== undefined,
    ).length;
    const left = test.length - answered;
    const question = reviewing ? null : test[step];

    const choose = (questionId: number, optionId: number) =>
        form.setData('answers', { ...data.answers, [questionId]: optionId });

    return (
        <form
            aria-labelledby="academy-test"
            className="mt-5 animate-[rz-fade_.35s_ease_both] motion-reduce:animate-none"
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
            <div className="flex items-center justify-between gap-3">
                <h2
                    id="academy-test"
                    className="text-[12px] font-bold tracking-[.06em] text-rz-slate uppercase"
                >
                    {t('business.academy.test_title')}
                </h2>
                <button
                    type="button"
                    onClick={onBackToCards}
                    className={textButton}
                >
                    {t('business.academy.back_to_cards')}
                </button>
            </div>
            <p className="mt-1 text-[12.5px] text-rz-secondary">
                {t('business.academy.test_intro', {
                    count: test.length,
                    pass: passMark,
                })}
            </p>
            <ol
                aria-label={t('business.academy.progress')}
                className="mt-3 flex gap-1"
            >
                {test.map((each, index) => {
                    const hasAnswer = data.answers[each.id] !== undefined;
                    const refused = fieldErrors[`answers.${each.id}`];

                    return (
                        <li key={each.id} className="flex-1">
                            <button
                                type="button"
                                onClick={() => setStep(index)}
                                aria-label={t(
                                    'business.academy.go_to_question',
                                    { current: index + 1 },
                                )}
                                aria-current={
                                    index === step ? 'step' : undefined
                                }
                                className="block w-full py-1.5"
                            >
                                <span
                                    className={cn(
                                        'block h-2 rounded-full transition-colors duration-300 motion-reduce:transition-none',
                                        refused
                                            ? 'bg-rz-danger'
                                            : hasAnswer
                                              ? 'bg-rz-accent-fill'
                                              : 'bg-rz-surface-sunken',
                                        index === step &&
                                            'ring-2 ring-rz-accent-fill/40 ring-offset-1 ring-offset-rz-page',
                                    )}
                                />
                            </button>
                        </li>
                    );
                })}
            </ol>
            <FieldError id="academy-form-error">{fieldErrors.form}</FieldError>

            {question !== null ? (
                <fieldset
                    key={question.id}
                    aria-describedby={`academy-answer-${question.id}-error`}
                    className="mt-4 animate-[rz-fade_.3s_ease_both] rounded-[22px] border border-rz-border bg-rz-surface p-5 shadow-rz-lifted motion-reduce:animate-none"
                >
                    <legend className="float-left w-full text-[11px] font-bold tracking-[.06em] text-rz-accent-app-text uppercase">
                        {t('business.academy.question', {
                            current: step + 1,
                            total: test.length,
                        })}
                    </legend>
                    <p className="clear-both pt-2 text-[18px] leading-snug font-semibold text-rz-ink">
                        {question.question}
                    </p>
                    <div className="mt-4 flex flex-col gap-2.5">
                        {question.options.map((option, index) => (
                            <Choice
                                key={option.id}
                                name={`question-${question.id}`}
                                letter={LETTERS[index] ?? String(index + 1)}
                                checked={
                                    data.answers[question.id] === option.id
                                }
                                onChoose={() => choose(question.id, option.id)}
                            >
                                {option.text}
                            </Choice>
                        ))}
                    </div>
                    <FieldError id={`academy-answer-${question.id}-error`}>
                        {fieldErrors[`answers.${question.id}`]}
                    </FieldError>
                    <div className="mt-5 grid grid-cols-[auto_1fr] gap-2.5">
                        <button
                            type="button"
                            disabled={step === 0}
                            onClick={() => setStep(step - 1)}
                            className="h-[52px] rounded-2xl border border-rz-border bg-rz-surface px-5 text-[14px] font-semibold text-rz-ink disabled:opacity-50"
                        >
                            {t('business.academy.previous')}
                        </button>
                        <button
                            type="button"
                            disabled={data.answers[question.id] === undefined}
                            onClick={() => setStep(step + 1)}
                            className={primaryButton}
                        >
                            {step === test.length - 1
                                ? t('business.academy.review_answers')
                                : t('business.academy.next_question')}
                        </button>
                    </div>
                </fieldset>
            ) : (
                <div className="mt-4 animate-[rz-fade_.3s_ease_both] motion-reduce:animate-none">
                    <h3 className="text-[18px] font-semibold text-rz-ink">
                        {t('business.academy.review_title')}
                    </h3>
                    <ol className="mt-3 flex flex-col gap-2">
                        {test.map((each, index) => {
                            const chosen = each.options.find(
                                (option) => option.id === data.answers[each.id],
                            );

                            return (
                                <li
                                    key={each.id}
                                    className="rounded-2xl border border-rz-border bg-rz-surface px-4 py-3"
                                >
                                    <div className="flex items-start gap-3">
                                        <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-rz-accent-soft text-[11.5px] font-bold text-rz-accent-app-text">
                                            {index + 1}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-[13.5px] font-semibold text-rz-ink">
                                                {each.question}
                                            </p>
                                            <p
                                                className={cn(
                                                    'mt-1 text-[13px]',
                                                    chosen
                                                        ? 'text-rz-slate'
                                                        : 'font-semibold text-rz-warning-text',
                                                )}
                                            >
                                                {chosen?.text ??
                                                    t(
                                                        'business.academy.unanswered',
                                                    )}
                                            </p>
                                            <FieldError
                                                id={`academy-review-${each.id}-error`}
                                            >
                                                {
                                                    fieldErrors[
                                                        `answers.${each.id}`
                                                    ]
                                                }
                                            </FieldError>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => setStep(index)}
                                            aria-label={t(
                                                'business.academy.change_answer',
                                                { current: index + 1 },
                                            )}
                                            className={textButton}
                                        >
                                            {t('business.academy.change')}
                                        </button>
                                    </div>
                                </li>
                            );
                        })}
                    </ol>
                    <p className="mt-3 text-[12.5px] text-rz-secondary">
                        {left > 0
                            ? t('business.academy.left', { count: left })
                            : t('business.academy.ready_to_send')}
                    </p>
                    <button
                        type="submit"
                        disabled={left > 0 || processing}
                        aria-busy={processing || undefined}
                        aria-describedby="academy-form-error"
                        className={cn(primaryButton, 'mt-3')}
                    >
                        {processing
                            ? t('business.academy.sending')
                            : t('business.academy.send')}
                    </button>
                </div>
            )}
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
                {result !== null && last !== null && (
                    <div
                        className={cn(
                            'mt-3 flex items-center gap-2.5 rounded-2xl border px-3.5 py-3',
                            last.passed
                                ? 'border-[#cfe9d8] bg-rz-positive-tint'
                                : 'border-[#f3dfbf] bg-rz-warning-tint',
                        )}
                    >
                        <span aria-hidden className="text-[20px]">
                            {last.passed ? '🏆' : '💪'}
                        </span>
                        <p
                            role="status"
                            className="text-[13px] font-medium text-rz-ink"
                        >
                            {result}
                        </p>
                    </div>
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
                {open && testing ? (
                    <Test
                        test={test}
                        attempt={attempt}
                        passMark={pass_mark}
                        revision={identity_context_revision}
                        url={links.submit.url}
                        onBackToCards={() => setTesting(false)}
                    />
                ) : (
                    <Flashcards
                        cards={cards}
                        testOpen={open}
                        testCount={total}
                        passMark={pass_mark}
                        closeUrl={links.close.url}
                        onStartTest={() => setTesting(true)}
                    />
                )}
            </div>
        </BusinessShell>
    );
}
