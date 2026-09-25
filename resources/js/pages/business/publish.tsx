import { Link } from '@inertiajs/react';
import { BusinessShell } from '@/components/business/business-shell';
import { BlankBody, HomeBody } from '@/components/business/home/home-body';
import { C3Notice } from '@/components/rozine/c3-notice';
import { ColumnSheet } from '@/components/rozine/column-sheet';
import { Icon } from '@/components/rozine/icon';
import { useC3Command } from '@/hooks/use-c3-command';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { C3BusinessPublishProps } from '@/types/business';

/** Release causes with their own explanation; any other code reads as a generic refusal. */
const RELEASE_CAUSES = [
    'ENGINE_GATE_FAILED',
    'AUTHORITY_CHANGED',
    'REPORT_NOT_CURRENT',
] as const;

type ReleaseCause = (typeof RELEASE_CAUSES)[number];

const isReleaseCause = (code: string): code is ReleaseCause =>
    (RELEASE_CAUSES as readonly string[]).includes(code);

const ROW =
    'flex items-center justify-between gap-3 border-b border-[#eef2f9] py-[11px] last:border-b-0 dark:border-rz-divider';

/**
 * "Publish to the Investor feed" (AC-03, C3 contract v2 §2f), opened from the released application
 * on Home. Publishing reuses the signatures retained at Review, so the sheet asks for no second
 * acceptance: it states each prerequisite as the server reports it, and when the quote or terms
 * changed since signing it leads back to Review instead of offering Publish. The listing fee is an
 * explicit zero MVP waiver with its disclosure; there is nothing to pay and no payment source.
 * Publish is offered only while the server lists `application.publish`, and nothing is shown as
 * published before the server's `LISTING_PUBLISHED` receipt.
 */
