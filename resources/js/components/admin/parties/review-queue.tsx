import { Link } from '@inertiajs/react';
import { initialOf } from '@/components/admin/format';
import { CAPTION, CARD_SHADOW } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { LicenceQueueEntry } from '@/types/admin-audit';

/**
 * The Audit Partner network's review queues (design T640–706): partner change requests (licence
 * renewals) above the ICPAR verification desk (first licences), oldest first. Approve and reject
 * open the partner's 360 on that decision's reason stage, since every decision carries a reason;
 * a viewer who may not decide only gets Review.
 */
export function LicenceQueue({
    kind,
    entries,
}: {
    kind: 'requests' | 'desk';
    entries: LicenceQueueEntry[];
}) {
    const { t, locale } = useTranslation();
    const accent = kind === 'desk' ? '#c2661f' : '#1e3aff';

    return (
        <section
            aria-label={t(`admin.parties.queue.${kind}.title`)}
            className="mb-6"
        >
            <h2 className="mb-1 text-[15px] font-semibold text-rz-ink">
                {t(`admin.parties.queue.${kind}.title`)}
            </h2>
            <p className={cn('mb-3 text-[12px]', CAPTION)}>
                {t(`admin.parties.queue.${kind}.caption`)}
            </p>
            {entries.length === 0 ? (
                <div
                    className={cn(
                        'rounded-[18px] border border-rz-hairline bg-rz-surface p-7 text-center text-[13px] text-rz-muted',
                        CARD_SHADOW,
                    )}
                >
                    {t(`admin.parties.queue.${kind}.empty`)}
                </div>
            ) : (
                <ul className="flex flex-col gap-3">
                    {entries.map((entry) => (
                        <li
                            key={entry.id}
                            className={cn(
                                'rounded-2xl border border-l-4 border-rz-hairline bg-rz-surface px-[18px] py-4',
                                CARD_SHADOW,
                            )}
                            style={{ borderLeftColor: accent }}
                        >
                            <div className="flex flex-wrap items-center gap-3">
                                <div
                                    aria-hidden="true"
                                    className="flex size-[42px] shrink-0 items-center justify-center rounded-xl text-[16px] font-bold text-white"
                                    style={{ background: accent }}
                                >
                                    {initialOf(entry.name) || '?'}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-[14.5px] font-bold text-rz-ink">
                                            {entry.name.trim() === ''
                                                ? t(
                                                      'admin.parties.name_pending',
                                                  )
                                                : entry.name}
                                        </span>
                                        <span
                                            className="rounded-md px-2 py-0.5 text-[10px] font-bold whitespace-nowrap"
                                            style={{
                                                color: accent,
                                                background: `${accent}24`,
                                            }}
                                        >
                                            {t(
                                                `admin.parties.queue.${kind}.chip`,
                                            )}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex w-full gap-2 sm:w-auto sm:shrink-0">
                                    {entry.links.reject && (
                                        <Link
                                            href={entry.links.reject}
                                            className="flex-1 rounded-[10px] border border-[#fdeaea] bg-rz-surface px-3.5 py-[9px] text-center text-[12.5px] font-bold text-[#e5484d] sm:flex-none"
                                        >
                                            {t('admin.parties.queue.reject')}
                                        </Link>
                                    )}
                                    {entry.links.approve ? (
                                        <Link
                                            href={entry.links.approve}
                                            className="flex-1 rounded-[10px] px-4 py-[9px] text-center text-[12.5px] font-bold text-white sm:flex-none"
                                            style={{ background: accent }}
                                        >
                                            {t('admin.parties.queue.approve')}
                                        </Link>
                                    ) : (
                                        <Link
                                            href={entry.links.review}
                                            className="flex-1 rounded-[10px] border border-rz-hairline bg-rz-surface px-3.5 py-[9px] text-center text-[12.5px] font-bold text-rz-slate sm:flex-none"
                                        >
                                            {t('admin.parties.queue.review')}
                                        </Link>
                                    )}
                                </div>
                            </div>
                            <dl className="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                                {(
                                    [
                                        ['licence', entry.licence],
                                        [
                                            'expires_on',
                                            formatDate(
                                                entry.expires_on,
                                                locale,
                                            ),
                                        ],
                                        [
                                            'submitted_at',
                                            formatDate(
                                                entry.submitted_at,
                                                locale,
                                            ),
                                        ],
                                    ] as const
                                ).map(([key, value]) => (
                                    <div
                                        key={key}
                                        className="min-w-0 rounded-[10px] border border-[#f0e6d4] bg-[#faf7f1] px-3 py-2.5 dark:border-rz-hairline dark:bg-rz-surface-muted"
                                    >
                                        <dt className="text-[10px] font-bold tracking-[.04em] text-[#a1875a] uppercase">
                                            {t(
                                                `admin.parties.queue.field.${key}`,
                                            )}
                                        </dt>
                                        <dd className="mt-0.5 truncate text-[13px] font-semibold text-rz-ink">
                                            {value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
