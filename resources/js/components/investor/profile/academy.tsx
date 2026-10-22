import {
    AMBER,
    BLUE,
    CourseAcademy,
    GREEN,
    finished,
    lessonsOf,
    useCourseProgress,
} from '@/components/rozine/academy';
import type {
    AcademyCourse,
    AcademyGroup,
    Progress,
} from '@/components/rozine/academy';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';

/**
 * The Investor Academy's lessons (design L7735–7811), in the design's tracks. Each topic is a card,
 * then one quick check; the copy restates BRS v2.0 rules. The design's Secondary market, Rewards &
 * growth and Plus tracks are left out: the market is not open, and no points, discounts or
 * referral pay are approved.
 */
export const ACADEMY: readonly AcademyGroup[] = [
    {
        key: 'basics',
        label: 'investor.academy.group.basics',
        lessons: [
            {
                key: 'notes',
                code: 'investor.academy.lesson.notes',
                icon: '📄',
                tint: BLUE,
                topics: [
                    { emoji: '📄', correct: 0 },
                    { emoji: '📆', correct: 1 },
                ],
            },
            {
                key: 'wallet',
                code: 'investor.academy.lesson.wallet',
                icon: '👛',
                tint: AMBER,
                topics: [
                    { emoji: '💰', correct: 2 },
                    { emoji: '🏦', correct: 0 },
                ],
            },
            {
                key: 'rating',
                code: 'investor.academy.lesson.rating',
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
        label: 'investor.academy.group.mission',
        lessons: [
            {
                key: 'mission',
                code: 'investor.academy.lesson.mission',
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
        label: 'investor.academy.group.investing',
        lessons: [
            {
                key: 'fundamentals',
                code: 'investor.academy.lesson.fundamentals',
                icon: '📚',
                tint: BLUE,
                topics: [
                    { emoji: '⚖', correct: 2 },
                    { emoji: '🎯', correct: 0 },
                ],
            },
            {
                key: 'diversify',
                code: 'investor.academy.lesson.diversify',
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
        label: 'investor.academy.group.rozine',
        lessons: [
            {
                key: 'deals',
                code: 'investor.academy.lesson.deals',
                icon: '🔎',
                tint: BLUE,
                topics: [
                    { emoji: '📋', correct: 2 },
                    { emoji: '💵', correct: 1 },
                ],
            },
            {
                key: 'repayments',
                code: 'investor.academy.lesson.repayments',
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

export const INVESTOR_ACADEMY: AcademyCourse = {
    storageKey: 'rozine.investor.academy.v1',
    title: 'investor.academy.title',
    intro: 'investor.academy.intro',
    groups: ACADEMY,
};

const LESSONS = lessonsOf(INVESTOR_ACADEMY);

/** The Investor Academy's progress on this device. */
export const useAcademyProgress = () => useCourseProgress(INVESTOR_ACADEMY);

/** Lessons done of the Academy's total once it is started; null before it starts or once it is done. */
export function useAcademyNudge(): { done: number; total: number } | null {
    const [progress] = useAcademyProgress();
    const done = LESSONS.filter((lesson) => finished(lesson, progress)).length;
    const started = Object.values(progress).some((value) => value > 0);

    return started && done < LESSONS.length
        ? { done, total: LESSONS.length }
        : null;
}

/** The Investor Academy in the profile column. */
export function Academy(
    props: Omit<Parameters<typeof CourseAcademy>[0], 'course'>,
) {
    return <CourseAcademy course={INVESTOR_ACADEMY} {...props} />;
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
