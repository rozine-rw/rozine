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
    kyc: 'submitted',
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
