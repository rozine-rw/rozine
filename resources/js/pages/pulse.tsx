import { Head, router, useHttp, usePoll } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
    storeBusiness,
    storeInvestor,
    storeStatement,
} from '@/actions/App/Http/Controllers/PulseController';
import { BusinessModal } from '@/components/pulse/business-modal';
import { BusinessPanel } from '@/components/pulse/business-panel';
import { InvestorModal } from '@/components/pulse/investor-modal';
import { InvestorPanel } from '@/components/pulse/investor-panel';
import {
    PulseBackdrop,
    PulseDisclaimer,
    PulseFooter,
    PulseSteps,
    PulseTopbar,
} from '@/components/pulse/pulse-chrome';
import { PulseHero, TractionCards } from '@/components/pulse/pulse-hero';
import { PulseToast } from '@/components/pulse/pulse-toast';
import type { SignupDetails } from '@/components/pulse/signup-fields';
import {
    BLENDED_YIELD,
    businessNotes,
    capacityFor,
    DEFAULT_SCORE,
    isValidContact,
    isValidName,
    qualifyFor,
    rate,
    TERMS,
    yieldFor,
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

type StatementResponse = {
    statement_path: string;
    annual_inflow: number;
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
    statement_path: string;
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

export default function Pulse({ traction, listings, districts }: PulseProps) {
    const [toastMessage, setToastMessage] = useState('');
    const toastTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const investorPanel = useRef<HTMLDivElement>(null);
    const businessPanel = useRef<HTMLDivElement>(null);
    const statementInput = useRef<HTMLInputElement>(null);

    // Investor flow.
    const [pledge, setPledge] = useState(500000);
    const [investorOpen, setInvestorOpen] = useState(false);
    const [investorStep, setInvestorStep] = useState<'notes' | 'pledged'>(
        'notes',
    );
    const [investorDetails, setInvestorDetails] =
        useState<SignupDetails>(EMPTY_DETAILS);
    const [investorQueue, setInvestorQueue] = useState('#0142');

    // Business flow.
    const [businessOpen, setBusinessOpen] = useState(false);
    const [businessStep, setBusinessStep] = useState<
        'idle' | 'parsing' | 'result' | 'pass'
    >('idle');
    const [businessDetails, setBusinessDetails] =
        useState<SignupDetails>(EMPTY_DETAILS);
    const [businessQueue, setBusinessQueue] = useState('#0142');
    const [loanNumber, setLoanNumber] = useState('#1,480');
    const [termIndex, setTermIndex] = useState(3);
    const [progress, setProgress] = useState(0);
    const [annualInflow, setAnnualInflow] = useState(0);
    const [statementName, setStatementName] = useState('statement.pdf');
    const [statementPath, setStatementPath] = useState('');
    const [businessListed, setBusinessListed] = useState(false);

    const upload = useHttp<{ statement: File | null }, StatementResponse>({
        statement: null,
    });
    const investorSignup = useHttp<InvestorPayload, SignupResponse>({
        ...EMPTY_PAYLOAD,
        pledge_amount: 0,
    });
    const businessSignup = useHttp<BusinessPayload, SignupResponse>({
        ...EMPTY_PAYLOAD,
        statement_path: '',
        term_months: 12,
        listed: false,
    });

    // The figures on the page are whatever the database holds, refreshed in
    // place so a signup made elsewhere shows up without a reload.
    usePoll(10000, { only: ['traction', 'listings'], async: true });

    useEffect(() => {
        return () => {
            if (toastTimer.current) {
                clearTimeout(toastTimer.current);
            }
        };
    }, []);

    const rating = useMemo(() => rate(DEFAULT_SCORE), []);
    const notes = useMemo(
        () => businessNotes(listings, pledge),
        [listings, pledge],
    );

    const term = TERMS[termIndex];
    const flatRate = yieldFor(DEFAULT_SCORE, term);
    const qualifiedAmount = qualifyFor(
        capacityFor(annualInflow),
        DEFAULT_SCORE,
        term,
    );
    const monthlyRepayment = (qualifiedAmount * (1 + flatRate / 100)) / term;
    const payout = pledge * (1 + BLENDED_YIELD / 100);

    const progressLabel =
        progress < 50
            ? 'READING STATEMENT'
            : progress < 100
              ? 'RECONSTRUCTING CASH FLOW'
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

        window.scrollTo({
            top: element.getBoundingClientRect().top + window.scrollY - 14,
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

    function onStatementSelected(file: File): void {
        setStatementName(file.name);
        setStatementPath('');
        setAnnualInflow(0);
        setProgress(8);
        setBusinessStep('parsing');
        setBusinessOpen(false);

        upload.setData('statement', file);
        upload.post(storeStatement.url(), {
            onProgress: (event) =>
                setProgress(
                    Math.max(
                        8,
                        Math.min(99, Math.round(event.percentage ?? 0)),
                    ),
                ),
            onSuccess: (response) => {
                setProgress(100);
                setAnnualInflow(response.annual_inflow);
                setStatementPath(response.statement_path);
                setTimeout(() => setBusinessStep('result'), 360);
            },
            onError: (errors) => {
                setBusinessStep('idle');
                reportErrors(errors);
            },
            onNetworkError: () => {
                setBusinessStep('idle');
                toast('We could not reach the server. Please try again.');
            },
        });
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
            statement_path: statementPath,
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
            <PulseTopbar />

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
                        pledge={pledge}
                        payout={payout}
                        notes={notes}
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
                        statementName={statementName}
                        annualInflow={annualInflow}
                        qualifiedAmount={qualifiedAmount}
                        monthlyRepayment={monthlyRepayment}
                        flatRate={`${flatRate.toFixed(1)}%`}
                        rating={rating}
                        termIndex={termIndex}
                        onUpload={() => statementInput.current?.click()}
                        onTermChange={setTermIndex}
                        onSaveSpot={() => setBusinessOpen(true)}
                    />
                </div>

                <PulseSteps />
                <PulseDisclaimer />
                <PulseFooter />
            </div>

            {businessOpen && (
                <BusinessModal
                    step={businessStep === 'idle' ? 'result' : businessStep}
                    progress={progress}
                    progressLabel={progressLabel}
                    qualifiedAmount={qualifiedAmount}
                    rating={rating}
                    termLabel={`${term}mo`}
                    flatRate={`${flatRate.toFixed(1)}%`}
                    queueNumber={businessQueue}
                    loanNumber={loanNumber}
                    districts={districts}
                    details={businessDetails}
                    listed={businessListed}
                    contactError={businessSignup.errors.contact}
                    canSubmit={
                        isComplete(businessDetails) && statementPath !== ''
                    }
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

            <input
                ref={statementInput}
                type="file"
                accept=".pdf,.csv,.txt,.xls,.xlsx,.jpg,.jpeg,.png"
                className="hidden"
                onChange={(event) => {
                    const file = event.target.files?.[0];

                    if (file) {
                        onStatementSelected(file);
                    }

                    event.target.value = '';
                }}
            />
        </div>
    );
}
