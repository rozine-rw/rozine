import type * as InertiaCore from '@inertiajs/core';
import {
    act,
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { POLL_INTERVAL_MS, POLL_LIMIT } from '@/hooks/use-bounded-poll';
import BusinessRepayments from '@/pages/business/repayments';
import type { BusinessRepaymentsProps } from '@/types/business';
import aheadFixture from '../../../resources/fixtures/ui/business-repayments-ahead.json';
import allocatingFixture from '../../../resources/fixtures/ui/business-repayments-allocating.json';
import dueFixture from '../../../resources/fixtures/ui/business-repayments-due.json';
import minimalFixture from '../../../resources/fixtures/ui/business-repayments-live-minimal.json';
import overdueFixture from '../../../resources/fixtures/ui/business-repayments-overdue.json';
import paidFixture from '../../../resources/fixtures/ui/business-repayments-paid.json';
import missingFixture from '../../../resources/fixtures/ui/business-repayments-policy-missing.json';
import receiptFixture from '../../../resources/fixtures/ui/business-repayments-receipt.json';
import repaidFixture from '../../../resources/fixtures/ui/business-repayments-repaid.json';
import shortFixture from '../../../resources/fixtures/ui/business-repayments-short.json';
import unconfirmedFixture from '../../../resources/fixtures/ui/business-repayments-unconfirmed.json';
import conflictFixture from '../../../resources/fixtures/ui/business-repayments-version-conflict.json';
import repaymentsFixture from '../../../resources/fixtures/ui/business-repayments.json';
import { answers, fails, inertia, operation } from '../auditor/inertia';

vi.mock('@inertiajs/react', () => import('../auditor/inertia'));

vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaCore>()),
    http: { onResponse: () => () => undefined },
}));

const props = (fixture: { props: unknown } = repaymentsFixture) =>
    structuredClone(fixture.props) as BusinessRepaymentsProps;

const sheet = () => screen.getByRole('dialog', { name: 'Repayments' });

