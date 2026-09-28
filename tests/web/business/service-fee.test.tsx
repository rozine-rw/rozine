import type * as InertiaCore from '@inertiajs/core';
import { screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { formatRwf } from '@/lib/rozine/format';
import BusinessApply from '@/pages/business/apply';
import BusinessPublish from '@/pages/business/publish';
import type {
    ApplicationQuote,
    BusinessApplyProps,
    C3BusinessPublishProps,
    ServiceFeeDisclosure,
} from '@/types/business';
import feeAbsentStep from '../../../resources/fixtures/ui/business-apply-review-service-fee-absent.json';
import feeUnavailableStep from '../../../resources/fixtures/ui/business-apply-review-service-fee-unavailable.json';
import feeStep from '../../../resources/fixtures/ui/business-apply-review-service-fee.json';
import reviewStep from '../../../resources/fixtures/ui/business-apply-review.json';
import publishFeeUnavailableFixture from '../../../resources/fixtures/ui/business-publish-service-fee-unavailable.json';
import publishFeeFixture from '../../../resources/fixtures/ui/business-publish-service-fee.json';
import { inertia } from '../auditor/inertia';
import { renderWithUser } from '../helpers/render-with-user';

vi.mock('@inertiajs/react', () => import('../auditor/inertia'));

vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaCore>()),
    http: { onResponse: () => () => undefined },
}));

type ReadyQuote = Extract<ApplicationQuote, { status: 'ready' }>;

const applyProps = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessApplyProps;

const publishProps = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as C3BusinessPublishProps;

const readyQuote = (page: BusinessApplyProps) => page.quote as ReadyQuote;

const offerCard = () => screen.getByRole('group', { name: 'Your offer' });

const publishDialog = () =>
    screen.getByRole('dialog', { name: 'Publish to the Investor feed' });

const ACCEPT = /^I accept this offer/u;
const UNAVAILABLE = "Fee terms unavailable — you can't sign yet";
const NOTE =
    'Projected service fee: 2% of each repayment, charged on amounts actually repaid (principal and interest, excluding fees and penalties). These figures assume every instalment is paid in full on schedule.';
/** The application-fee note wherever no service fee is shown: it claims nothing about other fees. */
const NEUTRAL_FEE_NOTE =
    'No fee on the amount you raise. Charged once your note is approved, before it goes live.';
/** The retired claim, untrue under §11.4 whether or not the server sends the fee yet. */
const NOTHING_ON_TOP = /nothing on top of your quoted rate/iu;
/** A zero amount standing in for a missing fee input: "RWF 0" not followed by a digit or comma. */
const ZERO = /RWF 0(?![\d,])/u;

beforeEach(() => inertia.reset());

describe('Service fee — the Due on approval note', () => {
    it.each([
        ['present', feeStep, false],
        ['absent, as on dev today', reviewStep, true],
        ['absent, in the absence-rule preview', feeAbsentStep, true],
        ['explicitly unavailable', feeUnavailableStep, true],
    ])(
        'never claims nothing is added on top of the rate when the fee is %s',
        (_case, fixture: { props: unknown }, neutral) => {
            renderWithUser(<BusinessApply {...applyProps(fixture)} />);

            expect(screen.queryByText(NOTHING_ON_TOP)).not.toBeInTheDocument();

            if (neutral) {
                expect(screen.getByText(NEUTRAL_FEE_NOTE)).toBeInTheDocument();
            } else {
                expect(
                    screen.queryByText(NEUTRAL_FEE_NOTE),
                ).not.toBeInTheDocument();
            }
        },
    );
});

