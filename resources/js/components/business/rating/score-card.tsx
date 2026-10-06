import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { BusinessRatingProps } from '@/types/business';
import { BAND_ARC, BAND_TEXT } from './band';

type ScoreCardProps = Pick<BusinessRatingProps, 'rating' | 'refusal' | 'drift'>;

/**
 * The score ring card (design L818–838). Before the first audit it says the rating is pending;
 * a refusal lists the engine's reasons; a drift since the audit lists what moved it.
 */
export function ScoreCard({ rating, refusal, drift }: ScoreCardProps) {
    const { t } = useTranslation();
    const degrees =
        rating === null ? 0 : Math.round((Number(rating.score) / 5) * 360);
    const arc = rating === null ? '#e9edf4' : BAND_ARC[rating.band];

    return (
        <div className="mt-[18px] flex items-center gap-4 rounded-2xl border border-rz-border bg-rz-surface p-[18px]">
            <div
                aria-hidden
                className="flex size-[82px] shrink-0 items-center justify-center rounded-full"
                style={{
                    background: `conic-gradient(${arc} ${degrees}deg, var(--rz-border) 0)`,
                }}
            >
                <div className="flex size-16 flex-col items-center justify-center rounded-full bg-rz-surface">
                    <span
                        className={cn(
                            'text-[22px] leading-none font-bold',
                            rating === null
                                ? 'text-rz-secondary'
                                : BAND_TEXT[rating.band],
                        )}
                    >
                        {rating === null ? '—' : rating.score}
                    </span>
                    <span className="text-[10.5px] font-semibold text-rz-secondary">
                        {t('business.rating.out_of')}
                    </span>
                </div>
            </div>
            <div className="flex-1">
                <p className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                    {t('business.rating.title')}
                </p>
                <p
                    className={cn(
                        'mt-0.5 text-[17px] font-bold',
                        rating === null
                            ? 'text-rz-secondary'
                            : BAND_TEXT[rating.band],
                    )}
                >
                    {rating === null
                        ? t('business.rating.pending')
                        : `${t(`business.rating.band.${rating.band}`)} · ${rating.score}`}
                </p>
                <p className="mt-[5px] text-[11.5px] leading-normal text-rz-secondary">
                    {t('business.rating.explainer')}
                </p>
                {drift !== null && rating !== null && (
                    <div className="mt-[11px] rounded-xl border border-[#fbe4cc] bg-[#fff8f1] px-3 py-[11px] dark:border-transparent dark:bg-[rgba(194,102,31,.12)]">
                        <p className="text-[11.5px] font-bold text-[#8a4a14] dark:text-[#f0a060]">
                            {t('business.rating.drift', {
                                audited: `${t(`business.rating.band.${drift.audited.band}`)} ${drift.audited.score}`,
                                now: `${t(`business.rating.band.${rating.band}`)} ${rating.score}`,
                            })}
                        </p>
                        <ul className="mt-[7px] flex flex-col gap-1">
                            {drift.moves.map((move) => (
                                <li
                                    key={move.reason}
                                    className="flex items-baseline justify-between gap-2.5"
                                >
                                    <span className="min-w-0 truncate text-[11px] text-[#7a6a55] dark:text-rz-secondary">
                                        {move.reason}
                                    </span>
                                    <span
                                        className={cn(
                                            'shrink-0 text-[11px] font-bold',
                                            move.delta.startsWith('-')
                                                ? 'text-[#a0524f] dark:text-[#ff8285]'
                                                : 'text-rz-accent-app-text',
                                        )}
                                    >
                                        {move.delta}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
                {refusal !== null && (
                    <div
                        role="status"
                        className="mt-[11px] rounded-xl border border-[#fdeaea] bg-[rgba(229,72,77,.08)] px-3 py-[11px] dark:border-[rgba(255,107,111,.25)]"
                    >
                        <p className="text-[11.5px] font-bold text-rz-danger-text">
                            {t('business.rating.refused')}
                        </p>
                        <ul className="mt-1.5 list-disc pl-4 text-[11px] leading-normal text-rz-slate">
                            {refusal.reasons.map((reason) => (
                                <li key={reason}>{reason}</li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </div>
    );
}
