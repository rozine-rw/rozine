import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import type { BusinessStatements } from '@/types/business';

type StatementUploadProps = {
    statements: BusinessStatements;
    identityContextRevision: number;
    /** Only a member who may save the application uploads; anyone else sees the list. */
    canUpload: boolean;
};

/**
 * The business's bank and Mobile Money statements on step 1 (BRS APP-B01, plan C-14). Each upload
 * goes to the server as the original file and the page is drawn again with it listed; receiving or
 * reading a statement verifies nothing, so the figures above stay as the server states them.
 */
export function StatementUpload({
    statements,
    identityContextRevision,
    canUpload,
}: StatementUploadProps) {
    const { t, locale } = useTranslation();
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const upload = (file: File) => {
        setError(null);
        router.post(
            statements.upload.url,
            {
                file,
                request_id: crypto.randomUUID(),
                identity_context_revision: identityContextRevision,
                expected_revision: statements.revision,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onStart: () => setUploading(true),
                onError: (errors) =>
                    setError(
                        errors.file ?? t('business.apply.statements.failed'),
                    ),
                onFinish: () => setUploading(false),
            },
        );
    };

    return (
        <section
            aria-labelledby="statements-heading"
            className="mt-[13px] rounded-2xl border border-rz-border bg-rz-surface p-4"
        >
            <h3
                id="statements-heading"
                className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase"
            >
                {t('business.apply.statements.title')}
            </h3>
            <p className="mt-1.5 text-[12.5px] leading-[1.55] text-rz-ink">
                {t('business.apply.statements.body')}
            </p>

            {statements.documents.length === 0 ? (
                <p className="mt-3 text-xs text-rz-secondary">
                    {t('business.apply.statements.empty')}
                </p>
            ) : (
                <ul className="mt-3 flex flex-col gap-2">
                    {statements.documents.map((document) => (
                        <li
                            key={document.id}
                            className="flex items-center justify-between gap-2.5 rounded-xl border border-rz-border px-3.5 py-2.5"
                        >
                            <div className="min-w-0">
                                <a
                                    href={document.link.url}
                                    className="block truncate text-[13px] font-semibold text-rz-accent-app-text underline-offset-2 hover:underline"
                                >
                                    {document.filename}
                                </a>
                                <p className="mt-0.5 text-[11px] text-rz-secondary">
                                    {t('business.apply.statements.received', {
                                        date: formatDate(
                                            document.received_at,
                                            locale,
                                        ),
                                    })}
                                </p>
                            </div>
                            <span className="shrink-0 rounded-[10px] border border-rz-border px-2 py-0.5 text-[11px] font-semibold text-rz-slate">
                                {t(
                                    `business.apply.statements.reading.${document.reading}`,
                                )}
                            </span>
                        </li>
                    ))}
                </ul>
            )}

            {error !== null && (
                <p
                    role="alert"
                    className="mt-3 flex items-center gap-2 text-[12.5px] text-rz-danger-text"
                >
                    <Icon name="warning" tone="red" />
                    {error}
                </p>
            )}

            {canUpload && (
                <label
                    className={`mt-3 inline-flex items-center justify-center rounded-xl bg-rz-accent-fill px-4 py-2.5 text-[13px] font-bold text-white ${uploading ? 'opacity-60' : 'cursor-pointer'}`}
                >
                    <input
                        type="file"
                        accept=".pdf,.csv,application/pdf,text/csv"
                        aria-label={t('business.apply.statements.upload')}
                        className="sr-only"
                        disabled={uploading}
                        onChange={(event) => {
                            const file = event.target.files?.[0];
                            event.target.value = '';

                            if (file) {
                                upload(file);
                            }
                        }}
                    />
                    {uploading
                        ? t('business.apply.statements.uploading')
                        : t('business.apply.statements.upload')}
                </label>
            )}
        </section>
    );
}
