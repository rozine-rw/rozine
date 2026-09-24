import { useState } from 'react';
import { Drawer, DrawerClose } from '@/components/admin/drawer';
import {
    avatarColor,
    formatTimestamp,
    initialOf,
} from '@/components/admin/format';
import { KYC_TONE } from '@/components/admin/parties/directory-table';
import { ReasonStage } from '@/components/admin/reason-stage';
import type { StageTone } from '@/components/admin/reason-stage';
import { TrailList } from '@/components/admin/trail-list';
import {
    Chip,
    EXPLAIN,
    MicroBadge,
    TONE_DOT,
    TONE_TEXT,
    useStatFormatter,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    PartyDetail,
    PartyListRow,
    StaffViewer,
    Tone,
} from '@/types/admin';

type Tab = 'overview' | 'activity' | 'controls';

type Command =
    | 'freeze'
    | 'release'
    | 'verify_kyc'
    | 'reject_kyc'
    | 'verify_licence'
    | 'reject_licence';

const COMMAND_TONE: Record<Command, StageTone> = {
    freeze: 'red',
    release: 'green',
    verify_kyc: 'green',
    reject_kyc: 'red',
    verify_licence: 'green',
    reject_licence: 'red',
};

const HEALTH_TONE: Record<PartyDetail['health'], Tone> = {
    active: 'green',
    healthy: 'green',
    watch: 'amber',
    distressed: 'red',
    kyc_pending: 'amber',
};

const SECTION =
    'text-[12px] font-bold tracking-[.05em] text-[#7b8699] uppercase dark:text-rz-muted';
const BOX = 'rounded-[13px] border border-rz-hairline bg-rz-surface';
const VERIFY =
    'h-[38px] flex-1 rounded-[10px] bg-[#1d9e75] text-[12.5px] font-bold text-white';
const REJECT =
    'h-[38px] flex-1 rounded-[10px] border border-[#fdeaea] bg-rz-surface text-[12.5px] font-bold text-[#e5484d] dark:border-[rgba(255,107,111,.3)] dark:text-[#ff6b6f]';

function ListDetail({ detail }: { detail: PartyListRow['detail'] }) {
    const { t } = useTranslation();

    switch (detail.kind) {
        case 'note_status':
            return <>{t(`admin.parties.note_status.${detail.value}`)}</>;
        case 'money':
            return <>{formatRwfShort(detail.value)}</>;
        case 'text':
            return <>{detail.value}</>;
    }
}

/**
 * The party 360 (design Entity drawer T3165–3378) for all four party types: figures, history,
 * and the controls a party needs — KYC or licence verification and an attributed, reasoned,
 * reversible freeze (MVP-ADMIN-SCR-08, AC-08). The design's message, edit, violation, feature
 * flag, risk tier and force-action controls are out of MVP scope and are left out.
 */
