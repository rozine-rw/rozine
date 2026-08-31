import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import type {
    InvestorPreview,
    PulsePolicy,
    Sizing,
    Traction,
} from '@/lib/pulse';
import Pulse from '@/pages/pulse';

type RequestOptions<T> = {
    onSuccess: (response: T) => void;
    onError: (errors: Record<string, string | string[]>) => void;
    onNetworkError: () => void;
};

const mocks = vi.hoisted(() => {
    const investorHttp = {
        clearErrors: vi.fn(),
        errors: {} as Record<string, string>,
        post: vi.fn(),
        processing: false,
        setData: vi.fn(),
    };
    const businessHttp = {
        clearErrors: vi.fn(),
        errors: {} as Record<string, string>,
        post: vi.fn(),
        processing: false,
        setData: vi.fn(),
    };
    const investorPreviewHttp = {
        cancel: vi.fn(),
        errors: {} as Record<string, string>,
        post: vi.fn(),
        processing: false,
        setData: vi.fn(),
    };
    const businessPreviewHttp = {
        cancel: vi.fn(),
        errors: {} as Record<string, string>,
        post: vi.fn(),
        processing: false,
        setData: vi.fn(),
    };

    return {
        attachTopbar: true,
        businessHttp,
        businessPreviewHttp,
        invokeHeroDuringRender: true,
        investorHttp,
        investorPreviewHttp,
        previewBusinessUrl: vi.fn(() => '/pulse/business/preview'),
        previewInvestorUrl: vi.fn(() => '/pulse/investor/preview'),
        reload: vi.fn(),
        storeBusinessUrl: vi.fn(() => '/pulse/business'),
        storeInvestorUrl: vi.fn(() => '/pulse/investor'),
        usePoll: vi.fn(),
    };
});

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <span>HEAD:{title}</span>,
    router: { reload: mocks.reload },
    useHttp: (initial: Record<string, unknown>) => {
        if ('name' in initial) {
            return 'pledge_amount' in initial
                ? mocks.investorHttp
                : mocks.businessHttp;
        }

        return 'pledge_amount' in initial
            ? mocks.investorPreviewHttp
            : mocks.businessPreviewHttp;
    },
    usePoll: mocks.usePoll,
}));

vi.mock('@/actions/App/Http/Controllers/PulseController', () => ({
    previewBusiness: { url: mocks.previewBusinessUrl },
    previewInvestor: { url: mocks.previewInvestorUrl },
    storeBusiness: { url: mocks.storeBusinessUrl },
    storeInvestor: { url: mocks.storeInvestorUrl },
}));

vi.mock('@/components/pulse/pulse-chrome', () => ({
    PulseBackdrop: () => <span>Backdrop</span>,
    PulseBriefs: () => <span>Briefs</span>,
    PulseDisclaimer: () => <span>Disclaimer</span>,
    PulseFooter: () => <span>Footer</span>,
    PulseTopbar: ({ barRef }: any) =>
        mocks.attachTopbar ? (
            <div ref={barRef} data-testid="topbar" />
        ) : (
            <div data-testid="topbar-without-ref" />
        ),
}));

vi.mock('@/components/pulse/pulse-hero', () => ({
    PulseHero: ({ onInvest, onBorrow }: any) => {
        if (mocks.invokeHeroDuringRender) {
            mocks.invokeHeroDuringRender = false;
            onInvest();
        }

        return (
            <>
                <button type="button" onClick={onInvest}>
                    Hero invest
                </button>
                <button type="button" onClick={onBorrow}>
                    Hero borrow
                </button>
            </>
        );
    },
    TractionCards: ({ investors, pledged, averageLoan }: any) => (
        <span>
            Traction {investors}/{pledged}/{String(averageLoan)}
        </span>
    ),
}));

vi.mock('@/components/pulse/investor-panel', () => ({
    InvestorPanel: ({
        panelRef,
        pledge,
        payout,
        notes,
        canSave,
        exampleOpen,
        onExampleToggle,
        onPledgeChange,
        onSaveSpot,
    }: any) => (
        <div ref={panelRef} data-testid="investor-panel">
            <span>
                Investor {pledge}/{String(payout)}/{notes.length}/
                {String(canSave)}/{String(exampleOpen)}
            </span>
            <button type="button" onClick={onExampleToggle}>
                Toggle example
            </button>
            <button type="button" onClick={() => onPledgeChange(25_000)}>
                Change pledge
            </button>
            <button type="button" onClick={onSaveSpot}>
                Open investor
            </button>
        </div>
    ),
}));

