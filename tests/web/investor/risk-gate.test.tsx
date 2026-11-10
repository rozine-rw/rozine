import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { RiskGate } from '@/components/investor/risk-gate';
import type { InvestorRiskAcknowledgementDue } from '@/types/investor';
import { answers, fails, inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const ACTION = {
    url: '/investor/risk-statements/acknowledgements',
    method: 'post' as const,
};

const due = (): InvestorRiskAcknowledgementDue => ({
    version: 'brs-3.0-kyc-r05',
    statements_sha256: 'a'.repeat(64),
    statements: [
        { key: 'total_loss', text: 'I can lose all the money I invest.' },
        {
            key: 'no_guarantee',
            text: 'There is no guarantee or collateral.',
        },
    ],
    identity_context_revision: 7,
    action: ACTION,
});

describe('The once-only risk acknowledgement (BRS v3.0 KYC-R05)', () => {
    beforeEach(() => resetInertia());
    afterEach(() => resetInertia());

    it('shows nothing when no acknowledgement is due', () => {
        const { container } = render(<RiskGate />);

        expect(container).toBeEmptyDOMElement();
        inertia.shared = { risk_acknowledgement: null };
        render(<RiskGate />);
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('lists every statement with one tick, and records it against the version and digest', async () => {
        const user = userEvent.setup();
        inertia.shared = { risk_acknowledgement: due() };
        render(<RiskGate />);

        const dialog = screen.getByRole('dialog', {
            name: 'Before you invest',
        });
        expect(dialog).toHaveTextContent('I can lose all the money I invest.');
        expect(dialog).toHaveTextContent(
            'There is no guarantee or collateral.',
        );
        expect(screen.getAllByRole('checkbox')).toHaveLength(1);

        const accept = screen.getByRole('button', { name: 'Continue' });
        expect(accept).toBeDisabled();
        await user.click(
            screen.getByRole('checkbox', {
                name: 'I understand all of the above',
            }),
        );
        expect(accept).toBeEnabled();

        inertia.queue.push(answers({ status: 'completed' }));
        await user.click(accept);

        expect(inertia.calls).toHaveLength(1);
        expect(inertia.calls[0]).toMatchObject({
            url: ACTION.url,
            method: 'post',
            body: {
                identity_context_revision: 7,
                version: 'brs-3.0-kyc-r05',
                statements_sha256: 'a'.repeat(64),
                acknowledged: true,
            },
        });
        await vi.waitFor(() => expect(inertia.reload).toHaveBeenCalledTimes(1));
    });

    it('clears the tick when the wording changed, and says so when recording fails', async () => {
        const user = userEvent.setup();
        inertia.shared = { risk_acknowledgement: due() };
        render(<RiskGate />);
        const tick = screen.getByRole('checkbox');
        const accept = screen.getByRole('button', { name: 'Continue' });

        await user.click(tick);
        inertia.queue.push(fails(409, { code: 'RISK_STATEMENTS_STALE' }));
        await user.click(accept);

        expect(await screen.findByRole('alert')).toBeInTheDocument();
        expect(tick).not.toBeChecked();
        expect(inertia.reload).not.toHaveBeenCalled();

        await user.click(tick);
        inertia.queue.push(fails(500));
        await user.click(accept);

        await vi.waitFor(() => expect(accept).toBeEnabled());
        expect(tick).toBeChecked();
        expect(screen.getByRole('alert')).toBeInTheDocument();
    });
});
