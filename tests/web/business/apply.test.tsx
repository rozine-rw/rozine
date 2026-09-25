import { act, render, screen, waitFor, within } from '@testing-library/react';
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
import type {
    ApplicationQuote,
    ApplicationSnapshot,
    BusinessApplyProps,
    OperationResource,
} from '@/types/business';
import businessStep from '../../../resources/fixtures/ui/business-apply-business.json';
import cosignStep from '../../../resources/fixtures/ui/business-apply-cosign.json';
import ineligibleStep from '../../../resources/fixtures/ui/business-apply-ineligible.json';
import minimalStep from '../../../resources/fixtures/ui/business-apply-live-minimal.json';
import liveStep from '../../../resources/fixtures/ui/business-apply-live.json';
import noLegalStep from '../../../resources/fixtures/ui/business-apply-no-legal.json';
import staleStep from '../../../resources/fixtures/ui/business-apply-quote-stale.json';
import raiseStep from '../../../resources/fixtures/ui/business-apply-raise.json';
import reducedStep from '../../../resources/fixtures/ui/business-apply-reduced.json';
import refusedStep from '../../../resources/fixtures/ui/business-apply-refused.json';
import reviewStep from '../../../resources/fixtures/ui/business-apply-review.json';
import soleTraderStep from '../../../resources/fixtures/ui/business-apply-sole-trader.json';
import submittedStep from '../../../resources/fixtures/ui/business-apply-submitted.json';
import unknownStep from '../../../resources/fixtures/ui/business-apply-unknown.json';

vi.setConfig({ testTimeout: 30_000 });

type Responder = (options: {
    onHttpException?: (response: {
        status: number;
        data: string;
        headers: Record<string, string>;
    }) => void;
}) => Promise<unknown>;

const inertia = vi.hoisted(() => ({
    visit: vi.fn(),
    /* A reload finishes at once unless a test holds it to watch the page meanwhile. */
    reload: vi.fn((options?: { onFinish?: () => void }) =>
        options?.onFinish?.(),
    ),
    body: {} as Record<string, unknown>,
    calls: [] as {
        url: string;
        method: string;
        body: Record<string, unknown>;
    }[],
    queue: [] as Responder[],
    errors: {} as Record<string, string>,
}));

vi.mock('@inertiajs/react', () => ({
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
    useHttp: () => ({
        errors: inertia.errors,
        processing: false,
        clearErrors: () => undefined,
        transform: (callback: () => Record<string, unknown>) => {
            inertia.body = callback();
        },
        submit: (
            route: { url: string; method: string },
            options: Parameters<Responder>[0],
        ) => {
            inertia.calls.push({ ...route, body: inertia.body });
            const respond = inertia.queue.shift();

            return respond ? respond(options) : new Promise(() => undefined);
        },
    }),
}));

type ReadyQuote = Extract<ApplicationQuote, { status: 'ready' }>;

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessApplyProps;

const ALL = ['application.save', 'application.evaluate', 'application.submit'];

const snapshotOf = (
    page: BusinessApplyProps,
    overrides: Partial<ApplicationSnapshot> = {},
): ApplicationSnapshot => ({
    application: page.application,
    quote: page.quote,
    acceptance: page.acceptance,
    submission: page.submission,
    next: { url: '/preview/business-apply-review', method: 'get' },
    ...overrides,
});

const operation = (
    overrides: Partial<OperationResource> = {},
): OperationResource => ({
    operation_id: 'op-1',
    status: 'completed',
    code: 'APPLICATION_SAVED',
    data: null,
    revision: 4,
    policy_version: 'engineering-2026-09-23.4',
    recorded_at: '2026-09-24T09:15:58+02:00',
    server_time: '2026-09-24T09:16:00+02:00',
    allowed_actions: ALL,
    field_errors: {},
    ...overrides,
});

const answers =
    (resource?: OperationResource): Responder =>
    () =>
        Promise.resolve(resource);

const fails =
    (status: number, body?: unknown): Responder =>
    (options) => {
        options.onHttpException?.({
            status,
            data: body === undefined ? '' : JSON.stringify(body),
            headers: {},
        });

        return Promise.reject(new Error(`HTTP ${status}`));
    };

const offline = (): Responder => () =>
    Promise.reject(new Error('Network error'));

/** A 25M, 4-month quote: the request the autosave tests type. */
const quoteFor25m = (base: ReadyQuote): ReadyQuote => ({
    ...base,
    quote_id: 'QTE-2026-0412-03',
    quote_revision: 3,
    requested_principal: { currency: 'RWF', amount: '25000000' },
    principal: { currency: 'RWF', amount: '25000000' },
    term_months: 4,
    rate_pct: '10.6',
    interest: { currency: 'RWF', amount: '2650000' },
    total: { currency: 'RWF', amount: '27650000' },
    units: '5000',
    schedule: [1, 2, 3, 4].map((instalment) => ({
        instalment,
        amount: { currency: 'RWF' as const, amount: '6912500' },
    })),
});

const typeRequest = async (user: ReturnType<typeof userEvent.setup>) => {
    const target = screen.getByLabelText('Fundraising target (RWF)');

    await user.clear(target);
    await user.type(target, '025000000x');
    await user.click(screen.getByRole('button', { name: '4' }));
};

const acceptEverything = async (
    user: ReturnType<typeof userEvent.setup>,
    page: BusinessApplyProps,
) => {
    await user.click(
        screen.getByRole('checkbox', { name: /^I accept this offer/u }),
    );

    for (const disclosure of page.acceptance.disclosures) {
        await user.click(
            screen.getByRole('checkbox', { name: disclosure.text }),
        );
    }

    await user.click(
        screen.getByRole('checkbox', {
            name: 'I agree to the Terms & Conditions.',
        }),
    );
    await user.click(
        screen.getByRole('checkbox', { name: 'I have read the Privacy Note.' }),
    );
    await user.click(screen.getByLabelText('Your full name'));
    await user.paste('Robert Mugisha');
};

