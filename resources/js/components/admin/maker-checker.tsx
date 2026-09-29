import { formatTimestamp } from '@/components/admin/format';
import { CAPTION, EXPLAIN } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { Attribution } from '@/types/admin';

type Step = {
    key: 'maker' | 'checker';
    by: Attribution | null;
};

/**
 * Maker-checker presentation (MVP-ADMIN-AC-03). The design has no dual-approval screen, so this
 * is composed from its own queue card (T579–590: white, #eaeef6 edge, a 4px accent left border,
 * 16px radius) and the stage copy style. Step one is the maker; step two must be a different
 * person. When the viewer made the request, the checker step says so — the server offers them
 * no approve action (no self-approval).
 */
export function MakerChecker({
    maker,
    checker,
    viewerIsMaker,
    waiting,
}: {
    maker: Attribution | null;
    checker: Attribution | null;
    viewerIsMaker: boolean;
    /** The request is waiting on the second person right now. */
    waiting: boolean;
}) {
    const { t } = useTranslation();
    const steps: Step[] = [
        { key: 'maker', by: maker },
        { key: 'checker', by: checker },
    ];

    return (
        <ol
            aria-label={t('admin.maker_checker.label')}
            className="flex flex-col gap-2.5"
        >
            {steps.map((step, index) => {
                const done = step.by !== null;
                const pending = !done && step.key === 'checker' && waiting;

                return (
                    <li
                        key={step.key}
                        className={cn(
                            'rounded-2xl border border-l-4 border-[#eaeef6] bg-rz-surface px-[18px] py-3.5 shadow-[0_1px_2px_rgba(16,32,58,.04),0_12px_28px_-20px_rgba(16,32,58,.18)] dark:border-rz-border dark:shadow-none',
                            done
                                ? 'border-l-[#1d9e75] dark:border-l-[#1d9e75]'
                                : pending
                                  ? 'border-l-[#7c3aed] dark:border-l-[#7c3aed]'
                                  : 'border-l-[#dbe3f0] dark:border-l-rz-border',
                        )}
                    >
                        <div className="flex flex-wrap items-center gap-2">
                            <span
                                className={cn(
                                    'text-[10px] font-bold tracking-[.06em] uppercase',
                                    CAPTION,
                                )}
                            >
                                {t('admin.maker_checker.step', {
                                    n: index + 1,
                                })}
                            </span>
                            <span className="text-[14px] font-bold text-rz-ink">
                                {t(`admin.maker_checker.${step.key}_title`)}
                            </span>
                            {pending && (
                                <span className="rounded-md bg-[rgba(124,58,237,.12)] px-2 py-0.5 text-[10px] font-bold text-[#7c3aed] dark:text-[#b199fb]">
                                    {t('admin.maker_checker.awaiting')}
                                </span>
                            )}
                        </div>
                        {step.by === null ? (
                            <p
                                className={cn(
                                    'mt-1.5 text-[12.5px] leading-[1.55]',
                                    EXPLAIN,
                                )}
                            >
                                {pending && viewerIsMaker
                                    ? t('admin.maker_checker.self_blocked')
                                    : t(
                                          `admin.maker_checker.${step.key}_empty`,
                                      )}
                            </p>
                        ) : (
                            <>
                                <p className="mt-1.5 text-[12.5px] text-rz-slate">
                                    {t('admin.maker_checker.by', {
                                        actor: step.by.actor,
                                        at: formatTimestamp(step.by.at),
                                    })}
                                </p>
                                {step.by.reason !== null && (
                                    <p
                                        className={cn(
                                            'mt-1 text-[12.5px] leading-[1.55]',
                                            EXPLAIN,
                                        )}
                                    >
                                        {t('admin.trail.reason', {
                                            reason: step.by.reason,
                                        })}
                                    </p>
                                )}
                            </>
                        )}
                    </li>
                );
            })}
        </ol>
    );
}
