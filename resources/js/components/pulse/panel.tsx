import type { ReactNode, RefObject } from 'react';

const TONES = {
    investor: {
        accent: '#0a5cff',
        shadow: 'var(--rz-panel-shadow-blue)',
        tick: 'rgba(10,92,255,.12)',
        tickStroke: '#0a5cff',
    },
    business: {
        accent: '#0f7a3d',
        shadow: 'var(--rz-panel-shadow-green)',
        tick: 'rgba(16,161,80,.13)',
        tickStroke: '#0f7a3d',
    },
};

/**
 * The card each side of the waitlist is worked out inside: a two-pixel border
 * in its own colour, under a solid bar of the same.
 */
export function PulsePanel({
    tone,
    panelRef,
    children,
}: {
    tone: 'investor' | 'business';
    panelRef: RefObject<HTMLDivElement | null>;
    children: ReactNode;
}) {
    const { accent, shadow } = TONES[tone];

    return (
        <div
            ref={panelRef}
            className="relative flex flex-col overflow-hidden rounded-[18px] border-2 bg-[var(--rz-panel-bg)] px-[14px] pt-[22px] pb-[15px] md:px-[19px] md:pt-[25px] md:pb-[19px]"
            style={{ borderColor: accent, boxShadow: shadow }}
        >
            <div
                className="absolute top-0 right-0 left-0 h-[7px]"
                style={{ background: accent }}
            />
            {children}
        </div>
    );
}

/**
 * What a side of the waitlist gets, or has to bring, ticked off in a list.
 */
export function PanelCriteria({
    tone,
    items,
}: {
    tone: 'investor' | 'business';
    items: string[];
}) {
    const { tick, tickStroke } = TONES[tone];

    return (
        <div className="mt-[13px] flex flex-col gap-[7px]">
            {items.map((item) => (
                <div
                    key={item}
                    className="flex items-start gap-2 text-[13px] leading-[1.45] text-[var(--rz-criteria)]"
                >
                    <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        className="mt-0.5 shrink-0"
                    >
                        <circle cx="12" cy="12" r="9.5" fill={tick} />
                        <path
                            d="m8 12.4 2.6 2.6L16.2 9.4"
                            stroke={tickStroke}
                            strokeWidth="2.1"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                    <span>{item}</span>
                </div>
            ))}
        </div>
    );
}

/**
 * The heading a panel opens with.
 */
export function PanelHeading({ children }: { children: ReactNode }) {
    return (
        <div className="mt-[15px] text-[22px] leading-[1.22] font-bold tracking-[-0.02em] text-[var(--rz-fg-title)]">
            {children}
        </div>
    );
}

/**
 * The label that names which side of the waitlist a panel is.
 */
export function PanelLabel({
    color,
    children,
}: {
    color: string;
    children: ReactNode;
}) {
    return (
        <div className="flex items-center justify-between">
            <span
                className="text-[11px] font-bold tracking-[0.16em]"
                style={{ color }}
            >
                {children}
            </span>
        </div>
    );
}
