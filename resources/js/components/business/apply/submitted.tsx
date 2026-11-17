import { Link } from '@inertiajs/react';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { ApplicationQuote, ApplicationSubmission } from '@/types/business';

type ReadyQuote = Extract<ApplicationQuote, { status: 'ready' }>;

/** The limit that sized the offer (REP-R05), the tightest first, from the engine's reason codes. */
const SIZING_CODES = [
    'EXPOSURE_CAPPED',
    'REPEAT_GROWTH_CAPPED',
    'DSCR_SCALED',
    'REPEAT_MONTHLY_DSCR_SCALED',
    'PRINCIPAL_MAX_CAPPED',
] as const;

function sizedBy(
    quote: ReadyQuote,
): (typeof SIZING_CODES)[number] | 'chosen' | 'capacity' | 'requested' {
    if (quote.principal.amount !== quote.offered_principal.amount) {
        return 'chosen';
    }

    return (
        SIZING_CODES.find((code) => quote.reason_codes.includes(code)) ??
        (quote.principal.amount === quote.requested_principal.amount
            ? 'requested'
            : 'capacity')
    );
}

/** What the business signed for, in francs: nobody commits without seeing what it costs. */
function OfferSummary({ quote }: { quote: ReadyQuote }) {
    const { t } = useTranslation();
    const rows: [string, string][] = [
        [t('business.apply.offer.principal'), formatRwf(quote.principal)],
        [
            t('business.apply.offer.instalment'),
            quote.schedule[0] === undefined
                ? '—'
                : t('business.apply.offer.instalment_value', {
                      amount: formatRwf(quote.schedule[0].amount),
                      count: quote.term_months,
                  }),
        ],
        [
            t('business.apply.offer.return'),
            t('business.apply.offer.return_value', {
                amount: formatRwf(quote.interest),
                rate: quote.rate_pct,
            }),
        ],
        [t('business.apply.offer.total'), formatRwf(quote.total)],
    ];

    return (
        <section
            aria-label={t('business.apply.offer.title')}
            className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4 text-left"
        >
            <h2 className="text-[13.5px] font-semibold text-rz-ink">
                {t('business.apply.offer.title')}
            </h2>
            <dl className="mt-2.5 divide-y divide-rz-border">
                {rows.map(([label, value]) => (
                    <div
                        key={label}
                        className="flex justify-between gap-3 py-2 text-[12.5px]"
                    >
                        <dt className="text-rz-secondary">{label}</dt>
                        <dd className="text-right font-semibold text-rz-ink">
                            {value}
                        </dd>
                    </div>
                ))}
            </dl>
            <p className="mt-2 text-xs leading-[1.55] text-rz-secondary">
                {t(`business.apply.offer.sized.${sizedBy(quote)}`, {
                    requested: formatRwf(quote.requested_principal),
                })}
            </p>
        </section>
    );
}

/**
 * "Your application has been submitted" (design L603–627), with the application's audit timeline.
 * The stages and IDs are the server's record, so the timeline moves as review progresses.
 * Submitting does not issue a note: the note ID appears only once the server has one.
 */
