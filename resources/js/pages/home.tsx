import { Head, useHttp } from '@inertiajs/react';
import type { CSSProperties, FormEvent } from 'react';
import { useEffect, useState } from 'react';
import legal from '@/routes/legal';

import { AppEntry } from '@/components/site/app-entry';
import {
    BIZ_FAQS,
    BIZ_STEPS,
    HELP_TABS,
    JOURNEY,
    LANDING_PAGES,
    TERMS,
    BANDS,
    businessEstimate,
    contactProblem,
    formatPosition,
    investorExample,
    pageFromHash,
    PAGE_HASH,
} from '@/lib/landing';
import type { Band, HelpTab, LandingPage, WaitlistRole } from '@/lib/landing';
import { store as storeWaitlist } from '@/routes/site/waitlist';

import '../../css/site.css';

/**
 * The rozine.rw landing page, built from the "Rozine.rw v2" Claude Design page.
 * Four views share one header and one waitlist: Invest, Businesses, How it works and Help.
 * Every inline style below is the design's own, so the page renders as the design does.
 */

type SiteProps = {
    showAppEntry?: boolean;
    auth?: { user: unknown };
};

type WaitlistAnswer = {
    queue_number: string;
    position: number;
    referral_link: string;
};

const IMG = '/site/landing/';
const WRAP: CSSProperties = {
    maxWidth: '1240px',
    margin: '0 auto',
};
const PAD_X = 'clamp(18px,4vw,44px)';
const SECTION_TOP = 'clamp(72px,10vw,136px)';

const kicker = (color: string, extra: CSSProperties = {}): CSSProperties => ({
    fontSize: '13px',
    fontWeight: 800,
    letterSpacing: '.1em',
    color,
    ...extra,
});

const coverImg = (position: string): CSSProperties => ({
    position: 'absolute',
    inset: 0,
    width: '100%',
    height: '100%',
    objectFit: 'cover',
    objectPosition: position,
});

const photoBox = (extra: CSSProperties = {}): CSSProperties => ({
    position: 'relative',
    borderRadius: '24px',
    overflow: 'hidden',
    background: '#e7ecf4',
    ...extra,
});

/** A numbered marker pinned over an app screenshot. */
function Pin({
    n,
    top,
    left,
    ring,
}: {
    n: number;
    top: string;
    left: string;
    ring: string;
}) {
    return (
        <span
            style={{
                position: 'absolute',
                top,
                left,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: '28px',
                height: '28px',
                borderRadius: '50%',
                background: '#1e3aff',
                color: '#ffffff',
                fontSize: '13px',
                fontWeight: 900,
                boxShadow: `0 0 0 4px ${ring}`,
            }}
        >
            {n}
        </span>
    );
}

/** A numbered line explaining one pin. */
function PinNote({
    n,
    title,
    body,
    dark = false,
}: {
    n: number;
    title: string;
    body: string;
    dark?: boolean;
}) {
    return (
        <div
            style={{
                display: 'grid',
                gridTemplateColumns: '24px minmax(0,1fr)',
                gap: '12px',
            }}
        >
            <span
                style={{
                    width: '24px',
                    height: '24px',
                    borderRadius: '50%',
                    background: '#1e3aff',
                    color: '#ffffff',
                    fontSize: '12px',
                    fontWeight: 900,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                }}
            >
                {n}
            </span>
            <p
                style={{
                    margin: 0,
                    fontSize: '15px',
                    lineHeight: 1.55,
                    color: dark ? '#b9c2d6' : '#3a465e',
                }}
            >
                <strong style={{ color: dark ? '#ffffff' : '#0c1830' }}>
                    {title}
                </strong>{' '}
                {body}
            </p>
        </div>
    );
}

/** A numbered step row, used by the business steps and the journey of your money. */
function StepList({
    steps,
    color,
}: {
    steps: ReadonlyArray<readonly [string, string]>;
    color: string;
}) {
    return (
        <div
            style={{
                flex: '1.4 1 480px',
                minWidth: 0,
                display: 'flex',
                flexDirection: 'column',
            }}
        >
            {steps.map(([title, body], index) => (
                <div
                    key={title}
                    style={{
                        display: 'grid',
                        gridTemplateColumns: '52px minmax(0,1fr)',
                        gap: '14px',
                        padding: '22px 0',
                        borderTop: '1px solid #e7ecf4',
                    }}
                >
                    <span
                        style={{
                            width: '36px',
                            height: '36px',
                            borderRadius: '12px',
                            background: color,
                            color: '#ffffff',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            fontSize: '15px',
                            fontWeight: 900,
                        }}
                    >
                        {index + 1}
                    </span>
                    <div
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: '6px',
                        }}
                    >
                        <h3
                            style={{
                                margin: 0,
                                fontSize: '20px',
                                fontWeight: 800,
                                letterSpacing: '-.025em',
                            }}
                        >
                            {title}
                        </h3>
                        <p
                            style={{
                                margin: 0,
                                fontSize: '15px',
                                lineHeight: 1.6,
                                color: '#69748a',
                                textWrap: 'pretty',
                            }}
                        >
                            {body}
                        </p>
                    </div>
                </div>
            ))}
        </div>
    );
}

/** An accordion of questions; one answer is open at a time. */
function FaqList({
    faqs,
    open,
    onToggle,
    openBg,
    signColor,
    answerWidth,
}: {
    faqs: ReadonlyArray<readonly [string, string]>;
    open: number;
    onToggle: (index: number) => void;
    openBg: string;
    signColor: string;
    answerWidth: string;
}) {
    return (
        <>
            {faqs.map(([question, answer], index) => {
                const isOpen = open === index;

                return (
                    <div
                        key={question}
                        style={{
                            background: isOpen ? openBg : '#f8fafd',
                            borderRadius: '20px',
                            padding: '4px 22px',
                        }}
                    >
                        <button
                            type="button"
                            aria-expanded={isOpen}
                            onClick={() => onToggle(isOpen ? -1 : index)}
                            style={{
                                width: '100%',
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'center',
                                gap: '16px',
                                padding: '18px 0',
                                background: 'none',
                                border: 'none',
                                textAlign: 'left',
                                cursor: 'pointer',
                            }}
                        >
                            <span
                                style={{
                                    fontSize: '18px',
                                    fontWeight: 700,
                                    letterSpacing: '-.015em',
                                }}
                            >
                                {question}
                            </span>
                            <span
                                aria-hidden="true"
                                style={{ fontSize: '22px', color: signColor }}
                            >
                                {isOpen ? '−' : '+'}
                            </span>
                        </button>
                        {isOpen && (
                            <p
                                style={{
                                    margin: '0 0 20px',
                                    maxWidth: answerWidth,
                                    fontSize: '15.5px',
                                    lineHeight: 1.65,
                                    color: '#3a465e',
                                    textWrap: 'pretty',
                                }}
                            >
                                {answer}
                            </p>
                        )}
                    </div>
                );
            })}
        </>
    );
}

/** A two-column heading block: photo on one side, steps on the other. */
function StepsSection({
    label,
    labelColor,
    title,
    photo,
    photoAlt,
    photoPosition,
    steps,
    stepColor,
}: {
    label: string;
    labelColor: string;
    title: string;
    photo: string;
    photoAlt: string;
    photoPosition: string;
    steps: ReadonlyArray<readonly [string, string]>;
    stepColor: string;
}) {
    return (
        <section
            style={{
                ...WRAP,
                padding: `${SECTION_TOP} ${PAD_X} 0`,
                display: 'flex',
                flexWrap: 'wrap',
                gap: 'clamp(32px,5vw,72px)',
            }}
        >
            <div
                style={{
                    flex: '1 1 320px',
                    minWidth: 0,
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '16px',
                }}
            >
                <span style={kicker(labelColor)}>{label}</span>
                <h2
                    style={{
                        margin: 0,
                        fontSize: 'clamp(34px,4.6vw,60px)',
                        lineHeight: 0.96,
                        fontWeight: 900,
                        letterSpacing: '-.05em',
                        textWrap: 'balance',
                    }}
                >
                    {title}
                </h2>
                <div
                    style={photoBox({
                        marginTop: '14px',
                        aspectRatio: '4/5',
                        maxHeight: '520px',
                    })}
                >
                    <img
                        src={IMG + photo}
                        alt={photoAlt}
                        loading="lazy"
                        style={coverImg(photoPosition)}
                    />
                </div>
            </div>
            <StepList steps={steps} color={stepColor} />
        </section>
    );
}

const HERO_BADGE = (bg: string, border: string): CSSProperties => ({
    alignSelf: 'flex-start',
    fontSize: '13px',
    fontWeight: 600,
    padding: '7px 14px',
    borderRadius: '999px',
    background: bg,
    border: `1px solid ${border}`,
});

const H1: CSSProperties = {
    margin: 0,
    fontSize: 'clamp(44px,7vw,98px)',
    lineHeight: 0.93,
    fontWeight: 900,
    letterSpacing: '-.055em',
    textWrap: 'balance',
};

const H2_BIG: CSSProperties = {
    margin: 0,
    fontSize: 'clamp(34px,5vw,66px)',
    lineHeight: 0.95,
    fontWeight: 900,
    letterSpacing: '-.05em',
    textWrap: 'balance',
};

const CHOICE = (on: boolean, color: string, tint: string): CSSProperties => ({
    height: '40px',
    borderRadius: '12px',
    border: `1.5px solid ${on ? color : '#e2e8f2'}`,
    background: on ? tint : '#ffffff',
    color: on ? color : '#3a465e',
    fontSize: '13.5px',
    fontWeight: 700,
    cursor: 'pointer',
});

const BAND_COLORS: Record<Band, [string, string]> = {
    Strong: ['#17795a', '#e7f4ee'],
    Stable: ['#1e3aff', '#eef1ff'],
    Distressed: ['#c0392b', '#fdecec'],
};

