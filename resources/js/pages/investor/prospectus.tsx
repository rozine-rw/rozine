import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { InvestorShell } from '@/components/investor/investor-shell';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import type { ProspectusProfile } from '@/types/business';
import type { InvestorProspectusProps } from '@/types/investor';
import type { Money } from '@/types/money';

const CARD =
    'rounded-2xl border border-rz-border bg-rz-surface px-4 py-4 sm:px-5';

const TAG =
    'inline-flex shrink-0 rounded-[10px] bg-[rgba(29,158,117,.1)] px-2 py-[3px] text-[10.5px] font-bold text-rz-positive dark:bg-[rgba(63,205,160,.14)]';

const rwf = (amount: string): Money => ({ currency: 'RWF', amount });

function Part({
    id,
    title,
    children,
}: {
    id: string;
    title: string;
    children: ReactNode;
}) {
    return (
        <section aria-labelledby={`${id}-title`} className={CARD}>
            <h2
                id={`${id}-title`}
                className="text-[15px] font-semibold text-rz-ink"
            >
                {title}
            </h2>
            <div className="mt-2.5 flex flex-col gap-2.5 text-sm text-rz-ink">
                {children}
            </div>
        </section>
    );
}

function Row({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-3 border-b border-rz-divider py-2 last:border-b-0">
            <dt className="text-rz-secondary">{label}</dt>
            <dd className="text-right font-semibold tabular-nums">{value}</dd>
        </div>
    );
}

/**
 * The signed prospectus behind a deal (BRS v3.0 PRO-R04), opened from Read on the deal's
 * Prospectus card. It shows exactly the version the business's official representative signed,
 * with its fingerprint: the purpose, the terms and the 3% platform fee, the use of funds, the
 * CPA-verified figures beside the business's own forecast, management and risks. A line or month
 * backed by an uploaded quote or signed contract says so; the documents themselves stay private.
 */