export default function BusinessPublish({
    identity_context_revision,
    application,
    release,
    prerequisites,
    listing_fee,
    fee_disclosure,
    listing,
    allowed_actions,
    actions,
    links,
    home,
    shell_links,
    preview_outcome,
}: C3BusinessPublishProps) {
    const { t, locale } = useTranslation();
    const command = useC3Command<'application.publish'>({
        actions: { 'application.publish': actions.publish },
        lookup: links.operation,
        lookupQuery: { identity_context_revision },
        allowed: allowed_actions,
        preview: preview_outcome,
    });
    const canPublish =
        links.review === null &&
        allowed_actions.includes('application.publish');

    const publish = () => {
        command.send('application.publish', {
            identity_context_revision,
            application_id: application.id,
            expected_application_revision: application.revision,
            fee_disclosure_version: fee_disclosure.version,
        });
    };

    const published = listing !== null && (
        <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-5 pt-4 pb-[22px]">
            <div className="flex items-center gap-3">
                <span className="flex size-[46px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-2xl">
                    <Icon name="check-badge" tone="green" />
                </span>
                <h2 className="text-[19px] leading-[1.25] font-semibold text-rz-ink">
                    {t('business.publish.published.title')}
                </h2>
            </div>
            <p className="mt-3 text-[12.5px] leading-normal text-rz-secondary">
                {t('business.publish.published.body', {
                    title: application.title,
                })}
            </p>
            <dl
                aria-label={t('business.publish.published.receipt')}
                className="mt-4 rounded-2xl border border-[#eef2f9] bg-[#f6f9fd] px-[15px] py-1.5 dark:border-rz-divider dark:bg-rz-surface-sunken"
            >
                <div className={ROW}>
                    <dt className="text-[13px] text-rz-secondary">
                        {t('business.publish.fee_label')}
                    </dt>
                    <dd className="text-[13px] font-semibold text-rz-ink">
                        {formatRwf(listing.receipt.amount)}
                    </dd>
                </div>
                <div className={ROW}>
                    <dt className="text-[13px] text-rz-secondary">
                        {t('business.publish.published.reference')}
                    </dt>
                    <dd className="text-[13px] font-semibold text-rz-ink">
                        {listing.receipt.reference}
                    </dd>
                </div>
                <div className={ROW}>
                    <dt className="text-[13px] text-rz-secondary">
                        {t('business.publish.published.recorded')}
                    </dt>
                    <dd className="text-[13px] font-semibold text-rz-ink">
                        {formatDateTime(listing.receipt.recorded_at, locale)}
                    </dd>
                </div>
                {listing.receipt.disclosure_version !== null && (
                    <div className={ROW}>
                        <dt className="text-[13px] text-rz-secondary">
                            {t('business.publish.published.disclosure')}
                        </dt>
                        <dd className="text-[13px] font-semibold text-rz-ink">
                            {listing.receipt.disclosure_version}
                        </dd>
                    </div>
                )}
            </dl>
            <Link
                href={listing.campaign}
                className="mt-4 flex h-[52px] w-full items-center justify-center rounded-xl bg-rz-accent-fill text-[15px] font-semibold text-white"
            >
                {t('business.publish.published.campaign')}
            </Link>
            <Link
                href={links.close}
                className="mt-2.5 flex h-[46px] w-full items-center justify-center rounded-xl border border-rz-border text-sm font-semibold text-rz-slate"
            >
                {t('business.publish.published.home')}
            </Link>
        </div>
    );

    const sheet = (
        <ColumnSheet
            label={t('business.publish.title')}
            close={links.close}
            fraction={0.8}
        >
            {published || (
                <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-5 pt-4 pb-[22px]">
                    <div className="flex items-center gap-3">
                        <span className="flex size-[46px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-2xl">
                            <Icon name="rocket" tone="green" />
                        </span>
                        <h2 className="text-[19px] leading-[1.25] font-semibold text-rz-ink">
                            {t('business.publish.title')}
                        </h2>
                    </div>
                    <p className="mt-3 text-[12.5px] leading-normal text-rz-secondary">
                        {t('business.publish.intro', {
                            title: application.title,
                        })}
                    </p>

                    <ReleaseState release={release} />

                    <p className="mt-4 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                        {t('business.publish.prerequisites')}
                    </p>
                    <ul
                        aria-label={t('business.publish.prerequisites')}
                        className="mt-2 rounded-2xl border border-rz-border bg-rz-surface px-[15px] py-1"
                    >
                        {prerequisites.map((item) => (
                            <li
                                key={item.key}
                                className="flex items-center gap-2.5 border-b border-rz-divider py-2.5 last:border-b-0"
                            >
                                <span
                                    aria-hidden
                                    className={cn(
                                        'flex size-5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold',
                                        item.met
                                            ? 'bg-rz-accent-fill text-white'
                                            : 'border-[1.5px] border-rz-border text-transparent',
                                    )}
                                >
                                    ✓
                                </span>
                                <span className="flex-1 text-[12.5px] text-rz-ink">
                                    {t(
                                        `business.publish.prerequisite.${item.key}`,
                                    )}
                                </span>
                                <span
                                    className={cn(
                                        'shrink-0 text-[11px] font-semibold',
                                        item.met
                                            ? 'text-rz-accent-app-text'
                                            : 'text-rz-secondary',
                                    )}
                                >
                                    {item.met
                                        ? t('business.publish.met')
                                        : t('business.publish.not_met')}
                                </span>
                            </li>
                        ))}
                    </ul>

                    <dl className="mt-4 rounded-2xl border border-[#eef2f9] bg-[#f6f9fd] px-[15px] py-1.5 dark:border-rz-divider dark:bg-rz-surface-sunken">
                        <div className={ROW}>
                            <dt className="text-[13px] text-rz-secondary">
                                {t('business.publish.target')}
                            </dt>
                            <dd className="text-[13px] font-semibold text-rz-ink">
                                {formatRwf(application.target)}
                            </dd>
                        </div>
                        <div className={ROW}>
                            <dt className="text-[13px] font-bold text-rz-ink">
                                {t('business.publish.fee_label')}
                                <span className="block text-[11px] font-normal text-rz-secondary">
                                    {t('business.publish.fee_waived')}
                                </span>
                            </dt>
                            <dd className="text-base font-bold text-rz-accent-app-text">
                                {formatRwf(listing_fee)}
                            </dd>
                        </div>
                    </dl>
                    <div className="mt-3 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                        <p className="text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                            {t('business.publish.disclosure_title')}
                        </p>
                        <p className="mt-1.5 text-xs leading-[1.55] text-rz-ink">
                            {fee_disclosure.text}
                        </p>
                        <p className="mt-1.5 text-[11px] text-rz-secondary">
                            {t('business.publish.disclosure_version', {
                                version: fee_disclosure.version,
                            })}
                        </p>
                    </div>

                    <C3Notice command={command} className="mt-4" />

                    {links.review !== null ? (
                        <>
                            <p
                                role="status"
                                className="mt-4 rounded-2xl border border-[#fbe4cc] bg-[#fff8f1] p-[15px] text-[12.5px] leading-normal text-rz-ink dark:border-transparent dark:bg-[rgba(194,102,31,.12)]"
                            >
                                {t('business.publish.changed')}
                            </p>
                            <Link
                                href={links.review}
                                className="mt-3 flex h-[52px] w-full items-center justify-center rounded-xl bg-rz-accent-fill text-[15px] font-semibold text-white"
                            >
                                {t('business.publish.review_again')}
                            </Link>
                        </>
                    ) : canPublish ? (
                        <button
                            type="button"
                            onClick={publish}
                            disabled={command.busy || command.unresolved}
                            aria-busy={command.busy || undefined}
                            className="mt-4 flex h-[52px] w-full items-center justify-center gap-[9px] rounded-xl bg-rz-accent-fill text-[15px] font-semibold text-white disabled:opacity-60"
                        >
                            {command.busy && (
                                <span className="size-[17px] animate-spin rounded-full border-[2.5px] border-white/40 border-t-white" />
                            )}
                            {command.busy
                                ? t('business.publish.publishing')
                                : t('business.publish.publish')}
                        </button>
                    ) : (
                        <p
                            role="status"
                            className="mt-4 flex items-start gap-3 rounded-2xl border border-[#dbe7ff] bg-rz-surface p-4 text-xs leading-[1.55] text-rz-secondary dark:border-rz-border"
                        >
                            <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                                <Icon name="hourglass" />
                            </span>
                            <span className="flex-1 self-center">
                                {t('business.publish.blocked')}
                            </span>
                        </p>
                    )}
                    <Link
                        href={links.close}
                        className="mt-2.5 flex h-[46px] w-full items-center justify-center rounded-xl border border-rz-border text-sm font-semibold text-rz-slate"
                    >
                        {t('business.publish.not_yet')}
                    </Link>
                </div>
            )}
        </ColumnSheet>
    );

    return (
        <BusinessShell
            title={t('business.publish.title')}
            tab="home"
            links={shell_links}
        >
            {home === null ? (
                <BlankBody overlay={{ column: 'left', content: sheet }} />
            ) : (
                <HomeBody
                    {...home}
                    overlay={{ column: 'left', content: sheet }}
                />
            )}
        </BusinessShell>
    );
}

