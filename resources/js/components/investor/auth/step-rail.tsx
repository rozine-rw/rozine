import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

type StepRailProps = {
    /** The flow's kicker, e.g. "Identity check" or "Business access". */
    kicker: string;
    /** The steps in order, as the person reads them. */
    steps: { key: string; label: string }[];
    /** Index of the step on screen; `steps.length` once everything is sent. */
    current: number;
    /** Brand colour for the current step and ticks. */
    accent?: string;
    /** Opens a step when the person may move back to it. */
    onOpen?: (key: string) => void;
    /** Guidance shown under the steps. */
    children?: ReactNode;
};

/**
 * The desktop side rail of a long form (KYC, KYB): every step with its state, so the person can
 * see the whole journey at once, and short guidance below. Hidden on a phone, where the progress
 * bar above the form does the same job.
 */
export function StepRail({
    kicker,
    steps,
    current,
    accent = '#1e3aff',
    onOpen,
    children,
}: StepRailProps) {
    const { t } = useTranslation();

    return (
        <nav aria-label={t('kyc.rail.steps')} className="flex flex-col gap-6">
            <div>
                <p
                    className="text-[12px] font-semibold tracking-[.05em] uppercase"
                    style={{ color: accent }}
                >
                    {kicker}
                </p>
                <p className="mt-1 text-[13px] text-rz-secondary">
                    {t('kyc.rail.step_of', {
                        current: Math.min(current + 1, steps.length),
                        total: steps.length,
                    })}
                </p>
            </div>
            <ol className="flex flex-col">
                {steps.map((step, index) => {
                    const done = index < current;
                    const active = index === current;
                    const label = (
                        <>
                            <span
                                aria-hidden
                                className={cn(
                                    'flex size-7 shrink-0 items-center justify-center rounded-full border text-[12.5px] font-semibold',
                                    done || active
                                        ? 'border-transparent text-white'
                                        : 'border-rz-border bg-rz-surface text-rz-secondary',
                                )}
                                style={
                                    done || active
                                        ? { background: accent }
                                        : undefined
                                }
                            >
                                {done ? '✓' : index + 1}
                            </span>
                            <span
                                className={cn(
                                    'text-[14px]',
                                    active
                                        ? 'font-semibold text-rz-ink'
                                        : done
                                          ? 'text-rz-body'
                                          : 'text-rz-secondary',
                                )}
                            >
                                {step.label}
                            </span>
                        </>
                    );

                    return (
                        <li
                            key={step.key}
                            aria-current={active ? 'step' : undefined}
                            className="relative pb-5 last:pb-0"
                        >
                            {index < steps.length - 1 && (
                                <span
                                    aria-hidden
                                    className="absolute top-8 bottom-1 left-[13.5px] w-px bg-rz-border"
                                />
                            )}
                            {done && onOpen !== undefined ? (
                                <button
                                    type="button"
                                    onClick={() => onOpen(step.key)}
                                    className="flex items-center gap-3 text-left hover:underline"
                                >
                                    {label}
                                </button>
                            ) : (
                                <div className="flex items-center gap-3">
                                    {label}
                                </div>
                            )}
                        </li>
                    );
                })}
            </ol>
            {children}
        </nav>
    );
}

/** A short titled note for the rail (what happens next, privacy). */
export function RailNote({ title, body }: { title: string; body: string }) {
    return (
        <div className="rounded-2xl border border-rz-border bg-rz-surface px-4 py-3.5">
            <p className="text-[13px] font-semibold text-rz-ink">{title}</p>
            <p className="mt-1 text-[12.5px] leading-relaxed text-rz-secondary">
                {body}
            </p>
        </div>
    );
}
