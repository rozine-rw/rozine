import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Chip, EXPLAIN } from '@/components/admin/ui';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { DispatchLocation, StaffViewer } from '@/types/admin';

const STATE_TONE = {
    verified: 'green',
    expired: 'amber',
    missing: 'red',
} as const;

const FIELD =
    'mt-1 h-[38px] w-full rounded-[10px] border border-rz-hairline bg-[#f7f9fd] px-3 text-[13px] text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] dark:bg-rz-field';
const LABEL = 'text-[11px] font-bold text-rz-slate';

type Fields = {
    latitude: string;
    longitude: string;
    uncertainty_m: string;
    reference: string;
    reason: string;
};

type Kind = 'office' | 'premises';

/**
 * The verification form: the coordinates, how far they may be off, the evidence checked and the
 * reason, under the viewer's name. Each opening is one request stamped with the time the server
 * offered it, so a double or delayed submit replays it.
 */
function VerifyLocationForm({
    kind,
    location,
    viewer,
    onCancel,
}: {
    kind: Kind;
    location: DispatchLocation & {
        verify: NonNullable<DispatchLocation['verify']>;
    };
    viewer: StaffViewer;
    onCancel: () => void;
}) {
    const { t } = useTranslation();
    const [requestId] = useState(() => crypto.randomUUID());
    const form = useForm<
        Fields & {
            request_id: string;
            expected_revision: number;
            verified_at: string;
        }
    >({
        latitude: '',
        longitude: '',
        uncertainty_m: '',
        reference: '',
        reason: '',
        request_id: requestId,
        expected_revision: location.revision,
        verified_at: location.verify.verified_at,
    });
    const set = (key: keyof Fields) => (value: string) =>
        form.setData((data) => ({ ...data, [key]: value }));
    const complete = (
        [
            'latitude',
            'longitude',
            'uncertainty_m',
            'reference',
            'reason',
        ] as const
    ).every((key) => form.data[key].trim() !== '');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(location.verify.url, {
            preserveScroll: true,
            onSuccess: onCancel,
        });
    };

    const input = (key: keyof Fields, inputMode?: 'decimal' | 'numeric') => (
        <div>
            <label htmlFor={`${kind}-${key}`} className={LABEL}>
                {t(`admin.parties.${kind}.field.${key}`)}
            </label>
            <input
                id={`${kind}-${key}`}
                value={form.data[key]}
                inputMode={inputMode}
                onChange={(event) => set(key)(event.target.value)}
                placeholder={t(`admin.parties.${kind}.placeholder.${key}`)}
                aria-invalid={form.errors[key] !== undefined || undefined}
                aria-describedby={`${kind}-${key}-error`}
                className={FIELD}
            />
            <FieldError id={`${kind}-${key}-error`}>
                {form.errors[key]}
            </FieldError>
        </div>
    );

    return (
        <form
            onSubmit={submit}
            aria-label={t(`admin.parties.${kind}.form`)}
            className="mt-3 rounded-[13px] border-[1.5px] border-[#1d9e75] bg-rz-surface p-4"
        >
            <p className={cn('text-[12.5px] leading-[1.55]', EXPLAIN)}>
                {t(`admin.parties.${kind}.form_body`)}
            </p>
            <div className="mt-3 grid grid-cols-2 gap-2.5">
                {input('latitude', 'decimal')}
                {input('longitude', 'decimal')}
                {input('uncertainty_m', 'numeric')}
                {input('reference')}
            </div>
            <label
                htmlFor={`${kind}-reason`}
                className={cn(LABEL, 'mt-2.5 block')}
            >
                {t(`admin.parties.${kind}.field.reason`)}
            </label>
            <textarea
                id={`${kind}-reason`}
                value={form.data.reason}
                onChange={(event) => set('reason')(event.target.value)}
                placeholder={t(`admin.parties.${kind}.placeholder.reason`)}
                aria-invalid={
                    (form.errors.reason ?? form.errors.verified_at) !==
                        undefined || undefined
                }
                aria-describedby={`${kind}-reason-error`}
                className="mt-1 min-h-[70px] w-full resize-y rounded-[11px] border border-rz-hairline bg-[#f7f9fd] px-3 py-2.5 text-[13px] leading-[1.5] text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] dark:bg-rz-field"
            />
            <FieldError id={`${kind}-reason-error`}>
                {form.errors.reason ?? form.errors.verified_at}
            </FieldError>
            <p className="mt-2 flex items-center gap-1.5 text-[11.5px] text-rz-muted">
                <span className="size-1.5 shrink-0 rounded-full bg-[#1d9e75]" />
                {t('admin.stage.logged_as', {
                    name: viewer.name,
                    role: t(`admin.role.${viewer.role}`),
                })}
            </p>
            <div className="mt-3 flex gap-2.5">
                <button
                    type="button"
                    onClick={onCancel}
                    className="h-[40px] rounded-[11px] border border-rz-hairline bg-rz-surface px-4 text-[13px] font-semibold text-rz-slate"
                >
                    {t('admin.stage.cancel')}
                </button>
                <button
                    type="submit"
                    disabled={!complete || form.processing}
                    aria-busy={form.processing || undefined}
                    className="h-[40px] flex-1 rounded-[11px] bg-[#1d9e75] text-[13px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {t(`admin.parties.${kind}.cta`)}
                </button>
            </div>
        </form>
    );
}

