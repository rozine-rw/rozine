/**
 * Content and arithmetic for the rozine.rw landing page (resources/js/pages/home.tsx),
 * taken from the "Rozine.rw v2" design. The figures are illustrations, worked on the client.
 */

export type LandingPage = 'home' | 'biz' | 'how' | 'help';
export type Band = 'Strong' | 'Stable' | 'Distressed';
export type HelpTab = 'investing' | 'money' | 'business' | 'account';
export type WaitlistRole = 'investor' | 'business';
type Faq = readonly [string, string];

/** Each view, its label in the header, and the header colour it wears. */
export const LANDING_PAGES: ReadonlyArray<[LandingPage, string, string]> = [
    ['home', 'Invest', '#1428a4'],
    ['biz', 'Businesses', '#17795a'],
    ['how', 'How it works', '#0c1830'],
    ['help', 'Help', '#1428a4'],
];

/** Each view has its own address, so back, reload and shared links land on it. */
export const PAGE_HASH: Record<LandingPage, string> = {
    home: '',
    biz: '#businesses',
    how: '#how-it-works',
    help: '#help',
};

/** Addresses the earlier site used, still honoured. */
const LEGACY_HASH: Record<string, LandingPage> = {
    '#investors': 'home',
};

/**
 * The view an address names. An empty fragment is the Invest view; a fragment that
 * names no view (such as #join) leaves the page where it is.
 */
export function pageFromHash(hash: string): LandingPage | null {
    if (hash === '' || hash === '#') {
        return 'home';
    }

    const found = (Object.keys(PAGE_HASH) as LandingPage[]).find(
        (page) => PAGE_HASH[page] === hash,
    );

    return found ?? LEGACY_HASH[hash] ?? null;
}

export const TERMS = [3, 4, 5, 6] as const;
export const BANDS: readonly Band[] = ['Strong', 'Stable', 'Distressed'];

const BAND_RATE: Record<Band, number> = {
    Strong: 12.5,
    Stable: 13.5,
    Distressed: 14.5,
};

/** Rozine keeps 15% of what an investor earns. */
const EARNINGS_KEPT = 0.85;

export const rwf = (amount: number): string =>
    'RWF ' + Math.round(amount).toLocaleString('en-US');

