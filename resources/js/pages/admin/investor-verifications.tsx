import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import {
    avatarColor,
    formatCount,
    formatTimestamp,
    initialOf,
} from '@/components/admin/format';
import { ReasonStage } from '@/components/admin/reason-stage';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    Chip,
    EmptyState,
    EXPLAIN,
    HeadCell,
    LABEL,
    PillTabs,
    ROW_RULE,
    ShowMoreLink,
    TABLE_HEAD,
    TableCard,
    useRelativeLabel,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    AdminInvestorVerificationsProps,
    InvestorVerificationReview,
    KycCaseStatus,
    StaffViewer,
    Tone,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,2.2fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,0.9fr)] gap-3 px-5';

const STATUS_TONE: Record<KycCaseStatus, Tone> = {
    draft: 'amber',
    submitted: 'blue',
    approved: 'green',
    rejected: 'red',
};

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
                                <Chip tone={STATUS_TONE[entry.status]}>
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

function Fact({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className={cn('text-[11px] font-semibold uppercase', LABEL)}>
                {label}
            </dt>
            <dd className="mt-0.5 text-[13px] font-semibold text-rz-ink">
                {value}
            </dd>
        </div>
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
    const { errors } = usePage<{ errors: Record<string, string> }>().props;
    const [stage, setStage] = useState<{
        kind: 'approve' | 'reject';
        action: RouteAction;
    } | null>(null);
    const [requestId] = useState(() => crypto.randomUUID());
    const { approve, reject } = review.actions;

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
                <Chip tone={STATUS_TONE[review.status]}>
                    {t(`admin.kyc.status.${review.status}`)}
                </Chip>
                <DrawerClose close={review.links.close} />
            </div>
            <div className="flex-1 overflow-y-auto px-[22px] py-[18px]">
                <section aria-label={t('admin.kyc.facts')}>
                    <dl className="grid grid-cols-2 gap-x-4 gap-y-3">
                        <Fact
                            label={t('admin.kyc.fact.date_of_birth')}
                            value={review.date_of_birth}
                        />
                        <Fact
                            label={t('admin.kyc.fact.id_type')}
                            value={t(`admin.kyc.id_type.${review.id_type}`)}
                        />
                        <Fact
                            label={t('admin.kyc.fact.id_number')}
                            value={review.id_number}
                        />
                        {review.submitted_at && (
                            <Fact
                                label={t('admin.kyc.fact.submitted_at')}
                                value={formatTimestamp(review.submitted_at)}
                            />
                        )}
                    </dl>
                </section>
                <section aria-label={t('admin.kyc.documents')} className="mt-5">
                    <h3
                        className={cn(
                            'text-[12px] font-bold tracking-[.05em] uppercase',
                            LABEL,
                        )}
                    >
                        {t('admin.kyc.documents')}
                    </h3>
                    <ul className="mt-2.5 overflow-hidden rounded-[13px] border border-rz-hairline bg-rz-surface">
                        {review.documents.map((document) => (
                            <li
                                key={document.id}
                                className="flex items-center gap-3 border-b border-[#eef2f8] px-[15px] py-3 last:border-b-0 dark:border-rz-divider"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="text-[13px] font-semibold text-rz-ink">
                                        {t(`admin.kyc.slot.${document.slot}`)}
                                        {!document.current && (
                                            <span className="ml-2 text-[11px] font-semibold text-rz-faint">
                                                {t(
                                                    'admin.kyc.document.replaced',
                                                )}
                                            </span>
                                        )}
                                    </div>
                                    <div className="truncate text-[11.5px] text-rz-muted">
                                        {t('admin.kyc.document.meta', {
                                            name: document.filename,
                                            size: formatCount(
                                                Math.ceil(
                                                    document.size_bytes / 1024,
                                                ),
                                            ),
                                        })}
                                    </div>
                                </div>
                                <a
                                    href={document.link.url}
                                    aria-label={t('admin.kyc.document.open', {
                                        name: document.filename,
                                    })}
                                    className="shrink-0 rounded-[9px] border border-rz-hairline px-3 py-1.5 text-[12px] font-semibold text-rz-accent-app-text"
                                >
                                    {t('admin.kyc.document.download')}
                                </a>
                            </li>
                        ))}
                    </ul>
                </section>
                {review.decision && (
                    <section
                        aria-label={t('admin.kyc.decision')}
                        className="mt-5 rounded-[13px] border border-rz-hairline bg-rz-surface px-[15px] py-3"
                    >
                        <div className="text-[13px] font-semibold text-rz-ink">
                            {t(`admin.kyc.status.${review.decision.outcome}`)}
                            {' · '}
                            <time
                                dateTime={review.decision.decided_at}
                                className="font-normal text-rz-muted tabular-nums"
                            >
                                {formatTimestamp(review.decision.decided_at)}
                            </time>
                        </div>
                        <p className="mt-1 text-[12.5px] text-rz-body">
                            {review.decision.reason}
                        </p>
                    </section>
                )}
                <section aria-label={t('admin.kyc.history')} className="mt-5">
                    <h3
                        className={cn(
                            'text-[12px] font-bold tracking-[.05em] uppercase',
                            LABEL,
                        )}
                    >
                        {t('admin.kyc.history')}
                    </h3>
                    <ol className="mt-2.5 overflow-hidden rounded-[13px] border border-rz-hairline bg-rz-surface">
                        {review.history.map((entry) => (
                            <li
                                key={entry.revision}
                                className="border-b border-[#eef2f8] px-[15px] py-2.5 last:border-b-0 dark:border-rz-divider"
                            >
                                <div className="flex items-center gap-2 text-[12.5px]">
                                    <span className="flex-1 font-semibold text-rz-ink">
                                        {t(
                                            `admin.kyc.command.${entry.command}`,
                                        )}
                                    </span>
                                    <time
                                        dateTime={entry.at}
                                        className="text-[11px] text-rz-muted tabular-nums"
                                    >
                                        {formatTimestamp(entry.at)}
                                    </time>
                                </div>
                                {entry.reason && (
                                    <p className="mt-0.5 text-[12px] text-rz-body">
                                        {entry.reason}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ol>
                </section>
                {errors.form && (
                    <p
                        role="alert"
                        className="mt-4 rounded-[11px] bg-[rgba(229,72,77,.1)] px-3.5 py-2.5 text-[12.5px] font-semibold text-[#c2292e]"
                    >
                        {errors.form}
                    </p>
                )}
                {stage ? (
                    <ReasonStage
                        title={t(`admin.kyc.stage.${stage.kind}.title`)}
                        body={t(`admin.kyc.stage.${stage.kind}.body`)}
                        cta={t(`admin.kyc.stage.${stage.kind}.cta`)}
                        placeholder={t(
                            `admin.kyc.stage.${stage.kind}.placeholder`,
                        )}
                        tone={stage.kind === 'approve' ? 'green' : 'red'}
                        action={stage.action}
                        viewer={viewer}
                        onCancel={() => setStage(null)}
                        payload={{
                            request_id: requestId,
                            expected_revision: review.revision,
                        }}
                    />
                ) : (
                    review.status === 'submitted' && (
                        <div className="mt-5 flex gap-2.5">
                            {reject && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        setStage({
                                            kind: 'reject',
                                            action: reject,
                                        })
                                    }
                                    className="h-[42px] flex-1 rounded-[11px] border border-[#e5484d] text-[13.5px] font-bold text-[#c2292e]"
                                >
                                    {t('admin.kyc.reject')}
                                </button>
                            )}
                            {approve && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        setStage({
                                            kind: 'approve',
                                            action: approve,
                                        })
                                    }
                                    className="h-[42px] flex-1 rounded-[11px] bg-[#1d9e75] text-[13.5px] font-bold text-white"
                                >
                                    {t('admin.kyc.approve')}
                                </button>
                            )}
                        </div>
                    )
                )}
            </div>
        </Drawer>
    );
}
