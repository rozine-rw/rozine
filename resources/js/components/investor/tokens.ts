import type { BusinessAccent, InvestorRatingBand } from '@/types/investor';

/**
 * Investor-only colour lookups. Business accents come from the design's industry palette; the
 * deeper stop is the design's `darken()` pair, corrected where its map was broken (ink mapped to
 * brown, amber and magenta fell back to blue). Fills keep the same value in dark mode because
 * they always carry white text.
 */
export const ACCENT_FILL: Record<BusinessAccent, string> = {
    green: '#17795a',
    blue: '#1e3aff',
    amber: '#c2661f',
    purple: '#6425c9',
    teal: '#046a86',
    magenta: '#a81a5b',
    ink: '#0c1830',
};

const ACCENT_DEEP: Record<BusinessAccent, string> = {
    green: '#17795a',
    blue: '#1e3aff',
    amber: '#a55418',
    purple: '#6d28d9',
    teal: '#0e7490',
    magenta: '#8a1449',
    ink: '#1c2a45',
};

/** The design's banner: `linear-gradient(135deg, colour, darken(colour))`. */
export const accentBanner = (accent: BusinessAccent): string =>
    `linear-gradient(135deg, ${ACCENT_FILL[accent]} 0%, ${ACCENT_DEEP[accent]} 100%)`;

/**
 * Rating band colours from the shared core (`rzRate`): `text` reads on white or the tint,
 * `plate` carries white text, `tint` sits behind. Weak is the fourth published band, which the
 * core never reaches; it takes the amber pair. Dark text lifts for contrast on navy.
 */
export const RATING_STYLE: Record<
    InvestorRatingBand,
    { text: string; plate: string; tint: string }
> = {
    strong: {
        text: 'text-[#17795a] dark:text-[#3fcda0]',
        plate: '#0b5f30',
        tint: 'bg-[rgba(29,158,117,.10)] dark:bg-[rgba(63,205,160,.14)]',
    },
    stable: {
        text: 'text-[#1832c8] dark:text-[#99a3ff]',
        plate: '#0a3fa8',
        tint: 'bg-[rgba(30,58,255,.10)] dark:bg-[rgba(99,120,255,.16)]',
    },
    weak: {
        text: 'text-[#a55418] dark:text-[#f0a060]',
        plate: '#8a4a14',
        tint: 'bg-[rgba(194,102,31,.10)] dark:bg-[rgba(240,160,96,.14)]',
    },
    distressed: {
        text: 'text-[#9c3a0a] dark:text-[#ff8285]',
        plate: '#7a2d08',
        tint: 'bg-[rgba(229,72,77,.10)] dark:bg-[rgba(255,107,111,.14)]',
    },
};

/** Section-title green used for yields, "GET BACK" and incoming money. */
export const POSITIVE_TEXT = 'text-[#17795a] dark:text-[#3fcda0]';

/** Warm text on the amber callouts (arrears, concentration, overdue report). */
export const AMBER_TEXT = 'text-[#8a4a14] dark:text-[#f0a060]';
