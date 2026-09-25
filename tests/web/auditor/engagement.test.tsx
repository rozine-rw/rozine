import {
    act,
    getDefaultNormalizer,
    render,
    screen,
    waitFor,
} from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { catalogFor } from '@/lib/i18n';
import { I18nContext } from '@/lib/i18n/context';
import AuditorEngagement from '@/pages/auditor/engagement';
import type {
    AuditorEngagementProps,
    EngagementOperationResource,
} from '@/types/auditor';
import acceptedFixture from '../../../resources/fixtures/ui/auditor-engagement-accepted.json';
import unavailableFixture from '../../../resources/fixtures/ui/auditor-engagement-unavailable.json';
import conflictFixture from '../../../resources/fixtures/ui/auditor-engagement-version-conflict.json';
import requiredFixture from '../../../resources/fixtures/ui/auditor-engagement.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, invalid } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const props = (
    fixture: { props: unknown } = requiredFixture,
): AuditorEngagementProps =>
    structuredClone(fixture.props) as AuditorEngagementProps;

const RELEASE = props().release!;

const ACCEPTED: EngagementOperationResource = {
    operation_id: 'op-accept',
    status: 'completed',
    code: 'AUDIT_ENGAGEMENT_ACCEPTED',
    data: {
        acceptance: {
            id: '01k6x0acceptance0000000001',
            release_id: RELEASE.id,
            release_revision: RELEASE.revision,
            release_sha256: RELEASE.sha256,
            accepted_at: '2026-10-03T17:00:02Z',
            sha256: 'b'.repeat(64),
        },
    },
    revision: 1,
    policy_version: 'engineering-2026-09-25.1',
    recorded_at: '2026-10-03T17:00:02Z',
    server_time: '2026-10-03T17:00:03Z',
    allowed_actions: [],
    field_errors: {},
};

/** Matches text exactly as it stands, with no whitespace trimmed or collapsed. */
const VERBATIM = getDefaultNormalizer({
    trim: false,
    collapseWhitespace: false,
});

const LABEL =
    'I have read and accept the Master Services Agreement and the Agreed Procedures';

const tick = async (view: ReturnType<typeof renderWithUser>) =>
    view.user.click(screen.getByRole('checkbox', { name: LABEL }));

const acceptButton = () =>
    screen.getByRole('button', { name: 'Accept the terms' });

beforeEach(() => inertia.reset());

