import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import BusinessMarket from '@/pages/business/market';
import type { BusinessMarketProps } from '@/types/business';
import fixture from '../../../resources/fixtures/ui/business-market.json';

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => (
        <span data-testid="head">{title}</span>
    ),
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

const props = () => structuredClone(fixture.props) as BusinessMarketProps;

describe('Business Market', () => {
    it("draws the design's overview with nothing measured, as no secondary-market read exists", () => {
        render(<BusinessMarket {...props()} />);

        expect(
            screen.getByRole('heading', { name: 'Market Overview' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'How your notes are performing on the secondary market.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('term').map((term) => term.textContent),
        ).toEqual([
            'DEMAND',
            'AVG SECONDARY PRICE',
            'VOLUME (7D)',
            'LIQUIDITY SCORE',
            'PERIOD HIGH',
            'PERIOD LOW',
        ]);
        expect(
            screen
                .getAllByRole('definition')
                .map((figure) => figure.textContent),
        ).toEqual(['—', '—', '—', '—', '—', '—']);

        expect(
            screen.getByText('No secondary trades yet.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Your notes on the market' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('None of your notes trade yet'),
        ).toBeInTheDocument();
        expect(screen.queryByText(/RWF/u)).not.toBeInTheDocument();
    });

    it('switches the price range as a view control only', async () => {
        const user = userEvent.setup();

        render(<BusinessMarket {...props()} />);
        const ranges = screen.getByRole('radiogroup', { name: 'Price range' });

        expect(within(ranges).getByRole('radio', { name: '7D' })).toBeChecked();
        await user.click(within(ranges).getByRole('radio', { name: '30D' }));
        expect(
            within(ranges).getByRole('radio', { name: '30D' }),
        ).toBeChecked();
        expect(
            within(ranges).getByRole('radio', { name: '7D' }),
        ).not.toBeChecked();
        expect(
            screen.getByText('No secondary trades yet.'),
        ).toBeInTheDocument();
    });

    it('marks Market current in the app navigation', () => {
        render(<BusinessMarket {...props()} />);

        for (const nav of screen.getAllByRole('navigation', {
            name: 'App navigation',
        })) {
            expect(
                within(nav).getByRole('link', { name: 'Market' }),
            ).toHaveAttribute('aria-current', 'page');
        }
    });
});
