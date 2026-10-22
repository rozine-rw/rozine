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
    useHttp: () => ({ submit: () => new Promise(() => undefined) }),
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
            sheet.getByText(
                'Every monthly payment must be covered 1.50 times by that cash',
            ),
        ).toBeInTheDocument();
        expect(sheet.getByText('1.50×')).toBeInTheDocument();
        expect(sheet.getByText('6 months · 13.0% flat')).toBeInTheDocument();
        expect(
            sheet.getByText('Three limits · the lowest one applies'),
        ).toBeInTheDocument();
        expect(
            sheet.getAllByText('Repaid at 1.50× cover over 6 months'),
        ).toHaveLength(2);
        expect(
            sheet.getByText(
                'RWF 100,000,000 (35% of a year’s revenue) less RWF 28,483,269 you already owe',
            ),
        ).toBeInTheDocument();
        expect(sheet.getByText('This is your limit')).toBeInTheDocument();
        expect(
            sheet.getByText('Hard ceiling on any single raise'),
        ).toBeInTheDocument();
        expect(sheet.getByText('RWF 34,690,000')).toBeInTheDocument();
        expect(
            sheet.getByText('What is limiting you: What your cash carries'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText(
                'To raise it — Keep more cash each month, or choose a longer term',
            ),
        ).toBeInTheDocument();
        expect(sheet.getByText('RWF 33,916,731')).toBeInTheDocument();
        expect(sheet.getByRole('link', { name: 'Raise' })).toHaveAttribute(
            'href',
            '/preview/business-apply-business',
        );
        expect(
            sheet.getByRole('progressbar', { name: 'Track record' }),
        ).toHaveValue(87);
        expect(sheet.getByText('RWF 28M')).toBeInTheDocument();
        expect(sheet.getByText('18.4%')).toBeInTheDocument();
        expect(sheet.getByText('RWF 46.1M')).toBeInTheDocument();
    });

    it('names the exposure limit when it binds, without a raise link', () => {
        const page = props(ratingFixture);
        const sizing = page.sizing as CapacitySizing;

        sizing.limits = sizing.limits.map((limit) => ({
            ...limit,
            binding: limit.key === 'exposure',
        }));
        page.links.raise = null;
        render(<BusinessRating {...page} />);

        const sheet = within(healthSheet());

        expect(
            sheet.getByText('What is limiting you: Exposure limit'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText(
                'To raise it — Grow your revenue, or repay what you owe',
            ),
        ).toBeInTheDocument();
        expect(
            sheet.queryByRole('link', { name: 'Raise' }),
        ).not.toBeInTheDocument();
    });

    it('names the first limit when none is marked, and the policy maximum when it binds', () => {
        const page = props(ratingFixture);
        const sizing = page.sizing as CapacitySizing;

        sizing.limits = sizing.limits.map((limit) => ({
            ...limit,
            binding: false,
        }));
        const { unmount } = render(<BusinessRating {...page} />);

        expect(
            within(healthSheet()).getByText(
                'What is limiting you: What your cash carries',
            ),
        ).toBeInTheDocument();
        unmount();

        sizing.limits = sizing.limits.map((limit) => ({
            ...limit,
            binding: limit.key === 'policy_max',
        }));
        render(<BusinessRating {...page} />);

        expect(
            within(healthSheet()).getByText(
                'To raise it — None — this is absolute',
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

    it('draws no financials the server has not published', () => {
        const page = props(pendingFixture);

        page.financials = null;
        render(<BusinessRating {...page} />);

        const sheet = within(healthSheet());

        expect(sheet.getByText('Pending audit')).toBeInTheDocument();
        expect(sheet.queryByText('Financials')).not.toBeInTheDocument();
        expect(sheet.queryByText('RWF 28M')).not.toBeInTheDocument();
    });

    it('gives the engine’s reasons when it will not rate', () => {
        render(<BusinessRating {...props(refusedFixture)} />);

        expect(within(healthSheet()).getByRole('status')).toHaveTextContent(
            'Fewer than 12 months of bank statements on file',
        );
    });
});
