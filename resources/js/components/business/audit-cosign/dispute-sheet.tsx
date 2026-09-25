import { useRef, useState } from 'react';
import type { ChangeEvent, FormEvent, ReactNode } from 'react';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';

/** The most a dispute's factual reason may hold. */
export const REASON_LIMIT = 2000;

/** The most a dispute's supporting text may hold. */
export const SUPPORTING_LIMIT = 1000;

/** What the business wrote and attached, as the page sends it. */
export type DisputeDraft = {
    reason: string;
    supporting: string;
    files: File[];
};

function LimitedField({
    id,
    label,
    help,
    value,
    limit,
    error,
    rows,
    onChange,
}: {
    id: string;
    label: string;
    help: string;
    value: string;
    limit: number;
    error: string | undefined;
    rows: number;
    onChange: (value: string) => void;
}) {
    const { t } = useTranslation();

    return (
        <div className="mt-3.5">
            <div className="mb-1.5 flex items-baseline justify-between gap-3">
                <label
                    htmlFor={id}
                    className="text-xs font-semibold text-rz-label uppercase"
                >
                    {label}
                </label>
                <span
                    id={`${id}-count`}
                    aria-live="polite"
                    className="text-[11px] font-semibold text-rz-secondary tabular-nums"
                >
                    {t('business.audit_cosign.count', {
                        count: value.length,
                        limit,
                    })}
                </span>
            </div>
            <textarea
                id={id}
                rows={rows}
                maxLength={limit}
                value={value}
                onChange={(event) =>
                    onChange(event.target.value.slice(0, limit))
                }
                aria-invalid={error !== undefined || undefined}
                aria-describedby={`${id}-help ${id}-count`}
                className="w-full resize-none rounded-xl border border-rz-field-border bg-rz-field px-3.5 py-[11px] text-sm text-rz-ink outline-none focus:border-rz-focus-border"
            />
            <p
                id={`${id}-help`}
                className="mt-1.5 text-[11px] leading-[1.45] text-rz-secondary"
            >
                {help}
            </p>
            <FieldError id={`${id}-error`}>{error}</FieldError>
        </div>
    );
}

/**
 * A dispute of the sealed report (pending the delivery 3 contract): a required factual reason of
 * up to 2,000 characters, and optional proof as text of up to 1,000 characters, files, or both.
 * The files go with the command as they are chosen; which types and sizes the server accepts is
 * pending that contract, so the page does not pre-judge them. Submitting never changes the
 * sealed report.
 */
