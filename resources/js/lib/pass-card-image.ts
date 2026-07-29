/**
 * Redraws a Pulse pass onto a canvas so a visitor can keep it.
 *
 * The card on screen is a stack of DOM layers, which a browser will not hand
 * back as an image, so it is painted again here. Every box below is the one the
 * design gives it at its native 900px width; the rendered card holds the same
 * proportions at whatever width it is shown.
 */

export type PassCardStat = {
    label: string;
    value: string;
};

export type PassCardSpec = {
    tone: 'investor' | 'business';
    tag: string;
    badge: string;
    holder: string;
    caption?: string;
    amount: string;
    stats: PassCardStat[];
};

const WIDTH = 900;

const HEIGHT = WIDTH / 1.585;

const SCALE = 2;

const RADIUS = 38;

/** The padding the card's content sits inside. */
const EDGE = 56;

const GUTTER = 52;

const LAYOUT = {
    logo: { height: 63 },
    tag: {
        size: 22.8,
        tracking: 0.14,
        color: 'rgba(255,255,255,0.72)',
        gap: 12,
    },
    badge: {
        size: 26,
        tracking: 0.02,
        border: 1.5,
        padding: { top: 7, right: 14, bottom: 8, left: 14 },
    },
    holder: {
        size: 27,
        tracking: 0.1,
        color: 'rgba(255,255,255,0.82)',
    },
    caption: {
        size: 21,
        tracking: 0.13,
        color: 'rgba(255,255,255,0.62)',
        gap: 15,
    },
    amount: { size: 99, tracking: -0.025, gap: 24, gapUnderCaption: 6 },
    statLabel: {
        size: 21,
        tracking: 0.1,
        color: 'rgba(255,255,255,0.6)',
    },
    statValue: { size: 34.5, gap: 4.8 },
    domain: {
        size: 31.5,
        tracking: 0.03,
        icon: 42,
        gap: 12,
    },
    statGap: 39,
    footerGap: 24,
};

const TONES = {
    investor: ['#3f83ff', '#0a44c4', '#061640'],
    business: ['#17c268', '#0a7c47', '#052a1c'],
};

const WORDMARK = '/images/rozine-wordmark-white.png';

const WING = '/images/rozine-wing-white.png';

/** The wing tile the card repeats, at the size the design stretches it to. */
const TILE = { width: 36, height: 20 };

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

    // An inset shadow lies over the background but under everything the card
    // holds, so the layers below are painted after it.
    paintInsetTop(
        ctx,
        { x: 0, y: 0, width: WIDTH, height: HEIGHT, radius: RADIUS },
        2,
        'rgba(255,255,255,0.3)',
    );

    paintWings(ctx, wing);
    paintCorner(ctx);
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
    tile.width = TILE.width * SCALE;
    tile.height = TILE.height * SCALE;

    const tileCtx = tile.getContext('2d');

    if (!tileCtx) {
        return;
    }

    // background-size stretches the mark to fill the tile, and the tiles butt
    // up against one another.
    tileCtx.drawImage(wing, 0, 0, tile.width, tile.height);

    const pattern = ctx.createPattern(tile, 'repeat');

    if (!pattern) {
        return;
    }

    pattern.setTransform(new DOMMatrix().scale(1 / SCALE));

    // The layer is 160% of the card, hung 30% off its top left corner, and
    // turned about the card's centre.
    ctx.save();
    ctx.globalAlpha = 0.11;
    ctx.translate(WIDTH / 2, HEIGHT / 2);
    ctx.rotate((-30 * Math.PI) / 180);
    ctx.translate(-WIDTH * 0.8, -HEIGHT * 0.8);
    ctx.fillStyle = pattern;
    ctx.fillRect(0, 0, WIDTH * 1.6, HEIGHT * 1.6);
    ctx.restore();
}

/**
 * `inset 0 <drop>px 0` — the sliver the same shape, dropped by `drop`, leaves
 * uncovered. It is cut on its own layer so the punch-out only reaches the
 * highlight and not what is already painted underneath.
 */
