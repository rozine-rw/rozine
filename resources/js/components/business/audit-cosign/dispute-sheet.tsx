import { useRef, useState } from 'react';
import type { ChangeEvent, FormEvent, ReactNode } from 'react';
import { FieldError } from '@/components/rozine/form';
import { formatFileSize } from '@/components/rozine/proof-file-list';
import { useTranslation } from '@/hooks/use-translation';

/** The most a dispute's supporting text may hold. */
export const SUPPORTING_LIMIT = 1000;

/** The most proof files one dispute may carry. */
export const PROOF_FILE_LIMIT = 5;

/** The largest proof file the server retains: 10 MiB. */
export const PROOF_FILE_MAX_BYTES = 10 * 1024 * 1024;

/** The proof the server accepts: PDF, JPEG or PNG originals. */
const PROOF_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

const PROOF_EXTENSIONS = ['.pdf', '.jpg', '.jpeg', '.png'];

const ACCEPT = [...PROOF_TYPES, ...PROOF_EXTENSIONS].join(',');

/** What the business wrote and attached, as the page sends it. */
export type DisputeDraft = {
    supporting: string;
    files: File[];
};

/**
 * A courtesy check before sending, so a file the server would refuse is not uploaded only to be
 * refused; the server stays the authority on what it retains. A browser may leave the type blank,
 * so the name's extension counts as well as the reported type.
 */
const proofFileProblem = (chosen: File): 'type' | 'size' | null => {
    const name = chosen.name.toLowerCase();
    const known =
        PROOF_TYPES.includes(chosen.type) ||
        PROOF_EXTENSIONS.some((extension) => name.endsWith(extension));

    if (!known) {
        return 'type';
    }

    return chosen.size > PROOF_FILE_MAX_BYTES ? 'size' : null;
};

/** A field error on any one file (`proof_files.0`) as well as on the list. */
const fileError = (
    errors: Partial<Record<string, string>>,
): string | undefined =>
    errors.proof_files ??
    Object.entries(errors).find(([field]) =>
        field.startsWith('proof_files.'),
    )?.[1];

/**
 * A dispute of the sealed report (N6): proof as plain text of up to 1,000 characters, up to five
 * PDF, JPEG or PNG files of at most 10 MiB each, or both — at least one of the two. There is no
 * separate reason field: file-only proof is enough (#99). Submitting never changes the sealed
 * report; it pauses the review window while the CPA reviews the proof.
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
    const [supporting, setSupporting] = useState('');
    const [files, setFiles] = useState<File[]>([]);
    const [problems, setProblems] = useState<string[]>([]);
    const picker = useRef<HTMLInputElement>(null);
    const title = t('business.audit_cosign.dispute.open');
    const ready = supporting.trim() !== '' || files.length > 0;
    const serverFileError = fileError(errors);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        onSubmit({ supporting: supporting.trim(), files });
    };

    /* Each chosen file is checked on its own; one the server would refuse is left out and named. */
    const attach = (event: ChangeEvent<HTMLInputElement>) => {
        const chosen = Array.from(event.target.files ?? []);
        const kept: File[] = [];
        const found: string[] = [];

        for (const file of chosen) {
            const problem = proofFileProblem(file);

            if (problem === 'type') {
                found.push(
                    t('business.audit_cosign.dispute.file_type', {
                        name: file.name,
                    }),
                );
            } else if (problem === 'size') {
                found.push(
                    t('business.audit_cosign.dispute.file_size', {
                        name: file.name,
                    }),
                );
            } else if (files.length + kept.length >= PROOF_FILE_LIMIT) {
                found.push(
                    t('business.audit_cosign.dispute.file_limit', {
                        name: file.name,
                        limit: PROOF_FILE_LIMIT,
                    }),
                );
            } else {
                kept.push(file);
            }
        }

        setFiles([...files, ...kept]);
        setProblems(found);
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
                    <p
                        id="dispute-proof-rule"
                        className="mt-1.5 text-[12.5px] leading-[1.55] font-semibold text-rz-ink"
                    >
                        {t('business.audit_cosign.dispute.proof_rule')}
                    </p>
                    {notice}
                    <div className="mt-3.5">
                        <div className="mb-1.5 flex items-baseline justify-between gap-3">
                            <label
                                htmlFor="dispute-supporting"
                                className="text-xs font-semibold text-rz-label uppercase"
                            >
                                {t('business.audit_cosign.dispute.supporting')}
                            </label>
                            <span
                                id="dispute-supporting-count"
                                aria-live="polite"
                                className="text-[11px] font-semibold text-rz-secondary tabular-nums"
                            >
                                {t('business.audit_cosign.count', {
                                    count: supporting.length,
                                    limit: SUPPORTING_LIMIT,
                                })}
                            </span>
                        </div>
                        <textarea
                            id="dispute-supporting"
                            rows={5}
                            maxLength={SUPPORTING_LIMIT}
                            value={supporting}
                            onChange={(event) =>
                                setSupporting(
                                    event.target.value.slice(
                                        0,
                                        SUPPORTING_LIMIT,
                                    ),
                                )
                            }
                            aria-invalid={
                                errors.supporting_text !== undefined ||
                                undefined
                            }
                            aria-describedby="dispute-supporting-help dispute-supporting-count dispute-proof-rule"
                            className="w-full resize-none rounded-xl border border-rz-field-border bg-rz-field px-3.5 py-[11px] text-sm text-rz-ink outline-none focus:border-rz-focus-border"
                        />
                        <p
                            id="dispute-supporting-help"
                            className="mt-1.5 text-[11px] leading-[1.45] text-rz-secondary"
                        >
                            {t('business.audit_cosign.dispute.supporting_help')}
                        </p>
                        <FieldError id="dispute-supporting-error">
                            {errors.supporting_text}
                        </FieldError>
                    </div>
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
                                        <span className="ml-auto shrink-0 text-[11px] text-rz-secondary tabular-nums">
                                            {formatFileSize(file.size, t)}
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
                            accept={ACCEPT}
                            aria-labelledby="dispute-files-label"
                            onChange={attach}
                            className="sr-only"
                            tabIndex={-1}
                        />
                        <button
                            type="button"
                            onClick={() => picker.current?.click()}
                            disabled={files.length >= PROOF_FILE_LIMIT}
                            className="h-10 rounded-xl border border-rz-border bg-rz-surface px-4 text-[13px] font-semibold text-rz-accent-app-text disabled:text-rz-secondary"
                        >
                            {t('business.audit_cosign.dispute.files_add')}
                        </button>
                        <p className="mt-1.5 text-[11px] leading-[1.45] text-rz-secondary">
                            {t('business.audit_cosign.dispute.files_help', {
                                limit: PROOF_FILE_LIMIT,
                            })}
                        </p>
                        {problems.length > 0 && (
                            <ul
                                role="alert"
                                className="mt-1.5 text-xs leading-[1.45] font-medium text-rz-danger-text"
                            >
                                {problems.map((problem, index) => (
                                    <li key={`${index}-${problem}`}>
                                        {problem}
                                    </li>
                                ))}
                            </ul>
                        )}
                        <FieldError id="dispute-files-error">
                            {serverFileError}
                        </FieldError>
                    </div>
                    <div className="mt-4 flex flex-col gap-2.5">
                        <button
                            type="submit"
                            disabled={!ready || locked}
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
