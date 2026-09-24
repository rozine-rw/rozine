import { render, screen, within } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import BusinessHome from '@/pages/business/home';
import type { BusinessHomeProps, BusinessTodo } from '@/types/business';
import homeFixture from '../../../resources/fixtures/ui/business-home.json';
import { renderWithUser } from '../helpers/render-with-user';

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
    useHttp: () => ({
        errors: {},
        clearErrors: () => undefined,
        transform: () => undefined,
        submit: () => new Promise(() => undefined),
    }),
}));

const fixture = homeFixture.props as BusinessHomeProps;
const link = (url: string) => ({ url, method: 'get' as const });
const money = (amount: number) => ({
    currency: 'RWF' as const,
    amount: String(amount),
});

describe('Business Home', () => {
    it('lays out the design sections from server facts', () => {
        render(<BusinessHome {...fixture} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Home');
        expect(screen.getByText('RWF 12,383,800')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Notifications, 2 unread' }),
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

    it('marks the Home tab current in both the sidebar and the tab bar, without Market', () => {
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
                within(nav).queryByRole('link', { name: 'Market' }),
            ).not.toBeInTheDocument();
        }
    });

    it('reads a pending rating, no live raise, no headroom and a quiet bell', () => {
        render(
            <BusinessHome
                {...fixture}
                rating={null}
                live_raise={null}
                headroom={null}
                unread_notifications={0}
                capital={{ ...fixture.capital, on_time_pct: null }}
            />,
        );

        expect(screen.getByText('Pending audit')).toBeInTheDocument();
        expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
        expect(
            screen.queryByText('Headroom available'),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Notifications' }),
        ).toBeInTheDocument();
        expect(screen.getByText('—', { selector: 'div' })).toBeInTheDocument();
    });

    it('renders every kind of Today item with its call to act', () => {
        const today: BusinessTodo[] = [
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
                'Pay the RWF 0 application fee to publish it to investors.',
            ),
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

        await user.click(screen.getByRole('button', { name: 'Repaying' }));
        expect(screen.getAllByText('Funded')).toHaveLength(2);

        await user.click(screen.getByRole('button', { name: 'Failed' }));
        expect(
            screen.getByRole('heading', { name: 'Failed note' }),
        ).toBeInTheDocument();
    });
});
