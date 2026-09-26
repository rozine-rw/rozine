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
    DisputeRecord,
    disputeState,
} from '@/components/business/audit-cosign/dispute-record';
import { DisputeSheet } from '@/components/business/audit-cosign/dispute-sheet';
import type { DisputeDraft } from '@/components/business/audit-cosign/dispute-sheet';
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
import type { MessageCode } from '@/lib/i18n/types';
import { formatDayMonth } from '@/lib/rozine/format';
import type { RouteAction } from '@/types';
import type {
    AuditCosignCommand,
    AuditCosignOperationResource,
    AuditPublishedReason,
    BusinessAuditCosignPageProps,
} from '@/types/business-audit';

/** The outcome of the signature that completes the set and publishes the report. */
const PUBLISHED = 'REPORT_PUBLISHED';

/** A recorded dispute: the page continues at the server's `data.next`. */
const DISPUTED = 'REPORT_DISPUTED';

/** The fields each form marks inline; a server error for any other shows as a banner. */
const SHOWN_FIELDS = ['accepted', 'note'];
const DISPUTE_FIELDS = ['supporting_text', 'proof_files'];

/** Whether the open form marks this field inline; the dispute also marks each file's error. */
const shownIn = (disputing: boolean, field: string): boolean =>
    disputing
        ? DISPUTE_FIELDS.includes(field) || field.startsWith('proof_files.')
        : SHOWN_FIELDS.includes(field);

/**
 * Why a published report was published, as the business reads it. Only a signature is a
 * signature: automatic approval and a staff resolution record none.
 */
const PUBLISHED_COPY: Record<AuditPublishedReason, MessageCode> = {
    signed: 'business.audit_cosign.yours.published',
    auto_approved: 'business.audit_cosign.yours.published_auto',
    staff_resolved: 'business.audit_cosign.yours.published_staff',
};

/** What the page is about now, from the server's publication, dispute and co-sign facts. */
type Heading =
    | 'open'
    | 'signed'
    | 'auto_approved'
    | 'staff_resolved'
    | 'disputed'
    | 'amended'
    | 'dispute_closed'
    | 'unavailable';

const HEADING_TITLE: Record<Heading, MessageCode> = {
    open: 'business.audit_cosign.title',
    signed: 'business.audit_cosign.heading.signed',
    auto_approved: 'business.audit_cosign.heading.auto_approved',
    staff_resolved: 'business.audit_cosign.heading.staff_resolved',
    disputed: 'business.audit_cosign.heading.disputed',
    amended: 'business.audit_cosign.heading.amended',
    dispute_closed: 'business.audit_cosign.heading.dispute_closed',
    unavailable: 'business.audit_cosign.heading.unavailable',
};

/**
 * The co-sign heading and its "before you co-sign" lead stay only while co-signing the report is
 * open: a published report is headed by how it was published, a disputed one by where the dispute
 * stands, and one that cannot be co-signed plainly as an audit report.
 */
const pageHeading = ({
    report,
    cosign,
}: BusinessAuditCosignPageProps): Heading => {
    if (report.published_at !== null) {
        return cosign.published_reason ?? 'signed';
    }

    if (cosign.dispute !== null) {
        switch (disputeState(cosign.dispute)) {
            case 'amended':
                return 'amended';
            case 'resolved':
            case 'upheld':
                return 'dispute_closed';
            default:
                return 'disputed';
        }
    }

    return cosign.state === 'unavailable' ? 'unavailable' : 'open';
};

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
 *
 * N6 (`cosign.policy_version` `monthly-review-2026-09-26`): one signatory signs off or disputes
 * within 24 hours of delivery, and an unsigned report is approved automatically at the end of the
 * window. A dispute is offered only when `allowed_actions` lists `report.dispute` and
 * `actions.dispute` is sent; it carries supporting text, proof files or both, as multipart through
 * the same command, and a recorded dispute follows `data.next`. Once the report is disputed the
 * page shows where the dispute stands and what was sent, and offers no action at all. Legacy
 * monthly reports keep their by-the-7th wording, and Flash reports their explicit signature.
 */
