import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { BusinessModal } from '@/components/pulse/business-modal';
import { InvestorModal } from '@/components/pulse/investor-modal';
import type { SignupDetails } from '@/components/pulse/signup-fields';
import type { Rating } from '@/lib/pulse';

const rating: Rating = {
    band: 'Strong',
    score: '4.2',
    color: { light: 'green', dark: 'lightgreen' },
    background: { light: 'palegreen', dark: 'darkgreen' },
};

const details = (changes: Partial<SignupDetails> = {}): SignupDetails => ({
    name: 'GreenLeaf Agro',
    contact: '+250 788 123 456',
    contactMethod: 'phone',
    province: 'Kigali',
    district: 'Gasabo',
    ...changes,
});

const districts = { Kigali: ['Gasabo', 'Kicukiro'] };

const businessDefaults = () => ({
    step: 'result' as const,
    progress: 68,
    progressLabel: 'MODELLING CASH FLOW',
    qualifiedAmount: 10_000_000,
    belowMinimum: false,
    rating,
    termLabel: '12mo',
    flatRate: '13.0%',
    queueNumber: '#0143',
    loanNumber: '#1,481',
    districts,
    details: details(),
    anonymous: false,
    canSubmit: true,
    processing: false,
    onAnonymousChange: vi.fn(),
    onChange: vi.fn(),
    onSubmit: vi.fn(),
    onShare: vi.fn(),
    onClose: vi.fn(),
});

const investorDefaults = () => ({
    step: 'notes' as const,
    pledge: 500_000,
    payout: 565_000,
    blendedYield: 13,
    queueNumber: '#0144',
    districts,
    details: details({ name: 'Diane Uwase' }),
    canSubmit: true,
    processing: false,
    onChange: vi.fn(),
    onSubmit: vi.fn(),
    onShare: vi.fn(),
    onClose: vi.fn(),
});

describe('BusinessModal', () => {
    it('shows sizing progress', () => {
        render(<BusinessModal {...businessDefaults()} step="parsing" />);

        expect(screen.getByText('68%')).toBeInTheDocument();
        expect(screen.getByText('MODELLING CASH FLOW')).toBeInTheDocument();
    });

    it('collects result details, toggles listing privacy, and submits', async () => {
        const user = userEvent.setup();
        const props = businessDefaults();

        render(<BusinessModal {...props} contactError="Use a valid contact" />);

        expect(screen.getByText('Reserve your spot')).toBeInTheDocument();
        expect(screen.getByText('Strong')).toBeInTheDocument();
        await user.click(screen.getByRole('checkbox'));
        await user.click(
            screen.getByRole('button', { name: 'Reserve my spot →' }),
        );

        expect(props.onAnonymousChange).toHaveBeenCalledWith(true);
        expect(props.onSubmit).toHaveBeenCalledOnce();
    });

    it('renders a named qualified pass and operates share and done actions', async () => {
        const user = userEvent.setup();
        const props = businessDefaults();

        render(<BusinessModal {...props} step="pass" />);

        expect(screen.getByText('PRE-QUALIFIED LOAN')).toBeInTheDocument();
        expect(screen.getAllByText('RWF 10M').length).toBeGreaterThan(0);
        expect(screen.getByText(/GreenLeaf Agro/)).toBeInTheDocument();
        await user.click(screen.getByTitle('WhatsApp'));
        await user.click(screen.getByRole('button', { name: 'Done' }));

        expect(props.onShare).toHaveBeenCalledWith('Shared to WhatsApp');
        expect(props.onClose).toHaveBeenCalledOnce();
    });

    it('renders the anonymous below-minimum waitlist fallbacks', () => {
        render(
            <BusinessModal
                {...businessDefaults()}
                step="pass"
                belowMinimum
                details={details({ name: ' ', district: '' })}
            />,
        );

        expect(screen.getByText('EARLY ACCESS')).toBeInTheDocument();
        expect(screen.getByText('WAITLIST')).toBeInTheDocument();
        expect(screen.getByText(/Pre-qualified business/)).toHaveTextContent(
            'Pre-qualified business · Gasabo',
        );
    });
});

describe('InvestorModal', () => {
    it('confirms an enabled pledge and relays field changes', async () => {
        const user = userEvent.setup();
        const props = investorDefaults();

        render(<InvestorModal {...props} contactError="Invalid contact" />);

        expect(screen.getByText('Confirm your pledge')).toBeInTheDocument();
        expect(screen.getByText('RWF 565K')).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', {
                name: 'Confirm RWF 500K pledge →',
            }),
        );
        expect(props.onSubmit).toHaveBeenCalledOnce();
    });

    it('renders a named pledged pass and operates share and close', async () => {
        const user = userEvent.setup();
        const props = investorDefaults();

        render(<InvestorModal {...props} step="pledged" />);

        expect(screen.getByText('PLEDGING INVESTOR')).toBeInTheDocument();
        expect(screen.getByText('Diane Uwase')).toBeInTheDocument();
        await user.click(screen.getByTitle('X'));
        await user.click(screen.getByRole('button', { name: 'Done' }));

        expect(props.onShare).toHaveBeenCalledWith('Ready to post on X');
        expect(props.onClose).toHaveBeenCalledOnce();
    });

    it('renders the fallback holder for an unnamed investor', () => {
        render(
            <InvestorModal
                {...investorDefaults()}
                step="pledged"
                details={details({ name: ' ' })}
            />,
        );

        expect(screen.getByText('Pledging investor')).toBeInTheDocument();
    });
});
