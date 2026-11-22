import { Link, router } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { avatarColor, initialOf } from '@/components/admin/format';
import { UserDrawer } from '@/components/admin/parties/user-drawer';
import {
    UserKycChip,
    UserRoleChips,
} from '@/components/admin/parties/user-roles';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    EmptyState,
    HeadCell,
    KpiTile,
    MicroBadge,
    ROW_RULE,
    SegmentedChips,
    TABLE_HEAD,
    TableCard,
    useRelativeLabel,
    useStatFormatter,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AdminUsersProps, UserRow } from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,2fr)_minmax(0,1.9fr)_minmax(0,.9fr)_minmax(0,.8fr)_minmax(0,.8fr)] gap-3 px-5';

const HEADS = ['user', 'roles', 'kyc', 'signed_up', 'last_seen'] as const;

function Row({ row, serverTime }: { row: UserRow; serverTime: string }) {
    const { t, locale } = useTranslation();
    const ago = useRelativeLabel(serverTime);

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
                    {initialOf(row.name) || '?'}
                </div>
                <div className="min-w-0">
                    <div className="flex min-w-0 items-center gap-[7px]">
                        {row.restricted && (
                            <MicroBadge>
                                {t('admin.users.badge.restricted')}
                            </MicroBadge>
                        )}
                        {/* The whole row opens the account. */}
                        <Link
                            href={row.link}
                            preserveScroll
                            className="truncate text-[13.5px] font-bold text-rz-ink after:absolute after:inset-0"
                        >
                            {row.name.trim() === ''
                                ? t('admin.parties.name_pending')
                                : row.name}
                        </Link>
                    </div>
                    <div className="truncate text-[11.5px] text-rz-muted">
                        {row.email}
                        {!row.email_verified &&
                            ` · ${t('admin.users.email_unverified')}`}
                    </div>
                </div>
            </div>
            <div role="cell" className="min-w-0">
                <UserRoleChips row={row} />
            </div>
            <span role="cell">
                <UserKycChip kyc={row.kyc} />
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {formatDate(row.signed_up_at, locale)}
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {row.last_seen_at === null
                    ? t('admin.users.never')
                    : ago(row.last_seen_at)}
            </span>
        </div>
    );
}

/**
 * Users (Accounts): every sign-in account on the platform — Investors, Businesses, Audit Partners,
 * staff and people with no role yet — with each one's roles, KYC state, sign-up date and last
 * activity. The role chips, KYC filter, sort and search are server queries; a row opens the account
 * in the drawer, as the Investor directory opens a person and their KYC review.
 */
export default function AdminUsers(props: AdminUsersProps) {
    const { t } = useTranslation();
    const format = useStatFormatter();

    return (
        <AdminFrame
            section="users"
            {...props}
            searchQuery={{ param: 'q', clears: ['user'] }}
            overlay={
                props.user && (
                    <UserDrawer
                        key={props.user.id}
                        user={props.user}
                        serverTime={props.server_time}
                    />
                )
            }
        >
            <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                {props.stats.map((stat, index) => (
                    <KpiTile
                        key={stat.key}
                        index={index}
                        label={t(`admin.users.stats.${stat.key}`)}
                        value={format(stat.value)}
                    />
                ))}
            </div>
            <div className="mb-3.5 flex flex-wrap items-center justify-between gap-3">
                <SegmentedChips
                    label={t('admin.users.filter')}
                    chips={props.chips.map((chip) => ({
                        ...chip,
                        label: t(`admin.users.chip.${chip.key}`),
                    }))}
                />
                <div className="flex flex-wrap items-center gap-2.5">
                    {props.filters.map((filter) => (
                        <select
                            key={filter.key}
                            aria-label={t(`admin.users.select.${filter.key}`)}
                            value={filter.value}
                            onChange={(event) =>
                                router.reload({
                                    data: {
                                        [filter.key]: event.target.value,
                                        user: undefined,
                                    },
                                })
                            }
                            className="cursor-pointer rounded-[10px] border border-rz-hairline bg-rz-surface px-3 py-[9px] text-[12.5px] font-semibold text-rz-ink outline-none"
                        >
                            {filter.key === 'kyc'
                                ? filter.options.map((option) => (
                                      <option key={option} value={option}>
                                          {t(
                                              `admin.users.kyc_filter.${option}`,
                                          )}
                                      </option>
                                  ))
                                : filter.options.map((option) => (
                                      <option key={option} value={option}>
                                          {t(`admin.users.sort.${option}`)}
                                      </option>
                                  ))}
                        </select>
                    ))}
                </div>
            </div>
            <p className="mb-2.5 text-[12px] font-semibold text-rz-muted">
                {props.shown < props.total
                    ? t('admin.users.showing', {
                          shown: props.shown,
                          total: props.total,
                      })
                    : t('admin.users.count', { count: props.total })}
            </p>
            {props.rows.length === 0 && props.search !== '' && (
                <SearchEmpty section="users" term={props.search} />
            )}
            <TableCard
                label={t('admin.users.table')}
                minWidth="min-w-[880px]"
                empty={
                    props.rows.length === 0 &&
                    props.search === '' && (
                        <EmptyState title={t('admin.users.empty')} />
                    )
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    {HEADS.map((head) => (
                        <HeadCell key={head}>
                            {t(`admin.users.col.${head}`)}
                        </HeadCell>
                    ))}
                </div>
                {props.rows.map((row) => (
                    <Row
                        key={row.id}
                        row={row}
                        serverTime={props.server_time}
                    />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