describe('Service fee — present', () => {
    it('shows the projected fee, the per-instalment fees and the total payable verbatim from the server', () => {
        const page = applyProps(feeStep);
        const quote = readyQuote(page);
        const fee = quote.service_fee as ServiceFeeDisclosure;

        renderWithUser(<BusinessApply {...page} />);
        const card = within(offerCard());

        /* The worked example, exactly as the fixture states it. */
        expect(formatRwf(quote.principal)).toBe('RWF 10,800,000');
        expect(formatRwf(quote.total)).toBe('RWF 11,998,800');
        expect(formatRwf(fee.total)).toBe('RWF 239,976');
        expect(formatRwf(quote.total_payable!)).toBe('RWF 12,238,776');

        expect(card.getByText('RWF 10,800,000')).toBeInTheDocument();
        expect(card.getByText('RWF 11,998,800')).toBeInTheDocument();
        expect(
            card.getByText('Service fee (2% of each repayment)'),
        ).toHaveTextContent('Projected');
        expect(card.getByText('RWF 239,976')).toBeInTheDocument();
        expect(card.getByText('Total payable (projected)')).toBeInTheDocument();
        expect(card.getByText('RWF 12,238,776')).toBeInTheDocument();
        expect(card.getByText(NOTE)).toBeInTheDocument();

        const rows = within(
            card.getByRole('list', { name: 'Repayment schedule' }),
        ).getAllByRole('listitem');

        expect(rows).toHaveLength(6);
        rows.forEach((row, index) => {
            expect(row).toHaveTextContent(
                `Instalment ${index + 1}${index === 5 ? ' · final' : ''}RWF 1,999,800+ RWF 39,996 service fee`,
            );
        });
        expect(card.queryByText(ZERO)).not.toBeInTheDocument();
        expect(screen.queryByText(UNAVAILABLE)).not.toBeInTheDocument();
        expect(
            screen.getByText(
                'No fee on the amount you raise. Charged once your note is approved, before it goes live. The service fee on each repayment is shown with your offer above.',
            ),
        ).toBeInTheDocument();
    });

    it('lets a signatory accept the offer and sign, as before', async () => {
        const page = applyProps(feeStep);
        const { user } = renderWithUser(<BusinessApply {...page} />);

        await user.click(screen.getByRole('checkbox', { name: ACCEPT }));

        for (const disclosure of page.acceptance.disclosures) {
            await user.click(
                screen.getByRole('checkbox', { name: disclosure.text }),
            );
        }

        await user.click(
            screen.getByRole('checkbox', {
                name: 'I agree to the Terms & Conditions.',
            }),
        );
        await user.click(
            screen.getByRole('checkbox', {
                name: 'I have read the Privacy Note.',
            }),
        );
        await user.click(screen.getByLabelText('Your full name'));
        await user.paste('Robert Mugisha');
        await user.click(
            screen.getByRole('button', { name: 'Sign application' }),
        );

        expect(inertia.calls).toEqual([
            expect.objectContaining({
                url: '/preview/business-apply-signatures',
                method: 'post',
            }),
        ]);
    });

    it('shows the same projection on the Raise quote', () => {
        const page = { ...applyProps(feeStep), step: 'raise' as const };

        renderWithUser(<BusinessApply {...page} />);

        expect(
            screen.getByText('Service fee (2% of each repayment)'),
        ).toBeInTheDocument();
        expect(screen.getByText('RWF 239,976')).toBeInTheDocument();
        expect(screen.getByText('RWF 12,238,776')).toBeInTheDocument();
        expect(screen.getAllByText('+ RWF 39,996 service fee')).toHaveLength(6);
        expect(screen.getByText(NOTE)).toBeInTheDocument();
    });
});

describe('Service fee — policy explicitly unavailable', () => {
    const unavailableVariants: [string, (quote: ReadyQuote) => void][] = [
        ['no service_fee block', () => undefined],
        [
            'no approved rounding policy',
            (quote) => {
                Object.assign(quote, {
                    service_fee: {
                        ...readyQuote(applyProps(feeStep)).service_fee,
                        rounding: null,
                    },
                    total_payable: readyQuote(applyProps(feeStep))
                        .total_payable,
                });
            },
        ],
        [
            'no total payable',
            (quote) => {
                Object.assign(quote, {
                    service_fee: readyQuote(applyProps(feeStep)).service_fee,
                    total_payable: undefined,
                });
            },
        ],
        [
            'an instalment with no projected fee',
            (quote) => {
                const fee = readyQuote(applyProps(feeStep))
                    .service_fee as ServiceFeeDisclosure;

                Object.assign(quote, {
                    service_fee: { ...fee, schedule: fee.schedule.slice(1) },
                    total_payable: readyQuote(applyProps(feeStep))
                        .total_payable,
                });
            },
        ],
    ];

    it.each(unavailableVariants)(
        'disables acceptance with %s, and never shows a fee figure',
        async (_case, vary) => {
            const page = applyProps(feeUnavailableStep);

            vary(readyQuote(page));

            const { user } = renderWithUser(<BusinessApply {...page} />);
            const card = within(offerCard());

            expect(screen.getByText(UNAVAILABLE)).toBeInTheDocument();
            expect(
                screen.getByText(
                    "Rozine hasn't published the service-fee terms for this offer, so it can't be accepted yet. Your draft and your offer stay saved.",
                ),
            ).toBeInTheDocument();
            expect(
                screen.queryByRole('checkbox', { name: ACCEPT }),
            ).not.toBeInTheDocument();
            expect(card.queryByText(/service fee/iu)).not.toBeInTheDocument();
            expect(card.queryByText(/Total payable/u)).not.toBeInTheDocument();
            expect(card.queryByText(ZERO)).not.toBeInTheDocument();
            /* The contractual figures still stand as the server sent them. */
            expect(card.getByText('RWF 11,998,800')).toBeInTheDocument();

            await user.click(
                screen.getByRole('button', { name: 'Sign application' }),
            );

            expect(
                screen.getByText('Complete this step to continue'),
            ).toBeInTheDocument();
            expect(inertia.calls).toEqual([]);
        },
    );

    it('keeps the unavailable state to someone who could otherwise sign', () => {
        const page = {
            ...applyProps(feeUnavailableStep),
            allowed_actions: [],
        };

        renderWithUser(<BusinessApply {...page} />);

        expect(screen.queryByText(UNAVAILABLE)).not.toBeInTheDocument();
        expect(
            within(offerCard()).queryByText(/Service fee/u),
        ).not.toBeInTheDocument();
        expect(within(offerCard()).queryByText(ZERO)).not.toBeInTheDocument();
    });
});

