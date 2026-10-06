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
    SegmentedChips,
    ShowMoreLink,
    TABLE_HEAD,
    TableCard,
    useStatFormatter,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AdminBookProps, BookNoteRow, Tone } from '@/types/admin';
import type { ServicingState } from '@/types/settlement';

const GRID =
    'grid grid-cols-[minmax(0,.9fr)_minmax(0,1.9fr)_minmax(0,1.1fr)_minmax(0,1.3fr)_minmax(0,.6fr)_minmax(180px,1.3fr)] gap-3 px-5';

/** The servicing state as a chip: nothing unpaid is green, due is blue, overdue is red. */
const SERVICING_TONE: Record<ServicingState, Tone> = {
    current: 'green',
    due_today: 'blue',
    overdue: 'red',
    repaid: 'grey',
    defaulted: 'red',
};

function Row({ row }: { row: BookNoteRow }) {
    const { t, locale } = useTranslation();

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3.5')}>
            <span role="cell" className="font-mono text-[12px] text-rz-body">
                {row.note_id}
            </span>
            <span
                role="cell"
                className="text-[12.5px] font-semibold text-rz-ink"
            >
                {row.business} · {row.note_title}
            </span>
            <span role="cell" className="text-[12.5px] font-bold text-rz-ink">
                {formatRwfShort(row.principal_outstanding)}
            </span>
            <span role="cell" className="text-[12px] text-rz-body">
                {row.next_due === null ? (
                    t('admin.book.nothing_due')
                ) : (
                    <>
                        {formatDate(row.next_due.on, locale)}
                        <span className="block text-[11px] text-rz-muted">
                            {formatRwfShort(row.next_due.amount)}
                        </span>
                    </>
                )}
            </span>
            <span role="cell" className="text-[12px] text-rz-body">
                {row.dpd === null ? '—' : row.dpd}
            </span>
            <div role="cell" className="flex items-center justify-end gap-1.5">
                <Chip tone={SERVICING_TONE[row.servicing]}>
                    {t(`admin.repayments.servicing.status.${row.servicing}`)}
                </Chip>
                <Link
                    href={row.link}
                    aria-label={t('admin.book.open_named', {
                        note: row.note_id,
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
 * Book (MVP-ADMIN-SCR-05, Phase 2 proposal `staff-book-v1`): every live note and its health, as
 * the core records its servicing state and DPD. The figures, counts and filters are the server's;
 * the page never totals, ranks or bands a note itself. Read-only: there is no approved book-level
 * exposure or concentration policy yet, so no breach is shown and no command exists here.
 */
export default function AdminBook(props: AdminBookProps) {
    const { t } = useTranslation();
    const format = useStatFormatter();
    const filtered = props.chips.some(
        (chip) => chip.active && chip.key !== 'all',
    );

    return (
        <AdminFrame section="book" {...props}>
            {props.stats.length > 0 && (
                <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                    {props.stats.map((stat, index) => (
                        <KpiTile
                            key={stat.key}
                            index={index}
                            label={t(`admin.book.stats.${stat.key}`)}
                            value={format(stat.value)}
                        />
                    ))}
                </div>
            )}
            <div className="mb-3.5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <CardTitle>{t('admin.book.title')}</CardTitle>
                    <p className="mt-[3px] text-[12px] text-rz-muted">
                        {t('admin.book.caption')}
                    </p>
                </div>
                {props.chips.length > 0 && (
                    <SegmentedChips
                        label={t('admin.book.filter')}
                        chips={props.chips.map((chip) => ({
                            ...chip,
                            label:
                                chip.key === 'all'
                                    ? t('admin.book.chip.all')
                                    : t(
                                          `admin.repayments.servicing.status.${chip.key}`,
                                      ),
                        }))}
                    />
                )}
            </div>
            {props.notes.length === 0 && props.search !== '' && (
                <SearchEmpty section="book" term={props.search} />
            )}
            <TableCard
                label={t('admin.book.table')}
                minWidth="min-w-[900px]"
                footer={
                    <>
                        {/* Outside the table's scroller, so it stays centred on a phone. */}
                        {props.notes.length === 0 &&
                            (filtered ? (
                                <EmptyState
                                    title={t('admin.book.filtered_title')}
                                    body={t('admin.book.filtered_body')}
                                />
                            ) : (
                                <EmptyState
                                    title={t('admin.book.empty_title')}
                                    body={t('admin.book.empty_body')}
                                />
                            ))}
                        {props.pagination.next !== null && (
                            <ShowMoreLink link={props.pagination.next}>
                                {t('admin.book.more')}
                            </ShowMoreLink>
                        )}
                    </>
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.book.col.note_id')}</HeadCell>
                    <HeadCell>{t('admin.book.col.note')}</HeadCell>
                    <HeadCell>{t('admin.book.col.principal')}</HeadCell>
                    <HeadCell>{t('admin.book.col.next_due')}</HeadCell>
                    <HeadCell>{t('admin.book.col.dpd')}</HeadCell>
                    <HeadCell end>{t('admin.book.col.health')}</HeadCell>
                </div>
                {props.notes.map((row) => (
                    <Row key={row.id} row={row} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
