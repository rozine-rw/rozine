import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    ScheduleInstalment,
    ServiceFeeDisclosure,
    ServiceFeeTerms,
} from '@/types/business';
import type { Money } from '@/types/money';

/**
 * Where the server's borrower service-fee terms stand for a binding surface (C4 gate 6):
 * - `shown`: every fee input is present, so the projection is shown verbatim;
 * - `absent`: the server predates gate 6 and sent no `service_fee`; nothing is shown and live
 *   acceptance is unchanged until the gate decision is recorded;
 * - `unavailable`: the fee policy is explicitly unavailable, so binding acceptance is disabled.
 */
export type FeeTermsState =
    | { state: 'shown'; fee: ServiceFeeDisclosure; totalPayable: Money }
    | { state: 'absent' }
    | { state: 'unavailable' };

/** The projected fee the server scheduled for one instalment. */
export const instalmentFee = (
    fee: ServiceFeeDisclosure,
    instalment: number,
): Money | undefined =>
    fee.schedule.find((line) => line.instalment === instalment)?.amount;

/**
 * Reads the fee terms exactly as the server sent them; no figure is ever derived here. A null
 * block, a null rounding policy, a missing total payable or a scheduled instalment with no
 * projected fee all read as unavailable, never as zero. An absent block is unavailable only where
 * `absentBlocks` asks for it (the synthetic fixture previews of the proposed rule).
 */
export function readFeeTerms(
    terms: ServiceFeeTerms,
    schedule: ScheduleInstalment[],
    absentBlocks: boolean,
): FeeTermsState {
    const fee = terms.service_fee;

    if (fee === undefined) {
        return absentBlocks ? { state: 'unavailable' } : { state: 'absent' };
    }

    const totalPayable = terms.total_payable ?? null;

    if (fee === null || fee.rounding === null || totalPayable === null) {
        return { state: 'unavailable' };
    }

    const covered = schedule.every(
        (row) => instalmentFee(fee, row.instalment) !== undefined,
    );

    return covered
        ? { state: 'shown', fee, totalPayable }
        : { state: 'unavailable' };
}

/** The server's rate in whole-percent form for the copy: 200 bps reads "2". */
const ratePercent = (fee: ServiceFeeDisclosure): string =>
    String(fee.rate_bps / 100);

/**
 * "Service fee (2% of each repayment)" with its projected total, then "Total payable
 * (projected)": both server figures, shown beneath the contractual total they add to.
 */
export function ServiceFeeRows({
    fee,
    totalPayable,
}: {
    fee: ServiceFeeDisclosure;
    totalPayable: Money;
}) {
    const { t } = useTranslation();

    return (
        <>
            <div className="flex items-baseline justify-between gap-3">
                <span className="min-w-0 text-[13.5px] text-rz-slate">
                    {t('business.apply.review.service_fee', {
                        rate: ratePercent(fee),
                    })}
                    <span className="mt-px block text-[11px] text-rz-secondary">
                        {t('business.apply.review.projected')}
                    </span>
                </span>
                <span className="shrink-0 text-[13px] font-semibold whitespace-nowrap text-rz-ink">
                    {formatRwf(fee.total)}
                </span>
            </div>
            <div className="my-0.5 h-px bg-[#dbe7ff] dark:bg-rz-divider" />
            <div className="flex items-baseline justify-between gap-2.5">
                <span className="min-w-0 text-sm font-bold text-rz-ink">
                    {t('business.apply.review.total_payable')}
                </span>
                <span className="shrink-0 text-lg font-extrabold tracking-[-.3px] whitespace-nowrap text-rz-ink">
                    {formatRwf(totalPayable)}
                </span>
            </div>
        </>
    );
}

/** The projection note: what the fee is charged on, and that the figures are a projection. */
export function ServiceFeeNote({
    fee,
    className,
}: {
    fee: ServiceFeeDisclosure;
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <p
            className={cn(
                'text-[11px] leading-[1.55] text-rz-secondary',
                className,
            )}
        >
            {t('business.apply.review.fee_projection_note', {
                rate: ratePercent(fee),
            })}
        </p>
    );
}

/** Fee terms unavailable: stated plainly, with nothing to accept and no stand-in figure. */
export function FeeTermsUnavailable({
    surface,
    className,
}: {
    surface: 'sign' | 'publish';
    className?: string;
}) {
    const { t } = useTranslation();
    const copy =
        surface === 'sign'
            ? ([
                  'business.apply.review.fee_unavailable',
                  'business.apply.review.fee_unavailable_body',
              ] as const)
            : ([
                  'business.publish.fee_unavailable',
                  'business.publish.fee_unavailable_body',
              ] as const);

    return (
        <div
            role="status"
            className={cn(
                'flex items-start gap-3 rounded-2xl border border-[#fbe4cc] bg-[#fff8f1] p-4 dark:border-transparent dark:bg-[rgba(194,102,31,.12)]',
                className,
            )}
        >
            <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                <Icon name="lock" />
            </span>
            <p className="flex-1 text-xs leading-[1.55] text-rz-secondary">
                <b className="block text-[13px] text-rz-ink">{t(copy[0])}</b>
                {t(copy[1])}
            </p>
        </div>
    );
}