vi.mock('@/components/pulse/business-panel', () => ({
    BusinessPanel: ({
        panelRef,
        idle,
        parsing,
        result,
        progress,
        progressLabel,
        figures,
        sizing,
        canSize,
        termIndex,
        onFiguresChange,
        onSize,
        onTermChange,
        onSaveSpot,
    }: any) => (
        <div ref={panelRef} data-testid="business-panel">
            <span>
                Business {String(idle)}/{String(parsing)}/{String(result)}/
                {progress}/{progressLabel}/{String(canSize)}/{termIndex}/
                {figures.name}/{String(sizing?.qualified_amount)}
            </span>
            <button
                type="button"
                onClick={() => onFiguresChange({ name: 'GreenLeaf Agro' })}
            >
                Set business name
            </button>
            <button
                type="button"
                onClick={() => onFiguresChange({ annualRevenue: 24_000_000 })}
            >
                Set revenue
            </button>
            <button
                type="button"
                onClick={() => onFiguresChange({ annualCosts: 24_000_000 })}
            >
                Set equal costs
            </button>
            <button
                type="button"
                onClick={() => onFiguresChange({ annualCosts: 12_000_000 })}
            >
                Set costs
            </button>
            <button
                type="button"
                onClick={() => onFiguresChange({ sector: 'Agriculture' })}
            >
                Set sector
            </button>
            <button
                type="button"
                onClick={() => onFiguresChange({ registeredYear: '2020' })}
            >
                Set year
            </button>
            <button type="button" onClick={onSize}>
                Start sizing
            </button>
            <button type="button" onClick={() => onTermChange(0)}>
                Change term
            </button>
            <button type="button" onClick={onSaveSpot}>
                Open business
            </button>
        </div>
    ),
}));

vi.mock('@/components/pulse/investor-modal', () => ({
    InvestorModal: ({
        step,
        details,
        canSubmit,
        processing,
        contactError,
        onChange,
        onSubmit,
        onShare,
        onClose,
    }: any) => (
        <section aria-label="Investor modal mock">
            <span>
                Investor modal {step}/{details.name}/{String(canSubmit)}/
                {String(processing)}/{String(contactError)}
            </span>
            <button type="button" onClick={() => onChange({ name: 'Diane' })}>
                Investor name
            </button>
            <button
                type="button"
                onClick={() => onChange({ contact: 'invalid' })}
            >
                Investor bad contact
            </button>
            <button
                type="button"
                onClick={() => onChange({ contact: '+250 788 123 456' })}
            >
                Investor contact
            </button>
            <button
                type="button"
                onClick={() =>
                    onChange({
                        contactMethod: 'email',
                        contact: 'diane@rw.test',
                    })
                }
            >
                Investor email
            </button>
            <button
                type="button"
                onClick={() => onChange({ province: 'Kigali' })}
            >
                Investor province
            </button>
            <button
                type="button"
                onClick={() => onChange({ district: 'Gasabo' })}
            >
                Investor district
            </button>
            <button type="button" onClick={onSubmit}>
                Submit investor
            </button>
            <button type="button" onClick={() => onShare('Investor shared')}>
                Share investor
            </button>
            <button type="button" onClick={onClose}>
                Close investor
            </button>
        </section>
    ),
}));

