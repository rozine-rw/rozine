import { useTranslation } from '@/hooks/use-translation';
import { formatDateLong } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { LegalDocument as LegalDocumentType } from '@/types/business';

/**
 * Terms & Conditions and the Privacy Note (design L1866–1889): the version the business accepted,
 * with the design's intro box and contact footer around the server's sections.
 */
export function LegalDocument({
    kind,
    document,
}: {
    kind: 'terms' | 'privacy';
    document: LegalDocumentType;
}) {
    const { t, locale } = useTranslation();

    return (
        <>
            <p className="mt-4 flex items-center gap-1.5 text-[11px] font-semibold text-rz-secondary">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden
                    className="size-3 shrink-0"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                        stroke="#a9b4c6"
                        strokeWidth="2"
                    />
                    <path
                        d="M12 7.5V12l3 2"
                        stroke="#a9b4c6"
                        strokeWidth="2"
                        strokeLinecap="round"
                    />
                </svg>
                {t('business.profile.legal.updated', {
                    date: formatDateLong(document.updated_on, locale),
                    version: document.version,
                })}
            </p>
            <p
                className={cn(
                    'mt-3 rounded-2xl border p-3.5 text-[12.5px] leading-[1.62] text-rz-slate',
                    kind === 'terms'
                        ? 'border-[rgba(29,158,117,.10)] bg-[rgba(29,158,117,.06)]'
                        : 'border-[rgba(29,158,117,.2)] bg-[rgba(29,158,117,.07)]',
                )}
            >
                {t(`business.profile.legal.${kind}_intro`)}
            </p>
            <ol>
                {document.sections.map((section, index) => (
                    <li key={section.heading} className="mt-[18px]">
                        <h2 className="text-sm leading-[1.4] font-bold text-rz-ink">
                            {index + 1}. {section.heading}
                        </h2>
                        <p className="mt-[7px] text-[12.5px] leading-[1.68] whitespace-pre-line text-rz-secondary">
                            {section.body}
                        </p>
                    </li>
                ))}
            </ol>
            <p className="mt-6 rounded-2xl border border-[#eef2f9] bg-[#f5f8fc] p-[15px] text-xs leading-[1.6] text-rz-secondary dark:border-rz-border dark:bg-rz-page">
                {t(`business.profile.legal.${kind}_footer`)}{' '}
                <span className="font-semibold text-rz-accent-app-text">
                    {t(`business.profile.legal.${kind}_contact`)}
                </span>
                {t('business.profile.legal.footer_end')}
            </p>
        </>
    );
}
