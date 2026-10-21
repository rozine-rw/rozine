import {
    EmptyState,
    HeadCell,
    InfoTip,
    ROW_RULE,
    TABLE_HEAD,
    TableCard,
    useRelativeLabel,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { StaffActionLogEntry } from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.8fr)_minmax(80px,0.6fr)] items-center gap-3.5 px-5';

const ACTION_TONE: Record<StaffActionLogEntry['action'], string> = {
    staff_suspended: 'text-[#b3352a] dark:text-[#ff6b6f]',
    staff_enabled: 'text-[#157357] dark:text-[#3fcda0]',
};

/**
 * Admin action log (design Staff & Roles): the latest staff access changes across every operator,
 * newest first, each with who made it, whose access it changed and the reason they gave.
 */
export function ActionLog({
    entries,
    serverTime,
}: {
    entries: StaffActionLogEntry[];
    serverTime: string;
}) {
    const { t } = useTranslation();
    const ago = useRelativeLabel(serverTime);

    return (
        <section aria-labelledby="action-log-title" className="mt-6">
            <div className="mb-2.5 flex items-center gap-[7px]">
                <h2
                    id="action-log-title"
                    className="text-[15px] font-semibold text-rz-ink"
                >
                    {t('admin.action_log.title')}
                </h2>
                <InfoTip label={t('admin.action_log.title')}>
                    {t('admin.action_log.info')}
                </InfoTip>
            </div>
            <TableCard
                label={t('admin.action_log.title')}
                minWidth="min-w-[760px]"
                empty={
                    entries.length === 0 && (
                        <EmptyState title={t('admin.action_log.empty')} />
                    )
                }
            >
                <div role="row" className={cn(GRID, TABLE_HEAD, 'py-[11px]')}>
                    <HeadCell>{t('admin.action_log.col.action')}</HeadCell>
                    <HeadCell>{t('admin.action_log.col.by')}</HeadCell>
                    <HeadCell>{t('admin.action_log.col.target')}</HeadCell>
                    <HeadCell>{t('admin.action_log.col.reason')}</HeadCell>
                    <HeadCell end>{t('admin.action_log.col.when')}</HeadCell>
                </div>
                {entries.map((entry) => (
                    <div
                        key={entry.id}
                        role="row"
                        className={cn(GRID, ROW_RULE, 'py-[11px]')}
                    >
                        <span
                            role="cell"
                            className={cn(
                                'truncate text-[12.5px] font-bold',
                                ACTION_TONE[entry.action],
                            )}
                        >
                            {t(`admin.action_log.action.${entry.action}`)}
                        </span>
                        <span
                            role="cell"
                            className="truncate text-[12.5px] text-rz-ink"
                        >
                            {entry.actor}
                        </span>
                        <span
                            role="cell"
                            className="truncate text-[12.5px] text-rz-ink"
                        >
                            {entry.target}
                        </span>
                        <span
                            role="cell"
                            title={entry.reason}
                            className="truncate text-[11.5px] text-rz-muted"
                        >
                            {entry.reason}
                        </span>
                        <span
                            role="cell"
                            className="text-right text-[11.5px] text-rz-muted"
                        >
                            {ago(entry.at)}
                        </span>
                    </div>
                ))}
            </TableCard>
        </section>
    );
}
