import { Link } from '@inertiajs/react';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteLink } from '@/types';

/**
 * Another submitted application of this business is still under review, so this draft cannot
 * be evaluated or submitted yet (one at a time in C2). It links to that application.
 */
export function PendingReview({
    link,
    className,
}: {
    link: RouteLink;
    className: string;
}) {
    const { t } = useTranslation();

    return (
        <div
            role="status"
            className={`flex items-start gap-3 rounded-2xl border border-[#dbe7ff] bg-rz-surface p-4 dark:border-rz-border ${className}`}
        >
            <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                <Icon name="hourglass" />
            </span>
            <p className="flex-1 text-xs leading-[1.55] text-rz-secondary">
                {t('business.apply.outcome.refused.APPLICATION_PENDING_REVIEW')}{' '}
                <Link href={link} className="font-bold text-rz-accent-app-text">
                    {t('business.apply.pending_review.link')}
                </Link>
            </p>
        </div>
    );
}