/** The investor hero's "What you could earn" calculator. */
function EarnCard() {
    const [amount, setAmount] = useState(100000);
    const [term, setTerm] = useState(5);
    const [band, setBand] = useState<Band>('Stable');
    const example = investorExample(amount, term, band);

    const label = (text: string) => (
        <span style={{ fontSize: '13px', fontWeight: 600, color: '#69748a' }}>
            {text}
        </span>
    );

    return (
        <div
            style={{
                background: '#ffffff',
                color: '#0c1830',
                borderRadius: '28px',
                padding: '22px',
                boxShadow: '0 50px 90px -40px rgba(5,10,40,.75)',
                display: 'flex',
                flexDirection: 'column',
                gap: '18px',
            }}
        >
            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                }}
            >
                <span
                    style={{
                        fontSize: '16px',
                        fontWeight: 800,
                        letterSpacing: '-.02em',
                    }}
                >
                    What you could earn
                </span>
                <span
                    style={{
                        fontSize: '11px',
                        fontWeight: 700,
                        letterSpacing: '.06em',
                        color: '#7c869a',
                    }}
                >
                    EXAMPLE
                </span>
            </div>
            <div
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '10px',
                }}
            >
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'baseline',
                    }}
                >
                    {label('You put in')}
                    <span
                        style={{
                            fontSize: '22px',
                            fontWeight: 800,
                            letterSpacing: '-.03em',
                        }}
                    >
                        {example.amount}
                    </span>
                </div>
                <input
                    type="range"
                    aria-label="Amount you put in"
                    min={5000}
                    max={2000000}
                    step={5000}
                    value={amount}
                    onChange={(event) => setAmount(Number(event.target.value))}
                    style={{ width: '100%', accentColor: '#1e3aff' }}
                />
            </div>
            <div
                role="group"
                aria-label="Term"
                style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}
            >
                {label('For')}
                <div
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(4,minmax(0,1fr))',
                        gap: '6px',
                    }}
                >
                    {TERMS.map((months) => (
                        <button
                            key={months}
                            type="button"
                            aria-pressed={months === term}
                            onClick={() => setTerm(months)}
                            style={CHOICE(
                                months === term,
                                '#1e3aff',
                                '#eef1ff',
                            )}
                        >
                            {months} mo
                        </button>
                    ))}
                </div>
            </div>
            <div
                role="group"
                aria-label="Rating"
                style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}
            >
                {label('In a business rated')}
                <div
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(3,minmax(0,1fr))',
                        gap: '6px',
                    }}
                >
                    {BANDS.map((name) => (
                        <button
                            key={name}
                            type="button"
                            aria-pressed={name === band}
                            onClick={() => setBand(name)}
                            style={CHOICE(
                                name === band,
                                BAND_COLORS[name][0],
                                BAND_COLORS[name][1],
                            )}
                        >
                            {name}
                        </button>
                    ))}
                </div>
            </div>
            <div
                style={{
                    background: '#f3f5ff',
                    borderRadius: '20px',
                    padding: '16px 16px 14px',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '12px',
                }}
            >
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'baseline',
                        gap: '10px',
                    }}
                >
                    <span style={{ fontSize: '14px', fontWeight: 700 }}>
                        You get back
                    </span>
                    <span
                        data-testid="earn-back"
                        style={{
                            fontSize: 'clamp(28px,3vw,34px)',
                            fontWeight: 900,
                            letterSpacing: '-.045em',
                            color: '#1e3aff',
                        }}
                    >
                        {example.back}
                    </span>
                </div>
                <div
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(3,minmax(0,1fr))',
                        gap: '8px',
                        borderTop: '1px solid #dfe4f5',
                        paddingTop: '12px',
                    }}
                >
                    {(
                        [
                            ['RATE', example.rate, undefined],
                            ['YOU EARN', example.earn, '#17795a'],
                            ['EACH MONTH', example.month, undefined],
                        ] as const
                    ).map(([name, value, color]) => (
                        <div
                            key={name}
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: '3px',
                            }}
                        >
                            <span
                                style={{
                                    fontSize: '10px',
                                    fontWeight: 700,
                                    letterSpacing: '.06em',
                                    color: '#69748a',
                                }}
                            >
                                {name}
                            </span>
                            <span
                                style={{
                                    fontSize: '15px',
                                    fontWeight: 800,
                                    color,
                                }}
                            >
                                {value}
                            </span>
                        </div>
                    ))}
                </div>
            </div>
            <span
                style={{
                    fontSize: '11.5px',
                    lineHeight: 1.5,
                    color: '#8894a8',
                    textWrap: 'pretty',
                }}
            >
                Example rates for the whole term, after Rozine's 15% earnings
                fee. Not guaranteed.
            </span>
        </div>
    );
}

/** The business hero's "How much could you raise?" estimate. */
function RaiseCard() {
    const [revenue, setRevenue] = useState(120000000);
    const estimate = businessEstimate(revenue);

    return (
        <div
            style={{
                background: '#ffffff',
                color: '#0c1830',
                borderRadius: '28px',
                padding: '22px',
                boxShadow: '0 50px 90px -40px rgba(5,40,30,.6)',
                display: 'flex',
                flexDirection: 'column',
                gap: '18px',
            }}
        >
            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                }}
            >
                <span
                    style={{
                        fontSize: '16px',
                        fontWeight: 800,
                        letterSpacing: '-.02em',
                    }}
                >
                    How much could you raise?
                </span>
                <span
                    style={{
                        fontSize: '11px',
                        fontWeight: 700,
                        letterSpacing: '.06em',
                        color: '#7c869a',
                    }}
                >
                    ESTIMATE
                </span>
            </div>
            <div
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '10px',
                }}
            >
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'baseline',
                    }}
                >
                    <span
                        style={{
                            fontSize: '13px',
                            fontWeight: 600,
                            color: '#69748a',
                        }}
                    >
                        Your yearly revenue
                    </span>
                    <span
                        style={{
                            fontSize: '20px',
                            fontWeight: 800,
                            letterSpacing: '-.02em',
                        }}
                    >
                        {estimate.revenue}
                    </span>
                </div>
                <input
                    type="range"
                    aria-label="Your yearly revenue"
                    min={30000000}
                    max={500000000}
                    step={5000000}
                    value={revenue}
                    onChange={(event) => setRevenue(Number(event.target.value))}
                    style={{ width: '100%' }}
                />
            </div>
            <div
                style={{
                    background: '#e7f4ee',
                    borderRadius: '18px',
                    padding: '16px',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '4px',
                }}
            >
                <span
                    style={{
                        fontSize: '10.5px',
                        fontWeight: 700,
                        letterSpacing: '.06em',
                        color: '#17795a',
                    }}
                >
                    YOU COULD RAISE UP TO
                </span>
                <span
                    data-testid="raise-max"
                    style={{
                        fontSize: 'clamp(32px,4vw,42px)',
                        fontWeight: 900,
                        letterSpacing: '-.045em',
                        color: '#0c1830',
                    }}
                >
                    {estimate.max}
                </span>
                <span style={{ fontSize: '12.5px', color: '#3a465e' }}>
                    {estimate.cap}
                </span>
            </div>
            <span
                style={{
                    fontSize: '11.5px',
                    lineHeight: 1.5,
                    color: '#8894a8',
                    textWrap: 'pretty',
                }}
            >
                The final amount depends on your verified monthly cash: it must
                cover each instalment at least 1.5 times.
            </span>
        </div>
    );
}

const WAITLIST_ROLES: ReadonlyArray<[WaitlistRole, string, string]> = [
    ['investor', 'I want to invest', '#1e3aff'],
    ['business', 'I want to borrow', '#1d9e75'],
];

const FIELD: CSSProperties = {
    height: '52px',
    borderRadius: '14px',
    border: '1.5px solid #dbe2ee',
    padding: '0 16px',
    fontSize: '15px',
    outline: 'none',
};

/** The waitlist form, its loading and error states, and the "You're in" card. */
function Waitlist({
    role,
    onRole,
}: {
    role: WaitlistRole;
    onRole: (role: WaitlistRole) => void;
}) {
    const http = useHttp<
        { role: WaitlistRole; name: string; contact: string; ref: string },
        WaitlistAnswer
    >({ role, name: '', contact: '', ref: '' });
    const [problem, setProblem] = useState('');
    const [joined, setJoined] = useState<WaitlistAnswer | null>(null);
    const [copied, setCopied] = useState(false);
    const isBusiness = role === 'business';
    const roleColor = isBusiness ? '#1d9e75' : '#1e3aff';
    const serverError =
        http.errors.name ?? http.errors.contact ?? http.errors.role;
    const shown = problem || serverError || '';

    useEffect(() => {
        const ref = new URLSearchParams(window.location.search).get('ref');

        if (ref) {
            http.setData('ref', ref.replace(/[^A-Za-z0-9]/g, '').slice(0, 64));
        }
        // Read the shared link once, on arrival.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        http.setData('role', role);
        // Follow the audience the visitor switched to.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [role]);

    const edit = (field: 'name' | 'contact', value: string) => {
        http.setData(field, value);
        http.clearErrors();
        setProblem('');
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (http.processing) {
            return;
        }

        const local = contactProblem(role, http.data.name, http.data.contact);

        if (local) {
            setProblem(local);

            return;
        }

        setProblem('');
        http.post(storeWaitlist.url(), {
            onNetworkError: () =>
                setProblem(
                    'We could not reach Rozine. Check your connection and try again.',
                ),
            onHttpException: (response) => {
                if (response.status !== 422) {
                    setProblem(
                        response.status === 429
                            ? 'Too many tries. Wait a minute and try again.'
                            : 'Something went wrong on our side. Please try again.',
                    );
                }
            },
        }).then(
            (answer) => {
                if (
                    answer &&
                    typeof answer === 'object' &&
                    'position' in answer
                ) {
                    setJoined(answer);
                }
            },
            () => undefined,
        );
    };

    const copy = (link: string) => {
        try {
            void navigator.clipboard?.writeText(link);
        } catch {
            // The link stays on screen to copy by hand.
        }

        setCopied(true);
    };

    if (joined) {
        const sharedLink = joined.referral_link.replace(/^https?:\/\//, '');

        return (
            <div
                role="status"
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '14px',
                }}
            >
                <span
                    style={{
                        fontSize: '13px',
                        fontWeight: 800,
                        letterSpacing: '.08em',
                        color: '#17795a',
                    }}
                >
                    YOU'RE IN
                </span>
                <span
                    style={{
                        fontSize: 'clamp(44px,5vw,60px)',
                        fontWeight: 900,
                        letterSpacing: '-.05em',
                        lineHeight: 1,
                    }}
                >
                    #{formatPosition(joined.position)}
                </span>
                <p
                    style={{
                        margin: 0,
                        fontSize: '15px',
                        lineHeight: 1.6,
                        color: '#69748a',
                    }}
                >
                    {isBusiness
                        ? "We'll be in touch to confirm your business qualifies. Know another business owner? Share your link."
                        : 'Share your link. Every person who joins through it moves you up the list.'}
                </p>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        background: '#f3f5ff',
                        borderRadius: '14px',
                        padding: '10px 10px 10px 14px',
                    }}
                >
                    <span
                        style={{
                            flex: 1,
                            minWidth: 0,
                            fontSize: '13.5px',
                            fontWeight: 600,
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                        }}
                    >
                        {sharedLink}
                    </span>
                    <button
                        type="button"
                        onClick={() => copy(joined.referral_link)}
                        style={{
                            height: '36px',
                            padding: '0 16px',
                            borderRadius: '10px',
                            border: 'none',
                            background: '#1e3aff',
                            color: '#ffffff',
                            fontSize: '13px',
                            fontWeight: 700,
                            cursor: 'pointer',
                        }}
                    >
                        {copied ? 'Copied' : 'Copy'}
                    </button>
                </div>
            </div>
        );
    }

    return (
        <form
            noValidate
            aria-label="Join the waitlist"
            onSubmit={submit}
            aria-busy={http.processing}
            style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}
        >
            <div
                role="group"
                aria-label="I am joining as"
                style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(2,minmax(0,1fr))',
                    gap: '6px',
                    background: '#f3f5f9',
                    borderRadius: '14px',
                    padding: '4px',
                }}
            >
                {WAITLIST_ROLES.map(([key, label, color]) => {
                    const on = key === role;

                    return (
                        <button
                            key={key}
                            type="button"
                            aria-pressed={on}
                            onClick={() => {
                                onRole(key);
                                setProblem('');
                                http.clearErrors();
                            }}
                            style={{
                                height: '42px',
                                borderRadius: '11px',
                                border: 'none',
                                background: on ? '#ffffff' : 'transparent',
                                color: on ? color : '#69748a',
                                fontSize: '13.5px',
                                fontWeight: 700,
                                cursor: 'pointer',
                            }}
                        >
                            {label}
                        </button>
                    );
                })}
            </div>
            <input
                className="rz-field"
                name="name"
                autoComplete={isBusiness ? 'organization' : 'name'}
                aria-label={isBusiness ? 'Business name' : 'Your name'}
                aria-invalid={Boolean(http.errors.name)}
                value={http.data.name}
                onChange={(event) => edit('name', event.target.value)}
                placeholder={isBusiness ? 'Business name' : 'Your name'}
                style={FIELD}
            />
            <input
                className="rz-field"
                name="contact"
                autoComplete="email"
                inputMode="email"
                aria-label="Phone number or email"
                aria-invalid={Boolean(http.errors.contact)}
                value={http.data.contact}
                onChange={(event) => edit('contact', event.target.value)}
                placeholder="Phone number or email"
                style={FIELD}
            />
            {shown !== '' && (
                <span
                    role="alert"
                    style={{ fontSize: '13px', color: '#c0392b' }}
                >
                    {shown}
                </span>
            )}
            <button
                type="submit"
                disabled={http.processing}
                style={{
                    height: '56px',
                    borderRadius: '16px',
                    border: 'none',
                    background: roleColor,
                    color: '#ffffff',
                    fontSize: '16px',
                    fontWeight: 800,
                    cursor: http.processing ? 'progress' : 'pointer',
                    opacity: http.processing ? 0.85 : 1,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '10px',
                }}
            >
                {http.processing && (
                    <span className="rz-spinner" aria-hidden="true" />
                )}
                {http.processing
                    ? 'Joining…'
                    : isBusiness
                      ? 'Join the business waitlist'
                      : 'Join the investor waitlist'}
            </button>
            <span style={{ fontSize: '12px', color: '#8894a8' }}>
                We'll only contact you about Rozine. No spam.
            </span>
        </form>
    );
}

