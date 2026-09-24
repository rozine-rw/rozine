import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import BusinessApply from '@/pages/business/apply';
import type { BusinessApplyProps } from '@/types/business';
import businessStep from '../../../resources/fixtures/ui/business-apply-business.json';
import raiseStep from '../../../resources/fixtures/ui/business-apply-raise.json';
import reviewStep from '../../../resources/fixtures/ui/business-apply-review.json';
import submittedStep from '../../../resources/fixtures/ui/business-apply-submitted.json';

type Transform = (data: Record<string, unknown>) => Record<string, unknown>;

const inertia = vi.hoisted(() => ({
    visit: vi.fn(),
    reload: vi.fn(),
    posts: [] as { url: string; payload: Record<string, unknown> }[],
    errors: {} as Record<string, string>,
    processing: false,
}));

vi.mock('@inertiajs/react', async () => {
    const { useRef, useState } = await import('react');

    return {
        Head: ({ title }: { title: string }) => (
            <span data-testid="head">{title}</span>
        ),
        Link: ({
            href,
            children,
            ...props
        }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
            <a href={href.url} {...props}>
                {children}
            </a>
        ),
        router: { visit: inertia.visit, reload: inertia.reload },
        useForm: <T extends Record<string, unknown>>(initial: T) => {
            const [data, setData] = useState(initial);
            const transform = useRef<Transform>((value) => value);

            return {
                data,
                setData: (next: (current: T) => T) => setData(next),
                errors: inertia.errors,
                processing: inertia.processing,
                transform: (fn: Transform) => {
                    transform.current = fn;
                },
                post: (url: string) =>
                    inertia.posts.push({
                        url,
                        payload: transform.current(data),
                    }),
            };
        },
    };
});

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessApplyProps;

beforeEach(() => {
    inertia.posts = [];
    inertia.errors = {};
    inertia.processing = false;
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Apply — step 1, business & finances', () => {
    it('shows the verified record read-only and moves on to step 2', async () => {
        const user = userEvent.setup();

        render(<BusinessApply {...props(businessStep)} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Apply for a raise',
        );
        expect(
            screen.getByRole('progressbar', { name: 'Application progress' }),
        ).toHaveValue(1);
        expect(screen.getByText('Step 1 of 3')).toBeInTheDocument();
        expect(screen.getByText('✓ RDB verified')).toBeInTheDocument();
        expect(
            screen.getByText('✓ Statements verified · OCR'),
        ).toBeInTheDocument();
        expect(screen.getByText('Est. 2018')).toBeInTheDocument();
        expect(screen.getByText('Board chair')).toBeInTheDocument();
        expect(
            screen.getByText('Financial standing · 5-year'),
        ).toBeInTheDocument();
        expect(screen.getByText('2021–2025')).toBeInTheDocument();
        expect(screen.getByText('RWF 1.6B')).toBeInTheDocument();
        expect(screen.getByText('✓ CRB verified')).toBeInTheDocument();
        expect(screen.getByText('RWF 33,915,000')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Close' })).toHaveAttribute(
            'href',
            '/preview/business-home',
        );

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(inertia.visit).toHaveBeenCalledWith({
            url: '/preview/business-apply-raise',
            method: 'get',
        });
    });

    it('reads a business with no audit, capacity, officers or verifications yet', () => {
        const pending = props(businessStep);

        pending.evidence = {
            ...pending.evidence,
            business: {
                ...pending.evidence.business,
                established_year: null,
                officers: [],
            },
            verified: { registry: false, statements: false },
            rating: null,
            debt_verified: false,
            capacity: null,
        };
        pending.links.next = null;

        render(<BusinessApply {...pending} />);

        expect(screen.queryByText('✓ RDB verified')).not.toBeInTheDocument();
        expect(screen.queryByText('Board chair')).not.toBeInTheDocument();
        expect(screen.queryByText('✓ CRB verified')).not.toBeInTheDocument();
        expect(screen.getAllByText('Pending audit')).toHaveLength(2);
    });
});

