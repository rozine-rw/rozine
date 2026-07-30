import { CheckIcon } from '@/components/pulse/icons';

/**
 * Asks a business whether to keep its name out of the listing. Ticked, it is
 * shown by the district it trades in; left alone, investors see it by name.
 */
export function ListingConsent({
    anonymous,
    district,
    onChange,
}: {
    anonymous: boolean;
    district: string;
    onChange: (anonymous: boolean) => void;
}) {
    return (
        <button
            type="button"
            role="checkbox"
            aria-checked={anonymous}
            onClick={() => onChange(!anonymous)}
            className="flex w-full cursor-pointer items-start gap-2.5 rounded-[10px] border border-[var(--rz-seg-border)] bg-[var(--rz-seg-bg)] px-3 py-2.5 text-left"
        >
            <span
                className={`mt-px flex h-[15px] w-[15px] shrink-0 items-center justify-center rounded-[5px] border-[1.5px] text-white transition-all duration-150 ${
                    anonymous
                        ? 'border-[#12a150] bg-[#12a150]'
                        : 'border-[var(--rz-input-border)] bg-transparent'
                }`}
            >
                {anonymous && <CheckIcon />}
            </span>
            <span className="text-[11px] leading-[1.45] font-semibold text-[var(--rz-muted)]">
                Hide my name — show me as “Business in{' '}
                {district || 'your district'}”.{' '}
                <span className="font-normal text-[var(--rz-dim)]">
                    Leave this off and investors see your business by name.
                </span>
            </span>
        </button>
    );
}
