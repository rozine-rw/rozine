import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { IconGradients } from '@/components/rozine/icon';
import { cn } from '@/lib/utils';

type InvestorAuthFrameProps = {
    title: string;
    /** `column`: the phone flow centred on a wide screen. `panel`: the design's desktop auth panel. */
    layout: 'column' | 'panel';
    children: ReactNode;
};

/**
 * The signed-out Investor surface. On a phone it fills the screen. On a wide screen it sits in the
 * design's desktop frame with no sidebar (Investor Desktop L111: signed out, the pane takes the
 * whole frame), either as the two-column auth panel or as the phone flow centred in the pane.
 */
export function InvestorAuthFrame({
    title,
    layout,
    children,
}: InvestorAuthFrameProps) {
    return (
        <div
            data-audience="investor"
            className="rz-surface min-h-svh bg-rz-surface lg:bg-rz-frame"
        >
            <Head title={title} />
            <IconGradients />
            <div className="lg:mx-auto lg:flex lg:min-h-svh lg:max-w-[var(--rz-desktop-max)] lg:p-7">
                <main
                    className={cn(
                        'relative min-h-svh lg:min-h-0 lg:flex-1 lg:rounded-[20px] lg:bg-rz-page',
                        layout === 'column' &&
                            'lg:flex lg:justify-center lg:overflow-y-auto',
                    )}
                >
                    {layout === 'column' ? (
                        <div className="mx-auto flex min-h-svh w-full max-w-[412px] flex-col lg:min-h-0 lg:bg-rz-surface">
                            {children}
                        </div>
                    ) : (
                        children
                    )}
                </main>
            </div>
        </div>
    );
}
