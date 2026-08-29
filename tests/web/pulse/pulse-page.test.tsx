import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { Listing, Traction } from '@/lib/pulse';
import Pulse from '@/pages/pulse';

type RequestOptions = {
    onSuccess: (response: {
        queue_number: string;
        loan_number?: string;
        traction: Traction;
    }) => void;
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

    return {
        attachTopbar: true,
        businessHttp,
        invokeHeroDuringRender: true,
        investorHttp,
        reload: vi.fn(),
        storeBusinessUrl: vi.fn(() => '/pulse/business'),
        storeInvestorUrl: vi.fn(() => '/pulse/investor'),
        usePoll: vi.fn(),
    };
});

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <span>HEAD:{title}</span>,
    router: { reload: mocks.reload },
    useHttp: (initial: Record<string, unknown>) =>
        'pledge_amount' in initial ? mocks.investorHttp : mocks.businessHttp,
    usePoll: mocks.usePoll,
}));

vi.mock('@/actions/App/Http/Controllers/PulseController', () => ({
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
        exampleOpen,
        onExampleToggle,
        onPledgeChange,
        onSaveSpot,
    }: any) => (
        <div ref={panelRef} data-testid="investor-panel">
            <span>
                Investor {pledge}/{payout}/{notes.length}/{String(exampleOpen)}
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
                {figures.name}/{sizing.qualifiedAmount}
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

const listings: Listing[] = [
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
    },
];

const renderPage = () =>
    render(
        <Pulse
            traction={traction}
            listings={listings}
            districts={{ Kigali: ['Gasabo'] }}
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
    mocks.investorHttp.errors = {};
    mocks.investorHttp.processing = false;
    mocks.investorHttp.clearErrors.mockReset();
    mocks.investorHttp.post.mockReset();
    mocks.investorHttp.setData.mockReset();
    mocks.reload.mockReset();
    mocks.storeBusinessUrl.mockClear();
    mocks.storeInvestorUrl.mockClear();
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

describe('Pulse page orchestration', () => {
    it('polls, scrolls both directions, toggles the example, and updates pledge', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        renderPage();

        expect(mocks.usePoll).toHaveBeenCalledWith(10000, {
            only: ['traction', 'listings'],
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
            screen.getByText(/Investor 25000\/28249\.999/),
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

    it('fails closed through every sizing prerequisite then reaches every progress stage', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        renderPage();

        await user.click(screen.getByRole('button', { name: 'Start sizing' }));
        expect(screen.getByText(/CHECKING YOUR FIGURES/)).toBeInTheDocument();

        await completeBusinessFigures(user);
        await user.click(screen.getByRole('button', { name: 'Change term' }));
        await user.click(screen.getByRole('button', { name: 'Start sizing' }));

        expect(
            screen.getByText(/true\/false\/8\/CHECKING/),
        ).toBeInTheDocument();

        await act(async () => {
            vi.advanceTimersByTime(960);
        });
        expect(screen.getByText(/MODELLING CASH FLOW/)).toBeInTheDocument();

        await act(async () => {
            vi.advanceTimersByTime(640);
        });
        expect(screen.getByText(/SIZING CAPACITY/)).toBeInTheDocument();

        await act(async () => {
            vi.advanceTimersByTime(360);
        });
        expect(screen.getByText(/false\/false\/true/)).toBeInTheDocument();
    });

    it('opens each business step and preserves an existing signup name', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        renderPage();
        await user.click(screen.getByRole('button', { name: 'Open business' }));
        expect(screen.getByText(/Business modal result/)).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Business name' }));
        await user.click(
            screen.getByRole('button', { name: 'Close business' }),
        );

        await completeBusinessFigures(user);
        await user.click(screen.getByRole('button', { name: 'Start sizing' }));
        await user.click(screen.getByRole('button', { name: 'Open business' }));
        expect(screen.getByText(/Business modal parsing/)).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Close business' }),
        );

        await act(async () => {
            vi.advanceTimersByTime(1960);
        });
        await user.click(screen.getByRole('button', { name: 'Open business' }));
        expect(
            screen.getByText(/Business modal result\/Named Business/),
        ).toBeInTheDocument();
    });

    it('submits an investor and handles success, server, fallback, and network replies', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        let request: RequestOptions | undefined;

        mocks.investorHttp.post.mockImplementation(
            (_url: string, options: RequestOptions) => {
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
            request?.onSuccess({ queue_number: '#0200', traction });
        });
        expect(screen.getByText(/Investor modal pledged/)).toBeInTheDocument();
        expect(mocks.reload).toHaveBeenCalledWith({
            only: ['traction', 'listings'],
            async: true,
        });
    });

    it('submits a listed business, clears contact errors, and handles both loan-number replies', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        let request: RequestOptions | undefined;

        mocks.businessHttp.errors = { contact: 'Server contact error' };
        mocks.businessHttp.processing = true;
        mocks.businessHttp.post.mockImplementation(
            (_url: string, options: RequestOptions) => {
                request = options;
            },
        );
        renderPage();
        await completeBusinessFigures(user);
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
            });
        });
        expect(screen.getByText(/Business modal pass/)).toBeInTheDocument();
        expect(screen.getByText(/#2,001/)).toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Submit business' }),
        );
        act(() => {
            request?.onSuccess({ queue_number: '#0202', traction });
        });
        expect(screen.getByText(/#2,001/)).toBeInTheDocument();
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

    it('cleans active toast, sizing, and result timers on unmount', async () => {
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
        await completeBusinessFigures(user);
        await user.click(screen.getByRole('button', { name: 'Start sizing' }));
        unmountFirst();

        expect(clearTimeoutSpy).toHaveBeenCalled();
        expect(clearIntervalSpy).toHaveBeenCalled();

        const { unmount: unmountSecond } = renderPage();
        await completeBusinessFigures(user);
        await user.click(screen.getByRole('button', { name: 'Start sizing' }));
        await act(async () => {
            vi.advanceTimersByTime(1600);
        });
        unmountSecond();
        expect(clearTimeoutSpy.mock.calls.length).toBeGreaterThan(1);
    });
});
