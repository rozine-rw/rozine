import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AuditDisputeProofFile } from '@/types/audit-dispute';

const MEBIBYTE = 1024 * 1024;

/** "312 KB" or "2.4 MB", in binary units as the server's 10 MiB limit counts them. */
export const formatFileSize = (
    bytes: number,
    t: ReturnType<typeof useTranslation>['t'],
): string =>
    bytes < MEBIBYTE
        ? t('common.file_size.kb', {
              size: Math.max(1, Math.round(bytes / 1024)),
          })
        : t('common.file_size.mb', {
              size: (Math.round((bytes / MEBIBYTE) * 10) / 10).toLocaleString(
                  'en-US',
              ),
          });

/** "PDF", "JPEG" or "PNG" from the server's media type. */
const fileKind = (mimeType: string): string =>
    mimeType.slice(mimeType.indexOf('/') + 1).toUpperCase();

/**
 * Retained dispute proof, each file with its kind and size and the server's authorized download
 * link. A download is a plain anchor: the server answers with an attachment, so it must never be
 * an Inertia visit, and the link is used exactly as sent.
 */
export function ProofFileList({
    label,
    files,
    className,
}: {
    label: string;
    files: AuditDisputeProofFile[];
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <ul
            aria-label={label}
            className={cn(
                'overflow-hidden rounded-xl border border-rz-border bg-rz-surface',
                className,
            )}
        >
            {files.map((file) => (
                <li
                    key={file.id}
                    className="flex items-center justify-between gap-3 border-b border-[#eef2f9] px-3 py-2.5 last:border-b-0 dark:border-rz-divider"
                >
                    <span className="min-w-0">
                        <span className="block truncate text-[12.5px] font-semibold text-rz-ink">
                            {file.name}
                        </span>
                        <span className="block text-[11px] text-rz-secondary">
                            {t('common.file_size.kind', {
                                kind: fileKind(file.mime_type),
                                size: formatFileSize(file.size_bytes, t),
                            })}
                        </span>
                    </span>
                    <a
                        href={file.download.url}
                        aria-label={t('common.proof_file.download', {
                            name: file.name,
                        })}
                        className="shrink-0 text-[12px] font-bold text-rz-accent-app-text"
                    >
                        {t('common.proof_file.download_short')}
                    </a>
                </li>
            ))}
        </ul>
    );
}
