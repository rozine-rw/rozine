import { AuditorShell } from '@/components/auditor/auditor-shell';
import {
    AuditorCommandProvider,
    useAuditorCommandCenter,
} from '@/components/auditor/commands';
import { HomeBody } from '@/components/auditor/home/home-body';
import { useTranslation } from '@/hooks/use-translation';
import type { AuditorHomeProps } from '@/types/auditor';

/**
 * Auditor Home (design L86–203): who the partner is and their standing, whether dispatch may offer
 * them work, what is nearby, what is on the clock, and what happened recently. A fact the server
 * cannot state — the balance, the licence expiry, a destination — reads as unavailable or is not
 * shown; the availability switch is the `availability.update` command.
 */
export default function AuditorHome(props: AuditorHomeProps) {
    const { t } = useTranslation();
    const center = useAuditorCommandCenter({
        page: props,
        lookup: props.links.operation,
        preview: props.preview_outcome,
    });

    return (
        <AuditorCommandProvider center={center}>
            <AuditorShell
                title={t('auditor.home.head_title')}
                tab="home"
                links={props.links}
                openJobs={props.nearby.count}
            >
                <HomeBody {...props} />
            </AuditorShell>
        </AuditorCommandProvider>
    );
}
