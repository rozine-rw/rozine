import { Head, router, useHttp, usePoll } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
    storeBusiness,
    storeInvestor,
} from '@/actions/App/Http/Controllers/PulseController';
import { BusinessModal } from '@/components/pulse/business-modal';
import { BusinessPanel } from '@/components/pulse/business-panel';
import type { BusinessFigures } from '@/components/pulse/business-panel';
import { InvestorModal } from '@/components/pulse/investor-modal';
import { InvestorPanel } from '@/components/pulse/investor-panel';
import {
    PulseBackdrop,
    PulseBriefs,
    PulseDisclaimer,
    PulseFooter,
    PulseTopbar,
} from '@/components/pulse/pulse-chrome';
import { PulseHero, TractionCards } from '@/components/pulse/pulse-hero';
import { PulseToast } from '@/components/pulse/pulse-toast';
import type { SignupDetails } from '@/components/pulse/signup-fields';
import {
    BLENDED_YIELD,
    businessNotes,
    isValidContact,
    isValidName,
    MINIMUM_REVENUE,
    sizingFor,
    TERMS,
} from '@/lib/pulse';
import type { ContactMethod, Listing, Traction } from '@/lib/pulse';

type PulseProps = {
    traction: Traction;
    listings: Listing[];
    districts: Record<string, string[]>;
};

type SignupResponse = {
    queue_number: string;
    loan_number?: string;
    traction: Traction;
};

type SignupPayload = {
    name: string;
    contact: string;
    contact_method: ContactMethod;
    province: string;
    district: string;
};

type InvestorPayload = SignupPayload & {
    pledge_amount: number;
};

type BusinessPayload = SignupPayload & {
    annual_revenue: number;
    annual_costs: number;
    sector: string;
    registered_year: number;
    term_months: number;
    listed: boolean;
};

const EMPTY_DETAILS: SignupDetails = {
    name: '',
    contact: '',
    contactMethod: 'phone',
    province: '',
    district: '',
};

const EMPTY_PAYLOAD: SignupPayload = {
    name: '',
    contact: '',
    contact_method: 'phone',
    province: '',
    district: '',
};

const EMPTY_FIGURES: BusinessFigures = {
    name: '',
    annualRevenue: 0,
    annualCosts: 0,
    sector: '',
    registeredYear: '',
};

/** The stages the sizing counts through before it reports a figure. */
const SIZING_STEPS = [24, 46, 68, 88, 100];

