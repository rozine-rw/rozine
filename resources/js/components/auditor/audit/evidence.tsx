import { formatKigaliTime } from '@/components/auditor/clock';
import { DIVIDER } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { EvidenceItem } from '@/types/auditor';

/** The first characters of a digest, enough to tell two items apart at a glance. */
const SHORT_DIGEST = 12;

/**
 * Evidence as the server identifies it (auditor-filing-v1 point 4): each item's ID, digest,
 * capture time, source, device attestation and position. Source and attestation are the server's
 * display facts — the page never asserts either — and a fact the capture could not supply reads
 * "Unavailable".
 */
export function EvidenceList({
    items,
    className,
}: {
    items: EvidenceItem[];
    className?: string;
}) {
    const { t, locale } = useTranslation();
    const unavailable = t('auditor.evidence.unavailable');

    return (
        <ul
            aria-label={t('auditor.evidence.title')}
            className={cn('flex flex-col gap-[9px]', className)}
        >
            {items.map((item) => {
                const rows = [
                    {
                        label: t('auditor.evidence.captured'),
                        value:
                            item.captured_at === null
                                ? unavailable
                                : `${formatDate(item.captured_at, locale)} · ${formatKigaliTime(item.captured_at)}`,
                    },
                    {
                        label: t('auditor.evidence.source'),
                        value: t(`auditor.evidence.source_${item.source}`),
                    },
                    {
                        label: t('auditor.evidence.attestation'),
                        value: t(
                            `auditor.evidence.attestation_${item.device_attestation}`,
                        ),
                    },
                    {
                        label: t('auditor.evidence.position'),
                        value: item.position ?? unavailable,
                    },
                    {
                        label: t('auditor.evidence.accuracy'),
                        value:
                            item.accuracy_m === null
                                ? unavailable
                                : t('auditor.evidence.metres', {
                                      metres: item.accuracy_m,
                                  }),
                    },
                ];

                return (
                    <li
                        key={item.evidence_id}
                        className="rounded-xl border border-rz-border bg-rz-surface px-3 py-2.5"
                    >
                        <div className="flex items-baseline justify-between gap-2.5">
                            <span className="text-[12px] font-bold text-rz-ink">
                                {t(`auditor.evidence.kind.${item.kind}`)}
                            </span>
                            <span className="truncate text-[10.5px] text-rz-secondary tabular-nums">
                                {item.evidence_id}
                            </span>
                        </div>
                        <p
                            title={item.sha256}
                            className="mt-0.5 truncate text-[10.5px] text-rz-secondary tabular-nums"
                        >
                            {t('auditor.evidence.digest', {
                                digest: item.sha256.slice(0, SHORT_DIGEST),
                            })}
                        </p>
                        <dl className="mt-1.5">
                            {rows.map((row) => (
                                <div
                                    key={row.label}
                                    className={cn(
                                        'flex items-center justify-between gap-2.5 border-b py-[5px] last:border-b-0',
                                        DIVIDER,
                                    )}
                                >
                                    <dt className="text-[11px] text-rz-secondary">
                                        {row.label}
                                    </dt>
                                    <dd className="text-right text-[11px] font-semibold text-rz-ink">
                                        {row.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </li>
                );
            })}
        </ul>
    );
}
