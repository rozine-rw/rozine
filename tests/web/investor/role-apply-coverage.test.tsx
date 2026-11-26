import { router } from '@inertiajs/react';
import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import RoleApply from '@/pages/role/apply';
import type { RoleApplicationProps } from '@/types/role-application';
import { inertia, resetInertia } from '../admin/inertia-mock';

vi.mock('@inertiajs/react', async () => {
    const mock = await import('../admin/inertia-mock');

    return { ...mock, router: { ...mock.router, post: vi.fn() } };
});

const props = (): RoleApplicationProps => ({
    role: 'auditor',
    application: {
        status: 'draft',
        revision: 2,
        role: 'auditor',
        step: 'coverage',
        answers: {
            declared: false,
            address: {
                province: '',
                district: '',
                sector: '',
                cell: '',
                street: '',
            },
            coverage: {
                districts: ['Kicukiro'],
                no_conflicts: true,
                will_declare: true,
                ethics: true,
            },
        },
        note: null,
        requested_changes: null,
        uploads: {},
    },
    kyc: 'submitted',
    blockers: [],
    two_factor: true,
    identity_context_revision: 3,
    catalog: {
        institution_types: [],
        min_monthly_commitment: 30000000,
        industries: [],
        districts: ['Gasabo', 'Kicukiro'],
        provinces: { 'Kigali City': ['Gasabo', 'Kicukiro'] },
    },
});

const fill = () => {
    fireEvent.change(screen.getByLabelText('Province'), {
        target: { value: 'Kigali City' },
    });
    fireEvent.change(screen.getByLabelText('District'), {
        target: { value: 'Kicukiro' },
    });
    fireEvent.change(screen.getByLabelText('Sector'), {
        target: { value: 'Kigarama' },
    });
    fireEvent.change(screen.getByLabelText('Cell'), {
        target: { value: 'Karugira' },
    });
    fireEvent.change(screen.getByLabelText('Street'), {
        target: { value: 'KK 709 St.' },
    });
};

beforeEach(() => {
    resetInertia();
    vi.mocked(router.post).mockReset();
});

describe('The Auditor application’s registered address', () => {
    it('saves the address with the coverage step, so a filled address is accepted', () => {
        render(<RoleApply {...props()} />);

        fill();
        fireEvent.click(
            screen.getByRole('button', { name: 'Save and continue' }),
        );

        expect(vi.mocked(router.post).mock.calls[0]?.[1]).toMatchObject({
            step: 'coverage',
            answers: {
                districts: ['Kicukiro'],
                address: {
                    province: 'Kigali City',
                    district: 'Kicukiro',
                    sector: 'Kigarama',
                    cell: 'Karugira',
                    street: 'KK 709 St.',
                },
            },
        });
    });

    it('asks for the street, or the nearest road or a landmark when it has no name', () => {
        render(<RoleApply {...props()} />);

        expect(screen.getByLabelText('Street')).toBeInTheDocument();
        expect(
            screen.getByText(
                'No street name? Enter the nearest road or a landmark.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByText(/Leave it empty/u)).not.toBeInTheDocument();
    });

    it('clears a field’s error once the field is filled in, until the next save', () => {
        inertia.errors = {
            'address.province': 'Choose the province.',
            'address.district': 'Choose a district in the selected province.',
            'address.sector': 'Enter the sector name.',
            'address.cell': 'Enter the cell name.',
            'address.street':
                'Enter the street, or the nearest road or landmark if it has no name.',
        };
        render(<RoleApply {...props()} />);

        expect(screen.getByText('Enter the sector name.')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Enter the street, or the nearest road or landmark if it has no name.',
            ),
        ).toBeInTheDocument();

        fill();

        expect(
            screen.queryByText('Choose the province.'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('Choose a district in the selected province.'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('Enter the sector name.'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('Enter the cell name.'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText(
                'Enter the street, or the nearest road or landmark if it has no name.',
            ),
        ).not.toBeInTheDocument();
    });
});
