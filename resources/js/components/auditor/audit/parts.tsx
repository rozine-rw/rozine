import type { FormDataType } from '@inertiajs/core';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { useAgo } from '@/components/auditor/clock';
import { AMBER_TEXT, Eyebrow } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type { CapturePackage, Variance } from '@/types/auditor';

/** The form every step submits; the footer's primary button targets it by id. */
export const STEP_FORM = 'auditor-step';

/** What a step needs to commit itself: where to save and which revision it read. */
export type StepContext = {
    /** Remembers unsaved input across a reload (MVP-SPINE-AC-12): `<audit id>:<step>`. */
    rememberKey: string;
    step: string;
    revision: number;
    save: RouteAction;
    serverTime: string;
};

/**
 * One step's form. Continue posts the step's facts with the aggregate revision it was read at;
 * the server validates, records and answers with the next step or field errors. Nothing here
 * decides whether the step is complete.
 */
export function useStepForm<T extends FormDataType<T>>(
    context: StepContext,
    initial: T,
) {
    const form = useForm<T>(`auditor:${context.rememberKey}`, initial);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            step: context.step,
            revision: context.revision,
        }));
        form.post(context.save.url, { preserveScroll: true });
    };

    return { form, submit };
}

/**
 * Ask the server what a typed figure reconciles to, once typing settles: a partial reload of the
 * stage with the figure as a query, exactly as the Business quote works. The variance and its
 * tolerance come back from the server; the page never computes them.
 */
export function useVariancePreview(
    values: Record<string, string>,
    delay = 450,
): void {
    const first = useRef(true);
    const key = JSON.stringify(values);

    useEffect(() => {
        if (first.current) {
            first.current = false;

            return;
        }

        const timer = window.setTimeout(() => {
            router.reload({
                only: ['stage', 'can_continue', 'hint'],
                data: JSON.parse(key) as Record<string, string>,
            });
        }, delay);

        return () => window.clearTimeout(timer);
    }, [key, delay]);
}

/** Step title and lead (design L1039–1040). */
export function StepHeading({ title, lead }: { title: string; lead: string }) {
    return (
        <>
            <h3 className="text-[16px] font-bold text-rz-ink">{title}</h3>
            <p className="mt-1 text-[12.5px] leading-[1.5] text-rz-secondary">
                {lead}
            </p>
        </>
    );
}

/** Digits only, grouped for display as the partner types: "42,000,000". */
export const groupDigits = (digits: string): string =>
    digits === '' ? '' : Number(digits).toLocaleString('en-US');

export const onlyDigits = (value: string): string => value.replace(/\D/g, '');

/** "Within tolerance" / "Variance exceeds … tolerance" (design L1152, L1293). */
export function VarianceChip({
    variance,
    compact = false,
}: {
    variance: Variance | null;
    compact?: boolean;
}) {
    const { t } = useTranslation();
    const tone =
        variance === null
            ? 'border-rz-border bg-rz-page text-rz-secondary dark:bg-rz-surface-muted'
            : variance.within
              ? 'border-[#cfe9d8] bg-[rgba(29,158,117,.08)] text-rz-positive dark:border-[rgba(63,205,160,.3)]'
              : 'border-[#f4c9c6] bg-[rgba(229,72,77,.08)] text-rz-danger-text dark:border-[rgba(255,107,111,.3)]';

    if (compact) {
        return (
            <div
                aria-live="polite"
                className={cn(
                    'flex h-[42px] shrink-0 flex-col items-center justify-center rounded-[10px] border px-[11px]',
                    tone,
                )}
            >
                <span className="text-[10px] font-bold tracking-[.05em] uppercase">
                    {t('auditor.audit.variance')}
                </span>
                <span className="text-[13px] font-bold">
                    {variance === null ? '—' : `${variance.pct}%`}
                </span>
            </div>
        );
    }

    if (variance === null) {
        return null;
    }

    return (
        <div
            aria-live="polite"
            className={cn(
                'mt-3 flex items-center justify-between rounded-[10px] border px-[13px] py-[11px]',
                tone,
            )}
        >
            <span className="text-[12px] font-bold">
                {variance.within
                    ? t('auditor.audit.within_tolerance')
                    : t('auditor.audit.outside_tolerance')}
            </span>
            <span className="text-[15px] font-bold">{variance.pct}%</span>
        </div>
    );
}

