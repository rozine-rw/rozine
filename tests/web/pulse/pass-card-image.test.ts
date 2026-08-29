import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { downloadPassCard, drawPassCard } from '@/lib/pass-card-image';
import type { PassCardSpec } from '@/lib/pass-card-image';

type TestContext = CanvasRenderingContext2D & {
    createPattern: ReturnType<typeof vi.fn>;
    measureText: ReturnType<typeof vi.fn>;
};

const investorCard = (changes: Partial<PassCardSpec> = {}): PassCardSpec => ({
    tone: 'investor',
    tag: 'PLEDGING INVESTOR',
    badge: '#0142',
    holder: 'Diane Uwase',
    caption: 'PLEDGED AMOUNT',
    amount: 'RWF 500K',
    stats: [
        { label: 'PROJECTED', value: 'RWF 565K' },
        { label: 'AVG YIELD', value: '13.0%' },
    ],
    ...changes,
});

const makeContext = ({
    pattern = true,
    fontMetrics = true,
}: {
    pattern?: boolean;
    fontMetrics?: boolean;
} = {}): TestContext => {
    const gradient = { addColorStop: vi.fn() };
    const canvasPattern = { setTransform: vi.fn() };
    const metrics = {
        width: 120,
        fontBoundingBoxAscent: fontMetrics ? 20 : 0,
        fontBoundingBoxDescent: fontMetrics ? 5 : 0,
    };

    return {
        beginPath: vi.fn(),
        clip: vi.fn(),
        createPattern: vi.fn(() => (pattern ? canvasPattern : null)),
        createRadialGradient: vi.fn(() => gradient),
        drawImage: vi.fn(),
        fill: vi.fn(),
        fillRect: vi.fn(),
        fillText: vi.fn(),
        measureText: vi.fn(() => metrics),
        restore: vi.fn(),
        rotate: vi.fn(),
        roundRect: vi.fn(),
        save: vi.fn(),
        scale: vi.fn(),
        stroke: vi.fn(),
        translate: vi.fn(),
    } as unknown as TestContext;
};

let contextQueue: Array<TestContext | null>;
let fallbackContext: TestContext;
let imageFails: boolean;
let encodedBlob: Blob | null;

class TestPath2D {
    path?: string;

    constructor(path?: string) {
        this.path = path;
    }

    addPath = vi.fn();
    ellipse = vi.fn();
    roundRect = vi.fn();
}

class TestDOMMatrix {
    scale(): this {
        return this;
    }

    translate(): this {
        return this;
    }
}

class TestImage {
    width = 200;
    height = 50;
    onload: null | (() => void) = null;
    onerror: null | (() => void) = null;

    set src(_src: string) {
        queueMicrotask(() => {
            if (imageFails) {
                this.onerror?.();

                return;
            }

            this.onload?.();
        });
    }
}

beforeEach(() => {
    contextQueue = [];
    fallbackContext = makeContext();
    imageFails = false;
    encodedBlob = new Blob(['card'], { type: 'image/png' });

    vi.stubGlobal('Path2D', TestPath2D);
    vi.stubGlobal('DOMMatrix', TestDOMMatrix);
    vi.stubGlobal('Image', TestImage);
    vi.stubGlobal('URL', {
        createObjectURL: vi.fn(() => 'blob:card'),
        revokeObjectURL: vi.fn(),
    });

    vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockImplementation(
        () => {
            if (contextQueue.length > 0) {
                return contextQueue.shift() as CanvasRenderingContext2D | null;
            }

            return fallbackContext;
        },
    );
    vi.spyOn(HTMLCanvasElement.prototype, 'toBlob').mockImplementation(
        (callback) => callback(encodedBlob),
    );
    vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
});

afterEach(() => {
    Reflect.deleteProperty(document, 'fonts');
    vi.unstubAllGlobals();
});

describe('drawPassCard', () => {
    it('draws the complete investor pass and waits for browser fonts', async () => {
        const fonts = { load: vi.fn(() => Promise.resolve([])) };

        Object.defineProperty(document, 'fonts', {
            configurable: true,
            value: fonts,
        });

        const canvas = await drawPassCard(investorCard());

        expect(canvas.width).toBe(1800);
        expect(canvas.height).toBe(1136);
        expect(fonts.load).toHaveBeenCalledTimes(4);
        expect(fallbackContext.fillText).toHaveBeenCalledWith(
            'PLEDGING INVESTOR',
            expect.any(Number),
            expect.any(Number),
        );
        expect(fallbackContext.fillText).toHaveBeenCalledWith(
            'PLEDGED AMOUNT',
            expect.any(Number),
            expect.any(Number),
        );
    });

    it('draws a business card without a caption using fallback font metrics', async () => {
        fallbackContext = makeContext({ fontMetrics: false });

        await drawPassCard(
            investorCard({
                tone: 'business',
                caption: undefined,
                holder: 'GreenLeaf Agro',
                stats: [],
            }),
        );

        expect(fallbackContext.fillText).toHaveBeenCalledWith(
            'GREENLEAF AGRO',
            expect.any(Number),
            expect.any(Number),
        );
        expect(fallbackContext.fillText).not.toHaveBeenCalledWith(
            'PLEDGED AMOUNT',
            expect.anything(),
            expect.anything(),
        );
    });

    it('fails clearly when the primary canvas context is unavailable', async () => {
        contextQueue = [null];

        await expect(drawPassCard(investorCard())).rejects.toThrow(
            'This browser cannot draw the card.',
        );
    });

    it('fails clearly when a required image cannot load', async () => {
        imageFails = true;

        await expect(drawPassCard(investorCard())).rejects.toThrow(
            'Could not load /images/rozine-wordmark-white.png.',
        );
    });

    it('continues when the card inset layer cannot draw', async () => {
        contextQueue = [makeContext(), null];

        await expect(drawPassCard(investorCard())).resolves.toBeInstanceOf(
            HTMLCanvasElement,
        );
    });

    it('continues when the wing tile has no drawing context', async () => {
        contextQueue = [makeContext(), makeContext(), null];

        await expect(drawPassCard(investorCard())).resolves.toBeInstanceOf(
            HTMLCanvasElement,
        );
    });

    it('continues when the wing tile cannot create a repeating pattern', async () => {
        const main = makeContext({ pattern: false });

        contextQueue = [main];

        await expect(drawPassCard(investorCard())).resolves.toBeInstanceOf(
            HTMLCanvasElement,
        );
        expect(main.createPattern).toHaveReturnedWith(null);
    });

    it('continues when the badge inset layer cannot draw', async () => {
        contextQueue = [makeContext(), makeContext(), makeContext(), null];

        await expect(drawPassCard(investorCard())).resolves.toBeInstanceOf(
            HTMLCanvasElement,
        );
    });
});

describe('downloadPassCard', () => {
    it('encodes, downloads, removes, and revokes the pass URL', async () => {
        const append = vi.spyOn(document.body, 'appendChild');

        await downloadPassCard(investorCard());

        expect(URL.createObjectURL).toHaveBeenCalledWith(encodedBlob);
        expect(append).toHaveBeenCalledWith(expect.any(HTMLAnchorElement));
        expect(HTMLAnchorElement.prototype.click).toHaveBeenCalledOnce();
        expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:card');
    });

    it('rejects a canvas encoding failure without creating a URL', async () => {
        encodedBlob = null;

        await expect(downloadPassCard(investorCard())).rejects.toThrow(
            'The card could not be encoded.',
        );
        expect(URL.createObjectURL).not.toHaveBeenCalled();
    });
});