export default function InvestorProspectus({
    deal,
    prospectus,
    inputs,
    facts,
    quoted_lines,
    contracted_months,
    links,
}: InvestorProspectusProps) {
    const { t, locale } = useTranslation();
    const roles: Record<ProspectusProfile['role'], string> = {
        representative: t('investor.prospectus.role.representative'),
        director: t('investor.prospectus.role.director'),
        owner: t('investor.prospectus.role.owner'),
        '': '',
    };

    return (
        <InvestorShell
            title={t('investor.prospectus.title', { name: deal.name })}
            tab="deals"
            links={links}
            showTabBar={false}
        >
            <div className="mx-auto flex w-full max-w-[760px] flex-col gap-4 px-4 py-5">
                <header>
                    <Link
                        href={links.back}
                        className="text-[13px] font-semibold text-rz-accent"
                    >
                        {t('investor.prospectus.back')}
                    </Link>
                    <h1 className="mt-1 text-xl font-semibold text-rz-ink">
                        {t('investor.prospectus.title', { name: deal.name })}
                    </h1>
                    <p className="mt-1 text-sm text-rz-secondary">
                        {t('investor.prospectus.signed', {
                            version: prospectus.version,
                            name: prospectus.signed_by,
                            date: formatDate(prospectus.signed_at, locale),
                        })}
                    </p>
                    <p className="mt-1 font-mono text-[11.5px] break-all text-rz-secondary">
                        {t('investor.prospectus.fingerprint', {
                            fingerprint: prospectus.fingerprint,
                        })}
                    </p>
                </header>

                <Part id="purpose" title={t('investor.prospectus.purpose')}>
                    <p className="whitespace-pre-line">{inputs.purpose}</p>
                </Part>

                <Part id="terms" title={t('investor.prospectus.terms')}>
                    <dl>
                        <Row
                            label={t('investor.prospectus.principal')}
                            value={formatRwf(rwf(facts.principal))}
                        />
                        <Row
                            label={t('investor.prospectus.fee')}
                            value={formatRwf(rwf(facts.platform_fee))}
                        />
                        <Row
                            label={t('investor.prospectus.net')}
                            value={formatRwf(rwf(facts.net_of_fee))}
                        />
                        <Row
                            label={t('investor.prospectus.term')}
                            value={t('investor.prospectus.months', {
                                count: facts.term_months,
                            })}
                        />
                        <Row
                            label={t('investor.prospectus.instalment')}
                            value={formatRwf(rwf(facts.instalment))}
                        />
                    </dl>
                </Part>

                <Part id="funds" title={t('investor.prospectus.funds')}>
                    <ul className="flex flex-col">
                        {inputs.use_of_funds.map((line, index) => (
                            <li
                                key={index}
                                className="flex items-center justify-between gap-3 border-b border-rz-divider py-2 last:border-b-0"
                            >
                                <span className="min-w-0">
                                    {line.label}
                                    {quoted_lines.includes(index) ? (
                                        <span className={`ml-2 ${TAG}`}>
                                            {t('investor.prospectus.quoted')}
                                        </span>
                                    ) : null}
                                </span>
                                <span className="shrink-0 font-semibold tabular-nums">
                                    {formatRwf(rwf(line.amount))}
                                </span>
                            </li>
                        ))}
                    </ul>
                </Part>

                <Part
                    id="financials"
                    title={t('investor.prospectus.financials')}
                >
                    <dl>
                        <Row
                            label={t('investor.prospectus.verified_revenue')}
                            value={formatRwf(
                                rwf(facts.verified_monthly_revenue),
                            )}
                        />
                        <Row
                            label={t('investor.prospectus.sealed')}
                            value={
                                facts.latest_sealed_report_at === null
                                    ? '—'
                                    : formatDate(
                                          facts.latest_sealed_report_at,
                                          locale,
                                      )
                            }
                        />
                    </dl>
                    <h3 className="mt-1 text-[13px] font-semibold">
                        {t('investor.prospectus.forecast')}
                    </h3>
                    <table className="w-full text-[13px]">
                        <thead>
                            <tr className="text-left text-rz-secondary">
                                <th className="py-1 font-semibold">
                                    {t('investor.prospectus.month')}
                                </th>
                                <th className="py-1 text-right font-semibold">
                                    {t('investor.prospectus.revenue')}
                                </th>
                                <th className="py-1 text-right font-semibold">
                                    {t('investor.prospectus.costs')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {inputs.forecast.map((month) => (
                                <tr
                                    key={month.month}
                                    className="border-t border-rz-divider"
                                >
                                    <td className="py-1.5 tabular-nums">
                                        {month.month}
                                        {contracted_months.includes(
                                            month.month,
                                        ) ? (
                                            <span className={`ml-2 ${TAG}`}>
                                                {t(
                                                    'investor.prospectus.contracted',
                                                )}
                                            </span>
                                        ) : null}
                                    </td>
                                    <td className="py-1.5 text-right tabular-nums">
                                        {formatRwf(rwf(month.revenue))}
                                    </td>
                                    <td className="py-1.5 text-right tabular-nums">
                                        {formatRwf(rwf(month.costs))}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <h3 className="mt-1 text-[13px] font-semibold">
                        {t('investor.prospectus.assumptions')}
                    </h3>
                    <p className="whitespace-pre-line">{inputs.assumptions}</p>
                    <h3 className="mt-1 text-[13px] font-semibold">
                        {t('investor.prospectus.downside')}
                    </h3>
                    <p className="whitespace-pre-line">{inputs.downside}</p>
                </Part>

                <Part
                    id="management"
                    title={t('investor.prospectus.management')}
                >
                    {inputs.profiles.map((profile) => (
                        <div key={`${profile.name}|${profile.role}`}>
                            <p className="font-semibold">
                                {profile.name}{' '}
                                <span className="font-normal text-rz-secondary">
                                    · {roles[profile.role]}
                                </span>
                            </p>
                            <p className="mt-0.5 whitespace-pre-line text-rz-secondary">
                                {profile.text}
                            </p>
                        </div>
                    ))}
                </Part>

                <Part id="risks" title={t('investor.prospectus.risks')}>
                    {inputs.largest_customer_share_percent !== null ? (
                        <p className="text-rz-secondary">
                            {t('investor.prospectus.largest_customer', {
                                percent: inputs.largest_customer_share_percent,
                            })}
                        </p>
                    ) : null}
                    <ul className="flex list-disc flex-col gap-1.5 pl-5">
                        {inputs.risks.map((risk) => (
                            <li key={risk.text}>
                                {risk.text}
                                {risk.concentration ? (
                                    <span className="ml-2 text-[12px] font-semibold text-rz-warning-text">
                                        {t('investor.prospectus.concentration')}
                                    </span>
                                ) : null}
                            </li>
                        ))}
                    </ul>
                </Part>

                <Link
                    href={links.back}
                    className="self-start rounded-xl border border-rz-border px-4 py-2.5 text-[13.5px] font-semibold text-rz-accent"
                >
                    {t('investor.prospectus.back')}
                </Link>
            </div>
        </InvestorShell>
    );
}
