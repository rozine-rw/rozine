import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import type { DesignColumn } from '@/components/admin/section-designs';
import {
    CAPTION,
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
import type {
    AdminMessagingProps,
    MessageAudience,
    MessageAutomation,
    MessageHistoryRow,
    MessageTemplate,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.3fr)_minmax(0,2fr)_minmax(0,1fr)_minmax(0,1.4fr)_minmax(0,.7fr)_minmax(0,.9fr)] gap-3 px-5';

const TABS = ['automations', 'templates', 'campaigns', 'history'] as const;

type Tab = (typeof TABS)[number];

type CatalogueTab = Exclude<Tab, 'history' | 'automations'>;

/** Each catalogue tab's columns; Campaigns has no read yet and keeps its empty state. */
const COLUMNS: Record<CatalogueTab, DesignColumn[]> = {
    templates: ['template', 'audience', 'channel'],
    campaigns: ['campaign', 'audience', 'sent'],
};

/** The design lists business rules first, then investor ones; then the other audiences. */
const AUDIENCES: readonly MessageAudience[] = [
    'business',
    'investor',
    'auditor',
    'everyone',
];

/** Rules the design names differently from the notice the party reads (Admin.dc.html L4020–4050). */
const DESIGN_RULE_NAMES: Partial<
    Record<
        string,
        | 'admin.messaging.rule.funds_disbursed'
        | 'admin.messaging.rule.verification_approved'
        | 'admin.messaging.rule.raise_fully_funded'
    >
> = {
    'business.disbursement_sent': 'admin.messaging.rule.funds_disbursed',
    'verification.approved': 'admin.messaging.rule.verification_approved',
    'business.raise_funded': 'admin.messaging.rule.raise_fully_funded',
};

const RULE_GRID =
    'grid grid-cols-[minmax(0,1.15fr)_minmax(0,1.25fr)_minmax(0,1.5fr)_56px] items-center gap-4';

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
 * Messaging & Automations (design T2327–2462). It opens on Automations, as the design does, or
 * on History, the tab its address names. Automations lists every notice the platform sends as its
 * event happens, by audience, and Templates every transactional email; neither can be edited
 * yet, so nothing is offered. History lists every notice that went out, newest first: who it
 * reached, the message, its category, the automation behind it and whether it arrived, the
 * channel and when. Campaigns are not sent yet, so that tab keeps its empty state.
 */
export default function AdminMessaging(props: AdminMessagingProps) {
    const { t } = useTranslation();
    const searched = props.search !== '';
    /* The page lives at /admin/messaging/history, so it opens on History. */
    const [tab, setTab] = useState<Tab>('history');

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
            ) : tab === 'automations' ? (
                <Automations automations={props.automations} />
            ) : (
                <CatalogueTable tab={tab} rows={catalogueRows(tab, props, t)} />
            )}
        </AdminFrame>
    );
}