vi.mock('@/components/pulse/business-modal', () => ({
    BusinessModal: ({
        step,
        details,
        anonymous,
        canSubmit,
        processing,
        contactError,
        loanNumber,
        onAnonymousChange,
        onChange,
        onSubmit,
        onShare,
        onClose,
    }: any) => (
        <section aria-label="Business modal mock">
            <span>
                Business modal {step}/{details.name}/{String(anonymous)}/
                {String(canSubmit)}/{String(processing)}/{String(contactError)}/
                {loanNumber}
            </span>
            <button type="button" onClick={() => onAnonymousChange(!anonymous)}>
                Toggle anonymous
            </button>
            <button
                type="button"
                onClick={() => onChange({ name: 'Named Business' })}
            >
                Business name
            </button>
            <button
                type="button"
                onClick={() => onChange({ contact: '+250 788 123 456' })}
            >
                Business contact
            </button>
            <button
                type="button"
                onClick={() =>
                    onChange({
                        contactMethod: 'email',
                        contact: 'owner@business.test',
                    })
                }
            >
                Business email
            </button>
            <button
                type="button"
                onClick={() => onChange({ province: 'Kigali' })}
            >
                Business province
            </button>
            <button
                type="button"
                onClick={() => onChange({ district: 'Gasabo' })}
            >
                Business district
            </button>
            <button type="button" onClick={onSubmit}>
                Submit business
            </button>
            <button type="button" onClick={() => onShare('Business shared')}>
                Share business
            </button>
            <button type="button" onClick={onClose}>
                Close business
            </button>
        </section>
    ),
}));

vi.mock('@/components/pulse/pulse-toast', () => ({
    PulseToast: ({ message }: { message: string }) => (
        <span role="status">{message}</span>
    ),
}));

const traction: Traction = {
    pledged: 1_000_000,
    investors: 5,
    businesses: 2,
    average_loan: 9_000_000,
    average_yield: 13,
    average_rating: 3.8,
    average_term: 6,
};

const policy: PulsePolicy = {
    terms: [3, 6, 9, 12],
    sectors: ['Agriculture', 'Other'],
    registration_years: [2026, 2020],
    minimum_revenue: 15_000_000,
    minimum_loan: 5_000_000,
    maximum_loan: 50_000_000,
    pledge: {
        minimum: 5_000,
        maximum: 50_000_000,
        step: 5_000,
        default: 500_000,
    },
};

const businessPreview: Sizing = {
    rating: { band: 'Strong', score: 4 },
    flat_rate: 12.5,
    sized_amount: 10_000_000,
    qualified_amount: 10_000_000,
    monthly_repayment: 1_000_000,
    monthly_surplus: 1_500_000,
    cover_ratio: 1.5,
    below_minimum: false,
    at_maximum: false,
    required_surplus: 500_000,
    status: 'pre_qualified',
};

const investorPreview = (
    changes: Partial<InvestorPreview> = {},
): InvestorPreview => ({
    pledge_amount: 500_000,
    projected_return: 565_000,
    blended_yield: 13,
    listings: [
        {
            id: 1,
            initial: 'G',
            name: 'GreenLeaf Agro',
            district: 'Gasabo',
            term: '6mo',
            yield: '13%',
            yield_rate: 13,
            rating_band: 'Strong',
            rating_score: '4.0',
            accent: 0,
            projected_return: 565_000,
        },
    ],
    ...changes,
});

const renderPage = () =>
    render(
        <Pulse
            traction={traction}
            districts={{ Kigali: ['Gasabo'] }}
            policy={policy}
            investor_preview={investorPreview()}
        />,
    );

beforeEach(() => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    mocks.attachTopbar = true;
    mocks.invokeHeroDuringRender = true;
    mocks.businessHttp.errors = {};
    mocks.businessHttp.processing = false;
    mocks.businessHttp.clearErrors.mockReset();
    mocks.businessHttp.post.mockReset();
    mocks.businessHttp.setData.mockReset();
    mocks.businessPreviewHttp.cancel.mockReset();
    mocks.businessPreviewHttp.post.mockReset();
    mocks.businessPreviewHttp.processing = false;
    mocks.businessPreviewHttp.setData.mockReset();
    mocks.investorHttp.errors = {};
    mocks.investorHttp.processing = false;
    mocks.investorHttp.clearErrors.mockReset();
    mocks.investorHttp.post.mockReset();
    mocks.investorHttp.setData.mockReset();
    mocks.investorPreviewHttp.cancel.mockReset();
    mocks.investorPreviewHttp.post.mockReset();
    mocks.investorPreviewHttp.processing = false;
    mocks.investorPreviewHttp.setData.mockReset();
    mocks.reload.mockReset();
    mocks.storeBusinessUrl.mockClear();
    mocks.storeInvestorUrl.mockClear();
    mocks.previewBusinessUrl.mockClear();
    mocks.previewInvestorUrl.mockClear();
    mocks.usePoll.mockReset();

    Object.defineProperty(window, 'scrollY', {
        configurable: true,
        value: 5,
    });
    vi.stubGlobal('scrollTo', vi.fn());
    vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockImplementation(
        function (this: HTMLElement) {
            const testId = this.getAttribute('data-testid');

            if (testId === 'topbar') {
                return { height: 50 } as DOMRect;
            }

            if (testId === 'investor-panel') {
                return { top: 200 } as DOMRect;
            }

            return { top: 10 } as DOMRect;
        },
    );
});

