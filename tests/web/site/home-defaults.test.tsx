import { act, render } from '@testing-library/react';
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

const mocks = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    router: { post: mocks.post },
}));

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

describe('what the site assumes when a choice has not been made', () => {
    it('reaches an investor by phone until they say otherwise', () => {
        const site = mountSite();

        act(() => {
            site.setState({ mode: undefined });
        });

        const vals = site.invVals();

        expect(vals.contactMode).toBe('tel');
        expect(vals.contactPh).toBe('078 000 0000');
        expect(vals.codeDisp).toBe('block');
        expect(vals.modes[0].bg).toBe('#ffffff');
    });

    it('reaches a business by phone until they say otherwise', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
            site.bSet({ mode: undefined });
        });

        const vals = site.bizVals();

        expect(vals.contactMode).toBe('tel');
        expect(vals.contactPh).toBe('078 000 0000');
        expect(vals.codeDisp).toBe('block');
        expect(vals.modes[0].bg).toBe('#ffffff');
    });

    it('sends a phone contact under the Rwandan code by default', () => {
        const site = mountSite();

        act(() => {
            site.setState({
                name: 'Diane Uwase',
                contact: '0788123456',
                country: 'Rwanda',
                mode: undefined,
            });
        });
        act(() => {
            site.invVals().submit();
        });

        expect(mocks.post.mock.calls[0][1]).toMatchObject({
            contact_method: 'phone',
            contact: '250788123456',
        });
    });

    it('treats a missing contact, country or business name as empty', () => {
        const site = mountSite();

        expect(
            site.siteContact('phone', '+250', null as unknown as string),
        ).toBe('250');

        act(() => {
            site.setState({
                name: 'Diane Uwase',
                contact: '0788123456',
                country: undefined,
            });
        });
        expect(site.invVals().cardCountry).toBe('Rwanda');
        expect(site.invVals().subDisabled).toBe(true);

        act(() => {
            site.setState({ page: 'biz' });
            site.bSet({ biz: undefined });
        });
        expect(site.bizVals().bizName).toBe('your business');
        expect(site.bizVals().cardBiz).toBe('YOUR BUSINESS');
    });

    it('opens on the investor page when no page has been chosen', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: '' });
        });

        expect(site.renderVals().pgInv).toBe(true);
    });

    it('reads a figure of nothing when the owner types something else', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
        });
        act(() => {
            site.bizVals().setRev({ target: { value: 'abc' } });
            site.bizVals().setExp({ target: { value: '' } });
        });

        expect(site.state.B.rev).toBe(0);
        expect(site.state.B.exp).toBe(0);
    });

    it('says nothing useful went wrong when the server sends no reason', () => {
        const site = mountSite();

        act(() => {
            site.setState({
                name: 'Diane Uwase',
                contact: '0788123456',
                country: 'Rwanda',
            });
        });
        act(() => {
            site.invVals().submit();
        });
        act(() => {
            mocks.post.mock.calls[0][2].onError(undefined);
        });

        expect(site.invVals().submitErr).toMatch(/Something went wrong/);
    });

    it('says nothing useful went wrong to a business too', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
            site.bSet({
                biz: 'Kigali Coffee',
                contact: '0788123456',
                prov: 'Kigali City',
                dist: 'Gasabo',
            });
        });
        act(() => {
            site.bizVals().submit();
        });
        act(() => {
            mocks.post.mock.calls[0][2].onError(undefined);
        });

        expect(site.bizVals().submitErr).toMatch(/Something went wrong/);
    });

    it('changes page whether or not a click event came with it', () => {
        const site = mountSite();

        act(() => {
            site.renderVals().goBiz(undefined);
        });
        expect(site.state.page).toBe('biz');

        const preventDefault = vi.fn();

        act(() => {
            site.renderVals().goInv({ preventDefault });
        });
        expect(preventDefault).toHaveBeenCalled();
        expect(site.state.page).toBe('inv');
    });

    it('only opens the borrowing sheet once the figures add up', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
            site.bSet({ rev: 0, exp: 0 });
        });
        act(() => {
            site.bizVals().openSheet();
        });
        expect(site.state.B.sheetOpen).toBeFalsy();

        act(() => {
            site.bSet({ rev: 80_000_000, exp: 62_000_000 });
        });
        act(() => {
            site.bizVals().openSheet();
        });
        expect(site.state.B.sheetOpen).toBe(true);
    });

    it('closes a help answer that was already open', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'help' });
        });

        const first = site.helpVals().groups[0].items[0];

        act(() => {
            first.toggle();
        });
        expect(site.helpVals().groups[0].items[0].open).toBe(true);

        act(() => {
            site.helpVals().groups[0].items[0].toggle();
        });
        expect(site.helpVals().groups[0].items[0].open).toBe(false);
    });

    it('sends a business by phone when the toggle was never touched', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
            site.bSet({
                biz: 'Kigali Coffee',
                contact: '0788123456',
                prov: 'Kigali City',
                dist: 'Gasabo',
                mode: undefined,
            });
        });
        act(() => {
            site.bizVals().submit();
        });

        expect(mocks.post.mock.calls[0][1]).toMatchObject({
            contact_method: 'phone',
            contact: '250788123456',
        });
    });

    it('closes the investor answer that opens with the page', () => {
        const site = mountSite();

        // the first question is open when the page loads
        expect(site.state.faq).toBe(0);

        act(() => {
            site.invVals().faq0();
        });
        expect(site.state.faq).toBe(-1);

        act(() => {
            site.invVals().faq0();
        });
        expect(site.state.faq).toBe(0);
    });

    it('closes a business answer that was already open', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
        });
        act(() => {
            site.bizVals().bfaq1();
        });
        expect(site.state.B.bfaq).toBe(1);

        act(() => {
            site.bizVals().bfaq1();
        });
        expect(site.state.B.bfaq).toBe(-1);
    });
});