const SECTOR_TILES: ReadonlyArray<[string, string, string, string]> = [
    ['restaurant-w.jpg', 'Food and drink', 'Food & drink', '50% 40%'],
    ['minimart-w.jpg', 'Retail', 'Retail', '45% 50%'],
    ['tea-w.jpg', 'Agriculture', 'Agriculture', '60% 40%'],
    ['fleet-w.jpg', 'Transport', 'Transport', '60% 60%'],
    ['lodge-w.jpg', 'Hospitality', 'Hospitality', '50% 60%'],
    ['salon-owner-w.jpg', 'Services', 'Services', '50% 30%'],
];

const FOUR_TAPS: ReadonlyArray<[string, string]> = [
    [
        'Pick a business',
        "Its verified numbers, its rating and exactly what you'd get back, all before you commit.",
    ],
    [
        'Watch it fill',
        "Raises fill within 30 days. If one ever doesn't, every franc comes straight back to you.",
    ],
    [
        'Paid every month',
        "Part of your money plus your return, each month, with a sealed accountant's report.",
    ],
    [
        'Reinvest or cash out',
        'Back the next business, withdraw to mobile money, or sell your note to another investor any time.',
    ],
];

const WHY_ROWS: ReadonlyArray<[string, string]> = [
    ['Verified in person before raising, and every month after', 'CPA'],
    ['Cash that covers every repayment 1.5 times over', '1.5×'],
    ['A sealed report on your business, every month', 'REPORT'],
    ['Pay late, and the penalties are paid to you', 'YOURS'],
];

const BIZ_STATS: ReadonlyArray<[string, string]> = [
    ['RWF 30M+', 'yearly revenue to qualify'],
    ['0', 'collateral'],
    ['30 days', 'to raise'],
    ['1.5×', 'bigger on your next raise'],
];

const QUALIFY_PHOTOS: ReadonlyArray<[string, string, string]> = [
    [
        'q-salon.jpg',
        'A stylist with a smiling client at a hair salon',
        '50% 30%',
    ],
    ['q-tea.jpg', 'A tea picker holding fresh leaves', '50% 40%'],
    ['q-dairy.jpg', 'Dairy cows at a feeding rail', '55% 50%'],
    ['q-shoes.jpg', 'A shopper choosing sneakers in a shoe store', '55% 40%'],
];

const QUALIFY_CARDS: ReadonlyArray<[string, string, string]> = [
    [
        'RW',
        'Registered in Rwanda',
        'An RDB-registered business with a tax record in good standing.',
    ],
    ['30M+', 'RWF 30M+ a year', 'Yearly revenue an accountant can verify.'],
    [
        '+',
        'Consistently profitable',
        'A record of real profit, so repayments fit comfortably.',
    ],
    [
        '✓',
        'Records we can check',
        'Bank, mobile-money and cash records that match your books.',
    ],
];

const MONEY_TILES: ReadonlyArray<[string, string, string]> = [
    ['m-stock.jpg', 'Fresh produce', '50% 70%'],
    ['m-inventory.jpg', 'Inventory', '55% 50%'],
    ['m-store.jpg', 'Store expansion', '70% 45%'],
    ['m-equipment.jpg', 'Equipment', '70% 50%'],
    ['m-machinery.jpg', 'Machinery', '45% 50%'],
    ['m-vehicle.jpg', 'Vehicles', '62% 60%'],
    ['m-delivery.jpg', 'Deliveries', '45% 35%'],
    ['m-orders.jpg', 'Big orders', '55% 45%'],
    ['m-livestock.jpg', 'Livestock', '62% 55%'],
    ['m-staff.jpg', 'Staff', '72% 35%'],
];

const LATE_DAYS: ReadonlyArray<[string, string, string]> = [
    [
        'Day 1',
        'Payment missed. A 5% penalty is added and reminders start.',
        '#8ea0ff',
    ],
    ['Day 7', 'Still unpaid. Another 5%.', '#8ea0ff'],
    [
        'Day 30',
        'A final 5%. Penalties stop at 15% of the instalment.',
        '#8ea0ff',
    ],
    ['Day 31', 'Legal recovery begins for everything owed.', '#ff8a8e'],
];

const FEES: ReadonlyArray<[string, string]> = [
    ['To invest', 'Nothing'],
    ['On the return you earn', '15%, less as you grow'],
    ['Selling a note early', '2%, seller only'],
    ['Buying a resale note', 'Nothing'],
];

const RATING_CARDS: ReadonlyArray<[string, string, string, string]> = [
    [
        'STRONG',
        'rgba(29,158,117,.1)',
        '#1d9e75',
        'Long, steady, profitable records. The lowest returns, because the lowest risk.',
    ],
    [
        'STABLE',
        'rgba(30,58,255,.08)',
        '#1e3aff',
        'Healthy businesses with a solid record. The middle of the range.',
    ],
    [
        'DISTRESSED',
        'rgba(229,72,77,.09)',
        '#c0392b',
        'Still verified and still covering repayments 1.5 times, but a weaker record. The highest returns, and the highest risk.',
    ],
];

/** The footer's links to the three legal documents everyone agrees to at sign-up. */
const LEGAL_LINKS: ReadonlyArray<[string, string]> = [
    ['Terms', legal.terms().url],
    ['Privacy', legal.privacy().url],
    ['Disclaimers', legal.disclaimers().url],
];

