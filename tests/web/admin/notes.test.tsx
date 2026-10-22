import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminNotes from '@/pages/admin/notes';
import type { AdminNotesProps, NoteRegisterRow } from '@/types/admin';
import shellFixture from '../../../resources/fixtures/ui/admin-exceptions-empty.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const note = (overrides: Partial<NoteRegisterRow>): NoteRegisterRow => ({
    id: '01K0000000000000000000AAAA',
    reference: 'NTE-00AAAA',
    business: 'Ubuki Crafts',
    sector: 'Agriculture',
    state: 'live',
    rate_pct: '15',
    term_months: 12,
    funded_pct: '75.0',
    raised: { currency: 'RWF', amount: '3000000' },
    target: { currency: 'RWF', amount: '4000000' },
    investors: 3,
    listed_at: '2026-10-01T10:00:00+02:00',
    ...overrides,
});

const tab = (
    key: AdminNotesProps['tabs'][number]['key'],
    count: number,
    active = false,
): AdminNotesProps['tabs'][number] => ({
    key,
    count,
    active,
    link: {
        url: key === 'all' ? '/admin/notes' : `/admin/notes?tab=${key}`,
        method: 'get',
    },
});

const props = (overrides: Partial<AdminNotesProps> = {}): AdminNotesProps => {
    const { viewer, nav, badges, server_time } = structuredClone(
        shellFixture.props,
    );

    return {
        viewer,
        nav: { ...nav, notes: { url: '/admin/notes', method: 'get' } },
        badges,
        server_time,
        search: '',
        contract_version: 'staff-notes-v1',
        staff_access_version: 'staff-access-v1',
        allowed_actions: [],
        stats: [
            { key: 'total', value: { kind: 'count', value: 2 } },
            { key: 'live', value: { kind: 'count', value: 1 } },
            { key: 'repaying', value: { kind: 'count', value: 1 } },
            {
                key: 'raised',
                value: {
                    kind: 'money',
                    value: { currency: 'RWF', amount: '6000000' },
                },
            },
        ],
        tabs: [
            tab('all', 2, true),
            tab('live', 1),
            tab('repaying', 1),
            tab('matured', 0),
            tab('failed', 0),
        ],
        notes: [
            note({}),
            note({
                id: '01K0000000000000000000BBBB',
                reference: 'NTE-00BBBB',
                business: 'GreenLeaf Agro',
                state: 'repaying',
                funded_pct: '100.0',
            }),
        ],
        pagination: { next: null },
        ...overrides,
    } as AdminNotesProps;
};

describe('Admin Notes', () => {
    beforeEach(() => resetInertia());

    it('shows the four figures, a count on every tab and each note by its state', () => {
        render(<AdminNotes {...props()} />);

        expect(screen.getByText('Total Notes')).toBeInTheDocument();
        expect(screen.getByText('Capital Raised')).toBeInTheDocument();
        const tabs = screen.getByRole('navigation', { name: 'Notes by state' });
        expect(
            within(tabs).getByRole('link', { name: 'All 2' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(tabs).getByRole('link', { name: 'Repaying 1' }),
        ).toHaveAttribute('href', '/admin/notes?tab=repaying');
        expect(
            within(tabs).getByRole('link', { name: 'Matured 0' }),
        ).toBeInTheDocument();

        const rows = screen.getAllByRole('row').slice(1);
        expect(rows).toHaveLength(2);
        expect(rows[0]).toHaveTextContent('Ubuki Crafts');
        expect(rows[0]).toHaveTextContent('NTE-00AAAA · Agriculture');
        expect(rows[0]).toHaveTextContent('Live');
        expect(rows[0]).toHaveTextContent('15% yield');
        expect(rows[1]).toHaveTextContent('Repaying');
        rows.forEach((row) =>
            expect(within(row).queryByRole('button')).not.toBeInTheDocument(),
        );
    });

    it('says when there are no notes yet, and offers the rest of a long list', () => {
        const { rerender } = render(<AdminNotes {...props({ notes: [] })} />);

        expect(screen.getByText('No notes to show yet.')).toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();

        rerender(<AdminNotes {...props({ notes: [], search: 'ubuki' })} />);
        expect(screen.getByRole('status')).toHaveTextContent(
            'No matches on this page',
        );

        rerender(
            <AdminNotes
                {...props({
                    pagination: {
                        next: { url: '/admin/notes?limit=500', method: 'get' },
                    },
                })}
            />,
        );
        expect(
            screen.getByRole('link', { name: 'More notes' }),
        ).toHaveAttribute('href', '/admin/notes?limit=500');
    });
});
