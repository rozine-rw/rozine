import { useId, useState } from 'react';
import { ColumnSheet } from '@/components/rozine/column-sheet';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';

/** The longest reason the sheet accepts. */
export const CANCEL_REASON_MAX = 500;

/**
 * "Cancel this raise?" (C3 v2 §2f), offered only while the campaign is raising and the server lists
 * `campaign.cancel`. It says what the server does on cancel — every commitment refunded in full,
 * without fee, and the exposure released — and takes an optional reason. Nothing changes on this
 * page until the server answers.
 */
export function CancelSheet({
    busy,
    onClose,
    onConfirm,
}: {
    busy: boolean;
    onClose: () => void;
    onConfirm: (reason: string | null) => void;
}) {
    const { t } = useTranslation();
    const [reason, setReason] = useState('');
    const field = useId();

    return (
        <ColumnSheet
            label={t('business.campaign.cancel.title')}
            close={onClose}
            fraction={0.7}
        >
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    const trimmed = reason.trim();

                    onConfirm(trimmed === '' ? null : trimmed);
                }}
                className="rz-scroll min-h-0 flex-1 overflow-y-auto px-5 pt-4 pb-[22px]"
            >
                <div className="flex items-center gap-3">
                    <span className="flex size-[46px] shrink-0 items-center justify-center rounded-xl bg-[rgba(229,72,77,.10)] text-2xl">
                        <Icon name="undo" tone="red" />
                    </span>
                    <h2 className="text-[19px] leading-[1.25] font-semibold text-rz-ink">
                        {t('business.campaign.cancel.title')}
                    </h2>
                </div>
                <p className="mt-3 text-[12.5px] leading-normal text-rz-secondary">
                    {t('business.campaign.cancel.body')}
                </p>
                <label
                    htmlFor={field}
                    className="mt-4 block text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase"
                >
                    {t('business.campaign.cancel.reason')}
                </label>
                <textarea
                    id={field}
                    value={reason}
                    maxLength={CANCEL_REASON_MAX}
                    rows={3}
                    onChange={(event) => setReason(event.target.value)}
                    className="mt-2 w-full rounded-xl border border-rz-border bg-rz-surface p-3 text-[13px] text-rz-ink"
                />
                <button
                    type="submit"
                    disabled={busy}
                    aria-busy={busy || undefined}
                    className="mt-4 flex h-[52px] w-full items-center justify-center rounded-xl bg-rz-danger text-[15px] font-semibold text-white disabled:opacity-60"
                >
                    {t('business.campaign.cancel.confirm')}
                </button>
                <button
                    type="button"
                    onClick={onClose}
                    className="mt-2.5 flex h-[46px] w-full items-center justify-center rounded-xl border border-rz-border text-sm font-semibold text-rz-slate"
                >
                    {t('business.campaign.cancel.keep')}
                </button>
            </form>
        </ColumnSheet>
    );
}
