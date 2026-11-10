import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { StepGuide } from '@/components/auditor/audit/step-guide';
import { VISIT_GUIDE } from '@/lib/audit-visit-guide';
import { catalogFor } from '@/lib/i18n';
import en from '@/lib/i18n/catalogs/en';
import { I18nContext } from '@/lib/i18n/context';
import type { AuditStepKey } from '@/types/auditor';

const guide = () => screen.getByRole('region', { name: 'How to do this step' });

describe('Guided visit step (VIS-R01)', () => {
    it('states Do, Enter, Upload and Done when for the ledger, with the upload rule and a How to', () => {
        render(<StepGuide step="ledger" />);

        const card = guide();
        expect(
            within(card)
                .getAllByRole('term')
                .map((term) => term.textContent),
        ).toEqual(['Do', 'Enter', 'Upload', 'Done when']);
        expect(
            within(card).getByText(
                'Inventory value in whole francs, for example 1250000.',
            ),
        ).toBeInTheDocument();
        expect(within(card).getByRole('note')).toHaveTextContent(
            /Never collect staff names or personal details/u,
        );
        expect(
            within(card).getByText('How to', { selector: 'summary' }),
        ).toBeInTheDocument();
        expect(
            within(card).getByText(/attach the ledger the business keeps/u),
        ).toBeInTheDocument();
    });

    it('leaves out the parts a step does not need, and the upload rule where nothing is collected', () => {
        render(<StepGuide step="statements" />);

        const card = guide();
        expect(
            within(card)
                .getAllByRole('term')
                .map((term) => term.textContent),
        ).toEqual(['Do', 'Done when']);
        expect(within(card).queryByRole('note')).not.toBeInTheDocument();
    });

    it.each(Object.keys(VISIT_GUIDE) as AuditStepKey[])(
        'gives the %s step copy for every part it states',
        (step) => {
            render(<StepGuide step={step} />);

            for (const message of Object.values(VISIT_GUIDE[step])) {
                expect(
                    within(guide()).getByText(en[message]),
                ).toBeInTheDocument();
            }
        },
    );

    it('reads in French and Kinyarwanda', () => {
        const { unmount } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <StepGuide step="photos" />
            </I18nContext>,
        );
        expect(
            screen.getByRole('region', { name: 'Comment faire cette étape' }),
        ).toHaveTextContent('Terminé quand');
        unmount();

        render(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <StepGuide step="photos" />
            </I18nContext>,
        );
        expect(
            screen.getByRole('region', { name: 'Uko iyi ntambwe ikorwa' }),
        ).toHaveTextContent('Birarangira iyo');
    });
});
