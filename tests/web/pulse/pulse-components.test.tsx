import { fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createRef } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    AuditIcon,
    CheckIcon,
    DownloadIcon,
    GlobeIcon,
    InstagramIcon,
    LinkedInIcon,
    MailIcon,
    MoonIcon,
    PhoneIcon,
    ShieldIcon,
    SunIcon,
    WarningIcon,
    WhatsAppIcon,
    XIcon,
} from '@/components/pulse/icons';
import { ListingConsent } from '@/components/pulse/listing-consent';
import {
    PanelCriteria,
    PanelHeading,
    PanelLabel,
    PulsePanel,
} from '@/components/pulse/panel';
import {
    PassActions,
    PassAmount,
    PassCard,
    PassFooter,
    PassHolder,
    PassStat,
} from '@/components/pulse/pass-card';
import {
    PulseBackdrop,
    PulseBriefs,
    PulseDisclaimer,
    PulseFooter,
    PulseTopbar,
} from '@/components/pulse/pulse-chrome';
import { PulseHero, TractionCards } from '@/components/pulse/pulse-hero';
import {
    ModalHeading,
    ModalSubmit,
    PulseModal,
} from '@/components/pulse/pulse-modal';
import { PulseToast } from '@/components/pulse/pulse-toast';
import { PulseWordmark } from '@/components/pulse/pulse-wordmark';
import { SignupFields } from '@/components/pulse/signup-fields';
import type { SignupDetails } from '@/components/pulse/signup-fields';
import { StatCurrency, StatTile } from '@/components/pulse/stat-tile';

const mocks = vi.hoisted(() => ({
    appearance: 'light' as 'light' | 'dark',
    download: vi.fn<() => Promise<void>>(),
    updateAppearance: vi.fn(),
}));

vi.mock('@/hooks/use-appearance', () => ({
    useAppearance: () => ({
        resolvedAppearance: mocks.appearance,
        updateAppearance: mocks.updateAppearance,
    }),
}));

vi.mock('@/lib/pass-card-image', () => ({
    downloadPassCard: mocks.download,
}));

const details = (changes: Partial<SignupDetails> = {}): SignupDetails => ({
    name: 'Diane Uwase',
    contact: '+250 788 123 456',
    contactMethod: 'phone',
    province: 'Kigali',
    district: 'Gasabo',
    ...changes,
});

afterEach(() => {
    mocks.appearance = 'light';
    mocks.download.mockReset();
    mocks.updateAppearance.mockReset();
});

describe('Pulse primitive components', () => {
    it('renders every inline icon, including default and custom sizes', () => {
        render(
            <>
                <span data-testid="pulse-icon">
                    <PhoneIcon />
                </span>
                <span data-testid="pulse-icon">
                    <MailIcon />
                </span>
                <span data-testid="pulse-icon">
                    <MailIcon size={20} />
                </span>
                <span data-testid="pulse-icon">
                    <AuditIcon />
                </span>
                <span data-testid="pulse-icon">
                    <ShieldIcon />
                </span>
                <span data-testid="pulse-icon">
                    <WarningIcon />
                </span>
                <span data-testid="pulse-icon">
                    <CheckIcon />
                </span>
                <span data-testid="pulse-icon">
                    <SunIcon />
                </span>
                <span data-testid="pulse-icon">
                    <MoonIcon />
                </span>
                <span data-testid="pulse-icon">
                    <LinkedInIcon />
                </span>
                <span data-testid="pulse-icon">
                    <GlobeIcon className="globe" />
                </span>
                <span data-testid="pulse-icon">
                    <WhatsAppIcon />
                </span>
                <span data-testid="pulse-icon">
                    <XIcon />
                </span>
                <span data-testid="pulse-icon">
                    <InstagramIcon />
                </span>
                <span data-testid="pulse-icon">
                    <DownloadIcon />
                </span>
                <span data-testid="pulse-icon">
                    <DownloadIcon size={22} />
                </span>
            </>,
        );

        expect(screen.getAllByTestId('pulse-icon')).toHaveLength(16);
    });

    it('renders both panel tones, criteria, labels, and headings', () => {
        const investorRef = createRef<HTMLDivElement>();
        const businessRef = createRef<HTMLDivElement>();

        render(
            <>
                <PulsePanel tone="investor" panelRef={investorRef}>
                    <PanelLabel color="blue">Investor</PanelLabel>
                    <PanelHeading>Invest</PanelHeading>
                    <PanelCriteria tone="investor" items={['One', 'Two']} />
                </PulsePanel>
                <PulsePanel tone="business" panelRef={businessRef}>
                    <PanelCriteria tone="business" items={['Three']} />
                </PulsePanel>
            </>,
        );

        expect(screen.getByText('Investor')).toHaveStyle({
            color: 'rgb(0, 0, 255)',
        });
        expect(screen.getByText('Invest')).toBeInTheDocument();
        expect(screen.getByText('Three')).toBeInTheDocument();
        expect(investorRef.current).toBeInTheDocument();
        expect(businessRef.current).toBeInTheDocument();
    });

    it('renders stat variants, currency, toast, and both wordmarks', () => {
        render(
            <>
                <StatTile label="Plain" color="red">
                    <StatCurrency />
                    1M
                </StatTile>
                <StatTile label="Average" color="blue" muted suffix="6MO">
                    13%
                </StatTile>
                <PulseToast message="Saved" />
                <PulseWordmark className="brand" />
            </>,
        );

        expect(screen.getByText('RWF')).toBeInTheDocument();
        expect(screen.getByText('6MO')).toBeInTheDocument();
        expect(screen.getByText('Saved')).toBeInTheDocument();
        expect(screen.getAllByAltText('rozine')).toHaveLength(2);
    });
});

