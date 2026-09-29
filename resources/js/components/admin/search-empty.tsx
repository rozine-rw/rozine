import { TONAL } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AdminSection } from '@/types/admin';

/** "No matches on this page" (design T184–190): the top-bar search found nothing here. */
export function SearchEmpty({
    section,
    term,
}: {
    section: AdminSection;
    term: string;
}) {
    const { t } = useTranslation();

    return (
        <div
            role="status"
            className={cn(
                'mb-5 flex items-center gap-[13px] rounded-[14px] border border-rz-hairline px-[18px] py-4',
                TONAL,
            )}
        >
            <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden
                className="shrink-0"
            >
                <circle
                    cx="11"
                    cy="11"
                    r="7"
                    stroke="#9aa3b5"
                    strokeWidth="1.8"
                />
                <path
                    d="m20 20-3-3"
                    stroke="#9aa3b5"
                    strokeWidth="1.8"
                    strokeLinecap="round"
                />
            </svg>
            <div>
                <div className="text-[14px] font-semibold text-rz-ink">
                    {t('admin.search.empty_title')}
                </div>
                <div className="text-[12.5px] text-[#7b8699] dark:text-rz-muted">
                    {t('admin.search.empty_body', {
                        section: t(`admin.section.${section}.title`),
                        term,
                    })}
                </div>
            </div>
        </div>
    );
}
