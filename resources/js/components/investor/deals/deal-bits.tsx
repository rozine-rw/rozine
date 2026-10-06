import { useState } from 'react';
import type { ReactNode } from 'react';
import { accentBanner, RATING_STYLE } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type {
    BusinessAccent,
    EvidencePhoto,
    InvestorRating,
} from '@/types/investor';

/** Glass rating badge on a photo: the band leads, the score rides a plate (SwipeCard L41–44). */
export function RatingGlassBadge({
    rating,
    variant = 'card',
}: {
    rating: InvestorRating;
    variant?: 'card' | 'desk';
}) {
    const { t } = useTranslation();

    return (
        <span
            className={cn(
                'flex items-center rounded-full py-0.5 pr-[3px] pl-[9px] backdrop-blur-[10px]',
                variant === 'card'
                    ? 'gap-[7px] border border-white/20 bg-[rgba(8,16,34,.5)] shadow-[0_6px_16px_-8px_rgba(0,0,0,.55)]'
                    : 'gap-1.5 bg-[rgba(8,16,34,.62)] backdrop-blur-[8px]',
            )}
        >
            <span className="text-[10.5px] leading-none font-medium tracking-[.08em] whitespace-nowrap text-white uppercase">
                {t(`investor.rating.${rating.band}`)}
            </span>
            <span
                className={cn(
                    'inline-flex items-center justify-center rounded-full text-[10.5px] font-semibold text-white',
                    variant === 'card'
                        ? 'h-[17px] min-w-6 px-1.5 leading-none'
                        : 'px-[7px] py-0.5',
                )}
                style={{ background: RATING_STYLE[rating.band].plate }}
            >
                {rating.score}
            </span>
        </span>
    );
}

/** "AUDITED" glass pill with the gold shield (SwipeCard L35–38). */
export function AuditedBadge() {
    const { t } = useTranslation();

    return (
        <span className="inline-flex items-center gap-[5px] rounded-full border border-white/20 bg-[rgba(8,16,34,.5)] px-[9px] py-[3px] shadow-[0_6px_16px_-8px_rgba(0,0,0,.55)] backdrop-blur-[10px]">
            <svg
                viewBox="0 0 20 20"
                fill="#e6c458"
                aria-hidden
                className="size-[11px]"
            >
                <path
                    fillRule="evenodd"
                    clipRule="evenodd"
                    d="M10 1.944A11.954 11.954 0 012.166 5 12.02 12.02 0 0010 18.2 12.02 12.02 0 0017.834 5 11.954 11.954 0 0110 1.944zM13.707 8.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                />
            </svg>
            <span className="text-[10.5px] leading-none font-semibold tracking-[.4px] text-white">
                {t('investor.deals.audited')}
            </span>
        </span>
    );
}

/** "JUST LISTED" green pill (SwipeCard L29–32). */
export function JustListedBadge() {
    const { t } = useTranslation();

    return (
        <span className="inline-flex items-center gap-1 rounded-full bg-[rgba(20,105,80,.94)] px-2 py-[3px]">
            <span className="size-[5px] rounded-full bg-white" />
            <span className="text-[10.5px] leading-none font-extrabold tracking-[.07em] text-white">
                {t('investor.deals.just_listed')}
            </span>
        </span>
    );
}

