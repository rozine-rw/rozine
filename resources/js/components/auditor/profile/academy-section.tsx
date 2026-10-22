import { useState } from 'react';
import { CARD, Eyebrow, PRIMARY } from '@/components/auditor/ui';
import {
    AMBER,
    BLUE,
    CourseAcademy,
    GREEN,
    finished,
    lessonsOf,
    useCourseProgress,
} from '@/components/rozine/academy';
import type { AcademyCourse, AcademyLesson } from '@/components/rozine/academy';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/**
 * The Auditor Academy's lessons, in the design's tracks (Auditor design "academy"). The design's
 * own copy predates BRS v2.0 (1.25 coverage tiers, a 15 km radius, first-to-accept jobs and a 2%
 * repayment fee), so every lesson restates the BRS rules instead: AUD-R01–R08, the 1.50 coverage
 * offer and the Audit Partner pay in section 14. The design's points, tiers and Academy Wallet are
 * left out, since lessons earn nothing.
 */
export const AUDITOR_ACADEMY: AcademyCourse = {
    storageKey: 'rozine.auditor.academy.v1',
    title: 'auditor.academy.title',
    intro: 'auditor.academy.intro',
    groups: [
        {
            key: 'role',
            label: 'auditor.academy.group.role',
            lessons: [
                {
                    key: 'role',
                    code: 'auditor.academy.lesson.role',
                    icon: '🧾',
                    tint: AMBER,
                    topics: [
                        { emoji: '🧾', correct: 0 },
                        { emoji: '🪪', correct: 1 },
                    ],
                },
                {
                    key: 'independence',
                    code: 'auditor.academy.lesson.independence',
                    icon: '⚖',
                    tint: BLUE,
                    topics: [
                        { emoji: '🤝', correct: 2 },
                        { emoji: '🔒', correct: 0 },
                    ],
                },
            ],
        },
        {
            key: 'assignments',
            label: 'auditor.academy.group.assignments',
            lessons: [
                {
                    key: 'dispatch',
                    code: 'auditor.academy.lesson.dispatch',
                    icon: '📍',
                    tint: GREEN,
                    topics: [
                        { emoji: '📍', correct: 1 },
                        { emoji: '🔄', correct: 2 },
                    ],
                },
            ],
        },
        {
            key: 'verification',
            label: 'auditor.academy.group.verification',
            lessons: [
                {
                    key: 'baseline',
                    code: 'auditor.academy.lesson.baseline',
                    icon: '🏪',
                    tint: AMBER,
                    topics: [
                        { emoji: '🏪', correct: 0 },
                        { emoji: '📷', correct: 1 },
                    ],
                },
                {
                    key: 'differences',
                    code: 'auditor.academy.lesson.differences',
                    icon: '🔏',
                    tint: BLUE,
                    topics: [
                        { emoji: '🔍', correct: 2 },
                        { emoji: '🔏', correct: 0 },
                    ],
                },
                {
                    key: 'numbers',
                    code: 'auditor.academy.lesson.numbers',
                    icon: '📊',
                    tint: GREEN,
                    topics: [
                        { emoji: '💵', correct: 0 },
                        { emoji: '📐', correct: 1 },
                    ],
                },
            ],
        },
        {
            key: 'monthly',
            label: 'auditor.academy.group.monthly',
            lessons: [
                {
                    key: 'cycle',
                    code: 'auditor.academy.lesson.cycle',
                    icon: '🗓',
                    tint: AMBER,
                    topics: [
                        { emoji: '🗓', correct: 0 },
                        { emoji: '⏰', correct: 1 },
                    ],
                },
                {
                    key: 'disputes',
                    code: 'auditor.academy.lesson.disputes',
                    icon: '💬',
                    tint: BLUE,
                    topics: [
                        { emoji: '⏳', correct: 2 },
                        { emoji: '🗂', correct: 0 },
                    ],
                },
            ],
        },
        {
            key: 'visits',
            label: 'auditor.academy.group.visits',
            lessons: [
                {
                    key: 'flash',
                    code: 'auditor.academy.lesson.flash',
                    icon: '⚡',
                    tint: AMBER,
                    topics: [
                        { emoji: '⚡', correct: 0 },
                        { emoji: '🚨', correct: 1 },
                    ],
                },
                {
                    key: 'arrears',
                    code: 'auditor.academy.lesson.arrears',
                    icon: '🚶',
                    tint: BLUE,
                    topics: [
                        { emoji: '📅', correct: 2 },
                        { emoji: '✉', correct: 0 },
                    ],
                },
            ],
        },
        {
            key: 'pay',
            label: 'auditor.academy.group.pay',
            lessons: [
                {
                    key: 'earnings',
                    code: 'auditor.academy.lesson.earnings',
                    icon: '💰',
                    tint: GREEN,
                    topics: [
                        { emoji: '🏦', correct: 2 },
                        { emoji: '📈', correct: 0 },
                    ],
                },
            ],
        },
    ],
};

