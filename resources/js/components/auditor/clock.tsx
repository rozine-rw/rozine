import { useEffect, useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/**
 * Time on the Auditor screens. Every clock runs from the server's `server_time`, never from the
 * browser clock alone (contract §4): the offset between the two is taken once and the display
 * ticks from there. The server still decides expiry; a clock at zero only says so.
 */
export function useServerNow(serverTime: string): number {
    const [offset] = useState(() => Date.parse(serverTime) - Date.now());
    const [now, setNow] = useState(() => Date.now() + offset);

    useEffect(() => {
        const timer = window.setInterval(
            () => setNow(Date.now() + offset),
            1000,
        );

        return () => window.clearInterval(timer);
    }, [offset]);

    return now;
}

const pad = (value: number): string => String(value).padStart(2, '0');

/** "20:59:55"; a passed deadline reads "00:00:00" (design L2065). */
export const formatCountdown = (remainingMs: number): string => {
    const seconds = Math.max(0, Math.floor(remainingMs / 1000));

    return `${pad(Math.floor(seconds / 3600))}:${pad(Math.floor((seconds % 3600) / 60))}:${pad(seconds % 60)}`;
};

const FOUR_HOURS = 4 * 3600 * 1000;
const TEN_HOURS = 10 * 3600 * 1000;

/** The clock chip's tint by time left (design L2066–2067): red under 4h, amber under 10h. */
const clockTone = (remainingMs: number): string => {
    if (remainingMs < FOUR_HOURS) {
        return 'bg-[rgba(229,72,77,.10)] text-rz-danger-text';
    }

    return remainingMs < TEN_HOURS
        ? 'bg-rz-accent-soft text-rz-ink'
        : 'bg-rz-page text-rz-ink dark:bg-rz-surface-muted';
};

type ClockChipProps = {
    serverTime: string;
    /** The deadline, or null while a job is only offered: the chip then shows the full window. */
    dueAt: string | null;
    /** The clock length in hours, shown before it starts. */
    hours?: number;
    size?: 'card' | 'sheet';
};

/** "TIME LEFT" and the ticking countdown (design L166, L1028). */
export function ClockChip({
    serverTime,
    dueAt,
    hours = 24,
    size = 'card',
}: ClockChipProps) {
    const { t } = useTranslation();
    const now = useServerNow(serverTime);
    const remaining = dueAt === null ? null : Date.parse(dueAt) - now;

    return (
        <div
            role="timer"
            aria-label={t('auditor.clock.label')}
            className={cn(
                'shrink-0 rounded-[10px] py-[5px] text-right',
                size === 'card' ? 'px-[9px]' : 'px-2.5',
                remaining === null
                    ? 'bg-rz-page text-rz-secondary dark:bg-rz-surface-muted'
                    : clockTone(remaining),
            )}
        >
            <p className="text-[10px] font-bold uppercase">
                {t('auditor.clock.time_left')}
            </p>
            <p
                className={cn(
                    'font-bold tabular-nums',
                    size === 'card' ? 'text-[14px]' : 'text-[15px]',
                )}
            >
                {remaining === null
                    ? formatCountdown(hours * 3600 * 1000)
                    : formatCountdown(remaining)}
            </p>
        </div>
    );
}

/** "3h ago", from the server's clock (design L2064). */
export function useAgo(serverTime: string): (at: string) => string {
    const { t } = useTranslation();
    const now = Date.parse(serverTime);

    return (at: string) => {
        const minutes = Math.round(Math.max(0, now - Date.parse(at)) / 60000);

        if (minutes < 60) {
            return t('auditor.time.minutes_ago', { count: minutes });
        }

        const hours = Math.round(minutes / 60);

        if (hours < 24) {
            return t('auditor.time.hours_ago', { count: hours });
        }

        return t('auditor.time.days_ago', { count: Math.round(hours / 24) });
    };
}

/** "14:02", Kigali time. */
export const formatKigaliTime = (iso: string): string =>
    new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
        timeZone: 'Africa/Kigali',
    }).format(new Date(iso));

/** The hour in Kigali at the server's time, for the design's greeting (L3031). */
export const kigaliHour = (serverTime: string): number =>
    Number(
        new Intl.DateTimeFormat('en-GB', {
            hour: 'numeric',
            hourCycle: 'h23',
            timeZone: 'Africa/Kigali',
        }).format(new Date(serverTime)),
    );
