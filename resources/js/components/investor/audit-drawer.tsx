import { useState } from 'react';
import { AMBER_TEXT } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AuditSummary } from '@/types/investor';

/**
 * The sealed Field Flash report (design L904–942) as a factual summary (C3 v2 §2b, H9). Collapsed it
 * names the Audit Partner, licence and date; open it gives the standard, the sealed report's digest
 * as a reference and the reconciliation statement, which may cite the governed tolerance. There is
 * no report export, inspection photo, location, cash figure or verdict. The design draws its text
 * in ink where the amber kicker was clearly meant, so the kicker and toggle take the warm tone.
 */
export function AuditDrawer({ audit }: { audit: AuditSummary }) {
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState(false);
    const rows: [string, string][] = [
        [t('investor.audit.standard'), audit.standard],
        [t('investor.audit.partner'), audit.partner],
        [t('investor.audit.licence'), audit.licence],
        [t('investor.audit.digest'), `sha256:${audit.digest.slice(0, 10)}…`],
    ];

    if (audit.tolerance !== null) {
        rows.push([t('investor.audit.tolerance'), formatRwf(audit.tolerance)]);
    }

    return (
        <div className="overflow-hidden rounded-2xl border border-[#e8d69f] bg-[#fdfaf2] dark:border-[rgba(212,175,55,.35)] dark:bg-[rgba(212,175,55,.06)]">
            <button
                type="button"
                aria-expanded={open}
                onClick={() => setOpen(!open)}
                className="flex w-full items-center gap-[11px] p-3.5 text-left"
            >
                <span className="flex size-9 shrink-0 items-center justify-center rounded-[10px] border border-[#d4af37] bg-[rgba(194,102,31,.10)]">
                    <svg
                        viewBox="0 0 20 20"
                        fill="#d4af37"
                        aria-hidden
                        className="size-[18px]"
                    >
                        <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M10 1.944A11.954 11.954 0 012.166 5 12.02 12.02 0 0010 18.2 12.02 12.02 0 0017.834 5 11.954 11.954 0 0110 1.944zM13.707 8.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        />
                    </svg>
                </span>
                <span className="min-w-0 flex-1">
                    <span
                        className={cn(
                            'block text-[10.5px] font-bold tracking-[.08em] uppercase',
                            AMBER_TEXT,
                        )}
                    >
                        {t('investor.audit.kicker', {
                            standard: audit.standard,
                        })}
                    </span>
                    <span className="mt-0.5 block text-[13.5px] font-bold text-rz-ink">
                        {audit.partner}
                    </span>
                    <span className="mt-px block text-[11.5px] text-rz-slate">
                        {t('investor.audit.verified_line', {
                            licence: audit.licence,
                            date: formatDate(audit.verified_on, locale),
                        })}
                    </span>
                </span>
                <span className={cn('shrink-0 text-xs font-bold', AMBER_TEXT)}>
                    {open ? t('investor.audit.hide') : t('investor.audit.view')}
                </span>
            </button>
            {open && (
                <div className="px-3.5 pb-3.5">
                    <dl className="rounded-xl border border-[#efe3c4] bg-rz-surface px-[13px] py-0.5 dark:border-rz-border">
                        {rows.map(([label, value], index) => (
                            <div
                                key={label}
                                className={cn(
                                    'flex items-center justify-between gap-3 py-[9px]',
                                    index < rows.length - 1 &&
                                        'border-b border-[#f6f1e3] dark:border-rz-divider',
                                )}
                            >
                                <dt className="text-[12.5px] text-rz-ink">
                                    {label}
                                </dt>
                                <dd className="text-right text-[12.5px] font-bold text-rz-ink">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                    <p
                        className={cn(
                            'mt-3 text-[10.5px] font-bold tracking-[.06em] uppercase',
                            AMBER_TEXT,
                        )}
                    >
                        {t('investor.audit.reconciliation')}
                    </p>
                    <p className="mt-1.5 rounded-xl border border-[#efe3c4] bg-rz-surface px-[13px] py-2.5 text-[12px] leading-[1.55] text-rz-ink dark:border-rz-border">
                        {audit.reconciliation_statement}
                    </p>
                    <p className="mt-2 text-[10.5px] leading-normal text-rz-slate">
                        {t('investor.audit.disclaimer')}
                    </p>
                </div>
            )}
        </div>
    );
}
