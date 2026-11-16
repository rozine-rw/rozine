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

    describe('Not now (#644)', () => {
        beforeEach(() => window.sessionStorage.clear());

        it('lets an Investor put the statements off to read their portfolio, until the app opens again', async () => {
            const user = userEvent.setup();
            inertia.shared = { risk_acknowledgement: due() };
            inertia.url = '/investor/portfolio';
            const view = render(<RiskGate />);

            expect(
                screen.queryByText(
                    'Adding money, your cart and checkout open once you have confirmed these.',
                ),
            ).not.toBeInTheDocument();
            await user.click(screen.getByRole('button', { name: 'Not now' }));

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            expect(inertia.visit).not.toHaveBeenCalled();
            view.unmount();

            // Another page in the same visit stays open.
            inertia.url = '/investor/holdings/01hz';
            const utils = render(<RiskGate />);
            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            utils.unmount();

            // A new visit (fresh session) asks again.
            window.sessionStorage.clear();
            render(<RiskGate />);
            expect(screen.getByRole('dialog')).toBeInTheDocument();
        });

        it.each([
            '/investor/wallet',
            '/investor/wallet?movement=external',
            '/investor/wallet?kind=withdraw',
        ])(
            'lets a holder read %s and withdraw once the statements are put off (#660)',
            (url) => {
                window.sessionStorage.setItem(
                    'rozine.risk-gate.later.brs-3.0-kyc-r05',
                    '1',
                );
                inertia.shared = { risk_acknowledgement: due() };
                inertia.url = url;
                render(<RiskGate />);

                expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            },
        );

        it('keeps adding money behind the statements and sends Not now back to the wallet (#660)', async () => {
            const user = userEvent.setup();
            window.sessionStorage.setItem(
                'rozine.risk-gate.later.brs-3.0-kyc-r05',
                '1',
            );
            inertia.shared = { risk_acknowledgement: due() };
            inertia.url = '/investor/wallet?kind=deposit';
            render(<RiskGate />);

            await user.click(
                screen.getByRole('button', { name: 'Not now, back to Wallet' }),
            );

            expect(inertia.visit).toHaveBeenCalledWith('/investor/wallet');
        });

        it('closes on Escape like Not now', async () => {
            const user = userEvent.setup();
            inertia.shared = { risk_acknowledgement: due() };
            inertia.url = '/investor/market';
            render(<RiskGate />);

            await user.keyboard('{Escape}');

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        });

        it.each(['/investor/cart', '/investor/deals/01hzcampaign/checkout'])(
            'keeps %s behind the statements and offers Portfolio instead',
            async (url) => {
                const user = userEvent.setup();
                window.sessionStorage.setItem(
                    'rozine.risk-gate.later.brs-3.0-kyc-r05',
                    '1',
                );
                inertia.shared = { risk_acknowledgement: due() };
                inertia.url = url;
                render(<RiskGate />);

                expect(screen.getByRole('dialog')).toHaveTextContent(
                    'Adding money, your cart and checkout open once you have confirmed these.',
                );
                await user.click(
                    screen.getByRole('button', {
                        name: 'Not now, go to Portfolio',
                    }),
                );

                expect(inertia.visit).toHaveBeenCalledWith(
                    '/investor/portfolio',
                );
            },
        );

        it('asks again when the wording changes, even after Not now', () => {
            window.sessionStorage.setItem(
                'rozine.risk-gate.later.brs-3.0-kyc-r05',
                '1',
            );
            inertia.shared = {
                risk_acknowledgement: { ...due(), version: 'brs-3.1-kyc-r05' },
            };
            inertia.url = '/investor/portfolio';
            render(<RiskGate />);

            expect(screen.getByRole('dialog')).toBeInTheDocument();
        });

        it('still shows the statements, and Not now still closes them, without browser storage', async () => {
            const user = userEvent.setup();
            const read = vi
                .spyOn(Storage.prototype, 'getItem')
                .mockImplementation(() => {
                    throw new Error('blocked');
                });
            const write = vi
                .spyOn(Storage.prototype, 'setItem')
                .mockImplementation(() => {
                    throw new Error('blocked');
                });
            inertia.shared = { risk_acknowledgement: due() };
            inertia.url = '/investor/deals';
            render(<RiskGate />);

            expect(screen.getByRole('dialog')).toBeInTheDocument();
            await user.click(screen.getByRole('button', { name: 'Not now' }));
            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            read.mockRestore();
            write.mockRestore();
        });

        it('reads an empty URL as a reading page', () => {
            inertia.shared = { risk_acknowledgement: due() };
            inertia.url = undefined as unknown as string;
            render(<RiskGate />);

            expect(
                screen.getByRole('button', { name: 'Not now' }),
            ).toBeInTheDocument();
        });
    });
});
