import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime, formatMonthYearLong } from '@/lib/rozine/format';
import type { AuditReport } from '@/types/business-audit';

export function SectionLabel({ children }: { children: ReactNode }) {
    return (
        <h2 className="mt-[22px] text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
            {children}
        </h2>
    );
}

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex items-baseline justify-between gap-3 border-b border-[#eef2f9] py-[9px] last:border-b-0 dark:border-rz-divider">
            <dt className="shrink-0 text-[12.5px] text-rz-secondary">
                {label}
            </dt>
            <dd className="min-w-0 text-right text-[13px] font-semibold break-words text-rz-ink">
                {children}
            </dd>
        </div>
    );
}

/**
 * The sealed report as the business reads it before co-signing: who sealed it under which
 * procedure, its digest and seal, the Audit Partner's note and the factual findings. Read-only —
 * the sealed report is immutable, and a co-sign screen carries no rating, health or figures.
 */
export function ReportSummary({
    businessName,
    report,
}: {
    businessName: string;
    report: AuditReport;
}) {
    const { t, locale } = useTranslation();
    const { seal } = report;

    return (
        <>
            <div className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4">
                <p className="text-[10.5px] font-bold tracking-[.06em] text-rz-accent-app-text uppercase">
                    {t(`business.audit_cosign.kind.${report.kind}`)}
                </p>
                <p className="mt-[3px] text-[17px] font-bold text-rz-ink">
                    {businessName}
                </p>
                <dl className="mt-2.5">
                    {report.period !== null && (
                        <Row label={t('business.audit_cosign.period')}>
                            {formatMonthYearLong(`${report.period}-01`, locale)}
                        </Row>
                    )}
                    <Row label={t('business.audit_cosign.auditor')}>
                        {t('business.audit_cosign.auditor_value', {
                            name: report.auditor.name,
                            licence: report.auditor.licence,
                        })}
                    </Row>
                    <Row label={t('business.audit_cosign.procedure')}>
                        {report.procedure_version}
                    </Row>
                    <Row label={t('business.audit_cosign.digest')}>
                        <span title={report.digest} translate="no">
                            {t('business.audit_cosign.digest_short', {
                                digest: report.digest.slice(0, 12),
                            })}
                        </span>
                    </Row>
                </dl>
            </div>

            <SectionLabel>{t('business.audit_cosign.seal.title')}</SectionLabel>
            <div className="mt-[11px] flex items-start gap-3 rounded-2xl border border-rz-border bg-rz-surface p-3.5">
                <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                    <Icon name={seal.status === 'valid' ? 'shield' : 'lock'} />
                </span>
                {seal.status === 'valid' ? (
                    <div className="min-w-0 flex-1">
                        <p className="text-[13.5px] font-semibold text-rz-ink">
                            {t('business.audit_cosign.seal.valid')}
                        </p>
                        {seal.signed_at !== null && (
                            <p className="mt-0.5 text-xs text-rz-secondary">
                                {t('business.audit_cosign.seal.valid_at', {
                                    date: formatDateTime(
                                        seal.signed_at,
                                        locale,
                                    ),
                                })}
                            </p>
                        )}
                        {seal.verification !== null && (
                            <Link
                                href={seal.verification}
                                className="mt-2 inline-block text-[12.5px] font-bold text-rz-accent-app-text"
                            >
                                {t('business.audit_cosign.seal.verify')}
                            </Link>
                        )}
                    </div>
                ) : (
                    <p className="min-w-0 flex-1 text-[13px] leading-normal text-rz-secondary">
                        {t('business.audit_cosign.seal.unavailable')}
                    </p>
                )}
            </div>

            {/* A report sealed with no note has no note section, never an empty box. */}
            {report.auditor_note.trim() !== '' && (
                <>
                    <SectionLabel>
                        {t('business.audit_cosign.note_title')}
                    </SectionLabel>
                    <p className="mt-[11px] rounded-2xl border border-rz-border bg-rz-surface p-3.5 text-[13px] leading-[1.6] whitespace-pre-line text-rz-slate">
                        {report.auditor_note}
                    </p>
                </>
            )}

            <SectionLabel>{t('business.audit_cosign.findings')}</SectionLabel>
            {report.findings.length === 0 ? (
                <p className="mt-[11px] text-[12.5px] text-rz-secondary">
                    {t('business.audit_cosign.findings_empty')}
                </p>
            ) : (
                <ol
                    aria-label={t('business.audit_cosign.findings')}
                    className="mt-[11px] overflow-hidden rounded-2xl border border-rz-border bg-rz-surface"
                >
                    {report.findings.map((finding) => (
                        <li
                            key={finding.code}
                            className="border-b border-[#eef2f9] px-[15px] py-[13px] last:border-b-0 dark:border-rz-divider"
                        >
                            <p className="text-[13.5px] font-semibold text-rz-ink">
                                {finding.title}
                            </p>
                            <p className="mt-[3px] text-[12.5px] leading-[1.55] whitespace-pre-line text-rz-secondary">
                                {finding.body}
                            </p>
                            <p className="mt-1.5 text-[11px] font-semibold text-rz-slate">
                                {t('business.audit_cosign.evidence', {
                                    count: finding.evidence_ids.length,
                                })}
                            </p>
                        </li>
                    ))}
                </ol>
            )}
            <p className="mt-3 text-[11.5px] leading-normal text-rz-secondary">
                {t('business.audit_cosign.sealed_note')}
            </p>
        </>
    );
}
