import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorAudit from '@/pages/auditor/audit';
import type {
    AuditProcedureProps,
    HistoryStage,
    PeopleStage,
    PremisesStage,
    RegistrationStage,
    TradingStage,
} from '@/types/auditor';
import history from '../../../resources/fixtures/ui/auditor-audit-history.json';
import people from '../../../resources/fixtures/ui/auditor-audit-people.json';
import premises from '../../../resources/fixtures/ui/auditor-audit-premises.json';
import registration from '../../../resources/fixtures/ui/auditor-audit-registration.json';
import trading from '../../../resources/fixtures/ui/auditor-audit-trading.json';
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

describe('Baseline visit — step bar', () => {
    it('lists the baseline steps between check-in and photos', () => {
        render(<AuditorAudit {...page(history)} />);
        const bar = within(sheet()).getByRole('list', { name: /steps/iu });

        expect(
            within(bar)
                .getAllByRole('listitem')
                .map((item) => item.textContent),
        ).toEqual([
            'Review',
            'Check-in',
            'History',
            'Premises',
            'People',
            'Registration',
            'Trading start',
            'Photos',
            'Ledger',
            'Seal',
        ]);
    });
});

describe('Baseline visit — history', () => {
    it('shows 36 verified months newest first and posts the tick', () => {
        render(<AuditorAudit {...page(history)} />);
        const rows = within(sheet()).getAllByRole('row').slice(1);

        expect(
            within(sheet()).getByText(
                'The verified money in and out for the last 36 months, newest first.',
            ),
        ).toBeInTheDocument();
        expect(rows).toHaveLength(36);
        expect(rows[0]).toHaveTextContent('Sep 20264,200,0003,100,000');
        expect(rows[35]).toHaveTextContent(/^Oct 2023/u);
        fireEvent.click(
            within(sheet()).getByRole('checkbox', {
                name: 'I compared every month with the statements and they match.',
            }),
        );
        proceed();

        expect(inertia.calls[0].body).toEqual({
            history_checked: true,
            step: 'history',
            ...command,
        });
    });

    it('says when no verified history is on file and shows the refusal', async () => {
        inertia.queue.push(
            invalid({
                history_checked:
                    'Compare every month of the history with the statements before continuing.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<HistoryStage>(history, (stage) => ({
                    ...stage,
                    months: [],
                }))}
            />,
        );

        expect(
            within(sheet()).getByText('No verified history is on file yet.'),
        ).toBeInTheDocument();
        expect(within(sheet()).queryByRole('table')).not.toBeInTheDocument();
        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(
            await within(sheet()).findByText(
                'Compare every month of the history with the statements before continuing.',
            ),
        ).toBeInTheDocument();
        expect(within(sheet()).getByRole('checkbox')).toHaveAttribute(
            'aria-describedby',
            'auditor-history-error',
        );
    });
});

