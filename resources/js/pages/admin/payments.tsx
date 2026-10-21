import { AdminFrame } from '@/components/admin/admin-frame';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    CardTitle,
    EmptyState,
    HeadCell,
    KpiTile,
    Panel,
    ROW_RULE,
    ShowMoreLink,
    TABLE_HEAD,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AdminPaymentsProps,
    PaymentsStatKey,
    PayoutInFlight,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[110px_minmax(0,1.4fr)_140px_120px_minmax(0,1.4fr)_150px] gap-3 px-5';

/** The design's rail colours: green, ink, blue. */
const STAT_ACCENT: Record<PaymentsStatKey, number> = {
    deposits: 1,
    withdrawals: 6,
    disbursed: 0,
};

/** "Money movement": credited deposits plus paid-out withdrawals per Kigali day. */
function MovementPanel({ bars }: { bars: AdminPaymentsProps['bars'] }) {
    const { t } = useTranslation();

    return (
        <Panel
            label={t('admin.design.payments.movement.title')}
            className="mb-6"
        >
            <CardTitle>{t('admin.design.payments.movement.title')}</CardTitle>
            <p className="mt-[3px] text-[12px] text-rz-muted">
                {t('admin.payments.movement.caption')}
            </p>
            <div className="mt-5 flex h-[180px] items-end gap-2.5">
                {bars.length === 0 && (
                    <p className="w-full self-center py-6 text-center text-[12.5px] text-rz-faint">
                        {t('admin.design.payments.movement.empty')}
                    </p>
                )}
                {bars.map((bar) => (
                    <div
                        key={bar.label}
                        data-testid="chart-bar"
                        className="flex h-full max-w-14 min-w-0 flex-1 flex-col items-center justify-end gap-1.5"
                    >
                        <span className="text-[8.5px] font-bold whitespace-nowrap text-[#1428a8] dark:text-[#99a3ff]">
                            {formatRwfShort(bar.amount).replace('RWF ', '')}
                        </span>
                        <div
                            className="w-full rounded-t-md bg-[#1e3aff] dark:bg-[#3d57ff]"
                            style={{
                                height: `${Math.max(2, bar.height_pct)}%`,
                            }}
                        />
                        <span className="text-[10px] text-rz-muted">
                            {bar.label}
                        </span>
                    </div>
                ))}
            </div>
        </Panel>
    );
}

function Row({ payout }: { payout: PayoutInFlight }) {
    const { t } = useTranslation();

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3.5')}>
            <span role="cell" className="font-mono text-[12px] text-rz-muted">
                {payout.reference}
            </span>
            <span
                role="cell"
                className="min-w-0 truncate text-[13px] font-semibold text-rz-ink"
            >
                {payout.name ?? t('admin.payments.unnamed')}
            </span>
            <span
                role="cell"
                className={cn(
                    'w-fit rounded-md px-2 py-[3px] text-[11px] font-semibold',
                    payout.owner === 'investor'
                        ? 'bg-[rgba(30,58,255,.1)] text-[#1e3aff] dark:text-[#99a3ff]'
                        : 'bg-[rgba(124,58,237,.1)] text-[#7c3aed] dark:text-[#c4a8ff]',
                )}
            >
                {t(`admin.payments.owner.${payout.owner}`)}
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatRwf(payout.amount)}
            </span>
            <span
                role="cell"
                className="min-w-0 truncate text-[12px] text-rz-body"
            >
                {payout.account}
            </span>
            <span
                role="cell"
                className={cn(
                    'text-[11.5px] font-semibold',
                    payout.state === 'unknown'
                        ? 'text-[#a4521a]'
                        : 'text-rz-muted',
                )}
            >
                {t(`admin.payments.state.${payout.state}`)}
            </span>
        </div>
    );
}

/**
 * Payment Center (`staff-payments-v1`): the money that moved over the last fourteen Kigali days,
 * the same deposits and payouts per day, and every withdrawal whose payout is still in flight.
 * Read-only: a payout follows the provider's verified outcome and no hold-for-review policy is
 * approved, so the design's Release, Hold and Reverse buttons are not offered.
 */
export default function AdminPayments(props: AdminPaymentsProps) {
    const { t } = useTranslation();

    return (
        <AdminFrame section="payments" {...props}>
            <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                {props.stats.map((stat) => (
                    <KpiTile
                        key={stat.key}
                        index={STAT_ACCENT[stat.key]}
                        label={t(`admin.design.payments.kpi.${stat.key}`)}
                        value={formatRwfShort(stat.value)}
                        sub={t('admin.payments.kpi.count', {
                            count: stat.count,
                        })}
                    />
                ))}
                <KpiTile
                    index={props.in_flight > 0 ? 3 : 1}
                    label={t('admin.payments.kpi.in_flight')}
                    value={String(props.in_flight)}
                    sub={t('admin.payments.kpi.in_flight.sub')}
                />
            </div>
            <MovementPanel bars={props.bars} />
            <div className="mb-3.5">
                <CardTitle>{t('admin.payments.title')}</CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.payments.caption')}
                </p>
            </div>
            {props.payouts.length === 0 && props.search !== '' && (
                <SearchEmpty section="payments" term={props.search} />
            )}
            <TableCard
                label={t('admin.payments.title')}
                minWidth="min-w-[820px]"
                footer={
                    <>
                        {props.payouts.length === 0 && props.search === '' && (
                            <EmptyState
                                title={t('admin.payments.empty_title')}
                                body={t('admin.payments.empty_body')}
                            />
                        )}
                        {props.pagination.next !== null && (
                            <ShowMoreLink link={props.pagination.next}>
                                {t('admin.payments.more')}
                            </ShowMoreLink>
                        )}
                    </>
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.payments.col.reference')}</HeadCell>
                    <HeadCell>{t('admin.payments.col.who')}</HeadCell>
                    <HeadCell>{t('admin.payments.col.type')}</HeadCell>
                    <HeadCell>{t('admin.payments.col.amount')}</HeadCell>
                    <HeadCell>{t('admin.payments.col.account')}</HeadCell>
                    <HeadCell>{t('admin.payments.col.state')}</HeadCell>
                </div>
                {props.payouts.map((payout) => (
                    <Row key={payout.id} payout={payout} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
