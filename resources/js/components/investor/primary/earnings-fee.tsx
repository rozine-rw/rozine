import { useTranslation } from '@/hooks/use-translation';
import { formatBpsPct } from '@/lib/investor/format';
import { cn } from '@/lib/utils';
import type { EarningsFee } from '@/types/settlement';

type Translate = ReturnType<typeof useTranslation>['t'];

/**
 * The locked Plus rate and its tier, "10.0% (Standard)", exactly as the server sent them. The
 * client formats the rate and never derives a tier or a fee from it (provisional; see `EarningsFee`).
 */
export function earningsFeeRate(t: Translate, fee: EarningsFee): string {
    return t('investor.plus.fee_rate', {
        rate: formatBpsPct(fee.rate_bps),
        tier: t(`investor.plus.tier.${fee.tier}`),
    });
}

/** The fee line's label: "Fee on earnings · 10.0% (Standard)". */
export function earningsFeeLabel(t: Translate, fee: EarningsFee): string {
    return t('investor.plus.fee_label', { rate: earningsFeeRate(t, fee) });
}

/** What the fee applies to and that its rate is fixed, shown under the fee at checkout (#99). */
export function EarningsFeeNote({ className }: { className?: string }) {
    const { t } = useTranslation();

    return (
        <p
            className={cn(
                'text-[11px] leading-[1.45] text-rz-secondary',
                className,
            )}
        >
            {t('investor.plus.fee_note')}
        </p>
    );
}
