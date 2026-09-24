import type { ReactNode } from 'react';
import { compactRwf } from '@/components/auditor/money';
import {
    AMBER_TEXT,
    DIVIDER,
    Eyebrow,
    INSET,
    StatTile,
    Tick,
} from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessFile, FileDocument } from '@/types/auditor';

const DOC_STROKE: Record<FileDocument['kind'], string> = {
    statement: 'text-[#c2661f]',
    registry: 'text-rz-positive',
    identity: 'text-rz-ink',
    pitch: 'text-[#c2661f]',
    photos: 'text-[#6425c9] dark:text-[#b199fb]',
};

const DOC_TILE: Record<FileDocument['kind'], string> = {
    statement: 'bg-rz-surface',
    registry: 'bg-[rgba(29,158,117,.1)]',
    identity: 'bg-rz-surface',
    pitch: 'bg-rz-surface',
    photos: 'bg-[rgba(124,58,237,.1)]',
};

/** The design's document glyphs (L3302–3305). */
function DocGlyph({ kind }: { kind: FileDocument['kind'] }) {
    const common = {
        stroke: 'currentColor',
        strokeWidth: 1.7,
    } as const;

    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden
            className="size-[17px]"
        >
            {kind === 'identity' ? (
                <>
                    <rect
                        x="3"
                        y="5"
                        width="18"
                        height="14"
                        rx="2"
                        {...common}
                    />
                    <circle cx="9" cy="11" r="2" {...common} />
                    <path
                        d="M14 10h4M14 13h4M6 15.5c.6-1.4 4.4-1.4 5 0"
                        {...common}
                        strokeWidth={1.5}
                        strokeLinecap="round"
                    />
                </>
            ) : kind === 'photos' ? (
                <>
                    <rect
                        x="3"
                        y="7"
                        width="18"
                        height="13"
                        rx="2.5"
                        {...common}
                    />
                    <circle cx="12" cy="13" r="3" {...common} />
                    <path d="M8.5 7l1.2-2h4.6l1.2 2" {...common} />
                </>
            ) : (
                <>
                    <path
                        d="M6 3h8l4 4v14H6z"
                        {...common}
                        strokeLinejoin="round"
                    />
                    <path
                        d="M14 3v4h4M8.5 13h7M8.5 16.5h7"
                        {...common}
                        strokeLinecap="round"
                    />
                </>
            )}
        </svg>
    );
}

/** A figure the server did not state: a dash, never 0. */
const NO_FIGURE = '—';

/** A section's honest empty line: nothing is on record, and nothing is made up in its place. */
function NothingOnRecord({ children }: { children: ReactNode }) {
    return (
        <p className="px-[15px] py-3 text-[11.5px] leading-[1.5] text-rz-secondary">
            {children}
        </p>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section>
            <Eyebrow as="h3" className="mt-3.5 tracking-[.05em]">
                {title}
            </Eyebrow>
            <div className="mt-2 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {children}
            </div>
        </section>
    );
}

type FileReviewProps = {
    business: string;
    file: BusinessFile;
    /**
     * Set when the file came to this partner from another (MVP-AUDITOR-SCR-02-ST-02). Only its
     * presence is used: the other partner is never named.
     */
    reassignedFrom: string | null;
};

/**
 * The business file (MVP-AUDITOR-SCR-02, design step 0 L1038–1093): the raise, what the business
 * submitted, the engine's automated checks, why the field visit is needed and what it must clear,
 * and the file's history. Everything is read-only server evidence; the partner judges nothing here.
 * A figure the server cannot state reads as a dash, a list it cannot state says nothing is on
 * record, and a reason or mandate not yet published is left out — nothing is invented.
 */
