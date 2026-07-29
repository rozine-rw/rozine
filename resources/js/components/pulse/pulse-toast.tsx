/**
 * The confirmation strip the waitlist answers with.
 */
export function PulseToast({ message }: { message: string }) {
    return (
        <div className="fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 animate-[rzp-in_0.25s_both] rounded-[10px] border border-[var(--rz-toast-border)] bg-[var(--rz-toast-bg)] px-[18px] py-3 text-[12.5px] font-semibold text-[var(--rz-fg)] shadow-[var(--rz-toast-shadow)]">
            {message}
        </div>
    );
}
