import { AdminFrame } from '@/components/admin/admin-frame';
import { CARD, CARD_SHADOW, EmptyState } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { AdminPendingSectionProps } from '@/types/admin';

/**
 * A design section whose screen is not wired yet: the console frame with the section's title and
 * an empty state, so every sidebar entry the design shows opens somewhere honest.
 */
export default function AdminPendingSection(props: AdminPendingSectionProps) {
    const { t } = useTranslation();

    return (
        <AdminFrame {...props}>
            <section
                aria-label={t(`admin.section.${props.section}.title`)}
                className={cn(CARD, CARD_SHADOW, 'rounded-2xl')}
            >
                <EmptyState
                    title={t('admin.pending.title')}
                    body={t('admin.pending.body')}
                />
            </section>
        </AdminFrame>
    );
}
