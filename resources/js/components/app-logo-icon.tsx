import type { HTMLAttributes } from 'react';

/**
 * The Rozine wing mark.
 *
 * Rendered as a CSS-masked element so it inherits the current text color
 * (via `fill-current` / `text-*` classes on the caller) and stays crisp at any
 * size, matching the favicon and app icon. The wing artwork is a wide (2:1)
 * mark, so `aspect-ratio` keeps it correctly proportioned whether the caller
 * sets a square size (`size-8`) or only a height (`h-10`).
 */
export default function AppLogoIcon({
    className,
    style,
    ...props
}: HTMLAttributes<HTMLSpanElement>) {
    return (
        <span
            {...props}
            aria-hidden="true"
            className={className}
            style={{
                display: 'inline-block',
                aspectRatio: '766 / 384',
                backgroundColor: 'currentColor',
                maskImage: 'url(/images/rozine-wing-white.png)',
                maskRepeat: 'no-repeat',
                maskPosition: 'center',
                maskSize: 'contain',
                WebkitMaskImage: 'url(/images/rozine-wing-white.png)',
                WebkitMaskRepeat: 'no-repeat',
                WebkitMaskPosition: 'center',
                WebkitMaskSize: 'contain',
                ...style,
            }}
        />
    );
}
