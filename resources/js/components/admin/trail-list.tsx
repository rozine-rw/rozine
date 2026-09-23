import { formatTimestamp } from '@/components/admin/format';
import { TONE_DOT, TONE_TEXT } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { TrailEntry } from '@/types/admin';

/**
 * A record's own trail: every privileged action on it with who, when and the reason they gave.
 * Read-only, newest first, exactly as the event log holds it.
 */
export function TrailList({
    title,
    entries,
    empty,
}: {
    title: string;
    entries: TrailEntry[];
    empty: string;
}) {
    const { t } = useTranslation();

    return (
        <section aria-label={title} className="mt-4">
            <h3 className="text-[12px] font-bold tracking-[.05em] text-[#7b8699] uppercase dark:text-rz-muted">
                {title}
            </h3>
            <ol className="mt-2.5 overflow-hidden rounded-[13px] border border-rz-hairline bg-rz-surface">
                {entries.length === 0 && (
                    <li className="px-[15px] py-3 text-[12.5px] text-rz-faint">
                        {empty}
                    </li>
                )}
                {entries.map((entry) => (
                    <li
                        key={entry.id}
                        className="border-b border-[#eef2f8] px-[15px] py-3 last:border-b-0 dark:border-rz-divider"
                    >
                        <div className="flex items-center gap-2">
                            <span
                                className={cn(
                                    'size-[7px] shrink-0 rounded-full',
                                    TONE_DOT[entry.action.tone],
                                )}
                            />
                            <span
                                className={cn(
                                    'flex-1 text-[13px] font-semibold',
                                    TONE_TEXT[entry.action.tone],
                                )}
                            >
                                {entry.action.label}
                            </span>
                            <time
                                dateTime={entry.at}
                                className="text-[11px] text-rz-muted tabular-nums"
                            >
                                {formatTimestamp(entry.at)}
                            </time>
                        </div>
                        <div className="mt-1 pl-[15px] text-[12px] text-rz-body">
                            {t('admin.trail.by', { actor: entry.actor })}
                        </div>
                        {entry.reason !== null && (
                            <p className="mt-1 pl-[15px] text-[12px] leading-[1.45] text-rz-slate">
                                {t('admin.trail.reason', {
                                    reason: entry.reason,
                                })}
                            </p>
                        )}
                    </li>
                ))}
            </ol>
        </section>
    );
}
