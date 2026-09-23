import { act, fireEvent, render, screen, within } from '@testing-library/react';
import { renderToString } from 'react-dom/server';
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
    CheckInStage,
    CountStage,
    LedgerStage,
    PhotosStage,
    SealStage,
    SealedStage,
    StatementsStage,
} from '@/types/auditor';
import checkInDone from '../../../resources/fixtures/ui/auditor-audit-check-in-done.json';
import checkIn from '../../../resources/fixtures/ui/auditor-audit-check-in.json';
import count from '../../../resources/fixtures/ui/auditor-audit-count.json';
import ledgerEmpty from '../../../resources/fixtures/ui/auditor-audit-ledger-empty.json';
import ledger from '../../../resources/fixtures/ui/auditor-audit-ledger.json';
import monthlySeal from '../../../resources/fixtures/ui/auditor-audit-monthly-seal.json';
import photosOffline from '../../../resources/fixtures/ui/auditor-audit-photos-offline.json';
import photos from '../../../resources/fixtures/ui/auditor-audit-photos.json';
import review from '../../../resources/fixtures/ui/auditor-audit-review.json';
import seal from '../../../resources/fixtures/ui/auditor-audit-seal.json';
import sealedMonthly from '../../../resources/fixtures/ui/auditor-audit-sealed-monthly.json';
import sealed from '../../../resources/fixtures/ui/auditor-audit-sealed.json';
import statementsUnavailable from '../../../resources/fixtures/ui/auditor-audit-statements-unavailable.json';
import statements from '../../../resources/fixtures/ui/auditor-audit-statements.json';
import { renderWithUser } from '../helpers/render-with-user';
import { inertia } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as AuditProcedureProps;

const withStage = <S extends AuditProcedureProps['stage']>(
    fixture: { props: unknown },
    change: (stage: S) => S,
): AuditProcedureProps => {
    const base = props(fixture);

    return { ...base, stage: change(base.stage as S) };
};

const sheet = (business = 'Huye Motors') =>
    screen.getByRole('dialog', { name: `${business} audit` });

const NOTE =
    'Six crates of stock were in the delivery van when I counted the store.';

beforeEach(() => inertia.reset());

afterEach(() => {
    vi.useRealTimers();
});

