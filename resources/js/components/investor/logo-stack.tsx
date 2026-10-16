import { STAR_PATH, WORDMARK_PATHS } from '@/components/rozine/logo';

/**
 * The stacked lockup: the star centred above the wordmark. The design's `logo-stack-*.png` export
 * is broken (it carries a second, leading star on the wordmark), so this draws the stack from the
 * same brand paths as `LogoLockup` instead. Paints with `currentColor`; always named, because it
 * stands alone on the auth screens.
 */
export function LogoStack({
    title,
    className,
}: {
    title: string;
    className?: string;
}) {
    return (
        <svg
            viewBox="2779 -766 3766 2663"
            fill="currentColor"
            role="img"
            aria-label={title}
            className={className}
        >
            <path d={STAR_PATH} transform="translate(2900.5 -1366)" />
            {WORDMARK_PATHS.map((path) => (
                <path key={path} d={path} />
            ))}
        </svg>
    );
}
