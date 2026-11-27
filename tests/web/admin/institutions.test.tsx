import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminInstitutions from '@/pages/admin/institutions';
import type {
    AdminInstitutionsProps,
    InstitutionDetail,
    InstitutionRow,
} from '@/types/admin';
import coverageFixture from '../../../resources/fixtures/ui/admin-coverage.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const shell = structuredClone(coverageFixture.props) as unknown as Omit<
    AdminInstitutionsProps,
    | 'stats'
    | 'chips'
    | 'shown'
    | 'total'
    | 'rows'
    | 'institution'
    | 'application'
    | 'applications_close'
>;

const link = (url: string) => ({ url, method: 'get' as const });
const rwf = (amount: string) => ({ currency: 'RWF' as const, amount });

const row = (
    overrides: Partial<InstitutionRow> & Pick<InstitutionRow, 'id' | 'name'>,
): InstitutionRow => ({
    party_id: `party-${overrides.id}`,
    status: 'submitted',
    rdb_code: '108123456',
    type: 'pension_fund',
    monthly_commitment: rwf('45000000'),
    representative: 'Aline Uwase',
    email: `${overrides.id}@example.test`,
    wallet: rwf('0'),
    invested: rwf('0'),
    holdings: 0,
    submitted_at: '2027-01-20T08:00:00+02:00',
    approved_at: null,
    link: link(`/admin/institutions?institution=${overrides.id}`),
    ...overrides,
});

const ROWS: InstitutionRow[] = [
    row({ id: 'A1', name: 'Kivu Pension Fund', status: 'approved' }),
    row({ id: 'B2', name: '', rdb_code: '', type: '', representative: '' }),
];

const page = (
    overrides: Partial<AdminInstitutionsProps> = {},
): AdminInstitutionsProps => ({
    ...shell,
    nav: { ...shell.nav, institutions: link('/admin/institutions') },
    search: '',
    server_time: '2027-01-23T10:00:00+02:00',
    stats: [
        { key: 'institutions', value: { kind: 'count', value: 2 } },
        { key: 'awaiting_review', value: { kind: 'count', value: 1 } },
        {
            key: 'committed_monthly',
            value: { kind: 'money', value: rwf('45000000') },
        },
        { key: 'invested', value: { kind: 'money', value: rwf('0') } },
    ],
    chips: (
        [
            'all',
            'approved',
            'submitted',
            'changes_requested',
            'draft',
            'rejected',
        ] as const
    ).map((key) => ({
        key,
        count: key === 'all' ? 2 : 1,
        link: link(
            key === 'all'
                ? '/admin/institutions'
                : `/admin/institutions?status=${key}`,
        ),
        active: key === 'all',
    })),
    shown: ROWS.length,
    total: ROWS.length,
    rows: ROWS,
    institution: null,
    application: null,
    applications_close: '/admin/institutions',
    ...overrides,
});

const detail = (
    overrides: Partial<InstitutionDetail> = {},
): InstitutionDetail => {
    const { link: _link, holdings: _holdings, ...base } = ROWS[0];

    return {
        ...base,
        invested: rwf('5400000'),
        approved_at: '2027-01-21T08:00:00+02:00',
        approved_by: 'Grace Officer',
        decided_at: '2027-01-21T08:00:00+02:00',
        cap_percent: 25,
        committed: rwf('50000'),
        company: {
            legal_name: 'Kivu Pension Fund',
            rdb_code: '108123456',
            tin: '123456789',
            company_type: 'ltd',
            incorporation_date: '2015-01-01',
            industry: 'Financial services',
            province: 'Kigali',
            district: 'Gasabo',
            sector: 'Remera',
            cell: 'Rukiri I',
            street: 'KG 11 Ave',
            phone: '+250788000000',
        },
        representative_role: {
            title: 'Chief Investment Officer',
            phone: '+250788000001',
            authorised: true,
        },
        account_details: {
            type: 'pension_fund',
            monthly_commitment: '45000000',
            bank_name: 'Bank of Kigali',
            account_name: 'Kivu Pension Fund',
            account_number: '000123456789',
            automates: true,
            own_rules: true,
        },
        directors: [
            {
                full_name: 'Jean Director',
                id_type: 'national_id',
                id_number: '1199080012345678',
                nationality: 'Rwandan',
                pep: true,
            },
        ],
        owners: [
            {
                full_name: 'Marie Owner',
                id_type: 'passport',
                id_number: 'PC99887',
                nationality: 'Kenyan',
                ownership_pct: '40',
                pep: false,
            },
        ],
        no_major_owner: false,
        account: {
            user_id: 7,
            name: 'Aline Uwase',
            email: 'aline@example.test',
            two_factor: true,
            kyc: 'approved',
        },
        documents: [
            {
                id: 'D1',
                slot: 'rdb_certificate',
                filename: 'rdb.pdf',
                media_type: 'application/pdf',
                size_bytes: 2048,
                uploaded_at: '2027-01-20T08:00:00+02:00',
                current: true,
                link: link('/admin/role-applications/A1/documents/D1'),
            },
        ],
        holdings: [
            {
                business_id: 'BIZ1',
                name: 'Kivu Harvest',
                principal: rwf('5400000'),
                notes: 1,
            },
        ],
        raises: [
            {
                campaign_id: 'C1',
                business: 'Kivu Harvest',
                units: '10',
                amount: rwf('50000'),
                raise_amount: rwf('10800000'),
                share_bps: 46,
                cap_percent: 25,
            },
        ],
        auto_deploy: { enabled: false },
        history: [
            {
                revision: 2,
                status: 'approved',
                command: 'approve',
                reason: 'All checks passed.',
                at: '2027-01-21T08:00:00+02:00',
            },
            {
                revision: 1,
                status: 'submitted',
                command: 'submit',
                reason: null,
                at: '2027-01-20T08:00:00+02:00',
            },
        ],
        links: {
            close: link('/admin/institutions'),
            review: link('/admin/institutions?institution=A1&application=A1'),
            user: link('/admin/users?user=7'),
            investor: link('/admin/investors?investor=party-A1'),
        },
        ...overrides,
    };
};

