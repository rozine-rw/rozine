/**
 * Redraws a Pulse pass onto a canvas so a visitor can keep it.
 *
 * The card on screen is a stack of DOM layers, which a browser will not hand
 * back as an image, so it is painted again here. Every box below is the one
 * the rendered card measures at its full 462px width.
 */

export type PassCardStat = {
    label: string;
    value: string;
};

export type PassCardSpec = {
    tone: 'investor' | 'business';
    tag: string;
    holder: string;
    caption?: string;
    amount: string;
    stats: PassCardStat[];
};

const WIDTH = 462;

const HEIGHT = WIDTH / 1.585;

const SCALE = 3;

const RADIUS = 20;

const EDGE = 16;

const LAYOUT = {
    logo: { x: EDGE, y: 13, height: 17 },
    tag: {
        top: 13,
        bottom: 22.5,
        size: 7.5,
        tracking: 0.14,
        color: 'rgba(255,255,255,0.72)',
    },
    holder: {
        bottom: 206,
        size: 9,
        tracking: 0.1,
        color: 'rgba(255,255,255,0.82)',
    },
    caption: {
        bottom: 212,
        size: 7.5,
        tracking: 0.13,
        color: 'rgba(255,255,255,0.62)',
    },
    amount: { top: 214, bottom: 247, size: 33 },
    statLabel: {
        top: 255,
        bottom: 263.5,
        size: 7,
        tracking: 0.1,
        color: 'rgba(255,255,255,0.6)',
    },
    statValue: { top: 264.5, bottom: 278.5, size: 11.5 },
    domain: {
        top: 263.5,
        bottom: 278.5,
        size: 10.5,
        tracking: 0.03,
        icon: 15,
        gap: 4,
    },
    statGap: 13,
    captionGap: 6,
};

const TONES = {
    investor: ['#3f83ff', '#0a44c4', '#061640'],
    business: ['#17c268', '#0a7c47', '#052a1c'],
};

const WORDMARK = '/images/rozine-wordmark-white.png';

const WING = '/images/rozine-wing-white.png';

/**
 * Draw a pass and hand it to the browser as a download.
 */
export async function downloadPassCard(spec: PassCardSpec): Promise<void> {
    const canvas = await drawPassCard(spec);

    const blob = await new Promise<Blob | null>((resolve) =>
        canvas.toBlob(resolve, 'image/png'),
    );

    if (!blob) {
        throw new Error('The card could not be encoded.');
    }

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = `rozine-pulse-${spec.tone}-pass.png`;
    document.body.appendChild(link);
    link.click();
    link.remove();

    URL.revokeObjectURL(url);
}

/**
 * Paint a pass onto a canvas.
 */
export async function drawPassCard(
    spec: PassCardSpec,
): Promise<HTMLCanvasElement> {
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(WIDTH * SCALE);
    canvas.height = Math.round(HEIGHT * SCALE);

    const ctx = canvas.getContext('2d');

    if (!ctx) {
        throw new Error('This browser cannot draw the card.');
    }

    const [wordmark, wing] = await Promise.all([
        loadImage(WORDMARK),
        loadImage(WING),
        loadFonts(),
    ]);

    ctx.scale(SCALE, SCALE);

    ctx.beginPath();
    ctx.roundRect(0, 0, WIDTH, HEIGHT, RADIUS);
    ctx.clip();

    paintBackground(ctx, spec.tone);
    paintWings(ctx, wing);
    paintSheen(ctx);
    paintHighlight(ctx);
    paintHeader(ctx, spec, wordmark);
    paintBody(ctx, spec);

    return canvas;
}

function paintBackground(
    ctx: CanvasRenderingContext2D,
    tone: PassCardSpec['tone'],
): void {
    const [start, middle, end] = TONES[tone];

    // radial-gradient(150% 130% at 14% 0%, …) — an ellipse, so the circular
    // gradient canvas offers is stretched onto one.
    const radiusX = WIDTH * 1.5;
    const radiusY = HEIGHT * 1.3;

    ctx.save();
    ctx.translate(WIDTH * 0.14, 0);
    ctx.scale(1, radiusY / radiusX);

    const gradient = ctx.createRadialGradient(0, 0, 0, 0, 0, radiusX);
    gradient.addColorStop(0, start);
    gradient.addColorStop(0.4, middle);
    gradient.addColorStop(1, end);

    ctx.fillStyle = gradient;
    ctx.fillRect(-WIDTH * 2, 0, WIDTH * 4, (HEIGHT * radiusX) / radiusY);
    ctx.restore();
}

