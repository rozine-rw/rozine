import {
    act,
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
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
    SealedStage,
    StatementsStage,
} from '@/types/auditor';
import amendment from '../../../resources/fixtures/ui/auditor-audit-amendment.json';
import checkInDone from '../../../resources/fixtures/ui/auditor-audit-check-in-done.json';
import checkIn from '../../../resources/fixtures/ui/auditor-audit-check-in.json';
import blocked from '../../../resources/fixtures/ui/auditor-audit-conflict-blocked.json';
import count from '../../../resources/fixtures/ui/auditor-audit-count.json';
import ledgerEmpty from '../../../resources/fixtures/ui/auditor-audit-ledger-empty.json';
import ledgerIngested from '../../../resources/fixtures/ui/auditor-audit-ledger-ingested.json';
import ledger from '../../../resources/fixtures/ui/auditor-audit-ledger.json';
import monthlySeal from '../../../resources/fixtures/ui/auditor-audit-monthly-seal.json';
import photosOffline from '../../../resources/fixtures/ui/auditor-audit-photos-offline.json';
import photosStorageFull from '../../../resources/fixtures/ui/auditor-audit-photos-storage-full.json';
import photosUploadFailed from '../../../resources/fixtures/ui/auditor-audit-photos-upload-failed.json';
import photos from '../../../resources/fixtures/ui/auditor-audit-photos.json';
import review from '../../../resources/fixtures/ui/auditor-audit-review.json';
import sealedMonthly from '../../../resources/fixtures/ui/auditor-audit-sealed-monthly.json';
import sealed from '../../../resources/fixtures/ui/auditor-audit-sealed.json';
import statementsUnavailable from '../../../resources/fixtures/ui/auditor-audit-statements-unavailable.json';
import statements from '../../../resources/fixtures/ui/auditor-audit-statements.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, inertia, invalid, operation } from './inertia';

vi.mock('@inertiajs/react', () => import('./inertia'));

vi.setConfig({ testTimeout: 30_000 });

const HANDOFF = { url: 'https://capture.example/handoff/fa_huye' };

