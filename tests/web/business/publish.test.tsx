import type * as InertiaCore from '@inertiajs/core';
import { screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import BusinessPublish from '@/pages/business/publish';
import type { C3BusinessPublishProps } from '@/types/business';
import liveMinimalFixture from '../../../resources/fixtures/ui/business-publish-live-minimal.json';
import notReleasedFixture from '../../../resources/fixtures/ui/business-publish-not-released.json';
import publishedFixture from '../../../resources/fixtures/ui/business-publish-published.json';
import refusedFixture from '../../../resources/fixtures/ui/business-publish-refused.json';
import releasedFixture from '../../../resources/fixtures/ui/business-publish-released.json';
import changedFixture from '../../../resources/fixtures/ui/business-publish-terms-changed.json';
import unconfirmedFixture from '../../../resources/fixtures/ui/business-publish-unconfirmed.json';
import awaitingFixture from '../../../resources/fixtures/ui/business-publish.json';
import { answers, fails, inertia, operation } from '../auditor/inertia';
import { renderWithUser } from '../helpers/render-with-user';

vi.mock('@inertiajs/react', () => import('../auditor/inertia'));

vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaCore>()),
    http: { onResponse: () => () => undefined },
}));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as C3BusinessPublishProps;

const sheet = () =>
    screen.getByRole('dialog', { name: 'Publish to the Investor feed' });

const UUID =
    /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/;

beforeEach(() => inertia.reset());

