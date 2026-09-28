import { AdminFrame } from '@/components/admin/admin-frame';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    CardTitle,
    Chip,
    EmptyState,
    HeadCell,
    ROW_RULE,
    TABLE_HEAD,
    TONE_TEXT,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDateTime } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AdminReportsProps,
    ReportPack,
    ReportPackKind,
    Tone,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_minmax(0,1.1fr)_minmax(0,1.2fr)_minmax(150px,.9fr)] gap-3 px-5';

const KIND_TONE: Record<ReportPackKind, Tone> = {
    regulator: 'purple',
    board: 'blue',
    export: 'grey',
};

const STATUS_TONE: Record<ReportPack['status'], Tone> = {
    complete: 'green',
    incomplete: 'grey',
    moving: 'amber',
};

function Row({ pack }: { pack: ReportPack }) {
    const { t, locale } = useTranslation();

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3.5')}>
            <span
                role="cell"
                className="text-[12.5px] font-semibold text-rz-ink"
            >
                <Chip tone={KIND_TONE[pack.kind]} className="mb-1">
                    {t(`admin.reports.kind.${pack.kind}`)}
                </Chip>
                <span className="block">{pack.label}</span>
            </span>
            <span role="cell" className="text-[12px] text-rz-body">
                {t('admin.reports.period', {
                    start: formatDate(pack.period.start, locale),
                    end: formatDate(pack.period.end, locale),
                })}
            </span>
            <span role="cell">
                <Chip tone={STATUS_TONE[pack.status]}>
                    {t(`admin.reports.status.${pack.status}`)}
                </Chip>
            </span>
            <span role="cell" className="text-[12px] text-rz-body">
                {pack.as_of === null ? '—' : formatDateTime(pack.as_of, locale)}
                {pack.status === 'moving' && (
                    <span
                        className={cn(
                            'mt-0.5 block text-[11px] font-semibold',
                            TONE_TEXT.amber,
                        )}
                    >
                        {t('admin.reports.snapshot')}
                    </span>
                )}
            </span>
            <div role="cell" className="flex justify-end">
                {pack.link === null ? (
                    <span className="text-right text-[11.5px] font-medium text-rz-muted">
                        {t('admin.reports.not_ready')}
                    </span>
                ) : (
                    <a
                        href={pack.link.url}
                        aria-label={t('admin.reports.download_named', {
                            label: pack.label,
                        })}
                        className="shrink-0 rounded-lg border border-rz-hairline bg-rz-surface px-3 py-1.5 text-[11.5px] font-semibold text-rz-slate"
                    >
                        {pack.status === 'moving'
                            ? t('admin.reports.download_snapshot')
                            : t('admin.reports.download')}
                    </a>
                )}
            </div>
            {pack.status === 'moving' && (
                <p
                    role="note"
                    className="col-span-full rounded-[10px] border border-[#f6e6cc] bg-[rgba(210,120,45,.06)] px-3 py-2 text-[11.5px] font-medium text-rz-body dark:border-rz-border"
                >
                    <span className={cn('font-bold', TONE_TEXT.amber)}>
                        {t('admin.reports.pending')}
                    </span>{' '}
                    {pack.pending}
                </p>
            )}
        </div>
    );
}

/**
 * Reports (MVP-ADMIN-SCR-10, Phase 2 proposal `staff-reports-v1`): the regulator pack, the board
 * pack and the exports, each for its period with how final its figures are. A period that has not
 * closed has nothing to download; figures still moving are offered only as a snapshot with its
 * as-of time and the server's note on what is pending. The page defines no pack's contents and
 * generates nothing: a download is the server's file.
 */
export default function AdminReports(props: AdminReportsProps) {
    const { t } = useTranslation();

    return (
        <AdminFrame section="reports" {...props}>
            <div className="mb-3.5">
                <CardTitle>{t('admin.reports.title')}</CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.reports.caption')}
                </p>
            </div>
            {props.packs.length === 0 && props.search !== '' && (
                <SearchEmpty section="reports" term={props.search} />
            )}
            <TableCard
                label={t('admin.reports.table')}
                minWidth="min-w-[860px]"
                empty={
                    props.packs.length === 0 && (
                        <EmptyState
                            title={t('admin.reports.empty_title')}
                            body={t('admin.reports.empty_body')}
                        />
                    )
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.reports.col.pack')}</HeadCell>
                    <HeadCell>{t('admin.reports.col.period')}</HeadCell>
                    <HeadCell>{t('admin.reports.col.status')}</HeadCell>
                    <HeadCell>{t('admin.reports.col.as_of')}</HeadCell>
                    <HeadCell end>{t('admin.reports.col.download')}</HeadCell>
                </div>
                {props.packs.map((pack) => (
                    <Row key={pack.id} pack={pack} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
