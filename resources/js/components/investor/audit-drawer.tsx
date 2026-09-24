import { useState } from 'react';
import { AMBER_TEXT, POSITIVE_TEXT } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AuditEvidence } from '@/types/investor';

/** The design's three evidence-tile gradients, shown until a photo file is available. */
const TILE_FILLS = [
    'linear-gradient(135deg,#3a5a7a,#0c1830)',
    'linear-gradient(135deg,#59708a,#243a52)',
    'linear-gradient(135deg,#4a6a60,#1c382f)',
];

/**
 * The sealed Field Flash report (design L904–942) — audit evidence. Collapsed it names the Audit
 * Partner, licence and date; open it lists the factual findings, the geo-tagged inspection photos,
 * the signed report and the agreed-upon-procedures disclaimer. The design draws its text in ink
 * where the amber kicker was clearly meant, so the kicker and toggle take the warm tone.
 */
export function AuditDrawer({ audit }: { audit: AuditEvidence }) {
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState(false);
    const rows: [string, string, string?][] = [
        [t('investor.audit.standard'), audit.standard],
        [t('investor.audit.partner'), audit.partner],
        [t('investor.audit.licence'), audit.licence],
        [t('investor.audit.cash'), formatRwf(audit.cash_observed)],
        [
            t('investor.audit.inventory'),
            t('investor.audit.units', {
                count: formatCount(audit.inventory_sample),
            }),
        ],
        [
            t('investor.audit.variance'),
            `${audit.variance_pct.startsWith('-') ? '' : '+'}${audit.variance_pct}%`,
            audit.variance_within_tolerance
                ? POSITIVE_TEXT
                : 'text-[#d0342c] dark:text-rz-danger-text',
        ],
        [t('investor.audit.digest'), `sha256:${audit.digest.slice(0, 10)}…`],
    ];

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
                        {rows.map(([label, value, tone], index) => (
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
                                <dd
                                    className={cn(
                                        'text-right text-[12.5px] font-bold',
                                        tone ?? 'text-rz-ink',
                                    )}
                                >
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
                        {t('investor.audit.photos')}
                    </p>
                    <div className="mt-2 grid grid-cols-3 gap-2">
                        {audit.photos.map((photo, index) => (
                            <div
                                key={photo.label}
                                className="relative flex aspect-square items-end overflow-hidden rounded-xl p-[7px]"
                                style={{
                                    background:
                                        TILE_FILLS[index % TILE_FILLS.length],
                                }}
                            >
                                {photo.url !== null && (
                                    <img
                                        src={photo.url}
                                        alt=""
                                        className="absolute inset-0 size-full object-cover"
                                    />
                                )}
                                <span className="relative text-[10.5px] leading-[1.25] font-bold text-white [text-shadow:0_1px_3px_rgba(0,0,0,.6)]">
                                    {photo.label}
                                    <br />
                                    {photo.geo}
                                </span>
                            </div>
                        ))}
                    </div>
                    <a
                        href={audit.report.url}
                        className="mt-3 flex h-[46px] w-full items-center justify-center rounded-xl bg-rz-accent-fill text-[13.5px] font-bold text-white"
                    >
                        {t('investor.audit.download')}
                    </a>
                    <p className="mt-2 text-[10.5px] leading-normal text-rz-slate">
                        {t('investor.audit.disclaimer')}
                    </p>
                </div>
            )}
        </div>
    );
}