beforeEach(() => {
    inertia.calls = [];
    inertia.queue = [];
    inertia.body = {};
    inertia.errors = {};
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Apply — step 1, business & finances', () => {
    it('shows the verified record read-only and saves the draft to move on to step 2', async () => {
        const user = userEvent.setup();
        const page = props(businessStep);

        inertia.queue.push(
            answers(
                operation({
                    data: snapshotOf(page, {
                        next: {
                            url: '/preview/business-apply-raise',
                            method: 'get',
                        },
                    }),
                }),
            ),
        );
        render(<BusinessApply {...page} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Apply for a raise',
        );
        expect(
            screen.getByRole('progressbar', { name: 'Application progress' }),
        ).toHaveValue(1);
        expect(screen.getByText('Step 1 of 3')).toBeInTheDocument();
        expect(screen.getByText('✓ RDB verified')).toBeInTheDocument();
        expect(screen.getByText('✓ Statements verified')).toBeInTheDocument();
        expect(screen.getAllByText('RDB 103847291').length).toBeGreaterThan(0);
        expect(screen.getByText('Est. 2018')).toBeInTheDocument();
        expect(screen.getByText('Chief Executive Officer')).toBeInTheDocument();
        expect(screen.getByText('Board Chair')).toBeInTheDocument();
        expect(
            screen.queryByText('Not eligible to raise yet'),
        ).not.toBeInTheDocument();
        expect(screen.getByText('Financial standing')).toBeInTheDocument();
        expect(screen.getByText('Statements verified')).toBeInTheDocument();
        /* The window is the server's period, never counted from the year rows. */
        expect(
            screen.getByText(/^Oct 2023 – Sept? 2026 · 36 months$/u),
        ).toBeInTheDocument();
        expect(screen.getByText('2023 · 3 months')).toBeInTheDocument();
        expect(screen.getByText('2026 · 9 months')).toBeInTheDocument();
        expect(screen.getByText('2025')).toBeInTheDocument();
        expect(screen.getByText('2024')).toBeInTheDocument();
        /* The totals are the server's: RWF 1,119,000,000, shown as given. */
        expect(screen.getByText('RWF 1.1B')).toBeInTheDocument();
        expect(screen.getAllByText('Net operating cash')).toHaveLength(5);
        expect(screen.queryByText('Net profit')).not.toBeInTheDocument();
        expect(screen.getByText('✓ CRB verified')).toBeInTheDocument();
        expect(screen.getByText('RWF 33,915,000')).toBeInTheDocument();
        expect(screen.queryByText('Unavailable')).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Close' })).toHaveAttribute(
            'href',
            '/preview/business-home',
        );

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith({
                url: '/preview/business-apply-raise',
                method: 'get',
            }),
        );
        expect(inertia.calls).toEqual([
            {
                url: '/preview/business-apply-draft',
                method: 'post',
                body: {
                    title: page.application.title,
                    target: page.application.target?.amount,
                    term_months: page.application.term_months,
                    use_of_funds: page.application.use_of_funds,
                    story: page.application.story,
                    step: 'raise',
                    identity_context_revision: page.identity_context_revision,
                    expected_revision: page.application.revision,
                    request_id: expect.any(String),
                },
            },
        ]);
    });

    it('follows next when a Continue is recovered by the lookup, without applying its receipt', async () => {
        const user = userEvent.setup();
        const page = props(businessStep);

        inertia.visit.mockClear();
        inertia.reload.mockClear();
        inertia.queue.push(
            offline(),
            answers(
                operation({
                    allowed_actions: [],
                    data: snapshotOf(page, {
                        next: {
                            url: '/preview/business-apply-raise',
                            method: 'get',
                        },
                    }),
                }),
            ),
        );
        render(<BusinessApply {...page} />);

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith({
                url: '/preview/business-apply-raise',
                method: 'get',
            }),
        );
        expect(inertia.calls[1].method).toBe('get');
        expect(inertia.reload).not.toHaveBeenCalled();
        /* The recorded (empty) capabilities never replace the page's own. */
        expect(
            screen.getByRole('button', { name: 'Continue' }),
        ).toBeInTheDocument();
    });

    it('starts a business with no draft yet from an empty request', async () => {
        const user = userEvent.setup();
        const page = props(businessStep);

        page.application = {
            ...page.application,
            target: null,
            term_months: null,
        };
        render(<BusinessApply {...page} />);

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(inertia.calls[0].body).toMatchObject({
            target: null,
            term_months: null,
            step: 'raise',
        });
    });

    it('shows a server error for a field the step does not show as a banner', () => {
        inertia.errors = {
            step: 'A draft may resume at the Business or Raise step.',
        };

        render(<BusinessApply {...props(businessStep)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'A draft may resume at the Business or Raise step.',
        );
    });

    it('shows an eligible sole trader with no company line and no registry badge', async () => {
        const user = userEvent.setup();

        render(<BusinessApply {...props(soleTraderStep)} />);

        expect(screen.getAllByText('Uwimana Tailoring').length).toBeGreaterThan(
            0,
        );
        expect(screen.getByText('Owner')).toBeInTheDocument();
        expect(screen.queryByText(/^RDB /u)).not.toBeInTheDocument();
        expect(screen.queryByText('✓ RDB verified')).not.toBeInTheDocument();
        expect(screen.getByText('✓ Statements verified')).toBeInTheDocument();
        expect(
            screen.queryByText('Not eligible to raise yet'),
        ).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Continue' }));
        expect(inertia.calls[0].body).toMatchObject({ step: 'raise' });
    });

    it('explains ineligibility in the server’s words and shows missing facts as unavailable', () => {
        render(<BusinessApply {...props(ineligibleStep)} />);

        expect(
            screen.getByText('Not eligible to raise yet'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                /^A first raise needs 36 complete, consecutive months/u,
            ),
        ).toBeInTheDocument();
        expect(screen.getAllByText('Unavailable')).toHaveLength(5);
        expect(
            screen.getByText(/^Sept? 2023 – Dec 2025 · 28 months$/u),
        ).toBeInTheDocument();
        expect(screen.getByText('2023 · 4 months')).toBeInTheDocument();
        expect(screen.getAllByText('Pending audit')).toHaveLength(2);
        expect(screen.getByText('Est. 2023')).toBeInTheDocument();
        expect(screen.queryByText('✓ CRB verified')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Continue' }),
        ).not.toBeInTheDocument();
    });

    it('hides the window when the server states none, and names a one-month window and year', () => {
        const page = props(businessStep);

        page.evidence = { ...page.evidence, period: null };
        const { unmount } = render(<BusinessApply {...page} />);

        expect(screen.queryByText(/ · 36 months$/u)).not.toBeInTheDocument();
        expect(screen.getByText('Financial standing')).toBeInTheDocument();
        unmount();

        page.evidence = {
            ...page.evidence,
            period: {
                from_month: '2026-09',
                through_month: '2026-09',
                months: 1,
            },
            years: [{ ...page.evidence.years[0], months: 1 }],
        };
        render(<BusinessApply {...page} />);

        expect(
            screen.getByText(/^Sept? 2026 – Sept? 2026 · 1 month$/u),
        ).toBeInTheDocument();
        expect(screen.getByText('2026 · 1 month')).toBeInTheDocument();
    });

    it('shows an evidenced existing debt without a CRB badge until CRB proof exists', () => {
        render(<BusinessApply {...props(minimalStep)} />);

        expect(screen.getByText('RWF 12M')).toBeInTheDocument();
        expect(screen.queryByText('✓ CRB verified')).not.toBeInTheDocument();
    });

    it('reads a business with no officers, founding year or verifications, and offers no dead Continue', () => {
        const bare = props(businessStep);

        bare.evidence = {
            ...bare.evidence,
            business: {
                ...bare.evidence.business,
                established_year: null,
                officers: [],
            },
            verified: { registry: false, statements: false },
        };
        bare.allowed_actions = [];

        render(<BusinessApply {...bare} />);

        expect(screen.queryByText('✓ RDB verified')).not.toBeInTheDocument();
        expect(
            screen.queryByText(/Statements verified/u),
        ).not.toBeInTheDocument();
        expect(screen.queryByText('Board Chair')).not.toBeInTheDocument();
        expect(screen.queryByText(/^Est\./u)).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Continue' }),
        ).not.toBeInTheDocument();
    });
});