const COMMAND = {
    audit_id: 'fa_huye',
    expected_revision: 7,
    identity_context_revision: 3,
    request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u),
};

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

        inertia.queue.push(
            answers(
                operation({
                    data: {
                        next: {
                            url: '/preview/auditor-audit-check-in',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        await user.click(
            within(dialog).getByRole('button', { name: 'Continue' }),
        );

        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-jobs',
            method: 'post',
            body: { ...COMMAND, step: 'review' },
        });
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-audit-check-in' },
            ]),
        );

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
        expect(within(dialog).getByRole('note')).toHaveTextContent(
            "The capture app isn't available for this assignment yet",
        );
        expect(
            within(dialog).queryByRole('link', {
                name: 'Check in with the capture app',
            }),
        ).not.toBeInTheDocument();
        expect(
            within(dialog).getByRole('button', { name: 'Continue' }),
        ).toBeDisabled();
        expect(
            within(dialog).getByText(
                'Check in on site in the capture app to continue.',
            ),
        ).toBeInTheDocument();
    });

    it('hands off to the capture app through the server’s link when there is one', () => {
        render(
            <AuditorAudit
                {...withStage<CheckInStage>(checkIn, (stage) => ({
                    ...stage,
                    package: { ...stage.package, handoff: HANDOFF },
                }))}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'Check in with the capture app' }),
        ).toHaveAttribute('href', HANDOFF.url);
    });

    it('shows a recorded check-in, its position, its evidence and a review flag', () => {
        render(
            <AuditorAudit
                {...withStage<CheckInStage>(checkInDone, (stage) => ({
                    ...stage,
                    package: { ...stage.package, handoff: HANDOFF },
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

        const evidence = within(dialog).getByRole('list', { name: 'Evidence' });

        expect(
            within(evidence).getByText('On-site check-in'),
        ).toBeInTheDocument();
        expect(
            within(evidence).getByText('ev_huye_checkin'),
        ).toBeInTheDocument();
        expect(within(evidence).getByText('Capture app')).toBeInTheDocument();
        expect(
            within(evidence).getByText('Device attestation'),
        ).toBeInTheDocument();
        expect(within(evidence).getByText('Unavailable')).toBeInTheDocument();
        expect(within(evidence).getByText('±12 m')).toBeInTheDocument();
        expect(
            within(evidence).getByText('3 Oct 2026 · 18:02'),
        ).toBeInTheDocument();
    });

    it.each([
        [{ position: null, accuracy_m: null }, 'Position unavailable'],
        [{ accuracy_m: null }, '-1.9441, 30.0619'],
    ])(
        'reads a check-in position the capture could not fully supply',
        (gap, label) => {
            render(
                <AuditorAudit
                    {...withStage<CheckInStage>(checkInDone, (stage) => {
                        const recorded = stage.check_in as Extract<
                            CheckInStage['check_in'],
                            { state: 'recorded' }
                        >;

                        return {
                            ...stage,
                            check_in: {
                                ...recorded,
                                evidence: { ...recorded.evidence, ...gap },
                            },
                        };
                    })}
                />,
            );

            expect(screen.getAllByText(label)[0]).toBeInTheDocument();
        },
    );

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
            <AuditorAudit
                {...withStage<PhotosStage>(photos, (stage) => ({
                    ...stage,
                    package: { ...stage.package, handoff: HANDOFF },
                }))}
                can_continue
            />,
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
        ).toHaveAttribute('href', HANDOFF.url);

        const title = within(dialog).getByLabelText('Extra 1');

        expect(title).toHaveValue('Cold room #2 at capacity');
        expect(within(dialog).getByText('26 left')).toBeInTheDocument();

        await user.clear(title);
        await user.type(title, 'Loading bay');
        expect(within(dialog).getByText('39 left')).toBeInTheDocument();

        await user.click(
            within(dialog).getByRole('button', { name: 'Continue' }),
        );
        expect(inertia.calls[0].body).toEqual({
            ...COMMAND,
            step: 'photos',
            titles: { 'extra-1': 'Loading bay' },
        });
    });

    it('offers no capture of its own when the capture app is unavailable', () => {
        render(<AuditorAudit {...props(photos)} />);

        expect(
            screen.queryByRole('link', { name: /Add photo/ }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('Capture unavailable')).toBeInTheDocument();
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

    it.each([
        [
            photosOffline,
            'No signal — the capture app keeps everything encrypted and syncs when you reconnect',
        ],
        [
            photosStorageFull,
            "Your phone's storage is full — free space so the capture app can keep capturing",
        ],
        [photosUploadFailed, 'Upload failed — open the capture app to retry'],
    ])(
        'explains what the companion reports about its package: %#',
        (fixture, text) => {
            render(<AuditorAudit {...props(fixture)} />);

            expect(screen.getByText(text)).toBeInTheDocument();
        },
    );

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
            data: { document: file, replaces: 'ld_2', ...COMMAND },
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
        expect(inertia.calls[0].body).toEqual({
            ...COMMAND,
            observed_stock: '36400000',
            reconciled: false,
            step: 'ledger',
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
                            ingestion: null,
                            evidence: null,
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

    it('shows a received document as not yet reviewed, never approved', () => {
        render(<AuditorAudit {...props(ledgerIngested)} />);

        expect(screen.getAllByText('Received · not yet reviewed')).toHaveLength(
            2,
        );
        expect(screen.queryByText(/approved/i)).not.toBeInTheDocument();
    });

    it('shows the server’s field errors after a save', async () => {
        inertia.queue.push(
            invalid({ observed_stock: 'Enter the value you counted.' }),
        );
        const { user } = renderWithUser(<AuditorAudit {...props(ledger)} />);

        await user.click(screen.getByRole('button', { name: 'Review & seal' }));

        expect(
            await screen.findByText('Enter the value you counted.'),
        ).toBeInTheDocument();
        expect(
            screen.getByLabelText('Observed on site · stock value (RWF)'),
        ).toHaveAttribute('aria-invalid', 'true');
    });

    it('starts empty and blocks the reconciliation tick', () => {
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
        expect(inertia.calls[0].body).toEqual({
            financial_proofs: ['momo', 'po'],
            inventory_proofs: ['photo', 'delivery'],
            cash: '1200000',
            stock_units: '188',
            operational_status: 'suspended',
            step: 'count',
            audit_id: 'mr_greenleaf',
            expected_revision: 3,
            identity_context_revision: 3,
            request_id: expect.any(String),
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

describe('Audit procedure — connection', () => {
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
            within(dialog).queryByRole('button', { name: /amendment/ }),
        ).not.toBeInTheDocument();
        expect(
            within(dialog).getByText('rpt_01J9Q3W7K9V5D1'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('sig_01J9Q3W7M4X2T8'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('key_icpar_p2026_0481_v1'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText(
                'The report is sealed and can no longer be edited. Huye Motors co-signs by 7 Oct 2026; it publishes to holders after that.',
            ),
        ).toBeInTheDocument();
    });

    it('starts a linked amendment of a published monthly report as a command', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'AUDIT_AMENDMENT_CREATED',
                    data: {
                        next: {
                            url: '/preview/auditor-audit-amendment',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(
            <AuditorAudit {...props(sealedMonthly)} />,
        );

        expect(screen.getByText('3 Oct 2026 · 11:20')).toBeInTheDocument();
        expect(screen.getByText('3 Oct 2026 · 11:21')).toBeInTheDocument();

        const amend = screen.getByRole('button', {
            name: 'Start a linked amendment',
        });

        await user.click(amend);
        expect(inertia.calls[0]).toMatchObject({
            url: '/preview/auditor-audit-amendment',
            body: { audit_id: 'mr_greenleaf', expected_revision: 3 },
        });
        await waitFor(() =>
            expect(inertia.visits).toEqual([
                { url: '/preview/auditor-audit-amendment' },
            ]),
        );
    });

    it('says an amended report stays as sealed and links its amendment', () => {
        render(
            <AuditorAudit
                {...withStage<SealedStage>(sealedMonthly, (stage) => ({
                    ...stage,
                    amended_by: {
                        report_id: 'rpt_02',
                        link: {
                            url: '/preview/auditor-audit-amendment',
                            method: 'get',
                        },
                    },
                }))}
                allowed_actions={[]}
            />,
        );

        expect(
            screen.getByText(
                /Report rpt_02 amends this one; this report stays as sealed./u,
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Open the amendment' }),
        ).toHaveAttribute('href', '/preview/auditor-audit-amendment');
        expect(
            screen.queryByRole('button', { name: 'Start a linked amendment' }),
        ).not.toBeInTheDocument();
    });

    it('opens an amendment with its link to the unchanged original', () => {
        render(<AuditorAudit {...props(amendment)} />);

        expect(screen.getByRole('note')).toHaveTextContent(
            'This is a linked amendment of report rpt_01J9Q3W7K9V5D1. That report stays published, unchanged.',
        );
        expect(
            screen.getByRole('link', { name: 'Open the original' }),
        ).toHaveAttribute('href', '/preview/auditor-audit-sealed-monthly');
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

describe('Audit procedure — a blocking conflict', () => {
    it('keeps only the receipt and a minimal status', () => {
        render(<AuditorAudit {...props(blocked)} />);
        const dialog = sheet();

        expect(
            within(dialog).getByRole('heading', { name: 'Conflict recorded' }),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText(
                'Your conflict has been recorded. Work on this assignment is stopped while Audit Operations arranges reassignment.',
            ),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText('Reassignment pending'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText(
                'Owner, director, employee or adviser tie',
            ),
        ).toBeInTheDocument();
        expect(within(dialog).queryByRole('timer')).not.toBeInTheDocument();
        expect(
            within(dialog).queryByRole('list', { name: 'Audit steps' }),
        ).not.toBeInTheDocument();
        expect(
            within(dialog).queryByRole('button', { name: /Continue|Declare/ }),
        ).not.toBeInTheDocument();
        expect(within(dialog).queryByText(/check-in/i)).not.toBeInTheDocument();
        expect(
            within(dialog).getByRole('link', { name: 'Back to jobs' }),
        ).toHaveAttribute('href', '/preview/auditor-jobs');
    });

    it('withdraws the procedure the moment a blocking conflict is recorded', async () => {
        inertia.queue.push(
            answers(
                operation({
                    code: 'CONFLICT_RECORDED',
                    data: {
                        next: { url: '/preview/auditor-jobs', method: 'get' },
                        conflict: {
                            conflict_id: 'cf_9',
                            kind: 'financial_interest',
                            declared_at: '2026-10-03T17:00:00Z',
                            note: 'I hold shares.',
                            blocking: true,
                            status: 'recorded',
                        },
                    },
                }),
            ),
        );
        const { user } = renderWithUser(
            <AuditorAudit {...props(checkInDone)} />,
        );

        await user.click(
            within(sheet()).getByRole('button', { name: 'Declare a conflict' }),
        );
        await user.click(
            screen.getByRole('radio', { name: 'Financial interest' }),
        );
        await user.click(
            screen.getByLabelText('Factual explanation (required)'),
        );
        await user.paste('I hold shares.');
        await user.click(
            screen.getByRole('button', { name: 'Declare interest' }),
        );

        expect(inertia.calls[0].body).toMatchObject({
            assignment_id: 'asg_fa_huye',
            expected_revision: 4,
            kind: 'financial_interest',
        });

        const result = await screen.findByRole('alertdialog', {
            name: 'Interest declared',
        });

        expect(result).toHaveAccessibleDescription(
            'Your conflict has been recorded. Work on this assignment is stopped.',
        );
        expect(
            within(sheet()).queryByText('Checked in on site · 18:02'),
        ).not.toBeInTheDocument();
        expect(within(sheet()).getByText('Recorded')).toBeInTheDocument();
    });
});
