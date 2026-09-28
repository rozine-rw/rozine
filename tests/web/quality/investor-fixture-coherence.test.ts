import { readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vite-plus/test';
import type {
    C3HoldingDetail,
    C4HoldingDetail,
    Commitment,
    HoldingHealth,
    HoldingSummary,
    InvestorPortfolioProps,
    InvestorRating,
    PayoutMonth,
    PortfolioEarnings,
    PortfolioTotals,
    PrimaryQuote,
} from '../../../resources/js/types/investor';
import type { Money } from '../../../resources/js/types/money';
import type {
    Ordinals,
    UnitRights,
} from '../../../resources/js/types/settlement';

/*
 * The Investor preview fixtures, kept internally coherent. Every figure is a server fact, so the
 * pages never do this arithmetic; a fixture that contradicts itself would show the reviewer a
 * screen the server could never send. Each invariant below is pure arithmetic over the fixtures,
 * derived from the contract types and the figures the pages label:
 *
 * - A note's total return is its principal times `rate_pct` (the quote's RWF 5,000 → RWF 675 at
 *   13.5%), split over the schedule with any extra shilling on the earliest instalments.
 * - "Expected profit" is that total return, gross; "Total at maturity" is principal plus return
 *   less the fee on earnings over the return (the quote's `maturity_value`).
 * - `value` is a cumulative performance figure (HoldingSummary): what was invested plus the gross
 *   return received so far, not the outstanding asset value or a spendable balance. Principal
 *   repaid is not earnings and is not counted as a gain. So `gain` is `value − invested`, the
 *   gross return received before the fee on earnings, and `gain_pct` is that over the amount
 *   invested.
 * - A campaign's raised, target and left-to-fill are its committed, total and available units at
 *   the unit price, and `funded_pct` is raised over target to one decimal.
 * - Every portfolio card opens its own note: a holding fixture with the same id, name and figures.
 *   At one server_time no business is both active and matured.
 * - The payouts are the scheduled instalments of the active notes still paying (healthy or watch),
 *   each in the calendar month it falls due, or where an on-track plan moves the deferred one.
 *   `projected_3m` is the first three months and `next_payout` the first; `avg_monthly` is
 *   `projected_3m` over three months; `this_month` is the gross return received in server_time's
 *   month. A matured tab carries the same totals and payouts as the active tab it links to.
 */

type Fixture = { name: string; props: Record<string, unknown> };
type Problems = string[];
type AnyHolding = C3HoldingDetail | C4HoldingDetail;
type Schedule = C3HoldingDetail['issue']['schedule'];
type CardLike = Record<string, unknown> & { campaign_id: string };

const UNIT_PRICE = 5000;
const directory = path.resolve(__dirname, '../../../resources/fixtures/ui');
const load = (prefixes: string[]): Fixture[] =>
    readdirSync(directory)
        .filter(
            (file) =>
                file.endsWith('.json') &&
                prefixes.some((prefix) => file.startsWith(prefix)),
        )
        .sort()
        .map((file) => ({
            name: file.replace(/\.json$/u, ''),
            props: (
                JSON.parse(
                    readFileSync(path.join(directory, file), 'utf8'),
                ) as {
                    props: Record<string, unknown>;
                }
            ).props,
        }));

const holdings = load(['investor-holding']);
const portfolios = load(['investor-portfolio']);
const deals = load([
    'investor-deal',
    'investor-checkout',
    'investor-commitment',
]);

const n = (money: Money): number => Number(money.amount);
const sum = (values: number[]): number =>
    values.reduce((total, value) => total + value, 0);
/** Half-up rounding of a nonnegative ratio, in integer arithmetic. */
const roundRatio = (numerator: number, denominator: number): number =>
    Math.floor((numerator * 2 + denominator) / (denominator * 2));
const feeOn = (amount: number, rateBps: number): number =>
    roundRatio(amount * rateBps, 10_000);
const tenths = (rate: string): number => Math.round(Number(rate) * 10);
/** A signed one-decimal percentage, as the engine formats `gain_pct` and `funded_pct`. */
const percent = (numerator: number, denominator: number): string => {
    const scaled = roundRatio(Math.abs(numerator) * 1000, denominator);
    const sign = numerator < 0 && scaled > 0 ? '-' : '';

    return `${sign}${Math.floor(scaled / 10)}.${scaled % 10}`;
};
const units = (ordinals: Ordinals): number =>
    sum(ordinals.map(({ first, last }) => Number(last) - Number(first) + 1));
const day = (iso: string): string => iso.slice(0, 10);
const daysBetween = (from: string, to: string): number =>
    (Date.parse(day(to)) - Date.parse(day(from))) / 86_400_000;
const nextDay = (date: string): string =>
    new Date(Date.parse(date) + 86_400_000).toISOString().slice(0, 10);
const gross = (row: Schedule[number]): number =>
    n(row.principal) + n(row.return);

function expectEqual(
    problems: Problems,
    where: string,
    actual: unknown,
    expected: unknown,
): void {
    if (JSON.stringify(actual) !== JSON.stringify(expected)) {
        problems.push(
            `${where}: expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`,
        );
    }
}

function expectTrue(problems: Problems, where: string, holds: boolean): void {
    if (!holds) {
        problems.push(where);
    }
}

/** An even split: the parts differ by at most one shilling, the larger ones first. */
function expectEvenSplit(
    problems: Problems,
    where: string,
    parts: number[],
    total: number,
): void {
    expectEqual(problems, `${where} sum`, sum(parts), total);
    const base = Math.floor(total / parts.length);
    const extra = total - base * parts.length;

    expectEqual(
        problems,
        `${where} split`,
        parts,
        parts.map((_, index) => (index < extra ? base + 1 : base)),
    );
}

function expectRights(
    problems: Problems,
    where: string,
    rights: UnitRights,
    principal: number,
    totalReturn: number,
): void {
    expectEqual(
        problems,
        `${where}.total_return`,
        n(rights.total_return),
        totalReturn,
    );
    expectEvenSplit(
        problems,
        `${where}.instalments principal`,
        rights.instalments.map((row) => n(row.principal)),
        principal,
    );
    expectEvenSplit(
        problems,
        `${where}.instalments return`,
        rights.instalments.map((row) => n(row.return)),
        totalReturn,
    );
}

const serverDay = (fixture: Fixture): string =>
    day(fixture.props.server_time as string);

function checkIssue(problems: Problems, name: string, holding: AnyHolding) {
    const { issue } = holding;
    const at = `${name} holding.issue`;
    const principal = n(holding.invested);
    const totalReturn = n(issue.terms.total_return);

    expectEqual(problems, `${at}.id`, issue.id, holding.id);
    expectEqual(problems, `${at}.principal`, n(issue.principal), principal);
    expectEqual(
        problems,
        `${at}.units`,
        Number(issue.units),
        principal / UNIT_PRICE,
    );
    expectEqual(
        problems,
        `${at}.ordinals length`,
        units(issue.ordinals),
        Number(issue.units),
    );
    expectEqual(
        problems,
        `${at}.issue_receipt.amount`,
        n(issue.issue_receipt.amount),
        principal,
    );
    expectEqual(
        problems,
        `${at}.issue_receipt.units`,
        issue.issue_receipt.units,
        issue.units,
    );
    expectEqual(
        problems,
        `${at}.terms.rate_pct`,
        issue.terms.rate_pct,
        holding.rate_pct,
    );
    expectEqual(
        problems,
        `${at}.terms.term_months`,
        issue.terms.term_months,
        holding.payments_total,
    );
    expectEqual(
        problems,
        `${at}.schedule length`,
        issue.schedule.length,
        holding.payments_total,
    );
    expectEqual(
        problems,
        `${at}.terms.total_return (principal × rate)`,
        totalReturn,
        roundRatio(principal * tenths(holding.rate_pct), 1000),
    );
    expectEvenSplit(
        problems,
        `${at}.schedule principal`,
        issue.schedule.map((row) => n(row.principal)),
        principal,
    );
    expectEvenSplit(
        problems,
        `${at}.schedule return`,
        issue.schedule.map((row) => n(row.return)),
        totalReturn,
    );
    expectRights(
        problems,
        `${at}.rights`,
        issue.rights,
        principal,
        totalReturn,
    );
    expectEqual(
        problems,
        `${at}.rights instalments match the schedule`,
        issue.rights.instalments.map((row) => [row.principal, row.return]),
        issue.schedule.map((row) => [row.principal, row.return]),
    );
    expectTrue(
        problems,
        `${at}.schedule is dated after the effective date, in order`,
        issue.schedule.every(
            (row, index, rows) =>
                row.due_on > issue.effective_date &&
                (index === 0 || row.due_on > rows[index - 1].due_on),
        ),
    );
}

function checkFigures(
    problems: Problems,
    fixture: Fixture,
    holding: AnyHolding,
) {
    const at = `${fixture.name} holding`;
    const { schedule, terms } = holding.issue;
    const made = holding.payments_made;
    const paid = schedule.slice(0, made);
    const unpaid = schedule.slice(made);
    const principal = n(holding.invested);
    const totalReturn = n(terms.total_return);
    const returnPaid = sum(paid.map((row) => n(row.return)));
    const today = serverDay(fixture);

    expectEqual(
        problems,
        `${at}.expected_profit (gross total return)`,
        n(holding.expected_profit),
        totalReturn,
    );
    expectEqual(
        problems,
        `${at}.maturity_value (principal + return − fee on earnings)`,
        n(holding.maturity_value),
        principal +
            totalReturn -
            feeOn(totalReturn, terms.earnings_fee.rate_bps),
    );
    expectEqual(
        problems,
        `${at}.value (invested + return received)`,
        n(holding.value),
        principal + returnPaid,
    );
    expectEqual(
        problems,
        `${at}.gain (return received)`,
        n(holding.gain),
        returnPaid,
    );
    expectEqual(
        problems,
        `${at}.gain_pct`,
        holding.gain_pct,
        percent(returnPaid, principal),
    );
    expectEqual(
        problems,
        `${at}.repaid_pct`,
        holding.repaid_pct,
        roundRatio(made * 100, holding.payments_total),
    );
    expectEqual(
        problems,
        `${at}.months_left`,
        holding.months_left,
        holding.payments_total - made,
    );
    expectEqual(problems, `${at}.on_time.made`, holding.on_time.made, made);
    expectEqual(
        problems,
        `${at}.on_time on_time + late`,
        holding.on_time.on_time + holding.on_time.late,
        made,
    );
    expectEqual(
        problems,
        `${at}.matures_on`,
        day(holding.matures_on),
        schedule[schedule.length - 1].due_on,
    );

    if ('servicing' in holding) {
        expectEqual(
            problems,
            `${at}.received (servicing net)`,
            holding.received,
            holding.servicing.received.net,
        );
    } else {
        expectEqual(
            problems,
            `${at}.received (paid instalments, net of the fee on earnings)`,
            n(holding.received),
            sum(
                paid.map(
                    (row) =>
                        gross(row) -
                        feeOn(n(row.return), terms.earnings_fee.rate_bps),
                ),
            ),
        );
    }

    const pastDue = schedule.filter((row) => row.due_on < today).length;

    if (['arrears', 'defaulted'].includes(holding.health)) {
        expectTrue(
            problems,
            `${at}: ${holding.health} needs an instalment past due and unpaid (${pastDue} due, ${made} made)`,
            pastDue > made,
        );
    } else {
        expectEqual(
            problems,
            `${at}: payments made match the instalments past due`,
            made,
            pastDue,
        );
    }

    if (holding.health === 'matured') {
        expectEqual(
            problems,
            `${at}: a matured note has every payment made`,
            made,
            holding.payments_total,
        );
    }

    const plan = holding.recovery_plan;

    if (plan !== null) {
        expectEqual(
            problems,
            `${at}.recovery_plan.deferred (the next unpaid instalment)`,
            n(plan.deferred),
            unpaid.length > 0 ? gross(unpaid[0]) : null,
        );
        expectTrue(
            problems,
            `${at}.recovery_plan.money_arrives on or before maturity`,
            day(plan.money_arrives) <= day(holding.matures_on),
        );
        expectTrue(
            problems,
            `${at}.recovery_plan.money_arrives ${plan.state === 'on_track' ? 'after' : 'on or before'} server_time`,
            plan.state === 'on_track'
                ? day(plan.money_arrives) > today
                : day(plan.money_arrives) <= today,
        );
    }

    const paying = ['healthy', 'watch'].includes(holding.health);
    const following = plan?.state === 'on_track' ? unpaid[1] : unpaid[0];
    let expectedNext: { amount: Money; due_on: string } | null = null;

    if (paying && following !== undefined) {
        expectedNext = {
            amount: { currency: 'RWF', amount: String(gross(following)) },
            due_on: following.due_on,
        };
    } else if (paying && plan !== null && unpaid.length > 0) {
        expectedNext = {
            amount: plan.deferred,
            due_on: day(plan.money_arrives),
        };
    }

    const next =
        holding.next_payment === null
            ? null
            : {
                  amount: holding.next_payment.amount,
                  due_on: day(holding.next_payment.due_on),
              };

    expectEqual(problems, `${at}.next_payment`, next, expectedNext);

    if ('servicing' in holding) {
        const projected = holding.servicing.next_payment;

        expectEqual(
            problems,
            `${at}.servicing.next_payment`,
            projected === null
                ? null
                : { amount: projected.entitled, due_on: projected.due_on },
            next,
        );
    }
}

const PLAN_BY_HEALTH: Record<HoldingHealth, (string | null)[]> = {
    healthy: [null],
    watch: [null, 'on_track'],
    arrears: [null, 'off_track'],
    defaulted: [null],
    frozen: [null],
    matured: [null],
};

function checkHealth(
    problems: Problems,
    fixture: Fixture,
    holding: AnyHolding,
) {
    const at = `${fixture.name} holding`;
    const inArrears = ['arrears', 'defaulted'].includes(holding.health);
    const plan = holding.recovery_plan;

    expectEqual(
        problems,
        `${at}: ${holding.health} ${inArrears ? 'shows' : 'shows no'} arrears`,
        holding.arrears !== null,
        inArrears,
    );
    expectTrue(
        problems,
        `${at}: ${holding.health} cannot carry a ${plan?.state} recovery plan`,
        PLAN_BY_HEALTH[holding.health].includes(plan?.state ?? null),
    );

    const unpaid = holding.issue.schedule[holding.payments_made];
    const today = serverDay(fixture);

    if (holding.arrears !== null && 'dpd' in holding.arrears) {
        expectEqual(
            problems,
            `${at}.arrears.dpd`,
            holding.arrears.dpd,
            daysBetween(unpaid.due_on, today),
        );
        expectEqual(
            problems,
            `${at}.arrears.since`,
            holding.arrears.since,
            nextDay(unpaid.due_on),
        );
    } else if (holding.arrears !== null) {
        expectEqual(
            problems,
            `${at}.arrears.days_overdue`,
            holding.arrears.days_overdue,
            daysBetween(
                plan === null ? unpaid.due_on : plan.money_arrives,
                today,
            ),
        );
    }

    const late = inArrears || holding.health === 'watch';
    const latest = holding.updates[0];

    if (late && latest !== undefined) {
        expectEqual(
            problems,
            `${at}.updates[0].status on a ${holding.health} note`,
            latest.status,
            'watch',
        );
    }

    const onTimeClaims = holding.updates.filter((update, index) =>
        /on time|paid on the/iu.test(
            `${update.summary} ${update.business_note}`,
        )
            ? (late && index === 0) ||
              (holding.on_time.made > 0 && holding.on_time.on_time === 0)
            : false,
    );

    expectEqual(
        problems,
        `${at}.updates claiming on-time payment`,
        onTimeClaims.map((update) => update.id),
        [],
    );
}

function checkRating(
    problems: Problems,
    where: string,
    rating: InvestorRating,
    change: C3HoldingDetail['rating_change'],
) {
    if (change === null) {
        return;
    }

    expectEqual(
        problems,
        `${where}.rating (rating_change.to)`,
        rating,
        change.to,
    );

    const move = tenths(change.to.score) - tenths(change.from.score);

    expectEqual(
        problems,
        `${where}.rating_change reasons add up to the move`,
        sum(change.reasons.map((reason) => tenths(reason.delta))),
        move,
    );
    change.reasons.forEach((reason, index) =>
        expectTrue(
            problems,
            `${where}.rating_change.reasons[${index}].delta ${reason.delta} has the sign of a ${move < 0 ? 'downgrade' : 'upgrade'}`,
            move < 0
                ? reason.delta.startsWith('-')
                : reason.delta.startsWith('+'),
        ),
    );
}

function checkServicing(
    problems: Problems,
    name: string,
    holding: C4HoldingDetail,
) {
    const at = `${name} holding.servicing`;
    const { servicing, instalments, payouts } = holding;
    const made = holding.payments_made;
    const schedule = holding.issue.schedule;
    const paid = instalments.filter((row) => row.status === 'paid');

    expectEqual(problems, `${at}.health`, servicing.health, holding.health);
    expectEqual(problems, `${at}.on_time`, servicing.on_time, holding.on_time);
    expectEqual(problems, `${at} paid instalments`, paid.length, made);
    expectEqual(
        problems,
        `${at} instalments match the schedule`,
        instalments.map((row) => [row.due_on, row.entitled]),
        schedule.map((row) => [
            row.due_on,
            { principal: row.principal, return: row.return },
        ]),
    );
    expectEqual(
        problems,
        `${at}.received.principal`,
        n(servicing.received.principal),
        sum(paid.map((row) => n(row.paid.principal))),
    );
    expectEqual(
        problems,
        `${at}.received.return`,
        n(servicing.received.return),
        sum(paid.map((row) => n(row.paid.return))),
    );
    expectEqual(
        problems,
        `${at}.received.net`,
        n(servicing.received.net),
        n(servicing.received.principal) +
            n(servicing.received.return) +
            n(servicing.received.late_fees) -
            n(servicing.received.fees),
    );
    expectEqual(
        problems,
        `${at}.received.net (payouts)`,
        n(servicing.received.net),
        sum(payouts.map((payout) => n(payout.net))),
    );
    expectEqual(
        problems,
        `${at}.received.fees (payouts)`,
        n(servicing.received.fees),
        sum(payouts.map((payout) => n(payout.fee))),
    );
    expectEqual(
        problems,
        `${at}.received.late_fees (payouts)`,
        n(servicing.received.late_fees),
        sum(payouts.map((payout) => n(payout.gross.late_fees))),
    );
    expectEqual(
        problems,
        `${at}.outstanding_principal`,
        n(servicing.outstanding_principal),
        n(holding.invested) - n(servicing.received.principal),
    );
    expectEqual(
        problems,
        `${at}.remaining_projected`,
        n(servicing.remaining_projected),
        sum(schedule.slice(made).map(gross)),
    );
    expectEqual(
        problems,
        `${at}.dpd`,
        servicing.dpd,
        holding.arrears === null ? null : holding.arrears.dpd,
    );
    expectEqual(
        problems,
        `${at}.state`,
        servicing.state === 'overdue' ||
            servicing.state === 'repaid' ||
            servicing.state === 'defaulted',
        ['arrears', 'matured', 'defaulted'].includes(holding.health),
    );
}

const holdingOf = (fixture: Fixture): AnyHolding =>
    fixture.props.holding as AnyHolding;

describe('Investor holding fixtures', () => {
    it('finds every holding fixture', () => {
        expect(holdings.length).toBeGreaterThan(10);
    });

    it('carry an issue record of the holding’s own note, with a schedule that sums', () => {
        const problems: Problems = [];

        holdings.forEach((fixture) =>
            checkIssue(problems, fixture.name, holdingOf(fixture)),
        );

        expect(problems).toEqual([]);
    });

    it('derive every figure from that schedule and the payments made', () => {
        const problems: Problems = [];

        holdings.forEach((fixture) =>
            checkFigures(problems, fixture, holdingOf(fixture)),
        );

        expect(problems).toEqual([]);
    });

    it('agree on health, arrears, recovery plan and the latest monthly update', () => {
        const problems: Problems = [];

        holdings.forEach((fixture) =>
            checkHealth(problems, fixture, holdingOf(fixture)),
        );

        expect(problems).toEqual([]);
    });

    it('show the rating a change leads to, with reasons that move the same way', () => {
        const problems: Problems = [];

        holdings.forEach((fixture) => {
            const holding = holdingOf(fixture);

            checkRating(
                problems,
                `${fixture.name} holding`,
                holding.rating,
                holding.rating_change,
            );
        });

        expect(problems).toEqual([]);
    });

    it('keep a servicing holding’s summary, instalments and payouts in step', () => {
        const problems: Problems = [];

        holdings.forEach((fixture) => {
            const holding = holdingOf(fixture);

            if ('servicing' in holding) {
                checkServicing(problems, fixture.name, holding);
            }
        });

        expect(problems).toEqual([]);
    });
});

const holdingFixtures = new Map(
    holdings.map((fixture) => [`/preview/${fixture.name}`, holdingOf(fixture)]),
);

const SUMMARY_FIELDS = [
    'name',
    'health',
    'invested',
    'value',
    'gain',
    'gain_pct',
    'repaid_pct',
    'matures_on',
    'payments_made',
    'payments_total',
    'accent',
    'industry',
    'district',
] as const;

describe('Investor portfolio fixtures', () => {
    it('list holdings whose figures agree with each other and with the note they open', () => {
        const problems: Problems = [];

        portfolios.forEach(({ name, props }) => {
            (props.holdings as HoldingSummary[]).forEach((holding, index) => {
                const at = `${name} holdings[${index}] (${holding.id})`;

                expectEqual(
                    problems,
                    `${at}.repaid_pct`,
                    holding.repaid_pct,
                    roundRatio(
                        holding.payments_made * 100,
                        holding.payments_total,
                    ),
                );
                expectEqual(
                    problems,
                    `${at}.gain_pct`,
                    holding.gain_pct,
                    percent(n(holding.gain), n(holding.invested)),
                );
                expectEqual(
                    problems,
                    `${at}: value − gain is what was invested`,
                    n(holding.value) - n(holding.gain),
                    n(holding.invested),
                );

                if (holding.health === 'matured') {
                    expectEqual(
                        problems,
                        `${at}: a matured note has made every payment`,
                        holding.payments_made,
                        holding.payments_total,
                    );
                }

                const detail = holdingFixtures.get(holding.link.url);

                expectTrue(
                    problems,
                    `${at}.link ${holding.link.url} opens a holding fixture`,
                    detail !== undefined,
                );
                expectEqual(
                    problems,
                    `${at}: the note it opens (id, name)`,
                    detail === undefined ? null : [detail.id, detail.name],
                    [holding.id, holding.name],
                );

                if (detail?.id === holding.id) {
                    SUMMARY_FIELDS.forEach((field) =>
                        expectEqual(
                            problems,
                            `${at}.${field} (as ${holding.link.url})`,
                            holding[field],
                            detail[field],
                        ),
                    );
                }
            });
        });

        expect(problems).toEqual([]);
    });

    it('total the active holdings and the upcoming payouts', () => {
        const problems: Problems = [];

        portfolios.forEach(({ name, props }) => {
            const list = props.holdings as HoldingSummary[];
            const payouts = props.payouts as PayoutMonth[];
            const peak = Math.max(
                0,
                ...payouts.map((month) => n(month.amount)),
            );
            const next3m = sum(
                payouts.slice(0, 3).map((month) => n(month.amount)),
            );

            payouts.forEach((month, index) => {
                expectEqual(
                    problems,
                    `${name} payouts[${index}].amount (payers)`,
                    n(month.amount),
                    sum(month.payers.map((payer) => n(payer.amount))),
                );
                expectEqual(
                    problems,
                    `${name} payouts[${index}].bar_pct`,
                    month.bar_pct,
                    roundRatio(n(month.amount) * 100, peak),
                );
            });

            const totals = props.totals as PortfolioTotals | undefined;

            if (totals !== undefined) {
                expectEqual(
                    problems,
                    `${name} totals.projected_3m`,
                    n(totals.projected_3m),
                    next3m,
                );
                expectEqual(
                    problems,
                    `${name} totals.next_payout`,
                    totals.next_payout?.amount ?? null,
                    payouts[0]?.amount ?? null,
                );

                if (props.tab === 'active') {
                    (['value', 'invested', 'gain'] as const).forEach((field) =>
                        expectEqual(
                            problems,
                            `${name} totals.${field}`,
                            n(totals[field]),
                            sum(list.map((holding) => n(holding[field]))),
                        ),
                    );
                    expectEqual(
                        problems,
                        `${name} totals.businesses`,
                        totals.businesses,
                        list.length,
                    );
                }
            }

            const earnings = props.earnings as PortfolioEarnings | undefined;

            if (earnings !== undefined) {
                const { realised } = earnings;
                const outstanding = n(earnings.outstanding_principal);

                expectEqual(
                    problems,
                    `${name} earnings.invested`,
                    n(earnings.invested),
                    sum(list.map((holding) => n(holding.invested))),
                );
                expectTrue(
                    problems,
                    `${name} earnings.outstanding_principal is within what was invested`,
                    outstanding >= 0 && outstanding <= n(earnings.invested),
                );
                expectEqual(
                    problems,
                    `${name} earnings.realised.principal`,
                    n(realised.principal),
                    n(earnings.invested) - outstanding,
                );
                expectEqual(
                    problems,
                    `${name} earnings.realised.return`,
                    n(realised.return),
                    sum(list.map((holding) => n(holding.gain))),
                );
                expectEqual(
                    problems,
                    `${name} earnings.realised.net_return`,
                    n(realised.net_return),
                    n(realised.return) +
                        n(realised.late_fees) -
                        n(realised.fees),
                );
                expectEqual(
                    problems,
                    `${name} earnings.projected.next_3m`,
                    n(earnings.projected.next_3m),
                    next3m,
                );
            }
        });

        expect(problems).toEqual([]);
    });
});

/** Where a portfolio's active holdings live: its own list, or the active tab it links to. */
function activeCards(fixture: Fixture): HoldingSummary[] {
    if (fixture.props.tab === 'active') {
        return fixture.props.holdings as HoldingSummary[];
    }

    const tabs = fixture.props.tabs as InvestorPortfolioProps['tabs'];
    const url = tabs.find((tab) => tab.key === 'active')?.link.url;
    const active = portfolios.find((other) => `/preview/${other.name}` === url);

    return (active?.props.holdings as HoldingSummary[] | undefined) ?? [];
}

type Landing = { card: HoldingSummary; on: string; amount: number };

/** The unpaid instalments of every active note still paying, on the day each lands. */
function landings(cards: HoldingSummary[]): Landing[] {
    return cards.flatMap((card) => {
        const detail = holdingFixtures.get(card.link.url);

        if (
            detail === undefined ||
            !['healthy', 'watch'].includes(detail.health)
        ) {
            return [];
        }

        const plan = detail.recovery_plan;

        return detail.issue.schedule
            .slice(detail.payments_made)
            .map((row, offset) => ({
                card,
                on:
                    offset === 0 && plan?.state === 'on_track'
                        ? day(plan.money_arrives)
                        : row.due_on,
                amount: gross(row),
            }));
    });
}

/** The payouts those landings add up to, a calendar month at a time, the largest payer first. */
function expectedPayouts(cards: HoldingSummary[]): PayoutMonth[] {
    const months = new Map<string, Map<HoldingSummary, number>>();

    landings(cards).forEach(({ card, on, amount }) => {
        const payers = months.get(on.slice(0, 7)) ?? new Map();

        payers.set(card, (payers.get(card) ?? 0) + amount);
        months.set(on.slice(0, 7), payers);
    });

    const rows = [...months.entries()]
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([month, payers]) => ({
            month,
            total: sum([...payers.values()]),
            payers: [...payers.entries()].sort(
                ([a, x], [b, y]) => y - x || a.name.localeCompare(b.name),
            ),
        }));
    const peak = Math.max(0, ...rows.map((row) => row.total));

    return rows.map(({ month, total, payers }) => ({
        month: `${month}-01T00:00:00+02:00`,
        amount: { currency: 'RWF', amount: String(total) },
        bar_pct: roundRatio(total * 100, peak),
        payers: payers.map(([card, amount]) => ({
            name: card.name,
            accent: card.accent,
            amount: { currency: 'RWF', amount: String(amount) },
        })),
    }));
}

