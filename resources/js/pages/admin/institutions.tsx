import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { avatarColor, initialOf } from '@/components/admin/format';
import {
    INSTITUTION_TONE,
    InstitutionDrawer,
    useInstitutionType,
} from '@/components/admin/parties/institution-drawer';
import { RoleApplicationDrawer } from '@/components/admin/parties/role-application-drawer';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    Chip,
    EmptyState,
    HeadCell,
    KpiTile,
    ROW_RULE,
    SegmentedChips,
    TABLE_HEAD,
    TableCard,
    useStatFormatter,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AdminInstitutionsProps, InstitutionRow } from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,2.1fr)_minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,.9fr)_minmax(0,.9fr)_minmax(0,.8fr)] gap-3 px-5';

const HEADS = [
    'institution',
    'representative',
    'status',
    'commitment',
    'invested',
    'wallet',
] as const;

function Row({ row }: { row: InstitutionRow }) {
    const { t } = useTranslation();
    const typeLabel = useInstitutionType();
    const name =
        row.name.trim() === ''
            ? t('admin.institutions.name_pending')
            : row.name;

    return (
        <div
            role="row"
            className={cn(
                GRID,
                ROW_RULE,
                'relative items-center py-[13px] hover:bg-[#e6edf9] dark:hover:bg-rz-surface-sunken',
            )}
        >
            <div role="cell" className="flex min-w-0 items-center gap-[11px]">
                <div
                    className="flex size-9 shrink-0 items-center justify-center rounded-[10px] text-[14px] font-bold text-white"
                    style={{ background: avatarColor(row.id) }}
                >
                    {initialOf(name) || '?'}
                </div>
                <div className="min-w-0">
                    {/* The whole row opens the institution. */}
                    <Link
                        href={row.link}
                        preserveScroll
                        className="block truncate text-[13.5px] font-bold text-rz-ink after:absolute after:inset-0"
                    >
                        {name}
                    </Link>
                    <div className="truncate text-[11.5px] text-rz-muted">
                        {[
                            row.type === '' ? null : typeLabel(row.type),
                            row.rdb_code === ''
                                ? null
                                : t('admin.institutions.rdb', {
                                      code: row.rdb_code,
                                  }),
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </div>
                </div>
            </div>
            <div role="cell" className="min-w-0">
                <div className="truncate text-[12.5px] font-semibold text-rz-ink">
                    {row.representative || '—'}
                </div>
                <div className="truncate text-[11.5px] text-rz-muted">
                    {row.email}
                </div>
            </div>
            <span role="cell">
                <Chip tone={INSTITUTION_TONE[row.status]}>
                    {t(`admin.institutions.status.${row.status}`)}
                </Chip>
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {formatRwfShort(row.monthly_commitment)}
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {formatRwfShort(row.invested)}
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {formatRwfShort(row.wallet)}
            </span>
        </div>
    );
}

/**
 * Institutions (Accounts; Erastus, 27 Nov 2026): every pension fund, VC and other institution that
 * applied to invest through Rozine, with its status, monthly commitment, representative and money. The
 * status chips and search are server queries; a row opens the institution's 360, and the 360 opens the
 * same review drawer the Businesses page uses, which closes back to the 360.
 */
export default function AdminInstitutions(props: AdminInstitutionsProps) {
    const { t } = useTranslation();
    const format = useStatFormatter();

    return (
        <AdminFrame
            section="institutions"
            {...props}
            searchQuery={{ param: 'q', clears: ['institution', 'application'] }}
            overlay={
                props.application ? (
                    <RoleApplicationDrawer
                        key={props.application.id}
                        application={props.application}
                        close={props.applications_close}
                    />
                ) : (
                    props.institution && (
                        <InstitutionDrawer
                            key={props.institution.id}
                            institution={props.institution}
                        />
                    )
                )
            }
        >
            <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                {props.stats.map((stat, index) => (
                    <KpiTile
                        key={stat.key}
                        index={index}
                        label={t(`admin.institutions.stats.${stat.key}`)}
                        value={format(stat.value)}
                        sub={
                            stat.key === 'committed_monthly'
                                ? t('admin.institutions.stats.approved_only')
                                : undefined
                        }
                    />
                ))}
            </div>
            <div className="mb-3.5 flex flex-wrap items-center justify-between gap-3">
                <SegmentedChips
                    label={t('admin.institutions.filter')}
                    chips={props.chips.map((chip) => ({
                        ...chip,
                        label: t(`admin.institutions.chip.${chip.key}`),
                    }))}
                />
            </div>
            <p className="mb-2.5 text-[12px] font-semibold text-rz-muted">
                {props.shown < props.total
                    ? t('admin.institutions.showing', {
                          shown: props.shown,
                          total: props.total,
                      })
                    : t('admin.institutions.count', { count: props.total })}
            </p>
            {props.rows.length === 0 && props.search !== '' && (
                <SearchEmpty section="institutions" term={props.search} />
            )}
            <TableCard
                label={t('admin.institutions.table')}
                minWidth="min-w-[920px]"
                empty={
                    props.rows.length === 0 &&
                    props.search === '' && (
                        <EmptyState title={t('admin.institutions.empty')} />
                    )
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    {HEADS.map((head) => (
                        <HeadCell key={head}>
                            {t(`admin.institutions.col.${head}`)}
                        </HeadCell>
                    ))}
                </div>
                {props.rows.map((row) => (
                    <Row key={row.id} row={row} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
