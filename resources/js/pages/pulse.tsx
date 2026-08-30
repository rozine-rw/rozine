import { Head, router, useHttp, usePoll } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    previewBusiness,
    previewInvestor,
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
    decorateListings,
    decorateRating,
    isValidContact,
    isValidName,
} from '@/lib/pulse';
import type {
    ContactMethod,
    InvestorPreview,
    PulsePolicy,
    Sizing,
    Traction,
} from '@/lib/pulse';

type PulseProps = {
    traction: Traction;
    districts: Record<string, string[]>;
    policy: PulsePolicy;
    investor_preview: InvestorPreview;
};

type InvestorSignupResponse = {
    queue_number: string;
    traction: Traction;
    investor_preview: InvestorPreview;
};

type BusinessSignupResponse = {
    queue_number: string;
    loan_number: string;
    traction: Traction;
    business_preview: Sizing;
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

type InvestorPreviewPayload = {
    pledge_amount: number;
};

type BusinessPreviewPayload = {
    annual_revenue: number;
    annual_costs: number;
    sector: string;
    registered_year: number;
    term_months: number;
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

export default function Pulse({
    traction,
    districts,
    policy,
    investor_preview: initialInvestorPreview,
}: PulseProps) {
    const [toastMessage, setToastMessage] = useState('');
    const toastTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const sizingTimer = useRef<ReturnType<typeof setInterval> | null>(null);
    const resultTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const investorPreviewTimer = useRef<ReturnType<typeof setTimeout> | null>(
        null,
    );
    const investorPreviewSequence = useRef(0);

    const topbar = useRef<HTMLDivElement>(null);
    const investorPanel = useRef<HTMLDivElement>(null);
    const businessPanel = useRef<HTMLDivElement>(null);

    // Investor flow.
    const [pledge, setPledge] = useState(policy.pledge.default);
    const [investorPreview, setInvestorPreview] =
        useState<InvestorPreview | null>(initialInvestorPreview);
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
    const [sizing, setSizing] = useState<Sizing | null>(null);
    // The checkbox asks to be hidden, so the listing consent is its inverse.
    const [businessAnonymous, setBusinessAnonymous] = useState(false);

    const investorSignup = useHttp<InvestorPayload, InvestorSignupResponse>({
        ...EMPTY_PAYLOAD,
        pledge_amount: 0,
    });
    const businessSignup = useHttp<BusinessPayload, BusinessSignupResponse>({
        ...EMPTY_PAYLOAD,
        annual_revenue: 0,
        annual_costs: 0,
        sector: '',
        registered_year: 0,
        term_months: 12,
        listed: false,
    });
    const investorPreviewRequest = useHttp<
        InvestorPreviewPayload,
        InvestorPreview
    >({
        pledge_amount: policy.pledge.default,
    });
    const businessPreviewRequest = useHttp<BusinessPreviewPayload, Sizing>({
        annual_revenue: 0,
        annual_costs: 0,
        sector: '',
        registered_year: 0,
        term_months: policy.terms[0],
    });
    const cancelInvestorPreview = investorPreviewRequest.cancel;
    const cancelBusinessPreview = businessPreviewRequest.cancel;

    const clearSizingTimers = useCallback((): void => {
        if (sizingTimer.current) {
            clearInterval(sizingTimer.current);
            sizingTimer.current = null;
        }

        if (resultTimer.current) {
            clearTimeout(resultTimer.current);
            resultTimer.current = null;
        }
    }, []);

    // The figures on the page are whatever the database holds, refreshed in
    // place so a signup made elsewhere shows up without a reload.
    usePoll(10000, { only: ['traction'], async: true });

    useEffect(() => {
        return () => {
            if (toastTimer.current) {
                clearTimeout(toastTimer.current);
            }

            clearSizingTimers();

            if (investorPreviewTimer.current) {
                clearTimeout(investorPreviewTimer.current);
            }

            cancelInvestorPreview();
            cancelBusinessPreview();
        };
    }, [cancelBusinessPreview, cancelInvestorPreview, clearSizingTimers]);

    const notes = useMemo(
        () => decorateListings(investorPreview?.listings ?? []),
        [investorPreview],
    );

    const term = policy.terms[termIndex];
    const canSize =
        figures.name.trim() !== '' &&
        figures.annualRevenue > 0 &&
        figures.annualCosts > 0 &&
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

    /** Request a non-persisting, server-authoritative sizing preview. */
    function startSizing(termMonths = term): void {
        if (!canSize) {
            return;
        }

        clearSizingTimers();
        businessPreviewRequest.cancel();
        setSizing(null);
        setBusinessOpen(false);
        setBusinessStep('parsing');
        setProgress(8);

        businessPreviewRequest.setData({
            annual_revenue: figures.annualRevenue,
            annual_costs: figures.annualCosts,
            sector: figures.sector,
            registered_year: parseInt(figures.registeredYear, 10),
            term_months: termMonths,
        });
        businessPreviewRequest.post(previewBusiness.url(), {
            onSuccess: (response) => {
                clearSizingTimers();
                setSizing(response);
                setProgress(100);
                resultTimer.current = setTimeout(
                    () => setBusinessStep('result'),
                    360,
                );
            },
            onError: (errors) => {
                clearSizingTimers();
                setBusinessStep('idle');
                reportErrors(errors);
            },
            onNetworkError: () => {
                clearSizingTimers();
                setBusinessStep('idle');
                toast('We could not reach the server. Please try again.');
            },
        });

        let step = 0;

        const activeSizingTimer = setInterval(() => {
            setProgress(SIZING_STEPS[step]);

            if (step >= SIZING_STEPS.length - 1) {
                clearInterval(activeSizingTimer);
                sizingTimer.current = null;
            }

            step++;
        }, 320);
        sizingTimer.current = activeSizingTimer;
    }

    /** Debounce previews and ignore any superseded response. */
    function changePledge(nextPledge: number): void {
        const sequence = ++investorPreviewSequence.current;

        setPledge(nextPledge);
        setInvestorPreview(null);
        investorPreviewRequest.cancel();

        if (investorPreviewTimer.current) {
            clearTimeout(investorPreviewTimer.current);
        }

        investorPreviewTimer.current = setTimeout(() => {
            investorPreviewRequest.setData({ pledge_amount: nextPledge });
            investorPreviewRequest.post(previewInvestor.url(), {
                onSuccess: (response) => {
                    if (investorPreviewSequence.current === sequence) {
                        setPledge(response.pledge_amount);
                        setInvestorPreview(response);
                    }
                },
                onError: (errors) => {
                    if (investorPreviewSequence.current === sequence) {
                        reportErrors(errors);
                    }
                },
                onNetworkError: () => {
                    if (investorPreviewSequence.current === sequence) {
                        toast(
                            'We could not refresh the projection. Please try again.',
                        );
                    }
                },
            });
        }, 250);
    }

    function refreshInvestorPreview(): void {
        changePledge(pledge);
    }

    function submitInvestor(): void {
        investorSignup.setData({
            ...payloadFor(investorDetails),
            pledge_amount: pledge,
        });
        investorSignup.post(storeInvestor.url(), {
            onSuccess: (response) => {
                setInvestorQueue(response.queue_number);
                setPledge(response.investor_preview.pledge_amount);
                setInvestorPreview(response.investor_preview);
                setInvestorStep('pledged');
                router.reload({ only: ['traction'], async: true });
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
            listed: !businessAnonymous,
        });
        businessSignup.post(storeBusiness.url(), {
            onSuccess: (response) => {
                setBusinessQueue(response.queue_number);
                setLoanNumber(response.loan_number);
                setSizing(response.business_preview);
                setBusinessStep('pass');
                refreshInvestorPreview();
                router.reload({ only: ['traction'], async: true });
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
                        payout={investorPreview?.projected_return ?? null}
                        notes={notes}
                        policy={policy}
                        canSave={
                            investorPreview?.pledge_amount === pledge &&
                            !investorPreviewRequest.processing
                        }
                        exampleOpen={exampleOpen}
                        onExampleToggle={() => setExampleOpen((open) => !open)}
                        onPledgeChange={changePledge}
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
                        policy={policy}
                        termIndex={termIndex}
                        canSize={canSize}
                        onFiguresChange={(changes) =>
                            setFigures((current) => ({
                                ...current,
                                ...changes,
                            }))
                        }
                        onSize={() => startSizing()}
                        onTermChange={(index) => {
                            setTermIndex(index);
                            startSizing(policy.terms[index]);
                        }}
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

            {businessOpen && sizing && (
                <BusinessModal
                    step={businessStep === 'pass' ? 'pass' : 'result'}
                    progress={progress}
                    progressLabel={progressLabel}
                    qualifiedAmount={sizing.qualified_amount}
                    belowMinimum={sizing.below_minimum}
                    rating={decorateRating(
                        sizing.rating.band,
                        sizing.rating.score,
                    )}
                    termLabel={`${term}mo`}
                    flatRate={`${sizing.flat_rate.toFixed(1)}%`}
                    queueNumber={businessQueue}
                    loanNumber={loanNumber}
                    districts={districts}
                    details={businessDetails}
                    anonymous={businessAnonymous}
                    contactError={businessSignup.errors.contact}
                    canSubmit={isComplete(businessDetails)}
                    processing={businessSignup.processing}
                    onAnonymousChange={setBusinessAnonymous}
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

            {investorOpen && investorPreview && (
                <InvestorModal
                    step={investorStep}
                    pledge={pledge}
                    payout={investorPreview.projected_return}
                    blendedYield={investorPreview.blended_yield}
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
