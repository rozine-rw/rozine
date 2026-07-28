/**
 * The confirmation strip the waitlist answers with.
 */
export function PulseToast({ message }: { message: string }) {
    return (
        <div className="fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 animate-[rzp-in_0.25s_both] rounded-[10px] border border-[var(--rz-toast-border)] bg-[#12131a] px-[18px] py-3 text-[12.5px] font-semibold text-[var(--rz-fg)] shadow-[0_18px_40px_-14px_rgba(0,0,0,0.8)]">
            {message}
        </div>
    );
}
