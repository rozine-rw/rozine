import { router } from '@inertiajs/react';
import { AuditorShell } from '@/components/auditor/auditor-shell';
import { useRefusalText } from '@/components/auditor/commands';
import {
    AcceptedCard,
    AcceptForm,
} from '@/components/auditor/engagement/acceptance';
import {
    TermsDocument,
    shortHash,
} from '@/components/auditor/engagement/terms-document';
import { ColumnPad, TabColumns } from '@/components/auditor/tab-columns';
import { CARD, EmptyState, ScreenTitle } from '@/components/auditor/ui';
import { ErrorBanner } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import { OperationNotice } from '@/components/rozine/operation-notice';
import {
    reloadPreservingState,
    useOperationCommand,
} from '@/hooks/use-operation-command';
import { useTranslation } from '@/hooks/use-translation';
import { refusalRefreshes } from '@/lib/rozine/operation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    AuditorEngagementProps,
    EngagementCommand,
    EngagementOperationResource,
    EngagementRelease,
} from '@/types/auditor';

/** Refusals fresh facts cannot change: a denial or a scoped not-found. */
const FINAL_REFUSALS: ReadonlySet<string> = new Set([
    'ACTION_FORBIDDEN',
    'NOT_FOUND',
]);

/** The engagement refusals with their own explanation; any other reads as an Auditor command's. */
const ENGAGEMENT_REFUSALS = [
    'AUDIT_ENGAGEMENT_VERSION_CONFLICT',
    'AUDIT_ENGAGEMENT_TERMS_REQUIRED',
] as const;

type EngagementRefusal = (typeof ENGAGEMENT_REFUSALS)[number];

const isEngagementRefusal = (code: string): code is EngagementRefusal =>
    (ENGAGEMENT_REFUSALS as readonly string[]).includes(code);

/**
 * The Auditor engagement terms (auditor-engagement-v1, #96): the platform Master Services
 * Agreement and the Agreed Procedures, each in full as retained, and the partner's explicit
 * acceptance. Accept is offered only when `allowed_actions` lists it, sends back the release
 * exactly as read, and goes through the shared operation command: an answer that is lost is
 * looked up at `links.operation` with the same `request_id`, never resent blindly.
 *
 * An acceptance, whether answered or recovered, is followed by a fresh read of `links.current`;
 * the page never shows an "accepted" state of its own. A version conflict or withdrawn terms
 * reload the page with the refusal still showing, and the acceptance form, keyed by release,
 * starts unticked again so a new release is read before it can be accepted.
 */
