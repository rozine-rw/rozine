import { BusinessShell } from '@/components/business/business-shell';
import { HomeBody } from '@/components/business/home/home-body';
import { useTranslation } from '@/hooks/use-translation';
import type { BusinessHomeProps } from '@/types/business';

/**
 * Business Home (MVP-BUSINESS-SCR-01, design L99–346): identity and rating, the live raise,
 * what needs doing today, the all-time record, every note, and the way into a new raise.
 */
export default function BusinessHome(props: BusinessHomeProps) {
    const { t } = useTranslation();

    return (
        <BusinessShell
            title={t('business.home.head_title')}
            tab="home"
            links={props.links}
        >
            <HomeBody {...props} />
        </BusinessShell>
    );
}
