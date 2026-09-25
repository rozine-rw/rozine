import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    carriedRefusal,
    carryRefusal,
    clearCarriedRefusal,
} from '@/components/business/apply/carried-refusal';
import { refusalRefreshes } from '@/components/business/apply/operation-outcome';
import { CosignForm } from '@/components/business/audit-cosign/cosign-form';
import { CosignNotice } from '@/components/business/audit-cosign/cosign-notice';
import { CosignStatus } from '@/components/business/audit-cosign/cosign-status';
import {
    ReportSummary,
    SectionLabel,
} from '@/components/business/audit-cosign/report-summary';
import { BusinessShell } from '@/components/business/business-shell';
import { ErrorBanner } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import {
    reloadPreservingState,
    useOperationCommand,
} from '@/hooks/use-operation-command';
import { useTranslation } from '@/hooks/use-translation';
import { formatDayMonth } from '@/lib/rozine/format';
import type { RouteAction } from '@/types';
import type {
    AuditCosignCommand,
    AuditCosignOperationResource,
    BusinessAuditCosignPageProps,
} from '@/types/business-audit';

/** The outcome of the signature that completes the set and publishes the report. */
const PUBLISHED = 'REPORT_PUBLISHED';

/** The fields the form marks inline; a server error for any other shows as a banner. */
const SHOWN_FIELDS = ['accepted', 'note'];

function StateCard({ icon, children }: { icon: IconName; children: string }) {
    return (
        <div
            role="status"
            className="mt-[11px] flex items-start gap-3 rounded-2xl border border-[#dbe7ff] bg-rz-surface p-4 dark:border-rz-border"
        >
            <span className="flex size-[34px] shrink-0 items-center justify-center rounded-xl bg-rz-accent-soft text-base">
                <Icon name={icon} />
            </span>
            <p className="flex-1 text-xs leading-[1.55] text-rz-secondary">
                {children}
            </p>
        </div>
    );
}

/**
 * Co-sign a sealed audit report (business-audit-report-v1, delivery 3). The report is the
 * server's and immutable; the page shows it, where the signatures stand, and — only when
 * `allowed_actions` lists `report.cosign` — the current person's own co-signature.
 *
 * The co-signature goes through the shared operation command: an answer that is lost is looked up
 * at `links.operation` with the same `request_id`, never resent blindly. A completed signature
 * reloads the page for the server's current counts; the one that publishes the report, and any
 * recovered receipt, follows `data.next`. A stale revision, digest or mandate is read afresh with
 * a remount, so the acceptance starts unticked on the current facts, and its banner is carried
 * across that remount. A denial stays on the page as it is.
 */
