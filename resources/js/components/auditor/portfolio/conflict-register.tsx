import { useState } from 'react';
import { useAuditorCommands } from '@/components/auditor/commands';
import { ConflictSheet } from '@/components/auditor/sheets/conflict-sheet';
import { DIVIDER } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AssignedFile, AuditorPortfolioProps } from '@/types/auditor';

/** The design lists the four most recent declarations (L2862). */
const RECORD_SHOWN = 4;

/**
 * Declare an interest (design L390–414, MVP-AUDITOR-AC-08): any assigned file, at any time. The
 * design declares on one tap; here the tap opens a confirmation that records the kind of interest,
 * because the declaration is permanent and re-dispatches the file.
 */
export function ConflictRegister({
    conflicts,
}: {
    conflicts: AuditorPortfolioProps['conflicts'];
}) {
    const { t, locale } = useTranslation();
    const center = useAuditorCommands();
    const [file, setFile] = useState<AssignedFile | null>(null);
    const count = conflicts.files.length;
    /* Each file is its own call (#96 point 3): only those it allows can be declared on. */
    const declarable = conflicts.files.filter((assigned) =>
        assigned.allowed_actions.includes('conflict.declare'),
    );

    return (
        <section className="mt-3 rounded-[20px] border border-rz-border bg-rz-surface p-4">
            <h2 className="text-[14.5px] font-bold text-rz-ink">
                {t('auditor.conflict.title')}
            </h2>
            <p className="mt-1 text-[12px] leading-[1.55] text-rz-secondary">
                {t('auditor.conflict.body')}
            </p>
            {count === 0 ? (
                <p className="mt-[9px] text-[11.5px] text-rz-muted">
                    {t('auditor.conflict.none_assigned')}
                </p>
            ) : (
                declarable.length > 0 && (
                    <>
                        <p className="mt-[11px] text-[10px] font-bold tracking-[.05em] text-rz-secondary uppercase">
                            {declarable.length === 1
                                ? t('auditor.conflict.options_one')
                                : t('auditor.conflict.options_other', {
                                      count: declarable.length,
                                  })}
                        </p>
                        <div className="rz-scroll mt-[7px] flex max-h-[132px] flex-wrap gap-[7px] overflow-y-auto">
                            {declarable.map((assigned) => (
                                <button
                                    key={assigned.id}
                                    type="button"
                                    onClick={() => setFile(assigned)}
                                    disabled={!center.conflict.idle}
                                    className="h-[34px] rounded-[10px] border border-rz-border bg-rz-surface px-3 text-[11.5px] font-semibold whitespace-nowrap text-rz-ink disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {assigned.business}
                                </button>
                            ))}
                        </div>
                    </>
                )
            )}
            {conflicts.record.length > 0 && (
                <div className="mt-[11px]">
                    <p className="text-[10px] font-bold tracking-[.05em] text-rz-secondary uppercase">
                        {t('auditor.conflict.on_record')}
                    </p>
                    <ul>
                        {conflicts.record
                            .slice(0, RECORD_SHOWN)
                            .map((entry) => (
                                <li
                                    key={entry.conflict_id}
                                    className={cn(
                                        'flex items-start gap-[9px] border-b py-2 last:border-b-0',
                                        DIVIDER,
                                    )}
                                >
                                    <span
                                        aria-hidden
                                        className="shrink-0 text-[12px] text-[#c2661f]"
                                    >
                                        ●
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block text-[12px] font-semibold text-rz-ink">
                                            {entry.business} · {entry.note_id}
                                        </span>
                                        <span className="mt-px block text-[11px] text-rz-secondary">
                                            {t(
                                                `auditor.conflict.kind.${entry.kind}`,
                                            )}{' '}
                                            ·{' '}
                                            {formatDate(
                                                entry.declared_on,
                                                locale,
                                            )}
                                        </span>
                                    </span>
                                </li>
                            ))}
                    </ul>
                </div>
            )}
            {file !== null && (
                <ConflictSheet
                    business={file.business}
                    assignment={{ id: file.id, revision: file.revision }}
                    action={conflicts.declare}
                    scope={file.allowed_actions}
                    onClose={() => setFile(null)}
                />
            )}
        </section>
    );
}
