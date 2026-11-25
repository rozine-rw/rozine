import { act, fireEvent, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { RoleApplicationDrawer } from '@/components/admin/parties/role-application-drawer';
import type { RoleApplicationReview } from '@/types/role-application';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia, resetInertia } from './inertia-mock';

const post = vi.hoisted(() => vi.fn());
vi.mock('@inertiajs/react', async () => {
    const mock = await import('./inertia-mock');

    return { ...mock, router: { ...mock.router, post } };
});

const application = (
    overrides: Partial<RoleApplicationReview> = {},
): RoleApplicationReview => ({
    id: '01jroleapp0000000000000000',
    revision: 9,
    role: 'business',
    status: 'submitted',
    submitted_at: '2026-11-23T17:02:00+02:00',
    decided_at: null,
    account: {
        name: 'Aline Uwase',
        email: 'aline@example.rw',
        two_factor: true,
    },
    kyc: 'approved',
    kyc_verification_id: null,
    state: {
        role: 'business',
        step: 'review',
        answers: { declared: true },
        documents: {},
        note: null,
    },
    people: {},
    documents: [],
    history: [],
    blockers: [],
    problems: [],
    business_id: null,
    allowed_actions: ['approve', 'more', 'reject'],
    revisable: {
        'company.tin': 'TIN',
        'company.legal_name': 'Company legal name',
        directors: 'Directors',
        'documents.articles': 'Articles of Association',
        'licence.licence_number': 'Licence number',
        'coverage.districts': 'Districts covered',
    },
    ...overrides,
});

beforeEach(() => {
    resetInertia();
    post.mockReset();
});

describe('Asking an applicant for changes', () => {
    it('ticks the items to change, grouped as the form is, and sends them with the decision', async () => {
        const { user } = renderWithUser(
            <RoleApplicationDrawer
                application={application()}
                close="/admin/businesses"
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Ask for more' }));
        const picker = screen.getByRole('group', {
            name: 'What must the applicant change?',
        });
        expect(
            within(picker)
                .getAllByText(
                    /^(Company|People|ICPAR licence|Coverage|Documents)$/u,
                )
                .map((group) => group.textContent),
        ).toEqual([
            'Company',
            'People',
            'ICPAR licence',
            'Coverage',
            'Documents',
        ]);
        const send = screen.getByRole('button', {
            name: 'Send back with 0 items',
        });
        expect(send).toBeDisabled();
        await user.click(
            within(picker).getByLabelText('Articles of Association'),
        );
        expect(
            screen.getByRole('button', { name: 'Send back with 1 item' }),
        ).toBeEnabled();
        await user.click(within(picker).getByLabelText('TIN'));
        fireEvent.change(within(picker).getByLabelText('What to fix in TIN'), {
            target: { value: 'Does not match the certificate.' },
        });
        await user.click(
            screen.getByRole('button', { name: 'Send back with 2 items' }),
        );
        expect(post).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
                decision: 'more',
                items: [
                    { key: 'documents.articles', note: '' },
                    {
                        key: 'company.tin',
                        note: 'Does not match the certificate.',
                    },
                ],
                expected_revision: 9,
            }),
            expect.objectContaining({ preserveScroll: true }),
        );
        act(() => post.mock.calls[0][2].onFinish());
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(screen.queryByRole('group')).not.toBeInTheDocument();
    });

    it('rejects and approves without items, and shows a refused item', async () => {
        inertia.errors = {
            'items.1.note': 'Keep each note to at most 300 characters.',
        };
        const { user } = renderWithUser(
            <RoleApplicationDrawer
                application={application()}
                close="/admin/businesses"
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Reject' }));
        expect(post.mock.calls[0][1]).not.toHaveProperty('items');
        act(() => post.mock.calls[0][2].onFinish());
        await user.click(screen.getByRole('button', { name: 'Ask for more' }));
        expect(
            screen.getByText('Keep each note to at most 300 characters.'),
        ).toBeInTheDocument();
        inertia.errors = {};
    });
});