describe('Apply — step 2, the quote', () => {
    it('reads the server quote on load — offer, schedule and residual — without evaluating', () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        render(<BusinessApply {...props(raiseStep)} />);

        expect(
            screen.getByLabelText('Note title · what this raise is for'),
        ).toHaveValue('Cold-Chain Hub');
        expect(screen.getByText('14/40')).toBeInTheDocument();
        expect(screen.getByLabelText('Fundraising target (RWF)')).toHaveValue(
            '35,000,000',
        );
        expect(screen.getByText('10.9%')).toBeInTheDocument();
        expect(screen.getByText('6,783')).toBeInTheDocument();
        expect(screen.getByText('RWF 33,915,000')).toBeInTheDocument();
        expect(
            screen.getByText(
                'You asked for RWF 35,000,000 · this is the offer you accept when you sign',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('RWF 3,696,735')).toBeInTheDocument();
        expect(screen.getByText('RWF 37,611,735')).toBeInTheDocument();

        const schedule = within(
            screen.getByRole('list', { name: 'Repayment schedule' }),
        ).getAllByRole('listitem');

        expect(schedule).toHaveLength(6);
        expect(schedule[0]).toHaveTextContent('Instalment 1RWF 6,268,622');
        expect(schedule[5]).toHaveTextContent(
            'Instalment 6 · finalRWF 6,268,625',
        );
        expect(
            screen.queryByText('Investor protection reserve'),
        ).not.toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(2000);
        });
        expect(inertia.calls).toHaveLength(0);
    });

    it('saves the typed request after 450 ms, evaluates the saved draft, then shows the new quote', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const page = props(raiseStep);
        const saved = {
            ...page.application,
            revision: 4,
            target: { currency: 'RWF' as const, amount: '25000000' },
            term_months: 4 as const,
        };
        const quote = quoteFor25m(page.quote as ReadyQuote);

        inertia.queue.push(
            answers(
                operation({
                    data: snapshotOf(page, { application: saved }),
                }),
            ),
            answers(
                operation({
                    code: 'APPLICATION_EVALUATED',
                    revision: 5,
                    data: snapshotOf(page, {
                        application: { ...saved, revision: 5 },
                        quote,
                    }),
                }),
            ),
            answers(
                operation({
                    revision: 6,
                    data: snapshotOf(page, {
                        application: { ...saved, revision: 6 },
                        quote,
                    }),
                }),
            ),
        );
        render(<BusinessApply {...page} />);

        await typeRequest(user);
        expect(screen.getByLabelText('Fundraising target (RWF)')).toHaveValue(
            '25,000,000',
        );
        expect(screen.getByRole('button', { name: '4' })).toHaveAttribute(
            'aria-pressed',
            'true',
        );
        expect(
            screen.getByRole('button', { name: 'Continue' }),
        ).toHaveAttribute('aria-disabled', 'true');
        expect(inertia.calls).toHaveLength(0);

        act(() => {
            vi.advanceTimersByTime(450);
        });

        await waitFor(() => expect(inertia.calls).toHaveLength(2));

        const [save, evaluate] = inertia.calls;

        expect(save).toEqual({
            url: '/preview/business-apply-draft',
            method: 'post',
            body: {
                title: 'Cold-Chain Hub',
                target: '25000000',
                term_months: 4,
                use_of_funds: ['equipment', 'expansion'],
                story: page.application.story,
                identity_context_revision: 4,
                expected_revision: 3,
                request_id: expect.any(String),
            },
        });
        expect(save.body).not.toHaveProperty('step');
        expect(evaluate).toEqual({
            url: '/preview/business-apply-evaluations',
            method: 'post',
            body: {
                target: '25000000',
                term_months: 4,
                evidence_version: 'ev-2026-09-21.3',
                identity_context_revision: 4,
                expected_revision: 4,
                request_id: expect.any(String),
            },
        });
        expect(evaluate.body.request_id).not.toBe(save.body.request_id);

        expect(await screen.findByText('RWF 27,650,000')).toBeInTheDocument();
        expect(screen.getByText('5,000')).toBeInTheDocument();
        expect(screen.queryByText(/^You asked for/u)).not.toBeInTheDocument();
        expect(
            within(
                screen.getByRole('list', { name: 'Repayment schedule' }),
            ).getAllByRole('listitem'),
        ).toHaveLength(4);
        expect(inertia.reload).toHaveBeenCalledWith({
            only: [
                'allowed_actions',
                'identity_context_revision',
                'server_time',
                'evidence',
            ],
        });

        const cta = screen.getByRole('button', { name: 'Continue' });

        expect(cta).not.toHaveAttribute('aria-disabled');
        await user.click(screen.getByRole('button', { name: 'Hiring' }));
        await user.click(screen.getByRole('button', { name: 'Expansion' }));
        await user.click(cta);

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith({
                url: '/preview/business-apply-review',
                method: 'get',
            }),
        );
        expect(inertia.calls[2].body).toMatchObject({
            target: '25000000',
            term_months: 4,
            use_of_funds: ['equipment', 'hiring'],
            step: 'review',
            expected_revision: 5,
        });
    });

    it('shows a refused evaluation in the server’s words and never resends the same request', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const page = props(raiseStep);
        const refusal: ApplicationQuote = {
            status: 'refused',
            code: 'DSCR_BELOW_CUTOFF',
            message: 'Cash flow does not cover RWF 25,000,000 over 4 months.',
        };

        inertia.queue.push(
            answers(operation({ data: snapshotOf(page) })),
            answers(
                operation({
                    code: 'APPLICATION_EVALUATED',
                    data: snapshotOf(page, { quote: refusal }),
                }),
            ),
        );
        render(<BusinessApply {...page} />);

        await typeRequest(user);
        act(() => {
            vi.advanceTimersByTime(450);
        });

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'Cash flow does not cover RWF 25,000,000 over 4 months.',
        );
        expect(
            screen.getByLabelText('Fundraising target (RWF)'),
        ).toHaveAttribute('aria-invalid', 'true');

        await user.click(screen.getByRole('button', { name: 'Continue' }));
        expect(
            screen.getByText('Complete this step to continue'),
        ).toBeInTheDocument();
        act(() => {
            vi.advanceTimersByTime(2000);
        });
        expect(inertia.calls).toHaveLength(2);
    });

    it('opens on a refusal without re-evaluating it', () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        render(<BusinessApply {...props(refusedStep)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            /^Your verified cash flow does not cover repayments/u,
        );
        act(() => {
            vi.advanceTimersByTime(2000);
        });
        expect(inertia.calls).toHaveLength(0);
    });

    it('evaluates a saved draft that has no quote yet, without saving it again', async () => {
        const page = props(raiseStep);

        page.quote = null;
        render(<BusinessApply {...page} />);

        await waitFor(() => expect(inertia.calls).toHaveLength(1));
        expect(inertia.calls[0].url).toBe(
            '/preview/business-apply-evaluations',
        );
        expect(inertia.calls[0].body).toMatchObject({
            target: '35000000',
            term_months: 6,
            expected_revision: 3,
        });
        expect(screen.getByRole('button', { name: 'Saving…' })).toBeDisabled();
    });

    it('stops on a version conflict, refreshes, and asks again only when told to', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        render(<BusinessApply {...props(raiseStep)} />);

        await typeRequest(user);
        act(() => {
            vi.advanceTimersByTime(450);
        });

        expect(await screen.findByRole('alert')).toHaveTextContent(
            "This application changed since you opened it. We've loaded the latest version — check it and try again.",
        );
        expect(inertia.reload).toHaveBeenCalledWith();
        act(() => {
            vi.advanceTimersByTime(2000);
        });
        expect(inertia.calls).toHaveLength(1);

        await user.click(screen.getByRole('button', { name: 'Continue' }));
        act(() => {
            vi.advanceTimersByTime(450);
        });

        await waitFor(() => expect(inertia.calls).toHaveLength(2));
        expect(inertia.calls[1].body.request_id).not.toBe(
            inertia.calls[0].body.request_id,
        );
    });

    it('marks server field errors and releases the command after a 422', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        inertia.errors = {
            title: 'Name the raise',
            target: 'Enter a target',
            term_months: 'Pick a term',
            story: 'Too long',
        };
        inertia.queue.push(answers());
        render(<BusinessApply {...props(raiseStep)} />);

        expect(
            screen.getByLabelText('Note title · what this raise is for'),
        ).toHaveAttribute('aria-invalid', 'true');
        expect(screen.getByText('Pick a term')).toBeInTheDocument();
        expect(screen.getByText('Too long')).toBeInTheDocument();

        await typeRequest(user);
        act(() => {
            vi.advanceTimersByTime(450);
        });
        await waitFor(() => expect(inertia.calls).toHaveLength(1));

        await user.click(screen.getByRole('button', { name: '5' }));
        act(() => {
            vi.advanceTimersByTime(450);
        });
        await waitFor(() => expect(inertia.calls).toHaveLength(2));
        expect(inertia.calls[1].body).toMatchObject({ term_months: 5 });
    });

    it('looks up an autosave whose answer was lost, then carries on', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const page = props(raiseStep);

        page.identity_context_revision = 7;
        inertia.queue.push(
            offline(),
            answers(operation({ data: snapshotOf(page) })),
        );
        render(<BusinessApply {...page} />);

        await typeRequest(user);
        act(() => {
            vi.advanceTimersByTime(450);
        });

        await waitFor(() => expect(inertia.calls).toHaveLength(3));

        const [save, lookup, evaluate] = inertia.calls;

        expect(lookup).toEqual({
            url: `/preview/business-operation-${String(save.body.request_id)}`,
            method: 'get',
            body: { command: 'save', identity_context_revision: 7 },
        });
        expect(evaluate.url).toBe('/preview/business-apply-evaluations');
    });

    it('reads every fact afresh after a recovered evaluation, never showing its recorded quote as current', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const page = props(raiseStep);
        const quote = quoteFor25m(page.quote as ReadyQuote);

        inertia.visit.mockClear();
        inertia.reload.mockClear();
        inertia.queue.push(
            answers(operation({ data: snapshotOf(page) })),
            offline(),
            answers(
                operation({
                    code: 'APPLICATION_EVALUATED',
                    data: snapshotOf(page, { quote }),
                }),
            ),
        );
        render(<BusinessApply {...page} />);

        await typeRequest(user);
        act(() => {
            vi.advanceTimersByTime(450);
        });

        await waitFor(() => expect(inertia.calls).toHaveLength(3));
        expect(inertia.calls[2].url).toMatch(
            /^\/preview\/business-operation-/u,
        );
        /* The direct save refreshes the remaining props; the recovered evaluation reloads all. */
        await waitFor(() => expect(inertia.reload).toHaveBeenCalledTimes(2));
        expect(inertia.reload.mock.calls[0]).toEqual([
            { only: expect.arrayContaining(['allowed_actions']) },
        ]);
        expect(inertia.reload.mock.calls[1]).toEqual([]);
        expect(screen.queryByText('RWF 27,650,000')).not.toBeInTheDocument();
        expect(screen.getByText('RWF 37,611,735')).toBeInTheDocument();
        expect(inertia.visit).not.toHaveBeenCalled();
    });

    it('offers no command when the server allows none', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const page = props(raiseStep);

        page.allowed_actions = [];
        render(<BusinessApply {...page} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'You can view this application, but not change it.',
        );
        expect(
            screen.queryByRole('button', { name: 'Continue' }),
        ).not.toBeInTheDocument();

        await typeRequest(user);
        act(() => {
            vi.advanceTimersByTime(2000);
        });
        expect(inertia.calls).toHaveLength(0);
    });

    it('reminds instead of saving an incomplete raise', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        render(<BusinessApply {...props(raiseStep)} />);

        await user.clear(
            screen.getByLabelText('Note title · what this raise is for'),
        );
        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(
            screen.getByText('Complete this step to continue'),
        ).toBeInTheDocument();
        act(() => {
            vi.advanceTimersByTime(2000);
        });
        expect(
            screen.queryByText('Complete this step to continue'),
        ).not.toBeInTheDocument();
        expect(inertia.calls).toHaveLength(0);
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
        expect(
            screen.queryByRole('list', { name: 'Repayment schedule' }),
        ).not.toBeInTheDocument();
    });

    it('explains the rate with the exact term premium, and the note unit', async () => {
        const user = userEvent.setup();

        render(<BusinessApply {...props(raiseStep)} />);

        await user.click(
            screen.getByRole('button', { name: 'How this rate is set' }),
        );

        const rate = screen.getByRole('tooltip');

        expect(rate).toHaveTextContent('Rated Strong');
        expect(rate).toHaveTextContent('10.0%');
        expect(rate).toHaveTextContent('+9/1000 term premium');
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

        pending.allowed_actions = [];
        pending.quote = {
            ...(pending.quote as ReadyQuote),
            reserve: { currency: 'RWF', amount: '369673' },
            rate_basis: {
                ...(pending.quote as ReadyQuote).rate_basis,
                band: null,
            },
        };
        const { rerender } = render(<BusinessApply {...pending} />);

        expect(
            screen.getByText('Investor protection reserve'),
        ).toBeInTheDocument();
        expect(screen.getByText('RWF 369,673')).toBeInTheDocument();
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
        const page = props(raiseStep);

        page.allowed_actions = [];
        render(<BusinessApply {...page} />);

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
});