export default function AuditorEngagement(props: AuditorEngagementProps) {
    const { t, locale } = useTranslation();
    const commandRefusal = useRefusalText();
    const { release, acceptance } = props;
    const accept = props.allowed_actions.includes('audit.engagement.accept')
        ? props.actions.accept
        : null;
    const command = useOperationCommand<
        EngagementCommand,
        EngagementOperationResource
    >({
        actions: (sent) => sent.route,
        lookup: props.links.operation,
        lookupNamesCommand: false,
        initial:
            props.preview_outcome === undefined
                ? undefined
                : { held: null, notice: props.preview_outcome },
        refresh: () => reloadPreservingState(),
        onCompleted: () => router.visit(props.links.current),
        onRefused: (_sent, code, status) => {
            if (refusalRefreshes(code, status, FINAL_REFUSALS)) {
                router.reload();
            }
        },
    });
    /* A field error with no field on this page still reaches the partner, as a banner. */
    const unshownError = Object.entries(command.errors).find(
        ([field]) => field !== 'accepted',
    )?.[1];

    /** Accepts the release exactly as read: its id, revision and SHA-256 go back unchanged. */
    const send = (read: EngagementRelease, route: RouteAction) => {
        command.send({
            name: 'audit.engagement.accept',
            route,
            payload: {
                identity_context_revision: props.identity_context_revision,
                expected_revision: read.revision,
                release_id: read.id,
                sha256: read.sha256,
                accepted: true,
                request_id: crypto.randomUUID(),
            },
        });
    };

    const notice = command.notice && (
        <OperationNotice
            notice={command.notice}
            busy={command.busy}
            onCheckAgain={command.checkAgain}
            onRetry={command.retry}
            className="mt-3.5"
            copy={{
                refused: (code, status) =>
                    isEngagementRefusal(code)
                        ? t(`auditor.engagement.refused.${code}`)
                        : commandRefusal(code, status),
                title: (kind) => t(`auditor.command.${kind}.title`),
                body: (kind) => t(`auditor.command.${kind}.body`),
                checkAgain: t('auditor.command.check_again'),
                tryAgain: t('auditor.command.try_again'),
            }}
        />
    );

    let acceptanceArea;

    if (release === null) {
        acceptanceArea = null;
    } else if (accept !== null) {
        acceptanceArea = (
            <AcceptForm
                key={`${release.id}:${release.revision}`}
                busy={command.busy}
                locked={command.unresolved}
                acceptanceRequired={command.errors.accepted !== undefined}
                onAccept={() => send(release, accept)}
            />
        );
    } else if (acceptance !== null) {
        acceptanceArea = (
            <AcceptedCard release={release} acceptance={acceptance} />
        );
    } else {
        acceptanceArea = (
            <p className="mt-3.5 text-[12.5px] leading-[1.45] text-rz-secondary">
                {t('auditor.engagement.no_accept')}
            </p>
        );
    }

    return (
        <AuditorShell
            title={t('auditor.engagement.head_title')}
            tab="profile"
            links={props.links}
            openJobs={props.open_jobs}
        >
            <TabColumns
                left={
                    <ColumnPad side="left" tab>
                        <ScreenTitle
                            title={t('auditor.engagement.title')}
                            lead={t('auditor.engagement.lead')}
                        />
                        {release === null ? (
                            <>
                                {notice}
                                <EmptyState>
                                    <span className="block font-semibold text-rz-ink">
                                        {t('auditor.engagement.unavailable')}
                                    </span>
                                    <span className="mt-1 block">
                                        {t(
                                            'auditor.engagement.unavailable_body',
                                        )}
                                    </span>
                                </EmptyState>
                            </>
                        ) : (
                            <>
                                {release.synthetic && (
                                    <div
                                        role="note"
                                        className="mt-3.5 flex items-start gap-3 rounded-2xl border-[1.5px] border-dashed border-[#e0a33a] bg-rz-accent-soft p-[13px] dark:border-[rgba(240,160,96,.6)]"
                                    >
                                        <span className="mt-px shrink-0 text-[16px]">
                                            <Icon name="warning" tone="amber" />
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-[13.5px] font-bold text-rz-ink">
                                                {t(
                                                    'auditor.engagement.synthetic_title',
                                                )}
                                            </span>
                                            <span className="mt-0.5 block text-[12px] leading-[1.5] text-rz-ink">
                                                {t(
                                                    'auditor.engagement.synthetic_body',
                                                )}
                                            </span>
                                        </span>
                                    </div>
                                )}
                                {locale !== 'en' && (
                                    <p className="mt-3 text-[12px] leading-[1.45] text-rz-secondary">
                                        {t(
                                            'auditor.engagement.original_language',
                                        )}
                                    </p>
                                )}
                                <TermsDocument
                                    kind="master_services"
                                    document={release.documents.master_services}
                                    version={release.version}
                                />
                                <TermsDocument
                                    kind="agreed_procedures"
                                    document={
                                        release.documents.agreed_procedures
                                    }
                                    version={release.version}
                                />
                            </>
                        )}
                    </ColumnPad>
                }
                right={
                    release === null ? null : (
                        <ColumnPad side="right">
                            <h2 className="mt-6 text-[17px] font-bold text-rz-ink lg:mt-0">
                                {t('auditor.engagement.acceptance_title')}
                            </h2>
                            <div
                                className={cn(
                                    CARD,
                                    'mt-3.5 p-[13px] text-[12px] leading-[1.55] text-rz-secondary',
                                )}
                            >
                                <p className="font-semibold text-rz-ink">
                                    {t('auditor.engagement.release_meta', {
                                        version: release.version,
                                        procedure: release.procedure_version,
                                    })}
                                </p>
                                <p className="mt-0.5 break-all">
                                    {t('auditor.engagement.release_hash', {
                                        hash: shortHash(release.sha256),
                                    })}
                                </p>
                            </div>
                            {notice}
                            {unshownError !== undefined && (
                                <div className="mt-3.5">
                                    <ErrorBanner>{unshownError}</ErrorBanner>
                                </div>
                            )}
                            {acceptanceArea}
                        </ColumnPad>
                    )
                }
            />
        </AuditorShell>
    );
}
