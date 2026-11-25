import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { useAuditorCommands } from '@/components/auditor/commands';
import { DIVIDER, PRIMARY } from '@/components/auditor/ui';
import { LegalDocumentBody } from '@/components/legal/legal-document-body';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type {
    AuditorProfileProps,
    RwandaAddress,
    RwandaProvinces,
} from '@/types/auditor';

const CARD =
    'mt-2.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface';

function Heading({ label }: { label: string }) {
    return (
        <h2 className="text-[11px] font-bold tracking-[.06em] text-rz-slate uppercase">
            {label}
        </h2>
    );
}

const LABEL =
    'mb-1.5 block text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase';
const FIELD =
    'w-full rounded-xl border border-rz-border bg-rz-field px-3.5 py-[11px] text-[13px] text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border';
const EMPTY_ADDRESS: RwandaAddress = {
    province: '',
    district: '',
    sector: '',
    cell: '',
    street: '',
};

/**
 * The registered address form (Erastus, 24 Nov 2026): a province, a district of that province, and
 * the sector, cell and street as typed. Saving is the `contact.update` command; the server checks
 * the district belongs to the province and says which field to fix.
 */
function AddressForm({
    contact,
    provinces,
    revision,
    onClose,
}: {
    contact: AuditorProfileProps['contact'];
    provinces: RwandaProvinces;
    revision: number;
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const center = useAuditorCommands();
    const [address, setAddress] = useState<RwandaAddress>(
        contact.address ?? EMPTY_ADDRESS,
    );
    // A field the partner has changed since the last save hides its error until the next save.
    const [edited, setEdited] = useState<(keyof RwandaAddress)[]>([]);
    const error = (field: keyof RwandaAddress) =>
        edited.includes(field) ? undefined : center.errors[`address.${field}`];
    const set = (value: Partial<RwandaAddress>) => {
        setEdited((current) => [
            ...current,
            ...(Object.keys(value) as (keyof RwandaAddress)[]),
        ]);
        setAddress((current) => ({ ...current, ...value }));
    };
    const field = (key: 'sector' | 'cell' | 'street', placeholder: string) => (
        <div className={key === 'street' ? 'sm:col-span-2' : undefined}>
            <label htmlFor={`address-${key}`} className={LABEL}>
                {t(`role.apply.address.${key}`)}
            </label>
            <input
                id={`address-${key}`}
                value={address[key]}
                placeholder={placeholder}
                aria-invalid={error(key) !== undefined}
                onChange={(event) => set({ [key]: event.target.value })}
                aria-describedby={
                    key === 'street' ? 'address-street-hint' : undefined
                }
                className={FIELD}
            />
            {key === 'street' && (
                <p
                    id="address-street-hint"
                    className="mt-1.5 text-[11.5px] text-rz-secondary"
                >
                    {t('role.apply.address.street_hint')}
                </p>
            )}
            <FieldError message={error(key)} />
        </div>
    );

    return (
        <form
            className="grid grid-cols-1 gap-3 border-t border-rz-border p-[15px] sm:grid-cols-2"
            onSubmit={(event) => {
                event.preventDefault();
                setEdited([]);
                center.send(
                    {
                        name: 'contact.update',
                        business: '',
                        route: contact.update,
                        scope: ['contact.update'],
                        payload: { address, expected_revision: revision },
                    },
                    { onCompleted: onClose },
                );
            }}
        >
            <div>
                <label htmlFor="address-province" className={LABEL}>
                    {t('role.apply.address.province')}
                </label>
                <select
                    id="address-province"
                    value={address.province}
                    aria-invalid={error('province') !== undefined}
                    onChange={(event) =>
                        set({
                            province: event.target.value,
                            district: (
                                provinces[event.target.value] ?? []
                            ).includes(address.district)
                                ? address.district
                                : '',
                        })
                    }
                    className={FIELD}
                >
                    <option value="">{t('role.apply.choose')}</option>
                    {Object.keys(provinces).map((province) => (
                        <option key={province} value={province}>
                            {province}
                        </option>
                    ))}
                </select>
                <FieldError message={error('province')} />
            </div>
            <div>
                <label htmlFor="address-district" className={LABEL}>
                    {t('role.apply.company.district')}
                </label>
                <select
                    id="address-district"
                    value={address.district}
                    aria-invalid={error('district') !== undefined}
                    onChange={(event) => set({ district: event.target.value })}
                    className={FIELD}
                >
                    <option value="">
                        {t('role.apply.address.select_district')}
                    </option>
                    {(provinces[address.province] ?? []).map((district) => (
                        <option key={district} value={district}>
                            {district}
                        </option>
                    ))}
                </select>
                <FieldError message={error('district')} />
            </div>
            {field('sector', t('role.apply.address.sector_placeholder'))}
            {field('cell', t('role.apply.address.cell_placeholder'))}
            {field('street', t('role.apply.address.street_placeholder'))}
            <div className="flex gap-2.5 sm:col-span-2">
                <button
                    type="button"
                    onClick={onClose}
                    className="flex-1 rounded-xl border border-rz-border py-3 text-[13px] font-bold text-rz-ink"
                >
                    {t('auditor.profile.contact.cancel')}
                </button>
                <button
                    type="submit"
                    disabled={!center.idle}
                    aria-busy={center.busy || undefined}
                    className={cn(
                        'flex-1 rounded-xl py-3 text-[13px]',
                        PRIMARY,
                    )}
                >
                    {center.busy
                        ? t('auditor.profile.contact.saving')
                        : t('auditor.profile.contact.save')}
                </button>
            </div>
        </form>
    );
}

function FieldError({ message }: { message?: string }) {
    return message ? (
        <p role="alert" className="mt-1.5 text-[11.5px] text-rz-danger-text">
            {message}
        </p>
    ) : null;
}

/**
 * Personal & contact (design L740–760): the account's own email and the partner's registered
 * address, which they edit here. The district of operation is the districts they accept audits in,
 * set under Availability & coverage. No phone is held for a partner, so it reads "Not provided".
 */
/** The address fields the contact section shows their own errors beside. */
export const ADDRESS_ERROR_FIELDS = [
    'address.province',
    'address.district',
    'address.sector',
    'address.cell',
    'address.street',
];

export function ContactSection({
    contact,
    provinces,
    revision,
    districts,
}: {
    contact: AuditorProfileProps['contact'];
    provinces: RwandaProvinces;
    /** The profile's revision, sent back as `expected_revision`. */
    revision: number;
    /** The districts the partner accepts audits in. */
    districts: string[];
}) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState(false);
    const rows: {
        key: 'phone' | 'email' | 'address' | 'district';
        icon: IconName;
        value: string | null;
    }[] = [
        { key: 'phone', icon: 'phone', value: contact.phone },
        { key: 'email', icon: 'mail', value: contact.email },
        { key: 'address', icon: 'pin', value: contact.address_line },
        {
            key: 'district',
            icon: 'compass',
            value: districts.length === 0 ? null : districts.join(', '),
        },
    ];

    return (
        <>
            <Heading label={t('auditor.profile.contact.title')} />
            <div className={CARD}>
                <dl>
                    {rows.map((row) => (
                        <div
                            key={row.key}
                            className={cn(
                                'relative border-b py-3 pr-[15px] pl-[57px] last:border-b-0',
                                DIVIDER,
                            )}
                        >
                            <dt className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
                                {/* The mark sits in the term so the row keeps one term and one description. */}
                                <span className="absolute top-1/2 left-[15px] flex size-[30px] -translate-y-1/2 items-center justify-center rounded-[9px] bg-rz-page text-[15px] dark:bg-rz-surface-muted">
                                    <Icon name={row.icon} tone="amber" />
                                </span>
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
                    ))}
                </dl>
                {editing ? (
                    <AddressForm
                        contact={contact}
                        provinces={provinces}
                        revision={revision}
                        onClose={() => setEditing(false)}
                    />
                ) : (
                    <button
                        type="button"
                        onClick={() => setEditing(true)}
                        className={cn(
                            'w-full border-t py-3 text-center text-[13px] font-bold text-rz-ink',
                            DIVIDER,
                        )}
                    >
                        {contact.address === null
                            ? t('auditor.profile.contact.add_address')
                            : t('auditor.profile.contact.edit_address')}
                    </button>
                )}
            </div>
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
 * Terms & legal: the engagement documents the partner accepts (the platform MSA and the agreed
 * procedures), opened on their own page, then Rozine's Terms, Privacy Notice and Disclaimers,
 * each opening in place to its full wording. Without an engagement summary only those three show.
 */
export function LegalSection({
    engagement,
    documents,
}: {
    engagement: AuditorProfileProps['engagement'];
    documents: AuditorProfileProps['legal_documents'];
}) {
    const { t } = useTranslation();

    return (
        <>
            <Heading label={t('auditor.profile.legal.title')} />
            {engagement !== null && (
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
                                {t(
                                    `auditor.profile.legal.${engagement.status}`,
                                )}
                            </span>
                        </span>
                        <span aria-hidden className="text-rz-secondary">
                            ›
                        </span>
                    </Link>
                </div>
            )}
            <p className="mt-4 mb-2 text-[11.5px] font-semibold tracking-[.04em] text-rz-secondary uppercase">
                {t('auditor.profile.legal.platform')}
            </p>
            <div className={cn(CARD, 'divide-y divide-rz-border')}>
                {documents.map((document) => (
                    <details
                        key={document.key}
                        id={`legal-${document.key}`}
                        className="group scroll-mt-4"
                    >
                        <summary className="flex cursor-pointer list-none items-center gap-3 p-[15px] [&::-webkit-details-marker]:hidden">
                            <span className="flex size-[30px] shrink-0 items-center justify-center rounded-[9px] bg-rz-page text-[15px] dark:bg-rz-surface-muted">
                                <Icon name="document" tone="amber" />
                            </span>
                            <span className="min-w-0 flex-1 text-[13.5px] font-semibold text-rz-ink">
                                {document.title}
                            </span>
                            <span
                                aria-hidden
                                className="text-rz-secondary transition-transform group-open:rotate-90"
                            >
                                ›
                            </span>
                        </summary>
                        <div className="px-[15px] pb-[15px]">
                            <LegalDocumentBody
                                document={document}
                                className="[&_h1]:sr-only"
                                targets={{
                                    terms: '#legal-terms',
                                    privacy: '#legal-privacy',
                                    disclaimers: '#legal-disclaimers',
                                }}
                            />
                        </div>
                    </details>
                ))}
            </div>
        </>
    );
}

const PENDING_ICON: Record<'payout' | 'legal', IconName> = {
    payout: 'bank',
    legal: 'document',
};

/**
 * A section with nothing to read yet, such as the payout account before its read exists. The
 * design draws each with content, so this is its empty state.
 */
export function PendingSection({ kind }: { kind: 'payout' | 'legal' }) {
    const { t } = useTranslation();

    const heading =
        kind === 'legal'
            ? t('auditor.profile.legal.title')
            : t(`auditor.profile.section.${kind}`);

    return (
        <>
            <Heading label={heading} />
            <div
                className={cn(
                    CARD,
                    'flex flex-col items-center px-[22px] py-7 text-center',
                )}
            >
                <span
                    aria-hidden
                    className="flex size-[52px] items-center justify-center rounded-[16px] bg-rz-accent-soft text-[22px]"
                >
                    <Icon name={PENDING_ICON[kind]} tone="amber" />
                </span>
                <p className="mt-3 text-[15px] font-semibold text-rz-ink">
                    {t(`auditor.profile.pending.${kind}_title`)}
                </p>
                <p className="mt-1.5 max-w-[260px] text-[13px] leading-normal text-rz-secondary">
                    {t(`auditor.profile.pending.${kind}_body`)}
                </p>
            </div>
        </>
    );
}
