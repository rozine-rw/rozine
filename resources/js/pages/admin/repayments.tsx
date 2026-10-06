import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { formatTimestamp } from '@/components/admin/format';
import { RepaymentStateChip } from '@/components/admin/repayments/panels';
import { RepaymentDrawer } from '@/components/admin/repayments/repayment-drawer';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    CardTitle,
    EmptyState,
    HeadCell,
    ROW_RULE,
    ShowMoreLink,
    TABLE_HEAD,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AdminRepaymentsProps, RepaymentRow } from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.2fr)_minmax(0,1.9fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,.7fr)_minmax(0,1.2fr)_minmax(200px,1.4fr)] gap-3 px-5';

function Row({ row }: { row: RepaymentRow }) {
    const { t } = useTranslation();

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3.5')}>
            <span role="cell" className="font-mono text-[12px] text-rz-body">
                {row.reference}
            </span>
            <span
                role="cell"
                className="text-[12.5px] font-semibold text-rz-ink"
            >
                {row.business} · {row.note_title}
            </span>
            <span role="cell" className="text-[12px] text-rz-body">
                {t(`admin.repayments.source.${row.source}`)}
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatRwfShort(row.amount)}
            </span>
            <span role="cell" className="text-[12px] text-rz-body">
                {row.dpd_at_receipt === null ? '—' : row.dpd_at_receipt}
            </span>
            <span role="cell" className="text-[12px] text-rz-body">
                {formatTimestamp(row.received_at)}
            </span>
            <div role="cell" className="flex items-center justify-end gap-1.5">
                <RepaymentStateChip state={row.state} />
                <Link
                    href={row.link}
                    aria-label={t('admin.disbursements.open_named', {
                        reference: row.reference,
                    })}
                    className={cn(
                        'shrink-0 rounded-lg px-3 py-1.5 text-[11.5px] font-semibold',
                        row.state === 'exception'
                            ? 'bg-[#e5484d] text-white'
                            : 'border border-rz-hairline bg-rz-surface text-rz-slate',
                    )}
                >
                    {t(
                        row.state === 'exception'
                            ? 'admin.disbursements.action.inspect'
                            : 'admin.disbursements.action.open',
                    )}
                </Link>
            </div>
        </div>
    );
}

/**
 * Repayments (AC-13, C4 v1 §4b, `staff-servicing-v1`): every receipt against a note, with its
 * reconciliation and allocation. Wallet repayments allocate with no staff step; an exception stays
 * blocked and is never shown as reconciled. The queue is read-only: nothing commits from the list,
 * and the drawer offers only requery, when the server lists it.
 */
export default function AdminRepayments(props: AdminRepaymentsProps) {
    const { t } = useTranslation();

    return (
        <AdminFrame
            section="repayments"
            {...props}
            overlay={
                props.repayment && (
                    <RepaymentDrawer
                        key={props.repayment.id}
                        repayment={props.repayment}
                        viewer={props.viewer}
                        preview={props.preview_outcome}
                    />
                )
            }
        >
            <div className="mb-3.5 flex flex-wrap items-center gap-2.5">
                <CardTitle>{t('admin.repayments.title')}</CardTitle>
                {(['due_today', 'overdue', 'exceptions'] as const).map(
                    (key) =>
                        props.counts[key] > 0 && (
                            <span
                                key={key}
                                className={cn(
                                    'rounded-lg border px-2.5 py-1 text-[11.5px] font-semibold',
                                    key === 'exceptions'
                                        ? 'border-[#fdd9da] bg-[rgba(229,72,77,.08)] text-[#c4373c] dark:border-rz-border dark:text-[#ff6b6f]'
                                        : 'border-rz-hairline bg-rz-surface text-rz-slate',
                                )}
                            >
                                {t(`admin.repayments.count.${key}`, {
                                    count: props.counts[key],
                                })}
                            </span>
                        ),
                )}
            </div>
            {props.repayments.length === 0 && props.search !== '' && (
                <SearchEmpty section="repayments" term={props.search} />
            )}
            <TableCard
                label={t('admin.repayments.table')}
                minWidth="min-w-[960px]"
                empty={
                    props.repayments.length === 0 && (
                        <EmptyState
                            title={t('admin.repayments.empty_title')}
                            body={t('admin.repayments.empty_body')}
                        />
                    )
                }
                footer={
                    props.pagination.next !== null && (
                        <ShowMoreLink link={props.pagination.next}>
                            {t('admin.repayments.older')}
                        </ShowMoreLink>
                    )
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.repayments.col.reference')}</HeadCell>
                    <HeadCell>{t('admin.disbursements.col.note')}</HeadCell>
                    <HeadCell>{t('admin.repayments.col.source')}</HeadCell>
                    <HeadCell>{t('admin.disbursements.col.amount')}</HeadCell>
                    <HeadCell>{t('admin.repayments.dpd')}</HeadCell>
                    <HeadCell>{t('admin.repayments.received_at')}</HeadCell>
                    <HeadCell end>
                        {t('admin.disbursements.col.actions')}
                    </HeadCell>
                </div>
                {props.repayments.map((row) => (
                    <Row key={row.id} row={row} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
