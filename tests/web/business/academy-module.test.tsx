import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import BusinessAcademyModule from '@/pages/business/academy-module';
import type { BusinessAcademyModuleProps } from '@/types/business';
import fixture from '../../../resources/fixtures/ui/business-academy-module.json';

const inertia = vi.hoisted(() => ({
    posts: [] as {
        url: string;
        data: Record<string, unknown>;
        options: Record<string, unknown>;
    }[],
    errors: {} as Record<string, string>,
    processing: false,
}));

vi.mock('@inertiajs/react', async () => {
    const { useState } = await import('react');

    return {
        Head: () => null,
        Link: ({
            href,
            children,
            ...props
        }: Omit<ComponentProps<'a'>, 'href'> & { href: string }) => (
            <a href={href} {...props}>
                {children}
            </a>
        ),
        useHttp: () => ({ submit: () => new Promise(() => undefined) }),
        useForm: <T extends Record<string, unknown>>(initial: T) => {
            const [data, setState] = useState(initial);
            let transform = (current: T): Record<string, unknown> => current;

            return {
                data,
                setData: (key: keyof T, value: unknown) =>
                    setState((current) => ({ ...current, [key]: value })),
                errors: inertia.errors,
                processing: inertia.processing,
                transform: (
                    callback: (current: T) => Record<string, unknown>,
                ) => {
                    transform = callback;
                },
                post: (url: string, options: Record<string, unknown>) => {
                    inertia.posts.push({ url, data: transform(data), options });
                },
            };
        },
    };
});

const props = (
    overrides: Partial<BusinessAcademyModuleProps> = {},
): BusinessAcademyModuleProps => ({
    ...(structuredClone(fixture.props) as BusinessAcademyModuleProps),
    ...overrides,
});

const button = (name: string) => screen.getByRole('button', { name });

/** Turns over every card and marks it known, to the deck's finish screen. */
const throughDeck = async (user: ReturnType<typeof userEvent.setup>) => {
    for (let card = 0; card < fixture.props.cards.length; card++) {
        await user.click(button('Show answer'));
        await user.click(button('Got it'));
    }
};

/** Answers each drawn question with the option at `choice(index)`, moving on after each. */
const answerAll = async (
    user: ReturnType<typeof userEvent.setup>,
    choice: (index: number) => number,
) => {
    for (let index = 0; index < fixture.props.test.length; index++) {
        await user.click(screen.getAllByRole('radio')[choice(index)]);
        await user.click(
            button(
                index === fixture.props.test.length - 1
                    ? 'Review answers'
                    : 'Next question',
            ),
        );
    }
};

beforeEach(() => {
    inertia.posts = [];
    inertia.errors = {};
    inertia.processing = false;
});