describe('Service fee — absent', () => {
    it('blocks acceptance in the preview of the proposed absence rule', () => {
        const page = applyProps(feeAbsentStep);

        expect(page.preview_fee_terms_required).toBe(true);
        expect(readyQuote(page)).not.toHaveProperty('service_fee');

        renderWithUser(<BusinessApply {...page} />);

        expect(screen.getByText(UNAVAILABLE)).toBeInTheDocument();
        expect(
            screen.queryByRole('checkbox', { name: ACCEPT }),
        ).not.toBeInTheDocument();
        expect(
            within(offerCard()).queryByText(/Service fee/u),
        ).not.toBeInTheDocument();
        expect(within(offerCard()).queryByText(ZERO)).not.toBeInTheDocument();
    });

    it.each([
        ['the absence-rule preview without its flag', feeAbsentStep],
        ['the existing review fixture', reviewStep],
    ])(
        'leaves live acceptance unchanged for %s',
        (_case, fixture: { props: unknown }) => {
            const page = applyProps(fixture);

            delete page.preview_fee_terms_required;

            renderWithUser(<BusinessApply {...page} />);

            expect(
                screen.getByRole('checkbox', { name: ACCEPT }),
            ).toBeInTheDocument();
            expect(screen.queryByText(UNAVAILABLE)).not.toBeInTheDocument();
            expect(
                within(offerCard()).queryByText(/Service fee/u),
            ).not.toBeInTheDocument();
            expect(
                within(offerCard()).queryByText(ZERO),
            ).not.toBeInTheDocument();
            expect(screen.getByText(NEUTRAL_FEE_NOTE)).toBeInTheDocument();
        },
    );
});

describe('Service fee — Publish', () => {
    it('shows the projected fee and total payable beside Publish', () => {
        renderWithUser(
            <BusinessPublish {...publishProps(publishFeeFixture)} />,
        );
        const view = within(publishDialog());

        expect(
            view.getByText('Service fee (2% of each repayment)'),
        ).toBeInTheDocument();
        expect(view.getByText('RWF 239,976')).toBeInTheDocument();
        expect(view.getByText('Total payable (projected)')).toBeInTheDocument();
        expect(view.getByText('RWF 12,238,776')).toBeInTheDocument();
        expect(view.getByText(NOTE)).toBeInTheDocument();
        expect(view.getByRole('button', { name: 'Publish' })).toBeEnabled();
    });

    it.each([
        ['explicitly unavailable', publishProps(publishFeeUnavailableFixture)],
        [
            'absent in the preview of the proposed absence rule',
            (() => {
                const page = publishProps(publishFeeFixture);

                delete page.service_fee;
                delete page.total_payable;

                return { ...page, preview_fee_terms_required: true as const };
            })(),
        ],
    ])(
        'withholds Publish while the fee terms are %s',
        (_case, page: C3BusinessPublishProps) => {
            renderWithUser(<BusinessPublish {...page} />);
            const view = within(publishDialog());

            expect(
                view.getByText("Fee terms unavailable — you can't publish yet"),
            ).toBeInTheDocument();
            expect(
                view.queryByRole('button', { name: 'Publish' }),
            ).not.toBeInTheDocument();
            expect(view.queryByText(/Service fee/u)).not.toBeInTheDocument();
            expect(view.queryByText(/Total payable/u)).not.toBeInTheDocument();
            /* The only zero figure is the listing fee's own explicit waiver. */
            expect(view.getAllByText('RWF 0')).toHaveLength(1);
            expect(view.getByText('Listing fee')).toBeInTheDocument();
        },
    );
});
