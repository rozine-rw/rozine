import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import {
    ApplicationStateChip,
    DECISION_TONE,
} from '@/components/admin/applications/application-status';
import { ReviewDrawer } from '@/components/admin/applications/review-drawer';
import { avatarColor, initialOf } from '@/components/admin/format';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    EmptyState,
    HeadCell,
    PillTabs,
    PolicyPeek,
    ROW_RULE,
    RatingPill,
    TABLE_HEAD,
    TableCard,
    useRelativeLabel,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { ApplicationRow, C3AdminApplicationsProps } from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1.3fr)_minmax(0,1.1fr)_minmax(92px,0.6fr)_minmax(0,0.9fr)_minmax(0,1.5fr)] gap-3 px-5';

/** Capacity-bar colour follows the rating band, as the design's queue draws it. */
const BAND_FILL = {
    strong: 'bg-[#17795a] dark:bg-[#3fcda0]',
    stable: 'bg-[#1832c8] dark:bg-[#5b74ff]',
    weak: 'bg-[#a55418]',
    distressed: 'bg-[#9c3a0a] dark:bg-[#ff8285]',
} as const;

const RESOLVED = new Set(['approved', 'rejected']);

function QueueRow({
    row,
    ago,
}: {
    row: ApplicationRow;
    ago: (iso: string) => string;
}) {
    const { t } = useTranslation();
    const actionable = !RESOLVED.has(row.state);

    return (
        <div
            role="row"
            className={cn(
                GRID,
                ROW_RULE,
                'relative items-center py-3.5 transition-colors duration-100 hover:bg-[#f5f8fd] dark:hover:bg-rz-surface-sunken',
            )}
        >
            <div role="cell" className="flex min-w-0 items-center gap-2.5">
                <div
                    className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] text-[13px] font-bold text-white"
                    style={{ background: avatarColor(row.id) }}
                >
                    {initialOf(row.business)}
                </div>
                <div className="min-w-0">
                    <Link
                        href={row.link}
                        className="block truncate text-[13px] font-bold text-rz-ink after:absolute after:inset-0"
                    >
                        {row.business}
                    </Link>
                    <div className="truncate text-[11px] text-rz-faint">
                        {row.note_title} · {row.id}
                    </div>
                </div>
            </div>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {row.sector}
            </span>
            <div role="cell">
                <div className="text-[12.5px] font-bold text-rz-ink">
                    {formatRwfShort(row.requested)}
                </div>
                <div className="text-[11px] text-rz-faint">
                    {t('admin.applications.terms', {
                        term: row.term_months,
                        rate: row.rate_pct,
                    })}
                </div>
            </div>
            <div role="cell" className="flex items-center gap-[7px]">
                <div
                    role="progressbar"
                    aria-label={t('admin.applications.capacity_used')}
                    aria-valuenow={row.capacity_used_pct}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    className="h-1.5 max-w-[70px] flex-1 overflow-hidden rounded-[3px] bg-[#e8edf5] dark:bg-rz-surface-muted"
                >
                    <div
                        className={cn(
                            'h-full rounded-[3px]',
                            row.rating === null
                                ? 'bg-[#69748a]'
                                : BAND_FILL[row.rating.band],
                        )}
                        style={{
                            width: `${Math.min(100, row.capacity_used_pct)}%`,
                        }}
                    />
                </div>
                <span className="text-[11px] text-rz-muted">
                    {row.capacity_used_pct}%
                </span>
            </div>
            <span role="cell">
                <RatingPill rating={row.rating} />
            </span>
            <span role="cell" className="text-[12px] text-rz-muted">
                {ago(row.submitted_at)}
            </span>
            <div
                role="cell"
                className="relative z-[1] flex flex-col items-end gap-1.5"
            >
                {actionable ? (
                    <>
                        <span
                            title={row.decision.reason}
                            className={cn(
                                'rounded-[7px] px-[9px] py-1 text-[10.5px] font-bold',
                                DECISION_TONE[row.decision.code].chip,
                            )}
                        >
                            {t(
                                `admin.applications.decision.${row.decision.code}`,
                            )}
                        </span>
                        <div className="flex gap-1.5">
                            <Link
                                href={row.link}
                                aria-label={t(
                                    'admin.applications.review_named',
                                    {
                                        name: row.business,
                                    },
                                )}
                                className="rounded-lg border border-rz-hairline bg-rz-surface px-[11px] py-1.5 text-[11.5px] font-semibold text-rz-slate"
                            >
                                {t('admin.applications.review')}
                            </Link>
                            {row.approve_link && (
                                <Link
                                    href={row.approve_link}
                                    aria-label={t(
                                        'admin.applications.approve_named',
                                        {
                                            name: row.business,
                                        },
                                    )}
                                    className="rounded-lg bg-[#1d9e75] px-[11px] py-1.5 text-[11.5px] font-semibold text-white"
                                >
                                    {t('admin.applications.approve')}
                                </Link>
                            )}
                        </div>
                    </>
                ) : (
                    <ApplicationStateChip state={row.state} />
                )}
            </div>
        </div>
    );
}

/**
 * The applications queue (MVP-ADMIN-SCR-02, design T348–417): the policy in force, one tab per
 * state with its server count, and each application with the engine's decision beside it.
 * Approve never commits from the row — it opens the review at its reasoned approval stage. An
 * approved application's review carries the minimal staff release (C3).
 */
export default function AdminApplications(props: C3AdminApplicationsProps) {
    const { t } = useTranslation();
    const ago = useRelativeLabel(props.server_time);

    return (
        <AdminFrame
            section="applications"
            {...props}
            overlay={
                props.review && (
                    <ReviewDrawer
                        key={props.review.id}
                        review={props.review}
                        viewer={props.viewer}
                        initialStage={props.stage}
                        lookup={props.links.operation}
                        preview={props.preview_outcome}
                    />
                )
            }
        >
            <PolicyPeek
                title={t('admin.applications.policy_title')}
                items={props.policy}
            />
            <PillTabs
                label={t('admin.applications.tabs')}
                tabs={props.tabs.map((tab) => ({
                    ...tab,
                    label: t(`admin.applications.tab.${tab.key}`),
                    active: tab.key === props.active_tab,
                }))}
            />
            {props.applications.length === 0 && props.search !== '' && (
                <SearchEmpty section="applications" term={props.search} />
            )}
            <TableCard
                label={t('admin.applications.table')}
                minWidth="min-w-[900px]"
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.applications.col.business')}</HeadCell>
                    <HeadCell>{t('admin.applications.col.sector')}</HeadCell>
                    <HeadCell>{t('admin.applications.col.requested')}</HeadCell>
                    <HeadCell>{t('admin.applications.col.capacity')}</HeadCell>
                    <HeadCell>{t('admin.applications.col.rating')}</HeadCell>
                    <HeadCell>{t('admin.applications.col.submitted')}</HeadCell>
                    <HeadCell end>
                        {t('admin.applications.col.decision')}
                    </HeadCell>
                </div>
                {props.applications.map((row) => (
                    <QueueRow key={row.id} row={row} ago={ago} />
                ))}
                {props.applications.length === 0 && (
                    <EmptyState title={t('admin.applications.empty')} />
                )}
            </TableCard>
        </AdminFrame>
    );
}
