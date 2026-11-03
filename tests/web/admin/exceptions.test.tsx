import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminExceptions from '@/pages/admin/exceptions';
import type { AdminExceptionsProps } from '@/types/admin';
import emptyFixture from '../../../resources/fixtures/ui/admin-exceptions-empty.json';
import exceptionsFixture from '../../../resources/fixtures/ui/admin-exceptions.json';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AdminExceptionsProps;

const rowOf = (reference: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(reference));

    return row;
};

const nav = () =>
    screen.getByRole('navigation', { name: 'Console navigation' });

beforeEach(resetInertia);

describe('Exceptions', () => {
    it('lists every open arrears, halt and variance with its age, owner and record, read-only', () => {
        render(<AdminExceptions {...props(exceptionsFixture)} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Risk Center');
        expect(
            within(nav()).getByRole('link', { name: 'Risk Center' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(nav()).getByRole('link', { name: 'Notes' }),
        ).not.toHaveAttribute('aria-current');
        expect(screen.getByText('6 open')).toBeInTheDocument();
        expect(screen.getByText('3 unassigned')).toBeInTheDocument();

        const filter = screen.getByRole('navigation', {
            name: 'Filter by kind',
        });

        expect(
            within(filter).getByRole('link', { name: /^All\s*6$/ }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(filter).getByRole('link', { name: /^Arrears\s*3$/ }),
        ).toBeInTheDocument();

        expect(rowOf('ARR-2026-0412')).toHaveTextContent('Arrears');
        expect(rowOf('ARR-2026-0412')).toHaveTextContent(
            'Ubuki Crafts · Loom Expansion',
        );
        expect(rowOf('ARR-2026-0412')).toHaveTextContent('38 DPD');
        expect(rowOf('ARR-2026-0412')).toHaveTextContent('RWF 2,340,000');
        expect(rowOf('ARR-2026-0412')).toHaveTextContent('Open 37 days');
        expect(rowOf('ARR-2026-0412')).toHaveTextContent('Owner: Grace Kalisa');

        /* The Book's overdue GreenLeaf note, its payment still being requeried. */
        const unconfirmed = rowOf('ARR-2027-0433');

        expect(unconfirmed).toHaveTextContent(
            'GreenLeaf Agro · Warehouse Robotics',
        );
        expect(unconfirmed).toHaveTextContent('2 DPD');
        expect(unconfirmed).toHaveTextContent('RWF 4,833,334');
        expect(unconfirmed).toHaveTextContent('Open 1 day');
        expect(unconfirmed).toHaveTextContent('Unassigned');

        const variance = rowOf('RPY-2026-0303');

        expect(variance).toHaveTextContent('Variance');
        expect(variance).toHaveTextContent('RWF 115,000');
        expect(variance).toHaveTextContent('Open 0 days');
        expect(variance).toHaveTextContent('Unassigned');
        expect(variance).not.toHaveTextContent('DPD');
        expect(
            screen.getByRole('link', { name: 'Open RPY-2026-0303' }),
        ).toHaveAttribute(
            'href',
            '/preview/admin-repayments-exception-mismatch',
        );

        /* The Reconciliation page's open statement break, as a variance. */
        const statement = rowOf('STM-2027-0122');

        expect(statement).toHaveTextContent('Variance');
        expect(statement).toHaveTextContent('Bank settlement account');
        expect(statement).not.toHaveTextContent('Bank settlement account ·');
        expect(
            exceptionsFixture.props.exceptions.find(
                (item) => item.reference === 'STM-2027-0122',
            )?.subject,
        ).toEqual({ kind: 'account', label: 'Bank settlement account' });
        expect(
            exceptionsFixture.props.exceptions.find(
                (item) => item.reference === 'ARR-2026-0412',
            )?.subject,
        ).toEqual({ kind: 'business', label: 'Ubuki Crafts' });
        expect(statement).toHaveTextContent('RWF 3,100');
        expect(
            screen.getByRole('link', { name: 'Open STM-2027-0122' }),
        ).toHaveAttribute('href', '/preview/admin-reconciliation');

        const halt = rowOf('FRZ-2026-0355');

        expect(halt).toHaveTextContent('Halt');
        expect(halt).toHaveTextContent('Rugali Freight');
        expect(halt).not.toHaveTextContent('Rugali Freight ·');
        expect(halt).toHaveTextContent('—');

        expect(
            screen.queryAllByRole('button', {
                name: /assign|escalate|halt|remedy|resolve/i,
            }),
        ).toEqual([]);
        expect(
            screen.queryByRole('link', { name: 'Older exceptions' }),
        ).not.toBeInTheDocument();
    });

    it('renders when the server sends no legacy links or queue sizes', () => {
        const fixture = props(exceptionsFixture);

        fixture.nav = {
            ...fixture.nav,
            today: null,
            applications: null,
            disbursements: null,
            repayments: null,
            businesses: null,
            investors: null,
            auditors: null,
            staff: null,
            ledger: null,
            events: null,
            book: null,
        };
        fixture.badges = { applications: null, disbursements: null };
        render(<AdminExceptions {...fixture} />);

        expect(
            within(nav()).getByRole('link', { name: 'Risk Center' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(nav()).queryByRole('link', { name: /Applications/u }),
        ).not.toBeInTheDocument();
        expect(
            within(nav()).queryByRole('link', { name: 'Book' }),
        ).not.toBeInTheDocument();
    });

    it('shows one day in the singular and offers a further page', () => {
        const page = props(exceptionsFixture);

        page.exceptions[0].age_days = 1;
        page.pagination = {
            next: { url: '/preview/admin-exceptions', method: 'get' },
        };
        render(<AdminExceptions {...page} />);

        expect(rowOf('ARR-2026-0412')).toHaveTextContent('Open 1 day');
        expect(
            screen.getByRole('link', { name: 'Older exceptions' }),
        ).toHaveAttribute('href', '/preview/admin-exceptions');
    });

    it('shows none open when nothing is open', () => {
        render(<AdminExceptions {...props(emptyFixture)} />);

        expect(screen.getByText('No open exceptions')).toBeInTheDocument();
        expect(screen.queryByText(/open$/)).not.toBeInTheDocument();
        expect(screen.queryByText(/unassigned/)).not.toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('tells a filter or a search that matched nothing apart from none open', () => {
        const page = props(emptyFixture);

        page.chips = page.chips.map((chip) => ({
            ...chip,
            active: chip.key === 'halt',
        }));
        page.search = 'Kivu';
        const { rerender } = render(<AdminExceptions {...page} />);

        expect(
            screen.getByText('No exceptions of this kind'),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(/Kivu/);

        rerender(<AdminExceptions {...page} chips={[]} />);

        expect(screen.getByText('No open exceptions')).toBeInTheDocument();
        expect(
            screen.queryByRole('navigation', { name: 'Filter by kind' }),
        ).not.toBeInTheDocument();
    });

    it('offers no Open link on an exception whose record the viewer may not open', () => {
        const page = props(exceptionsFixture);

        page.exceptions = [
            {
                ...page.exceptions[0],
                id: 'hld',
                kind: 'halt',
                reference: 'HLD-00ABCD',
                subject: { kind: 'investor', label: 'Aline Uwase' },
                note_title: null,
                description: 'Account on high-risk hold: withdrawals paused',
                amount: null,
                dpd: null,
                owner: null,
                link: null,
            },
        ];
        render(<AdminExceptions {...page} />);

        expect(rowOf('HLD-00ABCD')).toHaveTextContent(
            'Account on high-risk hold: withdrawals paused',
        );
        expect(
            within(rowOf('HLD-00ABCD')).queryByRole('link'),
        ).not.toBeInTheDocument();
    });
    it('shows a Flash Audit unfinished after 24 hours as an overdue audit on its Business, with its own filter', () => {
        const page = props(exceptionsFixture);

        page.chips = [
            ...page.chips.map((chip) => ({ ...chip, active: false })),
            {
                key: 'audit',
                count: 1,
                link: { url: '/admin/exceptions?kind=audit', method: 'get' },
                active: true,
            },
        ];
        page.exceptions = [
            {
                ...page.exceptions[0],
                id: 'aud',
                kind: 'audit',
                reference: 'AUD-00ABCD',
                subject: { kind: 'business', label: 'Sebeya Logistics' },
                note_title: null,
                description:
                    'Flash audit unfinished after 24 hours: offered to a partner, not yet accepted',
                amount: null,
                dpd: null,
                owner: null,
                link: {
                    url: '/admin/businesses?business=01k0',
                    method: 'get',
                },
            },
        ];
        render(<AdminExceptions {...page} />);

        const row = rowOf('AUD-00ABCD');

        expect(row).toHaveTextContent('Overdue flash audit');
        expect(row).toHaveTextContent('Sebeya Logistics');
        expect(row).toHaveTextContent(
            'Flash audit unfinished after 24 hours: offered to a partner, not yet accepted',
        );
        expect(
            within(
                screen.getByRole('navigation', { name: 'Filter by kind' }),
            ).getByRole('link', { name: /Overdue flash audit/ }),
        ).toHaveAttribute('href', '/admin/exceptions?kind=audit');
    });

    it('retries a stuck cart release for a viewer who may, every line together, and says why it could not', async () => {
        const user = userEvent.setup();
        const page = props(exceptionsFixture);
        const stuck = {
            ...page.exceptions[0],
            id: '01k0cartcheckout0000000000',
            kind: 'cart_release' as const,
            reference: 'CRT-000000',
            subject: { kind: 'investor' as const, label: 'Aline Uwase' },
            note_title: null,
            description:
                'Cart release failed (SETTLEMENT_FAILED, 2 attempts): 2 lines still held',
            amount: { currency: 'RWF' as const, amount: '25000' },
            dpd: null,
            owner: null,
            link: null,
            retry: {
                url: '/admin/exceptions/cart-releases/01k0cartcheckout0000000000/retry',
                method: 'post' as const,
            },
        };

        page.exceptions = [
            stuck,
            { ...stuck, id: 'other', reference: 'CRT-111111', retry: null },
        ];
        const { unmount } = render(<AdminExceptions {...page} />);

        expect(rowOf('CRT-000000')).toHaveTextContent('Cart release');
        expect(rowOf('CRT-000000')).toHaveTextContent('RWF 25,000');
        expect(
            within(rowOf('CRT-111111')).queryByRole('button'),
        ).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', {
                name: 'Retry the release of CRT-000000',
            }),
        );

        expect(inertia.posts).toHaveLength(1);
        expect(inertia.posts[0].url).toBe(stuck.retry.url);
        expect(inertia.posts[0].data.request_id).toMatch(/^[0-9a-f-]{36}$/);

        inertia.processing = true;
        inertia.errors = {
            request_id: 'This cart was already released. Refresh the list.',
        };
        unmount();
        render(<AdminExceptions {...page} />);

        const pending = screen.getByRole('button', {
            name: 'Retry the release of CRT-000000',
        });

        expect(pending).toBeDisabled();
        expect(pending).toHaveTextContent('Releasing…');
        expect(rowOf('CRT-000000')).toHaveTextContent(
            'This cart was already released. Refresh the list.',
        );
    });
});