export function FileReview({
    business,
    file,
    reassignedFrom,
}: FileReviewProps) {
    const { t, locale } = useTranslation();
    const { raise, history } = file;
    const hasReason = file.reason !== null;
    const hasMandate = file.mandate.length > 0;

    return (
        <>
            <h3 className="text-[16px] font-bold text-rz-ink">
                {t('auditor.file.title')}
            </h3>
            <p className="mt-1 text-[12.5px] leading-[1.5] text-rz-secondary">
                {t('auditor.file.lead', { business })}
            </p>

            {reassignedFrom !== null && (
                <div
                    role="note"
                    className="mt-3.5 rounded-2xl border border-[#f2d69a] bg-rz-surface p-3.5 dark:border-[rgba(240,160,96,.3)]"
                >
                    <p className={cn('text-[12px] font-bold', AMBER_TEXT)}>
                        {t('auditor.file.reassigned_title')}
                    </p>
                    <p
                        className={cn(
                            'mt-1 text-[11.5px] leading-[1.5]',
                            AMBER_TEXT,
                        )}
                    >
                        {t('auditor.file.reassigned_body')}
                    </p>
                </div>
            )}

            <div className="mt-3.5 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                <Eyebrow as="h3" className="tracking-[.04em]">
                    {t('auditor.file.raise')}
                </Eyebrow>
                <div className="mt-2.5 flex gap-2">
                    <StatTile
                        label={t('auditor.jobs.requested')}
                        value={
                            raise.requested === null
                                ? NO_FIGURE
                                : compactRwf(raise.requested)
                        }
                        className={cn(INSET, 'p-2.5')}
                        valueClassName="text-[13.5px]"
                    />
                    <StatTile
                        label={t('auditor.jobs.term')}
                        value={
                            raise.term_months === null
                                ? NO_FIGURE
                                : t('auditor.file.term_months', {
                                      months: raise.term_months,
                                  })
                        }
                        className={cn(INSET, 'p-2.5')}
                        valueClassName="text-[13.5px]"
                    />
                    <StatTile
                        label={t('auditor.file.return')}
                        value={
                            raise.return_pct === null
                                ? NO_FIGURE
                                : t('auditor.file.return_value', {
                                      pct: raise.return_pct,
                                  })
                        }
                        className={cn(INSET, 'p-2.5')}
                        valueClassName="text-[13.5px]"
                    />
                </div>
                <p className="mt-[11px] text-[10px] font-bold tracking-[.03em] text-rz-slate">
                    <span className="uppercase">
                        {t('auditor.file.use_of_funds')}
                    </span>
                    {' · '}
                    {raise.sector === null
                        ? t('auditor.jobs.sector_unavailable')
                        : t(`auditor.sector.${raise.sector}`)}
                </p>
                {raise.use_of_funds !== '' && (
                    <p className="mt-1 text-[12.5px] leading-[1.5] text-rz-slate">
                        {raise.use_of_funds}
                    </p>
                )}
            </div>

            <Section title={t('auditor.file.documents')}>
                {file.documents.length === 0 && (
                    <NothingOnRecord>
                        {t('auditor.file.no_documents')}
                    </NothingOnRecord>
                )}
                <ul>
                    {file.documents.map((document) => (
                        <li
                            key={document.name}
                            className={cn(
                                'flex items-center gap-3 border-b px-[15px] py-3',
                                DIVIDER,
                            )}
                        >
                            <span
                                className={cn(
                                    'flex size-[34px] shrink-0 items-center justify-center rounded-[10px]',
                                    DOC_TILE[document.kind],
                                    DOC_STROKE[document.kind],
                                )}
                            >
                                <DocGlyph kind={document.kind} />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block text-[13px] font-semibold text-rz-ink">
                                    {document.name}
                                </span>
                                <span className="mt-px block text-[11px] text-rz-secondary">
                                    {document.detail}
                                </span>
                            </span>
                            <span
                                className={cn(
                                    'shrink-0 rounded-[10px] px-2 py-1 text-[10px] font-bold',
                                    document.status === 'missing'
                                        ? 'bg-[rgba(192,57,43,.10)] text-rz-danger-text'
                                        : 'bg-rz-page text-rz-positive dark:bg-rz-surface-muted',
                                )}
                            >
                                {t(
                                    `auditor.file.doc_status.${document.status}`,
                                )}
                            </span>
                        </li>
                    ))}
                </ul>
            </Section>

            <Section title={t('auditor.file.prescreen')}>
                {file.prescreen.length === 0 && (
                    <NothingOnRecord>
                        {t('auditor.file.no_prescreen')}
                    </NothingOnRecord>
                )}
                <ul>
                    {file.prescreen.map((check) => (
                        <li
                            key={check.label}
                            className={cn(
                                'flex items-center gap-[11px] border-b px-[15px] py-3',
                                DIVIDER,
                            )}
                        >
                            <span
                                className={cn(
                                    'flex size-[22px] shrink-0 items-center justify-center rounded-[10px]',
                                    check.result === 'met'
                                        ? 'bg-[rgba(29,158,117,.10)] text-rz-positive'
                                        : 'bg-rz-accent-soft text-rz-ink',
                                )}
                            >
                                {check.result === 'met' ? (
                                    <Tick className="size-3" strokeWidth={3} />
                                ) : (
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden
                                        className="size-3"
                                    >
                                        <path
                                            d="M12 8v5M12 16v.4"
                                            stroke="currentColor"
                                            strokeWidth="2.6"
                                            strokeLinecap="round"
                                        />
                                    </svg>
                                )}
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block text-[12.5px] font-semibold text-rz-ink">
                                    {check.label}
                                </span>
                                <span className="mt-px block text-[11px] text-rz-secondary">
                                    {check.detail}
                                </span>
                            </span>
                            <span
                                className={cn(
                                    'shrink-0 text-[10px] font-bold',
                                    check.result === 'met'
                                        ? 'text-rz-positive'
                                        : 'text-rz-ink',
                                )}
                            >
                                {t(`auditor.file.check.${check.result}`)}
                            </span>
                        </li>
                    ))}
                </ul>
            </Section>

            {(hasReason || hasMandate) && (
                <div className="mt-3.5 rounded-2xl border border-[#f2d69a] bg-rz-surface p-3.5 dark:border-[rgba(240,160,96,.3)]">
                    <div className="flex items-center gap-2">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className="size-4 shrink-0"
                        >
                            <rect
                                x="4.8"
                                y="4.2"
                                width="14.4"
                                height="16.6"
                                rx="2.8"
                                fill="rgba(194,102,31,.10)"
                                stroke="#c2661f"
                                strokeWidth="1.6"
                            />
                            <path
                                d="M9.2 4.4V3.6c0-.9.7-1.6 1.6-1.6h2.4c.9 0 1.6.7 1.6 1.6v.8"
                                stroke="#c2661f"
                                strokeWidth="1.6"
                                strokeLinejoin="round"
                            />
                            <path
                                d="m9.4 12.9 2.1 2.1 3.5-3.7"
                                stroke="#c2661f"
                                strokeWidth="1.9"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                        <h3
                            className={cn(
                                'text-[12px] font-bold uppercase',
                                AMBER_TEXT,
                            )}
                        >
                            {hasReason
                                ? t('auditor.file.why')
                                : t('auditor.file.mandate')}
                        </h3>
                    </div>
                    {hasReason && (
                        <p
                            className={cn(
                                'mt-2 text-[12px] leading-[1.55]',
                                AMBER_TEXT,
                            )}
                        >
                            {file.reason}
                        </p>
                    )}
                    {hasReason && hasMandate && (
                        <p
                            className={cn(
                                'mt-3 border-t border-[#f2d69a] pt-3 text-[10.5px] font-bold tracking-[.05em] uppercase dark:border-[rgba(240,160,96,.3)]',
                                AMBER_TEXT,
                            )}
                        >
                            {t('auditor.file.mandate')}
                        </p>
                    )}
                    {hasMandate && (
                        <ol className="mt-[9px] flex flex-col gap-2">
                            {file.mandate.map((step, index) => (
                                <li
                                    key={step}
                                    className="flex items-start gap-[9px]"
                                >
                                    <span className="flex size-[17px] shrink-0 items-center justify-center rounded-[5px] bg-rz-accent-soft text-[10px] font-bold text-rz-ink">
                                        {index + 1}
                                    </span>
                                    <span
                                        className={cn(
                                            'min-w-0 flex-1 text-[11.5px] leading-[1.5]',
                                            AMBER_TEXT,
                                        )}
                                    >
                                        {step}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    )}
                </div>
            )}

            <Section title={t('auditor.file.history')}>
                <div className={cn('border-b px-[15px] py-3', DIVIDER)}>
                    {history.last_audit === null ? (
                        <>
                            <p className="text-[12.5px] font-semibold text-rz-ink">
                                {t('auditor.file.first_visit')}
                            </p>
                            <p className="mt-px text-[11px] leading-[1.5] text-rz-secondary">
                                {t('auditor.file.first_visit_body')}
                            </p>
                        </>
                    ) : (
                        <>
                            <p className="text-[12.5px] font-semibold text-rz-ink">
                                {t('auditor.file.last_audit', {
                                    date: formatDate(
                                        history.last_audit.on,
                                        locale,
                                    ),
                                    kind: t(
                                        `auditor.file.kind.${history.last_audit.kind}`,
                                    ),
                                    by: history.last_audit.by,
                                })}
                            </p>
                            <p className="mt-px text-[11px] leading-[1.5] text-rz-secondary">
                                {history.last_audit.summary}
                            </p>
                        </>
                    )}
                </div>
                <div className="px-[15px] py-3">
                    <p className="text-[10px] font-bold tracking-[.05em] text-rz-secondary uppercase">
                        {t('auditor.file.flags')}
                    </p>
                    {history.flags.length === 0 ? (
                        <p className="mt-1 text-[11.5px] text-rz-secondary">
                            {t('auditor.file.no_flags')}
                        </p>
                    ) : (
                        <ul className="mt-1.5 flex flex-col gap-1">
                            {history.flags.map((flag) => (
                                <li
                                    key={flag}
                                    className="flex items-start gap-[9px] text-[12px] text-rz-ink"
                                >
                                    <span
                                        aria-hidden
                                        className="text-[12px] text-[#c2661f]"
                                    >
                                        ●
                                    </span>
                                    {flag}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </Section>
        </>
    );
}
