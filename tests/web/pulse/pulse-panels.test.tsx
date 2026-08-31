import { fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createRef } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { BusinessPanel } from '@/components/pulse/business-panel';
import type { BusinessFigures } from '@/components/pulse/business-panel';
import { InvestorPanel } from '@/components/pulse/investor-panel';
import type { BusinessNote, PulsePolicy, Sizing } from '@/lib/pulse';

const policy: PulsePolicy = {
    terms: [3, 6, 9, 12],
    sectors: ['Agriculture', 'Other'],
    registration_years: [2026, 2025, 2020],
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

const figures = (changes: Partial<BusinessFigures> = {}): BusinessFigures => ({
    name: 'GreenLeaf Agro',
    annualRevenue: 24_000_000,
    annualCosts: 12_000_000,
    sector: 'Agriculture',
    registeredYear: '2020',
    ...changes,
});

const sizing = (changes: Partial<Sizing> = {}): Sizing => ({
    rating: {
        band: 'Strong',
        score: 4,
    },
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
    ...changes,
});

const businessDefaults = () => ({
    panelRef: createRef<HTMLDivElement>(),
    businesses: 4,
    averageLoan: 9_000_000 as number | null,
    averageRating: 3.7 as number | null,
    idle: false,
    parsing: false,
    result: false,
    progress: 46,
    progressLabel: 'CHECKING YOUR FIGURES',
    figures: figures(),
    sizing: sizing(),
    policy,
    termIndex: 1,
    canSize: true,
    onFiguresChange: vi.fn(),
    onSize: vi.fn(),
    onTermChange: vi.fn(),
    onSaveSpot: vi.fn(),
});

const note = (): BusinessNote => ({
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
    avatar: { light: 'green', dark: 'lightgreen' },
    ratingColor: { light: 'green', dark: 'lightgreen' },
});

const investorDefaults = () => ({
    panelRef: createRef<HTMLDivElement>(),
    pledged: 1_000_000,
    investors: 5,
    averageYield: 13.2 as number | null,
    averageTerm: 6 as number | null,
    pledge: 500_000,
    payout: 565_000,
    notes: [note()],
    policy,
    canSave: true,
    exampleOpen: false,
    onExampleToggle: vi.fn(),
    onPledgeChange: vi.fn(),
    onSaveSpot: vi.fn(),
});

describe('BusinessPanel', () => {
    it('collects every idle business figure and enables sizing', async () => {
        const user = userEvent.setup();
        const props = businessDefaults();

        render(<BusinessPanel {...props} idle />);

        fireEvent.change(screen.getByPlaceholderText('e.g. GreenLeaf Agro'), {
            target: { value: 'New Co' },
        });
        fireEvent.change(
            screen.getByLabelText('Total revenue over the last 12 months'),
            { target: { value: 'RWF 30,000,000' } },
        );
        fireEvent.change(
            screen.getByLabelText('Total costs over the last 12 months'),
            { target: { value: 'RWF 10,000,000' } },
        );
        await user.selectOptions(screen.getByLabelText('What you do'), 'Other');
        await user.selectOptions(
            screen.getByLabelText('Registered in'),
            '2026',
        );
        await user.click(
            screen.getByRole('button', { name: 'Check loan amount →' }),
        );

        expect(props.onFiguresChange).toHaveBeenCalledWith({ name: 'New Co' });
        expect(props.onFiguresChange).toHaveBeenCalledWith({
            annualRevenue: 30_000_000,
        });
        expect(props.onFiguresChange).toHaveBeenCalledWith({
            annualCosts: 10_000_000,
        });
        expect(props.onFiguresChange).toHaveBeenCalledWith({ sector: 'Other' });
        expect(props.onFiguresChange).toHaveBeenCalledWith({
            registeredYear: '2026',
        });
        expect(props.onSize).toHaveBeenCalledOnce();
        expect(
            screen.getByText('RWF 15M+ revenue in the last 12 months'),
        ).toBeInTheDocument();
    });

    it('shows the disabled, empty, and null-stat state', () => {
        const props = businessDefaults();

        render(
            <BusinessPanel
                {...props}
                idle
                averageLoan={null}
                averageRating={null}
                figures={figures({ annualRevenue: 0, annualCosts: 0 })}
                canSize={false}
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Check loan amount →' }),
        ).toBeDisabled();
        expect(screen.getAllByText('—')).toHaveLength(2);
    });

    it('does not make a browser-side eligibility decision from equal costs', () => {
        const props = businessDefaults();

        render(
            <BusinessPanel
                {...props}
                idle
                figures={figures({
                    annualRevenue: 12_000_000,
                    annualCosts: 12_000_000,
                })}
                canSize
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Check loan amount →' }),
        ).toBeEnabled();
    });

    it('renders parsing progress', () => {
        render(<BusinessPanel {...businessDefaults()} parsing />);

        expect(screen.getByText('46%')).toBeInTheDocument();
        expect(screen.getByText('CHECKING YOUR FIGURES')).toBeInTheDocument();
    });

    it('renders and operates a qualified result below the cap', async () => {
        const user = userEvent.setup();
        const props = businessDefaults();

        render(<BusinessPanel {...props} result />);

        expect(screen.getByText('PRE-QUALIFIED')).toBeInTheDocument();
        expect(screen.queryByText(/largest loan/)).not.toBeInTheDocument();
        expect(screen.getByText('✓ 1.50×')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: '3mo' }));
        await user.click(
            screen.getByRole('button', { name: 'Save your spot →' }),
        );

        expect(props.onTermChange).toHaveBeenCalledWith(0);
        expect(props.onSaveSpot).toHaveBeenCalledOnce();
    });

    it('renders a capped result and an uncovered ratio marker branch', () => {
        render(
            <BusinessPanel
                {...businessDefaults()}
                result
                sizing={sizing({ at_maximum: true, cover_ratio: 0.75 })}
            />,
        );

        expect(screen.getByText(/largest loan on Rozine/)).toBeInTheDocument();
        expect(screen.getByText('0.75×')).toBeInTheDocument();
    });

    it('explains and operates the below-minimum waitlist result', async () => {
        const user = userEvent.setup();
        const props = businessDefaults();

        render(
            <BusinessPanel
                {...props}
                result
                sizing={sizing({
                    below_minimum: true,
                    sized_amount: 2_000_000,
                    status: 'waitlisted',
                })}
            />,
        );

        expect(screen.getByText('NOT YET')).toBeInTheDocument();
        expect(screen.getByText(/Try a longer term/)).toBeInTheDocument();
        expect(screen.queryByText('MONTHLY')).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', {
                name: 'Join the launch waitlist →',
            }),
        );
        expect(props.onSaveSpot).toHaveBeenCalledOnce();
    });

    it('renders compact and regular rating pills', () => {
        const rating = sizing().rating;
        const { rerender } = render(
            <BusinessPanel {...businessDefaults()} result />,
        );

        expect(screen.getByText(rating.band)).toBeInTheDocument();

        rerender(
            <BusinessPanel
                {...businessDefaults()}
                result
                sizing={sizing({ rating })}
            />,
        );
        expect(screen.getByText(rating.score.toFixed(1))).toBeInTheDocument();
    });
});

