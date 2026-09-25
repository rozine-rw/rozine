import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorHolding from '@/pages/investor/holding';
import InvestorPortfolio from '@/pages/investor/portfolio';
import type {
    C3InvestorHoldingProps,
    C3InvestorPortfolioProps,
} from '@/types/investor';
import arrearsFixture from '../../../resources/fixtures/ui/investor-holding-arrears.json';
import issuedFixture from '../../../resources/fixtures/ui/investor-holding-issued.json';
import holdingMinimalFixture from '../../../resources/fixtures/ui/investor-holding-live-minimal.json';
import maturedFixture from '../../../resources/fixtures/ui/investor-holding-matured.json';
import planFixture from '../../../resources/fixtures/ui/investor-holding-plan.json';
import holdingFixture from '../../../resources/fixtures/ui/investor-holding.json';
import awaitingFixture from '../../../resources/fixtures/ui/investor-portfolio-awaiting-issue.json';
import emptyFixture from '../../../resources/fixtures/ui/investor-portfolio-empty.json';
import portfolioMinimalFixture from '../../../resources/fixtures/ui/investor-portfolio-live-minimal.json';
import portfolioFixture from '../../../resources/fixtures/ui/investor-portfolio.json';
import { resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const portfolio = (fixture: { props: unknown } = portfolioFixture) =>
    structuredClone(fixture.props) as C3InvestorPortfolioProps;
const holding = (fixture: { props: unknown } = holdingFixture) =>
    structuredClone(fixture.props) as C3InvestorHoldingProps;

beforeEach(() => {
    resetInertia();
    setWide(false);
});

describe('Portfolio', () => {
    it('shows value, holdings and health from server facts, three at a time on a phone', async () => {
        const user = userEvent.setup();

        render(<InvestorPortfolio {...portfolio()} />);

        expect(
            screen.getByRole('heading', { name: 'Portfolio' }),
        ).toBeInTheDocument();

        const total = screen.getByRole('region', { name: 'TOTAL VALUE' });

        expect(total).toHaveTextContent('6 businesses');
        expect(total).toHaveTextContent('RWF 4,458,462');
        expect(total).toHaveTextContent('RWF 4.2M');
        expect(total).toHaveTextContent('+RWF 283K');
        expect(total).toHaveTextContent('Next: RWF 859K · Oct ’26');

        const tabs = screen.getByRole('navigation', { name: 'Holdings' });

        expect(
            within(tabs).getByRole('link', { name: 'Active' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(tabs).getByRole('link', { name: 'Matured' }),
        ).not.toHaveAttribute('aria-current');

        const cards = () => screen.getAllByRole('link', { name: /Details/u });

        expect(cards()).toHaveLength(3);
        expect(cards()[0]).toHaveTextContent('GreenLeaf Agro');
        expect(cards()[0]).toHaveTextContent('+10.3%');
        expect(cards()[0]).toHaveTextContent('Invested RWF 800,000');
        expect(cards()[0]).toHaveTextContent('Matures Nov 2026 · 5/6 payments');
        expect(cards()[2]).toHaveTextContent('In arrears');

        await user.click(
            screen.getByRole('button', { name: 'See all 3 more ›' }),
        );
        expect(cards()).toHaveLength(6);
        expect(cards()[5]).toHaveTextContent('Watch');
    });

    it('lists who pays in each upcoming month and the exposure by industry and rating', async () => {
        const user = userEvent.setup();

        render(<InvestorPortfolio {...portfolio()} />);

        const payouts = screen.getByRole('region', {
            name: 'UPCOMING PAYOUTS · RWF',
        });

        expect(
            within(payouts).getByText('October 2026', { selector: 'span' }),
        ).toBeInTheDocument();
        expect(
            within(payouts).getByText('5 businesses pay this month'),
        ).toBeInTheDocument();
        await user.click(
            within(payouts).getByRole('radio', { name: 'December 2026' }),
        );
        expect(
            within(payouts).getByRole('radio', { name: 'December 2026' }),
        ).toBeChecked();
        expect(
            within(payouts).getByText(/business(es)? pays? this month/u),
        ).toBeInTheDocument();

        const industries = screen.getByRole('region', {
            name: 'DIVERSIFICATION BY INDUSTRY',
        });

        expect(within(industries).getByText('Agriculture')).toBeInTheDocument();
        expect(within(industries).getByText('30%')).toBeInTheDocument();

        const risk = screen.getByRole('region', { name: 'RISK BALANCE' });

        expect(within(risk).getByText('55%')).toBeInTheDocument();
        expect(screen.getByText('Well diversified')).toBeInTheDocument();
        expect(screen.getByText('IDLE IN WALLET')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Reinvest ›' }),
        ).toHaveAttribute('href', '/preview/investor-deals');
    });

    it('flags a concentrated position and hides callouts the server does not send', () => {
        const props = portfolio();

        props.concentration = {
            status: 'concentrated',
            business: 'Intare Supply',
            share_pct: 42,
        };
        props.idle = null;
        props.payouts[1].payers = [props.payouts[1].payers[0]];
        const { unmount } = render(<InvestorPortfolio {...props} />);

        expect(screen.getByText('Concentrated position')).toBeInTheDocument();
        expect(
            screen.getByText(/Intare Supply is 42% of your invested capital/u),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Browse to diversify' }),
        ).toBeInTheDocument();
        expect(screen.queryByText('IDLE IN WALLET')).not.toBeInTheDocument();
        unmount();

        props.concentration = null;
        props.totals.next_payout = null;
        render(<InvestorPortfolio {...props} />);
        expect(screen.queryByText('Well diversified')).not.toBeInTheDocument();
        expect(
            screen.queryByText('Concentrated position'),
        ).not.toBeInTheDocument();
        expect(screen.queryByText(/Next:/u)).not.toBeInTheDocument();
    });

    it('explains an empty portfolio and the matured tab', () => {
        const { unmount } = render(
            <InvestorPortfolio {...portfolio(emptyFixture)} />,
        );

        expect(screen.getByText('Nothing held yet')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Browse opportunities' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('region', { name: 'UPCOMING PAYOUTS · RWF' }),
        ).not.toBeInTheDocument();
        unmount();

        const matured = portfolio(emptyFixture);

        matured.tab = 'matured';
        render(<InvestorPortfolio {...matured} />);
        expect(screen.getByText('Nothing here yet')).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Browse opportunities' }),
        ).not.toBeInTheDocument();
    });

    it('shows a falling holding in red', () => {
        const props = portfolio();

        props.holdings[0].gain = { currency: 'RWF', amount: '-12000' };
        props.holdings[0].gain_pct = '-1.5';
        render(<InvestorPortfolio {...props} />);

        expect(screen.getByText('-1.5%')).toBeInTheDocument();
        expect(screen.getByText('-RWF 12,000')).toHaveClass(
            'text-rz-danger-text',
        );
    });

    it('splits list and insights into two columns on a wide screen, four holdings first', () => {
        setWide(true);
        render(<InvestorPortfolio {...portfolio()} />);

        expect(screen.getAllByRole('link', { name: /Details/u })).toHaveLength(
            4,
        );
        expect(
            screen.getByRole('button', { name: 'See all 2 more ›' }),
        ).toBeInTheDocument();
    });
});

describe('Holding detail', () => {
    it('shows the note’s figures, payments, rating and audited reports', async () => {
        const user = userEvent.setup();

        render(<InvestorHolding {...holding()} />);

        expect(
            screen.getByRole('heading', { name: 'GreenLeaf Agro' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/investor-portfolio',
        );
        expect(screen.getByText('800,000')).toBeInTheDocument();
        expect(screen.getByText('98,922')).toBeInTheDocument();
        expect(screen.getByText('13.5% yield')).toBeInTheDocument();
        expect(screen.getByText('898,922')).toBeInTheDocument();
        expect(screen.getByText('5/6 payments')).toBeInTheDocument();
        expect(screen.getAllByText('RWF 149,820')).toHaveLength(2);
        expect(screen.getByText('5/5')).toBeInTheDocument();
        expect(screen.getByText('All on time')).toBeInTheDocument();
        expect(screen.getByText('1 month left')).toBeInTheDocument();
        expect(screen.getByText('83%')).toBeInTheDocument();
        expect(
            screen.getByRole('progressbar', { name: 'REPAYMENT PROGRESS' }),
        ).toHaveValue(83);
        expect(screen.getByText('5 of 6 payments made')).toBeInTheDocument();
        expect(screen.getByText('647 investors')).toBeInTheDocument();
        expect(screen.queryByText('Payment delayed')).not.toBeInTheDocument();

        await user.click(screen.getAllByRole('button', { name: /net/u })[0]);
        expect(
            screen.getByRole('dialog', { name: 'August 2026 monthly report' }),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Close' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('surfaces arrears, the rating change and the recovery timeline at once', () => {
        render(<InvestorHolding {...holding(arrearsFixture)} />);

        expect(screen.getByText('In arrears')).toBeInTheDocument();
        expect(screen.getByText('Payment delayed')).toBeInTheDocument();
        expect(screen.getByText('11 days overdue')).toBeInTheDocument();
        expect(screen.getByText('IN RECOVERY')).toBeInTheDocument();
        expect(screen.getByText('Rating has changed')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Rated Strong 4.0 when you invested · now Stable 3.1',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('-0.6')).toHaveClass('text-rz-danger-text');
        expect(screen.getByText('Recovery plan agreed')).toBeInTheDocument();
        expect(screen.getByText('Repayments resume')).toHaveClass(
            'text-rz-secondary',
        );
        expect(
            screen.getByText(/Your principal remains a claim on the business/u),
        ).toBeInTheDocument();
        expect(screen.getByText('Paused')).toBeInTheDocument();
    });

    it('discloses a declared recovery plan, a default, a freeze and a matured note', () => {
        const { unmount } = render(
            <InvestorHolding {...holding(planFixture)} />,
        );

        expect(screen.getByText('One payment deferred')).toBeInTheDocument();
        expect(screen.getByText('On track')).toBeInTheDocument();
        expect(
            screen.getByText('Harvest delayed by late rains'),
        ).toBeInTheDocument();
        expect(screen.getByText('RWF 154,000')).toBeInTheDocument();
        expect(
            screen.queryByText(/Extra paid to you/u),
        ).not.toBeInTheDocument();
        unmount();

        const defaulted = holding(arrearsFixture);

        defaulted.holding.health = 'defaulted';
        defaulted.holding.on_time = { made: 3, on_time: 1, late: 2 };
        defaulted.holding.months_left = 2;
        defaulted.holding.rating_change = {
            from: { band: 'stable', score: '3.1' },
            to: { band: 'distressed', score: '2.4' },
            reasons: [{ label: 'Audit finding', delta: '+0.1' }],
        };
        defaulted.holding.recovery_plan = {
            state: 'off_track',
            reason: 'Missed catch-up',
            money_arrives: '2027-01-31T00:00:00+02:00',
            deferred: { currency: 'RWF', amount: '80000' },
        };
        const { unmount: unmountSecond } = render(
            <InvestorHolding {...defaulted} />,
        );

        expect(screen.getByText('Payments defaulted')).toBeInTheDocument();
        expect(screen.getByText('2 payments late')).toBeInTheDocument();
        expect(screen.getByText('2 months left')).toBeInTheDocument();
        expect(screen.getByText('+0.1')).not.toHaveClass('text-rz-danger-text');
        expect(screen.getByText('Off track')).toBeInTheDocument();
        unmountSecond();

        const frozen = holding();

        frozen.holding.health = 'frozen';
        frozen.holding.next_payment = null;
        frozen.holding.on_time = { made: 5, on_time: 4, late: 1 };
        frozen.holding.photos = [];
        const { unmount: unmountThird } = render(
            <InvestorHolding {...frozen} />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            'This note is frozen',
        );
        expect(screen.getByText('1 payment late')).toBeInTheDocument();
        expect(screen.queryByText('PHOTOS')).not.toBeInTheDocument();
        unmountThird();

        render(<InvestorHolding {...holding(maturedFixture)} />);
        expect(screen.getAllByText('Matured')).toHaveLength(1);
        expect(screen.getByText('Fully repaid')).toBeInTheDocument();
    });

    it('lays a holding out in two columns on a wide screen', () => {
        setWide(true);
        const props = holding();

        props.holding.health = 'frozen';
        render(<InvestorHolding {...props} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'This note is frozen',
        );
        expect(
            screen.getByRole('progressbar', { name: 'REPAYMENT PROGRESS' }),
        ).toBeInTheDocument();
    });
});

describe('Commitments and issue (C3)', () => {
    it('lists commitments awaiting issue apart from holdings, never counted among them', () => {
        const { unmount } = render(<InvestorPortfolio {...portfolio()} />);

        const awaiting = screen.getByRole('region', { name: 'Awaiting issue' });

        expect(awaiting).toHaveTextContent('Commitments, not yet holdings');
        expect(
            within(awaiting).getByRole('link', { name: /GreenLeaf Agro/u }),
        ).toHaveAttribute('href', '/preview/investor-commitment-confirmed');
        expect(awaiting).toHaveTextContent(
            'Committed — issued after disbursement',
        );
        expect(
            within(awaiting).getByRole('link', { name: /Kivu Coffee/u }),
        ).toHaveTextContent('Payout not yet confirmed — checking');
        expect(awaiting).not.toHaveTextContent(/failed|paid out|refunded/iu);
        expect(awaiting).toHaveTextContent('RWF 30,0006 notes');
        unmount();

        render(<InvestorPortfolio {...portfolio(awaitingFixture)} />);
        expect(
            screen.getByRole('region', { name: 'Awaiting issue' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Nothing held yet')).toBeInTheDocument();
    });

    it('keeps the awaiting section to the active tab and renders the live-minimal shape', () => {
        const matured = portfolio();

        matured.tab = 'matured';
        const { unmount } = render(<InvestorPortfolio {...matured} />);

        expect(
            screen.queryByRole('region', { name: 'Awaiting issue' }),
        ).not.toBeInTheDocument();
        unmount();

        render(<InvestorPortfolio {...portfolio(portfolioMinimalFixture)} />);
        expect(
            screen.queryByRole('region', { name: 'Awaiting issue' }),
        ).not.toBeInTheDocument();
    });

    it('shows how a holding was issued: its receipt, units, local issue time, effective date and dated schedule', () => {
        render(<InvestorHolding {...holding(issuedFixture)} />);

        const record = screen.getByRole('region', { name: 'Issue record' });

        expect(record).toHaveTextContent('Units6 notes · #1201–#1206');
        expect(record).toHaveTextContent('PrincipalRWF 30,000');
        expect(record).toHaveTextContent('Issued21 Sept 2026 · 15:20');
        expect(record).toHaveTextContent(
            'Payout effective21 Sept 2026 · 15:18',
        );
        expect(record).toHaveTextContent('Effective date (Kigali)21 Sept 2026');
        expect(record).toHaveTextContent('RZ-HLD-2201');

        const schedule = within(record).getByRole('table');

        expect(within(schedule).getAllByRole('row')).toHaveLength(7);
        expect(schedule).toHaveTextContent('21 Oct 2026RWF 5,000RWF 675');
        expect(
            screen.getByRole('progressbar', { name: 'REPAYMENT PROGRESS' }),
        ).toHaveValue(0);
        expect(
            screen.getByText('No monthly reports published yet.'),
        ).toBeInTheDocument();
    });

    it('renders the holding live-minimal shape', () => {
        render(<InvestorHolding {...holding(holdingMinimalFixture)} />);

        expect(
            screen.getByRole('region', { name: 'Issue record' }),
        ).toBeInTheDocument();
    });
});
