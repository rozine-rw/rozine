import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorAudit from '@/pages/auditor/audit';
import type {
    AuditProcedureProps,
    CashSalesStage,
    CostsStage,
} from '@/types/auditor';
import cashSales from '../../../resources/fixtures/ui/auditor-audit-cash-sales.json';
import costs from '../../../resources/fixtures/ui/auditor-audit-costs.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia, invalid } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const page = <S extends AuditProcedureProps['stage']>(
    fixture: { props: unknown },
    change: (stage: S) => S = (stage) => stage,
): AuditProcedureProps => {
    const base = structuredClone(fixture.props) as AuditProcedureProps;

    return { ...base, can_continue: true, stage: change(base.stage as S) };
};

const sheet = () =>
    screen.getByRole('dialog', { name: 'GreenLeaf Agro audit' });

const command = (revision: number) => ({
    audit_id: 'mr_greenleaf',
    expected_revision: revision,
    identity_context_revision: 3,
    request_id: expect.any(String),
});

const proceed = () =>
    fireEvent.click(within(sheet()).getByRole('button', { name: 'Continue' }));

beforeEach(() => inertia.reset());

describe('Audit procedure — cash sales not deposited', () => {
    it('posts the undeposited cash sales in whole francs', () => {
        render(<AuditorAudit {...page(cashSales)} />);
        const amount = within(sheet()).getByLabelText(
            /Not deposited this month/u,
        );

        expect(
            within(sheet()).getByRole('heading', {
                name: 'Cash sales not deposited',
            }),
        ).toBeInTheDocument();
        expect(amount).toHaveValue('15,000');
        fireEvent.change(amount, { target: { value: 'RWF 1,250,000' } });
        expect(amount).toHaveValue('1,250,000');
        proceed();

        expect(inertia.calls[0].body).toEqual({
            undeposited_cash_sales: '1250000',
            step: 'cash_sales',
            ...command(7),
        });
    });

    it('starts empty and shows the server’s refusal beside the amount', async () => {
        inertia.queue.push(
            invalid({
                undeposited_cash_sales:
                    'Use a non-negative whole-number amount.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<CashSalesStage>(cashSales, (stage) => ({
                    ...stage,
                    undeposited_cash_sales: null,
                }))}
            />,
        );
        const amount = within(sheet()).getByLabelText(
            /Not deposited this month/u,
        );
        expect(amount).toHaveValue('');

        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(
            await within(sheet()).findByText(
                'Use a non-negative whole-number amount.',
            ),
        ).toBeInTheDocument();
        expect(inertia.calls[0].body).toMatchObject({
            undeposited_cash_sales: '',
        });
        expect(amount).toHaveAttribute('aria-invalid', 'true');
        expect(amount).toHaveAttribute(
            'aria-describedby',
            'auditor-cash-sales-error',
        );
    });
});

describe('Audit procedure — costs in six categories', () => {
    it('lists the six categories, totals them and posts every amount', () => {
        render(<AuditorAudit {...page(costs)} />);
        const stock = within(sheet()).getByLabelText(/Stock and supplies/u);
        const other = within(sheet()).getByLabelText(/^Other/u);

        expect(
            within(sheet())
                .getAllByRole('textbox')
                .map((input) => input.closest('label')?.textContent),
        ).toEqual([
            expect.stringMatching(/^Stock and supplies/u),
            expect.stringMatching(/^Staff/u),
            expect.stringMatching(/^Premises/u),
            expect.stringMatching(/^Transport/u),
            expect.stringMatching(/^Taxes and fees/u),
            expect.stringMatching(/^Other/u),
        ]);
        expect(stock).toHaveValue('400,000');
        expect(other).toHaveValue('');
        expect(
            within(sheet()).getByText('Total costs').parentElement,
        ).toHaveTextContent('RWF 780,000');

        fireEvent.change(stock, { target: { value: '410,500' } });
        fireEvent.change(other, { target: { value: '5000 francs' } });
        expect(
            within(sheet()).getByText('Total costs').parentElement,
        ).toHaveTextContent('RWF 795,500');
        proceed();

        expect(inertia.calls[0].body).toEqual({
            costs: {
                stock: '410500',
                staff: '250000',
                premises: '80000',
                transport: '30000',
                taxes: '20000',
                other: '5000',
            },
            step: 'costs',
            ...command(7),
        });
    });

    it('starts empty and shows the server’s refusal on every category', async () => {
        inertia.queue.push(
            invalid({
                costs: 'Enter an amount for each of the six cost categories.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<CostsStage>(costs, (stage) => ({
                    ...stage,
                    categories: stage.categories.map((category) => ({
                        ...category,
                        amount: null,
                    })),
                }))}
            />,
        );
        const inputs = within(sheet()).getAllByRole('textbox');
        expect(
            inputs.map((input) => (input as HTMLInputElement).value),
        ).toEqual(['', '', '', '', '', '']);
        expect(
            within(sheet()).getByText('Total costs').parentElement,
        ).toHaveTextContent('RWF 0');

        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(
            await within(sheet()).findByText(
                'Enter an amount for each of the six cost categories.',
            ),
        ).toBeInTheDocument();
        expect(inertia.calls[0].body).toMatchObject({
            costs: {
                stock: '',
                staff: '',
                premises: '',
                transport: '',
                taxes: '',
                other: '',
            },
        });
        for (const input of inputs) {
            expect(input).toHaveAttribute('aria-invalid', 'true');
            expect(input).toHaveAttribute(
                'aria-describedby',
                'auditor-costs-error',
            );
        }
    });
});
