import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { StepEyebrow, StepHeading } from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import {
    NOTE_FIELD,
    ReasonSheet,
} from '@/components/auditor/sheets/reason-sheet';
import { DIVIDER } from '@/components/auditor/ui';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { SealStage } from '@/types/auditor';

/** The design's re-authentication PIN length (L1469–1485). */
export const PIN_LENGTH = 4;

const KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '', '0', 'back'];

const SUMMARY_TONE = {
    ok: 'text-rz-positive',
    flag: 'text-rz-danger-text',
    neutral: 'text-rz-ink',
} as const;

type Nested = 'preview' | 'pin' | 'suggest' | 'reject' | null;

/**
 * Sign off and seal (design L1405–1496). The partner records factual findings and an assessment
 * note, previews exactly what will be sealed, re-authenticates, and seals. The server checks the
 * PIN, licence standing, procedure and digest; the business co-signs afterwards. Monthly reports
 * can instead go back to the business or be rejected, each with a recorded reason.
 */
export function useSealFlow({
    stage,
    context,
    business,
    period,
    canContinue,
}: {
    stage: SealStage;
    context: StepContext;
    business: string;
    /** The audited month, or the empty string for a Flash Audit. */
    period: string;
    canContinue: boolean;
}): { body: ReactNode; footer: ReactNode; nested: ReactNode } {
    const { t } = useTranslation();
    const [nested, setNested] = useState<Nested>(null);
    const form = useForm({ note: stage.note.value, pin: '' });
    const close = () => {
        setNested(null);
        form.setData('pin', '');
    };
    const noteReady =
        !stage.note.required || form.data.note.trim().length >= stage.note.min;
    const ready = canContinue && noteReady;

    const press = (key: string) => {
        if (key === 'back') {
            form.setData('pin', form.data.pin.slice(0, -1));
        } else if (form.data.pin.length < PIN_LENGTH) {
            form.setData('pin', form.data.pin + key);
        }
    };

    const seal = () => {
        form.transform((data) => ({
            ...data,
            revision: context.revision,
            digest: stage.digest,
        }));
        form.post(stage.seal.url, {
            preserveScroll: true,
            onError: () => form.setData('pin', ''),
        });
    };

    const noteStatus = !stage.note.required
        ? t('auditor.seal.note_optional')
        : noteReady
          ? t('auditor.seal.note_done')
          : t('auditor.seal.note_required');

    const body = (
        <>
            <StepHeading
                title={t('auditor.seal.title')}
                lead={t('auditor.seal.lead')}
            />
            <dl className="mt-3.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {stage.summary.map((row) => (
                    <div
                        key={row.label}
                        className={cn(
                            'flex items-center gap-[11px] border-b px-[15px] py-3',
                            DIVIDER,
                        )}
                    >
                        <dt className="flex-1 text-[12.5px] text-rz-slate">
                            {row.label}
                        </dt>
                        <dd
                            className={cn(
                                'text-[12.5px] font-bold',
                                SUMMARY_TONE[row.tone],
                            )}
                        >
                            {row.value}
                        </dd>
                    </div>
                ))}
            </dl>
            <div className="mt-4 flex items-baseline justify-between gap-2.5">
                <StepEyebrow>
                    <label htmlFor="auditor-seal-note">
                        {t('auditor.seal.note')}
                    </label>
                </StepEyebrow>
                <span
                    className={cn(
                        'text-[11px] font-bold',
                        stage.note.required && !noteReady
                            ? 'text-rz-danger-text'
                            : stage.note.required
                              ? 'text-rz-positive'
                              : 'text-rz-secondary',
                    )}
                >
                    {noteStatus}
                </span>
            </div>
            <p className="mt-[5px] text-[11.5px] leading-[1.5] text-rz-secondary">
                {stage.note.why}
            </p>
            <textarea
                id="auditor-seal-note"
                value={form.data.note}
                maxLength={stage.note.max}
                onChange={(event) => form.setData('note', event.target.value)}
                placeholder={t('auditor.seal.note_placeholder')}
                aria-invalid={form.errors.note ? true : undefined}
                className={cn(NOTE_FIELD, 'mt-2 min-h-[84px]')}
            />
            <p className="mt-1 text-right text-[10.5px] text-rz-secondary">
                {t('auditor.seal.note_count', {
                    count: form.data.note.length,
                    max: stage.note.max,
                })}
            </p>
            <FieldError id="auditor-seal-note-error">
                {form.errors.note}
            </FieldError>
        </>
    );

    const footer = (
        <>
            <button
                type="button"
                disabled={!ready}
                onClick={() => setNested('preview')}
                className="h-[50px] w-full rounded-2xl bg-rz-accent-fill text-[14.5px] font-bold text-white disabled:cursor-not-allowed disabled:bg-rz-disabled disabled:text-rz-secondary"
            >
                {t('auditor.seal.preview')}
            </button>
            {(stage.suggest !== null || stage.reject !== null) && (
                <div className="flex gap-[9px]">
                    {stage.suggest !== null && (
                        <button
                            type="button"
                            onClick={() => setNested('suggest')}
                            className="h-11 flex-1 rounded-xl border border-rz-border bg-rz-surface text-[13px] font-bold text-rz-slate"
                        >
                            {t('auditor.seal.suggest')}
                        </button>
                    )}
                    {stage.reject !== null && (
                        <button
                            type="button"
                            onClick={() => setNested('reject')}
                            className="h-11 flex-1 rounded-xl border border-[#f2c4c4] bg-rz-surface text-[13px] font-bold text-[#d0342c] dark:border-[rgba(255,107,111,.3)] dark:text-rz-danger-text"
                        >
                            {t('auditor.seal.reject')}
                        </button>
                    )}
                </div>
            )}
        </>
    );

    let overlay: ReactNode = null;

    if (nested === 'suggest' && stage.suggest !== null) {
        overlay = (
            <ReasonSheet
                title={t('auditor.seal.suggest')}
                lead={t('auditor.seal.suggest_lead', { business })}
                label={t('auditor.decline.label')}
                placeholder={t('auditor.seal.suggest_placeholder')}
                submitLabel={t('auditor.seal.suggest_submit')}
                action={stage.suggest}
                payload={{ revision: context.revision }}
                onClose={close}
            />
        );
    } else if (nested === 'reject' && stage.reject !== null) {
        overlay = (
            <ReasonSheet
                title={t('auditor.seal.reject')}
                lead={t('auditor.seal.reject_lead', { business })}
                label={t('auditor.decline.label')}
                placeholder={t('auditor.seal.reject_placeholder')}
                submitLabel={t('auditor.seal.reject_submit')}
                action={stage.reject}
                payload={{ revision: context.revision }}
                destructive
                onClose={close}
            />
        );
    } else if (nested === 'preview' || nested === 'pin') {
        overlay = (
            <>
                <div className="fixed inset-0 z-[52] bg-[rgba(8,14,28,.45)] lg:absolute" />
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="auditor-findings-title"
                    className="fixed inset-x-0 top-[9%] bottom-0 z-[53] flex animate-[rz-sheetup_.3s_ease] flex-col overflow-hidden rounded-[20px_22px_0_0] bg-rz-surface lg:absolute"
                >
                    <div className="flex items-center gap-[11px] border-b border-rz-border px-[18px] pt-4 pb-[13px]">
                        <span className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] border border-[#d4af37] bg-rz-accent-soft">
                            <svg
                                viewBox="0 0 20 20"
                                aria-hidden
                                className="size-4"
                                fill="#d4af37"
                            >
                                <path
                                    fillRule="evenodd"
                                    d="M10 1.944A11.954 11.954 0 012.166 5 12.02 12.02 0 0010 18.2 12.02 12.02 0 0017.834 5 11.954 11.954 0 0110 1.944zM13.707 8.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-[10.5px] font-bold tracking-[.08em] text-rz-ink uppercase">
                                {t('auditor.seal.findings_eyebrow', {
                                    version: stage.procedure_version,
                                })}
                            </p>
                            <h3
                                id="auditor-findings-title"
                                className="truncate text-[15.5px] font-bold text-rz-ink"
                            >
                                {period === ''
                                    ? business
                                    : `${business} · ${period}`}
                            </h3>
                        </div>
                        <button
                            type="button"
                            onClick={close}
                            aria-label={t('auditor.sheet.close')}
                            className="flex size-8 shrink-0 items-center justify-center rounded-[10px] bg-[#eef2f8] text-[15px] text-rz-ink dark:bg-rz-surface-muted"
                        >
                            ✕
                        </button>
                    </div>
                    <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-4 pb-[18px]">
                        {nested === 'preview' ? (
                            <div className="flex flex-col gap-3">
                                {stage.findings.map((finding) => (
                                    <section
                                        key={finding.no}
                                        className="rounded-2xl border border-rz-border bg-[#f8fafc] px-3.5 py-[13px] dark:bg-rz-surface-sunken"
                                    >
                                        <p className="text-[10.5px] font-bold tracking-[.06em] text-rz-ink uppercase">
                                            {finding.no}
                                        </p>
                                        <h4 className="mt-[3px] text-[13px] font-bold text-rz-ink">
                                            {finding.title}
                                        </h4>
                                        <p className="mt-1.5 text-[12px] leading-[1.55] whitespace-pre-line text-rz-slate">
                                            {finding.body}
                                        </p>
                                    </section>
                                ))}
                                <div className="rounded-2xl bg-rz-page px-3.5 py-[13px] dark:bg-rz-surface-muted">
                                    <p className="text-[10.5px] font-bold tracking-[.06em] text-[#1e3aff] uppercase dark:text-rz-investor-text">
                                        {t('auditor.seal.digest')}
                                    </p>
                                    <p className="mt-[5px] text-[12px] tracking-[.01em] break-all text-rz-ink tabular-nums">
                                        {stage.digest}
                                    </p>
                                    <p className="mt-1.5 text-[10.5px] leading-[1.5] text-[#1e3aff] dark:text-rz-investor-text">
                                        {t('auditor.seal.digest_note')}
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <div className="pt-1.5 text-center">
                                <h4 className="text-[16px] font-bold text-rz-ink">
                                    {t('auditor.seal.pin_title')}
                                </h4>
                                <p className="mt-[5px] text-[12.5px] leading-[1.5] text-rz-secondary">
                                    {t('auditor.seal.pin_lead', {
                                        licence: stage.licence,
                                    })}
                                </p>
                                <div
                                    role="img"
                                    aria-label={t('auditor.seal.pin_entered', {
                                        count: form.data.pin.length,
                                        total: PIN_LENGTH,
                                    })}
                                    className="mt-[18px] flex justify-center gap-3"
                                >
                                    {Array.from(
                                        { length: PIN_LENGTH },
                                        (_, index) => (
                                            <span
                                                key={index}
                                                className={cn(
                                                    'size-3.5 rounded-full border-[1.5px]',
                                                    index < form.data.pin.length
                                                        ? 'border-[#0c1830] bg-[#0c1830] dark:border-rz-ink dark:bg-rz-ink'
                                                        : 'border-[#c9d2e0] bg-rz-surface dark:border-rz-border',
                                                )}
                                            />
                                        ),
                                    )}
                                </div>
                                <FieldError id="auditor-seal-pin-error">
                                    {form.errors.pin}
                                </FieldError>
                                <div className="mx-auto mt-5 grid max-w-[250px] grid-cols-3 gap-2.5">
                                    {KEYS.map((key, index) =>
                                        key === '' ? (
                                            <span key={index} />
                                        ) : (
                                            <button
                                                key={key}
                                                type="button"
                                                onClick={() => press(key)}
                                                aria-label={
                                                    key === 'back'
                                                        ? t(
                                                              'auditor.seal.pin_delete',
                                                          )
                                                        : undefined
                                                }
                                                className="h-[52px] rounded-2xl border border-rz-border bg-rz-surface text-[18px] font-semibold text-rz-ink"
                                            >
                                                {key === 'back' ? '⌫' : key}
                                            </button>
                                        ),
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                    <div className="border-t border-rz-border px-[18px] pt-3 pb-4">
                        {nested === 'preview' ? (
                            <button
                                type="button"
                                onClick={() => setNested('pin')}
                                className="h-[50px] w-full rounded-2xl bg-rz-page text-[14.5px] font-bold text-rz-ink dark:bg-rz-surface-muted"
                            >
                                {t('auditor.seal.apply')}
                            </button>
                        ) : (
                            <button
                                type="button"
                                onClick={seal}
                                disabled={
                                    form.data.pin.length < PIN_LENGTH ||
                                    form.processing
                                }
                                className="h-[50px] w-full rounded-2xl bg-[#17795a] text-[14.5px] font-bold text-white disabled:cursor-not-allowed disabled:bg-rz-disabled disabled:text-rz-secondary"
                            >
                                {t('auditor.seal.submit')}
                            </button>
                        )}
                    </div>
                </div>
            </>
        );
    }

    return { body, footer, nested: overlay };
}