describe('Baseline visit — premises and assets', () => {
    it('records who holds the premises and each asset, trimmed', () => {
        render(<AuditorAudit {...page(premises)} />);
        const rented = within(sheet()).getByRole('radio', { name: 'Rented' });

        expect(rented).toBeChecked();
        fireEvent.click(
            within(sheet()).getByRole('radio', {
                name: 'Owned by the business',
            }),
        );
        const second = within(sheet()).getByRole('listitem', {
            name: 'Asset 2',
        });
        fireEvent.click(
            within(second).getByRole('button', { name: 'Remove asset 2' }),
        );
        fireEvent.click(
            within(sheet()).getByRole('button', { name: 'Add an asset' }),
        );
        const added = within(sheet()).getByRole('listitem', {
            name: 'Asset 2',
        });
        fireEvent.change(within(added).getByLabelText('What it is'), {
            target: { value: '  Generator ' },
        });
        fireEvent.change(
            within(added).getByLabelText('Estimated value (RWF)'),
            { target: { value: '650,000' } },
        );
        expect(
            within(added).getByLabelText('Estimated value (RWF)'),
        ).toHaveValue('650,000');
        fireEvent.change(
            within(
                within(sheet()).getByRole('listitem', { name: 'Asset 1' }),
            ).getByLabelText('What it is'),
            { target: { value: 'Delivery motorbike' } },
        );
        proceed();

        expect(inertia.calls[0].body).toEqual({
            premises_tenure: 'owned',
            assets: [
                { item: 'Delivery motorbike', value: '1800000' },
                { item: 'Generator', value: '650000' },
            ],
            step: 'premises',
            ...command,
        });
    });

    it('starts unanswered with no assets, stops adding at the limit and shows refusals', async () => {
        inertia.queue.push(
            invalid({
                premises_tenure: 'Say who holds the premises.',
                assets: 'Give each asset what it is and its estimated value.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<PremisesStage>(premises, (stage) => ({
                    ...stage,
                    premises_tenure: null,
                    assets: [],
                    max: 1,
                }))}
            />,
        );

        expect(
            within(sheet()).getByText(
                'No assets listed. Continue if the business trades without any.',
            ),
        ).toBeInTheDocument();
        expect(
            within(sheet())
                .getAllByRole('radio')
                .some((radio) => (radio as HTMLInputElement).checked),
        ).toBe(false);
        const add = within(sheet()).getByRole('button', {
            name: 'Add an asset',
        });
        await user.click(add);
        expect(add).toBeDisabled();

        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(
            await within(sheet()).findByText('Say who holds the premises.'),
        ).toBeInTheDocument();
        expect(inertia.calls[0].body).toMatchObject({
            premises_tenure: null,
            assets: [{ item: '', value: '' }],
        });
        expect(
            within(sheet()).getByRole('group', { name: 'The premises are' }),
        ).toHaveAttribute('aria-describedby', 'auditor-premises-error');
        expect(within(sheet()).getByLabelText('What it is')).toHaveAttribute(
            'aria-describedby',
            'auditor-assets-error',
        );
        expect(
            within(sheet()).getByLabelText('Estimated value (RWF)'),
        ).toHaveAttribute('aria-invalid', 'true');
    });
});

describe('Baseline visit — people', () => {
    it('posts both checks once ticked', () => {
        render(<AuditorAudit {...page(people)} />);
        const owners = within(sheet()).getByRole('checkbox', {
            name: /ID of every director and owner/u,
        });
        const management = within(sheet()).getByRole('checkbox', {
            name: 'The management profiles match the people I met.',
        });

        expect(owners).toBeChecked();
        expect(management).not.toBeChecked();
        fireEvent.click(management);
        proceed();

        expect(inertia.calls[0].body).toEqual({
            owners_confirmed: true,
            management_confirmed: true,
            step: 'people',
            ...command,
        });
    });

    it('shows each refusal beside its check', async () => {
        inertia.queue.push(
            invalid({
                owners_confirmed:
                    'Check the identity of every director and owner before continuing.',
                management_confirmed:
                    'Confirm the management profiles before continuing.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<PeopleStage>(people, (stage) => ({
                    ...stage,
                    owners_confirmed: false,
                }))}
            />,
        );
        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(
            await within(sheet()).findByText(
                'Confirm the management profiles before continuing.',
            ),
        ).toBeInTheDocument();
        const [owners, management] = within(sheet()).getAllByRole('checkbox');
        expect(owners).toHaveAttribute(
            'aria-describedby',
            'auditor-owners_confirmed-error',
        );
        expect(management).toHaveAttribute(
            'aria-describedby',
            'auditor-management_confirmed-error',
        );
    });
});