describe('Publish before staff release', () => {
    it('states every prerequisite and the zero-fee waiver, with nothing to pay, accept or publish', () => {
        renderWithUser(<BusinessPublish {...props(awaitingFixture)} />);
        const view = within(sheet());

        expect(
            view.getByText(/Awaiting Rozine staff review/),
        ).toBeInTheDocument();

        const steps = view.getByRole('list', { name: 'Before you publish' });

        const items = within(steps).getAllByRole('listitem');

        expect(items).toHaveLength(4);
        expect(items[0]).toHaveTextContent(
            'Released by Rozine staff after reviewNot yet',
        );
        expect(items[1]).toHaveTextContent(
            'Your Review signatures are on recordDone',
        );
        expect(view.getByText('RWF 30,000,000')).toBeInTheDocument();
        expect(view.getByText('Listing fee')).toHaveTextContent(
            'Waived for the MVP',
        );
        expect(view.getByText('RWF 0')).toBeInTheDocument();
        expect(
            view.getByText(/Rozine waives the listing fee for the MVP/),
        ).toBeInTheDocument();
        expect(
            view.getByText('Disclosure synthetic-listing-fee-disclosure-0'),
        ).toBeInTheDocument();
        expect(view.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(view.queryByRole('radiogroup')).not.toBeInTheDocument();
        expect(
            view.queryByRole('button', { name: 'Publish' }),
        ).not.toBeInTheDocument();
        expect(view.getByRole('status')).toHaveTextContent(
            'Publishing opens once every step above is met.',
        );
        expect(view.getByRole('link', { name: 'Not yet' })).toHaveAttribute(
            'href',
            '/preview/business-home',
        );
        expect(inertia.calls).toHaveLength(0);
    });

    it('names the causes when staff could not release the application', () => {
        const page = props(refusedFixture);

        page.release.causes.push('ENGINE_GATE_FAILED', 'SOMETHING_NEW');
        renderWithUser(<BusinessPublish {...page} />);
        const view = within(sheet());

        expect(view.getByText('Not released for listing')).toBeInTheDocument();
        expect(
            view.getByText('The audit report is no longer current.'),
        ).toBeInTheDocument();
        expect(
            view.getByText(
                "The company's signing authority changed since you signed.",
            ),
        ).toBeInTheDocument();
        expect(
            view.getByText("The rating engine's credit gates don't pass."),
        ).toBeInTheDocument();
        expect(
            view.getByText("A release check didn't pass."),
        ).toBeInTheDocument();
        expect(
            view.queryByRole('button', { name: 'Publish' }),
        ).not.toBeInTheDocument();
    });

    it('shows a recorded not-released refusal from the server', () => {
        renderWithUser(<BusinessPublish {...props(notReleasedFixture)} />);

        expect(
            within(sheet()).getByText(
                "This application hasn't been released for listing yet.",
            ),
        ).toBeInTheDocument();
    });
});

describe('Publishing a released application', () => {
    it('sends application.publish with the retained revision and disclosure version, then follows the server', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'LISTING_PUBLISHED',
                    data: {
                        next: {
                            url: '/preview/business-publish-published',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(
            <BusinessPublish {...props(releasedFixture)} />,
        );
        const view = within(sheet());

        expect(
            view.getByText('Released for listing by Rozine staff.'),
        ).toBeInTheDocument();
        expect(view.queryByRole('checkbox')).not.toBeInTheDocument();

        await user.click(view.getByRole('button', { name: 'Publish' }));

        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/business-publish-published' },
            ]),
        );
        expect(inertia.calls).toHaveLength(1);
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/business-publish-listing',
            method: 'post',
            body: {
                identity_context_revision: 4,
                application_id: 'APP-2026-0398',
                expected_application_revision: 7,
                fee_disclosure_version: 'synthetic-listing-fee-disclosure-0',
            },
        });
        expect(
            (inertia.calls[0].body as { request_id: string }).request_id,
        ).toMatch(UUID);
        expect(
            screen.queryByText('Listed for investors'),
        ).not.toBeInTheDocument();
    });

    it('shows the publish in flight', async () => {
        const { user } = renderWithUser(
            <BusinessPublish {...props(releasedFixture)} />,
        );

        await user.click(
            within(sheet()).getByRole('button', { name: 'Publish' }),
        );

        expect(
            within(sheet()).getByRole('button', { name: 'Publishing…' }),
        ).toBeDisabled();
    });

    it('never resends after a 503: it looks up, refreshes, and offers the same request only while allowed', async () => {
        inertia.queue.push(
            fails(503, { code: 'RETRYABLE_CONTENTION' }),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        const page = props(releasedFixture);
        const { user, rerender } = renderWithUser(
            <BusinessPublish {...page} />,
        );

        await user.click(
            within(sheet()).getByRole('button', { name: 'Publish' }),
        );

        await waitFor(() =>
            expect(
                within(sheet()).getByText('Nothing was recorded'),
            ).toBeInTheDocument(),
        );
        expect(inertia.calls).toHaveLength(2);

        const sent = inertia.calls[0].body as { request_id: string };

        expect(inertia.calls[1]).toEqual({
            url: `/preview/business-campaign-operation-${sent.request_id}`,
            method: 'get',
            body: {
                identity_context_revision: 4,
                command: 'application.publish',
            },
        });
        expect(inertia.reloads).toHaveLength(1);
        expect(
            within(sheet()).getByRole('button', { name: 'Publish' }),
        ).toBeDisabled();

        rerender(<BusinessPublish {...page} allowed_actions={[]} />);

        expect(
            within(sheet()).getByText(
                'Nothing was recorded, and this action is no longer available with the current details.',
            ),
        ).toBeInTheDocument();

        rerender(<BusinessPublish {...page} />);
        inertia.queue.push(
            answers(
                operation({
                    code: 'LISTING_PUBLISHED',
                    data: { next: { url: '/preview/next', method: 'get' } },
                }),
            ),
        );
        await user.click(
            within(sheet()).getByRole('button', {
                name: 'Send the same request again',
            }),
        );

        await waitFor(() => expect(inertia.calls).toHaveLength(3));
        expect(inertia.calls[2].body).toEqual(inertia.calls[0].body);
    });

    it('looks up a seeded unconfirmed publish instead of resending it', async () => {
        inertia.queue.push(fails(503));
        const { user } = renderWithUser(
            <BusinessPublish {...props(unconfirmedFixture)} />,
        );
        const view = within(sheet());

        expect(view.getByText('Not yet confirmed')).toBeInTheDocument();
        expect(view.getByRole('button', { name: 'Publish' })).toBeDisabled();

        await user.click(view.getByRole('button', { name: 'Check again' }));

        await waitFor(() => expect(inertia.calls).toHaveLength(1));
        expect(inertia.calls[0]).toEqual({
            url: '/preview/business-campaign-operation-3b8e2f4a-6c1d-4e7f-9a2b-5c8d1e0f3a6b',
            method: 'get',
            body: {
                identity_context_revision: 4,
                command: 'application.publish',
            },
        });
        expect(inertia.calls.some((call) => call.method === 'post')).toBe(
            false,
        );
    });
});

