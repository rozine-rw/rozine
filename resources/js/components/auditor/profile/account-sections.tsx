import { Link } from '@inertiajs/react';
import { DIVIDER } from '@/components/auditor/ui';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { AuditorProfileProps } from '@/types/auditor';

const CARD =
    'mt-2.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface';

function Heading({ label }: { label: string }) {
    return (
        <h2 className="text-[11px] font-bold tracking-[.06em] text-rz-slate uppercase">
            {label}
        </h2>
    );
}

/**
 * Personal & contact (design L740–760): the account's own email. No phone, address or district of
 * operation is held for a partner, so those rows read "Not provided" and nothing is editable.
 */
export function ContactSection({
    contact,
}: {
    contact: AuditorProfileProps['contact'];
}) {
    const { t } = useTranslation();
    const rows: {
        key: 'phone' | 'email' | 'address' | 'district';
        icon: IconName;
        value: string | null;
    }[] = [
        { key: 'phone', icon: 'phone', value: contact.phone },
        { key: 'email', icon: 'mail', value: contact.email },
        { key: 'address', icon: 'pin', value: contact.address },
        { key: 'district', icon: 'compass', value: null },
    ];

    return (
        <>
            <Heading label={t('auditor.profile.contact.title')} />
            <dl className={CARD}>
                {rows.map((row) => (
                    <div
                        key={row.key}
                        className={cn(
                            'flex items-center gap-3 border-b px-[15px] py-3 last:border-b-0',
                            DIVIDER,
                        )}
                    >
                        <span className="flex size-[30px] shrink-0 items-center justify-center rounded-[9px] bg-rz-page text-[15px] dark:bg-rz-surface-muted">
                            <Icon name={row.icon} tone="amber" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <dt className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
                                {t(`auditor.profile.contact.${row.key}`)}
                            </dt>
                            <dd
                                className={cn(
                                    'mt-0.5 truncate text-[13px] font-semibold',
                                    row.value === null
                                        ? 'text-rz-muted'
                                        : 'text-rz-ink',
                                )}
                            >
                                {row.value ??
                                    t('auditor.profile.contact.not_provided')}
                            </dd>
                        </div>
                    </div>
                ))}
            </dl>
        </>
    );
}

const TELEMETRY = [
    'clock_expiries',
    'variance',
    'on_time',
    'strikes',
    'defended',
    'first_note',
] as const;

type TelemetryKey = (typeof TELEMETRY)[number];

/**
 * Audit-the-Auditor telemetry (design L915–941). On-time closing replaces the design's pass rate:
 * partners record findings, they do not pass anyone. A metric Rozine does not measure yet reads
 * as a dash, and nothing can be disputed until there is a record to dispute.
 */
export function TelemetrySection({
    onTimePct,
    jobsDone,
}: {
    onTimePct: number | null;
    jobsDone: number;
}) {
    const { t } = useTranslation();
    const rows: { key: TelemetryKey; value: string | null; sub: string }[] =
        TELEMETRY.map((key) =>
            key === 'on_time'
                ? {
                      key,
                      value: onTimePct === null ? null : `${onTimePct}%`,
                      sub: t('auditor.profile.telemetry.jobs_done', {
                          count: jobsDone,
                      }),
                  }
                : {
                      key,
                      value: null,
                      sub: t(`auditor.profile.telemetry.${key}_sub`),
                  },
        );

    return (
        <>
            <Heading label={t('auditor.profile.telemetry.title')} />
            <ul className={CARD}>
                {rows.map((row) => (
                    <li
                        key={row.key}
                        className={cn(
                            'flex items-center gap-3 border-b px-[15px] py-[13px] last:border-b-0',
                            DIVIDER,
                        )}
                    >
                        <span
                            aria-hidden
                            className={cn(
                                'size-[9px] shrink-0 rounded-full',
                                row.value === null
                                    ? 'bg-rz-faint'
                                    : 'bg-rz-positive',
                            )}
                        />
                        <div className="min-w-0 flex-1">
                            <p className="text-[13px] font-semibold text-rz-ink">
                                {t(`auditor.profile.telemetry.${row.key}`)}
                            </p>
                            <p className="mt-px text-[11px] text-rz-secondary">
                                {row.sub}
                            </p>
                        </div>
                        <span className="shrink-0 text-sm font-bold text-rz-ink">
                            {row.value ?? '—'}
                        </span>
                    </li>
                ))}
            </ul>
            <p className="mt-3 px-1 text-[11px] leading-normal text-rz-secondary">
                {t('auditor.profile.telemetry.note')}
            </p>
        </>
    );
}

