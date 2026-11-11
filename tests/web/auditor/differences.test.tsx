import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorAudit from '@/pages/auditor/audit';
import type { AuditProcedureProps, DifferencesStage } from '@/types/auditor';
import differences from '../../../resources/fixtures/ui/auditor-audit-differences.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia, invalid } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const page = (
    change: (stage: DifferencesStage) => DifferencesStage = (stage) => stage,
): AuditProcedureProps => {
    const base = structuredClone(differences.props) as AuditProcedureProps;

    return {
        ...base,
        can_continue: true,
        stage: change(base.stage as DifferencesStage),
    };
};

const sheet = () =>
    screen.getByRole('dialog', { name: 'GreenLeaf Agro audit' });

const amount = (cause: string) =>
    within(sheet()).getByLabelText(`Amount for ${cause}`);

beforeEach(() => inertia.reset());

describe('Audit procedure — explain the monthly difference', () => {
    it('shows the reported, counted and difference figures with the saved amounts', () => {
        render(<AuditorAudit {...page()} />);
        expect(within(sheet()).getByText('RWF 1,210,000')).toBeInTheDocument();
        expect(within(sheet()).getByText('RWF 1,184,000')).toBeInTheDocument();
        expect(within(sheet()).getByText('RWF 26,000')).toBeInTheDocument();
        expect(within(sheet()).getByText('Counted')).toBeInTheDocument();
        expect(amount('Personal spending from the business')).toHaveValue(
            '20,000',
        );
        expect(amount('Entry error')).toHaveValue('');
        expect(within(sheet()).getByRole('status')).toHaveTextContent(
            'RWF 6,000 still to explain.',
        );
    });

    it('keeps the running total as a guide and posts each cause with an amount', () => {
        render(<AuditorAudit {...page()} />);

        fireEvent.change(amount('Entry error'), {
            target: { value: '16,000' },
        });
        expect(amount('Entry error')).toHaveValue('16,000');
        expect(within(sheet()).getByRole('status')).toHaveTextContent(
            'RWF 10,000 more than the difference. Lower an amount.',
        );

        fireEvent.change(amount('Entry error'), {
            target: { value: '6000' },
        });
        expect(within(sheet()).getByRole('status')).toHaveTextContent(
            'The whole difference is explained.',
        );

        fireEvent.change(amount('Sales not recorded'), {
            target: { value: 'abc' },
        });
        fireEvent.click(
            within(sheet()).getByRole('button', {
                name: 'Continue',
            }),
        );

        expect(inertia.calls[0].body).toEqual({
            explanations: [
                { cause: 'personal_spending', amount: '20000' },
                { cause: 'entry_error', amount: '6000' },
            ],
            step: 'differences',
            audit_id: 'mr_greenleaf',
            expected_revision: 4,
            identity_context_revision: 3,
            request_id: expect.any(String),
        });
    });

    it('has nothing to explain when the count matches the statements', () => {
        render(
            <AuditorAudit
                {...page((stage) => ({
                    ...stage,
                    observed: stage.reported,
                    difference: { currency: 'RWF', amount: '0' },
                    explanations: [],
                }))}
            />,
        );

        expect(
            within(sheet()).getByText(
                'The cash counted matches the statements, so there is nothing to explain.',
            ),
        ).toBeInTheDocument();
        expect(within(sheet()).queryByRole('status')).not.toBeInTheDocument();

        fireEvent.click(
            within(sheet()).getByRole('button', {
                name: 'Continue',
            }),
        );
        expect(inertia.calls[0].body).toMatchObject({
            explanations: [],
            step: 'differences',
        });
    });

    it('reads figures the server could not give as unavailable', () => {
        render(
            <AuditorAudit
                {...page((stage) => ({
                    ...stage,
                    reported: null,
                    observed: null,
                    difference: null,
                    explanations: [],
                }))}
            />,
        );

        expect(within(sheet()).getAllByText('Unavailable')).toHaveLength(3);
    });

    it('shows the server’s refusal beside the causes', async () => {
        inertia.queue.push(
            invalid({
                explanations:
                    'Explain the whole difference of RWF 26000, to the franc.',
            }),
        );
        const { user } = renderWithUser(<AuditorAudit {...page()} />);

        await user.click(
            within(sheet()).getByRole('button', {
                name: 'Continue',
            }),
        );

        expect(
            await within(sheet()).findByText(
                'Explain the whole difference of RWF 26000, to the franc.',
            ),
        ).toBeInTheDocument();
        expect(amount('Entry error')).toHaveAttribute('aria-invalid', 'true');
        expect(amount('Entry error')).toHaveAttribute(
            'aria-describedby',
            'auditor-differences-error',
        );
    });
});