describe('Publish after the quote or terms changed', () => {
    it('routes back to Review to sign again instead of offering Publish', () => {
        const page = props(changedFixture);

        page.allowed_actions = ['application.publish'];
        renderWithUser(<BusinessPublish {...page} />);
        const view = within(sheet());

        expect(view.getByRole('status')).toHaveTextContent(
            'The quote or terms changed since you signed.',
        );
        expect(
            view.getByRole('link', { name: 'Review and sign again' }),
        ).toHaveAttribute('href', '/preview/business-apply-review');
        expect(
            view.queryByRole('button', { name: 'Publish' }),
        ).not.toBeInTheDocument();
        expect(
            within(
                view.getByRole('list', { name: 'Before you publish' }),
            ).getAllByRole('listitem')[2],
        ).toHaveTextContent('Quote unchanged since you signedNot yet');
    });
});

describe('A published listing', () => {
    it('shows the zero-fee LISTING_PUBLISHED receipt and leads to the campaign', () => {
        renderWithUser(<BusinessPublish {...props(publishedFixture)} />);
        const view = within(sheet());

        expect(
            view.getByRole('heading', { name: 'Listed for investors' }),
        ).toBeInTheDocument();

        const receipt = view.getByLabelText('Listing receipt');

        expect(receipt).toHaveTextContent('Listing feeRWF 0');
        expect(receipt).toHaveTextContent('ReferenceLST-2026-0398');
        expect(receipt).toHaveTextContent('Recorded25 Sept 2026 · 09:02');
        expect(receipt).toHaveTextContent(
            'Fee disclosuresynthetic-listing-fee-disclosure-0',
        );
        expect(
            view.getByRole('link', { name: 'View campaign' }),
        ).toHaveAttribute('href', '/preview/business-campaign-raising-live');
        expect(
            view.getByRole('link', { name: 'Back to Home' }),
        ).toHaveAttribute('href', '/preview/business-home');
        expect(
            view.queryByRole('button', { name: 'Publish' }),
        ).not.toBeInTheDocument();
    });

    it('keeps the receipt as recorded while the current facts move on', () => {
        const page = props(publishedFixture);
        const { rerender } = renderWithUser(<BusinessPublish {...page} />);

        rerender(
            <BusinessPublish
                {...page}
                listing_fee={{ currency: 'RWF', amount: '5000' }}
                application={{ ...page.application, revision: 9 }}
            />,
        );

        expect(within(sheet()).getByText('RWF 0')).toBeInTheDocument();
        expect(
            within(sheet()).queryByText('RWF 5,000'),
        ).not.toBeInTheDocument();
    });

    it('omits the disclosure row when the receipt carries none', () => {
        const page = props(publishedFixture);

        page.listing!.receipt.disclosure_version = null;
        renderWithUser(<BusinessPublish {...page} />);

        expect(
            within(sheet()).queryByText('Fee disclosure'),
        ).not.toBeInTheDocument();
    });
});

describe('Publish without Home', () => {
    it('opens over an empty backdrop with the live URL shapes', () => {
        renderWithUser(<BusinessPublish {...props(liveMinimalFixture)} />);

        expect(sheet()).toBeInTheDocument();
        expect(screen.queryByText('GreenLeaf Agro')).not.toBeInTheDocument();
        expect(
            within(sheet()).getByRole('link', { name: 'Not yet' }),
        ).toHaveAttribute('href', '/business');
    });
});