const rowOf = (name: string) =>
    screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(name))[0];

beforeEach(resetInertia);

describe('Institutions', () => {
    it('lists every institution with its status, commitment and representative', () => {
        render(<AdminInstitutions {...page()} />);

        expect(
            within(
                screen.getByRole('navigation', { name: 'Console navigation' }),
            ).getByRole('link', { name: 'Institutions' }),
        ).toHaveAttribute('aria-current', 'page');

        const kivu = rowOf('Kivu Pension Fund');
        expect(kivu).toHaveTextContent('Approved');
        expect(kivu).toHaveTextContent('RDB 108123456');
        expect(
            within(kivu).getByRole('link', { name: 'Kivu Pension Fund' }),
        ).toHaveAttribute('href', '/admin/institutions?institution=A1');

        const pending = rowOf('Name not given yet');
        expect(pending).toHaveTextContent('In review');
        expect(pending).toHaveTextContent('—');
    });

    it('says when nothing matches the filters', () => {
        render(
            <AdminInstitutions {...page({ rows: [], shown: 0, total: 0 })} />,
        );

        expect(
            screen.getByText('No institutions match these filters'),
        ).toBeInTheDocument();
    });

    it('opens the 360 with its raises, investments, company, people, documents and history', () => {
        render(<AdminInstitutions {...page({ institution: detail() })} />);

        const drawer = screen.getByRole('dialog');
        expect(drawer).toHaveTextContent('Share of each raise');
        expect(drawer).toHaveTextContent('Kivu Harvest');
        expect(drawer).toHaveTextContent('Grace Officer');
        expect(drawer).toHaveTextContent('25% of a raise');
        expect(drawer).toHaveTextContent('Jean Director');
        expect(drawer).toHaveTextContent('Marie Owner');
        expect(drawer).toHaveTextContent('Bank of Kigali');
        expect(drawer).toHaveTextContent('All checks passed.');
        expect(
            within(drawer).getByRole('link', { name: 'User account →' }),
        ).toHaveAttribute('href', '/admin/users?user=7');
        expect(
            within(drawer).getByRole('link', { name: 'Investor 360 →' }),
        ).toHaveAttribute('href', '/admin/investors?investor=party-A1');
    });

    it('shows a new institution’s empty 360 without missing sections', () => {
        render(
            <AdminInstitutions
                {...page({
                    institution: detail({
                        approved_at: null,
                        approved_by: null,
                        company: null,
                        representative_role: null,
                        account_details: null,
                        directors: [],
                        owners: [],
                        no_major_owner: true,
                        documents: [],
                        holdings: [],
                        raises: [],
                        history: [],
                        links: {
                            close: link('/admin/institutions'),
                            review: link(
                                '/admin/institutions?institution=A1&application=A1',
                            ),
                            user: null,
                            investor: null,
                        },
                    }),
                })}
            />,
        );

        const drawer = screen.getByRole('dialog');
        expect(drawer).toHaveTextContent(
            'No commitments or holdings in any raise yet.',
        );
        expect(drawer).toHaveTextContent('No issued holdings yet.');
        expect(drawer).toHaveTextContent(
            'The applicant has not given the company details yet.',
        );
        expect(drawer).toHaveTextContent('No documents uploaded yet.');
        expect(drawer).toHaveTextContent('No history yet.');
        expect(
            within(drawer).queryByRole('link', { name: 'User account →' }),
        ).not.toBeInTheDocument();
    });
});
