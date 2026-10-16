import { useId } from 'react';
import { CARD, Eyebrow } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { EngagementDocument } from '@/types/auditor';

export type TermsDocumentKind = 'master_services' | 'agreed_procedures';

/** The first 12 hex digits of a SHA-256, enough to tell two versions apart at a glance. */
export const shortHash = (sha256: string): string => sha256.slice(0, 12);

/**
 * One retained engagement document, exactly as published: its title and complete original text
 * as plain text, line breaks kept and nothing rendered as markup. The legal text stays in its
 * original language under a translated page, so the browser is asked not to translate it.
 */
export function TermsDocument({
    kind,
    document,
    version,
}: {
    kind: TermsDocumentKind;
    document: EngagementDocument;
    version: string;
}) {
    const { t } = useTranslation();
    const titleId = useId();

    return (
        <section
            aria-labelledby={titleId}
            className={cn(CARD, 'mt-3.5 p-[15px] lg:p-[17px]')}
        >
            <Eyebrow>{t(`auditor.engagement.document.${kind}`)}</Eyebrow>
            <h2
                id={titleId}
                translate="no"
                className="mt-1 text-[16px] leading-[1.3] font-bold text-rz-ink"
            >
                {document.title}
            </h2>
            <p className="mt-1 text-[11.5px] text-rz-secondary">
                {t('auditor.engagement.document_meta', {
                    version,
                    hash: shortHash(document.sha256),
                })}
            </p>
            <div
                translate="no"
                data-testid={`terms-${kind}`}
                className="mt-3 border-t border-[#eef2f9] pt-3 text-[13px] leading-[1.6] break-words whitespace-pre-wrap text-rz-ink dark:border-rz-divider"
            >
                {document.body}
            </div>
        </section>
    );
}
