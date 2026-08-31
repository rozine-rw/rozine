import { render } from '@testing-library/react';
import { createRef } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import Home from '@/pages/home';

const mocks = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    router: { post: mocks.post },
}));

/** A site instance that was never rendered, so DOM queries see only the fixture. */
const detachedSite = () => new Home({} as never);

/** Render the site and hand back the component instance for direct assertions. */
const mountSite = () => {
    const ref = createRef<Home>();

    render(<Home ref={ref} />);

    const instance = ref.current;

    if (!instance) {
        throw new Error('The site did not mount.');
    }

    return instance;
};

beforeEach(() => {
    document.body.innerHTML = '';
    mocks.post.mockReset();
    window.scrollTo = vi.fn() as unknown as typeof window.scrollTo;
});

afterEach(() => {
    vi.useRealTimers();
});

describe('money and phone formatting', () => {
    it('writes amounts in Rwandan francs with thousands separators', () => {
        const site = mountSite();

        expect(site.rwf(1_500_000)).toBe('RWF 1,500,000');
        expect(site.rwf(0)).toBe('RWF 0');
        expect(site.rwf(null as unknown as number)).toBe('RWF 0');
    });

    it('keeps only the digits a person typed', () => {
        const site = mountSite();

        expect(site.digits('078 123-456')).toBe('078123456');
        expect(site.digits(null)).toBe('');
    });

    it('reduces a contact to the form the waitlist stores', () => {
        const site = mountSite();

        expect(site.siteContact('email', '+250', ' Person@Example.COM ')).toBe(
            'person@example.com',
        );
        expect(site.siteContact('phone', '+250', '0788 123 456')).toBe(
            '250788123456',
        );
        expect(site.siteContact('phone', '+250', '788123456')).toBe(
            '250788123456',
        );
        expect(site.siteContact('phone', '+250', '250788123456')).toBe(
            '250788123456',
        );
        expect(site.siteContact('phone', '', '0788123456')).toBe(
            '250788123456',
        );
    });
});

describe('what an investor is charged and offered', () => {
    it('charges less as the amount lent grows', () => {
        const site = mountSite();

        expect(site.chargeFor(200_000_000)).toBe(4.5);
        expect(site.chargeFor(100_000_000)).toBe(5);
        expect(site.chargeFor(50_000_000)).toBe(6);
        expect(site.chargeFor(10_000_000)).toBe(7);
        expect(site.chargeFor(2_000_000)).toBe(8);
        expect(site.chargeFor(500_000)).toBe(10);
    });

    it('rounds the slider to a step that suits the amount', () => {
        const site = mountSite();

        expect(site.depFor(0)).toBe(5_000);
        expect(site.depFor(40)).toBe(200_000_000);
        expect(site.depFor(-5)).toBe(5_000);
        expect(site.depFor(99)).toBe(200_000_000);
        expect(site.depFor(null)).toBe(5_000);
        [8, 16, 24, 32].forEach((index) => {
            expect(site.depFor(index) % 5_000).toBe(0);
        });
    });

    it('prices a business between the floor and ceiling of the band', () => {
        const site = mountSite();

        expect(site.bChargeFor(92, 6)).toBe(16);
        expect(site.bChargeFor(40, 3)).toBe(14);
        expect(site.bChargeFor(200, 99)).toBe(16);
        expect(site.bChargeFor(-50, -5)).toBe(14);
    });
});

describe('the glass layer that inverts over the blue bands', () => {
    it('does nothing when the layer is absent', () => {
        const site = detachedSite();

        expect(() => site._rzMask()).not.toThrow();
    });

    it('clears the mask when no blue band is on the page', () => {
        const site = detachedSite();
        const host = document.createElement('div');
        const layer = document.createElement('div');

        layer.setAttribute('data-rz-glass', '');
        host.appendChild(layer);
        document.body.appendChild(host);
        vi.spyOn(host, 'scrollHeight', 'get').mockReturnValue(1000);

        site._rzMask();

        expect(layer.style.maskImage.replace(/\s/g, '')).toBe(
            'linear-gradient(rgba(0,0,0,0),rgba(0,0,0,0))',
        );
    });

    it('cuts the mask to the bands it finds', () => {
        const site = detachedSite();
        const host = document.createElement('div');
        const layer = document.createElement('div');
        const band = document.createElement('div');

        layer.setAttribute('data-rz-glass', '');
        band.setAttribute('data-rz-blue', '');
        host.append(layer, band);
        document.body.appendChild(host);
        vi.spyOn(host, 'scrollHeight', 'get').mockReturnValue(1000);
        vi.spyOn(band, 'getBoundingClientRect').mockReturnValue({
            top: 200,
            height: 300,
        } as DOMRect);

        site._rzMask();

        // the band sits from 200px to 500px of a 1000px host, so 20% to 50%
        expect(layer.style.maskImage).toContain('transparent 0.000% 20.000%');
        expect(layer.style.maskImage).toContain('20.000% 50.000%');
        expect(layer.style.maskImage).toContain('transparent 50.000% 100%');
    });

    it('gives up when the host has no height to measure against', () => {
        const site = detachedSite();
        const host = document.createElement('div');
        const layer = document.createElement('div');

        layer.setAttribute('data-rz-glass', '');
        host.appendChild(layer);
        document.body.appendChild(host);
        vi.spyOn(host, 'scrollHeight', 'get').mockReturnValue(0);

        site._rzMask();

        expect(layer).toHaveStyle({ maskImage: '' });
    });

    it('re-measures when the page resizes', () => {
        const observe = vi.fn();

        window.ResizeObserver = class {
            observe = observe;

            unobserve = vi.fn();

            disconnect = vi.fn();
        } as unknown as typeof ResizeObserver;

        const site = detachedSite();

        site._rzWatch();

        expect(observe).toHaveBeenCalled();

        delete (window as { ResizeObserver?: unknown }).ResizeObserver;
    });
});

describe('the counter that animates towards a new figure', () => {
    it('stops animating once the figures stop moving', () => {
        const site = mountSite();

        site._sync();
        const first = site.state.disp;

        site._sync();

        expect(site.state.disp).toBe(first);
    });

    it('cancels its frame when the page goes away', () => {
        const cancel = vi.spyOn(window, 'cancelAnimationFrame');
        const site = mountSite();

        site.componentWillUnmount();

        expect(cancel).toHaveBeenCalled();
    });
});