describe('Approving and reviewing a resubmission', () => {
    const person = {
        full_name: 'Aline Uwase',
        id_type: 'national_id' as const,
        id_number: '1199080012345678',
        nationality: 'Rwandan',
    };

    it('asks for a note and enhanced due diligence on a PEP before onboarding, and labels the representative', async () => {
        const { user } = renderWithUser(
            <RoleApplicationDrawer
                application={application({
                    state: {
                        role: 'business',
                        step: 'review',
                        answers: {
                            declared: true,
                            representative: {
                                title: 'Managing Director',
                                phone: '+250 788 000 222',
                                authorised: true,
                            },
                            directors: [person],
                            owners: [
                                { ...person, ownership_pct: '60', pep: true },
                            ],
                        },
                        documents: {},
                        note: null,
                    },
                })}
                close="/admin/businesses"
            />,
        );

        expect(screen.getByText('Representative phone')).toBeInTheDocument();
        expect(screen.getAllByText('PEP')).toHaveLength(2);
        await user.click(
            screen.getByRole('button', { name: 'Approve and onboard' }),
        );
        expect(post).not.toHaveBeenCalled();
        const stage = screen.getByRole('group', {
            name: 'Approve and onboard Aline Uwase?',
        });
        const approve = within(stage).getByRole('button', {
            name: 'Approve and onboard',
        });
        expect(approve).toBeDisabled();
        fireEvent.change(screen.getByLabelText(/note/iu), {
            target: { value: 'Registry, owners and IDs match.' },
        });
        expect(approve).toBeDisabled();
        await user.click(
            within(stage).getByLabelText(
                'I have done enhanced due diligence on this person.',
            ),
        );
        await user.click(approve);
        expect(post.mock.calls[0][1]).toMatchObject({
            decision: 'approve',
            note: 'Registry, owners and IDs match.',
        });
        act(() => post.mock.calls[0][2].onFinish());
        await user.click(within(stage).getByRole('button', { name: 'Cancel' }));
        expect(
            screen.queryByRole('group', { name: /Approve and onboard/u }),
        ).not.toBeInTheDocument();
    });

    it('shows what Compliance asked for on a resubmission and which items changed', async () => {
        const { user } = renderWithUser(
            <RoleApplicationDrawer
                application={application({
                    state: {
                        role: 'business',
                        step: 'review',
                        answers: {
                            declared: true,
                            company: {
                                legal_name: 'Kivu Harvest Ltd',
                                rdb_code: '108 123 456',
                                tin: '108123457',
                                company_type: 'private_limited',
                                incorporation_date: '2019-04-02',
                                industry: 'Agriculture',
                                province: 'Western',
                                district: 'Rubavu',
                                sector: 'Gisenyi',
                                cell: 'Amahoro',
                                street: 'KN 4 Ave',
                                phone: '+250 788 000 111',
                            },
                        },
                        documents: { articles: 'doc-1' },
                        note: 'Fix these.',
                        revision: {
                            note: 'Fix these.',
                            items: [
                                {
                                    key: 'company.tin',
                                    label: 'TIN',
                                    note: 'Does not match.',
                                },
                                {
                                    key: 'documents.articles',
                                    label: 'Articles of Association',
                                    note: '',
                                },
                            ],
                            requested_at: '2026-11-24T08:31:00+02:00',
                            before: {
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
                                        street: 'KN 4 Ave',
                                        phone: '+250 788 000 111',
                                    },
                                },
                                documents: { articles: 'doc-1' },
                            },
                        },
                    },
                })}
                close="/admin/businesses"
            />,
        );

        const banner = screen.getByRole('region', {
            name: 'Sent again after you asked for changes',
        });
        expect(banner).toHaveTextContent('TINChangedDoes not match.');
        expect(banner).toHaveTextContent('Articles of AssociationNot changed');
        await user.click(
            screen.getByRole('button', { name: 'Approve and onboard' }),
        );
        expect(
            screen.getByText('1 item(s) you asked to change were not changed.'),
        ).toBeInTheDocument();
    });
});