describe('Pulse navigation chrome', () => {
    it('shows both traction-card currency branches and intent actions', async () => {
        const user = userEvent.setup();
        const onInvest = vi.fn();
        const onBorrow = vi.fn();
        const { rerender } = render(
            <>
                <TractionCards
                    investors={12}
                    pledged={500_000}
                    averageLoan={null}
                />
                <PulseHero onInvest={onInvest} onBorrow={onBorrow} />
            </>,
        );

        expect(screen.getByText('—')).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: /I want to invest/i }),
        );
        await user.click(
            screen.getByRole('button', { name: /I want a business loan/i }),
        );

        expect(onInvest).toHaveBeenCalledOnce();
        expect(onBorrow).toHaveBeenCalledOnce();

        rerender(
            <TractionCards
                investors={1}
                pledged={5_000}
                averageLoan={9_000_000}
            />,
        );

        expect(screen.getByText('RWF 9,000,000')).toBeInTheDocument();
    });

    it('renders downloads, disclaimer, footer, backdrop, and both theme states', async () => {
        const user = userEvent.setup();
        const barRef = createRef<HTMLDivElement>();
        const { rerender } = render(
            <>
                <PulseBackdrop />
                <PulseTopbar barRef={barRef} />
                <PulseBriefs />
                <PulseDisclaimer />
                <PulseFooter />
            </>,
        );

        expect(barRef.current).toBeInTheDocument();
        expect(
            screen.getByText('Non-binding demand simulation.'),
        ).toBeVisible();
        expect(screen.getByTitle('Email')).toHaveAttribute(
            'href',
            'mailto:hello@rozine.rw',
        );
        expect(screen.getByTitle('LinkedIn')).toHaveAttribute(
            'href',
            'https://www.linkedin.com/company/rozine',
        );
        expect(
            screen.getByRole('link', { name: /Investor brief/i }),
        ).toHaveAttribute('download');

        await user.click(
            screen.getByRole('button', { name: 'Switch to dark' }),
        );
        expect(mocks.updateAppearance).toHaveBeenCalledWith('dark');

        mocks.appearance = 'dark';
        rerender(<PulseTopbar />);
        await user.click(
            screen.getByRole('button', { name: 'Switch to light' }),
        );
        expect(mocks.updateAppearance).toHaveBeenCalledWith('light');
    });
});

describe('Pulse signup controls', () => {
    it('toggles anonymous listing semantics with and without a district', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        const { rerender } = render(
            <ListingConsent
                anonymous={false}
                district=""
                onChange={onChange}
            />,
        );

        const checkbox = screen.getByRole('checkbox');

        expect(checkbox).not.toBeChecked();
        expect(screen.getByText(/Business in/)).toHaveTextContent(
            'Business in your district',
        );
        await user.click(checkbox);
        expect(onChange).toHaveBeenCalledWith(true);

        rerender(
            <ListingConsent anonymous district="Gasabo" onChange={onChange} />,
        );
        expect(screen.getByRole('checkbox')).toBeChecked();
        expect(screen.getByText(/Business in/)).toHaveTextContent(
            'Business in Gasabo',
        );
        await user.click(screen.getByRole('checkbox'));
        expect(onChange).toHaveBeenLastCalledWith(false);
    });

    it('reports every phone signup field interaction and its error state', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();

        render(
            <SignupFields
                accent="investor"
                nameLabel="Your name"
                namePlaceholder="Name"
                districts={{ Kigali: ['Gasabo', 'Kicukiro'] }}
                details={details()}
                contactError="Use a valid phone"
                onChange={onChange}
            >
                <span>Consent slot</span>
            </SignupFields>,
        );

        await user.type(screen.getByPlaceholderText('Name'), ' A');
        await user.selectOptions(screen.getByLabelText('Province'), 'Kigali');
        await user.selectOptions(screen.getByLabelText('District'), 'Kicukiro');
        await user.click(screen.getByRole('button', { name: /Phone/i }));
        await user.click(screen.getByRole('button', { name: /Email/i }));
        await user.type(screen.getByPlaceholderText('07xx xxx xxx'), '7');

        expect(onChange).toHaveBeenCalledWith({ name: 'Diane Uwase ' });
        expect(onChange).toHaveBeenCalledWith({ name: 'Diane UwaseA' });
        expect(onChange).toHaveBeenCalledWith({
            province: 'Kigali',
            district: '',
        });
        expect(onChange).toHaveBeenCalledWith({ district: 'Kicukiro' });
        expect(onChange).toHaveBeenCalledWith({
            contactMethod: 'phone',
            contact: '',
        });
        expect(onChange).toHaveBeenCalledWith({
            contactMethod: 'email',
            contact: '',
        });
        expect(screen.getByText('Use a valid phone')).toHaveAttribute(
            'id',
            'rz-contact-error',
        );
        expect(screen.getByText('Consent slot')).toBeInTheDocument();
    });

    it('renders email details without an error or known province', () => {
        render(
            <SignupFields
                accent="business"
                nameLabel="Business"
                namePlaceholder="Business name"
                districts={{ Kigali: ['Gasabo'] }}
                details={details({
                    contactMethod: 'email',
                    contact: 'owner@example.com',
                    province: 'Unknown',
                    district: '',
                })}
                onChange={vi.fn()}
            />,
        );

        const contact = screen.getByPlaceholderText('you@email.com');

        expect(contact).not.toHaveAttribute('aria-invalid');
        expect(screen.getByLabelText('District')).toHaveDisplayValue(
            'District',
        );
    });
});

