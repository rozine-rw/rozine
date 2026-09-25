import { Link, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { refusalRefreshes } from '@/components/business/apply/operation-outcome';
import { OutcomeBanner } from '@/components/business/apply/outcome-banner';
import { Icon } from '@/components/rozine/icon';
import {
    reloadPreservingState,
    useOperationCommand,
} from '@/hooks/use-operation-command';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import type { Money, RouteLink } from '@/types';
import type {
    CreateApplicationData,
    CreateApplicationEntry,
} from '@/types/business';
import type { OperationCommand, OperationResource } from '@/types/operation';

type GrowSectionProps = {
    headroom: Money | null;
    links: { rating: RouteLink; apply: RouteLink | null };
    createApplication: CreateApplicationEntry | null;
};

const CTA =
    'mt-2.5 flex h-[54px] w-full items-center justify-center gap-2 rounded-2xl bg-rz-accent-fill text-[15px] font-bold text-white lg:h-[46px]';

function ApplyLabel({ children }: { children: ReactNode }) {
    return (
        <>
            <span aria-hidden className="text-[19px] leading-none">
                +
            </span>
            {children}
        </>
    );
}

/**
 * Starts a raise with `application.create` when no draft is open (#96, option (a)): the common
 * command fields, an unknown outcome looked up before any resend, then the server's `next`. A
 * concurrent create completes as `APPLICATION_RESUMED` with the existing draft; both completed
 * codes simply follow `next`.
 */
function CreateApplication({ entry }: { entry: CreateApplicationEntry }) {
    const { t } = useTranslation();
    const command = useOperationCommand<
        OperationCommand<'create'>,
        OperationResource<CreateApplicationData>
    >({
        actions: { create: entry.action },
        lookup: entry.operation,
        lookupQuery: {
            identity_context_revision: entry.identity_context_revision,
        },
        refresh: reloadPreservingState,
        onCompleted: (_sent, resource) => {
            if (resource.data === null) {
                router.reload();

                return;
            }

            router.visit(resource.data.next);
        },
        onRefused: (_sent, code, status) => {
            if (refusalRefreshes(code, status)) {
                router.reload();
            }
        },
    });

    return (
        <>
            <button
                type="button"
                disabled={command.busy || command.unresolved}
                aria-busy={command.busy || undefined}
                onClick={() =>
                    command.send({
                        name: 'create',
                        payload: {
                            identity_context_revision:
                                entry.identity_context_revision,
                            expected_revision: entry.expected_revision,
                            request_id: crypto.randomUUID(),
                        },
                    })
                }
                className={`${CTA} disabled:opacity-60`}
            >
                <ApplyLabel>
                    {command.busy
                        ? t('business.grow.starting')
                        : t('business.grow.apply')}
                </ApplyLabel>
            </button>
            {command.notice && (
                <div className="mt-3">
                    <OutcomeBanner
                        notice={command.notice}
                        busy={command.busy}
                        onCheckAgain={command.checkAgain}
                        onRetry={command.retry}
                    />
                </div>
            )}
        </>
    );
}

/** "Grow" — headroom and the way into a new raise (design L331–341). */
export function GrowSection({
    headroom,
    links,
    createApplication,
}: GrowSectionProps) {
    const { t } = useTranslation();

    return (
        <section
            aria-labelledby="business-grow"
            className="px-5 pb-2 lg:px-[18px]"
        >
            <h2
                id="business-grow"
                className="mt-7 text-[17px] font-bold tracking-[-.2px] text-rz-ink lg:mt-[22px]"
            >
                {t('business.grow.title')}
            </h2>
            <p className="mt-0.5 text-xs text-rz-secondary lg:hidden">
                {t('business.grow.subtitle')}
            </p>

            {headroom !== null && (
                <Link
                    href={links.rating}
                    className="mt-[11px] flex w-full items-center gap-3.5 rounded-2xl border border-rz-border bg-rz-surface p-[17px] text-left lg:px-[15px] lg:py-[13px]"
                >
                    <span className="flex size-[46px] shrink-0 items-center justify-center rounded-xl bg-[rgba(58,99,184,.1)] text-[22px]">
                        <Icon name="trend-up" />
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="flex items-center gap-[7px] text-[10.5px] font-bold tracking-[.07em] text-rz-slate uppercase">
                            <span className="size-[7px] shrink-0 rounded-full bg-[#3a63b8]" />
                            {t('business.grow.headroom')}
                        </span>
                        <span className="mt-[3px] block text-xl font-bold tracking-[-.3px] text-[#1428a4] dark:text-rz-investor-text">
                            {formatRwf(headroom)}
                        </span>
                        <span className="mt-px block text-[11.5px] text-rz-secondary">
                            {t('business.grow.headroom_body')}
                        </span>
                    </span>
                    <span
                        aria-hidden
                        className="shrink-0 text-lg text-rz-secondary"
                    >
                        ›
                    </span>
                </Link>
            )}

            {links.apply !== null ? (
                <Link href={links.apply} className={CTA}>
                    <ApplyLabel>{t('business.grow.apply')}</ApplyLabel>
                </Link>
            ) : (
                createApplication !== null && (
                    <CreateApplication entry={createApplication} />
                )
            )}
        </section>
    );
}