export function Submitted({
    submission,
    quote = null,
    home,
}: {
    submission: ApplicationSubmission;
    /** The offer the business signed, from its retained submission. */
    quote?: ReadyQuote | null;
    home: RouteLink;
}) {
    const { t } = useTranslation();
    const done = submission.timeline.filter(
        (stage) => stage.state === 'done',
    ).length;
    const fill = Math.max(0, done - 1) / (submission.timeline.length - 1);
    const publish = submission.publish ?? null;
    const published = submission.timeline.every(
        (stage) => stage.state === 'done',
    );
    const status =
        publish !== null ? 'approved' : published ? 'published' : 'submitted';

    return (
        <div className="pt-5 text-center">
            <div className="mx-auto flex size-[88px] animate-[rz-pop_.5s_ease] items-center justify-center rounded-full bg-rz-accent-soft">
                <div className="flex size-[60px] items-center justify-center rounded-full bg-rz-accent-fill">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="size-[30px]"
                    >
                        <path
                            d="M5 13l4 4L19 7"
                            stroke="#fff"
                            strokeWidth="3"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </div>
            </div>
            <h1 className="mt-[22px] text-[23px] font-semibold text-rz-ink">
                {t(`business.apply.${status}.title`)}
            </h1>
            <p className="mt-2.5 text-sm leading-[1.6] text-rz-secondary">
                {t(`business.apply.${status}.body`)}
            </p>
            <div className="mt-4 flex flex-wrap justify-center gap-2">
                <p className="inline-flex items-center gap-1.5 rounded-[10px] border border-rz-border bg-rz-surface px-[13px] py-2 text-[12.5px] font-semibold text-rz-slate">
                    {t('business.apply.submitted.application_id', {
                        id: submission.application_id,
                    })}
                </p>
                {submission.note_id !== null && (
                    <p className="inline-flex items-center gap-1.5 rounded-[10px] border border-rz-border bg-rz-surface px-[13px] py-2 text-[12.5px] font-semibold text-rz-slate">
                        {t('business.apply.submitted.note_id', {
                            id: submission.note_id,
                        })}
                    </p>
                )}
            </div>

            {quote !== null && <OfferSummary quote={quote} />}

            <div className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4 text-left">
                <div className="flex items-start gap-[11px]">
                    <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                        <Icon name="bank" />
                    </span>
                    <div className="flex-1">
                        <p className="text-[13.5px] font-semibold text-rz-ink">
                            {t('business.apply.submitted.funded_title')}
                        </p>
                        <p className="mt-[3px] text-xs leading-[1.55] text-rz-secondary">
                            {t('business.apply.submitted.funded_body')}
                        </p>
                    </div>
                </div>
            </div>

            <div className="mt-6 rounded-2xl border border-rz-border bg-rz-surface px-[18px] pt-[22px] pb-[18px] text-left">
                <ol
                    aria-label={t('business.apply.submitted.timeline')}
                    className="relative flex justify-between"
                >
                    <span
                        aria-hidden
                        className="absolute inset-x-[9px] top-[9px] h-[3px] rounded-[2px] bg-rz-border"
                    />
                    <span
                        aria-hidden
                        className="absolute top-[9px] left-[9px] h-[3px] rounded-[2px] bg-rz-accent-fill"
                        style={{ width: `calc((100% - 18px) * ${fill})` }}
                    />
                    {submission.timeline.map(({ stage, state }) => (
                        <li
                            key={stage}
                            aria-current={
                                state === 'current' ? 'step' : undefined
                            }
                            className="relative z-[1] flex flex-1 flex-col items-center"
                        >
                            <span
                                className={cn(
                                    'flex size-[18px] shrink-0 items-center justify-center rounded-full',
                                    state === 'done' && 'bg-rz-accent-fill',
                                    state === 'current' && 'bg-[#c2661f]',
                                    state === 'pending' &&
                                        'border border-rz-border bg-rz-surface',
                                )}
                            >
                                {state === 'done' && (
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden
                                        className="size-2.5"
                                    >
                                        <path
                                            d="M5 13l4 4L19 7"
                                            stroke="#fff"
                                            strokeWidth="3.5"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        />
                                    </svg>
                                )}
                            </span>
                            <span
                                className={cn(
                                    'mt-2 text-center text-[11px] leading-[1.3] font-semibold',
                                    state === 'pending'
                                        ? 'text-rz-secondary'
                                        : 'text-rz-ink',
                                )}
                            >
                                {t(`business.apply.submitted.stage.${stage}`)}
                            </span>
                        </li>
                    ))}
                </ol>
            </div>

            {publish !== null && (
                <Link
                    href={publish}
                    className="mt-6 flex h-[50px] w-full items-center justify-center rounded-xl bg-rz-accent-fill text-[14.5px] font-semibold text-white"
                >
                    {t('business.apply.approved.publish')}
                </Link>
            )}
            <Link
                href={home}
                className={cn(
                    'flex h-[50px] w-full items-center justify-center rounded-xl text-[14.5px] font-semibold',
                    publish === null
                        ? 'mt-6 bg-rz-accent-fill text-white'
                        : 'mt-2.5 border border-rz-border bg-rz-surface text-rz-ink',
                )}
            >
                {t('business.apply.submitted.back_home')}
            </Link>
        </div>
    );
}
