import type { RatingBand } from '@/types/business';

/** The band's colour as text on a white card, lifted for dark surfaces. */
export const BAND_TEXT: Record<RatingBand, string> = {
    strong: 'text-[#17795a] dark:text-[#3fcda0]',
    stable: 'text-[#1832c8] dark:text-[#99a3ff]',
    weak: 'text-[#c2661f] dark:text-[#f0a060]',
    distressed: 'text-[#b3383c] dark:text-[#ff8285]',
};

/** The same colour for the score ring's filled arc. */
export const BAND_ARC: Record<RatingBand, string> = {
    strong: '#17795a',
    stable: '#1832c8',
    weak: '#c2661f',
    distressed: '#b3383c',
};
