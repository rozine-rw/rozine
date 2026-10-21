import { useState } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import type { DesignColumn } from '@/components/admin/section-designs';
import {
    CAPTION,
    CardTitle,
    EmptyState,
    HeadCell,
    KpiTile,
    ROW_RULE,
    TABLE_HEAD,
    TableCard,
} from '@/components/admin/ui';
import { ACADEMY } from '@/components/investor/profile/academy';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AdminPendingSectionProps } from '@/types/admin';

const TABS = ['investor', 'business', 'auditor', 'all'] as const;

type Tab = (typeof TABS)[number];

const COLUMNS: DesignColumn[] = ['lesson', 'track', 'topics'];

const GRID =
    'grid grid-cols-[minmax(0,1.6fr)_minmax(0,1.2fr)_minmax(0,.8fr)] gap-3 px-5';

/** A figure no server read states: the tile keeps its place and says nothing. */
const NO_FIGURE = '—';

const LESSONS = ACADEMY.flatMap((group) =>
    group.lessons.map((lesson) => ({ group: group.key, lesson })),
);

const TOPICS = LESSONS.reduce(
    (count, { lesson }) => count + lesson.topics.length,
    0,
);

/**
 * Academies: the lessons each app teaches, by track, with how many topics each holds (every topic
 * ends in one quick check). Only the Investor Academy has lessons yet; the Business and Auditor
 * academies keep their empty state. A learner's progress stays on their own device, so lessons
 * completed, quiz scores and points are not reported here, and nothing can be edited.
 */
export function AdminAcademies(props: AdminPendingSectionProps) {
    const { t } = useTranslation();
    const [tab, setTab] = useState<Tab>('investor');
    const title = t('admin.section.academies.title');
    const rows = tab === 'investor' || tab === 'all' ? LESSONS : [];

    return (
        <AdminFrame {...props}>
            <section aria-label={title}>
                <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                    <KpiTile
                        index={0}
                        label={t('admin.design.academies.kpi.live')}
                        value={String(LESSONS.length)}
                    />
                    <KpiTile
                        index={1}
                        label={t('admin.design.academies.kpi.tracks')}
                        value={String(ACADEMY.length)}
                    />
                    <KpiTile
                        index={2}
                        label={t('admin.design.academies.kpi.checks')}
                        value={String(TOPICS)}
                    />
                    <KpiTile
                        index={3}
                        label={t('admin.design.academies.kpi.completed')}
                        value={NO_FIGURE}
                        sub={t('admin.design.academies.kpi.completed.sub')}
                    />
                </div>
                <div
                    role="tablist"
                    aria-label={t('admin.design.tabs', { section: title })}
                    className="rz-hscroll mb-[18px] flex gap-2 overflow-x-auto"
                >
                    {TABS.map((item) => (
                        <button
                            key={item}
                            type="button"
                            role="tab"
                            aria-selected={item === tab}
                            onClick={() => setTab(item)}
                            className={cn(
                                'shrink-0 rounded-[10px] border px-[15px] py-2 text-[13px] font-semibold whitespace-nowrap',
                                item === tab
                                    ? 'border-rz-accent-fill bg-rz-accent-fill text-white'
                                    : 'border-rz-hairline bg-rz-surface text-rz-slate',
                            )}
                        >
                            {t(`admin.design.academies.tab.${item}`)}
                        </button>
                    ))}
                </div>
                <div className="mb-3.5">
                    <CardTitle>
                        {t('admin.design.academies.lessons.title')}
                    </CardTitle>
                    <p className={cn('mt-1 text-[12.5px]', CAPTION)}>
                        {t('admin.design.academies.lessons.sub')}
                    </p>
                </div>
                <TableCard
                    label={t('admin.design.academies.lessons.title')}
                    minWidth="min-w-[640px]"
                    empty={
                        rows.length === 0 && (
                            <EmptyState
                                title={t(
                                    'admin.design.academies.lessons.empty',
                                )}
                            />
                        )
                    }
                >
                    <div
                        role="row"
                        className={cn(GRID, TABLE_HEAD, 'py-[13px]')}
                    >
                        {COLUMNS.map((column) => (
                            <HeadCell key={column}>
                                {t(`admin.design.col.${column}`)}
                            </HeadCell>
                        ))}
                    </div>
                    {rows.map(({ group, lesson }) => (
                        <div
                            key={lesson.key}
                            role="row"
                            className={cn(GRID, ROW_RULE, 'items-center py-3')}
                        >
                            <span
                                role="cell"
                                className="flex min-w-0 items-center gap-2 text-[12.5px] font-semibold text-rz-ink"
                            >
                                <span aria-hidden="true">
                                    {lesson.topics[0].emoji}
                                </span>
                                <span className="truncate">
                                    {t(
                                        `investor.academy.lesson.${lesson.key}.title`,
                                    )}
                                </span>
                            </span>
                            <span
                                role="cell"
                                className="truncate text-[12.5px] text-rz-body"
                            >
                                {t(`investor.academy.group.${group}`)}
                            </span>
                            <span
                                role="cell"
                                className="text-[12.5px] text-rz-muted"
                            >
                                {lesson.topics.length}
                            </span>
                        </div>
                    ))}
                </TableCard>
            </section>
        </AdminFrame>
    );
}
