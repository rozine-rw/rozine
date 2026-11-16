import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InvestorWallet from '@/pages/investor/wallet';
import type { C3InvestorWalletProps } from '@/types/investor';
import withdrawFixture from '../../../resources/fixtures/ui/investor-wallet-withdraw.json';
import { resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

vi.setConfig({ testTimeout: 30_000 });

beforeEach(() => {
    resetInertia();
    setWide(false);
});

describe('Wallet repayments paid on to the Investor', () => {
    it('lists a note repayment as money in from its business, credited', () => {
        const props = structuredClone(
            withdrawFixture.props,
        ) as C3InvestorWalletProps;

        props.history.items = [
            {
                id: 'we-repay',
                movement: 'external',
                kind: 'repayment',
                direction: 'in',
                amount: { currency: 'RWF', amount: '548840' },
                counterparty: 'Kivu Harvest Ltd',
                occurred_at: '2026-11-16T16:20:00+02:00',
                link: {
                    url: '/investor/wallet?receipt=we-repay',
                    method: 'get',
                },
            },
        ];
        render(<InvestorWallet {...props} />);

        const row = screen.getByRole('link', {
            name: /Repayment · Kivu Harvest Ltd/u,
        });

        expect(within(row).getByText('Credited')).toBeInTheDocument();
        expect(within(row).queryByText(/Withdrawal/u)).not.toBeInTheDocument();
    });

    it('lists resale trades as a market purchase out and a market sale in (QA #656)', () => {
        const props = structuredClone(
            withdrawFixture.props,
        ) as C3InvestorWalletProps;
        const trade = (direction: 'in' | 'out', amount: string) =>
            ({
                id: `we-${direction}`,
                movement: 'external',
                kind: 'resale',
                direction,
                amount: { currency: 'RWF', amount },
                counterparty: 'Kivu Harvest Ltd',
                units: '2',
                occurred_at: '2026-11-16T18:16:00+02:00',
                link: {
                    url: `/investor/wallet?receipt=we-${direction}`,
                    method: 'get',
                },
            }) as const;

        props.history.items = [trade('out', '9000'), trade('in', '8820')];
        render(<InvestorWallet {...props} />);

        expect(
            screen.getByRole('link', {
                name: /Market purchase · Kivu Harvest Ltd/u,
            }),
        ).toHaveTextContent('RWF 9,000');
        expect(
            screen.getByRole('link', {
                name: /Market sale · Kivu Harvest Ltd/u,
            }),
        ).toHaveTextContent('RWF 8,820');
    });
});
