import {
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { catalogFor } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import { formatDate } from '@/lib/rozine/format';
import AuditorAudit from '@/pages/auditor/audit';
import type { AuditProcedureProps, SealedStage } from '@/types/auditor';
import sealedMonthly from '../../../resources/fixtures/ui/auditor-audit-sealed-monthly.json';
import autoApprovedFixture from '../../../resources/fixtures/ui/auditor-audit-sealed-n6-auto-approved.json';
import amendedFixture from '../../../resources/fixtures/ui/auditor-audit-sealed-n6-dispute-amended.json';
import amendmentRequiredFixture from '../../../resources/fixtures/ui/auditor-audit-sealed-n6-dispute-amendment-required.json';
import escalatedFixture from '../../../resources/fixtures/ui/auditor-audit-sealed-n6-dispute-escalated.json';
import underReviewFixture from '../../../resources/fixtures/ui/auditor-audit-sealed-n6-dispute-under-review.json';
import upheldFixture from '../../../resources/fixtures/ui/auditor-audit-sealed-n6-dispute-upheld.json';
import sealedPublished from '../../../resources/fixtures/ui/auditor-audit-sealed-published.json';
import { renderWithUser } from '../helpers/render-with-user';
import {
    answers,
    fails,
    inertia,
    invalid,
    offline,
    operation,
} from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AuditProcedureProps;

const stageOf = (page: AuditProcedureProps) => page.stage as SealedStage;

const BUSINESS = 'Kivu Coffee Roasters (synthetic)';

const sheet = () => screen.getByRole('dialog', { name: `${BUSINESS} audit` });

const panel = () => screen.getByRole('region', { name: 'Business dispute' });

const PROOF_TEXT =
    'Synthetic fixture text. Finding F-02 counts refund slip 0142 as a sale; the refund slip and the till roll are attached.';

const ESCALATED = operation({
    code: 'REPORT_DISPUTE_ESCALATED',
    data: {
        next: {
            url: '/preview/auditor-audit-sealed-n6-dispute-escalated',
            method: 'get',
        },
    },
});

beforeEach(() => inertia.reset());

/** The retained proof, each file a plain anchor to the server's download link. */
const expectProofFiles = (page: AuditProcedureProps) => {
    const files = within(
        within(panel()).getByRole('list', { name: 'Proof files' }),
    ).getAllByRole('listitem');

    expect(files).toHaveLength(2);
    expect(files[0]).toHaveTextContent(
        'synthetic-refund-slip-0142.pdfPDF · 242 KBDownload',
    );

    for (const file of stageOf(page).dispute!.proof_files) {
        const download = within(panel()).getByRole('link', {
            name: `Download ${file.name}`,
        });

        expect(download).toHaveAttribute('href', file.download.url);
        expect(download).not.toHaveAttribute('data-inertia-link');
    }
};

describe('Auditor sealed report — the Business dispute', () => {
    it('shows a dispute under review with its time, the proof as sent and the uphold and amend actions', () => {
        const page = props(underReviewFixture);

        render(<AuditorAudit {...page} />);

        expect(sheet()).toHaveTextContent(
            `The report is sealed. ${BUSINESS} disputed it, so its review window is paused and nothing is published while the dispute is open.`,
        );
        expect(within(panel()).getByText('Under review')).toBeInTheDocument();
        expect(
            within(panel()).getByText(
                `Submitted ${formatDate('2026-10-03T20:10:00Z', 'en')} · 22:10`,
            ),
        ).toBeInTheDocument();
        expect(
            within(panel()).getByText(
                'Review the proof first. Then start a linked amendment, or uphold your findings.',
            ),
        ).toBeInTheDocument();
        expect(
            within(panel()).getByText("The business's supporting text"),
        ).toBeInTheDocument();
        expect(within(panel()).getByText(PROOF_TEXT)).toBeInTheDocument();
        expectProofFiles(page);
        expect(
            within(panel()).queryByText(/^Outcome:/u),
        ).not.toBeInTheDocument();
        expect(
            within(panel()).queryByText('Rozine staff note'),
        ).not.toBeInTheDocument();
        expect(
            within(panel()).getByRole('button', { name: 'Uphold findings' }),
        ).toBeEnabled();
        expect(
            screen.getByRole('button', { name: 'Start a linked amendment' }),
        ).toBeEnabled();
        expect(within(sheet()).getByText('Disputed')).toBeInTheDocument();
    });

    it('says Rozine staff are reviewing an escalated dispute and that sealing an amendment alone does not resolve it', () => {
        render(<AuditorAudit {...props(escalatedFixture)} />);

        expect(
            within(panel()).getByText('With Rozine staff'),
        ).toBeInTheDocument();
        expect(
            within(panel()).getByText(
                "Rozine staff are reviewing this dispute. Sealing an amendment won't resolve it unless staff record that an amendment is required.",
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Uphold findings' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Start a linked amendment' }),
        ).toBeEnabled();
    });

    it('shows the amendment staff require, with their note', () => {
        const page = props(amendmentRequiredFixture);

        render(<AuditorAudit {...page} />);

        expect(
            within(panel()).getByText(
                'Rozine staff require an amendment. Once you seal a linked amendment, the business gets a fresh 24-hour window.',
            ),
        ).toBeInTheDocument();
        expect(
            within(panel()).getByText('Outcome: amendment required'),
        ).toBeInTheDocument();
        expect(
            within(panel()).getByText('Rozine staff note'),
        ).toBeInTheDocument();
        expect(
            within(panel()).getByText(stageOf(page).dispute!.resolution_note!),
        ).toBeInTheDocument();
        expectProofFiles(page);
    });

    it('shows a dispute resolved by the amendment, with no guidance and no text it was not sent', () => {
        render(<AuditorAudit {...props(amendedFixture)} />);

        expect(sheet()).toHaveTextContent(
            "You amended this report, so it won't be co-signed or published. The amendment replaces it.",
        );
        expect(within(panel()).getByText('Resolved')).toBeInTheDocument();
        expect(
            within(panel()).getByText('Outcome: amended'),
        ).toBeInTheDocument();
        expect(
            within(panel()).queryByText(/^Review the proof|^Rozine staff/u),
        ).not.toBeInTheDocument();
        expect(
            within(panel()).queryByText("The business's supporting text"),
        ).not.toBeInTheDocument();
    });

    it('says staff published the report over an upheld dispute, without implying a co-signature', () => {
        const page = props(upheldFixture);

        render(<AuditorAudit {...page} />);

        expect(stageOf(page).published_reason).toBe('staff_resolved');
        expect(sheet()).toHaveTextContent(
            `Published by Rozine staff on ${formatDate(stageOf(page).published_at!, 'en')}. The business did not co-sign it.`,
        );
        expect(sheet()).not.toHaveTextContent(/and co-signed/u);
        expect(within(sheet()).getByText('Not co-signed')).toBeInTheDocument();
        expect(
            within(panel()).getByText('Outcome: findings upheld'),
        ).toBeInTheDocument();
        expect(
            within(panel()).getByText(stageOf(page).dispute!.resolution_note!),
        ).toBeInTheDocument();
    });
});

