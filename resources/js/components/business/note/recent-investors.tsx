import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatRwf } from '@/lib/rozine/format';
import type { RouteLink } from '@/types';
import type { NoteInvestor } from '@/types/business';

/** Avatar colours in the design's order (L3753–3758). */
const AVATAR = [
    'bg-[#1e3aff]',
    'bg-rz-accent-fill',
    'bg-[#0c1830] dark:bg-[#2a3a5c]',
    'bg-[#6425c9]',
];

/** "Recent investors" (design L696–707): the latest holders, as the server discloses them. */
export function RecentInvestors({
    investors,
    total,
    viewAll,
}: {
    investors: NoteInvestor[];
    total: number;
    viewAll: RouteLink | null;
}) {
    const { t } = useTranslation();

    if (investors.length === 0) {
        return null;
    }

    return (
        <>
            <div className="mt-[18px] flex items-center justify-between">
                <h2 className="text-base font-semibold text-rz-ink">
                    {t('business.note.investors.title')}
                </h2>
                {viewAll !== null && (
                    <Link
                        href={viewAll}
                        className="text-[12.5px] font-semibold text-rz-accent-app-text"
                    >
                        {t('business.note.investors.view_all', {
                            count: formatCount(total),
                        })}
                    </Link>
                )}
            </div>
            <ul className="mt-3 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {investors.map((investor, index) => (
                    <li
                        key={`${investor.name}-${index}`}
                        className="flex items-center gap-3 border-b border-[#eef2f9] px-[15px] py-[13px] last:border-b-0 dark:border-rz-divider"
                    >
                        <span
                            aria-hidden
                            className={`flex size-[34px] items-center justify-center rounded-full text-xs font-semibold text-white ${AVATAR[index % AVATAR.length]}`}
                        >
                            {investor.initials}
                        </span>
                        <span className="flex-1">
                            <span className="block text-[13.5px] font-semibold text-rz-ink">
                                {investor.name}
                            </span>
                            <span className="block text-[11.5px] text-rz-secondary">
                                {t(
                                    `business.note.investors.kind.${investor.kind}`,
                                )}
                            </span>
                        </span>
                        <span className="text-[13.5px] font-semibold text-rz-ink">
                            {formatRwf(investor.amount)}
                        </span>
                    </li>
                ))}
            </ul>
        </>
    );
}
