import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorDeal from '@/pages/investor/deal';
import InvestorProspectus from '@/pages/investor/prospectus';
import type {
    C3DealDetail,
    C3InvestorDealProps,
    InvestorProspectusProps,
    ProspectusSummary,
} from '@/types/investor';
import dealFixture from '../../../resources/fixtures/ui/investor-deal.json';
import { resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

const SUMMARY: ProspectusSummary = {
    version: 2,
    fingerprint: 'abcdef0123456789abcdef0123456789',
    signed_at: '2026-11-10T09:00:00+00:00',
    signed_by: 'Aline Uwase',
};

const READ = { url: '/investor/deals/c1/prospectus', method: 'get' as const };

const withProspectus = (
    deal: C3DealDetail,
    prospectus: C3DealDetail['prospectus'],
    read: typeof READ | null,
): C3DealDetail => ({
    ...deal,
    prospectus,
    links: { ...deal.links, prospectus: read },
});

const dealPage = (
    prospectus: C3DealDetail['prospectus'] = SUMMARY,
    read: typeof READ | null = READ,
): C3InvestorDealProps => {
    const props = structuredClone(dealFixture.props) as C3InvestorDealProps;

    return {
        ...props,
        deal: withProspectus(props.deal, prospectus, read),
        home: {
            ...props.home,
            focus:
                props.home.focus === null
                    ? null
                    : withProspectus(props.home.focus, prospectus, read),
        },
    };
};

const reader = (
    overrides: Partial<InvestorProspectusProps> = {},
): InvestorProspectusProps => ({
    contract_version: 'investor-prospectus-v1',
    server_time: '2026-11-11T09:00:00+00:00',
    deal: { campaign_id: 'c1', name: 'Rugali Textiles' },
    prospectus: SUMMARY,
    inputs: {
        purpose: 'Buy and install a solar cold room.',
        use_of_funds: [
            {
                label: 'Solar cold room',
                amount: '8476000',
            },
            {
                label: 'Installation',
                amount: '2000000',
            },
        ],
        forecast: [
            {
                month: '2027-01',
                revenue: '4000000',
                costs: '1000000',
            },
            {
                month: '2027-02',
                revenue: '6000000',
                costs: '1000000',
            },
        ],
        assumptions: 'Sales hold.',
        downside: 'Pause stock purchases.',
        profiles: [
            { name: 'Aline Uwase', role: 'representative', text: 'Runs it.' },
            { name: 'Jean Mugabo', role: 'director', text: 'Keeps books.' },
            { name: 'Grace Ingabire', role: 'owner', text: 'Owns half.' },
        ],
        largest_customer_share_percent: 30,
        risks: [
            { text: 'Power cuts.', concentration: false },
            { text: 'One hotel buys a third.', concentration: true },
        ],
    },
    facts: {
        principal: '10800000',
        term_months: 2,
        instalment: '1999800',
        verified_monthly_revenue: '4000000',
        latest_sealed_report_at: '2026-11-01T10:00:00+00:00',
        platform_fee: '324000',
        net_of_fee: '10476000',
    },
    quoted_lines: [0],
    contracted_months: ['2027-02'],
    links: {
        deals: { url: '/investor/deals', method: 'get' },
        portfolio: null,
        market: null,
        cart: null,
        profile: null,
        wallet: null,
        notifications: null,
        launcher: { url: '/dashboard', method: 'get' },
        back: { url: '/investor/deals/c1', method: 'get' },
        pdf: { url: '/investor/deals/c1/prospectus.pdf', method: 'get' },
    },
    ...overrides,
});

beforeEach(() => {
    resetInertia();
    setWide(false);
});

describe('Prospectus card', () => {
    it('leads the phone deal page details with the signed version and Read', () => {
        render(<InvestorDeal {...dealPage()} />);

        const card = within(
            screen.getByRole('region', { name: 'Prospectus · version 2' }),
        );

        expect(card.getByText('Signed')).toBeInTheDocument();
        expect(
            card.getByText(/^Signed by Aline Uwase on /u),
        ).toBeInTheDocument();
        expect(card.getByText('Fingerprint abcdef012345…')).toBeInTheDocument();
        expect(card.getByRole('link', { name: 'Read' })).toHaveAttribute(
            'href',
            READ.url,
        );
        const regions = screen
            .getAllByRole('region')
            .map((region) => region.getAttribute('aria-label'));

        expect(regions.indexOf('Prospectus · version 2')).toBeLessThan(
            regions.indexOf('Photos'),
        );
    });

    it('shows the same card in the wide detail panel', () => {
        setWide(true);
        render(<InvestorDeal {...dealPage()} />);

        expect(
            screen.getByRole('region', { name: 'Prospectus · version 2' }),
        ).toBeInTheDocument();
    });

    it('shows no card before a prospectus is signed or without a link to read it', () => {
        const { unmount } = render(<InvestorDeal {...dealPage(null)} />);

        expect(screen.queryByText(/^Prospectus/u)).not.toBeInTheDocument();
        unmount();
        render(<InvestorDeal {...dealPage(SUMMARY, null)} />);
        expect(screen.queryByText(/^Prospectus/u)).not.toBeInTheDocument();
    });
});

describe('Prospectus reader', () => {
    it('shows the signed version, its terms, funds, financials, people and risks', () => {
        render(<InvestorProspectus {...reader()} />);

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Rugali Textiles prospectus',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/^Version 2, signed by Aline Uwase on /u),
        ).toBeInTheDocument();
        expect(
            screen.getByText(`Fingerprint ${SUMMARY.fingerprint}`),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('link', { name: 'Back to the deal' })[0],
        ).toHaveAttribute('href', '/investor/deals/c1');
        const download = screen.getByRole('link', { name: 'Download PDF' });

        expect(download).toHaveAttribute(
            'href',
            '/investor/deals/c1/prospectus.pdf',
        );
        expect(download).toHaveAttribute('download');

        const terms = within(
            screen.getByRole('region', { name: 'Terms and fees' }),
        );

        expect(terms.getByText('RWF 324,000')).toBeInTheDocument();
        expect(terms.getByText('RWF 10,476,000')).toBeInTheDocument();
        expect(terms.getByText('2 months')).toBeInTheDocument();

        const funds = within(
            screen.getByRole('region', { name: 'Use of funds' }),
        );

        expect(funds.getAllByText('Quote on file')).toHaveLength(1);
        expect(funds.getByText('RWF 2,000,000')).toBeInTheDocument();

        const financials = within(
            screen.getByRole('region', {
                name: 'CPA-verified financials and forecast',
            }),
        );

        expect(financials.getAllByText('Signed contract on file')).toHaveLength(
            1,
        );
        expect(financials.getByText('RWF 6,000,000')).toBeInTheDocument();
        expect(
            financials.getByText('Pause stock purchases.'),
        ).toBeInTheDocument();

        const management = within(
            screen.getByRole('region', { name: 'Management' }),
        );

        expect(
            management.getByText('· Official representative'),
        ).toBeInTheDocument();
        expect(management.getByText('· Director')).toBeInTheDocument();
        expect(management.getByText('· Owner')).toBeInTheDocument();

        const risks = within(screen.getByRole('region', { name: 'Risks' }));

        expect(
            risks.getByText('The largest customer brings 30% of revenue.'),
        ).toBeInTheDocument();
        expect(risks.getAllByText('Customer concentration')).toHaveLength(1);
    });

    it('marks a missing sealed report and leaves out an unknown customer share', () => {
        const props = reader();

        render(
            <InvestorProspectus
                {...props}
                facts={{ ...props.facts, latest_sealed_report_at: null }}
                inputs={{
                    ...props.inputs,
                    largest_customer_share_percent: null,
                }}
            />,
        );

        expect(screen.getByText('—')).toBeInTheDocument();
        expect(
            screen.queryByText(/^The largest customer brings/u),
        ).not.toBeInTheDocument();
    });
});