function paintInsetTop(
    ctx: CanvasRenderingContext2D,
    box: {
        x: number;
        y: number;
        width: number;
        height: number;
        radius: number;
    },
    drop: number,
    color: string,
): void {
    const layer = document.createElement('canvas');
    layer.width = Math.ceil(box.width * SCALE);
    layer.height = Math.ceil(box.height * SCALE);

    const layerCtx = layer.getContext('2d');

    if (!layerCtx) {
        return;
    }

    const shape = new Path2D();
    shape.roundRect(0, 0, box.width, box.height, box.radius);

    const dropped = new Path2D();
    dropped.roundRect(0, drop, box.width, box.height, box.radius);

    layerCtx.scale(SCALE, SCALE);
    layerCtx.fillStyle = color;
    layerCtx.fill(shape);

    // destination-out erases by the source's alpha, so the punch has to be
    // opaque or it would leave a share of the highlight behind everywhere.
    layerCtx.globalCompositeOperation = 'destination-out';
    layerCtx.fillStyle = '#000000';
    layerCtx.fill(dropped);

    ctx.drawImage(layer, box.x, box.y, box.width, box.height);
}

/** The soft glow the design hangs off the card's top right corner. */
function paintCorner(ctx: CanvasRenderingContext2D): void {
    const radius = 230;
    const centreX = WIDTH + 140 - radius;
    const centreY = -140 + radius;

    const gradient = ctx.createRadialGradient(
        centreX,
        centreY,
        0,
        centreX,
        centreY,
        radius,
    );
    gradient.addColorStop(0, 'rgba(255,255,255,0.2)');
    gradient.addColorStop(1, 'rgba(255,255,255,0)');

    ctx.fillStyle = gradient;
    ctx.fillRect(centreX - radius, centreY - radius, radius * 2, radius * 2);
}

function paintHeader(
    ctx: CanvasRenderingContext2D,
    spec: PassCardSpec,
    wordmark: HTMLImageElement,
): void {
    const { logo, tag, badge } = LAYOUT;

    ctx.drawImage(
        wordmark,
        EDGE,
        GUTTER,
        logo.height * (wordmark.width / wordmark.height),
        logo.height,
    );

    const badgeHeight =
        badge.padding.top +
        badge.size +
        badge.padding.bottom +
        badge.border * 2;
    const badgeWidth =
        measure(ctx, spec.badge, badge.size, 800, badge.tracking) +
        badge.padding.left +
        badge.padding.right +
        badge.border * 2;

    // The label and the badge are centred against one another, and the pair
    // hangs from the top of the content box.
    const height = Math.max(badgeHeight, lineHeight(ctx, tag.size, 700));
    const middle = GUTTER + height / 2;
    const badgeLeft = WIDTH - EDGE - badgeWidth;

    paintBadge(ctx, spec.badge, badgeLeft, middle - badgeHeight / 2, {
        width: badgeWidth,
        height: badgeHeight,
    });

    write(ctx, spec.tag, {
        x: badgeLeft - tag.gap,
        bottom: middle + lineHeight(ctx, tag.size, 700) / 2,
        size: tag.size,
        weight: 700,
        tracking: tag.tracking,
        color: tag.color,
        align: 'right',
    });
}

function paintBadge(
    ctx: CanvasRenderingContext2D,
    text: string,
    x: number,
    y: number,
    box: { width: number; height: number },
): void {
    const { badge } = LAYOUT;
    const inset = badge.border / 2;
    const pill = new Path2D();
    pill.roundRect(x, y, box.width, box.height, box.height / 2);

    ctx.save();
    ctx.fillStyle = 'rgba(255,255,255,0.16)';
    ctx.fill(pill);

    paintInsetTop(
        ctx,
        { x, y, width: box.width, height: box.height, radius: box.height / 2 },
        1,
        'rgba(255,255,255,0.25)',
    );

    const border = new Path2D();
    border.roundRect(
        x + inset,
        y + inset,
        box.width - badge.border,
        box.height - badge.border,
        (box.height - badge.border) / 2,
    );
    ctx.strokeStyle = 'rgba(255,255,255,0.34)';
    ctx.lineWidth = badge.border;
    ctx.stroke(border);
    ctx.restore();

    write(ctx, text, {
        x: x + badge.border + badge.padding.left,
        top: y + badge.border + badge.padding.top,
        bottom: y + badge.border + badge.padding.top + badge.size,
        size: badge.size,
        weight: 800,
        tracking: badge.tracking,
        color: '#ffffff',
    });
}

