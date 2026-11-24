import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { LegalDocumentLink, LegalDocumentText } from '@/types/legal';

/** Where a link from one document to another opens instead of the public page, per document. */
export type LegalDocumentTargets = Partial<
    Record<LegalDocumentLink['key'], string>
>;

/**
 * The document's HTML with its links to the other documents (`/terms`, `/privacy`,
 * `/disclaimers`) pointed at `targets`, so a reader inside the app stays there.
 */
function withTargets(html: string, targets: LegalDocumentTargets): string {
    return html.replace(
        /href="\/(terms|privacy|disclaimers)"/gu,
        (link, key: LegalDocumentLink['key']) =>
            targets[key] === undefined ? link : `href="${targets[key]}"`,
    );
}

/** Reading styles for the rendered Markdown of a legal document. */
const PROSE = cn(
    'text-[15px] leading-[1.65] text-rz-ink',
    '[&_h1]:text-2xl [&_h1]:leading-tight [&_h1]:font-semibold sm:[&_h1]:text-3xl',
    '[&_h1+p]:mt-2 [&_h1+p]:text-[13px] [&_h1+p]:text-rz-secondary',
    '[&_h2]:mt-9 [&_h2]:text-lg [&_h2]:font-semibold',
    '[&_li]:mt-2 [&_ol]:mt-3 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mt-3 [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-5',
    '[&_blockquote]:mt-3 [&_blockquote]:rounded-xl [&_blockquote]:border-l-4 [&_blockquote]:border-rz-accent-fill [&_blockquote]:bg-rz-page [&_blockquote]:px-4 [&_blockquote]:py-2 [&_blockquote_p]:mt-1',
    '[&_a]:font-semibold [&_a]:text-rz-accent-app-text [&_a]:underline [&_a]:underline-offset-4',
    '[&_code]:rounded [&_code]:bg-rz-page [&_code]:px-1 [&_code]:text-[13px]',
    '[&_table]:mt-4 [&_table]:block [&_table]:w-full [&_table]:overflow-x-auto [&_table]:text-sm [&_td]:border-t [&_td]:border-rz-border [&_td]:px-2 [&_td]:py-2 [&_td]:align-top [&_th]:px-2 [&_th]:py-2 [&_th]:text-left [&_th]:font-semibold',
);

/**
 * The full wording of the Terms, the Privacy Notice or the Disclaimers, exactly as issued, with its
 * version and the fingerprint a consent records. The public pages and every role's profile read
 * the document through this, so a person never has to leave the app to read it. A target that is
 * an in-page `#id` of a closed `<details>` opens it on the way.
 */
export function LegalDocumentBody({
    document,
    className,
    targets,
}: {
    document: LegalDocumentText;
    className?: string;
    targets: LegalDocumentTargets;
}) {
    const { t } = useTranslation();

    return (
        <>
            <article
                aria-label={document.title}
                className={cn(PROSE, className)}
                // The wording is the server's own published Markdown, rendered with raw HTML stripped.
                dangerouslySetInnerHTML={{
                    __html: withTargets(document.html, targets),
                }}
                onClick={(event) => {
                    const hash = (event.target as Element)
                        .closest('a')
                        ?.getAttribute('href');
                    const target = hash?.startsWith('#')
                        ? window.document.getElementById(hash.slice(1))
                        : null;

                    if (target instanceof HTMLDetailsElement) {
                        target.open = true;
                    }
                }}
            />
            <p className="mt-4 text-xs leading-normal break-all text-rz-secondary">
                {t('legal.fingerprint', {
                    version: document.version,
                    hash: document.sha256,
                })}
            </p>
        </>
    );
}