const LESSONS = lessonsOf(AUDITOR_ACADEMY);

type View = { kind: 'card' } | { kind: 'browse'; lesson: AcademyLesson | null };

/**
 * Profile's Auditor Academy section (design "ACADEMY"): the lessons done so far, Resume on the
 * next unfinished lesson and Browse all lessons. Progress is kept on this device only.
 */
export function AcademySection() {
    const { t } = useTranslation();
    const [progress, setProgress] = useCourseProgress(AUDITOR_ACADEMY);
    const [view, setView] = useState<View>({ kind: 'card' });
    const done = LESSONS.filter((lesson) => finished(lesson, progress)).length;
    const next = LESSONS.find((lesson) => !finished(lesson, progress));

    if (view.kind === 'browse') {
        return (
            <CourseAcademy
                course={AUDITOR_ACADEMY}
                progress={progress}
                onProgress={setProgress}
                onBack={() => setView({ kind: 'card' })}
                initialLesson={view.lesson}
            />
        );
    }

    return (
        <section aria-labelledby="auditor-academy">
            <Eyebrow as="h2">
                <span id="auditor-academy">{t('auditor.academy.title')}</span>
            </Eyebrow>
            <div className={cn(CARD, 'mt-2.5 p-[15px]')}>
                <div className="flex items-center gap-[13px]">
                    <p className="min-w-0 flex-1 text-sm font-bold text-rz-ink">
                        {done === 0
                            ? t('auditor.academy.summary_start')
                            : t('investor.academy.done_count', {
                                  done,
                                  total: LESSONS.length,
                              })}
                    </p>
                    <span className="shrink-0 text-[10px] font-bold text-rz-ink">
                        {t('investor.academy.tracks', {
                            count: AUDITOR_ACADEMY.groups.length,
                        })}
                    </span>
                </div>
                <div className="mt-3 h-1.5 overflow-hidden rounded bg-[#eef2f9] dark:bg-rz-surface-muted">
                    <div
                        className="h-full rounded bg-[#c2661f]"
                        style={{
                            width: `${Math.round((done / LESSONS.length) * 100)}%`,
                        }}
                    />
                </div>
                <button
                    type="button"
                    onClick={() =>
                        setView({ kind: 'browse', lesson: next ?? null })
                    }
                    className={cn(
                        PRIMARY,
                        'mt-[13px] h-11 w-full rounded-xl text-[13px]',
                    )}
                >
                    {next === undefined
                        ? t('auditor.academy.review')
                        : t('auditor.academy.resume', {
                              lesson: t(`${next.code}.title`),
                          })}
                </button>
                <button
                    type="button"
                    onClick={() => setView({ kind: 'browse', lesson: null })}
                    className="mt-2 h-10 w-full rounded-xl bg-[#f1f4f9] text-[13px] font-bold text-[#46526b] dark:bg-rz-surface-muted dark:text-rz-slate"
                >
                    {t('auditor.academy.browse')}
                </button>
            </div>
        </section>
    );
}
