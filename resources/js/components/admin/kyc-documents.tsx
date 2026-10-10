import * as DialogPrimitive from '@radix-ui/react-dialog';
import { ChevronLeft, ChevronRight, FileText, X } from 'lucide-react';
import { useState } from 'react';
import { formatCount } from '@/components/admin/format';
import { LABEL } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { InvestorVerificationDocument } from '@/types/admin';

const isImage = (document: InvestorVerificationDocument): boolean =>
    document.media_type.startsWith('image/');

const DOWNLOAD =
    'shrink-0 rounded-[9px] border border-rz-hairline px-3 py-1.5 text-[12px] font-semibold text-rz-accent-app-text';

/**
 * A case's identity documents laid out for review: the current ID front, back and selfie side by
 * side as previews of the files themselves, then any uploads they replaced. Each opens full size
 * in a viewer that steps through them, and every file still downloads.
 */
export function KycDocuments({
    documents,
}: {
    documents: InvestorVerificationDocument[];
}) {
    const { t } = useTranslation();
    const current = documents.filter((document) => document.current);
    const earlier = documents.filter((document) => !document.current);
    const ordered = [...current, ...earlier];
    const [open, setOpen] = useState<number | null>(null);

    const meta = (document: InvestorVerificationDocument): string =>
        t('admin.kyc.document.meta', {
            name: document.filename,
            size: formatCount(Math.ceil(document.size_bytes / 1024)),
        });
    const slot = (document: InvestorVerificationDocument): string =>
        t(`admin.kyc.slot.${document.slot}`);
    const download = (document: InvestorVerificationDocument) => (
        <a
            href={document.link.url}
            aria-label={t('admin.kyc.document.open', {
                name: document.filename,
            })}
            className={DOWNLOAD}
        >
            {t('admin.kyc.document.download')}
        </a>
    );

    return (
        <>
            {current.length > 0 && (
                <ul
                    aria-label={t('admin.kyc.document.current')}
                    className="mt-2.5 grid grid-cols-1 gap-2.5 sm:grid-cols-3"
                >
                    {current.map((document, index) => (
                        <li
                            key={document.id}
                            className="flex min-w-0 flex-col overflow-hidden rounded-[13px] border border-rz-hairline bg-rz-surface"
                        >
                            <button
                                type="button"
                                onClick={() => setOpen(index)}
                                aria-label={t('admin.kyc.document.view', {
                                    slot: slot(document),
                                })}
                                className="flex aspect-[4/3] items-center justify-center bg-[#eef2f8] dark:bg-rz-surface-sunken"
                            >
                                <Preview document={document} />
                            </button>
                            <div className="flex min-w-0 flex-1 flex-col gap-2 px-3 py-2.5">
                                <div className="min-w-0">
                                    <div className="text-[12.5px] font-semibold text-rz-ink">
                                        {slot(document)}
                                    </div>
                                    <div className="truncate text-[11px] text-rz-muted">
                                        {meta(document)}
                                    </div>
                                </div>
                                <div>{download(document)}</div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            {earlier.length > 0 && (
                <>
                    <h4
                        className={cn(
                            'mt-4 text-[11px] font-bold tracking-[.05em] uppercase',
                            LABEL,
                        )}
                    >
                        {t('admin.kyc.document.earlier')}
                    </h4>
                    <ul className="mt-2 overflow-hidden rounded-[13px] border border-rz-hairline bg-rz-surface">
                        {earlier.map((document, index) => (
                            <li
                                key={document.id}
                                className="flex items-center gap-3 border-b border-[#eef2f8] px-[15px] py-3 last:border-b-0 dark:border-rz-divider"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="text-[13px] font-semibold text-rz-ink">
                                        {slot(document)}{' '}
                                        <span className="ml-2 text-[11px] font-semibold text-rz-faint">
                                            {t('admin.kyc.document.replaced')}
                                        </span>
                                    </div>
                                    <div className="truncate text-[11.5px] text-rz-muted">
                                        {meta(document)}
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    onClick={() =>
                                        setOpen(current.length + index)
                                    }
                                    aria-label={t(
                                        'admin.kyc.document.view_file',
                                        { name: document.filename },
                                    )}
                                    className={DOWNLOAD}
                                >
                                    {t('admin.kyc.document.show')}
                                </button>
                                {download(document)}
                            </li>
                        ))}
                    </ul>
                </>
            )}
            <Viewer
                documents={ordered}
                index={open}
                onIndex={setOpen}
                slot={slot}
                meta={meta}
            />
        </>
    );
}

/** The file itself where the browser can draw it, or a PDF tile. */
function Preview({
    document,
    large = false,
}: {
    document: InvestorVerificationDocument;
    large?: boolean;
}) {
    const { t } = useTranslation();

    if (isImage(document)) {
        return (
            <img
                src={document.view.url}
                alt=""
                loading="lazy"
                className={cn(
                    'size-full object-contain',
                    large && 'max-h-[72vh]',
                )}
            />
        );
    }

    return (
        <span className="flex flex-col items-center gap-1.5 text-rz-muted">
            <FileText
                aria-hidden="true"
                className={large ? 'size-14' : 'size-8'}
            />
            <span className="text-[11px] font-bold tracking-[.05em] uppercase">
                {t('admin.kyc.document.pdf')}
            </span>
        </span>
    );
}

/**
 * The full-size viewer over the drawer. Escape closes only the viewer: its keydown is stopped
 * before the drawer's own Escape, which would close the case.
 */
function Viewer({
    documents,
    index,
    onIndex,
    slot,
    meta,
}: {
    documents: InvestorVerificationDocument[];
    index: number | null;
    onIndex: (index: number | null) => void;
    slot: (document: InvestorVerificationDocument) => string;
    meta: (document: InvestorVerificationDocument) => string;
}) {
    const { t } = useTranslation();
    const document = index === null ? undefined : documents[index];
    const total = documents.length;
    const position = index === null ? 0 : index + 1;
    const step = (by: number) =>
        index !== null && onIndex((index + by + total) % total);

    return (
        <DialogPrimitive.Root
            open={document !== undefined}
            onOpenChange={(open) => !open && onIndex(null)}
        >
            <DialogPrimitive.Portal>
                <DialogPrimitive.Overlay className="fixed inset-0 z-[60] bg-[rgba(8,14,28,.72)]" />
                <DialogPrimitive.Content
                    aria-describedby={undefined}
                    onEscapeKeyDown={(event) => event.stopPropagation()}
                    onKeyDown={(event) => {
                        if (event.key === 'ArrowLeft') {
                            step(-1);
                        } else if (event.key === 'ArrowRight') {
                            step(1);
                        }
                    }}
                    className="fixed inset-3 z-[61] flex flex-col overflow-hidden rounded-2xl bg-rz-page-console shadow-[0_26px_60px_-18px_rgba(16,32,58,.5)] sm:inset-8"
                >
                    {document && (
                        <>
                            <div className="flex items-center gap-3 border-b border-rz-hairline px-[18px] py-3">
                                <div className="min-w-0 flex-1">
                                    <DialogPrimitive.Title className="text-[15px] font-bold text-rz-ink">
                                        {slot(document)}{' '}
                                        {!document.current && (
                                            <span className="ml-2 text-[11px] font-semibold text-rz-faint">
                                                {t(
                                                    'admin.kyc.document.replaced',
                                                )}
                                            </span>
                                        )}
                                    </DialogPrimitive.Title>
                                    <p className="truncate text-[12px] text-rz-muted">
                                        {meta(document)}
                                    </p>
                                </div>
                                <span className="text-[12px] text-rz-muted tabular-nums">
                                    {t('admin.kyc.viewer.position', {
                                        index: position,
                                        total,
                                    })}
                                </span>
                                <a
                                    href={document.view.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className={DOWNLOAD}
                                >
                                    {t('admin.kyc.viewer.new_tab')}
                                </a>
                                <a
                                    href={document.link.url}
                                    aria-label={t('admin.kyc.document.open', {
                                        name: document.filename,
                                    })}
                                    className={DOWNLOAD}
                                >
                                    {t('admin.kyc.document.download')}
                                </a>
                                <DialogPrimitive.Close
                                    aria-label={t('admin.kyc.viewer.close')}
                                    className="flex size-[34px] items-center justify-center rounded-[10px] border border-rz-hairline bg-rz-surface text-rz-ink"
                                >
                                    <X aria-hidden="true" className="size-4" />
                                </DialogPrimitive.Close>
                            </div>
                            <div className="relative flex min-h-0 flex-1 items-center justify-center bg-[#eef2f8] p-4 dark:bg-rz-surface-sunken">
                                <Preview document={document} large />
                                {total > 1 && (
                                    <>
                                        <button
                                            type="button"
                                            onClick={() => step(-1)}
                                            aria-label={t(
                                                'admin.kyc.viewer.previous',
                                            )}
                                            className="absolute top-1/2 left-3 flex size-10 -translate-y-1/2 items-center justify-center rounded-full border border-rz-hairline bg-rz-surface text-rz-ink shadow-sm"
                                        >
                                            <ChevronLeft
                                                aria-hidden="true"
                                                className="size-5"
                                            />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => step(1)}
                                            aria-label={t(
                                                'admin.kyc.viewer.next',
                                            )}
                                            className="absolute top-1/2 right-3 flex size-10 -translate-y-1/2 items-center justify-center rounded-full border border-rz-hairline bg-rz-surface text-rz-ink shadow-sm"
                                        >
                                            <ChevronRight
                                                aria-hidden="true"
                                                className="size-5"
                                            />
                                        </button>
                                    </>
                                )}
                            </div>
                        </>
                    )}
                </DialogPrimitive.Content>
            </DialogPrimitive.Portal>
        </DialogPrimitive.Root>
    );
}