const millions = (amount: number): string =>
    'RWF ' + (amount / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';

/** The whole-term rate for a band and term: half a point more for every three extra months. */
export function rateFor(band: Band, term: number): number {
    return Math.round((BAND_RATE[band] + ((term - 3) * 0.5) / 3) * 10) / 10;
}

/** The "What you could earn" example, formatted for display. */
export function investorExample(amount: number, term: number, band: Band) {
    const rate = rateFor(band, term);
    const earn = ((amount * rate) / 100) * EARNINGS_KEPT;
    const back = amount + earn;

    return {
        amount: rwf(amount),
        rate: rate.toFixed(1) + '%',
        earn: '+' + rwf(earn),
        back: rwf(back),
        month: rwf(back / term),
    };
}

/** The "How much could you raise?" estimate: 35% of yearly revenue, up to RWF 100M. */
export function businessEstimate(revenue: number) {
    const cap = Math.min(
        100000000,
        Math.floor((revenue * 0.35) / 100000) * 100000,
    );

    return {
        revenue: millions(revenue),
        max: millions(cap),
        cap:
            cap >= 100000000
                ? 'The RWF 100M maximum per raise'
                : '35% of your yearly revenue',
    };
}

/** A place in the queue, written the way the design shows it. */
export const formatPosition = (position: number): string =>
    position.toLocaleString('en-US');

/**
 * What is wrong with the waitlist form before it is sent, or '' when nothing is.
 * The server checks the same things again.
 */
export function contactProblem(
    role: WaitlistRole,
    name: string,
    contact: string,
): string {
    if (name.trim().length < 2) {
        return role === 'business'
            ? 'Add your business name.'
            : 'Add your name.';
    }

    const typed = contact.trim();
    const isPhone = /^\+?\d[\d\s\-()]{8,}$/.test(typed);
    const isEmail = /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(typed);

    return isPhone || isEmail ? '' : 'Add a valid phone number or email.';
}

const step = (pairs: ReadonlyArray<Faq>): ReadonlyArray<Faq> => pairs;

export const BIZ_STEPS = step([
    [
        'Join the waitlist',
        "Tell us about your business. We'll confirm whether you meet the basics.",
    ],
    [
        'An accountant visits',
        'A licensed CPA checks your books, statements, cash records and premises in person. The app tells you exactly what to have ready.',
    ],
    [
        'Get your offer',
        'Your rating, amount, rate and repayments, in writing. Accept only if it works for you.',
    ],
    [
        'Go live with a prospectus',
        'Your listing and a full prospectus are built from your verified numbers. Investors have 30 days to fill it.',
    ],
    [
        'Get the money',
        'The moment your raise reaches 100%, the money is sent to your account.',
    ],
    [
        'Repay monthly',
        'Top up your Rozine wallet before each repayment day and the instalment is collected automatically. Reminders start 14 days ahead.',
    ],
    [
        'Raise again, bigger',
        'Repay cleanly and raise again straight away, up to 1.5 times your last raise. Every repaid note builds your track record.',
    ],
]);

export const JOURNEY = step([
    [
        'You add money',
        'It goes into a separate client account at a licensed Rwandan bank and shows in your wallet.',
    ],
    [
        'A business is checked',
        'A licensed accountant verifies its records in person. Only then can it raise.',
    ],
    [
        'You commit to a raise',
        'The amount leaves your available balance right away, so you always know what you have. You can withdraw until the raise fills.',
    ],
    [
        'The raise fills, or refunds',
        "At 100% the money goes to the business. If it doesn't fill in 30 days, it all comes back to you.",
    ],
    [
        "You're paid monthly",
        'Each month the accountant checks the business again and seals a report for you. Then your payout lands.',
    ],
    [
        'Your money is back',
        'Within 3 to 6 months. Reinvest it, withdraw it, or sell a note early to another investor.',
    ],
]);

const INVESTING: ReadonlyArray<Faq> = [
    [
        'Is my money guaranteed?',
        "No. You're lending to real businesses, so returns are not guaranteed and you could lose money. We check every business in person, size every loan to what it can repay, report to you monthly, pass late penalties to you and start legal recovery from day 31.",
    ],
    [
        'How much do I need to start?',
        'RWF 5,000. Every raise is split into RWF 5,000 units, so you can spread RWF 100,000 across twenty businesses.',
    ],
    [
        'Is 15% per year?',
        'No. Each note pays its rate once, over its whole term of 3 to 6 months. A 13.5% note over 5 months pays 13.5% of what you put in, before our fee.',
    ],
    [
        'What does Rozine charge me?',
        "Nothing to invest. We take 15% of the return you earn, less once your portfolio grows. If you sell a note early, there's a 2% fee.",
    ],
    [
        'How do I get paid?',
        'Every month, part of your money and part of your return land in your Rozine wallet. Reinvest it or withdraw it to mobile money or your bank.',
    ],
];

const MONEY: ReadonlyArray<Faq> = [
    [
        'Where is my money held?',
        'In a separate client account at a licensed Rwandan bank. It shows in your Rozine wallet, but Rozine can never use it for itself.',
    ],
    [
        'What if a raise never fills?',
        "Each raise has 30 days to reach 100%. If it doesn't, every investor gets their money back in full and no one pays a fee.",
    ],
    [
        'Can I change my mind?',
        'Yes, any time before the raise reaches 100%, for a full refund. Once it fills, the money goes to the business.',
    ],
    [
        'Can I get my money out early?',
        "Sell your note to another investor on Rozine. You set the price, up to what the note still has to pay out. The seller pays 2%; the buyer pays nothing. A sale needs a buyer, so it isn't guaranteed.",
    ],
    [
        'What happens if a business pays late?',
        'A 5% penalty on the missed instalment on day 1, day 7 and day 30, capped at 15%, all paid to investors. Reminders run throughout. From day 31, legal recovery begins.',
    ],
];

export const BIZ_FAQS: ReadonlyArray<Faq> = [
    [
        'Do I need collateral?',
        'No. Your verified records are the security. A licensed accountant checks them in person.',
    ],
    [
        'How much can I raise?',
        'From RWF 3M to RWF 100M, up to 35% of your yearly revenue. Your verified monthly cash must cover each instalment at least 1.5 times.',
    ],
    [
        'How long does it take?',
        "After your accountant's visit, your listing goes live with a full prospectus. Investors have 30 days to fill it, and the money is sent the moment it reaches 100%.",
    ],
    [
        'What does it cost?',
        'A return to investors of 12.5% to 15% of the amount for the whole term, set by your rating and term, plus a one-time 3% platform fee. Both are shown before you accept.',
    ],
    [
        'What if I pay late?',
        'Penalties of 5% of the missed instalment on day 1, day 7 and day 30, capped at 15%, go to your investors. From day 31, legal recovery begins. You get reminders from 14 days before each repayment.',
    ],
    [
        'Can I borrow again?',
        'Yes. After a clean repayment, you can raise again straight away, up to 1.5 times your last loan.',
    ],
];

const ACCOUNT: ReadonlyArray<Faq> = [
    [
        'Who can invest?',
        'Any adult with a valid Rwandan ID or passport and a mobile-money or bank account, after a quick identity check.',
    ],
    [
        'How do I join?',
        "Join the waitlist. We'll message you by SMS or email the moment you can open your account.",
    ],
    [
        'Is there a limit on how much I invest?',
        'No single investor can hold more than 50% of any raise, so every business is backed by many people.',
    ],
    ['How do I contact Rozine?', 'Email admin@rozine.rw. A person answers.'],
];

/** The Help view's topics and their questions. */
export const HELP_TABS: ReadonlyArray<[HelpTab, string, ReadonlyArray<Faq>]> = [
    ['investing', 'Investing', INVESTING],
    ['money', 'Your money', MONEY],
    ['business', 'For businesses', BIZ_FAQS],
    ['account', 'Account', ACCOUNT],
];
