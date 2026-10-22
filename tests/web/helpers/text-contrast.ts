type Colour = [number, number, number, number];

function colour(value: string): Colour {
    if (/^#[0-9a-f]{6}(?:[0-9a-f]{2})?$/iu.test(value)) {
        return [
            parseInt(value.slice(1, 3), 16),
            parseInt(value.slice(3, 5), 16),
            parseInt(value.slice(5, 7), 16),
            value.length === 9 ? parseInt(value.slice(7, 9), 16) / 255 : 1,
        ];
    }

    const match = /^rgba?\(([^)]+)\)$/u.exec(value);
    const channels = match?.[1].split(',').map(Number);

    if (!channels || channels.length < 3 || channels.some(Number.isNaN)) {
        throw new Error(`Unsupported test colour: ${value}`);
    }

    return [channels[0], channels[1], channels[2], channels[3] ?? 1];
}

function over(foreground: Colour, background: Colour): Colour {
    return [
        foreground[0] * foreground[3] + background[0] * (1 - foreground[3]),
        foreground[1] * foreground[3] + background[1] * (1 - foreground[3]),
        foreground[2] * foreground[3] + background[2] * (1 - foreground[3]),
        1,
    ];
}

function luminance(value: Colour): number {
    const [red, green, blue] = value.slice(0, 3).map((channel) => {
        const scaled = channel / 255;

        return scaled <= 0.04045
            ? scaled / 12.92
            : ((scaled + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
}

/** Resolve the rendered control's literal Tailwind text colour and inline fill, including alpha. */
export function textContrastOnSurface(
    element: HTMLElement,
    theme: 'light' | 'dark',
    surface: string,
): number {
    const classes = [...element.classList];
    const literal = (prefix: string) =>
        classes
            .map(
                (name) =>
                    new RegExp(
                        `^${prefix}text-\\[(#[0-9a-f]{6})\\]$`,
                        'iu',
                    ).exec(name)?.[1],
            )
            .find(Boolean);
    const foreground =
        element.style.color ||
        (theme === 'dark' ? literal('dark:') : undefined) ||
        literal('') ||
        (classes.includes('text-white') ? '#ffffff' : undefined);

    if (!foreground || !element.style.backgroundColor) {
        throw new Error(
            'A control must expose its actual literal text colour and inline fill.',
        );
    }

    const background = over(
        colour(element.style.backgroundColor),
        colour(surface),
    );
    const text = over(colour(foreground), background);
    const [light, dark] = [luminance(text), luminance(background)].sort(
        (a, b) => b - a,
    );

    return (light + 0.05) / (dark + 0.05);
}