export default function Home({ showAppEntry = false, auth }: SiteProps) {
    const [page, setPage] = useState<LandingPage>('home');
    const [role, setRole] = useState<WaitlistRole>('investor');
    const [bizFaq, setBizFaq] = useState(0);
    const [helpTab, setHelpTab] = useState<HelpTab>('investing');
    const [helpFaq, setHelpFaq] = useState(0);
    const current = LANDING_PAGES.find(([key]) => key === page)!;
    const headerBg = current[2];

    const show = (next: LandingPage) => {
        setPage(next);

        if (next === 'biz') {
            setRole('business');
        } else if (next === 'home') {
            setRole('investor');
        }

        window.scrollTo(0, 0);
    };

    const go = (next: LandingPage) => {
        show(next);

        const hash = PAGE_HASH[next];

        if (window.location.hash !== hash) {
            if (hash === '') {
                window.history.pushState(
                    null,
                    '',
                    window.location.pathname + window.location.search,
                );
            } else {
                window.location.hash = hash;
            }
        }
    };

    useEffect(() => {
        const follow = () => {
            const next = pageFromHash(window.location.hash);

            if (next !== null) {
                show(next);
            }
        };

        follow();
        window.addEventListener('hashchange', follow);
        window.addEventListener('popstate', follow);

        return () => {
            window.removeEventListener('hashchange', follow);
            window.removeEventListener('popstate', follow);
        };
        // The listeners only ever call setters.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const navButton = (key: LandingPage, label: string) => (
        <button
            key={key}
            type="button"
            aria-current={key === page ? 'page' : undefined}
            onClick={() => go(key)}
            style={{
                height: '38px',
                padding: '0 14px',
                borderRadius: '999px',
                border: 'none',
                background:
                    key === page ? 'rgba(255,255,255,.16)' : 'transparent',
                color: '#ffffff',
                fontSize: '14px',
                fontWeight: 600,
                cursor: 'pointer',
            }}
        >
            {label}
        </button>
    );

    const joinTitle =
        page === 'biz'
            ? 'Be among the first businesses to raise.'
            : 'The first raises will fill fast.';
    const joinSub =
        page === 'biz'
            ? "Join the waitlist and we'll reach out to book your accountant's visit."
            : 'Join the waitlist and hear about them first, by SMS or email.';

    return (
        <>
            <Head title="Rozine — invest in Rwandan businesses" />
            <div
                className="rz-site"
                style={{ minHeight: '100vh', background: '#ffffff' }}
            >
                <header
                    style={{
                        position: 'sticky',
                        top: 0,
                        zIndex: 50,
                        background: headerBg,
                        transition: 'background .3s ease',
                    }}
                >
                    <div
                        className="rz-hdr"
                        style={{
                            ...WRAP,
                            padding: `0 ${PAD_X}`,
                            height: '72px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '24px',
                        }}
                    >
                        <button
                            type="button"
                            aria-label="Rozine home"
                            onClick={() => go('home')}
                            style={{
                                display: 'flex',
                                flexShrink: 0,
                                background: 'none',
                                border: 'none',
                                padding: 0,
                                cursor: 'pointer',
                            }}
                        >
                            <img
                                src={IMG + 'logo-lockup-white.png'}
                                alt="Rozine"
                                style={{
                                    height: '22px',
                                    width: 'auto',
                                    display: 'block',
                                }}
                            />
                        </button>
                        <nav
                            className="rz-hdr-nav"
                            aria-label="Main"
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 'clamp(6px,1.4vw,14px)',
                                marginLeft: 'auto',
                                flexWrap: 'wrap',
                                justifyContent: 'flex-end',
                            }}
                        >
                            <span className="rz-hdr-tabs">
                                {LANDING_PAGES.map(([key, label]) =>
                                    navButton(key, label),
                                )}
                            </span>
                            <a
                                className="rz-hdr-join"
                                href="#join"
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    height: '40px',
                                    padding: '0 18px',
                                    borderRadius: '999px',
                                    background: '#ffffff',
                                    color: headerBg,
                                    fontSize: '14px',
                                    fontWeight: 700,
                                }}
                            >
                                Join the waitlist
                            </a>
                            <AppEntry
                                enabled={showAppEntry}
                                signedIn={Boolean(auth?.user)}
                                tone="dark"
                            />
                        </nav>
                    </div>
                </header>

                {page === 'home' && (
                    <>
                        <section
                            style={{
                                position: 'relative',
                                overflow: 'hidden',
                                background: '#1428a4',
                                color: '#ffffff',
                            }}
                        >
                            <div
                                aria-hidden="true"
                                style={{
                                    position: 'absolute',
                                    top: '-30%',
                                    right: '-10%',
                                    width: 'min(900px,90vw)',
                                    aspectRatio: '1',
                                    borderRadius: '50%',
                                    background:
                                        'radial-gradient(circle, rgba(30,58,255,.85) 0%, rgba(30,58,255,0) 62%)',
                                }}
                            />
                            <div
                                style={{
                                    position: 'relative',
                                    ...WRAP,
                                    padding: `clamp(36px,6vw,84px) ${PAD_X} clamp(56px,8vw,112px)`,
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    gap: 'clamp(36px,5vw,72px)',
                                    alignItems: 'center',
                                }}
                            >
                                <div
                                    style={{
                                        flex: '1.15 1 460px',
                                        minWidth: 0,
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '26px',
                                        animation: 'rzb-up .7s ease both',
                                    }}
                                >
                                    <span
                                        style={HERO_BADGE(
                                            'rgba(255,255,255,.12)',
                                            'rgba(255,255,255,.22)',
                                        )}
                                    >
                                        From RWF 5,000 · back in 3 to 6 months
                                    </span>
                                    <h1 style={H1}>
                                        Earn up to{' '}
                                        <span
                                            style={{
                                                color: '#6fd7b1',
                                                whiteSpace: 'nowrap',
                                            }}
                                        >
                                            15%
                                        </span>{' '}
                                        lending to profitable Rwandan
                                        businesses.
                                    </h1>
                                    <p
                                        style={{
                                            margin: 0,
                                            maxWidth: '560px',
                                            fontSize: 'clamp(17px,1.5vw,20px)',
                                            lineHeight: 1.55,
                                            color: '#dfe4ff',
                                            textWrap: 'pretty',
                                        }}
                                    >
                                        <strong style={{ color: '#ffffff' }}>
                                            Paid every month.
                                        </strong>{' '}
                                        Back the bakeries, hotels, farms and
                                        fleets keeping Rwanda moving, and get
                                        your money back with a return in 3 to 6
                                        months.
                                    </p>
                                    <div
                                        style={{
                                            display: 'flex',
                                            flexWrap: 'wrap',
                                            gap: '12px',
                                        }}
                                    >
                                        <a
                                            href="#join"
                                            style={{
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: '10px',
                                                height: '56px',
                                                padding: '0 28px',
                                                borderRadius: '999px',
                                                background: '#ffffff',
                                                color: '#1428a4',
                                                fontSize: '16px',
                                                fontWeight: 800,
                                            }}
                                        >
                                            Join the waitlist <span>→</span>
                                        </a>
                                        <button
                                            type="button"
                                            onClick={() => go('how')}
                                            style={{
                                                display: 'flex',
                                                alignItems: 'center',
                                                height: '56px',
                                                padding: '0 24px',
                                                borderRadius: '999px',
                                                border: '1px solid rgba(255,255,255,.4)',
                                                background: 'none',
                                                color: '#ffffff',
                                                fontSize: '15px',
                                                fontWeight: 600,
                                                cursor: 'pointer',
                                            }}
                                        >
                                            See how it works
                                        </button>
                                    </div>
                                </div>
                                <div
                                    style={{
                                        flex: '1 1 380px',
                                        minWidth: 0,
                                        maxWidth: '460px',
                                        margin: '0 auto',
                                        animation: 'rzb-up .7s ease .15s both',
                                    }}
                                >
                                    <EarnCard />
                                </div>
                            </div>
                        </section>

                        <section
                            style={{
                                ...WRAP,
                                padding: `clamp(56px,8vw,104px) ${PAD_X} 0`,
                            }}
                        >
                            <div
                                style={{
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    justifyContent: 'space-between',
                                    alignItems: 'flex-end',
                                    gap: '16px',
                                }}
                            >
                                <h2 style={{ ...H2_BIG, maxWidth: '720px' }}>
                                    The businesses you already love.{' '}
                                    <span style={{ color: '#1e3aff' }}>
                                        Now you can back their growth, and get
                                        paid for it.
                                    </span>
                                </h2>
                                <p
                                    style={{
                                        margin: 0,
                                        maxWidth: '340px',
                                        fontSize: '15.5px',
                                        lineHeight: 1.6,
                                        color: '#69748a',
                                        textWrap: 'pretty',
                                    }}
                                >
                                    Profitable Rwandan businesses across every
                                    sector, raising to grow, and paying you back
                                    every month.
                                </p>
                            </div>
                            <div
                                style={{
                                    marginTop: 'clamp(28px,4vw,48px)',
                                    display: 'grid',
                                    gridTemplateColumns:
                                        'repeat(auto-fit,minmax(max(130px, calc((100% - 60px) / 6)),1fr))',
                                    gap: '12px',
                                }}
                            >
                                {SECTOR_TILES.map(
                                    ([file, alt, caption, position]) => (
                                        <figure
                                            key={file}
                                            style={{
                                                position: 'relative',
                                                margin: 0,
                                                aspectRatio: '3/4',
                                                borderRadius: '22px',
                                                overflow: 'hidden',
                                                background: '#e7ecf4',
                                            }}
                                        >
                                            <img
                                                src={IMG + 'photos/' + file}
                                                alt={alt}
                                                loading="lazy"
                                                style={coverImg(position)}
                                            />
                                            <figcaption
                                                style={{
                                                    position: 'absolute',
                                                    left: 0,
                                                    right: 0,
                                                    bottom: 0,
                                                    padding: '48px 16px 14px',
                                                    background:
                                                        'linear-gradient(to top, rgba(12,24,48,.85), rgba(12,24,48,0))',
                                                    color: '#ffffff',
                                                    fontSize: '15px',
                                                    fontWeight: 700,
                                                    letterSpacing: '-.01em',
                                                }}
                                            >
                                                {caption}
                                            </figcaption>
                                        </figure>
                                    ),
                                )}
                            </div>
                        </section>

                        <section
                            style={{
                                ...WRAP,
                                padding: `${SECTION_TOP} ${PAD_X} 0`,
                            }}
                        >
                            <h2
                                style={{
                                    margin: 0,
                                    maxWidth: '900px',
                                    fontSize: 'clamp(36px,5.6vw,76px)',
                                    lineHeight: 0.95,
                                    fontWeight: 900,
                                    letterSpacing: '-.055em',
                                    textWrap: 'balance',
                                }}
                            >
                                Four taps from your phone{' '}
                                <span style={{ color: '#1e3aff' }}>
                                    to a growing business.
                                </span>
                            </h2>
                            <div
                                style={{
                                    marginTop: 'clamp(36px,5vw,64px)',
                                    display: 'grid',
                                    gridTemplateColumns:
                                        'repeat(auto-fit,minmax(240px,1fr))',
                                    gap: '16px',
                                }}
                            >
                                {FOUR_TAPS.map(([title, body], index) => {
                                    const lit = index === 2;

                                    return (
                                        <div
                                            key={title}
                                            style={{
                                                background: lit
                                                    ? '#1e3aff'
                                                    : '#f3f5ff',
                                                color: lit
                                                    ? '#ffffff'
                                                    : undefined,
                                                borderRadius: '24px',
                                                padding: '26px',
                                                display: 'flex',
                                                flexDirection: 'column',
                                                gap: '12px',
                                                minHeight: '240px',
                                            }}
                                        >
                                            <span
                                                style={{
                                                    width: '44px',
                                                    height: '44px',
                                                    borderRadius: '14px',
                                                    background: lit
                                                        ? '#ffffff'
                                                        : '#1e3aff',
                                                    color: lit
                                                        ? '#1e3aff'
                                                        : '#ffffff',
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    justifyContent: 'center',
                                                    fontSize: '18px',
                                                    fontWeight: 900,
                                                }}
                                            >
                                                {index + 1}
                                            </span>
                                            <h3
                                                style={{
                                                    margin: 'auto 0 0',
                                                    fontSize: '22px',
                                                    fontWeight: 800,
                                                    letterSpacing: '-.03em',
                                                }}
                                            >
                                                {title}
                                            </h3>
                                            <p
                                                style={{
                                                    margin: 0,
                                                    fontSize: '15px',
                                                    lineHeight: 1.6,
                                                    color: lit
                                                        ? '#dfe4ff'
                                                        : '#3a465e',
                                                    textWrap: 'pretty',
                                                }}
                                            >
                                                {body}
                                            </p>
                                        </div>
                                    );
                                })}
                            </div>
                        </section>

                        <section
                            style={{
                                ...WRAP,
                                padding: `${SECTION_TOP} ${PAD_X} 0`,
                            }}
                        >
                            <span style={kicker('#1e3aff')}>
                                INSIDE THE APP
                            </span>
                            <h2
                                style={{
                                    ...H2_BIG,
                                    margin: '14px 0 0',
                                    maxWidth: '820px',
                                }}
                            >
                                Pick every business yourself. Or set your rules
                                once.
                            </h2>
                            <div
                                style={{
                                    marginTop: 'clamp(32px,4.5vw,56px)',
                                    display: 'grid',
                                    gridTemplateColumns:
                                        'repeat(auto-fit,minmax(320px,1fr))',
                                    gap: '16px',
                                }}
                            >
                                <AppCard
                                    dark={false}
                                    label="YOU CHOOSE"
                                    title="See the numbers, then decide."
                                    image="app-deals.png"
                                    alt="The Rozine app showing a business raising"
                                    pins={[
                                        ['10%', '1.5%'],
                                        ['19%', '29%'],
                                        ['40.5%', '1.5%'],
                                        ['75.5%', '1.5%'],
                                    ]}
                                    notes={[
                                        [
                                            'Your wallet and your fee.',
                                            'The fee on your returns drops as your portfolio grows.',
                                        ],
                                        [
                                            'Verified and rated.',
                                            'An accountant has checked this business in person before you ever see it.',
                                        ],
                                        [
                                            'The raise, live.',
                                            'How much is filled, time left, and how many investors are in.',
                                        ],
                                        [
                                            'Invest + return = get back.',
                                            'Slide your amount and see the exact figure before you commit.',
                                        ],
                                    ]}
                                />
                                <AppCard
                                    dark
                                    label="ON AUTOPILOT"
                                    title="Set your rules. Rozine invests for you."
                                    image="app-automation.png"
                                    alt="The Rozine app showing automatic investing rules"
                                    pins={[
                                        ['23%', '1.5%'],
                                        ['39%', '1.5%'],
                                        ['49.2%', '44.5%'],
                                        ['79.5%', '1.5%'],
                                    ]}
                                    notes={[
                                        [
                                            'How much each month.',
                                            'The amount Rozine puts to work for you.',
                                        ],
                                        [
                                            'The most per business.',
                                            'So no single business ever carries too much of your money.',
                                        ],
                                        [
                                            'What to expect.',
                                            "Roughly how many businesses you'll back, and the fee you'll pay.",
                                        ],
                                        [
                                            'New raises, resale, or both.',
                                            'Back new businesses first, or buy notes from investors selling early.',
                                        ],
                                    ]}
                                />
                            </div>
                        </section>

                        <section
                            style={{
                                ...WRAP,
                                padding: `${SECTION_TOP} ${PAD_X} 0`,
                                display: 'flex',
                                flexWrap: 'wrap',
                                gap: 'clamp(32px,5vw,72px)',
                                alignItems: 'center',
                            }}
                        >
                            <div
                                style={{
                                    flex: '1 1 400px',
                                    minWidth: 0,
                                    position: 'relative',
                                    borderRadius: '28px',
                                    overflow: 'hidden',
                                    aspectRatio: '4/5',
                                    maxHeight: '640px',
                                    background: '#e7ecf4',
                                }}
                            >
                                <img
                                    src={IMG + 'photos/owner-w.jpg'}
                                    alt="A smiling shop owner"
                                    loading="lazy"
                                    style={coverImg('48% center')}
                                />
                                <div
                                    style={{
                                        position: 'absolute',
                                        left: '16px',
                                        bottom: '16px',
                                        right: '16px',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '12px',
                                        background: 'rgba(255,255,255,.94)',
                                        borderRadius: '18px',
                                        padding: '12px 14px',
                                    }}
                                >
                                    <span
                                        style={{
                                            width: '38px',
                                            height: '38px',
                                            flexShrink: 0,
                                            borderRadius: '12px',
                                            background: 'rgba(29,158,117,.14)',
                                            color: '#17795a',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontSize: '18px',
                                            fontWeight: 900,
                                        }}
                                    >
                                        ✓
                                    </span>
                                    <div
                                        style={{
                                            display: 'flex',
                                            flexDirection: 'column',
                                            gap: '1px',
                                            minWidth: 0,
                                        }}
                                    >
                                        <span
                                            style={{
                                                fontSize: '14px',
                                                fontWeight: 800,
                                                color: '#0c1830',
                                            }}
                                        >
                                            November report sealed
                                        </span>
                                        <span
                                            style={{
                                                fontSize: '12.5px',
                                                color: '#69748a',
                                            }}
                                        >
                                            Revenue up 8% · paid on time
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div
                                style={{
                                    flex: '1.1 1 440px',
                                    minWidth: 0,
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: '18px',
                                }}
                            >
                                <span style={kicker('#1e3aff')}>
                                    WHY ROZINE
                                </span>
                                <h2
                                    style={{
                                        margin: 0,
                                        fontSize: 'clamp(34px,4.6vw,60px)',
                                        lineHeight: 0.96,
                                        fontWeight: 900,
                                        letterSpacing: '-.05em',
                                        textWrap: 'balance',
                                    }}
                                >
                                    Only proven businesses make the cut.
                                </h2>
                                <p
                                    style={{
                                        margin: 0,
                                        maxWidth: '520px',
                                        fontSize: '16px',
                                        lineHeight: 1.65,
                                        color: '#69748a',
                                        textWrap: 'pretty',
                                    }}
                                >
                                    Profitable, verified in person by a licensed
                                    accountant, and borrowing only what their
                                    cash comfortably covers. Then checked again,
                                    every month you're invested.
                                </p>
                                <div
                                    style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        marginTop: '6px',
                                    }}
                                >
                                    {WHY_ROWS.map(([text, tag], index) => (
                                        <div
                                            key={tag}
                                            style={{
                                                display: 'grid',
                                                gridTemplateColumns:
                                                    'minmax(0,1fr) auto',
                                                gap: '16px',
                                                alignItems: 'baseline',
                                                padding: '20px 0',
                                                borderTop:
                                                    index === 0
                                                        ? '2px solid #0c1830'
                                                        : '1px solid #e7ecf4',
                                                borderBottom:
                                                    index ===
                                                    WHY_ROWS.length - 1
                                                        ? '1px solid #e7ecf4'
                                                        : undefined,
                                            }}
                                        >
                                            <span
                                                style={{
                                                    fontSize:
                                                        'clamp(18px,1.7vw,22px)',
                                                    fontWeight: 700,
                                                    letterSpacing: '-.02em',
                                                }}
                                            >
                                                {text}
                                            </span>
                                            <span
                                                style={{
                                                    fontSize: '13px',
                                                    fontWeight: 800,
                                                    color: '#1e3aff',
                                                }}
                                            >
                                                {tag}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                                <button
                                    type="button"
                                    onClick={() => go('how')}
                                    style={{
                                        alignSelf: 'flex-start',
                                        background: 'none',
                                        border: 'none',
                                        padding: 0,
                                        color: '#1e3aff',
                                        fontSize: '15px',
                                        fontWeight: 700,
                                        cursor: 'pointer',
                                    }}
                                >
                                    See how it all works →
                                </button>
                            </div>
                        </section>

                        <section
                            style={{
                                marginTop: SECTION_TOP,
                                background: '#1d9e75',
                                color: '#ffffff',
                            }}
                        >
                            <div
                                style={{
                                    ...WRAP,
                                    padding: `clamp(48px,7vw,88px) ${PAD_X}`,
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    gap: 'clamp(28px,5vw,64px)',
                                    alignItems: 'center',
                                }}
                            >
                                <div
                                    style={{
                                        flex: '1.2 1 420px',
                                        minWidth: 0,
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '16px',
                                    }}
                                >
                                    <span style={kicker('#d4f2e6')}>
                                        FOR BUSINESSES
                                    </span>
                                    <h2 style={H2_BIG}>
                                        Growth capital without collateral.
                                    </h2>
                                    <p
                                        style={{
                                            margin: 0,
                                            maxWidth: '520px',
                                            fontSize: '16.5px',
                                            lineHeight: 1.6,
                                            color: '#e7f8f1',
                                            textWrap: 'pretty',
                                        }}
                                    >
                                        Raise RWF 3M to RWF 100M from Rwandan
                                        investors. Repay over 3 to 6 months,
                                        then raise again, bigger.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() => go('biz')}
                                        style={{
                                            alignSelf: 'flex-start',
                                            marginTop: '6px',
                                            display: 'flex',
                                            alignItems: 'center',
                                            height: '52px',
                                            padding: '0 24px',
                                            borderRadius: '999px',
                                            border: 'none',
                                            background: '#ffffff',
                                            color: '#17795a',
                                            fontSize: '15px',
                                            fontWeight: 800,
                                            cursor: 'pointer',
                                        }}
                                    >
                                        See what you could raise →
                                    </button>
                                </div>
                                <div
                                    style={{
                                        flex: '1 1 360px',
                                        minWidth: 0,
                                        position: 'relative',
                                        borderRadius: '28px',
                                        overflow: 'hidden',
                                        aspectRatio: '4/5',
                                        maxHeight: '560px',
                                        background: '#17795a',
                                        display: 'flex',
                                        flexDirection: 'column',
                                        justifyContent: 'flex-end',
                                        padding: '14px',
                                    }}
                                >
                                    <img
                                        src={IMG + 'photos/studio-bright-w.jpg'}
                                        alt="A fashion designer at work in her studio"
                                        loading="lazy"
                                        style={coverImg('35% 50%')}
                                    />
                                    <div
                                        style={{
                                            position: 'relative',
                                            display: 'grid',
                                            gridTemplateColumns:
                                                'repeat(2,minmax(0,1fr))',
                                            gap: '8px',
                                        }}
                                    >
                                        {BIZ_STATS.map(([figure, text]) => (
                                            <div
                                                key={text}
                                                style={{
                                                    background:
                                                        'rgba(12,40,30,.62)',
                                                    backdropFilter: 'blur(8px)',
                                                    WebkitBackdropFilter:
                                                        'blur(8px)',
                                                    borderRadius: '18px',
                                                    padding: '14px 16px',
                                                    display: 'flex',
                                                    flexDirection: 'column',
                                                    gap: '4px',
                                                }}
                                            >
                                                <span
                                                    style={{
                                                        fontSize: '24px',
                                                        fontWeight: 900,
                                                        letterSpacing: '-.04em',
                                                    }}
                                                >
                                                    {figure}
                                                </span>
                                                <span
                                                    style={{
                                                        fontSize: '13.5px',
                                                        color: '#e7f8f1',
                                                    }}
                                                >
                                                    {text}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </section>
                    </>
                )}

                {page === 'biz' && (
                    <>
                        <section
                            style={{
                                position: 'relative',
                                overflow: 'hidden',
                                background: '#1d9e75',
                                color: '#ffffff',
                            }}
                        >
                            <div
                                aria-hidden="true"
                                style={{
                                    position: 'absolute',
                                    top: '-35%',
                                    right: '-12%',
                                    width: 'min(860px,90vw)',
                                    aspectRatio: '1',
                                    borderRadius: '50%',
                                    background:
                                        'radial-gradient(circle, rgba(48,196,145,.8) 0%, rgba(48,196,145,0) 62%)',
                                }}
                            />
                            <div
                                style={{
                                    position: 'relative',
                                    ...WRAP,
                                    padding: `clamp(36px,6vw,84px) ${PAD_X} clamp(56px,8vw,112px)`,
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    gap: 'clamp(36px,5vw,72px)',
                                    alignItems: 'center',
                                }}
                            >
                                <div
                                    style={{
                                        flex: '1.15 1 460px',
                                        minWidth: 0,
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '26px',
                                        animation: 'rzb-up .7s ease both',
                                    }}
                                >
                                    <span
                                        style={HERO_BADGE(
                                            'rgba(255,255,255,.14)',
                                            'rgba(255,255,255,.26)',
                                        )}
                                    >
                                        For Rwandan businesses
                                    </span>
                                    <h1 style={H1}>
                                        Raise up to RWF 100M. No collateral
                                        needed.
                                    </h1>
                                    <p
                                        style={{
                                            margin: 0,
                                            maxWidth: '560px',
                                            fontSize: 'clamp(17px,1.5vw,20px)',
                                            lineHeight: 1.55,
                                            color: '#e7f8f1',
                                            textWrap: 'pretty',
                                        }}
                                    >
                                        Rwandan investors fund your growth on
                                        your verified numbers, not your
                                        collateral. Money in 30 days, repaid
                                        over 3 to 6 months.
                                    </p>
                                    <a
                                        href="#join"
                                        style={{
                                            alignSelf: 'flex-start',
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: '10px',
                                            height: '56px',
                                            padding: '0 28px',
                                            borderRadius: '999px',
                                            background: '#ffffff',
                                            color: '#17795a',
                                            fontSize: '16px',
                                            fontWeight: 800,
                                        }}
                                    >
                                        Join the business waitlist{' '}
                                        <span>→</span>
                                    </a>
                                </div>
                                <div
                                    style={{
                                        flex: '1 1 380px',
                                        minWidth: 0,
                                        maxWidth: '460px',
                                        margin: '0 auto',
                                        animation: 'rzb-up .7s ease .15s both',
                                    }}
                                >
                                    <RaiseCard />
                                </div>
                            </div>
                        </section>

                        <section
                            style={{
                                ...WRAP,
                                padding: `${SECTION_TOP} ${PAD_X} 0`,
                            }}
                        >
                            <span style={kicker('#17795a')}>WHO QUALIFIES</span>
                            <h2
                                style={{
                                    ...H2_BIG,
                                    margin: '14px 0 0',
                                    maxWidth: '820px',
                                }}
                            >
                                Built for businesses that already work.
                            </h2>
                            <div
                                style={{
                                    marginTop: 'clamp(28px,4vw,48px)',
                                    display: 'grid',
                                    gridTemplateColumns: '1.6fr 1fr 1fr 1fr',
                                    gap: '12px',
                                    height: 'clamp(220px,30vw,380px)',
                                }}
                            >
                                {QUALIFY_PHOTOS.map(([file, alt, position]) => (
                                    <div key={file} style={photoBox()}>
                                        <img
                                            src={IMG + 'photos/' + file}
                                            alt={alt}
                                            loading="lazy"
                                            style={coverImg(position)}
                                        />
                                    </div>
                                ))}
                            </div>
                            <div
                                style={{
                                    marginTop: 'clamp(32px,4.5vw,56px)',
                                    display: 'grid',
                                    gridTemplateColumns:
                                        'repeat(auto-fit,minmax(240px,1fr))',
                                    gap: '16px',
                                }}
                            >
                                {QUALIFY_CARDS.map(([mark, title, body]) => (
                                    <div
                                        key={title}
                                        style={{
                                            background: '#f2faf6',
                                            borderRadius: '24px',
                                            padding: '26px',
                                            display: 'flex',
                                            flexDirection: 'column',
                                            gap: '10px',
                                        }}
                                    >
                                        <span
                                            style={{
                                                fontSize: '30px',
                                                fontWeight: 900,
                                                letterSpacing: '-.04em',
                                                color: '#17795a',
                                            }}
                                        >
                                            {mark}
                                        </span>
                                        <h3
                                            style={{
                                                margin: 0,
                                                fontSize: '20px',
                                                fontWeight: 800,
                                                letterSpacing: '-.025em',
                                            }}
                                        >
                                            {title}
                                        </h3>
                                        <p
                                            style={{
                                                margin: 0,
                                                fontSize: '15px',
                                                lineHeight: 1.6,
                                                color: '#3a465e',
                                            }}
                                        >
                                            {body}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <StepsSection
                            label="HOW IT WORKS"
                            labelColor="#17795a"
                            title="From first visit to funded in weeks."
                            photo="photos/tailor-w.jpg"
                            photoAlt="A designer fitting a client in her studio"
                            photoPosition="40% 40%"
                            steps={BIZ_STEPS}
                            stepColor="#1d9e75"
                        />

                        <section
                            style={{
                                ...WRAP,
                                margin: `${SECTION_TOP} auto 0`,
                                padding: `0 ${PAD_X}`,
                            }}
                        >
                            <div
                                style={{
                                    background: '#0c1830',
                                    color: '#ffffff',
                                    borderRadius: '28px',
                                    padding: 'clamp(28px,5vw,60px)',
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    gap: 'clamp(28px,5vw,64px)',
                                }}
                            >
                                <div
                                    style={{
                                        flex: '1 1 340px',
                                        minWidth: 0,
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '16px',
                                    }}
                                >
                                    <span style={kicker('#6fd7b1')}>
                                        SIMPLE PRICING
                                    </span>
                                    <h2
                                        style={{
                                            margin: 0,
                                            fontSize: 'clamp(30px,4vw,52px)',
                                            lineHeight: 0.98,
                                            fontWeight: 900,
                                            letterSpacing: '-.05em',
                                            textWrap: 'balance',
                                        }}
                                    >
                                        One rate. One fee. No surprises.
                                    </h2>
                                    <p
                                        style={{
                                            margin: 0,
                                            fontSize: '15.5px',
                                            lineHeight: 1.65,
                                            color: '#b9c2d6',
                                            textWrap: 'pretty',
                                        }}
                                    >
                                        Your return to investors is set from
                                        your verified rating and term: 12.5% to
                                        15% of the amount, for the whole term,
                                        never per year. Rozine charges a
                                        one-time 3% platform fee when your raise
                                        is funded. Stronger businesses pay less.
                                    </p>
                                </div>
                                <div
                                    style={{
                                        flex: '1 1 380px',
                                        minWidth: 0,
                                        background: 'rgba(255,255,255,.06)',
                                        border: '1px solid rgba(255,255,255,.12)',
                                        borderRadius: '22px',
                                        padding: '22px',
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '10px',
                                    }}
                                >
                                    <span
                                        style={{
                                            fontSize: '11px',
                                            fontWeight: 700,
                                            letterSpacing: '.08em',
                                            color: '#8894a8',
                                        }}
                                    >
                                        EXAMPLE · RWF 20M OVER 6 MONTHS AT 14%
                                    </span>
                                    {(
                                        [
                                            ['You raise', 'RWF 20,000,000'],
                                            [
                                                'Return to investors (14%)',
                                                'RWF 2,800,000',
                                            ],
                                            [
                                                'Platform fee (3%, once)',
                                                'RWF 600,000',
                                            ],
                                        ] as const
                                    ).map(([name, value]) => (
                                        <div
                                            key={name}
                                            style={{
                                                display: 'flex',
                                                justifyContent: 'space-between',
                                                fontSize: '15px',
                                                color: '#dfe4ef',
                                            }}
                                        >
                                            <span>{name}</span>
                                            <span style={{ fontWeight: 700 }}>
                                                {value}
                                            </span>
                                        </div>
                                    ))}
                                    <div
                                        style={{
                                            height: '1px',
                                            background: 'rgba(255,255,255,.14)',
                                            margin: '6px 0',
                                        }}
                                    />
                                    <div
                                        style={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'baseline',
                                        }}
                                    >
                                        <span
                                            style={{
                                                fontSize: '15px',
                                                fontWeight: 700,
                                            }}
                                        >
                                            Monthly repayment
                                        </span>
                                        <span
                                            style={{
                                                fontSize: '28px',
                                                fontWeight: 900,
                                                letterSpacing: '-.04em',
                                                color: '#6fd7b1',
                                            }}
                                        >
                                            RWF 3,800,000
                                        </span>
                                    </div>
                                    <span
                                        style={{
                                            fontSize: '12.5px',
                                            color: '#8894a8',
                                        }}
                                    >
                                        Six equal instalments. Nothing grows,
                                        nothing compounds.
                                    </span>
                                </div>
                            </div>
                        </section>

                        <section
                            style={{
                                ...WRAP,
                                padding: `${SECTION_TOP} ${PAD_X} 0`,
                                display: 'grid',
                                gridTemplateColumns:
                                    'repeat(auto-fit,minmax(320px,1fr))',
                                gap: '16px',
                            }}
                        >
                            {(
                                [
                                    [
                                        'grow-cafe.jpg',
                                        'A café owner serving a customer',
                                        '35% 40%',
                                        'GROW WITH US',
                                        'Repay on time, raise bigger.',
                                        'After a clean repayment you can raise again straight away, up to 1.5 times your last loan. Every loan you repay builds a public track record investors can see.',
                                    ],
                                    [
                                        'laptop-w.jpg',
                                        'Two business owners reviewing their numbers on a laptop',
                                        '50% 35%',
                                        'BE INVESTMENT READY',
                                        'Grow into an investable business.',
                                        'Every month, the app shows you what to fix next, based on your own verified numbers, with short lessons to help. Stronger books mean bigger raises.',
                                    ],
                                ] as const
                            ).map(
                                ([file, alt, position, label, title, body]) => (
                                    <div
                                        key={label}
                                        style={{
                                            background: '#f2faf6',
                                            borderRadius: '24px',
                                            padding: 'clamp(24px,3vw,34px)',
                                            display: 'flex',
                                            flexDirection: 'column',
                                            gap: '12px',
                                        }}
                                    >
                                        <div
                                            style={{
                                                position: 'relative',
                                                margin: 'calc(-1 * clamp(24px,3vw,34px)) calc(-1 * clamp(24px,3vw,34px)) 8px',
                                                height: 'clamp(180px,18vw,240px)',
                                                overflow: 'hidden',
                                                borderRadius: '24px 24px 0 0',
                                                background: '#e7ecf4',
                                            }}
                                        >
                                            <img
                                                src={IMG + 'photos/' + file}
                                                alt={alt}
                                                loading="lazy"
                                                style={coverImg(position)}
                                            />
                                        </div>
                                        <span style={kicker('#17795a')}>
                                            {label}
                                        </span>
                                        <h3
                                            style={{
                                                margin: 0,
                                                fontSize:
                                                    'clamp(24px,2.6vw,32px)',
                                                lineHeight: 1.05,
                                                fontWeight: 900,
                                                letterSpacing: '-.04em',
                                            }}
                                        >
                                            {title}
                                        </h3>
                                        <p
                                            style={{
                                                margin: 0,
                                                fontSize: '15px',
                                                lineHeight: 1.65,
                                                color: '#3a465e',
                                                textWrap: 'pretty',
                                            }}
                                        >
                                            {body}
                                        </p>
                                    </div>
                                ),
                            )}
                        </section>

                        <section
                            style={{
                                ...WRAP,
                                padding: `${SECTION_TOP} ${PAD_X} 0`,
                                display: 'flex',
                                flexWrap: 'wrap',
                                gap: 'clamp(28px,5vw,72px)',
                            }}
                        >
                            <div style={{ flex: '1 1 300px', minWidth: 0 }}>
                                <h2
                                    style={{
                                        margin: 0,
                                        fontSize: 'clamp(34px,4.6vw,60px)',
                                        lineHeight: 0.96,
                                        fontWeight: 900,
                                        letterSpacing: '-.05em',
                                    }}
                                >
                                    Questions businesses ask.
                                </h2>
                                <div
                                    style={photoBox({
                                        marginTop: '28px',
                                        aspectRatio: '4/5',
                                        maxHeight: '520px',
                                    })}
                                >
                                    <img
                                        src={IMG + 'photos/faq-baker.jpg'}
                                        alt="A smiling baker at his counter"
                                        loading="lazy"
                                        style={coverImg('62% 40%')}
                                    />
                                    <div
                                        style={{
                                            position: 'absolute',
                                            left: '14px',
                                            right: '14px',
                                            bottom: '14px',
                                            display: 'flex',
                                            flexDirection: 'column',
                                            gap: '8px',
                                        }}
                                    >
                                        <span
                                            style={{
                                                alignSelf: 'flex-start',
                                                maxWidth: '80%',
                                                background: '#ffffff',
                                                color: '#0c1830',
                                                fontSize: '14px',
                                                fontWeight: 700,
                                                padding: '10px 14px',
                                                borderRadius:
                                                    '16px 16px 16px 4px',
                                                boxShadow:
                                                    '0 12px 28px -14px rgba(12,24,48,.55)',
                                            }}
                                        >
                                            Can I borrow again?
                                        </span>
                                        <span
                                            style={{
                                                alignSelf: 'flex-end',
                                                maxWidth: '85%',
                                                background: '#1d9e75',
                                                color: '#ffffff',
                                                fontSize: '14px',
                                                fontWeight: 700,
                                                padding: '10px 14px',
                                                borderRadius:
                                                    '16px 16px 4px 16px',
                                                boxShadow:
                                                    '0 12px 28px -14px rgba(12,24,48,.55)',
                                            }}
                                        >
                                            Yes. Up to 1.5× your last loan.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div
                                style={{
                                    flex: '1.4 1 480px',
                                    minWidth: 0,
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: '10px',
                                }}
                            >
                                <FaqList
                                    faqs={BIZ_FAQS}
                                    open={bizFaq}
                                    onToggle={setBizFaq}
                                    openBg="#e7f4ee"
                                    signColor="#17795a"
                                    answerWidth="640px"
                                />
                            </div>
                        </section>
                    </>
                )}

                {page === 'how' && (
                    <>
                        <section
                            style={{
                                position: 'relative',
                                overflow: 'hidden',
                                background: '#0c1830',
                                color: '#ffffff',
                            }}
                        >
                            <div
                                aria-hidden="true"
                                style={{
                                    position: 'absolute',
                                    top: '-40%',
                                    left: '40%',
                                    width: 'min(900px,90vw)',
                                    aspectRatio: '1',
                                    borderRadius: '50%',
                                    background:
                                        'radial-gradient(circle, rgba(30,58,255,.55) 0%, rgba(30,58,255,0) 62%)',
                                }}
                            />
                            <div
                                style={{
                                    position: 'relative',
                                    ...WRAP,
                                    padding: `clamp(40px,7vw,96px) ${PAD_X} clamp(56px,8vw,104px)`,
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: '24px',
                                    animation: 'rzb-up .7s ease both',
                                }}
                            >
                                <span
                                    style={HERO_BADGE(
                                        'rgba(255,255,255,.1)',
                                        'rgba(255,255,255,.2)',
                                    )}
                                >
                                    How it works &amp; protection
                                </span>
                                <h1
                                    style={{
                                        ...H1,
                                        maxWidth: '980px',
                                        fontSize: 'clamp(44px,7vw,96px)',
                                    }}
                                >
                                    Every franc, followed from your phone and
                                    back.
                                </h1>
                                <p
                                    style={{
                                        margin: 0,
                                        maxWidth: '620px',
                                        fontSize: 'clamp(17px,1.5vw,20px)',
                                        lineHeight: 1.55,
                                        color: '#b9c2d6',
                                        textWrap: 'pretty',
                                    }}
                                >
                                    What happens to your money, who checks what,
                                    what you pay, and what happens if a business
                                    pays late. No small print.
                                </p>
                            </div>
                        </section>

                        <section
                            style={{
                                ...WRAP,
                                padding: `clamp(40px,6vw,72px) ${PAD_X} 0`,
                            }}
                        >
                            <div
                                style={{
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    justifyContent: 'space-between',
                                    alignItems: 'flex-end',
                                    gap: '12px 32px',
                                }}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '10px',
                                        maxWidth: '640px',
                                    }}
                                >
                                    <span style={kicker('#1e3aff')}>
                                        WHERE YOUR MONEY GOES
                                    </span>
                                    <h2
                                        style={{
                                            margin: 0,
                                            fontSize: 'clamp(28px,3.6vw,46px)',
                                            lineHeight: 1,
                                            fontWeight: 900,
                                            letterSpacing: '-.045em',
                                            textWrap: 'balance',
                                        }}
                                    >
                                        It buys what makes a business earn more.
                                    </h2>
                                </div>
                                <p
                                    style={{
                                        margin: 0,
                                        maxWidth: '380px',
                                        fontSize: '15.5px',
                                        lineHeight: 1.6,
                                        color: '#69748a',
                                        textWrap: 'pretty',
                                    }}
                                >
                                    Each business pays you back from what that
                                    growth earns, in equal monthly instalments.
                                </p>
                            </div>
                            <div
                                style={{
                                    marginTop: 'clamp(22px,3vw,34px)',
                                    overflow: 'hidden',
                                    borderRadius: '22px',
                                    maskImage:
                                        'linear-gradient(to right, transparent, #000 4%, #000 96%, transparent)',
                                    WebkitMaskImage:
                                        'linear-gradient(to right, transparent, #000 4%, #000 96%, transparent)',
                                }}
                            >
                                <div
                                    className="rz-roll"
                                    style={{
                                        display: 'flex',
                                        gap: '12px',
                                        width: 'max-content',
                                        animation:
                                            'rzb-roll 80s linear infinite',
                                    }}
                                >
                                    {[...MONEY_TILES, ...MONEY_TILES].map(
                                        ([file, caption, position], index) => {
                                            const copy =
                                                index >= MONEY_TILES.length;

                                            return (
                                                <figure
                                                    key={index}
                                                    aria-hidden={
                                                        copy || undefined
                                                    }
                                                    style={{
                                                        position: 'relative',
                                                        flex: '0 0 auto',
                                                        width: 'clamp(210px,22vw,270px)',
                                                        margin: 0,
                                                        aspectRatio: '3/4',
                                                        borderRadius: '22px',
                                                        overflow: 'hidden',
                                                        background: '#e7ecf4',
                                                    }}
                                                >
                                                    <img
                                                        src={
                                                            IMG +
                                                            'photos/' +
                                                            file
                                                        }
                                                        alt={
                                                            copy ? '' : caption
                                                        }
                                                        loading="lazy"
                                                        style={coverImg(
                                                            position,
                                                        )}
                                                    />
                                                    <figcaption
                                                        style={{
                                                            position:
                                                                'absolute',
                                                            left: '12px',
                                                            bottom: '12px',
                                                            maxWidth:
                                                                'calc(100% - 24px)',
                                                            background:
                                                                'rgba(255,255,255,.94)',
                                                            color: '#0c1830',
                                                            fontSize: '13px',
                                                            fontWeight: 800,
                                                            padding: '7px 12px',
                                                            borderRadius:
                                                                '999px',
                                                            whiteSpace:
                                                                'nowrap',
                                                            overflow: 'hidden',
                                                            textOverflow:
                                                                'ellipsis',
                                                        }}
                                                    >
                                                        {caption}
                                                    </figcaption>
                                                </figure>
                                            );
                                        },
                                    )}
                                </div>
                            </div>
                        </section>

                        <StepsSection
                            label="THE JOURNEY OF YOUR MONEY"
                            labelColor="#1e3aff"
                            title="Six steps. You see every one."
                            photo="photos/m-bar.jpg"
                            photoAlt="A bar owner leaning on her counter"
                            photoPosition="30% 50%"
                            steps={JOURNEY}
                            stepColor="#1e3aff"
                        />

                        <section
                            style={{
                                ...WRAP,
                                padding: `${SECTION_TOP} ${PAD_X} 0`,
                            }}
                        >
                            <span style={kicker('#1e3aff')}>RATINGS</span>
                            <h2
                                style={{
                                    ...H2_BIG,
                                    margin: '14px 0 0',
                                    maxWidth: '820px',
                                    fontSize: 'clamp(34px,5vw,62px)',
                                }}
                            >
                                Three labels. One published formula.
                            </h2>
                            <p
                                style={{
                                    margin: '18px 0 0',
                                    maxWidth: '640px',
                                    fontSize: '16px',
                                    lineHeight: 1.65,
                                    color: '#69748a',
                                    textWrap: 'pretty',
                                }}
                            >
                                Each business gets a label from verified facts:
                                how long it has traded, how profitable and how
                                consistent it has been, and its size. It
                                describes the business. It is not a
                                recommendation.
                            </p>
                            <div
                                style={{
                                    marginTop: 'clamp(28px,4vw,44px)',
                                    display: 'grid',
                                    gridTemplateColumns:
                                        'repeat(auto-fit,minmax(240px,1fr))',
                                    gap: '14px',
                                }}
                            >
                                {RATING_CARDS.map(
                                    ([name, tint, color, body]) => (
                                        <div
                                            key={name}
                                            style={{
                                                borderRadius: '22px',
                                                padding: '24px',
                                                background: tint,
                                                display: 'flex',
                                                flexDirection: 'column',
                                                gap: '8px',
                                            }}
                                        >
                                            <span
                                                style={{
                                                    alignSelf: 'flex-start',
                                                    fontSize: '12px',
                                                    fontWeight: 800,
                                                    letterSpacing: '.06em',
                                                    padding: '5px 11px',
                                                    borderRadius: '999px',
                                                    background: color,
                                                    color: '#ffffff',
                                                }}
                                            >
                                                {name}
                                            </span>
                                            <p
                                                style={{
                                                    margin: 0,
                                                    fontSize: '15px',
                                                    lineHeight: 1.6,
                                                    color: '#0c1830',
                                                }}
                                            >
                                                {body}
                                            </p>
                                        </div>
                                    ),
                                )}
                            </div>
                        </section>

                        <section
                            style={{
                                ...WRAP,
                                margin: `${SECTION_TOP} auto 0`,
                                padding: `0 ${PAD_X}`,
                            }}
                        >
                            <div
                                style={{
                                    background: '#0c1830',
                                    color: '#ffffff',
                                    borderRadius: '28px',
                                    padding: 'clamp(28px,5vw,60px)',
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: 'clamp(24px,3vw,36px)',
                                }}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '14px',
                                        maxWidth: '720px',
                                    }}
                                >
                                    <span style={kicker('#8ea0ff')}>
                                        IF A BUSINESS PAYS LATE
                                    </span>
                                    <h2
                                        style={{
                                            margin: 0,
                                            fontSize: 'clamp(30px,4vw,52px)',
                                            lineHeight: 0.98,
                                            fontWeight: 900,
                                            letterSpacing: '-.05em',
                                            textWrap: 'balance',
                                        }}
                                    >
                                        Automatic, from the first day. Penalties
                                        go to you.
                                    </h2>
                                </div>
                                <div
                                    style={{
                                        display: 'grid',
                                        gridTemplateColumns:
                                            'repeat(auto-fit,minmax(200px,1fr))',
                                        gap: '1px',
                                        background: 'rgba(255,255,255,.12)',
                                        borderRadius: '18px',
                                        overflow: 'hidden',
                                    }}
                                >
                                    {LATE_DAYS.map(([day, text, color]) => (
                                        <div
                                            key={day}
                                            style={{
                                                background: '#0c1830',
                                                padding: '22px',
                                                display: 'flex',
                                                flexDirection: 'column',
                                                gap: '8px',
                                            }}
                                        >
                                            <span
                                                style={{
                                                    fontSize: '24px',
                                                    fontWeight: 900,
                                                    letterSpacing: '-.03em',
                                                    color,
                                                }}
                                            >
                                                {day}
                                            </span>
                                            <span
                                                style={{
                                                    fontSize: '14.5px',
                                                    lineHeight: 1.55,
                                                    color: '#b9c2d6',
                                                }}
                                            >
                                                {text}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                                <p
                                    style={{
                                        margin: 0,
                                        maxWidth: '720px',
                                        fontSize: '14.5px',
                                        lineHeight: 1.6,
                                        color: '#8894a8',
                                        textWrap: 'pretty',
                                    }}
                                >
                                    Every penalty is paid to the investors in
                                    that note. There is no guarantee fund: if a
                                    business cannot pay and recovery falls
                                    short, you can lose money.
                                </p>
                            </div>
                        </section>

                        <section
                            style={{
                                ...WRAP,
                                padding: `${SECTION_TOP} ${PAD_X} 0`,
                                display: 'grid',
                                gridTemplateColumns:
                                    'repeat(auto-fit,minmax(320px,1fr))',
                                gap: '16px',
                            }}
                        >
                            <div
                                style={{
                                    border: '1px solid #dbe2ee',
                                    borderRadius: '24px',
                                    padding: 'clamp(24px,3vw,34px)',
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: '14px',
                                }}
                            >
                                <span style={kicker('#1e3aff')}>
                                    WHAT YOU PAY
                                </span>
                                {FEES.map(([name, value], index) => (
                                    <div
                                        key={name}
                                        style={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            gap: '12px',
                                            padding: '12px 0',
                                            borderBottom:
                                                index < FEES.length - 1
                                                    ? '1px solid #e7ecf4'
                                                    : undefined,
                                            fontSize: '15.5px',
                                        }}
                                    >
                                        <span>{name}</span>
                                        <span style={{ fontWeight: 800 }}>
                                            {value}
                                        </span>
                                    </div>
                                ))}
                            </div>
                            <div
                                style={{
                                    border: '1px solid #dbe2ee',
                                    borderRadius: '24px',
                                    padding: 'clamp(24px,3vw,34px)',
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: '12px',
                                }}
                            >
                                <span style={kicker('#1e3aff')}>
                                    GETTING OUT EARLY
                                </span>
                                <h3
                                    style={{
                                        margin: 0,
                                        fontSize: 'clamp(22px,2.4vw,30px)',
                                        lineHeight: 1.08,
                                        fontWeight: 900,
                                        letterSpacing: '-.035em',
                                    }}
                                >
                                    Sell any time, to another investor.
                                </h3>
                                <p
                                    style={{
                                        margin: 0,
                                        fontSize: '15px',
                                        lineHeight: 1.65,
                                        color: '#3a465e',
                                        textWrap: 'pretty',
                                    }}
                                >
                                    List your note at your price. It can never
                                    be priced above what it still has to pay
                                    out, so buyers are protected. A sale happens
                                    when a buyer takes it, which isn't
                                    guaranteed.
                                </p>
                            </div>
                        </section>
                    </>
                )}

                {page === 'help' && (
                    <>
                        <section
                            style={{
                                position: 'relative',
                                overflow: 'hidden',
                                background: '#1428a4',
                                color: '#ffffff',
                            }}
                        >
                            <div
                                style={{
                                    position: 'relative',
                                    ...WRAP,
                                    padding: `clamp(40px,7vw,96px) ${PAD_X} clamp(48px,7vw,88px)`,
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: '22px',
                                    animation: 'rzb-up .7s ease both',
                                }}
                            >
                                <h1
                                    style={{
                                        ...H1,
                                        maxWidth: '900px',
                                        fontSize: 'clamp(44px,7vw,96px)',
                                    }}
                                >
                                    Every question, answered plainly.
                                </h1>
                                <p
                                    style={{
                                        margin: 0,
                                        maxWidth: '560px',
                                        fontSize: 'clamp(17px,1.5vw,20px)',
                                        lineHeight: 1.55,
                                        color: '#dfe4ff',
                                    }}
                                >
                                    Still have a question? Email us at&nbsp;
                                    <a
                                        href="mailto:admin@rozine.rw"
                                        style={{
                                            color: '#ffffff',
                                            fontWeight: 700,
                                            textDecoration: 'underline',
                                        }}
                                    >
                                        admin@rozine.rw
                                    </a>
                                    .
                                </p>
                                <div
                                    role="tablist"
                                    aria-label="Help topics"
                                    style={{
                                        display: 'flex',
                                        flexWrap: 'wrap',
                                        gap: '8px',
                                    }}
                                >
                                    {HELP_TABS.map(([key, label]) => {
                                        const on = key === helpTab;

                                        return (
                                            <button
                                                key={key}
                                                type="button"
                                                role="tab"
                                                aria-selected={on}
                                                onClick={() => {
                                                    setHelpTab(key);
                                                    setHelpFaq(0);
                                                }}
                                                style={{
                                                    height: '42px',
                                                    padding: '0 18px',
                                                    borderRadius: '999px',
                                                    border: `1px solid ${on ? '#ffffff' : 'rgba(255,255,255,.35)'}`,
                                                    background: on
                                                        ? '#ffffff'
                                                        : 'transparent',
                                                    color: on
                                                        ? '#1428a4'
                                                        : '#ffffff',
                                                    fontSize: '14px',
                                                    fontWeight: 700,
                                                    cursor: 'pointer',
                                                }}
                                            >
                                                {label}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        </section>
                        <section
                            role="tabpanel"
                            style={{
                                maxWidth: '900px',
                                margin: '0 auto',
                                padding: `clamp(48px,7vw,88px) ${PAD_X} 0`,
                                display: 'flex',
                                flexDirection: 'column',
                                gap: '10px',
                            }}
                        >
                            <FaqList
                                faqs={
                                    HELP_TABS.find(
                                        ([key]) => key === helpTab,
                                    )![2]
                                }
                                open={helpFaq}
                                onToggle={setHelpFaq}
                                openBg="#f3f5ff"
                                signColor="#1e3aff"
                                answerWidth="680px"
                            />
                        </section>
                    </>
                )}

                <section
                    id="join"
                    style={{
                        marginTop: SECTION_TOP,
                        background: '#0c1830',
                        color: '#ffffff',
                        scrollMarginTop: '72px',
                    }}
                >
                    <div
                        style={{
                            ...WRAP,
                            padding: `clamp(56px,8vw,112px) ${PAD_X}`,
                            display: 'flex',
                            flexWrap: 'wrap',
                            gap: 'clamp(28px,5vw,72px)',
                            alignItems: 'center',
                        }}
                    >
                        <div
                            style={{
                                flex: '1.1 1 420px',
                                minWidth: 0,
                                display: 'flex',
                                flexDirection: 'column',
                                gap: '18px',
                            }}
                        >
                            <h2
                                style={{
                                    margin: 0,
                                    fontSize: 'clamp(40px,6.4vw,92px)',
                                    lineHeight: 0.92,
                                    fontWeight: 900,
                                    letterSpacing: '-.055em',
                                    textWrap: 'balance',
                                }}
                            >
                                {joinTitle}
                            </h2>
                            <p
                                style={{
                                    margin: 0,
                                    maxWidth: '480px',
                                    fontSize: '17px',
                                    lineHeight: 1.6,
                                    color: '#b9c2d6',
                                    textWrap: 'pretty',
                                }}
                            >
                                {joinSub}
                            </p>
                        </div>
                        <div
                            style={{
                                flex: '1 1 380px',
                                minWidth: 0,
                                background: '#ffffff',
                                color: '#0c1830',
                                borderRadius: '26px',
                                padding: 'clamp(20px,3vw,28px)',
                            }}
                        >
                            <Waitlist role={role} onRole={setRole} />
                        </div>
                    </div>
                    <div
                        style={{ borderTop: '1px solid rgba(255,255,255,.1)' }}
                    >
                        <div
                            style={{
                                ...WRAP,
                                padding: `26px ${PAD_X}`,
                                display: 'flex',
                                flexWrap: 'wrap',
                                gap: '14px 28px',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                                fontSize: '13px',
                                color: '#8894a8',
                            }}
                        >
                            <div
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '12px',
                                }}
                            >
                                <img
                                    src={IMG + 'logo-lockup-white.png'}
                                    alt="Rozine"
                                    style={{ height: '16px', width: 'auto' }}
                                />
                                <span
                                    style={{
                                        width: '1px',
                                        height: '14px',
                                        background: 'rgba(255,255,255,.25)',
                                    }}
                                />
                                <span
                                    style={{
                                        fontSize: '12px',
                                        fontWeight: 600,
                                        letterSpacing: '.02em',
                                        color: 'rgba(255,255,255,.72)',
                                        whiteSpace: 'nowrap',
                                    }}
                                >
                                    Rozine Technologies Ltd · Kigali, Rwanda
                                </span>
                            </div>
                            <span style={{ fontSize: '12px', lineHeight: 1.5 }}>
                                Investing carries risk, including loss of
                                capital. Returns are not guaranteed. Rozine has
                                applied to test this service in the Capital
                                Market Authority's Fintech Regulatory Sandbox.
                                Once admitted, it will run under limits set by
                                the CMA. Admission to the sandbox is not a
                                licence or an endorsement.
                            </span>
                            <nav
                                aria-label="Legal"
                                style={{
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    gap: '8px 18px',
                                }}
                            >
                                {LEGAL_LINKS.map(([label, href]) => (
                                    <a
                                        key={href}
                                        href={href}
                                        style={{
                                            color: '#ffffff',
                                            fontWeight: 600,
                                        }}
                                    >
                                        {label}
                                    </a>
                                ))}
                            </nav>
                            <a
                                href="mailto:admin@rozine.rw"
                                style={{ color: '#ffffff', fontWeight: 600 }}
                            >
                                admin@rozine.rw
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

/** One of the two "Inside the app" cards: a screenshot with numbered pins and their notes. */
function AppCard({
    dark,
    label,
    title,
    image,
    alt,
    pins,
    notes,
}: {
    dark: boolean;
    label: string;
    title: string;
    image: string;
    alt: string;
    pins: ReadonlyArray<[string, string]>;
    notes: ReadonlyArray<[string, string]>;
}) {
    return (
        <div
            style={{
                background: dark ? '#0c1830' : '#f3f5ff',
                color: dark ? '#ffffff' : undefined,
                borderRadius: '28px',
                padding: 'clamp(22px,3vw,36px)',
                display: 'flex',
                flexDirection: 'column',
                gap: '22px',
            }}
        >
            <div
                style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}
            >
                <span
                    style={{
                        fontSize: '12px',
                        fontWeight: 800,
                        letterSpacing: '.1em',
                        color: dark ? '#8ea0ff' : '#1e3aff',
                    }}
                >
                    {label}
                </span>
                <h3
                    style={{
                        margin: 0,
                        fontSize: 'clamp(24px,2.6vw,32px)',
                        lineHeight: 1.05,
                        fontWeight: 900,
                        letterSpacing: '-.04em',
                    }}
                >
                    {title}
                </h3>
            </div>
            <div
                style={{
                    position: 'relative',
                    width: '100%',
                    maxWidth: '320px',
                    margin: '0 auto',
                }}
            >
                <img
                    src={IMG + image}
                    alt={alt}
                    style={{
                        display: 'block',
                        width: '100%',
                        height: 'auto',
                        borderRadius: '22px',
                        boxShadow: dark
                            ? '0 40px 70px -40px rgba(0,0,0,.7)'
                            : '0 40px 70px -40px rgba(12,24,48,.45)',
                    }}
                />
                {pins.map(([top, left], index) => (
                    <Pin
                        key={index}
                        n={index + 1}
                        top={top}
                        left={left}
                        ring={dark ? '#0c1830' : '#ffffff'}
                    />
                ))}
            </div>
            <div
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '12px',
                }}
            >
                {notes.map(([noteTitle, body], index) => (
                    <PinNote
                        key={noteTitle}
                        n={index + 1}
                        title={noteTitle}
                        body={body}
                        dark={dark}
                    />
                ))}
            </div>
        </div>
    );
}
