import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminEvents from '@/pages/admin/events';
import AdminLedger from '@/pages/admin/ledger';
import type { AdminEventsProps, AdminLedgerProps } from '@/types/admin';
import runningFixture from '../../../resources/fixtures/ui/admin-events-export-running.json';
import filteredFixture from '../../../resources/fixtures/ui/admin-events-filtered-empty.json';
import eventsFixture from '../../../resources/fixtures/ui/admin-events.json';
import entryFixture from '../../../resources/fixtures/ui/admin-ledger-entry.json';
import ledgerFixture from '../../../resources/fixtures/ui/admin-ledger.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const ledgerProps = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminLedgerProps;

const eventProps = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminEventsProps;

beforeEach(resetInertia);

describe('Ledger', () => {
    it('lists every movement newest first, each opening its postings', async () => {
        const { user } = renderWithUser(
            <AdminLedger {...ledgerProps(ledgerFixture)} />,
        );

        expect(screen.getByText('1,932 entries')).toBeInTheDocument();
        expect(screen.getByText('2026-09-23 20:36:51')).toBeInTheDocument();
        expect(
            screen.getByText('Rozine Platform → Diane Uwase, CPA'),
        ).toBeInTheDocument();
        expect(screen.getByText('Contra entry')).toBeInTheDocument();
        expect(screen.getByText('RWF 4.8M')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Open entry LED-1928' }),
        ).toHaveAttribute('href', '/preview/admin-ledger-entry');
        expect(
            screen.getByRole('link', { name: /See all 1,932 entries/ }),
        ).toHaveAttribute('data-preserve-scroll', 'true');

        await user.type(
            screen.getByRole('searchbox', { name: 'Search the ledger' }),
            'contra{Enter}',
        );
        expect(inertia.reload).toEqual([{ data: { q: 'contra' } }]);
    });

    it('says when nothing is in range or the search found nothing', () => {
        const { unmount } = render(
            <AdminLedger
                {...ledgerProps(ledgerFixture)}
                entries={[]}
                more={null}
            />,
        );

        expect(
            screen.getByText('No ledger entries in this range.'),
        ).toBeInTheDocument();
        unmount();
        render(
            <AdminLedger
                {...ledgerProps(ledgerFixture)}
                entries={[]}
                more={null}
                search="zzz"
            />,
        );
        expect(
            screen.getByText(
                'Nothing in Ledger matches “zzz”. This search only looks inside the current page.',
            ),
        ).toBeInTheDocument();
    });

    it('drills an entry down to balanced postings, read-only, with its contra', () => {
        render(<AdminLedger {...ledgerProps(entryFixture)} />);

        const drawer = screen.getByRole('dialog', {
            name: 'Ledger entry LED-1928',
        });

        expect(
            within(drawer).getByText('RWF 12,000,000', { selector: 'h2' }),
        ).toBeInTheDocument();
        expect(within(drawer).getByText('op_7f3c21a9e4')).toBeInTheDocument();
        expect(
            within(drawer).getByText(
                'Posted by System · disbursement dsb_0598 · 2026-09-23 16:20:37',
            ),
        ).toBeInTheDocument();
        const postings = within(drawer).getByRole('table', {
            name: 'Postings',
        });

        expect(within(postings).getByText('2100-SET')).toBeInTheDocument();
        expect(within(postings).getByText('Balanced')).toBeInTheDocument();
        expect(within(postings).getAllByText('RWF 12,000,000')).toHaveLength(4);
        expect(
            within(drawer).getByRole('link', { name: 'LED-1925' }),
        ).toHaveAttribute('href', '/preview/admin-ledger-entry');
        expect(within(drawer).queryByRole('textbox')).not.toBeInTheDocument();
        expect(
            within(drawer).getByRole('link', {
                name: 'See this entry in the event log →',
            }),
        ).toHaveAttribute('href', '/preview/admin-events');
    });

    it('shows a contra entry, a reasoned posting and an unbalanced entry', () => {
        const fixture = ledgerProps(entryFixture);

        if (fixture.entry === null) {
            throw new Error('fixture has an entry');
        }

        fixture.entry = {
            ...fixture.entry,
            balanced: false,
            posted_by: {
                actor: 'Eric Ndoli',
                at: '2026-09-23T13:31:45+02:00',
                reason: 'Duplicate credit',
            },
            contra_of: { id: 'LED-1911', link: { url: '/x', method: 'get' } },
            contra_by: null,
        };
        render(<AdminLedger {...fixture} />);

        expect(screen.getByText('Out of balance')).toBeInTheDocument();
        expect(
            screen.getByText(/Reason: Duplicate credit/),
        ).toBeInTheDocument();
        expect(
            screen.getByText('This contra entry reverses'),
        ).toBeInTheDocument();
        expect(
            screen.queryByText('Reversed by contra entry'),
        ).not.toBeInTheDocument();
    });
});