beforeEach(() => {
    inertia.reset();
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Business repayments (business-servicing-v1)', () => {
    it('shows progress, the next instalment and its automatic collection, and offers paying it early', () => {
        render(<BusinessRepayments {...props()} />);

        const view = within(sheet());

        expect(sheet()).toHaveTextContent(
            'Fleet Expansion · RZN-2026-0418 · Current',
        );

        const progress = view.getByRole('region', {
            name: 'Repayment progress',
        });

        expect(progress).toHaveTextContent('RWF 6,930,000');
        expect(progress).toHaveTextContent('2 / 6 payments');
        expect(progress).toHaveTextContent('4 instalments left');
        expect(progress).toHaveTextContent('RWF 20,790,000');
        expect(within(progress).getByRole('progressbar')).toHaveValue(33.3);
        expect(
            within(progress).getByRole('link', { name: 'RWF 13,860,000' }),
        ).toHaveAttribute('href', '/preview/business-repayments-receipt');

        const due = view.getByRole('region', { name: 'Due now' });

        expect(due).toHaveTextContent('Next instalment · #3');
        expect(due).toHaveTextContent('RWF 3,465,000');
        expect(due).toHaveTextContent('Due 23 Dec 2026');
        expect(due).toHaveTextContent('PrincipalRWF 3,000,000');
        expect(due).toHaveTextContent('ReturnRWF 405,000');
        expect(due).toHaveTextContent('Service feeRWF 60,000');
        expect(due).not.toHaveTextContent('Late fees');
        expect(due).toHaveTextContent(
            "We'll collect it from your Rozine wallet on 23 Dec 2026.",
        );

        const pay = view.getByRole('region', { name: 'Pay from your wallet' });

        expect(pay).toHaveTextContent(
            "Pays instalment 3 early, at its scheduled amount. There's no discount for paying early.",
        );
        expect(pay).toHaveTextContent(
            'Your wallet has RWF 4,200,000 available.',
        );
        expect(
            within(pay).getByRole('button', { name: 'Pay RWF 3,465,000' }),
        ).toBeEnabled();

        const schedule = view.getByRole('region', {
            name: 'Repayment schedule',
        });

        expect(schedule).toHaveTextContent('#1 · paid 23 Oct 2026');
        expect(schedule).toHaveTextContent('#3 · due 23 Dec 2026');
        expect(schedule).toHaveTextContent('Upcoming');

        const late = view.getByRole('region', { name: 'If a payment is late' });

        expect(late).toHaveTextContent('Due date missed+5%');
        expect(late).toHaveTextContent('Day 30 unpaid+5%');
        expect(late).toHaveTextContent(
            'Late-fee policy synthetic-late-fee-policy-0.',
        );
        expect(
            view.getByRole('region', { name: 'Recent repayments' }),
        ).toHaveTextContent('Shared with 318 investors');
    });

    it('pays the next instalment with the quoted total and the servicing revision, then follows the server', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'REPAYMENT_RECEIVED',
                    data: {
                        next: {
                            url: '/preview/business-repayments-allocating',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        render(<BusinessRepayments {...props()} />);

        fireEvent.click(
            screen.getByRole('button', { name: 'Pay RWF 3,465,000' }),
        );

        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/business-repayments-allocating' },
            ]),
        );
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/business-repayments-paid',
            method: 'post',
            body: {
                identity_context_revision: 4,
                note_id: 'RZN-2026-0418',
                option: 'next_instalment',
                expected_servicing_revision: 7,
                quoted_total: { currency: 'RWF', amount: '3465000' },
            },
        });
    });

    it('reads a note paid ahead as current, with the next instalment not yet covered', () => {
        render(<BusinessRepayments {...props(aheadFixture)} />);

        const view = within(sheet());

        expect(view.getByRole('region', { name: 'Due now' })).toHaveTextContent(
            'Next instalment · #4',
        );
        expect(
            view.getByRole('region', { name: 'Repayment schedule' }),
        ).toHaveTextContent('#3 · paid 5 Dec 2026Paid');
        expect(
            view.getByRole('link', { name: 'Add money to your wallet' }),
        ).toBeVisible();
    });

    it('shows what is due today and pays it', async () => {
        render(<BusinessRepayments {...props(dueFixture)} />);

        const due = within(sheet()).getByRole('region', { name: 'Due now' });

        expect(due).toHaveTextContent('Due today');
        expect(due).toHaveTextContent(
            'If it is still unpaid on 30 Dec 2026, a late fee of about RWF 173,250 is added. This is a projection.',
        );

        fireEvent.click(
            screen.getByRole('button', { name: 'Pay RWF 3,465,000' }),
        );
        await waitFor(() => expect(inertia.calls).toHaveLength(1));
        expect(inertia.calls[0].body).toMatchObject({ option: 'due_now' });
    });

    it('shows an overdue instalment with its arrears, late-fee lines and the next collection attempt', () => {
        render(<BusinessRepayments {...props(overdueFixture)} />);

        const view = within(sheet());

        expect(sheet()).toHaveTextContent('· Overdue');

        const due = view.getByRole('region', { name: 'Due now' });

        expect(due).toHaveTextContent(
            "In arrears since 24 Dec 2026. Your notes can't be traded until it is paid.",
        );
        expect(
            within(due).getByRole('link', { name: 'RWF 3,811,500' }),
        ).toHaveAttribute('href', '/preview/business-repayments-overdue');
        expect(due).toHaveTextContent('8 days past due');
        expect(due).toHaveTextContent('Late feesRWF 346,500');
        expect(due).toHaveTextContent(
            "We'll collect it from your Rozine wallet on 1 Jan 2027.",
        );

        const lines = view.getByRole('list', {
            name: 'Late fees on this instalment',
        });

        expect(lines).toHaveTextContent(
            'Due date missed · 23 Dec 2026 · addedRWF 173,250',
        );
        expect(lines).toHaveTextContent(
            'Day 7 unpaid · 30 Dec 2026 · addedRWF 173,250',
        );
        expect(
            view.getByRole('region', { name: 'Repayment schedule' }),
        ).toHaveTextContent('RWF 3,811,500');
    });

    it('offers a top-up instead of Pay when the wallet is short', () => {
        render(<BusinessRepayments {...props(shortFixture)} />);

        const pay = within(sheet()).getByRole('region', {
            name: 'Pay from your wallet',
        });

        expect(
            within(pay).queryByRole('button', { name: /Pay RWF/u }),
        ).not.toBeInTheDocument();
        expect(within(pay).getByRole('status')).toHaveTextContent(
            "There isn't enough in your wallet for this payment.",
        );
        expect(
            within(pay).getByRole('link', {
                name: 'Add money to your wallet',
            }),
        ).toHaveAttribute('href', '/preview/business-wallet-deposit');
    });

    it('hides Pay when the server no longer lists repayment.pay, even with funds', () => {
        const withdrawn = props();

        withdrawn.allowed_actions = [];
        render(<BusinessRepayments {...withdrawn} />);

        const pay = within(sheet()).getByRole('region', {
            name: 'Pay from your wallet',
        });

        expect(within(pay).queryByRole('button')).not.toBeInTheDocument();
        expect(within(pay).queryByRole('status')).not.toBeInTheDocument();
    });

    it('lets the business choose between what is due and the next instalment', () => {
        const both = props(dueFixture);

        both.pay.options.push({
            ...both.pay.options[0],
            key: 'next_instalment',
            instalment_indexes: [4],
        });
        both.pay.funding.sufficient.next_instalment = true;
        render(<BusinessRepayments {...both} />);

        const choice = within(sheet()).getByRole('radiogroup', {
            name: 'Pay from your wallet',
        });

        expect(
            within(choice).getByRole('radio', { name: /What's due now/u }),
        ).toBeChecked();
        fireEvent.click(
            within(choice).getByRole('radio', {
                name: /Next instalment early/u,
            }),
        );
        expect(
            within(choice).getByRole('radio', {
                name: /Next instalment early/u,
            }),
        ).toBeChecked();
        expect(sheet()).toHaveTextContent('Pays instalment 4 early');
    });

    it('shows a recorded payment as being shared, never as paid, and polls boundedly', () => {
        vi.useFakeTimers();
        render(<BusinessRepayments {...props(allocatingFixture)} />);

        const view = within(sheet());
        const receipt = view.getByRole('region', { name: 'Payment recorded' });

        expect(receipt).toHaveTextContent('Being shared with your investors');
        expect(receipt).toHaveTextContent('RWF 3,465,000');
        expect(receipt).toHaveTextContent('ReferenceRZ-RPY-1223');
        expect(
            view.getByRole('region', { name: 'Repayment schedule' }),
        ).toHaveTextContent('#3 · paid 23 Dec 2026Processing');
        expect(
            view.queryByRole('region', { name: 'Pay from your wallet' }),
        ).not.toBeInTheDocument();

        for (let tick = 0; tick < POLL_LIMIT; tick++) {
            act(() => vi.advanceTimersByTime(POLL_INTERVAL_MS));
        }

        expect(inertia.reloads).toHaveLength(POLL_LIMIT);
        expect(inertia.reloads[0]).toMatchObject({
            only: expect.arrayContaining(['servicing', 'receipt']),
        });
    });

    it('shows an allocated payment with the number of investors paid, and closes it', () => {
        render(<BusinessRepayments {...props(paidFixture)} />);

        const receipt = within(sheet()).getByRole('region', {
            name: 'Payment recorded',
        });

        expect(receipt).toHaveTextContent('Shared with 318 investors');
        expect(
            within(receipt).getByRole('link', { name: 'Done' }),
        ).toHaveAttribute('href', '/preview/business-campaign-repaying');
        expect(inertia.reloads).toHaveLength(0);
    });

    it('opens an earlier repayment from the recent list', () => {
        render(<BusinessRepayments {...props(receiptFixture)} />);

        expect(
            within(sheet()).getByRole('region', { name: 'Payment recorded' }),
        ).toHaveTextContent('ReferenceRZ-RPY-1123');
    });

    it('shows an unapplied remainder and a one-investor allocation in the server’s words', () => {
        const odd = props(paidFixture);

        if (odd.receipt === null) {
            throw new Error('fixture has a receipt');
        }

        odd.receipt.unapplied = { currency: 'RWF', amount: '5000' };
        odd.receipt.investors_paid = 1;
        render(<BusinessRepayments {...odd} />);

        const receipt = within(sheet()).getByRole('region', {
            name: 'Payment recorded',
        });

        expect(receipt).toHaveTextContent(
            "RWF 5,000 wasn't needed and stays in your wallet.",
        );
        expect(receipt).toHaveTextContent('Shared with 1 investor');
    });

    it('reads a fully repaid note without a due card or pay panel', () => {
        render(<BusinessRepayments {...props(repaidFixture)} />);

        const view = within(sheet());

        expect(
            view.getByText('This note is fully repaid. Thank you.'),
        ).toBeInTheDocument();
        expect(
            view.queryByRole('region', { name: 'Pay from your wallet' }),
        ).not.toBeInTheDocument();
        expect(
            view.getByRole('region', { name: 'Repayment progress' }),
        ).toHaveTextContent('Fully repaid');
    });

    it('shows no ladder when the late-fee policy is unavailable', () => {
        render(<BusinessRepayments {...props(missingFixture)} />);

        expect(
            within(sheet()).getByRole('region', {
                name: 'If a payment is late',
            }),
        ).toHaveTextContent(
            "The late-fee policy isn't available right now, so no late fee is shown.",
        );
    });

    it('holds a seeded unconfirmed payment and offers no second one', () => {
        render(<BusinessRepayments {...props(unconfirmedFixture)} />);

        expect(
            within(sheet()).getByText('Not yet confirmed'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Pay RWF 3,465,000' }),
        ).toBeDisabled();
        expect(inertia.calls).toHaveLength(0);
    });

    it('explains a changed amount as a version conflict', () => {
        render(<BusinessRepayments {...props(conflictFixture)} />);

        expect(sheet()).toHaveTextContent(
            /Something changed since this page loaded/u,
        );
    });

    it('never resends a payment whose answer was lost', async () => {
        inertia.queue.push(
            fails(503, { code: 'RETRYABLE_CONTENTION' }),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        render(<BusinessRepayments {...props()} />);

        fireEvent.click(
            screen.getByRole('button', { name: 'Pay RWF 3,465,000' }),
        );

        expect(
            await within(sheet()).findByText('Nothing was recorded'),
        ).toBeInTheDocument();
        expect(inertia.calls[1]).toMatchObject({
            method: 'get',
            body: { identity_context_revision: 4, command: 'repayment.pay' },
        });
        expect(inertia.calls[1].url).toBe(
            `/preview/business-repayments-operation-${(inertia.calls[0].body as { request_id: string }).request_id}`,
        );
    });

    it('reads an overdue instalment without a server DPD as overdue, a part-percent step, and an uncounted allocation as still shared', () => {
        const odd = props(overdueFixture);

        odd.servicing.dpd = null;
        odd.ladder = {
            policy_version: 'synthetic-late-fee-policy-1',
            steps: [{ step: 'day_30', dpd: 30, rate_bps: 250 }],
        };
        odd.recent[0].investors_paid = null;
        render(<BusinessRepayments {...odd} />);

        const view = within(sheet());

        expect(view.getByRole('region', { name: 'Due now' })).toHaveTextContent(
            'RWF 3,811,500Overdue',
        );
        expect(
            view.getByRole('region', { name: 'If a payment is late' }),
        ).toHaveTextContent('+2.50%');
        expect(
            view.getByRole('region', { name: 'Recent repayments' }),
        ).toHaveTextContent('Being shared with your investors');
    });

    it('states automatic collection is off, and a restriction other than arrears', () => {
        const off = props();

        off.servicing.autocollect = { mode: 'off', next_attempt_on: null };
        off.servicing.restriction = {
            code: 'RESTRICTION_ACTIVE',
            since: '2026-11-30T23:59:00+02:00',
        };
        render(<BusinessRepayments {...off} />);

        const due = within(sheet()).getByRole('region', { name: 'Due now' });

        expect(due).toHaveTextContent(
            'Automatic collection from your wallet is off.',
        );
        expect(due).toHaveTextContent(
            'Restricted since 30 Nov 2026. You can still repay.',
        );
    });

    it('renders the live-minimal shape over an empty backdrop with nothing offered', () => {
        render(<BusinessRepayments {...props(minimalFixture)} />);

        const view = within(sheet());

        expect(
            view.queryByRole('region', { name: 'Repayment schedule' }),
        ).not.toBeInTheDocument();
        expect(
            view.queryByRole('region', { name: 'Pay from your wallet' }),
        ).not.toBeInTheDocument();
        expect(
            view.queryByRole('region', { name: 'Recent repayments' }),
        ).not.toBeInTheDocument();
        expect(
            view.queryByRole('link', { name: 'RWF 20,790,000' }),
        ).not.toBeInTheDocument();
        expect(
            view.getByRole('region', { name: 'If a payment is late' }),
        ).toHaveTextContent("The late-fee policy isn't available right now");
    });
});
