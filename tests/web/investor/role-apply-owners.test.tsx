import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import RoleApply from '@/pages/role/apply';
import type {
    RoleApplicationPerson,
    RoleApplicationProps,
} from '@/types/role-application';
import { resetInertia } from '../admin/inertia-mock';

vi.mock('@inertiajs/react', async () => {
    const mock = await import('../admin/inertia-mock');

    return { ...mock, router: { ...mock.router, post: vi.fn() } };
});

const jane: RoleApplicationPerson = {
    full_name: 'Jane Walkfive',
    id_type: 'national_id',
    id_number: '1198580012345678',
    nationality: 'Rwandan',
};

const props = (owners?: RoleApplicationPerson[]): RoleApplicationProps => ({
    role: 'business',
    application: {
        status: 'draft',
        revision: 4,
        role: 'business',
        step: 'people',
        answers: {
            declared: false,
            representative: {
                title: 'Director’s assistant',
                phone: '0788123457',
                authorised: true,
            },
            directors: [jane],
            ...(owners === undefined ? {} : { owners }),
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
        industries: ['Agriculture'],
        districts: ['Rubavu'],
        provinces: { Western: ['Rubavu'] },
    },
});

beforeEach(() => resetInertia());

describe('Owners on the People step (#761)', () => {
    it('picks a director as an owner instead of typing them again, and stops adding at 100%', () => {
        render(<RoleApply {...props()} />);

        expect(
            screen.getByText('Shares listed: 0% of 100%'),
        ).toBeInTheDocument();
        fireEvent.click(
            screen.getByRole('button', { name: '+ Jane Walkfive' }),
        );

        expect(screen.getByText('Director')).toBeInTheDocument();
        expect(
            (screen.getByLabelText('Share (%)') as HTMLInputElement).value,
        ).toBe('100');
        expect(
            screen.getByText('Shares listed: 100% of 100%'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'The shares add up to 100%, so no more owners can be added.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', {
                name: '+ Add an owner who isn’t a director',
            }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: '+ Jane Walkfive' }),
        ).not.toBeInTheDocument();

        fireEvent.change(screen.getByLabelText('Share (%)'), {
            target: { value: '60' },
        });
        fireEvent.click(
            screen.getByRole('button', {
                name: '+ Add an owner who isn’t a director',
            }),
        );

        expect(screen.getAllByLabelText('Share (%)')).toHaveLength(2);
        expect(
            (screen.getAllByLabelText('Share (%)')[1] as HTMLInputElement)
                .value,
        ).toBe('40');
        expect(screen.getAllByLabelText('Full name')).toHaveLength(2);
    });

    it('warns when the shares go over 100%', () => {
        render(
            <RoleApply
                {...props([
                    { ...jane, ownership_pct: '70', pep: false },
                    {
                        full_name: 'Paul Other',
                        id_type: 'passport',
                        id_number: 'PC99887',
                        nationality: 'Kenyan',
                        ownership_pct: '40',
                        pep: false,
                    },
                ])}
            />,
        );

        expect(
            screen.getByText(/Shares listed: 110% of 100%/),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/The shares add up to more than 100%/),
        ).toBeInTheDocument();
        expect(
            screen.queryByText(
                'The shares add up to 100%, so no more owners can be added.',
            ),
        ).not.toBeInTheDocument();
    });

    it('reads a decimal comma as a point and refuses anything else with a word, never rewriting it', () => {
        render(
            <RoleApply
                {...props([{ ...jane, ownership_pct: '30', pep: false }])}
            />,
        );
        const share = () =>
            screen.getByLabelText('Share (%)') as HTMLInputElement;
        const type = (value: string) =>
            fireEvent.change(share(), { target: { value } });
        const hint = 'Enter the share as a number, like 25 or 25.5.';

        type('25,5');
        expect(share().value).toBe('25.5');
        type('2,5');
        expect(share().value).toBe('2.5');

        for (const refused of ['-25', '25.5.5', '25,5,5', '2a5']) {
            type(refused);
            expect(share().value).toBe('2.5');
            expect(screen.getByText(hint)).toBeInTheDocument();
        }

        type('40');
        expect(share().value).toBe('40');
        expect(screen.queryByText(hint)).not.toBeInTheDocument();
    });

    it('corrects an owner picked from the directors when the director is corrected', () => {
        render(
            <RoleApply
                {...props([{ ...jane, ownership_pct: '100', pep: false }])}
            />,
        );

        fireEvent.change(screen.getAllByLabelText('Nationality')[0], {
            target: { value: 'Ugandan' },
        });

        expect(
            screen.getByText(/1198580012345678 · Ugandan/),
        ).toBeInTheDocument();
    });
});