describe('Audit procedure — review and check-in', () => {
    it('opens the file review as step one of the Flash procedure and commits it', async () => {
        const { user } = renderWithUser(<AuditorAudit {...props(review)} />);
        const dialog = sheet();

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Huye Motors · audit',
        );
        expect(
            within(dialog).getByText('Field Flash Audit'),
        ).toBeInTheDocument();
        expect(within(dialog).getByRole('timer')).toHaveTextContent(
            /20:59:5\d/,
        );
        expect(
            within(dialog).getByRole('listitem', { current: 'step' }),
        ).toHaveTextContent('Review');
        expect(
            within(dialog).getByText('Review the application'),
        ).toBeInTheDocument();
        expect(
            within(dialog).queryByRole('link', { name: 'Back' }),
        ).toHaveAttribute('href', '/preview/auditor-jobs');
        expect(
            screen.getAllByRole('link', { name: 'Close' })[0],
        ).toHaveAttribute('href', '/preview/auditor-jobs');

        await user.click(
            within(dialog).getByRole('button', { name: 'Continue' }),
        );

        expect(inertia.posts[0]).toMatchObject({
            url: '/preview/auditor-jobs',
            data: { step: 'review', revision: 7 },
        });

        await user.click(
            within(dialog).getByRole('button', { name: 'Declare a conflict' }),
        );
        expect(
            screen.getByRole('dialog', {
                name: 'Declare an interest in Huye Motors',
            }),
        ).toBeInTheDocument();
    });

    it('waits for the capture app to record the check-in', () => {
        render(<AuditorAudit {...props(checkIn)} />);
        const dialog = sheet();

        expect(
            within(dialog).getByText('Waiting for your check-in'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('Capture app not opened on this job yet'),
        ).toBeInTheDocument();
        expect(within(dialog).queryByText(/Last sync/)).not.toBeInTheDocument();
        expect(
            within(dialog).getByRole('link', {
                name: 'Check in with the capture app',
            }),
        ).toHaveAttribute('href', 'rozine-capture://assignment/fa_huye');
        expect(
            within(dialog).getByRole('button', { name: 'Continue' }),
        ).toBeDisabled();
        expect(
            within(dialog).getByText(
                'Check in on site in the capture app to continue.',
            ),
        ).toBeInTheDocument();
    });

    it('shows a recorded check-in, its position and a review flag', () => {
        render(
            <AuditorAudit
                {...withStage<CheckInStage>(checkInDone, (stage) => ({
                    ...stage,
                    check_in: {
                        ...stage.check_in,
                        review: true,
                    } as CheckInStage['check_in'],
                }))}
            />,
        );
        const dialog = sheet();

        expect(
            within(dialog).getByText('Checked in on site · 18:02'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('-1.9441, 30.0619 · ±12 m'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText(
                'Capturing on your phone · 1 of 4 received',
            ),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('Last sync 8m ago'),
        ).toBeInTheDocument();
        expect(within(dialog).getByRole('note')).toHaveTextContent(
            'This check-in goes to review',
        );
        expect(
            within(dialog).getByRole('link', { name: 'Open the capture app' }),
        ).toBeInTheDocument();
    });

    it('says when the browser drops offline and returns', () => {
        const online = vi
            .spyOn(navigator, 'onLine', 'get')
            .mockReturnValue(false);

        render(<AuditorAudit {...props(checkInDone)} />);
        act(() => {
            window.dispatchEvent(new Event('offline'));
        });

        expect(screen.getByText(/You're offline/)).toHaveAttribute(
            'role',
            'status',
        );

        online.mockReturnValue(true);
        act(() => {
            window.dispatchEvent(new Event('online'));
        });
        expect(screen.queryByText(/You're offline/)).not.toBeInTheDocument();
    });
});

describe('Audit procedure — photos', () => {
    it('shows captured and missing shots, and saves titles for the extras', async () => {
        const { user } = renderWithUser(
            <AuditorAudit {...props(photos)} can_continue />,
        );
        const dialog = sheet();

        expect(
            within(dialog).getByText(/3 required · 2 captured/),
        ).toBeInTheDocument();
        expect(within(dialog).getAllByText('Captured')).toHaveLength(3);
        expect(
            within(dialog).getByText('Not captured yet'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('Syncing · 4 of 5 received'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByRole('link', { name: /Add photo/ }),
        ).toHaveAttribute('href', 'rozine-capture://assignment/fa_huye');

        const title = within(dialog).getByLabelText('Extra 1');

        expect(title).toHaveValue('Cold room #2 at capacity');
        expect(within(dialog).getByText('26 left')).toBeInTheDocument();

        await user.clear(title);
        await user.type(title, 'Loading bay');
        expect(within(dialog).getByText('39 left')).toBeInTheDocument();

        await user.click(
            within(dialog).getByRole('button', { name: 'Continue' }),
        );
        expect(inertia.posts[0]).toMatchObject({
            data: { step: 'photos', titles: { 'extra-1': 'Loading bay' } },
        });
    });

    it('draws synced thumbnails, untitled extras and a monthly three-column grid', () => {
        const base = withStage<PhotosStage>(photos, (stage) => ({
            ...stage,
            slots: stage.slots.map((slot) => ({
                ...slot,
                thumbnail_url: slot.captured_at
                    ? `/thumb/${slot.key}.jpg`
                    : null,
                position: slot.extra ? null : slot.position,
                title: '',
            })),
        }));

        render(
            <AuditorAudit
                {...base}
                audit={{ ...base.audit, kind: 'monthly', month: '2026-09-01' }}
            />,
        );

        expect(screen.getByRole('img', { name: 'Storefront' })).toHaveAttribute(
            'src',
            '/thumb/storefront.jpg',
        );
        expect(
            screen.getByRole('img', { name: 'Untitled' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('list', { name: 'Site photos' })).toHaveClass(
            'grid-cols-3',
        );
    });

    it('explains a capture package that needs attention', () => {
        render(<AuditorAudit {...props(photosOffline)} />);

        expect(
            screen.getByText(
                'No signal — the capture app keeps everything encrypted and syncs when you reconnect',
            ),
        ).toBeInTheDocument();
    });

    it.each([
        [
            'storage_full',
            "Your phone's storage is full — free space so the capture app can keep capturing",
        ],
        [null, 'Upload failed — open the capture app to retry'],
    ] as const)('names the attention reason %s', (attention, text) => {
        render(
            <AuditorAudit
                {...withStage<PhotosStage>(photosOffline, (stage) => ({
                    ...stage,
                    package: { ...stage.package, attention },
                }))}
            />,
        );

        expect(screen.getByText(text)).toBeInTheDocument();
    });

    it('reports a complete package', () => {
        render(
            <AuditorAudit
                {...withStage<PhotosStage>(photos, (stage) => ({
                    ...stage,
                    package: { ...stage.package, status: 'complete' },
                }))}
            />,
        );

        expect(screen.getByText('All 5 items received')).toBeInTheDocument();
    });
});

describe('Audit procedure — ledger reconciliation', () => {
    it('asks the server for the variance once typing settles', () => {
        vi.useFakeTimers();
        render(<AuditorAudit {...props(ledger)} />);
        const dialog = sheet();
        const input = within(dialog).getByLabelText(
            'Observed on site · stock value (RWF)',
        );

        expect(within(dialog).getByText('RWF 38,000,000')).toBeInTheDocument();
        expect(input).toHaveValue('36,400,000');
        expect(
            within(dialog).getByText('Variance exceeds tolerance'),
        ).toBeInTheDocument();
        expect(within(dialog).getByText('-4.2%')).toBeInTheDocument();
        expect(
            within(dialog).getByText(/a variance over RWF 0 is flagged/),
        ).toBeInTheDocument();

        fireEvent.change(input, { target: { value: '37,1a00,000' } });
        expect(input).toHaveValue('37,100,000');
        expect(inertia.reloads).toHaveLength(0);

        act(() => {
            vi.advanceTimersByTime(450);
        });

        expect(inertia.reloads[0]).toEqual({
            only: ['stage', 'can_continue', 'hint'],
            data: { observed_stock: '37100000' },
        });
        expect(inertia.poll.stop).toHaveBeenCalled();
    });

    it('lists parsed and rejected ledger documents and uploads a re-scan', async () => {
        const { user } = renderWithUser(<AuditorAudit {...props(ledger)} />);
        const dialog = sheet();
        const file = new File(['%PDF'], 'receipts.pdf', {
            type: 'application/pdf',
        });
        const input = within(dialog).getByLabelText('Ledger document file');
        const click = vi.spyOn(input, 'click');

        expect(within(dialog).getByText('1 of 2 accepted')).toBeInTheDocument();
        expect(
            within(dialog).getByText('Accepted · PDF · 2.4 MB'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('Ledger entries read'),
        ).toBeInTheDocument();
        expect(within(dialog).getByText('612')).toBeInTheDocument();
        expect(
            within(dialog).getByText('Rejected · Scan · 1.1 MB'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText(/OCR confidence 41%/),
        ).toBeInTheDocument();

        await user.click(
            within(dialog).getByRole('button', {
                name: 'Re-scan this document',
            }),
        );
        expect(click).toHaveBeenCalled();
        await user.upload(input, file);

        expect(inertia.posts[0]).toMatchObject({
            url: '/preview/auditor-audit-ledger',
            data: { document: file, replaces: 'ld_2', revision: 7 },
            options: { forceFormData: true },
        });

        await user.click(
            within(dialog).getByRole('button', {
                name: /Add another ledger document/,
            }),
        );
        await user.upload(input, file);
        expect(inertia.posts[1].data).toMatchObject({ replaces: null });

        fireEvent.change(input, { target: { files: [] } });
        expect(inertia.posts).toHaveLength(2);
    });

    it('records whether the ledgers reconcile and commits the step', async () => {
        const { user } = renderWithUser(<AuditorAudit {...props(ledger)} />);
        const dialog = sheet();
        const tick = within(dialog).getByRole('checkbox', {
            name: /reconcile with the digital statements/,
        });

        expect(tick).toBeChecked();
        await user.click(tick);
        expect(tick).not.toBeChecked();
        await user.click(tick);
        await user.click(tick);

        await user.click(
            within(dialog).getByRole('button', { name: 'Review & seal' }),
        );
        expect(inertia.posts[0].data).toEqual({
            observed_stock: '36400000',
            reconciled: false,
            step: 'ledger',
            revision: 7,
        });
        expect(
            screen.queryByRole('link', { name: 'Close' }),
        ).not.toBeInTheDocument();
    });

    it('keeps polling while a document is still being read', () => {
        render(
            <AuditorAudit
                {...withStage<LedgerStage>(ledger, (stage) => ({
                    ...stage,
                    documents: [
                        {
                            id: 'ld_9',
                            name: 'Cash-book.pdf',
                            detail: 'PDF · 0.8 MB',
                            state: 'scanning',
                            fields: [],
                            failure: null,
                        },
                    ],
                }))}
            />,
        );

        expect(
            screen.getByText('OCR parsing … PDF · 0.8 MB'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('progressbar', { name: 'Reading the document' }),
        ).toBeInTheDocument();
        expect(screen.getByText('0 of 1 accepted')).toBeInTheDocument();
        expect(inertia.poll.start).toHaveBeenCalled();
    });

    it('starts empty, blocks the reconciliation tick and shows field errors', () => {
        inertia.errors = { observed_stock: 'Enter the value you counted.' };
        render(<AuditorAudit {...props(ledgerEmpty)} />);
        const dialog = sheet();

        expect(within(dialog).getByText('None attached')).toBeInTheDocument();
        expect(
            within(dialog).getByRole('button', {
                name: /Attach ledger document/,
            }),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByRole('checkbox', { name: /reconcile/ }),
        ).toBeDisabled();
        expect(
            within(dialog).getByText(
                'Attach and pass at least one ledger document first.',
            ),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('Enter the value you counted.'),
        ).toBeInTheDocument();
        expect(within(dialog).queryByText(/tolerance/)).not.toBeInTheDocument();
    });

    it('shows a variance inside tolerance', () => {
        render(
            <AuditorAudit
                {...withStage<LedgerStage>(ledger, (stage) => ({
                    ...stage,
                    variance: { pct: '0.0', within: true },
                }))}
            />,
        );

        expect(screen.getByText('Within tolerance')).toBeInTheDocument();
    });
});

describe('Audit procedure — monthly statements and count', () => {
    it('reads the month from the statements on file', () => {
        render(<AuditorAudit {...props(statements)} />);
        const dialog = sheet('GreenLeaf Agro');

        expect(
            within(dialog).getByText('Monthly report · audit'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByRole('heading', {
                name: 'GreenLeaf Agro · September 2026',
            }),
        ).toBeInTheDocument();
        expect(within(dialog).queryByRole('timer')).not.toBeInTheDocument();
        expect(within(dialog).getByText('RWF 162M')).toBeInTheDocument();
        expect(within(dialog).getByText('RWF 33.6M')).toHaveClass(
            'text-rz-positive',
        );
        expect(within(dialog).getByText('1.31×')).toHaveClass(
            'text-rz-positive',
        );
        expect(
            within(dialog).getByRole('link', {
                name: /GreenLeaf-Agro-2026-09.pdf/,
            }),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByRole('button', { name: 'Start the count' }),
        ).toBeEnabled();
    });

    it.each([
        ['watch', 'text-rz-ink'],
        ['below', 'text-[#d0342c]'],
    ] as const)('colours a %s liquidity cover', (band, tone) => {
        render(
            <AuditorAudit
                {...withStage<StatementsStage>(statements, (stage) => ({
                    ...stage,
                    statements: {
                        ...(stage.statements as Extract<
                            StatementsStage['statements'],
                            { status: 'available' }
                        >),
                        cover: { value: '0.9', band },
                    },
                }))}
            />,
        );

        expect(screen.getByText('0.9×')).toHaveClass(tone);
    });

    it('says when the statements are not on file yet', () => {
        render(<AuditorAudit {...props(statementsUnavailable)} />);

        expect(screen.getByRole('note')).toHaveTextContent(
            "Statements for this period aren't on file yet",
        );
        expect(
            screen.getByRole('button', { name: 'Start the count' }),
        ).toBeDisabled();
    });

    it('records the proof seen, the counts and the operational status', async () => {
        vi.useFakeTimers();
        render(<AuditorAudit {...props(count)} can_continue />);
        const dialog = sheet('GreenLeaf Agro');

        expect(
            within(dialog).getByRole('checkbox', { name: /Bank statement/ }),
        ).toBeChecked();
        fireEvent.click(
            within(dialog).getByRole('checkbox', { name: /Bank statement/ }),
        );
        fireEvent.click(
            within(dialog).getByRole('checkbox', {
                name: /POs & delivery receipts/,
            }),
        );
        fireEvent.click(
            within(dialog).getByRole('checkbox', {
                name: /Supplier delivery notes/,
            }),
        );
        fireEvent.click(
            within(dialog).getByRole('radio', { name: 'Suspended' }),
        );
        expect(
            within(dialog).getByRole('radio', { name: 'Suspended' }),
        ).toBeChecked();

        expect(
            within(dialog).getByText(
                'Statements show RWF 1,210,000 · tolerance RWF 0',
            ),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText(
                'Reported stock 190 units · tolerance RWF 0',
            ),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('1 Sept – 30 Sept'),
        ).toBeInTheDocument();
        expect(within(dialog).getByText('-2.1%')).toBeInTheDocument();
        expect(
            within(dialog).getByText('FMCG / Perishables'),
        ).toBeInTheDocument();

        fireEvent.change(
            within(dialog).getByLabelText('Observed on site · cash (RWF)'),
            {
                target: { value: '1,200,000' },
            },
        );
        fireEvent.change(
            within(dialog).getByLabelText('Observed on site · stock (units)'),
            {
                target: { value: '188' },
            },
        );
        act(() => {
            vi.advanceTimersByTime(450);
        });
        expect(inertia.reloads.at(-1)).toMatchObject({
            data: { cash: '1200000', stock_units: '188' },
        });

        fireEvent.click(
            within(dialog).getByRole('button', { name: 'Continue to photos' }),
        );
        expect(inertia.posts[0].data).toEqual({
            financial_proofs: ['momo', 'po'],
            inventory_proofs: ['photo', 'delivery'],
            cash: '1200000',
            stock_units: '188',
            operational_status: 'suspended',
            step: 'count',
            revision: 3,
        });
    });

    it('starts a count with no baseline, no counts and no status', () => {
        render(
            <AuditorAudit
                {...withStage<CountStage>(count, (stage) => ({
                    ...stage,
                    cash: { observed: null, statement: null, variance: null },
                    stock: {
                        observed_units: null,
                        reported_units: null,
                        variance: null,
                    },
                    operational_status: null,
                }))}
            />,
        );

        expect(
            screen.getByText('No statement balance on file for this period.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('No reported stock baseline for this period.'),
        ).toBeInTheDocument();
        expect(screen.getAllByText('—')).toHaveLength(2);
        expect(
            screen.getByLabelText('Observed on site · cash (RWF)'),
        ).toHaveValue('');
        expect(screen.queryAllByRole('radio', { checked: true })).toHaveLength(
            0,
        );
    });
});

describe('Audit procedure — seal', () => {
    it('needs a note before the findings can be previewed, then seals with the PIN', async () => {
        const { user } = renderWithUser(<AuditorAudit {...props(seal)} />);
        const dialog = sheet();
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
            within(dialog).queryByRole('button', { name: 'Suggest changes' }),
        ).not.toBeInTheDocument();

        await user.click(within(dialog).getByLabelText('Assessment note'));
        await user.paste(NOTE.slice(0, 100));
        expect(within(dialog).getByText('Required · done')).toBeInTheDocument();
        expect(
            within(dialog).getByText(`${NOTE.slice(0, 100).length} / 100`),
        ).toBeInTheDocument();

        await user.click(preview);

        const findings = screen.getByRole('dialog', { name: 'Huye Motors' });

        expect(
            within(findings).getByText('Factual findings · MVP-AUP-1'),
        ).toBeInTheDocument();
        expect(
            within(findings).getByText(
                'Scope of engagement & agreed-upon procedures',
            ),
        ).toBeInTheDocument();
        expect(within(findings).getByText(/sha256:9f2c/)).toBeInTheDocument();

        await user.click(
            within(findings).getByRole('button', {
                name: 'Apply ICPAR licence seal',
            }),
        );
        expect(
            within(findings).getByText(
                'Enter your Rozine PIN to apply licence ICPAR/P-2026/0481.',
            ),
        ).toBeInTheDocument();

        const submit = within(findings).getByRole('button', {
            name: 'Seal & submit to Rozine',
        });

        expect(submit).toBeDisabled();

        for (const digit of ['4', '8', '2', '9', '7']) {
            await user.click(
                within(findings).getByRole('button', { name: digit }),
            );
        }

        expect(
            within(findings).getByRole('img', {
                name: '4 of 4 digits entered',
            }),
        ).toBeInTheDocument();
        await user.click(
            within(findings).getByRole('button', { name: 'Delete digit' }),
        );
        expect(
            within(findings).getByRole('img', {
                name: '3 of 4 digits entered',
            }),
        ).toBeInTheDocument();
        await user.click(within(findings).getByRole('button', { name: '1' }));
        await user.click(submit);

        expect(inertia.posts[0]).toMatchObject({
            url: '/preview/auditor-audit-sealed',
            data: {
                note: NOTE.slice(0, 100),
                pin: '4821',
                revision: 7,
                digest: 'sha256:9f2c4e71b0a85d3e6c1f47a2d9b83e05c6a1f24d7e98b3c05a6d1e2f47b8c9d0',
            },
        });

        act(() => inertia.posts[0].options.onError?.());
        expect(
            within(findings).getByRole('img', {
                name: '0 of 4 digits entered',
            }),
        ).toBeInTheDocument();

        await user.click(
            within(findings).getByRole('button', { name: 'Close' }),
        );
        expect(
            screen.queryByRole('dialog', { name: 'Huye Motors' }),
        ).not.toBeInTheDocument();
    });

    it('shows server errors on the note and the PIN', async () => {
        inertia.errors = {
            note: 'Say what you saw.',
            pin: 'That PIN is not right.',
        };
        const base = withStage<SealStage>(seal, (stage) => ({
            ...stage,
            note: { ...stage.note, required: false },
        }));
        const { user } = renderWithUser(<AuditorAudit {...base} />);

        expect(screen.getByText('Optional')).toBeInTheDocument();
        expect(screen.getByText('Say what you saw.')).toBeInTheDocument();
        await user.click(
            screen.getByRole('button', { name: 'Preview findings' }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Apply ICPAR licence seal' }),
        );
        expect(screen.getByText('That PIN is not right.')).toBeInTheDocument();
    });

    it('keeps the preview closed while earlier steps are incomplete, and still offers a conflict', async () => {
        const { user } = renderWithUser(
            <AuditorAudit
                {...withStage<SealStage>(seal, (stage) => ({
                    ...stage,
                    note: { ...stage.note, required: false },
                }))}
                can_continue={false}
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Preview findings' }),
        ).toBeDisabled();
        await user.click(
            within(sheet()).getByRole('button', { name: 'Declare a conflict' }),
        );
        expect(
            screen.getByRole('dialog', {
                name: 'Declare an interest in Huye Motors',
            }),
        ).toBeInTheDocument();
    });

    it('sends a monthly report back or rejects it with a recorded reason', async () => {
        const { user } = renderWithUser(
            <AuditorAudit {...props(monthlySeal)} />,
        );
        const dialog = sheet('GreenLeaf Agro');

        expect(within(dialog).getByText('Required · done')).toBeInTheDocument();

        await user.click(
            within(dialog).getByRole('button', { name: 'Suggest changes' }),
        );
        let reason = screen.getByRole('dialog', { name: 'Suggest changes' });

        await user.type(
            within(reason).getByLabelText('Reason'),
            'Sign the count',
        );
        await user.click(
            within(reason).getByRole('button', { name: 'Send back' }),
        );
        expect(inertia.posts[0]).toMatchObject({
            url: '/preview/auditor-jobs',
            data: { reason: 'Sign the count', revision: 3 },
        });
        await user.click(
            within(reason).getByRole('button', { name: 'Cancel' }),
        );

        await user.click(
            within(dialog).getByRole('button', { name: 'Reject & flag' }),
        );
        reason = screen.getByRole('dialog', { name: 'Reject & flag' });
        expect(
            within(reason).getByRole('button', { name: 'Reject report' }),
        ).toHaveClass('bg-[#c0392b]');
        await user.click(
            within(reason).getByRole('button', { name: 'Cancel' }),
        );

        await user.click(
            within(dialog).getByRole('button', { name: 'Preview findings' }),
        );
        expect(
            screen.getByRole('dialog', {
                name: 'GreenLeaf Agro · September 2026',
            }),
        ).toBeInTheDocument();
    });

    it('renders on the server as online, without a banner', () => {
        const view = renderToString(<AuditorAudit {...props(review)} />);

        expect(view).toContain('Review the application');
        expect(view).not.toContain('offline');
    });

    it('stops listening for the connection when the sheet closes', () => {
        const remove = vi.spyOn(window, 'removeEventListener');
        const { unmount } = render(<AuditorAudit {...props(monthlySeal)} />);

        unmount();
        expect(remove).toHaveBeenCalledWith('offline', expect.any(Function));
    });
});

describe('Audit procedure — after the seal', () => {
    it('shows the filing timeline while the business co-signs', async () => {
        const { user } = renderWithUser(<AuditorAudit {...props(sealed)} />);

        await user.click(screen.getByRole('button', { name: 'Done' }));

        const dialog = sheet();
        const timeline = within(dialog).getByRole('list', {
            name: 'Filing progress',
        });

        expect(
            within(dialog).getByText('Sealed and filed'),
        ).toBeInTheDocument();
        expect(
            within(timeline).getByText('3 Oct 2026 · 18:40'),
        ).toBeInTheDocument();
        expect(within(timeline).getByText('Waiting')).toBeInTheDocument();
        expect(within(timeline).getByText('—')).toBeInTheDocument();
        expect(
            within(dialog).getByText(
                /Sealed under licence ICPAR\/P-2026\/0481/,
            ),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByRole('link', { name: 'Back to jobs' }),
        ).toHaveAttribute('href', '/preview/auditor-jobs');
        expect(
            within(dialog).queryByRole('button', {
                name: 'Declare a conflict',
            }),
        ).not.toBeInTheDocument();
        expect(
            within(dialog).queryByRole('link', { name: /amendment/ }),
        ).not.toBeInTheDocument();
    });

    it('shows a co-signed, published monthly report with its amendment route', () => {
        render(<AuditorAudit {...props(sealedMonthly)} />);

        expect(screen.getByText('3 Oct 2026 · 11:20')).toBeInTheDocument();
        expect(screen.getByText('3 Oct 2026 · 11:21')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Start a linked amendment' }),
        ).toBeInTheDocument();
    });

    it.each([
        ['declined', 'Disputed'],
        ['overdue', 'Overdue'],
    ] as const)('labels a %s co-signature', (state, label) => {
        render(
            <AuditorAudit
                {...withStage<SealedStage>(sealed, (stage) => ({
                    ...stage,
                    cosign: { ...stage.cosign, state },
                }))}
                outcome={null}
            />,
        );

        expect(screen.getByText(label)).toBeInTheDocument();
    });
});