describe('Auditor sealed report — who recorded the dispute note', () => {
    it('labels the note after your uphold as your reason, never as staff', () => {
        const page = props(escalatedFixture);

        render(<AuditorAudit {...page} />);

        expect(stageOf(page).dispute).toMatchObject({
            status: 'escalated',
            outcome: null,
        });
        expect(
            within(panel()).getByText('Your reason for upholding'),
        ).toBeInTheDocument();
        expect(
            within(panel()).getByText(stageOf(page).dispute!.resolution_note!),
        ).toBeInTheDocument();
        expect(
            within(panel()).queryByText('Rozine staff note'),
        ).not.toBeInTheDocument();
    });

    it.each([
        ['amendment required', amendmentRequiredFixture],
        ['upheld and published', upheldFixture],
    ])('labels the note staff recorded with %s as theirs', (_case, fixture) => {
        render(<AuditorAudit {...props(fixture)} />);

        expect(
            within(panel()).getByText('Rozine staff note'),
        ).toBeInTheDocument();
        expect(
            within(panel()).queryByText('Your reason for upholding'),
        ).not.toBeInTheDocument();
    });

    it('labels a note whose author the state does not say as a review note', () => {
        const page = props(amendedFixture);

        stageOf(page).dispute!.resolution_note = 'Synthetic note.';
        render(<AuditorAudit {...page} />);

        expect(within(panel()).getByText('Review note')).toBeInTheDocument();
        expect(
            within(panel()).queryByText(
                /Rozine staff note|Your reason for upholding/u,
            ),
        ).not.toBeInTheDocument();
    });

    it('reads the note labels in French and Kinyarwanda', () => {
        const { unmount } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <AuditorAudit {...props(escalatedFixture)} />
            </I18nContext>,
        );

        expect(screen.getByText('Votre motif de maintien')).toBeInTheDocument();
        unmount();

        render(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <AuditorAudit {...props(escalatedFixture)} />
            </I18nContext>,
        );

        expect(
            screen.getByText('Impamvu yawe yo kugumishaho'),
        ).toBeInTheDocument();
    });
});