describe('Apply — step 2, your raise', () => {
    it('shows the server quote and asks for a fresh one only after typing settles', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        render(<BusinessApply {...props(raiseStep)} />);

        expect(
            screen.getByLabelText('Note title · what this raise is for'),
        ).toHaveValue('Cold-Chain Hub');
        expect(screen.getByText('14/40')).toBeInTheDocument();
        expect(screen.getByLabelText('Fundraising target (RWF)')).toHaveValue(
            '30,000,000',
        );
        expect(screen.getByText('10.8%')).toBeInTheDocument();
        expect(screen.getByText('6,000')).toBeInTheDocument();
        expect(screen.getByText('RWF 3,240,000')).toBeInTheDocument();
        expect(screen.getByText('RWF 33,240,000')).toBeInTheDocument();
        expect(screen.getByText('RWF 5,540,000 / month')).toBeInTheDocument();
        expect(
            screen.queryByText('Investor protection reserve'),
        ).not.toBeInTheDocument();
        act(() => vi.advanceTimersByTime(1000));
        expect(inertia.reload).not.toHaveBeenCalled();

        const target = screen.getByLabelText('Fundraising target (RWF)');

        await user.clear(target);
        await user.type(target, '025000000x');
        expect(target).toHaveValue('25,000,000');
        await user.click(screen.getByRole('button', { name: '4' }));
        expect(screen.getByRole('button', { name: '4' })).toHaveAttribute(
            'aria-pressed',
            'true',
        );
        act(() => vi.advanceTimersByTime(450));

        expect(inertia.reload).toHaveBeenCalledTimes(1);

        const [{ only, data, onStart, onFinish }] = inertia.reload.mock
            .lastCall as [
            {
                only: string[];
                data: Record<string, unknown>;
                onStart: () => void;
                onFinish: () => void;
            },
        ];

        expect(only).toEqual(['quote']);
        expect(data).toEqual({ target: '25000000', term_months: 4 });
        act(() => onStart());
        expect(
            screen.getByRole('button', { name: 'Continue' }),
        ).toHaveAttribute('aria-disabled', 'true');
        act(() => onFinish());
        expect(
            screen.getByRole('button', { name: 'Continue' }),
        ).not.toHaveAttribute('aria-disabled');
    });

    it('saves the raise at its revision when complete', async () => {
        const user = userEvent.setup();

        render(<BusinessApply {...props(raiseStep)} />);

        await user.click(screen.getByRole('button', { name: 'Inventory' }));
        await user.click(screen.getByRole('button', { name: 'Expansion' }));
        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(inertia.posts).toEqual([
            {
                url: '/preview/business-apply-review',
                payload: expect.objectContaining({
                    step: 'raise',
                    revision: 3,
                    title: 'Cold-Chain Hub',
                    target: '30000000',
                    term_months: 6,
                    use_of_funds: ['equipment', 'inventory'],
                }),
            },
        ]);
    });

    it('reminds instead of saving an incomplete raise, and marks server field errors', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        inertia.errors = {
            title: 'Name the raise',
            target: 'Enter a target',
            term_months: 'Pick a term',
            story: 'Too long',
        };
        render(<BusinessApply {...props(raiseStep)} />);

        expect(
            screen.getByLabelText('Note title · what this raise is for'),
        ).toHaveAttribute('aria-invalid', 'true');
        expect(screen.getByText('Pick a term')).toBeInTheDocument();

        await user.clear(
            screen.getByLabelText('Note title · what this raise is for'),
        );
        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(
            screen.getByText('Complete this step to continue'),
        ).toBeInTheDocument();
        expect(inertia.posts).toHaveLength(0);
        act(() => vi.advanceTimersByTime(2000));
        expect(
            screen.queryByText('Complete this step to continue'),
        ).not.toBeInTheDocument();
    });

    it('shows a refusal in the server’s words', () => {
        const refused = props(raiseStep);

        refused.quote = {
            status: 'refused',
            code: 'CAPACITY_EXCEEDED',
            message: 'Above your approved capacity of RWF 33,915,000.',
        };
        render(<BusinessApply {...refused} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Above your approved capacity of RWF 33,915,000.',
        );
        expect(
            screen.getByLabelText('Fundraising target (RWF)'),
        ).toHaveAttribute('aria-invalid', 'true');
    });

    it('shows dashes until there is a quote to show', () => {
        const blank = props(raiseStep);

        blank.quote = null;
        blank.application = {
            ...blank.application,
            target: null,
            term_months: null,
        };
        render(<BusinessApply {...blank} />);

        expect(screen.getByLabelText('Fundraising target (RWF)')).toHaveValue(
            '',
        );
        expect(screen.getAllByText('—').length).toBeGreaterThanOrEqual(4);
    });

    it('explains the rate and the note unit from the quote', async () => {
        const user = userEvent.setup();

        render(<BusinessApply {...props(raiseStep)} />);

        await user.click(
            screen.getByRole('button', { name: 'How this rate is set' }),
        );

        const rate = screen.getByRole('tooltip');

        expect(rate).toHaveTextContent('Rated Strong');
        expect(rate).toHaveTextContent('10.0%');
        expect(rate).toHaveTextContent('+0.5 points for this term');
        expect(rate).toHaveTextContent('Never above 15.0%');

        await user.click(within(rate).getByRole('button', { name: 'Close' }));
        await user.click(
            screen.getByRole('button', { name: 'About Rozine notes' }),
        );
        expect(screen.getByRole('tooltip')).toHaveTextContent(
            'A Rozine note is RWF 5,000.',
        );

        await user.click(
            screen.getByRole('button', { name: 'About Rozine notes' }),
        );
        expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();
    });

    it('explains a pending rating and pending quote, with the reserve when there is one', async () => {
        const user = userEvent.setup();
        const pending = props(raiseStep);

        pending.quote = {
            ...(pending.quote as Extract<
                BusinessApplyProps['quote'],
                { status: 'ready' }
            >),
            reserve: { currency: 'RWF', amount: '324000' },
            rate_basis: {
                band: null,
                floor_pct: '10.0',
                cap_pct: '15.0',
                term_premium_pct: '0.5',
            },
        };
        const { rerender } = render(<BusinessApply {...pending} />);

        expect(
            screen.getByText('Investor protection reserve'),
        ).toBeInTheDocument();
        expect(screen.getByText('RWF 324,000')).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'How this rate is set' }),
        );
        expect(screen.getByRole('tooltip')).toHaveTextContent('Pending audit');

        pending.quote = null;
        rerender(<BusinessApply {...pending} />);
        expect(screen.getByRole('tooltip')).toHaveTextContent(
            'Enter a target and a term to see your quote.',
        );

        await user.click(
            within(screen.getByRole('tooltip')).getByRole('button', {
                name: 'Close',
            }),
        );
        await user.click(
            screen.getByRole('button', { name: 'About Rozine notes' }),
        );
        expect(screen.getByRole('tooltip')).toHaveTextContent(
            'Enter a target and a term to see your quote.',
        );
    });

    it('counts the story in words and the title to its limit', async () => {
        const user = userEvent.setup();

        render(<BusinessApply {...props(raiseStep)} />);

        const story = screen.getByLabelText(
            'Why should investors trust your business?',
        );

        await user.clear(story);
        expect(screen.getByText('0 / 100 words')).toHaveClass(
            'text-rz-secondary',
        );
        await user.click(story);
        await user.paste('word '.repeat(60));
        expect(screen.getByText('60 / 100 words')).toHaveClass(
            'text-rz-accent-app-text',
        );
        await user.paste('word '.repeat(41));
        expect(screen.getByText('101 / 100 words')).toHaveClass(
            'text-rz-danger-text',
        );

        const title = screen.getByLabelText(
            'Note title · what this raise is for',
        );

        await user.clear(title);
        await user.click(title);
        await user.paste('x'.repeat(45));
        expect(screen.getByText('40/40')).toHaveClass('text-rz-danger-text');
    });
});

