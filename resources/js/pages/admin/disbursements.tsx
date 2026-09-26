import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { DisbursementDrawer } from '@/components/admin/disbursements/disbursement-drawer';
import {
    DisbursementStateChip,
    ProviderStateChip,
} from '@/components/admin/disbursements/disbursement-status';
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
import { useRefusalText } from '@/components/rozine/c3-notice';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    C3AdminDisbursementsProps,
    C3DisbursementRow,
    C3DisbursementState,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.2fr)_minmax(0,1.9fr)_minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1.2fr)_minmax(230px,1.5fr)] gap-3 px-5';

const OPEN_LABEL: Record<
    C3DisbursementState,
    'authorize' | 'check' | 'open' | 'inspect'
> = {
    ready: 'authorize',
    awaiting_second_approver: 'check',
    on_hold: 'open',
    queued: 'open',
    dispatched: 'open',
    succeeded: 'open',
    failed_closing: 'inspect',
};

const OPEN_TONE: Partial<Record<C3DisbursementState, string>> = {
    ready: 'bg-[#1d9e75] text-white',
    awaiting_second_approver: 'bg-[#1d9e75] text-white',
    failed_closing: 'bg-[#e5484d] text-white',
};

function Row({ row }: { row: C3DisbursementRow }) {
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
            <span role="cell" className="truncate text-[12px] text-rz-body">
                {row.destination}
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatRwfShort(row.amount)}
            </span>
            <span role="cell">
                <ProviderStateChip state={row.provider_state} />
            </span>
            <div role="cell" className="flex items-center justify-end gap-1.5">
                <DisbursementStateChip state={row.state} />
                <Link
                    href={row.link}
                    aria-label={t('admin.disbursements.open_named', {
                        reference: row.reference,
                    })}
                    className={cn(
                        'shrink-0 rounded-lg px-3 py-1.5 text-[11.5px] font-semibold',
                        OPEN_TONE[row.state] ??
                            'border border-rz-hairline bg-rz-surface text-rz-slate',
                    )}
                >
                    {t(`admin.disbursements.action.${OPEN_LABEL[row.state]}`)}
                </Link>
            </div>
        </div>
    );
}

/**
 * Disbursements (MVP-ADMIN-SCR-03, C3 proposal v2 §2e): funded raises waiting to be paid to
 * their business, and the ones already in flight. No release commits from the list and nothing
 * is released in bulk; every command runs from the drawer with two distinct staff. There is no
 * sourced deadline after full funding, so none is shown, and a provider that has not confirmed an
 * outcome never reads as paid or failed.
 */
export default function AdminDisbursements(props: C3AdminDisbursementsProps) {
    const { t } = useTranslation();
    const refusal = useRefusalText();
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
                        preview={props.preview_outcome}
                    />
                )
            }
        >
            {props.disbursement === null && props.refusal !== null && (
                <p
                    role="alert"
                    className="mb-3.5 rounded-2xl border border-rz-border bg-rz-surface p-4 text-[12.5px] leading-[1.55] text-rz-secondary"
                >
                    {refusal(props.refusal.code)}
                </p>
            )}
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
                footer={
                    props.pagination.next !== null && (
                        <ShowMoreLink link={props.pagination.next}>
                            {t('admin.disbursements.older')}
                        </ShowMoreLink>
                    )
                }
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
                    <HeadCell>{t('admin.disbursements.col.provider')}</HeadCell>
                    <HeadCell end>
                        {t('admin.disbursements.col.actions')}
                    </HeadCell>
                </div>
                {props.disbursements.map((row) => (
                    <Row key={row.id} row={row} />
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
