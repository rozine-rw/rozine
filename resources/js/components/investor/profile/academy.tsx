import { useState } from 'react';
import { POSITIVE_TEXT } from '@/components/investor/tokens';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

type LessonKey =
    | 'notes'
    | 'wallet'
    | 'rating'
    | 'mission'
    | 'fundamentals'
    | 'diversify'
    | 'deals'
    | 'repayments';

/** Every topic has three answers; `correct` is the right one's place. */
type Topic = { emoji: string; correct: 0 | 1 | 2 };

type Lesson = {
    key: LessonKey;
    icon: string;
    tint: string;
    topics: readonly [Topic, Topic];
};

type LessonGroup = {
    key: 'basics' | 'mission' | 'investing' | 'rozine';
    lessons: readonly Lesson[];
};

const OPTIONS = [1, 2, 3] as const;

const BLUE = 'bg-[rgba(30,58,255,.10)]';
const GREEN = 'bg-[rgba(29,158,117,.10)]';
const AMBER = 'bg-[rgba(194,102,31,.10)]';

/**
 * The Investor Academy's lessons (design L7735–7811), in the design's tracks. Each topic is a card,
 * then one quick check; the copy restates BRS v2.0 rules. The design's Secondary market, Rewards &
 * growth and Plus tracks are left out: the market is not open, and no points, discounts or
 * referral pay are approved.
 */
export const ACADEMY: readonly LessonGroup[] = [
    {
        key: 'basics',
        lessons: [
            {
                key: 'notes',
                icon: '📄',
                tint: BLUE,
                topics: [
                    { emoji: '📄', correct: 0 },
                    { emoji: '📆', correct: 1 },
                ],
            },
            {
                key: 'wallet',
                icon: '👛',
                tint: AMBER,
                topics: [
                    { emoji: '💰', correct: 2 },
                    { emoji: '🏦', correct: 0 },
                ],
            },
            {
                key: 'rating',
                icon: '📊',
                tint: BLUE,
                topics: [
                    { emoji: '🎯', correct: 1 },
                    { emoji: '🧭', correct: 2 },
                ],
            },
        ],
    },
    {
        key: 'mission',
        lessons: [
            {
                key: 'mission',
                icon: '🚀',
                tint: GREEN,
                topics: [
                    { emoji: '🌐', correct: 0 },
                    { emoji: '🔎', correct: 1 },
                ],
            },
        ],
    },
    {
        key: 'investing',
        lessons: [
            {
                key: 'fundamentals',
                icon: '📚',
                tint: BLUE,
                topics: [
                    { emoji: '⚖', correct: 2 },
                    { emoji: '🎯', correct: 0 },
                ],
            },
            {
                key: 'diversify',
                icon: '🧩',
                tint: GREEN,
                topics: [
                    { emoji: '🌰', correct: 1 },
                    { emoji: '🔀', correct: 0 },
                ],
            },
        ],
    },
    {
        key: 'rozine',
        lessons: [
            {
                key: 'deals',
                icon: '🔎',
                tint: BLUE,
                topics: [
                    { emoji: '📋', correct: 2 },
                    { emoji: '💵', correct: 1 },
                ],
            },
            {
                key: 'repayments',
                icon: '🔄',
                tint: GREEN,
                topics: [
                    { emoji: '🗓', correct: 0 },
                    { emoji: '🔁', correct: 2 },
                ],
            },
        ],
    },
];

const LESSONS = ACADEMY.flatMap((group) => group.lessons);

/** Topics finished per lesson. Kept on this device only: a convenience, never a record. */
type Progress = Record<string, number>;

const STORAGE_KEY = 'rozine.investor.academy.v1';

const readProgress = (): Progress => {
    try {
        const stored = JSON.parse(
            window.localStorage.getItem(STORAGE_KEY) ?? '{}',
        ) as unknown;

        return typeof stored === 'object' &&
            stored !== null &&
            !Array.isArray(stored)
            ? Object.fromEntries(
                  Object.entries(stored).filter(
                      ([, value]) => typeof value === 'number',
                  ),
              )
            : {};
    } catch {
        return {};
    }
};

const writeProgress = (progress: Progress): void => {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(progress));
    } catch {
        /* Storage can be blocked; progress then lasts for this visit only. */
    }
};

const finished = (lesson: Lesson, progress: Progress): boolean =>
    (progress[lesson.key] ?? 0) >= lesson.topics.length;

/** The Academy's progress, read once from this device and saved on every change. */
export function useAcademyProgress(): [Progress, (next: Progress) => void] {
    const [progress, setProgress] = useState<Progress>(readProgress);

    return [
        progress,
        (next: Progress) => {
            writeProgress(next);
            setProgress(next);
        },
    ];
}

