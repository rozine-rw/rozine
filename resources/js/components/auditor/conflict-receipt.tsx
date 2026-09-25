import { useId } from 'react';
import { formatKigaliTime } from '@/components/auditor/clock';
import { DIVIDER, StatusPill } from '@/components/auditor/ui';
import type { PillTone } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    ConflictAssignmentStatus,
    ConflictReceipt,
} from '@/types/auditor';

const STATUS_TONE: Record<ConflictAssignmentStatus, PillTone> = {
    reassignment_pending: 'amber',
    reassigned: 'neutral',
    recorded: 'neutral',
    closed: 'neutral',
};

/**
 * The partner's own receipt for a blocking conflict (auditor-filing-v1, confirmation on #96). Work
 * on the assignment has stopped: the file, evidence, step saves and sealing are gone, and this is
 * all that remains — the declarant's own kind, note and date, and where the assignment stands in
 * coarse terms, never who takes it next. It names no Business: the private read carries none.
 */
export function ConflictReceiptCard({ receipt }: { receipt: ConflictReceipt }) {
    const { t, locale } = useTranslation();
    const titleId = useId();
    const rows = [
        {
            label: t('auditor.receipt.kind'),
            value: t(`auditor.conflict.kind.${receipt.kind}`),
        },
        {
            label: t('auditor.receipt.declared'),
            value: `${formatDate(receipt.declared_at, locale)} · ${formatKigaliTime(receipt.declared_at)}`,
        },
    ];

    return (
        <section aria-labelledby={titleId}>
            <div className="rounded-2xl border border-[#f2d69a] bg-rz-surface p-[15px] dark:border-[rgba(240,160,96,.3)]">
                <div className="flex items-start gap-[11px]">
                    <span
                        aria-hidden
                        className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft text-[16px] font-bold text-rz-accent-app-text"
                    >
                        !
                    </span>
                    <div className="min-w-0 flex-1">
                        <h3
                            id={titleId}
                            className="text-[15px] font-bold text-rz-ink"
                        >
                            {t('auditor.receipt.title')}
                        </h3>
                        <p className="mt-1 text-[12.5px] leading-[1.55] text-rz-secondary">
                            {t(`auditor.receipt.body.${receipt.status}`)}
                        </p>
                    </div>
                </div>
                <div className="mt-3 flex items-center justify-between gap-2.5">
                    <span className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('auditor.receipt.status')}
                    </span>
                    <StatusPill tone={STATUS_TONE[receipt.status]}>
                        {t(`auditor.receipt.state.${receipt.status}`)}
                    </StatusPill>
                </div>
            </div>
            <dl className="mt-3 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {rows.map((row) => (
                    <div
                        key={row.label}
                        className={cn(
                            'flex items-center justify-between gap-3 border-b px-[15px] py-3 last:border-b-0',
                            DIVIDER,
                        )}
                    >
                        <dt className="text-[12px] text-rz-secondary">
                            {row.label}
                        </dt>
                        <dd className="min-w-0 text-right text-[12px] font-bold break-words text-rz-ink">
                            {row.value}
                        </dd>
                    </div>
                ))}
                <div className="px-[15px] py-3">
                    <dt className="text-[12px] text-rz-secondary">
                        {t('auditor.receipt.note')}
                    </dt>
                    <dd className="mt-1 text-[12.5px] leading-[1.55] whitespace-pre-line text-rz-ink">
                        {receipt.note}
                    </dd>
                </div>
            </dl>
        </section>
    );
}
