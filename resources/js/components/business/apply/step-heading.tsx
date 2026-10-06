import { useTranslation } from '@/hooks/use-translation';

/** "STEP n OF 3", the step title and its one-line brief (design L366–368). */
export function StepHeading({
    step,
    title,
    subtitle,
}: {
    step: 1 | 2 | 3;
    title: string;
    subtitle: string;
}) {
    const { t } = useTranslation();

    return (
        <>
            <p className="text-[13px] font-semibold tracking-[.06em] text-rz-accent-app-text uppercase">
                {t('business.apply.step_of', { step, total: 3 })}
            </p>
            <h2 className="mt-1.5 text-[21px] font-semibold text-rz-ink">
                {title}
            </h2>
            <p className="mt-1.5 text-[13.5px] text-rz-secondary">{subtitle}</p>
        </>
    );
}