/** Gross return received so far in server_time's calendar month, across the active notes. */
function returnThisMonth(fixture: Fixture, cards: HoldingSummary[]): number {
    const month = serverDay(fixture).slice(0, 7);

    return sum(
        cards.flatMap((card) => {
            const detail = holdingFixtures.get(card.link.url);

            return (detail?.issue.schedule ?? [])
                .slice(0, detail?.payments_made ?? 0)
                .filter((row) => row.due_on.startsWith(month))
                .map((row) => n(row.return));
        }),
    );
}

describe('Investor portfolio fixtures against the notes they list', () => {
    it('never show a business as both active and matured at one server_time', () => {
        const states = new Map<string, Set<string>>();
        const record = (time: string, name: string, health: string) => {
            const key = `${time} ${name}`;

            states.set(
                key,
                (states.get(key) ?? new Set()).add(
                    health === 'matured' ? 'matured' : 'active',
                ),
            );
        };

        holdings.forEach((fixture) => {
            const holding = holdingOf(fixture);

            record(serverDay(fixture), holding.name, holding.health);
        });
        portfolios.forEach((fixture) =>
            (fixture.props.holdings as HoldingSummary[]).forEach((card) =>
                record(serverDay(fixture), card.name, card.health),
            ),
        );

        expect(
            [...states.entries()]
                .filter(([, seen]) => seen.size > 1)
                .map(([key]) => key),
        ).toEqual([]);
    });

    it('schedule payouts and totals from the active notes’ own schedules', () => {
        const problems: Problems = [];

        portfolios.forEach((fixture) => {
            const { name, props } = fixture;
            const cards = activeCards(fixture);
            const payouts = props.payouts as PayoutMonth[];
            const expected = expectedPayouts(cards);
            const projected = sum(
                expected.slice(0, 3).map((month) => n(month.amount)),
            );

            expectEqual(problems, `${name} payouts`, payouts, expected);

            const totals = props.totals as PortfolioTotals | undefined;

            if (totals !== undefined) {
                expectEqual(
                    problems,
                    `${name} totals.next_payout`,
                    totals.next_payout,
                    expected[0] === undefined
                        ? null
                        : {
                              amount: expected[0].amount,
                              month: expected[0].month,
                          },
                );
                expectEqual(
                    problems,
                    `${name} totals.projected_3m`,
                    n(totals.projected_3m),
                    projected,
                );
                expectEqual(
                    problems,
                    `${name} totals.avg_monthly (projected_3m over three months)`,
                    n(totals.avg_monthly),
                    roundRatio(projected, 3),
                );
                expectEqual(
                    problems,
                    `${name} totals.this_month (return received this month)`,
                    n(totals.this_month),
                    returnThisMonth(fixture, cards),
                );
            }

            if (props.tab === 'matured') {
                const url = (props.tabs as InvestorPortfolioProps['tabs']).find(
                    (tab) => tab.key === 'active',
                )?.link.url;
                const active = portfolios.find(
                    (other) => `/preview/${other.name}` === url,
                );

                expectEqual(
                    problems,
                    `${name} totals and payouts (as ${url})`,
                    [props.totals, props.payouts],
                    [active?.props.totals, active?.props.payouts],
                );
            }

            const earnings = props.earnings as PortfolioEarnings | undefined;

            if (earnings !== undefined) {
                const first = landings(cards).sort((a, b) =>
                    a.on.localeCompare(b.on),
                )[0]?.on;
                const unpaid = cards.flatMap((card) => {
                    const detail = holdingFixtures.get(card.link.url);

                    return (detail?.issue.schedule ?? []).slice(
                        detail?.payments_made ?? 0,
                    );
                });

                expectEqual(
                    problems,
                    `${name} earnings.next_payout (the next day a note pays)`,
                    earnings.next_payout,
                    first === undefined
                        ? null
                        : {
                              due_on: first,
                              projected: {
                                  currency: 'RWF',
                                  amount: String(
                                      sum(
                                          landings(cards)
                                              .filter(({ on }) => on === first)
                                              .map(({ amount }) => amount),
                                      ),
                                  ),
                              },
                          },
                );
                expectEqual(
                    problems,
                    `${name} earnings.outstanding_principal (unpaid principal)`,
                    n(earnings.outstanding_principal),
                    sum(unpaid.map((row) => n(row.principal))),
                );
                expectEqual(
                    problems,
                    `${name} earnings.projected.remaining_return (unpaid return)`,
                    n(earnings.projected.remaining_return),
                    sum(unpaid.map((row) => n(row.return))),
                );
            }
        });

        expect(problems).toEqual([]);
    });
});