describe('Pulse modal and pass interactions', () => {
    it('closes from the backdrop and button while dialog clicks stay contained', async () => {
        const user = userEvent.setup();
        const onClose = vi.fn();

        render(
            <PulseModal label="Flow" onClose={onClose}>
                <ModalHeading title="Title" subtitle="Subtitle" />
            </PulseModal>,
        );

        fireEvent.click(screen.getByRole('dialog'));
        expect(onClose).not.toHaveBeenCalled();

        fireEvent.click(screen.getByTestId('pulse-modal-backdrop'));
        await user.click(screen.getByRole('button', { name: 'Close' }));
        expect(onClose).toHaveBeenCalledTimes(2);
    });

    it('enforces enabled and processing submit states', async () => {
        const user = userEvent.setup();
        const onClick = vi.fn();
        const { rerender } = render(
            <ModalSubmit
                accent="blue"
                enabled={false}
                processing={false}
                onClick={onClick}
            >
                Submit
            </ModalSubmit>,
        );

        expect(screen.getByRole('button')).toBeDisabled();

        rerender(
            <ModalSubmit accent="blue" enabled processing onClick={onClick}>
                Submit
            </ModalSubmit>,
        );
        expect(screen.getByRole('button')).toBeDisabled();

        rerender(
            <ModalSubmit
                accent="blue"
                enabled
                processing={false}
                onClick={onClick}
            >
                Submit
            </ModalSubmit>,
        );
        await user.click(screen.getByRole('button'));
        expect(onClick).toHaveBeenCalledOnce();
    });

    it('renders both pass tones, captions, footer, and no-caption amount', () => {
        render(
            <>
                <PassCard tone="investor" tag="INVESTOR" badge="#1">
                    <PassHolder>Ada</PassHolder>
                    <PassAmount caption="PLEDGED">RWF 5K</PassAmount>
                    <PassFooter>
                        <PassStat label="RETURN" value="13%" />
                    </PassFooter>
                </PassCard>
                <PassCard tone="business" tag="BUSINESS" badge="#2">
                    <PassAmount>WAITLIST</PassAmount>
                </PassCard>
            </>,
        );

        expect(screen.getByText('PLEDGED')).toBeInTheDocument();
        expect(screen.getByText('WAITLIST')).toBeInTheDocument();
        expect(screen.getAllByText('rozine.rw')).toHaveLength(1);
    });

    it('shares, downloads successfully, handles download errors, and finishes', async () => {
        const user = userEvent.setup();
        const onShare = vi.fn();
        const onDone = vi.fn();
        mocks.download
            .mockResolvedValueOnce(undefined)
            .mockRejectedValueOnce(new Error('canvas failed'));

        render(
            <PassActions
                card={{
                    tone: 'investor',
                    tag: 'INVESTOR',
                    badge: '#1',
                    holder: 'Ada',
                    amount: 'RWF 5K',
                    stats: [],
                }}
                onShare={onShare}
                onDone={onDone}
            />,
        );

        await user.click(screen.getByTitle('WhatsApp'));
        await user.click(screen.getByTitle('X'));
        await user.click(screen.getByTitle('Instagram'));
        await user.click(screen.getByTitle('Download'));
        await user.click(screen.getByTitle('Download'));
        await user.click(screen.getByRole('button', { name: 'Done' }));

        expect(onShare).toHaveBeenCalledWith('Shared to WhatsApp');
        expect(onShare).toHaveBeenCalledWith('Ready to post on X');
        expect(onShare).toHaveBeenCalledWith(
            'Card saved for your Instagram story',
        );
        expect(onShare).toHaveBeenCalledWith('Saving your card…');
        expect(onShare).toHaveBeenCalledWith('Your card could not be saved.');
        expect(onDone).toHaveBeenCalledOnce();
    });
});