function paintWings(
    ctx: CanvasRenderingContext2D,
    wing: HTMLImageElement,
): void {
    const tile = document.createElement('canvas');
    tile.width = 19 * SCALE;
    tile.height = 10.5 * SCALE;

    const tileCtx = tile.getContext('2d');

    if (!tileCtx) {
        return;
    }

    // The pattern places an 18 x 9.4 slot at (0.5, 0.75); the mark is fitted
    // inside it on its own aspect ratio, centred.
    const height = 18 / (wing.width / wing.height);
    tileCtx.drawImage(
        wing,
        0.5 * SCALE,
        (0.75 + (9.4 - height) / 2) * SCALE,
        18 * SCALE,
        height * SCALE,
    );

    const pattern = ctx.createPattern(tile, 'repeat');

    if (!pattern) {
        return;
    }

    pattern.setTransform(new DOMMatrix().scale(1 / SCALE).rotate(-30));

    ctx.save();
    ctx.globalAlpha = 0.11;
    ctx.fillStyle = pattern;
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
    ctx.restore();
}

function paintSheen(ctx: CanvasRenderingContext2D): void {
    const band = WIDTH * 0.46;

    // linear-gradient(100deg, …) runs right and very slightly down.
    const radians = ((100 - 90) * Math.PI) / 180;
    const gradient = ctx.createLinearGradient(
        0,
        0,
        band * Math.cos(radians),
        band * Math.sin(radians),
    );
    gradient.addColorStop(0, 'rgba(255,255,255,0.16)');
    gradient.addColorStop(0.72, 'rgba(255,255,255,0)');

    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, band, HEIGHT);
}

function paintHighlight(ctx: CanvasRenderingContext2D): void {
    const centreX = WIDTH + 70 - 115;
    const centreY = -70 + 115;

    const gradient = ctx.createRadialGradient(
        centreX,
        centreY,
        0,
        centreX,
        centreY,
        115,
    );
    gradient.addColorStop(0, 'rgba(255,255,255,0.2)');
    gradient.addColorStop(1, 'rgba(255,255,255,0)');

    ctx.fillStyle = gradient;
    ctx.fillRect(centreX - 115, centreY - 115, 230, 230);
}

function paintHeader(
    ctx: CanvasRenderingContext2D,
    spec: PassCardSpec,
    wordmark: HTMLImageElement,
): void {
    const { logo, tag } = LAYOUT;

    ctx.drawImage(
        wordmark,
        logo.x,
        logo.y,
        logo.height * (wordmark.width / wordmark.height),
        logo.height,
    );

    write(ctx, spec.tag, {
        x: WIDTH - EDGE,
        top: tag.top,
        bottom: tag.bottom,
        size: tag.size,
        weight: 700,
        tracking: tag.tracking,
        color: tag.color,
        align: 'right',
    });
}

function paintBody(ctx: CanvasRenderingContext2D, spec: PassCardSpec): void {
    const {
        holder,
        caption,
        amount,
        statLabel,
        statValue,
        statGap,
        captionGap,
    } = LAYOUT;

    write(ctx, spec.amount, {
        x: EDGE,
        top: amount.top,
        bottom: amount.bottom,
        size: amount.size,
        weight: 800,
        tracking: -0.025,
        color: '#ffffff',
    });

    const holderBottom = spec.caption
        ? caption.bottom - lineHeight(ctx, caption.size, 700) - captionGap
        : holder.bottom;

    if (spec.caption) {
        write(ctx, spec.caption, {
            x: EDGE,
            bottom: caption.bottom,
            size: caption.size,
            weight: 700,
            tracking: caption.tracking,
            color: caption.color,
        });
    }

    write(ctx, spec.holder.toUpperCase(), {
        x: EDGE,
        bottom: holderBottom,
        size: holder.size,
        weight: 600,
        tracking: holder.tracking,
        color: holder.color,
    });

    let x = EDGE;

    spec.stats.forEach((stat) => {
        write(ctx, stat.label, {
            x,
            top: statLabel.top,
            bottom: statLabel.bottom,
            size: statLabel.size,
            weight: 400,
            tracking: statLabel.tracking,
            color: statLabel.color,
        });

        write(ctx, stat.value, {
            x,
            top: statValue.top,
            bottom: statValue.bottom,
            size: statValue.size,
            weight: 700,
            color: '#ffffff',
        });

        const width = Math.max(
            measure(ctx, stat.label, statLabel.size, 400, statLabel.tracking),
            measure(ctx, stat.value, statValue.size, 700, 0),
        );

        x += width + statGap;
    });

    paintDomain(ctx);
}