/** Every object under `value` that satisfies `test`, with its path. */
function collect<T>(
    value: unknown,
    test: (candidate: Record<string, unknown>) => boolean,
    at = 'props',
): { at: string; item: T }[] {
    if (Array.isArray(value)) {
        return value.flatMap((entry, index) =>
            collect<T>(entry, test, `${at}[${index}]`),
        );
    }

    if (value === null || typeof value !== 'object') {
        return [];
    }

    const record = value as Record<string, unknown>;

    return [
        ...(test(record) ? [{ at, item: record as T }] : []),
        ...Object.entries(record).flatMap(([key, entry]) =>
            collect<T>(entry, test, `${at}.${key}`),
        ),
    ];
}

const isCard = (candidate: Record<string, unknown>) =>
    typeof candidate.campaign_id === 'string' && 'funded_pct' in candidate;
const isDeal = (candidate: Record<string, unknown>) =>
    typeof candidate.campaign_id === 'string' &&
    'rate_pct' in candidate &&
    'name' in candidate;

const SHARED_DEAL_FIELDS = [
    'name',
    'rating',
    'rate_pct',
    'term_months',
    'raised',
    'target',
    'funded_pct',
    'units',
    'investors',
    'lifecycle',
    'restriction',
    'unit_price',
] as const;

