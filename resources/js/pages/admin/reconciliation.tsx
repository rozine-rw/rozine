import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    CardTitle,
    Chip,
    EmptyState,
    HeadCell,
    Panel,
    ROW_RULE,
    TABLE_HEAD,
    TONE_TEXT,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDateTime, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AdminReconciliationProps,
    ReconciliationAccount,
    ReconciliationBreakRecord,
    ReconciliationDayClose,
    Tone,
} from '@/types/admin';

const ACCOUNT_GRID =
    'grid grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.1fr)] gap-3 px-5';

const BREAK_GRID =
    'grid grid-cols-[minmax(0,1fr)_minmax(0,2.4fr)_minmax(0,.9fr)_minmax(0,1.2fr)_minmax(90px,.6fr)] gap-3 px-5';

const DAY_CLOSE_TONE: Record<ReconciliationDayClose['state'], Tone> = {
    reconciled: 'green',
    open_break: 'red',
    not_reconciled: 'amber',
};

function DayClose({ close }: { close: ReconciliationDayClose }) {
    const { t, locale } = useTranslation();

    return (
        <Panel
            label={t('admin.reconciliation.day_close.label')}
            className="mb-4"
        >
            <div className="flex flex-wrap items-center gap-2.5">
                <CardTitle>
                    {t('admin.reconciliation.day_close.title', {
                        date: formatDate(close.business_date, locale),
                    })}
                </CardTitle>
                <Chip tone={DAY_CLOSE_TONE[close.state]}>
                    {t(`admin.reconciliation.day_close.state.${close.state}`)}
                </Chip>
            </div>
            <p className="mt-1.5 text-[12.5px] text-rz-body">
                {close.state === 'reconciled'
                    ? t('admin.reconciliation.day_close.body.reconciled', {
                          time: formatDateTime(close.reconciled_at, locale),
                      })
                    : t(`admin.reconciliation.day_close.body.${close.state}`)}
            </p>
        </Panel>
    );
}

function AccountRow({ account }: { account: ReconciliationAccount }) {
    const { t, locale } = useTranslation();
    const balanced =
        account.difference !== null && account.difference.amount === '0';

    return (
        <div
            role="row"
            className={cn(ACCOUNT_GRID, ROW_RULE, 'items-center py-3.5')}
        >
            <span
                role="cell"
                className="text-[12.5px] font-semibold text-rz-ink"
            >
                {account.label}
                <span className="mt-0.5 block text-[11px] font-medium text-rz-muted">
                    {t('admin.reconciliation.as_of', {
                        time: formatDateTime(account.as_of, locale),
                    })}
                </span>
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatRwf(account.ledger)}
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {account.statement === null
                    ? t('admin.reconciliation.no_statement')
                    : formatRwf(account.statement)}
            </span>
            <span
                role="cell"
                className={cn(
                    'text-[12.5px] font-bold',
                    account.difference === null
                        ? 'text-rz-body'
                        : balanced
                          ? TONE_TEXT.green
                          : TONE_TEXT.red,
                )}
            >
                {account.difference === null
                    ? '—'
                    : formatRwf(account.difference)}
            </span>
            <span role="cell">
                {account.feed.state === 'available' ? (
                    <Chip tone="green">
                        {t('admin.reconciliation.feed.available')}
                    </Chip>
                ) : (
                    <>
                        <Chip tone="amber">
                            {t('admin.reconciliation.feed.unavailable')}
                        </Chip>
                        <span className="mt-1 block text-[11px] font-semibold text-rz-muted">
                            {t('admin.reconciliation.feed.since', {
                                time: formatDateTime(
                                    account.feed.since,
                                    locale,
                                ),
                            })}
                        </span>
                    </>
                )}
            </span>
        </div>
    );
}

