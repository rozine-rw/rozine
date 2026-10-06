import { Head } from '@inertiajs/react';
import { Icon, IconGradients } from '@/components/rozine/icon';
import { LogoLockup } from '@/components/rozine/logo';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AuditVerifySealProps } from '@/types/audit-seal';

/**
 * The public, human-readable audit seal check: the same minimal facts the verification endpoint
 * returns — the report ID, its full digest, whether the seal checks out, and any amendment link
 * between reports. It is public, so it shows nothing about the Business, its signers, the note,
 * the findings or any document, and it draws no role shell.
 */
export default function AuditVerifySeal({ seal }: AuditVerifySealProps) {
    const { t } = useTranslation();
    const valid = seal.seal_status === 'valid';

    return (
        <main
            data-audience="investor"
            className="rz-surface min-h-svh bg-rz-page"
        >
            <Head title={t('audit.verify_seal.head_title')} />
            <IconGradients />
            <div className="mx-auto w-full max-w-[520px] px-4 pt-[calc(env(safe-area-inset-top)+20px)] pb-10 sm:pt-12">
                <LogoLockup
                    title={t('common.brand.name')}
                    className="h-7 w-auto text-rz-accent-lockup"
                />
                <h1 className="mt-7 text-2xl font-semibold text-rz-ink">
                    {t('audit.verify_seal.title')}
                </h1>
                <p className="mt-1.5 text-sm leading-[1.55] text-rz-secondary">
                    {t('audit.verify_seal.lead')}
                </p>

                <section
                    role="status"
                    className={cn(
                        'mt-5 flex items-start gap-3 rounded-2xl border p-4',
                        valid
                            ? 'border-[#cfe9d8] bg-[#f0f9f3] dark:border-transparent dark:bg-[rgba(63,205,160,.14)]'
                            : 'border-[#fbe4cc] bg-[#fff8f1] dark:border-transparent dark:bg-[rgba(194,102,31,.12)]',
                    )}
                >
                    <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-surface text-base">
                        <Icon
                            name={valid ? 'check-badge' : 'warning'}
                            tone={valid ? 'green' : 'amber'}
                        />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-[15px] font-bold text-rz-ink">
                            {t(
                                valid
                                    ? 'audit.verify_seal.valid'
                                    : 'audit.verify_seal.unavailable',
                            )}
                        </p>
                        <p className="mt-0.5 text-[12.5px] leading-normal text-rz-secondary">
                            {t(
                                valid
                                    ? 'audit.verify_seal.valid_body'
                                    : 'audit.verify_seal.unavailable_body',
                            )}
                        </p>
                    </div>
                </section>

                <dl className="mt-4 rounded-2xl border border-rz-border bg-rz-surface px-4 py-1">
                    <div className="border-b border-rz-divider py-3">
                        <dt className="text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                            {t('audit.verify_seal.report_id')}
                        </dt>
                        <dd
                            translate="no"
                            className="mt-1 text-sm font-semibold break-all text-rz-ink"
                        >
                            {seal.report_id}
                        </dd>
                    </div>
                    <div className="py-3">
                        <dt className="text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                            {t('audit.verify_seal.digest')}
                        </dt>
                        <dd
                            translate="no"
                            className="mt-1 font-mono text-[13px] leading-[1.55] break-all text-rz-ink"
                        >
                            {seal.digest}
                        </dd>
                    </div>
                </dl>

                {(seal.amends_id !== null || seal.amended_by !== null) && (
                    <ul className="mt-4 flex flex-col gap-2 text-[13px] leading-normal text-rz-ink">
                        {seal.amends_id !== null && (
                            <li className="rounded-xl border border-rz-border bg-rz-surface px-4 py-3 break-all">
                                {t('audit.verify_seal.amends', {
                                    id: seal.amends_id,
                                })}
                            </li>
                        )}
                        {seal.amended_by !== null && (
                            <li className="rounded-xl border border-rz-border bg-rz-surface px-4 py-3 break-all">
                                {t('audit.verify_seal.amended_by', {
                                    id: seal.amended_by,
                                })}
                            </li>
                        )}
                    </ul>
                )}

                <p className="mt-5 text-[12px] leading-[1.55] text-rz-secondary">
                    {t('audit.verify_seal.scope')}
                </p>
            </div>
        </main>
    );
}
