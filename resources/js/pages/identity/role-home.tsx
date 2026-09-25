import { Head, Link, router, useHttp } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { BusinessApplicationEntries } from '@/components/business/application-entries';
import { Button } from '@/components/ui/button';
import { useAccessRefresh } from '@/hooks/use-access-refresh';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';
import { store } from '@/routes/identity/bookmarks';
import { edit } from '@/routes/profile';
import type { BusinessApplications } from '@/types/business';
import type {
    IdentityContext,
    MarketplaceRole,
    RoleBookmark,
    SaveRoleBookmarkInput,
} from '@/types/identity';

/** The facts a focus, reconnect or visibility signal reads afresh. */
const REFRESHED_PROPS = ['identity', 'business_applications'];

type Props = {
    identity: IdentityContext;
    role: MarketplaceRole;
    section: 'overview' | 'access';
    /** The Business role's way into Apply; null (or absent) for every other role. */
    business_applications?: BusinessApplications | null;
};

export default function RoleHome({
    identity,
    role,
    section,
    business_applications: businessApplications = null,
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