export default function BusinessAuditCosign(
    props: BusinessAuditCosignPageProps,
) {
    const { t, locale } = useTranslation();
    const { report, cosign, links } = props;
    const cosignAction = props.allowed_actions.includes('report.cosign')
        ? props.actions.cosign
        : null;
    const shellLinks = props.shell_links ?? {
        home: links.close,
        launcher: links.close,
        reports: null,
        profile: null,
    };

    const [carried] = useState(() => carriedRefusal(window.location.href));

    useEffect(() => {
        clearCarriedRefusal();
    }, []);

    /** Set while a fresh read is pending: nothing is signed meanwhile. */
    const [refreshing, setRefreshing] = useState(false);

    /** A fresh, non-preserving visit: the page remounts on the server's current facts. */
    const readAfresh = () => {
        setRefreshing(true);
        router.visit(window.location.href, {
            preserveState: false,
            preserveScroll: true,
            replace: true,
            onFinish: () => setRefreshing(false),
        });
    };

    const command = useOperationCommand<
        AuditCosignCommand,
        AuditCosignOperationResource
    >({
        actions: (sent) => sent.route,
        lookup: links.operation,
        initial:
            carried === null
                ? undefined
                : {
                      held: null,
                      notice: {
                          kind: 'refused',
                          code: carried.code,
                          status: carried.status,
                      },
                  },
        refresh: () => reloadPreservingState(),
        onCompleted: (_sent, resource, { recovered }) => {
            if (
                resource.data !== null &&
                (recovered || resource.code === PUBLISHED)
            ) {
                router.visit(resource.data.next);

                return;
            }

            router.reload();
        },
        onRefused: (_sent, code, status) => {
            if (refusalRefreshes(code, status)) {
                carryRefusal({
                    url: window.location.href,
                    code,
                    status,
                    haltedKey: null,
                });
                readAfresh();
            }
        },
    });

    const cosignWith = (route: RouteAction, note: string) => {
        command.send({
            name: 'report.cosign',
            route,
            payload: {
                request_id: crypto.randomUUID(),
                identity_context_revision: props.identity_context_revision,
                expected_revision: cosign.revision,
                report_revision: report.revision,
                digest: report.digest,
                mandate_version: cosign.mandate_version,
                accepted: true,
                note,
            },
        });
    };

    const you = cosign.signers.find((signer) => signer.is_you);
    const waitingFor = cosign.signers
        .filter((signer) => signer.state === 'pending' && !signer.is_you)
        .map((signer) => signer.name);
    /* A field error the form has no field for still reaches the business. */
    const unshownError = Object.entries(command.errors).find(
        ([field]) => !SHOWN_FIELDS.includes(field),
    )?.[1];

    let yours;

    if (cosignAction !== null) {
        yours = (
            <CosignForm
                initialNote={cosign.your_note}
                busy={command.busy}
                locked={command.busy || command.unresolved || refreshing}
                errors={command.errors}
                onCosign={(note) => cosignWith(cosignAction, note)}
            />
        );
    } else if (cosign.state === 'unavailable') {
        yours = (
            <p className="mt-[11px] text-[12.5px] leading-normal text-rz-secondary">
                {t('business.audit_cosign.yours.unavailable')}
            </p>
        );
    } else if (report.published_at !== null) {
        yours = (
            <StateCard icon="check-badge">
                {t('business.audit_cosign.yours.published')}
            </StateCard>
        );
    } else if (you?.state === 'signed') {
        yours = (
            <StateCard icon="hourglass">
                {[
                    you.signed_at === null
                        ? t('business.audit_cosign.yours.signed')
                        : t('business.audit_cosign.yours.signed_on', {
                              date: formatDayMonth(you.signed_at, locale),
                          }),
                    waitingFor.length > 0
                        ? t('business.audit_cosign.yours.waiting', {
                              names: waitingFor.join(', '),
                          })
                        : t('business.audit_cosign.yours.all_in'),
                ].join(' ')}
            </StateCard>
        );
    } else {
        yours = (
            <StateCard icon="lock">
                {t('business.audit_cosign.yours.cannot')}
            </StateCard>
        );
    }

    return (
        <BusinessShell
            title={t('business.audit_cosign.head_title')}
            tab="reports"
            links={shellLinks}
            showTabBar={false}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+4px)] pb-10 lg:grid lg:h-full lg:min-h-0 lg:grid-cols-2 lg:gap-4 lg:px-5 lg:pt-4 lg:pb-5">
                <section
                    data-rzcol
                    aria-labelledby="audit-cosign-title"
                    className="rz-scroll lg:min-h-0 lg:overflow-y-auto lg:rounded-2xl lg:border lg:border-rz-border lg:bg-rz-surface lg:px-4 lg:pt-4 lg:pb-5"
                >
                    <div className="flex items-center gap-3">
                        <Link
                            href={links.close}
                            aria-label={t('business.audit_cosign.back')}
                            className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                        >
                            <span aria-hidden>←</span>
                        </Link>
                        <h1
                            id="audit-cosign-title"
                            className="text-xl font-semibold text-rz-ink"
                        >
                            {t('business.audit_cosign.title')}
                        </h1>
                    </div>
                    <p className="mt-3 text-[12.5px] leading-[1.55] text-rz-secondary">
                        {t('business.audit_cosign.lead')}
                    </p>
                    <ReportSummary
                        businessName={props.business.name}
                        report={report}
                    />
                </section>
                <section
                    data-rzcol
                    aria-label={t('business.audit_cosign.yours.title')}
                    className="rz-scroll lg:min-h-0 lg:overflow-y-auto lg:rounded-2xl lg:border lg:border-rz-border lg:bg-rz-surface lg:px-4 lg:pb-5"
                >
                    {command.notice && (
                        <CosignNotice
                            notice={command.notice}
                            busy={command.busy}
                            onCheckAgain={command.checkAgain}
                            onRetry={command.retry}
                        />
                    )}
                    <CosignStatus report={report} cosign={cosign} />
                    <SectionLabel>
                        {t('business.audit_cosign.yours.title')}
                    </SectionLabel>
                    {unshownError !== undefined && (
                        <div className="mt-[11px]">
                            <ErrorBanner>{unshownError}</ErrorBanner>
                        </div>
                    )}
                    {yours}
                </section>
            </div>
        </BusinessShell>
    );
}
