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
import { TabColumns } from '@/components/auditor/tab-columns';
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
import ledgerUndeclared from '../../../resources/fixtures/ui/auditor-audit-ledger-undeclared.json';
import ledger from '../../../resources/fixtures/ui/auditor-audit-ledger.json';
import monthlySeal from '../../../resources/fixtures/ui/auditor-audit-monthly-seal.json';
import photosOffline from '../../../resources/fixtures/ui/auditor-audit-photos-offline.json';
import photosStorageFull from '../../../resources/fixtures/ui/auditor-audit-photos-storage-full.json';
import photosUploadFailed from '../../../resources/fixtures/ui/auditor-audit-photos-upload-failed.json';
import photos from '../../../resources/fixtures/ui/auditor-audit-photos.json';
import review from '../../../resources/fixtures/ui/auditor-audit-review.json';
import sealedMonthlyOverdue from '../../../resources/fixtures/ui/auditor-audit-sealed-monthly-overdue.json';
import sealedMonthly from '../../../resources/fixtures/ui/auditor-audit-sealed-monthly.json';
import sealedPublished from '../../../resources/fixtures/ui/auditor-audit-sealed-published.json';
import sealedUnavailable from '../../../resources/fixtures/ui/auditor-audit-sealed-seal-unavailable.json';
import sealed from '../../../resources/fixtures/ui/auditor-audit-sealed.json';
import statementsNetOutflow from '../../../resources/fixtures/ui/auditor-audit-statements-net-outflow.json';
import statementsNoCover from '../../../resources/fixtures/ui/auditor-audit-statements-no-cover.json';
import statementsUnavailable from '../../../resources/fixtures/ui/auditor-audit-statements-unavailable.json';
import statements from '../../../resources/fixtures/ui/auditor-audit-statements.json';
import { renderWithUser } from '../helpers/render-with-user';
import { answers, fails, inertia, invalid, operation } from './inertia';

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
    it('counts the required photos alone when no extra photo was taken', () => {
        render(
            <AuditorAudit
                {...withStage<PhotosStage>(photos, (stage) => ({
                    ...stage,
                    slots: stage.slots.filter((slot) => !slot.extra),
                }))}
            />,
        );

        expect(
            screen.getByText(/2 of 3 required captured\.$/u),
        ).toBeInTheDocument();
        expect(screen.queryByText(/extra photo/u)).not.toBeInTheDocument();
    });

    const SYNTHETIC =
        'Synthetic test evidence (isolated) — not a native capture.';

    it('labels an isolated synthetic photo package, never as native proof', () => {
        render(
            <AuditorAudit
                {...withStage<PhotosStage>(photos, (stage) => ({
                    ...stage,
                    package: { ...stage.package, source: 'isolated_synthetic' },
                }))}
            />,
        );

        expect(within(sheet()).getByText(SYNTHETIC)).toHaveAttribute(
            'role',
            'note',
        );
        /* No handoff still reads as capture unavailable, beside the label. */
        expect(
            within(sheet()).queryByRole('link', { name: /Add photo/ }),
        ).not.toBeInTheDocument();
    });

    it.each([['companion_device' as const], [undefined]])(
        'adds no synthetic label for a %s package',
        (source) => {
            render(
                <AuditorAudit
                    {...withStage<PhotosStage>(photos, (stage) => ({
                        ...stage,
                        package: { ...stage.package, source, handoff: HANDOFF },
                    }))}
                />,
            );

            expect(screen.queryByText(SYNTHETIC)).not.toBeInTheDocument();
            expect(
                within(sheet()).getByRole('link', { name: /Add photo/ }),
            ).toHaveAttribute('href', HANDOFF.url);
        },
    );

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
            within(dialog).getByText(
                /2 of 3 required captured\. Plus 1 extra photo\./u,
            ),
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
            within(dialog).getByText('Rejected · CSV · 1.1 MB'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText(/This CSV is not UTF-8 text/),
        ).toBeInTheDocument();

        await user.click(
            within(dialog).getByRole('button', {
                name: 'Re-scan this document',
            }),
        );
        expect(click).toHaveBeenCalled();

        const saved = operation({
            code: 'INGESTED_NOT_AUDIT_APPROVED',
            revision: 8,
            data: {
                next: { url: '/preview/auditor-audit-ledger', method: 'get' },
            },
        });

        inertia.queue.push(answers(saved), answers(saved));
        await user.upload(input, file);

        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-audit-ledger-ingested',
            method: 'post',
            body: {
                step: 'ledger',
                document: file,
                replaces: 'ld_2',
                ...COMMAND,
            },
        });
        /*
         * The receipt verifies and advances nothing: its `next` is this same step, which is
         * redrawn in place rather than visited afresh.
         */
        await waitFor(() => expect(inertia.reloads).toEqual([undefined]));
        expect(inertia.visits).toEqual([]);
        expect(inertia.posts).toHaveLength(0);

        await user.click(
            within(dialog).getByRole('button', {
                name: /Add another ledger document/,
            }),
        );
        await user.upload(input, file);
        expect(inertia.calls[1].body).toMatchObject({ replaces: null });

        fireEvent.change(input, { target: { files: [] } });
        expect(inertia.calls).toHaveLength(2);
    });

    it('looks up a lost upload and resends the same upload, with the same request, only if unrecorded', async () => {
        inertia.queue.push(
            fails(503),
            fails(404, { code: 'OPERATION_NOT_FOUND' }),
        );
        const { user } = renderWithUser(<AuditorAudit {...props(ledger)} />);
        const input = screen.getByLabelText('Ledger document file');
        const file = new File(['%PDF'], 'stock-book.pdf', {
            type: 'application/pdf',
        });

        await user.upload(input, file);

        expect(
            await screen.findByRole('button', { name: 'Try again' }),
        ).toBeInTheDocument();
        expect(inertia.reloads).toEqual([{ onFinish: expect.any(Function) }]);
        expect(
            screen.getByRole('button', { name: /Add another ledger document/ }),
        ).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Try again' }));

        expect(inertia.calls[2]).toEqual(inertia.calls[0]);
        expect(inertia.calls[2].body).toMatchObject({ document: file });
        expect(inertia.calls[1].url).toMatch(
            /^\/preview\/auditor-operation-[0-9a-f-]{36}$/u,
        );
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

        expect(screen.getByText('Checking … PDF · 0.8 MB')).toBeInTheDocument();
        expect(
            screen.getByRole('progressbar', { name: 'Reading the document' }),
        ).toBeInTheDocument();
        expect(screen.getByText('0 of 1 accepted')).toBeInTheDocument();
        expect(inertia.poll.start).toHaveBeenCalled();
    });

    it('pauses the re-check while Review & seal is in flight, then follows the server to the next step', async () => {
        let answer: (value: unknown) => void = () => undefined;

        inertia.queue.push(
            () =>
                new Promise((resolve) => {
                    answer = resolve;
                }),
        );
        const { user } = renderWithUser(
            <AuditorAudit
                {...withStage<LedgerStage>(ledger, (stage) => ({
                    ...stage,
                    documents: [
                        {
                            ...stage.documents[0],
                            state: 'scanning',
                            fields: [],
                        },
                    ],
                }))}
            />,
        );

        expect(inertia.poll.start).toHaveBeenCalledTimes(1);
        expect(inertia.poll.stop).not.toHaveBeenCalled();

        await user.click(screen.getByRole('button', { name: 'Review & seal' }));

        /* A read sent now could land after the move to the seal step and undo it. */
        expect(inertia.poll.stop).toHaveBeenCalledTimes(1);
        expect(inertia.calls[0].body).toMatchObject({
            step: 'ledger',
            observed_stock: '36400000',
        });

        await act(async () => {
            answer(
                operation({
                    data: {
                        next: {
                            url: '/auditor/reports/fa_huye',
                            method: 'get',
                        },
                    },
                }),
            );
        });

        expect(inertia.visits).toEqual([{ url: '/auditor/reports/fa_huye' }]);
    });

    it('keeps a typed, unsaved stock value through a ledger upload and seals with it', async () => {
        const page = withStage<LedgerStage>(ledger, (stage) => ({
            ...stage,
            observed_stock: null,
        }));
        const { user } = renderWithUser(<AuditorAudit {...page} />);
        const box = screen.getByLabelText(
            'Observed on site · stock value (RWF)',
        );

        expect(box).toHaveValue('');
        await user.type(box, '38000000');
        expect(box).toHaveValue('38,000,000');

        inertia.queue.push(
            answers(
                operation({
                    code: 'INGESTED_NOT_AUDIT_APPROVED',
                    data: {
                        next: {
                            url: '/auditor/reports/fa_huye',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        await user.upload(
            screen.getByLabelText('Ledger document file'),
            new File(['amount\n1\n'], 'ledger.csv', { type: 'text/csv' }),
        );

        await waitFor(() => expect(inertia.reloads).toContainEqual(undefined));
        expect(inertia.visits).toEqual([]);
        expect(box).toHaveValue('38,000,000');

        inertia.queue.push(answers(operation()));
        await user.click(screen.getByRole('button', { name: 'Review & seal' }));

        expect(inertia.calls[1].body).toMatchObject({
            step: 'ledger',
            observed_stock: '38000000',
        });
    });

    it('shows the previewed figure the variance was measured against when the page is reloaded', () => {
        inertia.url = '/auditor/reports/fa_huye?observed_stock=38000000';
        render(
            <AuditorAudit
                {...withStage<LedgerStage>(ledger, (stage) => ({
                    ...stage,
                    observed_stock: null,
                    variance: { pct: '0.0', within: true },
                }))}
            />,
        );

        expect(
            screen.getByLabelText('Observed on site · stock value (RWF)'),
        ).toHaveValue('38,000,000');
        expect(screen.getByText('Within tolerance')).toBeInTheDocument();
    });

    it('shows the saved stock value after a reload with no preview, and ignores a malformed one', () => {
        inertia.url = '/auditor/reports/fa_huye?observed_stock=12abc';
        render(<AuditorAudit {...props(ledger)} />);

        expect(
            screen.getByLabelText('Observed on site · stock value (RWF)'),
        ).toHaveValue('36,400,000');
    });

    it('offers the retained original as an ordinary download link', async () => {
        const { user } = renderWithUser(
            <AuditorAudit
                {...withStage<LedgerStage>(ledger, (stage) => ({
                    ...stage,
                    documents: stage.documents.map((document, index) =>
                        index === 0
                            ? {
                                  ...document,
                                  link: {
                                      url: '/auditor/reports/fa_huye/ledger/ld_1/original',
                                      method: 'get',
                                  },
                              }
                            : { ...document, link: null },
                    ),
                }))}
            />,
        );
        const [download] = screen.getAllByRole('link', {
            name: 'Download original',
        });

        expect(
            screen.getAllByRole('link', { name: 'Download original' }),
        ).toHaveLength(1);
        expect(download).toHaveAttribute(
            'href',
            '/auditor/reports/fa_huye/ledger/ld_1/original',
        );

        /* A plain anchor: the browser downloads it, and Inertia never visits it. */
        download.addEventListener('click', (event) => event.preventDefault());
        await user.click(download);
        expect(inertia.visits).toHaveLength(0);
    });

    it('uploads to the route the stage supplies, not the step save', async () => {
        inertia.queue.push(answers(operation()));
        const { user } = renderWithUser(
            <AuditorAudit
                {...withStage<LedgerStage>(ledger, (stage) => ({
                    ...stage,
                    upload: {
                        url: '/auditor/reports/fa_huye/ledger',
                        method: 'post',
                    },
                }))}
            />,
        );
        const file = new File(['%PDF'], 'stock-book.pdf', {
            type: 'application/pdf',
        });

        await user.upload(screen.getByLabelText('Ledger document file'), file);

        expect(inertia.calls[0]).toMatchObject({
            url: '/auditor/reports/fa_huye/ledger',
            method: 'post',
            body: { step: 'ledger', document: file, replaces: null },
        });
    });

    it('offers no upload without the stage route, keeping the documents and their downloads', () => {
        render(
            <AuditorAudit
                {...withStage<LedgerStage>(ledger, (stage) => ({
                    ...stage,
                    upload: null,
                    documents: stage.documents.map((document) => ({
                        ...document,
                        link: {
                            url: `/auditor/reports/fa_huye/ledger/${document.id}/original`,
                            method: 'get',
                        },
                    })),
                }))}
            />,
        );
        const dialog = sheet();

        expect(
            within(dialog).queryByLabelText('Ledger document file'),
        ).not.toBeInTheDocument();
        expect(
            within(dialog).queryByRole('button', { name: /ledger/iu }),
        ).not.toBeInTheDocument();
        expect(
            within(dialog).queryByRole('button', {
                name: 'Re-scan this document',
            }),
        ).not.toBeInTheDocument();
        expect(
            within(dialog).queryByText(/Upload the original ledger/u),
        ).not.toBeInTheDocument();
        expect(
            within(dialog).getByText('Huye-Motors-ledger-Q3.pdf'),
        ).toBeInTheDocument();
        expect(
            within(dialog).getAllByRole('link', { name: 'Download original' }),
        ).toHaveLength(2);
    });

    it('offers no upload when the ledger is read from a later step', () => {
        const page = props(ledgerIngested);

        render(
            <AuditorAudit
                {...page}
                steps={page.steps.map((step) => ({
                    ...step,
                    state: step.key === 'seal' ? 'current' : 'done',
                }))}
            />,
        );

        expect(
            screen.queryByLabelText('Ledger document file'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', {
                name: /Add another ledger document/u,
            }),
        ).not.toBeInTheDocument();
        expect(screen.getAllByText('Received · not yet reviewed')).toHaveLength(
            2,
        );
    });

    it('offers no download for a document without a link', () => {
        render(<AuditorAudit {...props(ledger)} />);

        expect(
            screen.queryByRole('link', { name: 'Download original' }),
        ).not.toBeInTheDocument();
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

    it.each([
        [
            {
                document:
                    'The ledger must be a PDF or UTF-8 CSV of at most 10 MB.',
            },
        ],
        [{ replaces: 'That document is not on this audit.' }],
    ])(
        'shows why an upload was refused beside the upload: %j',
        async (errors) => {
            inertia.queue.push(invalid(errors));
            const { user } = renderWithUser(
                <AuditorAudit {...props(ledger)} />,
            );

            await user.upload(
                screen.getByLabelText('Ledger document file'),
                new File(['x'.repeat(10)], 'huge.pdf', {
                    type: 'application/pdf',
                }),
            );

            const [message] = Object.values(errors);

            expect(await screen.findByText(message)).toBeInTheDocument();
            expect(screen.getAllByText(message)).toHaveLength(1);
            expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        },
    );

    it('names the formats and the limit, and accepts only a PDF or CSV', () => {
        render(<AuditorAudit {...props(ledger)} />);

        expect(
            screen.getByText(
                'Upload the original ledger as a PDF or CSV (up to 10 MB). A scanned ledger can be a PDF; a scan without text is marked for manual source review.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Ledger document file')).toHaveAttribute(
            'accept',
            'application/pdf,.pdf,text/csv,.csv',
        );
        expect(sheet()).not.toHaveTextContent(/OCR|PNG|TIFF/u);
    });

    it.each([
        [
            new File(['x'], 'ledger.png', { type: 'image/png' }),
            'Choose the ledger as a PDF or CSV file.',
        ],
        [
            new File(['x'], 'ledger.tiff', { type: 'image/tiff' }),
            'Choose the ledger as a PDF or CSV file.',
        ],
        [
            new File(['x'.repeat(10 * 1024 * 1024 + 1)], 'ledger.pdf', {
                type: 'application/pdf',
            }),
            'This file is larger than 10 MB. Upload a PDF or CSV of 10 MB or less.',
        ],
    ])(
        'keeps a file the server would refuse on the page: %#',
        (chosen, message) => {
            render(<AuditorAudit {...props(ledger)} />);
            const input = screen.getByLabelText('Ledger document file');

            /* Past the picker's own filter, as a drag or a loose picker could be. */
            fireEvent.change(input, { target: { files: [chosen] } });

            expect(screen.getByText(message)).toBeInTheDocument();
            expect(inertia.calls).toHaveLength(0);
        },
    );

    it('sends a CSV the browser types loosely, at exactly the limit, and clears an earlier complaint', () => {
        inertia.queue.push(
            answers(
                operation({
                    data: {
                        next: {
                            url: '/preview/auditor-audit-ledger',
                            method: 'get',
                        },
                    },
                }),
            ),
        );
        render(<AuditorAudit {...props(ledger)} />);
        const input = screen.getByLabelText('Ledger document file');

        fireEvent.change(input, {
            target: {
                files: [new File(['x'], 'ledger.png', { type: 'image/png' })],
            },
        });
        expect(
            screen.getByText('Choose the ledger as a PDF or CSV file.'),
        ).toBeInTheDocument();

        const csv = new File(['x'.repeat(10 * 1024 * 1024)], 'Stock.CSV', {
            type: 'application/vnd.ms-excel',
        });

        fireEvent.change(input, { target: { files: [csv] } });

        expect(
            screen.queryByText('Choose the ledger as a PDF or CSV file.'),
        ).not.toBeInTheDocument();
        expect(inertia.calls[0].body).toMatchObject({
            step: 'ledger',
            document: csv,
        });
    });

    it('sends a PDF or CSV named without its extension, by its type', async () => {
        inertia.queue.push(answers(operation()), answers(operation()));
        const { user } = renderWithUser(<AuditorAudit {...props(ledger)} />);
        const input = screen.getByLabelText('Ledger document file');

        await user.upload(
            input,
            new File(['%PDF'], 'ledger', { type: 'application/pdf' }),
        );
        await waitFor(() => expect(inertia.calls).toHaveLength(1));
        await user.upload(
            input,
            new File(['a,b'], 'ledger', { type: 'text/csv' }),
        );
        await waitFor(() => expect(inertia.calls).toHaveLength(2));
    });

    it('banners a field error the step has no field for', async () => {
        inertia.queue.push(invalid({ step: 'This step is not open.' }));
        const { user } = renderWithUser(
            <AuditorAudit {...props(photos)} can_continue />,
        );

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'This step is not open.',
        );
    });

    it('starts empty and blocks the reconciliation tick', () => {
        render(<AuditorAudit {...props(ledgerEmpty)} />);
        const dialog = sheet();

        expect(within(dialog).getByText('None attached')).toBeInTheDocument();
        expect(
            within(dialog).getByRole('button', {
                name: 'Attach the ledger (PDF or CSV)',
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

    it('reads an undeclared stock as not declared, never zero, and keeps reconciliation blocked', async () => {
        const { user } = renderWithUser(
            <AuditorAudit {...props(ledgerUndeclared)} />,
        );
        const dialog = sheet();
        const tick = within(dialog).getByRole('checkbox', {
            name: /reconcile with the digital statements/,
        });

        expect(within(dialog).getByText('Not declared')).toBeInTheDocument();
        expect(
            within(dialog).getByText(
                'The business has not declared a stock value, so there is no reported figure to compare your count with. Record what you counted.',
            ),
        ).toBeInTheDocument();
        expect(within(dialog).queryByText('RWF 0')).not.toBeInTheDocument();
        expect(within(dialog).queryByText(/tolerance/)).not.toBeInTheDocument();
        /* A parsed ledger alone does not open the reconciliation tick. */
        expect(tick).toBeDisabled();
        expect(tick).not.toBeChecked();
        expect(
            within(dialog).getByText(
                'Reconciliation stays blocked until the business declares its stock.',
            ),
        ).toBeInTheDocument();
        expect(
            within(dialog).queryByText(
                'Attach and pass at least one ledger document first.',
            ),
        ).not.toBeInTheDocument();
        expect(
            within(dialog).getByRole('button', { name: 'Review & seal' }),
        ).toBeDisabled();

        await user.click(tick);
        expect(tick).not.toBeChecked();
    });

    it('never records a reconciliation against an undeclared stock', async () => {
        const { user } = renderWithUser(
            <AuditorAudit
                {...withStage<LedgerStage>(ledgerUndeclared, (stage) => ({
                    ...stage,
                    reconciled: true,
                }))}
                can_continue
            />,
        );

        expect(
            screen.getByRole('checkbox', { name: /reconcile/ }),
        ).not.toBeChecked();
        await user.click(screen.getByRole('button', { name: 'Review & seal' }));
        expect(inertia.calls[0].body).toEqual({
            ...COMMAND,
            observed_stock: '36400000',
            reconciled: false,
            step: 'ledger',
        });
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
        /* A factual cover with no approved thresholds: shown neutrally, never as a band. */
        expect(within(dialog).getByText('1.31×')).toHaveClass('text-rz-ink');
        expect(within(dialog).getByText('1.31×')).not.toHaveClass(
            'text-rz-positive',
        );
        expect(
            within(dialog).getByRole('button', { name: 'Start the count' }),
        ).toBeEnabled();
    });

    it('lists every source document of the month as an ordinary download link', async () => {
        const { user } = renderWithUser(
            <AuditorAudit {...props(statements)} />,
        );
        const documents = within(sheet('GreenLeaf Agro')).getByRole('list', {
            name: 'Source documents',
        });
        const links = within(documents).getAllByRole('link');

        expect(links.map((link) => link.textContent)).toEqual([
            'GreenLeaf-Agro-BK-current-2026-09.pdfView',
            'GreenLeaf-Agro-BK-savings-2026-09.pdfView',
            'GreenLeaf-Agro-MoMo-2026-09.csvView',
        ]);
        expect(links.map((link) => link.getAttribute('href'))).toEqual([
            '/preview/auditor-audit-statements?document=sd_bk_current',
            '/preview/auditor-audit-statements?document=sd_bk_savings',
            '/preview/auditor-audit-statements?document=sd_momo',
        ]);

        /* A plain anchor: the browser downloads it, and Inertia never visits it. */
        links[1].addEventListener('click', (event) => event.preventDefault());
        await user.click(links[1]);
        expect(inertia.visits).toHaveLength(0);
    });

    it('says when a month has no source documents on file', () => {
        render(
            <AuditorAudit
                {...withStage<StatementsStage>(statements, (stage) => ({
                    ...stage,
                    statements: {
                        ...(stage.statements as Extract<
                            StatementsStage['statements'],
                            { status: 'available' }
                        >),
                        documents: [],
                    },
                }))}
            />,
        );

        expect(
            screen.getByText('No source documents are on file for this month.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('list', { name: 'Source documents' }),
        ).not.toBeInTheDocument();
    });

    it('reads a cover that cannot be computed as unavailable, never zero', () => {
        render(<AuditorAudit {...props(statementsNoCover)} />);
        const dialog = sheet('GreenLeaf Agro');

        expect(within(dialog).getByText('Unavailable')).toHaveClass(
            'text-rz-secondary',
        );
        expect(within(dialog).queryByText(/×/u)).not.toBeInTheDocument();
        expect(
            within(
                within(dialog).getByRole('list', { name: 'Source documents' }),
            ).getAllByRole('link'),
        ).toHaveLength(1);
    });

    it('never tones a net outflow green', () => {
        render(<AuditorAudit {...props(statementsNetOutflow)} />);
        const net = screen.getByText('RWF −10.4M');

        expect(net).toHaveClass('text-rz-ink');
        expect(net).not.toHaveClass('text-rz-positive');
        expect(screen.getByText('0.92×')).toHaveClass('text-rz-ink');
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
            within(dialog).getByText('Reported stock: 190 units'),
        ).toBeInTheDocument();
        /* The stock tolerance is in units, apart from the cash line's RWF tolerance. */
        expect(
            within(dialog).getByText('Stock tolerance: 0 units'),
        ).toBeInTheDocument();
        expect(
            within(dialog).queryByText(/Stock tolerance: RWF/u),
        ).not.toBeInTheDocument();
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

    it('shows the previewed cash and units a reloaded count was measured against', () => {
        inertia.url =
            '/auditor/reports/mr_greenleaf?cash=1200000&stock_units=188';
        render(<AuditorAudit {...props(count)} />);

        expect(
            screen.getByLabelText('Observed on site · cash (RWF)'),
        ).toHaveValue('1,200,000');
        expect(
            screen.getByLabelText('Observed on site · stock (units)'),
        ).toHaveValue('188');
    });

    it('starts a count with no baseline, no counts and no status', () => {
        render(
            <AuditorAudit
                {...withStage<CountStage>(count, (stage) => ({
                    ...stage,
                    cash: { observed: null, statement: null, variance: null },
                    stock: {
                        ...stage.stock,
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
        expect(screen.queryByText('Unavailable')).not.toBeInTheDocument();
        expect(
            screen.getByLabelText('Observed on site · cash (RWF)'),
        ).toHaveValue('');
        expect(screen.queryAllByRole('radio', { checked: true })).toHaveLength(
            0,
        );
    });

    it('states the stock tolerance in units exactly as the server sends it', () => {
        render(
            <AuditorAudit
                {...withStage<CountStage>(count, (stage) => ({
                    ...stage,
                    tolerance: 'RWF 5,000',
                    stock: { ...stage.stock, tolerance_units: '1500' },
                }))}
            />,
        );

        expect(
            screen.getByText('Stock tolerance: 1,500 units'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Statements show RWF 1,210,000 · tolerance RWF 5,000',
            ),
        ).toBeInTheDocument();
    });
});

describe('Audit procedure — retained records', () => {
    it('reads a count period that was never pinned as unavailable', () => {
        render(
            <AuditorAudit
                {...withStage<CountStage>(count, (stage) => ({
                    ...stage,
                    period: null,
                    cash: { ...stage.cash, statement: null, variance: null },
                    stock: {
                        ...stage.stock,
                        reported_units: null,
                        variance: null,
                    },
                }))}
            />,
        );
        const dialog = sheet('GreenLeaf Agro');

        expect(within(dialog).getByText('Unavailable')).toHaveClass(
            'text-rz-secondary',
        );
        expect(within(dialog).queryByText(/ – /u)).not.toBeInTheDocument();
        /* Missing declarations say so; none reads as a zero. */
        expect(
            within(dialog).getByText(
                'No statement balance on file for this period.',
            ),
        ).toBeInTheDocument();
        expect(
            within(dialog).getByText(
                'No reported stock baseline for this period.',
            ),
        ).toBeInTheDocument();
        expect(within(dialog).queryByText(/RWF 0\b/u)).not.toBeInTheDocument();
        /* The only zero in units is the stock policy tolerance, never a missing count. */
        expect(within(dialog).getAllByText(/\b0 units/u)).toEqual([
            within(dialog).getByText('Stock tolerance: 0 units'),
        ]);
    });

    it('offers no amendment while the stage does not enable it', () => {
        const page = props(sealedMonthly);

        render(
            <AuditorAudit
                {...page}
                actions={{ ...page.actions, amend: null }}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Start a linked amendment' }),
        ).not.toBeInTheDocument();
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
        /* A Flash report has no monthly deadline: co-signing is due, with no invented date. */
        expect(
            within(dialog).getByText(
                'The report is sealed and can no longer be edited. Huye Motors still needs to co-sign; it publishes to holders after that.',
            ),
        ).toBeInTheDocument();
        expect(dialog).not.toHaveTextContent(/co-signs by/u);
        expect(within(dialog).queryByRole('note')).not.toBeInTheDocument();
        /* No public seal check is linked until the server sends one. */
        expect(
            within(dialog).queryByRole('link', { name: 'Verify seal' }),
        ).not.toBeInTheDocument();
    });

    it('links the public seal check the server sends with the sealed report', () => {
        render(
            <AuditorAudit
                {...withStage<SealedStage>(sealed, (stage) => ({
                    ...stage,
                    verification: {
                        url: '/audit-seals/rpt_01J9Q3W7K9V5D1',
                        method: 'get',
                    },
                }))}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'Verify seal' }),
        ).toHaveAttribute('href', '/audit-seals/rpt_01J9Q3W7K9V5D1');
    });

    /** The sealed report's sheet, whose introduction says where the filing stands. */
    const intro = (business: string) => sheet(business);

    it('keeps the co-sign date a monthly report awaiting its co-signature is due by', () => {
        render(
            <AuditorAudit
                {...withStage<SealedStage>(sealedMonthly, (stage) => ({
                    ...stage,
                    cosign: {
                        ...stage.cosign,
                        state: 'pending',
                        signed_at: null,
                    },
                    published_at: null,
                }))}
            />,
        );

        expect(
            within(intro('Kivu Coffee Roasters')).getByText(
                'The report is sealed and can no longer be edited. Kivu Coffee Roasters co-signs by 7 Oct 2026; it publishes to holders after that.',
            ),
        ).toBeInTheDocument();
    });

    it.each([
        [sealedPublished, 'Huye Motors', '4 Oct 2026'],
        [sealedMonthly, 'Kivu Coffee Roasters', '3 Oct 2026'],
    ])(
        'says a published report was co-signed and published, never that it awaits either: %#',
        (fixture, business, date) => {
            render(<AuditorAudit {...props(fixture)} />);
            const text = intro(business);

            expect(text).toHaveTextContent(
                `Sealed and co-signed; published to holders on ${date}.`,
            );
            expect(text).not.toHaveTextContent(
                /needs to co-sign|co-signs by|publishes to holders after/u,
            );
        },
    );

    const AMENDMENT = {
        report_id: 'rpt_01J9Q3W7K9V5D1_a1',
        link: { url: '/auditor/reports/rpt_01J9Q3W7K9V5D1_a1', method: 'get' },
    } as const;

    it('says an unpublished report you amended is replaced, never that it awaits co-signing', () => {
        render(
            <AuditorAudit
                {...withStage<SealedStage>(sealed, (stage) => ({
                    ...stage,
                    amended_by: AMENDMENT,
                }))}
            />,
        );
        const dialog = intro('Huye Motors');

        expect(
            within(dialog).getByText(
                "You amended this report, so it won't be co-signed or published. The amendment replaces it.",
            ),
        ).toBeInTheDocument();
        expect(dialog).not.toHaveTextContent(
            /needs to co-sign|co-signs by|publishes to holders/u,
        );
        expect(
            within(dialog).getByRole('link', { name: 'Open the amendment' }),
        ).toHaveAttribute('href', AMENDMENT.link.url);
    });

    it('keeps the published wording for a report amended after publication', () => {
        render(
            <AuditorAudit
                {...withStage<SealedStage>(sealedPublished, (stage) => ({
                    ...stage,
                    amended_by: AMENDMENT,
                }))}
            />,
        );
        const dialog = intro('Huye Motors');

        expect(dialog).toHaveTextContent(
            'Sealed and co-signed; published to holders on 4 Oct 2026.',
        );
        expect(dialog).not.toHaveTextContent(/won't be co-signed/u);
        expect(
            within(dialog).getByRole('link', { name: 'Open the amendment' }),
        ).toHaveAttribute('href', AMENDMENT.link.url);
    });

    it('says an overdue co-signature closed without publishing or approving anything', () => {
        render(<AuditorAudit {...props(sealedMonthlyOverdue)} />);
        const text = intro('Kivu Coffee Roasters');

        expect(text).toHaveTextContent(
            "The report is sealed, but Kivu Coffee Roasters's co-signing window has passed. It can no longer be co-signed and is not published; nothing is approved automatically.",
        );
        expect(
            within(
                within(sheet('Kivu Coffee Roasters')).getByRole('list', {
                    name: 'Filing progress',
                }),
            ).getByText('Overdue'),
        ).toBeInTheDocument();
    });

    it.each([
        [
            'signed',
            'The report is sealed and Kivu Coffee Roasters has co-signed it. It publishes to holders next.',
        ],
        [
            'declined',
            'The report is sealed. Kivu Coffee Roasters disputed it rather than co-signing, so it is not published.',
        ],
    ] as const)('says where a %s, unpublished report stands', (state, text) => {
        render(
            <AuditorAudit
                {...withStage<SealedStage>(sealedMonthly, (stage) => ({
                    ...stage,
                    cosign: { ...stage.cosign, state },
                    published_at: null,
                }))}
            />,
        );

        expect(intro('Kivu Coffee Roasters')).toHaveTextContent(text);
    });

    it('says a seal cannot be verified now while keeping the sealed record and its history', () => {
        render(<AuditorAudit {...props(sealedUnavailable)} />);
        const dialog = sheet('Kivu Coffee Roasters');

        expect(within(dialog).getByRole('note')).toHaveTextContent(
            "This seal can't be verified right now — its signing key is no longer current. The sealed record and its history are unchanged.",
        );
        /* The immutable seal, signature, digest and timeline stay as they were. */
        const stage = props(sealedUnavailable).stage as SealedStage;

        expect(within(dialog).getByText(stage.digest)).toBeInTheDocument();
        expect(
            within(dialog).getByText(stage.signature_ref),
        ).toBeInTheDocument();
        expect(within(dialog).getByText(stage.key_id)).toBeInTheDocument();
        expect(
            within(dialog).getByRole('list', { name: 'Filing progress' }),
        ).toBeInTheDocument();
        expect(dialog).not.toHaveTextContent(/seal (is )?valid/iu);
    });

    it('shows no verification notice for a seal that verifies', () => {
        render(<AuditorAudit {...props(sealedMonthly)} />);

        expect(
            screen.queryByText(/can't be verified right now/u),
        ).not.toBeInTheDocument();
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
        /* Exactly the amendment's target and revision, plus the common fields. */
        expect(inertia.calls[0]).toEqual({
            url: '/preview/auditor-audit-amendment',
            method: 'post',
            body: {
                audit_id: 'mr_greenleaf',
                expected_revision: 3,
                identity_context_revision: 3,
                request_id: expect.stringMatching(/^[0-9a-f-]{36}$/u),
            },
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
            'This is a linked amendment of report rpt_01J9Q3W7K9V5D1. That report remains unchanged.',
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

        await waitFor(() =>
            expect(inertia.visits).toEqual([{ url: '/preview/auditor-jobs' }]),
        );
        expect(
            within(sheet()).queryByText('Checked in on site · 18:02'),
        ).not.toBeInTheDocument();
        expect(within(sheet()).getByText('Recorded')).toBeInTheDocument();
    });
});

describe('Audit procedure — the monthly sheet on a phone', () => {
    /** The lg breakpoint, switchable at run time as a window resize would. */
    const viewport = (initial: boolean) => {
        let matches = initial;
        const listeners = new Set<() => void>();

        vi.stubGlobal('matchMedia', (query: string) => ({
            get matches() {
                return matches;
            },
            media: query,
            addEventListener: (_: string, listener: () => void) =>
                listeners.add(listener),
            removeEventListener: (_: string, listener: () => void) =>
                listeners.delete(listener),
        }));

        return (next: boolean) => {
            matches = next;
            act(() => listeners.forEach((listener) => listener()));
        };
    };

    /** Live Jobs send no monthly section, so a phone has no right backdrop at all. */
    const liveMonthly = (): AuditProcedureProps => {
        const page = props(statements);

        return { ...page, jobs: { ...page.jobs, monthly: null } };
    };

    const startCount = () =>
        screen.getByRole('button', { name: 'Start the count' });

    afterEach(() => vi.unstubAllGlobals());

    it('keeps the monthly procedure on a phone when Jobs send no monthly section', () => {
        viewport(false);
        render(<AuditorAudit {...liveMonthly()} />);

        expect(screen.getByTestId('column-right')).toContainElement(
            sheet('GreenLeaf Agro'),
        );
        expect(startCount()).toBeEnabled();
    });

    it('keeps the same procedure attached when a wide window narrows to a phone', () => {
        const resize = viewport(true);

        render(<AuditorAudit {...liveMonthly()} />);
        const before = startCount();

        resize(false);

        expect(startCount()).toBe(before);
        expect(before).toBeInTheDocument();
        expect(screen.getByTestId('column-right')).toContainElement(before);
    });

    it('keeps the monthly section beneath the sheet on a wide screen', () => {
        viewport(true);
        render(<AuditorAudit {...props(statements)} />);
        const right = screen.getByTestId('column-right');

        expect(right).toContainElement(sheet('GreenLeaf Agro'));
        expect(right).toContainElement(
            screen.getByRole('heading', { name: 'Monthly reports' }),
        );
    });
});

describe('Tab columns', () => {
    const overlay = (column: 'left' | 'right') => ({
        column,
        content: <div role="dialog" aria-label="Overlay" />,
    });

    it('opens a right overlay in its own column even with nothing beneath it', () => {
        render(
            <TabColumns
                left={<p>Left</p>}
                right={null}
                overlay={overlay('right')}
            />,
        );

        expect(screen.getByTestId('column-right')).toContainElement(
            screen.getByRole('dialog', { name: 'Overlay' }),
        );
    });

    it('leaves out a right column with neither content nor an overlay', () => {
        render(
            <TabColumns
                left={<p>Left</p>}
                right={null}
                overlay={overlay('left')}
            />,
        );

        expect(screen.queryByTestId('column-right')).not.toBeInTheDocument();
        expect(screen.getByTestId('column-left')).toContainElement(
            screen.getByRole('dialog', { name: 'Overlay' }),
        );
    });

    it('draws both columns with the overlay over its own, as before', () => {
        render(
            <TabColumns
                left={<p>Left</p>}
                right={<p>Right</p>}
                overlay={overlay('right')}
            />,
        );
        const right = screen.getByTestId('column-right');

        expect(right).toContainElement(screen.getByText('Right'));
        expect(right).toContainElement(
            screen.getByRole('dialog', { name: 'Overlay' }),
        );
    });
});