describe('Event log', () => {
    it('lists who did what, when and from where, and opens each row to its before and after', async () => {
        const { user } = renderWithUser(
            <AdminEvents {...eventProps(eventsFixture)} />,
        );

        expect(
            screen.getByText(
                'Audit logs are immutable. Nothing on this screen can be edited or deleted.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('8 entries')).toBeInTheDocument();
        expect(screen.getByText('2026-09-23 19:37:23')).toBeInTheDocument();

        const approved = screen.getByRole('button', { name: 'Approved RNP' });

        expect(approved).toHaveAttribute('aria-expanded', 'false');
        await user.click(approved);
        expect(approved).toHaveAttribute('aria-expanded', 'true');
        expect(
            screen.getByText(
                'Reason: Evidence complete; DSCR 1.62 on the final offer.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('under_review')).toBeInTheDocument();
        expect(screen.getByText('approved')).toBeInTheDocument();
        await user.click(approved);
        expect(screen.queryByText('under_review')).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Flagged note' }));
        expect(
            screen.getByText('No field values changed.'),
        ).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Contra entry posted' }),
        );
        expect(
            screen.getByText('No reason recorded — a system action.'),
        ).toBeInTheDocument();
        expect(screen.getByText('—')).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Rejected application' }),
        );
        expect(screen.getByText('Grace Kalisa')).toBeInTheDocument();
        expect(screen.getAllByText('—')).toHaveLength(2);
        await user.click(
            screen.getByRole('button', { name: 'Updated policy value' }),
        );
        expect(screen.getByText('RWF 10,000,000')).toBeInTheDocument();
        expect(
            screen.queryByRole('textbox', { name: /reason/i }),
        ).not.toBeInTheDocument();
    });

    it('filters on the server by search, preset and dates', async () => {
        const { user } = renderWithUser(
            <AdminEvents {...eventProps(eventsFixture)} />,
        );

        await user.type(
            screen.getByRole('searchbox', { name: 'Search the audit trail' }),
            'freeze{Enter}',
        );
        await user.click(screen.getByRole('button', { name: '7d' }));
        fireEvent.change(screen.getByLabelText('From date'), {
            target: { value: '2026-09-01' },
        });
        fireEvent.change(screen.getByLabelText('To date'), {
            target: { value: '2026-09-10' },
        });
        const base = { q: '', preset: null, from: null, to: null };

        expect(inertia.reload).toEqual([
            { data: { ...base, q: 'freeze' } },
            { data: { ...base, preset: '7d', from: null, to: null } },
            { data: { ...base, preset: null, from: '2026-09-01' } },
            { data: { ...base, preset: null, to: '2026-09-10' } },
        ]);
        expect(
            screen.queryByRole('button', { name: 'Clear' }),
        ).not.toBeInTheDocument();
    });

    it('explains a filter that matched nothing and clears it', async () => {
        const fixture = eventProps(filteredFixture);
        const { user } = renderWithUser(<AdminEvents {...fixture} />);

        expect(
            screen.getByText('Nothing in the trail matches these filters.'),
        ).toBeInTheDocument();
        expect(screen.getByRole('button', { name: '7d' })).toHaveAttribute(
            'aria-pressed',
            'true',
        );
        await user.click(screen.getByRole('button', { name: 'Clear filters' }));
        await user.click(screen.getByRole('button', { name: 'Clear' }));
        expect(inertia.reload).toEqual([
            { data: { q: '', preset: null, from: null, to: null } },
            { data: { q: '', preset: null, from: null, to: null } },
        ]);
    });

    it('reads an empty trail and date filters', () => {
        render(
            <AdminEvents
                {...eventProps(eventsFixture)}
                events={[]}
                filters={{
                    q: '',
                    preset: null,
                    from: '2026-09-01',
                    to: '2026-09-20',
                }}
            />,
        );

        expect(screen.getByLabelText('From date')).toHaveValue('2026-09-01');
        fireEvent.change(screen.getByLabelText('From date'), {
            target: { value: '' },
        });
        fireEvent.change(screen.getByLabelText('To date'), {
            target: { value: '' },
        });
        expect(inertia.reload).toEqual([
            { data: { q: '', preset: null, from: null, to: '2026-09-20' } },
            { data: { q: '', preset: null, from: '2026-09-01', to: null } },
        ]);
        expect(
            screen.getByText('Nothing in the trail matches these filters.'),
        ).toBeInTheDocument();
    });

    it('counts an end date alone as an active filter', () => {
        render(
            <AdminEvents
                {...eventProps(eventsFixture)}
                filters={{ q: '', preset: null, from: null, to: '2026-09-20' }}
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Clear' }),
        ).toBeInTheDocument();
    });

    it('says when nothing has been recorded', () => {
        render(<AdminEvents {...eventProps(eventsFixture)} events={[]} />);

        expect(
            screen.getByText('No actions recorded yet.'),
        ).toBeInTheDocument();
    });

    it('starts an attributed export with a reason and the filters shown', async () => {
        const { user } = renderWithUser(
            <AdminEvents
                {...eventProps(eventsFixture)}
                more={{ url: '/preview/admin-events', method: 'get' }}
            />,
        );

        expect(
            screen.getByRole('link', { name: /See all 8 entries/ }),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Export' }));
        const stage = screen.getByRole('form', { name: 'Export this trail' });

        await user.type(within(stage).getByRole('textbox'), 'RCMA request');
        await user.click(
            within(stage).getByRole('button', { name: 'Start export' }),
        );
        expect(inertia.posts).toEqual([
            {
                url: '/admin/events/export',
                data: {
                    reason: 'RCMA request',
                    q: '',
                    preset: '',
                    from: '',
                    to: '',
                },
            },
        ]);
        await user.click(within(stage).getByRole('button', { name: 'Cancel' }));
        expect(
            screen.getByRole('button', { name: 'Export' }),
        ).toBeInTheDocument();
    });

    it('shows an export running and a finished export to download', () => {
        const { unmount } = render(
            <AdminEvents {...eventProps(runningFixture)} />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            'Export running · A. Diane · 2026-09-23 17:38:00',
        );
        expect(
            screen.queryByRole('button', { name: 'Export' }),
        ).not.toBeInTheDocument();
        unmount();
        render(
            <AdminEvents
                {...eventProps(eventsFixture)}
                export={{
                    state: 'ready',
                    requested: {
                        actor: 'A. Diane',
                        at: '2026-09-23T17:38:00+02:00',
                        reason: null,
                    },
                    download: { url: '/exports/1', method: 'get' },
                }}
            />,
        );
        expect(
            screen.getByRole('link', { name: 'Download export' }),
        ).toHaveAttribute('href', '/exports/1');
    });
});
