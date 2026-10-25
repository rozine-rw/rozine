import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import {
    CardTitle,
    Chip,
    EmptyState,
    HeadCell,
    KpiTile,
    ROW_RULE,
    SegmentedChips,
    TABLE_HEAD,
    TONE_TEXT,
    TableCard,
    useStatFormatter,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatDate,
    formatDateTime,
    formatMonthYearLong,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { Tone } from '@/types/admin';
import type {
    AdminAuditReportsProps,
    AuditRegisterRow,
    AuditRegisterStatus,
} from '@/types/admin-audit';

const GRID =
    'grid grid-cols-[minmax(0,1.6fr)_minmax(0,1.1fr)_minmax(0,1.4fr)_minmax(0,1.1fr)_minmax(0,.7fr)_minmax(220px,1.3fr)] gap-3 px-5';

const STATUS_TONE: Record<AuditRegisterStatus, Tone> = {
    not_started: 'grey',
    in_progress: 'blue',
    overdue: 'red',
    returned: 'amber',
    sealed: 'purple',
    awaiting_cosign: 'amber',
    published: 'green',
    disputed: 'red',
    escalated: 'red',
    amended: 'grey',
    withdrawn: 'grey',
};

function Row({ row }: { row: AuditRegisterRow }) {
    const { t, locale } = useTranslation();

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3.5')}>
            <span role="cell" className="min-w-0">
                <span className="block truncate text-[13px] font-bold text-rz-ink">
                    {row.business}
                </span>
                <span className="mt-0.5 block text-[11px] text-rz-muted">
                    {t(
                        row.kind === 'monthly'
                            ? 'admin.audit_reports.monthly'
                            : 'admin.audit_reports.flash',
                    )}
                </span>
            </span>
            <span role="cell" className="min-w-0">
                <span className="block text-[12.5px] font-semibold text-rz-ink">
                    {row.period === null
                        ? t('admin.audit_reports.period_flash')
                        : formatMonthYearLong(row.period, locale)}
                </span>
                <span className="mt-0.5 block text-[11px] text-rz-muted">
                    {row.cosign_by !== null
                        ? t('admin.audit_reports.cosign_by', {
                              date: formatDateTime(row.cosign_by, locale),
                          })
                        : row.sealed_at !== null
                          ? t('admin.audit_reports.sealed_on', {
                                date: formatDate(row.sealed_at, locale),
                            })
                          : row.due_at === null
                            ? '—'
                            : t('admin.audit_reports.due', {
                                  date: formatDate(row.due_at, locale),
                              })}
                </span>
            </span>
            <span role="cell" className="min-w-0">
                <span className="block truncate text-[12.5px] text-rz-body">
                    {row.auditor ?? '—'}
                </span>
                {row.licence !== null && (
                    <span className="mt-0.5 block text-[11px] text-rz-muted">
                        {row.licence}
                    </span>
                )}
            </span>
            <span role="cell">
                <Chip tone={STATUS_TONE[row.status]}>
                    {t(`admin.audit_reports.status.${row.status}`)}
                </Chip>
                {row.sealed_at !== null && (
                    <span className="mt-1 block font-mono text-[10.5px] text-rz-muted">
                        sha256:{row.digest?.slice(0, 8)}
                    </span>
                )}
            </span>
            <span
                role="cell"
                className={cn(
                    'text-[12.5px] font-bold',
                    row.variance_flagged ? TONE_TEXT.red : 'text-rz-ink',
                )}
            >
                {row.variance_pct === null ? '—' : `${row.variance_pct}%`}
            </span>
            <div
                role="cell"
                className="flex flex-wrap items-center justify-end gap-1.5"
            >
                {row.links.remind && (
                    <Link
                        href={row.links.remind}
                        as="button"
                        method="post"
                        preserveScroll
                        aria-label={t(
                            row.owes === 'business'
                                ? 'admin.audit_reports.remind_business'
                                : 'admin.audit_reports.remind_auditor',
                            {
                                business: row.business,
                            },
                        )}
                        className="rounded-lg border border-rz-hairline bg-rz-surface px-2.5 py-1.5 text-[11px] font-bold text-rz-accent-app-text"
                    >
                        {t('admin.audit_reports.remind')}
                    </Link>
                )}
                {row.links.remind === null && row.reminded_at !== null && (
                    <span className="text-[11px] font-medium text-rz-muted">
                        {t('admin.audit_reports.reminded', {
                            date: formatDateTime(row.reminded_at, locale),
                        })}
                    </span>
                )}
                {row.links.pdf && (
                    <a
                        href={row.links.pdf.url}
                        aria-label={t('admin.audit_reports.pdf_named', {
                            business: row.business,
                        })}
                        className="rounded-lg border border-[#e8d69f] bg-rz-surface px-2.5 py-1.5 text-[11px] font-bold text-[#7a5d33] dark:text-[#f0a060]"
                    >
                        {t('admin.audit_reports.pdf')}
                    </a>
                )}
            </div>
        </div>
    );
}

