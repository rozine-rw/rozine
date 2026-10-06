import { useTranslation } from '@/hooks/use-translation';
import { dashboard, login, register } from '@/routes';

type AppEntryProps = {
    /** The isolated environment's profile (`uat`, `demo`), or null on the live site. */
    environment: string | null;
    signedIn: boolean;
};

const PILL = {
    padding: '7px 15px',
    borderRadius: '999px',
    fontSize: '13.5px',
    fontWeight: '600',
    whiteSpace: 'nowrap',
} as const;

/**
 * The way from the pre-launch site into the apps, on staging and demo only. A signed-in visitor
 * goes straight to the launcher; anyone else can sign in or create an account. The live site
 * passes no environment and keeps its pre-launch header unchanged.
 */
export function AppEntry({ environment, signedIn }: AppEntryProps) {
    const { t } = useTranslation();

    if (environment === null) {
        return null;
    }

    return (
        <span
            role="group"
            aria-label={t('site.app_entry.label')}
            style={{ display: 'flex', alignItems: 'center', gap: '8px' }}
        >
            {signedIn ? (
                <a
                    href={dashboard().url}
                    style={{ ...PILL, background: '#1d9e75', color: '#fff' }}
                >
                    {t('site.app_entry.open_app')}
                </a>
            ) : (
                <>
                    <a href={login().url} style={{ ...PILL, color: '#0c1830' }}>
                        {t('site.app_entry.sign_in')}
                    </a>
                    <a
                        href={register().url}
                        className="rz-app-entry-secondary"
                        style={{
                            ...PILL,
                            background: '#1d9e75',
                            color: '#fff',
                        }}
                    >
                        {t('site.app_entry.create_account')}
                    </a>
                </>
            )}
        </span>
    );
}
