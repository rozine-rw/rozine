import { render, screen, within } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { bareCompanyCode } from '@/components/business/home/business-hero';
import BusinessHome from '@/pages/business/home';
import type { BusinessHomeProps, BusinessTodo } from '@/types/business';
import inbox from '../../../resources/fixtures/business-notices.json';
import noDealFixture from '../../../resources/fixtures/ui/business-home-no-deal.json';
import homeFixture from '../../../resources/fixtures/ui/business-home.json';
import { renderWithUser } from '../helpers/render-with-user';

const http = vi.hoisted(() => ({
    submit: (): Promise<unknown> => new Promise(() => undefined),
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => (
        <span data-testid="head">{title}</span>
    ),
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
    router: { visit: vi.fn(), reload: vi.fn() },
    usePage: () => ({ props: {} }),
    useHttp: () => ({
        errors: {},
        clearErrors: () => undefined,
        transform: () => undefined,
        submit: () => http.submit(),
    }),
}));

const fixture = homeFixture.props as BusinessHomeProps;
const link = (url: string) => ({ url, method: 'get' as const });
const money = (amount: number) => ({
    currency: 'RWF' as const,
    amount: String(amount),
});

describe('Business Home', () => {
    it('opens the Business notices behind the bell, worded by kind (BRS NTF-R01)', async () => {
        const submit = http.submit;
        http.submit = () => Promise.resolve({ data: inbox });
        const { user } = renderWithUser(<BusinessHome {...fixture} />);

        await user.click(
            screen.getByRole('button', { name: 'Notifications, 2 unread' }),
        );
        const panel = screen.getByRole('dialog', { name: 'Notifications' });
        const items = await within(panel).findAllByRole('button', {
            name: /RWF|audit report/u,
        });

        expect(items).toHaveLength(11);
        expect(items[0]).toHaveTextContent(
            'Raise closed without full fundingSolar Dryer Expansion closed at its deadline with RWF 7,450,000 of RWF 12,000,000 committed. Investors were refunded and no fee was charged.',
        );
        expect(items[1]).toHaveTextContent(
            'Funds sentRWF 6,000,000 for Cold Room Upgrade was sent to Bank of Kigali Account ····2231.',
        );
        expect(items[2]).toHaveTextContent(
            'Raise fully fundedCold Room Upgrade reached its target of RWF 6,000,000. Rozine now prepares the payout to your account.',
        );
        expect(items[3]).toHaveTextContent(
            'Deposit receivedRWF 2,500,000 is now in your Business wallet.',
        );
        expect(items[4]).toHaveTextContent(
            'Withdrawal sentRWF 800,000 was sent to Bank of Kigali Account ····2231.',
        );
        expect(items[5]).toHaveTextContent(
            "Withdrawal returnedThe payout of RWF 300,000 to MTN MoMo +250 788 ···· 456 didn't go through. It's back in your Business wallet.",
        );
        expect(items[6]).toHaveTextContent(
            'Application approvedSolar Dryer Expansion is approved for RWF 12,000,000 over 12 months. Review and sign to publish the raise.',
        );
        expect(items[7]).toHaveTextContent(
            'Review window closingYour window to co-sign or dispute the audit report closes at',
        );
        expect(items[8]).toHaveTextContent(
            'Audit report ready for your approvalAline Uwase sealed your audit report. Co-sign or dispute it by',
        );
        expect(items[9]).toHaveTextContent(
            'Audit report ready for your approvalAline Uwase sealed your audit report. Review it, then co-sign or dispute it.',
        );
        expect(items[10]).toHaveTextContent(
            "Monthly audit openYour September 2026 audit has started. Have the month's statements and records ready so your Audit Partner can verify them and seal the audit report by",
        );

        await user.click(
            within(panel).getByRole('button', { name: 'Close notifications' }),
        );

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        http.submit = submit;
    });

    it('lays out the design sections from server facts', () => {
        render(<BusinessHome {...fixture} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Home');
        expect(screen.getByText('RWF 12,383,800')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Notifications, 2 unread' }),
        ).toBeInTheDocument();
        expect(screen.getByText('GreenLeaf Agro')).toBeInTheDocument();
        expect(screen.getByText('RDB 103847291')).toBeInTheDocument();
        expect(screen.getByText('Strong · 4.8')).toBeInTheDocument();
        expect(
            screen.getByRole('progressbar', {
                name: 'Cold-Chain Hub funding progress',
            }),
        ).toHaveValue(66);
        expect(screen.getAllByText('RWF 11.9M of RWF 18M')).toHaveLength(2);
        expect(screen.getByText('284 investors ›')).toBeInTheDocument();
        expect(screen.getByText('RWF 33,916,731')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Apply for a raise' }),
        ).toBeEnabled();
        expect(screen.getByRole('link', { name: 'Launcher' })).toHaveAttribute(
            'href',
            '/preview/launcher-ready',
        );
    });

    it('prints the RDB prefix once when the recorded code already carries it', () => {
        render(
            <BusinessHome
                {...fixture}
                business={{ ...fixture.business, company_code: 'RDB-0048213' }}
            />,
        );

        expect(screen.getByText('RDB 0048213')).toBeInTheDocument();
        expect(screen.queryByText(/RDB RDB/u)).not.toBeInTheDocument();
    });

    it('shows a verified sole trader with no company line, never a stand-in number', () => {
        render(
            <BusinessHome
                {...fixture}
                business={{
                    name: 'Uwimana Tailoring',
                    company_code: null,
                    industry: 'Textiles & Apparel',
                    district: 'Nyarugenge',
                }}
            />,
        );

        expect(screen.getByText('Uwimana Tailoring')).toBeInTheDocument();
        expect(
            screen.getByText('Textiles & Apparel · Nyarugenge'),
        ).toBeInTheDocument();
        expect(screen.queryByText(/^RDB/u)).not.toBeInTheDocument();
    });

    it("marks the Home tab current in both the sidebar and the tab bar, with the design's four tabs", () => {
        render(<BusinessHome {...fixture} />);

        const navs = screen.getAllByRole('navigation', {
            name: 'App navigation',
        });

        expect(navs).toHaveLength(2);

        for (const nav of navs) {
            expect(
                within(nav).getByRole('link', { name: 'Home' }),
            ).toHaveAttribute('aria-current', 'page');
            expect(
                within(nav).getByRole('link', { name: 'Reports' }),
            ).not.toHaveAttribute('aria-current');
            expect(
                within(nav)
                    .getAllByRole('link')
                    .map((link) => link.textContent),
            ).toEqual(['Home', 'Reports', 'Market', 'Profile']);
            expect(
                within(nav).getByRole('link', { name: 'Market' }),
            ).toHaveAttribute('href', '/preview/business-market');
        }
    });

    it('reads a pending rating, no live raise, no headroom and a quiet bell', () => {
        render(
            <BusinessHome {...(noDealFixture.props as BusinessHomeProps)} />,
        );

        expect(screen.getByText('Pending audit')).toBeInTheDocument();
        expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
        expect(
            screen.queryByText('Headroom available'),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Notifications' }),
        ).toBeInTheDocument();
        expect(screen.getByText('—', { selector: 'div' })).toBeInTheDocument();
    });

    it('hides destinations the live server has no route for, and shows the rating unlinked', () => {
        render(
            <BusinessHome
                {...fixture}
                headroom={money(5_000_000)}
                links={{
                    ...fixture.links,
                    reports: null,
                    profile: null,
                    withdraw: null,
                    notifications: null,
                    rating: null,
                }}
            />,
        );

        expect(
            screen.queryByRole('link', { name: 'Withdraw' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /^Notifications/u }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('Strong · 4.8')).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: /Strong · 4\.8/u }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('Headroom available'),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Deposit' })).toHaveAttribute(
            'href',
            '/preview/business-wallet',
        );
    });

    it('renders every kind of Today item with its call to act', () => {
        const today: BusinessTodo[] = [
            {
                kind: 'audit_cosign',
                report_kind: 'flash',
                link: link('/business/b1/audit-reports/r1'),
            },
            {
                kind: 'application_declined',
                title: 'Solar Roof',
                reason: null,
                link: link('/a'),
            },
            {
                kind: 'application_declined',
                title: 'Cold Room',
                reason: 'Statements incomplete',
                link: link('/b'),
            },
            {
                kind: 'disbursement_ready',
                gross: money(18000000),
                note_title: 'Cold-Chain Hub',
                link: link('/c'),
            },
            {
                kind: 'audit_window',
                month: '2026-10-01T00:00:00+02:00',
                window_open: false,
                days_left: 8,
                sealed_by: '2026-11-07T00:00:00+02:00',
                link: link('/d'),
            },
        ];

        render(<BusinessHome {...fixture} today={today} />);

        expect(screen.getByText('Audit report ready')).toBeInTheDocument();
        expect(screen.getByText('Flash audit report')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Your CPA sealed it. Read the findings before you co-sign.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Review & co-sign/ }),
        ).toHaveAttribute('href', '/business/b1/audit-reports/r1');
        expect(screen.getAllByText('Application declined')).toHaveLength(2);
        expect(
            screen.getByText(
                'Outside your approved capacity — talk to us before resubmitting.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('Statements incomplete')).toBeInTheDocument();
        expect(screen.getAllByText('See why')).toHaveLength(2);
        expect(screen.getByText('RWF 18,000,000')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Confirm disbursement/ }),
        ).toHaveAttribute('href', '/c');
        expect(screen.getByText('October 2026 audit')).toBeInTheDocument();
        expect(
            screen.getByText('Month closes in 8 days · sealed by 7 Nov'),
        ).toBeInTheDocument();
    });

    it('shows the fixture approval, audit window and repayment exactly', () => {
        render(<BusinessHome {...fixture} />);

        expect(
            screen.getByText(
                "Publish it to investors. There's no listing fee.",
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Publish/ }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Your CPA visits after month end · sealed by 7 Oct',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('RWF 4,833,334')).toBeInTheDocument();
        expect(
            screen.getByText('Fleet Expansion Note · Due 5 Oct 2026'),
        ).toBeInTheDocument();
    });

    it('asks for the listing fee only when the server charges one', () => {
        const today: BusinessTodo[] = [
            {
                kind: 'application_approved',
                title: 'Warehouse Robotics',
                fee: money(50000),
                link: link('/p'),
            },
        ];

        render(<BusinessHome {...fixture} today={today} />);

        expect(
            screen.getByText(
                'Pay the RWF 50,000 application fee to publish it to investors.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Pay fee & publish/ }),
        ).toHaveAttribute('href', '/p');
    });

    it('says when nothing needs doing', () => {
        render(<BusinessHome {...fixture} today={[]} />);

        expect(screen.getByText("You're all caught up")).toBeInTheDocument();
    });

    it('explains each capital figure, one at a time, and closes on Escape or outside', async () => {
        const { user } = renderWithUser(<BusinessHome {...fixture} />);

        expect(screen.getByText('88M')).toBeInTheDocument();
        expect(screen.getByText('1,657')).toBeInTheDocument();
        expect(screen.getByText('1 active')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'About Raised' }));
        expect(screen.getByRole('tooltip')).toHaveTextContent(
            'Total capital you’ve raised',
        );

        await user.click(screen.getByRole('button', { name: 'About On-time' }));
        expect(screen.getByRole('tooltip')).toHaveTextContent(
            'Share of your scheduled repayments',
        );

        await user.click(screen.getByRole('button', { name: 'About On-time' }));
        expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'About Investors' }),
        );
        await user.keyboard('{Shift}');
        expect(screen.getByRole('tooltip')).toBeInTheDocument();
        await user.keyboard('{Escape}');
        expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'About Repaid' }));
        await user.click(screen.getByRole('button', { name: 'Close' }));
        expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();
    });

    it('files notes under the design status filters, with drafts resumable', async () => {
        const { user } = renderWithUser(<BusinessHome {...fixture} />);

        expect(
            screen.getAllByRole('heading', { name: 'Cold-Chain Hub' }),
        ).toHaveLength(1);
        expect(screen.getByRole('button', { name: 'Active' })).toHaveAttribute(
            'aria-pressed',
            'true',
        );

        await user.click(screen.getByRole('button', { name: 'Draft' }));
        expect(
            screen.getByRole('heading', { name: 'Equipment Upgrade' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Continue application' }),
        ).toHaveAttribute('href', '/preview/business-apply-business');

        await user.click(screen.getByRole('button', { name: 'Repaying' }));
        expect(
            screen.getByRole('heading', { name: 'Fleet Expansion' }),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Archived' }));
        expect(
            screen.getByRole('heading', { name: 'Grain Storage' }),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Failed' }));
        expect(
            screen.getByText('No notes with this status'),
        ).toBeInTheDocument();
    });

    it('labels funded and failed notes', async () => {
        const { user } = renderWithUser(
            <BusinessHome
                {...fixture}
                notes={[
                    {
                        ...fixture.notes[1],
                        id: 'N-1',
                        title: 'Funded note',
                        status: 'funded',
                    },
                    {
                        ...fixture.notes[1],
                        id: 'N-2',
                        title: 'Failed note',
                        status: 'failed',
                    },
                ]}
            />,
        );

        // A funded note is counted as active, so it sits under the Active tab.
        await user.click(screen.getByRole('button', { name: 'Active' }));
        expect(screen.getAllByText('Funded')).toHaveLength(2);
        expect(screen.queryByText(/N-1/u)).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Failed' }));
        expect(
            screen.getByRole('heading', { name: 'Failed note' }),
        ).toBeInTheDocument();
    });
});

describe('The RDB company code', () => {
    it('drops a recorded RDB prefix once, and keeps a code that is nothing but the prefix', () => {
        expect(bareCompanyCode('RDB-2016-196807')).toBe('2016-196807');
        expect(bareCompanyCode('103847291')).toBe('103847291');
        expect(bareCompanyCode('RDB')).toBe('RDB');
    });
});