/** The register's table: one row per audit, under the shared column heads. */
function RegisterTable({
    label,
    rows,
    empty,
}: {
    label: string;
    rows: AuditRegisterRow[];
    empty: ReactNode;
}) {
    const { t } = useTranslation();

    return (
        <TableCard
            label={label}
            minWidth="min-w-[980px]"
            empty={rows.length === 0 && empty}
        >
            <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                <HeadCell>{t('admin.audit_reports.col.business')}</HeadCell>
                <HeadCell>{t('admin.audit_reports.col.period')}</HeadCell>
                <HeadCell>{t('admin.audit_reports.col.partner')}</HeadCell>
                <HeadCell>{t('admin.audit_reports.col.status')}</HeadCell>
                <HeadCell>{t('admin.audit_reports.col.variance')}</HeadCell>
                <HeadCell end>{t('admin.audit_reports.col.action')}</HeadCell>
            </div>
            {rows.map((row) => (
                <Row key={row.id} row={row} />
            ))}
        </TableCard>
    );
}

/**
 * Reports oversight (design "Monthly audit compliance tracker" and "Field Flash Audit operations"):
 * every audit with the state of its report, its due date and, once sealed, its digest, weighted
 * variance and PDF. Remind nudges whoever owes the next step: the Audit Partner before the seal,
 * the Business while it owes a co-sign or dispute. The server decides who may remind, and when.
 */
export default function AdminAuditReports(props: AdminAuditReportsProps) {
    const { t, locale } = useTranslation();
    const format = useStatFormatter();

    return (
        <AdminFrame section="reports" {...props}>
            <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                {props.stats.map((stat, index) => (
                    <KpiTile
                        key={stat.key}
                        index={index}
                        label={t(`admin.audit_reports.stats.${stat.key}`)}
                        value={format(stat.value)}
                    />
                ))}
            </div>
            <div className="mb-3">
                <CardTitle>{t('admin.audit_reports.field.title')}</CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.audit_reports.field.caption')}
                </p>
            </div>
            <div className="mb-6">
                <RegisterTable
                    label={t('admin.audit_reports.field.table')}
                    rows={props.field}
                    empty={
                        <EmptyState
                            title={t('admin.audit_reports.field.empty')}
                        />
                    }
                />
            </div>
            <div className="mb-3">
                <CardTitle>{t('admin.audit_reports.title')}</CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.audit_reports.caption')}
                </p>
            </div>
            <div className="mb-3">
                <SegmentedChips
                    label={t('admin.audit_reports.filter')}
                    chips={props.chips.map((chip) => ({
                        ...chip,
                        label: t(`admin.audit_reports.chip.${chip.key}`),
                    }))}
                />
            </div>
            <RegisterTable
                label={t('admin.audit_reports.table')}
                rows={props.rows}
                empty={
                    <EmptyState
                        title={t('admin.audit_reports.empty_title')}
                        body={t('admin.audit_reports.empty_body')}
                    />
                }
            />
            <div className="mt-6 mb-3">
                <CardTitle>{t('admin.audit_reports.feed.title')}</CardTitle>
            </div>
            <div className="grid gap-3 md:grid-cols-2">
                {props.published.map((row) => (
                    <div
                        key={row.id}
                        className="rounded-2xl border border-rz-hairline bg-rz-surface p-4"
                    >
                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                            <span className="text-[13.5px] font-bold text-rz-ink">
                                {row.business}
                            </span>
                            <span className="text-[11.5px] text-rz-muted">
                                {row.period === null
                                    ? t('admin.audit_reports.flash')
                                    : formatMonthYearLong(row.period, locale)}
                            </span>
                        </div>
                        <p className="mt-1.5 text-[12px] text-rz-positive">
                            {t('admin.audit_reports.feed.audited', {
                                auditor: row.auditor ?? '—',
                                licence: row.licence ?? '—',
                            })}
                        </p>
                    </div>
                ))}
                {props.published.length === 0 && (
                    <p className="text-[12.5px] text-rz-muted">
                        {t('admin.audit_reports.feed.empty')}
                    </p>
                )}
            </div>
        </AdminFrame>
    );
}
