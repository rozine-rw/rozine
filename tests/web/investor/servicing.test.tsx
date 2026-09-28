import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorHolding from '@/pages/investor/holding';
import InvestorPortfolio from '@/pages/investor/portfolio';
import type {
    C4InvestorHoldingProps,
    C4InvestorPortfolioProps,
} from '@/types/investor';
import arrearsFixture from '../../../resources/fixtures/ui/investor-holding-servicing-arrears-dpd8.json';
import currentFixture from '../../../resources/fixtures/ui/investor-holding-servicing-current.json';
import collectedFixture from '../../../resources/fixtures/ui/investor-holding-servicing-late-fee-collected.json';
import holdingMinimalFixture from '../../../resources/fixtures/ui/investor-holding-servicing-live-minimal.json';
import processingFixture from '../../../resources/fixtures/ui/investor-holding-servicing-processing.json';
import repaidFixture from '../../../resources/fixtures/ui/investor-holding-servicing-repaid.json';
import earningsEmptyFixture from '../../../resources/fixtures/ui/investor-portfolio-earnings-empty.json';
import earningsMinimalFixture from '../../../resources/fixtures/ui/investor-portfolio-earnings-live-minimal.json';
import earningsFixture from '../../../resources/fixtures/ui/investor-portfolio-earnings.json';
import { resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const holding = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as C4InvestorHoldingProps;
const portfolio = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as C4InvestorPortfolioProps;

const region = (name: string) => screen.getByRole('region', { name });

/** The instalment row that names this index. */
const instalment = (index: number) => {
    const [row] = within(region('Instalments'))
        .getAllByRole('listitem')
        .filter((item) => item.textContent.startsWith(`Instalment ${index} ·`));

    return row;
};

beforeEach(() => {
    resetInertia();
    setWide(false);
});

describe('A servicing holding (investor-servicing-v1)', () => {
    it('shows the server state, what was received by component and what is still scheduled, with its basis', () => {
        render(<InvestorHolding {...holding(currentFixture)} />);
        const summary = region('Repayment');

        expect(within(summary).getByText('On track')).toBeInTheDocument();
        expect(
            within(summary).queryByText(/days? past due/),
        ).not.toBeInTheDocument();
        expect(within(summary).queryAllByRole('listitem')).toEqual([]);
        expect(summary).toHaveTextContent('Principal receivedRWF 10,000');
        expect(summary).toHaveTextContent('Return receivedRWF 1,350');
        expect(summary).toHaveTextContent('Late fees receivedRWF 0');
        expect(summary).toHaveTextContent('Rozine feesRWF 114');
        expect(summary).toHaveTextContent('Net receivedRWF 11,236');
        expect(summary).toHaveTextContent('Principal outstandingRWF 20,000');
        expect(summary).toHaveTextContent(
            'Still scheduledScheduled, not guaranteedRWF 22,700',
        );
        expect(summary).toHaveTextContent(
            'Next paymentScheduled, not guaranteedRWF 5,675 · 21 Dec 2026',
        );
        expect(
            within(summary).getByRole('link', {
                name: 'Basis for Net received',
            }),
        ).toHaveAttribute('href', '/preview/investor-wallet-receipt');
        expect(
            within(summary).getByRole('link', {
                name: 'Basis for Principal outstanding',
            }),
        ).toHaveAttribute('href', '/preview/investor-holding-issued');
        expect(
            within(summary).getByRole('link', {
                name: 'Basis for Still scheduled',
            }),
        ).toHaveAttribute('href', '/preview/investor-holding-issued');
        expect(
            within(summary).queryByRole('link', {
                name: 'Basis for Principal received',
            }),
        ).not.toBeInTheDocument();
    });

    it('lists instalments with this holding’s rights, and payouts newest first with the server’s fee and net', () => {
        render(<InvestorHolding {...holding(currentFixture)} />);

        expect(instalment(1)).toHaveTextContent('Paid');
        expect(instalment(1)).toHaveTextContent(
            'Your share: principal RWF 5,000 · return RWF 675',
        );
        expect(instalment(1)).toHaveTextContent(
            'Paid to you: principal RWF 5,000 · return RWF 675',
        );
        expect(
            within(instalment(1)).getByRole('link', {
                name: 'Payout RWF 5,618 net',
            }),
        ).toHaveAttribute('href', '/preview/investor-wallet-receipt');
        expect(instalment(3)).toHaveTextContent('Upcoming');
        expect(instalment(3)).toHaveTextContent(
            'Paid to you: principal RWF 0 · return RWF 0',
        );
        expect(
            within(instalment(3)).queryByRole('link'),
        ).not.toBeInTheDocument();

        const payouts = within(region('Payouts')).getAllByRole('listitem');

        expect(payouts).toHaveLength(2);
        expect(payouts[0]).toHaveTextContent(
            'Instalment 2 · credited 21 Nov 2026',
        );
        expect(payouts[1]).toHaveTextContent(
            'Instalment 1 · credited 21 Oct 2026',
        );
        expect(payouts[0]).toHaveTextContent('GrossRWF 5,675');
        expect(payouts[0]).toHaveTextContent('Repayment fee (1%)RWF 57');
        expect(payouts[0]).toHaveTextContent('Net to your walletRWF 5,618');
        expect(
            within(payouts[0]).getByRole('link', {
                name: 'Receipt RZ-PAY-3102',
            }),
        ).toHaveAttribute('href', '/preview/investor-wallet-receipt');
        expect(
            screen.queryByRole('region', { name: 'Late fees on this holding' }),
        ).not.toBeInTheDocument();
    });

    it('offers no sale: exit options only render the server’s causes', () => {
        render(<InvestorHolding {...holding(currentFixture)} />);
        const exit = region('Exit options');

        expect(exit).toHaveTextContent("Selling this holding isn't available.");
        expect(within(exit).getAllByRole('listitem')).toHaveLength(1);
        expect(exit).toHaveTextContent("Resale isn't open yet");
        expect(exit).toHaveTextContent('Next record date 21 Dec 2026');
        expect(within(exit).queryByRole('button')).not.toBeInTheDocument();
        expect(within(exit).queryByRole('link')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /sell/i }),
        ).not.toBeInTheDocument();
    });

    it('never shows a processing instalment as paid', () => {
        render(<InvestorHolding {...holding(processingFixture)} />);

        expect(instalment(3)).toHaveTextContent(
            'Processing · not yet paid to you',
        );
        expect(instalment(3)).toHaveTextContent(
            'Paid to you: principal RWF 0 · return RWF 0',
        );
        expect(
            within(instalment(3)).queryByRole('link'),
        ).not.toBeInTheDocument();
        expect(within(region('Payouts')).getAllByRole('listitem')).toHaveLength(
            2,
        );
    });

    it('breaks down late fees line by line, each payable only if collected and not guaranteed', () => {
        render(<InvestorHolding {...holding(arrearsFixture)} />);
        const summary = region('Repayment');

        expect(
            within(summary).getByText('Payment overdue'),
        ).toBeInTheDocument();
        expect(
            within(summary).getByText('8 days past due'),
        ).toBeInTheDocument();
        expect(within(summary).getByRole('listitem')).toHaveTextContent(
            'In arrears since 22 Dec 2026',
        );
        expect(summary).toHaveTextContent(
            'Next paymentScheduled, not guaranteed—',
        );
        expect(instalment(3)).toHaveTextContent('Overdue');

        const fees = region('Late fees on this holding');
        const lines = within(fees)
            .getAllByRole('listitem')
            .filter((item) => item.textContent.startsWith('Instalment'));

        expect(lines).toHaveLength(2);
        expect(lines[0]).toHaveTextContent('Instalment 3 · Due date · 5%');
        expect(lines[0]).toHaveTextContent('Charged · not collected');
        expect(lines[0]).toHaveTextContent('Applies from 22 Dec 2026');
        expect(lines[0]).toHaveTextContent('Your shareRWF 284');
        expect(lines[0]).toHaveTextContent('CollectedRWF 0');
        expect(lines[0]).toHaveTextContent('Not yet collectedRWF 284');
        expect(lines[1]).toHaveTextContent('Instalment 3 · Day 7 · 5%');

        for (const line of lines) {
            const terms = within(line).getByRole('list', { name: 'Terms' });

            expect(terms).toHaveTextContent('Payable only if collected');
            expect(terms).toHaveTextContent('Not guaranteed by Rozine');
            expect(within(line).queryByRole('link')).not.toBeInTheDocument();
        }

        expect(fees).toHaveTextContent('Your share, all linesRWF 568');
        expect(fees).toHaveTextContent(
            'Late fees are paid to you only if and when the business pays them. Rozine does not guarantee them. Disclosure late-fee-disclosure-draft-0.',
        );

        const exit = region('Exit options');

        expect(within(exit).getAllByRole('listitem')).toHaveLength(2);
        expect(exit).toHaveTextContent('The business is behind on payments');
        expect(screen.getByText('8 days overdue')).toBeInTheDocument();
    });

    it('links each collected late-fee share to the payout that credited it', () => {
        render(<InvestorHolding {...holding(collectedFixture)} />);
        const fees = region('Late fees on this holding');

        /* Two status pills, two line figures and the total. */
        expect(within(fees).getAllByText('Collected')).toHaveLength(5);

        for (const link of within(fees).getAllByRole('link', {
            name: 'View payout',
        })) {
            expect(link).toHaveAttribute(
                'href',
                '/preview/investor-wallet-receipt',
            );
        }

        expect(
            within(region('Payouts')).getAllByRole('listitem')[0],
        ).toHaveTextContent('GrossRWF 6,243');
    });

    it('lays a repaid holding out in two columns with its causes and no record date', () => {
        setWide(true);
        render(<InvestorHolding {...holding(repaidFixture)} />);

        expect(
            within(region('Repayment')).getByText('Fully repaid'),
        ).toBeInTheDocument();
        expect(within(region('Payouts')).getAllByRole('listitem')).toHaveLength(
            6,
        );

        const exit = region('Exit options');

        expect(exit).toHaveTextContent('No units are free to sell');
        expect(exit).not.toHaveTextContent('Next record date');
    });

    it.each([
        ['due_today', 0, 'Payment due today', null],
        ['defaulted', 45, 'In default', '45 days past due'],
    ] as const)(
        'names the %s state as the server sends it',
        (state, dpd, label, late) => {
            const props = holding(currentFixture);

            props.holding.servicing.state = state;
            props.holding.servicing.dpd = dpd;
            props.holding.servicing.restrictions = [
                { code: 'DEFAULT', since: '2027-02-05T00:00:00+02:00' },
                { code: 'DISPUTED', since: '2027-02-06T00:00:00+02:00' },
                {
                    code: 'RESTRICTION_ACTIVE',
                    since: '2027-02-07T00:00:00+02:00',
                },
                {
                    code: 'NOTE_INELIGIBLE',
                    since: '2027-02-08T00:00:00+02:00',
                },
            ];
            render(<InvestorHolding {...props} />);
            const summary = region('Repayment');

            expect(within(summary).getByText(label)).toBeInTheDocument();
            expect(within(summary).getAllByRole('listitem')).toHaveLength(4);
            expect(summary).toHaveTextContent('In default since 5 Feb 2027');
            expect(summary).toHaveTextContent('Disputed since 6 Feb 2027');
            expect(summary).toHaveTextContent('Restricted since 7 Feb 2027');
            expect(summary).toHaveTextContent(
                'Note ineligible since 8 Feb 2027',
            );

            if (late === null) {
                expect(
                    within(summary).queryByText(/days? past due/),
                ).not.toBeInTheDocument();
            } else {
                expect(within(summary).getByText(late)).toBeInTheDocument();
            }
        },
    );

    it('renders the live-minimal shape with no basis, no payouts and nothing offered', () => {
        render(<InvestorHolding {...holding(holdingMinimalFixture)} />);

        expect(
            screen.queryByRole('link', { name: /^Basis for/ }),
        ).not.toBeInTheDocument();
        expect(region('Payouts')).toHaveTextContent('No payouts yet.');
        expect(
            within(region('Instalments')).getAllByText('Upcoming'),
        ).toHaveLength(6);
        expect(
            screen.queryByRole('region', { name: 'Late fees on this holding' }),
        ).not.toBeInTheDocument();
        expect(
            within(region('Exit options')).queryByRole('button'),
        ).not.toBeInTheDocument();
    });
});