describe('Auditor sealed report — publication under N6', () => {
    it('says a report published automatically after the 24-hour window was never co-signed', () => {
        const page = props(autoApprovedFixture);

        render(<AuditorAudit {...page} />);

        expect(stageOf(page)).toMatchObject({
            policy_version: 'monthly-review-2026-09-26',
            published_reason: 'auto_approved',
            cosign: { state: 'pending', signed_at: null },
        });
        expect(sheet()).toHaveTextContent(
            `Published automatically after the 24-hour window, on ${formatDate(stageOf(page).published_at!, 'en')}. The business did not co-sign it.`,
        );
        expect(sheet()).not.toHaveTextContent(/and co-signed|Waiting/u);
        expect(within(sheet()).getByText('Not co-signed')).toBeInTheDocument();
        expect(
            screen.queryByRole('region', { name: 'Business dispute' }),
        ).not.toBeInTheDocument();
    });

    it.each([
        ['a signature', 'signed'],
        ['no reason under the legacy policy', null],
    ] as const)(
        'keeps the co-signed wording for a publication with %s',
        (_case, reason) => {
            const page = props(sealedPublished);

            stageOf(page).published_reason = reason;
            render(<AuditorAudit {...page} />);

            expect(stageOf(page).policy_version).toBe(
                'audit-publication-legacy',
            );
            expect(
                screen.getByRole('dialog', { name: 'Huye Motors audit' }),
            ).toHaveTextContent(
                `Sealed and co-signed; published to holders on ${formatDate(stageOf(page).published_at!, 'en')}.`,
            );
            expect(screen.queryByText('Not co-signed')).not.toBeInTheDocument();
        },
    );

    it('reads automatic and staff publication in French and Kinyarwanda', () => {
        const { unmount } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <AuditorAudit {...props(autoApprovedFixture)} />
            </I18nContext>,
        );

        expect(screen.getByText('Non cosigné')).toBeInTheDocument();
        expect(
            screen.getByText(/^Publié automatiquement après la fenêtre/u),
        ).toBeInTheDocument();
        unmount();

        render(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <AuditorAudit {...props(upheldFixture)} />
            </I18nContext>,
        );

        expect(
            screen.getByText(/^Byatangajwe n'abakozi ba Rozine ku wa/u),
        ).toBeInTheDocument();
    });

    it('shows no dispute panel on a sealed report that is not disputed', () => {
        render(<AuditorAudit {...props(sealedMonthly)} />);

        expect(
            screen.queryByRole('region', { name: 'Business dispute' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Uphold findings' }),
        ).not.toBeInTheDocument();
    });

    it('reads the dispute in French and Kinyarwanda', () => {
        const { unmount } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <AuditorAudit {...props(underReviewFixture)} />
            </I18nContext>,
        );

        expect(
            screen.getByRole('button', { name: 'Maintenir les constats' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText("Contestation de l'entreprise"),
        ).toBeInTheDocument();
        unmount();

        render(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <AuditorAudit {...props(upheldFixture)} />
            </I18nContext>,
        );

        expect(
            screen.getByText('Umwanzuro: ibyagaragajwe byagumishijweho'),
        ).toBeInTheDocument();
    });
});

describe('Auditor sealed report — upholding the findings', () => {
    const uphold = () =>
        screen.queryByRole('button', { name: 'Uphold findings' });

    const upholdSheet = () =>
        screen.getByRole('dialog', { name: 'Uphold findings' });

    it.each([
        ['the command is not allowed', ['audit.amend'], true],
        [
            'the server sends no route',
            ['audit.amend', 'audit.dispute.uphold'],
            false,
        ],
    ] as const)('offers no uphold when %s', (_case, allowed, withRoute) => {
        const page = props(underReviewFixture);

        render(
            <AuditorAudit
                {...page}
                allowed_actions={[...allowed]}
                actions={{
                    ...page.actions,
                    dispute_uphold: withRoute
                        ? page.actions.dispute_uphold
                        : null,
                }}
            />,
        );

        expect(uphold()).not.toBeInTheDocument();
        expect(panel()).toBeInTheDocument();
    });

    it('needs a plain-text reason of up to 1,000 characters and says it goes to staff without publishing', async () => {
        const { user } = renderWithUser(
            <AuditorAudit {...props(underReviewFixture)} />,
        );

        await user.click(uphold()!);

        const dialog = within(upholdSheet());
        const submit = dialog.getByRole('button', {
            name: 'Send to Rozine staff',
        });
        const reason = dialog.getByLabelText('Your reason (required)');

        expect(
            dialog.getByText(
                `Your reason goes to Rozine staff, who review ${BUSINESS}'s dispute. Upholding does not publish the report.`,
            ),
        ).toBeInTheDocument();
        expect(dialog.getByText('0 / 1000')).toBeInTheDocument();
        expect(submit).toBeDisabled();

        await user.type(reason, '   ');
        expect(submit).toBeDisabled();

        fireEvent.change(reason, { target: { value: 'r'.repeat(1050) } });
        expect(reason).toHaveValue('r'.repeat(1000));
        expect(dialog.getByText('1000 / 1000')).toBeInTheDocument();
        expect(submit).toBeEnabled();

        await user.click(dialog.getByRole('button', { name: 'Cancel' }));
        expect(
            screen.queryByRole('dialog', { name: 'Uphold findings' }),
        ).not.toBeInTheDocument();
        expect(inertia.calls).toEqual([]);
    });

    it('sends exactly the uphold body and follows data.next once escalated to staff', async () => {
        inertia.queue.push(answers(ESCALATED));
        const page = props(underReviewFixture);
        const { user } = renderWithUser(<AuditorAudit {...page} />);

        await user.click(uphold()!);
        await user.type(
            within(upholdSheet()).getByLabelText('Your reason (required)'),
            '  The till roll shows the sale on 14 August.  ',
        );
        await user.click(
            within(upholdSheet()).getByRole('button', {
                name: 'Send to Rozine staff',
            }),
        );

        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-audit-sealed-n6-dispute-escalated',
            method: 'post',
            body: {
                expected_revision: 2,
                report_revision: 3,
                digest: stageOf(page).digest,
                reason: 'The till roll shows the sale on 14 August.',
                identity_context_revision: 3,
                request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u),
            },
        });
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-audit-sealed-n6-dispute-escalated' },
            ]),
        );
        expect(
            screen.queryByRole('dialog', { name: 'Uphold findings' }),
        ).not.toBeInTheDocument();
    });

    it('looks a lost uphold up by its command, with the same request', async () => {
        inertia.queue.push(fails(503), answers(ESCALATED));
        const { user } = renderWithUser(
            <AuditorAudit {...props(underReviewFixture)} />,
        );

        await user.click(uphold()!);
        await user.type(
            within(upholdSheet()).getByLabelText('Your reason (required)'),
            'Findings stand.',
        );
        await user.click(
            within(upholdSheet()).getByRole('button', {
                name: 'Send to Rozine staff',
            }),
        );

        await waitFor(() => expect(inertia.calls).toHaveLength(2));
        const sent = inertia.calls[0].body as { request_id: string };

        expect(inertia.calls[1]).toEqual({
            url: `/preview/auditor-operation-${sent.request_id}`,
            method: 'get',
            body: { command: 'audit.dispute.uphold' },
        });
        await waitFor(() => expect(inertia.visits).toHaveLength(1));
    });

    it('holds the uphold while its outcome is unknown, in the sheet', async () => {
        inertia.queue.push(fails(503), offline());
        const { user } = renderWithUser(
            <AuditorAudit {...props(underReviewFixture)} />,
        );

        await user.click(uphold()!);
        await user.type(
            within(upholdSheet()).getByLabelText('Your reason (required)'),
            'Findings stand.',
        );
        await user.click(
            within(upholdSheet()).getByRole('button', {
                name: 'Send to Rozine staff',
            }),
        );

        expect(
            await within(upholdSheet()).findByText(
                "We couldn't confirm your last action",
            ),
        ).toBeInTheDocument();
        expect(
            within(upholdSheet()).getByRole('button', {
                name: 'Send to Rozine staff',
            }),
        ).toBeDisabled();
    });

    it('marks a reason error in the sheet', async () => {
        inertia.queue.push(invalid({ reason: 'Give a factual reason.' }));
        const { user } = renderWithUser(
            <AuditorAudit {...props(underReviewFixture)} />,
        );

        await user.click(uphold()!);
        const reason = within(upholdSheet()).getByLabelText(
            'Your reason (required)',
        );

        await user.type(reason, 'x');
        await user.click(
            within(upholdSheet()).getByRole('button', {
                name: 'Send to Rozine staff',
            }),
        );

        expect(
            await within(upholdSheet()).findByText('Give a factual reason.'),
        ).toBeInTheDocument();
        expect(reason).toHaveAttribute('aria-invalid', 'true');
        expect(reason).toHaveAttribute(
            'aria-describedby',
            'auditor-uphold-reason-error',
        );
    });
});
