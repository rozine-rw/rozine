import { act, render, screen, waitFor, within } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import AuditorAudit from '@/pages/auditor/audit';
import type {
    AuditProcedureProps,
    AuditorPreviewOutcome,
    SealStage,
} from '@/types/auditor';
import monthlyReject from '../../../resources/fixtures/ui/auditor-audit-monthly-reject.json';
import monthlyRequestChanges from '../../../resources/fixtures/ui/auditor-audit-monthly-request-changes.json';
import monthlySeal from '../../../resources/fixtures/ui/auditor-audit-monthly-seal.json';
import digestStale from '../../../resources/fixtures/ui/auditor-audit-seal-digest-stale.json';
import mfaMissing from '../../../resources/fixtures/ui/auditor-audit-seal-mfa-missing.json';
import notFound from '../../../resources/fixtures/ui/auditor-audit-seal-not-found.json';
import proofExpired from '../../../resources/fixtures/ui/auditor-audit-seal-proof-expired.json';
import stepUp from '../../../resources/fixtures/ui/auditor-audit-seal-step-up.json';
import throttled from '../../../resources/fixtures/ui/auditor-audit-seal-throttled.json';
import unknown from '../../../resources/fixtures/ui/auditor-audit-seal-unknown.json';
import wrongCode from '../../../resources/fixtures/ui/auditor-audit-seal-wrong-code.json';
import seal from '../../../resources/fixtures/ui/auditor-audit-seal.json';
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

const withStage = (
    fixture: { props: unknown },
    change: (stage: SealStage) => SealStage,
): AuditProcedureProps => {
    const base = props(fixture);

    return { ...base, stage: change(base.stage as SealStage) };
};

const NOTE =
    'Six crates of stock were in the delivery van when I counted the store.';

const DIGEST =
    'sha256:9f2c4e71b0a85d3e6c1f47a2d9b83e05c6a1f24d7e98b3c05a6d1e2f47b8c9d0';

const PROOF = { proof: 'stp_new', expires_at: '2026-10-03T17:05:00Z' };

const SEALED = operation({
    code: 'AUDIT_SEALED',
    data: {
        next: { url: '/preview/auditor-audit-sealed', method: 'get' },
        sealed: {
            sealed_at: '2026-10-03T17:00:02Z',
            digest: DIGEST,
            licence: 'ICPAR/P-2026/0481',
            signature_ref: 'sig_1',
            report_id: 'rpt_1',
            key_id: 'key_1',
        },
    },
});

const findings = () => screen.getByRole('dialog', { name: 'Huye Motors' });

const codeField = () => screen.getByLabelText('Six-digit authenticator code');

/** Renders the step-up fixture and focuses its code entry. */
const openCode = async (page: AuditProcedureProps = props(stepUp)) => {
    const view = renderWithUser(<AuditorAudit {...page} />);

    await view.user.click(codeField());

    return view;
};

beforeEach(() => inertia.reset());

afterEach(() => {
    vi.useRealTimers();
});

