import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    CardTitle,
    Chip,
    EmptyState,
    EXPLAIN,
    HeadCell,
    KpiTile,
    ROW_RULE,
    TABLE_HEAD,
    TableCard,
    useStatFormatter,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AdminComplaintsProps,
    ComplaintDetail,
    ComplaintRow,
    ComplaintStatKey,
    ComplaintStatusKey,
    Tone,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.5fr)_minmax(0,.9fr)_minmax(0,1.5fr)_minmax(100px,.7fr)] gap-3 px-5';

const STATUS_TONE: Record<ComplaintStatusKey, Tone> = {
    awaiting: 'amber',
    answered: 'blue',
    resolved: 'green',
};

/** Each tile keeps its accent whatever the order: past a clock reads in the red accent. */
const STAT_ACCENT: Record<ComplaintStatKey, number> = {
    unresolved: 0,
    awaiting: 2,
    late: 7,
};

/** The clock that matters next, or which clock it is past, in the server's words. */
function useClock(): (row: ComplaintRow | ComplaintDetail) => {
    text: string;
    late: boolean;
} {
    const { t, locale } = useTranslation();

    return (row) => {
        if (row.late !== null) {
            return { text: t(`admin.complaints.late.${row.late}`), late: true };
        }

        if (row.status === 'resolved') {
            return { text: t('admin.complaints.done'), late: false };
        }

        return row.status === 'awaiting'
            ? {
                  text: t('admin.complaints.acknowledge_by', {
                      time: formatDateTime(row.acknowledge_by, locale),
                  }),
                  late: false,
              }
            : {
                  text: t('admin.complaints.resolve_by', {
                      time: formatDateTime(row.resolve_by, locale),
                  }),
                  late: false,
              };
    };
}

function Row({ row }: { row: ComplaintRow }) {
    const { t } = useTranslation();
    const clock = useClock()(row);

    return (
        <div role="row" className={cn(GRID, ROW_RULE, 'items-center py-3')}>
            <span
                role="cell"
                className="text-[12.5px] font-semibold text-rz-ink"
            >
                {row.reference}
                <span className="mt-0.5 block text-[11px] font-medium text-rz-muted">
                    {row.customer} · {t(`admin.complaints.role.${row.role}`)}
                </span>
            </span>
            <span role="cell">
                <Chip tone={STATUS_TONE[row.status]}>
                    {t(`admin.complaints.status.${row.status}`)}
                </Chip>
            </span>
            <span
                role="cell"
                className={cn(
                    'text-[12px] font-semibold',
                    clock.late
                        ? 'text-[#b3352a] dark:text-[#ff6b6f]'
                        : 'text-rz-slate',
                )}
            >
                {clock.text}
            </span>
            <div role="cell" className="flex justify-end">
                <Link
                    href={row.link}
                    preserveScroll
                    aria-label={t('admin.complaints.open_named', {
                        reference: row.reference,
                    })}
                    className="shrink-0 rounded-lg border border-rz-hairline bg-rz-surface px-3 py-1.5 text-[11.5px] font-semibold text-rz-slate"
                >
                    {t('admin.complaints.open')}
                </Link>
            </div>
        </div>
    );
}

/** The answer form: the text, whether it resolves the complaint, and a fresh `request_id`. */
function AnswerForm({ action }: { action: { url: string } }) {
    const { t } = useTranslation();
    const form = useForm<{ body: string; resolves: boolean }>({
        body: '',
        resolves: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            request_id: crypto.randomUUID(),
        }));
        form.post(action.url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form
            onSubmit={submit}
            className="shrink-0 border-t border-[#e2e8f4] bg-rz-surface px-5 py-4 dark:border-rz-border"
        >
            <label
                htmlFor="complaint-answer"
                className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase"
            >
                {t('admin.complaints.answer.label')}
            </label>
            <textarea
                id="complaint-answer"
                rows={3}
                value={form.data.body}
                onChange={(event) => form.setData('body', event.target.value)}
                placeholder={t('admin.complaints.answer.placeholder')}
                aria-invalid={form.errors.body !== undefined || undefined}
                className="mt-1.5 box-border w-full resize-none rounded-xl border border-rz-hairline bg-[#f8fafc] px-3 py-2.5 text-[13px] text-rz-ink outline-none dark:bg-rz-surface-sunken"
            />
            {form.errors.body !== undefined && (
                <p
                    role="alert"
                    className="mt-1 text-[12px] font-semibold text-[#b3352a]"
                >
                    {form.errors.body}
                </p>
            )}
            <label className="mt-2.5 flex items-center gap-2 text-[12.5px] font-semibold text-rz-ink">
                <input
                    type="checkbox"
                    checked={form.data.resolves}
                    onChange={(event) =>
                        form.setData('resolves', event.target.checked)
                    }
                    className="size-4"
                />
                {t('admin.complaints.answer.resolves')}
            </label>
            <div className="mt-3 flex items-center justify-between gap-3">
                <p className={cn('text-[11.5px]', EXPLAIN)}>
                    {t('admin.complaints.answer.hint')}
                </p>
                <button
                    type="submit"
                    disabled={form.processing}
                    className="shrink-0 rounded-[10px] bg-[#1e3aff] px-4 py-2 text-[12.5px] font-bold text-white disabled:opacity-60"
                >
                    {t('admin.complaints.answer.send')}
                </button>
            </div>
        </form>
    );
}

