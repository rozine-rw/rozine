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
        expect(
            within(business).getByRole('link', { name: 'Businesses →' }),
        ).toHaveAttribute('href', '/admin/businesses');
    });

    it('says when nothing has been asked for yet', () => {
        render(<AdminAccessRequests {...page([])} />);

        expect(screen.getByText('No access requests')).toBeInTheDocument();
    });
});
