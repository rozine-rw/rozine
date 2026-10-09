import { screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AdminInvestorVerifications from '@/pages/admin/investor-verifications';
import type {
    AdminInvestorVerificationsProps,
    InvestorVerificationReview,
} from '@/types/admin';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia, resetInertia } from './inertia-mock';

vi.mock('@inertiajs/react', () => import('./inertia-mock'));

const link = (url: string) => ({ url, method: 'get' as const });

const nav = {
    investors: link('/admin/investors'),
    compliance: link('/admin/investor-verifications'),
    launcher: link('/dashboard'),
    today: null,
    applications: null,
    disbursements: null,
    repayments: null,
    businesses: null,
    auditors: null,
    staff: null,
    ledger: null,
    events: null,
};

const queue = (
    overrides: Partial<AdminInvestorVerificationsProps> = {},
): AdminInvestorVerificationsProps => ({
    contract_version: 'staff-investor-verifications-v1',
    server_time: '2026-10-06T10:00:00+00:00',
    viewer: {
        id: '7',
        name: 'Claudine Mukamana',
        email: 'claudine@rozine.rw',
        initials: 'C',
        role: 'compliance',
    },
    nav,
    badges: { applications: null, disbursements: null },
    search: '',
    active_tab: 'submitted',
    tabs: [
        {
            key: 'submitted',
            count: 2,
            link: link('/admin/investor-verifications?tab=submitted'),
        },
        {
            key: 'decided',
            count: 1,
            link: link('/admin/investor-verifications?tab=decided'),
        },
    ],
    entries: [
        {
            id: '01j9aline0000000000000000a',
            revision: 6,
            status: 'submitted',
            submitted_at: '2026-10-06T08:00:00+00:00',
            decided_at: null,
            name: 'Aline Uwase',
            email: 'aline@example.rw',
            id_type: 'national_id',
            selected: true,
            link: link('/admin/investor-verifications?verification=a'),
        },
        {
            id: '01j9jean00000000000000000b',
            revision: 5,
            status: 'submitted',
            submitted_at: '2026-10-06T09:30:00+00:00',
            decided_at: null,
            name: 'Jean Habimana',
            email: 'jean@example.rw',
            id_type: 'passport',
            selected: false,
            link: link('/admin/investor-verifications?verification=b'),
        },
    ],
    pagination: {
        next: link('/admin/investor-verifications?tab=submitted&before=b'),
    },
    review: null,
    ...overrides,
});

const review = (
    overrides: Partial<InvestorVerificationReview> = {},
): InvestorVerificationReview => ({
    id: '01j9aline0000000000000000a',
    revision: 6,
    status: 'submitted',
    submitted_at: '2026-10-06T08:00:00+00:00',
    account: { name: 'Aline Uwase', email: 'aline@example.rw' },
    date_of_birth: '01 / 05 / 1990',
    id_type: 'national_id',
    id_number: '1199080012345678',
    decision: null,
    documents: [
        {
            id: 'doc-front-old',
            slot: 'front',
            filename: 'blurred.png',
            media_type: 'image/png',
            size_bytes: 2048,
            sha256: 'a'.repeat(64),
            uploaded_at: '2026-10-06T07:00:00+00:00',
            current: false,
            link: link('/admin/investor-verifications/a/documents/old'),
            view: link(
                '/admin/investor-verifications/a/documents/old?disposition=inline',
            ),
        },
        {
            id: 'doc-front',
            slot: 'front',
            filename: 'front.png',
            media_type: 'image/png',
            size_bytes: 1500,
            sha256: 'b'.repeat(64),
            uploaded_at: '2026-10-06T07:10:00+00:00',
            current: true,
            link: link('/admin/investor-verifications/a/documents/front'),
            view: link(
                '/admin/investor-verifications/a/documents/front?disposition=inline',
            ),
        },
    ],
    history: [
        {
            revision: 1,
            status: 'draft',
            command: 'verification.save',
            reason: null,
            at: '2026-10-06T07:00:00+00:00',
        },
        {
            revision: 6,
            status: 'submitted',
            command: 'verification.submit',
            reason: null,
            at: '2026-10-06T08:00:00+00:00',
        },
    ],
    links: { close: link('/admin/investor-verifications?tab=submitted') },
    actions: {
        approve: {
            url: '/admin/investor-verifications/a/approve',
            method: 'post',
        },
        reject: {
            url: '/admin/investor-verifications/a/reject',
            method: 'post',
        },
    },
    ...overrides,
});

