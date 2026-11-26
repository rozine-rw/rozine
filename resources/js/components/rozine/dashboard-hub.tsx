import { Link } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { formatDate, formatDateTime } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import { verification as investorVerification } from '@/routes/investor';
import { show as roleApplication } from '@/routes/role-application';
import { edit as security } from '@/routes/security';
import type {
    HubAction,
    HubDone,
    HubNext,
    HubStep,
    OnboardingHub,
} from '@/types';

const PANEL =
    'rounded-[18px] border border-rz-hairline bg-rz-surface px-5 py-5 sm:px-6';
const SUPPORT = 'mailto:hello@rozine.rw';
/** Where "Later" remembers the urgent cards set aside, for this browser tab only. */
const LATER_KEY = 'rozine.hub.later';

const check = (
    <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2.4"
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden="true"
    >
        <path d="M20 6 9 17l-5-5" />
    </svg>
);

/** Where an action is done. */
export function actionHref(action: HubAction): string {
    switch (action.kind) {
        case 'kyc_changes':
        case 'kyc_start':
        case 'kyc_continue':
            return investorVerification().url;
        case 'two_factor':
            return security().url;
        default:
            return roleApplication().url;
    }
}

function roleKey<T extends string>(
    base: T,
    role: 'business' | 'auditor' | 'institution' | null,
) {
    return `${base}_${role ?? 'business'}` as const;
}

function actionTitle(action: HubAction): MessageCode {
    switch (action.kind) {
        case 'role_changes':
        case 'role_rejected':
        case 'role_continue':
            return `suite.hub.action.${roleKey(action.kind, action.role)}.title` as MessageCode;
        default:
            return `suite.hub.action.${action.kind}.title` as MessageCode;
    }
}

function actionBody(action: HubAction): MessageCode {
    if (action.kind === 'two_factor' && action.role === 'auditor') {
        return 'suite.hub.action.two_factor.body_auditor';
    }

    return `suite.hub.action.${action.kind}.body` as MessageCode;
}

const BUTTON: Record<HubAction['kind'], MessageCode> = {
    kyc_changes: 'suite.hub.button.fix',
    role_changes: 'suite.hub.button.fix',
    role_rejected: 'suite.hub.button.reapply',
    kyc_start: 'suite.hub.button.start',
    kyc_continue: 'suite.hub.button.continue',
    role_continue: 'suite.hub.button.continue',
    two_factor: 'suite.hub.button.setup',
};

function stepLabel(step: HubStep): MessageCode {
    if (step.key === 'role') {
        return roleKey(
            'suite.hub.step.role',
            step.role === 'investor' ? 'business' : step.role,
        );
    }

    if (step.key === 'open') {
        return `suite.hub.step.open_${step.role ?? 'investor'}` as MessageCode;
    }

    return `suite.hub.step.${step.key}` as MessageCode;
}

function nextCopy(
    key: HubNext,
    role: 'business' | 'auditor' | 'institution' | null,
    identityApproved: boolean,
): { title: MessageCode; body: MessageCode } {
    const keyed =
        key === 'role_send' || key === 'role_review' || key === 'role_switch'
            ? roleKey(`suite.hub.next.${key}`, role)
            : `suite.hub.next.${key}`;
    const waitsOnIdentity = key === 'role_send' || key === 'role_review';

    return {
        title: `${keyed}.title` as MessageCode,
        body: `${keyed}.${waitsOnIdentity && identityApproved ? 'body_approved' : 'body'}` as MessageCode,
    };
}

function doneLabel(done: HubDone): MessageCode {
    return done.key === 'role_sent' || done.key === 'role_approved'
        ? (`${roleKey(`suite.hub.done.${done.key}`, done.role)}` as MessageCode)
        : (`suite.hub.done.${done.key}` as MessageCode);
}

function Items({ action }: { action: HubAction }) {
    const { t } = useTranslation();

    if (action.items.length === 0) {
        return null;
    }

    return (
        <ul
            aria-label={t('suite.hub.items_label')}
            className="mt-2.5 flex flex-wrap gap-2"
        >
            {action.items.map((item) => (
                <li
                    key={item.key}
                    title={item.note || undefined}
                    className="rounded-lg border border-rz-warning-text/30 bg-rz-surface px-2.5 py-1 text-[12.5px] font-semibold text-rz-ink"
                >
                    {item.label}
                </li>
            ))}
        </ul>
    );
}

