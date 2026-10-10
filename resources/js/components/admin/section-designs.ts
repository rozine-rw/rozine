import type { MessageCode } from '@/lib/i18n/types';
import type { AdminPendingSectionProps } from '@/types/admin';

/**
 * The design's layout for each console section whose live reads are not wired yet (Rozine Admin
 * standalone design): its KPI tiles, tabs and tables, named by message code. The page renders the
 * labels, column heads and empty states only. No figure, policy value or sample record from the
 * design is carried here, and none of its actions are, because no server read or command backs them.
 */

type ColumnOf<C> = C extends `admin.design.col.${infer K}` ? K : never;

/** A shared column head, `admin.design.col.{column}`. */
export type DesignColumn = ColumnOf<MessageCode>;

export type DesignKpi = { label: MessageCode; sub?: MessageCode };

export type DesignTab = { key: string; label: MessageCode };

export type DesignBlock = {
    key: string;
    title: MessageCode;
    empty: MessageCode;
    sub?: MessageCode;
    /** A chart panel in the design: it renders as its titled empty state. */
    chart?: boolean;
    columns?: DesignColumn[];
    /** Shown only while this tab is selected; untabbed blocks always show. */
    tab?: string;
};

export type SectionDesign = {
    kpis?: DesignKpi[];
    /** The first tab is selected on open. */
    tabs?: DesignTab[];
    blocks: DesignBlock[];
};

/** Message codes under `admin.design.{section}`, checked against the catalog at build time. */
function codes<S extends string>(section: S) {
    const prefix = `admin.design.${section}` as const;

    return {
        kpi: <K extends string>(key: K) => ({
            label: `${prefix}.kpi.${key}` as const,
        }),
        captioned: <K extends string>(key: K) => ({
            label: `${prefix}.kpi.${key}` as const,
            sub: `${prefix}.kpi.${key}.sub` as const,
        }),
        tab: <K extends string>(key: K) => ({
            key,
            label: `${prefix}.tab.${key}` as const,
        }),
        sub: <K extends string>(key: K) => `${prefix}.${key}.sub` as const,
        block: <K extends string>(
            key: K,
            extra: Omit<DesignBlock, 'key' | 'title' | 'empty'> = {},
        ) => ({
            key,
            title: `${prefix}.${key}.title` as const,
            empty: `${prefix}.${key}.empty` as const,
            ...extra,
        }),
    };
}

const notes = codes('notes');
const primary = codes('primary_market');
const secondary = codes('secondary_market');
const reports = codes('reports');
const risk = codes('risk');
const compliance = codes('compliance');
const payments = codes('payments');
const ratings = codes('ratings');
const deferrals = codes('deferrals');
const plus = codes('plus');
const finance = codes('finance');
const messaging = codes('messaging');
const academies = codes('academies');
const appControl = codes('app_control');
const engines = codes('engines');
const policies = codes('policies');
const health = codes('system_health');

const ENGINE_COLUMNS: DesignColumn[] = ['input', 'value', 'source'];

export const SECTION_DESIGNS: Record<
    AdminPendingSectionProps['section'],
    SectionDesign | undefined
