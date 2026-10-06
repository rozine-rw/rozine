import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatCount,
    formatMonthYear,
    formatRwfShort,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessNoteSummary, NoteStatus } from '@/types/business';

const FILTERS = ['draft', 'active', 'repaying', 'failed', 'archived'] as const;

type NoteFilter = (typeof FILTERS)[number];

/** Where each note state is filed, as the design's status map files it. */
const FILTER_OF: Record<NoteStatus, NoteFilter> = {
    draft: 'draft',
    active: 'active',
    funded: 'repaying',
    repaying: 'repaying',
    completed: 'archived',
    failed: 'failed',
};

const STATUS_TONE: Record<NoteStatus, string> = {
    draft: 'bg-[rgba(194,102,31,.10)] text-rz-ink',
    active: 'bg-rz-accent-soft text-rz-accent-app-text',
    funded: 'bg-[rgba(105,116,138,.10)] text-rz-secondary',
    repaying: 'bg-rz-accent-soft text-rz-accent-app-text',
    completed: 'bg-[rgba(105,116,138,.10)] text-rz-secondary',
    failed: 'bg-[rgba(229,72,77,.10)] text-rz-danger-text',
};

/** The design's banner placeholders until a note carries its own photos. */
const BANNERS: { background: string; glyph: IconName }[] = [
    {
        background:
            'linear-gradient(135deg,#1e3aff 0%,#1e3aff 70%,#f4f7fc 100%)',
        glyph: 'truck',
    },
    {
        background:
            'radial-gradient(130% 120% at 18% 12%,#1e3aff 0%,#1e3aff 55%,#f4f7fc 100%)',
        glyph: 'factory',
    },
    {
        background:
            'linear-gradient(200deg,#1e3aff 0%,#eef3fb 60%,#f4f7fc 100%)',
        glyph: 'people',
    },
];

function NoteCard({ note }: { note: BusinessNoteSummary }) {
    const { t, locale } = useTranslation();
    const status = t(`business.note.status.${note.status}`);
    const created =
        note.created_at === null
            ? status
            : formatMonthYear(note.created_at, locale);

    return (
        <article className="overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
            <div className="relative h-[138px] lg:hidden">
                <div className="rz-hscroll absolute inset-0 flex snap-x snap-mandatory overflow-x-auto overflow-y-hidden">
                    {BANNERS.map((banner) => (
                        <div
                            key={banner.glyph}
                            className="relative h-[138px] flex-[0_0_100%] snap-start"
                            style={{ background: banner.background }}
                        >
                            <div className="absolute inset-0 flex items-center justify-center text-[59px] opacity-[.14]">
                                <Icon name={banner.glyph} tone="white" />
                            </div>
                        </div>
                    ))}
                </div>
                <div className="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(8,11,20,.3)_0%,rgba(8,11,20,0)_34%,rgba(8,11,20,.93)_100%)]" />
                <span
                    className={cn(
                        'pointer-events-none absolute top-[11px] right-3 inline-flex items-center gap-[5px] rounded-[10px] px-[9px] py-1 text-[11px] font-semibold',
                        STATUS_TONE[note.status],
                    )}
                >
                    {status}
                </span>
                <div className="pointer-events-none absolute right-[13px] bottom-[11px] left-[13px]">
                    <h3 className="text-[15.5px] font-semibold text-white [text-shadow:0_1px_4px_rgba(0,0,0,.5)]">
                        {note.title}
                    </h3>
                    <p className="mt-0.5 text-[11px] text-white [text-shadow:0_1px_3px_rgba(0,0,0,.5)]">
                        {note.id} · {created}
                    </p>
                </div>
            </div>

            <div className="p-3.5 lg:px-[13px] lg:pt-2.5 lg:pb-[11px]">
                <Link href={note.link} className="block">
                    <span className="mb-2 hidden items-center gap-[9px] lg:flex">
                        <span
                            className={cn(
                                'flex size-7 shrink-0 items-center justify-center rounded-[10px] text-[13px] font-bold',
                                STATUS_TONE[note.status],
                            )}
                        >
                            {note.title.trim().charAt(0).toUpperCase()}
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-[13.5px] font-semibold text-rz-ink">
                                {note.title}
                            </span>
                            <span className="block truncate text-[10.5px] text-rz-secondary">
                                {note.id} · {created}
                            </span>
                        </span>
                        <span
                            className={cn(
                                'shrink-0 rounded-[10px] px-2 py-[3px] text-[10.5px] font-semibold',
                                STATUS_TONE[note.status],
                            )}
                        >
                            {status}
                        </span>
                    </span>
                    <span className="block h-[7px] overflow-hidden rounded-[4px] bg-rz-border">
                        <span
                            className="block h-full bg-rz-accent-fill"
                            style={{ width: `${note.funded_pct}%` }}
                        />
                    </span>
                    <span className="mt-2 flex items-center justify-between">
                        <span className="text-[12.5px] font-semibold text-rz-ink">
                            {note.funded_pct}%{' '}
                            <span className="font-medium text-rz-secondary">
                                {t('business.note.funded')}
                            </span>
                        </span>
                        <span className="inline-flex items-center gap-1 text-[11.5px] font-semibold text-rz-secondary">
                            <span className="size-1.5 rounded-full bg-rz-accent-app-text shadow-[0_0_0_3px_rgba(29,158,117,.10)]" />
                            {t('business.note.investors', {
                                count: formatCount(note.investors),
                            })}
                        </span>
                    </span>
                    <span className="mt-[5px] block text-[11.5px] text-rz-secondary">
                        {t('business.home.raised_of', {
                            raised: formatRwfShort(note.raised),
                            target: formatRwfShort(note.target),
                        })}
                    </span>
                </Link>
                {note.resume && (
                    <Link
                        href={note.resume}
                        className="mt-3 flex h-11 w-full items-center justify-center gap-[7px] rounded-xl bg-rz-accent-fill text-[13.5px] font-semibold text-white lg:mt-[9px] lg:h-9"
                    >
                        {t('business.note.continue_application')}
                        <span aria-hidden className="text-base leading-none">
                            ›
                        </span>
                    </Link>
                )}
            </div>
        </article>
    );
}

