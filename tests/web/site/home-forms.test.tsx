import { act, fireEvent, render, screen, within } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import Home from '@/pages/home';
import { landing } from './landing-inertia';
import type { Controls, PostOptions } from './landing-inertia';

vi.mock('@inertiajs/react', () => import('./landing-inertia'));

const JOINED = {
    queue_number: '#1204',
    position: 1204,
    referral_link: 'https://rozine.rw/join?ref=diane1204',
};

const formElement = () =>
    screen.getByRole('form', { name: 'Join the waitlist' });
const form = () => within(formElement());
const fill = (name: string, contact: string) => {
    fireEvent.change(form().getByRole('textbox', { name: /name/i }), {
        target: { value: name },
    });
    fireEvent.change(
        form().getByRole('textbox', { name: 'Phone number or email' }),
        {
            target: { value: contact },
        },
    );
};
const send = () =>
    fireEvent.click(
        form().getByRole('button', {
            name: /Join the (investor|business) waitlist/,
        }),
    );

/** Answer the next post with `run`, which may resolve, reject or report errors. */
const answerWith = (
    run: (options: PostOptions, controls: Controls) => Promise<unknown>,
) => landing.answer.mockImplementationOnce(run);

beforeEach(() => {
    landing.posts = [];
    landing.answer.mockReset();
    landing.answer.mockImplementation(() => new Promise(() => undefined));
    window.history.replaceState(null, '', '/');
    vi.spyOn(window, 'scrollTo').mockImplementation(() => undefined);
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe('the waitlist form', () => {
    it('checks the name and contact before sending anything', () => {
        render(<Home />);

        send();
        expect(screen.getByRole('alert')).toHaveTextContent('Add your name.');

        fill('Diane Uwase', '12345');
        send();
        expect(screen.getByRole('alert')).toHaveTextContent(
            'Add a valid phone number or email.',
        );
        expect(landing.posts).toHaveLength(0);

        fireEvent.change(
            form().getByRole('textbox', { name: 'Phone number or email' }),
            {
                target: { value: '0788 123 456' },
            },
        );
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('sends an investor signup, shows a loader, then the place in the queue and the link', async () => {
        let resolve: (value: unknown) => void = () => undefined;
        answerWith(
            (_options, controls) =>
                new Promise((done) => {
                    resolve = (value) => {
                        controls.finish();
                        done(value);
                    };
                }),
        );
        render(<Home />);

        fill('Diane Uwase', '+250 788 123 456');
        send();

        expect(landing.posts).toEqual([
            {
                url: '/waitlist',
                data: {
                    role: 'investor',
                    name: 'Diane Uwase',
                    contact: '+250 788 123 456',
                    ref: '',
                },
            },
        ]);
        const busy = form().getByRole('button', { name: 'Joining…' });
        expect(busy).toBeDisabled();

        fireEvent.submit(formElement());
        expect(landing.posts).toHaveLength(1);

        await act(async () => resolve(JOINED));

        const done = screen.getByRole('status');
        expect(done).toHaveTextContent("YOU'RE IN");
        expect(done).toHaveTextContent('#1,204');
        expect(done).toHaveTextContent(
            'Every person who joins through it moves you up the list.',
        );
        expect(done).toHaveTextContent('rozine.rw/join?ref=diane1204');
    });

    it('copies the share link', async () => {
        const writeText = vi.fn(() => Promise.resolve());
        Object.assign(navigator, { clipboard: { writeText } });
        answerWith(() => Promise.resolve(JOINED));
        render(<Home />);

        fill('Diane Uwase', 'diane@example.com');
        await act(async () => send());
        fireEvent.click(screen.getByRole('button', { name: 'Copy' }));

        expect(writeText).toHaveBeenCalledWith(
            'https://rozine.rw/join?ref=diane1204',
        );
        expect(
            screen.getByRole('button', { name: 'Copied' }),
        ).toBeInTheDocument();
    });

    it('still says copied when the browser will not copy, leaving the link on screen', async () => {
        Object.assign(navigator, {
            clipboard: {
                writeText: () => {
                    throw new Error('blocked');
                },
            },
        });
        answerWith(() => Promise.resolve(JOINED));
        render(<Home />);

        fill('Diane Uwase', 'diane@example.com');
        await act(async () => send());
        fireEvent.click(screen.getByRole('button', { name: 'Copy' }));

        expect(
            screen.getByRole('button', { name: 'Copied' }),
        ).toBeInTheDocument();
    });

    it('keeps the link on screen when the browser has no clipboard', async () => {
        Object.assign(navigator, { clipboard: undefined });
        answerWith(() => Promise.resolve(JOINED));
        render(<Home />);

        fill('Diane Uwase', 'diane@example.com');
        await act(async () => send());
        fireEvent.click(screen.getByRole('button', { name: 'Copy' }));

        expect(
            screen.getByRole('button', { name: 'Copied' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'rozine.rw/join?ref=diane1204',
        );
    });

    it('signs a business up from the Businesses view, in its own words', async () => {
        answerWith(() => Promise.resolve({ ...JOINED, position: 7 }));
        render(<Home />);
        fireEvent.click(
            within(screen.getByRole('banner')).getByRole('button', {
                name: 'Businesses',
            }),
        );

        expect(
            form().getByRole('button', { name: 'I want to borrow' }),
        ).toHaveAttribute('aria-pressed', 'true');
        send();
        expect(screen.getByRole('alert')).toHaveTextContent(
            'Add your business name.',
        );

        fill('Kigali Coffee Roasters', 'owner@kcr.rw');
        await act(async () => send());

        expect(landing.posts[0].data.role).toBe('business');
        expect(screen.getByRole('status')).toHaveTextContent('#7');
        expect(screen.getByRole('status')).toHaveTextContent(
            'confirm your business qualifies',
        );
    });

    it('lets the visitor switch who they are joining as', () => {
        render(<Home />);

        fireEvent.click(
            form().getByRole('button', { name: 'I want to borrow' }),
        );
        expect(
            form().getByRole('textbox', { name: 'Business name' }),
        ).toBeInTheDocument();
        expect(
            form().getByRole('button', { name: 'Join the business waitlist' }),
        ).toBeInTheDocument();

        fireEvent.click(
            form().getByRole('button', { name: 'I want to invest' }),
        );
        expect(
            form().getByRole('textbox', { name: 'Your name' }),
        ).toBeInTheDocument();
    });

    it('carries the code from a shared link', () => {
        window.history.replaceState(null, '', '/?ref=diane1204<x>');
        render(<Home />);

        fill('Eric', 'eric@example.com');
        send();

        expect(landing.posts[0].data.ref).toBe('diane1204x');
    });

    it('shows what the server refused', async () => {
        answerWith((_options, controls) => {
            controls.setErrors({
                contact: 'This phone number is already on the waitlist.',
            });
            controls.finish();

            return Promise.reject(new Error('422'));
        });
        render(<Home />);

        fill('Diane', '0788123456');
        await act(async () => send());

        expect(screen.getByRole('alert')).toHaveTextContent(
            'This phone number is already on the waitlist.',
        );
        expect(
            form().getByRole('textbox', { name: 'Phone number or email' }),
        ).toHaveAttribute('aria-invalid', 'true');

        fireEvent.change(form().getByRole('textbox', { name: 'Your name' }), {
            target: { value: 'Diane U' },
        });
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it.each([
        [429, 'Too many tries. Wait a minute and try again.'],
        [500, 'Something went wrong on our side. Please try again.'],
    ])('explains a %s answer', async (status, message) => {
        answerWith((options, controls) => {
            options.onHttpException?.({ status });
            controls.finish();

            return Promise.reject(new Error(String(status)));
        });
        render(<Home />);

        fill('Diane', '0788123456');
        await act(async () => send());

        expect(screen.getByRole('alert')).toHaveTextContent(message);
    });

    it('leaves field errors to the form on a 422', async () => {
        answerWith((options, controls) => {
            options.onHttpException?.({ status: 422 });
            controls.setErrors({ name: 'Add your name.' });
            controls.finish();

            return Promise.resolve(undefined);
        });
        render(<Home />);

        fill('Diane', '0788123456');
        await act(async () => send());

        expect(screen.getByRole('alert')).toHaveTextContent('Add your name.');
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('says when Rozine could not be reached', async () => {
        answerWith((options, controls) => {
            options.onNetworkError?.();
            controls.finish();

            return Promise.reject(new Error('offline'));
        });
        render(<Home />);

        fill('Diane', '0788123456');
        await act(async () => send());

        expect(screen.getByRole('alert')).toHaveTextContent(
            'We could not reach Rozine.',
        );
        expect(
            form().getByRole('button', { name: 'Join the investor waitlist' }),
        ).toBeEnabled();
    });
});
