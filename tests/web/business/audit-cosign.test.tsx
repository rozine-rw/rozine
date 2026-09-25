import type * as InertiaCore from '@inertiajs/core';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useEffect, useState } from 'react';
import type { ComponentProps } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { clearCarriedRefusal } from '@/components/business/apply/carried-refusal';
import { catalogFor } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import {
    formatDate,
    formatDateTime,
    formatDayMonth,
} from '@/lib/rozine/format';
import BusinessAuditCosign from '@/pages/business/audit-cosign';
import type {
    AuditCosignOperationResource,
    BusinessAuditCosignPageProps,
} from '@/types/business-audit';
import flashFixture from '../../../resources/fixtures/ui/business-audit-cosign-flash.json';
import overdueFixture from '../../../resources/fixtures/ui/business-audit-cosign-overdue.json';
import partlyFixture from '../../../resources/fixtures/ui/business-audit-cosign-partly-signed.json';
import publishedFixture from '../../../resources/fixtures/ui/business-audit-cosign-published.json';
import sealFixture from '../../../resources/fixtures/ui/business-audit-cosign-seal-unavailable.json';
import unavailableFixture from '../../../resources/fixtures/ui/business-audit-cosign-unavailable.json';
import pendingFixture from '../../../resources/fixtures/ui/business-audit-cosign.json';

vi.setConfig({ testTimeout: 30_000 });

type Responder = (options: {
    onHttpException?: (response: {
        status: number;
        data: string;
        headers: Record<string, string>;
    }) => void;
}) => Promise<unknown>;

const inertia = vi.hoisted(() => ({
    visit: vi.fn(),
    reload: vi.fn((options?: { onFinish?: () => void }) =>
        options?.onFinish?.(),
    ),
    body: {} as Record<string, unknown>,
    calls: [] as {
        url: string;
        method: string;
        body: Record<string, unknown>;
    }[],
    queue: [] as Responder[],
    errors: {} as Record<string, string>,
}));

vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaCore>()),
    http: { onResponse: () => () => undefined },
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
    router: { visit: inertia.visit, reload: inertia.reload },
    useHttp: () => ({
        errors: inertia.errors,
        clearErrors: () => undefined,
        transform: (callback: () => Record<string, unknown>) => {
            inertia.body = callback();
        },
        submit: (
            route: { url: string; method: string },
            options: Parameters<Responder>[0],
        ) => {
            inertia.calls.push({ ...route, body: inertia.body });
            const respond = inertia.queue.shift();

            return respond ? respond(options) : new Promise(() => undefined);
        },
    }),
}));

type Fixture = { props: unknown };

const props = (fixture: Fixture = pendingFixture) =>
    structuredClone(fixture.props) as BusinessAuditCosignPageProps;

const operation = (
    overrides: Partial<AuditCosignOperationResource> = {},
): AuditCosignOperationResource => ({
    operation_id: 'op-cosign',
    status: 'completed',
    code: 'REPORT_COSIGNATURE_RECORDED',
    data: {
        next: { url: '/preview/business-audit-cosign-next', method: 'get' },
    },
    revision: 8,
    policy_version: 'engineering-2026-09-25.1',
    recorded_at: '2026-09-04T09:12:00+02:00',
    server_time: '2026-09-04T09:12:01+02:00',
    allowed_actions: [],
    field_errors: {},
    ...overrides,
});

const answers =
    (resource: AuditCosignOperationResource): Responder =>
    () =>
        Promise.resolve(resource);

const fails =
    (status: number, body?: unknown): Responder =>
    (options) => {
        options.onHttpException?.({
            status,
            data: body === undefined ? '' : JSON.stringify(body),
            headers: {},
        });

        return Promise.reject(new Error(`HTTP ${status}`));
    };

const offline = (): Responder => () =>
    Promise.reject(new Error('Network error'));

/**
 * Stands in for Inertia's page swap: a visit that does not preserve state hands the page new
 * props under a new key, which remounts it, as the React adapter does.
 */
