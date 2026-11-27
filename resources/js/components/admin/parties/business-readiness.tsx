import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { ReadinessRequirementRow } from '@/types/admin';

const TONE: Record<ReadinessRequirementRow['status'], string> = {
    met: 'text-[#157357] dark:text-[#4fd1a5]',
    due: 'text-[#9e5a1c] dark:text-[#f0a060]',
    overdue: 'text-[#b3352a] dark:text-[#ff6b6f]',
    lapsed: 'text-[#b3352a] dark:text-[#ff6b6f]',
};

/**
 * A Business's readiness requirements in the staff 360 (BRS v3.0 RDY-R03, APP-D01 readiness
 * monitor): each requirement triggered so far, its status and the date that matters for it, so
 * staff can see what the Business is still due on. Read-only.
 */
export function BusinessReadiness({
    requirements,
    section,
}: {
    requirements: ReadinessRequirementRow[];
    section: string;
}) {
    const { t, locale } = useTranslation();

    return (
        <section
            aria-label={t('admin.parties.readiness.title')}
            className="border-t border-[#e3e9f3] pt-[18px] dark:border-rz-border"
        >
            <h3 className={cn(section, 'mb-2.5')}>
                {t('admin.parties.readiness.title')}
            </h3>
            {requirements.length === 0 ? (
                <p className="text-[12.5px] text-rz-muted">
                    {t('admin.parties.readiness.empty')}
                </p>
            ) : (
                <ul className="flex flex-col gap-2">
                    {requirements.map((requirement) => (
                        <li
                            key={requirement.code}
                            className="flex items-start justify-between gap-3 text-[12.5px]"
                        >
                            <span className="min-w-0 text-rz-ink">
                                {t(
                                    `business.readiness.requirement.${requirement.code}.title` as MessageCode,
                                )}
                            </span>
                            <span
                                className={cn(
                                    'shrink-0 text-right font-semibold',
                                    TONE[requirement.status],
                                )}
                            >
                                {t(
                                    `business.readiness.requirement.status.${requirement.status}`,
                                )}
                                {dateOf(requirement) !== null && (
                                    <span className="block text-[11.5px] font-normal text-rz-muted">
                                        {formatDate(
                                            dateOf(requirement) ?? '',
                                            locale,
                                        )}
                                    </span>
                                )}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

/** The date that matters: when it was met, or when it falls due. */
function dateOf(requirement: ReadinessRequirementRow): string | null {
    return requirement.status === 'met'
        ? requirement.met_on
        : requirement.due_on;
}
