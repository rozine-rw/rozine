import { render, screen, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { catalogFor } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import AuditVerifySeal from '@/pages/audit/verify-seal';
import type { AuditVerifySealProps } from '@/types/audit-seal';
import amendedByFixture from '../../../resources/fixtures/ui/audit-verify-seal-amended-by.json';
import amendsFixture from '../../../resources/fixtures/ui/audit-verify-seal-amends.json';
import unavailableFixture from '../../../resources/fixtures/ui/audit-verify-seal-unavailable.json';
import validFixture from '../../../resources/fixtures/ui/audit-verify-seal.json';
import cosignFixture from '../../../resources/fixtures/ui/business-audit-cosign.json';

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => (
        <span data-testid="head">{title}</span>
    ),
}));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AuditVerifySealProps;

describe('Public audit seal verification', () => {
    it('shows a verified seal with the report ID and the full digest, and nothing else', () => {
        const { seal } = props(validFixture);

        render(<AuditVerifySeal {...props(validFixture)} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Verify audit seal',
        );
        expect(screen.getByRole('img', { name: 'Rozine' })).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Audit seal check' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Seal verifiedThis digest matches the sealed report.',
        );
        expect(screen.getByText('SYN-RPT-2026-08')).toBeInTheDocument();

        const digest = screen.getByText(seal.digest);

        expect(seal.digest).toHaveLength(64);
        expect(digest).toHaveClass('font-mono', 'break-all');
        expect(digest).toHaveAttribute('translate', 'no');
        expect(screen.queryByText(/^Amend/u)).not.toBeInTheDocument();
        expect(screen.queryByRole('list')).not.toBeInTheDocument();
        expect(screen.getByRole('main')).toHaveAttribute(
            'data-audience',
            'investor',
        );
    });

    it("says plainly when the seal can't be verified", () => {
        render(<AuditVerifySeal {...props(unavailableFixture)} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            "This seal can't be verified right nowTry again later.",
        );
        expect(screen.queryByText('Seal verified')).not.toBeInTheDocument();
    });

    it('names the report an amendment amends, as plain text', () => {
        render(<AuditVerifySeal {...props(amendsFixture)} />);

        const facts = within(screen.getByRole('list')).getAllByRole('listitem');

        expect(facts).toHaveLength(1);
        expect(facts[0]).toHaveTextContent('Amends report SYN-RPT-2026-08');
        expect(screen.getByText('SYN-RPT-2026-08-A1')).toBeInTheDocument();
        expect(screen.queryByRole('link')).not.toBeInTheDocument();
    });

    it('names the report that amended this one, and both links together', () => {
        const { unmount } = render(
            <AuditVerifySeal {...props(amendedByFixture)} />,
        );

        expect(screen.getByRole('listitem')).toHaveTextContent(
            'Amended by report SYN-RPT-2026-08-A1',
        );
        unmount();

        const both = props(amendedByFixture);

        both.seal.amends_id = 'SYN-RPT-2026-07';
        render(<AuditVerifySeal {...both} />);

        expect(
            screen.getAllByRole('listitem').map((item) => item.textContent),
        ).toEqual([
            'Amends report SYN-RPT-2026-07',
            'Amended by report SYN-RPT-2026-08-A1',
        ]);
    });

    it('shows none of the co-sign facts: no Business, signer, note or finding', () => {
        const cosign = cosignFixture.props;

        render(<AuditVerifySeal {...props(validFixture)} />);

        const text = document.body.textContent ?? '';

        for (const privateFact of [
            cosign.business.name,
            cosign.report.auditor.name,
            cosign.report.auditor_note,
            ...cosign.report.findings.map((finding) => finding.title),
            ...cosign.cosign.signers.map((signer) => signer.name),
        ]) {
            expect(text).not.toContain(privateFact);
        }

        expect(Object.keys(props(validFixture).seal).sort()).toEqual([
            'amended_by',
            'amends_id',
            'digest',
            'report_id',
            'seal_status',
        ]);
    });

    it('reads in French and Kinyarwanda', () => {
        const { unmount } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <AuditVerifySeal {...props(amendsFixture)} />
            </I18nContext>,
        );

        expect(screen.getByText('Sceau vérifié')).toBeInTheDocument();
        expect(
            screen.getByText('Modifie le rapport SYN-RPT-2026-08'),
        ).toBeInTheDocument();
        unmount();

        render(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <AuditVerifySeal {...props(unavailableFixture)} />
            </I18nContext>,
        );

        expect(
            screen.getByText('Iyi kashe ntishobora kugenzurwa ubu'),
        ).toBeInTheDocument();
    });
});
