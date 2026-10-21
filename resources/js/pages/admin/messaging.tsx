import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import type { DesignColumn } from '@/components/admin/section-designs';
import {
    Chip,
    EmptyState,
    HeadCell,
    ROW_RULE,
    TABLE_HEAD,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AdminMessagingProps, MessageHistoryRow } from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.3fr)_minmax(0,2fr)_minmax(0,1fr)_minmax(0,1.4fr)_minmax(0,.7fr)_minmax(0,.9fr)] gap-3 px-5';

const TABS = ['automations', 'templates', 'campaigns', 'history'] as const;

type Tab = (typeof TABS)[number];

/** The design's columns for the tabs that are not wired yet. */
const PENDING_COLUMNS: Record<Exclude<Tab, 'history'>, DesignColumn[]> = {
    automations: ['rule', 'fires_when', 'timing', 'state'],
    templates: ['template', 'channel', 'updated'],
    campaigns: ['campaign', 'audience', 'sent'],
};

/** A search reloads from the first page, so it drops the older-page cursor. */
const SEARCH_QUERY = { param: 'q', clears: ['before'] };

function HistoryLine({ row }: { row: MessageHistoryRow }) {
    const { t, locale } = useTranslation();

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3')}>
            <span
                role="cell"
                className="truncate text-[12.5px] font-semibold text-rz-ink"
            >
                {row.recipient}
            </span>
            <span role="cell" className="truncate text-[12.5px] text-rz-body">
                {t(`investor.notices.${row.kind}.title`)}
            </span>
            <span role="cell" className="text-[11.5px] text-rz-muted">
                {t(`admin.messaging.category.${row.category}`)}
            </span>
            <span role="cell" className="flex min-w-0 items-center gap-1.5">
                <Chip tone="blue">
                    {t(`admin.messaging.source.${row.source}`)}
                </Chip>
                <span
                    className={cn(
                        'truncate text-[11px]',
                        row.status === 'failed'
                            ? 'font-semibold text-[#b3352a] dark:text-[#ff6b6f]'
                            : 'text-rz-faint',
                    )}
                >
                    {t(`admin.messaging.status.${row.status}`)}
                </span>
            </span>
            <span role="cell" className="text-[11.5px] text-rz-muted">
                {t(`admin.messaging.channel.${row.channel}`)}
            </span>
            <span
                role="cell"
                className="text-right text-[11px] whitespace-nowrap text-rz-faint"
            >
                {formatDateTime(row.at, locale)}
            </span>
        </div>
    );
}

/**
 * Messaging & Automations (design T2327–2462). History lists every notice that went out, newest
 * first: who it reached, the message, its category, the automation behind it and whether it
 * arrived, the channel and when. Automations, templates and campaigns cannot be edited yet, so
 * those tabs keep their empty state and offer nothing.
 */
export default function AdminMessaging(props: AdminMessagingProps) {
    const { t } = useTranslation();
    const [tab, setTab] = useState<Tab>('history');
    const searched = props.search !== '';

    return (
        <AdminFrame section="messaging" searchQuery={SEARCH_QUERY} {...props}>
            <div
                role="tablist"
                aria-label={t('admin.design.tabs', {
                    section: t('admin.section.messaging.title'),
                })}
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
                        {t(`admin.design.messaging.tab.${item}`)}
                    </button>
                ))}
            </div>
            {tab === 'history' ? (
                <TableCard
                    label={t('admin.design.messaging.history.title')}
                    minWidth="min-w-[860px]"
                    empty={
                        props.history.length === 0 && (
                            <EmptyState
                                title={t(
                                    searched
                                        ? 'admin.messaging.no_match'
                                        : 'admin.design.messaging.history.empty',
                                )}
                            />
                        )
                    }
                    footer={
                        (props.older || props.newest) && (
                            <div className="flex items-center justify-between border-t border-[#eef2f8] bg-rz-surface-sunken px-5 py-3 text-[12.5px] font-bold text-rz-accent-app-text dark:border-rz-divider">
                                {props.newest ? (
                                    <Link href={props.newest} preserveScroll>
                                        {t('admin.messaging.newest')}
                                    </Link>
                                ) : (
                                    <span />
                                )}
                                {props.older && (
                                    <Link href={props.older} preserveScroll>
                                        {t('admin.messaging.older')}
                                    </Link>
                                )}
                            </div>
                        )
                    }
                >
                    <div
                        role="row"
                        className={cn(GRID, TABLE_HEAD, 'py-[13px]')}
                    >
                        <HeadCell>
                            {t('admin.messaging.col.recipient')}
                        </HeadCell>
                        <HeadCell>{t('admin.messaging.col.message')}</HeadCell>
                        <HeadCell>{t('admin.messaging.col.category')}</HeadCell>
                        <HeadCell>{t('admin.messaging.col.why')}</HeadCell>
                        <HeadCell>{t('admin.messaging.col.via')}</HeadCell>
                        <HeadCell end>{t('admin.messaging.col.when')}</HeadCell>
                    </div>
                    {props.history.map((row) => (
                        <HistoryLine key={row.id} row={row} />
                    ))}
                </TableCard>
            ) : (
                <TableCard
                    label={t(`admin.design.messaging.${tab}.title`)}
                    minWidth="min-w-[720px]"
                    empty={
                        <EmptyState
                            title={t(`admin.design.messaging.${tab}.empty`)}
                        />
                    }
                >
                    <div
                        role="row"
                        className={cn('grid gap-3 px-5 py-[13px]', TABLE_HEAD)}
                        style={{
                            gridTemplateColumns: `repeat(${PENDING_COLUMNS[tab].length}, minmax(0, 1fr))`,
                        }}
                    >
                        {PENDING_COLUMNS[tab].map((column) => (
                            <HeadCell key={column}>
                                {t(`admin.design.col.${column}`)}
                            </HeadCell>
                        ))}
                    </div>
                </TableCard>
            )}
        </AdminFrame>
    );
}