/** The live automations (design T2337–2381): a count, then one block of rules per audience. */
function Automations({
    automations,
}: {
    automations: readonly MessageAutomation[];
}) {
    const { t } = useTranslation();

    return (
        <div>
            <p className="mb-4 flex items-center gap-2.5 rounded-[11px] border border-rz-hairline bg-rz-surface-sunken px-3.5 py-2.5 text-[12.5px] text-rz-body">
                <svg
                    aria-hidden
                    width="15"
                    height="15"
                    viewBox="0 0 24 24"
                    fill="none"
                    className="shrink-0 stroke-rz-accent-fill"
                >
                    <path
                        d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z"
                        strokeWidth="1.7"
                        strokeLinejoin="round"
                    />
                </svg>
                <span>
                    <strong className="font-bold text-rz-ink">
                        {t('admin.messaging.automations.live', {
                            count: automations.length,
                        })}
                    </strong>
                    {' · '}
                    {t('admin.messaging.automations.fire')}
                </span>
            </p>
            {automations.length === 0 && (
                <EmptyState
                    title={t('admin.design.messaging.automations.empty')}
                />
            )}
            {AUDIENCES.map((audience) => {
                const rules = automations.filter(
                    (automation) => automation.audience === audience,
                );

                if (rules.length === 0) {
                    return null;
                }

                const heading = t('admin.messaging.automations.group', {
                    audience: t(`admin.messaging.audience.${audience}`),
                });

                return (
                    <section
                        key={audience}
                        aria-label={heading}
                        className="mb-6"
                    >
                        <h3 className="mb-2.5 text-[11px] font-bold tracking-[.1em] text-rz-muted uppercase">
                            {heading}
                        </h3>
                        <div
                            role="table"
                            aria-label={heading}
                            className="flex flex-col gap-[9px]"
                        >
                            <div
                                role="row"
                                className={cn(
                                    RULE_GRID,
                                    'px-[18px] text-[10px] font-bold tracking-[.06em] text-rz-faint uppercase',
                                )}
                            >
                                {(
                                    [
                                        'rule',
                                        'fires_when',
                                        'timing',
                                        'on',
                                    ] as const
                                ).map((column) => (
                                    <span
                                        key={column}
                                        role="columnheader"
                                        className={cn(
                                            column === 'on' && 'text-right',
                                        )}
                                    >
                                        {t(`admin.design.col.${column}`)}
                                    </span>
                                ))}
                            </div>
                            {rules.map((rule) => (
                                <div
                                    key={rule.kind}
                                    role="row"
                                    className={cn(
                                        RULE_GRID,
                                        'rounded-xl border border-rz-hairline bg-rz-surface px-[18px] py-[13px]',
                                    )}
                                >
                                    <span
                                        role="cell"
                                        className="flex min-w-0 items-center gap-[9px]"
                                    >
                                        <span
                                            aria-hidden
                                            className="size-[7px] shrink-0 rounded-full bg-[#1d9e75]"
                                        />
                                        <span className="truncate text-[13px] font-bold text-rz-ink">
                                            {t(
                                                DESIGN_RULE_NAMES[rule.kind] ??
                                                    `investor.notices.${rule.kind}.title`,
                                            )}
                                        </span>
                                    </span>
                                    <span
                                        role="cell"
                                        className="truncate text-[12.5px] text-rz-muted"
                                    >
                                        {t(
                                            `admin.messaging.trigger.${rule.kind}`,
                                        )}
                                    </span>
                                    <span
                                        role="cell"
                                        className="truncate text-[11.5px] text-rz-faint"
                                    >
                                        {t('admin.messaging.timing', {
                                            channels: rule.channels
                                                .map((channel) =>
                                                    t(
                                                        `admin.messaging.channel.${channel}`,
                                                    ),
                                                )
                                                .join(', '),
                                        })}
                                    </span>
                                    <span
                                        role="cell"
                                        className="justify-self-end"
                                    >
                                        <Chip tone="green">
                                            {t('admin.messaging.state.live')}
                                        </Chip>
                                    </span>
                                </div>
                            ))}
                        </div>
                    </section>
                );
            })}
        </div>
    );
}

type Translate = ReturnType<typeof useTranslation>['t'];

/** A catalogue tab's rows as cell text. */
function catalogueRows(
    tab: CatalogueTab,
    props: AdminMessagingProps,
    t: Translate,
): { key: string; cells: string[] }[] {
    if (tab === 'templates') {
        return props.templates.map((template: MessageTemplate) => ({
            key: template.key,
            cells: [
                t(`admin.messaging.template.${template.key}`),
                t(`admin.messaging.audience.${template.audience}`),
                t('admin.messaging.channel.mail'),
            ],
        }));
    }

    return [];
}

/** Automations, Templates or Campaigns: the design's columns over the server's catalogue. */
function CatalogueTable({
    tab,
    rows,
}: {
    tab: CatalogueTab;
    rows: { key: string; cells: string[] }[];
}) {
    const { t } = useTranslation();
    const grid = {
        gridTemplateColumns: `minmax(0, 1.6fr) repeat(${COLUMNS[tab].length - 1}, minmax(0, 1fr))`,
    };

    return (
        <div>
            {tab !== 'campaigns' && (
                <p className={cn('mb-3 text-[12.5px]', CAPTION)}>
                    {t(`admin.messaging.${tab}.sub`)}
                </p>
            )}
            <TableCard
                label={t(`admin.design.messaging.${tab}.title`)}
                minWidth="min-w-[720px]"
                empty={
                    rows.length === 0 && (
                        <EmptyState
                            title={t(`admin.design.messaging.${tab}.empty`)}
                        />
                    )
                }
            >
                <div
                    role="row"
                    className={cn('grid gap-3 px-5 py-[13px]', TABLE_HEAD)}
                    style={grid}
                >
                    {COLUMNS[tab].map((column) => (
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
                            'grid items-center gap-3 px-5 py-3',
                            ROW_RULE,
                        )}
                        style={grid}
                    >
                        {row.cells.map((cell, index) => (
                            <span
                                role="cell"
                                key={index}
                                className={cn(
                                    'min-w-0 truncate text-[12.5px]',
                                    index === 0
                                        ? 'font-semibold text-rz-ink'
                                        : 'text-rz-muted',
                                )}
                            >
                                {cell}
                            </span>
                        ))}
                    </div>
                ))}
            </TableCard>
        </div>
    );
}
