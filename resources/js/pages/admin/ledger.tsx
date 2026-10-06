import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { formatTimestamp } from '@/components/admin/format';
import { KIND_META } from '@/components/admin/kind-meta';
import { EntryDrawer } from '@/components/admin/ledger/entry-drawer';
import {
    CardTitle,
    HeadCell,
    ShowMoreLink,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AdminLedgerProps } from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,2fr)_minmax(0,1.2fr)_minmax(0,1fr)] gap-3 px-5';

/**
 * The ledger (design Finance → Ledger & payouts, T1650–1688): every money movement, newest first,
 * searchable by type, party or reference. Each row opens its postings. Read-only.
 */
export default function AdminLedger(props: AdminLedgerProps) {
    const { t } = useTranslation();

    const search = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.reload({
            data: { q: new FormData(event.currentTarget).get('q') },
        });
    };

    return (
        <AdminFrame
            section="ledger"
            {...props}
            overlay={
                props.entry && (
                    <EntryDrawer key={props.entry.id} entry={props.entry} />
                )
            }
        >
            <TableCard
                label={t('admin.ledger.table')}
                minWidth="min-w-[820px]"
                className="shadow-[0_1px_2px_rgba(16,32,58,.04),0_14px_30px_-24px_rgba(16,32,58,.28)]"
                footer={
                    props.more && (
                        <ShowMoreLink link={props.more}>
                            {t('admin.ledger.see_all', {
                                count: formatCount(props.total),
                            })}
                        </ShowMoreLink>
                    )
                }
            >
                <div className="border-b border-[#e3e9f4] bg-[#f8fafd] px-5 pt-[18px] pb-[15px] dark:border-rz-border dark:bg-rz-surface-sunken">
                    <div className="flex flex-wrap items-baseline justify-between gap-3">
                        <div>
                            <CardTitle>{t('admin.ledger.title')}</CardTitle>
                            <p className="mt-[3px] text-[12px] text-rz-muted">
                                {t('admin.ledger.caption')}
                            </p>
                        </div>
                        <span className="text-[12px] font-semibold text-[#7b8699] dark:text-rz-muted">
                            {t('admin.ledger.count', {
                                count: formatCount(props.total),
                            })}
                        </span>
                    </div>
                    <form
                        onSubmit={search}
                        role="search"
                        className="mt-[13px] flex h-[34px] min-w-[190px] flex-1 items-center gap-[7px] rounded-[9px] border border-[#dbe3f0] bg-rz-surface px-[11px] dark:border-rz-border"
                    >
                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                        >
                            <circle
                                cx="11"
                                cy="11"
                                r="7"
                                stroke="#9aa3b5"
                                strokeWidth="1.8"
                            />
                            <path
                                d="m20 20-3.2-3.2"
                                stroke="#9aa3b5"
                                strokeWidth="1.8"
                                strokeLinecap="round"
                            />
                        </svg>
                        <input
                            type="search"
                            name="q"
                            defaultValue={props.search}
                            aria-label={t('admin.ledger.search_label')}
                            placeholder={t('admin.ledger.search_placeholder')}
                            className="min-w-0 flex-1 border-none bg-transparent text-[12.5px] text-rz-ink outline-none placeholder:text-rz-muted"
                        />
                    </form>
                </div>
                <div
                    role="row"
                    className={cn(
                        GRID,
                        'border-b border-[#e3e9f4] bg-[#f7f9fd] py-[11px] dark:border-rz-border dark:bg-rz-surface-sunken',
                    )}
                >
                    <HeadCell>{t('admin.ledger.col.time')}</HeadCell>
                    <HeadCell>{t('admin.ledger.col.type')}</HeadCell>
                    <HeadCell>{t('admin.ledger.col.from_to')}</HeadCell>
                    <HeadCell>{t('admin.ledger.col.reference')}</HeadCell>
                    <HeadCell end>{t('admin.ledger.col.amount')}</HeadCell>
                </div>
                {props.entries.map((row) => (
                    <div
                        key={row.id}
                        role="row"
                        className={cn(
                            GRID,
                            'relative items-center border-b border-[#f2f5fa] py-3 hover:bg-[#f5f8fd] dark:border-rz-divider dark:hover:bg-rz-surface-sunken',
                        )}
                    >
                        <span
                            role="cell"
                            className="text-[12px] text-[#7b8699] tabular-nums dark:text-rz-muted"
                        >
                            {formatTimestamp(row.at)}
                        </span>
                        <span
                            role="cell"
                            className={cn(
                                'truncate text-[12px] font-bold',
                                KIND_META[row.kind].color,
                            )}
                        >
                            <Link
                                href={row.link}
                                aria-label={t('admin.ledger.open_named', {
                                    id: row.id,
                                })}
                                className="after:absolute after:inset-0"
                            >
                                {t(`admin.ledger.kind.${row.kind}`)}
                            </Link>
                        </span>
                        <span
                            role="cell"
                            className="truncate text-[12px] text-rz-ink"
                        >
                            {row.from} → {row.to}
                        </span>
                        <span
                            role="cell"
                            className="truncate text-[11.5px] text-rz-body"
                        >
                            {row.reference}
                        </span>
                        <span
                            role="cell"
                            className="text-right text-[12.5px] font-bold text-rz-ink tabular-nums"
                        >
                            {formatRwfShort(row.amount)}
                        </span>
                    </div>
                ))}
                {props.entries.length === 0 && (
                    <div className="p-10 text-center text-[13px] text-rz-faint">
                        {props.search === ''
                            ? t('admin.ledger.empty')
                            : t('admin.search.empty_body', {
                                  section: t('admin.section.ledger.title'),
                                  term: props.search,
                              })}
                    </div>
                )}
            </TableCard>
        </AdminFrame>
    );
}
