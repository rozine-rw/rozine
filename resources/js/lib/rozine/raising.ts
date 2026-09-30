import type { CampaignProgressV2 } from '@/types/business';
import type {
    BusinessCampaignLifecycle,
    RaisingLifecycle,
} from '@/types/settlement';

/** Every open lifecycle the server reports (#96 5885379912). */
const RAISING: readonly string[] = [
    'live',
    'fully_reserved',
    'sold_out_pending_settlement',
    'inventory_unavailable',
    'closing_pending_settlement',
] satisfies RaisingLifecycle[];

/** Settling a full or past-deadline raise: the countdown no longer applies. */
export const SETTLING: readonly RaisingLifecycle[] = [
    'sold_out_pending_settlement',
    'closing_pending_settlement',
];

/** A whole-unit count as the server sends it: digits only. */
const UNITS = /^\d+$/;

const isUnits = (value: unknown): boolean =>
    typeof value === 'string' && UNITS.test(value);

/**
 * Whether a raising campaign's progress can be shown. The server supplies every figure and the
 * client never subtracts, so an unknown lifecycle, a missing unit bucket or amount, or a page
 * whose top-level lifecycle disagrees with its progress fails closed rather than guessing. Other
 * phases are read as they are.
 */
export function isReadableProgress(
    progress: CampaignProgressV2,
    lifecycle: BusinessCampaignLifecycle,
): boolean {
    if (progress.phase !== 'raising') {
        return true;
    }

    const units: Partial<Record<string, unknown>> = progress.units;

    return (
        RAISING.includes(progress.lifecycle) &&
        progress.lifecycle === lifecycle &&
        ['total', 'available', 'reserved', 'committed', 'unavailable'].every(
            (bucket) => isUnits(units[bucket]),
        ) &&
        [progress.committed, progress.reserved, progress.remaining].every(
            (money: unknown) =>
                typeof money === 'object' &&
                money !== null &&
                isUnits((money as { amount?: unknown }).amount),
        )
    );
}