/** Where staff review of the application stands; a refusal names its causes. */
function ReleaseState({
    release,
}: {
    release: C3BusinessPublishProps['release'];
}) {
    const { t } = useTranslation();

    if (release.state === 'released') {
        return (
            <p className="mt-4 flex items-center gap-3 rounded-2xl border border-[#cfe9d8] bg-rz-accent-soft p-[15px] text-[12.5px] leading-normal text-rz-ink dark:border-transparent">
                <Icon name="check-badge" tone="green" />
                <span className="flex-1">
                    {t('business.publish.release.released')}
                </span>
            </p>
        );
    }

    if (release.state === 'awaiting_staff_review') {
        return (
            <p className="mt-4 flex items-start gap-3 rounded-2xl border border-rz-border bg-rz-surface p-[15px] text-[12.5px] leading-normal text-rz-ink">
                <Icon name="hourglass" />
                <span className="flex-1">
                    {t('business.publish.release.awaiting')}
                </span>
            </p>
        );
    }

    return (
        <div className="mt-4 rounded-2xl border border-[rgba(229,72,77,.25)] bg-[rgba(229,72,77,.06)] p-[15px] text-[12.5px] leading-normal text-rz-ink">
            <p className="flex items-center gap-3 font-semibold">
                <Icon name="blocked" tone="red" />
                <span className="flex-1">
                    {t('business.publish.release.refused')}
                </span>
            </p>
            <ul className="mt-2 list-disc space-y-1 pl-9">
                {release.causes.map((cause) => (
                    <li key={cause}>
                        {isReleaseCause(cause)
                            ? t(`business.publish.cause.${cause}`)
                            : t('business.publish.cause.other')}
                    </li>
                ))}
            </ul>
        </div>
    );
}