/**
 * The Learning card under the profile menu (design L2810–2827): the Academy with the lessons done
 * so far. The design's points total is left out, since lessons earn nothing.
 */
export function AcademyCard({
    progress,
    onOpen,
}: {
    progress: Progress;
    onOpen: () => void;
}) {
    const { t } = useTranslation();
    const done = LESSONS.filter((lesson) => finished(lesson, progress)).length;
    const total = LESSONS.length;
    const started = Object.values(progress).some((value) => value > 0);

    return (
        <>
            <h2 className="mt-[18px] text-[13px] font-semibold text-rz-secondary">
                {t('investor.academy.learning')}
            </h2>
            <button
                type="button"
                onClick={onOpen}
                className="mt-2.5 block w-full rounded-2xl border border-[#f6e7c8] bg-[linear-gradient(145deg,#fdf3e0,#fdf6ea)] p-[17px] text-left dark:border-[rgba(240,160,96,.3)] dark:bg-[rgba(240,160,96,.08)] dark:bg-none"
            >
                <span className="flex items-center gap-[13px]">
                    <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[rgba(194,102,31,.2)] text-[21px]">
                        <Icon name="graduation" tone="amber" />
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="block text-[15.5px] font-semibold text-rz-ink">
                            {t('investor.academy.title')}
                        </span>
                        <span className="mt-0.5 block text-[12.5px] text-[#8a6d33] dark:text-[#f0a060]">
                            {t(
                                done >= total
                                    ? 'investor.academy.summary_done'
                                    : started
                                      ? 'investor.academy.summary_resume'
                                      : 'investor.academy.summary_start',
                            )}
                        </span>
                    </span>
                    <span aria-hidden className="text-lg text-rz-ink">
                        ›
                    </span>
                </span>
                <span className="mt-3.5 block h-1.5 overflow-hidden rounded-[3px] bg-[rgba(181,121,26,.10)]">
                    <span
                        className="block h-full bg-[#c2661f]"
                        style={{
                            width: `${Math.round((done / total) * 100)}%`,
                        }}
                    />
                </span>
                <span className="mt-[7px] flex items-center justify-between text-[11.5px] font-medium text-[#8a6d33] dark:text-[#f0a060]">
                    <span>
                        {t('investor.academy.done_count', { done, total })}
                    </span>
                    <span className="font-semibold text-rz-ink">
                        {t('investor.academy.tracks', {
                            count: ACADEMY.length,
                        })}
                    </span>
                </span>
            </button>
        </>
    );
}

/**
 * The Academy (design L2219–2262): tracks of lessons, each with its progress. Opening a lesson
 * plays it in the same column (L5250–5300), as the BRS asks of desktop overlays.
 */
