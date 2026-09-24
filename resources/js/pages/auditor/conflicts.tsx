import { AuditorShell } from '@/components/auditor/auditor-shell';
import { ConflictReceiptCard } from '@/components/auditor/conflict-receipt';
import { ColumnPad, TabColumns } from '@/components/auditor/tab-columns';
import { EmptyState, ScreenTitle, ShowMore } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { AuditorConflictsProps, OwnConflict } from '@/types/auditor';

/** Characters of the assignment ID kept in its short reference. */
const REFERENCE_TAIL = 6;

/** "…7QK2M4" for a long assignment ID; a short one reads as sent. */
const shortReference = (id: string): string =>
    id.length > REFERENCE_TAIL + 2 ? `…${id.slice(-REFERENCE_TAIL)}` : id;

function OwnConflictReceipt({ entry }: { entry: OwnConflict }) {
    const { t } = useTranslation();
    const name = `${t('auditor.conflict.business_on_record')} · ${t(
        'auditor.conflict.assignment_ref',
        { reference: shortReference(entry.assignment_id) },
    )}`;

    return (
        <article aria-label={name}>
            <h2 className="mb-2 text-[13px] font-bold text-rz-ink">{name}</h2>
            <ConflictReceiptCard receipt={entry.conflict} />
        </article>
    );
}

/**
 * The partner's own conflict receipts (AC-08, S-C): each declaration as it was recorded — its kind,
 * the partner's note, the date and where the assignment stands in coarse terms, including a close
 * by Audit Operations. The private read names no Business, note, case or replacement partner, so
 * each receipt reads "Business on record" with a short assignment reference. The single receipt a
 * completed declaration leads to is this page with one entry. It sits under Portfolio, the tab its
 * register lives on, and offers no command.
 */
export default function AuditorConflicts(props: AuditorConflictsProps) {
    const { t } = useTranslation();
    const next = props.pagination.next;

    return (
        <AuditorShell
            title={t('auditor.conflicts.head_title')}
            tab="portfolio"
            links={props.links}
            openJobs={0}
        >
            <TabColumns
                left={
                    <ColumnPad side="left" tab>
                        <ScreenTitle
                            title={t('auditor.conflicts.title')}
                            lead={t('auditor.conflicts.lead')}
                        />
                        {props.conflicts.length > 0 ? (
                            <div className="mt-3.5 flex flex-col gap-5">
                                {props.conflicts.map((entry) => (
                                    <OwnConflictReceipt
                                        key={entry.conflict.conflict_id}
                                        entry={entry}
                                    />
                                ))}
                            </div>
                        ) : next === null ? (
                            <EmptyState>
                                {t('auditor.conflicts.empty')}
                            </EmptyState>
                        ) : (
                            <p className="mt-3.5 text-center text-[12px] text-rz-secondary">
                                {t('auditor.conflicts.page_empty')}
                            </p>
                        )}
                        {next !== null && <ShowMore next={next} />}
                    </ColumnPad>
                }
                right={null}
            />
        </AuditorShell>
    );
}
