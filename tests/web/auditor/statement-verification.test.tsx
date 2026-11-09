import { screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import AuditorFile from '@/pages/auditor/file';
import type { AuditorFileProps, StatementReviewFile } from '@/types/auditor';
import startFixture from '../../../resources/fixtures/ui/auditor-file-start.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, operation } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const REVIEW: StatementReviewFile = {
    assignment_revision: 4,
    evidence_revision: 2,
    verification_revision: 0,
    procedure_version: 'mvp-aup-1',
    checks: ['complete_rail_inventory', 'originals_authentic'],
    documents: [
        {
            id: 'doc_bank',
            filename: 'bank-statement.pdf',
            sha256: 'a'.repeat(64),
            received_on: '2026-10-01',
            open: {
                url: '/auditor/jobs/fa_huye/statements/doc_bank',
                method: 'get',
            },
        },
        {
            id: 'doc_momo',
            filename: 'momo-statement.csv',
            sha256: 'b'.repeat(64),
            received_on: '2026-10-01',
            open: {
                url: '/auditor/jobs/fa_huye/statements/doc_momo',
                method: 'get',
            },
        },
    ],
    transcription: {
        id: '01JBTRANSCRIPTION0000000000',
        sha256: 'c'.repeat(64),
        months: ['2026-08', '2026-09'],
        observations: [
            {
                month: '2026-08',
                operating_inflow: '1200000',
                operating_outflow: '800000',
                owner_draw: '100000',
                debt_service: '50000',
            },
            {
                month: '2026-09',
                operating_inflow: '1300000',
                operating_outflow: '850000',
                owner_draw: '100000',
                debt_service: '50000',
            },
        ],
        balances: [
            {
                account: 'bank-a',
                month: '2026-08',
                opening_balance: '1250',
                closing_balance: '351250',
            },
        ],
    },
    verify: {
        url: '/auditor/jobs/fa_huye/statement-verification',
        method: 'post',
    },
    operation: {
        url: '/auditor/statement-operations/{request_id}',
        method: 'get',
    },
};

const props = (review: StatementReviewFile | null = REVIEW) => {
    const file = structuredClone(
        startFixture.props,
    ) as unknown as AuditorFileProps;

    return {
        ...file,
        application: null,
        allowed_actions: ['statement.verify'],
        review: structuredClone(review),
    } as AuditorFileProps;
};

const sheet = () =>
    screen.getByRole('dialog', { name: 'Huye Motors business file' });

const form = () =>
    within(sheet()).getByRole('form', { name: 'Verify the statements' });

beforeEach(() => inertia.reset());

/** Fills every field the review needs, with one declared debt. */
const fillIn = async (user: ReturnType<typeof renderWithUser>['user']) => {
    const verify = form();
    const references = within(verify).getAllByLabelText(
        'Where you checked it (page, line or note)',
    );

    await user.type(references[0], 'Pages 1 to 4');
    await user.type(references[1], 'Rows 2 to 180');
    await user.click(
        within(verify).getByLabelText(
            'Every account and wallet the business uses is listed',
        ),
    );
    await user.click(
        within(verify).getByLabelText('The statements are genuine originals'),
    );
    await user.type(
        within(verify).getByLabelText(
            'Where the full list of accounts is recorded',
        ),
        'Owner interview',
    );
    await user.click(
        within(verify).getByRole('button', { name: 'Add a debt' }),
    );

    const debt = within(verify).getByRole('group', { name: 'Debt 1' });

    await user.type(
        within(debt).getByLabelText('Lender and loan'),
        'BK asset loan',
    );
    await user.type(
        within(debt).getByLabelText('Amount still owed (RWF)'),
        '900000',
    );
    await user.type(
        within(debt).getByLabelText(/^Paid in August 2026/u),
        '50000',
    );
    await user.type(
        within(debt).getByLabelText(/^Paid in September 2026/u),
        '50000',
    );
    await user.type(
        within(debt).getByLabelText(
            'Scheduled monthly payment from October 2026 (RWF)',
        ),
        '50000',
    );
    await user.selectOptions(
        within(debt).getByLabelText('Last scheduled payment'),
        'December 2026',
    );
    await user.click(within(debt).getByLabelText('momo-statement.csv'));
    await user.click(within(debt).getByLabelText('bank-statement.pdf'));
    /* A source ticked by mistake comes off again. */
    await user.click(within(debt).getByLabelText('momo-statement.csv'));
    await user.type(
        within(verify).getByLabelText('Usual monthly owner draw (RWF)'),
        '100000',
    );
    await user.type(
        within(verify).getByLabelText('Where you saw it'),
        'Monthly transfers to owner',
    );
    await user.type(
        within(verify).getByLabelText('Your findings'),
        'Figures match.{Enter}No gaps.',
    );
};

