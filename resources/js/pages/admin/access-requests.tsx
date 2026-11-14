import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import {
    CardTitle,
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
import type { AccessRequestRow, AdminAccessRequestsProps } from '@/types/admin';
import type { RouteLink } from '@/types/routing';

const GRID =
    'grid grid-cols-[minmax(0,1.4fr)_minmax(0,.7fr)_minmax(0,1.3fr)_minmax(0,1fr)_minmax(120px,.9fr)] gap-3 px-5';

function Row({ row, grant }: { row: AccessRequestRow; grant: RouteLink }) {
    const { t, locale } = useTranslation();

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-start py-3')}>
            <span
                role="cell"
                className="text-[12.5px] font-semibold text-rz-ink"
            >
                {row.name}
                <a
                    href={`mailto:${row.email}`}
                    className="mt-0.5 block text-[11px] font-medium break-all text-rz-accent-text"
                >
                    {row.email}
                </a>
                {row.phone !== null && (
                    <a
                        href={`tel:${row.phone}`}
                        className="mt-0.5 block text-[11px] font-medium text-rz-muted"
                    >
                        {row.phone}
                    </a>
                )}
            </span>
            <span role="cell">
                <Chip tone={row.role === 'business' ? 'blue' : 'purple'}>
                    {t(`suite.app.${row.role}.title`)}
                </Chip>
            </span>
            <span role="cell" className="text-[12.5px] text-rz-ink">
                {row.organization === '' ? '—' : row.organization}
                {row.reference !== null && (
                    <span className="mt-0.5 block text-[11px] font-medium text-rz-muted">
                        {t(`admin.access_requests.reference.${row.role}`, {
                            reference: row.reference,
                        })}
                    </span>
                )}
                {row.note !== null && (
                    <span className="mt-1 block text-[11.5px] whitespace-pre-line text-rz-body">
                        {row.note}
                    </span>
                )}
            </span>
            <span role="cell" className="text-[12px] text-rz-body">
                {formatDateTime(row.sent_at, locale)}
            </span>
            <div role="cell" className="flex justify-end">
                <Link
                    href={grant}
                    className="shrink-0 rounded-lg border border-rz-hairline bg-rz-surface px-3 py-1.5 text-[11.5px] font-semibold text-rz-slate"
                >
                    {t(`admin.access_requests.grant.${row.role}`)}
                </Link>
            </div>
        </div>
    );
}

/**
 * Business and Auditor access requests sent from the launcher (#616), newest first, for staff who
 * may act on them: Business requests for whoever onboards businesses, Auditor requests for whoever
 * verifies Audit Partners. Read-only: each row opens the tool that grants the role.
 */
export default function AdminAccessRequests(props: AdminAccessRequestsProps) {
    const { t } = useTranslation();

    return (
        <AdminFrame section="access_requests" searchable={false} {...props}>
            <div className="mb-3.5">
                <CardTitle>{t('admin.access_requests.title')}</CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.access_requests.caption')}
                </p>
            </div>
            <TableCard
                label={t('admin.access_requests.table')}
                minWidth="min-w-[760px]"
                empty={
                    props.requests.length === 0 && (
                        <EmptyState
                            title={t('admin.access_requests.empty_title')}
                            body={t('admin.access_requests.empty_body')}
                        />
                    )
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.access_requests.col.person')}</HeadCell>
                    <HeadCell>{t('admin.access_requests.col.role')}</HeadCell>
                    <HeadCell>
                        {t('admin.access_requests.col.details')}
                    </HeadCell>
                    <HeadCell>{t('admin.access_requests.col.sent')}</HeadCell>
                    <HeadCell end>
                        {t('admin.access_requests.col.action')}
                    </HeadCell>
                </div>
                {props.requests.map((row) => (
                    <Row key={row.id} row={row} grant={props.links[row.role]} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