/** The greeting, the setup progress and the step strip at the top of the hub. */
export function HubOverview({ hub }: { hub: OnboardingHub }) {
    const { t, locale } = useTranslation();
    const done = hub.steps.filter((step) => step.state === 'done').length;
    const total = hub.steps.length;
    const urgent = hub.actions.filter((action) => action.urgent).length;
    const percent = Math.round((done / Math.max(total, 1)) * 100);
    const [today] = useState(() => new Date());

    return (
        <>
            <section className="flex flex-col gap-1.5">
                <p className="text-xs font-bold tracking-[.08em] text-rz-faint uppercase">
                    {new Intl.DateTimeFormat(locale, {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                    }).format(today)}
                </p>
                <h1 className="text-[28px] font-semibold tracking-[-0.02em] text-rz-ink sm:text-[32px]">
                    {hub.name === ''
                        ? t('suite.hub.greeting_plain')
                        : t('suite.hub.greeting', { name: hub.name })}
                </h1>
                <p className="text-[15px] text-rz-body">
                    {done === total
                        ? t('suite.hub.summary_complete')
                        : t('suite.hub.summary', { done, total })}{' '}
                    {urgent > 0 && (
                        <strong className="font-semibold text-rz-warning-text">
                            {urgent === 1
                                ? t('suite.hub.needs_one')
                                : t('suite.hub.needs_many', { count: urgent })}
                        </strong>
                    )}
                </p>
            </section>
            <section aria-labelledby="hub-setup" className={PANEL}>
                <div className="flex flex-wrap items-baseline justify-between gap-3">
                    <h2
                        id="hub-setup"
                        className="text-[15px] font-semibold text-rz-ink"
                    >
                        {t('suite.hub.setup.title')}
                    </h2>
                    <span className="text-[13px] text-rz-body">
                        {t('suite.hub.setup.percent', { percent })}
                    </span>
                </div>
                <div
                    role="progressbar"
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={percent}
                    aria-labelledby="hub-setup"
                    className="mt-3 h-1.5 overflow-hidden rounded-full bg-rz-surface-muted"
                >
                    <div
                        className="h-1.5 rounded-full bg-rz-accent-fill transition-[width] duration-500"
                        style={{ width: `${percent}%` }}
                    />
                </div>
                <ol className="mt-[18px] grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    {hub.steps.map((step, index) => (
                        <li
                            key={step.key}
                            data-state={step.state}
                            className="flex flex-col gap-1.5"
                        >
                            <span
                                className={cn(
                                    'flex size-7 items-center justify-center rounded-full text-[13px] font-bold',
                                    step.state === 'done' &&
                                        'bg-rz-positive-tint text-rz-positive',
                                    (step.state === 'attention' ||
                                        step.state === 'rejected') &&
                                        'bg-rz-warning-tint text-rz-warning-text',
                                    (step.state === 'review' ||
                                        step.state === 'current') &&
                                        'border-2 border-rz-accent-fill text-rz-accent-text',
                                    step.state === 'upcoming' &&
                                        'border-2 border-rz-border text-rz-faint',
                                )}
                            >
                                {step.state === 'done'
                                    ? check
                                    : step.state === 'attention' ||
                                        step.state === 'rejected'
                                      ? '!'
                                      : index + 1}
                            </span>
                            <span
                                className={cn(
                                    'text-sm font-semibold',
                                    step.state === 'upcoming'
                                        ? 'text-rz-slate'
                                        : 'text-rz-ink',
                                )}
                            >
                                {t(stepLabel(step))}
                            </span>
                            <span
                                className={cn(
                                    'text-xs',
                                    step.state === 'attention' ||
                                        step.state === 'rejected'
                                        ? 'font-semibold text-rz-warning-text'
                                        : 'text-rz-body',
                                )}
                            >
                                {step.state === 'done' && step.at !== null
                                    ? formatDate(step.at, locale)
                                    : t(`suite.hub.state.${step.state}`)}
                            </span>
                        </li>
                    ))}
                </ol>
            </section>
        </>
    );
}

