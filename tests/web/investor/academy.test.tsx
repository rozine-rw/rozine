import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { ACADEMY } from '@/components/investor/profile/academy';
import en from '@/lib/i18n/catalogs/en';
import InvestorProfile from '@/pages/investor/profile';
import type { InvestorProfileProps } from '@/types/investor';
import profileFixture from '../../../resources/fixtures/ui/investor-profile.json';
import { resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const STORAGE_KEY = 'rozine.investor.academy.v1';

const profile = () =>
    structuredClone(profileFixture.props) as InvestorProfileProps;

const LESSONS = ACADEMY.flatMap((group) => group.lessons);

beforeEach(() => {
    resetInertia();
    setWide(false);
    window.localStorage.clear();
});

describe('Investor Academy', () => {
    it('shows the Learning card under the profile menu with nothing done yet', () => {
        render(<InvestorProfile {...profile()} />);

        expect(
            screen.getByRole('heading', { name: 'Learning' }),
        ).toBeInTheDocument();

        const card = screen.getByRole('button', {
            name: /Investor Academy/u,
        });

        expect(card).toHaveTextContent('Learn how Rozine notes work');
        expect(card).toHaveTextContent('0 of 8 lessons done');
        expect(card).toHaveTextContent('4 tracks');
    });

    it('opens the Academy as its own phone page with its tracks, and goes back', async () => {
        const user = userEvent.setup();

        render(<InvestorProfile {...profile()} />);
        await user.click(
            screen.getByRole('button', { name: /Investor Academy/u }),
        );

        expect(
            screen.getByRole('heading', { level: 1, name: 'Investor Academy' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('navigation', { name: 'Profile menu' }),
        ).not.toBeInTheDocument();
        expect(
            screen
                .getAllByRole('heading', { level: 3 })
                .map((heading) => heading.textContent),
        ).toEqual([
            'Rozine basics',
            'Our mission',
            'Investing 101',
            'Investing on Rozine',
        ]);

        const basics = screen.getByRole('region', { name: 'Rozine basics' });

        expect(basics).toHaveTextContent('0/3 done');
        expect(
            within(basics).getByRole('button', { name: /Rozine notes/u }),
        ).toHaveTextContent('Start');

        await user.click(screen.getByRole('button', { name: 'Back' }));
        expect(
            screen.getByRole('navigation', { name: 'Profile menu' }),
        ).toBeInTheDocument();
    });

    it('plays a lesson: card, quick check, a missed answer comes back once, then done', async () => {
        const user = userEvent.setup();

        render(<InvestorProfile {...profile()} />);
        await user.click(
            screen.getByRole('button', { name: /Investor Academy/u }),
        );
        await user.click(screen.getByRole('button', { name: /Rozine notes/u }));

        const lesson = screen.getByRole('region', { name: 'Rozine notes' });

        expect(lesson).toHaveTextContent('1 of 2');
        expect(lesson).toHaveTextContent('What is a note?');
        await user.click(screen.getByRole('button', { name: 'Got it' }));
        expect(lesson).toHaveTextContent('Quick check');
        await user.click(
            screen.getByRole('button', {
                name: 'Lend to a verified business for a fixed return',
            }),
        );
        expect(screen.getByRole('status')).toHaveTextContent('Correct');
        expect(JSON.parse(window.localStorage.getItem(STORAGE_KEY)!)).toEqual({
            notes: 1,
        });
        await user.click(screen.getByRole('button', { name: 'Next' }));

        expect(lesson).toHaveTextContent('Returns and repayment');
        await user.click(screen.getByRole('button', { name: 'Got it' }));
        await user.click(
            screen.getByRole('button', {
                name: 'In one lump sum after a year',
            }),
        );
        expect(screen.getByRole('status')).toHaveTextContent('Not quite');
        expect(lesson).toHaveTextContent('2 of 3');
        await user.click(screen.getByRole('button', { name: 'Next' }));

        expect(lesson).toHaveTextContent('3 of 3');
        expect(lesson).toHaveTextContent('Returns and repayment');
        await user.click(screen.getByRole('button', { name: 'Got it' }));
        await user.click(
            screen.getByRole('button', { name: 'Only if you ask for it' }),
        );
        expect(screen.getByRole('status')).toHaveTextContent('Not quite');
        expect(lesson).toHaveTextContent('3 of 3');
        await user.click(screen.getByRole('button', { name: 'Finish' }));

        expect(lesson).not.toHaveTextContent('Lesson complete');
        expect(lesson).toHaveTextContent('Not quite yet');
        expect(lesson).toHaveTextContent(
            'You passed 1 of 2 quick checks in Rozine notes. Try the lesson again to finish it.',
        );
        expect(screen.getByRole('progressbar')).toHaveValue(3);
        await user.click(
            screen.getByRole('button', { name: 'Try the lesson again' }),
        );
        expect(lesson).toHaveTextContent('1 of 2');
        expect(lesson).toHaveTextContent('What is a note?');
        await user.click(screen.getByRole('button', { name: 'Got it' }));
        await user.click(
            screen.getByRole('button', {
                name: 'Lend to a verified business for a fixed return',
            }),
        );
        await user.click(screen.getByRole('button', { name: 'Next' }));
        await user.click(screen.getByRole('button', { name: 'Got it' }));
        await user.click(
            screen.getByRole('button', {
                name: 'Monthly, over the term of the note',
            }),
        );
        await user.click(screen.getByRole('button', { name: 'Finish' }));
        expect(lesson).toHaveTextContent('Lesson complete');
        expect(lesson).toHaveTextContent('You finished Rozine notes.');
        await user.click(
            screen.getByRole('button', { name: 'Back to the Academy' }),
        );

        expect(
            screen.getByRole('button', { name: /Rozine notes/u }),
        ).toHaveTextContent('Done ✓');
    });

    it('marks a lesson done and the card complete once every topic is passed', async () => {
        const user = userEvent.setup();
        const done = Object.fromEntries(
            LESSONS.map((lesson) => [lesson.key, 2]),
        );

        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({ ...done, repayments: 1 }),
        );
        render(<InvestorProfile {...profile()} />);

        expect(
            screen.getByRole('button', { name: /Investor Academy/u }),
        ).toHaveTextContent('Pick up where you left off');
        await user.click(
            screen.getByRole('button', { name: /Investor Academy/u }),
        );
        await user.click(
            screen.getByRole('button', { name: /Repayments and payouts/u }),
        );
        await user.click(screen.getByRole('button', { name: 'Got it' }));
        await user.click(
            screen.getByRole('button', {
                name: 'Scheduled monthly over the term',
            }),
        );
        await user.click(screen.getByRole('button', { name: 'Next' }));
        await user.click(screen.getByRole('button', { name: 'Got it' }));
        await user.click(
            screen.getByRole('button', { name: 'Reinvest it or withdraw it' }),
        );
        await user.click(screen.getByRole('button', { name: 'Finish' }));
        await user.click(screen.getByRole('button', { name: 'Close lesson' }));

        expect(
            screen.getByRole('region', { name: 'Investing on Rozine' }),
        ).toHaveTextContent('2/2 done');
        expect(
            screen.getByRole('button', { name: /Repayments and payouts/u }),
        ).toHaveTextContent('Done ✓');
        await user.click(screen.getByRole('button', { name: 'Back' }));
        expect(
            screen.getByRole('button', { name: /Investor Academy/u }),
        ).toHaveTextContent('All lessons complete');
        expect(
            screen.getByRole('button', { name: /Investor Academy/u }),
        ).toHaveTextContent('8 of 8 lessons done');
    });

    it('ignores unreadable or blocked storage', async () => {
        const user = userEvent.setup();

        window.localStorage.setItem(STORAGE_KEY, '[1, 2]');
        const { unmount } = render(<InvestorProfile {...profile()} />);

        expect(
            screen.getByRole('button', { name: /Investor Academy/u }),
        ).toHaveTextContent('0 of 8 lessons done');
        unmount();

        window.localStorage.setItem(STORAGE_KEY, '{"notes": 1, "x": "y"}');
        const view = render(<InvestorProfile {...profile()} />);

        expect(
            screen.getByRole('button', { name: /Investor Academy/u }),
        ).toHaveTextContent('Pick up where you left off');
        view.unmount();

        window.localStorage.setItem(STORAGE_KEY, 'not json');
        const setItem = vi
            .spyOn(Storage.prototype, 'setItem')
            .mockImplementation(() => {
                throw new Error('blocked');
            });

        render(<InvestorProfile {...profile()} />);
        await user.click(
            screen.getByRole('button', { name: /Investor Academy/u }),
        );
        await user.click(
            screen.getByRole('button', { name: /Diversification/u }),
        );
        await user.click(screen.getByRole('button', { name: 'Got it' }));
        await user.click(
            screen.getByRole('button', {
                name: 'Reduce the effect of one business',
            }),
        );
        expect(screen.getByRole('status')).toHaveTextContent('Correct');
        setItem.mockRestore();
    });

    it('opens the Academy beside the menu on a wide screen', async () => {
        const user = userEvent.setup();

        setWide(true);
        render(<InvestorProfile {...profile()} />);
        await user.click(
            screen.getByRole('button', { name: /Investor Academy/u }),
        );

        expect(
            screen.getByRole('navigation', { name: 'Profile menu' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { level: 2, name: 'Investor Academy' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Back' }),
        ).not.toBeInTheDocument();
    });

    it('gives every topic a card, a question and three answers in every language', () => {
        const codes = LESSONS.flatMap((lesson) => [
            `investor.academy.lesson.${lesson.key}.title`,
            `investor.academy.lesson.${lesson.key}.sub`,
            ...[1, 2].flatMap((topic) =>
                ['title', 'body', 'q', 'option1', 'option2', 'option3'].map(
                    (part) =>
                        `investor.academy.lesson.${lesson.key}.topic${topic}.${part}`,
                ),
            ),
        ]);

        expect(codes.filter((code) => !(code in en))).toEqual([]);
    });
});
