import { Head, Link, router, useHttp } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { EngagementBanner } from '@/components/auditor/engagement/engagement-banner';
import { BusinessApplicationEntries } from '@/components/business/application-entries';
import { Button } from '@/components/ui/button';
import { useAccessRefresh } from '@/hooks/use-access-refresh';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';
import { store } from '@/routes/identity/bookmarks';
import { edit } from '@/routes/profile';
import type { RouteLink } from '@/types';
import type { EngagementSummary } from '@/types/auditor';
import type { BusinessApplications } from '@/types/business';
import type {
    IdentityContext,
    MarketplaceRole,
    RoleBookmark,
    SaveRoleBookmarkInput,
} from '@/types/identity';

/** The facts a focus, reconnect or visibility signal reads afresh, the engagement summary included. */
const REFRESHED_PROPS = ['identity', 'business_applications', 'engagement'];

type Props = {
    identity: IdentityContext;
    role: MarketplaceRole;
    section: 'overview' | 'access';
    /** The Business role's way into Apply; null (or absent) for every other role. */
    business_applications?: BusinessApplications | null;
    /**
     * The Auditor role's engagement summary; null for every other role. Like the Business entry
     * above, it is optional so a page rendered without it simply shows none.
     */
    engagement?: EngagementSummary | null;
    /**
     * The Auditor role's way into its work, as the Auditor shell's `links` carry them. Optional
     * until the server sends them on the role home; a destination it does not send is not shown.
     */
    links?: { jobs: RouteLink | null; profile: RouteLink | null } | null;
};

export default function RoleHome({
    identity,
    role,
    section,
    business_applications: businessApplications = null,
    engagement = null,
    links = null,
}: Props) {
    const { t } = useTranslation();
    const request = useHttp<SaveRoleBookmarkInput, { data: RoleBookmark }>();
    /* A withdrawn Business entry disappears on the same fresh read as a withdrawn role. */
    const refreshing = useAccessRefresh(REFRESHED_PROPS);
    const pending = useRef<SaveRoleBookmarkInput | null>(null);
    const [failed, setFailed] = useState(false);
    async function changeSection(next: 'overview' | 'access') {
        if (request.processing || refreshing) {
            return;
        }

        if (
            pending.current?.query.section !== next ||
            pending.current.expected_revision !== identity.context_revision
        ) {
            pending.current = {
                role,
                route: `${role}.home`,
                parameters: {},
                query: { section: next },
                expected_revision: identity.context_revision,
                request_id: crypto.randomUUID(),
            };
        }

        const payload = pending.current;
        request.transform(() => payload);

        try {
            const response = await request.submit(store());
            router.visit(response.data.url);
        } catch {
            setFailed(true);
        }
    }

    return (
        <main className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
            <Head title={t(`identity.home.${role}`)} />
            <Link href={dashboard()}>{t('identity.home.back')}</Link>
            <h1 className="text-2xl font-semibold">
                {t(`identity.home.${role}`)}
            </h1>
            {failed ? (
                <p role="alert">{t('identity.home.failed')}</p>
            ) : (
                <>
                    <nav
                        className="flex gap-3"
                        aria-label={t('identity.home.overview')}
                    >
                        {(['overview', 'access'] as const).map((tab) => (
                            <Button
                                key={tab}
                                variant={
                                    section === tab ? 'default' : 'outline'
                                }
                                disabled={request.processing || refreshing}
                                aria-pressed={section === tab}
                                onClick={() => void changeSection(tab)}
                            >
                                {t(`identity.home.${tab}`)}
                            </Button>
                        ))}
                    </nav>
                    <p role="status">
                        {request.processing
                            ? t('identity.home.saving')
                            : t(
                                  section === 'access'
                                      ? 'identity.home.verified'
                                      : 'identity.home.ready',
                              )}
                    </p>
                    <EngagementBanner engagement={engagement} />
                    {engagement !== null && (
                        <nav
                            aria-label={t('identity.home.auditor_nav')}
                            className="flex flex-wrap gap-x-5 gap-y-2"
                        >
                            {links?.jobs != null && (
                                <Link href={links.jobs}>
                                    {t('auditor.nav.jobs')}
                                </Link>
                            )}
                            {links?.profile != null && (
                                <Link href={links.profile}>
                                    {t('auditor.nav.profile')}
                                </Link>
                            )}
                            <Link href={engagement.link}>
                                {t('auditor.engagement.title')}
                            </Link>
                        </nav>
                    )}
                    {businessApplications !== null && (
                        <BusinessApplicationEntries
                            applications={businessApplications}
                        />
                    )}
                    <Link href={edit()}>{t('identity.home.settings')}</Link>
                </>
            )}
        </main>
    );
}
