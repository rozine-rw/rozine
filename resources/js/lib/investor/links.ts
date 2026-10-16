import type { RouteLink } from '@/types';

/**
 * A server link with the investor's own choices added as query parameters (the quantity they
 * picked). The server still resolves price, fees and eligibility from its own records.
 */
export const withQuery = (
    link: RouteLink,
    params: Record<string, string | number>,
): RouteLink => {
    const query = new URLSearchParams(
        Object.entries(params).map(([key, value]) => [key, String(value)]),
    ).toString();

    return {
        ...link,
        url: `${link.url}${link.url.includes('?') ? '&' : '?'}${query}`,
    };
};
