import { createElement } from 'react';
import { ICON_SHAPES } from '@/components/rozine/icon-shapes';
import type { IconName } from '@/components/rozine/icon-shapes';

export type { IconName };

export type IconTone = 'blue' | 'green' | 'amber' | 'red' | 'purple' | 'white';

type GradientStops = Record<Exclude<IconTone, 'white'>, [string, string]>;

/** The design's canonical gradient stops (`rozine-icons.js` DEFS). */
const STOPS: GradientStops = {
    blue: ['#5b74ff', '#1832d6'],
    green: ['#3fcda0', '#1d9e75'],
    amber: ['#ffc44f', '#c2661f'],
    red: ['#ff8285', '#e5484d'],
    purple: ['#b199fb', '#7c3aed'],
};

/** The Business app ships its own two stops; every other app uses the canonical set. */
const APP_STOPS: Partial<Record<'business', Partial<GradientStops>>> = {
    business: { blue: ['#5b74ff', '#1e3aff'], red: ['#ff8285', '#b3383c'] },
};

/**
 * Declares the icon gradients once per page. Icons reference them by id, so an app shell renders
 * this exactly once, before any icon paints.
 */
export function IconGradients({ app }: { app?: 'business' }) {
    const stops = { ...STOPS, ...(app ? APP_STOPS[app] : undefined) };

    return (
        <svg
            width="0"
            height="0"
            aria-hidden
            className="pointer-events-none absolute"
        >
            <defs>
                {Object.entries(stops).map(([tone, [from, to]]) => (
                    <linearGradient
                        key={tone}
                        id={`rz-g-${tone}`}
                        x1="0"
                        y1="0"
                        x2="1"
                        y2="1"
                    >
                        <stop offset="0" stopColor={from} />
                        <stop offset="1" stopColor={to} />
                    </linearGradient>
                ))}
            </defs>
        </svg>
    );
}

type IconProps = {
    name: IconName;
    tone?: IconTone;
    className?: string;
};

/** One Rozine line icon at `1em`, so it takes its size from the surrounding font size. */
export function Icon({ name, tone = 'blue', className }: IconProps) {
    const paint = tone === 'white' ? '#ffffff' : `url(#rz-g-${tone})`;

    return (
        <svg
            viewBox="0 0 24 24"
            width="1em"
            height="1em"
            fill="none"
            stroke={paint}
            strokeWidth={1.7}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden
            className={className ?? 'inline-block shrink-0 align-middle'}
        >
            {ICON_SHAPES[name].map(([tag, attributes, fill], index) =>
                createElement(tag, {
                    key: index,
                    ...attributes,
                    ...(fill === undefined
                        ? {}
                        : {
                              fill: paint,
                              stroke: 'none',
                              fillOpacity: fill === 'mass' ? 0.16 : undefined,
                          }),
                }),
            )}
        </svg>
    );
}
