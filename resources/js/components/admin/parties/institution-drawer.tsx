import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import {
    avatarColor,
    formatTimestamp,
    initialOf,
} from '@/components/admin/format';
import { Chip } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { InstitutionDetail, InstitutionStatus, Tone } from '@/types/admin';

const SECTION =
    'text-[12px] font-bold tracking-[.05em] text-[#636e86] uppercase dark:text-rz-muted';
const BOX = 'rounded-[13px] border border-rz-hairline bg-rz-surface';
const ITEM =
    'border-b border-[#eef2f8] px-[15px] py-3 text-[12.5px] last:border-b-0 dark:border-rz-divider';

export const INSTITUTION_TONE: Record<InstitutionStatus, Tone> = {
    approved: 'green',
    submitted: 'blue',
    changes_requested: 'amber',
    draft: 'grey',
    rejected: 'red',
};

/** The institution types the application offers; any other reads as written. */
const TYPES = [
    'pension_fund',
    'venture_capital',
    'asset_manager',
    'insurer',
    'bank',
    'corporate_treasury',
    'other',
] as const;

export function useInstitutionType(): (type: string) => string {
    const { t } = useTranslation();

    return (type) =>
        (TYPES as readonly string[]).includes(type)
            ? t(`role.apply.institution.type.${type as (typeof TYPES)[number]}`)
            : type;
}

function Fact({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="min-w-0 rounded-[11px] border border-rz-hairline bg-rz-surface px-3 py-2.5">
            <dt className="truncate text-[10.5px] font-semibold text-rz-muted">
                {label}
            </dt>
            <dd className="mt-[3px] text-[13px] font-bold break-words text-rz-ink">
                {children}
            </dd>
        </div>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section aria-label={title} className="mt-5 first:mt-0">
            <h3 className={SECTION}>{title}</h3>
            <div className="mt-2.5">{children}</div>
        </section>
    );
}

function Empty({ children }: { children: ReactNode }) {
    return (
        <p className={cn(BOX, 'px-4 py-3.5 text-[12.5px] text-rz-muted')}>
            {children}
        </p>
    );
}

/**
 * One institution's 360 (Erastus, 27 Nov 2026): its review status and the action to open the review
 * drawer, the company and its account, the people who own and run it, the representative's account,
 * its money, its share of each raise against its cap, Auto-Deploy, its documents and its history.
 */
