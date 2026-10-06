import { render, screen, within } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import BusinessRating from '@/pages/business/rating';
import type { BusinessRatingProps, CapacitySizing } from '@/types/business';
import driftFixture from '../../../resources/fixtures/ui/business-rating-drift.json';
import pendingFixture from '../../../resources/fixtures/ui/business-rating-pending.json';
import refusedFixture from '../../../resources/fixtures/ui/business-rating-refused.json';
import ratingFixture from '../../../resources/fixtures/ui/business-rating.json';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
}));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessRatingProps;

const healthSheet = () =>
    screen.getByRole('dialog', { name: 'Financial health' });

describe('Financial health', () => {
    it('shows the published rating and its factors', () => {
        render(<BusinessRating {...props(ratingFixture)} />);

        const sheet = within(healthSheet());

        expect(sheet.getByText('Strong · 4.8')).toBeInTheDocument();
        expect(sheet.getByText('4.8')).toBeInTheDocument();
        expect(
            sheet.getByRole('progressbar', { name: 'Repayment history' }),
        ).toHaveValue(96);
        expect(sheet.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-home',
        );
        expect(
            screen.getByRole('link', { name: 'Back to Home' }),
        ).toHaveAttribute('href', '/preview/business-home');
    });

    it('lays out how the engine sized capacity', () => {
        render(<BusinessRating {...props(ratingFixture)} />);

        const sheet = within(healthSheet());

        expect(sheet.getByText('RWF 9,800,000')).toBeInTheDocument();
        expect(
            sheet.getByText('net 18.4% margin + 3.1% depreciation added back'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText(
                'Your CPA verified stock covering at least 1× the raise',
            ),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('RWF 21,000,000 counted on site by your CPA'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText(/A loan that buys stock is repaid/),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('EBITDA RWF 9,800,000/mo × M 1.6'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('35% of RWF 336,000,000 audited annual revenue'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('2.5% of the RWF 3,700,000,000 outstanding book'),
        ).toBeInTheDocument();
        expect(sheet.getByText('Launch phase cap')).toBeInTheDocument();
        expect(sheet.getByText('This is your limit')).toBeInTheDocument();
        expect(
            sheet.getByText('Hard ceiling on any single raise'),
        ).toBeInTheDocument();
        expect(sheet.getAllByText('RWF 80,000,000')).toHaveLength(2);
        expect(
            sheet.getByText('What is limiting you: Launch phase cap'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('To raise it — Lifts at the next phase'),
        ).toBeInTheDocument();
        expect(sheet.getByText('RWF 33,916,731')).toBeInTheDocument();
        expect(sheet.getByRole('link', { name: 'Raise' })).toHaveAttribute(
            'href',
            '/preview/business-apply-business',
        );
        expect(
            sheet.getByText('No stock cover needed at this level'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('Needs 2.0× cover · your stock gives 1.34×'),
        ).toBeInTheDocument();
        expect(sheet.getAllByText('Applied')).toHaveLength(2);
        expect(sheet.getByText('Not yet')).toBeInTheDocument();
        expect(sheet.getByText('RWF 28M')).toBeInTheDocument();
        expect(sheet.getByText('18.4%')).toBeInTheDocument();
        expect(sheet.getByText('RWF 46.1M')).toBeInTheDocument();
    });

    it('explains the other stock states and limits without a raise link', () => {
        const page = props(ratingFixture);
        const sizing = page.sizing as CapacitySizing;

        sizing.cover_tier = 'none';
        sizing.stock = {
            state: 'indicative',
            value: { currency: 'RWF', amount: '18000000' },
        };
        sizing.limits = sizing.limits.map((limit) => ({
            ...limit,
            binding: limit.key === 'capacity',
        }));
        page.links.raise = null;
        render(<BusinessRating {...page} />);

        const sheet = within(healthSheet());

        expect(
            sheet.getByText('No verified stock cover on file yet'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText(/RWF 18,000,000 is normal for your sector/),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('What is limiting you: Cash capacity'),
        ).toBeInTheDocument();
        expect(
            sheet.queryByRole('link', { name: 'Raise' }),
        ).not.toBeInTheDocument();
    });

    it('names the first limit when none is marked and shows no stock', () => {
        const page = props(ratingFixture);
        const sizing = page.sizing as CapacitySizing;

        sizing.cover_tier = 'cover2x';
        sizing.stock = { state: 'none', value: null };
        sizing.limits = sizing.limits.map((limit) => ({
            ...limit,
            binding: false,
        }));
        render(<BusinessRating {...page} />);

        const sheet = within(healthSheet());

        expect(
            sheet.getByText(
                'Your CPA verified stock covering at least 2× the raise',
            ),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('No stock position on file'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('What is limiting you: Cash capacity'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText(
                'To raise it — Raise EBITDA, or have your CPA verify stock cover',
            ),
        ).toBeInTheDocument();
    });

    it('lists what has moved the rating since the audit', () => {
        render(<BusinessRating {...props(driftFixture)} />);

        const sheet = within(healthSheet());

        expect(
            sheet.getByText('Rated Strong 4.9 at audit — now Strong 4.8'),
        ).toBeInTheDocument();
        expect(sheet.getByText('-0.15')).toBeInTheDocument();
        expect(sheet.getByText('+0.05')).toBeInTheDocument();
    });

    it('says the rating waits for the first audit', () => {
        render(<BusinessRating {...props(pendingFixture)} />);

        const sheet = within(healthSheet());

        expect(sheet.getByText('Pending audit')).toBeInTheDocument();
        expect(sheet.getByText('—')).toBeInTheDocument();
        expect(sheet.queryByText('Score breakdown')).not.toBeInTheDocument();
        expect(
            sheet.queryByText('How your capacity is sized'),
        ).not.toBeInTheDocument();
        expect(sheet.queryByRole('status')).not.toBeInTheDocument();
    });

    it('gives the engine’s reasons when it will not rate', () => {
        render(<BusinessRating {...props(refusedFixture)} />);

        expect(within(healthSheet()).getByRole('status')).toHaveTextContent(
            'Fewer than 12 months of bank statements on file',
        );
    });
});
