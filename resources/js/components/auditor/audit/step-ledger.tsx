import { usePoll } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import type { ChangeEvent } from 'react';
import {
    STEP_FORM,
    StepHeading,
    VarianceChip,
    groupDigits,
    onlyDigits,
    useStepForm,
    useVariancePreview,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { useAuditorCommands } from '@/components/auditor/commands';
import { Tick } from '@/components/auditor/ui';
import { FieldError } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { LedgerDocument, LedgerStage } from '@/types/auditor';

/** Documents only: PDF or a clean scan (design L1159). */
const ACCEPT =
    '.pdf,.png,.tif,.tiff,.csv,application/pdf,image/png,image/tiff,text/csv';

/** How often a document still being read is re-checked. */
const SCAN_POLL_MS = 2000;

const RELOAD = ['stage', 'can_continue', 'hint'];

function DocumentRow({
    document,
    canUpload,
    onRescan,
}: {
    document: LedgerDocument;
    canUpload: boolean;
    onRescan: () => void;
}) {
    const { t } = useTranslation();
    const scanning = document.state === 'scanning';
    const parsed = document.state === 'parsed';

    return (
        <li
            className={cn(
                'rounded-xl border bg-rz-surface px-3.5 py-[13px]',
                parsed
                    ? 'border-rz-positive'
                    : 'border-[#f0dcb8] dark:border-[rgba(240,160,96,.3)]',
            )}
        >
            <div className="flex items-center gap-[11px]">
                <span className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-rz-page text-[14px] dark:bg-rz-surface-muted">
                    <Icon name="document" tone="amber" />
                </span>
                <span className="min-w-0 flex-1">
                    <span className="block truncate text-[12.5px] font-bold text-rz-ink">
                        {document.name}
                    </span>
                    <span
                        className={cn(
                            'mt-0.5 block text-[10.5px]',
                            scanning
                                ? 'text-rz-ink'
                                : parsed
                                  ? 'text-rz-positive'
                                  : 'text-rz-danger-text',
                        )}
                    >
                        {t(`auditor.ledger.doc.${document.state}`, {
                            detail: document.detail,
                        })}
                    </span>
                    {document.ingestion === 'INGESTED_NOT_AUDIT_APPROVED' && (
                        <span className="mt-1 inline-block rounded-[8px] bg-rz-page px-[7px] py-0.5 text-[10px] font-semibold text-rz-slate dark:bg-rz-surface-muted">
                            {t('auditor.ledger.ingested')}
                        </span>
                    )}
                </span>
                {scanning && (
                    <span
                        role="progressbar"
                        aria-label={t('auditor.ledger.reading')}
                        className="size-[15px] shrink-0 animate-[rz-aud-spin_.8s_linear_infinite] rounded-full border-2 border-[#c2661f] border-t-transparent"
                    />
                )}
            </div>
            {parsed && (
                <dl className="mt-[11px] rounded-[10px] border border-[#eef2f9] bg-[#f8fafc] px-3 py-[3px] dark:border-rz-divider dark:bg-rz-surface-sunken">
                    {document.fields.map((field) => (
                        <div
                            key={field.label}
                            className="flex items-center justify-between gap-2.5 border-b border-[#f1f4f9] py-2 last:border-b-0 dark:border-rz-divider"
                        >
                            <dt className="text-[11px] text-rz-secondary">
                                {field.label}
                            </dt>
                            <dd className="text-right text-[11px] font-bold text-rz-ink">
                                {field.value}
                            </dd>
                        </div>
                    ))}
                </dl>
            )}
            {document.state === 'failed' && (
                <>
                    <p className="mt-[11px] rounded-[10px] border border-[#f4c9c6] bg-rz-surface px-3 py-2.5 text-[11px] leading-[1.5] text-[#c8322b] dark:border-[rgba(255,107,111,.3)] dark:text-rz-danger-text">
                        {document.failure}
                    </p>
                    <button
                        type="button"
                        onClick={onRescan}
                        disabled={!canUpload}
                        className="mt-[9px] h-[38px] w-full rounded-[10px] border border-[#f0c9c6] bg-rz-surface text-[12px] font-bold text-[#c8322b] dark:border-[rgba(255,107,111,.3)] dark:text-rz-danger-text"
                    >
                        {t('auditor.ledger.rescan')}
                    </button>
                </>
            )}
        </li>
    );
}

/**
 * Inventory and ledger (design L1144–1201). The partner records the stock value they counted and
 * attaches the ledger books; the server reads each document, measures the variance against the
 * reported figure under the policy tolerance, and says whether anything must be explained at
 * sign-off. The "reconciles" tick is a factual attestation, not a verdict.
 */
export function StepLedger({
    stage,
    context,
}: {
    stage: LedgerStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const center = useAuditorCommands();
    const canUpload = center.allowed('audit.save_step') && center.idle;
    const { form, submit, errors } = useStepForm(context, {
        observed_stock: stage.observed_stock?.amount ?? '',
        reconciled: stage.reconciled,
    });
    const file = useRef<HTMLInputElement>(null);
    const replaces = useRef<string | null>(null);
    const scanning = stage.documents.some(
        (document) => document.state === 'scanning',
    );
    const parsed = stage.documents.filter(
        (document) => document.state === 'parsed',
    ).length;
    const { start, stop } = usePoll(
        SCAN_POLL_MS,
        { only: RELOAD },
        { autoStart: false },
    );

    useVariancePreview({ observed_stock: form.data.observed_stock });

    useEffect(() => {
        if (scanning) {
            start();
        } else {
            stop();
        }
    }, [scanning, start, stop]);

    const pick = (documentId: string | null) => {
        replaces.current = documentId;
        file.current?.click();
    };

    const upload = (event: ChangeEvent<HTMLInputElement>) => {
        const chosen = event.target.files?.[0];

        if (!chosen) {
            return;
        }

        /*
         * `audit.save_step` for the ledger, sent as multipart because it carries the file. Its
         * receipt verifies nothing and advances nothing on its own; a lost answer is looked up
         * and, if unrecorded, resent as the same upload with the same request.
         */
        center.send({
            name: 'audit.save_step',
            business: context.business,
            route: context.save,
            payload: {
                step: 'ledger',
                audit_id: context.auditId,
                expected_revision: context.revision,
                document: chosen,
                replaces: replaces.current,
            },
        });
        event.target.value = '';
    };

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.ledger.title')}
                lead={t('auditor.ledger.lead', { tolerance: stage.tolerance })}
            />
            <div className="mt-3.5 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                <div className="flex items-center justify-between">
                    <span className="text-[12.5px] text-rz-secondary">
                        {t('auditor.ledger.reported')}
                    </span>
                    <span className="text-[15px] font-bold text-rz-ink">
                        {formatRwf(stage.reported_stock)}
                    </span>
                </div>
                <label
                    htmlFor="auditor-observed-stock"
                    className="mt-3.5 block text-[11px] font-bold tracking-[.03em] text-rz-slate uppercase"
                >
                    {t('auditor.ledger.observed')}
                </label>
                <input
                    id="auditor-observed-stock"
                    inputMode="numeric"
                    autoComplete="off"
                    value={groupDigits(form.data.observed_stock)}
                    onChange={(event) =>
                        form.setData(
                            'observed_stock',
                            onlyDigits(event.target.value),
                        )
                    }
                    placeholder={t('auditor.ledger.observed_placeholder')}
                    aria-invalid={errors.observed_stock ? true : undefined}
                    className="mt-1.5 w-full rounded-xl border-[1.5px] border-[#dbe3f0] bg-[#f6f9fd] px-3.5 py-[13px] text-[15px] font-bold text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border dark:border-rz-border dark:bg-rz-surface-sunken"
                />
                {errors.observed_stock && (
                    <p className="mt-1.5 text-[11.5px] font-semibold text-rz-danger-text">
                        {errors.observed_stock}
                    </p>
                )}
                <VarianceChip variance={stage.variance} />
            </div>

            <div className="mt-4 flex items-baseline justify-between gap-2.5">
                <h4 className="text-[11px] font-bold tracking-[.03em] text-rz-slate uppercase">
                    {t('auditor.ledger.attachments')}
                </h4>
                <span
                    className={cn(
                        'text-[10.5px] font-semibold',
                        parsed > 0
                            ? 'text-rz-positive'
                            : 'text-[#c8322b] dark:text-rz-danger-text',
                    )}
                >
                    {stage.documents.length === 0
                        ? t('auditor.ledger.none')
                        : t('auditor.ledger.accepted', {
                              parsed,
                              count: stage.documents.length,
                          })}
                </span>
            </div>
            <p className="mt-1 text-[11.5px] leading-[1.5] text-rz-secondary">
                {t('auditor.ledger.rules')}
            </p>
            <ul className="mt-2.5 flex flex-col gap-[9px]">
                {stage.documents.map((document) => (
                    <DocumentRow
                        key={document.id}
                        document={document}
                        canUpload={canUpload}
                        onRescan={() => pick(document.id)}
                    />
                ))}
            </ul>
            <input
                ref={file}
                type="file"
                accept={ACCEPT}
                aria-label={t('auditor.ledger.file_input')}
                onChange={upload}
                className="sr-only"
                tabIndex={-1}
            />
            <button
                type="button"
                onClick={() => pick(null)}
                disabled={!canUpload}
                className="mt-[9px] flex w-full items-center justify-center gap-[9px] rounded-xl border-[1.5px] border-dashed border-rz-secondary bg-[#f8fafc] p-3.5 dark:bg-rz-surface-sunken"
            >
                <span aria-hidden className="text-[15px] text-rz-secondary">
                    ＋
                </span>
                <span className="text-[12.5px] font-bold text-rz-slate">
                    {stage.documents.length === 0
                        ? t('auditor.ledger.attach')
                        : t('auditor.ledger.attach_another')}
                </span>
            </button>
            <FieldError id="auditor-ledger-document-error">
                {errors.document}
            </FieldError>
            <FieldError id="auditor-ledger-replaces-error">
                {errors.replaces}
            </FieldError>

            <button
                type="button"
                role="checkbox"
                aria-checked={form.data.reconciled}
                disabled={!stage.ledger_ready}
                onClick={() =>
                    form.setData('reconciled', !form.data.reconciled)
                }
                className={cn(
                    'mt-3.5 flex w-full items-center gap-3 rounded-xl border border-rz-border p-3.5 text-left disabled:cursor-not-allowed',
                    stage.ledger_ready
                        ? 'bg-rz-surface'
                        : 'bg-[#f6f8fb] dark:bg-rz-surface-sunken',
                )}
            >
                <span
                    className={cn(
                        'flex size-6 shrink-0 items-center justify-center rounded-[10px] border-2 text-white',
                        form.data.reconciled
                            ? 'border-rz-positive bg-[#17795a]'
                            : 'border-rz-secondary bg-rz-surface',
                    )}
                >
                    {form.data.reconciled && (
                        <Tick className="size-3.5" strokeWidth={3} />
                    )}
                </span>
                <span
                    className={cn(
                        'min-w-0 flex-1 text-[12.5px] leading-[1.4]',
                        stage.ledger_ready
                            ? 'text-rz-slate'
                            : 'text-rz-secondary',
                    )}
                >
                    {t('auditor.ledger.reconciles')}
                    {!stage.ledger_ready && (
                        <span className="mt-1 block text-[11px] font-semibold text-[#c8322b] dark:text-rz-danger-text">
                            {t('auditor.ledger.reconciles_blocked')}
                        </span>
                    )}
                </span>
            </button>
        </form>
    );
}