describe('Auditor engagement terms', () => {
    it('shows both documents in full as plain text, with their version, hashes and synthetic provenance', () => {
        render(<AuditorEngagement {...props()} />);

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Engagement terms',
        );
        expect(
            screen.getByRole('heading', {
                name: 'Synthetic master services terms',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', {
                name: 'Synthetic MVP-AUP-1 procedure terms',
            }),
        ).toBeInTheDocument();

        for (const kind of ['master_services', 'agreed_procedures'] as const) {
            const body = screen.getByTestId(`terms-${kind}`);
            const document = RELEASE.documents[kind];

            /*
             * The original text exactly, as the body's own text: every line break and indent kept,
             * and no element inside it, so nothing was rendered as markup.
             */
            expect(
                screen.getByText(document.body, { normalizer: VERBATIM }),
            ).toBe(body);
            expect(body).toHaveClass('whitespace-pre-wrap');
            expect(body).toHaveAttribute('translate', 'no');
            expect(
                screen.getByText(
                    `Version synthetic-terms-1 · SHA-256 ${document.sha256.slice(0, 12)}…`,
                ),
            ).toBeInTheDocument();
        }

        expect(screen.getByText('Master Services Agreement')).toBeVisible();
        expect(screen.getByText('Agreed Procedures')).toBeVisible();
        expect(
            screen.getByText('Version synthetic-terms-1 · procedure MVP-AUP-1'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(`Release SHA-256 ${RELEASE.sha256.slice(0, 12)}…`),
        ).toBeInTheDocument();
        expect(screen.getByRole('note')).toHaveTextContent(
            'Synthetic test terms' +
                'These are test terms, not for real engagements. Accepting them represents no real professional engagement.',
        );
        expect(
            screen.queryByText(
                'The terms are shown in their original language.',
            ),
        ).not.toBeInTheDocument();
    });

    it('leaves out the synthetic label for real terms', () => {
        const base = props();

        render(
            <AuditorEngagement
                {...base}
                release={{ ...base.release!, synthetic: false }}
            />,
        );

        expect(screen.queryByRole('note')).not.toBeInTheDocument();
    });

    it('keeps the retained text in its original language under a translated page', () => {
        render(
            <I18nContext value={{ locale: 'fr', catalog: catalogFor('fr') }}>
                <AuditorEngagement {...props()} />
            </I18nContext>,
        );

        expect(
            screen.getByText(
                'Les conditions sont affichées dans leur langue d’origine.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText(RELEASE.documents.master_services.body, {
                normalizer: VERBATIM,
            }),
        ).toBe(screen.getByTestId('terms-master_services'));
        expect(
            screen.getByRole('button', { name: 'Accepter les conditions' }),
        ).toBeDisabled();
    });

    it('keeps Accept disabled until the separate box is ticked', async () => {
        const view = renderWithUser(<AuditorEngagement {...props()} />);
        const box = screen.getByRole('checkbox', { name: LABEL });

        expect(box).not.toBeChecked();
        expect(acceptButton()).toBeDisabled();

        await tick(view);
        expect(box).toBeChecked();
        expect(acceptButton()).toBeEnabled();

        await tick(view);
        expect(acceptButton()).toBeDisabled();
        expect(inertia.calls).toEqual([]);
    });

    it('sends the release exactly as read and reads the page afresh once accepted', async () => {
        inertia.queue.push(answers(ACCEPTED));
        const view = renderWithUser(<AuditorEngagement {...props()} />);

        await tick(view);
        await view.user.click(acceptButton());

        expect(inertia.calls).toHaveLength(1);
        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-engagement-accepted',
            method: 'post',
            body: {
                identity_context_revision: 3,
                expected_revision: 1,
                release_id: RELEASE.id,
                sha256: RELEASE.sha256,
                accepted: true,
                request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u) as string,
            },
        });
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-engagement' },
            ]),
        );
        /* No "accepted" state of the page's own: only the fresh read shows it. */
        expect(screen.queryByText(/You accepted/u)).not.toBeInTheDocument();
    });

    it('looks a lost acceptance up by its request_id alone, then reads the page afresh', async () => {
        inertia.queue.push(fails(503), answers(ACCEPTED));
        const view = renderWithUser(<AuditorEngagement {...props()} />);

        await tick(view);
        await view.user.click(acceptButton());

        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-engagement' },
            ]),
        );
        const requestId = (inertia.calls[0].body as { request_id: string })
            .request_id;

        expect(inertia.calls[1]).toEqual({
            url: `/preview/auditor-engagement-operation-${requestId}`,
            method: 'get',
            body: {},
        });
    });

    it('refreshes before offering the identical acceptance again when nothing was recorded', async () => {
        inertia.holdReload = true;
        inertia.queue.push(
            fails(503),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        const view = renderWithUser(<AuditorEngagement {...props()} />);

        await tick(view);
        await view.user.click(acceptButton());

        await waitFor(() =>
            expect(inertia.reloads).toEqual([
                { onFinish: expect.any(Function) },
            ]),
        );
        expect(screen.getByText('Checking what happened')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Accepting…' }),
        ).toBeDisabled();

        act(() => inertia.finishReload.forEach((finish) => finish()));
        const retry = await screen.findByRole('button', { name: 'Try again' });

        inertia.queue.push(answers(ACCEPTED));
        await view.user.click(retry);

        expect(inertia.calls[2]).toEqual(inertia.calls[0]);
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-engagement' },
            ]),
        );
    });

    it('asks the lookup again while it cannot be reached', async () => {
        inertia.queue.push(fails(503), fails(503));
        const view = renderWithUser(<AuditorEngagement {...props()} />);

        await tick(view);
        await view.user.click(acceptButton());

        const again = await screen.findByRole('button', {
            name: 'Check again',
        });

        expect(
            screen.getByText("We couldn't confirm your last action"),
        ).toBeInTheDocument();
        inertia.queue.push(answers(ACCEPTED));
        await view.user.click(again);

        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-engagement' },
            ]),
        );
        expect(inertia.calls[2].url).toBe(inertia.calls[1].url);
    });

    it('re-reads the page after a version conflict and makes the new release be accepted afresh', async () => {
        inertia.queue.push(
            fails(409, { code: 'AUDIT_ENGAGEMENT_VERSION_CONFLICT' }),
        );
        const view = renderWithUser(<AuditorEngagement {...props()} />);

        await tick(view);
        await view.user.click(acceptButton());

        expect(
            await screen.findByText(
                /The engagement terms changed before your acceptance reached Rozine/u,
            ),
        ).toBeInTheDocument();
        expect(inertia.reloads).toEqual([undefined]);

        /* The reload delivers the replacement release: the box starts unticked again. */
        view.rerender(<AuditorEngagement {...props(conflictFixture)} />);

        expect(screen.getByRole('checkbox', { name: LABEL })).not.toBeChecked();
        expect(acceptButton()).toBeDisabled();
        expect(screen.getByRole('alert')).toHaveTextContent(
            /The engagement terms changed/u,
        );
        expect(
            screen.getByText(/6\. Changes/u, { selector: 'div' }),
        ).toBeInTheDocument();

        inertia.queue.push(answers(ACCEPTED));
        await tick(view);
        await view.user.click(acceptButton());

        expect(inertia.calls[1].body).toMatchObject({
            expected_revision: 2,
            release_id: '01k6x0engagement0000000002',
        });
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('opens the version-conflict preview with its banner and the replacement release', () => {
        render(<AuditorEngagement {...props(conflictFixture)} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'The engagement terms changed before your acceptance reached Rozine, so nothing was accepted. Read the current version in full before you accept.',
        );
        expect(
            screen.getByText('Version synthetic-terms-2 · procedure MVP-AUP-1'),
        ).toBeInTheDocument();
    });

    it('refreshes after withdrawn terms and shows that nothing was accepted', async () => {
        inertia.queue.push(
            fails(409, { code: 'AUDIT_ENGAGEMENT_TERMS_REQUIRED' }),
        );
        const view = renderWithUser(<AuditorEngagement {...props()} />);

        await tick(view);
        await view.user.click(acceptButton());

        expect(
            await screen.findByText(
                'These engagement terms were withdrawn before your acceptance reached Rozine, so nothing was accepted.',
            ),
        ).toBeInTheDocument();
        expect(inertia.reloads).toEqual([undefined]);

        view.rerender(<AuditorEngagement {...props(unavailableFixture)} />);
        expect(screen.getByRole('alert')).toHaveTextContent(/were withdrawn/u);
        expect(
            screen.getByText('No engagement terms are available right now'),
        ).toBeInTheDocument();
    });

    it('shows a recorded unticked acceptance beside the box', async () => {
        inertia.queue.push(
            invalid({
                accepted:
                    'Read and explicitly accept both engagement documents.',
            }),
        );
        const view = renderWithUser(<AuditorEngagement {...props()} />);

        await tick(view);
        await view.user.click(acceptButton());

        const message = await screen.findByText(
            'Tick the box to confirm you have read and accept both documents.',
        );
        const box = screen.getByRole('checkbox', { name: LABEL });

        expect(box).toHaveAttribute('aria-invalid', 'true');
        expect(box).toHaveAttribute('aria-describedby', message.id);
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        expect(inertia.reloads).toEqual([]);
        expect(acceptButton()).toBeEnabled();
    });

    it('still shows a field error the page has no field for', async () => {
        inertia.queue.push(invalid({ sha256: 'The sha256 is invalid.' }));
        const view = renderWithUser(<AuditorEngagement {...props()} />);

        await tick(view);
        await view.user.click(acceptButton());

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'The sha256 is invalid.',
        );
        expect(
            screen.getByRole('checkbox', { name: LABEL }),
        ).not.toHaveAttribute('aria-invalid');
    });

    it('explains a denial without reloading', async () => {
        inertia.queue.push(fails(403, { code: 'ACTION_FORBIDDEN' }));
        const view = renderWithUser(<AuditorEngagement {...props()} />);

        await tick(view);
        await view.user.click(acceptButton());

        expect(await screen.findByRole('alert')).toHaveTextContent(
            "You can't do this on this assignment any more. Your access changed.",
        );
        expect(inertia.reloads).toEqual([]);
    });

    it('shows the retained acceptance once the current terms are accepted', () => {
        render(<AuditorEngagement {...props(acceptedFixture)} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'You accepted version synthetic-terms-1 on 2 Oct 2026',
        );
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Accept the terms' }),
        ).not.toBeInTheDocument();
        expect(screen.getByTestId('terms-agreed_procedures')).toBeVisible();
    });

    it('offers no acceptance the server does not list', () => {
        const base = props();

        render(
            <AuditorEngagement
                {...base}
                allowed_actions={[]}
                actions={{ accept: null }}
            />,
        );

        expect(
            screen.getByText(
                'Accepting these terms isn’t available to you right now.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
    });

    it('never infers Accept from an action route the server did not list', () => {
        render(<AuditorEngagement {...props()} allowed_actions={[]} />);

        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
    });

    it('says when no terms are available', () => {
        render(<AuditorEngagement {...props(unavailableFixture)} />);

        expect(
            screen.getByText('No engagement terms are available right now'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'New work is paused until Rozine publishes terms. Nothing is needed from you meanwhile.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByTestId('column-right')).not.toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
    });
});
