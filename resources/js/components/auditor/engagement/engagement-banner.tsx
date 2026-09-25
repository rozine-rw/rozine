import { Link } from '@inertiajs/react';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { EngagementSummary } from '@/types/auditor';

/**
 * The engagement summary a page carries (#96): a link to the agreement page while the current
 * terms still need this partner's acceptance, and a muted line while no usable terms are
 * published. Nothing shows once the current terms are accepted, or where the page carries no
 * summary. It only informs: whether an offer can be accepted is still that offer's own
 * `allowed_actions`.
 */
export function EngagementBanner({
    engagement,
    className,
}: {
    engagement: EngagementSummary | null | undefined;
    className?: string;
}) {
    const { t } = useTranslation();

    if (engagement?.status === 'required') {
        return (
            <Link
                href={engagement.link}
                className={cn(
                    'flex w-full items-center gap-3 rounded-2xl border-[1.5px] border-[#f0d18f] bg-rz-surface p-[13px] text-left dark:border-[rgba(240,160,96,.45)]',
                    className,
                )}
            >
                <span className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft text-[16px]">
                    <Icon name="document" tone="amber" />
                </span>
                <span className="min-w-0 flex-1 text-[13.5px] leading-[1.4] font-semibold text-rz-ink">
                    {t('auditor.engagement.banner.required')}
                </span>
                <span aria-hidden className="shrink-0 text-[16px] text-rz-ink">
                    →
                </span>
            </Link>
        );
    }

    if (engagement?.status === 'unavailable') {
        return (
            <p
                role="status"
                className={cn(
                    'text-[12.5px] leading-[1.45] text-rz-secondary',
                    className,
                )}
            >
                {t('auditor.engagement.banner.unavailable')}
            </p>
        );
    }

    return null;
}
