import { Link } from '@inertiajs/react';
import { BusinessShell } from '@/components/business/business-shell';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AcademyModuleSummary,
    BusinessAcademyProps,
} from '@/types/business';

const STAGE_KEY = {
    1: 'business.readiness.stage.1',
    2: 'business.readiness.stage.2',
    3: 'business.readiness.stage.3',
    4: 'business.readiness.stage.4',
} as const;

const STATE_TONE = {
    open: 'bg-rz-accent-soft text-rz-accent-app-text',
    passed: 'bg-[rgba(31,138,76,.12)] text-[#17663a] dark:text-[#7fd6a2]',
    waiting: 'bg-[rgba(194,102,31,.12)] text-[#8a4710] dark:text-[#f0b27a]',
    preview: 'bg-rz-page text-rz-secondary',
} as const;

function ModuleCard({
    module,
    total,
}: {
    module: AcademyModuleSummary;
    total: number;
}) {
    const { t, locale } = useTranslation();
    const state = module.status?.state ?? 'preview';

    return (
        <li>
            <Link
                href={module.link.url}
                className="block rounded-2xl border border-rz-border bg-rz-surface p-3.5"
            >
                <span className="flex items-start justify-between gap-3">
                    <span className="text-[14px] font-semibold text-rz-ink">
                        {module.title}
                    </span>
                    <span
                        className={cn(
                            'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                            STATE_TONE[state],
                        )}
                    >
                        {state === 'waiting' && module.status?.retake_at
                            ? t('business.academy.state.waiting', {
                                  date: formatDateTime(
                                      module.status.retake_at,
                                      locale,
                                  ),
                              })
                            : t(`business.academy.state.${state}`)}
                    </span>
                </span>
                <span className="mt-1 block text-[12.5px] text-rz-secondary">
                    {module.summary}
                </span>
                <span className="mt-1.5 block text-[12px] text-rz-secondary">
                    {t('business.academy.cards', { count: module.cards })}
                    {module.status?.best != null &&
                        ` · ${t('business.academy.best', {
                            score: module.status.best,
                            total,
                        })}`}
                </span>
            </Link>
        </li>
    );
}

/**
 * The Business Academy (BRS v3.0 RDY-R07): the core modules of the business's stage, each with
 * where its test stands, then the next stage's core as a preview. Questions appear only inside a
 * module's test.
 */
export default function BusinessAcademy({
    level,
    pass_mark,
    questions,
    core,
    preview,
    links,
}: BusinessAcademyProps) {
    const { t } = useTranslation();
    const stageName = (stage: 1 | 2 | 3 | 4) => t(STAGE_KEY[stage]);
    const next = level < 4 ? ((level + 1) as 2 | 3 | 4) : null;

    return (
        <BusinessShell
            title={t('business.academy.title')}
            tab="readiness"
            links={links}
        >
            <div className="mx-auto max-w-[620px] px-5 pt-[calc(env(safe-area-inset-top)+4px)] pb-[92px] lg:pt-6">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.close.url}
                        aria-label={t('business.academy.back')}
                        className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <h1 className="text-2xl font-semibold text-rz-ink">
                        {t('business.academy.title')}
                    </h1>
                </div>
                <p className="mt-3 text-[13px] leading-[1.55] text-rz-secondary">
                    {t('business.academy.intro', {
                        questions,
                        pass: pass_mark,
                    })}
                </p>
                <h2
                    id="academy-core"
                    className="mt-6 text-base font-semibold text-rz-ink"
                >
                    {t('business.academy.core_title', {
                        stage: stageName(level),
                    })}
                </h2>
                <ul aria-labelledby="academy-core" className="mt-3 space-y-2">
                    {core.map((module) => (
                        <ModuleCard
                            key={module.key}
                            module={module}
                            total={questions}
                        />
                    ))}
                </ul>
                {next !== null && (
                    <>
                        <h2
                            id="academy-preview"
                            className="mt-6 text-base font-semibold text-rz-ink"
                        >
                            {t('business.academy.preview_title', {
                                stage: stageName(next),
                            })}
                        </h2>
                        <p className="mt-1 text-[12.5px] text-rz-secondary">
                            {t('business.academy.preview_body')}
                        </p>
                        <ul
                            aria-labelledby="academy-preview"
                            className="mt-3 space-y-2"
                        >
                            {preview.map((module) => (
                                <ModuleCard
                                    key={module.key}
                                    module={module}
                                    total={questions}
                                />
                            ))}
                        </ul>
                    </>
                )}
            </div>
        </BusinessShell>
    );
}
