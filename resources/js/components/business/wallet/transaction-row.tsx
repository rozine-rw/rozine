import { cn } from '@/lib/utils';
import type { WalletTransaction } from '@/types/business';

/** The design's in/out arrow tile (`_ic`, L3355–3360): green down-arrow in, grey up-arrow out. */
export function DirectionTile({
    direction,
    large = false,
}: {
    direction: WalletTransaction['direction'];
    large?: boolean;
}) {
    const inbound = direction === 'in';

    return (
        <span
            aria-hidden
            className={cn(
                'flex shrink-0 items-center justify-center rounded-[10px]',
                large ? 'size-[38px] scale-[1.15]' : 'size-8',
                inbound ? 'bg-rz-accent-soft' : 'bg-[rgba(194,102,31,.10)]',
            )}
        >
            <svg viewBox="0 0 24 24" fill="none" className="size-4">
                {inbound ? (
                    <path
                        d="M12 4v10M8 11l4 4 4-4M5 20h14"
                        stroke="currentColor"
                        className="text-rz-accent-app-text"
                        strokeWidth="1.9"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                ) : (
                    <path
                        d="M12 20V10M8 13l4-4 4 4M5 4h14"
                        stroke="currentColor"
                        className="text-rz-secondary"
                        strokeWidth="1.9"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                )}
            </svg>
        </span>
    );
}
