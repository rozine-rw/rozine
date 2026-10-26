import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminMessaging from '@/pages/admin/messaging';
import type { AdminMessagingProps, MessageHistoryRow } from '@/types/admin';
import reconciliationFixture from '../../../resources/fixtures/ui/admin-reconciliation.json';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const row = (overrides: Partial<MessageHistoryRow>): MessageHistoryRow => ({
    id: '01k0000000000000000000000a',
    recipient: 'Aline Uwase',
    recipient_kind: 'person',
    kind: 'verification.approved',
    category: 'onboarding',
    source: 'automation',
    channel: 'mail',
    status: 'sent',
    at: '2027-01-22T08:15:00+00:00',
    ...overrides,
});

const props = (
    overrides: Partial<AdminMessagingProps> = {},
): AdminMessagingProps => {
    const { viewer, nav, badges } = structuredClone(
        reconciliationFixture.props,
    ) as unknown as AdminMessagingProps;

    return {
        viewer,
        nav: {
            ...nav,
            messaging: { url: '/admin/messaging/history', method: 'get' },
        },
        badges,
        search: '',
        contract_version: 'staff-messaging-v1',
        server_time: '2027-01-22T10:00:00+00:00',
        history: [
            row({ id: 'a', status: 'failed' }),
            row({
                id: 'b',
                recipient: 'Kigali Bakery',
                recipient_kind: 'business',
                kind: 'business.deposit_received',
                category: 'wallet',
                channel: 'in_app',
                status: 'delivered',
            }),
        ],
        automations: [],
        templates: [],
        older: { url: '/admin/messaging/history?before=b', method: 'get' },
        newest: null,
        ...overrides,
    };
};

const rowOf = (text: string) =>
    screen
        .getAllByRole('row')
        .find((candidate) =>
            within(candidate).queryByText(text),
        ) as HTMLElement;

beforeEach(resetInertia);

