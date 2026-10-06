import { usePage } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';

export default function NonLiveNotice() {
    const { nonLiveEnvironment } = usePage().props;
    const { t } = useTranslation();

    if (nonLiveEnvironment !== 'demo' && nonLiveEnvironment !== 'uat') {
        return null;
    }

    const title = t(
        nonLiveEnvironment === 'demo' ? 'environment.demo' : 'environment.uat',
    );

    return (
        <aside
            role="note"
            aria-label={title}
            className="border-b border-border bg-primary px-4 py-3 text-primary-foreground"
        >
            <div className="mx-auto flex max-w-7xl flex-col gap-1 text-sm sm:flex-row sm:flex-wrap sm:items-baseline sm:gap-x-4">
                <strong className="shrink-0 font-semibold">{title}</strong>
                <p className="max-w-prose text-pretty">
                    {t('environment.synthetic_only')}
                </p>
            </div>
        </aside>
    );
}
