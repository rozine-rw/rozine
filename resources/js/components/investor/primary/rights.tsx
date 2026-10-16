import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { Ordinals, UnitRights } from '@/types/settlement';

/** Unit ordinal ranges as "#1201–#1206, #1300", exactly as the server reserved them. */
export function formatOrdinals(ordinals: Ordinals): string {
    return ordinals
        .map((range) =>
            range.first === range.last
                ? `#${range.first}`
                : `#${range.first}–#${range.last}`,
        )
        .join(', ');
}

/**
 * The exact component rights of reserved or committed units (H3): the total return and each
 * instalment's principal and return, relative only. There is no due date before issue (H4); dates
 * are attached when the notes are issued.
 */
export function RightsTable({
    rights,
    className,
}: {
    rights: UnitRights;
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <div
            className={cn(
                'rounded-xl border border-rz-border bg-rz-surface px-3 py-2',
                className,
            )}
        >
            <table className="w-full text-left text-[11.5px]">
                <caption className="pb-1 text-left text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                    {t('investor.primary.rights.title')}
                </caption>
                <thead>
                    <tr className="text-rz-secondary">
                        <th scope="col" className="py-1 font-semibold">
                            {t('investor.primary.rights.instalment')}
                        </th>
                        <th
                            scope="col"
                            className="py-1 text-right font-semibold"
                        >
                            {t('investor.primary.rights.principal')}
                        </th>
                        <th
                            scope="col"
                            className="py-1 text-right font-semibold"
                        >
                            {t('investor.primary.rights.return')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {rights.instalments.map((instalment) => (
                        <tr
                            key={instalment.index}
                            className="border-t border-[#eef2f9] dark:border-rz-divider"
                        >
                            <td className="py-1 text-rz-slate">
                                {t('investor.primary.rights.nth', {
                                    n: instalment.index,
                                })}
                            </td>
                            <td className="py-1 text-right font-semibold text-rz-ink tabular-nums">
                                {formatRwf(instalment.principal)}
                            </td>
                            <td className="py-1 text-right font-semibold text-rz-ink tabular-nums">
                                {formatRwf(instalment.return)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
            <p className="mt-1 flex justify-between border-t border-[#eef2f9] pt-1.5 text-[11.5px] dark:border-rz-divider">
                <span className="text-rz-secondary">
                    {t('investor.primary.rights.total_return')}
                </span>
                <span className="font-bold text-rz-ink">
                    {formatRwf(rights.total_return)}
                </span>
            </p>
            <p className="mt-1 text-[10.5px] leading-[1.4] text-rz-secondary">
                {t('investor.primary.rights.undated')}
            </p>
        </div>
    );
}
