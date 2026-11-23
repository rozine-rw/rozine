import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { useTranslation } from '@/hooks/use-translation';
import type { LegalDocumentLink } from '@/types/legal';

const BEFORE = {
    terms: 'legal.consent.terms_before',
    privacy: 'legal.consent.privacy_before',
    disclaimers: 'legal.consent.disclaimers_before',
} as const;

/**
 * One required tick box per legal document, each linking to the document it agrees to (opened in
 * a new tab, so the form keeps what was typed). Each sends the digest of the wording behind its
 * link, so the server records exactly the version the person was shown.
 */
export function LegalConsentFields({
    documents,
    errors,
    tabIndex,
}: {
    documents: LegalDocumentLink[];
    errors: Record<string, string | undefined>;
    tabIndex?: number;
}) {
    const { t } = useTranslation();
    const stale = documents
        .map((document) => errors[`legal_documents.${document.key}`])
        .find((message) => message !== undefined);

    return (
        <fieldset className="grid gap-3">
            <legend className="sr-only">{t('legal.consent.legend')}</legend>
            {documents.map((document) => (
                <div key={document.key} className="grid gap-1">
                    <input
                        type="hidden"
                        name={`legal_documents[${document.key}]`}
                        value={document.sha256}
                    />
                    <div className="flex items-start gap-3">
                        <Checkbox
                            id={`legal-${document.key}`}
                            name={`legal_agreements[${document.key}]`}
                            value="1"
                            required
                            tabIndex={tabIndex}
                            className="mt-0.5"
                            aria-invalid={
                                errors[`legal_agreements.${document.key}`] !==
                                undefined
                                    ? true
                                    : undefined
                            }
                        />
                        <label
                            htmlFor={`legal-${document.key}`}
                            className="text-sm leading-snug"
                        >
                            {t(BEFORE[document.key])}{' '}
                            <a
                                href={document.url}
                                target="_blank"
                                rel="noopener"
                                className="font-medium underline underline-offset-4"
                            >
                                {document.title}
                            </a>
                        </label>
                    </div>
                    <InputError
                        message={errors[`legal_agreements.${document.key}`]}
                    />
                </div>
            ))}
            <InputError
                message={
                    stale ?? errors.legal_agreements ?? errors.legal_documents
                }
            />
        </fieldset>
    );
}
