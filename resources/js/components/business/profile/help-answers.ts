import type { HelpGroup } from '@/components/rozine/help-center';

/**
 * The Business support center's answers, in the design's groups (Business `faqData`). Each
 * answer restates a BRS v2.0 rule. Figures the BRS and the build still disagree on (the platform
 * fee, how disbursement is approved) are not quoted: the answer points to the RWF amounts shown
 * before the raise is published.
 */
export const HELP_GROUPS: readonly HelpGroup[] = [
    {
        key: 'start',
        icon: '🚀',
        heading: 'business.help.group.start',
        questions: [
            {
                q: 'business.help.start.what.q',
                a: 'business.help.start.what.a',
            },
            {
                q: 'business.help.start.eligible.q',
                a: 'business.help.start.eligible.a',
            },
            {
                q: 'business.help.start.documents.q',
                a: 'business.help.start.documents.a',
            },
        ],
    },
    {
        key: 'capacity',
        icon: '📐',
        heading: 'business.help.group.capacity',
        questions: [
            {
                q: 'business.help.capacity.how.q',
                a: 'business.help.capacity.how.a',
            },
            {
                q: 'business.help.capacity.rate.q',
                a: 'business.help.capacity.rate.a',
            },
            {
                q: 'business.help.capacity.more.q',
                a: 'business.help.capacity.more.a',
            },
        ],
    },
    {
        key: 'raise',
        icon: '📣',
        heading: 'business.help.group.raise',
        questions: [
            {
                q: 'business.help.raise.time.q',
                a: 'business.help.raise.time.a',
            },
            {
                q: 'business.help.raise.unfunded.q',
                a: 'business.help.raise.unfunded.a',
            },
            {
                q: 'business.help.raise.schedule.q',
                a: 'business.help.raise.schedule.a',
            },
        ],
    },
    {
        key: 'repay',
        icon: '🗓️',
        heading: 'business.help.group.repay',
        questions: [
            { q: 'business.help.repay.how.q', a: 'business.help.repay.how.a' },
            {
                q: 'business.help.repay.early.q',
                a: 'business.help.repay.early.a',
            },
            {
                q: 'business.help.repay.late.q',
                a: 'business.help.repay.late.a',
            },
        ],
    },
    {
        key: 'reports',
        icon: '📋',
        heading: 'business.help.group.reports',
        questions: [
            {
                q: 'business.help.reports.monthly.q',
                a: 'business.help.reports.monthly.a',
            },
            {
                q: 'business.help.reports.rating.q',
                a: 'business.help.reports.rating.a',
            },
            {
                q: 'business.help.reports.dispute.q',
                a: 'business.help.reports.dispute.a',
            },
        ],
    },
    {
        key: 'fees',
        icon: '💳',
        heading: 'business.help.group.fees',
        questions: [
            { q: 'business.help.fees.fees.q', a: 'business.help.fees.fees.a' },
            {
                q: 'business.help.fees.funds.q',
                a: 'business.help.fees.funds.a',
            },
        ],
    },
    {
        key: 'investors',
        icon: '👥',
        heading: 'business.help.group.investors',
        questions: [
            {
                q: 'business.help.investors.who.q',
                a: 'business.help.investors.who.a',
            },
            {
                q: 'business.help.investors.secondary.q',
                a: 'business.help.investors.secondary.a',
            },
        ],
    },
    {
        key: 'account',
        icon: '🔒',
        heading: 'business.help.group.account',
        questions: [
            {
                q: 'business.help.account.team.q',
                a: 'business.help.account.team.a',
            },
            {
                q: 'business.help.account.complain.q',
                a: 'business.help.account.complain.a',
            },
        ],
    },
];

/** When the answers were last checked against the BRS (v2.0). */
export const HELP_UPDATED = '2026-10-15T12:00:00+02:00';