export function Academy({
    progress,
    onProgress,
    onBack,
}: {
    progress: Progress;
    onProgress: (next: Progress) => void;
    onBack: (() => void) | null;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState<Lesson | null>(null);
    const Heading = onBack === null ? 'h2' : 'h1';

    if (open !== null) {
        return (
            <LessonPlayer
                lesson={open}
                onClose={() => setOpen(null)}
                onTopicDone={(reached) =>
                    onProgress({
                        ...progress,
                        [open.key]: Math.max(progress[open.key] ?? 0, reached),
                    })
                }
            />
        );
    }

    return (
        <>
            <div className="flex items-center gap-3">
                {onBack !== null && (
                    <button
                        type="button"
                        onClick={onBack}
                        aria-label={t('investor.common.back')}
                        className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg leading-none text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </button>
                )}
                <Heading className="text-xl font-semibold text-rz-ink">
                    {t('investor.academy.title')}
                </Heading>
            </div>
            <p className="mt-3 text-[13px] leading-normal text-rz-secondary">
                {t('investor.academy.intro')}
            </p>
            {ACADEMY.map((group) => {
                const done = group.lessons.filter((lesson) =>
                    finished(lesson, progress),
                ).length;

                return (
                    <section
                        key={group.key}
                        aria-label={t(`investor.academy.group.${group.key}`)}
                    >
                        <div className="mt-5 flex items-center justify-between">
                            <h3 className="text-xs font-bold tracking-[.03em] text-rz-slate uppercase">
                                {t(`investor.academy.group.${group.key}`)}
                            </h3>
                            <span className="text-[11px] font-semibold text-rz-secondary">
                                {t('investor.academy.group_progress', {
                                    done,
                                    total: group.lessons.length,
                                })}
                            </span>
                        </div>
                        <ul className="mt-[11px] flex flex-col gap-[11px]">
                            {group.lessons.map((lesson) => {
                                const reached = Math.min(
                                    progress[lesson.key] ?? 0,
                                    lesson.topics.length,
                                );
                                const complete =
                                    reached >= lesson.topics.length;

                                return (
                                    <li key={lesson.key}>
                                        <button
                                            type="button"
                                            onClick={() => setOpen(lesson)}
                                            className="flex w-full flex-col gap-3 rounded-2xl border border-rz-border bg-rz-surface p-[15px] text-left"
                                        >
                                            <span className="flex items-center gap-[13px]">
                                                <span
                                                    aria-hidden
                                                    className={cn(
                                                        'flex size-10 shrink-0 items-center justify-center rounded-[10px] text-lg',
                                                        lesson.tint,
                                                    )}
                                                >
                                                    {lesson.icon}
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <span className="block text-sm font-semibold text-rz-ink">
                                                        {t(
                                                            `investor.academy.lesson.${lesson.key}.title`,
                                                        )}
                                                    </span>
                                                    <span className="mt-0.5 block text-xs text-rz-secondary">
                                                        {t(
                                                            `investor.academy.lesson.${lesson.key}.sub`,
                                                        )}
                                                    </span>
                                                </span>
                                                <span
                                                    className={cn(
                                                        'shrink-0 text-[11.5px] font-semibold',
                                                        complete
                                                            ? POSITIVE_TEXT
                                                            : 'text-rz-accent-app-text',
                                                    )}
                                                >
                                                    {complete
                                                        ? t(
                                                              'investor.academy.status.done',
                                                          )
                                                        : reached > 0
                                                          ? t(
                                                                'investor.academy.status.progress',
                                                                {
                                                                    done: reached,
                                                                    total: lesson
                                                                        .topics
                                                                        .length,
                                                                },
                                                            )
                                                          : t(
                                                                'investor.academy.status.start',
                                                            )}
                                                </span>
                                            </span>
                                            <span className="block h-[5px] overflow-hidden rounded-[3px] bg-[#eef2f9] dark:bg-rz-surface-muted">
                                                <span
                                                    className={cn(
                                                        'block h-full rounded-[3px]',
                                                        complete
                                                            ? 'bg-[#17795a]'
                                                            : 'bg-rz-accent-fill',
                                                    )}
                                                    style={{
                                                        width: `${Math.round((reached / lesson.topics.length) * 100)}%`,
                                                    }}
                                                />
                                            </span>
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    </section>
                );
            })}
        </>
    );
}

type Phase = 'card' | 'quiz' | 'feedback' | 'done';

/**
 * One lesson (design L5250–5300): each topic's card, then its quick check. A wrong answer brings
 * that topic back once at the end, as the design does; a topic counts as reached when its check
 * is passed.
 */
function LessonPlayer({
    lesson,
    onClose,
    onTopicDone,
}: {
    lesson: Lesson;
    onClose: () => void;
    onTopicDone: (reached: number) => void;
}) {
    const { t } = useTranslation();
    const [queue, setQueue] = useState<number[]>([0, 1]);
    const [position, setPosition] = useState(0);
    const [phase, setPhase] = useState<Phase>('card');
    const [correct, setCorrect] = useState(false);
    const [passed, setPassed] = useState<number[]>([]);
    const [retried, setRetried] = useState<number[]>([]);
    const index = queue[position] as number;
    const topic = lesson.topics[index];
    const number = index === 0 ? 1 : 2;
    const prefix =
        `investor.academy.lesson.${lesson.key}.topic${number}` as const;
    const last = position + 1 >= queue.length;

    const answer = (option: number) => {
        const right = option === topic.correct;
        setCorrect(right);
        setPhase('feedback');

        if (right) {
            /* A passed topic never comes back, so each pass is a new one. */
            const next = [...passed, index];
            setPassed(next);
            onTopicDone(next.length);
        } else if (!retried.includes(index)) {
            setRetried([...retried, index]);
            setQueue([...queue, index]);
        }
    };

    const advance = () => {
        if (last) {
            setPhase('done');
        } else {
            setPosition(position + 1);
            setPhase('card');
        }
    };

    const primary =
        'mt-4 h-[52px] w-full rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white';

    return (
        <section aria-label={t(`investor.academy.lesson.${lesson.key}.title`)}>
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    onClick={onClose}
                    aria-label={t('investor.academy.close')}
                    className="size-[38px] shrink-0 rounded-[10px] border border-rz-border bg-rz-surface text-base text-rz-ink"
                >
                    <span aria-hidden>✕</span>
                </button>
                <div
                    role="progressbar"
                    aria-label={t('investor.academy.progress')}
                    aria-valuemin={0}
                    aria-valuemax={queue.length}
                    aria-valuenow={phase === 'done' ? queue.length : position}
                    className="h-1.5 flex-1 overflow-hidden rounded bg-[#e9edf4] dark:bg-rz-surface-muted"
                >
                    <div
                        className="h-full bg-rz-accent-fill transition-[width] duration-300 motion-reduce:transition-none"
                        style={{
                            width: `${Math.round(((phase === 'done' ? queue.length : position) / queue.length) * 100)}%`,
                        }}
                    />
                </div>
                <span className="text-xs font-semibold text-rz-secondary">
                    {t('investor.academy.step', {
                        step: Math.min(position + 1, queue.length),
                        total: queue.length,
                    })}
                </span>
            </div>
            <h2 className="mt-[9px] text-[12.5px] font-semibold tracking-[.04em] text-rz-accent-app-text">
                {t(`investor.academy.lesson.${lesson.key}.title`)}
            </h2>

            <div className="pt-1.5 pb-[30px]">
                {phase === 'card' && (
                    <>
                        <div className="mt-3 flex min-h-[220px] flex-col justify-center rounded-[20px] border border-[#d6e4ff] bg-rz-accent-soft p-6 dark:border-rz-border">
                            <p aria-hidden className="text-[26px]">
                                {topic.emoji}
                            </p>
                            <p className="mt-3.5 text-[19px] font-semibold text-rz-ink">
                                {t(`${prefix}.title`)}
                            </p>
                            <p className="mt-2.5 text-sm leading-[1.65] text-rz-slate">
                                {t(`${prefix}.body`)}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setPhase('quiz')}
                            className={primary}
                        >
                            {t('investor.academy.got_it')}
                        </button>
                    </>
                )}
                {phase === 'quiz' && (
                    <fieldset className="mt-3">
                        <legend className="text-[11.5px] font-semibold tracking-[.05em] text-rz-secondary uppercase">
                            {t('investor.academy.quick_check')}
                        </legend>
                        <p className="mt-2 text-lg font-semibold text-rz-ink">
                            {t(`${prefix}.q`)}
                        </p>
                        <div className="mt-4 flex flex-col gap-2.5">
                            {OPTIONS.map((option) => (
                                <button
                                    key={option}
                                    type="button"
                                    onClick={() => answer(option - 1)}
                                    className="rounded-xl border border-rz-border bg-rz-surface p-[15px] text-left text-sm font-semibold text-rz-ink"
                                >
                                    {t(`${prefix}.option${option}`)}
                                </button>
                            ))}
                        </div>
                    </fieldset>
                )}
                {phase === 'feedback' && (
                    <>
                        <div
                            role="status"
                            className={cn(
                                'mt-3 rounded-2xl border p-[22px] text-center',
                                correct
                                    ? 'border-[#cfe9d8] bg-[#f0f9f3] dark:border-[rgba(63,205,160,.25)] dark:bg-[rgba(63,205,160,.08)]'
                                    : 'border-[#f3dfbf] bg-[#fff8ee] dark:border-[rgba(240,160,96,.3)] dark:bg-[rgba(240,160,96,.08)]',
                            )}
                        >
                            <p aria-hidden className="text-[31px]">
                                {correct ? '🎉' : '🤔'}
                            </p>
                            <p className="mt-3 text-lg font-semibold text-rz-ink">
                                {t(
                                    correct
                                        ? 'investor.academy.correct'
                                        : 'investor.academy.wrong',
                                )}
                            </p>
                            <p className="mt-2 text-[13.5px] leading-[1.6] text-rz-slate">
                                {t(
                                    correct
                                        ? 'investor.academy.correct_body'
                                        : 'investor.academy.wrong_body',
                                )}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={advance}
                            className={primary}
                        >
                            {t(
                                last
                                    ? 'investor.academy.finish'
                                    : 'investor.academy.next',
                            )}
                        </button>
                    </>
                )}
                {phase === 'done' && (
                    <div className="mt-[30px] text-center">
                        <span className="mx-auto flex size-[84px] items-center justify-center rounded-full bg-[rgba(29,158,117,.14)]">
                            <span className="flex size-14 items-center justify-center rounded-full bg-[#17795a]">
                                <svg
                                    width="26"
                                    height="26"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden
                                >
                                    <path
                                        d="M5 13l4 4L19 7"
                                        stroke="#fff"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    />
                                </svg>
                            </span>
                        </span>
                        <p className="mt-[18px] text-[22px] font-semibold text-rz-ink">
                            {t('investor.academy.done_title')}
                        </p>
                        <p className="mt-2 text-[13.5px] leading-normal text-rz-slate">
                            {t('investor.academy.done_body', {
                                lesson: t(
                                    `investor.academy.lesson.${lesson.key}.title`,
                                ),
                            })}
                        </p>
                        <button
                            type="button"
                            onClick={onClose}
                            className={primary}
                        >
                            {t('investor.academy.done_cta')}
                        </button>
                    </div>
                )}
            </div>
        </section>
    );
}
