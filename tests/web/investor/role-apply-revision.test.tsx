import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import RoleApply from '@/pages/role/apply';
import type { RoleApplicationProps } from '@/types/role-application';
import { resetInertia } from '../admin/inertia-mock';

vi.mock('@inertiajs/react', async () => {
    const mock = await import('../admin/inertia-mock');

    return { ...mock, router: { ...mock.router, post: vi.fn() } };
});

const props = (
    overrides: Partial<RoleApplicationProps['application']> = {},
    step = 'company',
): RoleApplicationProps => ({
    role: 'business',
    application: {
        status: 'changes_requested',
        revision: 12,
        role: 'business',
        step,
        answers: {
            declared: false,
            company: {
                legal_name: 'Kivu Harvest Ltd',
                rdb_code: '108 123 456',
                tin: '108123456',
                company_type: 'private_limited',
                incorporation_date: '2019-04-02',
                industry: 'Agriculture',
                province: 'Western',
                district: 'Rubavu',
                sector: 'Gisenyi',
                cell: 'Amahoro',
                street: 'KN 4 Ave, Gisenyi',
                phone: '+250 788 000 111',
            },
        },
        note: 'Two small fixes and we can approve.',
        requested_changes: {
            note: 'Two small fixes and we can approve.',
            items: [
                {
                    key: 'company.tin',
                    label: 'TIN',
                    note: 'Does not match the certificate.',
                },
                {
                    key: 'company.address',
                    label: 'Registered address',
                    note: '',
                },
                {
                    key: 'documents.articles',
                    label: 'Articles of Association',
                    note: '',
                },
            ],
            requested_at: '2026-11-24T08:31:00+02:00',
        },
        uploads: {},
        ...overrides,
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

describe('An application Compliance sent back', () => {
    it('lists the items to change and flags each one on the form, which stays editable', () => {
        render(<RoleApply {...props()} />);

        expect(
            screen.getByText('Change these, then send it again:'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                (_, element) =>
                    element?.tagName === 'LI' &&
                    element.textContent ===
                        'TIN: Does not match the certificate.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'You can still change any other answer before you send it again.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Compliance: Does not match the certificate.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Compliance asked you to change this.'),
        ).toBeInTheDocument();
        expect(screen.getByLabelText(/TIN/u)).toHaveClass(
            'border-rz-warning-text',
        );
        expect(screen.getByLabelText(/TIN/u)).toBeEnabled();
        expect(screen.getByLabelText(/Registered name/iu)).not.toHaveClass(
            'border-rz-warning-text',
        );
    });

    it('lists the items even without a note, and flags a document', () => {
        render(<RoleApply {...props({ note: null }, 'documents')} />);

        expect(
            screen.getByText('Change these, then send it again:'),
        ).toBeInTheDocument();
        expect(
            screen.getAllByText('Compliance asked you to change this.').length,
        ).toBeGreaterThan(0);
    });

    it('keeps "Tap to upload" on every document and asks for a board resolution when you are not a director', () => {
        render(
            <RoleApply
                {...props(
                    {
                        status: 'draft',
                        note: null,
                        requested_changes: undefined,
                        answers: {
                            declared: false,
                            directors: [
                                {
                                    full_name: 'Aline Uwase',
                                    id_type: 'national_id',
                                    id_number: '1199080012345678',
                                    nationality: 'Rwandan',
                                },
                            ],
                        },
                    },
                    'documents',
                )}
            />,
        );

        expect(
            screen.getByText('Only our Compliance team sees these.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Needed: you are not listed as a director.'),
        ).toBeInTheDocument();
        expect(
            screen.getAllByText('Tap to upload').length,
        ).toBeGreaterThanOrEqual(6);
    });

    it('shows no flags once the application is sent again', () => {
        render(<RoleApply {...props({ status: 'draft', note: null })} />);

        expect(
            screen.queryByText('Change these, then send it again:'),
        ).not.toBeInTheDocument();
        expect(screen.queryByText(/^Compliance/u)).not.toBeInTheDocument();
    });
});

describe('Fixing an application Compliance sent back', () => {
    const flagged = (key: string, label: string) => {
        const base = props();

        return {
            ...base,
            application: {
                ...base.application,
                requested_changes: {
                    ...base.application.requested_changes!,
                    items: [{ key, label, note: '' }],
                },
            },
        };
    };

    it('opens on the step of the first flagged item', () => {
        render(
            <RoleApply
                {...flagged('documents.articles', 'Articles of Association')}
            />,
        );

        expect(
            screen.getByRole('heading', { level: 1, name: 'Documents' }),
        ).toBeInTheDocument();
    });

    it('warns on Check and send about flagged items left unchanged', () => {
        render(<RoleApply {...flagged('declared', 'Declaration')} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            "You haven't changed these yet",
        );
        expect(
            screen.getByRole('button', { name: 'Declaration' }),
        ).toBeInTheDocument();
    });
});
