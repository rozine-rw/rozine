import { AdminFrame } from '@/components/admin/admin-frame';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    EmptyState,
    HeadCell,
    KpiTile,
    PillTabs,
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
    AdminNotesProps,
    NoteRegisterRow,
    NoteState,
    NotesStatKey,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.6fr)_110px_80px_90px_minmax(0,1fr)_minmax(0,1.3fr)] gap-3.5 px-5';

/** The design's rail colours: ink, blue, green, ink. */
const STAT_ACCENT: Record<NotesStatKey, number> = {
    total: 6,
    live: 0,
    repaying: 1,
    raised: 6,
};

/** The state chip's tint: blue while raising, ink once full, green repaying, red failed. */
const STATE_TONE: Record<NoteState, string> = {
    live: 'bg-[rgba(30,58,255,.10)] text-[#1e3aff] dark:text-[#99a3ff]',
    funded: 'bg-[rgba(20,40,168,.10)] text-[#1428a8] dark:text-[#99a3ff]',
    repaying: 'bg-[rgba(29,158,117,.12)] text-[#17795a] dark:text-[#4cc497]',
    failed: 'bg-[rgba(229,72,77,.10)] text-rz-danger-text',
};

function Row({ note }: { note: NoteRegisterRow }) {
    const { t } = useTranslation();
    const width = `${Math.min(100, Number(note.funded_pct))}%`;

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3.5')}>
            <div role="cell" className="min-w-0">
                <div className="truncate text-[13px] font-bold text-rz-ink">
                    {note.business}
                </div>
                <div className="mt-0.5 truncate text-[11px] text-rz-muted">
                    {[note.reference, note.sector].join(' · ')}
                </div>
            </div>
            <div role="cell">
                <span
                    className={cn(
                        'rounded-[6px] px-2 py-0.5 text-[10.5px] font-bold whitespace-nowrap',
                        STATE_TONE[note.state],
                    )}
                >
                    {t(`admin.notes.state.${note.state}`)}
                </span>
            </div>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {t('admin.primary.yield', { rate: note.rate_pct })}
            </span>
            <span role="cell" className="text-[12.5px] text-rz-body">
                {t('admin.primary.term', { count: note.term_months })}
            </span>
            <div role="cell" className="flex items-center gap-[9px]">
                <div className="h-1.5 flex-1 overflow-hidden rounded bg-[#e6ecf6] dark:bg-rz-surface-muted">
                    <div className="h-full bg-[#1e3aff]" style={{ width }} />
                </div>
                <span className="shrink-0 text-[12px] font-bold text-rz-ink">
                    {t('admin.primary.funded', { pct: note.funded_pct })}
                </span>
            </div>
            <div role="cell" className="min-w-0">
                <div className="truncate text-[12.5px] font-semibold text-rz-ink">
                    {t('admin.primary.raised_of', {
                        raised: formatRwf(note.raised),
                        target: formatRwf(note.target),
                    })}
                </div>
                <div className="mt-0.5 text-[11px] text-rz-muted">
                    {t('admin.primary.investors', { count: note.investors })}
                </div>
            </div>
        </div>
    );
}

/**
 * Notes (`staff-notes-v1`, design Notes section): every published raise as a note, by where it
 * sits now, with the server's four figures and a count on every tab. The same rule counts the
 * dashboard's notes, and capital raised is its issued-Holding figure, so the two always agree.
 * Read-only: nothing here changes a note.
 */
export default function AdminNotes(props: AdminNotesProps) {
    const { t } = useTranslation();
    const format = useStatFormatter();

    return (
        <AdminFrame section="notes" {...props}>
            <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                {props.stats.map((stat) => (
                    <KpiTile
                        key={stat.key}
                        index={STAT_ACCENT[stat.key]}
                        label={t(`admin.design.notes.kpi.${stat.key}`)}
                        value={format(stat.value)}
                    />
                ))}
            </div>
            <PillTabs
                label={t('admin.notes.tabs')}
                tabs={props.tabs.map((tab) => ({
                    ...tab,
                    label: t(`admin.design.notes.tab.${tab.key}`),
                }))}
            />
            {props.notes.length === 0 && props.search !== '' && (
                <SearchEmpty section="notes" term={props.search} />
            )}
            <TableCard
                label={t('admin.design.notes.notes.title')}
                minWidth="min-w-[820px]"
                empty={
                    props.notes.length === 0 &&
                    props.search === '' && (
                        <EmptyState
                            title={t('admin.design.notes.notes.empty')}
                        />
                    )
                }
                footer={
                    props.pagination.next !== null && (
                        <ShowMoreLink link={props.pagination.next}>
                            {t('admin.notes.more')}
                        </ShowMoreLink>
                    )
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.design.col.note')}</HeadCell>
                    <HeadCell>{t('admin.design.col.state')}</HeadCell>
                    <HeadCell>{t('admin.design.col.yield')}</HeadCell>
                    <HeadCell>{t('admin.design.col.term')}</HeadCell>
                    <HeadCell>{t('admin.design.col.funded')}</HeadCell>
                    <HeadCell>{t('admin.design.col.raised')}</HeadCell>
                </div>
                {props.notes.map((note) => (
                    <Row key={note.id} note={note} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
