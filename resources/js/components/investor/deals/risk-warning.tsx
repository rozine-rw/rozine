import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import legal from '@/routes/legal';

/** The risk warning every listing carries (BRS v3.0 GEN-R03, PRO), linking to the Disclaimers that explain it. */
export function RiskWarning({ className }: { className?: string }) {
    const { t } = useTranslation();

    return (
        <p
            role="note"
            className={cn(
                'rounded-xl border border-rz-border bg-rz-surface px-3 py-2.5 text-xs leading-normal text-rz-secondary',
                className,
            )}
        >
            {t('legal.risk_warning')}{' '}
            <a
                href={legal.disclaimers().url}
                className="font-semibold text-rz-accent-app-text underline underline-offset-2"
            >
                {t('legal.risk_warning_link')}
            </a>
        </p>
    );
}