export default function Pulse({ traction, listings, districts }: PulseProps) {
    const [toastMessage, setToastMessage] = useState('');
    const toastTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const sizingTimer = useRef<ReturnType<typeof setInterval> | null>(null);
    const resultTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const topbar = useRef<HTMLDivElement>(null);
    const investorPanel = useRef<HTMLDivElement>(null);
    const businessPanel = useRef<HTMLDivElement>(null);

    // Investor flow.
    const [pledge, setPledge] = useState(500000);
    const [investorOpen, setInvestorOpen] = useState(false);
    const [investorStep, setInvestorStep] = useState<'notes' | 'pledged'>(
        'notes',
    );
    const [investorDetails, setInvestorDetails] =
        useState<SignupDetails>(EMPTY_DETAILS);
    const [investorQueue, setInvestorQueue] = useState('#0142');
    const [exampleOpen, setExampleOpen] = useState(false);

    // Business flow.
    const [businessOpen, setBusinessOpen] = useState(false);
    const [businessStep, setBusinessStep] = useState<
        'idle' | 'parsing' | 'result' | 'pass'
    >('idle');
    const [figures, setFigures] = useState<BusinessFigures>(EMPTY_FIGURES);
    const [businessDetails, setBusinessDetails] =
        useState<SignupDetails>(EMPTY_DETAILS);
    const [businessQueue, setBusinessQueue] = useState('#0142');
    const [loanNumber, setLoanNumber] = useState('#1,480');
    const [termIndex, setTermIndex] = useState(3);
    const [progress, setProgress] = useState(0);
    const [businessListed, setBusinessListed] = useState(false);

    const investorSignup = useHttp<InvestorPayload, SignupResponse>({
        ...EMPTY_PAYLOAD,
        pledge_amount: 0,
    });
    const businessSignup = useHttp<BusinessPayload, SignupResponse>({
        ...EMPTY_PAYLOAD,
        annual_revenue: 0,
        annual_costs: 0,
        sector: '',
        registered_year: 0,
        term_months: 12,
        listed: false,
    });

    // The figures on the page are whatever the database holds, refreshed in
    // place so a signup made elsewhere shows up without a reload.
    usePoll(10000, { only: ['traction', 'listings'], async: true });

    useEffect(() => {
        const timers = [toastTimer, sizingTimer, resultTimer];

        return () => {
            timers.forEach((timer) => {
                if (timer.current) {
                    clearTimeout(
                        timer.current as ReturnType<typeof setTimeout>,
                    );
                    clearInterval(
                        timer.current as ReturnType<typeof setInterval>,
                    );
                }
            });
        };
    }, []);

    const notes = useMemo(
        () => businessNotes(listings, pledge),
        [listings, pledge],
    );

    const term = TERMS[termIndex];
    const sizing = useMemo(
        () =>
            sizingFor(
                figures.annualRevenue,
                figures.annualCosts,
                figures.sector,
                parseInt(figures.registeredYear, 10) || 0,
                term,
            ),
        [figures, term],
    );

    const payout = pledge * (1 + BLENDED_YIELD / 100);

    const canSize =
        isValidName(figures.name) &&
        figures.annualRevenue >= MINIMUM_REVENUE &&
        figures.annualCosts > 0 &&
        figures.annualCosts < figures.annualRevenue &&
        figures.sector !== '' &&
        figures.registeredYear !== '';

    const progressLabel =
        progress < 50
            ? 'CHECKING YOUR FIGURES'
            : progress < 100
              ? 'MODELLING CASH FLOW'
              : 'SIZING CAPACITY';

    function toast(message: string): void {
        setToastMessage(message);

        if (toastTimer.current) {
            clearTimeout(toastTimer.current);
        }

        toastTimer.current = setTimeout(() => setToastMessage(''), 2500);
    }

    function reportErrors(errors: Record<string, string | string[]>): void {
        const [first] = Object.values(errors).flat();

        toast(first ?? 'Something went wrong. Please try again.');
    }

    function scrollTo(element: HTMLDivElement | null): void {
        if (!element) {
            return;
        }

        // Clear the sticky masthead the panel would otherwise land under.
        const bar = topbar.current?.getBoundingClientRect().height ?? 0;

        window.scrollTo({
            top: Math.max(
                0,
                element.getBoundingClientRect().top + window.scrollY - bar - 18,
            ),
            behavior: 'smooth',
        });
    }

    function isComplete(details: SignupDetails): boolean {
        return (
            isValidName(details.name) &&
            isValidContact(details.contact, details.contactMethod) &&
            details.province !== '' &&
            details.district !== ''
        );
    }

    function payloadFor(details: SignupDetails): SignupPayload {
        return {
            name: details.name,
            contact: details.contact,
            contact_method: details.contactMethod,
            province: details.province,
            district: details.district,
        };
    }

    /**
     * Count the sizing through its stages. The figures are the visitor's own,
     * so nothing is fetched — the pause is the model being read back to them.
     */
    function startSizing(): void {
        if (!canSize) {
            return;
        }

        clearSizingTimers();
        setBusinessOpen(false);
        setBusinessStep('parsing');
        setProgress(8);

        let step = 0;

        sizingTimer.current = setInterval(() => {
            setProgress(SIZING_STEPS[step]);

            if (step >= SIZING_STEPS.length - 1) {
                clearSizingTimers();
                resultTimer.current = setTimeout(
                    () => setBusinessStep('result'),
                    360,
                );
            }

            step++;
        }, 320);
    }

    function clearSizingTimers(): void {
        if (sizingTimer.current) {
            clearInterval(sizingTimer.current);
            sizingTimer.current = null;
        }
    }

    function submitInvestor(): void {
        investorSignup.setData({
            ...payloadFor(investorDetails),
            pledge_amount: pledge,
        });
        investorSignup.post(storeInvestor.url(), {
            onSuccess: (response) => {
                setInvestorQueue(response.queue_number);
                setInvestorStep('pledged');
                router.reload({ only: ['traction', 'listings'], async: true });
            },
            onError: reportErrors,
            onNetworkError: () =>
                toast('We could not reach the server. Please try again.'),
        });
    }

    function submitBusiness(): void {
        businessSignup.setData({
            ...payloadFor(businessDetails),
            annual_revenue: figures.annualRevenue,
            annual_costs: figures.annualCosts,
            sector: figures.sector,
            registered_year: parseInt(figures.registeredYear, 10),
            term_months: term,
            listed: businessListed,
        });
        businessSignup.post(storeBusiness.url(), {
            onSuccess: (response) => {
                setBusinessQueue(response.queue_number);
                setLoanNumber(response.loan_number ?? loanNumber);
                setBusinessStep('pass');
                router.reload({ only: ['traction', 'listings'], async: true });
            },
            onError: reportErrors,
            onNetworkError: () =>
                toast('We could not reach the server. Please try again.'),
        });
    }

    return (
        <div className="rz-pulse relative min-h-screen">
            <Head title="Pulse" />

            <PulseBackdrop />
            <PulseTopbar barRef={topbar} />

            <div className="relative z-[2] mx-auto max-w-[1120px] px-2 md:px-[26px]">
                <TractionCards
                    investors={traction.investors}
                    pledged={traction.pledged}
                    averageLoan={traction.average_loan}
                />

                <PulseHero
                    onInvest={() => scrollTo(investorPanel.current)}
                    onBorrow={() => scrollTo(businessPanel.current)}
                />

                <div className="mt-1.5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InvestorPanel
                        panelRef={investorPanel}
                        pledged={traction.pledged}
                        investors={traction.investors}
                        averageYield={traction.average_yield}
                        averageTerm={traction.average_term}
                        pledge={pledge}
                        payout={payout}
                        notes={notes}
                        exampleOpen={exampleOpen}
                        onExampleToggle={() => setExampleOpen((open) => !open)}
                        onPledgeChange={setPledge}
                        onSaveSpot={() => {
                            setInvestorStep('notes');
                            setInvestorOpen(true);
                        }}
                    />

                    <BusinessPanel
                        panelRef={businessPanel}
                        businesses={traction.businesses}
                        averageLoan={traction.average_loan}
                        averageRating={traction.average_rating}
                        idle={
                            !(
                                businessStep === 'parsing' ||
                                businessStep === 'result'
                            ) || businessOpen
                        }
                        parsing={businessStep === 'parsing' && !businessOpen}
                        result={businessStep === 'result' && !businessOpen}
                        progress={progress}
                        progressLabel={progressLabel}
                        figures={figures}
                        sizing={sizing}
                        termIndex={termIndex}
                        canSize={canSize}
                        onFiguresChange={(changes) =>
                            setFigures((current) => ({
                                ...current,
                                ...changes,
                            }))
                        }
                        onSize={startSizing}
                        onTermChange={setTermIndex}
                        onSaveSpot={() => {
                            setBusinessDetails((current) => ({
                                ...current,
                                name: current.name || figures.name,
                            }));
                            setBusinessOpen(true);
                        }}
                    />
                </div>

                <PulseBriefs />
                <PulseDisclaimer />
                <PulseFooter />
            </div>

            {businessOpen && (
                <BusinessModal
                    step={businessStep === 'idle' ? 'result' : businessStep}
                    progress={progress}
                    progressLabel={progressLabel}
                    qualifiedAmount={sizing.qualifiedAmount}
                    belowMinimum={sizing.belowMinimum}
                    rating={sizing.rating}
                    termLabel={`${term}mo`}
                    flatRate={`${sizing.flatRate.toFixed(1)}%`}
                    queueNumber={businessQueue}
                    loanNumber={loanNumber}
                    districts={districts}
                    details={businessDetails}
                    listed={businessListed}
                    contactError={businessSignup.errors.contact}
                    canSubmit={isComplete(businessDetails) && canSize}
                    processing={businessSignup.processing}
                    onListedChange={setBusinessListed}
                    onChange={(changes) => {
                        if (
                            'contact' in changes ||
                            'contactMethod' in changes
                        ) {
                            businessSignup.clearErrors('contact');
                        }

                        setBusinessDetails((current) => ({
                            ...current,
                            ...changes,
                        }));
                    }}
                    onSubmit={submitBusiness}
                    onShare={toast}
                    onClose={() => setBusinessOpen(false)}
                />
            )}

            {investorOpen && (
                <InvestorModal
                    step={investorStep}
                    pledge={pledge}
                    payout={payout}
                    queueNumber={investorQueue}
                    districts={districts}
                    details={investorDetails}
                    contactError={investorSignup.errors.contact}
                    canSubmit={isComplete(investorDetails)}
                    processing={investorSignup.processing}
                    onChange={(changes) => {
                        if (
                            'contact' in changes ||
                            'contactMethod' in changes
                        ) {
                            investorSignup.clearErrors('contact');
                        }

                        setInvestorDetails((current) => ({
                            ...current,
                            ...changes,
                        }));
                    }}
                    onSubmit={submitInvestor}
                    onShare={toast}
                    onClose={() => setInvestorOpen(false)}
                />
            )}

            {toastMessage !== '' && <PulseToast message={toastMessage} />}
        </div>
    );
}
