import { useForm } from '@inertiajs/react';
import { UploadTile } from '@/components/business/onboarding/upload-tile';
import { FieldError } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type { OnboardingDocuments, ShowcaseSlot } from '@/types/business';

const SLOT_ICON: Record<ShowcaseSlot, IconName> = {
    products: 'box',
    facilities: 'factory',
    team: 'people',
    operations: 'settings',
    customers: 'handshake',
    impact: 'globe',
    brand: 'sparkle',
};

const IMAGES = 'image/*';

type StepDocumentsProps = {
    documents: OnboardingDocuments;
    actions: { certificate: RouteAction; upload: RouteAction };
};

/** Step 2 of 4 — "RDB certificate, logo & photos" (design L1625–1648). */
export function StepDocuments({ documents, actions }: StepDocumentsProps) {
    const { t } = useTranslation();
    const { certificate } = documents;
    const verified = certificate.status === 'verified';
    const form = useForm({ number: certificate.number ?? '' });

    return (
        <>
            <h2 className="mt-1.5 text-[22px] font-semibold text-rz-ink">
                {t('business.onboarding.documents.title')}
            </h2>
            <p className="mt-1.5 text-[13.5px] text-rz-secondary">
                {t('business.onboarding.documents.subtitle')}
            </p>

            <div
                className={cn(
                    'mt-4 rounded-2xl border bg-rz-surface p-3.5',
                    verified ? 'border-rz-accent-fill' : 'border-rz-ink',
                )}
            >
                <div className="flex items-center justify-between gap-2.5">
                    <p className="text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                        {t('business.onboarding.documents.certificate')}
                    </p>
                    <span
                        className={cn(
                            'shrink-0 rounded-[10px] px-2 py-[3px] text-[10.5px] font-bold uppercase',
                            verified
                                ? 'bg-rz-page text-rz-accent-app-text'
                                : 'bg-[rgba(194,102,31,.10)] text-rz-ink',
                        )}
                    >
                        {t(
                            `business.onboarding.documents.certificate_${certificate.status}`,
                        )}
                    </span>
                </div>
                <UploadTile
                    slot="certificate"
                    action={actions.upload}
                    label={t('business.onboarding.documents.certificate_drop')}
                    accept="application/pdf,image/*"
                    className="mt-2.5 flex h-[120px] flex-col items-center justify-center gap-1.5 overflow-hidden rounded-xl border-[1.5px] border-dashed border-[#dbe3f0] text-center dark:border-rz-border"
                >
                    <span className="text-xl">
                        <Icon
                            name={
                                certificate.file_name === null
                                    ? 'document'
                                    : 'check-badge'
                            }
                            tone="green"
                        />
                    </span>
                    <span className="px-3 text-[11.5px] text-rz-secondary">
                        {certificate.file_name ??
                            t('business.onboarding.documents.certificate_drop')}
                    </span>
                </UploadTile>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(actions.certificate.url, {
                            preserveScroll: true,
                        });
                    }}
                >
                    <label
                        htmlFor="certificate-number"
                        className="mt-[11px] block text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase"
                    >
                        {t('business.onboarding.documents.certificate_number')}
                    </label>
                    <input
                        id="certificate-number"
                        value={form.data.number}
                        onChange={(event) =>
                            form.setData('number', event.target.value)
                        }
                        placeholder={t(
                            'business.onboarding.documents.certificate_placeholder',
                        )}
                        aria-invalid={
                            form.errors.number !== undefined || undefined
                        }
                        disabled={verified}
                        className="mt-1.5 h-11 w-full rounded-xl border border-[#dbe3f0] bg-rz-field px-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border dark:border-rz-border"
                    />
                    <FieldError id="certificate-number-error">
                        {form.errors.number}
                    </FieldError>
                    <button
                        type="submit"
                        disabled={verified || form.processing}
                        className={cn(
                            'mt-[11px] h-[42px] w-full rounded-xl border text-[13px] font-bold',
                            verified
                                ? 'border-rz-accent-fill bg-rz-page text-rz-accent-app-text'
                                : 'border-rz-accent-fill bg-rz-accent-fill text-white',
                        )}
                    >
                        {verified
                            ? t(
                                  'business.onboarding.documents.certificate_on_file',
                              )
                            : form.processing
                              ? t('business.onboarding.documents.verifying')
                              : t(
                                    'business.onboarding.documents.verify_certificate',
                                )}
                    </button>
                </form>
            </div>

            <div className="mt-4 flex items-center gap-3.5">
                <UploadTile
                    slot="logo"
                    action={actions.upload}
                    label={t('business.onboarding.documents.logo_label')}
                    accept={IMAGES}
                    className="flex size-[72px] shrink-0 flex-col items-center justify-center overflow-hidden rounded-2xl border-[1.5px] border-dashed border-[#dbe3f0] dark:border-rz-border"
                >
                    {documents.logo_url === null ? (
                        <>
                            <span
                                aria-hidden
                                className="text-xl text-rz-accent-app-text"
                            >
                                ＋
                            </span>
                            <span className="mt-0.5 text-[10px] text-rz-secondary">
                                {t('business.onboarding.documents.logo')}
                            </span>
                        </>
                    ) : (
                        <>
                            <span
                                aria-hidden
                                className="text-xl text-rz-accent-app-text"
                            >
                                ✓
                            </span>
                            <span className="mt-0.5 text-[10px] text-rz-secondary">
                                {t('business.onboarding.documents.logo_set')}
                            </span>
                        </>
                    )}
                </UploadTile>
                <p className="text-[13px] leading-normal text-rz-secondary">
                    {t('business.onboarding.documents.logo_help')}
                </p>
            </div>

            <p className="mt-4 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                {t('business.onboarding.documents.photos')}
            </p>
            <div className="mt-2.5 grid grid-cols-3 gap-2.5">
                {documents.photos.map(({ slot, url }, index) => (
                    <UploadTile
                        key={slot}
                        slot={slot}
                        action={actions.upload}
                        label={t(`business.apply.raise.slot.${slot}`)}
                        accept={IMAGES}
                        className={cn(
                            'flex aspect-square flex-col items-center justify-center rounded-2xl border-[1.5px] border-dashed border-rz-border',
                            url === null
                                ? index < 2
                                    ? 'bg-[rgba(30,58,255,.08)]'
                                    : 'bg-[#f3f6fc] dark:bg-rz-surface-sunken'
                                : 'bg-rz-accent-soft',
                        )}
                    >
                        <span className="text-lg">
                            {url === null ? (
                                <Icon name={SLOT_ICON[slot]} />
                            ) : (
                                <span className="text-rz-accent-app-text">
                                    ✓
                                </span>
                            )}
                        </span>
                        <span className="mt-[3px] text-[10.5px] text-rz-secondary">
                            {url === null
                                ? t(`business.apply.raise.slot.${slot}`)
                                : t('business.onboarding.documents.uploaded')}
                        </span>
                    </UploadTile>
                ))}
            </div>
        </>
    );
}
