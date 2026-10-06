import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    CardTitle,
    Chip,
    EmptyState,
    HeadCell,
    KpiTile,
    ROW_RULE,
    TABLE_HEAD,
    TableCard,
    useStatFormatter,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AdminCoverageProps,
    CoverageDistrict,
    CoverageStatKey,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,.9fr)_minmax(110px,.8fr)] gap-3 px-5';

/** Each tile keeps its accent whatever the order: uncovered reads in the red accent. */
const STAT_ACCENT: Record<CoverageStatKey, number> = {
    districts: 0,
    uncovered: 7,
    audits_open: 2,
};

function Row({ row }: { row: CoverageDistrict }) {
    const { t } = useTranslation();

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3')}>
            <span
                role="cell"
                className="text-[12.5px] font-semibold text-rz-ink"
            >
                {row.district}
                <span className="mt-0.5 block text-[11px] font-medium text-rz-muted">
                    {row.province}
                </span>
            </span>
            {/* The verdict comes second, so a phone shows it without scrolling. */}
            <span role="cell">
                <Chip tone={row.capacity === 'covered' ? 'green' : 'red'}>
                    {t(`admin.coverage.capacity.${row.capacity}`)}
                </Chip>
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatCount(row.active_partners)}
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatCount(row.audits_open)}
            </span>
            <div role="cell" className="flex justify-end">
                {row.link === null ? (
                    <span className="text-[11.5px] text-rz-muted">—</span>
                ) : (
                    <Link
                        href={row.link}
                        aria-label={t('admin.coverage.open_named', {
                            district: row.district,
                        })}
                        className="shrink-0 rounded-lg border border-rz-hairline bg-rz-surface px-3 py-1.5 text-[11.5px] font-semibold text-rz-slate"
                    >
                        {t('admin.coverage.open')}
                    </Link>
                )}
            </div>
        </div>
    );
}

/**
 * Partner coverage (MVP-ADMIN-SCR-07, Phase 2 proposal `staff-coverage-v1`): every district with
 * the server's count of active Audit Partners and open audits, and the server's verdict on whether
 * it is covered. The page applies no radius, capacity or eligibility rule of its own. Read-only:
 * dispatching, reassigning and suspending are not offered.
 */
export default function AdminCoverage(props: AdminCoverageProps) {
    const { t } = useTranslation();
    const format = useStatFormatter();

    return (
        <AdminFrame section="coverage" {...props}>
            {props.stats.length > 0 && (
                <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3 lg:gap-4">
                    {props.stats.map((stat) => (
                        <KpiTile
                            key={stat.key}
                            index={STAT_ACCENT[stat.key]}
                            label={t(`admin.coverage.stats.${stat.key}`)}
                            value={format(stat.value)}
                        />
                    ))}
                </div>
            )}
            <div className="mb-3.5">
                <CardTitle>{t('admin.coverage.title')}</CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.coverage.caption')}
                </p>
            </div>
            {props.districts.length === 0 && props.search !== '' && (
                <SearchEmpty section="coverage" term={props.search} />
            )}
            <TableCard
                label={t('admin.coverage.table')}
                minWidth="min-w-[720px]"
                empty={
                    props.districts.length === 0 && (
                        <EmptyState
                            title={t('admin.coverage.empty_title')}
                            body={t('admin.coverage.empty_body')}
                        />
                    )
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.coverage.col.district')}</HeadCell>
                    <HeadCell>{t('admin.coverage.col.capacity')}</HeadCell>
                    <HeadCell>{t('admin.coverage.col.partners')}</HeadCell>
                    <HeadCell>{t('admin.coverage.col.audits')}</HeadCell>
                    <HeadCell end>{t('admin.coverage.col.open')}</HeadCell>
                </div>
                {props.districts.map((row) => (
                    <Row key={row.id} row={row} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
