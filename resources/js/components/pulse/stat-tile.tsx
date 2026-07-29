import type { ReactNode } from 'react';

/**
 * One of the three live figures that sit at the top of each panel.
 *
 * A figure may carry a suffix — the term an average yield is measured over —
 * which sits on the same baseline a size down.
 */
export function StatTile({
    label,
    color,
    muted = false,
    suffix,
    children,
}: {
    label: string;
    color: string;
    muted?: boolean;
    suffix?: string;
    children: ReactNode;
}) {
    return (
        <div className="min-w-0 rounded-[10px] border border-[var(--rz-tile-border)] bg-[var(--rz-tile-bg)] p-[9px] md:px-3 md:py-2.5">
            <div className="flex items-center gap-[5px] text-[8px] font-bold tracking-[var(--rz-stat-tracking)] whitespace-nowrap text-[var(--rz-stat-label)] md:text-[11px]">
                <span className="relative h-1.5 w-1.5 shrink-0">
                    <span className="absolute inset-0 rounded-full bg-[var(--rz-green-dot)]" />
                    <span className="absolute inset-0 animate-[rzp-ping_1.8s_ease-out_infinite] rounded-full bg-[var(--rz-green-dot)]" />
                </span>
                {label}
            </div>
            <div
                className={`rz-num mt-2 text-[13px] leading-none tracking-[-0.01em] whitespace-nowrap md:text-[17px] ${
                    suffix
                        ? 'flex items-baseline gap-[3px]'
                        : 'overflow-hidden text-ellipsis'
                } ${muted ? 'font-semibold' : 'font-bold dark:font-semibold'}`}
                style={{ color }}
            >
                {children}
                {suffix && (
                    <span className="text-[7.5px] font-bold tracking-[0.02em] text-[var(--rz-hint)] md:text-[10px]">
                        {suffix}
                    </span>
                )}
            </div>
        </div>
    );
}

/**
 * The small currency prefix that leads a figure.
 */
export function StatCurrency() {
    return (
        <span className="mr-[3px] align-baseline text-[9px] font-bold tracking-[0.04em] text-[var(--rz-currency)] md:text-[11px]">
            RWF
        </span>
    );
}
