import { Link } from '@inertiajs/react';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import { formatTimestamp } from '@/components/admin/format';
import { KIND_META } from '@/components/admin/kind-meta';
import { CAPTION, EXPLAIN, LABEL } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { LedgerEntryDetail } from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)] gap-3 px-4';

/**
 * The ledger drill-down: one money movement opened to its postings (MVP-ADMIN ledger view). The
 * design's ledger rows open nothing; this drawer is composed from its drawer shell and ledger
 * table style. Read-only — an entry is never edited; a correction is its own contra entry,
 * linked both ways (AC-02).
 */
export function EntryDrawer({ entry }: { entry: LedgerEntryDetail }) {
    const { t } = useTranslation();
    const meta = KIND_META[entry.kind];
    const facts = [
        ['from', entry.from],
        ['to', entry.to],
        ['reference', entry.reference],
        ['operation', entry.operation_id],
    ] as const;

    return (
        <Drawer
            label={t('admin.ledger.drawer_label', { id: entry.id })}
            close={entry.links.close}
        >
            <div className="shrink-0 border-b border-rz-hairline bg-rz-surface px-[18px] py-4">
                <div className="flex items-start gap-3">
                    <span
                        aria-hidden
                        className={cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-[11px] border border-rz-hairline bg-rz-surface text-[18px] font-bold',
                            meta.color,
                        )}
                    >
                        {meta.glyph}
                    </span>
                    <div className="min-w-0 flex-1">
                        <div
                            className={cn('text-[12px] font-bold', meta.color)}
                        >
                            {t(`admin.ledger.kind.${entry.kind}`)}
                        </div>
                        <h2 className="text-[19px] font-bold text-rz-ink tabular-nums">
                            {formatRwf(entry.amount)}
                        </h2>
                        <p className="mt-0.5 truncate text-[12px] text-[#7b8699] tabular-nums dark:text-rz-muted">
                            {formatTimestamp(entry.at)} · {entry.id}
                        </p>
                    </div>
                    <DrawerClose close={entry.links.close} />
                </div>
            </div>
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-[18px] pb-[26px]">
                <dl className="grid grid-cols-2 gap-2">
                    {facts.map(([key, value]) => (
                        <div
                            key={key}
                            className="min-w-0 rounded-[11px] border border-rz-hairline bg-rz-surface px-3 py-2.5"
                        >
                            <dt
                                className={cn(
                                    'text-[10.5px] font-semibold',
                                    CAPTION,
                                )}
                            >
                                {t(`admin.ledger.fact.${key}`)}
                            </dt>
                            <dd
                                className={cn(
                                    'mt-[3px] truncate text-[13px] font-bold text-rz-ink',
                                    key === 'operation' &&
                                        'font-mono text-[12px]',
                                )}
                            >
                                {value}
                            </dd>
                        </div>
                    ))}
                </dl>
                <p className={cn('mt-3 text-[12px]', EXPLAIN)}>
                    {t('admin.ledger.posted_by', {
                        actor: entry.posted_by.actor,
                        at: formatTimestamp(entry.posted_by.at),
                    })}
                    {entry.posted_by.reason !== null &&
                        ` · ${t('admin.trail.reason', { reason: entry.posted_by.reason })}`}
                </p>

                <h3 className="mt-5 text-[12px] font-bold tracking-[.05em] text-[#7b8699] uppercase dark:text-rz-muted">
                    {t('admin.ledger.postings')}
                </h3>
                <div
                    role="table"
                    aria-label={t('admin.ledger.postings')}
                    className="mt-2.5 overflow-hidden rounded-[13px] border border-rz-hairline bg-rz-surface"
                >
                    <div
                        role="row"
                        className={cn(
                            GRID,
                            'border-b border-[#e3e9f4] bg-rz-surface-sunken py-2.5 dark:border-rz-border',
                        )}
                    >
                        <span
                            role="columnheader"
                            className={cn(
                                'text-[11px] font-semibold uppercase',
                                LABEL,
                            )}
                        >
                            {t('admin.ledger.col.account')}
                        </span>
                        <span
                            role="columnheader"
                            className={cn(
                                'text-right text-[11px] font-semibold uppercase',
                                LABEL,
                            )}
                        >
                            {t('admin.ledger.col.debit')}
                        </span>
                        <span
                            role="columnheader"
                            className={cn(
                                'text-right text-[11px] font-semibold uppercase',
                                LABEL,
                            )}
                        >
                            {t('admin.ledger.col.credit')}
                        </span>
                    </div>
                    {entry.postings.map((posting, index) => (
                        <div
                            key={`${posting.account_code}-${index}`}
                            role="row"
                            className={cn(
                                GRID,
                                'items-center border-b border-[#f2f5fa] py-2.5 dark:border-rz-divider',
                            )}
                        >
                            <span role="cell" className="min-w-0">
                                <span className="block truncate text-[12.5px] font-semibold text-rz-ink">
                                    {posting.account}
                                </span>
                                <span className="block font-mono text-[11px] text-rz-faint">
                                    {posting.account_code}
                                </span>
                            </span>
                            <span
                                role="cell"
                                className="text-right text-[12.5px] font-semibold text-rz-ink tabular-nums"
                            >
                                {posting.debit === null
                                    ? ''
                                    : formatRwf(posting.debit)}
                            </span>
                            <span
                                role="cell"
                                className="text-right text-[12.5px] font-semibold text-rz-ink tabular-nums"
                            >
                                {posting.credit === null
                                    ? ''
                                    : formatRwf(posting.credit)}
                            </span>
                        </div>
                    ))}
                    <div
                        role="row"
                        className={cn(
                            GRID,
                            'items-center bg-rz-surface-sunken py-2.5',
                        )}
                    >
                        <span
                            role="cell"
                            className="flex items-center gap-2 text-[12.5px] font-bold text-rz-ink"
                        >
                            {t('admin.ledger.total')}
                            <span
                                className={cn(
                                    'rounded-md px-2 py-0.5 text-[10px] font-bold',
                                    entry.balanced
                                        ? 'bg-[rgba(29,158,117,.12)] text-[#1d9e75] dark:text-[#3fcda0]'
                                        : 'bg-[rgba(255,77,79,.12)] text-[#e5484d] dark:text-[#ff6b6f]',
                                )}
                            >
                                {t(
                                    entry.balanced
                                        ? 'admin.ledger.balanced'
                                        : 'admin.ledger.unbalanced',
                                )}
                            </span>
                        </span>
                        <span
                            role="cell"
                            className="text-right text-[12.5px] font-bold text-rz-ink tabular-nums"
                        >
                            {formatRwf(entry.totals.debit)}
                        </span>
                        <span
                            role="cell"
                            className="text-right text-[12.5px] font-bold text-rz-ink tabular-nums"
                        >
                            {formatRwf(entry.totals.credit)}
                        </span>
                    </div>
                </div>

                {entry.contra_of !== null && (
                    <p className="mt-3.5 text-[12.5px] text-rz-slate">
                        {t('admin.ledger.contra_of')}{' '}
                        <Link
                            href={entry.contra_of.link}
                            className="font-mono font-bold text-rz-accent-app-text"
                        >
                            {entry.contra_of.id}
                        </Link>
                    </p>
                )}
                {entry.contra_by !== null && (
                    <p className="mt-3.5 text-[12.5px] text-rz-slate">
                        {t('admin.ledger.contra_by')}{' '}
                        <Link
                            href={entry.contra_by.link}
                            className="font-mono font-bold text-rz-accent-app-text"
                        >
                            {entry.contra_by.id}
                        </Link>
                    </p>
                )}
                <p className="mt-3.5 flex items-center gap-2 rounded-xl border border-[#dde6f3] bg-[rgba(30,58,255,.07)] px-3.5 py-2.5 text-[12.5px] text-[#5f6fc8] dark:border-rz-border dark:text-[#99a3ff]">
                    {t('admin.ledger.immutable')}
                </p>
                <Link
                    href={entry.links.events}
                    className="mt-3.5 block w-full rounded-[11px] border border-rz-hairline bg-rz-surface p-[11px] text-center text-[13px] font-bold text-rz-accent-app-text"
                >
                    {t('admin.ledger.open_events')}
                </Link>
            </div>
        </Drawer>
    );
}