function paintBody(ctx: CanvasRenderingContext2D, spec: PassCardSpec): void {
    const { holder, caption, amount, statLabel, statValue, statGap, domain } =
        LAYOUT;

    const bottom = HEIGHT - GUTTER;

    const statsHeight =
        lineHeight(ctx, statLabel.size, 400) +
        statValue.gap +
        lineHeight(ctx, statValue.size, 700);
    const domainHeight = Math.max(
        domain.icon,
        lineHeight(ctx, domain.size, 700),
    );

    // The figures and the mark sit on the floor of the content box.
    const footerTop = bottom - Math.max(statsHeight, domainHeight);
    const amountBottom = footerTop - LAYOUT.footerGap;
    const amountTop = amountBottom - amount.size;

    write(ctx, spec.amount, {
        x: EDGE,
        top: amountTop,
        bottom: amountBottom,
        size: amount.size,
        weight: 800,
        tracking: amount.tracking,
        color: '#ffffff',
    });

    let holderBottom = amountTop - amount.gap;

    if (spec.caption) {
        const captionBottom = amountTop - amount.gapUnderCaption;

        write(ctx, spec.caption, {
            x: EDGE,
            bottom: captionBottom,
            size: caption.size,
            weight: 700,
            tracking: caption.tracking,
            color: caption.color,
        });

        holderBottom =
            captionBottom - lineHeight(ctx, caption.size, 700) - caption.gap;
    }

    write(ctx, spec.holder.toUpperCase(), {
        x: EDGE,
        bottom: holderBottom,
        size: holder.size,
        weight: 600,
        tracking: holder.tracking,
        color: holder.color,
    });

    const statsTop = bottom - statsHeight;
    let x = EDGE;

    spec.stats.forEach((stat) => {
        write(ctx, stat.label, {
            x,
            top: statsTop,
            bottom: statsTop + lineHeight(ctx, statLabel.size, 400),
            size: statLabel.size,
            weight: 400,
            tracking: statLabel.tracking,
            color: statLabel.color,
        });

        write(ctx, stat.value, {
            x,
            bottom,
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

    paintDomain(ctx, bottom - domainHeight / 2);
}

function paintDomain(ctx: CanvasRenderingContext2D, middle: number): void {
    const { domain } = LAYOUT;
    const label = 'rozine.rw';
    const width = measure(ctx, label, domain.size, 700, domain.tracking);

    write(ctx, label, {
        x: WIDTH - EDGE,
        top: middle - lineHeight(ctx, domain.size, 700) / 2,
        bottom: middle + lineHeight(ctx, domain.size, 700) / 2,
        size: domain.size,
        weight: 700,
        tracking: domain.tracking,
        color: '#ffffff',
        align: 'right',
    });

    paintGlobe(
        ctx,
        WIDTH - EDGE - width - domain.gap - domain.icon,
        middle - domain.icon / 2,
        domain.icon,
    );
}

/** The globe the card closes with, drawn from the icon's own 24px artwork. */
function paintGlobe(
    ctx: CanvasRenderingContext2D,
    x: number,
    y: number,
    size: number,
): void {
    const scale = size / 24;
    const place = new DOMMatrix().translate(x, y).scale(scale);

    const outline = new Path2D();
    outline.ellipse(12, 12, 9, 9, 0, 0, Math.PI * 2);

    const globe = new Path2D();
    globe.addPath(outline, place);

    const meridians = new Path2D();
    meridians.addPath(
        new Path2D(
            'M3.2 12h17.6M12 3c2.7 2.6 2.7 15.4 0 18M12 3c-2.7 2.6-2.7 15.4 0 18',
        ),
        place,
    );

    ctx.save();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 1.7 * scale;
    ctx.stroke(globe);
    ctx.lineWidth = 1.5 * scale;
    ctx.stroke(meridians);
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
        document.fonts.load('800 99px Inter'),
        document.fonts.load('700 34.5px Inter'),
        document.fonts.load('600 27px Inter'),
        document.fonts.load('400 21px Inter'),
    ]);
}
