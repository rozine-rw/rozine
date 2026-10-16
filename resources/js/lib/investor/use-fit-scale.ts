import { useLayoutEffect, useState } from 'react';

/**
 * The design's Deals canvas rule (`_dskFit`, Investor L7048): the desktop Deals screen is drawn on
 * a canvas `height` tall and scaled down to fit a shorter pane, its width widened by the same
 * factor, so the deck, invest bar and detail panel keep their proportions at 1113×750 and grow
 * naturally in a taller window. Attach `frame` as the callback ref of the element to fit into.
 */
export function useFitScale(height: number): {
    frame: (element: HTMLDivElement | null) => void;
    scale: number;
    width: number | null;
} {
    const [element, frame] = useState<HTMLDivElement | null>(null);
    const [fit, setFit] = useState<{ scale: number; width: number | null }>({
        scale: 1,
        width: null,
    });

    useLayoutEffect(() => {
        if (element === null || typeof ResizeObserver === 'undefined') {
            return;
        }

        const measure = () => {
            const scale = Math.min(1, element.clientHeight / height);

            if (scale > 0 && element.clientWidth > 1) {
                setFit({
                    scale: Math.round(scale * 1000) / 1000,
                    width: Math.round(element.clientWidth / scale),
                });
            }
        };

        const observer = new ResizeObserver(measure);

        observer.observe(element);
        measure();

        return () => observer.disconnect();
    }, [element, height]);

    return { frame, ...fit };
}