/** Everything the person has to do, urgent first, then what happens after. */
export function HubWork({ hub }: { hub: OnboardingHub }) {
    const { t, locale } = useTranslation();
    const urgent = hub.actions.filter((action) => action.urgent).length;

    return (
        <>
            <section
                aria-labelledby="hub-needs"
                className={cn(PANEL, 'flex flex-col gap-3.5')}
            >
                <div className="flex items-center gap-2.5">
                    <h2
                        id="hub-needs"
                        className="text-[17px] font-semibold text-rz-ink"
                    >
                        {t('suite.hub.needs.title')}
                    </h2>
                    {hub.actions.length > 0 && (
                        <span
                            className={cn(
                                'rounded-full px-2 py-0.5 text-xs font-bold',
                                urgent > 0
                                    ? 'bg-rz-warning-tint text-rz-warning-text'
                                    : 'bg-rz-accent-soft text-rz-accent-text',
                            )}
                        >
                            {hub.actions.length}
                        </span>
                    )}
                </div>
                {hub.actions.length === 0 ? (
                    <p className="text-sm text-rz-body">
                        {t('suite.hub.needs.empty')}
                    </p>
                ) : (
                    hub.actions.map((action) => (
                        <article
                            key={action.id}
                            id={`hub-action-${action.kind}`}
                            className={cn(
                                'flex flex-wrap items-start gap-3.5 rounded-[14px] border px-4 py-4 sm:px-[18px]',
                                action.urgent
                                    ? 'border-rz-warning-text/30 bg-rz-warning-tint/40'
                                    : 'border-rz-hairline',
                            )}
                        >
                            <div className="min-w-0 flex-[1_1_260px]">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span
                                        className={cn(
                                            'text-[11px] font-bold tracking-[.06em] uppercase',
                                            action.urgent
                                                ? 'text-rz-warning-text'
                                                : 'text-rz-faint',
                                        )}
                                    >
                                        {t(
                                            action.urgent
                                                ? 'suite.hub.tag.urgent'
                                                : action.kind === 'two_factor'
                                                  ? 'suite.hub.tag.recommended'
                                                  : 'suite.hub.tag.todo',
                                        )}
                                    </span>
                                    {action.at && (
                                        <span className="text-xs text-rz-body">
                                            {formatDateTime(action.at, locale)}
                                        </span>
                                    )}
                                </div>
                                <h3 className="mt-1 text-base font-semibold text-rz-ink">
                                    {t(actionTitle(action), {
                                        company: action.company ?? '',
                                    })}
                                </h3>
                                <p className="mt-1.5 text-sm text-rz-body">
                                    {action.note
                                        ? `“${action.note}”`
                                        : t(actionBody(action))}
                                </p>
                                <Items action={action} />
                            </div>
                            <Link
                                href={actionHref(action)}
                                className={cn(
                                    'flex h-11 items-center self-center rounded-xl px-[18px] text-sm font-semibold',
                                    action.urgent ||
                                        action.kind !== 'two_factor'
                                        ? 'bg-rz-accent-fill text-white'
                                        : 'border border-rz-border text-rz-ink',
                                )}
                            >
                                {t(BUTTON[action.kind])}
                            </Link>
                        </article>
                    ))
                )}
            </section>
            <section aria-labelledby="hub-next" className={PANEL}>
                <h2
                    id="hub-next"
                    className="text-[17px] font-semibold text-rz-ink"
                >
                    {t('suite.hub.next.title')}
                </h2>
                <ol className="mt-3.5 flex flex-col gap-3.5">
                    {hub.next.map((key, index) => {
                        const copy = nextCopy(
                            key,
                            hub.role,
                            !hub.next.some((next) => next.startsWith('kyc_')),
                        );

                        return (
                            <li key={key} className="flex gap-3">
                                <span className="flex size-[26px] flex-none items-center justify-center rounded-full bg-rz-surface-muted text-[13px] font-bold text-rz-ink">
                                    {index + 1}
                                </span>
                                <div>
                                    <p className="text-[14.5px] font-semibold text-rz-ink">
                                        {t(copy.title, {
                                            company:
                                                hub.company ??
                                                t('suite.hub.your_business'),
                                        })}
                                    </p>
                                    <p className="mt-0.5 text-[13.5px] text-rz-body">
                                        {t(copy.body)}
                                    </p>
                                </div>
                            </li>
                        );
                    })}
                </ol>
            </section>
        </>
    );
}

