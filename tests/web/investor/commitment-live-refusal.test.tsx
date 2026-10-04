import type * as InertiaReact from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import InvestorCommitment from '@/pages/investor/commitment';
import type { C3InvestorCommitmentProps } from '@/types/investor';

/** The real Inertia `Link`, which calls `href.toString()`, with the rest of the page's Inertia mocked. */
vi.mock('@inertiajs/react', async () => {
    const actual =
        await vi.importActual<typeof InertiaReact>('@inertiajs/react');
    const mock = await import('./inertia-mock');

    return { ...mock, Link: actual.Link };
});

/** The props `investor.commitments.show` sends with a scoped refusal: no commitment, and null unlive destinations. */
const refused = (code: string, status: number): C3InvestorCommitmentProps => ({
    contract_version: 'investor-primary-v1',
    identity_context_revision: 1,
    server_time: '2026-10-04T12:00:00+00:00',
    allowed_actions: [],
    commitment: null,
    refusal: { code, status },
    links: {
        deals: null,
        portfolio: null,
        profile: null,
        wallet: { url: '/investor/wallet', method: 'get' },
        notifications: null,
        launcher: { url: '/dashboard', method: 'get' },
        close: { url: '/investor/wallet', method: 'get' },
        operation: null,
    },
});

describe('Commitment live refusal', () => {
    it.each([
        ['COMMITMENT_NOT_FOUND', 404],
        ['NOT_FOUND', 404],
        ['COMMITMENT_STATE_UNAVAILABLE', 409],
    ])(
        'renders the %s refusal with real links and hides the unlive portfolio destination',
        (code, status) => {
            render(<InvestorCommitment {...refused(code, status)} />);

            expect(screen.getByRole('alert')).toBeInTheDocument();
            expect(
                screen.queryByRole('link', { name: 'Back to portfolio' }),
            ).not.toBeInTheDocument();
            expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
                'href',
                '/investor/wallet',
            );
        },
    );
});
