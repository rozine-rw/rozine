import { useTranslation } from '@/hooks/use-translation';
import { timeLeft, useServerNow } from '@/lib/investor/server-clock';
import type { TimeLeft } from '@/lib/investor/server-clock';

export type TimeLeftTone = 'urgent' | 'near' | 'calm';

/** The design's clock colour (`clockInfo`): red inside a week, ink inside two, grey beyond. */
export function timeTone(left: TimeLeft): TimeLeftTone {
    if (left.kind !== 'days' || left.days <= 7) {
        return 'urgent';
    }

    return left.days <= 14 ? 'near' : 'calm';
}

export const TONE_TEXT: Record<TimeLeftTone, string> = {
    urgent: 'text-rz-danger-text',
    near: 'text-rz-ink',
    calm: 'text-rz-secondary',
};

/**
 * Time left on a raise, against the server clock: "12 days", then a live HH:MM:SS countdown in
 * the final day, then "Closing" once the server's close time has passed.
 */
export function useTimeLeft(
    closesAt: string,
    serverTime: string,
): { label: string; tone: TimeLeftTone; clock: boolean } {
    const { t } = useTranslation();
    const first = timeLeft(closesAt, Date.parse(serverTime));
    const now = useServerNow(serverTime, first.kind === 'clock');
    const left = timeLeft(closesAt, now);
    const label =
        left.kind === 'days'
            ? t('investor.deals.days_left', { count: left.days })
            : left.kind === 'clock'
              ? left.label
              : t('investor.deals.closing');

    return { label, tone: timeTone(left), clock: left.kind === 'clock' };
}
