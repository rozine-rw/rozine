import { CheckIcon } from '@/components/pulse/icons';

/**
 * Asks a business whether investors may see it by name. Left unticked it is
 * listed by its district alone.
 */
export function ListingConsent({
    checked,
    district,
    onChange,
}: {
    checked: boolean;
    district: string;
    onChange: (checked: boolean) => void;
}) {
    return (
        <button
            type="button"
            role="checkbox"
            aria-checked={checked}
            onClick={() => onChange(!checked)}
            className="flex w-full cursor-pointer items-start gap-2.5 rounded-[10px] border border-[var(--rz-seg-border)] bg-[var(--rz-seg-bg)] px-3 py-2.5 text-left"
        >
            <span
                className={`mt-px flex h-[15px] w-[15px] shrink-0 items-center justify-center rounded-[5px] border-[1.5px] text-white transition-all duration-150 ${
                    checked
                        ? 'border-[#12a150] bg-[#12a150]'
                        : 'border-[var(--rz-input-border)] bg-transparent'
                }`}
            >
                {checked && <CheckIcon />}
            </span>
            <span className="text-[11px] leading-[1.45] font-semibold text-[var(--rz-muted)]">
                Show my business to investors on Pulse.{' '}
                <span className="font-normal text-[var(--rz-dim)]">
                    Leave this off and you appear as “Business in{' '}
                    {district || 'your district'}”.
                </span>
            </span>
        </button>
    );
}
