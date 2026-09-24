import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { formatTimestamp } from '@/components/admin/format';
import { ReasonStage } from '@/components/admin/reason-stage';
import {
    CardTitle,
    HeadCell,
    ShowMoreLink,
    TONE_TEXT,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AdminEventsProps, EventRow } from '@/types/admin';

const GRID =
    'grid grid-cols-[150px_minmax(0,1.1fr)_minmax(0,1.5fr)_minmax(0,1.2fr)_90px] gap-3 px-5';

const FILTER_BUTTON =
    'h-[34px] rounded-[9px] border border-[#dbe3f0] px-[13px] text-[12px] font-bold dark:border-rz-border';

const DATE_INPUT =
    'relative h-[34px] rounded-[9px] border border-[#dbe3f0] bg-rz-surface px-2.5 text-[12px] text-rz-ink uppercase outline-none dark:border-rz-border [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:h-auto [&::-webkit-calendar-picker-indicator]:w-auto [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-0';

type Filters = AdminEventsProps['filters'];

function EventLine({ row }: { row: EventRow }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    return (
        <div
            role="row"
            className="border-b border-[#f2f5fa] dark:border-rz-divider"
        >
            <div
                className={cn(
                    GRID,
                    'relative items-center py-3 hover:bg-[#f5f8fd] dark:hover:bg-rz-surface-sunken',
                )}
            >
                <span
                    role="cell"
                    className="text-[11.5px] whitespace-nowrap text-rz-muted tabular-nums"
                >
                    {formatTimestamp(row.at)}
                </span>
                <span
                    role="cell"
                    className="truncate text-[12.5px] text-rz-ink"
                >
                    {row.actor}
                </span>
                <span role="cell" className="min-w-0">
                    <button
                        type="button"
                        aria-expanded={open}
                        onClick={() => setOpen((value) => !value)}
                        className={cn(
                            'block max-w-full truncate text-left text-[12.5px] font-semibold after:absolute after:inset-0',
                            TONE_TEXT[row.action.tone],
                        )}
                    >
                        {row.action.label}
                    </button>
                </span>
                <span role="cell" className="truncate text-[12px] text-rz-body">
                    {row.target}
                </span>
                <span
                    role="cell"
                    className="text-right text-[11px] whitespace-nowrap text-rz-faint"
                >
                    {row.source}
                </span>
            </div>
            {open && (
                <div className="bg-rz-surface-sunken px-5 pt-1 pb-3.5">
                    <p className="text-[12px] text-rz-slate">
                        {row.reason === null
                            ? t('admin.events.no_reason')
                            : t('admin.trail.reason', { reason: row.reason })}
                    </p>
                    {row.changes.length > 0 ? (
                        <table className="mt-2.5 w-full max-w-[640px] text-left text-[12px]">
                            <thead>
                                <tr className="text-[10.5px] font-semibold text-[#7b8699] uppercase dark:text-rz-muted">
                                    <th className="py-1 pr-3 font-semibold">
                                        {t('admin.events.field')}
                                    </th>
                                    <th className="py-1 pr-3 font-semibold">
                                        {t('admin.events.before')}
                                    </th>
                                    <th className="py-1 font-semibold">
                                        {t('admin.events.after')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {row.changes.map((change) => (
                                    <tr
                                        key={change.field}
                                        className="border-t border-rz-hairline"
                                    >
                                        <td className="py-1.5 pr-3 font-semibold text-rz-ink">
                                            {change.field}
                                        </td>
                                        <td className="py-1.5 pr-3 text-[#e5484d] line-through decoration-1 dark:text-[#ff6b6f]">
                                            {change.before ?? '—'}
                                        </td>
                                        <td className="py-1.5 font-semibold text-[#1d9e75] dark:text-[#3fcda0]">
                                            {change.after ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <p className="mt-1.5 text-[12px] text-rz-faint">
                            {t('admin.events.no_changes')}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}

/**
 * The event log (MVP-ADMIN-SCR-09, design Activity & Audit T1809–1854): actor, action, target,
 * time and source, each row opening to its reason and before/after values (AC-06). Nothing here
 * can be edited or deleted. Filters are server queries; an export is a reasoned, attributed
 * request whose progress the server reports (SCR-09-ST-02).
 */
export default function AdminEvents(props: AdminEventsProps) {
    const { t } = useTranslation();
    const [exporting, setExporting] = useState(false);
    const { filters } = props;
    const active =
        filters.q !== '' ||
        filters.preset !== null ||
        filters.from !== null ||
        filters.to !== null;

    const apply = (next: Partial<Filters>) => {
        router.reload({ data: { ...filters, ...next } });
    };

    const search = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const field = event.currentTarget.elements.namedItem(
            'q',
        ) as HTMLInputElement;

        apply({ q: field.value });
    };

    const clear = () => apply({ q: '', preset: null, from: null, to: null });
    const exportAction = props.actions.export;

    return (
        <AdminFrame section="events" {...props}>
            <div className="mb-[18px] flex items-center gap-2.5 rounded-xl border border-[#dde6f3] bg-[rgba(30,58,255,.07)] px-4 py-3 dark:border-rz-border">
                <svg
                    width="17"
                    height="17"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden
                    className="shrink-0"
                >
                    <rect
                        x="4.5"
                        y="10.5"
                        width="15"
                        height="10"
                        rx="2.5"
                        fill="url(#rz-g-blue)"
                        fillOpacity=".16"
                    />
                    <rect
                        x="4.5"
                        y="10.5"
                        width="15"
                        height="10"
                        rx="2.5"
                        stroke="url(#rz-g-blue)"
                        strokeWidth="1.7"
                    />
                    <path
                        d="M8 10.5V8a4 4 0 0 1 8 0v2.5"
                        stroke="url(#rz-g-blue)"
                        strokeWidth="1.7"
                    />
                </svg>
                <span className="text-[13px] text-[#5f6fc8] dark:text-[#99a3ff]">
                    {t('admin.events.immutable')}
                </span>
            </div>

            <TableCard
                label={t('admin.events.table')}
                minWidth="min-w-[760px]"
                className="shadow-[0_1px_2px_rgba(16,32,58,.04),0_14px_30px_-24px_rgba(16,32,58,.28)]"
                footer={
                    props.more && (
                        <ShowMoreLink link={props.more}>
                            {t('admin.events.see_all', {
                                count: formatCount(props.total),
                            })}
                        </ShowMoreLink>
                    )
                }
            >
                <div className="border-b border-[#e3e9f4] bg-[#f8fafd] px-5 pt-4 pb-3.5 dark:border-rz-border dark:bg-rz-surface-sunken">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <CardTitle>{t('admin.events.title')}</CardTitle>
                        <div className="flex flex-wrap items-center gap-2.5">
                            <span className="text-[12px] font-semibold text-[#7b8699] dark:text-rz-muted">
                                {t('admin.events.count', {
                                    count: formatCount(props.total),
                                })}
                            </span>
                            {props.export.state === 'running' && (
                                <span
                                    role="status"
                                    className="flex items-center gap-1.5 rounded-lg bg-[rgba(30,58,255,.1)] px-2.5 py-1 text-[11.5px] font-semibold text-rz-accent-app-text"
                                >
                                    <span className="size-2 animate-pulse rounded-full bg-rz-accent-fill" />
                                    {t('admin.events.export_running', {
                                        actor: props.export.requested.actor,
                                        at: formatTimestamp(
                                            props.export.requested.at,
                                        ),
                                    })}
                                </span>
                            )}
                            {props.export.state === 'ready' && (
                                <Link
                                    href={props.export.download}
                                    className="rounded-lg bg-[#1d9e75] px-3 py-1.5 text-[11.5px] font-semibold text-white"
                                >
                                    {t('admin.events.export_download')}
                                </Link>
                            )}
                            {exportAction &&
                                props.export.state !== 'running' &&
                                !exporting && (
                                    <button
                                        type="button"
                                        onClick={() => setExporting(true)}
                                        className="h-[30px] rounded-lg border border-rz-hairline bg-rz-surface px-3 text-[12px] font-bold text-rz-slate"
                                    >
                                        {t('admin.events.export')}
                                    </button>
                                )}
                        </div>
                    </div>
                    {exporting && exportAction && (
                        <ReasonStage
                            title={t('admin.events.export_title')}
                            body={t('admin.events.export_body')}
                            cta={t('admin.events.export_cta')}
                            placeholder={t('admin.events.export_placeholder')}
                            tone="blue"
                            action={exportAction}
                            viewer={props.viewer}
                            onCancel={() => setExporting(false)}
                            payload={{
                                q: filters.q,
                                preset: filters.preset ?? '',
                                from: filters.from ?? '',
                                to: filters.to ?? '',
                            }}
                        />
                    )}
                    <div className="mt-3 flex flex-wrap items-center gap-2">
                        <form
                            onSubmit={search}
                            role="search"
                            className="flex h-[34px] min-w-[190px] flex-1 items-center gap-[7px] rounded-[9px] border border-[#dbe3f0] bg-rz-surface px-[11px] dark:border-rz-border"
                        >
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden
                            >
                                <circle
                                    cx="11"
                                    cy="11"
                                    r="7"
                                    stroke="#9aa3b5"
                                    strokeWidth="1.8"
                                />
                                <path
                                    d="m20 20-3.2-3.2"
                                    stroke="#9aa3b5"
                                    strokeWidth="1.8"
                                    strokeLinecap="round"
                                />
                            </svg>
                            <input
                                type="search"
                                name="q"
                                defaultValue={filters.q}
                                aria-label={t('admin.events.search_label')}
                                placeholder={t(
                                    'admin.events.search_placeholder',
                                )}
                                className="min-w-0 flex-1 border-none bg-transparent text-[12.5px] text-rz-ink outline-none placeholder:text-rz-muted"
                            />
                        </form>
                        {(['today', '7d', '30d'] as const).map((preset) => (
                            <button
                                key={preset}
                                type="button"
                                aria-pressed={filters.preset === preset}
                                onClick={() =>
                                    apply({ preset, from: null, to: null })
                                }
                                className={cn(
                                    FILTER_BUTTON,
                                    filters.preset === preset
                                        ? 'bg-[#eef3fb] text-rz-accent-app-text dark:bg-rz-surface-muted'
                                        : 'bg-rz-surface text-rz-slate',
                                )}
                            >
                                {t(`admin.events.preset.${preset}`)}
                            </button>
                        ))}
                        <input
                            type="date"
                            aria-label={t('admin.events.from')}
                            value={filters.from ?? ''}
                            onChange={(event) =>
                                apply({
                                    preset: null,
                                    from: event.target.value || null,
                                })
                            }
                            className={DATE_INPUT}
                        />
                        <span className="text-[11px] text-rz-faint">
                            {t('admin.events.to_word')}
                        </span>
                        <input
                            type="date"
                            aria-label={t('admin.events.to')}
                            value={filters.to ?? ''}
                            onChange={(event) =>
                                apply({
                                    preset: null,
                                    to: event.target.value || null,
                                })
                            }
                            className={DATE_INPUT}
                        />
                        {active && (
                            <button
                                type="button"
                                onClick={clear}
                                className={cn(
                                    FILTER_BUTTON,
                                    'bg-[#eef3fb] text-rz-accent-app-text dark:bg-rz-surface-muted',
                                )}
                            >
                                {t('admin.events.clear')}
                            </button>
                        )}
                    </div>
                </div>
                <div
                    role="row"
                    className={cn(
                        GRID,
                        'border-b border-[#e3e9f4] bg-[#f7f9fd] py-[11px] dark:border-rz-border dark:bg-rz-surface-sunken',
                    )}
                >
                    <HeadCell>{t('admin.events.col.when')}</HeadCell>
                    <HeadCell>{t('admin.events.col.who')}</HeadCell>
                    <HeadCell>{t('admin.events.col.action')}</HeadCell>
                    <HeadCell>{t('admin.events.col.object')}</HeadCell>
                    <HeadCell end>{t('admin.events.col.source')}</HeadCell>
                </div>
                {props.events.map((row) => (
                    <EventLine key={row.id} row={row} />
                ))}
                {props.events.length === 0 && (
                    <div className="p-10 text-center">
                        <div className="text-[14px] font-semibold text-rz-ink">
                            {t(
                                active
                                    ? 'admin.events.filtered_empty'
                                    : 'admin.events.empty',
                            )}
                        </div>
                        {active && (
                            <button
                                type="button"
                                onClick={clear}
                                className="mt-3 rounded-[9px] bg-[#eef3fb] px-3.5 py-2 text-[12px] font-bold text-rz-accent-app-text dark:bg-rz-surface-muted"
                            >
                                {t('admin.events.clear_filters')}
                            </button>
                        )}
                    </div>
                )}
            </TableCard>
        </AdminFrame>
    );
}