/** What is done, the newest onboarding updates and where to get help. */
export function HubAside({ hub }: { hub: OnboardingHub }) {
    const { t, locale } = useTranslation();

    return (
        <>
            <section aria-labelledby="hub-done" className={PANEL}>
                <h2
                    id="hub-done"
                    className="text-[17px] font-semibold text-rz-ink"
                >
                    {t('suite.hub.done.title')}
                </h2>
                <ul className="mt-3 flex flex-col gap-2.5 text-sm">
                    {hub.done.map((done) => (
                        <li
                            key={done.key}
                            className="flex items-center gap-2.5 text-rz-ink"
                        >
                            <span className="flex text-rz-positive">
                                {check}
                            </span>
                            <span className="flex-1">{t(doneLabel(done))}</span>
                            {done.at && (
                                <span className="text-[12.5px] text-rz-body">
                                    {formatDate(done.at, locale)}
                                </span>
                            )}
                        </li>
                    ))}
                </ul>
            </section>
            <section aria-labelledby="hub-recent" className={PANEL}>
                <h2
                    id="hub-recent"
                    className="text-[17px] font-semibold text-rz-ink"
                >
                    {t('suite.hub.recent.title')}
                </h2>
                {hub.recent.length === 0 ? (
                    <p className="mt-2.5 text-sm text-rz-body">
                        {t('suite.hub.recent.empty')}
                    </p>
                ) : (
                    <ul className="mt-2.5 flex flex-col">
                        {hub.recent.map((notice) => (
                            <li
                                key={notice.id}
                                className="border-b border-rz-hairline py-2.5 last:border-b-0"
                            >
                                <div className="flex items-center gap-2">
                                    <span
                                        className={cn(
                                            'size-2 rounded-full',
                                            notice.read
                                                ? 'bg-rz-border'
                                                : 'bg-rz-accent-fill',
                                        )}
                                    />
                                    <span
                                        className={cn(
                                            'text-sm',
                                            notice.read
                                                ? 'font-medium text-rz-slate'
                                                : 'font-semibold text-rz-ink',
                                        )}
                                    >
                                        {t(
                                            `investor.notices.${notice.kind}.title` as MessageCode,
                                        )}
                                    </span>
                                </div>
                                <p className="mt-0.5 ml-4 text-[12.5px] text-rz-body">
                                    {formatDateTime(notice.created_at, locale)}{' '}
                                    · {t('suite.hub.recent.emailed')}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
            <section
                aria-labelledby="hub-help"
                className={cn(PANEL, 'bg-rz-surface-sunken')}
            >
                <h2
                    id="hub-help"
                    className="text-[15px] font-semibold text-rz-ink"
                >
                    {t('suite.hub.help.title')}
                </h2>
                <p className="mt-1.5 text-sm text-rz-body">
                    {t('suite.hub.help.body')}
                </p>
                <a
                    href={SUPPORT}
                    className="mt-1 block py-1.5 text-sm font-semibold text-rz-accent-text"
                >
                    {t('suite.hub.help.contact')}
                </a>
            </section>
        </>
    );
}

function readLater(): string[] {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const saved = JSON.parse(
            window.sessionStorage.getItem(LATER_KEY) ?? '[]',
        ) as unknown;

        return Array.isArray(saved)
            ? saved.filter((id): id is string => typeof id === 'string')
            : [];
    } catch {
        return [];
    }
}

function writeLater(ids: string[]) {
    try {
        window.sessionStorage.setItem(LATER_KEY, JSON.stringify(ids));
    } catch {
        // Storage blocked: "Later" still hides the card until the page reloads.
    }
}

/**
 * Urgent items as a card in the bottom-right corner (Erastus, 24 Nov 2026): one at a time with its
 * action buttons, paged when there are several. "Later" sets a card aside for this tab; a new
 * request from Compliance has a new id, so it shows again.
 */
export function UrgentFlashcards({ actions }: { actions: HubAction[] }) {
    const { t, locale } = useTranslation();
    const [later, setLater] = useState<string[]>(readLater);
    const [index, setIndex] = useState(0);

    const cards = actions.filter(
        (action) => action.urgent && !later.includes(action.id),
    );

    if (cards.length === 0) {
        return null;
    }

    const position = Math.min(index, cards.length - 1);
    const card = cards[position];
    const items = card.items.map((item) => item.label).join(', ');
    const page = (step: number) =>
        setIndex((position + step + cards.length) % cards.length);

    return (
        <aside
            role="alertdialog"
            aria-labelledby="hub-flash-title"
            aria-describedby="hub-flash-body"
            className="fixed right-4 bottom-4 z-40 w-[380px] max-w-[calc(100%-32px)] animate-rz-rise rounded-[18px] border border-rz-warning-text/30 bg-rz-surface px-[18px] pt-[18px] pb-4 shadow-[0_24px_60px_-24px_rgba(20,45,95,0.45)] sm:right-6 sm:bottom-6 dark:shadow-[0_24px_60px_-20px_rgba(0,0,0,0.8)]"
        >
            <div className="flex items-center gap-2">
                <span className="rounded-md bg-rz-warning-tint px-2 py-1 text-[11px] font-bold tracking-[.06em] text-rz-warning-text uppercase">
                    {t('suite.hub.flash.label')}
                </span>
                {card.at && (
                    <span className="text-xs text-rz-body">
                        {t('suite.hub.flash.source', {
                            time: formatDateTime(card.at, locale),
                        })}
                    </span>
                )}
                <span className="flex-1" />
                {cards.length > 1 && (
                    <>
                        <span className="text-xs text-rz-body">
                            {t('suite.hub.flash.position', {
                                index: position + 1,
                                total: cards.length,
                            })}
                        </span>
                        <button
                            type="button"
                            aria-label={t('suite.hub.flash.previous')}
                            onClick={() => page(-1)}
                            className="size-8 rounded-lg border border-rz-hairline text-rz-slate"
                        >
                            ‹
                        </button>
                        <button
                            type="button"
                            aria-label={t('suite.hub.flash.next')}
                            onClick={() => page(1)}
                            className="size-8 rounded-lg border border-rz-hairline text-rz-slate"
                        >
                            ›
                        </button>
                    </>
                )}
            </div>
            <h3
                id="hub-flash-title"
                className="mt-3 text-[16.5px] leading-[1.3] font-semibold text-rz-ink"
            >
                {t(
                    card.kind === 'kyc_changes'
                        ? 'suite.hub.flash.kyc_title'
                        : roleKey('suite.hub.flash.role_title', card.role),
                )}
            </h3>
            <p
                id="hub-flash-body"
                className="mt-1.5 text-sm leading-[1.45] text-rz-body"
            >
                {items !== ''
                    ? t('suite.hub.flash.body_items', { items })
                    : t('suite.hub.flash.body')}
            </p>
            <div className="mt-3.5 flex flex-wrap gap-2">
                <Link
                    href={actionHref(card)}
                    className="flex h-11 flex-auto items-center justify-center rounded-xl bg-rz-accent-fill px-4 text-sm font-semibold text-white"
                >
                    {t(BUTTON[card.kind])}
                </Link>
                <button
                    type="button"
                    onClick={() =>
                        document
                            .getElementById(`hub-action-${card.kind}`)
                            ?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center',
                            })
                    }
                    className="h-11 rounded-xl border border-rz-border px-3.5 text-sm font-semibold text-rz-ink"
                >
                    {t('suite.hub.flash.details')}
                </button>
                <button
                    type="button"
                    onClick={() => {
                        const next = [...later, card.id];
                        setLater(next);
                        writeLater(next);
                    }}
                    className="h-11 rounded-xl px-3 text-sm font-medium text-rz-body"
                >
                    {t('suite.hub.flash.later')}
                </button>
            </div>
        </aside>
    );
}

/** A small "Live" pill: the page updates by itself. */
export function LivePill(): ReactNode {
    const { t } = useTranslation();

    return (
        <span className="flex items-center gap-1.5 rounded-full bg-rz-positive-tint px-2.5 py-1 text-xs font-semibold text-rz-positive">
            <span className="size-[7px] rounded-full bg-rz-positive" />
            {t('suite.hub.live')}
        </span>
    );
}