describe('Seal — findings and preview', () => {
    it('needs a note before the findings can be previewed, and previews exactly what is sealed', async () => {
        const { user } = renderWithUser(<AuditorAudit {...props(seal)} />);
        const dialog = screen.getByRole('dialog', {
            name: 'Huye Motors audit',
        });
        const preview = within(dialog).getByRole('button', {
            name: 'Preview findings',
        });

        expect(within(dialog).getByText('Stock variance')).toBeInTheDocument();
        expect(
            within(dialog).getByText('−4.2% · outside tolerance'),
        ).toHaveClass('text-rz-danger-text');
        expect(within(dialog).getByText('Required')).toBeInTheDocument();
        expect(preview).toBeDisabled();
        expect(
            within(dialog).queryByRole('button', { name: 'Request changes' }),
        ).not.toBeInTheDocument();

        await user.click(within(dialog).getByLabelText('Assessment note'));
        await user.paste(NOTE);
        expect(within(dialog).getByText('Required · done')).toBeInTheDocument();
        expect(
            within(dialog).getByText(`${NOTE.length} / 100`),
        ).toBeInTheDocument();

        await user.click(preview);

        const sheet = findings();

        expect(
            within(sheet).getByText('Factual findings · MVP-AUP-1'),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText(
                'Scope of engagement & agreed-upon procedures',
            ),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText(/^Evidence: ev_huye_storefront, /u),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText('Evidence sealed with this report'),
        ).toBeInTheDocument();

        const evidence = within(sheet).getByRole('list', { name: 'Evidence' });

        expect(within(evidence).getAllByRole('listitem')).toHaveLength(6);
        expect(within(evidence).getAllByText('Web upload')).toHaveLength(1);
        expect(
            within(evidence).queryByText('Attested'),
        ).not.toBeInTheDocument();
        expect(
            within(evidence).getAllByText('Unavailable').length,
        ).toBeGreaterThan(6);
        expect(
            within(sheet).getByText(
                'Procedure MVP-AUP-1 · findings fnd-2026-10-03.2 · evidence ev-2026-10-03.5',
            ),
        ).toBeInTheDocument();
        expect(within(sheet).getByText(DIGEST)).toBeInTheDocument();
        expect(sheet).not.toHaveTextContent(/\bPass\b|\bFail\b/u);

        await user.click(
            within(sheet).getByRole('button', {
                name: 'Confirm with your authenticator',
            }),
        );
        expect(
            within(sheet).getByRole('heading', { name: "Confirm it's you" }),
        ).toBeInTheDocument();

        await user.click(within(sheet).getByRole('button', { name: 'Close' }));
        expect(
            screen.queryByRole('dialog', { name: 'Huye Motors' }),
        ).not.toBeInTheDocument();
    });

    it('names the evidence each attestation and source reports, as the server says', async () => {
        const { user } = renderWithUser(
            <AuditorAudit
                {...withStage(seal, (stage) => ({
                    ...stage,
                    note: { ...stage.note, required: false },
                    findings: stage.findings.map((finding) => ({
                        ...finding,
                        evidence_ids: [],
                    })),
                    evidence: [
                        {
                            ...stage.evidence[0],
                            device_attestation: 'verified',
                        },
                        {
                            ...stage.evidence[1],
                            evidence_id: 'ev_2',
                            device_attestation: 'unverified',
                        },
                    ],
                }))}
            />,
        );

        expect(screen.getByText('Optional')).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Preview findings' }),
        );
        expect(screen.getByText('Attested')).toBeInTheDocument();
        expect(screen.getByText('Not attested')).toBeInTheDocument();
        expect(screen.queryByText(/^Evidence: /u)).not.toBeInTheDocument();
    });

    it('keeps the preview closed while earlier steps are incomplete, and still offers a conflict', async () => {
        const { user } = renderWithUser(
            <AuditorAudit
                {...withStage(seal, (stage) => ({
                    ...stage,
                    note: { ...stage.note, required: false },
                }))}
                can_continue={false}
            />,
        );
        const dialog = screen.getByRole('dialog', {
            name: 'Huye Motors audit',
        });

        expect(
            screen.getByRole('button', { name: 'Preview findings' }),
        ).toBeDisabled();
        await user.click(
            within(dialog).getByRole('button', { name: 'Declare a conflict' }),
        );
        expect(
            screen.getByRole('dialog', {
                name: 'Declare an interest in Huye Motors',
            }),
        ).toBeInTheDocument();
    });

    it('offers no seal, and opens no seal sheet, when the server does not allow sealing', () => {
        render(<AuditorAudit {...props(stepUp)} allowed_actions={[]} />);

        expect(
            screen.queryByRole('button', { name: 'Preview findings' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('dialog', { name: 'Huye Motors' }),
        ).not.toBeInTheDocument();
    });
});

describe('Seal — authenticator step-up', () => {
    it('trades a six-digit code for a proof, then seals with the proof alone', async () => {
        inertia.queue.push(answers(PROOF), answers(SEALED));
        const { user } = renderWithUser(<AuditorAudit {...props(stepUp)} />);
        const sheet = findings();

        expect(
            within(sheet).getByText(
                'Enter the six-digit code from your authenticator app to seal under licence ICPAR/P-2026/0481.',
            ),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByText(
                'This code only confirms that you are the one sealing. It says nothing about the phones or devices used to capture the evidence.',
            ),
        ).toBeInTheDocument();

        const submit = within(sheet).getByRole('button', {
            name: 'Seal & submit to Rozine',
        });

        expect(submit).toBeDisabled();
        expect(codeField()).toHaveAttribute('autocomplete', 'one-time-code');
        await user.click(codeField());
        await user.paste('12a345');
        expect(codeField()).toHaveValue('12345');
        expect(submit).toBeDisabled();
        await user.paste('678');
        expect(codeField()).toHaveValue('123456');

        await user.click(submit);

        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-audit-sealed' },
            ]),
        );
        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-audit-seal-step-up',
            method: 'post',
            body: {
                audit_id: 'fa_huye',
                expected_revision: 7,
                digest: DIGEST,
                identity_context_revision: 3,
                request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u),
                code: '123456',
            },
        });
        expect(inertia.calls[1]).toEqual({
            url: '/preview/auditor-audit-sealed',
            method: 'post',
            body: {
                audit_id: 'fa_huye',
                expected_revision: 7,
                digest: DIGEST,
                procedure_version: 'MVP-AUP-1',
                findings_version: 'fnd-2026-10-03.2',
                evidence_version: 'ev-2026-10-03.5',
                evidence_ids: [
                    'ev_huye_checkin',
                    'ev_huye_storefront',
                    'ev_huye_stock',
                    'ev_huye_till',
                    'ev_huye_extra_1',
                    'ev_huye_ledger_1',
                ],
                note: NOTE,
                step_up: { proof: 'stp_new' },
                identity_context_revision: 3,
                request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u),
            },
        });
        expect(JSON.stringify(inertia.calls[1])).not.toContain('123456');
        expect(
            (inertia.calls[0].body as { request_id: string }).request_id,
        ).not.toBe(
            (inertia.calls[1].body as { request_id: string }).request_id,
        );
        expect(
            screen.queryByRole('dialog', { name: 'Huye Motors' }),
        ).not.toBeInTheDocument();
    });

    it('clears a wrong code and shows the server’s words, or its own', async () => {
        inertia.queue.push(
            invalid({ code: 'The provided code was invalid.' }),
            invalid({}),
        );
        const { user } = await openCode();

        await user.paste('111111');
        await user.click(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        );

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'The provided code was invalid.',
        );
        expect(codeField()).toHaveValue('');
        expect(codeField()).toHaveAttribute('aria-invalid', 'true');

        await user.click(codeField());
        await user.paste('222222');
        await user.click(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        );
        await waitFor(() =>
            expect(screen.getByRole('alert')).toHaveTextContent(
                "That code didn't match. Enter the current code from your authenticator.",
            ),
        );
        expect(inertia.calls).toHaveLength(2);
    });

    it('asks for a new code when the proof expired before the seal', async () => {
        inertia.queue.push(
            answers(PROOF),
            fails(409, { code: 'STEP_UP_EXPIRED' }),
        );
        const { user } = await openCode();

        await user.paste('123456');
        await user.click(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        );

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'Your confirmation expired before the seal went through. Enter a new code.',
        );
        expect(
            within(findings()).queryByText(
                'That confirmation expired before the seal. Enter a new code from your authenticator.',
            ),
        ).not.toBeInTheDocument();
        expect(inertia.reloads).toHaveLength(0);
        expect(codeField()).toBeEnabled();
    });

    it('closes the sheet and asks again when the digest went stale at step-up', async () => {
        inertia.queue.push(fails(409, { code: 'DIGEST_STALE' }));
        const { user } = await openCode();

        await user.paste('123456');
        await user.click(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        );

        await waitFor(() =>
            expect(
                screen.queryByRole('dialog', { name: 'Huye Motors' }),
            ).not.toBeInTheDocument(),
        );
        expect(
            screen.getByText(
                'The evidence changed after your preview. Review the new preview and confirm with a new code.',
            ),
        ).toBeInTheDocument();
        expect(inertia.reloads).toEqual([undefined]);

        await user.click(
            screen.getByRole('button', { name: 'Preview findings' }),
        );
        expect(
            screen.queryByText(/The evidence changed after your preview/u),
        ).not.toBeInTheDocument();
    });

    it('reloads and says so when the seal itself finds the digest stale', async () => {
        inertia.queue.push(
            answers(PROOF),
            fails(409, { code: 'DIGEST_STALE' }),
        );
        const { user } = await openCode();

        await user.paste('123456');
        await user.click(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        );

        await waitFor(() => expect(inertia.reloads).toEqual([undefined]));
        expect(
            screen.queryByRole('dialog', { name: 'Huye Motors' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText(
                'The evidence changed after your preview. Review the new preview and confirm with a new code.',
            ),
        ).toBeInTheDocument();
    });

    it.each([
        [
            fails(403),
            "You can't do this on this assignment any more. Your access changed.",
        ],
        [
            offline(),
            "Rozine couldn't be reached to check your code. Nothing was sealed — enter a new code to try again.",
        ],
    ])(
        'explains a step-up that could not finish: %#',
        async (responder, text) => {
            inertia.queue.push(responder);
            const { user } = await openCode();

            await user.paste('123456');
            await user.click(
                screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
            );

            expect(await screen.findByRole('alert')).toHaveTextContent(text);
            expect(inertia.reloads).toHaveLength(0);
        },
    );

    it('waits out a throttle the server sends with Retry-After', async () => {
        inertia.queue.push(
            fails(429, { code: 'TOO_MANY_ATTEMPTS' }, { 'retry-after': '2' }),
        );
        const { user } = await openCode();

        await user.paste('123456');
        await user.click(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        );

        expect(await within(findings()).findByRole('timer')).toHaveTextContent(
            'Too many attempts. You can enter a new code in 0:02.',
        );
        expect(codeField()).toBeDisabled();
        await waitFor(
            () =>
                expect(
                    within(findings()).queryByRole('timer'),
                ).not.toBeInTheDocument(),
            { timeout: 4000 },
        );
        expect(codeField()).toBeEnabled();
    });

    it('waits without a countdown when the throttle names no wait', async () => {
        inertia.queue.push(fails(429));
        const { user } = await openCode();

        await user.paste('123456');
        await user.click(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        );

        expect(await within(findings()).findByRole('timer')).toHaveTextContent(
            'Too many attempts. Wait a moment, then enter a new code.',
        );
    });

    it('shows a note error the seal returns', async () => {
        inertia.queue.push(
            answers(PROOF),
            invalid({ note: 'Say what you saw.' }),
        );
        const { user } = await openCode();

        await user.paste('123456');
        await user.click(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        );

        expect(
            await screen.findByText('Say what you saw.'),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Assessment note')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
    });

    it('says a seal is recorded and still processing, inside the sheet', async () => {
        inertia.queue.push(
            answers(PROOF),
            answers(
                operation({ status: 'pending', code: 'OPERATION_PENDING' }),
            ),
        );
        const { user } = await openCode();

        await user.paste('123456');
        await user.click(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        );

        expect(
            await within(findings()).findByText('Recorded — still processing'),
        ).toBeInTheDocument();
    });

    it('looks up a seal whose answer was lost, and never resends it blindly', async () => {
        inertia.queue.push(answers(PROOF), fails(503), offline());
        const { user } = await openCode();

        await user.paste('123456');
        await user.click(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        );

        expect(
            await within(findings()).findByText(
                "We couldn't confirm your last action",
            ),
        ).toBeInTheDocument();
        expect(inertia.calls[2].url).toMatch(
            /^\/preview\/auditor-operation-[0-9a-f-]{36}$/u,
        );
        expect(inertia.calls[2].body).toEqual({ command: 'audit.seal' });
        expect(
            screen.getByRole('button', { name: 'Seal & submit to Rozine' }),
        ).toBeDisabled();
    });
});

describe('Seal — previewed states', () => {
    it('opens on the code entry for a step-up', () => {
        render(<AuditorAudit {...props(stepUp)} />);

        expect(
            within(findings()).getByRole('heading', {
                name: "Confirm it's you",
            }),
        ).toBeInTheDocument();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it.each([
        [
            wrongCode,
            "That code didn't match. Enter the current code from your authenticator.",
        ],
        [
            proofExpired,
            'Your confirmation expired before the seal went through. Enter a new code.',
        ],
    ])('says why a new code is needed: %#', (fixture, text) => {
        render(<AuditorAudit {...props(fixture)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(text);
        expect(codeField()).toHaveValue('');
    });

    it('waits without a countdown for a throttle that names no wait', () => {
        render(
            <AuditorAudit
                {...props(stepUp)}
                preview_outcome={{ kind: 'step_up', state: 'throttled' }}
            />,
        );

        expect(within(findings()).getByRole('timer')).toHaveTextContent(
            'Too many attempts. Wait a moment, then enter a new code.',
        );
    });

    it('counts down a throttle from its Retry-After', () => {
        vi.useFakeTimers();
        render(<AuditorAudit {...props(throttled)} />);

        expect(within(findings()).getByRole('timer')).toHaveTextContent('0:42');
        act(() => {
            vi.advanceTimersByTime(1000);
        });
        expect(within(findings()).getByRole('timer')).toHaveTextContent('0:41');
    });

    it('sends a partner without a confirmed authenticator to their security settings', () => {
        render(<AuditorAudit {...props(mfaMissing)} />);
        const sheet = findings();

        expect(
            within(sheet).getByRole('heading', {
                name: 'Turn on two-factor authentication to seal',
            }),
        ).toBeInTheDocument();
        expect(
            within(sheet).getByRole('link', { name: 'Open security settings' }),
        ).toHaveAttribute('href', '/settings/security');
        expect(
            screen.queryByLabelText('Six-digit authenticator code'),
        ).not.toBeInTheDocument();
    });

    it('says the digest went stale and a new preview is needed', () => {
        render(<AuditorAudit {...props(digestStale)} />);

        expect(
            screen.getByText(
                'The evidence changed after your preview. Review the new preview and confirm with a new code.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Preview findings' }),
        ).toBeEnabled();
    });

    it('checks the lookup for a seal awaiting its outcome', async () => {
        inertia.queue.push(answers(SEALED));
        const { user } = renderWithUser(<AuditorAudit {...props(unknown)} />);

        expect(
            screen.getByText("We couldn't confirm your last action"),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Preview findings' }),
        ).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Check again' }));

        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-operation-3c8f1e52-7a4d-4b9e-a0c6-5d2f8e1b7a94',
            method: 'get',
            body: { command: 'audit.seal' },
        });
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-audit-sealed' },
            ]),
        );
    });

    it('resends the identical seal, with the same request, once the lookup found none', async () => {
        inertia.queue.push(answers(SEALED));
        const { user } = renderWithUser(<AuditorAudit {...props(notFound)} />);
        const held = (
            props(notFound).preview_outcome as Extract<
                AuditorPreviewOutcome,
                { kind: 'not_recorded' }
            >
        ).command;

        expect(screen.getByText('Not recorded')).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Try again' }));

        expect(inertia.calls[0]).toEqual({
            url: held.route.url,
            method: 'post',
            body: held.payload,
        });
        expect(JSON.stringify(inertia.calls[0].body)).not.toMatch(/"code"/u);
    });
});

