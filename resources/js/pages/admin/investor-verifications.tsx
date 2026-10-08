import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import { avatarColor, initialOf } from '@/components/admin/format';
import { KYC_STATUS_TONE, KycReview } from '@/components/admin/kyc-review';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    Chip,
    EmptyState,
    EXPLAIN,
    HeadCell,
    PillTabs,
    ROW_RULE,
    ShowMoreLink,
    TABLE_HEAD,
    TableCard,
    useRelativeLabel,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type {
    AdminInvestorVerificationsProps,
    InvestorVerificationReview,
    StaffViewer,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,2.2fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,0.9fr)] gap-3 px-5';

/** A new search starts from the first page with no case open. */
const SEARCH_QUERY = { param: 'search', clears: ['before', 'verification'] };

/**
 * Compliance's Investor identity queue (`investors.verify`): people's own submissions, oldest
 * first, each opened in a drawer with its answers, private documents and history. Approve and
 * reject both go through the reason stage; the server rechecks the revision and the permission.
 */
export default function AdminInvestorVerifications(
    props: AdminInvestorVerificationsProps,
) {
    const { t } = useTranslation();
    const ago = useRelativeLabel(props.server_time);

    return (
        <AdminFrame
            section="investors"
            {...props}
            searchQuery={SEARCH_QUERY}
            overlay={
                props.review && (
                    <ReviewDrawer
                        key={`${props.review.id}@${props.review.revision}`}
                        review={props.review}
                        viewer={props.viewer}
                    />
                )
            }
        >
            <p className={cn('mb-4 max-w-[720px] text-[13px]', EXPLAIN)}>
                {t('admin.kyc.intro')}
            </p>
            <PillTabs
                label={t('admin.kyc.tabs')}
                tabs={props.tabs.map((tab) => ({
                    ...tab,
                    label: t(`admin.kyc.tab.${tab.key}`),
                    active: tab.key === props.active_tab,
                }))}
            />
            {props.entries.length === 0 && props.search !== '' ? (
                <SearchEmpty section="investors" term={props.search} />
            ) : (
                <TableCard
                    label={t('admin.kyc.table')}
                    minWidth="min-w-[640px]"
                    empty={
                        props.entries.length === 0 && (
                            <EmptyState
                                title={t(`admin.kyc.empty.${props.active_tab}`)}
                            />
                        )
                    }
                    footer={
                        props.pagination.next && (
                            <ShowMoreLink link={props.pagination.next}>
                                {t('admin.kyc.more')}
                            </ShowMoreLink>
                        )
                    }
                >
                    <div role="row" className={cn(GRID, TABLE_HEAD)}>
                        <HeadCell>{t('admin.kyc.col.person')}</HeadCell>
                        <HeadCell>{t('admin.kyc.col.document')}</HeadCell>
                        <HeadCell>{t('admin.kyc.col.submitted')}</HeadCell>
                        <HeadCell>{t('admin.kyc.col.status')}</HeadCell>
                    </div>
                    {props.entries.map((entry) => (
                        <div
                            key={entry.id}
                            role="row"
                            aria-current={entry.selected || undefined}
                            className={cn(
                                GRID,
                                ROW_RULE,
                                'relative items-center py-3.5 hover:bg-[#f5f8fd] dark:hover:bg-rz-surface-sunken',
                                entry.selected &&
                                    'bg-[#f5f8fd] dark:bg-rz-surface-sunken',
                            )}
                        >
                            <div
                                role="cell"
                                className="flex min-w-0 items-center gap-2.5"
                            >
                                <div
                                    className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] text-[13px] font-bold text-white"
                                    style={{
                                        background: avatarColor(entry.id),
                                    }}
                                >
                                    {initialOf(entry.name)}
                                </div>
                                <div className="min-w-0">
                                    <Link
                                        href={entry.link}
                                        preserveScroll
                                        className="block truncate text-[13px] font-bold text-rz-ink after:absolute after:inset-0"
                                    >
                                        {entry.name}
                                    </Link>
                                    <div className="truncate text-[11px] text-rz-faint">
                                        {entry.email}
                                    </div>
                                </div>
                            </div>
                            <div
                                role="cell"
                                className="text-[12.5px] text-rz-body"
                            >
                                {t(`admin.kyc.id_type.${entry.id_type}`)}
                            </div>
                            <div
                                role="cell"
                                className="text-[12px] text-rz-muted tabular-nums"
                            >
                                <time dateTime={entry.submitted_at}>
                                    {ago(entry.submitted_at)}
                                </time>
                            </div>
                            <div role="cell">
                                <Chip tone={KYC_STATUS_TONE[entry.status]}>
                                    {t(`admin.kyc.status.${entry.status}`)}
                                </Chip>
                            </div>
                        </div>
                    ))}
                </TableCard>
            )}
        </AdminFrame>
    );
}

function ReviewDrawer({
    review,
    viewer,
}: {
    review: InvestorVerificationReview;
    viewer: StaffViewer;
}) {
    const { t } = useTranslation();

    return (
        <Drawer label={t('admin.kyc.drawer')} close={review.links.close}>
            <div className="flex items-start gap-3 border-b border-rz-hairline px-[22px] py-[18px]">
                <div className="min-w-0 flex-1">
                    <h2 className="truncate text-[17px] font-bold text-rz-ink">
                        {review.account.name}
                    </h2>
                    <p className="truncate text-[12.5px] text-rz-muted">
                        {review.account.email}
                    </p>
                </div>
                <Chip tone={KYC_STATUS_TONE[review.status]}>
                    {t(`admin.kyc.status.${review.status}`)}
                </Chip>
                <DrawerClose close={review.links.close} />
            </div>
            <div className="flex-1 overflow-y-auto px-[22px] py-[18px]">
                <KycReview review={review} viewer={viewer} />
            </div>
        </Drawer>
    );
}
