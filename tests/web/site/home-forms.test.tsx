import { act, render, screen } from '@testing-library/react';
import { createRef } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import Home from '@/pages/home';

const mocks = vi.hoisted(() => ({
    post: vi.fn(),
    download: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    router: { post: mocks.post },
}));

vi.mock('@/lib/pass-card-image', () => ({
    downloadPassCard: mocks.download,
}));

const renderSite = () => {
    const ref = createRef<Home>();
    const { unmount } = render(<Home ref={ref} />);
    const instance = ref.current;

    if (!instance) {
        throw new Error('The site did not mount.');
    }

    return { site: instance, leavePage: unmount };
};

const mountSite = () => renderSite().site;

/** The options object the component hands to the Inertia router. */
const lastPostOptions = () =>
    mocks.post.mock.calls.at(-1)?.[2] as {
        onSuccess: () => void;
        onError: (errors: Record<string, string>) => void;
    };

const readyInvestor = (site: Home) => {
    act(() => {
        site.setState({
            name: 'Diane Uwase',
            contact: '0788123456',
            country: 'Rwanda',
            mode: 'phone',
            sheetOpen: true,
        });
    });
};

const readyBusiness = (site: Home) => {
    act(() => {
        site.setState({ page: 'biz' });
        site.bSet({
            biz: 'Kigali Coffee Roasters',
            contact: '0788123456',
            mode: 'phone',
            prov: 'Kigali City',
            dist: 'Gasabo',
            sheetOpen: true,
        });
    });
};

beforeEach(() => {
    document.body.innerHTML = '';
    mocks.post.mockReset();
    mocks.download.mockReset().mockResolvedValue(undefined);
    window.scrollTo = vi.fn() as unknown as typeof window.scrollTo;
});

afterEach(() => {
    vi.useRealTimers();
});

describe('an investor leaving their details', () => {
    it('holds the details back until they are complete', () => {
        const site = mountSite();

        act(() => {
            site.invVals().submit();
        });

        expect(mocks.post).not.toHaveBeenCalled();
    });

    it('sends the pledge with the contact reduced to one form', () => {
        const site = mountSite();

        readyInvestor(site);
        act(() => {
            site.invVals().submit();
        });

        const [url, payload] = mocks.post.mock.calls[0];

        expect(url).toContain('investor');
        expect(payload).toMatchObject({
            name: 'Diane Uwase',
            contact_method: 'phone',
            contact: '250788123456',
            country: 'Rwanda',
        });
        expect(payload.pledge_amount).toBeGreaterThan(0);
    });

    it('shows the card once the details are recorded', () => {
        const site = mountSite();

        readyInvestor(site);
        act(() => {
            site.invVals().submit();
        });
        act(() => {
            lastPostOptions().onSuccess();
        });

        expect(site.state.sent).toBe(true);
        expect(site.state.submitting).toBe(false);
    });

    it('repeats what the server objected to', () => {
        const site = mountSite();

        readyInvestor(site);
        act(() => {
            site.invVals().submit();
        });
        act(() => {
            lastPostOptions().onError({
                contact: 'This phone number is already in the waitlist.',
            });
        });

        expect(site.state.sent).toBeFalsy();
        expect(site.invVals().submitErr).toBe(
            'This phone number is already in the waitlist.',
        );
        expect(screen.getByText(/already in the waitlist/)).toBeInTheDocument();
    });

    it('falls back to a plain apology when the server says nothing useful', () => {
        const site = mountSite();

        readyInvestor(site);
        act(() => {
            site.invVals().submit();
        });
        act(() => {
            lastPostOptions().onError({});
        });

        expect(site.invVals().submitErr).toMatch(/Something went wrong/);
    });

    it('will not send the same details twice at once', () => {
        const site = mountSite();

        readyInvestor(site);
        act(() => {
            site.invVals().submit();
        });
        act(() => {
            site.invVals().submit();
        });

        expect(mocks.post).toHaveBeenCalledTimes(1);
    });

    it('reaches an investor by email when they ask for that', () => {
        const site = mountSite();

        act(() => {
            site.setState({
                name: 'Diane Uwase',
                contact: 'Diane@Example.COM',
                country: 'Rwanda',
                mode: 'email',
            });
        });
        act(() => {
            site.invVals().submit();
        });

        expect(mocks.post.mock.calls[0][1]).toMatchObject({
            contact_method: 'email',
            contact: 'diane@example.com',
        });
    });
});