function paintDomain(ctx: CanvasRenderingContext2D): void {
    const { domain } = LAYOUT;
    const label = 'rozine.rw';
    const width = measure(ctx, label, domain.size, 700, domain.tracking);

    write(ctx, label, {
        x: WIDTH - EDGE,
        top: domain.top,
        bottom: domain.bottom,
        size: domain.size,
        weight: 700,
        tracking: domain.tracking,
        color: '#ffffff',
        align: 'right',
    });

    const centre = WIDTH - EDGE - width - domain.gap - domain.icon / 2;
    const middle = (domain.top + domain.bottom) / 2;
    const radius = 5.6;

    ctx.save();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 1.1;

    ctx.beginPath();
    ctx.arc(centre, middle, radius, 0, Math.PI * 2);
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(centre - radius, middle);
    ctx.lineTo(centre + radius, middle);
    ctx.stroke();

    ctx.beginPath();
    ctx.ellipse(centre, middle, radius / 2, radius, 0, 0, Math.PI * 2);
    ctx.stroke();
    ctx.restore();
}

type WriteOptions = {
    x: number;
    size: number;
    weight: number;
    color: string;
    top?: number;
    bottom?: number;
    tracking?: number;
    align?: CanvasTextAlign;
};

/**
 * Draw a run of text into the box the rendered card gives it, sitting the
 * glyphs on the same baseline the browser would.
 */
function write(
    ctx: CanvasRenderingContext2D,
    text: string,
    options: WriteOptions,
): void {
    ctx.save();
    ctx.font = font(options.size, options.weight);
    ctx.letterSpacing = `${(options.tracking ?? 0) * options.size}px`;
    ctx.fillStyle = options.color;
    ctx.textAlign = options.align ?? 'left';
    ctx.textBaseline = 'alphabetic';

    const metrics = ctx.measureText(text);
    const ascent = metrics.fontBoundingBoxAscent || options.size * 0.8;
    const descent = metrics.fontBoundingBoxDescent || options.size * 0.2;

    const top = options.top ?? (options.bottom ?? 0) - (ascent + descent);
    const boxHeight =
        options.bottom !== undefined && options.top !== undefined
            ? options.bottom - options.top
            : ascent + descent;

    ctx.fillText(
        text,
        options.x,
        top + (boxHeight - (ascent + descent)) / 2 + ascent,
    );
    ctx.restore();
}

function lineHeight(
    ctx: CanvasRenderingContext2D,
    size: number,
    weight: number,
): number {
    ctx.save();
    ctx.font = font(size, weight);
    const metrics = ctx.measureText('Hg');
    ctx.restore();

    return (
        (metrics.fontBoundingBoxAscent || size * 0.8) +
        (metrics.fontBoundingBoxDescent || size * 0.2)
    );
}

function measure(
    ctx: CanvasRenderingContext2D,
    text: string,
    size: number,
    weight: number,
    tracking: number,
): number {
    ctx.save();
    ctx.font = font(size, weight);
    ctx.letterSpacing = `${tracking * size}px`;
    const width = ctx.measureText(text).width;
    ctx.restore();

    return width;
}

function font(size: number, weight: number): string {
    return `${weight} ${size}px Inter, -apple-system, system-ui, sans-serif`;
}

function loadImage(src: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error(`Could not load ${src}.`));
        image.src = src;
    });
}

async function loadFonts(): Promise<void> {
    if (!('fonts' in document)) {
        return;
    }

    await Promise.all([
        document.fonts.load('800 33px Inter'),
        document.fonts.load('700 12px Inter'),
        document.fonts.load('600 9px Inter'),
        document.fonts.load('400 7px Inter'),
    ]);
}
