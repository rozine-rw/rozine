import { AdminFrame } from '@/components/admin/admin-frame';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    CardTitle,
    EmptyState,
    HeadCell,
    KpiTile,
    ROW_RULE,
    ShowMoreLink,
    TABLE_HEAD,
    TableCard,
    useStatFormatter,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AdminPrimaryMarketProps,
    PrimaryMarketRaise,
    PrimaryMarketStatKey,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.5fr)_150px_minmax(0,1.5fr)_110px] gap-3.5 px-5';

/** The design's rail colours: ink, green, ink, blue. */
const STAT_ACCENT: Record<PrimaryMarketStatKey, number> = {
    active: 6,
    in_flight: 1,
    fill: 6,
    completion: 0,
};

/** Green once the raise is full, blue while it fills (design `pctColor`). */
const fillColour = (raise: PrimaryMarketRaise): [string, string] =>
    Number(raise.funded_pct) >= 100
        ? ['bg-[#1d9e75]', 'text-[#1d9e75]']
        : ['bg-[#1e3aff]', 'text-[#1e3aff] dark:text-[#99a3ff]'];

function Row({ raise }: { raise: PrimaryMarketRaise }) {
    const { t } = useTranslation();
    const [bar, text] = fillColour(raise);
    const width = `${Math.min(100, Number(raise.funded_pct))}%`;

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3.5')}>
            <div role="cell" className="min-w-0">
                <div className="flex items-center gap-2">
                    <span className="min-w-0 truncate text-[13px] font-bold text-rz-ink">
                        {raise.business}
                    </span>
                    {raise.lifecycle !== 'live' && (
                        <span className="shrink-0 rounded-[5px] bg-[rgba(210,120,45,.14)] px-1.5 py-0.5 text-[9px] font-bold text-[#c2661f] uppercase">
                            {t(`admin.primary.lifecycle.${raise.lifecycle}`)}
                        </span>
                    )}
                </div>
                <div className="mt-0.5 truncate text-[11px] text-rz-muted">
                    {[
                        raise.reference,
                        raise.sector,
                        t('admin.primary.yield', { rate: raise.rate_pct }),
                        t('admin.primary.term', { count: raise.term_months }),
                    ].join(' · ')}
                </div>
            </div>
            <div role="cell" className="flex items-center gap-[9px]">
                <div className="h-1.5 flex-1 overflow-hidden rounded bg-[#e6ecf6] dark:bg-rz-surface-muted">
                    <div className={cn('h-full', bar)} style={{ width }} />
                </div>
                <span className={cn('shrink-0 text-[12px] font-bold', text)}>
                    {t('admin.primary.funded', { pct: raise.funded_pct })}
                </span>
            </div>
            <div role="cell" className="min-w-0">
                <div className="truncate text-[12.5px] font-semibold text-rz-ink">
                    {t('admin.primary.raised_of', {
                        raised: formatRwf(raise.raised),
                        target: formatRwf(raise.target),
                    })}
                </div>
                <div className="mt-0.5 text-[11px] text-rz-muted">
                    {t('admin.primary.investors', { count: raise.investors })}
                </div>
            </div>
            <span
                role="cell"
                className="text-[12px] whitespace-nowrap text-rz-body"
            >
                {raise.days_left === 0
                    ? t('admin.primary.closes_today')
                    : t('admin.primary.days_left', { count: raise.days_left })}
            </span>
        </div>
    );
}

/**
 * Primary Market Monitor (`staff-primary-market-v1`): every raise still filling, with its fill,
 * investors and days left, and the server's four market figures. Read-only: a raise fills or
 * fails on its own clock (BRS), so the design's Feature, Pause, Fill and Fail moderation is not
 * offered.
 */
export default function AdminPrimaryMarket(props: AdminPrimaryMarketProps) {
    const { t } = useTranslation();
    const format = useStatFormatter();

    return (
        <AdminFrame section="primary_market" {...props}>
            <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                {props.stats.map((stat) => (
                    <KpiTile
                        key={stat.key}
                        index={STAT_ACCENT[stat.key]}
                        label={t(`admin.design.primary_market.kpi.${stat.key}`)}
                        value={format(stat.value)}
                        sub={t(
                            `admin.design.primary_market.kpi.${stat.key}.sub`,
                        )}
                    />
                ))}
            </div>
            <div className="mb-3.5">
                <CardTitle>{t('admin.primary.title')}</CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.primary.caption')}
                </p>
            </div>
            {props.raises.length === 0 && props.search !== '' && (
                <SearchEmpty section="primary_market" term={props.search} />
            )}
            <TableCard
                label={t('admin.primary.title')}
                minWidth="min-w-[760px]"
                footer={
                    <>
                        {props.raises.length === 0 && props.search === '' && (
                            <EmptyState
                                title={t('admin.primary.empty_title')}
                                body={t('admin.primary.empty_body')}
                            />
                        )}
                        {props.pagination.next !== null && (
                            <ShowMoreLink link={props.pagination.next}>
                                {t('admin.primary.more')}
                            </ShowMoreLink>
                        )}
                    </>
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.primary.col.raise')}</HeadCell>
                    <HeadCell>{t('admin.primary.col.funded')}</HeadCell>
                    <HeadCell>{t('admin.primary.col.progress')}</HeadCell>
                    <HeadCell>{t('admin.primary.col.closes')}</HeadCell>
                </div>
                {props.raises.map((raise) => (
                    <Row key={raise.id} raise={raise} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
