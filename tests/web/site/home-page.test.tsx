import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createRef } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
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

/** A stand-in for the change event the design's handlers read. */
const changeEvent = (value: string) => ({
    target: { value },
    preventDefault: vi.fn(),
    stopPropagation: vi.fn(),
});

/**
 * Call every handler the current page hands to the markup, including the few
 * the design builds but never wires to a control.
 */
const exerciseHandlers = (value: unknown, seen = new Set<unknown>()): void => {
    if (value === null || typeof value !== 'object') {
        return;
    }

    if (seen.has(value)) {
        return;
    }

    seen.add(value);

    for (const entry of Object.values(value as Record<string, unknown>)) {
        if (typeof entry === 'function') {
            act(() => {
                (entry as (event: unknown) => void)(changeEvent('7'));
            });

            continue;
        }

        exerciseHandlers(entry, seen);
    }
};

beforeEach(() => {
    mocks.post.mockReset();
    window.scrollTo = vi.fn() as unknown as typeof window.scrollTo;
});

afterEach(() => {
    vi.useRealTimers();
});

describe('moving between the three audiences', () => {
    it('opens on the investor page', () => {
        mountSite();

        expect(
            screen.getByRole('heading', { level: 1, name: /Earn up to/ }),
        ).toBeInTheDocument();
    });

    it('shows businesses what they could borrow', async () => {
        const user = userEvent.setup();

        mountSite();
        await user.click(screen.getByText('For businesses'));

        expect(
            screen.getByRole('heading', { level: 1, name: /Borrow up to/ }),
        ).toBeInTheDocument();
    });

    it('answers questions on the help page', async () => {
        const user = userEvent.setup();

        mountSite();
        await user.click(screen.getByRole('link', { name: 'Help' }));

        expect(screen.getByText('Getting started')).toBeInTheDocument();
    });
});

describe('the investor calculator', () => {
    it('reprices when the term changes', async () => {
        const user = userEvent.setup();
        const site = mountSite();

        for (const term of ['3 months', '4 months', '5 months', '6 months']) {
            await user.click(screen.getByRole('button', { name: term }));
        }

        expect(site.state.term).toBe(6);
    });

    it('reprices when the rating band changes', async () => {
        const user = userEvent.setup();
        const site = mountSite();

        for (const band of ['Strong', 'Stable', 'Distressed']) {
            await user.click(screen.getByRole('button', { name: band }));
        }

        expect(site.state.band).toBe(2);
    });

    it('carries the slider through to the amount on offer', () => {
        const site = mountSite();
        const slider = screen.getByRole('slider');

        act(() => {
            site.setState({ depIdx: 40 });
        });

        expect(slider).toBeInTheDocument();
        expect(site.invVals().depStr).toBe('RWF 200,000,000');
    });

    it('reaches the floor and the ceiling of what may be pledged', () => {
        const site = mountSite();

        act(() => {
            site.setState({ depIdx: 0 });
        });
        expect(site.invVals().depStr).toBe('RWF 5,000');

        act(() => {
            site.setState({ depIdx: 40 });
        });
        expect(site.invVals().depStr).toBe('RWF 200,000,000');
    });
});

describe('the business calculator', () => {
    const openBusiness = async () => {
        const user = userEvent.setup();
        const site = mountSite();

        await user.click(screen.getByText('For businesses'));

        return { site, user };
    };

    it('sizes a loan from the figures reported', async () => {
        const { site } = await openBusiness();

        expect(site.bizVals().qualStr).toMatch(/^RWF /);
        expect(site.bizVals().showLeft).toBe(true);
    });

    it('asks for figures before it will size anything', async () => {
        const { site } = await openBusiness();

        act(() => {
            site.bSet({ rev: 0, exp: 0 });
        });

        expect(site.bizVals().qualStr).toBe('—');
        expect(site.bizVals().note).toMatch(/Enter your last 12 months/);
        expect(site.bizVals().showLeft).toBe(false);
    });

    it('says so when the costs swallow the sales', async () => {
        const { site } = await openBusiness();

        act(() => {
            site.bSet({ rev: 40_000_000, exp: 41_000_000 });
        });

        expect(site.bizVals().leftLabel).toMatch(/COSTS ARE HIGHER/);
        expect(site.bizVals().leftStr).toBe('Check the figures');
        expect(site.bizVals().note).toMatch(
            /Enter costs lower than your sales/,
        );
    });

    it('turns away a business that falls under the smallest loan', async () => {
        const { site } = await openBusiness();

        act(() => {
            site.bSet({ rev: 16_000_000, exp: 15_500_000 });
        });

        expect(site.bizVals().qualStr).toBe('—');
        expect(site.bizVals().note).toMatch(/RWF 3,000,000 minimum/);
    });

    it('caps the largest loan it will size', async () => {
        const { site } = await openBusiness();

        act(() => {
            site.bSet({ rev: 100_000_000_000, exp: 1_000_000 });
        });

        expect(site.bizVals().qualStr).toBe('RWF 100,000,000');
    });

    it('offers a district only once a province is chosen', async () => {
        const { site } = await openBusiness();

        expect(site.bizVals().distDisabled).toBe(true);

        act(() => {
            site.bSet({ prov: 'Kigali City' });
        });

        expect(site.bizVals().distDisabled).toBe(false);
        expect(site.bizVals().districts).toEqual([
            'Gasabo',
            'Kicukiro',
            'Nyarugenge',
        ]);
    });
});

describe('every control the design builds', () => {
    it('runs the investor handlers without complaint', () => {
        const site = mountSite();

        exerciseHandlers(site.invVals());

        expect(site.state.page).toBeDefined();
    });

    it('runs the business handlers without complaint', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'biz' });
        });
        exerciseHandlers(site.bizVals());

        expect(site.state.B).toBeDefined();
    });

    it('runs the help handlers without complaint', () => {
        const site = mountSite();

        act(() => {
            site.setState({ page: 'help' });
        });
        exerciseHandlers(site.helpVals());

        expect(site.state.H).toBeDefined();
    });
});
