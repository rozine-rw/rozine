import { act, fireEvent, render, screen, within } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { WithdrawPanel } from '@/components/investor/wallet/funding';
import type { BusinessWalletProps } from '@/types/business';
import type { C3InvestorWalletProps } from '@/types/investor';
import businessFixture from '../../../resources/fixtures/ui/business-wallet-withdraw.json';
import investorFixture from '../../../resources/fixtures/ui/investor-wallet-withdraw.json';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

type Funding = C3InvestorWalletProps['funding'];
type Quote = NonNullable<NonNullable<Funding['withdrawal']>['quote']>;
type QuoteReload = {
    onCancelToken?: (token: { cancel: () => void }) => void;
    onSuccess?: (page: { props: { funding: Funding } }) => void;
    onError?: (errors: Record<string, string>) => void;
    onHttpException?: () => void;
    onNetworkError?: () => void;
    onCancel?: () => void;
    onFinish?: () => void;
};

const subjects = [
    {
        name: 'Investor',
        make: () => {
            const props = structuredClone(
                investorFixture.props,
            ) as C3InvestorWalletProps;

            return {
                funding: props.funding,
                action: props.actions.withdraw,
                available: props.wallet.breakdown.available,
                allowed_actions: props.allowed_actions,
                identity_context_revision: props.identity_context_revision,
                operation: props.links.withdraw_operation,
                close: props.links.close,
                wide: false,
                command: 'wallet.withdraw',
                payload: {},
            };
        },
    },
    {
        name: 'Business',
        make: () => {
            const props = structuredClone(
                businessFixture.props,
            ) as BusinessWalletProps;

            return {
                funding: props.funding,
                action: props.actions.withdraw,
                available: props.wallet.available,
                allowed_actions: props.allowed_actions,
                identity_context_revision: props.identity_context_revision,
                operation: props.links.withdrawal_operation,
                close: props.links.close,
                wide: false,
                command: 'business.wallet.withdraw',
                payload: { business_id: props.business.id },
            };
        },
    },
];

beforeEach(() => {
    resetInertia();
    vi.useFakeTimers();
});

afterEach(() => vi.useRealTimers());