describe('Reviewing an auditor application (#757)', () => {
    const auditor = (kyc: RoleApplicationReview['kyc']) =>
        application({
            role: 'auditor',
            kyc,
            state: {
                role: 'auditor',
                step: 'review',
                answers: {
                    declared: true,
                    licence: {
                        membership_number: 'ICPAR-0042',
                        licence_number: 'PC-2026-11',
                        expires_on: '2027-12-31',
                        firm: '',
                    },
                    address: {
                        province: 'Kigali City',
                        district: 'Gasabo',
                        sector: 'Kacyiru',
                        cell: 'Kamatamu',
                        street: 'KG 7 Ave',
                    },
                    coverage: {
                        districts: ['Gasabo'],
                        no_conflicts: true,
                        will_declare: true,
                        ethics: false,
                    },
                },
                documents: {},
                note: null,
            },
        });

    it('shows the registered address under its own heading, apart from the licence', () => {
        renderWithUser(
            <RoleApplicationDrawer
                application={auditor('approved')}
                close="/admin/auditors"
            />,
        );

        expect(
            screen.getByRole('heading', { name: 'Registered address' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Kacyiru')).toBeInTheDocument();
    });

    it('shows each conflicts and ethics declaration with its answer', () => {
        renderWithUser(
            <RoleApplicationDrawer
                application={auditor('approved')}
                close="/admin/auditors"
            />,
        );

        expect(screen.getByText('Independence')).toBeInTheDocument();
        const items = screen
            .getAllByRole('listitem')
            .filter((item) => /confirmed/u.test(item.textContent ?? ''));
        expect(
            items.map((item) => item.textContent?.trim().slice(0, 1)),
        ).toEqual(['✓', '✓', '✗']);
        expect(items[2]).toHaveTextContent('not confirmed');
    });

    it("keeps Approve off until the applicant's KYC is approved, and says why", () => {
        renderWithUser(
            <RoleApplicationDrawer
                application={auditor('submitted')}
                close="/admin/auditors"
            />,
        );

        const approve = screen.getByRole('button', {
            name: 'Approve and accredit',
        });
        expect(approve).toBeDisabled();
        expect(approve).toHaveAccessibleDescription(
            "Approve the applicant's own KYC first (Users page). Then approve this application.",
        );
    });
});

describe('Application history', () => {
    it('reads a send-back followed by applicant saves as one send-back, then the saves, then the resend', () => {
        const entry = (revision: number, status: string, command: string) => ({
            revision,
            status,
            command,
            reason: null,
            at: `2026-11-24T1${revision}:00:00+02:00`,
        });
        renderWithUser(
            <RoleApplicationDrawer
                application={application({
                    history: [
                        entry(1, 'changes_requested', 'application.more'),
                        entry(2, 'changes_requested', 'application.save'),
                        entry(3, 'changes_requested', 'application.save'),
                        entry(4, 'submitted', 'application.submit'),
                    ],
                })}
                close="/admin/businesses"
            />,
        );

        expect(
            screen.getByText(/then 2 saves by the applicant/u),
        ).toBeInTheDocument();
        expect(screen.getByText('Sent again for review')).toBeInTheDocument();
        expect(
            screen.queryByText('Waiting for review'),
        ).not.toBeInTheDocument();
    });
});

describe('A resent registered address (#771)', () => {
    const company = (street: string) => ({
        legal_name: 'Walk Five Ltd',
        rdb_code: '108 123 456',
        tin: '108123456',
        company_type: 'private_limited',
        incorporation_date: '2019-04-02',
        industry: 'Agriculture',
        province: 'Kigali City',
        district: 'Kicukiro',
        sector: '',
        cell: '',
        street,
        phone: '+250 788 000 111',
    });
    const resent = (key: string, street: string) =>
        application({
            state: {
                role: 'business',
                step: 'review',
                answers: { declared: true, company: company(street) },
                documents: {},
                note: 'Fix the address.',
                revision: {
                    note: 'Fix the address.',
                    items: [{ key, label: 'Registered address', note: '' }],
                    requested_at: '2026-11-24T08:31:00+02:00',
                    before: {
                        answers: {
                            declared: false,
                            company: company('KK 15 Rd, Kicukiro'),
                        },
                        documents: {},
                    },
                },
            },
        });

    it.each(['company.address', 'company.registered_address'])(
        'reads the address parts together under %s: untouched is Not changed, edited is Changed',
        (key) => {
            const { unmount } = renderWithUser(
                <RoleApplicationDrawer
                    application={resent(key, 'KK 15 Rd, Kicukiro')}
                    close="/admin/businesses"
                />,
            );
            expect(
                screen.getByRole('region', {
                    name: 'Sent again after you asked for changes',
                }),
            ).toHaveTextContent('Registered addressNot changed');
            unmount();

            renderWithUser(
                <RoleApplicationDrawer
                    application={resent(key, 'KK 15 Rd, Plot 12')}
                    close="/admin/businesses"
                />,
            );
            expect(
                screen.getByRole('region', {
                    name: 'Sent again after you asked for changes',
                }),
            ).toHaveTextContent('Registered addressChanged');
        },
    );
});