const inertiaPage = {
    swap: null as null | ((next: BusinessAuditCosignPageProps) => void),
};

function InertiaPage({ initial }: { initial: BusinessAuditCosignPageProps }) {
    const [page, setPage] = useState({ props: initial, key: 0 });

    useEffect(() => {
        inertiaPage.swap = (next) =>
            setPage((current) => ({ props: next, key: current.key + 1 }));
    }, []);

    return <BusinessAuditCosign key={page.key} {...page.props} />;
}

type VisitOptions = { preserveState?: boolean; onFinish?: () => void };

const ACCEPT =
    'I confirm the statements behind this report are on file and its findings are factual.';

const acceptBox = () => screen.getByRole('checkbox', { name: ACCEPT });

const cosignButton = () =>
    screen.getByRole('button', { name: /^Co-sign(ing…| report)$/u });

const signAs = async (
    user: ReturnType<typeof userEvent.setup>,
    note?: string,
) => {
    await user.click(acceptBox());

    if (note !== undefined) {
        await user.type(screen.getByLabelText('Your recap (optional)'), note);
    }

    await user.click(cosignButton());
};

const setup = (page: BusinessAuditCosignPageProps = props()) => ({
    user: userEvent.setup(),
    ...render(<InertiaPage initial={page} />),
});

beforeEach(() => {
    inertia.calls = [];
    inertia.queue = [];
    inertia.body = {};
    inertia.errors = {};
    inertiaPage.swap = null;
});

afterEach(() => clearCarriedRefusal());