describe('Auditor statement verification', () => {
    it('waits for the business to save its figures before it can verify', () => {
        renderWithUser(
            <AuditorFile
                {...props({ ...REVIEW, transcription: null, verify: null })}
            />,
        );

        expect(
            within(sheet()).getByText(
                "The business hasn't saved its monthly figures yet. You can verify the statements once it has.",
            ),
        ).toBeInTheDocument();
        expect(
            within(sheet()).queryByRole('form', {
                name: 'Verify the statements',
            }),
        ).not.toBeInTheDocument();
        /* A statement review has no application, yet it is not unavailable. */
        expect(
            within(sheet()).queryByRole('button', { name: 'Start the audit' }),
        ).not.toBeInTheDocument();
    });

    it('shows each statement to open and the saved figures to compare', () => {
        renderWithUser(<AuditorFile {...props()} />);

        const verify = form();

        expect(
            within(verify).getAllByRole('link', { name: 'Open' })[0],
        ).toHaveAttribute('href', '/auditor/jobs/fa_huye/statements/doc_bank');
        expect(
            within(verify).getByRole('rowheader', { name: 'August 2026' }),
        ).toBeInTheDocument();
        expect(within(verify).getByText('1,200,000')).toBeInTheDocument();
        expect(within(verify).getByText('850,000')).toBeInTheDocument();
        expect(
            within(verify).getByText(
                'No debts declared. Add any loan or credit you found; leaving none says the business has none.',
            ),
        ).toBeInTheDocument();
        expect(
            within(verify).getByText(
                'Tick every check and fill in each field to verify. Amounts are whole RWF.',
            ),
        ).toBeInTheDocument();
        expect(
            within(verify).getByRole('button', { name: 'Verify statements' }),
        ).toBeDisabled();
    });

    it('keeps the review closed while two debts share a name or an amount is not whole RWF', async () => {
        const { user } = renderWithUser(<AuditorFile {...props()} />);

        await fillIn(user);

        const verify = form();
        const submit = within(verify).getByRole('button', {
            name: 'Verify statements',
        });

        expect(submit).toBeEnabled();

        await user.click(
            within(verify).getByRole('button', { name: 'Add a debt' }),
        );

        const second = within(verify).getByRole('group', { name: 'Debt 2' });

        await user.type(
            within(second).getByLabelText('Lender and loan'),
            'BK asset loan',
        );
        await user.type(
            within(second).getByLabelText('Amount still owed (RWF)'),
            '1',
        );
        await user.type(
            within(second).getByLabelText(/^Paid in August 2026/u),
            '0',
        );
        await user.type(
            within(second).getByLabelText(/^Paid in September 2026/u),
            '0',
        );
        await user.type(
            within(second).getByLabelText(
                'Scheduled monthly payment from October 2026 (RWF)',
            ),
            '0',
        );
        await user.selectOptions(
            within(second).getByLabelText('Last scheduled payment'),
            'After April 2027',
        );
        await user.click(within(second).getByLabelText('bank-statement.pdf'));
        expect(submit).toBeDisabled();

        await user.click(
            within(second).getByRole('button', { name: 'Remove this debt' }),
        );
        expect(submit).toBeEnabled();

        const owner = within(verify).getByLabelText(
            'Usual monthly owner draw (RWF)',
        );

        await user.clear(owner);
        await user.type(owner, '1,000');
        expect(submit).toBeDisabled();
    });

    it('sends the review pinned to what it was made against, then says the statements are verified', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'STATEMENT_SOURCE_VERIFIED',
                    data: { next: { url: '/auditor/jobs', method: 'get' } },
                }),
            ),
        );
        const { user } = renderWithUser(<AuditorFile {...props()} />);

        await fillIn(user);
        await user.click(
            within(form()).getByRole('button', { name: 'Verify statements' }),
        );

        expect(inertia.calls[0]).toMatchObject({
            url: '/auditor/jobs/fa_huye/statement-verification',
            method: 'post',
            body: {
                expected_revision: 4,
                expected_evidence_revision: 2,
                expected_verification_revision: 0,
                transcription_id: '01JBTRANSCRIPTION0000000000',
                transcription_sha256: 'c'.repeat(64),
                review: {
                    procedure_version: 'mvp-aup-1',
                    checks: {
                        complete_rail_inventory: true,
                        originals_authentic: true,
                    },
                    source_checks: {
                        doc_bank: {
                            sha256: 'a'.repeat(64),
                            reference: 'Pages 1 to 4',
                        },
                        doc_momo: {
                            sha256: 'b'.repeat(64),
                            reference: 'Rows 2 to 180',
                        },
                    },
                    inventory_reference: 'Owner interview',
                    obligations: [
                        {
                            id: 'BK asset loan',
                            principal: '900000',
                            source_ids: ['doc_bank'],
                            /* The statements' months, then the contractual schedule underwriting reads. */
                            service_by_month: {
                                '2026-08': '50000',
                                '2026-09': '50000',
                                '2026-10': '50000',
                                '2026-11': '50000',
                                '2026-12': '50000',
                                '2027-01': '0',
                                '2027-02': '0',
                                '2027-03': '0',
                                '2027-04': '0',
                            },
                        },
                    ],
                    recurring_owner_draw: '100000',
                    owner_draw_reference: 'Monthly transfers to owner',
                    /* The procedure refuses line breaks, so the findings travel on one line. */
                    findings: 'Figures match. No gaps.',
                },
            },
        });
        expect(
            await screen.findByRole('heading', { name: 'Statements verified' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                "Huye Motors's statements and monthly figures are now on record as checked. They count toward its application once every other requirement is met.",
            ),
        ).toBeInTheDocument();
        /* The form gives way to what happened and the way back to Jobs (#511). */
        expect(screen.getByRole('status')).toHaveTextContent(
            'Verification recorded',
        );
        expect(
            screen.queryByRole('form', { name: 'Verify the statements' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Back to Jobs' }),
        ).toHaveAttribute('href', '/auditor/jobs');
    });

    it("shows each account's balances to check and a whole-RWF hint on amount fields (#511)", () => {
        renderWithUser(<AuditorFile {...props()} />);

        const balances = within(form()).getByRole('row', {
            name: /^bank-a August 2026/u,
        });
        expect(balances).toHaveTextContent('1,250');
        expect(balances).toHaveTextContent('351,250');
        expect(
            within(form()).getByLabelText('Usual monthly owner draw (RWF)'),
        ).toHaveAttribute('placeholder', 'Whole RWF, e.g. 150000');
    });

    it('describes a statement review, not a site audit, in the file brief (#511)', () => {
        const file = props();
        (file.file as { brief: string | null }).brief = 'statement_review';
        renderWithUser(<AuditorFile {...file} />);

        expect(
            within(sheet()).getByText('Why this statement review is required'),
        ).toBeInTheDocument();
        expect(
            within(sheet()).getByText('What you must check'),
        ).toBeInTheDocument();
        expect(
            within(sheet()).getByText(
                'Open each statement and note where you checked it.',
            ),
        ).toBeInTheDocument();
    });

    it('explains a review refusal in the form, without refreshing the page', async () => {
        inertia.queue.push(
            fails(409, { code: 'STATEMENT_TRANSCRIPTION_STALE' }),
        );
        const { user } = renderWithUser(<AuditorFile {...props()} />);

        await fillIn(user);
        await user.click(
            within(form()).getByRole('button', { name: 'Verify statements' }),
        );

        expect(await within(form()).findByRole('alert')).toHaveTextContent(
            'The business saved new figures since you opened the file. Reload it and review them.',
        );
        expect(inertia.reloads).toHaveLength(0);
    });

    it('leaves any other refusal to the page', async () => {
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        const { user } = renderWithUser(<AuditorFile {...props()} />);

        await fillIn(user);
        await user.click(
            within(form()).getByRole('button', { name: 'Verify statements' }),
        );

        await waitFor(() => expect(inertia.reloads).toHaveLength(1));
        expect(within(form()).queryByRole('alert')).not.toBeInTheDocument();
    });
    it('carries a debt still being repaid through every scheduled month', async () => {
        inertia.queue.push(
            answers(operation({ code: 'STATEMENT_SOURCE_VERIFIED' })),
        );
        const { user } = renderWithUser(<AuditorFile {...props()} />);

        await fillIn(user);

        const debt = within(form()).getByRole('group', { name: 'Debt 1' });

        expect(
            within(debt).getByRole('option', { name: 'Choose a month' }),
        ).toBeDisabled();
        await user.selectOptions(
            within(debt).getByLabelText('Last scheduled payment'),
            'After April 2027',
        );
        await user.click(
            within(form()).getByRole('button', { name: 'Verify statements' }),
        );

        const body = inertia.calls[0].body as {
            review: {
                obligations: { service_by_month: Record<string, string> }[];
            };
        };

        expect(
            Object.values(body.review.obligations[0].service_by_month),
        ).toEqual(Array(9).fill('50000'));
    });

    it('lets a stepped or final balloon payment differ from the level payment', async () => {
        inertia.queue.push(
            answers(operation({ code: 'STATEMENT_SOURCE_VERIFIED' })),
        );
        const { user } = renderWithUser(<AuditorFile {...props()} />);

        await fillIn(user);

        const debt = within(form()).getByRole('group', { name: 'Debt 1' });
        const submit = within(form()).getByRole('button', {
            name: 'Verify statements',
        });
        const november = within(debt).getByLabelText(
            'Due in November 2026 (RWF)',
        );
        const december = within(debt).getByLabelText(
            'Due in December 2026 (RWF)',
        );

        expect(november).toHaveValue('50000');
        expect(
            within(debt).getByLabelText('Due in January 2027 (RWF)'),
        ).toHaveValue('0');

        await user.clear(december);
        expect(submit).toBeDisabled();
        await user.type(december, '500000');
        await user.clear(november);
        await user.type(november, '75000');
        expect(submit).toBeEnabled();
        await user.click(submit);

        const body = inertia.calls[0].body as {
            review: {
                obligations: { service_by_month: Record<string, string> }[];
            };
        };

        expect(body.review.obligations[0].service_by_month).toEqual({
            '2026-08': '50000',
            '2026-09': '50000',
            '2026-10': '50000',
            '2026-11': '75000',
            '2026-12': '500000',
            '2027-01': '0',
            '2027-02': '0',
            '2027-03': '0',
            '2027-04': '0',
        });
    });

    it('starts the review afresh when the evidence it was made against changes', async () => {
        const { user, rerender } = renderWithUser(<AuditorFile {...props()} />);

        await fillIn(user);
        expect(
            within(form()).getByRole('button', { name: 'Verify statements' }),
        ).toBeEnabled();

        const changed = structuredClone(REVIEW);
        changed.evidence_revision = 3;
        changed.documents.push({
            id: 'doc_new',
            filename: 'new-statement.pdf',
            sha256: 'd'.repeat(64),
            received_on: '2026-10-02',
            open: {
                url: '/auditor/jobs/fa_huye/statements/doc_new',
                method: 'get',
            },
        });
        rerender(<AuditorFile {...props(changed)} />);

        const verify = form();

        expect(
            within(verify).getAllByLabelText(
                'Where you checked it (page, line or note)',
            ),
        ).toHaveLength(3);
        expect(
            within(verify).getAllByLabelText(
                'Where you checked it (page, line or note)',
            )[0],
        ).toHaveValue('');
        expect(
            within(verify).getByLabelText(
                'The statements are genuine originals',
            ),
        ).not.toBeChecked();
        expect(
            within(verify).getByRole('button', { name: 'Verify statements' }),
        ).toBeDisabled();
    });
});