describe('Baseline visit — registration and tax', () => {
    it('posts the certificate check and the tax clearance expiry', () => {
        render(<AuditorAudit {...page(registration)} />);
        const tax = within(sheet()).getByLabelText('Tax clearance expires');

        expect(tax).toHaveValue('2027-03-31');
        fireEvent.change(tax, { target: { value: '2027-06-30' } });
        proceed();

        expect(inertia.calls[0].body).toEqual({
            registration_confirmed: true,
            tax_clearance_expires_on: '2027-06-30',
            step: 'registration',
            ...command,
        });
    });

    it('starts empty and shows the refusals', async () => {
        inertia.queue.push(
            invalid({
                registration_confirmed:
                    'Check the registration certificate before continuing.',
                tax_clearance_expires_on:
                    'Use the date on the certificate, as YYYY-MM-DD.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<RegistrationStage>(registration, (stage) => ({
                    ...stage,
                    registration_confirmed: false,
                    tax_clearance_expires_on: null,
                }))}
            />,
        );
        const checked = within(sheet()).getByRole('checkbox', {
            name: 'The RDB certificate matches the business’s name and code.',
        });
        expect(checked).not.toBeChecked();
        await user.click(checked);
        await user.click(checked);
        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue' }),
        );

        expect(
            await within(sheet()).findByText(
                'Use the date on the certificate, as YYYY-MM-DD.',
            ),
        ).toBeInTheDocument();
        expect(inertia.calls[0].body).toMatchObject({
            registration_confirmed: false,
            tax_clearance_expires_on: '',
        });
        expect(checked).toHaveAttribute(
            'aria-describedby',
            'auditor-registration-error',
        );
        expect(
            within(sheet()).getByLabelText('Tax clearance expires'),
        ).toHaveAttribute('aria-describedby', 'auditor-registration-tax-error');
    });
});

describe('Baseline visit — no tax clearance', () => {
    it('posts no expiry when the business has no certificate, and reopens that way', () => {
        render(<AuditorAudit {...page(registration)} />);
        const none = within(sheet()).getByRole('checkbox', {
            name: 'The business has no tax clearance certificate',
        });
        const tax = within(sheet()).getByLabelText('Tax clearance expires');

        expect(none).not.toBeChecked();
        expect(tax).toBeEnabled();
        fireEvent.click(none);
        expect(tax).toBeDisabled();
        proceed();

        expect(inertia.calls[0].body).toEqual({
            registration_confirmed: true,
            tax_clearance_expires_on: 'none',
            step: 'registration',
            ...command,
        });
    });

    it('opens a saved step without a certificate with the box ticked', () => {
        render(
            <AuditorAudit
                {...page<RegistrationStage>(registration, (stage) => ({
                    ...stage,
                    tax_clearance_expires_on: null,
                }))}
            />,
        );

        expect(
            within(sheet()).getByRole('checkbox', {
                name: 'The business has no tax clearance certificate',
            }),
        ).toBeChecked();
        expect(
            within(sheet()).getByLabelText('Tax clearance expires'),
        ).toBeDisabled();
    });
});

describe('Baseline visit — trading start', () => {
    it('posts the month trading started and moves on to photos', () => {
        render(<AuditorAudit {...page(trading)} />);
        const since = within(sheet()).getByLabelText('Trading since');

        expect(since).toHaveValue('2021-06');
        fireEvent.change(since, { target: { value: '2020-02' } });
        proceed('Continue to photos');

        expect(inertia.calls[0].body).toEqual({
            trading_since: '2020-02',
            step: 'trading',
            ...command,
        });
    });

    it('starts empty and shows the refusal', async () => {
        inertia.queue.push(
            invalid({
                trading_since: 'Use the month trading started, as YYYY-MM.',
            }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...page<TradingStage>(trading, (stage) => ({
                    ...stage,
                    trading_since: null,
                }))}
            />,
        );
        const since = within(sheet()).getByLabelText('Trading since');
        expect(since).toHaveValue('');
        await user.click(
            within(sheet()).getByRole('button', { name: 'Continue to photos' }),
        );

        expect(
            await within(sheet()).findByText(
                'Use the month trading started, as YYYY-MM.',
            ),
        ).toBeInTheDocument();
        expect(since).toHaveAttribute(
            'aria-describedby',
            'auditor-trading-error',
        );
    });
});