/**
 * Security & devices: whether two-factor sign-in is on and the way to change the password, both
 * managed on the account's security settings. No device list is held, so none is shown.
 */
export function SecuritySection({
    security,
    settings,
}: {
    security: AuditorProfileProps['security'];
    settings: RouteLink;
}) {
    const { t } = useTranslation();
    const row = cn(
        'flex w-full items-center gap-3 border-b p-[15px] text-left last:border-b-0',
        DIVIDER,
    );

    return (
        <>
            <Heading label={t('auditor.profile.security.title')} />
            <div className={CARD}>
                <Link href={settings} className={row}>
                    <span className="flex size-[30px] shrink-0 items-center justify-center rounded-[9px] bg-rz-page text-[15px] dark:bg-rz-surface-muted">
                        <Icon name="lock" tone="amber" />
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="block text-[13.5px] font-semibold text-rz-ink">
                            {t('auditor.profile.security.two_factor')}
                        </span>
                        <span className="block text-[11.5px] text-rz-secondary">
                            {t(
                                security.two_factor
                                    ? 'auditor.profile.security.two_factor_on'
                                    : 'auditor.profile.security.two_factor_off',
                            )}
                        </span>
                    </span>
                    <span aria-hidden className="text-rz-secondary">
                        ›
                    </span>
                </Link>
                <Link href={settings} className={row}>
                    <span className="flex size-[30px] shrink-0 items-center justify-center rounded-[9px] bg-rz-page text-[15px] dark:bg-rz-surface-muted">
                        <Icon name="key" tone="amber" />
                    </span>
                    <span className="flex-1 text-[13.5px] font-semibold text-rz-ink">
                        {t('auditor.profile.security.password')}
                    </span>
                    <span aria-hidden className="text-rz-secondary">
                        ›
                    </span>
                </Link>
            </div>
        </>
    );
}

/**
 * Terms & legal: the engagement documents the partner accepts — the platform MSA and the agreed
 * procedures — opened on their own page. Without an engagement summary there is nothing to open.
 */
export function LegalSection({
    engagement,
}: {
    engagement: AuditorProfileProps['engagement'];
}) {
    const { t } = useTranslation();

    if (engagement === null) {
        return <PendingSection kind="legal" />;
    }

    return (
        <>
            <Heading label={t('auditor.profile.legal.title')} />
            <div className={CARD}>
                <Link
                    href={engagement.link}
                    className="flex w-full items-center gap-3 p-[15px] text-left"
                >
                    <span className="flex size-[30px] shrink-0 items-center justify-center rounded-[9px] bg-rz-page text-[15px] dark:bg-rz-surface-muted">
                        <Icon name="document" tone="amber" />
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="block text-[13.5px] font-semibold text-rz-ink">
                            {t('auditor.profile.legal.engagement')}
                        </span>
                        <span className="block text-[11.5px] text-rz-secondary">
                            {t(`auditor.profile.legal.${engagement.status}`)}
                        </span>
                    </span>
                    <span aria-hidden className="text-rz-secondary">
                        ›
                    </span>
                </Link>
            </div>
        </>
    );
}

const PENDING_ICON: Record<
    'earnings' | 'payout' | 'learn' | 'legal',
    IconName
> = {
    earnings: 'money-bag',
    payout: 'bank',
    learn: 'graduation',
    legal: 'document',
};

/**
 * A section with nothing to read yet: no earnings or payout-account read, and no approved academy
 * lessons. The design draws each with content, so this is its empty state.
 */
export function PendingSection({
    kind,
}: {
    kind: 'earnings' | 'payout' | 'learn' | 'legal';
}) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col items-center px-[22px] py-14 text-center">
            <span
                aria-hidden
                className="flex size-[72px] items-center justify-center rounded-[20px] bg-rz-accent-soft text-[26px]"
            >
                <Icon name={PENDING_ICON[kind]} tone="amber" />
            </span>
            <p className="mt-4 text-[17px] font-semibold text-rz-ink">
                {t(`auditor.profile.pending.${kind}_title`)}
            </p>
            <p className="mt-1.5 max-w-[260px] text-[13px] leading-normal text-rz-secondary">
                {t(`auditor.profile.pending.${kind}_body`)}
            </p>
        </div>
    );
}
