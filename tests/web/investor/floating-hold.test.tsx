import { act, fireEvent, render, screen } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import InvestorCart from '@/pages/investor/cart';
import type {
    C3InvestorCartProps,
    SharedCheckoutHolds,
} from '@/types/investor';
import emptyCartFixture from '../../../resources/fixtures/ui/investor-cart-empty.json';
import { inertia, resetInertia, setWide } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const NOW = '2026-10-30T12:00:00Z';

const page = () =>
    structuredClone(emptyCartFixture.props) as C3InvestorCartProps;

const hold = (
    id: string,
    name: string,
    expiresAt: string,
): SharedCheckoutHolds['holds'][number] => ({
    reservation_id: id,
    deal_name: name,
    amount: { currency: 'RWF', amount: '60000' },
    units: '6',
    expires_at: expiresAt,
    link: {
        url: `/investor/deals/01campaign/checkout?reservation=${id}`,
        method: 'get',
    },
});

beforeEach(() => {
    resetInertia();
    setWide(false);
    vi.useFakeTimers({ toFake: ['Date', 'setInterval', 'clearInterval'] });
    vi.setSystemTime(new Date(NOW));
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Floating checkout hold', () => {
    it('draws nothing without a live hold', () => {
        inertia.shared = { checkout_holds: null };
        const { unmount } = render(<InvestorCart {...page()} />);
        expect(screen.queryByTestId('floating-hold')).not.toBeInTheDocument();
        unmount();

        inertia.shared = {
            checkout_holds: { server_time: NOW, holds: [] },
        };
        render(<InvestorCart {...page()} />);
        expect(screen.queryByTestId('floating-hold')).not.toBeInTheDocument();
    });

    it('counts the soonest hold down, opens on hover, and links to its checkout', () => {
        inertia.shared = {
            checkout_holds: {
                server_time: NOW,
                holds: [
                    hold('r1', 'GreenLeaf Agro', '2026-10-30T12:04:32Z'),
                    hold('r2', 'Kigali Bakes', '2026-10-30T12:05:00Z'),
                ],
            },
        };
        render(<InvestorCart {...page()} />);

        const pill = screen.getByRole('link', {
            name: 'Held for checkout, 04:32 left. Open checkout',
        });
        expect(pill).toHaveAttribute(
            'href',
            '/investor/deals/01campaign/checkout?reservation=r1',
        );
        expect(pill).toHaveTextContent('+1');

        act(() => {
            vi.advanceTimersByTime(2000);
        });
        expect(screen.getByRole('timer')).toHaveTextContent('04:30');

        fireEvent.mouseEnter(screen.getByTestId('floating-hold'));
        const card = screen.getByTestId('floating-hold');
        expect(card).toHaveTextContent('GreenLeaf Agro · 6 notes');
        expect(card).toHaveTextContent('Continue to checkout');
        expect(card).toHaveTextContent('Released in 04:30 unless you confirm');
        expect(
            screen.getByRole('link', { name: '+1 more hold in your wallet' }),
        ).toHaveAttribute('href', '/preview/investor-wallet');

        fireEvent.mouseLeave(card);
        expect(card).not.toHaveTextContent('Continue to checkout');
    });

    it('opens on keyboard focus and closes once focus leaves it', () => {
        inertia.shared = {
            checkout_holds: {
                server_time: NOW,
                holds: [hold('r1', 'GreenLeaf Agro', '2026-10-30T12:00:45Z')],
            },
        };
        render(<InvestorCart {...page()} />);
        const card = screen.getByTestId('floating-hold');

        fireEvent.focus(
            screen.getByRole('link', { name: /Held for checkout/ }),
        );
        expect(card).toHaveTextContent('Continue to checkout');
        expect(card).toHaveTextContent('00:45');

        fireEvent.blur(card, { relatedTarget: document.body });
        expect(card).not.toHaveTextContent('Continue to checkout');
    });

    it('stays open on mouse-leave while focus is still inside it', () => {
        inertia.shared = {
            checkout_holds: {
                server_time: NOW,
                holds: [
                    hold('r1', 'GreenLeaf Agro', '2026-10-30T12:04:32Z'),
                    hold('r2', 'Kigali Bakes', '2026-10-30T12:05:00Z'),
                ],
            },
        };
        render(<InvestorCart {...page()} />);
        const card = screen.getByTestId('floating-hold');

        fireEvent.mouseEnter(card);
        const more = screen.getByRole('link', {
            name: '+1 more hold in your wallet',
        });
        act(() => {
            more.focus();
        });
        fireEvent.mouseLeave(card);
        expect(more).toBeInTheDocument();
        expect(more).toHaveFocus();
        expect(card).toHaveTextContent('Continue to checkout');
    });

    it('drops a hold once its clock runs out', () => {
        inertia.shared = {
            checkout_holds: {
                server_time: NOW,
                holds: [hold('r1', 'GreenLeaf Agro', '2026-10-30T12:00:02Z')],
            },
        };
        render(<InvestorCart {...page()} />);
        expect(screen.getByTestId('floating-hold')).toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(3000);
        });
        expect(screen.queryByTestId('floating-hold')).not.toBeInTheDocument();
    });
});