describe('a business asking to borrow', () => {
    it('holds the request back until the details are complete', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
        });
        act(() => {
            site.bizVals().submit();
        });

        expect(mocks.post).not.toHaveBeenCalled();
    });

    it('sends the figures the owner reported', () => {
        const site = mountSite();

        readyBusiness(site);
        act(() => {
            site.bizVals().submit();
        });

        const [url, payload] = mocks.post.mock.calls[0];

        expect(url).toContain('business');
        expect(payload).toMatchObject({
            name: 'Kigali Coffee Roasters',
            contact_method: 'phone',
            contact: '250788123456',
            province: 'Kigali City',
            district: 'Gasabo',
            annual_revenue: 80_000_000,
            annual_costs: 62_000_000,
            term_months: 6,
        });
    });

    it('shows the card once the request is recorded', () => {
        const site = mountSite();

        readyBusiness(site);
        act(() => {
            site.bizVals().submit();
        });
        act(() => {
            lastPostOptions().onSuccess();
        });

        expect(site.state.B.sent).toBe(true);
        expect(site.state.B.submitting).toBe(false);
    });

    it('repeats what the server objected to', () => {
        const site = mountSite();

        readyBusiness(site);
        act(() => {
            site.bizVals().submit();
        });
        act(() => {
            lastPostOptions().onError({ contact: 'Already on the waitlist.' });
        });

        expect(site.bizVals().submitErr).toBe('Already on the waitlist.');
    });

    it('falls back to a plain apology when the server says nothing useful', () => {
        const site = mountSite();

        readyBusiness(site);
        act(() => {
            site.bizVals().submit();
        });
        act(() => {
            lastPostOptions().onError({});
        });

        expect(site.bizVals().submitErr).toMatch(/Something went wrong/);
    });

    it('will not send the same request twice at once', () => {
        const site = mountSite();

        readyBusiness(site);
        act(() => {
            site.bizVals().submit();
        });
        act(() => {
            site.bizVals().submit();
        });

        expect(mocks.post).toHaveBeenCalledTimes(1);
    });
});