/** "YOUR NOTES" — status chips and the note cards (design L271–329). */
export function NotesSection({ notes }: { notes: BusinessNoteSummary[] }) {
    const { t } = useTranslation();
    const [filter, setFilter] = useState<NoteFilter>('active');
    const shown = notes.filter((note) => FILTER_OF[note.status] === filter);

    return (
        <section aria-labelledby="business-notes">
            <div className="px-5 lg:px-[18px]">
                <h2
                    id="business-notes"
                    className="mt-[18px] text-xs font-bold tracking-[.04em] text-rz-slate uppercase lg:mt-4"
                >
                    {t('business.note.title')}
                </h2>
                <div
                    role="group"
                    aria-label={t('business.note.filter_label')}
                    className="rz-hscroll relative mt-[11px] flex snap-x gap-2 overflow-x-auto [mask-image:linear-gradient(90deg,#000_0,#000_calc(100%-26px),transparent_100%)]"
                >
                    {FILTERS.map((key) => (
                        <button
                            key={key}
                            type="button"
                            aria-pressed={filter === key}
                            onClick={() => setFilter(key)}
                            className={cn(
                                'flex-[0_0_auto] rounded-[10px] border px-3.5 py-2 text-[13px] font-semibold whitespace-nowrap',
                                filter === key
                                    ? 'border-[#cfe9d8] bg-rz-accent-fill text-white dark:border-rz-accent-fill'
                                    : 'border-rz-border bg-rz-surface text-rz-slate',
                            )}
                        >
                            {t(`business.note.filter.${key}`)}
                        </button>
                    ))}
                </div>
            </div>
            <div className="flex flex-col gap-3 px-5 pt-3.5 lg:gap-2 lg:px-[18px] lg:pt-3 lg:pb-2">
                {shown.map((note) => (
                    <NoteCard key={note.id} note={note} />
                ))}
                {shown.length === 0 && (
                    <div className="px-5 py-10 text-center">
                        <div className="text-3xl">
                            <Icon name="inbox-empty" />
                        </div>
                        <p className="mt-2.5 text-[14.5px] font-semibold text-rz-ink">
                            {t('business.note.empty.title')}
                        </p>
                        <p className="mt-1.5 text-[13px] text-rz-secondary">
                            {t('business.note.empty.body')}
                        </p>
                    </div>
                )}
            </div>
        </section>
    );
}