describe('Portfolio earnings (investor-servicing-v1)', () => {
    it('shows realised earnings apart from what is only scheduled, with each basis', () => {
        render(<InvestorPortfolio {...portfolio(earningsFixture)} />);
        const card = region('Earnings');

        expect(card).toHaveTextContent('InvestedBasisRWF 4,175,000');
        expect(card).toHaveTextContent(
            'Principal outstandingBasisRWF 1,756,665',
        );
        expect(card).toHaveTextContent('Principal backRWF 2,418,335');
        expect(card).toHaveTextContent('ReturnRWF 309,386');
        expect(card).toHaveTextContent('Late feesRWF 3,120');
        expect(card).toHaveTextContent('Rozine feesRWF 30,937');
        expect(card).toHaveTextContent('Net returnBasisRWF 281,569');
        expect(card).toHaveTextContent('This month, netRWF 96,856');
        expect(card).toHaveTextContent('Monthly average, netRWF 528,987');
        expect(card).toHaveTextContent('ProjectedScheduled, not guaranteed');
        expect(card).toHaveTextContent('Return still scheduledRWF 224,614');
        expect(card).toHaveTextContent('Next 3 monthsRWF 1,620,281');
        expect(card).toHaveTextContent('Next payout RWF 151,333 · 3 Oct 2026');
        expect(
            within(card).getByRole('link', { name: 'Basis for Invested' }),
        ).toHaveAttribute('href', '/preview/investor-wallet');
        expect(
            within(card).getByRole('link', { name: 'Basis for Net return' }),
        ).toHaveAttribute('href', '/preview/investor-wallet-receipt');
        expect(
            within(card).getByRole('link', {
                name: 'Basis for Principal outstanding',
            }),
        ).toHaveAttribute(
            'href',
            '/preview/investor-holding-servicing-current',
        );
        expect(
            screen.queryByRole('region', { name: 'TOTAL VALUE' }),
        ).not.toBeInTheDocument();
    });

    it('shows an empty portfolio with no average, no next payout and no basis', () => {
        setWide(true);
        render(<InvestorPortfolio {...portfolio(earningsEmptyFixture)} />);
        const card = region('Earnings');

        expect(card).toHaveTextContent('Monthly average, net—');
        expect(card).not.toHaveTextContent('Next payout');
        expect(within(card).queryByRole('link')).not.toBeInTheDocument();
    });

    it('renders the live-minimal earnings shape', () => {
        render(<InvestorPortfolio {...portfolio(earningsMinimalFixture)} />);

        expect(region('Earnings')).toHaveTextContent('InvestedRWF 0');
    });
});