describe('Apply — step 3, review & sign', () => {
    it('signs only after the offer, every disclosure, both agreements and the attestation', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);
        const quote = page.quote as ReadyQuote;

        render(<BusinessApply {...page} />);

        expect(screen.getByText('Sign here')).toBeInTheDocument();
        expect(screen.getByText('RWF 0')).toBeInTheDocument();
        expect(
            screen.getByText('10.9% flat', { exact: false }),
        ).toBeInTheDocument();
        expect(
            screen.getByText("Signatures the company's mandate requires: 2"),
        ).toBeInTheDocument();

        const signers = within(
            screen.getByRole('list', { name: 'Signatories' }),
        ).getAllByRole('listitem');

        expect(signers[0]).toHaveTextContent(
            'Robert MugishaChief Executive OfficerPending',
        );
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-apply-raise',
        );

        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );
        expect(
            screen.getByText('Complete this step to continue'),
        ).toBeInTheDocument();

        const first = page.acceptance.disclosures[0].text;

        await user.click(screen.getByRole('checkbox', { name: first }));
        await user.click(screen.getByRole('checkbox', { name: first }));
        await acceptEverything(user, page);
        expect(
            screen.getByText('Robert Mugisha', { selector: 'span' }),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(inertia.calls).toEqual([
            {
                url: '/preview/business-apply-signatures',
                method: 'post',
                body: {
                    identity_context_revision: 4,
                    expected_revision: 3,
                    request_id: expect.any(String),
                    quote_id: quote.quote_id,
                    quote_revision: 2,
                    evidence_version: 'ev-2026-09-21.3',
                    mandate_version: page.acceptance.mandate_version,
                    accepted_principal: '33915000',
                    disclosures: page.acceptance.disclosures.map(
                        ({ key, version, sha256 }) => ({
                            key,
                            version,
                            sha256,
                        }),
                    ),
                    documents: page.acceptance.documents.map(
                        ({ kind, version, sha256 }) => ({
                            kind,
                            version,
                            sha256,
                        }),
                    ),
                    terms: true,
                    privacy: true,
                    signature_name: 'Robert Mugisha',
                },
            },
        ]);
        expect(
            screen.getByRole('button', { name: 'Signing…' }),
        ).toHaveAttribute('aria-busy', 'true');
    });

    it('records one signature and stays to show who still has to sign', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);
        const acceptance = structuredClone(page.acceptance);

        acceptance.signers[0] = {
            ...acceptance.signers[0],
            state: 'signed',
            signed_at: '2026-09-24T09:20:00+02:00',
        };
        inertia.queue.push(
            answers(
                operation({
                    code: 'APPLICATION_SIGNATURE_RECORDED',
                    allowed_actions: [],
                    data: snapshotOf(page, { acceptance }),
                }),
            ),
        );
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(
            await screen.findByText(/^Signed · 24 Sep/u),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Waiting for Aline Uwase to sign.',
        );
        expect(
            screen.queryByRole('button', { name: 'Sign application' }),
        ).not.toBeInTheDocument();
        expect(inertia.reload).toHaveBeenCalledWith({
            only: expect.arrayContaining(['allowed_actions']),
        });
        expect(inertia.visit).not.toHaveBeenCalled();
    });

    it('moves on to the submitted page once the last signature completes the application', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(
            answers(
                operation({
                    code: 'APPLICATION_SUBMITTED',
                    data: snapshotOf(page, {
                        next: {
                            url: '/preview/business-apply-submitted',
                            method: 'get',
                        },
                    }),
                }),
            ),
        );
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith({
                url: '/preview/business-apply-submitted',
                method: 'get',
            }),
        );
    });

    it('reloads when a completed command carries no snapshot', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(answers(operation({ data: null })));
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        await waitFor(() => expect(inertia.reload).toHaveBeenCalledWith());
    });

    it('asks for a fresh acceptance when the quote went stale, without resending', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(fails(409, { code: 'QUOTE_STALE' }));
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'Your quote changed before you signed. Check the new offer and accept it again.',
        );
        expect(
            screen.getByRole('checkbox', { name: /^I accept this offer/u }),
        ).not.toBeChecked();
        expect(
            screen.getByRole('checkbox', {
                name: 'I agree to the Terms & Conditions.',
            }),
        ).toBeChecked();
        expect(inertia.reload).toHaveBeenCalledWith();
        expect(inertia.calls).toHaveLength(1);
    });

    it('asks for the signature again when the mandate went stale, under a new request ID', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(fails(409, { code: 'MANDATE_STALE' }));
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(await screen.findByRole('alert')).toHaveTextContent(
            "The company's signing mandate changed before you signed. Check who must sign now, then sign again.",
        );
        expect(screen.getByLabelText('Your full name')).toHaveValue('');
        expect(screen.getByText('Sign here')).toBeInTheDocument();
        expect(
            screen.getByRole('checkbox', { name: /^I accept this offer/u }),
        ).toBeChecked();
        expect(inertia.reload).toHaveBeenCalledWith();

        await user.click(screen.getByLabelText('Your full name'));
        await user.paste('Robert Mugisha');
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(inertia.calls).toHaveLength(2);

        const [first, second] = inertia.calls.map((call) => call.body);

        expect(second.request_id).not.toBe(first.request_id);
        expect({ ...second, request_id: null }).toEqual({
            ...first,
            request_id: null,
        });
    });

    it('asks to re-read and re-accept when a document version was rejected as stale', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(
            answers(
                operation({
                    status: 'rejected',
                    code: 'DOCUMENT_VERSION_STALE',
                }),
            ),
        );
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'A document or disclosure changed before you signed.',
        );
        expect(
            screen.getByRole('checkbox', {
                name: 'I agree to the Terms & Conditions.',
            }),
        ).not.toBeChecked();
        expect(
            screen.getByRole('checkbox', {
                name: page.acceptance.disclosures[0].text,
            }),
        ).not.toBeChecked();
        expect(
            screen.getByRole('checkbox', { name: /^I accept this offer/u }),
        ).toBeChecked();
    });

    it.each([
        [
            403,
            { code: 'MANDATE_REQUIRED' },
            "Your verified mandate doesn't let you sign for this business.",
        ],
        [403, undefined, "You can't do this for this business."],
        [
            403,
            { code: 'PARTY_AUTHORITY_REQUIRED' },
            'Your access has changed. Return to your apps and try again.',
        ],
        [
            404,
            { code: 'NOT_FOUND' },
            'This application is no longer available to you.',
        ],
    ])('never retries a %i denial (%j)', async (status, body, message) => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(fails(status, body));
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(await screen.findByRole('alert')).toHaveTextContent(message);
        expect(inertia.reload).not.toHaveBeenCalled();
        expect(
            screen.queryByRole('button', { name: 'Try again' }),
        ).not.toBeInTheDocument();
        expect(inertia.calls).toHaveLength(1);
    });

    it.each([
        [
            409,
            { code: 'IDEMPOTENCY_CONFLICT' },
            'This request was already used with different details',
        ],
        [
            400,
            '<html>',
            "The server couldn't complete this. We've loaded the latest version.",
        ],
    ])('refreshes after a %i refusal', async (status, body, message) => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(fails(status, body));
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(await screen.findByRole('alert')).toHaveTextContent(message);
        expect(inertia.reload).toHaveBeenCalledWith();
    });

    it('looks up a lost answer and retries the identical request only when nothing was recorded', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(
            offline(),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
            answers(
                operation({
                    code: 'APPLICATION_SUBMITTED',
                    data: snapshotOf(page, {
                        next: {
                            url: '/preview/business-apply-submitted',
                            method: 'get',
                        },
                    }),
                }),
            ),
        );
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(
            await screen.findByText('No result recorded'),
        ).toBeInTheDocument();
        expect(inertia.calls[1]).toEqual({
            url: `/preview/business-operation-${String(inertia.calls[0].body.request_id)}`,
            method: 'get',
            body: { command: 'submit', identity_context_revision: 4 },
        });

        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );
        expect(inertia.calls).toHaveLength(2);

        await user.click(screen.getByRole('button', { name: 'Try again' }));

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith({
                url: '/preview/business-apply-submitted',
                method: 'get',
            }),
        );
        expect(inertia.calls[2]).toEqual(inertia.calls[0]);
    });

    it('refreshes the page’s facts before offering the retry, and retries the held request unchanged', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);
        let finishReload: () => void = () => undefined;

        inertia.reload.mockImplementationOnce(
            (options?: { onFinish?: () => void }) => {
                finishReload = () => options?.onFinish?.();
            },
        );
        inertia.queue.push(
            offline(),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        const view = render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        await waitFor(() =>
            expect(inertia.reload).toHaveBeenCalledWith({
                onFinish: expect.any(Function),
            }),
        );
        expect(screen.getByText('Checking what happened')).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Try again' }),
        ).not.toBeInTheDocument();

        act(() => finishReload());
        expect(
            await screen.findByRole('button', { name: 'Try again' }),
        ).toBeInTheDocument();

        /* The refreshed props move on; the held request does not. */
        view.rerender(
            <BusinessApply
                {...page}
                identity_context_revision={page.identity_context_revision + 1}
                application={{
                    ...page.application,
                    revision: page.application.revision + 1,
                }}
            />,
        );
        inertia.queue.push(offline(), offline());
        await user.click(screen.getByRole('button', { name: 'Try again' }));

        await waitFor(() =>
            expect(inertia.calls.length).toBeGreaterThanOrEqual(3),
        );
        expect(inertia.calls[2]).toEqual(inertia.calls[0]);
    });

    it('keeps checking an uncertain outcome until the server settles it', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(fails(503), offline());
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(
            await screen.findByText("We couldn't confirm this yet"),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Try again' }),
        ).not.toBeInTheDocument();

        inertia.queue.push(
            answers(
                operation({ status: 'pending', code: 'OPERATION_PENDING' }),
            ),
        );
        await user.click(screen.getByRole('button', { name: 'Check again' }));
        expect(
            await screen.findByText('Still being processed'),
        ).toBeInTheDocument();

        inertia.queue.push(fails(500));
        await user.click(screen.getByRole('button', { name: 'Check again' }));
        expect(
            await screen.findByText("We couldn't confirm this yet"),
        ).toBeInTheDocument();

        inertia.queue.push(
            answers(operation({ status: 'rejected', code: 'QUOTE_STALE' })),
        );
        await user.click(screen.getByRole('button', { name: 'Check again' }));
        expect(await screen.findByRole('alert')).toHaveTextContent(
            'Your quote changed before you signed.',
        );
        expect(inertia.calls.map((call) => call.method)).toEqual([
            'post',
            'get',
            'get',
            'get',
            'get',
        ]);
    });

    it('shows the lookup while it runs', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(offline());
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(
            await screen.findByText('Checking what happened'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Check again' }),
        ).not.toBeInTheDocument();
    });

    it('treats a denied lookup as final', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        inertia.queue.push(offline(), fails(403));
        render(<BusinessApply {...page} />);

        await acceptEverything(user, page);
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(await screen.findByRole('alert')).toHaveTextContent(
            "You can't do this for this business.",
        );
    });

    it('resumes the fixture’s unconfirmed command with its original request ID', async () => {
        const user = userEvent.setup();
        const page = props(unknownStep);

        render(<BusinessApply {...page} />);

        expect(
            screen.getByText("We couldn't confirm this yet"),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Check again' }));

        expect(inertia.calls[0]).toEqual({
            url: '/preview/business-operation-6f1c2d3e-4b5a-4c6d-8e7f-90a1b2c3d4e5',
            method: 'get',
            body: { command: 'submit', identity_context_revision: 4 },
        });
    });

    it('opens on the stale-quote preview with its refusal shown', () => {
        render(<BusinessApply {...props(staleStep)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Your quote changed before you signed.',
        );
    });

    it('waits for a co-signatory when the current person has signed', () => {
        render(<BusinessApply {...props(cosignStep)} />);

        const signers = within(
            screen.getByRole('list', { name: 'Signatories' }),
        ).getAllByRole('listitem');

        expect(signers[0]).toHaveTextContent('Signed · 24 Sep');
        expect(signers[1]).toHaveTextContent('Pending');
        expect(screen.getByRole('status')).toHaveTextContent(
            'Waiting for Aline Uwase to sign.',
        );
        expect(screen.queryByText('Risk disclosures')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('checkbox', { name: /^I accept this offer/u }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Sign application' }),
        ).not.toBeInTheDocument();
    });

    it('explains who may sign when the current person may not, and marks an undated signature', () => {
        const page = props(cosignStep);

        page.acceptance.signers = page.acceptance.signers.map((signer) => ({
            ...signer,
            state: 'signed' as const,
            signed_at: null,
        }));
        page.quote = null;
        render(<BusinessApply {...page} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            "Only a signatory on the company's verified mandate can sign this application.",
        );
        expect(screen.getAllByText('Signed')).toHaveLength(2);
        expect(
            screen.getByText(
                'There is no offer to accept yet. Go back to your raise to get a quote.',
            ),
        ).toBeInTheDocument();
    });

    it('cannot sign without an offer to accept', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);

        page.quote = null;
        render(<BusinessApply {...page} />);

        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );
        expect(
            screen.getByText('Complete this step to continue'),
        ).toBeInTheDocument();
        expect(inertia.calls).toHaveLength(0);
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
        expect(within(terms).getByText('Full text')).toBeInTheDocument();
        expect(
            within(terms).getByText(/^TEST TEXT — synthetic fixture/u),
        ).toHaveClass('whitespace-pre-line');
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

    it('evaluates a smaller accepted amount against the saved request and asks for acceptance again', async () => {
        const user = userEvent.setup();
        const page = props(reviewStep);
        const quote = page.quote as ReadyQuote;
        const smaller: ReadyQuote = {
            ...quote,
            quote_id: 'quote-smaller',
            quote_revision: 3,
            principal: { currency: 'RWF', amount: '30000000' },
            total: { currency: 'RWF', amount: '33270000' },
        };

        inertia.queue.push(
            answers(
                operation({
                    code: 'APPLICATION_EVALUATED',
                    data: snapshotOf(page, { quote: smaller }),
                }),
            ),
            answers(
                operation({
                    code: 'APPLICATION_EVALUATED',
                    data: snapshotOf(page, { quote }),
                }),
            ),
        );
        render(<BusinessApply {...page} />);

        await user.click(
            screen.getByRole('checkbox', { name: /^I accept this offer/u }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Take a smaller amount' }),
        );

        const recalculate = screen.getByRole('button', {
            name: 'Recalculate',
        });

        expect(recalculate).toBeDisabled();
        expect(
            screen.getByText(/^Up to the offer, in whole notes of RWF 5,000/u),
        ).toBeInTheDocument();

        const amount = screen.getByLabelText('Amount you want (RWF)');

        await user.type(amount, '030,000,000');
        expect(amount).toHaveValue('30,000,000');
        await user.click(recalculate);

        expect(
            await screen.findByText(
                'You chose RWF 30,000,000 of the RWF 33,915,000 offered.',
            ),
        ).toBeInTheDocument();
        expect(inertia.calls[0]).toEqual({
            url: '/preview/business-apply-evaluations',
            method: 'post',
            body: {
                target: page.application.target?.amount,
                term_months: page.application.term_months,
                evidence_version: page.evidence.version,
                accepted_principal: '30000000',
                identity_context_revision: page.identity_context_revision,
                expected_revision: page.application.revision,
                request_id: expect.any(String),
            },
        });
        expect(
            screen.getByRole('checkbox', { name: /^I accept this offer/u }),
        ).not.toBeChecked();

        await user.click(
            screen.getByRole('button', { name: 'Use the full offer' }),
        );

        await waitFor(() =>
            expect(screen.queryByText(/^You chose/u)).not.toBeInTheDocument(),
        );
        expect(inertia.calls[1].body).not.toHaveProperty('accepted_principal');
        expect(inertia.calls[1].body.request_id).not.toBe(
            inertia.calls[0].body.request_id,
        );
    });

    it('recalculates on Enter, ignores an empty amount and can put the control away', async () => {
        const user = userEvent.setup();

        render(<BusinessApply {...props(reviewStep)} />);

        await user.click(
            screen.getByRole('button', { name: 'Take a smaller amount' }),
        );

        const amount = screen.getByLabelText('Amount you want (RWF)');

        await user.type(amount, '{Enter}');
        expect(inertia.calls).toHaveLength(0);
        await user.type(amount, 'x{Tab}');
        expect(amount).toHaveValue('');

        await user.type(amount, '25000000{Enter}');
        expect(inertia.calls[0].body).toMatchObject({
            accepted_principal: '25000000',
        });
        expect(
            screen.getByRole('button', { name: 'Recalculating…' }),
        ).toHaveAttribute('aria-busy', 'true');

        await user.type(amount, '{Enter}');
        expect(inertia.calls).toHaveLength(1);

        await user.click(
            screen.getByRole('button', { name: 'Keep the offer' }),
        );
        expect(
            screen.queryByLabelText('Amount you want (RWF)'),
        ).not.toBeInTheDocument();
    });

    it('opens the smaller-amount control on the server’s field error for it', () => {
        inertia.errors = {
            accepted_principal:
                'Choose a whole number of notes up to the offer',
        };

        render(<BusinessApply {...props(reviewStep)} />);

        expect(screen.getByLabelText('Amount you want (RWF)')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(
            screen.getByText('Choose a whole number of notes up to the offer'),
        ).toBeInTheDocument();
    });

    it('opens the reduced-offer preview on its recalculated schedule', () => {
        render(<BusinessApply {...props(reducedStep)} />);

        expect(
            screen.getByText(
                'You chose RWF 30,000,000 of the RWF 33,915,000 offered.',
            ),
        ).toBeInTheDocument();
        expect(
            within(
                screen.getByRole('list', { name: 'Repayment schedule' }),
            ).getAllByText('RWF 5,545,000'),
        ).toHaveLength(6);
    });

    it('shows a chosen smaller amount without offering to change it to someone who may not sign', () => {
        const page = props(reviewStep);
        const quote = page.quote as ReadyQuote;

        page.quote = {
            ...quote,
            principal: { currency: 'RWF', amount: '30000000' },
        };
        page.allowed_actions = ['application.save'];
        render(<BusinessApply {...page} />);

        expect(
            screen.getByText(
                'You chose RWF 30,000,000 of the RWF 33,915,000 offered.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Use the full offer' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Take a smaller amount' }),
        ).not.toBeInTheDocument();
    });

    it('keeps the smaller-amount error inline rather than in a banner', () => {
        inertia.errors = { accepted_principal: 'Too much' };

        render(<BusinessApply {...props(reviewStep)} />);

        expect(screen.getByText('Too much')).toBeInTheDocument();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('marks server field errors inline', () => {
        inertia.errors = {
            signature_name: 'Sign with your full name',
            disclosures: 'Tick every disclosure',
        };
        render(<BusinessApply {...props(reviewStep)} />);

        expect(screen.getByLabelText('Your full name')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(
            screen.getByText('Sign with your full name'),
        ).toBeInTheDocument();
        expect(screen.getByText('Tick every disclosure')).toBeInTheDocument();
    });
});

describe('Apply — submitted', () => {
    it('confirms the submission with its application ID and live review timeline', () => {
        render(<BusinessApply {...props(submittedStep)} />);

        expect(
            screen.queryByRole('progressbar', { name: 'Application progress' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('heading', {
                name: 'Your application has been submitted',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Application ID · 01k6p4c8s3d0f4g9h2j6k1m7n3'),
        ).toBeInTheDocument();
        expect(screen.queryByText(/^Note ID/u)).not.toBeInTheDocument();

        const stages = within(
            screen.getByRole('list', { name: 'Application progress' }),
        ).getAllByRole('listitem');

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

    it('shows the note ID once the server has issued one', () => {
        const page = props(submittedStep);

        page.submission = {
            ...page.submission!,
            note_id: 'RNP-2026-0412',
        };
        render(<BusinessApply {...page} />);

        expect(screen.getByText('Note ID · RNP-2026-0412')).toBeInTheDocument();
    });

    it('renders nothing in the sheet body without a submission record', () => {
        const missing = props(submittedStep);

        missing.submission = null;
        render(<BusinessApply {...missing} />);

        expect(
            screen.queryByRole('heading', {
                name: 'Your application has been submitted',
            }),
        ).not.toBeInTheDocument();
    });
});

describe('Apply — without Home', () => {
    it('opens the sheet over an empty backdrop and hides the destinations the server left out', () => {
        render(<BusinessApply {...props(liveStep)} />);

        expect(
            screen.getByRole('dialog', { name: 'Raise application' }),
        ).toBeInTheDocument();
        expect(screen.queryByText('GreenLeaf Agro')).not.toBeInTheDocument();

        const nav = screen.getByRole('navigation', { name: 'App navigation' });

        expect(within(nav).getByRole('link', { name: 'Home' })).toHaveAttribute(
            'href',
            '/business',
        );
        expect(
            within(nav).queryByRole('link', { name: 'Reports' }),
        ).not.toBeInTheDocument();
        expect(
            within(nav).queryByRole('link', { name: 'Profile' }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Launcher' })).toHaveAttribute(
            'href',
            '/dashboard',
        );
    });

    it('reads its navigation from shell_links rather than Home', () => {
        const page = props(raiseStep);

        page.shell_links = { ...page.shell_links, profile: null };
        render(<BusinessApply {...page} />);

        expect(screen.getAllByText('GreenLeaf Agro').length).toBeGreaterThan(0);

        const nav = screen.getByRole('navigation', { name: 'App navigation' });

        expect(
            within(nav).getByRole('link', { name: 'Reports' }),
        ).toHaveAttribute('href', '/preview/business-reports');
        expect(
            within(nav).queryByRole('link', { name: 'Profile' }),
        ).not.toBeInTheDocument();
    });
});

describe('Apply — the live business-application-v1 projection', () => {
    const LIVE =
        '/business/01k6p4b7r2c9d3f8g1h5j0k6m2/applications/01k6p4c8s3d0f4g9h2j6k1m7n3' as const;

    it('reads the flat draft and the quote with its offered principal, with no preview outcome', () => {
        const page = props(liveStep);

        expect(page.preview_outcome).toBeUndefined();
        expect(page.home).toBeNull();
        render(<BusinessApply {...page} />);

        expect(screen.getByLabelText('Fundraising target (RWF)')).toHaveValue(
            '35,000,000',
        );
        expect(screen.getAllByText('RWF 33,915,000').length).toBeGreaterThan(0);
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('sends the autosave and evaluation to the server’s own actions, the autosave without a step', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const page = props(liveStep);

        inertia.queue.push(answers(operation({ data: snapshotOf(page) })));
        render(<BusinessApply {...page} />);

        await typeRequest(user);
        act(() => {
            vi.advanceTimersByTime(450);
        });

        await waitFor(() => expect(inertia.calls).toHaveLength(2));
        expect(inertia.calls.map(({ url, method }) => [url, method])).toEqual([
            [`${LIVE}/save`, 'post'],
            [`${LIVE}/evaluate`, 'post'],
        ]);
        expect(inertia.calls[0].body).not.toHaveProperty('step');
        expect(Object.keys(inertia.calls[1].body).sort()).toEqual([
            'evidence_version',
            'expected_revision',
            'identity_context_revision',
            'request_id',
            'target',
            'term_months',
        ]);
    });

    it('looks a lost answer up at the server’s lookup with the command and identity context', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const page = props(liveStep);

        inertia.queue.push(offline());
        render(<BusinessApply {...page} />);

        await typeRequest(user);
        act(() => {
            vi.advanceTimersByTime(450);
        });

        await waitFor(() => expect(inertia.calls).toHaveLength(2));
        expect(inertia.calls[1]).toEqual({
            url: `/business/application-operations/${String(inertia.calls[0].body.request_id)}`,
            method: 'get',
            body: { command: 'save', identity_context_revision: 4 },
        });
    });

    it('saves a just-created draft from the Business step with its step, at the server’s action', async () => {
        const user = userEvent.setup();
        const page = props(minimalStep);

        expect(page.quote).toBeNull();
        render(<BusinessApply {...page} />);

        expect(screen.getByRole('link', { name: 'Close' })).toHaveAttribute(
            'href',
            '/business',
        );
        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(inertia.calls[0]).toEqual({
            url: `${LIVE}/save`,
            method: 'post',
            body: {
                title: '',
                target: null,
                term_months: null,
                use_of_funds: [],
                story: '',
                step: 'raise',
                identity_context_revision: 4,
                expected_revision: 1,
                request_id: expect.any(String),
            },
        });
    });
});

describe('Apply — no approved legal text yet', () => {
    it('says the agreement is not available, with nothing to sign and no stand-in text', () => {
        render(<BusinessApply {...props(noLegalStep)} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            "The agreement isn't available yet.",
        );
        expect(
            screen.queryByRole('button', { name: 'Sign application' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(screen.queryByText('Risk disclosures')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Read' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByLabelText('Your full name'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText(/^Your signature legally binds/u),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Take a smaller amount' }),
        ).not.toBeInTheDocument();
        expect(screen.getAllByText('RWF 33,915,000').length).toBeGreaterThan(0);
        expect(screen.getByText('RWF 0')).toBeInTheDocument();
    });

    it.each([
        ['the Privacy Note', 'privacy', false],
        ['the risk disclosures', null, true],
    ] as const)(
        'offers no signature when %s alone is missing',
        (_missing, document, withoutDisclosures) => {
            const page = props(reviewStep);

            page.acceptance.documents = page.acceptance.documents.filter(
                (candidate) => candidate.kind !== document,
            );

            if (withoutDisclosures) {
                page.acceptance.disclosures = [];
            }

            render(<BusinessApply {...page} />);

            expect(screen.getByRole('status')).toHaveTextContent(
                "The agreement isn't available yet.",
            );
            expect(
                screen.queryByRole('button', { name: 'Sign application' }),
            ).not.toBeInTheDocument();
        },
    );

    it('offers no signature even if a submit were allowed without the legal text', () => {
        const page = props(noLegalStep);

        page.allowed_actions = [
            'application.save',
            'application.evaluate',
            'application.submit',
        ];
        page.acceptance.documents = props(reviewStep).acceptance.documents;
        render(<BusinessApply {...page} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            "The agreement isn't available yet.",
        );
        expect(
            screen.queryByRole('button', { name: 'Sign application' }),
        ).not.toBeInTheDocument();
    });
});