> = {
    notes: {
        kpis: [
            notes.kpi('total'),
            notes.kpi('live'),
            notes.kpi('repaying'),
            notes.kpi('raised'),
        ],
        tabs: [
            notes.tab('all'),
            notes.tab('live'),
            notes.tab('repaying'),
            notes.tab('matured'),
            notes.tab('failed'),
        ],
        blocks: [
            notes.block('notes', {
                columns: [
                    'note',
                    'issuer',
                    'state',
                    'yield',
                    'term',
                    'funded',
                    'raised',
                ],
            }),
        ],
    },
    primary_market: {
        kpis: [
            primary.captioned('active'),
            primary.captioned('in_flight'),
            primary.captioned('fill'),
            primary.captioned('completion'),
        ],
        blocks: [
            primary.block('trend', { chart: true }),
            primary.block('raises', {
                columns: ['raise', 'funded', 'progress', 'closes'],
            }),
        ],
    },
    secondary_market: {
        kpis: [
            secondary.kpi('volume'),
            secondary.captioned('orders'),
            secondary.captioned('trades'),
            secondary.captioned('tradable'),
        ],
        blocks: [
            secondary.block('activity', { chart: true }),
            secondary.block('market', {
                sub: secondary.sub('market'),
                columns: [
                    'note',
                    'issuer',
                    'orders',
                    'avg_ask',
                    'spread',
                    'volume_30d',
                    'trades',
                ],
            }),
        ],
    },
    reports: {
        kpis: [
            reports.kpi('published'),
            reports.kpi('awaiting'),
            reports.kpi('breached'),
            reports.kpi('on_time'),
        ],
        blocks: [
            reports.block('field', {
                sub: reports.sub('field'),
                columns: [
                    'business',
                    'assigned_cpa',
                    'status',
                    'variance',
                    'signed',
                ],
            }),
            reports.block('monthly', {
                sub: reports.sub('monthly'),
                columns: ['business', 'period', 'audit_partner', 'status'],
            }),
        ],
    },
    risk: {
        kpis: [
            risk.kpi('exposure'),
            risk.kpi('late'),
            risk.kpi('distressed'),
            risk.kpi('default_probability'),
        ],
        blocks: [
            risk.block('distressed', {
                columns: [
                    'business',
                    'outstanding',
                    'days_late',
                    'risk',
                    'default_probability',
                ],
            }),
        ],
    },
    compliance: {
        kpis: [
            compliance.kpi('kyc_completion'),
            compliance.kpi('aml_alerts'),
            compliance.kpi('sanctions'),
            compliance.kpi('pending'),
        ],
        blocks: [
            compliance.block('queue', {
                columns: ['applicant', 'document', 'checks', 'decision'],
            }),
        ],
    },
    payments: {
        kpis: [
            payments.kpi('deposits'),
            payments.kpi('withdrawals'),
            payments.captioned('disbursed'),
            payments.captioned('awaiting'),
        ],
        blocks: [
            payments.block('movement', { chart: true }),
            payments.block('approvals', {
                columns: ['reference', 'user', 'type', 'amount', 'compliance'],
            }),
        ],
    },
    ratings: {
        kpis: [
            ratings.kpi('rated'),
            ratings.kpi('changed'),
            ratings.kpi('trending_down'),
            ratings.kpi('pinned'),
        ],
        tabs: [
            ratings.tab('changed'),
            ratings.tab('trending_down'),
            ratings.tab('all'),
        ],
        blocks: [
            ratings.block('book', {
                columns: ['business', 'sector', 'band', 'change', 'reason'],
            }),
        ],
    },
    deferrals: {
        kpis: [
            deferrals.kpi('this_month'),
            deferrals.kpi('live'),
            deferrals.kpi('deferred'),
            deferrals.kpi('extra_paid'),
        ],
        blocks: [
            deferrals.block('awaiting', {
                columns: ['business', 'why', 'ask', 'deferred', 'opened'],
            }),
            deferrals.block('live_plans', {
                sub: deferrals.sub('live_plans'),
                columns: [
                    'business',
                    'why',
                    'plan',
                    'deferred',
                    'due',
                    'state',
                ],
            }),
            deferrals.block('decided', {
                columns: ['business', 'plan', 'decision', 'decided'],
            }),
        ],
    },
    plus: {
        tabs: [plus.tab('tiers'), plus.tab('institutions'), plus.tab('all')],
        blocks: [
            plus.block('subscribers', {
                columns: ['investor', 'type', 'tier', 'monthly'],
            }),
            plus.block('orders', {
                sub: plus.sub('orders'),
                columns: [
                    'investor',
                    'min_interest',
                    'bands',
                    'filled_mtd',
                    'state',
                ],
            }),
        ],
    },
    finance: {
        kpis: [
            finance.kpi('revenue'),
            finance.kpi('primary'),
            finance.kpi('investor_fees'),
            finance.kpi('secondary'),
        ],
        tabs: [
            finance.tab('streams'),
            finance.tab('ramp'),
            finance.tab('ledger'),
        ],
        blocks: [
            finance.block('streams', {
                tab: 'streams',
                columns: ['stream', 'paid_by', 'collected'],
            }),
            finance.block('ramp', {
                tab: 'ramp',
                columns: ['position', 'amount', 'updated'],
            }),
            finance.block('ledger', {
                tab: 'ledger',
                columns: ['entry', 'account', 'debit', 'credit', 'posted'],
            }),
        ],
    },
    messaging: {
        tabs: [
            messaging.tab('automations'),
            messaging.tab('templates'),
            messaging.tab('campaigns'),
            messaging.tab('history'),
        ],
        blocks: [
            messaging.block('automations', {
                tab: 'automations',
                columns: ['rule', 'fires_when', 'timing', 'state'],
            }),
            messaging.block('templates', {
                tab: 'templates',
                columns: ['template', 'channel', 'updated'],
            }),
            messaging.block('campaigns', {
                tab: 'campaigns',
                columns: ['campaign', 'audience', 'sent'],
            }),
            messaging.block('history', {
                tab: 'history',
                columns: ['message', 'recipient', 'channel', 'sent'],
            }),
        ],
    },
    academies: {
        kpis: [
            academies.kpi('completed'),
            academies.kpi('live'),
            academies.kpi('quiz'),
            academies.kpi('points'),
        ],
        tabs: [
            academies.tab('investor'),
            academies.tab('business'),
            academies.tab('auditor'),
            academies.tab('all'),
        ],
        blocks: [
            academies.block('lessons', {
                columns: ['lesson', 'time', 'done', 'score', 'points'],
            }),
        ],
    },
    app_control: {
        blocks: [
            appControl.block('apps', {
                sub: appControl.sub('apps'),
                columns: ['app', 'status', 'features'],
            }),
            appControl.block('content', {
                sub: appControl.sub('content'),
                columns: ['type', 'entity', 'status'],
            }),
        ],
    },
    engines: {
        tabs: [
            engines.tab('sizing'),
            engines.tab('refinance'),
            engines.tab('queue'),
            engines.tab('floors'),
            engines.tab('cpa'),
            engines.tab('loss'),
        ],
        blocks: [
            engines.block('sizing', { tab: 'sizing', columns: ENGINE_COLUMNS }),
            engines.block('refinance', {
                tab: 'refinance',
                columns: ENGINE_COLUMNS,
            }),
            engines.block('queue', { tab: 'queue', columns: ENGINE_COLUMNS }),
            engines.block('floors', { tab: 'floors', columns: ENGINE_COLUMNS }),
            engines.block('cpa', { tab: 'cpa', columns: ENGINE_COLUMNS }),
            engines.block('loss', { tab: 'loss', columns: ENGINE_COLUMNS }),
        ],
    },
    policies: {
        tabs: [
            policies.tab('fees'),
            policies.tab('underwriting'),
            policies.tab('business'),
            policies.tab('investor'),
            policies.tab('kyc'),
            policies.tab('penalties'),
            policies.tab('limits'),
        ],
        blocks: [
            policies.block('rules', { columns: ['rule', 'value', 'updated'] }),
        ],
    },
    system_health: {
        blocks: [
            health.block('signals', {
                columns: ['signal', 'status', 'reading', 'recommendation'],
            }),
        ],
    },
    // Designed screens whose live reads land in their own branches keep the plain empty state.
    today: undefined,
    businesses: undefined,
    auditors: undefined,
    staff: undefined,
    events: undefined,
};