describe('Investor deal, checkout and commitment fixtures', () => {
    it('finds every deal fixture', () => {
        expect(deals.length).toBeGreaterThan(20);
    });

    it('fill each campaign from its units, at the unit price', () => {
        const problems: Problems = [];

        deals.forEach(({ name, props }) =>
            collect<Record<string, unknown>>(props, isCard).forEach(
                ({ at, item }) => {
                    const card = item as {
                        units: Record<
                            'total' | 'committed' | 'reserved' | 'available',
                            string
                        >;
                        unit_price: Money;
                        raised: Money;
                        target: Money;
                        left_to_fill: Money;
                        funded_pct: string;
                        lifecycle: string;
                    };
                    const where = `${name} ${at}`;
                    const u = card.units;
                    const price = n(card.unit_price);

                    expectEqual(
                        problems,
                        `${where}.unit_price`,
                        price,
                        UNIT_PRICE,
                    );
                    expectEqual(
                        problems,
                        `${where}.units committed + reserved + available`,
                        Number(u.committed) +
                            Number(u.reserved) +
                            Number(u.available),
                        Number(u.total),
                    );
                    expectEqual(
                        problems,
                        `${where}.raised (committed units)`,
                        n(card.raised),
                        Number(u.committed) * price,
                    );
                    expectEqual(
                        problems,
                        `${where}.target (total units)`,
                        n(card.target),
                        Number(u.total) * price,
                    );
                    expectEqual(
                        problems,
                        `${where}.left_to_fill (available units)`,
                        n(card.left_to_fill),
                        Number(u.available) * price,
                    );
                    expectEqual(
                        problems,
                        `${where}.funded_pct`,
                        card.funded_pct,
                        percent(n(card.raised), n(card.target)),
                    );

                    const open = Number(u.available) > 0;
                    const lifecycleHolds = {
                        live: open,
                        fully_reserved: !open && Number(u.reserved) > 0,
                        funded: !open && u.reserved === '0',
                        disbursing: !open && u.reserved === '0',
                        issued: !open && u.reserved === '0',
                    }[card.lifecycle];

                    expectTrue(
                        problems,
                        `${where}: a ${card.lifecycle} campaign with ${u.available} available and ${u.reserved} reserved`,
                        lifecycleHolds ?? true,
                    );
                },
            ),
        );

        expect(problems).toEqual([]);
    });

    it('show the same campaign the same way wherever it appears on a page', () => {
        const problems: Problems = [];

        deals.forEach(({ name, props }) => {
            const byCampaign = new Map<
                string,
                { at: string; item: CardLike }[]
            >();

            collect<CardLike>(props, isDeal).forEach((entry) =>
                byCampaign.set(entry.item.campaign_id, [
                    ...(byCampaign.get(entry.item.campaign_id) ?? []),
                    entry,
                ]),
            );
            byCampaign.forEach(([first, ...rest]) =>
                rest.forEach(({ at, item }) =>
                    SHARED_DEAL_FIELDS.filter(
                        (field) => field in item && field in first.item,
                    ).forEach((field) =>
                        expectEqual(
                            problems,
                            `${name} ${at}.${field} (as ${first.at})`,
                            item[field],
                            first.item[field],
                        ),
                    ),
                ),
            );

            collect<Commitment>(
                props,
                (candidate) => 'deal_name' in candidate,
            ).forEach(({ at, item }) => {
                const deal = byCampaign.get(item.campaign_id)?.[0];

                if (deal !== undefined) {
                    expectEqual(
                        problems,
                        `${name} ${at}.deal_name`,
                        item.deal_name,
                        deal.item.name,
                    );
                }
            });
        });

        expect(problems).toEqual([]);
    });

    it('quote, reserve and commit whole notes with rights and fees that follow from them', () => {
        const problems: Problems = [];

        deals.forEach(({ name, props }) => {
            collect<PrimaryQuote>(
                props,
                (candidate) =>
                    'basis' in candidate && 'expected_return' in candidate,
            ).forEach(({ at, item: quote }) => {
                const where = `${name} ${at}`;
                const amount = n(quote.amount);
                const expectedReturn = roundRatio(
                    amount * tenths(quote.rate_pct),
                    1000,
                );
                const fee = feeOn(expectedReturn, quote.earnings_fee.rate_bps);

                expectEqual(
                    problems,
                    `${where}.amount (units × unit price)`,
                    amount,
                    Number(quote.units) * n(quote.unit_price),
                );
                expectEqual(
                    problems,
                    `${where}.expected_return (amount × rate)`,
                    n(quote.expected_return),
                    expectedReturn,
                );
                expectEqual(
                    problems,
                    `${where}.payout_fee`,
                    n(quote.payout_fee),
                    fee,
                );
                expectEqual(
                    problems,
                    `${where}.maturity_value`,
                    n(quote.maturity_value),
                    amount + expectedReturn - fee,
                );
                expectEqual(
                    problems,
                    `${where}.rights present only once reserved`,
                    quote.rights !== null,
                    quote.basis === 'reserved',
                );

                if (quote.rights !== null) {
                    expectRights(
                        problems,
                        `${where}.rights`,
                        quote.rights,
                        amount,
                        expectedReturn,
                    );
                }
            });

            collect<{ units: string; amount: Money }>(
                props,
                (candidate) =>
                    'units' in candidate &&
                    'amount' in candidate &&
                    Object.keys(candidate).length === 2,
            ).forEach(({ at, item }) =>
                expectEqual(
                    problems,
                    `${name} ${at}.amount (units × unit price)`,
                    n(item.amount),
                    Number(item.units) * UNIT_PRICE,
                ),
            );

            collect<{
                units: string;
                ordinals: Ordinals;
                rights: UnitRights;
                amount?: Money;
                principal?: Money;
                terms?: Commitment['terms'];
                state: string;
                holding?: Commitment['holding'];
            }>(
                props,
                (candidate) => 'ordinals' in candidate && 'rights' in candidate,
            ).forEach(({ at, item }) => {
                const where = `${name} ${at}`;
                const principal = n(
                    item.principal ??
                        item.amount ?? { currency: 'RWF', amount: 'NaN' },
                );

                expectEqual(
                    problems,
                    `${where} principal (units × unit price)`,
                    principal,
                    Number(item.units) * UNIT_PRICE,
                );
                expectEqual(
                    problems,
                    `${where}.ordinals length`,
                    units(item.ordinals),
                    Number(item.units),
                );
                expectRights(
                    problems,
                    `${where}.rights`,
                    item.rights,
                    principal,
                    n(item.rights.total_return),
                );

                if (item.terms !== undefined && 'payout_fee' in item.terms) {
                    expectEqual(
                        problems,
                        `${where}.rights.total_return (principal × rate)`,
                        n(item.rights.total_return),
                        roundRatio(
                            principal * tenths(item.terms.rate_pct),
                            1000,
                        ),
                    );
                    expectEqual(
                        problems,
                        `${where}.terms.payout_fee`,
                        n(item.terms.payout_fee),
                        feeOn(
                            n(item.rights.total_return),
                            item.terms.earnings_fee.rate_bps,
                        ),
                    );
                    expectEqual(
                        problems,
                        `${where}.holding only once issued`,
                        item.holding !== null,
                        item.state === 'issued',
                    );
                }
            });
        });

        expect(problems).toEqual([]);
    });
});
