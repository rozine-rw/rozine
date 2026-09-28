import { Link } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    CardTitle,
    Chip,
    EmptyState,
    HeadCell,
    ROW_RULE,
    SegmentedChips,
    ShowMoreLink,
    TABLE_HEAD,
    TONE_TEXT,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AdminExceptionsProps,
    ExceptionItem,
    ExceptionKind,
    Tone,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.1fr)_minmax(0,.8fr)_minmax(0,2.4fr)_minmax(0,1fr)_minmax(0,1.3fr)_minmax(90px,.7fr)] gap-3 px-5';

const KIND_TONE: Record<ExceptionKind, Tone> = {
    arrears: 'red',
    halt: 'purple',
    variance: 'amber',
};

function Row({ item }: { item: ExceptionItem }) {
    const { t } = useTranslation();

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3.5')}>
            <span role="cell" className="font-mono text-[12px] text-rz-body">
                {item.reference}
            </span>
            <span role="cell">
                <Chip tone={KIND_TONE[item.kind]}>
                    {t(`admin.exceptions.kind.${item.kind}`)}
                </Chip>
            </span>
            <span
                role="cell"
                className="text-[12.5px] font-semibold text-rz-ink"
            >
                {item.note_title === null
                    ? item.business
                    : `${item.business} · ${item.note_title}`}
                <span className="mt-0.5 block text-[11px] font-medium text-rz-muted">
                    {item.description}
                    {item.dpd !== null &&
                        ` · ${t('admin.exceptions.dpd', { count: item.dpd })}`}
                </span>
            </span>
            <span
                role="cell"
                className={cn(
                    'text-[12.5px] font-bold',
                    item.amount === null ? 'text-rz-body' : TONE_TEXT.red,
                )}
            >
                {item.amount === null ? '—' : formatRwf(item.amount)}
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
                    aria-label={t('admin.exceptions.open_named', {
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
 * Exceptions (MVP-ADMIN-SCR-06, Phase 2 proposal `staff-exceptions-v1`): every open arrears, halt
 * and variance, aged and owned like a reconciliation break, each linking to its record. The counts
 * and filters are the server's. Read-only: assigning, escalating, halting and remedies wait on the
 * unsigned D-25/28/54 escalation policy, so no command and no escalation window is shown here.
 */
export default function AdminExceptions(props: AdminExceptionsProps) {
    const { t } = useTranslation();
    const filtered = props.chips.some(
        (chip) => chip.active && chip.key !== 'all',
    );

    return (
        <AdminFrame section="exceptions" {...props}>
            <div className="mb-3.5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div className="flex flex-wrap items-center gap-2.5">
                        <CardTitle>{t('admin.exceptions.title')}</CardTitle>
                        {(['open', 'unassigned'] as const).map(
                            (key) =>
                                props.counts[key] > 0 && (
                                    <span
                                        key={key}
                                        className={cn(
                                            'rounded-lg border px-2.5 py-1 text-[11.5px] font-semibold',
                                            key === 'unassigned'
                                                ? 'border-[#fdd9da] bg-[rgba(229,72,77,.08)] text-[#c4373c] dark:border-rz-border dark:text-[#ff6b6f]'
                                                : 'border-rz-hairline bg-rz-surface text-rz-slate',
                                        )}
                                    >
                                        {t(`admin.exceptions.count.${key}`, {
                                            count: props.counts[key],
                                        })}
                                    </span>
                                ),
                        )}
                    </div>
                    <p className="mt-[3px] text-[12px] text-rz-muted">
                        {t('admin.exceptions.caption')}
                    </p>
                </div>
                {props.chips.length > 0 && (
                    <SegmentedChips
                        label={t('admin.exceptions.filter')}
                        chips={props.chips.map((chip) => ({
                            ...chip,
                            label:
                                chip.key === 'all'
                                    ? t('admin.exceptions.chip.all')
                                    : t(`admin.exceptions.kind.${chip.key}`),
                        }))}
                    />
                )}
            </div>
            {props.exceptions.length === 0 && props.search !== '' && (
                <SearchEmpty section="exceptions" term={props.search} />
            )}
            <TableCard
                label={t('admin.exceptions.table')}
                minWidth="min-w-[920px]"
                footer={
                    <>
                        {/* Outside the table's scroller, so it stays centred on a phone. */}
                        {props.exceptions.length === 0 &&
                            (filtered ? (
                                <EmptyState
                                    title={t('admin.exceptions.filtered_title')}
                                    body={t('admin.exceptions.filtered_body')}
                                />
                            ) : (
                                <EmptyState
                                    title={t('admin.exceptions.empty_title')}
                                    body={t('admin.exceptions.empty_body')}
                                />
                            ))}
                        {props.pagination.next !== null && (
                            <ShowMoreLink link={props.pagination.next}>
                                {t('admin.exceptions.more')}
                            </ShowMoreLink>
                        )}
                    </>
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.exceptions.col.reference')}</HeadCell>
                    <HeadCell>{t('admin.exceptions.col.kind')}</HeadCell>
                    <HeadCell>{t('admin.exceptions.col.subject')}</HeadCell>
                    <HeadCell>{t('admin.exceptions.col.amount')}</HeadCell>
                    <HeadCell>{t('admin.exceptions.col.owner')}</HeadCell>
                    <HeadCell end>{t('admin.exceptions.col.open')}</HeadCell>
                </div>
                {props.exceptions.map((item) => (
                    <Row key={item.id} item={item} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