function ComplaintDrawer({ complaint }: { complaint: ComplaintDetail }) {
    const { t, locale } = useTranslation();
    const clock = useClock()(complaint);

    return (
        <Drawer
            label={t('admin.complaints.drawer', {
                reference: complaint.reference,
            })}
            close={complaint.close}
        >
            <div className="flex shrink-0 items-start justify-between gap-3 border-b border-[#e2e8f4] bg-rz-surface px-5 py-4 dark:border-rz-border">
                <div className="min-w-0">
                    <h2 className="truncate text-[17px] font-bold text-rz-ink">
                        {complaint.reference}
                    </h2>
                    <p className="mt-0.5 text-[12px] text-rz-muted">
                        {complaint.customer} ·{' '}
                        {t(`admin.complaints.role.${complaint.role}`)}
                    </p>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <Chip tone={STATUS_TONE[complaint.status]}>
                            {t(`admin.complaints.status.${complaint.status}`)}
                        </Chip>
                        <span
                            className={cn(
                                'text-[11.5px] font-semibold',
                                clock.late
                                    ? 'text-[#b3352a] dark:text-[#ff6b6f]'
                                    : 'text-rz-slate',
                            )}
                        >
                            {clock.text}
                        </span>
                    </div>
                </div>
                <DrawerClose close={complaint.close} />
            </div>
            <ol className="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto px-5 py-4">
                {complaint.messages.map((message) => (
                    <li
                        key={message.id}
                        className={cn(
                            'flex flex-col',
                            message.author === 'staff'
                                ? 'items-end'
                                : 'items-start',
                        )}
                    >
                        <span className="mb-1 text-[10.5px] font-semibold text-rz-muted">
                            {t(
                                `admin.complaints.from.${message.resolves ? 'resolution' : message.author}`,
                                { time: formatDateTime(message.at, locale) },
                            )}
                        </span>
                        <p
                            className={cn(
                                'max-w-[85%] rounded-2xl px-3.5 py-2.5 text-[13px] leading-normal whitespace-pre-line',
                                message.author === 'staff'
                                    ? 'bg-[#1e3aff] text-white'
                                    : 'border border-rz-hairline bg-rz-surface text-rz-ink',
                            )}
                        >
                            {message.body}
                        </p>
                    </li>
                ))}
            </ol>
            {complaint.answer === null ? (
                <p className="shrink-0 border-t border-[#e2e8f4] px-5 py-4 text-[12.5px] text-rz-muted dark:border-rz-border">
                    {t('admin.complaints.closed')}
                </p>
            ) : (
                <AnswerForm
                    key={complaint.messages.length}
                    action={complaint.answer}
                />
            )}
        </Drawer>
    );
}

/**
 * Complaints (BRS v2.0 CMP-R03, `staff-complaints-v1`): every complaint with its reference, the
 * customer, its status and the clock that matters next, or the one it is past; the figures are the
 * server's. A complaint opens in the drawer with its conversation, where staff holding
 * `complaints.answer` answer it, and an answer marked as the resolution closes it.
 */
export default function AdminComplaints(props: AdminComplaintsProps) {
    const { t } = useTranslation();
    const format = useStatFormatter();

    return (
        <AdminFrame
            section="complaints"
            {...props}
            searchQuery={{ param: 'q', clears: ['complaint'] }}
            overlay={
                props.complaint && (
                    <ComplaintDrawer
                        key={props.complaint.id}
                        complaint={props.complaint}
                    />
                )
            }
        >
            <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3 lg:gap-4">
                {props.stats.map((stat) => (
                    <KpiTile
                        key={stat.key}
                        index={STAT_ACCENT[stat.key]}
                        label={t(`admin.complaints.stats.${stat.key}`)}
                        value={format(stat.value)}
                    />
                ))}
            </div>
            <div className="mb-3.5">
                <CardTitle>{t('admin.complaints.title')}</CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.complaints.caption')}
                </p>
            </div>
            {props.complaints.length === 0 && props.search !== '' && (
                <SearchEmpty section="complaints" term={props.search} />
            )}
            <TableCard
                label={t('admin.complaints.table')}
                minWidth="min-w-[640px]"
                empty={
                    props.complaints.length === 0 &&
                    props.search === '' && (
                        <EmptyState
                            title={t('admin.complaints.empty_title')}
                            body={t('admin.complaints.empty_body')}
                        />
                    )
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[13px]')}>
                    <HeadCell>{t('admin.complaints.col.reference')}</HeadCell>
                    <HeadCell>{t('admin.complaints.col.status')}</HeadCell>
                    <HeadCell>{t('admin.complaints.col.clock')}</HeadCell>
                    <HeadCell end>{t('admin.complaints.col.open')}</HeadCell>
                </div>
                {props.complaints.map((row) => (
                    <Row key={row.id} row={row} />
                ))}
            </TableCard>
        </AdminFrame>
    );
}