export function DisputeSheet({
    busy,
    locked,
    errors,
    notice,
    onSubmit,
    onClose,
}: {
    busy: boolean;
    locked: boolean;
    errors: Partial<Record<string, string>>;
    /** What happened to the last command, shown inside the sheet while it is open. */
    notice: ReactNode;
    onSubmit: (draft: DisputeDraft) => void;
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const [reason, setReason] = useState('');
    const [supporting, setSupporting] = useState('');
    const [files, setFiles] = useState<File[]>([]);
    const picker = useRef<HTMLInputElement>(null);
    const title = t('business.audit_cosign.dispute.open');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        onSubmit({
            reason: reason.trim(),
            supporting: supporting.trim(),
            files,
        });
    };

    const attach = (event: ChangeEvent<HTMLInputElement>) => {
        const chosen = Array.from(event.target.files ?? []);

        setFiles((current) => [...current, ...chosen]);
        event.target.value = '';
    };

    return (
        <div className="fixed inset-0 z-[70] flex items-end justify-center">
            <button
                type="button"
                aria-label={t('business.audit_cosign.dispute.cancel')}
                onClick={onClose}
                disabled={busy}
                className="absolute inset-0 animate-[rz-scrim_.22s_ease] bg-[rgba(8,14,28,.5)] backdrop-blur-[3px]"
            />
            <div
                role="dialog"
                aria-modal="true"
                aria-label={title}
                className="relative flex max-h-[88%] w-full max-w-[560px] animate-[rz-sheetup_.34s_cubic-bezier(.16,1,.3,1)_both] flex-col overflow-hidden rounded-t-3xl bg-rz-surface shadow-[0_-18px_50px_-16px_rgba(20,45,95,.4)]"
            >
                <form
                    onSubmit={submit}
                    noValidate
                    className="rz-scroll flex-1 overflow-y-auto px-5 pt-[18px] pb-6"
                >
                    <h2 className="text-lg font-bold text-rz-ink">{title}</h2>
                    <p className="mt-1.5 text-[12.5px] leading-[1.55] text-rz-secondary">
                        {t('business.audit_cosign.dispute.intro')}
                    </p>
                    {notice}
                    <LimitedField
                        id="dispute-reason"
                        label={t('business.audit_cosign.dispute.reason')}
                        help={t('business.audit_cosign.dispute.reason_help')}
                        value={reason}
                        limit={REASON_LIMIT}
                        error={errors.reason}
                        rows={5}
                        onChange={setReason}
                    />
                    <LimitedField
                        id="dispute-supporting"
                        label={t('business.audit_cosign.dispute.supporting')}
                        help={t(
                            'business.audit_cosign.dispute.supporting_help',
                        )}
                        value={supporting}
                        limit={SUPPORTING_LIMIT}
                        error={errors.supporting_text}
                        rows={3}
                        onChange={setSupporting}
                    />
                    <div className="mt-3.5">
                        <p
                            id="dispute-files-label"
                            className="mb-1.5 text-xs font-semibold text-rz-label uppercase"
                        >
                            {t('business.audit_cosign.dispute.files')}
                        </p>
                        {files.length > 0 && (
                            <ul
                                aria-labelledby="dispute-files-label"
                                className="mb-2 overflow-hidden rounded-xl border border-rz-border"
                            >
                                {files.map((file, index) => (
                                    <li
                                        key={`${file.name}-${index}`}
                                        className="flex items-center justify-between gap-3 border-b border-[#eef2f9] px-3 py-2 text-[12.5px] text-rz-ink last:border-b-0 dark:border-rz-divider"
                                    >
                                        <span className="min-w-0 truncate">
                                            {file.name}
                                        </span>
                                        <button
                                            type="button"
                                            aria-label={t(
                                                'business.audit_cosign.dispute.file_remove',
                                                { name: file.name },
                                            )}
                                            onClick={() =>
                                                setFiles((current) =>
                                                    current.filter(
                                                        (_kept, at) =>
                                                            at !== index,
                                                    ),
                                                )
                                            }
                                            className="shrink-0 p-0 text-base leading-none text-rz-secondary"
                                        >
                                            <span aria-hidden>×</span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <input
                            ref={picker}
                            id="dispute-files"
                            type="file"
                            multiple
                            aria-labelledby="dispute-files-label"
                            onChange={attach}
                            className="sr-only"
                            tabIndex={-1}
                        />
                        <button
                            type="button"
                            onClick={() => picker.current?.click()}
                            className="h-10 rounded-xl border border-rz-border bg-rz-surface px-4 text-[13px] font-semibold text-rz-accent-app-text"
                        >
                            {t('business.audit_cosign.dispute.files_add')}
                        </button>
                        <p className="mt-1.5 text-[11px] leading-[1.45] text-rz-secondary">
                            {t('business.audit_cosign.dispute.files_help')}
                        </p>
                        <FieldError id="dispute-files-error">
                            {errors.proof_files}
                        </FieldError>
                    </div>
                    <div className="mt-4 flex flex-col gap-2.5">
                        <button
                            type="submit"
                            disabled={reason.trim() === '' || locked}
                            aria-busy={busy || undefined}
                            className="h-[50px] w-full rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white disabled:bg-rz-page disabled:text-rz-secondary"
                        >
                            {busy
                                ? t('business.audit_cosign.dispute.submitting')
                                : t('business.audit_cosign.dispute.submit')}
                        </button>
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={busy}
                            className="h-11 w-full rounded-2xl border border-rz-border bg-rz-surface text-[14px] font-semibold text-rz-ink"
                        >
                            {t('business.audit_cosign.dispute.cancel')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