export default function BusinessAuditCosign(
    props: BusinessAuditCosignPageProps,
) {
    const { t, locale } = useTranslation();
    const { report, cosign, links } = props;
    /* A disputed report is signed or disputed no more, whatever else is sent. */
    const dispute = cosign.dispute;
    const cosignAction =
        dispute === null && props.allowed_actions.includes('report.cosign')
            ? props.actions.cosign
            : null;
    const disputeAction =
        dispute === null && props.allowed_actions.includes('report.dispute')
            ? props.actions.dispute
            : null;
    const [disputing, setDisputing] = useState(false);
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
        /* The lookup names the command and the identity context the page was read under. */
        lookupQuery: {
            identity_context_revision: props.identity_context_revision,
        },
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
                (recovered ||
                    resource.code === PUBLISHED ||
                    resource.code === DISPUTED)
            ) {
                router.visit(resource.data.next);

                return;
            }

            /* A dispute answered without a next page is read afresh, closing the sheet. */
            if (resource.code === DISPUTED) {
                readAfresh();

                return;
            }

            router.reload();
        },
        onRefused: (_sent, code, status) => {
            setDisputing(false);

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

    const disputeWith = (route: RouteAction, draft: DisputeDraft) => {
        command.send({
            name: 'report.dispute',
            route,
            payload: {
                request_id: crypto.randomUUID(),
                identity_context_revision: props.identity_context_revision,
                expected_revision: cosign.revision,
                report_revision: report.revision,
                mandate_version: cosign.mandate_version,
                digest: report.digest,
                ...(draft.supporting === ''
                    ? {}
                    : { supporting_text: draft.supporting }),
                ...(draft.files.length === 0
                    ? {}
                    : { proof_files: draft.files }),
            },
        });
    };

    const locked = command.busy || command.unresolved || refreshing;
    const you = cosign.signers.find((signer) => signer.is_you);
    const waitingFor = cosign.signers
        .filter((signer) => signer.state === 'pending' && !signer.is_you)
        .map((signer) => signer.name);
    /* A field error the form has no field for still reaches the business. */
    const unshownError = Object.entries(command.errors).find(
        ([field]) => !shownIn(disputing, field),
    )?.[1];
    const notice = command.notice && (
        <CosignNotice
            notice={command.notice}
            busy={command.busy}
            onCheckAgain={command.checkAgain}
            onRetry={command.retry}
        />
    );

    let yours;

    if (dispute !== null) {
        yours = <DisputeRecord dispute={dispute} />;
    } else if (cosignAction !== null) {
        yours = (
            <CosignForm
                initialNote={cosign.your_note}
                busy={command.busy}
                locked={locked}
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
                {t(PUBLISHED_COPY[cosign.published_reason ?? 'signed'])}
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

    const heading = pageHeading(props);

    return (
        <BusinessShell
            title={t(
                heading === 'open'
                    ? 'business.audit_cosign.head_title'
                    : 'business.audit_cosign.head_title_read',
            )}
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
                            {t(HEADING_TITLE[heading])}
                        </h1>
                    </div>
                    <p className="mt-3 text-[12.5px] leading-[1.55] text-rz-secondary">
                        {t(
                            heading === 'open'
                                ? 'business.audit_cosign.lead'
                                : 'business.audit_cosign.lead_read',
                        )}
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
                    {!disputing && notice}
                    <CosignStatus
                        report={report}
                        cosign={cosign}
                        serverTime={props.server_time}
                    />
                    <SectionLabel>
                        {t('business.audit_cosign.yours.title')}
                    </SectionLabel>
                    {unshownError !== undefined && !disputing && (
                        <div className="mt-[11px]">
                            <ErrorBanner>{unshownError}</ErrorBanner>
                        </div>
                    )}
                    {yours}
                    {disputeAction !== null && (
                        <button
                            type="button"
                            onClick={() => setDisputing(true)}
                            disabled={locked}
                            className="mt-3 h-11 w-full rounded-2xl border border-rz-border bg-rz-surface text-[14px] font-semibold text-rz-ink disabled:text-rz-secondary"
                        >
                            {t('business.audit_cosign.dispute.open')}
                        </button>
                    )}
                </section>
                {disputing && disputeAction !== null && (
                    <DisputeSheet
                        busy={command.busy}
                        locked={locked}
                        errors={command.errors}
                        notice={
                            <>
                                {notice}
                                {unshownError !== undefined && (
                                    <div className="mt-3">
                                        <ErrorBanner>
                                            {unshownError}
                                        </ErrorBanner>
                                    </div>
                                )}
                            </>
                        }
                        onSubmit={(draft) => disputeWith(disputeAction, draft)}
                        onClose={() => setDisputing(false)}
                    />
                )}
            </div>
        </BusinessShell>
    );
}
