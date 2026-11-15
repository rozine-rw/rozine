import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminAccessRequests from '@/pages/admin/access-requests';
import type { AdminAccessRequestsProps } from '@/types/admin';
import coverageFixture from '../../../resources/fixtures/ui/admin-coverage.json';
import { resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const shell = structuredClone(coverageFixture.props) as unknown as Omit<
    AdminAccessRequestsProps,
    'roles' | 'requests' | 'links'
>;

const page = (
    requests: AdminAccessRequestsProps['requests'],
): AdminAccessRequestsProps => ({
    ...shell,
    nav: {
        ...shell.nav,
        access_requests: { url: '/admin/access-requests', method: 'get' },
    },
    search: '',
    roles: ['business', 'auditor'],
    requests,
    links: {
        business: { url: '/admin/businesses', method: 'get' },
        auditor: { url: '/admin/auditors', method: 'get' },
    },
});

const rowOf = (name: string) => {
    const [row] = screen
        .getAllByRole('row')
        .filter((candidate) => within(candidate).queryByText(name));

    return row;
};

beforeEach(resetInertia);

describe('Access requests', () => {
    it('lists each request with who sent it, what they gave and where to grant it', () => {
        render(
            <AdminAccessRequests
                {...page([
                    {
                        id: '01JAUDITOR',
                        role: 'auditor',
                        name: 'Grace Uwase',
                        email: 'grace@example.test',
                        organization: '',
                        reference: 'ICPAR-2041',
                        phone: null,
                        note: null,
                        sent_at: '2026-11-14T13:45:00+00:00',
                        outcome: null,
                        handled_at: null,
                        identity_verified: true,
                        grant: { url: '/admin/auditors', method: 'get' },
                    },
                    {
                        id: '01JBUSINESS',
                        role: 'business',
                        name: 'Jean Mugisha',
                        email: 'jean@example.test',
                        organization: 'Kivu Harvest Ltd',
                        reference: null,
                        phone: '+250 788 000 000',
                        note: 'Coffee washing station',
                        sent_at: '2026-11-14T12:00:00+00:00',
                        outcome: null,
                        handled_at: null,
                        identity_verified: false,
                        grant: {
                            url: '/admin/businesses?onboard_request=01JBUSINESS',
                            method: 'get',
                        },
                    },
                ])}
            />,
        );

        expect(screen.getByTestId('head')).toHaveTextContent('Access requests');
        expect(
            within(
                screen.getByRole('navigation', { name: 'Console navigation' }),
            ).getByRole('link', { name: 'Access requests' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(screen.queryByRole('searchbox')).not.toBeInTheDocument();

        const auditor = rowOf('Grace Uwase');

        expect(auditor).toHaveTextContent('Auditor');
        expect(auditor).toHaveTextContent('—');
        expect(auditor).toHaveTextContent('ICPAR licence ICPAR-2041');
        expect(auditor).toHaveTextContent('Identity verified');
        expect(auditor).toHaveTextContent('14 Nov 2026 · 15:45 CAT');
        expect(
            within(auditor).getByRole('link', { name: 'grace@example.test' }),
        ).toHaveAttribute('href', 'mailto:grace@example.test');
        expect(
            within(auditor).getByRole('link', { name: 'Auditor Network →' }),
        ).toHaveAttribute('href', '/admin/auditors');

        const business = rowOf('Jean Mugisha');

        expect(business).toHaveTextContent('Kivu Harvest Ltd');
        expect(business).toHaveTextContent('Coffee washing station');
        expect(
            within(business).getByRole('link', { name: '+250 788 000 000' }),
        ).toHaveAttribute('href', 'tel:+250 788 000 000');
        expect(business).toHaveTextContent('Identity not verified');
        expect(
            within(business).getByRole('link', {
                name: 'Onboard this business →',
            }),
        ).toHaveAttribute(
            'href',
            '/admin/businesses?onboard_request=01JBUSINESS',
        );
        expect(screen.getByText('Nothing handled yet')).toBeInTheDocument();
    });

    it('moves a request whose person now holds the role to Handled, with the membership status', () => {
        const sent = {
            role: 'business' as const,
            email: 'person@example.test',
            organization: 'Kivu Harvest Ltd',
            reference: null,
            phone: null,
            note: null,
            sent_at: '2026-11-14T12:00:00+00:00',
            handled_at: '2026-11-15T09:30:00+00:00',
            identity_verified: true,
            grant: { url: '/admin/businesses', method: 'get' as const },
        };
        render(
            <AdminAccessRequests
                {...page([
                    {
                        ...sent,
                        id: '1',
                        name: 'Open One',
                        outcome: null,
                        handled_at: null,
                    },
                    {
                        ...sent,
                        id: '2',
                        name: 'Pending One',
                        outcome: 'pending',
                    },
                    { ...sent, id: '3', name: 'Active One', outcome: 'active' },
                    {
                        ...sent,
                        id: '4',
                        name: 'Paused One',
                        outcome: 'suspended',
                    },
                    {
                        ...sent,
                        id: '5',
                        name: 'Revoked One',
                        outcome: 'revoked',
                    },
                ])}
            />,
        );

        const open = screen.getByRole('table', { name: 'Access requests' });
        const handled = screen.getByRole('table', {
            name: 'Handled access requests',
        });

        expect(within(open).getByText('Open One')).toBeInTheDocument();
        expect(within(open).queryByText('Active One')).not.toBeInTheDocument();
        expect(within(handled).queryByText('Open One')).not.toBeInTheDocument();
        expect(within(handled).getByText('Membership')).toBeInTheDocument();

        for (const [name, status] of [
            ['Pending One', 'Pending'],
            ['Active One', 'Active'],
            ['Paused One', 'Suspended'],
            ['Revoked One', 'Revoked'],
        ]) {
            const row = rowOf(name);

            expect(row).toHaveTextContent(status);
            expect(row).toHaveTextContent(
                'Role last updated 15 Nov 2026 · 11:30 CAT',
            );
            expect(
                within(row).queryByRole('link', {
                    name: 'Onboard this business →',
                }),
            ).not.toBeInTheDocument();
        }
    });

    it('says when every request has been handled', () => {
        render(
            <AdminAccessRequests
                {...page([
                    {
                        id: '1',
                        role: 'auditor',
                        name: 'Grace Uwase',
                        email: 'grace@example.test',
                        organization: '',
                        reference: 'ICPAR-2041',
                        phone: null,
                        note: null,
                        sent_at: '2026-11-14T13:45:00+00:00',
                        outcome: 'active',
                        handled_at: '2026-11-15T09:30:00+00:00',
                        identity_verified: true,
                        grant: { url: '/admin/auditors', method: 'get' },
                    },
                ])}
            />,
        );

        expect(screen.getByText('No open access requests')).toBeInTheDocument();
        expect(rowOf('Grace Uwase')).toHaveTextContent('Active');
    });

    it('says when nothing has been asked for yet', () => {
        render(<AdminAccessRequests {...page([])} />);

        expect(screen.getByText('No open access requests')).toBeInTheDocument();
        expect(screen.getByText('Nothing handled yet')).toBeInTheDocument();
        expect(
            screen.queryByText('Swipe sideways to see every column.'),
        ).not.toBeInTheDocument();
        // With no rows the header fits the phone instead of scrolling sideways.
        expect(
            screen
                .getByRole('group', { name: 'Access requests' })
                .querySelector('.min-w-0'),
        ).not.toBeNull();
        expect(
            screen
                .getByRole('group', { name: 'Access requests' })
                .querySelector('.min-w-\\[760px\\]'),
        ).toBeNull();
    });
});