export function InstitutionDrawer({
    institution,
}: {
    institution: InstitutionDetail;
}) {
    const { t, locale } = useTranslation();
    const typeLabel = useInstitutionType();
    const name =
        institution.name.trim() === ''
            ? t('admin.institutions.name_pending')
            : institution.name;
    const company = institution.company;
    const account = institution.account_details;

    return (
        <Drawer
            label={t('admin.institutions.drawer_label', { name })}
            close={institution.links.close}
        >
            <div className="shrink-0 border-b border-rz-hairline bg-rz-surface px-[18px] py-4">
                <div className="flex items-center gap-3">
                    <div
                        className="flex size-10 shrink-0 items-center justify-center rounded-[11px] text-[16px] font-bold text-white"
                        style={{ background: avatarColor(institution.id) }}
                    >
                        {initialOf(name)}
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            <h2 className="truncate text-[17px] font-bold text-rz-ink">
                                {name}
                            </h2>
                            <Chip tone={INSTITUTION_TONE[institution.status]}>
                                {t(
                                    `admin.institutions.status.${institution.status}`,
                                )}
                            </Chip>
                        </div>
                        <p className="mt-0.5 truncate text-[12px] text-rz-muted">
                            {[
                                institution.type === ''
                                    ? null
                                    : typeLabel(institution.type),
                                institution.rdb_code === ''
                                    ? null
                                    : t('admin.institutions.rdb', {
                                          code: institution.rdb_code,
                                      }),
                            ]
                                .filter(Boolean)
                                .join(' · ')}
                        </p>
                    </div>
                    <DrawerClose close={institution.links.close} />
                </div>
                <div className="mt-3 flex flex-wrap gap-2">
                    <Link
                        href={institution.links.review}
                        preserveScroll
                        className="rounded-[10px] bg-rz-accent-fill px-3 py-[7px] text-[12px] font-semibold text-white"
                    >
                        {t(
                            institution.status === 'submitted'
                                ? 'admin.institutions.review'
                                : 'admin.institutions.open_application',
                        )}
                    </Link>
                    {institution.links.user && (
                        <Link
                            href={institution.links.user}
                            className="rounded-[10px] border border-rz-hairline bg-rz-surface px-3 py-[7px] text-[12px] font-semibold text-rz-accent-app-text"
                        >
                            {t('admin.institutions.open_user')}
                        </Link>
                    )}
                    {institution.links.investor && (
                        <Link
                            href={institution.links.investor}
                            className="rounded-[10px] border border-rz-hairline bg-rz-surface px-3 py-[7px] text-[12px] font-semibold text-rz-accent-app-text"
                        >
                            {t('admin.institutions.open_investor')}
                        </Link>
                    )}
                </div>
            </div>

            <div
                tabIndex={0}
                className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-[18px] pb-[26px]"
            >
                <Section title={t('admin.institutions.overview')}>
                    <dl className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <Fact label={t('admin.institutions.fact.commitment')}>
                            {formatRwf(institution.monthly_commitment)}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.invested')}>
                            {formatRwf(institution.invested)}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.wallet')}>
                            {formatRwf(institution.wallet)}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.committed')}>
                            {formatRwf(institution.committed)}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.holdings')}>
                            {institution.holdings.reduce(
                                (notes, holding) => notes + holding.notes,
                                0,
                            )}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.cap')}>
                            {t('admin.institutions.cap_value', {
                                percent: institution.cap_percent,
                            })}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.submitted')}>
                            {institution.submitted_at === null
                                ? t('admin.institutions.not_yet')
                                : formatDate(institution.submitted_at, locale)}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.approved')}>
                            {institution.approved_at === null
                                ? t('admin.institutions.not_yet')
                                : institution.approved_by === null
                                  ? formatDate(institution.approved_at, locale)
                                  : t('admin.institutions.approved_by', {
                                        date: formatDate(
                                            institution.approved_at,
                                            locale,
                                        ),
                                        name: institution.approved_by,
                                    })}
                        </Fact>
                    </dl>
                </Section>

                <Section title={t('admin.institutions.auto_deploy')}>
                    <div className={cn(BOX, 'px-4 py-3.5')}>
                        <p className="text-[13px] font-bold text-rz-ink">
                            {t(
                                institution.auto_deploy.enabled
                                    ? 'admin.institutions.auto_deploy_on'
                                    : 'admin.institutions.auto_deploy_off',
                            )}
                        </p>
                        <p className="mt-1 text-[12px] text-rz-muted">
                            {t('admin.institutions.auto_deploy_body', {
                                amount: formatRwf(
                                    institution.monthly_commitment,
                                ),
                            })}
                        </p>
                    </div>
                </Section>

                <Section title={t('admin.institutions.raises')}>
                    {institution.raises.length === 0 ? (
                        <Empty>{t('admin.institutions.raises_empty')}</Empty>
                    ) : (
                        <ul className={cn(BOX, 'overflow-hidden')}>
                            {institution.raises.map((raise) => {
                                const share = raise.share_bps / 100;
                                const atCap = share >= raise.cap_percent;

                                return (
                                    <li
                                        key={raise.campaign_id}
                                        className={ITEM}
                                    >
                                        <div className="flex justify-between gap-3">
                                            <span className="font-semibold text-rz-ink">
                                                {raise.business}
                                            </span>
                                            <span
                                                className={cn(
                                                    'shrink-0 font-semibold',
                                                    atCap
                                                        ? 'text-[#a55418] dark:text-[#f0a060]'
                                                        : 'text-rz-body',
                                                )}
                                            >
                                                {t(
                                                    'admin.institutions.share_of_cap',
                                                    {
                                                        share: share.toFixed(
                                                            share % 1 === 0
                                                                ? 0
                                                                : 2,
                                                        ),
                                                        cap: raise.cap_percent,
                                                    },
                                                )}
                                            </span>
                                        </div>
                                        <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-rz-surface-sunken">
                                            <div
                                                className={cn(
                                                    'h-full rounded-full',
                                                    atCap
                                                        ? 'bg-[#c2661f]'
                                                        : 'bg-rz-accent-fill',
                                                )}
                                                style={{
                                                    width: `${Math.min(100, raise.cap_percent === 0 ? 0 : (share / raise.cap_percent) * 100)}%`,
                                                }}
                                            />
                                        </div>
                                        <div className="mt-1 text-[11.5px] text-rz-muted">
                                            {t(
                                                'admin.institutions.raise_amounts',
                                                {
                                                    amount: formatRwf(
                                                        raise.amount,
                                                    ),
                                                    total: formatRwf(
                                                        raise.raise_amount,
                                                    ),
                                                },
                                            )}
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </Section>

                <Section title={t('admin.institutions.investments')}>
                    {institution.holdings.length === 0 ? (
                        <Empty>{t('admin.institutions.holdings_empty')}</Empty>
                    ) : (
                        <ul className={cn(BOX, 'overflow-hidden')}>
                            {institution.holdings.map((holding) => (
                                <li
                                    key={holding.business_id}
                                    className={cn(
                                        ITEM,
                                        'flex justify-between gap-3',
                                    )}
                                >
                                    <span className="font-semibold text-rz-ink">
                                        {holding.name}
                                    </span>
                                    <span className="shrink-0 text-rz-body">
                                        {t('admin.institutions.holding', {
                                            amount: formatRwf(
                                                holding.principal,
                                            ),
                                            count: holding.notes,
                                        })}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>

                <Section title={t('admin.institutions.company')}>
                    {company === null ? (
                        <Empty>{t('admin.institutions.company_empty')}</Empty>
                    ) : (
                        <dl className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <Fact label={t('admin.institutions.fact.tin')}>
                                {company.tin || '—'}
                            </Fact>
                            <Fact label={t('admin.institutions.fact.sector')}>
                                {company.industry || '—'}
                            </Fact>
                            <Fact
                                label={t(
                                    'admin.institutions.fact.incorporated',
                                )}
                            >
                                {company.incorporation_date || '—'}
                            </Fact>
                            <Fact label={t('admin.institutions.fact.address')}>
                                {[
                                    company.street,
                                    company.cell,
                                    company.sector,
                                    company.district,
                                    company.province,
                                ]
                                    .filter((part) => part !== '')
                                    .join(', ') || '—'}
                            </Fact>
                            <Fact label={t('admin.institutions.fact.phone')}>
                                {company.phone || '—'}
                            </Fact>
                            {account !== null && (
                                <Fact label={t('admin.institutions.fact.bank')}>
                                    {[
                                        account.bank_name,
                                        account.account_name,
                                        account.account_number,
                                    ]
                                        .filter((part) => part !== '')
                                        .join(' · ')}
                                </Fact>
                            )}
                        </dl>
                    )}
                </Section>

                <Section title={t('admin.institutions.people')}>
                    <ul className={cn(BOX, 'overflow-hidden')}>
                        {institution.directors.map((person, index) => (
                            <li key={`d${index}`} className={ITEM}>
                                <span className="font-semibold text-rz-ink">
                                    {person.full_name}
                                </span>
                                <span className="ml-2 text-rz-muted">
                                    {t('admin.institutions.director')}
                                    {person.pep &&
                                        ` · ${t('admin.institutions.pep')}`}
                                </span>
                            </li>
                        ))}
                        {institution.owners.map((person, index) => (
                            <li key={`o${index}`} className={ITEM}>
                                <span className="font-semibold text-rz-ink">
                                    {person.full_name}
                                </span>
                                <span className="ml-2 text-rz-muted">
                                    {t('admin.institutions.owner', {
                                        percent: person.ownership_pct ?? '—',
                                    })}
                                    {person.pep &&
                                        ` · ${t('admin.institutions.pep')}`}
                                </span>
                            </li>
                        ))}
                        {institution.no_major_owner && (
                            <li className={cn(ITEM, 'text-rz-body')}>
                                {t('role.apply.people.no_major_owner')}
                            </li>
                        )}
                        {institution.directors.length === 0 &&
                            institution.owners.length === 0 &&
                            !institution.no_major_owner && (
                                <li className={cn(ITEM, 'text-rz-muted')}>
                                    {t('admin.institutions.people_empty')}
                                </li>
                            )}
                    </ul>
                </Section>

                <Section title={t('admin.institutions.representative')}>
                    <dl className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <Fact label={t('admin.institutions.fact.name')}>
                            {institution.account.name || '—'}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.email')}>
                            {institution.account.email}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.title')}>
                            {institution.representative_role?.title || '—'}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.kyc')}>
                            {t(
                                `admin.institutions.kyc.${
                                    (
                                        [
                                            'approved',
                                            'submitted',
                                            'rejected',
                                            'draft',
                                        ] as const
                                    ).find(
                                        (kyc) =>
                                            kyc === institution.account.kyc,
                                    ) ?? 'none'
                                }`,
                            )}
                        </Fact>
                        <Fact label={t('admin.institutions.fact.two_factor')}>
                            {t(
                                institution.account.two_factor
                                    ? 'admin.users.on'
                                    : 'admin.users.off',
                            )}
                        </Fact>
                        {institution.account.user_id !== null && (
                            <Fact label={t('admin.users.fact.user_id')}>
                                {institution.account.user_id}
                            </Fact>
                        )}
                    </dl>
                </Section>

                <Section title={t('admin.institutions.documents')}>
                    {institution.documents.length === 0 ? (
                        <Empty>{t('admin.institutions.documents_empty')}</Empty>
                    ) : (
                        <ul className={cn(BOX, 'overflow-hidden')}>
                            {institution.documents.map((document) => (
                                <li
                                    key={document.id}
                                    className={cn(
                                        ITEM,
                                        'flex justify-between gap-3',
                                    )}
                                >
                                    <a
                                        href={document.link.url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="truncate font-semibold text-rz-accent-app-text"
                                    >
                                        {document.filename}
                                    </a>
                                    <span className="shrink-0 text-[11.5px] text-rz-muted">
                                        {formatDate(
                                            document.uploaded_at,
                                            locale,
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>

                <Section title={t('admin.institutions.history')}>
                    <ol className={cn(BOX, 'overflow-hidden')}>
                        {institution.history.map((entry) => (
                            <li key={entry.revision} className={ITEM}>
                                <div className="flex justify-between gap-3">
                                    <span className="font-semibold text-rz-ink">
                                        {(
                                            [
                                                'draft',
                                                'submitted',
                                                'changes_requested',
                                                'approved',
                                                'rejected',
                                            ] as const
                                        ).includes(
                                            entry.status as InstitutionStatus,
                                        )
                                            ? t(
                                                  `admin.institutions.status.${entry.status as InstitutionStatus}`,
                                              )
                                            : entry.status}
                                    </span>
                                    <span className="shrink-0 text-[11.5px] text-rz-muted">
                                        {formatTimestamp(entry.at)}
                                    </span>
                                </div>
                                {entry.reason !== null &&
                                    entry.reason !== '' && (
                                        <div className="mt-0.5 text-[11.5px] text-rz-muted">
                                            {entry.reason}
                                        </div>
                                    )}
                            </li>
                        ))}
                        {institution.history.length === 0 && (
                            <li className={cn(ITEM, 'text-rz-muted')}>
                                {t('admin.institutions.history_empty')}
                            </li>
                        )}
                    </ol>
                </Section>
            </div>
        </Drawer>
    );
}
