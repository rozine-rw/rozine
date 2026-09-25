import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteLink } from '@/types';
import type {
    BusinessIdentity,
    BusinessRating,
    RatingBand,
} from '@/types/business';

/** The band's deep tone, behind the score disc on the green hero. */
const BAND_DISC: Record<RatingBand, string> = {
    strong: 'bg-[#17795a]',
    stable: 'bg-[#1832c8]',
    weak: 'bg-[#c2661f]',
    distressed: 'bg-[#b3383c]',
};

type BusinessHeroProps = {
    business: BusinessIdentity;
    rating: BusinessRating | null;
    ratingLink: RouteLink;
};

/**
 * The green business card with its rating band (design L125–148). The ring and disc show the
 * published score only; before the first audit the band reads as pending instead of a number.
 */
export function BusinessHero({
    business,
    rating,
    ratingLink,
}: BusinessHeroProps) {
    const { t } = useTranslation();
    const degrees = rating === null ? 0 : (Number(rating.score) / 5) * 360;

    return (
        <div className="relative overflow-hidden rounded-[20px] bg-[#0b7a3f] p-4 shadow-[inset_0_0_0_1px_rgba(255,255,255,.14)]">
            <div className="relative flex items-center gap-3">
                <div className="flex size-[52px] shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-black/14 text-[22px] font-bold text-white">
                    {business.name.trim().charAt(0).toUpperCase()}
                </div>
                <div className="min-w-0 flex-1">
                    <p className="truncate text-[17px] leading-[1.2] font-bold tracking-[-.2px] text-white">
                        {business.name}
                    </p>
                    {business.company_code !== null && (
                        <p className="mt-[3px] truncate text-xs text-white">
                            {t('business.home.company_code', {
                                code: business.company_code,
                            })}
                        </p>
                    )}
                    <p className="mt-px truncate text-xs text-white">
                        {business.industry} · {business.district}
                    </p>
                </div>
            </div>

            <Link
                href={ratingLink}
                className="relative mt-4 flex w-full items-center gap-[13px] rounded-2xl border border-white/18 bg-black/14 px-[13px] py-[11px] text-left"
            >
                <span
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-full"
                    style={{
                        background: `conic-gradient(#8fe3b0 ${degrees}deg, rgba(255,255,255,.22) 0deg)`,
                    }}
                >
                    <span
                        className={`flex size-9 items-center justify-center rounded-full text-[13px] font-bold text-white ${
                            rating === null
                                ? 'bg-[#0b7a3f]'
                                : BAND_DISC[rating.band]
                        }`}
                    >
                        {rating?.score ?? '—'}
                    </span>
                </span>
                <span className="min-w-0 flex-1">
                    <span className="block text-[10.5px] font-bold tracking-[.07em] text-white uppercase">
                        {t('business.home.rozine_rating')}
                    </span>
                    <span className="mt-0.5 block text-base leading-[1.1] font-bold whitespace-nowrap text-white">
                        {rating === null
                            ? t('business.rating.pending')
                            : `${t(`business.rating.band.${rating.band}`)} · ${rating.score}`}
                    </span>
                </span>
                <span aria-hidden className="shrink-0 text-lg text-white">
                    ›
                </span>
            </Link>
        </div>
    );
}
