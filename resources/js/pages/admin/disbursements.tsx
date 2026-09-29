import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import {
    DisbursementDrawer,
    STATE_TONE,
} from '@/components/admin/disbursements/disbursement-drawer';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    CardTitle,
    Chip,
    EmptyState,
    HeadCell,
    PolicyPeek,
    ROW_RULE,
    TABLE_HEAD,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AdminDisbursementsProps,
    DisbursementRow,
    DisbursementState,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.2fr)_minmax(0,1.9fr)_minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,0.9fr)_minmax(230px,1.5fr)] gap-3 px-5';

const OPEN_LABEL: Record<
    DisbursementState,
    'release' | 'check' | 'open' | 'inspect'
> = {
    ready: 'release',
    awaiting_second_approver: 'check',
    on_hold: 'open',
    dispatched: 'open',
    paid: 'open',
    failed: 'inspect',
};

/** Whole Kigali calendar days from the server's today to the due date. */
const daysUntil = (dueOn: string, serverTime: string): number =>
    Math.round(
        (Date.parse(`${dueOn.slice(0, 10)}T00:00:00Z`) -
            Date.parse(`${serverTime.slice(0, 10)}T00:00:00Z`)) /
            86400000,
    );

function DueLabel({
    dueOn,
    serverTime,
}: {
    dueOn: string;
    serverTime: string;
}) {
    const { t } = useTranslation();
    const days = daysUntil(dueOn, serverTime);

    if (days < 0) {
        return (
            <span className="text-[12px] font-semibold text-[#e5484d] dark:text-[#ff6b6f]">
                {t('admin.disbursements.overdue', { count: -days })}
            </span>
        );
    }

    if (days === 0) {
        return (
            <span className="text-[12px] font-semibold text-[#e5484d] dark:text-[#ff6b6f]">
                {t('admin.disbursements.today')}
            </span>
        );
    }

    if (days === 1) {
        return (
            <span className="text-[12px] font-semibold text-[#c2661f] dark:text-[#f0a060]">
                {t('admin.disbursements.tomorrow')}
            </span>
        );
    }

    return (
        <span className="text-[12px] font-semibold text-rz-body">
            {t('admin.disbursements.in_days', { count: days })}
        </span>
    );
}

function Row({
    row,
    serverTime,
}: {
    row: DisbursementRow;
    serverTime: string;
}) {
    const { t } = useTranslation();
    const primary =
        row.state === 'ready' ||
        row.state === 'awaiting_second_approver' ||
        row.state === 'failed';

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
            <span role="cell" className="truncate text-[12px] text-rz-body">
                {row.destination}
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatRwfShort(row.amount)}
            </span>
            <span role="cell">
                <DueLabel dueOn={row.due_on} serverTime={serverTime} />
            </span>
            <div role="cell" className="flex items-center justify-end gap-1.5">
                <Chip tone={STATE_TONE[row.state]}>
                    {t(`admin.disbursements.state.${row.state}`)}
                </Chip>
                <Link
                    href={row.link}
                    aria-label={t('admin.disbursements.open_named', {
                        reference: row.reference,
                    })}
                    className={cn(
                        'shrink-0 rounded-lg px-3 py-1.5 text-[11.5px] font-semibold',
                        primary
                            ? row.state === 'failed'
                                ? 'bg-[#e5484d] text-white'
                                : 'bg-[#1d9e75] text-white'
                            : 'border border-rz-hairline bg-rz-surface text-rz-slate',
                    )}
                >
                    {t(`admin.disbursements.action.${OPEN_LABEL[row.state]}`)}
                </Link>
            </div>
        </div>
    );
}

/**
 * Disbursements (MVP-ADMIN-SCR-03, design "Pending disbursements" T1690–1715): approved, funded
 * raises waiting to be paid to their business. The design's per-row Release and "Release all"
 * are replaced by the maker-checker drawer — no release commits from the list, and nothing is
 * released in bulk (AC-03).
 */
export default function AdminDisbursements(props: AdminDisbursementsProps) {
    const { t } = useTranslation();
    const awaiting = props.awaiting_second_approver;

    return (
        <AdminFrame
            section="disbursements"
            {...props}
            overlay={
                props.disbursement && (
                    <DisbursementDrawer
                        key={props.disbursement.id}
                        disbursement={props.disbursement}
                        viewer={props.viewer}
                    />
                )
            }
        >
            <PolicyPeek
                title={t('admin.disbursements.policy_title')}
                items={props.policy}
            />
            <div className="mb-3.5 flex flex-wrap items-center gap-2.5">
                <CardTitle>{t('admin.disbursements.title')}</CardTitle>
                {awaiting > 0 && (
                    <span className="rounded-lg border border-[#e7e1fb] bg-[rgba(124,58,237,.1)] px-2.5 py-1 text-[11.5px] font-semibold text-[#7c3aed] dark:border-rz-border dark:text-[#b199fb]">
                        {t('admin.disbursements.awaiting_count', {
                            count: awaiting,
                        })}
                    </span>
                )}
            </div>
            {props.disbursements.length === 0 && props.search !== '' && (
                <SearchEmpty section="disbursements" term={props.search} />
            )}
            <TableCard
                label={t('admin.disbursements.table')}
                minWidth="min-w-[900px]"
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>
                        {t('admin.disbursements.col.reference')}
                    </HeadCell>
                    <HeadCell>{t('admin.disbursements.col.note')}</HeadCell>
                    <HeadCell>
                        {t('admin.disbursements.col.recipient')}
                    </HeadCell>
                    <HeadCell>{t('admin.disbursements.col.amount')}</HeadCell>
                    <HeadCell>{t('admin.disbursements.col.due')}</HeadCell>
                    <HeadCell end>
                        {t('admin.disbursements.col.actions')}
                    </HeadCell>
                </div>
                {props.disbursements.map((row) => (
                    <Row
                        key={row.id}
                        row={row}
                        serverTime={props.server_time}
                    />
                ))}
                {props.disbursements.length === 0 && (
                    <EmptyState
                        title={t('admin.disbursements.empty_title')}
                        body={t('admin.disbursements.empty_body')}
                    />
                )}
            </TableCard>
        </AdminFrame>
    );
}
