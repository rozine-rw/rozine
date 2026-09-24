import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AuditStepKey } from '@/types/auditor';

type StepState = 'done' | 'current' | 'todo';

const BAR: Record<StepState, string> = {
    done: 'bg-rz-positive',
    current: 'bg-[#0c1830] dark:bg-rz-ink',
    todo: 'bg-rz-secondary dark:bg-rz-surface-muted',
};

/**
 * The procedure's fixed-order step bar (design L1030–1034, L1246–1250): done green, current ink,
 * to do grey. The order and each step's state are the server's.
 */
export function StepBar({
    steps,
}: {
    steps: { key: AuditStepKey; state: StepState }[];
}) {
    const { t } = useTranslation();

    return (
        <ol
            aria-label={t('auditor.audit.steps')}
            className="mt-3.5 flex gap-1.5"
        >
            {steps.map((step) => (
                <li
                    key={step.key}
                    aria-current={step.state === 'current' ? 'step' : undefined}
                    className="flex-1"
                >
                    <span
                        aria-hidden
                        className={cn(
                            'block h-[5px] rounded-[3px]',
                            BAR[step.state],
                        )}
                    />
                    <span
                        className={cn(
                            'mt-[5px] block text-center text-[10.5px] font-bold',
                            step.state === 'todo'
                                ? 'text-rz-secondary'
                                : 'text-rz-ink',
                        )}
                    >
                        {t(`auditor.audit.step.${step.key}`)}
                    </span>
                </li>
            ))}
        </ol>
    );
}
