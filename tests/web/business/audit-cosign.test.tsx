import type * as InertiaCore from '@inertiajs/core';
import {
    act,
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
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
import n6AutoApprovedFixture from '../../../resources/fixtures/ui/business-audit-cosign-n6-auto-approved.json';
import n6AmendedFixture from '../../../resources/fixtures/ui/business-audit-cosign-n6-dispute-amended.json';
import n6AmendmentRequiredFixture from '../../../resources/fixtures/ui/business-audit-cosign-n6-dispute-amendment-required.json';
import n6EscalatedFixture from '../../../resources/fixtures/ui/business-audit-cosign-n6-dispute-escalated.json';
import n6UnderReviewFixture from '../../../resources/fixtures/ui/business-audit-cosign-n6-dispute-under-review.json';
import n6UpheldFixture from '../../../resources/fixtures/ui/business-audit-cosign-n6-dispute-upheld.json';
import n6OpenFixture from '../../../resources/fixtures/ui/business-audit-cosign-n6-open.json';
import n6SignedFixture from '../../../resources/fixtures/ui/business-audit-cosign-n6-signed.json';
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
        /* Marked, so a test can tell an Inertia visit from a plain download anchor. */
        <a href={href.url} data-inertia-link="" {...props}>
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

const ACCEPT = 'I have reviewed the audit findings and co-sign this report.';

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
    it('leaves out the Audit Partner note section when the report was sealed with no note', () => {
        const page = props();

        setup({ ...page, report: { ...page.report, auditor_note: '  ' } });

        expect(
            screen.queryByRole('heading', { name: "Audit Partner's note" }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Factual findings' }),
        ).toBeInTheDocument();
    });

    it('shows the monthly report header, seal, note and factual findings, with no rating or figures', () => {
        const page = props();

        setup(page);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Co-sign audit report',
        );
        expect(
            screen.getByRole('heading', { name: 'Co-sign the audit report' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Your CPA sealed this report after the on-site audit. Read the factual findings before you co-sign.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByText(/uploaded|on file|24 hours|automatically/u),
        ).not.toBeInTheDocument();
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
        ).toHaveAttribute('href', '/preview/audit-verify-seal');
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
                name: "J'ai examiné les constats d'audit et je cosigne ce rapport.",
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
            screen.getByText(
                "This report isn't open for co-signing right now.",
            ),
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
            body: { identity_context_revision: 4, command: 'report.cosign' },
        });
        expect(inertia.reload).not.toHaveBeenCalled();
    });

    it('recovers a lost signature that published the report, without a refusal or a second Co-sign', async () => {
        const published = props(publishedFixture);

        inertia.visit.mockImplementationOnce(() =>
            inertiaPage.swap?.(published),
        );
        inertia.queue.push(
            offline(),
            answers(operation({ code: 'REPORT_PUBLISHED' })),
        );
        const { user } = setup();

        await signAs(user);

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith({
                url: '/preview/business-audit-cosign-next',
                method: 'get',
            }),
        );

        const lookup = inertia.calls[1];

        expect(lookup.method).toBe('get');
        expect(lookup.body).toEqual({
            identity_context_revision: 4,
            command: 'report.cosign',
        });
        expect(inertia.calls).toHaveLength(2);
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        expect(
            await screen.findByText(
                'Every required signature is in and the report is published.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /Co-sign/u }),
        ).not.toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
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
            await screen.findByText(/^This report's signatures changed/u),
        ).toHaveTextContent(
            "This report's signatures changed while you were working on it. We've loaded the latest — check it and try again. (VERSION_CONFLICT)",
        );
        expect(screen.getByText('1 of 2 signatures')).toBeInTheDocument();
        expect(acceptBox()).not.toBeChecked();
        expect(screen.getByLabelText('Your recap (optional)')).toHaveValue('');
        expect(cosignButton()).toBeDisabled();
    });

    it.each([
        [
            'DIGEST_STALE',
            "The report you read is no longer the current one. We've loaded it — read it and try again.",
        ],
        [
            'MANDATE_STALE',
            "The company's signing mandate changed. Check who can sign now, then try again.",
        ],
        [
            'IDEMPOTENCY_CONFLICT',
            "This request was already used with different details, so it was not sent again. We've loaded the latest.",
        ],
        [
            'REPORT_WINDOW_CLOSED',
            "This wasn't recorded. Check the report as it stands now and try again.",
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

    it('reads an amended report afresh as unavailable, carrying the amendment banner', async () => {
        inertia.visit.mockImplementationOnce(
            (_url: unknown, options?: VisitOptions) => {
                if (options?.preserveState === false) {
                    inertiaPage.swap?.(props(unavailableFixture));
                }

                options?.onFinish?.();
            },
        );
        inertia.queue.push(fails(409, { code: 'AUDIT_REPORT_AMENDED' }));
        const { user } = setup();

        await signAs(user);

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith(
                window.location.href,
                expect.objectContaining({ preserveState: false }),
            ),
        );
        expect(
            await screen.findByText(
                "The auditor has amended this report, so it can no longer be co-signed. The amended report will come to you for sign-off once it's sealed. (AUDIT_REPORT_AMENDED)",
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                "This report isn't open for co-signing right now.",
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(inertia.reload).not.toHaveBeenCalled();
    });

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
        [403, 'ACTION_FORBIDDEN', "You can't do this for this business."],
        [
            403,
            'MANDATE_REQUIRED',
            "Your verified mandate doesn't let you sign for this business.",
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

describe('Business audit co-sign — N6 review window copy', () => {
    it('shows the 24-hour wording, the arrival, the close and a countdown under the N6 policy', () => {
        vi.useFakeTimers({ toFake: ['Date', 'setInterval', 'clearInterval'] });
        vi.setSystemTime(new Date('2026-09-03T08:00:00Z'));

        try {
            const page = props(n6OpenFixture);

            render(<InertiaPage initial={page} />);

            const reviewWindow = screen.getByRole('note', {
                name: '24-hour review window',
            });

            expect(reviewWindow).toHaveTextContent(
                '24-hour review window' +
                    '06:45:00 left' +
                    'Once the audit report is sealed in your app, you have 24 hours to sign off or submit a dispute with supporting proof.' +
                    `Arrived in your app ${formatDateTime(page.cosign.delivered_at!, 'en')}` +
                    `Sign off or dispute by ${formatDateTime(page.cosign.due_at!, 'en')}` +
                    'Unsigned reports are automatically approved at the end of the window.',
            );

            act(() => {
                vi.advanceTimersByTime(1000);
            });

            expect(reviewWindow).toHaveTextContent('06:44:59 left');
            expect(
                screen.queryByText(/Co-sign by|Overdue/u),
            ).not.toBeInTheDocument();
            expect(screen.getByText('0 of 1 signatures')).toBeInTheDocument();
            expect(acceptBox()).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Submit a dispute' }),
            ).toBeEnabled();
        } finally {
            vi.useRealTimers();
        }
    });

    it('says the window has ended, never overdue, once due_at has passed before the server publishes', () => {
        const page = props(n6OpenFixture);

        page.server_time = '2026-09-03T17:00:00+02:00';
        page.cosign.overdue = true;
        page.cosign.delivered_at = null;
        setup(page);

        const reviewWindow = screen.getByRole('note', {
            name: '24-hour review window',
        });

        expect(reviewWindow).toHaveTextContent('The 24-hour window has ended.');
        expect(reviewWindow).not.toHaveTextContent(/left|Arrived/u);
        expect(screen.queryByText(/Overdue/u)).not.toBeInTheDocument();
    });

    it('keeps the by-the-7th wording for a report under any other policy, and Flash as it was', () => {
        const legacy = props(n6OpenFixture);

        legacy.cosign.policy_version = 'monthly-cosign-2026-06';
        const first = setup(legacy);

        expect(
            screen.getByText(
                `Co-sign by ${formatDate(legacy.cosign.due_at!, 'en')}`,
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByText(/24 hours|automatically/u),
        ).not.toBeInTheDocument();
        first.unmount();

        const second = setup(props(pendingFixture));

        expect(
            screen.queryByRole('note', { name: '24-hour review window' }),
        ).not.toBeInTheDocument();
        expect(screen.getByText(/^Co-sign by /u)).toBeInTheDocument();
        second.unmount();

        setup(props(flashFixture));
        expect(
            screen.queryByText(/Co-sign by|24 hours|Overdue/u),
        ).not.toBeInTheDocument();
    });

    it('reads the window in French and Kinyarwanda', () => {
        const { unmount } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <BusinessAuditCosign {...props(n6OpenFixture)} />
            </I18nContext>,
        );

        expect(
            screen.getByText(
                "Une fois le rapport d'audit scellé dans votre application, vous avez 24 heures pour l'approuver ou soumettre une contestation avec des justificatifs.",
            ),
        ).toBeInTheDocument();
        unmount();

        render(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <BusinessAuditCosign {...props(n6OpenFixture)} />
            </I18nContext>,
        );

        expect(
            screen.getByText(
                'Raporo zitasinywe zemezwa mu buryo bwikora iyo igihe kirangiye.',
            ),
        ).toBeInTheDocument();
    });
});

describe('Business audit co-sign — N6 publication', () => {
    it('says a report published automatically after the window carries no signature', () => {
        setup(props(n6AutoApprovedFixture));

        expect(
            screen.getByText(
                'Published automatically after the 24-hour window',
            ),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Published automatically after the 24-hour window: no one signed off or disputed it in time. No signature was recorded for it.',
        );
        expect(screen.getByText('0 of 1 signatures')).toBeInTheDocument();
        expect(
            screen.queryByRole('note', { name: '24-hour review window' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('shows a signed publication with its date', () => {
        const page = props(n6SignedFixture);

        setup(page);

        expect(
            screen.getByText(
                `Published ${formatDate(page.report.published_at!, 'en')}`,
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('1 of 1 signatures')).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Every required signature is in and the report is published.',
        );
    });

    it('reads a publication without a reason as signed', () => {
        const page = props(publishedFixture);

        page.cosign.published_reason = null;
        setup(page);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Every required signature is in and the report is published.',
        );
    });

    it('says staff published a report, with no signature implied', () => {
        const page = props(n6UpheldFixture);

        page.cosign.dispute = null;
        setup(page);

        expect(
            screen.getByText(
                `Published by Rozine staff ${formatDate(page.report.published_at!, 'en')}`,
            ),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Rozine staff resolved the dispute and published the report. No signature was recorded for it.',
        );
    });
});

describe('Business audit co-sign — N6 dispute states', () => {
    const proofText =
        'Synthetic fixture text. Finding SYN-F-02 counts refund slip 0142 as a sale; the refund slip and the 14 August till roll are attached.';

    /** The submitted proof shows in every state, each file a plain download of the server's link. */
    const expectProof = (
        page: BusinessAuditCosignPageProps,
        { text = true }: { text?: boolean } = {},
    ) => {
        const dispute = page.cosign.dispute!;

        expect(
            screen.getByRole('heading', { name: 'Your dispute' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                `Submitted ${formatDateTime(dispute.submitted_at, 'en')}`,
            ),
        ).toBeInTheDocument();

        if (text) {
            expect(screen.getByText(proofText)).toBeInTheDocument();
        } else {
            expect(
                screen.queryByText('Your supporting text'),
            ).not.toBeInTheDocument();
        }

        const files = within(
            screen.getByRole('list', { name: 'Your proof files' }),
        ).getAllByRole('listitem');

        expect(files).toHaveLength(2);
        expect(files[0]).toHaveTextContent(
            'synthetic-refund-slip-0142.pdfPDF · 242 KBDownload',
        );
        expect(files[1]).toHaveTextContent(
            'synthetic-till-roll-14-aug.jpgJPEG · 1.8 MBDownload',
        );

        for (const file of dispute.proof_files) {
            const download = screen.getByRole('link', {
                name: `Download ${file.name}`,
            });

            expect(download).toHaveAttribute('href', file.download.url);
            expect(download).not.toHaveAttribute('data-inertia-link');
        }
    };

    /** Nothing can be signed or disputed, and no deadline or overdue shows. */
    const expectNoActions = () => {
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('note', { name: '24-hour review window' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText(/Co-sign by|Overdue|overdue/u),
        ).not.toBeInTheDocument();
    };

    it('says a dispute under review pauses the timer, shows the proof and offers nothing, even when actions are sent', () => {
        const page = props(n6UnderReviewFixture);

        page.cosign.overdue = true;
        page.allowed_actions = ['report.cosign', 'report.dispute'];
        page.actions = {
            cosign: { url: '/cosign', method: 'post' },
            dispute: { url: '/dispute', method: 'post' },
        };
        setup(page);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Dispute under review' +
                'The timer is paused. The CPA is reviewing your proof.',
        );
        expectProof(page);
        expectNoActions();
    });

    it('says Rozine staff are reviewing an escalated dispute', () => {
        const page = props(n6EscalatedFixture);

        setup(page);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Rozine staff are reviewing' +
                'Rozine staff are reviewing your dispute. The timer stays paused and the report is not published meanwhile.',
        );
        expect(screen.getByText(proofText)).toBeInTheDocument();
        expect(
            screen.queryByRole('list', { name: 'Your proof files' }),
        ).not.toBeInTheDocument();
        expectNoActions();
    });

    it('says the auditor must amend the report when staff require an amendment', () => {
        const page = props(n6AmendmentRequiredFixture);

        setup(page);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Amendment required' +
                "The auditor must amend the report; you'll get a fresh 24-hour window. The timer stays paused until then.",
        );
        expect(screen.getByText('Rozine staff note')).toBeInTheDocument();
        expect(
            screen.getByText(page.cosign.dispute!.resolution_note!),
        ).toBeInTheDocument();
        expectProof(page);
        expectNoActions();
    });

    it('links the amendment of a resolved, amended dispute', () => {
        const page = props(n6AmendedFixture);

        setup(page);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Report amended' +
                'The auditor amended the report. The amended report has its own 24-hour window.',
        );
        expect(
            screen.getByRole('link', {
                name: 'Open the amended report (SYN-RPT-2026-08-N6-A1)',
            }),
        ).toHaveAttribute('href', '/preview/business-audit-cosign-n6-open');
        expectProof(page, { text: false });
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
    });

    it('offers no amendment link while the server sends none', () => {
        const page = props(n6AmendedFixture);

        page.cosign.dispute!.amendment = null;
        setup(page);

        expect(
            screen.queryByRole('link', { name: /Open the amended report/u }),
        ).not.toBeInTheDocument();
    });

    it('says staff upheld the findings and published the report, with their note', () => {
        const page = props(n6UpheldFixture);

        setup(page);

        expect(
            screen.getByText(
                `Published by Rozine staff ${formatDate(page.report.published_at!, 'en')}`,
            ),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Published by Rozine staff' +
                'Rozine staff reviewed your dispute, kept the findings and published the report. No signature was recorded for it.',
        );
        expect(
            screen.getByText(page.cosign.dispute!.resolution_note!),
        ).toBeInTheDocument();
        expect(screen.getByText('0 of 1 signatures')).toBeInTheDocument();
        expectProof(page);
        expectNoActions();
    });

    it('reads a resolved dispute without an outcome as closed', () => {
        const page = props(n6AmendedFixture);

        page.cosign.dispute!.outcome = null;
        setup(page);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Dispute closed' + 'This dispute is closed.',
        );
    });

    it('reads an escalated dispute with an unexpected outcome as with staff', () => {
        const page = props(n6EscalatedFixture);

        page.cosign.dispute!.outcome = 'upheld';
        setup(page);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Rozine staff are reviewing',
        );
    });

    it('reads the dispute states in French and Kinyarwanda', () => {
        const { unmount } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <BusinessAuditCosign {...props(n6UnderReviewFixture)} />
            </I18nContext>,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            "Contestation en cours d'examen" +
                'Le délai est suspendu. Le CPA examine vos justificatifs.',
        );
        expect(
            screen.getByRole('link', {
                name: 'Télécharger synthetic-refund-slip-0142.pdf',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('PDF · 242 Ko')).toBeInTheDocument();
        unmount();

        render(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <BusinessAuditCosign {...props(n6AmendmentRequiredFixture)} />
            </I18nContext>,
        );

        expect(screen.getByText('Hakenewe ivugurura')).toBeInTheDocument();
    });
});

describe('Business audit co-sign — N6 dispute sheet', () => {
    const disputeButton = () =>
        screen.queryByRole('button', { name: 'Submit a dispute' });

    const openSheet = async (user: ReturnType<typeof userEvent.setup>) => {
        await user.click(disputeButton()!);
    };

    const fileInput = () =>
        screen.getByLabelText('Proof files', {
            selector: 'input',
        }) as HTMLInputElement;

    /** Hands files to the picker as a browser would, whatever `accept` says. */
    const choose = (input: HTMLInputElement, files: File[]) =>
        fireEvent.change(input, { target: { files } });

    const pdf = (name = 'refund-slip.pdf', size = 3) =>
        new File(['x'.repeat(size)], name, { type: 'application/pdf' });

    it('offers no dispute unless both the action and its route are sent', () => {
        const first = setup();

        expect(disputeButton()).not.toBeInTheDocument();
        first.unmount();

        const noRoute = props(n6OpenFixture);

        noRoute.actions.dispute = null;
        const second = setup(noRoute);

        expect(disputeButton()).not.toBeInTheDocument();
        second.unmount();

        const notAllowed = props(n6OpenFixture);

        notAllowed.allowed_actions = ['report.cosign'];
        setup(notAllowed);

        expect(disputeButton()).not.toBeInTheDocument();
        expect(acceptBox()).toBeInTheDocument();
    });

    it('offers a dispute beside the sign-off, and alone when signing is not allowed', () => {
        const first = setup(props(n6OpenFixture));

        expect(disputeButton()).toBeEnabled();
        expect(acceptBox()).toBeInTheDocument();
        first.unmount();

        const disputeOnly = props(n6OpenFixture);

        disputeOnly.allowed_actions = ['report.dispute'];
        setup(disputeOnly);

        expect(disputeButton()).toBeEnabled();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
    });

    it('needs supporting text or a file, has no separate reason, and limits the text to 1,000 characters', async () => {
        const { user } = setup(props(n6OpenFixture));
        await openSheet(user);
        const sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );
        const submit = sheet.getByRole('button', { name: 'Submit dispute' });
        const text = sheet.getByLabelText('Supporting text');

        expect(
            sheet.getByText(
                'Show what you dispute in the findings and your proof. Your dispute does not change the sealed report, and it pauses the 24-hour window while the CPA reviews it.',
            ),
        ).toBeInTheDocument();
        expect(
            sheet.getByText('Add supporting text, at least one file, or both.'),
        ).toBeInTheDocument();
        expect(
            sheet.getByText(
                'Up to 5 files: PDF, JPEG or PNG, at most 10 MB each.',
            ),
        ).toBeInTheDocument();
        expect(sheet.queryByLabelText(/reason/iu)).not.toBeInTheDocument();
        expect(sheet.getByText('0/1000')).toBeInTheDocument();
        expect(submit).toBeDisabled();

        await user.type(text, '   ');
        expect(submit).toBeDisabled();

        fireEvent.change(text, { target: { value: 's'.repeat(1050) } });
        expect(text).toHaveValue('s'.repeat(1000));
        expect(sheet.getByText('1000/1000')).toBeInTheDocument();
        expect(submit).toBeEnabled();

        fireEvent.change(text, { target: { value: '' } });
        expect(submit).toBeDisabled();

        choose(fileInput(), [pdf()]);
        expect(submit).toBeEnabled();
        expect(inertia.calls).toEqual([]);
    });

    it('checks each file before sending: PDF, JPEG or PNG, at most 10 MiB, at most five', async () => {
        const { user } = setup(props(n6OpenFixture));
        await openSheet(user);
        const sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );
        const input = fileInput();
        const oversized = pdf('bank-statement.pdf', 10 * 1024 * 1024 + 1);
        const atLimit = pdf('ledger.pdf', 10 * 1024 * 1024);
        const untyped = new File(['p'], 'TILL-ROLL.PNG', { type: '' });

        choose(input, [
            new File(['t'], 'notes.txt', { type: 'text/plain' }),
            oversized,
            atLimit,
            untyped,
        ]);

        expect(sheet.getByRole('alert')).toHaveTextContent(
            "notes.txt wasn't added: only PDF, JPEG or PNG files are accepted." +
                "bank-statement.pdf wasn't added: each file can be at most 10 MB.",
        );

        const listed = () =>
            within(
                sheet.getByRole('list', { name: 'Proof files' }),
            ).getAllByRole('listitem');

        expect(listed()).toHaveLength(2);
        expect(listed()[0]).toHaveTextContent('ledger.pdf10 MB');
        expect(listed()[1]).toHaveTextContent('TILL-ROLL.PNG1 KB');

        choose(input, [
            new File(['j'], 'photo-1.jpg', { type: 'image/jpeg' }),
            new File(['j'], 'photo-2.jpeg', { type: 'image/jpeg' }),
            new File(['j'], 'photo-3.jpg', { type: 'image/jpeg' }),
            new File(['j'], 'photo-4.jpg', { type: 'image/jpeg' }),
        ]);

        expect(listed()).toHaveLength(5);
        expect(sheet.getByRole('alert')).toHaveTextContent(
            "photo-4.jpg wasn't added: a dispute can carry at most 5 files.",
        );
        expect(sheet.getByRole('button', { name: 'Add files' })).toBeDisabled();

        await user.click(
            sheet.getByRole('button', { name: 'Remove photo-1.jpg' }),
        );
        expect(listed()).toHaveLength(4);
        expect(sheet.getByRole('button', { name: 'Add files' })).toBeEnabled();

        choose(input, [pdf('another.pdf')]);
        expect(sheet.queryByRole('alert')).not.toBeInTheDocument();
        expect(input).toHaveAttribute(
            'accept',
            'application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png',
        );
    });

    it('opens the file picker from its button and ignores an empty choice', async () => {
        const { user } = setup(props(n6OpenFixture));
        await openSheet(user);
        const sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );
        const input = fileInput();
        const click = vi.spyOn(input, 'click');

        await user.click(sheet.getByRole('button', { name: 'Add files' }));

        expect(click).toHaveBeenCalledTimes(1);
        fireEvent.change(input, { target: { files: null } });
        expect(
            sheet.queryByRole('list', { name: 'Proof files' }),
        ).not.toBeInTheDocument();
    });

    it('sends exactly the contract body with supporting text and files', async () => {
        const page = props(n6OpenFixture);
        const { user } = setup(page);
        await openSheet(user);
        const sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );
        const receipt = new File(['r'], 'receipt-book.jpg', {
            type: 'image/jpeg',
        });
        const refund = pdf('refund-slip.pdf');

        await user.type(
            sheet.getByLabelText('Supporting text'),
            '  Refund slip 0142 matches the till roll.  ',
        );
        choose(fileInput(), [receipt, refund]);
        await user.click(sheet.getByRole('button', { name: 'Submit dispute' }));

        expect(inertia.calls).toEqual([
            {
                url: '/preview/business-audit-cosign-disputes',
                method: 'post',
                body: {
                    request_id: expect.stringMatching(
                        /^[0-9a-f-]{36}$/u,
                    ) as string,
                    identity_context_revision: 4,
                    expected_revision: 7,
                    report_revision: 3,
                    mandate_version: 2,
                    digest: page.report.digest,
                    supporting_text: 'Refund slip 0142 matches the till roll.',
                    proof_files: [receipt, refund],
                },
            },
        ]);
        expect(
            sheet.getByRole('button', { name: 'Submitting…' }),
        ).toHaveAttribute('aria-busy', 'true');
    });

    it('sends text alone without proof_files, and files alone without supporting_text', async () => {
        const first = setup(props(n6OpenFixture));
        await openSheet(first.user);
        let sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );

        await first.user.type(sheet.getByLabelText('Supporting text'), 'x');
        await first.user.click(
            sheet.getByRole('button', { name: 'Submit dispute' }),
        );

        expect(inertia.calls[0].body).not.toHaveProperty('proof_files');
        expect(inertia.calls[0].body).toHaveProperty('supporting_text', 'x');
        first.unmount();

        const second = setup(props(n6OpenFixture));
        await openSheet(second.user);
        sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );
        const only = pdf();

        choose(fileInput(), [only]);
        await second.user.click(
            sheet.getByRole('button', { name: 'Submit dispute' }),
        );

        expect(inertia.calls[1].body).not.toHaveProperty('supporting_text');
        expect(inertia.calls[1].body).toHaveProperty('proof_files', [only]);
    });

    it('follows data.next once the dispute is recorded', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'REPORT_DISPUTED',
                    data: {
                        next: {
                            url: '/preview/business-audit-cosign-n6-dispute-under-review',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        const { user } = setup(props(n6OpenFixture));
        await openSheet(user);
        const sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );

        await user.type(sheet.getByLabelText('Supporting text'), 'x');
        await user.click(sheet.getByRole('button', { name: 'Submit dispute' }));

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith({
                url: '/preview/business-audit-cosign-n6-dispute-under-review',
                method: 'get',
            }),
        );
        expect(inertia.reload).not.toHaveBeenCalled();
    });

    it('reads the page afresh when a recorded dispute names no next page', async () => {
        inertia.queue.push(
            answers(operation({ code: 'REPORT_DISPUTED', data: null })),
        );
        const { user } = setup(props(n6OpenFixture));
        await openSheet(user);
        const sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );

        await user.type(sheet.getByLabelText('Supporting text'), 'x');
        await user.click(sheet.getByRole('button', { name: 'Submit dispute' }));

        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith(
                window.location.href,
                expect.objectContaining({ preserveState: false }),
            ),
        );
    });

    it.each([
        [
            'DIGEST_STALE',
            "The report you read is no longer the current one. We've loaded it — read it and try again.",
        ],
        [
            'MANDATE_STALE',
            "The company's signing mandate changed. Check who can sign now, then try again.",
        ],
        ['AUDIT_REPORT_DISPUTE_WINDOW_CLOSED', null],
        ['AUDIT_REPORT_ALREADY_DISPUTED', null],
    ])(
        'reads a %s refusal afresh with its code carried across',
        async (code, reason) => {
            inertia.queue.push(fails(409, { code }));
            const { user } = setup(props(n6OpenFixture));
            await openSheet(user);
            const sheet = within(
                screen.getByRole('dialog', { name: 'Submit a dispute' }),
            );

            await user.type(sheet.getByLabelText('Supporting text'), 'x');
            await user.click(
                sheet.getByRole('button', { name: 'Submit dispute' }),
            );

            await waitFor(() =>
                expect(inertia.visit).toHaveBeenCalledWith(
                    window.location.href,
                    expect.objectContaining({ preserveState: false }),
                ),
            );
            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            expect(
                await screen.findByText(new RegExp(`\\(${code}\\)$`, 'u')),
            ).toBeInTheDocument();

            if (reason !== null) {
                expect(
                    screen.getByText(`${reason} (${code})`),
                ).toBeInTheDocument();
            }
        },
    );

    it('keeps a withdrawn authority on the page as a denial, without reading afresh', async () => {
        inertia.queue.push(fails(403, { code: 'ACTION_FORBIDDEN' }));
        const { user } = setup(props(n6OpenFixture));
        await openSheet(user);
        const sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );

        await user.type(sheet.getByLabelText('Supporting text'), 'x');
        await user.click(sheet.getByRole('button', { name: 'Submit dispute' }));

        expect(
            await screen.findByText(
                "You can't do this for this business. (ACTION_FORBIDDEN)",
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        expect(inertia.visit).not.toHaveBeenCalled();
    });

    it('looks a lost dispute up by command and identity context, keeping it in the sheet', async () => {
        inertia.queue.push(offline(), fails(503));
        const { user } = setup(props(n6OpenFixture));
        await openSheet(user);
        const sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );

        await user.type(sheet.getByLabelText('Supporting text'), 'x');
        await user.click(sheet.getByRole('button', { name: 'Submit dispute' }));

        expect(
            await sheet.findByText("We couldn't confirm this yet"),
        ).toBeInTheDocument();
        expect(
            sheet.getByRole('button', { name: 'Submit dispute' }),
        ).toBeDisabled();

        const sent = inertia.calls[0].body as { request_id: string };

        expect(inertia.calls[1]).toEqual({
            url: `/preview/business-audit-cosign-operation-${sent.request_id}`,
            method: 'get',
            body: { identity_context_revision: 4, command: 'report.dispute' },
        });

        await user.click(sheet.getByRole('button', { name: 'Cancel' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        expect(
            screen.getByText("We couldn't confirm this yet"),
        ).toBeInTheDocument();
        expect(disputeButton()).toBeDisabled();
    });

    it('marks the text and file errors in the sheet, including one file, and any other as a banner', async () => {
        inertia.errors = {
            identity_context_revision: 'Unrelated.',
            supporting_text: 'Too long.',
            'proof_files.1': 'That file could not be read.',
        };
        const { user } = setup(props(n6OpenFixture));

        expect(screen.getByRole('alert')).toHaveTextContent('Unrelated.');

        await openSheet(user);

        const sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );

        expect(sheet.getByText('Too long.')).toBeInTheDocument();
        expect(
            sheet.getByText('That file could not be read.'),
        ).toBeInTheDocument();
        expect(sheet.getByLabelText('Supporting text')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(sheet.getByRole('alert')).toHaveTextContent('Unrelated.');

        await user.click(screen.getAllByRole('button', { name: 'Cancel' })[0]);
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('marks an error on the whole file list in the sheet', async () => {
        inertia.errors = { proof_files: 'Add at most 5 files.' };
        const { user } = setup(props(n6OpenFixture));
        await openSheet(user);
        const sheet = within(
            screen.getByRole('dialog', { name: 'Submit a dispute' }),
        );

        expect(sheet.getByText('Add at most 5 files.')).toBeInTheDocument();
        expect(sheet.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('reads the sheet in French and Kinyarwanda', async () => {
        const user = userEvent.setup();
        const { unmount } = render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <BusinessAuditCosign {...props(n6OpenFixture)} />
            </I18nContext>,
        );

        await user.click(
            screen.getByRole('button', { name: 'Soumettre une contestation' }),
        );
        expect(screen.getByLabelText('Texte justificatif')).toBeInTheDocument();
        unmount();

        render(
            <I18nContext value={{ locale: 'rw', catalog: catalogFor('rw') }}>
                <BusinessAuditCosign {...props(n6OpenFixture)} />
            </I18nContext>,
        );

        expect(
            screen.getByText("Igihe cyo gusuzuma cy'amasaha 24"),
        ).toBeInTheDocument();
    });
});