describe('Seal — monthly filings', () => {
    it('requests changes with one of the server’s reasons and a factual explanation', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'AUDIT_CHANGES_REQUESTED',
                    data: {
                        next: { url: '/preview/auditor-jobs', method: 'get' },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(
            <AuditorAudit {...props(monthlyRequestChanges)} />,
        );
        const sheet = screen.getByRole('dialog', { name: 'Request changes' });

        expect(
            within(sheet).getByRole('radio', {
                name: 'Original documents missing',
            }),
        ).toBeChecked();
        expect(within(sheet).getAllByRole('radio')).toHaveLength(6);

        const submit = within(sheet).getByRole('button', {
            name: 'Request changes',
        });

        expect(submit).toBeDisabled();
        await user.click(
            within(sheet).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('The September cash count sheet is unsigned.');
        await user.click(submit);

        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/auditor-jobs',
            body: {
                audit_id: 'mr_greenleaf',
                expected_revision: 3,
                reason_code: 'missing_originals',
                reason: 'The September cash count sheet is unsigned.',
            },
        });

        const result = await screen.findByRole('alertdialog', {
            name: 'Changes requested',
        });

        await user.click(within(result).getByRole('button', { name: 'Done' }));
        expect(inertia.visits).toEqual([{ url: '/preview/auditor-jobs' }]);
    });

    it('rejects a filing that cannot be verified, never as a credit verdict', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'AUDIT_REJECTED',
                    data: {
                        next: { url: '/preview/auditor-jobs', method: 'get' },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(
            <AuditorAudit {...props(monthlyReject)} />,
        );
        const sheet = screen.getByRole('dialog', { name: 'Reject filing' });

        expect(sheet).toHaveTextContent(
            "this concerns the filing, not the business's credit",
        );
        expect(
            within(sheet).getByRole('radio', {
                name: 'Evidence cannot be verified',
            }),
        ).toBeChecked();

        const submit = within(sheet).getByRole('button', {
            name: 'Reject filing',
        });

        expect(submit).toHaveClass('bg-[#c0392b]');
        await user.click(
            within(sheet).getByLabelText('Factual explanation (required)'),
        );
        await user.paste('Bank statements do not match the uploaded ledger.');
        await user.click(submit);

        expect(
            await screen.findByRole('alertdialog', { name: 'Filing rejected' }),
        ).toBeInTheDocument();
    });

    it('opens both reason sheets from the footer and previews the seal', async () => {
        const { user } = renderWithUser(
            <AuditorAudit {...props(monthlySeal)} />,
        );
        const dialog = screen.getByRole('dialog', {
            name: 'GreenLeaf Agro audit',
        });

        expect(within(dialog).getByText('Required · done')).toBeInTheDocument();
        await user.click(
            within(dialog).getByRole('button', { name: 'Request changes' }),
        );
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        await user.click(
            within(dialog).getByRole('button', { name: 'Reject filing' }),
        );
        expect(
            screen.getByRole('dialog', { name: 'Reject filing' }),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        await user.click(
            within(dialog).getByRole('button', { name: 'Preview findings' }),
        );
        expect(
            screen.getByRole('dialog', {
                name: 'GreenLeaf Agro · September 2026',
            }),
        ).toBeInTheDocument();
    });

    it('offers only the monthly commands the server allows', () => {
        render(
            <AuditorAudit
                {...props(monthlySeal)}
                allowed_actions={['audit.seal']}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Request changes' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Reject filing' }),
        ).not.toBeInTheDocument();
    });

    it('ignores a decline sheet a preview names on the procedure', () => {
        render(
            <AuditorAudit
                {...props(monthlySeal)}
                preview_outcome={{
                    kind: 'sheet',
                    sheet: 'decline',
                    reason: null,
                }}
            />,
        );

        expect(screen.queryAllByRole('dialog')).toHaveLength(1);
    });
});
