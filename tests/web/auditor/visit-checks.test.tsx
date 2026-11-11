import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorAudit from '@/pages/auditor/audit';
import type {
    AuditProcedureProps,
    ChangesStage,
    ExpiriesStage,
    LoanUseStage,
    StaffStage,
} from '@/types/auditor';
import changes from '../../../resources/fixtures/ui/auditor-audit-changes.json';
import expiries from '../../../resources/fixtures/ui/auditor-audit-expiries.json';
import loanUse from '../../../resources/fixtures/ui/auditor-audit-loan-use.json';
import staff from '../../../resources/fixtures/ui/auditor-audit-staff.json';
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

const proceed = (name = 'Continue') =>
    fireEvent.click(within(sheet()).getByRole('button', { name }));

beforeEach(() => inertia.reset());

describe('Audit procedure — staff count', () => {
    it('posts the RSSB staff count once it is checked against the total page', () => {
        render(<AuditorAudit {...page(staff)} />);
        const count = within(sheet()).getByLabelText('People employed');
        const checked = within(sheet()).getByRole('checkbox', {
            name: /RSSB total page, with the names covered/u,
        });

        expect(count).toHaveValue('6');
        expect(checked).toBeChecked();
        fireEvent.change(count, { target: { value: '1,204 people' } });
        expect(count).toHaveValue('1,204');
        proceed();

        expect(inertia.calls[0].body).toEqual({
            staff_count: '1204',
            rssb_checked: true,
            step: 'staff',
            ...command(7),
        });
    });

    it('starts empty and shows the server’s refusals beside each field', async () => {
        inertia.queue.push(
            invalid({
                staff_count: 'Use a non-negative whole-number amount.',
                rssb_checked:
                    'Check the staff count against the RSSB total page before continuing.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<StaffStage>(staff, (stage) => ({
                    ...stage,
                    staff_count: null,
                    rssb_checked: false,
                }))}
            />,
        );
        const count = within(sheet()).getByLabelText('People employed');
        const checked = within(sheet()).getByRole('checkbox');
        expect(count).toHaveValue('');
        expect(checked).not.toBeChecked();
        await user.click(checked);
        await user.click(checked);

        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(
            await within(sheet()).findByText(
                'Use a non-negative whole-number amount.',
            ),
        ).toBeInTheDocument();
        expect(inertia.calls[0].body).toMatchObject({
            staff_count: '',
            rssb_checked: false,
        });
        expect(count).toHaveAttribute('aria-invalid', 'true');
        expect(count).toHaveAttribute(
            'aria-describedby',
            'auditor-staff-error',
        );
        expect(checked).toHaveAttribute('aria-invalid', 'true');
        expect(checked).toHaveAttribute(
            'aria-describedby',
            'auditor-rssb-error',
        );
    });
});

describe('Audit procedure — tax and RSSB expiry dates', () => {
    it('posts both dates as written on the certificates', () => {
        render(<AuditorAudit {...page(expiries)} />);
        const tax = within(sheet()).getByLabelText('Tax clearance expires');
        const rssb = within(sheet()).getByLabelText('RSSB clearance expires');

        expect(tax).toHaveValue('2027-03-31');
        expect(rssb).toHaveValue('2026-10-20');
        fireEvent.change(tax, { target: { value: '2027-04-30' } });
        fireEvent.change(rssb, { target: { value: '2027-01-15' } });
        proceed();

        expect(inertia.calls[0].body).toEqual({
            tax_clearance_expires_on: '2027-04-30',
            rssb_clearance_expires_on: '2027-01-15',
            step: 'expiries',
            ...command(8),
        });
    });

    it('starts empty and shows the server’s refusal beside each date', async () => {
        inertia.queue.push(
            invalid({
                tax_clearance_expires_on:
                    'Use the date on the certificate, as YYYY-MM-DD.',
                rssb_clearance_expires_on: 'Enter the RSSB expiry date.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<ExpiriesStage>(expiries, (stage) => ({
                    ...stage,
                    tax_clearance_expires_on: null,
                    rssb_clearance_expires_on: null,
                }))}
            />,
        );
        const tax = within(sheet()).getByLabelText('Tax clearance expires');
        const rssb = within(sheet()).getByLabelText('RSSB clearance expires');
        expect(tax).toHaveValue('');

        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(
            await within(sheet()).findByText('Enter the RSSB expiry date.'),
        ).toBeInTheDocument();
        expect(tax).toHaveAttribute(
            'aria-describedby',
            'auditor-tax-expiry-error',
        );
        expect(rssb).toHaveAttribute('aria-invalid', 'true');
        expect(rssb).toHaveAttribute(
            'aria-describedby',
            'auditor-rssb-expiry-error',
        );
    });
});