describe('Business audit co-sign — reading the sealed report', () => {
    it('shows the monthly report header, seal, note and factual findings, with no rating or figures', () => {
        const page = props();

        setup(page);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Co-sign audit report',
        );
        expect(
            screen.getByRole('heading', { name: 'Co-sign the audit report' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Monthly audit report')).toBeInTheDocument();
        expect(
            screen.getByText('GreenLeaf Agro (synthetic)'),
        ).toBeInTheDocument();
        expect(screen.getByText('August 2026')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Synthetic CPA Jean-Paul M. · licence SYN-ICPAR-0000',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('MVP-AUP-1')).toBeInTheDocument();

        const digest = screen.getByText(`${page.report.digest.slice(0, 12)}…`);

        expect(digest).toHaveAttribute('title', page.report.digest);
        expect(screen.getByText('Seal valid')).toBeInTheDocument();
        expect(
            screen.getByText(
                `Sealed ${formatDateTime(page.report.seal.signed_at!, 'en')}`,
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Verify seal' }),
        ).toHaveAttribute('href', '/preview/business-audit-cosign');
        expect(screen.getByText(page.report.auditor_note)).toBeInTheDocument();

        const findings = within(
            screen.getByRole('list', { name: 'Factual findings' }),
        ).getAllByRole('listitem');

        expect(findings).toHaveLength(3);
        expect(findings[0]).toHaveTextContent(
            'Synthetic finding: bank statement matches the ledger',
        );
        expect(findings[0]).toHaveTextContent('2 evidence items');
        expect(findings[1]).toHaveTextContent('1 evidence item');
        expect(findings[2]).toHaveTextContent('3 evidence items');
        expect(
            screen.getByText(
                'This report is sealed. Nothing on this page changes it.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByText(/rating|health|inflow|outflow|verdict/iu),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-reports',
        );
    });

    it('shows where the signatures stand, marks the current person and the co-sign date', () => {
        const page = props();

        setup(page);

        expect(screen.getByText('0 of 2 signatures')).toBeInTheDocument();

        const signers = within(
            screen.getByRole('list', { name: 'Signatories' }),
        ).getAllByRole('listitem');

        expect(signers[0]).toHaveAttribute('aria-current', 'true');
        expect(signers[0]).toHaveTextContent(
            'Synthetic signatory Aline UwaseYouWaiting',
        );
        expect(signers[1]).not.toHaveAttribute('aria-current');
        expect(signers[1]).toHaveTextContent(
            'Synthetic signatory Eric NshutiWaiting',
        );

        const due = screen.getByText(
            `Co-sign by ${formatDate(page.cosign.due_at!, 'en')}`,
        );

        expect(due).not.toHaveAttribute('data-overdue');
        expect(acceptBox()).not.toBeChecked();
        expect(cosignButton()).toBeDisabled();
    });

    it('marks an overdue monthly co-signature, with the other signature already in', () => {
        const page = props(overdueFixture);

        setup(page);

        expect(
            screen.getByText(
                `Overdue — co-signing was due by ${formatDate(page.cosign.due_at!, 'en')}`,
            ),
        ).toHaveAttribute('data-overdue', 'true');
        expect(screen.getByText('1 of 2 signatures')).toBeInTheDocument();
        expect(
            screen.getByText(
                `Signed · ${formatDayMonth(page.cosign.signers[1].signed_at!, 'en')}`,
            ),
        ).toBeInTheDocument();
        expect(acceptBox()).toBeInTheDocument();
    });

    it('gives a Flash report no month and no deadline', () => {
        setup(props(flashFixture));

        expect(screen.getByText('Flash audit report')).toBeInTheDocument();
        expect(screen.queryByText('Period')).not.toBeInTheDocument();
        expect(
            screen.queryByText(/Co-sign by|Overdue/u),
        ).not.toBeInTheDocument();
        expect(screen.getByText('0 of 1 signatures')).toBeInTheDocument();
        expect(
            within(
                screen.getByRole('list', { name: 'Factual findings' }),
            ).getAllByRole('listitem'),
        ).toHaveLength(1);
        expect(acceptBox()).toBeInTheDocument();
    });

    it('shows an unavailable seal plainly, with nothing to verify', () => {
        setup(props(sealFixture));

        expect(
            screen.getByText("The seal can't be checked right now."),
        ).toBeInTheDocument();
        expect(screen.queryByText('Seal valid')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Verify seal' }),
        ).not.toBeInTheDocument();
    });

    it('shows a valid seal without a time or link when the server sends neither, and an empty findings list', () => {
        const page = props();

        page.report.seal = {
            status: 'valid',
            signed_at: null,
            verification: null,
        };
        page.report.findings = [];
        setup(page);

        expect(screen.getByText('Seal valid')).toBeInTheDocument();
        expect(screen.queryByText(/^Sealed /u)).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Verify seal' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('No findings were recorded.'),
        ).toBeInTheDocument();
    });

    it('reads in French and Kinyarwanda', () => {
        const { unmount } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <BusinessAuditCosign {...props()} />
            </I18nContext>,
        );

        expect(
            screen.getByRole('checkbox', {
                name: 'Je confirme que les relevés sur lesquels repose ce rapport sont bien au dossier et que ses constats sont factuels.',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('Constats factuels')).toBeInTheDocument();
        unmount();

        render(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <BusinessAuditCosign {...props()} />
            </I18nContext>,
        );

        expect(
            screen.getByRole('button', { name: 'Shyiraho umukono' }),
        ).toBeDisabled();
    });
});

describe('Business audit co-sign — states after signing', () => {
    it('waits for the others once the current person has signed', () => {
        const page = props(partlyFixture);

        setup(page);

        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(screen.getByText('1 of 2 signatures')).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            `You co-signed on ${formatDayMonth(page.cosign.signers[0].signed_at!, 'en')}. ` +
                'Waiting for Synthetic signatory Eric Nshuti to co-sign. The report publishes once every required signature is in.',
        );
    });

    it('says every signature is in while publication is still to come', () => {
        const page = props(partlyFixture);

        page.cosign.state = 'signed';
        page.cosign.signed_count = 2;
        page.cosign.signers = [
            { ...page.cosign.signers[0], signed_at: null },
            {
                ...page.cosign.signers[1],
                state: 'signed',
                signed_at: '2026-09-05T15:02:00+02:00',
            },
        ];
        setup(page);

        expect(screen.getByRole('status')).toHaveTextContent(
            'You co-signed this report. Every required signature is in. The report publishes once its publication checks pass.',
        );
        expect(
            within(
                screen.getByRole('list', { name: 'Signatories' }),
            ).getAllByRole('listitem')[0],
        ).toHaveTextContent('Synthetic signatory Aline UwaseYouSigned');
    });

    it('shows a published report with every signature in', () => {
        const page = props(publishedFixture);

        setup(page);

        expect(
            screen.getByText(
                `Published ${formatDate(page.report.published_at!, 'en')}`,
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('2 of 2 signatures')).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Every required signature is in and the report is published.',
        );
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
    });

    it('says plainly when co-signing is not available', () => {
        setup(props(unavailableFixture));

        expect(
            screen.getByText("Co-signing isn't available for this report."),
        ).toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('offers no action when allowed_actions is empty, even with a route', () => {
        const page = props();

        page.allowed_actions = [];
        setup(page);

        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            "You can't co-sign this report.",
        );
    });

    it('offers no action when the server sends no route, and none to a person who is not a signatory', () => {
        const page = props();

        page.actions.cosign = null;
        page.cosign.signers = page.cosign.signers.map((signer) => ({
            ...signer,
            is_you: false,
        }));
        setup(page);

        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            "You can't co-sign this report.",
        );
        expect(
            screen.queryByText('You', { selector: 'span' }),
        ).not.toBeInTheDocument();
    });

    it('keeps the shell to its close link when the server sends no shell navigation', () => {
        const page = props();

        delete page.shell_links;
        setup(page);

        expect(
            screen.queryByRole('link', { name: 'Reports' }),
        ).not.toBeInTheDocument();
        expect(
            screen
                .getAllByRole('link', { name: 'Home' })
                .every(
                    (link) =>
                        link.getAttribute('href') ===
                        '/preview/business-reports',
                ),
        ).toBe(true);
    });
});

describe('Business audit co-sign — the co-signature', () => {
    it('keeps Co-sign disabled until the acceptance is ticked', async () => {
        const { user } = setup();

        expect(cosignButton()).toBeDisabled();
        await user.click(acceptBox());
        expect(acceptBox()).toBeChecked();
        expect(cosignButton()).toBeEnabled();
        await user.click(acceptBox());
        expect(acceptBox()).not.toBeChecked();
        expect(cosignButton()).toBeDisabled();
        expect(inertia.calls).toEqual([]);
    });

    it('sends exactly the contract body: the co-sign revision, the report revision, digest and mandate', async () => {
        const page = props();
        const { user } = setup(page);

        await signAs(user, '  Synthetic recap: outage on 14 August.  ');

        expect(inertia.calls).toEqual([
            {
                url: '/preview/business-audit-cosign-signatures',
                method: 'post',
                body: {
                    request_id: expect.stringMatching(
                        /^[0-9a-f-]{36}$/u,
                    ) as string,
                    identity_context_revision: 4,
                    expected_revision: 7,
                    report_revision: 3,
                    digest: page.report.digest,
                    mandate_version: 2,
                    accepted: true,
                    note: 'Synthetic recap: outage on 14 August.',
                },
            },
        ]);
        expect(page.cosign.revision).not.toBe(page.report.revision);
        expect(cosignButton()).toHaveTextContent('Co-signing…');
        expect(cosignButton()).toHaveAttribute('aria-busy', 'true');
        expect(cosignButton()).toBeDisabled();
    });

    it('limits the recap to 100 characters and counts them', async () => {
        const page = props();

        page.cosign.your_note = 'x'.repeat(120);
        const { user } = setup(page);
        const note = screen.getByLabelText('Your recap (optional)');

        expect(note).toHaveValue('x'.repeat(100));
        expect(screen.getByText('100/100')).toBeInTheDocument();

        await user.clear(note);
        expect(screen.getByText('0/100')).toBeInTheDocument();
        await user.type(note, 'y'.repeat(105));
        expect(note).toHaveValue('y'.repeat(100));
        expect(screen.getByText('100/100')).toBeInTheDocument();

        await user.click(acceptBox());
        await user.click(cosignButton());
        expect(inertia.calls[0].body.note).toBe('y'.repeat(100));
    });

    it('reloads the page for the current counts once one signature is recorded', async () => {
        inertia.queue.push(answers(operation()));
        const { user } = setup();

        await signAs(user);

        await waitFor(() => expect(inertia.reload).toHaveBeenCalledTimes(1));
        expect(inertia.visit).not.toHaveBeenCalled();
    });

    it('follows data.next once the last signature publishes the report', async () => {
        inertia.queue.push(answers(operation({ code: 'REPORT_PUBLISHED' })));
        const { user } = setup();

        await signAs(user);

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith({
                url: '/preview/business-audit-cosign-next',
                method: 'get',
            }),
        );
        expect(inertia.reload).not.toHaveBeenCalled();
    });

    it('reloads when a publishing answer carries no next page', async () => {
        inertia.queue.push(
            answers(operation({ code: 'REPORT_PUBLISHED', data: null })),
        );
        const { user } = setup();

        await signAs(user);

        await waitFor(() => expect(inertia.reload).toHaveBeenCalledTimes(1));
        expect(inertia.visit).not.toHaveBeenCalled();
    });

    it('looks a lost answer up with the same request and follows the recovered receipt', async () => {
        inertia.queue.push(offline(), answers(operation()));
        const { user } = setup();

        await signAs(user);

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith({
                url: '/preview/business-audit-cosign-next',
                method: 'get',
            }),
        );

        const sent = inertia.calls[0].body.request_id as string;

        expect(inertia.calls[1]).toEqual({
            url: `/preview/business-audit-cosign-operation-${sent}`,
            method: 'get',
            body: { command: 'report.cosign' },
        });
        expect(inertia.reload).not.toHaveBeenCalled();
    });

    it('holds an unconfirmed co-signature until the lookup answers', async () => {
        inertia.queue.push(fails(503), fails(503));
        const { user } = setup();

        await signAs(user);

        expect(
            await screen.findByText("We couldn't confirm this yet"),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Nothing will be sent again until the server confirms what happened to your request.',
            ),
        ).toBeInTheDocument();
        expect(cosignButton()).toBeDisabled();

        inertia.queue.push(answers(operation({ code: 'REPORT_PUBLISHED' })));
        await user.click(screen.getByRole('button', { name: 'Check again' }));

        await waitFor(() => expect(inertia.visit).toHaveBeenCalledTimes(1));
        expect(inertia.calls.map((call) => call.method)).toEqual([
            'post',
            'get',
            'get',
        ]);
    });

    it('refreshes the facts when the lookup has no result, then resends the identical co-signature', async () => {
        inertia.queue.push(
            offline(),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
            answers(operation()),
        );
        const { user } = setup();

        await signAs(user, 'Synthetic recap');

        expect(
            await screen.findByText('No result recorded'),
        ).toBeInTheDocument();
        expect(inertia.reload).toHaveBeenCalledTimes(1);

        await user.click(screen.getByRole('button', { name: 'Try again' }));

        await waitFor(() => expect(inertia.reload).toHaveBeenCalledTimes(2));
        expect(inertia.calls).toHaveLength(3);
        expect(inertia.calls[2]).toEqual(inertia.calls[0]);
        expect(inertia.visit).not.toHaveBeenCalled();
    });

    it('reads a stale revision afresh with a remount, carrying its banner, and starts unticked', async () => {
        const page = props();
        const fresh = props(overdueFixture);

        inertia.visit.mockImplementationOnce(
            (_url: unknown, options?: VisitOptions) => {
                if (options?.preserveState === false) {
                    inertiaPage.swap?.(fresh);
                }

                options?.onFinish?.();
            },
        );
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        const { user } = setup(page);

        await signAs(user, 'Synthetic recap');

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith(
                window.location.href,
                expect.objectContaining({
                    preserveState: false,
                    preserveScroll: true,
                    replace: true,
                }),
            ),
        );
        expect(
            await screen.findByText(/^The signatures on this report changed/u),
        ).toHaveTextContent(
            "The signatures on this report changed while you were signing. We've loaded the latest — check it and co-sign again. (VERSION_CONFLICT)",
        );
        expect(screen.getByText('1 of 2 signatures')).toBeInTheDocument();
        expect(acceptBox()).not.toBeChecked();
        expect(screen.getByLabelText('Your recap (optional)')).toHaveValue('');
        expect(cosignButton()).toBeDisabled();
    });

    it.each([
        [
            'DIGEST_STALE',
            "The report you read is no longer the current one. We've loaded it — read it and co-sign again.",
        ],
        [
            'MANDATE_STALE',
            "The company's signing mandate changed. Check who must co-sign now, then co-sign again.",
        ],
        [
            'IDEMPOTENCY_CONFLICT',
            "This request was already used with different details, so it was not sent again. We've loaded the latest.",
        ],
        [
            'REPORT_WINDOW_CLOSED',
            "Your co-signature wasn't recorded. Check the report as it stands now and try again.",
        ],
    ])(
        'reads the page afresh after %s and shows the code as sent',
        async (code, reason) => {
            inertia.visit.mockImplementationOnce(
                (_url: unknown, options?: VisitOptions) => {
                    inertiaPage.swap?.(props());
                    options?.onFinish?.();
                },
            );
            inertia.queue.push(fails(409, { code }));
            const { user } = setup();

            await signAs(user);

            expect(
                await screen.findByText(`${reason} (${code})`),
            ).toBeInTheDocument();
            expect(inertia.visit).toHaveBeenCalledTimes(1);
        },
    );

    it('sends nothing more while a fresh read is still pending', async () => {
        inertia.visit.mockImplementationOnce(() => undefined);
        inertia.queue.push(fails(409, { code: 'VERSION_CONFLICT' }));
        const { user } = setup();

        await signAs(user);

        await waitFor(() => expect(inertia.visit).toHaveBeenCalledTimes(1));
        expect(acceptBox()).toBeChecked();
        expect(cosignButton()).toBeDisabled();
    });

    it.each([
        [403, 'ACTION_FORBIDDEN', "You can't co-sign for this business."],
        [
            403,
            'MANDATE_REQUIRED',
            "Your verified mandate doesn't let you co-sign for this business.",
        ],
        [404, 'NOT_FOUND', 'This report is no longer available to you.'],
        [
            403,
            'BUSINESS_SUSPENDED',
            'Your access has changed. Return to your apps and try again.',
        ],
    ])(
        'shows a %s %s denial as it is, without reading the page afresh',
        async (status, code, reason) => {
            inertia.queue.push(fails(status, { code }));
            const { user } = setup();

            await signAs(user);

            expect(
                await screen.findByText(`${reason} (${code})`),
            ).toBeInTheDocument();
            expect(inertia.visit).not.toHaveBeenCalled();
            expect(inertia.reload).not.toHaveBeenCalled();
            expect(acceptBox()).toBeChecked();
        },
    );

    it('marks field errors inline and shows any other as a banner', () => {
        inertia.errors = {
            accepted: 'Accept the statements and findings to co-sign.',
            note: 'The recap may not be longer than 100 characters.',
            report_revision: 'The report changed.',
        };
        setup();

        expect(
            screen.getByText('Accept the statements and findings to co-sign.'),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Your recap (optional)')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(screen.getByRole('alert')).toHaveTextContent(
            'The report changed.',
        );
    });
});