function BreakRow({
    item,
    account,
}: {
    item: ReconciliationBreakRecord;
    account: string | undefined;
}) {
    const { t } = useTranslation();

    return (
        <div
            role="row"
            className={cn(BREAK_GRID, ROW_RULE, 'items-center py-3.5')}
        >
            <span role="cell" className="font-mono text-[12px] text-rz-body">
                {item.reference}
            </span>
            <span
                role="cell"
                className="text-[12.5px] font-semibold text-rz-ink"
            >
                {item.description}
                {account !== undefined && (
                    <span className="mt-0.5 block text-[11px] font-medium text-rz-muted">
                        {account}
                    </span>
                )}
            </span>
            <span
                role="cell"
                className={cn('text-[12.5px] font-bold', TONE_TEXT.red)}
            >
                {formatRwf(item.gap)}
            </span>
            <span role="cell" className="text-[12px] text-rz-body">
                {t('admin.exceptions.age', { count: item.age_days })}
                <span
                    className={cn(
                        'block text-[11px] font-semibold',
                        item.owner === null ? TONE_TEXT.amber : 'text-rz-muted',
                    )}
                >
                    {item.owner === null
                        ? t('admin.today.breaks.unassigned')
                        : t('admin.today.breaks.owner', {
                              name: item.owner.actor,
                          })}
                </span>
            </span>
            <div role="cell" className="flex justify-end">
                <Link
                    href={item.link}
                    aria-label={t('admin.reconciliation.open_named', {
                        reference: item.reference,
                    })}
                    className="shrink-0 rounded-lg border border-rz-hairline bg-rz-surface px-3 py-1.5 text-[11.5px] font-semibold text-rz-slate"
                >
                    {t('admin.disbursements.action.open')}
                </Link>
            </div>
        </div>
    );
}

/**
 * Reconciliation (MVP-ADMIN-SCR-04, Phase 2 proposal `staff-reconciliation-v1`): the close of the
 * last business day, then each account's three balances side by side (ledger, statement and their
 * difference) as the server states them, and every open break, aged, linking to its record. The
 * account topology is undecided (D-21), so the page shows the server's account names and knows no
 * account kinds, reserve or float. Read-only: assigning, escalating and closing the day are not
 * offered.
 */
export default function AdminReconciliation(props: AdminReconciliationProps) {
    const { t } = useTranslation();
    const accountLabel = new Map(
        props.accounts.map((account) => [account.id, account.label]),
    );

    return (
        <AdminFrame section="reconciliation" {...props}>
            <DayClose close={props.day_close} />
            {props.accounts.length === 0 && props.search !== '' && (
                <SearchEmpty section="reconciliation" term={props.search} />
            )}
            <div className="mb-3.5">
                <CardTitle>
                    {t('admin.reconciliation.accounts.title')}
                </CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.reconciliation.accounts.caption')}
                </p>
            </div>
            <TableCard
                label={t('admin.reconciliation.accounts.table')}
                minWidth="min-w-[860px]"
                className="mb-6"
                empty={
                    props.accounts.length === 0 && (
                        <EmptyState
                            title={t(
                                'admin.reconciliation.accounts.empty_title',
                            )}
                            body={t('admin.reconciliation.accounts.empty_body')}
                        />
                    )
                }
            >
                <div
                    role="row"
                    className={cn(ACCOUNT_GRID, TABLE_HEAD, 'py-[13px]')}
                >
                    <HeadCell>{t('admin.reconciliation.col.account')}</HeadCell>
                    <HeadCell>{t('admin.reconciliation.col.ledger')}</HeadCell>
                    <HeadCell>
                        {t('admin.reconciliation.col.statement')}
                    </HeadCell>
                    <HeadCell>
                        {t('admin.reconciliation.col.difference')}
                    </HeadCell>
                    <HeadCell>{t('admin.reconciliation.col.feed')}</HeadCell>
                </div>
                {props.accounts.map((account) => (
                    <AccountRow key={account.id} account={account} />
                ))}
            </TableCard>
            <div className="mb-3.5">
                <CardTitle>{t('admin.reconciliation.breaks.title')}</CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.reconciliation.breaks.caption')}
                </p>
            </div>
            <TableCard
                label={t('admin.reconciliation.breaks.table')}
                minWidth="min-w-[820px]"
                empty={
                    props.breaks.length === 0 && (
                        <EmptyState
                            title={t('admin.reconciliation.breaks.empty_title')}
                            body={t('admin.reconciliation.breaks.empty_body')}
                        />
                    )
                }
            >
                <div
                    role="row"
                    className={cn(BREAK_GRID, TABLE_HEAD, 'py-[13px]')}
                >
                    <HeadCell>
                        {t('admin.reconciliation.col.reference')}
                    </HeadCell>
                    <HeadCell>{t('admin.reconciliation.col.break')}</HeadCell>
                    <HeadCell>{t('admin.reconciliation.col.gap')}</HeadCell>
                    <HeadCell>{t('admin.reconciliation.col.owner')}</HeadCell>
                    <HeadCell end>
                        {t('admin.reconciliation.col.record')}
                    </HeadCell>
                </div>
                {props.breaks.map((item) => (
                    <BreakRow
                        key={item.id}
                        item={item}
                        account={accountLabel.get(item.account_id)}
                    />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