export function PartyDrawer({
    party,
    viewer,
}: {
    party: PartyDetail;
    viewer: StaffViewer;
}) {
    const { t, locale } = useTranslation();
    const format = useStatFormatter();
    const [tab, setTab] = useState<Tab>('overview');
    const [stage, setStage] = useState<{
        key: Command;
        action: RouteAction;
    } | null>(null);
    const frozen = party.freeze !== null;
    const overdue = party.kyc?.state === 'overdue';
    const open = (key: Command, action: RouteAction) =>
        setStage({ key, action });
    const {
        freeze,
        release,
        verify_kyc,
        reject_kyc,
        verify_licence,
        reject_licence,
    } = party.actions;

    const reasonStage = (keys: Command[]) =>
        stage !== null && keys.includes(stage.key) ? (
            <ReasonStage
                key={stage.key}
                title={t(`admin.parties.stage.${stage.key}.title`, {
                    name: party.name,
                })}
                body={t(`admin.parties.stage.${stage.key}.body`, {
                    app: t(`admin.parties.app.${party.kind}`),
                })}
                cta={t(`admin.parties.stage.${stage.key}.cta`)}
                placeholder={t(`admin.parties.stage.${stage.key}.placeholder`)}
                tone={COMMAND_TONE[stage.key]}
                action={stage.action}
                viewer={viewer}
                onCancel={() => setStage(null)}
            />
        ) : null;

    return (
        <Drawer
            label={t('admin.parties.drawer_label', { name: party.name })}
            close={party.links.close}
        >
            <div className="shrink-0 border-b border-rz-hairline bg-rz-surface px-[18px] pt-4">
                <div className="flex items-center gap-3">
                    <div
                        className="flex size-10 shrink-0 items-center justify-center rounded-[11px] text-[16px] font-bold text-white"
                        style={{
                            background:
                                party.kind === 'auditor'
                                    ? '#c2661f'
                                    : avatarColor(party.id),
                        }}
                    >
                        {initialOf(party.name)}
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            <h2 className="truncate text-[17px] font-bold text-rz-ink">
                                {party.name}
                            </h2>
                            <span className="flex shrink-0 items-center gap-[5px]">
                                <span
                                    className={cn(
                                        'size-[7px] rounded-full',
                                        TONE_DOT[HEALTH_TONE[party.health]],
                                    )}
                                />
                                <span
                                    className={cn(
                                        'text-[12px] font-bold',
                                        TONE_TEXT[HEALTH_TONE[party.health]],
                                    )}
                                >
                                    {t(`admin.parties.health.${party.health}`)}
                                </span>
                            </span>
                            {frozen && (
                                <MicroBadge>
                                    {t('admin.parties.badge.frozen')}
                                </MicroBadge>
                            )}
                            {overdue && (
                                <MicroBadge>
                                    {t('admin.parties.badge.kyc_overdue')}
                                </MicroBadge>
                            )}
                        </div>
                        <p className="mt-0.5 truncate text-[12px] text-[#7b8699] dark:text-rz-muted">
                            <span className="uppercase">
                                {t(`admin.parties.type.${party.kind}`)}
                            </span>{' '}
                            · {party.subtitle}
                        </p>
                    </div>
                    <DrawerClose close={party.links.close} />
                </div>
                <div
                    role="tablist"
                    aria-label={t('admin.parties.tabs')}
                    className="mt-3.5 flex gap-[22px]"
                >
                    {(['overview', 'activity', 'controls'] as const).map(
                        (key) => (
                            <button
                                key={key}
                                type="button"
                                role="tab"
                                aria-selected={tab === key}
                                onClick={() => setTab(key)}
                                className={cn(
                                    'relative pb-2.5 text-[12.5px] font-bold',
                                    tab === key
                                        ? 'text-rz-ink'
                                        : 'text-rz-muted',
                                )}
                            >
                                {t(`admin.parties.tab.${key}`)}
                                {tab === key && (
                                    <span className="absolute inset-x-0 -bottom-px h-0.5 rounded-sm bg-rz-accent-fill" />
                                )}
                            </button>
                        ),
                    )}
                </div>
            </div>

            <div
                role="tabpanel"
                className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-[18px] pb-[26px]"
            >
                {tab === 'overview' && (
                    <>
                        {party.freeze !== null && (
                            <p
                                role="status"
                                className="mb-3.5 rounded-xl border border-[#fdeaea] bg-[rgba(255,77,79,.06)] px-3.5 py-2.5 text-[12.5px] leading-[1.5] text-[#b3261e] dark:border-[rgba(255,107,111,.25)] dark:text-[#ff8285]"
                            >
                                {t('admin.parties.frozen_note', {
                                    actor: party.freeze.actor,
                                    at: formatTimestamp(party.freeze.at),
                                })}
                            </p>
                        )}
                        {overdue &&
                            party.kyc !== null &&
                            party.kyc.due_on !== null && (
                                <p
                                    role="status"
                                    className="mb-3.5 rounded-xl border border-[#f6e6cc] bg-[rgba(210,120,45,.08)] px-3.5 py-2.5 text-[12.5px] leading-[1.5] text-[#9e5a1c] dark:border-rz-border dark:text-[#f0a060]"
                                >
                                    {t('admin.parties.kyc_overdue_note', {
                                        date: formatDate(
                                            party.kyc.due_on,
                                            locale,
                                        ),
                                    })}
                                </p>
                            )}
                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            {party.stats.map((stat) => (
                                <div
                                    key={stat.key}
                                    className="min-w-0 rounded-[11px] border border-rz-hairline bg-rz-surface px-3 py-2.5"
                                >
                                    <div className="truncate text-[10.5px] font-semibold text-rz-muted">
                                        {t(`admin.parties.stat.${stat.key}`)}
                                    </div>
                                    <div className="mt-[3px] truncate text-[15px] font-bold text-rz-ink">
                                        {format(stat.value)}
                                    </div>
                                </div>
                            ))}
                        </div>
                        {party.list !== null && (
                            <>
                                <h3 className={cn(SECTION, 'mt-5 normal-case')}>
                                    {t(`admin.parties.list.${party.list.key}`)}
                                </h3>
                                <ul
                                    className={cn(
                                        BOX,
                                        'mt-2.5 overflow-hidden',
                                    )}
                                >
                                    {party.list.rows.length === 0 && (
                                        <li className="px-[15px] py-3 text-[12.5px] text-rz-faint">
                                            {t('admin.parties.list_empty')}
                                        </li>
                                    )}
                                    {party.list.rows.map((row) => (
                                        <li
                                            key={row.id}
                                            className="flex items-center gap-[11px] border-b border-[#eef2f8] px-[15px] py-3 last:border-b-0 dark:border-rz-divider"
                                        >
                                            <span
                                                className={cn(
                                                    'size-[7px] shrink-0 rounded-full',
                                                    TONE_DOT[row.tone],
                                                )}
                                            />
                                            <span className="flex-1 text-[13px] font-semibold text-rz-ink">
                                                {row.title}
                                            </span>
                                            <span className="text-[12px] text-rz-body">
                                                <ListDetail
                                                    detail={row.detail}
                                                />
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </>
                        )}
                    </>
                )}

                {tab === 'activity' && (
                    <TrailList
                        title={t('admin.parties.history')}
                        entries={party.history}
                        empty={t('admin.parties.history_empty')}
                    />
                )}

                {tab === 'controls' && (
                    <>
                        {party.kyc !== null && (
                            <section
                                aria-label={t('admin.parties.kyc_title')}
                                className="mb-5"
                            >
                                <h3 className={SECTION}>
                                    {t('admin.parties.kyc_title')}
                                </h3>
                                <div
                                    className={cn(BOX, 'mt-2.5 px-4 py-[15px]')}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-[13px] text-rz-slate">
                                            {t('admin.parties.kyc_current')}
                                        </span>
                                        <Chip tone={KYC_TONE[party.kyc.state]}>
                                            {t(
                                                `admin.parties.kyc.${party.kyc.state}`,
                                            )}
                                        </Chip>
                                    </div>
                                    {party.kyc.due_on !== null && (
                                        <p
                                            className={cn(
                                                'mt-2 text-[12px]',
                                                EXPLAIN,
                                            )}
                                        >
                                            {t('admin.parties.kyc_due', {
                                                date: formatDate(
                                                    party.kyc.due_on,
                                                    locale,
                                                ),
                                            })}
                                        </p>
                                    )}
                                    {(verify_kyc || reject_kyc) && (
                                        <div className="mt-3 flex gap-2">
                                            {verify_kyc && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        open(
                                                            'verify_kyc',
                                                            verify_kyc,
                                                        )
                                                    }
                                                    className={VERIFY}
                                                >
                                                    {t(
                                                        'admin.parties.verify_kyc',
                                                    )}
                                                </button>
                                            )}
                                            {reject_kyc && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        open(
                                                            'reject_kyc',
                                                            reject_kyc,
                                                        )
                                                    }
                                                    className={REJECT}
                                                >
                                                    {t('admin.parties.reject')}
                                                </button>
                                            )}
                                        </div>
                                    )}
                                    {reasonStage(['verify_kyc', 'reject_kyc'])}
                                </div>
                            </section>
                        )}

                        {party.licence !== null && (
                            <section
                                aria-label={t('admin.parties.licence_title')}
                                className="mb-5"
                            >
                                <h3 className={SECTION}>
                                    {t('admin.parties.licence_title')}
                                </h3>
                                <div
                                    className={cn(BOX, 'mt-2.5 px-4 py-[15px]')}
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <span className="text-[13px] text-rz-slate">
                                            {t('admin.parties.licence_status')}
                                        </span>
                                        <Chip
                                            tone={
                                                party.licence.state ===
                                                'verified'
                                                    ? 'green'
                                                    : party.licence.state ===
                                                        'pending'
                                                      ? 'amber'
                                                      : 'red'
                                            }
                                        >
                                            {t(
                                                `admin.parties.licence.${party.licence.state}`,
                                            )}
                                        </Chip>
                                    </div>
                                    <dl className="grid grid-cols-2 gap-[9px]">
                                        {(
                                            [
                                                [
                                                    'member_id',
                                                    party.licence.member_id,
                                                ],
                                                [
                                                    'licence',
                                                    party.licence.licence,
                                                ],
                                                [
                                                    'expires_on',
                                                    formatDate(
                                                        party.licence
                                                            .expires_on,
                                                        locale,
                                                    ),
                                                ],
                                                [
                                                    'district',
                                                    party.licence.district,
                                                ],
                                            ] as const
                                        ).map(([key, value]) => (
                                            <div
                                                key={key}
                                                className="rounded-[10px] border border-[#f0e6d4] bg-[#faf7f1] px-[11px] py-[9px] dark:border-rz-border dark:bg-rz-surface-sunken"
                                            >
                                                <dt className="text-[10px] font-bold text-[#a1875a] uppercase dark:text-[#f0a060]">
                                                    {t(
                                                        `admin.parties.licence_field.${key}`,
                                                    )}
                                                </dt>
                                                <dd className="mt-[3px] text-[12.5px] font-bold text-rz-ink">
                                                    {value}
                                                </dd>
                                            </div>
                                        ))}
                                    </dl>
                                    {(verify_licence || reject_licence) && (
                                        <div className="mt-3 flex gap-2">
                                            {verify_licence && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        open(
                                                            'verify_licence',
                                                            verify_licence,
                                                        )
                                                    }
                                                    className={VERIFY}
                                                >
                                                    {t(
                                                        'admin.parties.verify_licence',
                                                    )}
                                                </button>
                                            )}
                                            {reject_licence && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        open(
                                                            'reject_licence',
                                                            reject_licence,
                                                        )
                                                    }
                                                    className={REJECT}
                                                >
                                                    {t('admin.parties.reject')}
                                                </button>
                                            )}
                                        </div>
                                    )}
                                    {reasonStage([
                                        'verify_licence',
                                        'reject_licence',
                                    ])}
                                </div>
                            </section>
                        )}

                        <section
                            aria-label={t('admin.parties.account_state')}
                            className="border-t border-[#e3e9f3] pt-[18px] dark:border-rz-border"
                        >
                            <h3 className={cn(SECTION, 'mb-2.5')}>
                                {t('admin.parties.account_state')}
                            </h3>
                            {party.freeze !== null && (
                                <div className="mb-3 rounded-xl border border-[#fdeaea] bg-rz-surface px-3.5 py-3 dark:border-[rgba(255,107,111,.25)]">
                                    <div className="text-[13px] font-bold text-[#e5484d] dark:text-[#ff6b6f]">
                                        {t('admin.parties.frozen_by', {
                                            actor: party.freeze.actor,
                                            at: formatTimestamp(
                                                party.freeze.at,
                                            ),
                                        })}
                                    </div>
                                    {party.freeze.reason !== null && (
                                        <p
                                            className={cn(
                                                'mt-1 text-[12.5px] leading-[1.5]',
                                                EXPLAIN,
                                            )}
                                        >
                                            {t('admin.trail.reason', {
                                                reason: party.freeze.reason,
                                            })}
                                        </p>
                                    )}
                                </div>
                            )}
                            {party.release_blocked === 'LEGAL_HOLD' && (
                                <p className="mb-3 rounded-xl bg-[rgba(210,120,45,.08)] px-3.5 py-2.5 text-[12.5px] leading-[1.5] text-[#9e5a1c] dark:text-[#f0a060]">
                                    {t('admin.parties.legal_hold')}
                                </p>
                            )}
                            {stage === null && freeze && (
                                <button
                                    type="button"
                                    onClick={() => open('freeze', freeze)}
                                    className="h-[46px] w-full rounded-xl border border-[#fdeaea] bg-[rgba(255,77,79,.08)] text-[13.5px] font-bold text-[#e5484d] dark:border-[rgba(255,107,111,.3)] dark:text-[#ff6b6f]"
                                >
                                    {t('admin.parties.freeze')}
                                </button>
                            )}
                            {stage === null && release && (
                                <button
                                    type="button"
                                    onClick={() => open('release', release)}
                                    className="h-[46px] w-full rounded-xl border border-[#1d9e75] bg-[#1d9e75] text-[13.5px] font-bold text-white"
                                >
                                    {t('admin.parties.release')}
                                </button>
                            )}
                            {reasonStage(['freeze', 'release'])}
                            <p className="mt-2 text-[11.5px] leading-[1.5] text-rz-muted">
                                {t('admin.parties.freeze_caption', {
                                    app: t(`admin.parties.app.${party.kind}`),
                                })}
                            </p>
                            <TrailList
                                title={t('admin.parties.restrictions')}
                                entries={party.restrictions}
                                empty={t('admin.parties.restrictions_empty')}
                            />
                        </section>
                    </>
                )}
            </div>
        </Drawer>
    );
}
