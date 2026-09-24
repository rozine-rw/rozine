import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type WizardCtaProps = Omit<ComponentProps<'button'>, 'className'> & {
    ready: boolean;
    busy?: boolean;
};

/**
 * The wizard's pinned action (design L2457). Until the step is complete it stays grey but still
 * answers a tap — with a reminder rather than silence, as the design does.
 */
export function WizardCta({
    ready,
    busy = false,
    children,
    ...props
}: WizardCtaProps) {
    return (
        <button
            type="submit"
            aria-disabled={!ready || undefined}
            aria-busy={busy || undefined}
            disabled={busy}
            className={cn(
                'h-[54px] w-full rounded-2xl text-[15.5px] font-semibold lg:h-[50px]',
                ready
                    ? 'bg-rz-accent-fill text-white'
                    : 'bg-rz-page text-rz-secondary',
            )}
            {...props}
        >
            {children}
        </button>
    );
}