describe('Audit procedure — use of the loan money', () => {
    it('asks where the money went unless it was used as planned', () => {
        render(<AuditorAudit {...page(loanUse)} />);
        const partly = within(sheet()).getByRole('radio', {
            name: 'Partly as planned',
        });

        expect(partly).toBeChecked();
        expect(
            within(sheet()).getByLabelText('Where did the money go?'),
        ).toHaveValue('Part of the loan paid the October rent.');

        fireEvent.click(
            within(sheet()).getByRole('radio', { name: 'As planned' }),
        );
        const note = within(sheet()).getByLabelText('Note (optional)');
        fireEvent.change(note, { target: { value: '' } });
        proceed();

        expect(inertia.calls[0].body).toEqual({
            loan_use: 'as_planned',
            loan_use_note: '',
            step: 'loan_use',
            ...command(9),
        });
    });

    it('starts unanswered and shows the server’s refusals', async () => {
        inertia.queue.push(
            invalid({
                loan_use: 'Say how the loan money was used.',
                loan_use_note: 'Use plain text of at most 200 characters.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<LoanUseStage>(loanUse, (stage) => ({
                    ...stage,
                    loan_use: null,
                    note: '',
                }))}
            />,
        );
        expect(within(sheet()).getAllByRole('radio')).toHaveLength(3);

        for (const radio of within(sheet()).getAllByRole('radio')) {
            expect(radio).not.toBeChecked();
        }

        await user.click(
            within(sheet()).getByRole('radio', { name: 'Not as planned' }),
        );
        expect(
            within(sheet()).getByLabelText('Where did the money go?'),
        ).toHaveValue('');

        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(
            await within(sheet()).findByText(
                'Say how the loan money was used.',
            ),
        ).toBeInTheDocument();
        expect(
            within(sheet()).getByRole('group', {
                name: 'Use of the loan money',
            }),
        ).toHaveAttribute('aria-describedby', 'auditor-loan-use-error');
        expect(
            within(sheet()).getByLabelText('Where did the money go?'),
        ).toHaveAttribute('aria-describedby', 'auditor-loan-use-note-error');
    });
});

describe('Audit procedure — changes', () => {
    it('asks each change unanswered and posts every yes or no, ending at photos', () => {
        render(<AuditorAudit {...page(changes)} />);
        const director = within(sheet()).getByRole('group', {
            name: 'A change of director',
        });

        for (const radio of within(sheet()).getAllByRole('radio')) {
            expect(radio).not.toBeChecked();
        }

        fireEvent.click(within(director).getByRole('radio', { name: 'Yes' }));

        for (const name of [
            'A new loan or debt',
            'A change of owners or shareholders',
            'A legal claim against the business',
        ]) {
            fireEvent.click(
                within(within(sheet()).getByRole('group', { name })).getByRole(
                    'radio',
                    { name: 'No' },
                ),
            );
        }

        proceed('Continue to photos');

        expect(inertia.calls[0].body).toEqual({
            changes: {
                new_debt: false,
                director_change: true,
                ownership_change: false,
                legal_claim: false,
            },
            step: 'changes',
            ...command(10),
        });
    });

    it('shows recorded answers and the server’s refusal', async () => {
        inertia.queue.push(
            invalid({ changes: 'Answer yes or no for each change.' }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<ChangesStage>(changes, (stage) => ({
                    ...stage,
                    changes: {
                        new_debt: true,
                        director_change: false,
                        ownership_change: false,
                        legal_claim: false,
                    },
                }))}
            />,
        );
        const debt = within(sheet()).getByRole('group', {
            name: 'A new loan or debt',
        });
        expect(within(debt).getByRole('radio', { name: 'Yes' })).toBeChecked();

        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue to photos' }),
        );

        expect(
            await within(sheet()).findByText(
                'Answer yes or no for each change.',
            ),
        ).toBeInTheDocument();
        expect(debt).toHaveAttribute(
            'aria-describedby',
            'auditor-changes-error',
        );
        expect(debt).toHaveAttribute('aria-invalid', 'true');
    });
});
