import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorAudit from '@/pages/auditor/audit';
import type {
    AuditProcedureProps,
    DrawingsStage,
    LoansStage,
} from '@/types/auditor';
import drawings from '../../../resources/fixtures/ui/auditor-audit-drawings.json';
import loans from '../../../resources/fixtures/ui/auditor-audit-loans.json';
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

const COMMAND = {
    audit_id: 'mr_greenleaf',
    expected_revision: 4,
    identity_context_revision: 3,
    request_id: expect.any(String),
};

beforeEach(() => inertia.reset());

describe('Audit procedure — owner drawings', () => {
    it('posts the drawings as whole francs', () => {
        render(<AuditorAudit {...page(drawings)} />);
        const amount = within(sheet()).getByLabelText(
            /Taken by the owners this month/u,
        );

        expect(amount).toHaveValue('150,000');
        fireEvent.change(amount, { target: { value: 'RWF 95,000' } });
        expect(amount).toHaveValue('95,000');
        fireEvent.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(inertia.calls[0].body).toEqual({
            owner_drawings: '95000',
            step: 'drawings',
            ...COMMAND,
        });
    });

    it('starts empty and shows the server’s refusal beside the amount', async () => {
        inertia.queue.push(
            invalid({
                owner_drawings: 'Use a non-negative whole-number amount.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<DrawingsStage>(drawings, (stage) => ({
                    ...stage,
                    owner_drawings: null,
                }))}
            />,
        );
        const amount = within(sheet()).getByLabelText(
            /Taken by the owners this month/u,
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
        expect(amount).toHaveAttribute('aria-invalid', 'true');
        expect(amount).toHaveAttribute(
            'aria-describedby',
            'auditor-drawings-error',
        );
    });
});

describe('Audit procedure — other loans', () => {
    it('edits, adds and removes loans and posts them in order', () => {
        render(<AuditorAudit {...page(loans)} />);
        const first = within(sheet()).getByRole('listitem', {
            name: 'Loan 1',
        });

        expect(within(first).getByLabelText('Lender')).toHaveValue(
            'Bank of Kigali',
        );
        expect(within(first).getByLabelText('Still owed (RWF)')).toHaveValue(
            '2,400,000',
        );
        fireEvent.change(
            within(first).getByLabelText('Monthly payment (RWF)'),
            {
                target: { value: '180,000' },
            },
        );

        fireEvent.click(
            within(sheet()).getByRole('button', { name: 'Add a loan' }),
        );
        const second = within(sheet()).getByRole('listitem', {
            name: 'Loan 2',
        });
        fireEvent.change(within(second).getByLabelText('Lender'), {
            target: { value: ' Umurenge SACCO ' },
        });
        fireEvent.change(within(second).getByLabelText('Still owed (RWF)'), {
            target: { value: '500000' },
        });
        fireEvent.change(
            within(second).getByLabelText('Monthly payment (RWF)'),
            { target: { value: '0' } },
        );
        fireEvent.click(
            within(sheet()).getByRole('button', { name: 'Add a loan' }),
        );
        fireEvent.click(
            within(sheet()).getByRole('button', { name: 'Remove loan 3' }),
        );

        fireEvent.click(
            within(sheet()).getByRole('button', {
                name: 'Explain the difference',
            }),
        );

        expect(inertia.calls[0].body).toEqual({
            loans: [
                {
                    lender: 'Bank of Kigali',
                    outstanding: '2400000',
                    monthly_payment: '180000',
                },
                {
                    lender: 'Umurenge SACCO',
                    outstanding: '500000',
                    monthly_payment: '0',
                },
            ],
            step: 'loans',
            ...COMMAND,
        });
    });

    it('continues with no loans and stops adding at the limit', () => {
        render(
            <AuditorAudit
                {...page<LoansStage>(loans, (stage) => ({
                    ...stage,
                    max: 1,
                    loans: [],
                }))}
            />,
        );

        expect(
            within(sheet()).getByText(
                'No other loans recorded. Continue if the business owes no one else.',
            ),
        ).toBeInTheDocument();
        fireEvent.click(
            within(sheet()).getByRole('button', { name: 'Add a loan' }),
        );
        expect(
            within(sheet()).getByRole('button', { name: 'Add a loan' }),
        ).toBeDisabled();
        fireEvent.click(
            within(sheet()).getByRole('button', { name: 'Remove loan 1' }),
        );

        fireEvent.click(
            within(sheet()).getByRole('button', {
                name: 'Explain the difference',
            }),
        );
        expect(inertia.calls[0].body).toMatchObject({
            loans: [],
            step: 'loans',
        });
    });

    it('ties the server’s refusal to every loan field', async () => {
        inertia.queue.push(
            invalid({ loans: 'Leave out a loan that is fully repaid.' }),
        );
        const { user } = renderWithUser(<AuditorAudit {...page(loans)} />);

        await user.click(
            within(sheet()).getByRole('button', {
                name: 'Explain the difference',
            }),
        );

        expect(
            await within(sheet()).findByText(
                'Leave out a loan that is fully repaid.',
            ),
        ).toBeInTheDocument();
        for (const field of within(sheet())
            .getByRole('listitem', { name: 'Loan 1' })
            .querySelectorAll('input')) {
            expect(field).toHaveAttribute('aria-invalid', 'true');
            expect(field).toHaveAttribute(
                'aria-describedby',
                'auditor-loans-error',
            );
        }
    });
});
