import { fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import BusinessProspectus, {
    forecastMonths,
} from '@/pages/business/prospectus';
import type {
    BusinessProspectusProps,
    ProspectusInputs,
} from '@/types/business';

const inertia = vi.hoisted(() => ({
    sent: [] as { method: string; url: string; data: unknown }[],
    errors: {} as Record<string, string>,
    signErrors: {} as Record<string, string>,
    uploadErrors: {} as Record<string, string>,
    processing: false,
}));

vi.mock('@inertiajs/react', async () => {
    const { useState } = await import('react');

    return {
        Head: ({ title }: { title: string }) => (
            <span data-testid="head">{title}</span>
        ),
        Link: ({
            href,
            children,
            ...props
        }: Omit<ComponentProps<'a'>, 'href'> & {
            href: { url: string; method: string };
        }) => (
            <a href={href.url} {...props}>
                {children}
            </a>
        ),
        useForm: <T extends object>(initial: T) => {
            const [data, setState] = useState(initial);
            const signing = Object.keys(initial).length === 0;
            const uploading = 'file' in initial;
            let transform = (current: T): unknown => current;

            return {
                data,
                setData: (
                    key: keyof T | ((current: T) => T),
                    value?: unknown,
                ) =>
                    setState((current) =>
                        typeof key === 'function'
                            ? key(current)
                            : { ...current, [key]: value },
                    ),
                isDirty: JSON.stringify(data) !== JSON.stringify(initial),
                errors: signing
                    ? inertia.signErrors
                    : uploading
                      ? inertia.uploadErrors
                      : inertia.errors,
                processing: inertia.processing,
                transform: (callback: (current: T) => unknown) => {
                    transform = callback;
                },
                put: (url: string) =>
                    inertia.sent.push({
                        method: 'put',
                        url,
                        data: transform(data),
                    }),
                post: (
                    url: string,
                    options: { onSuccess?: () => void } = {},
                ) => {
                    inertia.sent.push({
                        method: 'post',
                        url,
                        data: transform(data),
                    });
                    options.onSuccess?.();
                },
            };
        },
    };
});

const LINK = (url: string) => ({ url, method: 'get' as const });

const complete = (): ProspectusInputs => ({
    purpose: 'Buy and install a solar cold room.',
    use_of_funds: [
        {
            label: 'Solar cold room',
            amount: '8476000',
            quote_amount: '8500000',
            quote_document_id: 'quote1',
        },
    ],
    forecast: [
        {
            month: '2026-12',
            revenue: '4000000',
            costs: '1000000',
            contract_document_id: null,
        },
        {
            month: '2027-01',
            revenue: '4000000',
            costs: '1000000',
            contract_document_id: null,
        },
    ],
    assumptions: 'Sales hold.',
    downside: 'Pause stock purchases.',
    profiles: [
        { name: 'Verified person 0', role: 'representative', text: 'Runs it.' },
    ],
    largest_customer_share_percent: 10,
    risks: [{ text: 'Power cuts.', concentration: false }],
});

const page = (
    overrides: Partial<BusinessProspectusProps> = {},
): BusinessProspectusProps => ({
    contract_version: 'business-prospectus-v1',
    server_time: '2026-11-10T09:00:00+00:00',
    sections: ['purpose', 'forecast', 'management', 'risks', 'declaration'],
    shell_links: {
        home: LINK('/business/home'),
        launcher: LINK('/launcher'),
        reports: null,
        market: null,
        profile: null,
    },
    application: { id: 'app', title: 'Cold room' },
    version: 3,
    inputs: complete(),
    signed: null,
    unavailable: null,
    facts: {
        principal: '10800000',
        term_months: 2,
        instalment: '1999800',
        verified_monthly_revenue: '4000000',
        latest_sealed_report_at: '2026-11-01T00:00:00+00:00',
        registered_names: ['Verified person 0'],
        required_people: [
            { name: 'Verified person 0', role: 'representative' },
        ],
        evidence: { quote1: 'quote', contract1: 'contract' },
        signed: false,
        platform_fee: '324000',
        net_of_fee: '10476000',
    },
    evaluation: {
        policy_version: 'brs-v3.0-pro-r03',
        gates: [
            { id: 'P-G01', section: 'purpose', passed: true, fix: null },
            {
                id: 'P-G04',
                section: 'purpose',
                passed: false,
                fix: 'Use-of-funds lines must add up to RWF 10,476,000.',
            },
            { id: 'P-G09', section: 'risks', passed: false, fix: null },
            {
                id: 'P-G12',
                section: 'declaration',
                passed: false,
                fix: 'Signing opens once every other gate passes.',
            },
        ],
        may_sign: false,
        publishable: false,
    },
    documents: [
        {
            id: 'quote1',
            kind: 'quote',
            filename: 'cold-room-quote.pdf',
            media_type: 'application/pdf',
            size_bytes: 2048,
            uploaded_at: '2026-11-10T08:00:00+00:00',
        },
        {
            id: 'contract1',
            kind: 'contract',
            filename: 'hotel-contract.png',
            media_type: 'image/png',
            size_bytes: 4096,
            uploaded_at: '2026-11-10T08:30:00+00:00',
        },
    ],
    actions: {
        save: { url: '/business/b/applications/app/prospectus', method: 'put' },
        upload: {
            url: '/business/b/applications/app/prospectus/documents',
            method: 'post',
        },
        sign: null,
    },
    links: { close: LINK('/business/home') },
    ...overrides,
});

const UUID = /^[0-9a-f-]{36}$/u;

beforeEach(() => {
    inertia.sent = [];
    inertia.errors = {};
    inertia.signErrors = {};
    inertia.uploadErrors = {};
    inertia.processing = false;
});

describe('forecastMonths', () => {
    it('keeps a forecast that already covers the term', () => {
        const months = complete().forecast;

        expect(forecastMonths(months, 2, '2026-11-10T09:00:00Z')).toBe(months);
    });

    it('lays out the term from the month after now, keeping months already written', () => {
        const written = complete().forecast.slice(1);

        expect(
            forecastMonths(written, 3, '2026-11-10T09:00:00Z').map((row) => [
                row.month,
                row.revenue,
            ]),
        ).toEqual([
            ['2026-12', ''],
            ['2027-01', '4000000'],
            ['2027-02', ''],
        ]);
    });
});

describe('Listing Builder', () => {
    it('shows the approved terms, every check and the fixes beside their sections', () => {
        render(<BusinessProspectus {...page()} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Listing Builder' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Draft · version 3')).toBeInTheDocument();
        expect(screen.getByText('RWF 10,800,000')).toBeInTheDocument();
        expect(screen.getByText('RWF 324,000')).toBeInTheDocument();
        expect(screen.getByText('2 months')).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Checks · 1 of 4 passed' }),
        ).toBeInTheDocument();

        const purpose = within(
            screen.getByRole('region', { name: 'Purpose and use of funds' }),
        );

        expect(
            purpose.getByText(
                'Use-of-funds lines must add up to RWF 10,476,000.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryAllByText(
                'Signing opens once every other gate passes.',
            ),
        ).toHaveLength(1);
        expect(
            screen.getByRole('button', { name: 'Sign and lock' }),
        ).toBeDisabled();
        expect(
            screen.getByText(
                'Signing opens for the official representative once every check passes.',
            ),
        ).toBeInTheDocument();
    });

    it('edits every part of the draft and saves it against the version read', async () => {
        const user = userEvent.setup();

        render(
            <BusinessProspectus
                {...page({
                    actions: {
                        save: {
                            url: '/business/b/applications/app/prospectus',
                            method: 'put',
                        },
                        upload: null,
                        sign: { url: '/sign', method: 'post' },
                    },
                })}
            />,
        );

        await user.clear(screen.getByLabelText('What the money is for'));
        await user.type(
            screen.getByLabelText('What the money is for'),
            'A cold room.',
        );
        await user.click(screen.getByRole('button', { name: 'Add a line' }));
        const items = screen.getAllByLabelText('Item');

        await user.type(items[1], 'Wiring');
        await user.type(
            screen.getAllByLabelText('Amount (RWF)')[1],
            '2,000,000',
        );
        const quotes = screen.getAllByLabelText('Quote or invoice (RWF)');

        await user.type(quotes[1], '2000000');
        await user.clear(quotes[1]);
        await user.selectOptions(
            screen.getAllByLabelText('Quote document')[1],
            'cold-room-quote.pdf',
        );
        await user.selectOptions(
            screen.getAllByLabelText('Quote document')[0],
            'No document',
        );
        await user.click(screen.getAllByRole('button', { name: 'Remove' })[0]);

        await user.clear(screen.getByLabelText('Revenue in 2026-12 (RWF)'));
        await user.type(
            screen.getByLabelText('Revenue in 2026-12 (RWF)'),
            '3900000',
        );
        await user.clear(screen.getByLabelText('Costs in 2026-12 (RWF)'));
        await user.type(
            screen.getByLabelText('Costs in 2026-12 (RWF)'),
            '900000',
        );
        await user.selectOptions(
            screen.getByLabelText('Signed contract for 2027-01'),
            'hotel-contract.png',
        );
        expect(
            within(
                screen.getByLabelText('Signed contract for 2026-12'),
            ).queryByText('cold-room-quote.pdf'),
        ).not.toBeInTheDocument();
        await user.type(screen.getByLabelText('Assumptions'), ' Steady.');
        await user.type(screen.getByLabelText('If sales fall short'), ' Cut.');

        await user.click(screen.getByRole('button', { name: 'Add a person' }));
        await user.type(
            screen.getAllByLabelText('Name as registered')[1],
            'Ann',
        );
        await user.selectOptions(screen.getAllByLabelText('Role')[1], 'owner');
        await user.type(screen.getByLabelText('About Ann'), 'Owns half.');
        await user.click(
            within(
                screen.getByRole('region', { name: 'Management' }),
            ).getAllByRole('button', { name: 'Remove' })[0],
        );

        const share = screen.getByLabelText(
            'Largest customer’s share of revenue (%)',
        );

        await user.clear(share);
        await user.clear(share);
        await user.type(share, '30');
        await user.click(screen.getByRole('button', { name: 'Add a risk' }));
        await user.type(screen.getByLabelText('Risk 2'), 'One buyer.');
        await user.click(screen.getAllByLabelText('Customer concentration')[1]);
        await user.click(
            within(
                screen.getByRole('region', { name: 'Risk factors' }),
            ).getAllByRole('button', { name: 'Remove' })[0],
        );

        await user.click(screen.getByRole('button', { name: 'Save draft' }));

        expect(inertia.sent).toHaveLength(1);
        expect(inertia.sent[0]).toMatchObject({
            method: 'put',
            url: '/business/b/applications/app/prospectus',
        });
        expect(inertia.sent[0].data).toEqual({
            purpose: 'A cold room.',
            use_of_funds: [
                {
                    label: 'Wiring',
                    amount: '2000000',
                    quote_amount: null,
                    quote_document_id: 'quote1',
                },
            ],
            forecast: [
                {
                    month: '2026-12',
                    revenue: '3900000',
                    costs: '900000',
                    contract_document_id: null,
                },
                {
                    month: '2027-01',
                    revenue: '4000000',
                    costs: '1000000',
                    contract_document_id: 'contract1',
                },
            ],
            assumptions: 'Sales hold. Steady.',
            downside: 'Pause stock purchases. Cut.',
            profiles: [{ name: 'Ann', role: 'owner', text: 'Owns half.' }],
            largest_customer_share_percent: 30,
            risks: [{ text: 'One buyer.', concentration: true }],
            request_id: expect.stringMatching(UUID),
            expected_version: 3,
        });
        expect(
            screen.getByText('Save your changes before signing.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Sign and lock' }),
        ).toBeDisabled();
    });

    it('lets the representative sign a passing draft once it is saved', async () => {
        const user = userEvent.setup();

        inertia.signErrors = { declaration: 'Fix every check before signing.' };
        render(
            <BusinessProspectus
                {...page({
                    actions: {
                        save: { url: '/save', method: 'put' },
                        upload: null,
                        sign: { url: '/sign', method: 'post' },
                    },
                })}
            />,
        );

        expect(
            screen.getByText(
                'Every check passes. The official representative can sign now.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Fix every check before signing.'),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Sign and lock' }));

        expect(inertia.sent).toEqual([
            {
                method: 'post',
                url: '/sign',
                data: {
                    request_id: expect.stringMatching(UUID),
                    expected_version: 3,
                },
            },
        ]);
    });

    it('shows a signed prospectus as locked and read-only', () => {
        render(
            <BusinessProspectus
                {...page({
                    signed: {
                        version: 4,
                        fingerprint: 'abcdef0123456789',
                        signed_at: '2026-11-10T09:00:00+00:00',
                    },
                    documents: [],
                    actions: { save: null, upload: null, sign: null },
                })}
            />,
        );

        expect(screen.getByText('Signed · version 4')).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Fingerprint abcdef012345…',
        );
        expect(
            screen.queryByRole('button', { name: 'Save draft' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Sign and lock' }),
        ).not.toBeInTheDocument();
        expect(screen.getByLabelText('What the money is for')).toBeDisabled();
        expect(
            screen.queryByRole('heading', { name: 'Quotes and contracts' }),
        ).not.toBeInTheDocument();

        fireEvent.submit(screen.getByRole('form', { name: 'Listing Builder' }));
        expect(inertia.sent).toEqual([]);
    });

    it('keeps drafting open while the approved terms cannot be read', () => {
        inertia.processing = true;
        inertia.errors = {
            purpose: 'Enter text.',
            largest_customer_share_percent: 'Too high.',
        };
        render(
            <BusinessProspectus
                {...page({
                    facts: null,
                    evaluation: null,
                    unavailable: 'REPORT_NOT_CURRENT',
                    inputs: {
                        ...complete(),
                        largest_customer_share_percent: null,
                    },
                    actions: {
                        save: { url: '/save', method: 'put' },
                        upload: null,
                        sign: { url: '/sign', method: 'post' },
                    },
                })}
            />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            'REPORT_NOT_CURRENT',
        );
        expect(
            screen.queryByRole('heading', { name: 'Your approved terms' }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('Enter text.')).toBeInTheDocument();
        expect(screen.getByText('Too high.')).toBeInTheDocument();
        expect(
            screen.getByLabelText('Largest customer’s share of revenue (%)'),
        ).toHaveValue('');
        expect(screen.getByRole('button', { name: 'Saving…' })).toBeDisabled();
        expect(screen.getByRole('button', { name: 'Signing…' })).toBeDisabled();
    });

    it('uploads a quote or contract, lists what is uploaded and clears the picker after', async () => {
        const user = userEvent.setup();
        const file = new File(['%PDF-1.7'], 'contract.pdf', {
            type: 'application/pdf',
        });

        render(<BusinessProspectus {...page()} />);

        const evidence = within(
            screen.getByRole('region', { name: 'Quotes and contracts' }),
        );

        expect(evidence.getByText('cold-room-quote.pdf')).toBeInTheDocument();
        expect(evidence.getByText(/^Signed contract · /u)).toBeInTheDocument();
        expect(evidence.getByRole('button', { name: 'Upload' })).toBeDisabled();
        await user.selectOptions(
            evidence.getByLabelText('Document type'),
            'contract',
        );
        await user.upload(evidence.getByLabelText('File'), file);
        await user.click(evidence.getByRole('button', { name: 'Upload' }));

        expect(inertia.sent).toEqual([
            {
                method: 'post',
                url: '/business/b/applications/app/prospectus/documents',
                data: {
                    kind: 'contract',
                    file,
                    request_id: expect.stringMatching(UUID),
                },
            },
        ]);
        expect(evidence.getByRole('button', { name: 'Upload' })).toBeDisabled();
        await user.selectOptions(
            evidence.getByLabelText('Document type'),
            'quote',
        );
        expect(evidence.getByLabelText('Document type')).toHaveValue('quote');
    });

    it('shows an upload refusal and progress, and says when nothing is uploaded yet', async () => {
        const user = userEvent.setup();

        inertia.uploadErrors = { file: 'Upload a PDF, PNG or JPEG.' };
        const { rerender } = render(
            <BusinessProspectus {...page({ documents: [] })} />,
        );

        expect(
            screen.getByText('No documents uploaded yet.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Upload a PDF, PNG or JPEG.'),
        ).toBeInTheDocument();
        await user.upload(
            screen.getByLabelText('File'),
            new File(['x'], 'x.pdf', { type: 'application/pdf' }),
        );
        await user.upload(screen.getByLabelText('File'), []);
        expect(screen.getByRole('button', { name: 'Upload' })).toBeDisabled();

        inertia.uploadErrors = {
            kind: 'Choose a quote or invoice, or a signed contract.',
        };
        inertia.processing = true;
        rerender(<BusinessProspectus {...page({ documents: [] })} />);
        expect(
            screen.getByText(
                'Choose a quote or invoice, or a signed contract.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Uploading…' }),
        ).toBeDisabled();
    });

    it('lists uploaded documents on a locked prospectus without offering uploads', () => {
        render(
            <BusinessProspectus
                {...page({ actions: { save: null, upload: null, sign: null } })}
            />,
        );

        expect(
            within(
                screen.getByRole('region', { name: 'Quotes and contracts' }),
            ).getByText('hotel-contract.png'),
        ).toBeInTheDocument();
        expect(screen.queryByLabelText('File')).not.toBeInTheDocument();
        fireEvent.submit(screen.getByRole('form', { name: 'Listing Builder' }));
        expect(inertia.sent).toEqual([]);
    });
});
