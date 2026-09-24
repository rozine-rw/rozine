import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import BusinessReports from '@/pages/business/reports';
import type { BusinessReportsProps, ReportDetail } from '@/types/business';
import annualFixture from '../../../resources/fixtures/ui/business-reports-annual.json';
import mayFixture from '../../../resources/fixtures/ui/business-reports-may.json';
import listFixture from '../../../resources/fixtures/ui/business-reports.json';

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
}));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessReportsProps;

describe('Business Reports', () => {
    it('lists the published reports the Audit Partner filed', () => {
        render(<BusinessReports {...props(listFixture)} />);

        expect(screen.getByTestId('head')).toHaveTextContent('Reports');
        expect(
            screen.getByText(
                'Verified each month by your on-site Audit Partner.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Published' })).toHaveAttribute(
            'aria-selected',
            'true',
        );

        const list = within(
            screen.getByRole('tabpanel', { name: 'Published' }),
        );

        expect(
            list.getByRole('link', {
                name: /^May 2026/,
            }),
        ).toHaveAttribute('href', '/preview/business-reports-may');
        expect(
            list.getByText('Inflow RWF 29M · Watch · audited by CPA J-P M.'),
        ).toBeInTheDocument();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('moves between the in-audit and archived reports', async () => {
        const user = userEvent.setup();

        render(<BusinessReports {...props(listFixture)} />);

        await user.click(screen.getByRole('tab', { name: 'In audit' }));

        expect(
            screen.getByText('With CPA Jean-Paul M. · sealed by 7 Jul'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', {
                name: /^June 2026/,
            }),
        ).toHaveAttribute('href', '/preview/business-audit-prep');

        await user.click(screen.getByRole('tab', { name: 'Archived' }));

        expect(
            screen.getByRole('link', {
                name: /^2025 Annual/,
            }),
        ).toHaveAttribute('href', '/preview/business-reports-annual');
    });

    it('fills gaps in a row and says when a tab is empty', async () => {
        const user = userEvent.setup();
        const page = props(listFixture);

        page.reports.verified[0].inflow = null;
        page.reports.verified[0].health = null;
        page.reports.in_audit[0].seal_by = null;
        page.reports.archived = [];
        render(<BusinessReports {...page} />);

        expect(
            screen.getByText('Inflow — · — · audited by CPA J-P M.'),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('tab', { name: 'In audit' }));

        expect(
            screen.getByText('With CPA Jean-Paul M. · sealed by —'),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('tab', { name: 'Archived' }));

        expect(screen.getByText('No reports here yet.')).toBeInTheDocument();
    });

    it('explains how monthly audits work with the policy the server quotes', async () => {
        const user = userEvent.setup();

        render(<BusinessReports {...props(listFixture)} />);

        const toggle = screen.getByRole('button', {
            name: 'How monthly audits work',
        });

        expect(toggle).toHaveAttribute('aria-expanded', 'false');

        await user.click(toggle);

        expect(toggle).toHaveAttribute('aria-expanded', 'true');

        const guide = within(
            screen.getByRole('region', { name: 'How monthly audits work' }),
        );

        expect(
            guide.getByText('Your Audit Partner opens the file'),
        ).toBeInTheDocument();
        expect(guide.getByText('7th')).toBeInTheDocument();
        expect(
            guide.getByText(/you get 60 minutes to add a recap and co-sign/),
        ).toBeInTheDocument();
    });

    it('opens a published month exactly as investors see it', () => {
        render(<BusinessReports {...props(mayFixture)} />);

        const sheet = within(screen.getByRole('dialog', { name: 'May 2026' }));

        expect(sheet.getByText('Live to your investors')).toBeInTheDocument();
        expect(
            sheet.getByText('Published 6 Jun 2026 · seen by 647 investors'),
        ).toBeInTheDocument();
        expect(sheet.getByText('RWF 33.1M')).toBeInTheDocument();
        expect(sheet.getByText('RWF 26.4M')).toBeInTheDocument();
        expect(sheet.getByText('Healthy')).toBeInTheDocument();
        expect(sheet.getByText('RWF 6,700,000')).toBeInTheDocument();
        expect(sheet.getByText('20.2%')).toBeInTheDocument();
        expect(sheet.getByText('74')).toBeInTheDocument();
        expect(
            sheet.getByText('Audited by CPA Jean-Paul M.'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText(/This is the whole of what your investors can see/),
        ).toBeInTheDocument();
        expect(sheet.getByRole('link', { name: 'Close' })).toHaveAttribute(
            'href',
            '/preview/business-reports',
        );
    });

    it('opens an archived annual filing on the Archived tab', () => {
        render(<BusinessReports {...props(annualFixture)} />);

        expect(screen.getByRole('tab', { name: 'Archived' })).toHaveAttribute(
            'aria-selected',
            'true',
        );

        const sheet = within(
            screen.getByRole('dialog', { name: '2025 Annual' }),
        );

        expect(sheet.getByText('Archived filing')).toBeInTheDocument();
        expect(
            sheet.getByText('Filed 31 Jan 2026 · archived'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('Quarters above the 10% floor'),
        ).toBeInTheDocument();
        expect(sheet.getByText('4 of 4')).toBeInTheDocument();
        expect(
            sheet.getByText(/no longer shown on your investor page/),
        ).toBeInTheDocument();
    });

    it('colours a month by its audited health', () => {
        const page = props(mayFixture);

        (page.report as ReportDetail).health = 'at_risk';

        render(<BusinessReports {...page} />);

        expect(
            within(screen.getByRole('dialog', { name: 'May 2026' })).getByText(
                'At risk',
            ),
        ).toBeInTheDocument();
    });
});