const STATUS_DOT: Record<CapturePackage['status'], string> = {
    not_started: 'bg-rz-secondary',
    capturing: 'bg-[#c2661f]',
    syncing: 'bg-[#1e3aff]',
    needs_attention: 'bg-rz-danger',
    complete: 'bg-rz-positive',
};

/**
 * The capture companion handoff (D-04). Photos and the on-site check-in are taken in the narrow
 * native capture app, camera-only and signed on the device; the web shows where the package is
 * (MVP-AUDITOR-SCR-04-ST-01…03) and opens the app on this assignment.
 */
export function CaptureHandoff({
    capture,
    serverTime,
    action,
}: {
    capture: CapturePackage;
    serverTime: string;
    /** The label on the button that opens the app. */
    action: string;
}) {
    const { t } = useTranslation();
    const ago = useAgo(serverTime);
    const status =
        capture.status === 'needs_attention'
            ? t(
                  `auditor.capture.attention.${capture.attention ?? 'upload_failed'}`,
              )
            : t(`auditor.capture.status.${capture.status}`, {
                  received: capture.received,
                  expected: capture.expected,
              });

    return (
        <div className="mt-3.5 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
            <div className="flex items-start gap-[11px]">
                <span className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft text-rz-accent-app-text">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="size-[18px]"
                    >
                        <rect
                            x="6.5"
                            y="2.5"
                            width="11"
                            height="19"
                            rx="2.5"
                            stroke="currentColor"
                            strokeWidth="1.7"
                        />
                        <path
                            d="M10.5 18.5h3"
                            stroke="currentColor"
                            strokeWidth="1.7"
                            strokeLinecap="round"
                        />
                    </svg>
                </span>
                <div className="min-w-0 flex-1">
                    <p className="text-[13px] font-bold text-rz-ink">
                        {t('auditor.capture.title')}
                    </p>
                    <p className="mt-0.5 text-[11.5px] leading-[1.5] text-rz-secondary">
                        {t('auditor.capture.body')}
                    </p>
                </div>
            </div>
            <div
                role="status"
                className={cn(
                    'mt-3 flex items-start gap-2 rounded-[10px] border px-3 py-2.5',
                    capture.status === 'needs_attention'
                        ? 'border-[#f4c9c6] bg-[rgba(229,72,77,.06)] dark:border-[rgba(255,107,111,.3)]'
                        : 'border-[#eef2f9] bg-[#f8fafc] dark:border-rz-divider dark:bg-rz-surface-sunken',
                )}
            >
                <span
                    aria-hidden
                    className={cn(
                        'mt-[5px] size-[7px] shrink-0 rounded-full',
                        STATUS_DOT[capture.status],
                    )}
                />
                <span className="min-w-0 flex-1">
                    <span
                        className={cn(
                            'block text-[12px] font-semibold',
                            capture.status === 'needs_attention'
                                ? 'text-rz-danger-text'
                                : 'text-rz-ink',
                        )}
                    >
                        {status}
                    </span>
                    {capture.last_sync_at !== null && (
                        <span className="mt-px block text-[10.5px] text-rz-secondary">
                            {t('auditor.capture.last_sync', {
                                when: ago(capture.last_sync_at),
                            })}
                        </span>
                    )}
                </span>
            </div>
            <a
                href={capture.handoff.url}
                className="mt-3 flex h-[46px] w-full items-center justify-center rounded-xl bg-[#0c1830] text-[14px] font-bold text-white dark:bg-rz-accent-fill"
            >
                {action}
            </a>
        </div>
    );
}

/** An amber note panel (design L1079). */
export function AmberNote({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <div
            role="note"
            className="mt-3.5 rounded-2xl border border-[#f2d69a] bg-rz-surface p-3.5 dark:border-[rgba(240,160,96,.3)]"
        >
            <p className={cn('text-[12px] font-bold', AMBER_TEXT)}>{title}</p>
            <p className={cn('mt-1 text-[11.5px] leading-[1.5]', AMBER_TEXT)}>
                {children}
            </p>
        </div>
    );
}

/** A labelled group heading inside a step (design L1275, 11px/.05em). */
export function StepEyebrow({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <Eyebrow as="h4" className={cn('tracking-[.05em]', className)}>
            {children}
        </Eyebrow>
    );
}