describe('Academy module', () => {
    it('turns a card over, earns a star when known and brings a missed card back at the end', async () => {
        const user = userEvent.setup();

        render(<BusinessAcademyModule {...props()} />);

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Keeping your books',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('Card 1 of 9')).toBeInTheDocument();
        expect(screen.getByLabelText('0 stars')).toBeInTheDocument();
        expect(
            screen.queryByText(/^Your books are the record/u),
        ).not.toBeInTheDocument();
        expect(button('Previous')).toBeDisabled();

        /* Tapping the card turns it over and back again. */
        await user.click(screen.getByRole('button', { expanded: false }));
        expect(
            screen.getByText(/^Your books are the record/u),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { expanded: true }));
        expect(
            screen.queryByText(/^Your books are the record/u),
        ).not.toBeInTheDocument();

        await user.click(button('Show answer'));
        await user.click(button('Got it'));
        expect(screen.getByText('Card 2 of 9')).toBeInTheDocument();
        expect(screen.getByLabelText('1 star')).toBeInTheDocument();
        expect(screen.getByText('Nice one! +1 star')).toBeInTheDocument();

        /* Going back takes the star of that card away until it is marked again. */
        await user.click(button('Previous'));
        expect(screen.getByText('Card 1 of 9')).toBeInTheDocument();
        expect(screen.getByLabelText('0 stars')).toBeInTheDocument();
        await user.click(button('Show answer'));
        await user.click(button('Got it'));

        await user.click(button('Show answer'));
        await user.click(button('Still learning'));
        expect(
            screen.getByText('No problem. This card comes back at the end.'),
        ).toBeInTheDocument();

        for (let card = 3; card <= 9; card++) {
            expect(screen.getByText(`Card ${card} of 9`)).toBeInTheDocument();
            await user.click(button('Show answer'));
            await user.click(button('Got it'));
        }

        expect(
            screen.getByText('Practice again: 1 card to go'),
        ).toBeInTheDocument();
        expect(screen.getByText('Same day')).toBeInTheDocument();
        await user.click(button('Show answer'));
        await user.click(button('Still learning'));
        expect(
            screen.getByText('Practice again: 1 card to go'),
        ).toBeInTheDocument();
        await user.click(button('Show answer'));
        await user.click(button('Got it'));

        expect(
            screen.getByRole('heading', { name: 'Deck complete!' }),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('9 stars')).toBeInTheDocument();
        expect(
            screen.getByText('You knew 8 of 9 cards on the first try.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'You are ready for the test: 10 questions, 8 correct answers pass.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();

        await user.click(button('Go through the cards again'));
        expect(screen.getByText('Card 1 of 9')).toBeInTheDocument();
        expect(screen.getByLabelText('0 stars')).toBeInTheDocument();
    });

    it('lets a learner skip the cards to an open test and come back to them', async () => {
        const user = userEvent.setup();

        render(<BusinessAcademyModule {...props()} />);
        await user.click(button('Skip to the test'));
        expect(screen.getByRole('form', { name: 'Test' })).toBeInTheDocument();
        await user.click(button('Back to the cards'));
        expect(screen.getByText('Card 1 of 9')).toBeInTheDocument();
    });

    it('opens the test after the deck, one question at a time, and sends every answer once, for its attempt', async () => {
        const user = userEvent.setup();

        render(<BusinessAcademyModule {...props()} />);
        await throughDeck(user);
        await user.click(button('Start the test'));

        const test = screen.getByRole('form', { name: 'Test' });

        expect(
            within(test).getByText(
                'Answer all 10 questions. 8 correct answers pass.',
            ),
        ).toBeInTheDocument();
        expect(within(test).getAllByRole('group')).toHaveLength(1);
        expect(within(test).getByText('Question 1 of 10')).toBeInTheDocument();
        expect(button('Previous')).toBeDisabled();
        expect(button('Next question')).toBeDisabled();

        await answerAll(user, () => 0);
        expect(
            screen.getByRole('heading', { name: 'Check your answers' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('All answered. Send when you are ready.'),
        ).toBeInTheDocument();

        /* Changing the last answer from the check goes back to that question. */
        await user.click(button('Change answer 10'));
        expect(screen.getByText('Question 10 of 10')).toBeInTheDocument();
        await user.click(screen.getAllByRole('radio')[1]);
        await user.click(screen.getAllByRole('radio')[2]);
        await user.click(button('Previous'));
        expect(screen.getByText('Question 9 of 10')).toBeInTheDocument();
        await user.click(button('Go to question 10'));
        await user.click(button('Review answers'));
        await user.click(button('Send answers'));

        const drawn = fixture.props.test;

        expect(inertia.posts).toHaveLength(1);
        expect(inertia.posts[0].url).toBe('/preview/business-academy-module');
        expect(inertia.posts[0].options).toEqual({
            preserveScroll: true,
            preserveState: true,
        });
        expect(inertia.posts[0].data).toMatchObject({
            attempt: 1,
            identity_context_revision: 3,
            answers: Object.fromEntries(
                drawn.map((question, index) => [
                    question.id,
                    question.options[index === 9 ? 2 : 0].id,
                ]),
            ),
        });
        expect(inertia.posts[0].data.request_id).toMatch(/^[0-9a-f-]{36}$/u);
    });

    it('holds the send until every question is answered', async () => {
        const user = userEvent.setup();

        render(<BusinessAcademyModule {...props()} />);
        await user.click(button('Skip to the test'));
        await user.click(button('Go to question 10'));
        await user.click(screen.getAllByRole('radio')[0]);
        await user.click(button('Review answers'));

        expect(
            screen.getByText('9 questions left to answer.'),
        ).toBeInTheDocument();
        expect(screen.getAllByText('Not answered yet')).toHaveLength(9);
        expect(button('Send answers')).toBeDisabled();
    });

    it('marks a refused answer and the form, and shows the send in progress', async () => {
        const question = fixture.props.test[0].id;

        inertia.errors = {
            [`answers.${question}`]: 'Choose one answer.',
            form: 'This test was already answered. Reload the page for your next attempt.',
        };
        inertia.processing = true;
        const user = userEvent.setup();

        render(<BusinessAcademyModule {...props()} />);
        await throughDeck(user);
        await user.click(button('Start the test'));

        expect(screen.getByText('Choose one answer.')).toBeInTheDocument();
        expect(
            screen.getByText(
                'This test was already answered. Reload the page for your next attempt.',
            ),
        ).toBeInTheDocument();
        await answerAll(user, () => 0);
        expect(screen.getByText('Choose one answer.')).toBeInTheDocument();
        expect(button('Sending…')).toHaveAttribute('aria-busy', 'true');
    });

    it('says when a failed test reopens and offers no test meanwhile', async () => {
        const user = userEvent.setup();

        render(
            <BusinessAcademyModule
                {...props({
                    status: {
                        state: 'waiting',
                        attempts: 1,
                        best: 6,
                        retake_at: '2026-11-11T08:00:00Z',
                    },
                    attempt: null,
                    test: null,
                    last: {
                        module: 'bookkeeping',
                        attempt: 1,
                        score: 6,
                        passed: false,
                        submitted_at: '2026-11-10T08:00:00Z',
                    },
                })}
            />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            /^Not passed: 6 of 10\. The test reopens on .+ with new questions\.$/u,
        );
        expect(
            screen.queryByRole('button', { name: 'Skip to the test' }),
        ).not.toBeInTheDocument();
        await throughDeck(user);
        expect(
            screen.queryByRole('button', { name: 'Start the test' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText(
                'Come back to these cards whenever you want a refresher.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Back to the Academy' }),
        ).toHaveAttribute('href', '/preview/business-academy');
    });

    it('offers the next test straight after a failed one has reopened', () => {
        render(
            <BusinessAcademyModule
                {...props({
                    status: {
                        state: 'open',
                        attempts: 1,
                        best: 6,
                        retake_at: null,
                    },
                    attempt: 2,
                    last: {
                        module: 'bookkeeping',
                        attempt: 1,
                        score: 6,
                        passed: false,
                        submitted_at: '2026-11-09T08:00:00Z',
                    },
                })}
            />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            'Not passed: 6 of 10. Your next test is ready below.',
        );
    });

    it('keeps the cards of a passed module to revise', () => {
        render(
            <BusinessAcademyModule
                {...props({
                    status: {
                        state: 'passed',
                        attempts: 2,
                        best: 9,
                        retake_at: null,
                    },
                    attempt: null,
                    test: null,
                    last: {
                        module: 'bookkeeping',
                        attempt: 2,
                        score: 9,
                        passed: true,
                        submitted_at: '2026-11-10T08:00:00Z',
                    },
                })}
            />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            'Passed with 9 of 10.',
        );
        expect(
            screen.getByText(
                'You have passed this module. Its cards stay here to revise.',
            ),
        ).toBeInTheDocument();
    });

    it('shows a preview with cards only', () => {
        render(
            <BusinessAcademyModule
                {...props({
                    role: 'preview',
                    status: null,
                    attempt: null,
                    test: null,
                    last: null,
                })}
            />,
        );

        expect(
            screen.getByText(
                'This module belongs to the next stage. Its test opens when you reach it.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-academy',
        );
    });
});
