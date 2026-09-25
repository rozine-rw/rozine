import { Link } from '@inertiajs/react';
import { OutcomeBanner } from '@/components/business/apply/outcome-banner';
import { useCreateApplication } from '@/components/business/use-create-application';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteAction, RouteLink } from '@/types';
import type {
    BusinessApplications,
    BusinessApplicationSummary,
} from '@/types/business';

/** The saved pointers the Apply wizard names; any other is left unnamed rather than guessed. */
const NAMED_STEPS = ['business', 'raise', 'review'] as const;

const isNamedStep = (step: string): step is (typeof NAMED_STEPS)[number] =>
    (NAMED_STEPS as readonly string[]).includes(step);

/** "Apply for a raise": `application.create` for this business, then the server's `next`. */
function StartApplication({
    action,
    operation,
    identityContextRevision,
}: {
    action: RouteAction;
    operation: RouteLink;
    identityContextRevision: number;
}) {
    const { t } = useTranslation();
    const command = useCreateApplication({
        action,
        operation,
        identity_context_revision: identityContextRevision,
        expected_revision: 0,
    });

    return (
        <div className="flex flex-col gap-3">
            <Button
                className="self-start"
                disabled={command.busy || command.unresolved}
                aria-busy={command.busy || undefined}
                onClick={command.start}
            >
                {command.busy
                    ? t('business.grow.starting')
                    : t('business.grow.apply')}
            </Button>
            {command.notice && (
                <OutcomeBanner
                    notice={command.notice}
                    busy={command.busy}
                    onCheckAgain={command.checkAgain}
                    onRetry={command.retry}
                />
            )}
        </div>
    );
}

/** A draft resumes at its saved step; a submitted application is read-only. */
function ApplicationLink({
    application,
}: {
    application: BusinessApplicationSummary;
}) {
    const { t } = useTranslation();
    const { step } = application;

    if (application.status === 'submitted') {
        return (
            <div className="flex flex-col gap-1">
                <p className="text-sm text-muted-foreground">
                    {t('business.entries.submitted')}
                </p>
                <Link href={application.link} className="font-medium">
                    {t('business.entries.view')}
                </Link>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-1">
            {isNamedStep(step) && (
                <p className="text-sm text-muted-foreground">
                    {t('business.entries.saved_at', {
                        step: t(`business.apply.${step}.title`),
                    })}
                </p>
            )}
            <Link href={application.link} className="font-medium">
                {t('business.entries.continue')}
            </Link>
        </div>
    );
}

/**
 * The Business role landing page's way into Apply (#96): each business the person may act for,
 * with its application, or a way to start one when the server allows `application.create`. It
 * carries no financial facts, so none are shown or stood in for.
 */
export function BusinessApplicationEntries({
    applications,
}: {
    applications: BusinessApplications;
}) {
    const { t } = useTranslation();

    return (
        <section
            aria-labelledby="business-applications"
            className="flex flex-col gap-3"
        >
            <h2 id="business-applications" className="text-lg font-semibold">
                {t('business.entries.title')}
            </h2>
            {applications.entries.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('business.entries.empty')}
                </p>
            ) : (
                <ul className="flex flex-col gap-3">
                    {applications.entries.map((entry) => {
                        /* A start is offered only when the server allows it and supplies its route. */
                        const create = entry.allowed_actions.includes(
                            'application.create',
                        )
                            ? entry.actions.create
                            : null;

                        return (
                            <li
                                key={entry.business_id}
                                aria-labelledby={`business-entry-${entry.business_id}`}
                                className="flex flex-col gap-3 rounded-lg border p-4"
                            >
                                <h3
                                    id={`business-entry-${entry.business_id}`}
                                    className="font-medium"
                                >
                                    {entry.name}
                                </h3>
                                {entry.application !== null ? (
                                    <ApplicationLink
                                        application={entry.application}
                                    />
                                ) : create !== null ? (
                                    <StartApplication
                                        action={create}
                                        operation={applications.operation}
                                        identityContextRevision={
                                            applications.identity_context_revision
                                        }
                                    />
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        {t('business.entries.view_only')}
                                    </p>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
            {applications.pagination.next !== null && (
                <Link
                    href={applications.pagination.next}
                    className="self-start font-medium"
                >
                    {t('business.entries.more')}
                </Link>
            )}
        </section>
    );
}
