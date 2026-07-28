import type { ReactNode } from 'react';

/**
 * The sheet both waitlist flows are claimed in.
 */
export function PulseModal({
    label,
    onClose,
    children,
}: {
    label: string;
    onClose: () => void;
    children: ReactNode;
}) {
    return (
        <>
            <div
                onClick={onClose}
                className="fixed inset-0 z-50 bg-[var(--rz-overlay)] backdrop-blur-[5px]"
                aria-hidden="true"
            />
            <div className="fixed inset-0 z-[51] flex items-center justify-center overflow-y-auto p-3">
                <div
                    onClick={(event) => event.stopPropagation()}
                    role="dialog"
                    aria-modal="true"
                    aria-label={label}
                    className="w-full max-w-[500px] animate-[rzp-in_0.3s_both] overflow-hidden rounded-2xl border border-[var(--rz-modal-border)] bg-[var(--rz-modal-bg)] shadow-[var(--rz-modal-shadow)]"
                >
                    <div className="flex items-center justify-between border-b border-[var(--rz-modal-line)] px-5 py-4">
                        <span className="text-[11px] font-bold tracking-[0.16em] text-[var(--rz-dim)]">
                            {label}
                        </span>
                        <button
                            type="button"
                            onClick={onClose}
                            aria-label="Close"
                            className="h-7 w-7 cursor-pointer rounded-lg border border-[var(--rz-seg-border)] bg-transparent text-[15px] text-[var(--rz-muted)]"
                        >
                            ×
                        </button>
                    </div>
                    <div className="px-[18px] pt-3.5 pb-4">{children}</div>
                </div>
            </div>
        </>
    );
}

/**
 * The heading that opens a flow.
 */
export function ModalHeading({
    title,
    subtitle,
}: {
    title: string;
    subtitle: string;
}) {
    return (
        <>
            <div className="text-[20px] font-bold tracking-[-0.02em] text-[var(--rz-fg-strong)]">
                {title}
            </div>
            <div className="mt-1 text-[13px] text-[var(--rz-muted)]">
                {subtitle}
            </div>
        </>
    );
}

/**
 * The button that commits a signup.
 */
export function ModalSubmit({
    accent,
    enabled,
    processing,
    onClick,
    children,
}: {
    accent: string;
    enabled: boolean;
    processing: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            disabled={!enabled || processing}
            onClick={onClick}
            style={{
                background: enabled ? accent : 'var(--rz-disabled-bg)',
                color: enabled ? '#fff' : 'var(--rz-disabled-fg)',
                cursor: enabled ? 'pointer' : 'not-allowed',
            }}
            className="rz-cta mt-1 h-12 w-full rounded-[10px] border-none text-[14px] font-semibold transition-[filter] duration-150"
        >
            {children}
        </button>
    );
}
