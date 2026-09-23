import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { ApplicationState, EngineDecision } from '@/types/admin';

/** Application state colours (design S5470): blue, amber, purple, deep orange, green, red. */
const STATE_CHIP: Record<ApplicationState, string> = {
    submitted:
        'bg-[rgba(30,58,255,.12)] text-rz-accent-app-text dark:bg-[rgba(99,120,255,.18)]',
    under_review:
        'bg-[rgba(210,120,45,.14)] text-[#c2661f] dark:text-[#f0a060]',
    info_requested:
        'bg-[rgba(124,58,237,.12)] text-[#7c3aed] dark:text-[#b199fb]',
    escalated: 'bg-[rgba(194,65,12,.12)] text-[#c2410c] dark:text-[#f59a6b]',
    approved: 'bg-[rgba(29,158,117,.12)] text-[#1d9e75] dark:text-[#3fcda0]',
    rejected: 'bg-[rgba(255,77,79,.12)] text-[#e5484d] dark:text-[#ff6b6f]',
};

export function ApplicationStateChip({
    state,
    className,
}: {
    state: ApplicationState;
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <span
            className={cn(
                'inline-flex shrink-0 rounded-lg px-[11px] py-[5px] text-[11.5px] font-semibold whitespace-nowrap',
                STATE_CHIP[state],
                className,
            )}
        >
            {t(`admin.applications.state.${state}`)}
        </span>
    );
}

/** Engine recommendation colours: green to approve, red to reject, amber for audit or review. */
export const DECISION_TONE: Record<
    EngineDecision['code'],
    { chip: string; box: string; text: string }
> = {
    approve: {
        chip: 'bg-[rgba(29,158,117,.15)] text-[#1d9e75] dark:text-[#3fcda0]',
        box: 'bg-[rgba(29,158,117,.12)]',
        text: 'text-[#1d9e75] dark:text-[#3fcda0]',
    },
    reject: {
        chip: 'bg-[rgba(255,77,79,.15)] text-[#e5484d] dark:text-[#ff6b6f]',
        box: 'bg-[rgba(255,77,79,.12)]',
        text: 'text-[#e5484d] dark:text-[#ff6b6f]',
    },
    audit: {
        chip: 'bg-[rgba(210,120,45,.15)] text-[#c2661f] dark:text-[#f0a060]',
        box: 'bg-[rgba(210,120,45,.14)]',
        text: 'text-[#c2661f] dark:text-[#f0a060]',
    },
    review: {
        chip: 'bg-[rgba(210,120,45,.15)] text-[#c2661f] dark:text-[#f0a060]',
        box: 'bg-[rgba(210,120,45,.14)]',
        text: 'text-[#c2661f] dark:text-[#f0a060]',
    },
};
