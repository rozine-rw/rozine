import { fireEvent, render, screen, within } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import Home from '@/pages/home';

vi.mock('@inertiajs/react', () => import('./landing-inertia'));

const header = () => screen.getByRole('banner');
const tab = (name: string) => within(header()).getByRole('button', { name });
const heading = (name: string | RegExp) =>
    screen.getByRole('heading', { level: 1, name });

beforeEach(() => {
    window.history.replaceState(null, '', '/');
    vi.spyOn(window, 'scrollTo').mockImplementation(() => undefined);
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe('the landing page views', () => {
    it('opens on Invest with its calculator', () => {
        render(<Home />);

        expect(heading(/Earn up to 15% lending/)).toBeInTheDocument();
        expect(tab('Invest')).toHaveAttribute('aria-current', 'page');
        expect(screen.getByTestId('earn-back')).toHaveTextContent(
            'RWF 111,730',
        );
        expect(
            screen.getByRole('heading', {
                name: 'The first raises will fill fast.',
            }),
        ).toBeInTheDocument();
    });

    it('lists what every business on Rozine has', () => {
        render(<Home />);

        expect(
            screen.getByText('Every business on Rozine has:'),
        ).toBeInTheDocument();
        const standards = screen
            .getAllByRole('listitem')
            .map((item) => item.textContent);

        expect(standards).toEqual(
            expect.arrayContaining([
                '3 years of bank records',
                'RWF 30M+ in yearly revenue',
                'Books checked in person by an accountant',
            ]),
        );
    });

    it('switches to every tab from the header and gives each its own address', () => {
        render(<Home />);

        fireEvent.click(tab('Businesses'));
        expect(
            heading('Raise up to RWF 100M. No collateral needed.'),
        ).toBeInTheDocument();
        expect(window.location.hash).toBe('#businesses');
        expect(
            screen.getByRole('heading', {
                name: 'Be among the first businesses to raise.',
            }),
        ).toBeInTheDocument();

        fireEvent.click(tab('How it works'));
        expect(
            heading(
                "Your money sits in a separate bank account, never in Rozine's.",
            ),
        ).toBeInTheDocument();
        expect(window.location.hash).toBe('#how-it-works');

        fireEvent.click(tab('Help'));
        expect(
            heading('Every question, answered plainly.'),
        ).toBeInTheDocument();
        expect(window.location.hash).toBe('#help');

        fireEvent.click(tab('Invest'));
        expect(heading(/Earn up to 15%/)).toBeInTheDocument();
        expect(window.location.hash).toBe('');
        expect(window.scrollTo).toHaveBeenCalledWith(0, 0);
    });

    it('takes the logo, "See how it works" and "See what you could raise" to their views', () => {
        render(<Home />);

        fireEvent.click(
            screen.getByRole('button', { name: 'See how it works' }),
        );
        expect(
            heading(/Your money sits in a separate bank account/),
        ).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Rozine home' }));
        expect(heading(/Earn up to/)).toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('button', { name: 'See how it all works →' }),
        );
        expect(
            heading(/Your money sits in a separate bank account/),
        ).toBeInTheDocument();

        fireEvent.click(tab('Invest'));
        fireEvent.click(
            screen.getByRole('button', { name: 'See what you could raise →' }),
        );
        expect(heading(/Raise up to RWF 100M/)).toBeInTheDocument();
    });

    it('points every waitlist call to action at the form', () => {
        render(<Home />);

        for (const link of screen.getAllByRole('link', {
            name: /Join the waitlist/,
        })) {
            expect(link).toHaveAttribute('href', '#join');
        }

        fireEvent.click(tab('Businesses'));
        expect(
            screen.getByRole('link', { name: 'Join the business waitlist →' }),
        ).toHaveAttribute('href', '#join');
    });

    it('keeps the address when the open tab is chosen again', () => {
        render(<Home />);

        fireEvent.click(tab('Help'));
        const before = window.history.length;
        fireEvent.click(tab('Help'));

        expect(window.location.hash).toBe('#help');
        expect(window.history.length).toBe(before);
    });
});

describe('the Invest calculator', () => {
    it('follows the amount, the term and the rating', () => {
        render(<Home />);

        fireEvent.change(
            screen.getByRole('slider', { name: 'Amount you put in' }),
            {
                target: { value: '1000000' },
            },
        );
        expect(screen.getByTestId('earn-back')).toHaveTextContent(
            'RWF 1,117,300',
        );

        const term = within(screen.getByRole('group', { name: 'Term' }));
        fireEvent.click(term.getByRole('button', { name: '3 mo' }));
        expect(term.getByRole('button', { name: '3 mo' })).toHaveAttribute(
            'aria-pressed',
            'true',
        );
        expect(screen.getByTestId('earn-back')).toHaveTextContent(
            'RWF 1,114,750',
        );

        const rating = within(screen.getByRole('group', { name: 'Rating' }));
        fireEvent.click(rating.getByRole('button', { name: 'Strong' }));
        expect(screen.getByTestId('earn-back')).toHaveTextContent(
            'RWF 1,106,250',
        );
        fireEvent.click(rating.getByRole('button', { name: 'Distressed' }));
        expect(screen.getByTestId('earn-back')).toHaveTextContent(
            'RWF 1,123,250',
        );
    });
});

describe('the Businesses view', () => {
    it('estimates the raise from yearly revenue', () => {
        render(<Home />);
        fireEvent.click(tab('Businesses'));

        expect(screen.getByTestId('raise-max')).toHaveTextContent('RWF 42M');
        fireEvent.change(
            screen.getByRole('slider', { name: 'Your yearly revenue' }),
            {
                target: { value: '400000000' },
            },
        );
        expect(screen.getByTestId('raise-max')).toHaveTextContent('RWF 100M');
        expect(
            screen.getByText('The RWF 100M maximum per raise'),
        ).toBeInTheDocument();
    });

    it('opens one business question at a time and can close it', () => {
        render(<Home />);
        fireEvent.click(tab('Businesses'));

        const first = screen.getByRole('button', {
            name: /Do I need collateral\?/,
        });
        expect(first).toHaveAttribute('aria-expanded', 'true');

        const last = screen.getByRole('button', {
            name: /Can I borrow again\?/,
        });
        fireEvent.click(last);
        expect(last).toHaveAttribute('aria-expanded', 'true');
        expect(first).toHaveAttribute('aria-expanded', 'false');
        expect(
            screen.getByText(
                'Yes. After a clean repayment, you can raise again straight away, up to 1.5 times your last loan.',
            ),
        ).toBeInTheDocument();

        fireEvent.click(last);
        expect(last).toHaveAttribute('aria-expanded', 'false');
    });
});

describe('the How it works view', () => {
    it('shows the money strip once to readers and the journey of six steps', () => {
        render(<Home />);
        fireEvent.click(tab('How it works'));

        expect(screen.getAllByRole('img', { name: 'Livestock' })).toHaveLength(
            1,
        );
        expect(
            screen.getByRole('heading', { name: 'Your money is back' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Day 31')).toBeInTheDocument();
        expect(screen.getByText('15%, less as you grow')).toBeInTheDocument();
    });
});

describe('the Help view', () => {
    it('switches topics and opens the first question of each', () => {
        render(<Home />);
        fireEvent.click(tab('Help'));

        const topics = within(
            screen.getByRole('tablist', { name: 'Help topics' }),
        );
        expect(topics.getByRole('tab', { name: 'Investing' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(
            screen.getByRole('button', { name: /Is my money guaranteed\?/ }),
        ).toHaveAttribute('aria-expanded', 'true');

        fireEvent.click(
            screen.getByRole('button', { name: /Is 15% per year\?/ }),
        );
        fireEvent.click(topics.getByRole('tab', { name: 'Account' }));
        expect(topics.getByRole('tab', { name: 'Account' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(
            screen.getByRole('button', { name: /Who can invest\?/ }),
        ).toHaveAttribute('aria-expanded', 'true');
        expect(
            screen.getAllByRole('link', { name: 'admin@rozine.rw' })[0],
        ).toHaveAttribute('href', 'mailto:admin@rozine.rw');

        fireEvent.click(topics.getByRole('tab', { name: 'Your money' }));
        expect(
            screen.getByRole('button', { name: /Where is my money held\?/ }),
        ).toBeInTheDocument();
        fireEvent.click(topics.getByRole('tab', { name: 'For businesses' }));
        expect(
            screen.getByRole('button', { name: /Do I need collateral\?/ }),
        ).toBeInTheDocument();
    });
});
