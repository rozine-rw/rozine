import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { formatDate, formatDateTime } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import { decide, document } from '@/routes/staff/role-applications';
import { index as users } from '@/routes/staff/users';
import type {
    RoleApplicationPerson,
    RoleApplicationReview,
} from '@/types/role-application';

const SECTION =
    'text-[12px] font-bold tracking-[.05em] text-[#636e86] uppercase dark:text-rz-muted';
const BOX = 'rounded-[13px] border border-rz-hairline bg-rz-surface';

type Decision = 'approve' | 'more' | 'reject';

function Fact({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="min-w-0 rounded-[11px] border border-rz-hairline bg-rz-surface px-3 py-2.5">
            <dt className="truncate text-[10.5px] font-semibold text-rz-muted">
                {label}
            </dt>
            <dd className="mt-[3px] text-[13px] font-bold break-words text-rz-ink">
                {children === '' || children === null ? '—' : children}
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

function People({
    title,
    people,
}: {
    title: string;
    people: RoleApplicationPerson[];
}) {
    const { t } = useTranslation();

    return (
        <div className={cn(BOX, 'mt-2 px-4 py-3')}>
            <p className="text-[12px] font-bold text-rz-ink">{title}</p>
            <ul className="mt-1.5 space-y-1.5">
                {people.map((person) => (
                    <li
                        key={`${person.id_type}:${person.id_number}`}
                        className="flex flex-wrap justify-between gap-2 text-[12px] text-rz-body"
                    >
                        <span className="font-semibold">
                            {person.full_name}
                            {person.pep && (
                                <span className="ml-1.5 rounded bg-[rgba(194,102,31,.14)] px-1.5 py-0.5 text-[10px] font-bold text-[#a3480a] dark:text-[#f0a060]">
                                    PEP
                                </span>
                            )}
                        </span>
                        <span className="text-rz-muted">
                            {t(`role.apply.people.${person.id_type}`)}{' '}
                            {person.id_number} · {person.nationality}
                            {person.ownership_pct !== undefined &&
                                ` · ${person.ownership_pct}%`}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/**
 * One role-switch application in the console drawer (Erastus, 23 Nov 2026): everything the
 * applicant sent, each document to open, what would stop the switch, and the decision. Approving a
 * business onboards it with the applicant as representative; approving an Auditor accredits them.
 * Either way the applicant's Investor role ends, since an account holds one role at a time.
 */
export function RoleApplicationDrawer({
    application,
    close,
}: {
    application: RoleApplicationReview;
    close: string;
}) {
    const { t, locale } = useTranslation();
    const errors = usePage().props.errors as Record<string, string>;
    const [note, setNote] = useState('');
    const [busy, setBusy] = useState<Decision | null>(null);
    const answers = application.state.answers;
    const name =
        application.account.name.trim() === ''
            ? application.account.email
            : application.account.name;
    const title =
        application.role === 'business'
            ? (answers.company?.legal_name ?? name)
            : name;
    const closeLink = { url: close, method: 'get' as const };
    const slotLabel = (slot: string): string => {
        if (slot.startsWith('id:')) {
            const person = application.people[slot.slice(3)];

            return t('role.apply.document.person_id', {
                name: person?.full_name ?? slot.slice(3),
            });
        }

        return t(`role.apply.document.${slot}` as MessageCode);
    };

    function send(decision: Decision) {
        setBusy(decision);
        router.post(
            decide(application.id).url,
            {
                decision,
                note,
                expected_revision: application.revision,
                request_id: crypto.randomUUID(),
            },
            { preserveScroll: true, onFinish: () => setBusy(null) },
        );
    }

    return (
        <Drawer
            label={t('admin.role_applications.drawer_label', { name: title })}
            close={closeLink}
        >
            <div className="shrink-0 border-b border-rz-hairline bg-rz-surface px-[18px] py-4">
                <div className="flex items-center gap-3">
                    <div className="min-w-0 flex-1">
                        <p className="text-[11px] font-bold tracking-[.05em] text-rz-muted uppercase">
                            {t(
                                `admin.role_applications.${application.role}.kicker`,
                            )}
                        </p>
                        <h2 className="truncate text-[17px] font-bold text-rz-ink">
                            {title}
                        </h2>
                        <p className="mt-0.5 truncate text-[12px] text-rz-muted">
                            {name} · {application.account.email}
                        </p>
                    </div>
                    <DrawerClose close={closeLink} />
                </div>
                <p className="mt-2 text-[12px] font-semibold text-rz-body">
                    {t(
                        `admin.role_applications.status.${application.status}` as MessageCode,
                    )}
                    {application.submitted_at !== null &&
                        ` · ${t('admin.role_applications.sent_on', {
                            date: formatDate(application.submitted_at, locale),
                        })}`}
                </p>
            </div>

            <div
                tabIndex={0}
                className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-[18px] pb-[26px]"
            >
                <Section title={t('admin.role_applications.identity')}>
                    <div
                        className={cn(
                            BOX,
                            'flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-[12.5px]',
                        )}
                    >
                        <span className="font-semibold text-rz-ink">
                            {t(
                                `admin.role_applications.kyc.${application.kyc}`,
                            )}
                        </span>
                        <a
                            href={
                                users({
                                    query: { q: application.account.email },
                                }).url
                            }
                            className="font-semibold text-rz-accent-app-text"
                        >
                            {t('admin.role_applications.open_kyc')}
                        </a>
                    </div>
                    {application.role === 'auditor' && (
                        <p className="mt-2 text-[12px] text-rz-muted">
                            {t(
                                application.account.two_factor
                                    ? 'admin.role_applications.two_factor_on'
                                    : 'admin.role_applications.two_factor_off',
                            )}
                        </p>
                    )}
                </Section>

                {application.role === 'business' && answers.company && (
                    <Section title={t('role.apply.step.company.title')}>
                        <dl className="grid grid-cols-2 gap-2">
                            <Fact label={t('role.apply.company.rdb_code')}>
                                {answers.company.rdb_code}
                            </Fact>
                            <Fact label={t('role.apply.company.tin')}>
                                {answers.company.tin}
                            </Fact>
                            <Fact label={t('role.apply.company.company_type')}>
                                {t(
                                    `role.apply.company_type.${answers.company.company_type}` as MessageCode,
                                )}
                            </Fact>
                            <Fact label={t('role.apply.company.incorporated')}>
                                {answers.company.incorporation_date}
                            </Fact>
                            <Fact label={t('role.apply.company.sector')}>
                                {answers.company.industry}
                            </Fact>
                            <Fact label={t('role.apply.company.district')}>
                                {answers.company.district}
                            </Fact>
                            <Fact label={t('role.apply.company.address')}>
                                {answers.company.registered_address}
                            </Fact>
                            <Fact label={t('role.apply.company.phone')}>
                                {answers.company.phone}
                            </Fact>
                        </dl>
                    </Section>
                )}

                {application.role === 'business' && (
                    <Section title={t('role.apply.step.people.title')}>
                        {answers.representative && (
                            <p className="text-[12.5px] text-rz-body">
                                {t('admin.role_applications.representative', {
                                    name,
                                    title: answers.representative.title,
                                    phone: answers.representative.phone,
                                })}
                            </p>
                        )}
                        <People
                            title={t('role.apply.people.directors')}
                            people={answers.directors ?? []}
                        />
                        <People
                            title={t('role.apply.people.owners')}
                            people={answers.owners ?? []}
                        />
                    </Section>
                )}

                {application.role === 'auditor' && answers.licence && (
                    <Section title={t('role.apply.step.licence.title')}>
                        <dl className="grid grid-cols-2 gap-2">
                            <Fact label={t('role.apply.licence.membership')}>
                                {answers.licence.membership_number}
                            </Fact>
                            <Fact label={t('role.apply.licence.certificate')}>
                                {answers.licence.licence_number}
                            </Fact>
                            <Fact label={t('role.apply.licence.valid_until')}>
                                {formatDate(answers.licence.expires_on, locale)}
                            </Fact>
                            <Fact label={t('role.apply.licence.firm')}>
                                {answers.licence.firm}
                            </Fact>
                        </dl>
                        {answers.coverage && (
                            <p className="mt-2 text-[12.5px] text-rz-body">
                                {t('role.apply.coverage.districts')}:{' '}
                                {answers.coverage.districts.join(', ')}
                            </p>
                        )}
                    </Section>
                )}

                <Section title={t('role.apply.step.documents.title')}>
                    <ul className="space-y-2">
                        {application.documents
                            .filter((file) => file.current)
                            .map((file) => (
                                <li
                                    key={file.id}
                                    className={cn(
                                        BOX,
                                        'flex items-center justify-between gap-3 px-4 py-3',
                                    )}
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-[12.5px] font-bold text-rz-ink">
                                            {slotLabel(file.slot)}
                                        </p>
                                        <p className="truncate text-[11.5px] text-rz-muted">
                                            {file.filename}
                                        </p>
                                    </div>
                                    <a
                                        href={
                                            document(
                                                {
                                                    application: application.id,
                                                    document: file.id,
                                                },
                                                {
                                                    query: {
                                                        disposition: 'inline',
                                                    },
                                                },
                                            ).url
                                        }
                                        target="_blank"
                                        rel="noreferrer"
                                        className="shrink-0 rounded-[10px] border border-rz-hairline px-3 py-[7px] text-[12px] font-semibold text-rz-accent-app-text"
                                    >
                                        {t('role.apply.document.view')}
                                    </a>
                                </li>
                            ))}
                    </ul>
                </Section>

                {(application.blockers.length > 0 ||
                    application.problems.length > 0) && (
                    <Section
                        title={t('admin.role_applications.before_approval')}
                    >
                        <ul className="list-disc space-y-1 rounded-[13px] border border-[#f1d9bd] bg-[#fff8ef] py-3 pr-4 pl-8 text-[12.5px] text-rz-body dark:border-[rgba(255,180,100,.3)] dark:bg-[rgba(194,102,31,.12)]">
                            {application.blockers.map((code) => (
                                <li key={code}>
                                    {t(
                                        `role.apply.blocker.${code}` as MessageCode,
                                    )}
                                </li>
                            ))}
                            {application.problems.length > 0 && (
                                <li>
                                    {t('admin.role_applications.incomplete', {
                                        fields: application.problems.join(', '),
                                    })}
                                </li>
                            )}
                        </ul>
                    </Section>
                )}

                {application.allowed_actions.length > 0 && (
                    <Section title={t('admin.role_applications.decision')}>
                        <label
                            htmlFor="role-application-note"
                            className="text-[12px] font-semibold text-rz-body"
                        >
                            {t('admin.role_applications.note')}
                        </label>
                        <textarea
                            id="role-application-note"
                            value={note}
                            maxLength={1000}
                            rows={3}
                            onChange={(event) => setNote(event.target.value)}
                            className="mt-1.5 w-full rounded-[11px] border border-rz-hairline bg-rz-surface px-3 py-2.5 text-[13px] text-rz-ink outline-none"
                        />
                        {(errors.note || errors.form) && (
                            <p
                                role="alert"
                                className="mt-1.5 text-[12px] font-semibold text-[#b3352a] dark:text-[#ff6b6f]"
                            >
                                {errors.note ?? errors.form}
                            </p>
                        )}
                        <div className="mt-3 flex flex-wrap gap-2">
                            <button
                                type="button"
                                disabled={busy !== null}
                                onClick={() => send('reject')}
                                className="rounded-[10px] border border-[#fdeaea] bg-rz-surface px-3.5 py-[9px] text-[12.5px] font-bold text-[#b3352a] disabled:opacity-60 dark:border-rz-danger-tint dark:text-[#ff6b6f]"
                            >
                                {t('admin.role_applications.reject')}
                            </button>
                            <button
                                type="button"
                                disabled={busy !== null}
                                onClick={() => send('more')}
                                className="rounded-[10px] border border-rz-hairline bg-rz-surface px-3.5 py-[9px] text-[12.5px] font-bold text-rz-slate disabled:opacity-60"
                            >
                                {t('admin.role_applications.more')}
                            </button>
                            <button
                                type="button"
                                disabled={busy !== null}
                                onClick={() => send('approve')}
                                className="flex-1 rounded-[10px] bg-[#17795a] px-4 py-[9px] text-[12.5px] font-bold text-white disabled:opacity-60"
                            >
                                {busy === 'approve'
                                    ? t('admin.role_applications.approving')
                                    : t(
                                          `admin.role_applications.${application.role}.approve`,
                                      )}
                            </button>
                        </div>
                        <p className="mt-2 text-[11.5px] text-rz-muted">
                            {t('admin.role_applications.note_hint')}
                        </p>
                    </Section>
                )}

                <Section title={t('admin.role_applications.history')}>
                    <ol className="space-y-1.5">
                        {[...application.history].reverse().map((entry) => (
                            <li
                                key={entry.revision}
                                className="text-[12px] text-rz-body"
                            >
                                <span className="font-semibold">
                                    {t(
                                        `admin.role_applications.status.${entry.status}` as MessageCode,
                                    )}
                                </span>{' '}
                                · {formatDateTime(entry.at, locale)}
                                {entry.reason !== null && (
                                    <span className="block text-rz-muted">
                                        {entry.reason}
                                    </span>
                                )}
                            </li>
                        ))}
                    </ol>
                </Section>
            </div>
        </Drawer>
    );
}