afterEach(() => {
    vi.runOnlyPendingTimers();
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

async function completeBusinessFigures(
    user: ReturnType<typeof userEvent.setup>,
) {
    await user.click(screen.getByRole('button', { name: 'Set business name' }));
    await user.click(screen.getByRole('button', { name: 'Set revenue' }));
    await user.click(screen.getByRole('button', { name: 'Set equal costs' }));
    await user.click(screen.getByRole('button', { name: 'Set costs' }));
    await user.click(screen.getByRole('button', { name: 'Set sector' }));
    await user.click(screen.getByRole('button', { name: 'Set year' }));
}

async function completeInvestorDetails(
    user: ReturnType<typeof userEvent.setup>,
) {
    await user.click(screen.getByRole('button', { name: 'Investor name' }));
    await user.click(
        screen.getByRole('button', { name: 'Investor bad contact' }),
    );
    await user.click(screen.getByRole('button', { name: 'Investor email' }));
    await user.click(screen.getByRole('button', { name: 'Investor province' }));
    await user.click(screen.getByRole('button', { name: 'Investor district' }));
}

async function completeBusinessDetails(
    user: ReturnType<typeof userEvent.setup>,
) {
    await user.click(screen.getByRole('button', { name: 'Business name' }));
    await user.click(screen.getByRole('button', { name: 'Business contact' }));
    await user.click(screen.getByRole('button', { name: 'Business email' }));
    await user.click(screen.getByRole('button', { name: 'Business province' }));
    await user.click(screen.getByRole('button', { name: 'Business district' }));
}

async function previewBusinessSizing(
    user: ReturnType<typeof userEvent.setup>,
    response: Sizing = businessPreview,
): Promise<RequestOptions<Sizing>> {
    let request: RequestOptions<Sizing> | undefined;

    mocks.businessPreviewHttp.post.mockImplementation(
        (_url: string, options: RequestOptions<Sizing>) => {
            request = options;
        },
    );
    await completeBusinessFigures(user);
    await user.click(screen.getByRole('button', { name: 'Start sizing' }));

    act(() => {
        request?.onSuccess(response);
    });
    await act(async () => {
        vi.advanceTimersByTime(360);
    });

    if (!request) {
        throw new Error('Business preview request was not captured.');
    }

    return request;
}

describe('Pulse page orchestration', () => {
    it('polls, scrolls both directions, toggles the example, and updates pledge', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        renderPage();

        expect(mocks.usePoll).toHaveBeenCalledWith(10000, {
            only: ['traction'],
            async: true,
        });
        await user.click(screen.getByRole('button', { name: 'Hero invest' }));
        await user.click(screen.getByRole('button', { name: 'Hero borrow' }));

        expect(window.scrollTo).toHaveBeenCalledWith({
            top: 137,
            behavior: 'smooth',
        });
        expect(window.scrollTo).toHaveBeenCalledWith({
            top: 0,
            behavior: 'smooth',
        });

        await user.click(
            screen.getByRole('button', { name: 'Toggle example' }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Toggle example' }),
        );
        await user.click(screen.getByRole('button', { name: 'Change pledge' }));
        expect(
            screen.getByText(/Investor 25000\/null\/0\/false/),
        ).toBeInTheDocument();

        await act(async () => {
            vi.advanceTimersByTime(250);
        });
        expect(mocks.investorPreviewHttp.setData).toHaveBeenCalledWith({
            pledge_amount: 25_000,
        });
        expect(mocks.investorPreviewHttp.post).toHaveBeenCalledWith(
            '/pulse/investor/preview',
            expect.any(Object),
        );

        const request = mocks.investorPreviewHttp.post.mock.calls[0]?.[1] as
            | RequestOptions<InvestorPreview>
            | undefined;

        act(() => {
            request?.onSuccess(
                investorPreview({
                    pledge_amount: 25_000,
                    projected_return: 28_250,
                    listings: [],
                }),
            );
        });
        expect(
            screen.getByText(/Investor 25000\/28250\/0\/true/),
        ).toBeInTheDocument();
    });

    it('scrolls without a topbar ref', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        mocks.attachTopbar = false;
        renderPage();
        await user.click(screen.getByRole('button', { name: 'Hero invest' }));

        expect(window.scrollTo).toHaveBeenLastCalledWith({
            top: 187,
            behavior: 'smooth',
        });
    });

    it('does not allow a spot to open while a current preview is processing', () => {
        mocks.investorPreviewHttp.processing = true;

        renderPage();

        expect(
            screen.getByText(/Investor 500000\/565000\/1\/false/),
        ).toBeInTheDocument();
    });

    it('requests sizing from the server and reveals only its returned result', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        renderPage();

        await user.click(screen.getByRole('button', { name: 'Start sizing' }));
        expect(mocks.businessPreviewHttp.post).not.toHaveBeenCalled();

        await completeBusinessFigures(user);
        await user.click(screen.getByRole('button', { name: 'Start sizing' }));

        expect(mocks.businessPreviewHttp.setData).toHaveBeenCalledWith({
            annual_revenue: 24_000_000,
            annual_costs: 12_000_000,
            sector: 'Agriculture',
            registered_year: 2020,
            term_months: 12,
        });
        expect(mocks.businessPreviewHttp.post).toHaveBeenCalledWith(
            '/pulse/business/preview',
            expect.any(Object),
        );

        expect(
            screen.getByText(/true\/false\/8\/CHECKING/),
        ).toBeInTheDocument();

        await act(async () => {
            vi.advanceTimersByTime(960);
        });
        expect(screen.getByText(/MODELLING CASH FLOW/)).toBeInTheDocument();

        await act(async () => {
            vi.advanceTimersByTime(960);
        });
        expect(screen.getByText(/SIZING CAPACITY/)).toBeInTheDocument();
        expect(screen.getByText(/false\/true\/false/)).toBeInTheDocument();

        const request = mocks.businessPreviewHttp.post.mock.calls[0]?.[1] as
            | RequestOptions<Sizing>
            | undefined;

        act(() => {
            request?.onSuccess(businessPreview);
        });
        expect(screen.getByText(/100\/SIZING CAPACITY/)).toBeInTheDocument();

        await act(async () => vi.advanceTimersByTime(360));
        expect(screen.getByText(/false\/false\/true/)).toBeInTheDocument();
        expect(screen.getByText(/10000000/)).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Change term' }));
        expect(mocks.businessPreviewHttp.setData).toHaveBeenLastCalledWith(
            expect.objectContaining({ term_months: 3 }),
        );
    });

    it('opens each business step and preserves an existing signup name', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        renderPage();
        await user.click(screen.getByRole('button', { name: 'Open business' }));
        expect(
            screen.queryByLabelText('Business modal mock'),
        ).not.toBeInTheDocument();

        await previewBusinessSizing(user);
        await user.click(screen.getByRole('button', { name: 'Open business' }));
        expect(screen.getByText(/Business modal result/)).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Business name' }));
        await user.click(
            screen.getByRole('button', { name: 'Close business' }),
        );

        let request: RequestOptions<Sizing> | undefined;
        mocks.businessPreviewHttp.post.mockImplementation(
            (_url: string, options: RequestOptions<Sizing>) => {
                request = options;
            },
        );
        await user.click(screen.getByRole('button', { name: 'Change term' }));

        act(() => {
            request?.onSuccess(businessPreview);
        });
        await act(async () => vi.advanceTimersByTime(360));
        await user.click(screen.getByRole('button', { name: 'Open business' }));
        expect(
            screen.getByText(/Business modal result\/Named Business/),
        ).toBeInTheDocument();
    });

    it('submits an investor and handles success, server, fallback, and network replies', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        let request:
            | RequestOptions<{
                  queue_number: string;
                  traction: Traction;
                  investor_preview: InvestorPreview;
              }>
            | undefined;

        mocks.investorHttp.post.mockImplementation(
            (
                _url: string,
                options: RequestOptions<{
                    queue_number: string;
                    traction: Traction;
                    investor_preview: InvestorPreview;
                }>,
            ) => {
                request = options;
            },
        );
        renderPage();
        await user.click(screen.getByRole('button', { name: 'Open investor' }));
        await completeInvestorDetails(user);
        await user.click(
            screen.getByRole('button', { name: 'Submit investor' }),
        );

        expect(mocks.investorHttp.setData).toHaveBeenCalledWith({
            name: 'Diane',
            contact: 'diane@rw.test',
            contact_method: 'email',
            province: 'Kigali',
            district: 'Gasabo',
            pledge_amount: 500_000,
        });
        expect(mocks.investorHttp.post).toHaveBeenCalledWith(
            '/pulse/investor',
            expect.any(Object),
        );

        act(() => {
            request?.onError({ contact: ['First error', 'Second error'] });
        });
        expect(screen.getByRole('status')).toHaveTextContent('First error');

        act(() => {
            request?.onError({});
        });
        expect(screen.getByRole('status')).toHaveTextContent(
            'Something went wrong. Please try again.',
        );

        act(() => {
            request?.onNetworkError();
        });
        expect(screen.getByRole('status')).toHaveTextContent(
            'We could not reach the server. Please try again.',
        );

        act(() => {
            request?.onSuccess({
                queue_number: '#0200',
                traction,
                investor_preview: investorPreview(),
            });
        });
        expect(screen.getByText(/Investor modal pledged/)).toBeInTheDocument();
        expect(mocks.reload).toHaveBeenCalledWith({
            only: ['traction'],
            async: true,
        });
    });

    it('submits a listed business and replaces its preview with the server receipt', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        let request:
            | RequestOptions<{
                  queue_number: string;
                  loan_number: string;
                  traction: Traction;
                  business_preview: Sizing;
              }>
            | undefined;

        mocks.businessHttp.errors = { contact: 'Server contact error' };
        mocks.businessHttp.processing = true;
        mocks.businessHttp.post.mockImplementation(
            (
                _url: string,
                options: RequestOptions<{
                    queue_number: string;
                    loan_number: string;
                    traction: Traction;
                    business_preview: Sizing;
                }>,
            ) => {
                request = options;
            },
        );
        renderPage();
        await previewBusinessSizing(user);
        await user.click(screen.getByRole('button', { name: 'Open business' }));
        await completeBusinessDetails(user);

        expect(mocks.businessHttp.clearErrors).toHaveBeenCalledWith('contact');
        expect(
            screen.getByText(/true\/Server contact error/),
        ).toBeInTheDocument();

        mocks.businessHttp.processing = false;
        await user.click(
            screen.getByRole('button', { name: 'Toggle anonymous' }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Submit business' }),
        );

        expect(mocks.businessHttp.setData).toHaveBeenCalledWith({
            name: 'Named Business',
            contact: 'owner@business.test',
            contact_method: 'email',
            province: 'Kigali',
            district: 'Gasabo',
            annual_revenue: 24_000_000,
            annual_costs: 12_000_000,
            sector: 'Agriculture',
            registered_year: 2020,
            term_months: 12,
            listed: false,
        });

        act(() => {
            request?.onError({ contact: 'Fix the contact' });
            request?.onNetworkError();
            request?.onSuccess({
                queue_number: '#0201',
                loan_number: '#2,001',
                traction,
                business_preview: {
                    ...businessPreview,
                    qualified_amount: 11_000_000,
                },
            });
        });
        expect(screen.getByText(/Business modal pass/)).toBeInTheDocument();
        expect(screen.getByText(/#2,001/)).toBeInTheDocument();

        expect(mocks.reload).toHaveBeenCalledWith({
            only: ['traction'],
            async: true,
        });
    });

    it('clears investor contact errors for contact and method but not unrelated changes', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        mocks.investorHttp.errors = { contact: 'Bad contact' };
        renderPage();
        await user.click(screen.getByRole('button', { name: 'Open investor' }));
        await user.click(screen.getByRole('button', { name: 'Investor name' }));
        await user.click(
            screen.getByRole('button', { name: 'Investor contact' }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Investor email' }),
        );

        expect(mocks.investorHttp.clearErrors).toHaveBeenCalledTimes(2);
        expect(mocks.investorHttp.clearErrors).toHaveBeenCalledWith('contact');
    });

    it('fails closed for stale, invalid, and unavailable server previews', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const investorRequests: RequestOptions<InvestorPreview>[] = [];
        const businessRequests: RequestOptions<Sizing>[] = [];

        mocks.investorPreviewHttp.post.mockImplementation(
            (_url: string, options: RequestOptions<InvestorPreview>) => {
                investorRequests.push(options);
            },
        );
        mocks.businessPreviewHttp.post.mockImplementation(
            (_url: string, options: RequestOptions<Sizing>) => {
                businessRequests.push(options);
            },
        );
        renderPage();

        await user.click(screen.getByRole('button', { name: 'Change pledge' }));
        await act(async () => vi.advanceTimersByTime(250));
        await user.click(screen.getByRole('button', { name: 'Change pledge' }));

        act(() => {
            investorRequests[0].onSuccess(
                investorPreview({ projected_return: 1 }),
            );
            investorRequests[0].onError({ pledge_amount: 'Stale error' });
            investorRequests[0].onNetworkError();
        });
        expect(screen.queryByRole('status')).not.toBeInTheDocument();

        await act(async () => vi.advanceTimersByTime(250));
        act(() => {
            investorRequests[1].onError({
                pledge_amount: ['Use a valid pledge'],
            });
        });
        expect(screen.getByRole('status')).toHaveTextContent(
            'Use a valid pledge',
        );

        await user.click(screen.getByRole('button', { name: 'Change pledge' }));
        await act(async () => vi.advanceTimersByTime(250));
        act(() => investorRequests[2].onNetworkError());
        expect(screen.getByRole('status')).toHaveTextContent(
            'We could not refresh the projection. Please try again.',
        );

        await completeBusinessFigures(user);
        await user.click(screen.getByRole('button', { name: 'Start sizing' }));
        act(() =>
            businessRequests[0].onError({
                annual_costs: 'Costs must be below revenue.',
            }),
        );
        expect(screen.getByRole('status')).toHaveTextContent(
            'Costs must be below revenue.',
        );

        await user.click(screen.getByRole('button', { name: 'Start sizing' }));
        act(() => businessRequests[1].onNetworkError());
        expect(screen.getByRole('status')).toHaveTextContent(
            'We could not reach the server. Please try again.',
        );
    });

    it('shows and expires shared toast messages, clearing a prior timer', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        renderPage();
        await user.click(screen.getByRole('button', { name: 'Open investor' }));
        await user.click(
            screen.getByRole('button', { name: 'Share investor' }),
        );
        expect(screen.getByRole('status')).toHaveTextContent('Investor shared');

        await user.click(
            screen.getByRole('button', { name: 'Share investor' }),
        );
        await act(async () => {
            vi.advanceTimersByTime(2500);
        });
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('cleans active preview, toast, sizing, and result timers on unmount', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const clearTimeoutSpy = vi.spyOn(globalThis, 'clearTimeout');
        const clearIntervalSpy = vi.spyOn(globalThis, 'clearInterval');
        const { unmount: unmountFirst } = renderPage();

        await user.click(screen.getByRole('button', { name: 'Open investor' }));
        await user.click(
            screen.getByRole('button', { name: 'Share investor' }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Close investor' }),
        );
        await user.click(screen.getByRole('button', { name: 'Change pledge' }));
        await completeBusinessFigures(user);
        await user.click(screen.getByRole('button', { name: 'Start sizing' }));
        unmountFirst();

        expect(clearTimeoutSpy).toHaveBeenCalled();
        expect(clearIntervalSpy).toHaveBeenCalled();
        expect(mocks.investorPreviewHttp.cancel).toHaveBeenCalled();
        expect(mocks.businessPreviewHttp.cancel).toHaveBeenCalled();

        const { unmount: unmountSecond } = renderPage();
        let request: RequestOptions<Sizing> | undefined;
        mocks.businessPreviewHttp.post.mockImplementation(
            (_url: string, options: RequestOptions<Sizing>) => {
                request = options;
            },
        );
        await completeBusinessFigures(user);
        await user.click(screen.getByRole('button', { name: 'Start sizing' }));
        act(() => {
            request?.onSuccess(businessPreview);
        });
        unmountSecond();
        expect(clearTimeoutSpy.mock.calls.length).toBeGreaterThan(1);
    });
});