describe.each(subjects)('$name withdrawal quote confirmation', ({ make }) => {
    const setup = () => {
        const props = make();
        const view = render(<WithdrawPanel {...props} />);
        const panel = screen.getByRole('form', { name: 'Withdraw' });
        const confirm = () =>
            within(panel).getByRole('button', { name: 'Confirm withdrawal' });
        const edit = (value: string) =>
            fireEvent.change(within(panel).getByLabelText('AMOUNT'), {
                target: { value },
            });
        const start = () => {
            void act(() => vi.advanceTimersByTime(300));

            return inertia.reload.mock.lastCall?.[0] as QuoteReload;
        };
        const fundingFor = (
            amount: string,
            refusal: Quote['refusal'] = null,
        ): Funding => ({
            ...props.funding,
            kind: 'withdraw',
            withdrawal: {
                ...props.funding.withdrawal!,
                quote: {
                    amount: { currency: 'RWF', amount },
                    charge: { currency: 'RWF', amount: '0' },
                    received: { currency: 'RWF', amount },
                    refusal,
                },
            },
        });
        const replace = (funding: Funding) =>
            view.rerender(<WithdrawPanel {...props} funding={funding} />);
        const succeed = (reload: QuoteReload, funding: Funding) => {
            replace(funding);
            act(() => {
                reload.onSuccess?.({ props: { funding } });
                reload.onFinish?.();
            });
        };

        return {
            ...view,
            props,
            panel,
            confirm,
            edit,
            start,
            fundingFor,
            replace,
            succeed,
        };
    };

    it('blocks clicks and form submission immediately after an edit until the matching quote arrives', () => {
        const { panel, confirm, edit, start, fundingFor, succeed, props } =
            setup();

        edit('40,000');
        expect(confirm()).toBeDisabled();
        expect(panel).toHaveTextContent('You receive—');
        fireEvent.click(confirm());
        fireEvent.submit(panel);
        expect(inertia.calls).toHaveLength(0);
        expect(inertia.reload).not.toHaveBeenCalled();

        const reload = start();
        expect(confirm()).toBeDisabled();
        succeed(reload, fundingFor('40000'));
        expect(panel).toHaveTextContent('You receiveRWF 40,000');
        expect(confirm()).toBeEnabled();
        fireEvent.click(confirm());
        expect(inertia.calls[0]).toMatchObject({
            body: {
                ...props.payload,
                amount: { currency: 'RWF', amount: '40000' },
            },
        });
    });

    it('does not revive the initial quote when an edit returns to its original amount', () => {
        const { panel, confirm, edit, start, fundingFor, succeed } = setup();

        edit('40000');
        edit('100000');
        expect(confirm()).toBeDisabled();
        expect(panel).toHaveTextContent('You receive—');
        succeed(start(), fundingFor('100000'));
        expect(confirm()).toBeEnabled();
    });

    it('ignores superseded callbacks and cancels their request before accepting a newer quote', () => {
        const { panel, confirm, edit, start, fundingFor, succeed, replace } =
            setup();

        edit('40000');
        const old = start();
        const cancel = vi.fn(() => {
            old.onCancel?.();
            old.onFinish?.();
        });
        act(() => old.onCancelToken?.({ cancel }));
        edit('60000');
        expect(cancel).toHaveBeenCalledOnce();
        const current = start();

        act(() => {
            const funding = fundingFor('40000');
            replace(funding);
            old.onSuccess?.({ props: { funding } });
            old.onFinish?.();
        });
        expect(confirm()).toBeDisabled();
        expect(panel).toHaveTextContent('You receive—');
        succeed(current, fundingFor('60000'));
        expect(confirm()).toBeEnabled();

        act(() => {
            const funding = fundingFor('40000');
            replace(funding);
            old.onSuccess?.({ props: { funding } });
            old.onFinish?.();
        });
        expect(confirm()).toBeDisabled();
        expect(panel).toHaveTextContent('You receive—');
        fireEvent.submit(panel);
        expect(inertia.calls).toHaveLength(0);
    });

    it.each(['error', 'cancel', 'finish', 'http', 'network'] as const)(
        'keeps a %s response closed even if a late success follows',
        (outcome) => {
            const { panel, confirm, edit, start, fundingFor, replace } =
                setup();

            edit('40000');
            const reload = start();
            act(() => {
                if (outcome === 'error') {
                    reload.onError?.({ amount: 'Unavailable' });
                }

                if (outcome === 'cancel') {
                    reload.onCancel?.();
                }

                if (outcome === 'http') {
                    reload.onHttpException?.();
                }

                if (outcome === 'network') {
                    reload.onNetworkError?.();
                }

                if (outcome === 'finish') {
                    reload.onFinish?.();
                }

                const funding = fundingFor('40000');
                replace(funding);
                reload.onSuccess?.({ props: { funding } });
                reload.onFinish?.();
            });
            expect(confirm()).toBeDisabled();
            fireEvent.submit(panel);
            expect(inertia.calls).toHaveLength(0);
        },
    );

    it('requires the successful quote to match the amount and honor its refusal', () => {
        const { confirm, edit, start, fundingFor, succeed } = setup();

        edit('40000');
        succeed(start(), fundingFor('90000'));
        expect(confirm()).toBeDisabled();
        edit('50000');
        succeed(start(), fundingFor('50000', 'INSUFFICIENT_AVAILABLE_FUNDS'));
        expect(confirm()).toBeDisabled();
        expect(screen.getByRole('alert')).toHaveTextContent(
            'available balance',
        );
    });

    it('normalizes leading zeros before requesting and confirming a quote', () => {
        const { panel, confirm, edit, start, fundingFor, succeed } = setup();

        edit('0040000');
        const reload = start();
        expect(inertia.reload).toHaveBeenLastCalledWith(
            expect.objectContaining({
                data: { kind: 'withdraw', amount: '40000' },
            }),
        );
        succeed(reload, fundingFor('40000'));
        expect(confirm()).toBeEnabled();
        fireEvent.submit(panel);
        expect(inertia.calls[0]).toMatchObject({
            body: { amount: { currency: 'RWF', amount: '40000' } },
        });
    });

    it('clears pending timers and cancels only its own in-flight quote on unmount', () => {
        const first = setup();
        first.edit('40000');
        first.unmount();
        void act(() => vi.advanceTimersByTime(300));
        expect(inertia.reload).not.toHaveBeenCalled();

        const second = setup();
        second.edit('40000');
        const reload = second.start();
        const cancel = vi.fn();
        act(() => reload.onCancelToken?.({ cancel }));
        second.unmount();
        expect(cancel).toHaveBeenCalledOnce();
    });

    it('rejects an older response even when later edits return to its requested amount', () => {
        const { confirm, edit, start, fundingFor, succeed } = setup();

        edit('40000');
        const old = start();
        edit('60000');
        start();
        edit('40000');
        const current = start();

        succeed(old, fundingFor('40000'));
        expect(confirm()).toBeDisabled();
        succeed(current, fundingFor('40000'));
        expect(confirm()).toBeEnabled();
    });

    it('cancels a late obsolete token without replacing the current request token', () => {
        const { edit, start } = setup();

        edit('40000');
        const old = start();
        edit('60000');
        const current = start();
        const oldCancel = vi.fn();
        const currentCancel = vi.fn();
        act(() => {
            current.onCancelToken?.({ cancel: currentCancel });
            old.onCancelToken?.({ cancel: oldCancel });
        });
        expect(oldCancel).toHaveBeenCalledOnce();
        expect(currentCancel).not.toHaveBeenCalled();
        edit('80000');
        expect(currentCancel).toHaveBeenCalledOnce();
    });

    it('clears an empty input without sending another quote request', () => {
        const { panel, confirm, edit } = setup();

        edit('');
        void act(() => vi.advanceTimersByTime(300));
        expect(confirm()).toBeDisabled();
        expect(panel).toHaveTextContent('You receive—');
        expect(inertia.reload).not.toHaveBeenCalled();
        fireEvent.submit(panel);
        expect(inertia.calls).toHaveLength(0);
    });
});