describe('Messaging History', () => {
    it.each([
        ['business.disbursement_sent', 'payments', 'Funds disbursed'],
        ['business.raise_funded', 'fundraising', 'Raise fully funded'],
    ] as const)(
        'names the backed %s rule without changing its notice title',
        (kind, category, title) => {
            render(
                <AdminMessaging
                    {...props({
                        automations: [
                            {
                                kind,
                                category,
                                audience: 'business',
                                channels: ['in_app'],
                            },
                        ],
                        history: [
                            row({
                                kind,
                                category,
                                recipient: 'Kigali Bakery',
                                recipient_kind: 'business',
                            }),
                        ],
                    })}
                />,
            );

            fireEvent.click(screen.getByRole('tab', { name: 'Automations' }));
            const rules = screen.getByRole('table', {
                name: 'Businesses automations',
            });
            expect(
                within(rules).getByRole('row', {
                    name: new RegExp(title, 'u'),
                }),
            ).toHaveTextContent('As it happens · In-app');
            expect(
                screen.queryByText('Monthly report reminder'),
            ).not.toBeInTheDocument();

            fireEvent.click(screen.getByRole('tab', { name: 'History' }));
            expect(rowOf('Kigali Bakery')).toHaveTextContent(
                kind === 'business.disbursement_sent'
                    ? 'Funds sent'
                    : 'Raise fully funded',
            );
        },
    );

    it('lists each notice with its recipient, message, category, automation, outcome, channel and time', () => {
        render(<AdminMessaging {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Messaging & Automations',
        );
        expect(
            screen.getByRole('tab', { name: 'Automations' }),
        ).toHaveAttribute('aria-selected', 'false');
        expect(screen.getByRole('tab', { name: 'History' })).toHaveAttribute(
            'aria-selected',
            'true',
        );

        const mail = rowOf('Aline Uwase');

        expect(mail).toHaveTextContent('You are verified');
        expect(mail).toHaveTextContent('Onboarding');
        expect(mail).toHaveTextContent('Automation');
        expect(mail).toHaveTextContent('Email failed');
        expect(mail).toHaveTextContent('Email');

        const inApp = rowOf('Kigali Bakery');

        expect(inApp).toHaveTextContent('Deposit received');
        expect(inApp).toHaveTextContent('Wallet');
        expect(inApp).toHaveTextContent('In the app inbox');
        expect(inApp).toHaveTextContent('In-app');

        expect(
            screen.getByRole('link', { name: 'Older messages' }),
        ).toHaveAttribute('href', '/admin/messaging/history?before=b');
        expect(
            screen.queryByRole('link', { name: 'Newest messages' }),
        ).not.toBeInTheDocument();
    });

    it('says when a search matches nothing, and searches from the first page', () => {
        render(
            <AdminMessaging
                {...props({
                    search: 'nobody',
                    history: [],
                    older: null,
                    newest: {
                        url: '/admin/messaging/history?q=nobody',
                        method: 'get',
                    },
                })}
            />,
        );

        expect(screen.getByRole('tab', { name: 'History' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(
            screen.getByText('No messages match your search.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Newest messages' }),
        ).toHaveAttribute('href', '/admin/messaging/history?q=nobody');
        expect(
            screen.queryByRole('link', { name: 'Older messages' }),
        ).not.toBeInTheDocument();

        const search = screen.getByRole('search');

        fireEvent.change(within(search).getByRole('searchbox'), {
            target: { value: 'Aline' },
        });
        fireEvent.submit(search);

        expect(inertia.reload).toContainEqual({
            data: { q: 'Aline', before: undefined },
        });
    });

    it('stays on History when paging back through older messages', () => {
        render(
            <AdminMessaging
                {...props({
                    newest: { url: '/admin/messaging/history', method: 'get' },
                })}
            />,
        );

        expect(screen.getByRole('tab', { name: 'History' })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(rowOf('Aline Uwase')).toHaveTextContent('Email failed');
    });

    it('shows an empty history before any notice went out', () => {
        render(<AdminMessaging {...props({ history: [], older: null })} />);

        expect(
            screen.getByText('No messages to show yet.'),
        ).toBeInTheDocument();
    });

    it('lists each automation with its audience, timing and channels, and each email template', () => {
        render(
            <AdminMessaging
                {...props({
                    automations: [
                        {
                            kind: 'verification.approved',
                            category: 'onboarding',
                            audience: 'investor',
                            channels: ['in_app', 'mail'],
                        },
                        {
                            kind: 'support.complaint_resolved',
                            category: 'support',
                            audience: 'everyone',
                            channels: ['in_app'],
                        },
                    ],
                    templates: [
                        {
                            key: 'auditor.job_offered',
                            audience: 'auditor',
                        },
                        {
                            key: 'business.disbursement_sent',
                            audience: 'business',
                        },
                        {
                            key: 'business.reporting_window_open',
                            audience: 'business',
                        },
                    ],
                })}
            />,
        );

        fireEvent.click(screen.getByRole('tab', { name: 'Automations' }));
        expect(screen.getByText('2 automations live')).toBeInTheDocument();
        expect(
            screen
                .getAllByRole('heading', { level: 3 })
                .map((heading) => heading.textContent),
        ).toEqual([
            'Investors automations',
            'Everyone with an account automations',
        ]);
        expect(
            within(screen.getByRole('table', { name: 'Investors automations' }))
                .getAllByRole('columnheader')
                .map((cell) => cell.textContent),
        ).toEqual(['Rule', 'Fires when', 'Timing & channels', 'On']);
        expect(
            within(rowOf('Verification approved'))
                .getAllByRole('cell')
                .map((cell) => cell.textContent),
        ).toEqual([
            'Verification approved',
            'Compliance approves an identity check',
            'As it happens · In-app, Email',
            'Live',
        ]);
        expect(
            within(
                screen.getByRole('table', {
                    name: 'Everyone with an account automations',
                }),
            ).getByRole('row', { name: /Complaint resolved/u }),
        ).toHaveTextContent('A complaint is resolvedAs it happens · In-app');
        expect(
            screen.queryByRole('button', { name: /edit/i }),
        ).not.toBeInTheDocument();

        fireEvent.click(screen.getByRole('tab', { name: 'Templates' }));
        expect(
            within(rowOf('Audit job offered'))
                .getAllByRole('cell')
                .map((cell) => cell.textContent),
        ).toEqual(['Audit job offered', 'Audit Partners', 'Email']);
        expect(
            within(rowOf('Funds disbursed'))
                .getAllByRole('cell')
                .map((cell) => cell.textContent),
        ).toEqual(['Funds disbursed', 'Businesses', 'Email']);
        expect(
            within(rowOf('Monthly report reminder'))
                .getAllByRole('cell')
                .map((cell) => cell.textContent),
        ).toEqual(['Monthly report reminder', 'Businesses', 'Email']);

        fireEvent.click(screen.getByRole('tab', { name: 'Campaigns' }));
        expect(
            screen.queryByText(/cannot be edited from the console/u),
        ).not.toBeInTheDocument();
    });

    it('names the staff-triggered report reminder in the Audit Partner automations', () => {
        render(
            <AdminMessaging
                {...props({
                    automations: [
                        {
                            kind: 'auditor.report_reminder',
                            category: 'audits',
                            audience: 'auditor',
                            channels: ['in_app'],
                        },
                    ],
                })}
            />,
        );

        fireEvent.click(screen.getByRole('tab', { name: 'Automations' }));
        expect(rowOf('Audit report reminder')).toHaveTextContent(
            'Staff reminds the Audit Partner about a report',
        );
    });

    it('keeps automations, templates and campaigns at their empty state', () => {
        render(<AdminMessaging {...props()} />);
        fireEvent.click(screen.getByRole('tab', { name: 'Automations' }));

        expect(screen.getByText('0 automations live')).toBeInTheDocument();
        expect(
            screen.getByText('No automations to show yet.'),
        ).toBeInTheDocument();
        expect(screen.queryByText('Aline Uwase')).not.toBeInTheDocument();

        fireEvent.click(screen.getByRole('tab', { name: 'Templates' }));
        expect(
            screen.getByText('No templates to show yet.'),
        ).toBeInTheDocument();

        fireEvent.click(screen.getByRole('tab', { name: 'Campaigns' }));
        expect(
            screen.getByText('No campaigns to show yet.'),
        ).toBeInTheDocument();
        expect(
            screen.queryAllByRole('button', { name: /new campaign/i }),
        ).toHaveLength(0);
    });
});
