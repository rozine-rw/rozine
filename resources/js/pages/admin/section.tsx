import { useState } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { SECTION_DESIGNS } from '@/components/admin/section-designs';
import type { DesignBlock } from '@/components/admin/section-designs';
import {
    CAPTION,
    CARD,
    CARD_SHADOW,
    CardTitle,
    EmptyState,
    HeadCell,
    KpiTile,
    TABLE_HEAD,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AdminPendingSectionProps } from '@/types/admin';

/** A figure the server has not stated: the tile keeps its place and says nothing. */
const NO_FIGURE = '—';

/**
 * A design section whose live reads are not wired yet: the console frame with the design's KPI
 * tiles, tabs and tables, every figure blank and every table at its empty state. Nothing is
 * invented and no action is offered, because no server read or command backs them yet. A section
 * the design layout does not cover keeps the plain empty state.
 */
export default function AdminPendingSection(props: AdminPendingSectionProps) {
    const { t } = useTranslation();
    const design = SECTION_DESIGNS[props.section];
    const [tab, setTab] = useState(design?.tabs?.[0]?.key);
    const title = t(`admin.section.${props.section}.title`);

    if (design === undefined) {
        return (
            <AdminFrame {...props}>
                <section
                    aria-label={title}
                    className={cn(CARD, CARD_SHADOW, 'rounded-2xl')}
                >
                    <EmptyState
                        title={t('admin.pending.title')}
                        body={t('admin.pending.body')}
                    />
                </section>
            </AdminFrame>
        );
    }

    return (
        <AdminFrame {...props}>
            <section aria-label={title}>
                {design.kpis !== undefined && (
                    <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                        {design.kpis.map((kpi, index) => (
                            <KpiTile
                                key={kpi.label}
                                index={index}
                                label={t(kpi.label)}
                                value={NO_FIGURE}
                                sub={
                                    kpi.sub === undefined
                                        ? undefined
                                        : t(kpi.sub)
                                }
                            />
                        ))}
                    </div>
                )}
                {design.tabs !== undefined && (
                    <div
                        role="tablist"
                        aria-label={t('admin.design.tabs', { section: title })}
                        className="rz-hscroll mb-[18px] flex gap-2 overflow-x-auto"
                    >
                        {design.tabs.map((item) => (
                            <button
                                key={item.key}
                                type="button"
                                role="tab"
                                aria-selected={item.key === tab}
                                onClick={() => setTab(item.key)}
                                className={cn(
                                    'shrink-0 rounded-[10px] border px-[15px] py-2 text-[13px] font-semibold whitespace-nowrap',
                                    item.key === tab
                                        ? 'border-rz-accent-fill bg-rz-accent-fill text-white'
                                        : 'border-rz-hairline bg-rz-surface text-rz-slate',
                                )}
                            >
                                {t(item.label)}
                            </button>
                        ))}
                    </div>
                )}
                <div className="flex flex-col gap-[18px]">
                    {design.blocks
                        .filter(
                            (block) =>
                                block.tab === undefined || block.tab === tab,
                        )
                        .map((block) => (
                            <Block key={block.key} block={block} />
                        ))}
                </div>
            </section>
        </AdminFrame>
    );
}

/** One designed panel: its title and caption over a chart's or a table's empty state. */
function Block({ block }: { block: DesignBlock }) {
    const { t } = useTranslation();
    const title = t(block.title);
    const empty = <EmptyState title={t(block.empty)} />;
    const columns = block.columns ?? [];

    return (
        <div>
            <div className="mb-3.5">
                <CardTitle>{title}</CardTitle>
                {block.sub !== undefined && (
                    <p className={cn('mt-1 text-[12.5px]', CAPTION)}>
                        {t(block.sub)}
                    </p>
                )}
            </div>
            {block.chart === true ? (
                <section
                    aria-label={title}
                    className={cn(CARD, CARD_SHADOW, 'rounded-[20px]')}
                >
                    {empty}
                </section>
            ) : (
                <TableCard label={title} minWidth="min-w-[720px]" empty={empty}>
                    <div
                        role="row"
                        className={cn('grid gap-3 px-5 py-[13px]', TABLE_HEAD)}
                        style={{
                            gridTemplateColumns: `repeat(${columns.length}, minmax(0, 1fr))`,
                        }}
                    >
                        {columns.map((column) => (
                            <HeadCell key={column}>
                                {t(`admin.design.col.${column}`)}
                            </HeadCell>
                        ))}
                    </div>
                </TableCard>
            )}
        </div>
    );
}
