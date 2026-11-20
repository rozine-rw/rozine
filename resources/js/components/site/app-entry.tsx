import { useTranslation } from '@/hooks/use-translation';
import { dashboard, login, register } from '@/routes';

type AppEntryProps = {
    enabled: boolean;
    signedIn: boolean;
    /** `dark` when the links sit on a coloured header and need light text. */
    tone?: 'light' | 'dark';
};

const PILL = {
    padding: '7px 15px',
    borderRadius: '999px',
    fontSize: '13.5px',
    fontWeight: '600',
    whiteSpace: 'nowrap',
} as const;

/**
 * The way from the pre-launch site into the apps, locally and on staging and demo. A signed-in
 * visitor goes straight to the launcher; anyone else can sign in or create an account.
 */
export function AppEntry({ enabled, signedIn, tone = 'light' }: AppEntryProps) {
    const { t } = useTranslation();

    if (!enabled) {
        return null;
    }

    const onDark = tone === 'dark';
    const filled = onDark
        ? {
              background: 'transparent',
              color: '#fff',
              boxShadow: 'inset 0 0 0 1px rgba(255,255,255,.55)',
          }
        : { background: '#1d9e75', color: '#fff' };

    return (
        <span
            role="group"
            aria-label={t('site.app_entry.label')}
            style={{ display: 'flex', alignItems: 'center', gap: '8px' }}
        >
            {signedIn ? (
                <a href={dashboard().url} style={{ ...PILL, ...filled }}>
                    {t('site.app_entry.open_app')}
                </a>
            ) : (
                <>
                    <a
                        href={login().url}
                        style={{ ...PILL, color: onDark ? '#fff' : '#0c1830' }}
                    >
                        {t('site.app_entry.sign_in')}
                    </a>
                    <a
                        href={register().url}
                        className="rz-app-entry-secondary"
                        style={{ ...PILL, ...filled }}
                    >
                        {t('site.app_entry.create_account')}
                    </a>
                </>
            )}
        </span>
    );
}
