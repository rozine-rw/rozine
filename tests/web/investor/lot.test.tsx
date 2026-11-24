import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorLot from '@/pages/investor/lot';
import type { InvestorLotProps } from '@/types/investor';
import lotFixture from '../../../resources/fixtures/ui/investor-lot.json';
import { finishReloads, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const page = () =>
    structuredClone(lotFixture.props) as unknown as InvestorLotProps;

beforeEach(() => {
    resetInertia();
    finishReloads();
    setWide(false);
});

describe('Bought lot (QA #663 item 2)', () => {
    it('shows what the lot cost, what it has received and what comes next', () => {
        render(<InvestorLot {...page()} />);

        expect(
            screen.getByRole('heading', { name: 'Kivu Harvest Ltd' }),
        ).toBeInTheDocument();
        expect(screen.getByText('PRICE PAID')).toBeInTheDocument();
        expect(screen.getByText('RWF 10,400')).toBeInTheDocument();
        expect(screen.getByText('RWF 5,200 per unit')).toBeInTheDocument();
        expect(screen.getByText('1/5 payments')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/investor/portfolio?tab=secondary',
        );
        expect(
            screen.getByRole('link', { name: 'Resell on the market' }),
        ).toHaveAttribute('href', '/investor/market');
    });

    it('lists the schedule with what was paid, what went to the seller and what is due', () => {
        render(<InvestorLot {...page()} />);

        const rows = within(
            screen.getByRole('region', { name: 'Payment schedule' }),
        ).getAllByRole('listitem');

        expect(rows).toHaveLength(6);
        expect(
            within(rows[0]).getByText('Paid to the seller'),
        ).toBeInTheDocument();
        expect(within(rows[1]).getByText(/^Paid /)).toBeInTheDocument();
        expect(within(rows[2]).getByText(/^Due /)).toBeInTheDocument();
    });

    it('shows a repaid lot as repaid in full, with nothing to resell', () => {
        const props = page();
        props.lot.repaid = true;
        props.lot.next_payment = null;
        render(<InvestorLot {...props} />);

        expect(screen.getByText('Repaid in full')).toBeInTheDocument();
        expect(screen.queryByText(/^Matures /)).not.toBeInTheDocument();
        expect(screen.getByText('No payment ahead')).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Resell on the market' }),
        ).not.toBeInTheDocument();
    });
});