describe('InvestorPanel', () => {
    it('toggles the repayment example, lists a note, and saves a spot', async () => {
        const user = userEvent.setup();
        const props = investorDefaults();

        render(<InvestorPanel {...props} exampleOpen />);

        expect(screen.getByText(/A business borrows/)).toBeInTheDocument();
        expect(screen.getByText('GreenLeaf Agro')).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Hide the example' }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Save your spot →' }),
        );
        expect(props.onExampleToggle).toHaveBeenCalledOnce();
        expect(props.onSaveSpot).toHaveBeenCalledOnce();
    });

    it('shows empty and null-average states', () => {
        render(
            <InvestorPanel
                {...investorDefaults()}
                averageYield={null}
                averageTerm={null}
                notes={[]}
                payout={null}
                canSave={false}
            />,
        );

        expect(
            screen.getByRole('button', {
                name: 'See how a repayment works →',
            }),
        ).toHaveAttribute('aria-expanded', 'false');
        expect(
            screen.getByText(/No businesses have pre-qualified/),
        ).toBeVisible();
        expect(screen.getAllByText('—')).toHaveLength(2);
        expect(screen.getByText('Calculating on the server…')).toBeVisible();
        expect(
            screen.getByRole('button', { name: 'Save your spot →' }),
        ).toBeDisabled();
    });

    it('accepts typed and ranged pledges, including empty input', () => {
        const props = investorDefaults();

        render(<InvestorPanel {...props} />);

        const amount = screen.getByLabelText('Pledge amount');
        const slider = screen.getByLabelText('Adjust pledge amount');
        const select = vi.spyOn(amount as HTMLInputElement, 'select');

        fireEvent.focus(amount);
        fireEvent.change(amount, { target: { value: 'RWF 1,234,567' } });
        fireEvent.change(amount, { target: { value: '' } });
        fireEvent.blur(amount);
        fireEvent.change(slider, { target: { value: '25000' } });
        Object.defineProperty(slider, 'value', {
            configurable: true,
            get: () => '',
        });
        fireEvent.change(slider);

        expect(select).toHaveBeenCalledOnce();
        expect(props.onPledgeChange).toHaveBeenCalledWith(1_234_567);
        expect(props.onPledgeChange).toHaveBeenCalledWith(0);
        expect(props.onPledgeChange).toHaveBeenCalledWith(25_000);
        expect(props.onPledgeChange).toHaveBeenCalledWith(0);
    });

    it('does not normalize a typed pledge in the browser on blur', () => {
        const props = investorDefaults();

        render(<InvestorPanel {...props} pledge={12_501} />);
        fireEvent.blur(screen.getByLabelText('Pledge amount'));

        expect(props.onPledgeChange).not.toHaveBeenCalled();
    });

    it('passes an oversized typed pledge to server preview validation', () => {
        const props = investorDefaults();

        render(<InvestorPanel {...props} />);
        fireEvent.change(screen.getByLabelText('Pledge amount'), {
            target: { value: '999999999' },
        });

        expect(props.onPledgeChange).toHaveBeenCalledWith(999_999_999);
    });
});
