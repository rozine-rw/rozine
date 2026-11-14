import { useTranslation } from '@/hooks/use-translation';
import { formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { C3AboutBusiness, C3DealFinancials } from '@/types/investor';

/**
 * EBITDA as the allowlist publishes it (H9): a figure only when it was sourced as EBITDA, and
 * otherwise "Unavailable" — never another measure standing in for it.
 */
export function EbitdaValue({
    ebitda,
    className,
}: {
    ebitda: C3DealFinancials['ebitda'];
    className: string;
}) {
    const { t } = useTranslation();

    if (ebitda.value === null) {
        return (
            <p className={className}>
                {t('investor.deal.ebitda_unavailable')}
                <span className="mt-0.5 block text-[10.5px] font-medium text-rz-secondary">
                    {t('investor.deal.ebitda_not_sourced')}
                </span>
            </p>
        );
    }

    return <p className={className}>{formatRwfShort(ebitda.value)}</p>;
}

/** The business's coarse approved description (H9): no address, team size or company code. */
export function AboutCard({
    about,
    story = null,
}: {
    about: C3AboutBusiness;
    /** The deal's own story, already shown above; the card doesn't repeat a description that matches it (#594). */
    story?: string | null;
}) {
    const { t } = useTranslation();
    const repeated = about.description.trim() === story?.trim();

    return (
        <div className="mt-2.5 rounded-xl border border-rz-border bg-rz-surface px-[13px] py-2.5">
            {!repeated && (
                <p className="text-xs leading-[1.55] text-rz-secondary">
                    {about.description}
                </p>
            )}
            <p
                className={cn(
                    'flex justify-between gap-2.5 text-xs',
                    !repeated &&
                        'mt-2 border-t border-[#eef2f9] pt-2 dark:border-rz-divider',
                )}
            >
                <span className="text-rz-secondary">
                    {t('investor.deal.industry')}
                </span>
                <span className="text-right font-semibold text-rz-ink">
                    {about.industry} · {about.district}
                </span>
            </p>
        </div>
    );
}
