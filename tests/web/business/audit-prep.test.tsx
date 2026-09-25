import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import BusinessAuditPrep from '@/pages/business/audit-prep';
import type { BusinessAuditPrepProps } from '@/types/business';
import firstFixture from '../../../resources/fixtures/ui/business-audit-prep-first.json';
import reassignedFixture from '../../../resources/fixtures/ui/business-audit-prep-reassigned.json';
import prepFixture from '../../../resources/fixtures/ui/business-audit-prep.json';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
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
    structuredClone(fixture.props) as BusinessAuditPrepProps;

const prep = () =>
    screen.getByRole('dialog', { name: 'Get ready for your audit' });

describe('Get ready for your audit', () => {
    it('counts down the open audit window and when it must be sealed', () => {
        render(<BusinessAuditPrep {...props(prepFixture)} />);

        const sheet = within(prep());

        expect(sheet.getByText('Audit window open')).toBeInTheDocument();
        expect(sheet.getByText('September 2026 audit')).toBeInTheDocument();
        expect(sheet.getByText('7')).toBeInTheDocument();
        expect(sheet.getByText('Days left')).toBeInTheDocument();
        expect(
            sheet.getByText(
                'Your CPA seals the September 2026 report by 7 Oct. You cannot start or edit it.',
            ),
        ).toBeInTheDocument();
        expect(sheet.queryByRole('status')).not.toBeInTheDocument();
        expect(
            sheet.getByText(
                'You add a recap and co-sign by 7 Oct, or dispute with counter-proof.',
            ),
        ).toBeInTheDocument();
        expect(sheet.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-home',
        );
    });

    it("carries #99 C5's notification, receipts and on-site visit lines, and no upload wording", () => {
        render(<BusinessAuditPrep {...props(prepFixture)} />);

        const sheet = within(prep());

        expect(
            sheet.getByText(
                'Notification received: Please prepare all bank statements, Mobile Money logs, and physical receipt books for your upcoming CPA visit.',
            ),
        ).toBeInTheDocument();
        expect(
            sheet.getByText(
                "Ensure all physical till receipts and digital transactions are reconciled for the CPA's on-site review.",
            ),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('Till receipts and transactions reconciled'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText(
                'Your assigned CPA will visit your premises to review records, reconcile cash flows, and generate the monthly audit report.',
            ),
        ).toBeInTheDocument();
        expect(
            sheet.queryByText(/uploaded|linked feeds/u),
        ).not.toBeInTheDocument();
    });

    it('lets the business tick off what it has ready', async () => {
        const user = userEvent.setup();

        render(<BusinessAuditPrep {...props(prepFixture)} />);

        const stock = within(prep()).getByRole('checkbox', {
            name: /Stock counted and ledgers current/,
        });

        expect(stock).not.toBeChecked();

        await user.click(stock);

        expect(stock).toBeChecked();

        await user.click(stock);

        expect(stock).not.toBeChecked();
    });

    it('marks the first audit before its window opens', () => {
        render(<BusinessAuditPrep {...props(firstFixture)} />);

        expect(
            within(prep()).getByText('Your first audit'),
        ).toBeInTheDocument();
    });

    it('names the next audit and a new Audit Partner on the file', () => {
        const page = props(reassignedFixture);

        page.audit.window_open = false;
        render(<BusinessAuditPrep {...page} />);

        const sheet = within(prep());

        expect(sheet.getByText('Next audit')).toBeInTheDocument();
        expect(sheet.getByText('Day left')).toBeInTheDocument();
        expect(sheet.getByRole('status')).toHaveTextContent(
            'Your file moved from CPA Jean-Paul M. to CPA Diane U., and your history moved with it.',
        );
    });
});