/** The blue verified rosette beside a business name (SwipeCard L67). */
export function VerifiedRosette({ className }: { className: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden className={className}>
            <path
                d="M12 2.2l2.35 1.7 2.9-.05 1.05 2.7 2.45 1.55-.95 2.75.95 2.75-2.45 1.55-1.05 2.7-2.9-.05L12 21.8l-2.35-1.7-2.9.05-1.05-2.7L3.25 15.85l.95-2.75-.95-2.75L5.7 8.8l1.05-2.7 2.9.05z"
                fill="#1e3aff"
            />
            <path
                d="M8.6 12.2l2.2 2.2 4.5-4.6"
                stroke="#fff"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

/** A filed photo, or the business's accent gradient while no file is available. */
export function PhotoFill({
    photo,
    accent,
}: {
    photo: EvidencePhoto | undefined;
    accent: BusinessAccent;
}) {
    return (
        <span
            className="absolute inset-0 block"
            style={{ background: accentBanner(accent) }}
        >
            {photo?.url != null && (
                <img
                    src={photo.url}
                    alt=""
                    className="size-full object-cover"
                    draggable={false}
                />
            )}
        </span>
    );
}

type PhotoStripProps = {
    photos: EvidencePhoto[];
    accent: BusinessAccent;
    name: string;
    /** Dot row offset and look differ between the phone card and the desk card. */
    variant: 'card' | 'desk';
    children?: ReactNode;
};

/**
 * The card's photo zone: one filed photo at a time with arrows and dots. Browsing photos never
 * opens the deal (SwipeCard L20–58).
 */
export function PhotoStrip({
    photos,
    accent,
    name,
    variant,
    children,
}: PhotoStripProps) {
    const { t } = useTranslation();
    const [index, setIndex] = useState(0);
    const count = Math.max(photos.length, 1);
    const current = photos[index];
    const arrow =
        variant === 'card'
            ? 'size-[30px] border border-white/20 bg-[rgba(8,16,34,.4)] backdrop-blur-[6px]'
            : 'size-6 bg-[rgba(8,16,34,.30)] opacity-75 backdrop-blur-[6px]';

    return (
        <>
            <PhotoFill photo={current} accent={accent} />
            {children}
            {count > 1 && (
                <>
                    <button
                        type="button"
                        aria-label={t('investor.deals.photo_previous', {
                            name,
                        })}
                        disabled={index === 0}
                        onClick={() => setIndex(index - 1)}
                        className={cn(
                            'absolute top-1/2 z-[3] flex -translate-y-1/2 items-center justify-center rounded-full disabled:opacity-0',
                            variant === 'card' ? 'left-[9px]' : 'left-2.5',
                            arrow,
                        )}
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className={
                                variant === 'card'
                                    ? 'size-[15px]'
                                    : 'size-[13px]'
                            }
                        >
                            <path
                                d="M15 5l-7 7 7 7"
                                stroke="#fff"
                                strokeWidth="2.4"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                    </button>
                    <button
                        type="button"
                        aria-label={t('investor.deals.photo_next', { name })}
                        disabled={index === count - 1}
                        onClick={() => setIndex(index + 1)}
                        className={cn(
                            'absolute top-1/2 z-[3] flex -translate-y-1/2 items-center justify-center rounded-full disabled:opacity-0',
                            variant === 'card' ? 'right-[9px]' : 'right-2.5',
                            arrow,
                        )}
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className={
                                variant === 'card'
                                    ? 'size-[15px]'
                                    : 'size-[13px]'
                            }
                        >
                            <path
                                d="M9 5l7 7-7 7"
                                stroke="#fff"
                                strokeWidth="2.4"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                    </button>
                </>
            )}
            <div
                aria-hidden
                className={cn(
                    'pointer-events-none absolute inset-x-0 z-[2] flex justify-center',
                    variant === 'card'
                        ? 'bottom-[74px] gap-[5px]'
                        : 'bottom-16 gap-1',
                )}
            >
                {Array.from({ length: count }, (_, dot) => (
                    <span
                        key={dot}
                        className={cn(
                            'shrink-0 transition-all duration-200',
                            variant === 'card'
                                ? 'h-[5px] rounded-[3px]'
                                : 'h-1 rounded-[10px]',
                            dot === index
                                ? variant === 'card'
                                    ? 'w-4 bg-white'
                                    : 'w-[18px] bg-white'
                                : 'w-[5px] bg-white/50',
                        )}
                    />
                ))}
            </div>
        </>
    );
}
