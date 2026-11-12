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

/** Turns through every card to the last one. */
const toLastCard = async (user: ReturnType<typeof userEvent.setup>) => {
    for (let card = 1; card < fixture.props.cards.length; card++) {
        await user.click(button('Next'));
    }
};

beforeEach(() => {
    inertia.posts = [];
    inertia.errors = {};
    inertia.processing = false;
});

describe('Academy module', () => {
    it('turns each flashcard over and moves between them', async () => {
        const user = userEvent.setup();

        render(<BusinessAcademyModule {...props()} />);

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Keeping your books',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('Card 1 of 9')).toBeInTheDocument();
        expect(screen.getByText('Books')).toBeInTheDocument();
        expect(
            screen.queryByText(/^Your books are the record/u),
        ).not.toBeInTheDocument();
        expect(button('Previous')).toBeDisabled();

        await user.click(button('Show answer'));
        expect(
            screen.getByText(/^Your books are the record/u),
        ).toBeInTheDocument();
        await user.click(button('Show card'));
        expect(
            screen.queryByText(/^Your books are the record/u),
        ).not.toBeInTheDocument();

        await user.click(button('Next'));
        expect(screen.getByText('Card 2 of 9')).toBeInTheDocument();
        await user.click(button('Show answer'));
        await user.click(button('Previous'));
        expect(screen.getByText('Card 1 of 9')).toBeInTheDocument();
        expect(button('Show answer')).toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('opens the test after the last card and sends every answer once, for its attempt', async () => {
        const user = userEvent.setup();

        render(<BusinessAcademyModule {...props()} />);
        expect(
            screen.queryByRole('button', { name: 'Start the test' }),
        ).not.toBeInTheDocument();
        await toLastCard(user);
        await user.click(button('Start the test'));

        const test = screen.getByRole('form', { name: 'Test' });

        expect(
            within(test).getByText(
                'Answer all 10 questions. 8 correct answers pass.',
            ),
        ).toBeInTheDocument();
        expect(
            within(test).getByText('10 questions left to answer.'),
        ).toBeInTheDocument();
        expect(button('Send answers')).toBeDisabled();

        const groups = within(test).getAllByRole('group');

        expect(groups).toHaveLength(10);

        for (const group of groups.slice(0, 9)) {
            await user.click(within(group).getAllByRole('radio')[0]);
        }

        expect(
            within(test).getByText('1 question left to answer.'),
        ).toBeInTheDocument();
        await user.click(within(groups[9]).getAllByRole('radio')[1]);
        await user.click(within(groups[9]).getAllByRole('radio')[2]);
        expect(
            within(test).queryByText(/left to answer/u),
        ).not.toBeInTheDocument();
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

    it('marks a refused answer and the form, and shows the send in progress', async () => {
        const question = fixture.props.test[0].id;

        inertia.errors = {
            [`answers.${question}`]: 'Choose one answer.',
            form: 'This test was already answered. Reload the page for your next attempt.',
        };
        inertia.processing = true;
        const user = userEvent.setup();

        render(<BusinessAcademyModule {...props()} />);
        await toLastCard(user);
        await user.click(button('Start the test'));

        expect(screen.getByText('Choose one answer.')).toBeInTheDocument();
        expect(
            screen.getByText(
                'This test was already answered. Reload the page for your next attempt.',
            ),
        ).toBeInTheDocument();
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
        await toLastCard(user);
        expect(
            screen.queryByRole('button', { name: 'Start the test' }),
        ).not.toBeInTheDocument();
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
