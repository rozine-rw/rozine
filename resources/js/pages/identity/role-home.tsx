import { Head, Link, router, useHttp } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';
import { store } from '@/routes/identity/bookmarks';
import { edit } from '@/routes/profile';
import type {
    IdentityContext,
    MarketplaceRole,
    RoleBookmark,
    SaveRoleBookmarkInput,
} from '@/types/identity';

type Props = {
    identity: IdentityContext;
    role: MarketplaceRole;
    section: 'overview' | 'access';
};

export default function RoleHome({ identity, role, section }: Props) {
    const { t } = useTranslation();
    const request = useHttp<SaveRoleBookmarkInput, { data: RoleBookmark }>();
    const pending = useRef<SaveRoleBookmarkInput | null>(null);
    const [failed, setFailed] = useState(false);
    async function changeSection(next: 'overview' | 'access') {
        if (request.processing) {
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
                                disabled={request.processing}
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
                    <Link href={edit()}>{t('identity.home.settings')}</Link>
                </>
            )}
        </main>
    );
}