/**
 * An Audit Partner's registered office or a Business's premises (BR-51): dispatch only offers a job
 * when both are verified and within 30 km of each other, so a missing or lapsed one is called out.
 */
export function DispatchLocationSection({
    kind,
    location,
    viewer,
    section,
    box,
}: {
    kind: Kind;
    location: DispatchLocation;
    viewer: StaffViewer;
    section: string;
    box: string;
}) {
    const { t, locale } = useTranslation();
    const [verifying, setVerifying] = useState(false);
    const verify = location.verify;

    return (
        <section aria-label={t(`admin.parties.${kind}.title`)} className="mb-5">
            <h3 className={section}>{t(`admin.parties.${kind}.title`)}</h3>
            <div className={cn(box, 'mt-2.5 px-4 py-[15px]')}>
                <div className="flex items-center justify-between">
                    <span className="text-[13px] text-rz-slate">
                        {t(`admin.parties.${kind}.status`)}
                    </span>
                    <Chip tone={STATE_TONE[location.state]}>
                        {t(`admin.parties.${kind}.state.${location.state}`)}
                    </Chip>
                </div>
                {location.state !== 'verified' && (
                    <p
                        className={cn(
                            'mt-2 text-[12.5px] leading-[1.5]',
                            EXPLAIN,
                        )}
                    >
                        {t(`admin.parties.${kind}.note.${location.state}`)}
                    </p>
                )}
                {location.latitude !== null && location.longitude !== null && (
                    <dl className="mt-3 grid grid-cols-2 gap-[9px] text-[12.5px]">
                        <div>
                            <dt className="text-[11px] text-rz-muted">
                                {t(`admin.parties.${kind}.coordinates`)}
                            </dt>
                            <dd className="font-bold text-rz-ink">
                                {t(`admin.parties.${kind}.point`, {
                                    latitude: location.latitude,
                                    longitude: location.longitude,
                                    metres: location.uncertainty_m ?? 0,
                                })}
                            </dd>
                        </div>
                        {location.verified_at !== null && (
                            <div>
                                <dt className="text-[11px] text-rz-muted">
                                    {t(`admin.parties.${kind}.verified_on`)}
                                </dt>
                                <dd className="font-bold text-rz-ink">
                                    {formatDate(location.verified_at, locale)}
                                </dd>
                            </div>
                        )}
                        {location.reference !== null && (
                            <div className="col-span-2">
                                <dt className="text-[11px] text-rz-muted">
                                    {t(`admin.parties.${kind}.evidence`)}
                                </dt>
                                <dd className="break-words text-rz-ink">
                                    {location.reference}
                                </dd>
                            </div>
                        )}
                    </dl>
                )}
                {verify !== null && !verifying && (
                    <button
                        type="button"
                        onClick={() => setVerifying(true)}
                        className="mt-3 h-[38px] w-full rounded-[10px] bg-[#157357] text-[12.5px] font-bold text-white"
                    >
                        {t(
                            location.state === 'missing'
                                ? `admin.parties.${kind}.record`
                                : `admin.parties.${kind}.reverify`,
                        )}
                    </button>
                )}
                {verify !== null && verifying && (
                    <VerifyLocationForm
                        kind={kind}
                        location={{ ...location, verify }}
                        viewer={viewer}
                        onCancel={() => setVerifying(false)}
                    />
                )}
            </div>
        </section>
    );
}