beforeEach(() => {
    resetInertia();
    vi.spyOn(crypto, 'randomUUID').mockReturnValue(
        '9b2f6f5c-6c1e-4f2b-8a37-2f1c6f4b9d10',
    );
});

describe('Compliance identity queue', () => {
    it('sits under Compliance in the console, which it lights and titles', () => {
        renderWithUser(<AdminInvestorVerifications {...queue()} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Compliance Dashboard',
        );
        const console = screen.getByRole('navigation', {
            name: 'Console navigation',
        });
        expect(
            within(console).getByRole('link', { name: 'Compliance' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(console).getByRole('link', { name: 'Investors' }),
        ).not.toHaveAttribute('aria-current');
    });

    it('lists submissions with their document, age and state, and pages on', () => {
        renderWithUser(<AdminInvestorVerifications {...queue()} />);

        expect(
            screen.getByText(
                /Identity submissions from people who want to invest/,
            ),
        ).toBeInTheDocument();
        const tabs = screen.getByRole('navigation', {
            name: 'Verification states',
        });
        expect(
            within(tabs).getByRole('link', { name: 'Waiting for review 2' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(tabs).getByRole('link', { name: 'Decided 1' }),
        ).not.toHaveAttribute('aria-current');

        const table = screen.getByRole('table', {
            name: 'Identity submissions',
        });
        const [, aline, jean] = within(table).getAllByRole('row');
        expect(aline).toHaveAttribute('aria-current', 'true');
        expect(jean).not.toHaveAttribute('aria-current');
        expect(
            within(aline).getByRole('link', { name: 'Aline Uwase' }),
        ).toHaveAttribute(
            'href',
            '/admin/investor-verifications?verification=a',
        );
        expect(within(aline).getByText('National ID')).toBeInTheDocument();
        expect(within(aline).getByText('2h ago')).toBeInTheDocument();
        expect(within(aline).getAllByText('Waiting')).toHaveLength(1);
        expect(within(jean).getByText('Passport')).toBeInTheDocument();
        expect(within(jean).getByText('jean@example.rw')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Show more' })).toHaveAttribute(
            'href',
            '/admin/investor-verifications?tab=submitted&before=b',
        );
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('says when nothing is waiting, when nothing is decided, and when a search finds no one', () => {
        const { rerender } = renderWithUser(
            <AdminInvestorVerifications
                {...queue({ entries: [], pagination: { next: null } })}
            />,
        );
        expect(
            screen.getByText('No submissions are waiting for review.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Show more' }),
        ).not.toBeInTheDocument();

        rerender(
            <AdminInvestorVerifications
                {...queue({
                    entries: [],
                    active_tab: 'decided',
                    pagination: { next: null },
                })}
            />,
        );
        expect(screen.getByText('No decisions yet.')).toBeInTheDocument();

        rerender(
            <AdminInvestorVerifications
                {...queue({
                    entries: [],
                    search: 'nobody',
                    pagination: { next: null },
                })}
            />,
        );
        expect(screen.getByRole('status')).toHaveTextContent('nobody');
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
    });
});

describe('Compliance review drawer', () => {
    it('shows the submitted answers, private document links and history', () => {
        renderWithUser(
            <AdminInvestorVerifications {...queue({ review: review() })} />,
        );
        const drawer = screen.getByRole('dialog', {
            name: 'Identity submission',
        });

        expect(
            within(drawer).getByRole('heading', { name: 'Aline Uwase' }),
        ).toBeInTheDocument();
        expect(
            within(drawer).getByText('aline@example.rw'),
        ).toBeInTheDocument();
        const facts = within(drawer).getByRole('region', {
            name: 'Submitted details',
        });
        expect(within(facts).getByText('01 / 05 / 1990')).toBeInTheDocument();
        expect(within(facts).getByText('National ID')).toBeInTheDocument();
        expect(within(facts).getByText('1199080012345678')).toBeInTheDocument();
        expect(
            within(facts).getByText('2026-10-06 10:00:00'),
        ).toBeInTheDocument();

        const documents = within(drawer).getByRole('region', {
            name: 'Documents',
        });
        const current = within(documents).getByRole('list', {
            name: 'Current documents',
        });
        const [front] = within(current).getAllByRole('listitem');
        expect(within(front).getByText('ID front')).toBeInTheDocument();
        expect(within(front).getByText('front.png · 2 KB')).toBeInTheDocument();
        expect(
            within(front).getByRole('button', { name: 'View ID front' }),
        ).toContainHTML(
            'src="/admin/investor-verifications/a/documents/front?disposition=inline"',
        );
        expect(
            within(front).getByRole('link', { name: 'Download front.png' }),
        ).toHaveAttribute(
            'href',
            '/admin/investor-verifications/a/documents/front',
        );
        expect(
            within(documents).getByText('Earlier uploads'),
        ).toBeInTheDocument();
        const [replaced] = within(documents)
            .getAllByRole('listitem')
            .filter((item) => !current.contains(item));
        expect(within(replaced).getByText('Replaced')).toBeInTheDocument();
        expect(
            within(replaced).getByText('blurred.png · 2 KB'),
        ).toBeInTheDocument();
        expect(
            within(replaced).getByRole('link', {
                name: 'Download blurred.png',
            }),
        ).toHaveAttribute(
            'href',
            '/admin/investor-verifications/a/documents/old',
        );

        const history = within(drawer).getByRole('region', { name: 'History' });
        expect(within(history).getByText('Saved a step')).toBeInTheDocument();
        expect(
            within(history).getByText('Submitted for review'),
        ).toBeInTheDocument();
        expect(
            within(drawer).queryByRole('region', { name: 'Decision' }),
        ).not.toBeInTheDocument();
        expect(within(drawer).queryByRole('alert')).not.toBeInTheDocument();
    });

    it('approves with a reason, the reviewed revision and one request id', async () => {
        inertia.succeed = true;
        const { user } = renderWithUser(
            <AdminInvestorVerifications {...queue({ review: review() })} />,
        );

        await user.click(screen.getByRole('button', { name: 'Approve' }));
        const stage = screen.getByRole('form', {
            name: 'Approve this identity',
        });
        expect(stage).toHaveClass('border-[#1d9e75]');
        expect(
            within(stage).getByText(
                'Logged to the audit trail as Claudine Mukamana · Compliance',
            ),
        ).toBeInTheDocument();
        await user.type(
            within(stage).getByRole('textbox'),
            'Photo, number and selfie match.',
        );
        await user.click(
            within(stage).getByRole('button', { name: 'Approve identity' }),
        );

        expect(inertia.posts).toEqual([
            {
                url: '/admin/investor-verifications/a/approve',
                data: {
                    reason: 'Photo, number and selfie match.',
                    request_id: '9b2f6f5c-6c1e-4f2b-8a37-2f1c6f4b9d10',
                    expected_revision: 6,
                },
            },
        ]);
        expect(
            screen.queryByRole('form', { name: 'Approve this identity' }),
        ).not.toBeInTheDocument();
    });

    it('rejects through the red stage, which can be cancelled', async () => {
        const { user } = renderWithUser(
            <AdminInvestorVerifications {...queue({ review: review() })} />,
        );

        await user.click(screen.getByRole('button', { name: 'Reject' }));
        const stage = screen.getByRole('form', {
            name: 'Reject this submission',
        });
        expect(stage).toHaveClass('border-[#e5484d]');
        expect(
            within(stage).getByText(
                'The person sees your reason and can correct their details.',
            ),
        ).toBeInTheDocument();
        await user.click(within(stage).getByRole('button', { name: 'Cancel' }));
        expect(
            screen.getByRole('button', { name: 'Reject' }),
        ).toBeInTheDocument();
        expect(inertia.posts).toEqual([]);
    });

    it('shows the server refusal and only the actions the server sends', () => {
        inertia.errors = {
            form: 'This case changed since you opened it. Review it again.',
        };
        renderWithUser(
            <AdminInvestorVerifications
                {...queue({
                    review: review({
                        actions: {
                            approve: {
                                url: '/admin/investor-verifications/a/approve',
                                method: 'post',
                            },
                        },
                    }),
                })}
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            'This case changed since you opened it. Review it again.',
        );
        expect(
            screen.getByRole('button', { name: 'Approve' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Reject' }),
        ).not.toBeInTheDocument();

        inertia.errors = {};
    });

    it('reads a decided case with its reason, and a reopened draft without actions', () => {
        const { rerender } = renderWithUser(
            <AdminInvestorVerifications
                {...queue({
                    review: review({
                        status: 'approved',
                        revision: 7,
                        decision: {
                            outcome: 'approved',
                            reason: 'Photo, number and selfie match.',
                            decided_at: '2026-10-06T09:00:00+00:00',
                        },
                        history: [
                            {
                                revision: 7,
                                status: 'approved',
                                command: 'verification.approve',
                                reason: 'Photo, number and selfie match.',
                                at: '2026-10-06T09:00:00+00:00',
                            },
                        ],
                        actions: {},
                    }),
                })}
            />,
        );
        const decision = screen.getByRole('region', { name: 'Decision' });
        expect(decision).toHaveTextContent('Approved · 2026-10-06 11:00:00');
        expect(decision).toHaveTextContent('Photo, number and selfie match.');
        const history = screen.getByRole('region', { name: 'History' });
        expect(
            within(history).getByText('Approved by Compliance'),
        ).toBeInTheDocument();
        expect(
            within(history).getByText('Photo, number and selfie match.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Approve' }),
        ).not.toBeInTheDocument();

        rerender(
            <AdminInvestorVerifications
                {...queue({
                    review: review({
                        status: 'draft',
                        submitted_at: null,
                        actions: {},
                    }),
                })}
            />,
        );
        const drawer = screen.getByRole('dialog', {
            name: 'Identity submission',
        });
        expect(within(drawer).getByText('Reopened')).toBeInTheDocument();
        expect(within(drawer).queryByText('Submitted')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Reject' }),
        ).not.toBeInTheDocument();
    });
});

describe('Compliance document viewer', () => {
    const pdf = {
        id: 'doc-back',
        slot: 'back' as const,
        filename: 'back.pdf',
        media_type: 'application/pdf',
        size_bytes: 4096,
        sha256: 'c'.repeat(64),
        uploaded_at: '2026-10-06T07:20:00+00:00',
        current: true,
        link: link('/admin/investor-verifications/a/documents/back'),
        view: link(
            '/admin/investor-verifications/a/documents/back?disposition=inline',
        ),
    };

    it('opens a document full size and steps through the case without closing it', async () => {
        const { user } = renderWithUser(
            <AdminInvestorVerifications {...queue({ review: review() })} />,
        );

        await user.click(screen.getByRole('button', { name: 'View ID front' }));
        let viewer = screen.getByRole('dialog', { name: 'ID front' });
        expect(within(viewer).getByText('1 of 2')).toBeInTheDocument();
        expect(viewer).toContainHTML(
            'src="/admin/investor-verifications/a/documents/front?disposition=inline"',
        );
        expect(
            within(viewer).getByRole('link', { name: 'Open in new tab' }),
        ).toHaveAttribute(
            'href',
            '/admin/investor-verifications/a/documents/front?disposition=inline',
        );
        expect(
            within(viewer).getByRole('link', { name: 'Download front.png' }),
        ).toHaveAttribute(
            'href',
            '/admin/investor-verifications/a/documents/front',
        );

        await user.click(
            within(viewer).getByRole('button', { name: 'Next document' }),
        );
        viewer = screen.getByRole('dialog', { name: 'ID front Replaced' });
        expect(within(viewer).getByText('2 of 2')).toBeInTheDocument();
        expect(
            within(viewer).getByText('blurred.png · 2 KB'),
        ).toBeInTheDocument();

        await user.keyboard('{ArrowRight}');
        expect(
            screen.getByRole('dialog', { name: 'ID front' }),
        ).toBeInTheDocument();
        await user.keyboard('{ArrowLeft}');
        expect(
            screen.getByRole('dialog', { name: 'ID front Replaced' }),
        ).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Previous document' }),
        );
        expect(
            screen.getByRole('dialog', { name: 'ID front' }),
        ).toBeInTheDocument();

        await user.keyboard('{Escape}');
        expect(
            screen.queryByRole('dialog', { name: 'ID front' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('dialog', { name: 'Identity submission' }),
        ).toBeInTheDocument();
        expect(inertia.visits).toEqual([]);
    });

    it('opens an earlier upload from its row and closes with the close button', async () => {
        const { user } = renderWithUser(
            <AdminInvestorVerifications {...queue({ review: review() })} />,
        );

        await user.click(
            screen.getByRole('button', { name: 'View blurred.png' }),
        );
        const viewer = screen.getByRole('dialog', {
            name: 'ID front Replaced',
        });
        expect(within(viewer).getByText('2 of 2')).toBeInTheDocument();

        await user.click(
            within(viewer).getByRole('button', { name: 'Close viewer' }),
        );
        expect(
            screen.queryByRole('dialog', { name: 'ID front Replaced' }),
        ).not.toBeInTheDocument();
    });

    it('shows a PDF as a tile, and a single document without stepping', async () => {
        const { user } = renderWithUser(
            <AdminInvestorVerifications
                {...queue({ review: review({ documents: [pdf] }) })}
            />,
        );
        const documents = screen.getByRole('region', { name: 'Documents' });
        expect(
            within(documents).queryByText('Earlier uploads'),
        ).not.toBeInTheDocument();
        const tile = within(documents).getByRole('button', {
            name: 'View ID back',
        });
        expect(within(tile).getByText('PDF')).toBeInTheDocument();
        expect(
            within(tile).queryByRole('presentation'),
        ).not.toBeInTheDocument();

        await user.click(tile);
        const viewer = screen.getByRole('dialog', { name: 'ID back' });
        expect(within(viewer).getByText('1 of 1')).toBeInTheDocument();
        expect(within(viewer).getByText('PDF')).toBeInTheDocument();
        expect(
            within(viewer).queryByRole('button', { name: 'Next document' }),
        ).not.toBeInTheDocument();
        await user.keyboard('{ArrowRight}');
        expect(
            screen.getByRole('dialog', { name: 'ID back' }),
        ).toBeInTheDocument();
        await user.keyboard('{Home}');
        expect(within(viewer).getByText('1 of 1')).toBeInTheDocument();
    });

    it('lists no current documents when every upload was replaced', () => {
        renderWithUser(
            <AdminInvestorVerifications
                {...queue({
                    review: review({ documents: [{ ...pdf, current: false }] }),
                })}
            />,
        );
        const documents = screen.getByRole('region', { name: 'Documents' });
        expect(
            within(documents).queryByRole('list', {
                name: 'Current documents',
            }),
        ).not.toBeInTheDocument();
        expect(
            within(documents).getByText('Earlier uploads'),
        ).toBeInTheDocument();
    });
});