describe('the card a signup can keep', () => {
    // The saved/failed note clears itself after 2.2s of real time; hold the clock so a slow run
    // cannot let it expire before the assertion reads it.
    beforeEach(() => {
        vi.useFakeTimers({ toFake: ['setTimeout', 'clearTimeout'] });
    });

    it('hands the investor the card they are looking at', async () => {
        const site = mountSite();

        act(() => {
            site.setState({
                sent: true,
                name: 'Diane Uwase',
                country: 'Rwanda',
                depIdx: 22,
                term: 6,
            });
        });
        await act(async () => {
            await site.invVals().shareBtns[0].on();
        });

        expect(mocks.download).toHaveBeenCalledOnce();

        const spec = mocks.download.mock.calls[0][0];

        expect(spec).toMatchObject({
            surface: 'site',
            tone: 'investor',
            tag: 'INVESTOR NOTE',
            caption: 'PUTTING TO WORK',
            holder: 'DIANE UWASE \u00b7 Rwanda',
        });
        expect(spec.amount).toMatch(/^RWF /);
        expect(spec.stats.map((s: { label: string }) => s.label)).toEqual([
            'YOU GET BACK',
            'TERM',
        ]);
        expect(site.state.shareMsg).toBe('Card saved to your device');
    });

    it('hands the business the card they are looking at', async () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
            site.bSet({ sent: true, biz: 'Kigali Coffee', dist: 'Gasabo' });
        });
        await act(async () => {
            await site.bizVals().shareBtns[0].on();
        });

        const spec = mocks.download.mock.calls[0][0];

        expect(spec).toMatchObject({
            surface: 'site',
            tone: 'business',
            tag: 'BORROWING REQUEST',
            caption: 'ASKING TO BORROW',
            holder: 'KIGALI COFFEE \u00b7 Gasabo',
        });
        expect(spec.stats.map((s: { label: string }) => s.label)).toEqual([
            'TERM',
            'FLAT CHARGE',
        ]);
        expect(site.state.B.shareMsg).toBe('Card saved to your device');
    });

    it('clears the note a moment after the card is saved', async () => {
        vi.useFakeTimers();

        const site = mountSite();

        act(() => {
            site.setState({ sent: true, name: 'Diane', country: 'Rwanda' });
        });
        await act(async () => {
            await site.invVals().shareBtns[0].on();
        });

        expect(site.state.shareMsg).toBe('Card saved to your device');

        act(() => {
            vi.advanceTimersByTime(2400);
        });

        expect(site.state.shareMsg).toBe('');
    });

    it('says so when the card cannot be drawn', async () => {
        mocks.download.mockRejectedValue(new Error('no canvas here'));

        const site = mountSite();

        act(() => {
            site.setState({ sent: true, name: 'Diane', country: 'Rwanda' });
        });
        await act(async () => {
            await site.invVals().shareBtns[0].on();
        });

        expect(site.state.shareMsg).toBe('The card could not be saved');
    });

    it('says so when a business card cannot be drawn', async () => {
        mocks.download.mockRejectedValue(new Error('no canvas here'));

        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
            site.bSet({ sent: true, biz: 'Kigali Coffee' });
        });
        await act(async () => {
            await site.bizVals().shareBtns[0].on();
        });

        expect(site.state.B.shareMsg).toBe('The card could not be saved');
    });

    it('clears a business note a moment later too', async () => {
        vi.useFakeTimers();

        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
            site.bSet({ sent: true, biz: 'Kigali Coffee' });
        });
        await act(async () => {
            await site.bizVals().shareBtns[0].on();
        });

        expect(site.state.B.shareMsg).toBe('Card saved to your device');

        act(() => {
            vi.advanceTimersByTime(2400);
        });

        expect(site.state.B.shareMsg).toBe('');
    });

    it('drops the waiting note when the visitor leaves before it clears', async () => {
        vi.useFakeTimers();

        const { site, leavePage } = renderSite();

        act(() => {
            site.setState({ sent: true, name: 'Diane', country: 'Rwanda' });
        });
        await act(async () => {
            await site.invVals().shareBtns[0].on();
        });

        expect(site.state.shareMsg).toBe('Card saved to your device');

        leavePage();

        const touchesState = vi.spyOn(site, 'setState');

        act(() => {
            vi.advanceTimersByTime(2400);
        });

        expect(touchesState).not.toHaveBeenCalled();
    });
});

describe('keeping the glass layer in step with the page', () => {
    it('orders the bands it masks from the top down', () => {
        const site = new Home({} as never);
        const host = document.createElement('div');
        const layer = document.createElement('div');
        const lower = document.createElement('div');
        const upper = document.createElement('div');

        layer.setAttribute('data-rz-glass', '');
        lower.setAttribute('data-rz-blue', '');
        upper.setAttribute('data-rz-blue', '');
        host.append(layer, lower, upper);
        document.body.appendChild(host);
        vi.spyOn(host, 'scrollHeight', 'get').mockReturnValue(1000);
        vi.spyOn(lower, 'getBoundingClientRect').mockReturnValue({
            top: 600,
            height: 100,
        } as DOMRect);
        vi.spyOn(upper, 'getBoundingClientRect').mockReturnValue({
            top: 100,
            height: 100,
        } as DOMRect);

        site._rzMask();

        expect(layer.style.maskImage.indexOf('10.000%')).toBeLessThan(
            layer.style.maskImage.indexOf('60.000%'),
        );
    });

    it('re-measures when the page is resized or reflows', () => {
        const site = new Home({} as never);
        const remeasure = vi.spyOn(site, '_rzMask');
        let observed: (() => void) | undefined;

        window.ResizeObserver = class {
            constructor(callback: () => void) {
                observed = callback;
            }

            observe = vi.fn();

            unobserve = vi.fn();

            disconnect = vi.fn();
        } as unknown as typeof ResizeObserver;

        site._rzWatch();
        remeasure.mockClear();

        observed?.();
        window.dispatchEvent(new Event('resize'));

        expect(remeasure).toHaveBeenCalledTimes(2);

        delete (window as { ResizeObserver?: unknown }).ResizeObserver;
    });
});
