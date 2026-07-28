import type { ReactNode } from 'react';

/**
 * One of the three live figures that sit at the top of each panel.
 */
export function StatTile({
    label,
    color,
    muted = false,
    children,
}: {
    label: string;
    color: string;
    muted?: boolean;
    children: ReactNode;
}) {
    return (
        <div className="min-w-0 rounded-[10px] border border-[var(--rz-tile-border)] bg-[var(--rz-tile-bg)] p-[9px] md:px-3 md:py-2.5">
            <div className="flex items-center gap-[5px] text-[8px] font-bold tracking-[var(--rz-stat-tracking)] whitespace-nowrap text-[var(--rz-stat-label)] md:text-[11px]">
                <span className="relative h-1.5 w-1.5 shrink-0">
                    <span className="absolute inset-0 rounded-full bg-[var(--rz-green-fg)]" />
                    <span className="absolute inset-0 animate-[rzp-ping_1.8s_ease-out_infinite] rounded-full bg-[var(--rz-green-fg)]" />
                </span>
                {label}
            </div>
            <div
                className={`rz-num mt-2 overflow-hidden text-[13px] leading-none tracking-[-0.01em] text-ellipsis whitespace-nowrap md:text-[17px] ${
                    muted ? 'font-semibold' : 'font-bold dark:font-semibold'
                }`}
                style={{ color }}
            >
                {children}
            </div>
        </div>
    );
}

/**
 * The small currency prefix that leads a figure.
 */
export function StatCurrency() {
    return (
        <span className="mr-[3px] align-top text-[8.5px] font-bold tracking-[0.04em] text-[var(--rz-currency)] md:text-[11px]">
            RWF
        </span>
    );
}
