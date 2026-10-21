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
    ROW_RULE,
    TABLE_HEAD,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatRwf, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AdminPendingSectionProps,
    EngineRow,
    FinanceStream,
    PolicyRule,
    RatingBookRow,
} from '@/types/admin';

/** A figure the server has not stated: the tile keeps its place and says nothing. */
const NO_FIGURE = '—';

/**
 * A design section whose live reads are not wired yet: the console frame with the design's KPI
 * tiles, tabs and tables, every figure blank and every table at its empty state unless the server
 * sends it (Policies' rules, Finance's fee revenue). Nothing is invented and no action is offered,
 * because no server read or command backs them yet. A section
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
                                value={figureOf(props, kpi.label)}
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
                            <Block
                                key={block.key}
                                block={block}
                                rows={rowsOf(block, tab, props, t)}
                            />
                        ))}
                </div>
            </section>
        </AdminFrame>
    );
}

/** A tile's count or money figure by its design key (the label code's last part), or the blank mark. */
function figureOf(props: AdminPendingSectionProps, label: string): string {
    const key = label.slice(label.lastIndexOf('.') + 1);
    const count = props.counts?.[key];
    const figure = props.figures?.[key];

    if (count !== undefined) {
        return formatCount(count);
    }

    return figure === undefined ? NO_FIGURE : formatRwfShort(figure);
}

/** The blocks that list engine settings: Engines' tabs and the Ratings scorecard. */
const ENGINE_BLOCKS = new Set(['sizing', 'cpa', 'weights', 'bands', 'movers']);

/**
 * The rows the server read for a block, as cell text: Policies' rules under the selected tab,
 * Finance's fee streams, and the engine settings Engines and Ratings list for underwriters. Every
 * other block has none and keeps its empty state.
 */
function rowsOf(
    block: DesignBlock,
    tab: string | undefined,
    props: AdminPendingSectionProps,
    t: ReturnType<typeof useTranslation>['t'],
): { key: string; cells: string[] }[] {
    if (block.key === 'rules') {
        return (props.rules ?? [])
            .filter((rule: PolicyRule) => rule.tab === tab)
            .map((rule) => ({
                key: rule.key,
                cells: [
                    t(`admin.policy.${rule.key}`),
                    rule.value,
                    rule.version ?? NO_FIGURE,
                ],
            }));
    }

    if (ENGINE_BLOCKS.has(block.key)) {
        return (props.engine ?? [])
            .filter((row: EngineRow) => row.tab === (block.tab ?? block.key))
            .map((row) => ({
                key: row.key,
                cells: [t(`admin.engine.${row.key}`), row.value, row.source],
            }));
    }

    /* No rating history is kept, so only All lists the book; band changes and trends stay empty. */
    if (block.key === 'book') {
        return (tab === 'all' ? (props.book ?? []) : []).map(
            (row: RatingBookRow) => ({
                key: row.key,
                cells: [
                    row.business,
                    row.sector,
                    `${t(`admin.rating.band.${row.rating.band}`)} · ${row.rating.score}`,
                    NO_FIGURE,
                    NO_FIGURE,
                ],
            }),
        );
    }

    if (block.key === 'streams') {
        return (props.streams ?? []).map((stream: FinanceStream) => ({
            key: stream.key,
            cells: [
                t(`admin.finance.stream.${stream.key}`),
                t(`admin.finance.paid_by.${stream.paid_by}`),
                formatRwf(stream.collected),
            ],
        }));
    }

    return [];
}

/** One designed panel: its title and caption over a chart's or a table's rows or empty state. */
function Block({
    block,
    rows,
}: {
    block: DesignBlock;
    rows: { key: string; cells: string[] }[];
}) {
    const { t } = useTranslation();
    const title = t(block.title);
    const columns = block.columns ?? [];
    const grid = {
        gridTemplateColumns: `repeat(${columns.length}, minmax(0, 1fr))`,
    };
    const empty = <EmptyState title={t(block.empty)} />;

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
                <TableCard
                    label={title}
                    minWidth="min-w-[720px]"
                    empty={rows.length === 0 ? empty : undefined}
                >
                    <div
                        role="row"
                        className={cn('grid gap-3 px-5 py-[13px]', TABLE_HEAD)}
                        style={grid}
                    >
                        {columns.map((column) => (
                            <HeadCell key={column}>
                                {t(`admin.design.col.${column}`)}
                            </HeadCell>
                        ))}
                    </div>
                    {rows.map((row) => (
                        <div
                            key={row.key}
                            role="row"
                            className={cn(
                                'grid items-center gap-3 px-5 py-3.5',
                                ROW_RULE,
                            )}
                            style={grid}
                        >
                            {row.cells.map((cell, index) => (
                                <span
                                    key={index}
                                    role="cell"
                                    className={cn(
                                        'min-w-0 text-[13px] text-rz-ink',
                                        index === 0
                                            ? 'font-semibold'
                                            : 'font-medium',
                                    )}
                                >
                                    {cell}
                                </span>
                            ))}
                        </div>
                    ))}
                </TableCard>
            )}
        </div>
    );
}