describe('Apply — explainers and dead links', () => {
    it('closes each explainer from outside it', async () => {
        const user = userEvent.setup();

        render(<BusinessApply {...props(raiseStep)} />);

        await user.click(
            screen.getByRole('button', { name: 'How this rate is set' }),
        );
        await user.click(screen.getAllByRole('button', { name: 'Close' })[0]);
        expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'About Rozine notes' }),
        );
        await user.click(screen.getAllByRole('button', { name: 'Close' })[0]);
        expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();
    });

    it('stays put when the server offers no next step', async () => {
        const user = userEvent.setup();
        const stuck = props(businessStep);

        stuck.links.next = null;
        inertia.visit.mockClear();
        render(<BusinessApply {...stuck} />);

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(inertia.visit).not.toHaveBeenCalled();
    });
});

describe('Apply — step 3, review & sign', () => {
    it('submits only once every disclosure, agreement and the signature are in', async () => {
        const user = userEvent.setup();

        render(<BusinessApply {...props(reviewStep)} />);

        expect(screen.getByText('Sign here')).toBeInTheDocument();
        expect(screen.getByText('RWF 0')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-apply-raise',
        );

        await user.click(screen.getByRole('button', { name: 'Submit note' }));
        expect(
            screen.getByText('Complete this step to continue'),
        ).toBeInTheDocument();

        for (const box of screen.getAllByRole('checkbox')) {
            await user.click(box);
        }

        await user.click(
            screen.getByRole('checkbox', {
                name: 'I confirm the accuracy of all information provided.',
            }),
        );
        await user.click(
            screen.getByRole('checkbox', {
                name: 'I confirm the accuracy of all information provided.',
            }),
        );
        await user.click(screen.getByLabelText('Your full name'));
        await user.paste('Robert Mugisha');
        expect(screen.getByLabelText('Your full name')).toHaveValue(
            'Robert Mugisha',
        );
        expect(screen.getByText('Robert Mugisha')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Submit note' }));

        expect(inertia.posts).toEqual([
            {
                url: '/preview/business-apply-submitted',
                payload: {
                    disclosures: [
                        'obligations',
                        'statements',
                        'repayment',
                        'accuracy',
                    ],
                    terms: true,
                    privacy: true,
                    signature_name: 'Robert Mugisha',
                    revision: 3,
                    documents: [
                        { kind: 'terms', version: '2.5' },
                        { kind: 'privacy', version: '1.4' },
                    ],
                },
            },
        ]);
    });

    it('opens each document summary and closes it three ways', async () => {
        const user = userEvent.setup();

        render(<BusinessApply {...props(reviewStep)} />);

        const [readTerms, readPrivacy] = screen.getAllByRole('button', {
            name: 'Read',
        });

        await user.click(readTerms);

        const terms = screen.getByRole('dialog', {
            name: 'Terms & Conditions',
        });

        expect(terms).toHaveTextContent('version 2.5');
        expect(terms).toHaveTextContent('Governing law');
        await user.click(within(terms).getByRole('button', { name: 'Got it' }));
        expect(
            screen.queryByRole('dialog', { name: 'Terms & Conditions' }),
        ).not.toBeInTheDocument();

        await user.click(readPrivacy);
        await user.click(
            within(
                screen.getByRole('dialog', { name: 'Privacy Note' }),
            ).getByRole('button', { name: 'Close' }),
        );
        expect(
            screen.queryByRole('dialog', { name: 'Privacy Note' }),
        ).not.toBeInTheDocument();

        await user.click(readPrivacy);
        await user.click(screen.getAllByRole('button', { name: 'Close' })[0]);
        expect(
            screen.queryByRole('dialog', { name: 'Privacy Note' }),
        ).not.toBeInTheDocument();
    });

    it('labels the busy submission and a missing document link', () => {
        inertia.processing = true;
        inertia.errors = {
            signature_name: 'Sign with your full name',
            disclosures: 'Tick every disclosure',
        };
        const busy = props(reviewStep);

        busy.acceptance.documents = [];
        render(<BusinessApply {...busy} />);

        expect(
            screen.getByRole('button', { name: 'Submitting…' }),
        ).toHaveAttribute('aria-busy', 'true');
        expect(
            screen.queryByRole('button', { name: 'Read' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('Sign with your full name'),
        ).toBeInTheDocument();
        expect(screen.getByText('Tick every disclosure')).toBeInTheDocument();
    });
});

describe('Apply — submitted', () => {
    it('confirms the submission with its note ID and live review timeline', () => {
        render(<BusinessApply {...props(submittedStep)} />);

        expect(
            screen.queryByRole('progressbar', { name: 'Application progress' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('heading', {
                name: 'Your note has been submitted',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('Note ID · RNP-2026-0412')).toBeInTheDocument();

        const timeline = screen.getByRole('list', {
            name: 'Application progress',
        });

        const stages = within(timeline).getAllByRole('listitem');

        expect(stages).toHaveLength(4);
        expect(stages[1]).toHaveTextContent('Under review');
        expect(stages[1]).toHaveAttribute('aria-current', 'step');
        expect(stages[0]).not.toHaveAttribute('aria-current');
        expect(
            screen.getByRole('link', { name: 'Back to Home' }),
        ).toHaveAttribute('href', '/preview/business-home');
        expect(
            screen.queryByRole('button', { name: 'Continue' }),
        ).not.toBeInTheDocument();
    });

    it('labels a saving raise', () => {
        inertia.processing = true;
        render(<BusinessApply {...props(raiseStep)} />);

        expect(screen.getByRole('button', { name: 'Saving…' })).toBeDisabled();
    });

    it('renders nothing in the sheet body without a submission record', () => {
        const missing = props(submittedStep);

        missing.submission = null;
        render(<BusinessApply {...missing} />);

        expect(
            screen.queryByRole('heading', {
                name: 'Your note has been submitted',
            }),
        ).not.toBeInTheDocument();
    });
});
