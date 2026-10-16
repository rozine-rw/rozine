import type { HelpGroup } from '@/components/rozine/help-center';

/**
 * The Investor help center's answers, in the design's groups (Investor L3884–4140). Each answer
 * restates a BRS v2.0 rule; the design's Learning & rewards group is left out, since no points,
 * tiers, discounts or referral pay are approved.
 */
export const HELP_GROUPS: readonly HelpGroup[] = [
    {
        key: 'start',
        icon: '🚀',
        heading: 'investor.help.group.start',
        questions: [
            {
                q: 'investor.help.start.what.q',
                a: 'investor.help.start.what.a',
            },
            {
                q: 'investor.help.start.first.q',
                a: 'investor.help.start.first.a',
            },
            {
                q: 'investor.help.start.minimum.q',
                a: 'investor.help.start.minimum.a',
            },
        ],
    },
    {
        key: 'returns',
        icon: '💸',
        heading: 'investor.help.group.returns',
        questions: [
            {
                q: 'investor.help.returns.when.q',
                a: 'investor.help.returns.when.a',
            },
            {
                q: 'investor.help.returns.yield.q',
                a: 'investor.help.returns.yield.a',
            },
            {
                q: 'investor.help.returns.guaranteed.q',
                a: 'investor.help.returns.guaranteed.a',
            },
        ],
    },
    {
        key: 'risk',
        icon: '🛡️',
        heading: 'investor.help.group.risk',
        questions: [
            {
                q: 'investor.help.risk.protected.q',
                a: 'investor.help.risk.protected.a',
            },
            {
                q: 'investor.help.risk.vetting.q',
                a: 'investor.help.risk.vetting.a',
            },
            {
                q: 'investor.help.risk.rating.q',
                a: 'investor.help.risk.rating.a',
            },
            { q: 'investor.help.risk.late.q', a: 'investor.help.risk.late.a' },
        ],
    },
    {
        key: 'portfolio',
        icon: '📊',
        heading: 'investor.help.group.portfolio',
        questions: [
            {
                q: 'investor.help.portfolio.diversify.q',
                a: 'investor.help.portfolio.diversify.a',
            },
            {
                q: 'investor.help.portfolio.track.q',
                a: 'investor.help.portfolio.track.a',
            },
            {
                q: 'investor.help.portfolio.reinvest.q',
                a: 'investor.help.portfolio.reinvest.a',
            },
        ],
    },
    {
        key: 'secondary',
        icon: '🔁',
        heading: 'investor.help.group.secondary',
        questions: [
            {
                q: 'investor.help.secondary.exit.q',
                a: 'investor.help.secondary.exit.a',
            },
            {
                q: 'investor.help.secondary.price.q',
                a: 'investor.help.secondary.price.a',
            },
            {
                q: 'investor.help.secondary.guaranteed.q',
                a: 'investor.help.secondary.guaranteed.a',
            },
        ],
    },
    {
        key: 'wallet',
        icon: '👛',
        heading: 'investor.help.group.wallet',
        questions: [
            {
                q: 'investor.help.wallet.move.q',
                a: 'investor.help.wallet.move.a',
            },
            {
                q: 'investor.help.wallet.fees.q',
                a: 'investor.help.wallet.fees.a',
            },
            {
                q: 'investor.help.wallet.speed.q',
                a: 'investor.help.wallet.speed.a',
            },
        ],
    },
    {
        key: 'safety',
        icon: '🏦',
        heading: 'investor.help.group.safety',
        questions: [
            {
                q: 'investor.help.safety.held.q',
                a: 'investor.help.safety.held.a',
            },
            {
                q: 'investor.help.safety.stop.q',
                a: 'investor.help.safety.stop.a',
            },
        ],
    },
    {
        key: 'account',
        icon: '🔒',
        heading: 'investor.help.group.account',
        questions: [
            {
                q: 'investor.help.account.secure.q',
                a: 'investor.help.account.secure.a',
            },
            {
                q: 'investor.help.account.complain.q',
                a: 'investor.help.account.complain.a',
            },
        ],
    },
];

/** When the answers were last checked against the BRS (v2.0). */
export const HELP_UPDATED = '2026-10-15T12:00:00+02:00';
