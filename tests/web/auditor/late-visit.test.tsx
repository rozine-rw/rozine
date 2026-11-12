import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorAudit from '@/pages/auditor/audit';
import type {
    AuditProcedureProps,
    AvailableStage,
    LateReasonStage,
} from '@/types/auditor';
import available from '../../../resources/fixtures/ui/auditor-audit-available.json';
import reason from '../../../resources/fixtures/ui/auditor-audit-reason.json';
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

const sheet = () => screen.getByRole('dialog', { name: 'Huye Motors audit' });

const command = {
    audit_id: 'fa_huye',
    expected_revision: 7,
    identity_context_revision: 3,
    request_id: expect.any(String),
};

const proceed = (name = 'Continue') =>
    fireEvent.click(within(sheet()).getByRole('button', { name }));

beforeEach(() => inertia.reset());

describe('Late visit — step bar', () => {
    it('lists the late visit steps', () => {
        render(<AuditorAudit {...page(available)} />);
        const bar = within(sheet()).getByRole('list', { name: /steps/iu });

        expect(
            within(bar)
                .getAllByRole('listitem')
                .map((item) => item.textContent),
        ).toEqual(['Review', 'Check-in', 'Money now', 'Reason', 'Seal']);
    });
});

describe('Late visit — money available now', () => {
    it('posts the proofs ticked and the amount without separators', () => {
        render(<AuditorAudit {...page(available)} />);

        expect(
            within(sheet()).getByText(
                'The repayment is 7 days late. Record what the business could pay with today.',
            ),
        ).toBeInTheDocument();
        fireEvent.click(
            within(sheet()).getByRole('checkbox', { name: /bank/u }),
        );
        fireEvent.click(
            within(sheet()).getByRole('checkbox', { name: /momo/u }),
        );
        fireEvent.click(
            within(sheet()).getByRole('checkbox', { name: /momo/u }),
        );
        fireEvent.click(
            within(sheet()).getByRole('checkbox', { name: /momo/u }),
        );
        const amount = within(sheet()).getByRole('textbox', {
            name: /Available now/u,
        });
        fireEvent.change(amount, { target: { value: '450,000x' } });
        expect(amount).toHaveValue('450,000');
        proceed();

        expect(inertia.calls[0].body).toEqual({
            cash_available: '450000',
            financial_proofs: ['bank', 'momo'],
            step: 'available',
            ...command,
        });
    });

    it('keeps what was saved and shows the refusals', async () => {
        inertia.queue.push(
            invalid({
                cash_available: 'Use a non-negative whole-number amount.',
                financial_proofs:
                    'Inspect every required proof before continuing.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<AvailableStage>(available, (stage) => ({
                    ...stage,
                    cash_available: { amount: '1200', currency: 'RWF' },
                    financial_proofs: stage.financial_proofs.map((proof) => ({
                        ...proof,
                        seen: proof.key === 'bank',
                    })),
                }))}
            />,
        );

        expect(
            within(sheet()).getByRole('textbox', { name: /Available now/u }),
        ).toHaveValue('1,200');
        expect(
            within(sheet()).getByRole('checkbox', { name: /bank/u }),
        ).toBeChecked();
        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(
            await within(sheet()).findByText(
                'Use a non-negative whole-number amount.',
            ),
        ).toBeInTheDocument();
        expect(
            within(sheet()).getByText(
                'Inspect every required proof before continuing.',
            ),
        ).toBeInTheDocument();
    });
});

describe('Late visit — reason', () => {
    it('offers the fixed list and posts the reason on the way to the seal', () => {
        render(<AuditorAudit {...page(reason)} />);
        const list = within(sheet()).getByRole('group', {
            name: 'Why it is late',
        });

        expect(
            within(list)
                .getAllByRole('radio')
                .map((option) => option.parentElement?.textContent),
        ).toEqual([
            'Sales fell',
            'Customers have not paid',
            'An unexpected cost',
            'Money went on stock',
            'Owner away or unwell',
            'Trading has stopped',
            'Other',
        ]);
        expect(
            within(sheet()).getByRole('textbox', { name: 'Note (optional)' }),
        ).toBeInTheDocument();
        fireEvent.click(
            within(list).getByRole('radio', {
                name: 'Customers have not paid',
            }),
        );
        proceed('Review & seal');

        expect(inertia.calls[0].body).toEqual({
            late_reason: 'customers_owe',
            late_reason_note: '',
            step: 'reason',
            ...command,
        });
    });

    it('asks what happened for Other and shows the refusals', async () => {
        inertia.queue.push(
            invalid({
                late_reason: 'Choose why the repayment is late.',
                late_reason_note: 'Use plain text of at most 200 characters.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<LateReasonStage>(reason, (stage) => ({
                    ...stage,
                    late_reason: 'other',
                    late_reason_note: 'Flooded shop.',
                }))}
            />,
        );
        const note = within(sheet()).getByRole('textbox', {
            name: 'Say what happened',
        });

        expect(note).toHaveValue('Flooded shop.');
        await user.clear(note);
        await user.type(note, 'Roof fell in.');
        await user.click(
            within(sheet()).getByRole('button', { name: 'Review & seal' }),
        );

        expect(inertia.calls[0].body).toMatchObject({
            late_reason: 'other',
            late_reason_note: 'Roof fell in.',
        });
        expect(
            await within(sheet()).findByText(
                'Choose why the repayment is late.',
            ),
        ).toBeInTheDocument();
        expect(
            within(sheet()).getByText(
                'Use plain text of at most 200 characters.',
            ),
        ).toBeInTheDocument();
    });
});
